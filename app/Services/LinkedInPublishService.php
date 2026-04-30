<?php

namespace App\Services;

use App\Models\Linkedin;
use App\Models\Post;
use Illuminate\Support\Facades\Http;

class LinkedInPublishService
{
    private const API_BASE_V2 = 'https://api.linkedin.com/v2';

    private const RESTLI_VERSION = '2.0.0';

    public function publish(Post $post, Linkedin $account): array
    {
        $token = (string) ($account->getRawOriginal('access_token') ?? $account->access_token ?? '');
        if ($token === '') {
            return ['success' => false, 'message' => 'LinkedIn access token is missing.'];
        }

        $authorUrn = 'urn:li:person:'.$account->linkedin_id;
        $ugcPayload = $this->buildUgcPayload($post, $authorUrn, $token);
        if (! ($ugcPayload['success'] ?? false)) {
            return $ugcPayload;
        }

        $response = Http::withHeaders($this->jsonHeaders($token))
            ->post(self::API_BASE_V2.'/ugcPosts', $ugcPayload['payload']);

        if (! $response->successful()) {
            return [
                'success' => false,
                'message' => $response->json('message')
                    ?? $response->json('error.message')
                    ?? 'LinkedIn post publish failed.',
                'response' => $response->body(),
            ];
        }

        $id = (string) ($response->header('x-restli-id') ?? '');
        if ($id === '') {
            $id = (string) ($response->json('id') ?? '');
        }

        return [
            'success' => true,
            'id' => $id,
            'response' => $response->json() ?: ['raw' => $response->body()],
        ];
    }

    private function buildUgcPayload(Post $post, string $authorUrn, string $token): array
    {
        $commentary = $this->buildCommentary($post);
        $mediaCategory = 'NONE';
        $media = [];

        if ($post->type === 'photo') {
            $imageUrl = $this->resolveImageUrl($post);
            if ($imageUrl === '') {
                return ['success' => false, 'message' => 'Photo publish requires a valid image URL or uploaded image.'];
            }
            $assetResult = $this->registerAndUploadAsset($token, $authorUrn, 'image', $imageUrl);
            if (! ($assetResult['success'] ?? false)) {
                return [
                    'success' => false,
                    'message' => 'Failed to register/upload image asset to LinkedIn.',
                    'details' => $assetResult,
                ];
            }
            $assetUrn = (string) ($assetResult['asset'] ?? '');
            $mediaCategory = 'IMAGE';
            $media[] = [
                'status' => 'READY',
                'media' => $assetUrn,
                'title' => ['text' => $this->mediaTitle($post)],
            ];
        } elseif ($post->type === 'video') {
            $videoUrl = $this->resolveVideoUrl($post);
            if ($videoUrl === '') {
                return ['success' => false, 'message' => 'Video publish requires a valid video URL or uploaded video.'];
            }
            $assetResult = $this->registerAndUploadAsset($token, $authorUrn, 'video', $videoUrl);
            if (! ($assetResult['success'] ?? false)) {
                return [
                    'success' => false,
                    'message' => 'Failed to register/upload video asset to LinkedIn.',
                    'details' => $assetResult,
                ];
            }
            $assetUrn = (string) ($assetResult['asset'] ?? '');
            $mediaCategory = 'VIDEO';
            $media[] = [
                'status' => 'READY',
                'media' => $assetUrn,
                'title' => ['text' => $this->mediaTitle($post)],
            ];
        } elseif ($post->type === 'carousel') {
            return [
                'success' => false,
                'message' => 'LinkedIn Share API (ugcPosts) does not support carousel in this implementation.',
            ];
        } elseif ($post->type === 'document') {
            return [
                'success' => false,
                'message' => 'LinkedIn Share API (ugcPosts) does not support document publishing in this implementation.',
            ];
        }

        $shareContent = [
            'shareCommentary' => [
                'text' => $commentary,
            ],
            'shareMediaCategory' => $mediaCategory,
        ];
        if ($media !== []) {
            $shareContent['media'] = $media;
        }

        return [
            'success' => true,
            'payload' => [
                'author' => $authorUrn,
                'lifecycleState' => 'PUBLISHED',
                'specificContent' => [
                    'com.linkedin.ugc.ShareContent' => $shareContent,
                ],
                'visibility' => [
                    'com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC',
                ],
            ],
        ];
    }

