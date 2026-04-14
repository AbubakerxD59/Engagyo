<?php

namespace App\Services;

use App\Models\Feature;
use App\Models\InstagramAccount;
use App\Models\User;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * OAuth for Instagram API with Instagram Login (Meta).
 *
 * @see https://developers.facebook.com/docs/instagram-platform/instagram-api-with-instagram-login/
 */
class InstagramLoginService
{
    public const CONNECTION_INSTAGRAM_LOGIN = 'instagram_login';

    public const CONNECTION_FACEBOOK_PAGE = 'facebook_page';

    /** @return list<string> */
    public function scopes(): array
    {
        return [
            'instagram_business_basic',
            'instagram_business_content_publish',
            'instagram_business_manage_comments',
            'instagram_business_manage_messages',
        ];
    }

    /*
     * @description Must match a redirect URI listed under Meta → your app → Instagram → OAuth redirect URIs
     */
    public function redirectUri(): string
    {
        $configured = route('instagram.callback', [], true);
        if (is_string($configured)) {
            $configured = trim($configured);
            if ($configured !== '') {
                return $configured;
            }
        }

        return route('instagram.callback', [], true);
    }

    public function oauthAppId(): string
    {
        return (string) config('services.instagram.app_id', '');
    }

    public function oauthAppSecret(): string
    {
        return (string) config('services.instagram.app_secret', '');
    }

    public function authorizeUrl(): string
    {
        $state = Str::random(40);
        session(['instagram_oauth_state' => $state]);

        $appId = $this->oauthAppId();
        if ($appId === '') {
            throw new \RuntimeException('INSTAGRAM_APP_ID (or FACEBOOK_APP_ID fallback) is not configured.');
        }

        $base = rtrim((string) config('services.instagram.oauth_authorize_url', 'https://www.instagram.com/oauth/authorize'), '?');

        $query = http_build_query([
            'client_id' => $appId,
            'redirect_uri' => $this->redirectUri(),
            'response_type' => 'code',
            'scope' => implode(',', $this->scopes()),
            'state' => $state,
        ], '', '&', PHP_QUERY_RFC3986);

        return $base . '?' . $query;
    }

    /**
     * Exchange authorization code for a long-lived user token; fetch Instagram professional profile.
     *
     * @return array{success: bool, access_token?: string, ig_user_id?: string, username?: string|null, name?: string|null, profile_picture_url?: string|null, message?: string}
     */
    public function completeAuthorization(string $code): array
    {
        $short = $this->exchangeCodeForShortLivedToken($code);
        if (! $short['success']) {
            return $short;
        }

        $long = $this->exchangeShortLivedForLongLived((string) $short['access_token']);
        if (! $long['success']) {
            return $long;
        }

        $profile = $this->fetchInstagramProfile((string) $long['access_token']);
        if (! $profile['success']) {
            return $profile;
        }

        return [
            'success' => true,
            'access_token' => (string) $long['access_token'],
            'ig_user_id' => (string) $profile['ig_user_id'],
            'username' => $profile['username'] ?? null,
            'name' => $profile['name'] ?? null,
            'profile_picture_url' => $profile['profile_picture_url'] ?? null,
        ];
    }

    /**
     * @return array{success: bool, access_token?: string, message?: string}
     */
    public function exchangeCodeForShortLivedToken(string $code): array
    {
        $secret = $this->oauthAppSecret();
        $appId = $this->oauthAppId();
        if ($appId === '' || $secret === '') {
            return ['success' => false, 'message' => 'Instagram OAuth is not configured (INSTAGRAM_APP_ID / INSTAGRAM_APP_SECRET or FACEBOOK_* fallback missing).'];
        }

        /** @var Response $resp */
        $resp = Http::asForm()
            ->acceptJson()
            ->timeout(60)
            ->post('https://api.instagram.com/oauth/access_token', [
                'client_id' => $appId,
                'client_secret' => $secret,
                'grant_type' => 'authorization_code',
                'redirect_uri' => $this->redirectUri(),
                'code' => $code,
            ]);

        if (! $resp->successful()) {
            return [
                'success' => false,
                'message' => 'Could not exchange Instagram authorization code: ' . $this->formatHttpError($resp),
            ];
        }

        $token = $this->parseShortLivedTokenFromBody($resp->json());

        if ($token === null) {
            return [
                'success' => false,
                'message' => 'Unexpected token response from Instagram.',
            ];
        }

        return ['success' => true, 'access_token' => $token];
    }

