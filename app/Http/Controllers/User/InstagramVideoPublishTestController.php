<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\InstagramAccount;
use App\Models\User;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

/**
 * Debug UI: run Instagram Content Publishing video/reel flow and record every HTTP request/response.
 * Does not create Post rows. Optional "stop before media_publish" avoids publishing to the live account.
 */
class InstagramVideoPublishTestController extends Controller
{
    public function show()
    {
        $user = Auth::guard('user')->user();
        if (! $user instanceof User) {
            abort(403);
        }

        $ownerId = (int) ($user->getEffectiveUser()?->id ?? $user->id);
        $accounts = InstagramAccount::query()
            ->where('user_id', $ownerId)
            ->orderBy('username')
            ->get();

        return view('debug.instagram-video-publish-test', [
            'accounts' => $accounts,
            'steps' => null,
            'fatalError' => session('fatalError'),
        ]);
    }

    public function run(Request $request)
    {
        $user = Auth::guard('user')->user();
        if (! $user instanceof User) {
            abort(403);
        }

        $ownerId = (int) ($user->getEffectiveUser()?->id ?? $user->id);

        $validated = $request->validate([
            'instagram_account_id' => ['required', 'integer'],
            'video_url' => ['required', 'url'],
            'caption' => ['nullable', 'string', 'max:2200'],
            'stop_before_media_publish' => ['sometimes', 'boolean'],
            'max_poll_attempts' => ['nullable', 'integer', 'min:1', 'max:200'],
            'poll_interval_seconds' => ['nullable', 'integer', 'min:1', 'max:60'],
        ]);

        $ig = InstagramAccount::query()
            ->where('id', (int) $validated['instagram_account_id'])
            ->where('user_id', $ownerId)
            ->first();

        if (! $ig || empty($ig->ig_user_id)) {
            return redirect()
                ->route('panel.debug.instagram-video-publish-test')
                ->withInput()
                ->with('fatalError', 'Instagram account not found or missing ig_user_id.');
        }

        $token = (string) ($ig->getAttributes()['access_token'] ?? '');
        if ($token === '') {
            return redirect()
                ->route('panel.debug.instagram-video-publish-test')
                ->withInput()
                ->with('fatalError', 'This Instagram account has no access token stored.');
        }

        $maxPoll = (int) ($validated['max_poll_attempts'] ?? 80);
        $sleepSec = (int) ($validated['poll_interval_seconds'] ?? 5);
        $stopBeforePublish = $request->boolean('stop_before_media_publish', true);

        set_time_limit(min(3600, $maxPoll * $sleepSec + 600));

        $base = $this->graphBaseUrl($ig);
        $igUserId = (string) $ig->ig_user_id;
        $steps = [];

        $includeCopyrightCheck = str_contains($base, 'graph.facebook.com');
        $statusFields = 'id,status_code,status'.($includeCopyrightCheck ? ',copyright_check_status' : '');

        // --- Step 1: create REELS container ---
        $createPayload = [
            'media_type' => 'REELS',
            'video_url' => $validated['video_url'],
            'share_to_feed' => 'true',
            'access_token' => $token,
        ];
        $caption = isset($validated['caption']) ? trim((string) $validated['caption']) : '';
        if ($caption !== '') {
            $createPayload['caption'] = $caption;
        }

        $createUrl = "{$base}/{$igUserId}/media";
        $t0 = microtime(true);
        $createResp = Http::asForm()
            ->acceptJson()
            ->timeout(120)
            ->post($createUrl, $createPayload);
        $steps[] = $this->stepFromHttp(
            '1. Create media container (REELS)',
            'POST',
            $createUrl,
            $this->redactToken($createPayload),
            $createResp,
            microtime(true) - $t0
        );

        if (! $createResp->successful()) {
            return view('debug.instagram-video-publish-test', [
                'accounts' => InstagramAccount::query()->where('user_id', $ownerId)->orderBy('username')->get(),
                'steps' => $steps,
                'fatalError' => 'Create container failed — see last step response.',
            ]);
        }

        $creationId = $createResp->json('id');
        if (empty($creationId)) {
            return view('debug.instagram-video-publish-test', [
                'accounts' => InstagramAccount::query()->where('user_id', $ownerId)->orderBy('username')->get(),
                'steps' => $steps,
                'fatalError' => 'Create response missing container id.',
            ]);
        }

        $creationId = (string) $creationId;

        // --- Steps 2+: poll container status ---
        $lastPayload = null;
        $lastCode = null;
        $pollError = null;

        for ($i = 0; $i < $maxPoll; $i++) {
            $statusUrl = "{$base}/{$creationId}";
            $query = [
                'fields' => $statusFields,
                'access_token' => $token,
            ];
            $t1 = microtime(true);
            $statusResp = Http::acceptJson()
                ->timeout(90)
                ->get($statusUrl, $query);
            $steps[] = $this->stepFromHttp(
                '2.'.($i + 1).'. Poll container status (attempt '.($i + 1).' / '.$maxPoll.')',
                'GET',
                $statusUrl.'?'.$this->queryStringForLog($query),
                ['fields' => $statusFields, 'access_token' => '***REDACTED***'],
                $statusResp,
                microtime(true) - $t1
            );

            if (! $statusResp->successful()) {
                $pollError = 'Status poll HTTP error — see step response.';
                break;
            }

            $payload = $statusResp->json();
            $lastPayload = is_array($payload) ? $payload : null;
            $code = $this->normalizeContainerStatusCode($lastPayload);
            $lastCode = $code;

            if ($code === 'FINISHED') {
                $pollError = null;
                break;
            }

            if (in_array($code, ['ERROR', 'EXPIRED'], true)) {
                $pollError = 'Container reported '.$code.' — see last response body.';
                break;
            }

            sleep($sleepSec);
        }

        if ($pollError !== null) {
            return view('debug.instagram-video-publish-test', [
                'accounts' => InstagramAccount::query()->where('user_id', $ownerId)->orderBy('username')->get(),
                'steps' => $steps,
                'fatalError' => $pollError,
            ]);
        }

        if ($lastCode !== 'FINISHED') {
            return view('debug.instagram-video-publish-test', [
                'accounts' => InstagramAccount::query()->where('user_id', $ownerId)->orderBy('username')->get(),
                'steps' => $steps,
                'fatalError' => 'Timed out waiting for FINISHED. Last status: '.($lastCode ?? 'unknown').'.',
            ]);
        }

        if ($stopBeforePublish) {
            $steps[] = [
                'title' => '3. media_publish (skipped)',
                'method' => '—',
                'url' => "{$base}/{$igUserId}/media_publish",
                'request' => ['creation_id' => $creationId, 'access_token' => '***REDACTED***'],
                'response_status' => null,
                'response_body' => 'You enabled “Stop before media_publish”. The container is ready; no publish call was made.',
                'ok' => true,
                'duration_ms' => null,
            ];

            return view('debug.instagram-video-publish-test', [
                'accounts' => InstagramAccount::query()->where('user_id', $ownerId)->orderBy('username')->get(),
                'steps' => $steps,
                'fatalError' => null,
            ]);
        }

        // --- Final: media_publish ---
        $publishUrl = "{$base}/{$igUserId}/media_publish";
        $publishPayload = [
            'creation_id' => $creationId,
            'access_token' => $token,
        ];
        $t2 = microtime(true);
        $publishResp = Http::asForm()
            ->acceptJson()
            ->timeout(120)
            ->post($publishUrl, $publishPayload);
        $steps[] = $this->stepFromHttp(
            '3. media_publish',
            'POST',
            $publishUrl,
            $this->redactToken($publishPayload),
            $publishResp,
            microtime(true) - $t2
        );

        $fatal = null;
        if (! $publishResp->successful()) {
            $fatal = 'media_publish failed — see last step.';
        } elseif (empty($publishResp->json('id'))) {
            $fatal = 'media_publish response missing published media id.';
        }

        return view('debug.instagram-video-publish-test', [
            'accounts' => InstagramAccount::query()->where('user_id', $ownerId)->orderBy('username')->get(),
            'steps' => $steps,
            'fatalError' => $fatal,
        ]);
    }

