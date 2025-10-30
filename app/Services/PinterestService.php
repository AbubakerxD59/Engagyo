<?php

namespace App\Services;

use Exception;
use App\Models\Post;
use GuzzleHttp\Psr7\Utils;
use App\Services\HttpService;
use DirkGroenen\Pinterest\Pinterest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
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
        info("video function start");
        info("postdata: " . json_encode($post));
        $this->header = array("Content-Type" => "application/json", "Authorization" => "Bearer  " . $access_token);
        $post_row = Post::find($id);
        // step 1
        // try {
        info("here");
        $registerResponse = $this->client->postJson($this->baseUrl . "media", ['media_type' => 'video'], $this->header);
        $uploadUrl = $registerResponse['upload_url'];
        $uploadParameters = $registerResponse['upload_parameters'];
        $mediaId = $registerResponse['media_id'];
        $fileContents = Storage::disk("s3")->get($post["video_key"]);
        if ($fileContents === false) {
            info("File not found on S3");
        } else {
            // save aws file to local storage
            $localPublicPath = 'uploads/videos/' . basename($post["video_key"]);
            $fullPath = public_path($localPublicPath);
            $directory = dirname($fullPath);
            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }
            $bytesWritten = file_put_contents($fullPath, $fileContents);
            // step 2
            $videoPath = public_path($localPublicPath);
            $multipart = [];
            foreach ($uploadParameters as $name => $contents) {
                $multipart[] = ['name' => $name, 'contents' => $contents];
            }
            $multipart[] = [
                'name' => 'file', // Key required by Pinterest for the file contents
                'contents' => Utils::tryFopen($videoPath, 'r'), // Read file content
                'filename' => basename($videoPath),
            ];
            info("multipart: " . json_encode($multipart));
            info($uploadUrl);
            $uploadResponse = $this->client->postMultipart($uploadUrl, $multipart);
            // $uploadResponse = Http::asMultipart()->post($uploadUrl, $multipart);
            info("uploadResponse: " . json_encode($uploadResponse));
            // step 3
            $mediaReady = false;
            $maxAttempts = 10;
            $attempt = 0;
            while (!$mediaReady && $attempt < $maxAttempts) {
                $attempt++;
                sleep(2);
                $statusResponse = $this->client->get($this->baseUrl . 'media/' . $mediaId, [], $this->header);
                $status = $statusResponse['status'] ?? 'unknown';
                if ($status === 'succeeded') {
                    $mediaReady = true;
                } elseif ($status === 'failed') {
                    throw new \Exception("Video processing failed on Pinterest side. Status: " . $statusResponse['details']);
                }
                info("Pinterest Media Status for $mediaId: $status (Attempt $attempt)");
            }
            // step 4
            $pinPayload = [
                "board_id" => (string) $post["board_id"],
                'media_id' => $mediaId,
            ];
            if (!empty($post["title"])) {
                $pinPayload["title"] = $post["title"];
            }
            info(message: json_encode($pinPayload));
            $pinResponse = $this->client->postJson($this->baseUrl . "pins", $pinPayload, $this->header);
            dd($pinResponse);
        }
        // } catch (Exception $e) {
        //     info($e->getMessage());
        // }
    }
}
