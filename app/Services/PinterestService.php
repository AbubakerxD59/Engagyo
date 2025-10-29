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

    public function video($id, $post, $access_token): array
    {
        info("video upload start");
        // 1. Get a temporary, publicly accessible URL for the S3 video
        $title = $post["title"];
        $description = $post["title"];
        $s3Path = $post["video_key"];
        $s3Url = $this->getTemporaryS3Url($s3Path);
        info($s3Url);

        // 2. Register media upload with Pinterest
        $mediaData = $this->registerMediaUpload($access_token);
        info("step 2:" . json_encode($mediaData));
        $mediaId = $mediaData['media_id'];
        $uploadUrl = $mediaData['upload_url'];
        $uploadParams = $mediaData['upload_parameters'];

        // 3. Upload the video media to the Pinterest-provided S3 URL
        $this->uploadMedia($s3Url, $uploadUrl, $uploadParams);

        // 4. Wait for media processing (status check is *highly* recommended in production, skipped for brevity)
        // Pinterest must process the uploaded video, which takes time.
        sleep(15);

        // 5. Create the Pin using the media_id
        return $this->createPin($post["board_id"], $mediaId, $title, $description, $access_token);
    }
    // ----------------------------------------------------------------------

    /**
     * Generates a temporary signed public URL for an S3 object.
     * @param string $path
     * @return string
     * @throws Exception
     */
    protected function getTemporaryS3Url(string $path): string
    {
        // 's3' is the default S3 disk name in Laravel's config/filesystems.php
        if (!Storage::disk('s3')->exists($path)) {
            throw new Exception("S3 file not found at path: {$path}");
        }

        // Generate a temporary URL that is valid for 5 minutes
        return Storage::disk('s3')->temporaryUrl($path, now()->addMinutes(5));
    }

    /**
     * Registers the intent to upload media and gets the upload URL.
     * @return array
     * @throws Exception
     */
    protected function registerMediaUpload($accessToken): array
    {
        $headers = [
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type' => 'application/json',
        ];

        // The URI is relative to the base URL configured in HttpService's constructor
        $response = $this->client->post($this->baseUrl . 'media', ['media_type' => 'video'], $headers);
        info("register: ".json_encode($response));
        return $response
    }

    /**
     * Uploads the video file content to the Pinterest-provided S3 URL.
     * This uses the temporary S3 URL to get the video content and then uploads it 
     * using the specific multipart form required by Pinterest's upload endpoint.
     *
     * @param string $s3Url The temporary public URL of the video file.
     * @param string $uploadUrl The S3 URL provided by Pinterest for the upload.
     * @param array $uploadParams The form parameters provided by Pinterest for the S3 upload.
     * @throws Exception
     */
    protected function uploadMedia(string $s3Url, string $uploadUrl, array $uploadParams): void
    {
        // Fetch the video content directly from the S3 URL using the HttpService's raw method
        $videoContent = $this->client->getRaw($s3Url);

        // Prepare the Guzzle-formatted multi-part request data
        $multipartData = [];
        foreach ($uploadParams as $key => $value) {
            $multipartData[] = ['name' => $key, 'contents' => $value];
        }

        // Add the actual file content as the 'file' part
        $multipartData[] = [
            'name' => 'file',
            'contents' => $videoContent,
            'filename' => basename($s3Url), // Use the filename from the URL
        ];
        info("multipart data: " . json_encode($multipartData));

        // Perform the direct multi-part POST request to the Pinterest-provided S3 URL
        $this->client->postMultipart($uploadUrl, $multipartData);
    }

    /**
     * Creates the final Pin using the media ID.
     * @param string $mediaId
     * @param string $title
     * @param string $description
     * @return array
     * @throws Exception
     */
    protected function createPin($board_id, string $mediaId, string $title, string $description, $access_token): array
    {
        $payload = [
            'board_id' => $board_id,
            'media_source' => [
                'source_type' => 'video_id',
                'video_id' => $mediaId,
            ],
            'title' => $title,
            'description' => $description,
        ];
        info("createPin: " . json_encode($payload));

        $headers = [
            'Authorization' => 'Bearer ' . $access_token,
            'Content-Type' => 'application/json',
        ];

        // The URI is relative to the base URL configured in HttpService's constructor
        return $this->client->post('pins', $payload, $headers);
    }
}
