<?php

namespace App\Services;

class ThreadsService
{
    private string $clientId;
    private string $clientSecret;
    private string $redirectUri;
    private array $scopes;
    private HttpService $client;

    public function __construct()
    {
        $this->clientId = (string) env('THREADS_APP_ID');
        $this->clientSecret = (string) env('THREADS_APP_SECRET');
        $this->redirectUri = route('threads.callback');
        $this->scopes = [
            'threads_basic',
            'threads_content_publish',
            'threads_delete',
            'threads_manage_insights',
            'threads_manage_replies',
            'threads_profile_discovery',
            'threads_share_to_instagram',
        ];
        $this->client = new HttpService();
    }

    public function getLoginUrl(): string
    {
        if ($this->clientId === '') {
            throw new \RuntimeException('THREADS_APP_ID is not configured.');
        }

        $state = bin2hex(random_bytes(16));
        session_set('threads_oauth_state', $state);

        $query = http_build_query([
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'scope' => implode(',', $this->scopes),
            'response_type' => 'code',
            'state' => $state,
        ], '', '&', PHP_QUERY_RFC3986);

        return 'https://threads.net/oauth/authorize?' . $query;
    }

    public function getOauthToken(string $code): ?array
    {
        if ($this->clientId === '' || $this->clientSecret === '') {
            return [
                'success' => false,
                'message' => 'Threads OAuth is not configured (THREADS_APP_ID / THREADS_APP_SECRET missing).',
            ];
        }

        return $this->client->post('https://graph.threads.net/oauth/access_token', [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->redirectUri,
            'code' => $code,
        ], [
            'Content-Type' => 'application/x-www-form-urlencoded',
        ]);
    }

    public function getLongLivedToken(string $shortLivedToken): ?array
    {
        return $this->client->get('https://graph.threads.net/access_token', [
            'grant_type' => 'th_exchange_token',
            'client_secret' => $this->clientSecret,
            'access_token' => $shortLivedToken,
        ]);
    }

    public function me(string $accessToken): ?array
    {
        return $this->client->get('https://graph.threads.net/me', [
            'fields' => 'id,username,threads_profile_picture_url',
            'access_token' => $accessToken,
        ]);
    }
}