    private function buildCommentary(Post $post): string
    {
        $title = trim((string) ($post->title ?? ''));
        $comment = trim((string) ($post->comment ?? ''));
        if ($title !== '' && $comment !== '') {
            return $title."\n\n".$comment;
        }

        return $title !== '' ? $title : $comment;
    }

    private function mediaTitle(Post $post): string
    {
        $title = trim((string) ($post->title ?? ''));

        return $title !== '' ? $title : 'LinkedIn media';
    }

    private function registerAndUploadAsset(string $token, string $ownerUrn, string $kind, string $sourceUrl): array
    {
        $recipe = $kind === 'video'
            ? 'urn:li:digitalmediaRecipe:feedshare-video'
            : 'urn:li:digitalmediaRecipe:feedshare-image';

        $registerResponse = Http::withHeaders($this->jsonHeaders($token))
            ->post(self::API_BASE_V2.'/assets?action=registerUpload', [
                'registerUploadRequest' => [
                    'recipes' => [$recipe],
                    'owner' => $ownerUrn,
                    'serviceRelationships' => [
                        [
                            'relationshipType' => 'OWNER',
                            'identifier' => 'urn:li:userGeneratedContent',
                        ],
                    ],
                ],
            ]);

        if (! $registerResponse->successful()) {
            return [
                'success' => false,
                'stage' => 'register_upload',
                'status' => $registerResponse->status(),
                'response' => $registerResponse->json() ?: $registerResponse->body(),
            ];
        }

        $registerJson = $registerResponse->json();
        $value = is_array($registerJson) ? ($registerJson['value'] ?? []) : [];
        $uploadMechanism = is_array($value) ? ($value['uploadMechanism'] ?? []) : [];
        $httpRequestMechanism = is_array($uploadMechanism)
            ? ($uploadMechanism['com.linkedin.digitalmedia.uploading.MediaUploadHttpRequest'] ?? [])
            : [];

        $uploadUrl = is_array($httpRequestMechanism) ? (string) ($httpRequestMechanism['uploadUrl'] ?? '') : '';
        $asset = is_array($value) ? (string) ($value['asset'] ?? '') : '';
        if ($uploadUrl === '' || $asset === '') {
            return [
                'success' => false,
                'stage' => 'register_upload_parse',
                'response' => $registerJson ?: $registerResponse->body(),
            ];
        }

        $binaryResponse = Http::timeout(120)->get($sourceUrl);
        if (! $binaryResponse->successful()) {
            return [
                'success' => false,
                'stage' => 'fetch_source_binary',
                'status' => $binaryResponse->status(),
                'source_url' => $sourceUrl,
                'response' => $binaryResponse->body(),
            ];
        }

        $contentType = (string) ($binaryResponse->header('Content-Type') ?: 'application/octet-stream');
        $upload = Http::withHeaders([
            'Authorization' => 'Bearer '.$token,
            'Content-Type' => $contentType,
        ])
            ->withBody($binaryResponse->body(), $contentType)
            ->put($uploadUrl);

        if (! $upload->successful()) {
            return [
                'success' => false,
                'stage' => 'upload_binary',
                'status' => $upload->status(),
                'response' => $upload->body(),
            ];
        }

        return [
            'success' => true,
            'asset' => $asset,
            'upload_status' => $upload->status(),
        ];
    }

    private function resolveImageUrl(Post $post): string
    {
        $raw = (string) ($post->getRawOriginal('image') ?: $post->image ?: '');
        if ($raw === '') {
            return '';
        }

        return $this->resolveImagePath($raw);
    }

    private function resolveImagePath(string $path): string
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return getImage('', $path);
    }

    private function resolveVideoUrl(Post $post): string
    {
        $raw = (string) ($post->getRawOriginal('video') ?: $post->video ?: '');
        if ($raw === '') {
            return '';
        }
        if (str_starts_with($raw, 'http://') || str_starts_with($raw, 'https://')) {
            return $raw;
        }

        return (string) fetchFromS3($raw);
    }

    private function jsonHeaders(string $token): array
    {
        return [
            'Authorization' => 'Bearer '.$token,
            'Content-Type' => 'application/json',
            'X-Restli-Protocol-Version' => self::RESTLI_VERSION,
        ];
    }
}