    /**
     * @param  array<string, mixed>|null  $json
     */
    private function parseShortLivedTokenFromBody(?array $json): ?string
    {
        if ($json === null) {
            return null;
        }
        if (isset($json['data']) && is_array($json['data'])) {
            $first = $json['data'][0] ?? null;
            if (is_array($first) && ! empty($first['access_token'])) {
                return (string) $first['access_token'];
            }
        }
        if (! empty($json['access_token'])) {
            return (string) $json['access_token'];
        }

        return null;
    }

    /**
     * @return array{success: bool, access_token?: string, message?: string}
     */
    public function exchangeShortLivedForLongLived(string $shortLivedUserAccessToken): array
    {
        $secret = $this->oauthAppSecret();
        if ($secret === '') {
            return ['success' => false, 'message' => 'Instagram app secret is not configured (INSTAGRAM_APP_SECRET or FACEBOOK_APP_SECRET fallback).'];
        }

        $resp = Http::acceptJson()
            ->timeout(60)
            ->get('https://graph.instagram.com/access_token', [
                'grant_type' => 'ig_exchange_token',
                'client_secret' => $secret,
                'access_token' => $shortLivedUserAccessToken,
            ]);

        if (! $resp->successful()) {
            return [
                'success' => false,
                'message' => 'Could not obtain long-lived Instagram token: ' . $this->formatHttpError($resp),
            ];
        }

        $token = $resp->json('access_token');
        if (empty($token)) {
            return ['success' => false, 'message' => 'Long-lived token response missing access_token.'];
        }

        return ['success' => true, 'access_token' => (string) $token];
    }

    /**
     * @return array{success: bool, ig_user_id?: string, username?: string|null, name?: string|null, profile_picture_url?: string|null, message?: string}
     */
    public function fetchInstagramProfile(string $longLivedUserAccessToken): array
    {
        $v = ltrim((string) config('services.instagram.graph_version', 'v21.0'), '/');
        $url = 'https://graph.instagram.com/' . $v . '/me';

        $resp = Http::acceptJson()
            ->timeout(60)
            ->get($url, [
                'fields' => 'id,user_id,username,name,profile_picture_url,account_type',
                'access_token' => $longLivedUserAccessToken,
            ]);

        if (! $resp->successful()) {
            return [
                'success' => false,
                'message' => 'Could not load Instagram profile: ' . $this->formatHttpError($resp),
            ];
        }

        $id = $resp->json('id');
        $userId = $resp->json('user_id');
        $igUserId = $id ?: $userId;
        if (empty($igUserId)) {
            return ['success' => false, 'message' => 'Instagram profile response missing id.'];
        }

        return [
            'success' => true,
            'ig_user_id' => (string) $igUserId,
            'username' => $resp->json('username'),
            'name' => $resp->json('name'),
            'profile_picture_url' => $resp->json('profile_picture_url'),
        ];
    }

    /**
     * Refresh a long-lived Instagram Login token (approx. 60 days).
     *
     * @return array{success: bool, message?: string, data?: array{access_token: string, metadata?: mixed}}
     */
    public function refreshAccessToken(InstagramAccount $account): array
    {
        $logService = app(SocialMediaLogService::class);
        $accountId = $account->id;

        try {
            $resp = Http::acceptJson()
                ->timeout(60)
                ->get('https://graph.instagram.com/refresh_access_token', [
                    'grant_type' => 'ig_refresh_token',
                    'access_token' => $account->access_token,
                ]);

            if (! $resp->successful()) {
                $msg = 'Instagram API error: ' . $this->formatHttpError($resp);
                $logService->logTokenRefresh('instagram', $accountId, 'failed', $msg);

                return ['success' => false, 'message' => $msg];
            }

            $newToken = $resp->json('access_token');
            if (empty($newToken)) {
                $logService->logTokenRefresh('instagram', $accountId, 'failed', 'Missing access_token in refresh response');

                return ['success' => false, 'message' => 'Invalid refresh response from Instagram.'];
            }

            $account->update([
                'access_token' => (string) $newToken,
                'expires_in' => time(),
            ]);

            $logService->logTokenRefresh('instagram', $accountId, 'success', 'Instagram Login token refreshed');

            return [
                'success' => true,
                'data' => [
                    'access_token' => (string) $newToken,
                    'metadata' => null,
                ],
            ];
        } catch (\Throwable $e) {
            $logService->logTokenRefresh('instagram', $accountId, 'failed', $e->getMessage());

            return ['success' => false, 'message' => 'Unexpected error while refreshing Instagram token: ' . $e->getMessage()];
        }
    }

