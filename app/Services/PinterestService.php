<?php

namespace App\Services;

use App\Models\Post;
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
    private $sandbox_baseUrl = "https://api-sandbox.pinterest.com/v5/";
    private $uploadUrl = "https://pinterest-media-upload.s3-accelerate.amazonaws.com/";
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
        // Step 1
        $this->header = array("Content-Type" => "application/json", "Authorization" => "Bearer  " . $access_token);
        $response = $this->client->postJson($this->baseUrl . "media", ['media_type' => 'video'], $this->header);
        // $response = Http::withToken($access_token)
        //     ->post("{$this->baseUrl}/media", [
        //         'media_type' => 'video'
        //     ]);
        info("step 1:" . json_encode($response));
        if ($response->failed()) {
            $post = $this->post->find($id);
            $post->update([
                "status" => -1,
                "response" => 'Failed to register media upload: ' . json_encode($response->body())
            ]);
        } else {
            $mediaData = $response->json();
            $mediaId = $mediaData['media_id'];
            $uploadUrl = $mediaData['upload_url'];
            $uploadParameters = $mediaData['upload_parameters'];
            // Step 2
            $s3_file = $post["media_source"]["media_id"];
            // $file_name = basename($s3_file);
            // $videoMimeType = Storage::disk("s3")->mimeType($s3_file);
            // $videoStream = Storage::disk("s3")->getDriver()->readStream($s3_file);
            $s3Url = Storage::disk('s3')->temporaryUrl($s3_file, now()->addMinutes(5));
            if ($s3Url === false) {
                info('Failed to get stream for S3 file: ' . $s3_file);
                $post->update([
                    "status" => -1,
                    "response" => 'Failed to get stream for S3 file: ' . $s3_file
                ]);
            } else {
                $videoContent = Http::get($s3Url)->body();
                $multipartData = [];
                foreach ($uploadParameters as $key => $value) {
                    $multipartData[] = ['name' => $key, 'contents' => $value];
                }
                $multipartData[] = [
                    'name' => 'file',
                    'contents' => $videoContent,
                ];
                $response = Http::asMultipart()->post($uploadUrl, $multipartData);
                info("step 2:" . json_encode($response));
                if ($response->failed()) {
                    info('Video file upload failed: ' . json_encode($response->body()));
                    $post->update([
                        "status" => -1,
                        "response" => 'Video file upload failed: ' . $response->body()
                    ]);
                }
                if (is_resource($videoStream)) {
                    fclose($videoStream);
                }
                // Step 3
                $maxAttempts = 10;
                $waitInterval = 5;
                for ($i = 0; $i < $maxAttempts; $i++) {
                    // Wait for the interval before the next check
                    if ($i > 0) {
                        sleep($waitInterval);
                    }
                    $checkResponse = Http::withToken($access_token)
                        ->get("{$this->baseUrl}/media/{$mediaId}");

                    if ($checkResponse->failed()) {
                        info('Step 3: Failed to check media status: ' . json_encode($checkResponse->body()));
                    }
                    $status = $checkResponse->json()['status'] ?? 'unknown';
                    if ($status === 'succeeded') {
                        break;
                    } elseif ($status === 'failed') {
                        info("Step 3: Pinterest video processing failed for Media ID: {$mediaId}");
                    } elseif ($i === $maxAttempts - 1) {
                        info("Step 3: Video processing timed out after {$maxAttempts} attempts. Current status: {$status}");
                    }
                }
                // Step 4
                $post["media_source"]["media_id"] = $mediaId;
                $response = $this->create($id, $post, $access_token);
                info('Step 4:' . json_encode($response));
            }
        }
    }
}
