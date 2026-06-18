<?php

namespace App\Http\Controllers\FrontEnd;

use App\Http\Controllers\Controller;
use App\Jobs\SyncYouTubeChannelVideosJob;
use App\Models\Feature;
use App\Models\User;
use App\Services\FeatureUsageService;
use App\Services\SocialMediaLogService;
use App\Services\YouTubeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class YouTubeAuthController extends Controller
{
    private YouTubeService $youtubeService;

    private SocialMediaLogService $logService;

    public function __construct()
    {
        $this->youtubeService = new YouTubeService();
        $this->logService = new SocialMediaLogService();
    }

    public function callback(Request $request, FeatureUsageService $featureUsageService)
    {
        $userId = Auth::guard('user')->id();
        $user = User::with('youtubes')->find($userId);

        if (! $user) {
            return redirect(route('panel.accounts'))->with('error', 'User session expired. Please login again.');
        }

        if ($request->has('error')) {
            $errorMessage = $request->error_description ?? $request->error ?? 'YouTube authorization was denied.';
            $this->logService->log('youtube', 'callback_error', $errorMessage, ['user_id' => $userId], 'error');

            return redirect(route('panel.accounts'))->with('error', $errorMessage);
        }

        $sessionState = session_get('youtube_oauth_state');
        if (empty($request->state) || empty($sessionState) || $request->state !== $sessionState) {
            $this->logService->log('youtube', 'callback_error', 'Invalid OAuth state', ['user_id' => $userId], 'error');

            return redirect(route('panel.accounts'))->with('error', 'Invalid OAuth state for YouTube authorization.');
        }

        if (! $request->has('code')) {
            $this->logService->log('youtube', 'callback_error', 'Missing authorization code', ['user_id' => $userId], 'error');

            return redirect(route('panel.accounts'))->with('error', 'Invalid authorization code for YouTube.');
        }

        $tokenResponse = $this->youtubeService->getAccessToken((string) $request->code);
        if (empty($tokenResponse['access_token'])) {
            $errorMessage = $tokenResponse['error_description'] ?? $tokenResponse['message'] ?? 'Failed to get YouTube access token.';
            $this->logService->logApiError('youtube', '/oauth2/token', $errorMessage, ['user_id' => $userId]);

            return redirect(route('panel.accounts'))->with('error', $errorMessage);
        }

        $accessToken = $tokenResponse['access_token'];
        $channelsResponse = $this->youtubeService->getChannels($accessToken);
        $channels = $channelsResponse['items'] ?? [];

        if (empty($channels)) {
            $errorMessage = $channelsResponse['error']['message'] ?? 'No YouTube channels found for this Google account.';
            $this->logService->logApiError('youtube', '/youtube/v3/channels', $errorMessage, ['user_id' => $userId]);

            return redirect(route('panel.accounts'))->with('error', $errorMessage);
        }

        $connectedCount = 0;

        foreach ($channels as $channel) {
            $channelId = $channel['id'] ?? null;
            if (! $channelId) {
                continue;
            }

            $snippet = $channel['snippet'] ?? [];
            $existing = $user->youtubes()->where('channel_id', $channelId)->first();
            $didIncrement = false;

            if (! $existing) {
                $result = $featureUsageService->checkAndIncrement($user, Feature::$features_list[0], 1);
                if (! $result['allowed']) {
                    if ($connectedCount > 0) {
                        session_delete('youtube_oauth_state');

                        return redirect(route('panel.accounts'))->with(
                            'success',
                            "YouTube authorization completed for {$connectedCount} channel(s). ".$result['message']
                        );
                    }

                    return redirect(route('panel.accounts'))->with('error', $result['message']);
                }
                $didIncrement = true;
            }

            $thumbnails = $snippet['thumbnails'] ?? [];
            $profileImageUrl = $thumbnails['high']['url']
                ?? $thumbnails['medium']['url']
                ?? $thumbnails['default']['url']
                ?? null;

            $profileImage = '';
            if (! empty($profileImageUrl)) {
                $savedProfileImage = saveImageFromUrl($profileImageUrl);
                $profileImage = $savedProfileImage ?: '';
            }

            try {
                $payload = [
                    'user_id' => $user->id,
                    'channel_id' => $channelId,
                    'username' => $snippet['title'] ?? 'YouTube Channel',
                    'custom_url' => $snippet['customUrl'] ?? null,
                    'profile_image' => $profileImage,
                    'access_token' => $accessToken,
                    'expires_in' => $tokenResponse['expires_in'] ?? null,
                ];

                if (! empty($tokenResponse['refresh_token'])) {
                    $payload['refresh_token'] = $tokenResponse['refresh_token'];
                }

                if (Schema::hasColumn('youtubes', 'url_shortener_enabled')) {
                    $payload['url_shortener_enabled'] = in_array('youtube', $user->url_shorten_platforms ?? []);
                }

                $youtubeAccount = $user->youtubes()->updateOrCreate(
                    ['channel_id' => $channelId],
                    $payload
                );

                $this->logService->logAccountConnection('youtube', $youtubeAccount->id, $youtubeAccount->username, 'connected');
                SyncYouTubeChannelVideosJob::dispatch((int) $youtubeAccount->id);
                $connectedCount++;
            } catch (\Throwable $e) {
                if ($didIncrement) {
                    $user->decrementFeatureUsage(Feature::$features_list[0], 1);
                }

                $this->logService->log('youtube', 'connect_failed', $e->getMessage(), [
                    'user_id' => $userId,
                    'channel_id' => $channelId,
                ], 'error');
            }
        }

        session_delete('youtube_oauth_state');

        if ($connectedCount === 0) {
            return redirect(route('panel.accounts'))->with('error', 'Failed to connect YouTube channel(s).');
        }

        $message = $connectedCount === 1
            ? 'YouTube channel connected successfully!'
            : "{$connectedCount} YouTube channels connected successfully!";

        return redirect(route('panel.accounts'))->with('success', $message);
    }
}