    /**
     * Persist connected account for the authenticated app user.
     *
     * @return array{success: string, message: string}
     */
    public function connectForUser(User $user, string $code): array
    {
        $result = $this->completeAuthorization($code);
        if (! $result['success']) {
            return [
                'success' => 'error',
                'message' => $result['message'] ?? 'Instagram authorization failed.',
            ];
        }

        $igUserId = (string) $result['ig_user_id'];
        $accessToken = (string) $result['access_token'];

        /** @var FeatureUsageService $featureUsage */
        $featureUsage = app(FeatureUsageService::class);

        $existing = InstagramAccount::where('user_id', $user->id)->where('ig_user_id', $igUserId)->first();
        $didIncrement = false;
        if (! $existing) {
            $quota = $featureUsage->checkAndIncrement($user, Feature::$features_list[0], 1);
            if (! $quota['allowed']) {
                return [
                    'success' => 'error',
                    'message' => 'Your plan does not allow more social accounts. Upgrade your package to connect Instagram.',
                ];
            }
            $didIncrement = true;
        }

        $igPicUrl = $result['profile_picture_url'] ?? null;
        $profileStored = $existing ? $existing->getRawOriginal('profile_image') : null;
        if (! empty($igPicUrl) && filter_var($igPicUrl, FILTER_VALIDATE_URL)) {
            $savedLocal = saveImageFromUrl($igPicUrl);
            $profileStored = $savedLocal ?: $igPicUrl;
        }

        try {
            $row = InstagramAccount::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'ig_user_id' => $igUserId,
                ],
                [
                    'facebook_id' => null,
                    'page_id' => null,
                    'connection_source' => self::CONNECTION_INSTAGRAM_LOGIN,
                    'username' => $result['username'] ?: ($result['name'] ?: $igUserId),
                    'name' => $result['name'],
                    'profile_image' => $profileStored,
                    'access_token' => $accessToken,
                    'expires_in' => time(),
                ]
            );

            $logService = app(SocialMediaLogService::class);
            $logService->logAccountConnection('instagram', $row->id, $row->username ?? $igUserId, 'connected');

            $msg = $row->wasRecentlyCreated
                ? 'Instagram connected with Instagram Login.'
                : 'Instagram account refreshed.';

            return ['success' => 'success', 'message' => $msg];
        } catch (\Throwable $e) {
            if ($didIncrement) {
                $user->decrementFeatureUsage(Feature::$features_list[0], 1);
            }
            app(SocialMediaLogService::class)->log('instagram', 'connect_failed', $e->getMessage(), [
                'user_id' => $user->id,
                'ig_user_id' => $igUserId,
            ], 'error');

            return [
                'success' => 'error',
                'message' => 'Could not save Instagram account: ' . $e->getMessage(),
            ];
        }
    }

    private function formatHttpError(Response $resp): string
    {
        $json = $resp->json();
        if (is_array($json)) {
            $msg = $json['error_message'] ?? $json['error']['message'] ?? $json['message'] ?? null;
            if (is_string($msg) && $msg !== '') {
                return $msg;
            }
        }

        $body = $resp->body();

        return 'HTTP ' . $resp->status() . (strlen($body) > 0 ? ': ' . substr($body, 0, 500) : '');
    }
}
