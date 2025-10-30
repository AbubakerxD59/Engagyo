<?php

namespace App\Services;

use CURLFile;
use Exception;
use App\Models\Post;
use App\Services\HttpService;
use DirkGroenen\Pinterest\Pinterest;
use Illuminate\Support\Facades\Storage;
use GuzzleHttp\Exception\RequestException;
use App\Models\Pinterest as PinterestModel;

class PinterestService
{
    private $pinterest;
    private $client;
    private $post;
    private $auth;
    private $header;
    private $scopes;
    private $baseUrl = "https://api.pinterest.com/v5/";
    public function __construct()
    {
        $this->scopes = ['user_accounts:read', 'boards:read', 'pins:read', 'boards:write', 'pins:write'];
        $pinterest_id = env("PINTEREST_KEY");
        $pinterest_secret = env("PINTEREST_SECRET");
        $this->pinterest = new Pinterest($pinterest_id, $pinterest_secret);
        $this->client = new HttpService();
        $this->post = new Post();
        $this->auth = base64_encode("{$pinterest_id}:{$pinterest_secret}");
        $this->header = array("Content-Type" => "application/x-www-form-urlencoded", "Authorization" => "Basic " . $this->auth);
    }

    public function getLoginUrl()
    {
        $url = $this->pinterest->auth->getLoginUrl(route("pinterest.callback"), $this->scopes);
        return $url;
    }

    public function getOauthToken($code = null)
    {
        $data = array(
            "grant_type" => "authorization_code",
            "code" => (string) $code,
            "redirect_uri" => route("pinterest.callback"),
            "continuous_refresh" => true,
        );
        $token = $this->client->post($this->baseUrl . "oauth/token", $data,  $this->header);
        return $token;
    }

    public function refreshAccessToken($refresh_token, $pinterest_id)
    {
        $data = array(
            "refresh_token" => $refresh_token,
            "grant_type" => "refresh_token",
        );
        $token = $this->client->post($this->baseUrl . "oauth/token", $data,  $this->header);
        if ($token) {
            $pinterest = PinterestModel::find($pinterest_id);
            $pinterest->update([
                "access_token" => $token["access_token"],
                "expires_in" => $token["expires_in"],
                "refresh_token" => $token["refresh_token"],
                "refresh_token_expires_in" => $token["refresh_token_expires_in"],
            ]);
        }
        return $token;
    }

    public function me($access_token)
    {
        $this->header = array("Content-Type" => "application/json", "Authorization" => "Bearer  " . $access_token);
        $me = $this->client->get($this->baseUrl . "user_account", [], $this->header);
        return $me;
    }

    public function getBoards($access_token)
    {
        $this->header = array("Content-Type" => "application/json", "Authorization" => "Bearer  " . $access_token);
        $boards = $this->client->get($this->baseUrl . "boards", [], $this->header);
        return $boards;
    }

    public function create($id, $post, $access_token)
    {
        $this->header = array("Content-Type" => "application/json", "Authorization" => "Bearer  " . $access_token);
        $publish = $this->client->postJson($this->baseUrl . "pins", $post, $this->header);
        $post = $this->post->find($id);
        if (isset($publish['id'])) {
            $post->update([
                "post_id" => $publish["id"],
                "status" => 1,
                "response" => "Published Successfully!"
            ]);
        } else {
            $post->update([
                "status" => -1,
                "response" => json_encode($publish)
            ]);
        }
    }

    public function video($id, $post, $access_token)
    {
        $this->header = array("Content-Type" => "application/json", "Authorization" => "Bearer  " . $access_token);
        $post_row = Post::find($id);
        // step 1
        $response = $this->postIntent();
        if (isset($response["media_id"])) {
            $media_id = $response["media_id"];
            // step 2
            $file = $this->saveFileFromAws($post["video_key"]);
            if ($file["success"]) {
                // step 3
                $media_upload = $this->uploadToUrl($response, $file["fullPath"]);
                // step 4
                $media_status = $this->getUploadedMedia($media_id);
                if ($media_status["success"]) {
                    // step 5
                    $upload_video = $this->uploadVideo($post, $media_id);
                    if (isset($upload_video["id"])) {
                        $post_row->update([
                            "post_id" => $upload_video["id"],
                            "status" => 1,
                            "message" => "Published Successfully!"
                        ]);
                    } else {
                        $post_row->update([
                            "status" => -1,
                            "message" => json_encode($upload_video)
                        ]);
                    }
                } else {
                    $post_row->update([
                        "status" => -1,
                        "message" => $media_status["message"]
                    ]);
                }
            } else {
                $post_row->update([
                    "status" => -1,
                    "message" => $file["message"]
                ]);
            }
        }
    }
    private function postIntent()
    {
        $response = $this->client->postJson($this->baseUrl . "media", ['media_type' => 'video'], $this->header);
        return $response;
    }
    private function saveFileFromAws($video_key)
    {
        try {
            $fileContents = Storage::disk("s3")->get($video_key);
            $localPublicPath = 'uploads/videos/' . basename($video_key);
            $fullPath = public_path($localPublicPath);
            $directory = dirname($fullPath);
            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }
            $bytesWritten = file_put_contents($fullPath, $fileContents);
            return array(
                "success" => true,
                "directory" => $directory,
                "fullPath" => $fullPath
            );
        } catch (Exception $e) {
            return array(
                "success" => false,
                "message" => $e->getMessage()
            );
        }
    }
    private function uploadToUrl($parameters, $file)
    {
        $uploadUrl = $parameters["upload_url"];
        $multipartData = array();
        foreach ($parameters["upload_parameters"] as $name => $contents) {
            $multipartData[] = [
                'name' => $name,
                'contents' => $contents,
            ];
        }
        if (file_exists($file)) {
            $multipartData[] = [
                'name' => 'file',
                'contents' => fopen($file, 'r'),
                'filename' => basename($file),
                'headers' => [
                    'Content-Type' => mime_content_type($file) ?: 'video/mp4',
                ],
            ];
        } else {
            throw new \Exception("File not found at path: {$file}");
        }
        $response = $this->client->postMultipart($uploadUrl, $multipartData);
        return true;
    }
    private function getUploadedMedia($mediaId)
    {
        $mediaReady = false;
        $maxAttempts = 15;
        $attempt = 0;
        while (!$mediaReady && $attempt < $maxAttempts) {
            $attempt++;
            sleep(2);
            $response = $this->client->get($this->baseUrl . 'media/' . $mediaId, [], $this->header);
            $status = $response['status'] ?? 'unknown';
            if ($status === 'succeeded') {
                $mediaReady = true;
            } elseif ($status === 'failed') {
                $error =  "Video processing failed on Pinterest side. Status: " . $response['details'];
            }
        }
        if ($mediaReady) {
            $response =  [
                "success" => true
            ];
        } else {
            $response = [
                "success" => false,
                "message" => isset($error) ? $error : "Video processing failed on Pinterest side."
            ];
        }
        return $response;
    }
    private function uploadVideo($postData, $media_id)
    {
        $payload = [
            "title" => $postData["title"],
            "board_id" => (string) $postData["board_id"],
            "media_source" => [
                "source_type" => "video_id",
                "media_id" => $media_id,
                "cover_image_key_frame_time" => 1
            ]
        ];
        $response = $this->client->postJson($this->baseUrl . "pins", $payload, $this->header);
        return $response;
    }
}
