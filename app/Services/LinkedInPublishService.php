<?php

namespace App\Services;

use App\Models\Linkedin;
use App\Models\Post;
use Illuminate\Support\Facades\Http;

class LinkedInPublishService
{
    private const API_BASE = 'https://api.linkedin.com/rest';

    private const API_VERSION = '202405';

    public function publish(Post $post, Linkedin $account): array
    {
        $token = (string) ($account->getRawOriginal('access_token') ?? $account->access_token ?? '');
        if ($token === '') {
            return ['success' => false, 'message' => 'LinkedIn access token is missing.'];
        }

        $authorUrn = 'urn:li:person:'.$account->linkedin_id;
        $commentary = $this->buildCommentary($post);
        $content = $this->buildContentPayload($post, $token);

        if (isset($content['success']) && $content['success'] === false) {
            return $content;
        }

        $payload = [
            'author' => $authorUrn,
            'commentary' => $commentary,
            'visibility' => 'PUBLIC',
            'distribution' => [
                'feedDistribution' => 'MAIN_FEED',
                'targetEntities' => [],
                'thirdPartyDistributionChannels' => [],
            ],
            'lifecycleState' => 'PUBLISHED',
            'isReshareDisabledByAuthor' => false,
        ];

        if (! empty($content)) {
            $payload['content'] = $content;
        }

        $response = Http::withHeaders($this->jsonHeaders($token))
            ->post(self::API_BASE.'/posts', $payload);

        if (! $response->successful()) {
            return [
                'success' => false,
                'message' => $response->json('message')
                    ?? $response->json('error.message')
                    ?? 'LinkedIn post publish failed.',
                'response' => $response->body(),
            ];
        }

        $id = (string) ($response->json('id') ?? $response->header('x-restli-id') ?? '');

        return [
            'success' => true,
            'id' => $id,
            'response' => $response->json() ?: ['raw' => $response->body()],
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

    private function buildContentPayload(Post $post, string $token): array
    {
        if ($post->type === 'content_only') {
            return [];
        }

        $meta = $this->decodeMetadata($post->metadata);

        if ($post->type === 'photo') {
            $urn = $this->uploadImage($this->resolveImageUrl($post), $token);
            if ($urn === null) {
                return ['success' => false, 'message' => 'Failed to upload LinkedIn image.'];
            }

            return ['media' => ['id' => $urn]];
        }

        if ($post->type === 'video') {
            $urn = $this->uploadVideo($this->resolveVideoUrl($post), $token);
            if ($urn === null) {
                return ['success' => false, 'message' => 'Failed to upload LinkedIn video.'];
            }

            return ['media' => ['id' => $urn]];
        }

        if ($post->type === 'carousel') {
            $items = $meta['linkedin_carousel'] ?? [];
            if (! is_array($items) || count($items) < 2) {
                return ['success' => false, 'message' => 'LinkedIn carousel requires at least 2 images.'];
            }
            $images = [];
            foreach ($items as $path) {
                $url = $this->resolveImagePath((string) $path);
                $urn = $this->uploadImage($url, $token);
                if ($urn === null) {
                    return ['success' => false, 'message' => 'Failed to upload one or more LinkedIn carousel images.'];
                }
                $images[] = ['id' => $urn];
            }

            return ['multiImage' => ['images' => $images]];
        }

        if ($post->type === 'document') {
            $docPath = (string) ($meta['linkedin_document']['path'] ?? '');
            $docName = (string) ($meta['linkedin_document']['name'] ?? 'document');
            if ($docPath === '') {
                return ['success' => false, 'message' => 'LinkedIn document path missing.'];
            }
            $url = $this->resolveDocumentPath($docPath);
            $urn = $this->uploadDocument($url, $token);
            if ($urn === null) {
                return ['success' => false, 'message' => 'Failed to upload LinkedIn document.'];
            }

            return ['media' => ['id' => $urn, 'title' => $docName]];
        }

        return [];
    }

    private function uploadImage(string $url, string $token): ?string
    {
        return $this->uploadBinaryAsset('images', $url, $token, 'urn:li:image:');
    }

    private function uploadVideo(string $url, string $token): ?string
    {
        return $this->uploadBinaryAsset('videos', $url, $token, 'urn:li:video:');
    }

    private function uploadDocument(string $url, string $token): ?string
    {
        return $this->uploadBinaryAsset('documents', $url, $token, 'urn:li:document:');
    }

    private function uploadBinaryAsset(string $assetType, string $sourceUrl, string $token, string $urnPrefix): ?string
    {
        $init = Http::withHeaders($this->jsonHeaders($token))
            ->post(self::API_BASE.'/'.$assetType.'?action=initializeUpload', ['initializeUploadRequest' => new \stdClass()]);

        if (! $init->successful()) {
            return null;
        }

        $uploadUrl = (string) ($init->json('value.uploadUrl') ?? '');
        $assetKey = match ($assetType) {
            'images' => 'image',
            'videos' => 'video',
            'documents' => 'document',
            default => 'asset',
        };
        $asset = (string) ($init->json('value.'.$assetKey) ?? $init->json('value.asset') ?? '');
        if ($uploadUrl === '' || $asset === '') {
            return null;
        }

        $binaryResponse = Http::timeout(120)->get($sourceUrl);
        if (! $binaryResponse->successful()) {
            return null;
        }

        $contentType = (string) ($binaryResponse->header('Content-Type') ?: 'application/octet-stream');
        $upload = Http::withHeaders(['Content-Type' => $contentType])
            ->withBody($binaryResponse->body(), $contentType)
            ->put($uploadUrl);

        if (! $upload->successful()) {
            return null;
        }

        if (str_starts_with($asset, 'urn:li:')) {
            return $asset;
        }

        return str_starts_with($asset, $urnPrefix) ? $asset : $urnPrefix.$asset;
    }

    private function decodeMetadata(mixed $metadata): array
    {
        if (is_array($metadata)) {
            return $metadata;
        }
        if (! is_string($metadata) || trim($metadata) === '') {
            return [];
        }

        $decoded = json_decode($metadata, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function resolveImageUrl(Post $post): string
    {
        return $this->resolveImagePath((string) ($post->getRawOriginal('image') ?: $post->image ?: ''));
    }

    private function resolveImagePath(string $path): string
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return url(getImage('', $path));
    }

    private function resolveVideoUrl(Post $post): string
    {
        $raw = (string) ($post->getRawOriginal('video') ?: $post->video ?: '');
        if (str_starts_with($raw, 'http://') || str_starts_with($raw, 'https://')) {
            return $raw;
        }

        return (string) fetchFromS3($raw);
    }

    private function resolveDocumentPath(string $path): string
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }
        if (str_starts_with($path, '/')) {
            return url($path);
        }

        return asset($path);
    }

    private function jsonHeaders(string $token): array
    {
        return [
            'Authorization' => 'Bearer '.$token,
            'Content-Type' => 'application/json',
            'X-Restli-Protocol-Version' => '2.0.0',
            'LinkedIn-Version' => self::API_VERSION,
        ];
    }
}

