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
     *     url_cloak?: int|bool|null,
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
        if (array_key_exists('url_cloak', $payload)) {
            $body['url_cloak'] = filter_var($payload['url_cloak'], FILTER_VALIDATE_BOOL) ? 1 : 0;
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

    /**
     * Fetch link details and click count via GET /api/links/{code}.
     *
     * @see https://shrtnlnk.com/api-docs#get-link-api
     * @return array{success: bool, message?: string, not_found?: bool, data?: array<string, mixed>}
     */
    public function getLink(string $shortCode): array
    {
        if (! $this->isEnabled()) {
            return [
                'success' => false,
                'message' => 'ShrtLnk integration is disabled.',
            ];
        }

        $code = trim($shortCode);
        if ($code === '') {
            return [
                'success' => false,
                'message' => 'Short code is required.',
            ];
        }

        $url = config('shrtlnk.base_url').'/api/links/'.rawurlencode($code);

        try {
            $response = Http::acceptJson()
                ->timeout((int) config('shrtlnk.timeout', 15))
                ->get($url);
        } catch (\Throwable $e) {
            Log::warning('ShrtLnk GET link request failed', [
                'code' => $code,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Could not reach the ShrtLnk shortener service.',
            ];
        }

        if ($response->status() === 404) {
            return [
                'success' => false,
                'not_found' => true,
                'message' => 'Short link not found on ShrtLnk.',
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
        $message = is_array($json) ? ($json['message'] ?? null) : null;

        Log::warning('ShrtLnk GET link error response', [
            'code' => $code,
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        return [
            'success' => false,
            'message' => $message ?: 'ShrtLnk could not fetch the short link.',
        ];
    }

    /**
     * Partially update an existing short link via PATCH /api/links/{code}.
     *
     * @see https://shrtnlnk.com/api-docs#update-link-api
     *
     * @param  array{
     *     original_url?: string,
     *     url_cloak?: int|bool|null,
     *     page_title?: string|null,
     *     thumbnail_url?: string|null,
     *     source?: string|null,
     *     user_id?: int|null,
     * }  $payload
     * @return array{success: bool, message?: string, data?: array<string, mixed>}
     */
    public function updateLink(string $shortCode, array $payload): array
    {
        if (! $this->isEnabled()) {
            return [
                'success' => false,
                'message' => 'ShrtLnk integration is disabled.',
            ];
        }

        $code = trim($shortCode);
        if ($code === '') {
            return [
                'success' => false,
                'message' => 'Short code is required.',
            ];
        }

        $body = [];
        if (! empty($payload['original_url'])) {
            $body['original_url'] = $payload['original_url'];
        }
        if (array_key_exists('url_cloak', $payload)) {
            $body['url_cloak'] = filter_var($payload['url_cloak'], FILTER_VALIDATE_BOOL) ? 1 : 0;
        }
        if (! empty($payload['page_title'])) {
            $body['page_title'] = substr((string) $payload['page_title'], 0, 500);
        }
        if (! empty($payload['thumbnail_url'])) {
            $body['thumbnail_url'] = $payload['thumbnail_url'];
        }
        if (! empty($payload['source'])) {
            $body['source'] = $payload['source'];
        }
        if (! empty($payload['user_id'])) {
            $body['user_id'] = (int) $payload['user_id'];
        }

        if ($body === []) {
            return [
                'success' => false,
                'message' => 'No fields to update.',
            ];
        }

        $url = config('shrtlnk.base_url').'/api/links/'.rawurlencode($code);

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->timeout((int) config('shrtlnk.timeout', 15))
                ->patch($url, $body);
        } catch (\Throwable $e) {
            Log::warning('ShrtLnk API update request failed', [
                'code' => $code,
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

        Log::warning('ShrtLnk API update error response', [
            'code' => $code,
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        return [
            'success' => false,
            'message' => $message ?: 'ShrtLnk could not update the short link.',
        ];
    }
}