    private function graphBaseUrl(InstagramAccount $ig): string
    {
        $v = ltrim((string) config('services.instagram.graph_version', 'v21.0'), '/');

        if ($ig->usesInstagramLogin()) {
            return 'https://graph.instagram.com/'.$v;
        }

        return 'https://graph.facebook.com/'.$v;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function redactToken(array $payload): array
    {
        $out = $payload;
        if (array_key_exists('access_token', $out)) {
            $out['access_token'] = '***REDACTED***';
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private function queryStringForLog(array $query): string
    {
        $q = $this->redactToken($query);

        return http_build_query($q);
    }

    /**
     * @param  array<string, mixed>  $requestLogged
     * @return array<string, mixed>
     */
    private function stepFromHttp(
        string $title,
        string $method,
        string $url,
        array $requestLogged,
        Response $response,
        float $durationSec,
    ): array {
        $json = $response->json();

        return [
            'title' => $title,
            'method' => $method,
            'url' => $url,
            'request' => $requestLogged,
            'response_status' => $response->status(),
            'response_body' => is_array($json) ? $json : $response->body(),
            'ok' => $response->successful(),
            'duration_ms' => (int) round($durationSec * 1000),
        ];
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    private function normalizeContainerStatusCode(?array $payload): ?string
    {
        if ($payload === null) {
            return null;
        }

        if (! empty($payload['status_code']) && is_string($payload['status_code'])) {
            return strtoupper(trim($payload['status_code']));
        }

        $status = $payload['status'] ?? null;
        if (is_string($status) && $status !== '') {
            return strtoupper(trim($status));
        }

        if (is_array($status)) {
            foreach (['status_code', 'name', 'coding'] as $key) {
                if (! empty($status[$key]) && is_string($status[$key])) {
                    return strtoupper(trim((string) $status[$key]));
                }
            }
        }

        return null;
    }
}
