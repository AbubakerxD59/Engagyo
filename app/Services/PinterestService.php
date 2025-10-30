<?php

namespace App\Services;

use Exception;
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
        info("pinterest service video function");
        $this->header = array("Content-Type" => "application/json", "Authorization" => "Bearer  " . $access_token);
        info(json_encode($this->header));
        // step 1
        // $registerResponse = $this->client->postJson($this->baseUrl . "media", ['media_type' => 'video'], $this->header);
        // info(json_encode($registerResponse));
        // $uploadUrl = $registerResponse['upload_url'];
        // $uploadParameters = $registerResponse['upload_parameters'];
        // $mediaId = $registerResponse['media_id'];
        info("aws key: " . $post["video_key"]);
        $fileContents = Storage::disk("s3")->get($post["video_key"]);
        if ($fileContents === false) {
            $post->update([
                "status" => -1,
                "response" => "File not found on S3"
            ]);
        } else {
            // save aws file to local storage
            $localPublicPath = 'uploads/videos/' . basename($post["video_key"]);
            $fullPath = public_path($localPublicPath);
            $directory = dirname($fullPath);
            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }
            $bytesWritten = file_put_contents($fullPath, $fileContents);
            $publicUrl = asset($localPublicPath);
            info("bytesWritten: " . $bytesWritten);
            info("publicUrl: " . $bytesWritten);
            dd($bytesWritten, $publicUrl);
        }
    }
}
