<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\InstagramAccount;
use App\Models\User;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

/**
 * Debug: upload a video file, expose it as a public video_url, run Instagram Content Publishing
 * (REELS + share_to_feed → poll container → media_publish) and record each HTTP step.
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
            'publishedMediaId' => null,
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
            'video' => [
                'required',
                'file',
                'max:'.(100 * 1024),
                'mimes:mp4,mov,webm,m4v',
            ],
            'caption' => ['nullable', 'string', 'max:2200'],
            'confirm_publish' => ['accepted'],
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

        $maxPoll = (int) ($validated['max_poll_attempts'] ?? 200);
        $sleepSec = (int) ($validated['poll_interval_seconds'] ?? 5);

        set_time_limit(min(3600, $maxPoll * $sleepSec + 900));

        $accounts = InstagramAccount::query()
            ->where('user_id', $ownerId)
            ->orderBy('username')
            ->get();

        $tempAbsolutePath = null;
        $steps = [];
        $publishedMediaId = null;
        $fatalError = null;

        try {
            /** @var UploadedFile $uploaded */
            $uploaded = $request->file('video');
            $stored = $this->storeTestVideoUpload($uploaded);
            $tempAbsolutePath = $stored['absolute_path'];
            $videoUrl = $stored['public_https_url'];

            $base = $this->graphBaseUrl($ig);
            $igUserId = (string) $ig->ig_user_id;

            $steps[] = $this->stepMeta(
                '0. Save upload → public video_url (Meta fetches this URL)',
                $videoUrl,
                [
                    'original_name' => $uploaded->getClientOriginalName(),
                    'mime' => $uploaded->getMimeType(),
                    'size_bytes' => $uploaded->getSize(),
                    'saved_path' => $stored['public_relative_path'],
                    'note' => 'APP_URL must be HTTPS and reachable from the public internet so Instagram can download the file.',
                ]
            );

            $includeCopyrightCheck = str_contains($base, 'graph.facebook.com');
            $statusFields = 'id,status_code,status'.($includeCopyrightCheck ? ',copyright_check_status' : '');

            $createPayload = [
                'media_type' => 'REELS',
                'video_url' => $videoUrl,
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
                '1. Create media container',
                'POST',
                $createUrl,
                $this->redactToken($createPayload),
                $createResp,
                microtime(true) - $t0
            );

            if (! $createResp->successful()) {
                $fatalError = 'Create container failed: '.$this->formatGraphError($createResp);

                return view('debug.instagram-video-publish-test', compact('accounts', 'steps', 'fatalError', 'publishedMediaId'));
            }

            $creationId = $createResp->json('id');
            if (empty($creationId)) {
                $fatalError = 'Create response missing container id.';

                return view('debug.instagram-video-publish-test', compact('accounts', 'steps', 'fatalError', 'publishedMediaId'));
            }

            $creationId = (string) $creationId;

            $lastPayload = null;
            $lastCode = null;
            $pollFatal = null;

            for ($i = 0; $i < $maxPoll; $i++) {
                $statusUrl = "{$base}/{$creationId}";
                $query = ['fields' => $statusFields, 'access_token' => $token];
                $t1 = microtime(true);
                $statusResp = Http::acceptJson()
                    ->timeout(90)
                    ->get($statusUrl, $query);
                $steps[] = $this->stepFromHttp(
                    '2.'.($i + 1).'. GET container status',
                    'GET',
                    $statusUrl.'?'.$this->queryStringForLog($query),
                    ['fields' => $statusFields, 'access_token' => '***REDACTED***'],
                    $statusResp,
                    microtime(true) - $t1
                );

                if (! $statusResp->successful()) {
                    $http = $statusResp->status();
                    if (in_array($http, [400, 401, 403], true)) {
                        $pollFatal = 'Could not read container status: '.$this->formatGraphError($statusResp);
                        break;
                    }
                    sleep($sleepSec);

                    continue;
                }

                $payload = $statusResp->json();
                $lastPayload = is_array($payload) ? $payload : null;
                $code = $this->normalizeContainerStatusCode($lastPayload);
                $lastCode = $code;

                if ($code === 'FINISHED') {
                    $pollFatal = null;
                    break;
                }

                if (in_array($code, ['ERROR', 'EXPIRED'], true)) {
                    if ($code === 'ERROR') {
                        sleep(10);
                        $tRec = microtime(true);
                        $recheck = Http::acceptJson()
                            ->timeout(90)
                            ->get($statusUrl, $query);
                        $steps[] = $this->stepFromHttp(
                            '2.'.($i + 1).'b. Recheck after ERROR (Meta may recover)',
                            'GET',
                            $statusUrl.'?'.$this->queryStringForLog($query),
                            ['fields' => $statusFields, 'access_token' => '***REDACTED***'],
                            $recheck,
                            microtime(true) - $tRec
                        );
                        if ($recheck->successful()) {
                            $recheckPayload = $recheck->json();
                            if (is_array($recheckPayload)) {
                                $lastPayload = $recheckPayload;
                                $retryCode = $this->normalizeContainerStatusCode($recheckPayload);
                                if ($retryCode === 'FINISHED') {
                                    $pollFatal = null;
                                    $lastCode = 'FINISHED';
                                    break;
                                }
                                $lastCode = $retryCode ?? $lastCode;
                            }
                        }
                    }

                    if ($lastCode !== 'FINISHED') {
                        $pollFatal = 'Container status '.($lastCode ?? $code ?? 'unknown').'. Response: '.(is_array($lastPayload) ? json_encode($lastPayload) : '');
                        break;
                    }
                }

                sleep($sleepSec);
            }

            if ($pollFatal !== null) {
                $fatalError = $pollFatal;

                return view('debug.instagram-video-publish-test', compact('accounts', 'steps', 'fatalError', 'publishedMediaId'));
            }

            if ($lastCode !== 'FINISHED') {
                $fatalError = 'Timed out waiting for FINISHED. Last status: '.($lastCode ?? 'unknown').'.';

                return view('debug.instagram-video-publish-test', compact('accounts', 'steps', 'fatalError', 'publishedMediaId'));
            }

            if ($tempAbsolutePath !== null && is_file($tempAbsolutePath)) {
                @unlink($tempAbsolutePath);
                $tempAbsolutePath = null;
            }

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
                '3. media_publish (live post)',
                'POST',
                $publishUrl,
                $this->redactToken($publishPayload),
                $publishResp,
                microtime(true) - $t2
            );

            if (! $publishResp->successful()) {
                $fatalError = 'media_publish failed: '.$this->formatGraphError($publishResp);
            } else {
                $publishedMediaId = $publishResp->json('id');
                if (empty($publishedMediaId)) {
                    $fatalError = 'media_publish response missing published media id.';
                }
            }
        } finally {
            if ($tempAbsolutePath !== null && is_file($tempAbsolutePath)) {
                @unlink($tempAbsolutePath);
            }
        }

        return view('debug.instagram-video-publish-test', [
            'accounts' => $accounts,
            'steps' => $steps,
            'fatalError' => $fatalError,
            'publishedMediaId' => $publishedMediaId,
        ]);
    }

    /**
     * @return array{absolute_path: string, public_relative_path: string, public_https_url: string}
     */
    private function storeTestVideoUpload(UploadedFile $file): array
    {
        $relativeDir = 'uploads/instagram-video-test';
        $dir = public_path($relativeDir);
        if (! is_dir($dir)) {
            if (! mkdir($dir, 0755, true) && ! is_dir($dir)) {
                throw new \RuntimeException('Could not create directory: '.$dir);
            }
        }

        $ext = strtolower((string) $file->getClientOriginalExtension());
        if ($ext === '' || ! in_array($ext, ['mp4', 'mov', 'webm', 'mpeg', 'm4v'], true)) {
            $ext = 'mp4';
        }

        $basename = uniqid('igpub_', true).'.'.$ext;
        $file->move($dir, $basename);

        $absolutePath = $dir.DIRECTORY_SEPARATOR.$basename;
        $publicRelative = $relativeDir.'/'.$basename;
        $url = rtrim((string) config('app.url'), '/').'/'.$publicRelative;
        if (preg_match('#^http://#i', $url) === 1) {
            $url = preg_replace('#^http://#i', 'https://', $url);
        }

        return [
            'absolute_path' => $absolutePath,
            'public_relative_path' => $publicRelative,
            'public_https_url' => $url,
        ];
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
     * @param  array<string, mixed>  $info
     * @return array<string, mixed>
     */
    private function stepMeta(string $title, string $url, array $info): array
    {
        return [
            'title' => $title,
            'method' => '—',
            'url' => $url,
            'request' => $info,
            'response_status' => null,
            'response_body' => 'File stored. Use the URL above as video_url in step 1.',
            'ok' => true,
            'duration_ms' => null,
        ];
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
        return http_build_query($this->redactToken($query));
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

    private function formatGraphError(Response $response): string
    {
        $json = $response->json();
        if (! is_array($json) || empty($json['error']) || ! is_array($json['error'])) {
            return $response->body() ?: 'Instagram Graph API request failed.';
        }

        $err = $json['error'];
        $parts = [];
        if (! empty($err['message'])) {
            $parts[] = (string) $err['message'];
        }
        if (! empty($err['error_user_msg'])) {
            $parts[] = (string) $err['error_user_msg'];
        }
        if (isset($err['error_subcode'])) {
            $parts[] = '(error_subcode: '.$err['error_subcode'].')';
        }

        $msg = implode(' ', array_filter(array_unique($parts)));

        return $msg !== '' ? $msg : ($response->body() ?: 'Instagram Graph API request failed.');
    }
}
