<?php

namespace App\Services;

class LinkedInService
{
    private string $clientId;

    private string $clientSecret;

    private string $redirectUri;

    private array $scopes;

    private HttpService $client;

    public function __construct()
    {
        $this->clientId = (string) config('services.linkedin.client_id', env('LINKEDIN_CLIENT_ID'));
        $this->clientSecret = (string) config('services.linkedin.client_secret', env('LINKEDIN_CLIENT_SECRET'));
        $this->redirectUri = route('linkedin.callback');
        $this->scopes = [
            'r_verify',
            'openid',
            'profile',
            'email',
            'w_member_social',
            'r_profile_basicinfo'
        ];
        $this->client = new HttpService();
    }

    public function getLoginUrl(): string
    {
        if ($this->clientId === '' || $this->clientSecret === '') {
            throw new \RuntimeException('LinkedIn OAuth is not configured.');
        }

        $state = bin2hex(random_bytes(16));
        session_set('linkedin_oauth_state', $state);

        $query = http_build_query([
            'response_type' => 'code',
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'scope' => implode(' ', $this->scopes),
            'state' => $state,
        ], '', '&', PHP_QUERY_RFC3986);

        return 'https://www.linkedin.com/oauth/v2/authorization?' . $query;
    }

    public function getAccessToken(string $code): ?array
    {
        return $this->client->post('https://www.linkedin.com/oauth/v2/accessToken', [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->redirectUri,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
        ], [
            'Content-Type' => 'application/x-www-form-urlencoded',
        ]);
    }

    public function getUserInfo(string $accessToken): ?array
    {
        return $this->client->get('https://api.linkedin.com/v2/userinfo', [], [
            'Authorization' => 'Bearer ' . $accessToken,
        ]);
    }
}
