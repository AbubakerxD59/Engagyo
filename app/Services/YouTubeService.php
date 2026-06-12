<?php

namespace App\Services;

class YouTubeService
{
    private string $clientId;

    private string $clientSecret;

    private string $redirectUri;

    private array $scopes;

    private HttpService $client;

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
}
