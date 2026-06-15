<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Post;
use App\Models\Youtube as YoutubeModel;
use Exception;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class YouTubeService
{
    private string $clientId;

    private string $clientSecret;

    private string $redirectUri;

    private array $scopes;

    private HttpService $client;

    private SocialMediaLogService $logService;

    public function __construct()
    {
        $this->clientId = (string) config('services.youtube.client_id', env('GOOGLE_CLIENT_ID'));
        $this->clientSecret = (string) config('services.youtube.client_secret', env('GOOGLE_CLIENT_SECRET'));
        $this->redirectUri = (string) (config('services.youtube.redirect') ?: route('youtube.callback'));
        $this->scopes = [
            'https://www.googleapis.com/auth/youtube.readonly',
            'https://www.googleapis.com/auth/youtube.upload',
            'https://www.googleapis.com/auth/youtube.force-ssl',
        ];
        $this->client = new HttpService();
        $this->logService = new SocialMediaLogService();
    }

    public function getLoginUrl(): string
    {
        if ($this->clientId === '' || $this->clientSecret === '') {
            throw new \RuntimeException('YouTube OAuth is not configured.');
        }

        $state = bin2hex(random_bytes(16));
        session_set('youtube_oauth_state', $state);

        $query = http_build_query([
            'response_type' => 'code',
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'scope' => implode(' ', $this->scopes),
            'state' => $state,
            'access_type' => 'offline',
            'prompt' => 'consent',
        ], '', '&', PHP_QUERY_RFC3986);

        return 'https://accounts.google.com/o/oauth2/v2/auth?'.$query;
    }

    public function getAccessToken(string $code): ?array
    {
        return $this->client->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->redirectUri,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
        ], [
            'Content-Type' => 'application/x-www-form-urlencoded',
        ]);
    }

    public function refreshAccessToken(string $refreshToken): ?array
    {
        return $this->client->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
        ], [
            'Content-Type' => 'application/x-www-form-urlencoded',
        ]);
    }

    public function getChannels(string $accessToken): ?array
    {
        return $this->client->get('https://www.googleapis.com/youtube/v3/channels', [
            'part' => 'snippet',
            'mine' => 'true',
        ], [
            'Authorization' => 'Bearer '.$accessToken,
        ]);
    }

    public static function validateToken(?YoutubeModel $youtube): array
    {
        try {
            if (! $youtube) {
                return [
                    'success' => false,
                    'message' => 'YouTube account not found.',
                ];
            }

            if (empty($youtube->access_token)) {
                return [
                    'success' => false,
                    'message' => 'YouTube access token is missing. Please reconnect your YouTube account.',
                ];
            }

            if ($youtube->validToken()) {
                return ['success' => true, 'access_token' => $youtube->access_token];
            }

            if (empty($youtube->refresh_token)) {
                return [
                    'success' => false,
                    'message' => 'YouTube refresh token is missing. Please reconnect your YouTube account.',
                ];
            }

            $service = new self();
            $tokenResponse = $service->refreshAccessToken($youtube->refresh_token);

            if (! empty($tokenResponse['access_token'])) {
                $youtube->update([
                    'access_token' => $tokenResponse['access_token'],
                    'expires_in' => $tokenResponse['expires_in'] ?? null,
                ]);

                return ['success' => true, 'access_token' => $tokenResponse['access_token']];
            }

            $errorMessage = $tokenResponse['error_description']
                ?? $tokenResponse['message']
                ?? 'Failed to refresh YouTube token. Please reconnect your account.';

            return [
                'success' => false,
                'message' => $errorMessage,
            ];
        } catch (\Exception $e) {
            info('YouTube validateToken error: '.$e->getMessage());

            return [
                'success' => false,
                'message' => 'Error validating YouTube token: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Publish a video to YouTube for the given post.
     */
    public function video(int $id, array $post, string $accessToken): void
    {
        $postRow = Post::with('youtube')->find($id);
        $localFilePath = null;

        try {
            $videoSource = $post['file_url'] ?? null;
            if (empty($videoSource)) {
                throw new Exception('Video file is required for YouTube upload.');
            }

            $downloadResult = $this->downloadFromS3ToLocal($videoSource);
            if (! $downloadResult['success']) {
                throw new Exception('Failed to download video: '.($downloadResult['message'] ?? 'Unknown error'));
            }

            $localFilePath = $downloadResult['local_path'];
            if (empty($localFilePath) || ! file_exists($localFilePath)) {
                throw new Exception('Local video file could not be prepared for upload.');
            }

            $title = trim((string) ($post['title'] ?? $postRow->title ?? ''));
            if ($title === '') {
                $title = 'Untitled Video';
            }
            $title = mb_substr($title, 0, 100);

            $description = trim((string) ($post['description'] ?? $postRow->comment ?? $postRow->description ?? ''));
            $description = mb_substr($description, 0, 5000);

            $privacyStatus = $this->normalizePrivacyStatus($post['privacy_status'] ?? 'public');

            $uploadResponse = $this->uploadVideo($accessToken, $localFilePath, [
                'title' => $title,
                'description' => $description,
                'privacy_status' => $privacyStatus,
                'tags' => $post['tags'] ?? [],
            ]);

            $videoId = $uploadResponse['id'] ?? null;
            if (empty($videoId)) {
                throw new Exception('YouTube upload succeeded but no video ID was returned.');
            }

            $postRow->update([
                'post_id' => $videoId,
                'status' => 1,
                'published_at' => date('Y-m-d H:i:s'),
                'response' => json_encode([
                    'success' => true,
                    'video_id' => $videoId,
                    'privacy_status' => $privacyStatus,
                    'message' => 'Video published to YouTube successfully.',
                ]),
            ]);

            $this->successNotification(
                $postRow->user_id,
                'Post Published',
                'Your video was published to YouTube successfully.',
                $postRow
            );
            $this->logService->logPost('youtube', 'video', $id, ['video_id' => $videoId], 'success');
        } catch (RequestException $e) {
            $errorMessage = $this->extractApiErrorMessage($e);
            $this->markPostFailed($postRow, $errorMessage);
            $this->errorNotification($postRow->user_id, 'Post Publishing Failed', 'Failed to publish YouTube video. '.$errorMessage, $postRow);
            $this->logService->logPost('youtube', 'video', $id, [], 'failed');
            $this->logService->logApiError('youtube', '/upload/youtube/v3/videos', $errorMessage, ['post_id' => $id]);
        } catch (Exception $e) {
            $errorMessage = $e->getMessage();
            $this->markPostFailed($postRow, $errorMessage);
            $this->errorNotification($postRow->user_id, 'Post Publishing Failed', 'Failed to publish YouTube video. '.$errorMessage, $postRow);
            $this->logService->logPost('youtube', 'video', $id, [], 'failed');
            $this->logService->logApiError('youtube', '/upload/youtube/v3/videos', $errorMessage, ['post_id' => $id]);
        } finally {
            if ($localFilePath) {
                $this->deleteLocalFile($localFilePath);
            }
        }
    }

    /**
     * Upload a video file using YouTube resumable upload.
     *
     * @return array<string, mixed>
     */
    public function uploadVideo(string $accessToken, string $filePath, array $metadata): array
    {
        if (! file_exists($filePath)) {
            throw new Exception("Video file not found at {$filePath}");
        }

        $fileSize = filesize($filePath);
        if ($fileSize === false || $fileSize <= 0) {
            throw new Exception('Video file is empty or unreadable.');
        }

        $mimeType = $this->detectMimeType($filePath);
        $tags = array_values(array_filter(array_map('strval', $metadata['tags'] ?? [])));

        $videoMetadata = [
            'snippet' => [
                'title' => (string) ($metadata['title'] ?? 'Untitled Video'),
                'description' => (string) ($metadata['description'] ?? ''),
            ],
            'status' => [
                'privacyStatus' => $this->normalizePrivacyStatus($metadata['privacy_status'] ?? 'public'),
            ],
        ];

        if (array_key_exists('made_for_kids', $metadata) && $metadata['made_for_kids'] !== '' && $metadata['made_for_kids'] !== null) {
            $videoMetadata['status']['selfDeclaredMadeForKids'] = (bool) $metadata['made_for_kids'];
        }

        if ($tags !== []) {
            $videoMetadata['snippet']['tags'] = array_slice($tags, 0, 30);
        }

        $guzzle = $this->client->getClient();
        $initResponse = $guzzle->post('https://www.googleapis.com/upload/youtube/v3/videos', [
            'query' => [
                'uploadType' => 'resumable',
                'part' => 'snippet,status',
            ],
            'headers' => [
                'Authorization' => 'Bearer '.$accessToken,
                'Content-Type' => 'application/json; charset=UTF-8',
                'X-Upload-Content-Type' => $mimeType,
                'X-Upload-Content-Length' => (string) $fileSize,
            ],
            'json' => $videoMetadata,
            'http_errors' => true,
        ]);

        $uploadUrl = $initResponse->getHeaderLine('Location');
        if ($uploadUrl === '') {
            throw new Exception('YouTube did not return an upload URL.');
        }

        $uploadResponse = $guzzle->put($uploadUrl, [
            'headers' => [
                'Content-Type' => $mimeType,
                'Content-Length' => (string) $fileSize,
            ],
            'body' => fopen($filePath, 'rb'),
            'http_errors' => true,
        ]);

        $body = $uploadResponse->getBody()->getContents();
        $decoded = json_decode($body, true);

        if (! is_array($decoded)) {
            throw new Exception('Invalid response from YouTube upload API.');
        }

        return $decoded;
    }

    private function normalizePrivacyStatus(mixed $value): string
    {
        $status = strtolower(trim((string) $value));
        if (! in_array($status, ['public', 'private', 'unlisted'], true)) {
            return 'public';
        }

        return $status;
    }

    private function detectMimeType(string $filePath): string
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo ? finfo_file($finfo, $filePath) : false;
        if ($finfo) {
            finfo_close($finfo);
        }

        return is_string($mime) && $mime !== '' ? $mime : 'video/mp4';
    }

    /**
     * @return array{success: bool, message?: string, local_path?: ?string}
     */
    private function downloadFromS3ToLocal(string $s3KeyOrUrl): array
    {
        try {
            $isUrl = filter_var($s3KeyOrUrl, FILTER_VALIDATE_URL);

            if ($isUrl) {
                if (str_contains($s3KeyOrUrl, 'amazonaws.com') || str_contains($s3KeyOrUrl, 's3.')) {
                    $parsedUrl = parse_url($s3KeyOrUrl);
                    $s3Key = ltrim((string) ($parsedUrl['path'] ?? ''), '/');
                } else {
                    $contents = $this->client->getRaw($s3KeyOrUrl);
                    $extension = pathinfo(parse_url($s3KeyOrUrl, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION) ?: 'mp4';
                    $localPublicPath = 'uploads/videos/'.uniqid('youtube_', true).'.'.$extension;
                    $fullPath = public_path($localPublicPath);
                    $directory = dirname($fullPath);
                    if (! file_exists($directory)) {
                        mkdir($directory, 0755, true);
                    }
                    file_put_contents($fullPath, $contents);

                    return [
                        'success' => true,
                        'local_path' => $fullPath,
                    ];
                }
            } else {
                $s3Key = $s3KeyOrUrl;
            }

            $fileContents = Storage::disk('s3')->get($s3Key);
            if (! $fileContents) {
                throw new Exception('Failed to download file from S3');
            }

            $extension = pathinfo($s3Key, PATHINFO_EXTENSION) ?: 'mp4';
            $localPublicPath = 'uploads/videos/'.uniqid('youtube_', true).'.'.$extension;
            $fullPath = public_path($localPublicPath);
            $directory = dirname($fullPath);
            if (! file_exists($directory)) {
                mkdir($directory, 0755, true);
            }

            if (file_put_contents($fullPath, $fileContents) === false) {
                throw new Exception('Failed to save file to local storage');
            }

            return [
                'success' => true,
                'local_path' => $fullPath,
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'local_path' => null,
            ];
        }
    }

    private function deleteLocalFile(?string $filePath): void
    {
        if ($filePath && file_exists($filePath)) {
            try {
                unlink($filePath);
            } catch (Exception $e) {
                Log::warning('Failed to delete local YouTube upload file: '.$e->getMessage());
            }
        }
    }

    private function markPostFailed(?Post $postRow, string $errorMessage): void
    {
        if (! $postRow) {
            return;
        }

        $postRow->update([
            'status' => -1,
            'published_at' => date('Y-m-d H:i:s'),
            'response' => json_encode([
                'success' => false,
                'error' => $errorMessage,
            ]),
        ]);
    }

    private function extractApiErrorMessage(RequestException $e): string
    {
        $errorMessage = $e->getMessage();
        if ($e->hasResponse()) {
            $responseBody = $e->getResponse()->getBody()->getContents();
            $decoded = json_decode($responseBody, true);
            if (isset($decoded['error']['message'])) {
                $errorMessage = $decoded['error']['message'];
            }
        }

        return $errorMessage;
    }

    private function successNotification(int $userId, string $title, string $message, ?Post $post = null): void
    {
        $body = ['type' => 'success', 'message' => $message];
        if ($post) {
            $post->loadMissing(['youtube']);
            $body['social_type'] = 'youtube';
            $body['account_image'] = $post->youtube?->profile_image;
            $body['account_name'] = $post->youtube?->username ?? '';
            $body['account_username'] = $post->youtube?->custom_url ?? $post->youtube?->username ?? '';
        }

        Notification::create([
            'user_id' => $userId,
            'title' => $title,
            'body' => $body,
            'is_read' => false,
            'is_system' => false,
        ]);
    }

    private function errorNotification(int $userId, string $title, string $message, ?Post $post = null): void
    {
        $body = ['type' => 'error', 'message' => $message];
        if ($post) {
            $post->loadMissing(['youtube']);
            $body['social_type'] = 'youtube';
            $body['account_image'] = $post->youtube?->profile_image;
            $body['account_name'] = $post->youtube?->username ?? '';
            $body['account_username'] = $post->youtube?->custom_url ?? $post->youtube?->username ?? '';
        }

        Notification::create([
            'user_id' => $userId,
            'title' => $title,
            'body' => $body,
            'is_read' => false,
            'is_system' => false,
        ]);
    }
}
