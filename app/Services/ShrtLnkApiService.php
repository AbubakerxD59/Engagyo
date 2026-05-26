<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ShrtLnkApiService
{
    public function isEnabled(): bool
    {
        return (bool) config('shrtlnk.enabled', true);
    }

    /**
     * Create or return an existing short link via ShrtLnk POST /api/links.
     *
     * @param  array{
     *     original_url: string,
     *     user_id?: int|null,
     *     user_agent?: string|null,
     *     ip_address?: string|null,
     *     page_title?: string|null,
     *     thumbnail_url?: string|null,
     *     source?: string|null,
     * }  $payload
     * @return array{success: bool, message?: string, data?: array<string, mixed>}
     */
    public function createLink(array $payload): array
    {
        if (! $this->isEnabled()) {
            return [
                'success' => false,
                'message' => 'ShrtLnk integration is disabled.',
            ];
        }

        $body = [
            'original_url' => $payload['original_url'],
            'source' => $payload['source'] ?? config('shrtlnk.source', 'engagyo'),
        ];

        if (! empty($payload['user_id'])) {
            $body['user_id'] = (int) $payload['user_id'];
        }
        if (! empty($payload['user_agent'])) {
            $body['user_agent'] = substr((string) $payload['user_agent'], 0, 65535);
        }
        if (! empty($payload['ip_address'])) {
            $body['ip_address'] = $payload['ip_address'];
        }
        if (! empty($payload['page_title'])) {
            $body['page_title'] = substr((string) $payload['page_title'], 0, 500);
        }
        if (! empty($payload['thumbnail_url'])) {
            $body['thumbnail_url'] = $payload['thumbnail_url'];
        }

        $url = config('shrtlnk.base_url').'/api/links';

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->timeout((int) config('shrtlnk.timeout', 15))
                ->post($url, $body);
        } catch (\Throwable $e) {
            Log::warning('ShrtLnk API request failed', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Could not reach the ShrtLnk shortener service.',
            ];
        }

        if ($response->successful()) {
            $json = $response->json();
            if (is_array($json) && ($json['success'] ?? false) === true) {
                return [
                    'success' => true,
                    'data' => $json,
                ];
            }

            return [
                'success' => false,
                'message' => is_array($json) ? ($json['message'] ?? 'ShrtLnk returned an unexpected response.') : 'ShrtLnk returned an unexpected response.',
            ];
        }

        $json = $response->json();
        $message = is_array($json)
            ? ($json['message'] ?? collect($json['errors'] ?? [])->flatten()->first())
            : null;

        Log::warning('ShrtLnk API error response', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        return [
            'success' => false,
            'message' => $message ?: 'ShrtLnk could not create the short link.',
        ];
    }
}
