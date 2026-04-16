<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Instagram video publish API test</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 64rem; margin: 2rem auto; padding: 0 1rem; line-height: 1.5; color: #18181b; }
        h1 { font-size: 1.35rem; margin-top: 0; }
        h2 { font-size: 1.05rem; margin-top: 2rem; }
        .warn { background: #fef3c7; border: 1px solid #f59e0b; padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 1.25rem; }
        .ok { background: #ecfdf5; border: 1px solid #10b981; padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 1.25rem; }
        .err { background: #fef2f2; border: 1px solid #ef4444; padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 1.25rem; }
        label { display: block; font-weight: 600; font-size: 0.875rem; margin-top: 1rem; }
        input[type="text"], input[type="file"], input[type="number"], select, textarea {
            width: 100%; max-width: 40rem; padding: 0.5rem 0.65rem; border: 1px solid #d4d4d8; border-radius: 6px; font-size: 0.9375rem; box-sizing: border-box;
        }
        input[type="file"] { padding: 0.4rem; background: #fff; }
        textarea { min-height: 4rem; font-family: inherit; }
        .row-inline { display: flex; gap: 1.5rem; flex-wrap: wrap; margin-top: 0.5rem; }
        .row-inline > div label { margin-top: 0; font-weight: 500; }
        .check { display: flex; align-items: center; gap: 0.5rem; margin-top: 1rem; }
        .check input { width: auto; }
        .check label { margin-top: 0; font-weight: 500; }
        button[type="submit"] {
            margin-top: 1.25rem; padding: 0.6rem 1.25rem; background: #18181b; color: #fff; border: none; border-radius: 8px; font-size: 0.9375rem; cursor: pointer;
        }
        button[type="submit"]:hover { background: #27272a; }
        .muted { color: #71717a; font-size: 0.8125rem; }
        .step {
            border: 1px solid #e4e4e7; border-radius: 8px; margin-bottom: 1rem; overflow: hidden;
        }
        .step-head {
            padding: 0.65rem 1rem; background: #fafafa; border-bottom: 1px solid #e4e4e7;
            display: flex; flex-wrap: wrap; align-items: baseline; gap: 0.5rem 1rem; font-size: 0.875rem;
        }
        .step-head strong { font-size: 0.9375rem; }
        .badge { font-size: 0.75rem; padding: 0.15rem 0.45rem; border-radius: 4px; font-weight: 600; }
        .badge-ok { background: #d1fae5; color: #065f46; }
        .badge-bad { background: #fee2e2; color: #991b1b; }
        .step-body { padding: 1rem; }
        pre {
            background: #f4f4f5; padding: 0.85rem; border-radius: 6px; overflow-x: auto; font-size: 0.8125rem; margin: 0.5rem 0 0; white-space: pre-wrap; word-break: break-word;
        }
        .step-meta { color: #52525b; font-size: 0.8125rem; }
        code { word-break: break-all; }
        a { color: #2563eb; }
    </style>
</head>
<body>
    <h1>Instagram video / Reels — Content Publishing API test</h1>
    <p class="muted">Your file is saved under <code>public/uploads/instagram-video-test/</code> and exposed as <code>APP_URL</code> + that path so Meta can use it as <code>video_url</code>. Then: <code>POST …/media</code> → poll → optional <code>media_publish</code>. Tokens are redacted below.</p>

    <div class="warn">
        <strong>HTTPS &amp; reachability.</strong> <code>APP_URL</code> must be a URL Instagram’s servers can load (production domain or a tunnel such as ngrok). Local <code>http://127.0.0.1</code> will not work. The uploaded file is deleted after this request finishes.
    </div>

    <div class="warn">
        <strong>Use a test account.</strong> Unchecking “stop before media_publish” posts to Instagram. The browser may wait a long time while Meta transcodes video.
    </div>

    @php
        $displayError = $fatalError ?? session('fatalError');
    @endphp
    @if (!empty($displayError))
        <div class="err"><strong>Stopped:</strong> {{ $displayError }}</div>
    @endif

    @if (is_array($steps ?? null) && count($steps) > 0 && empty($displayError))
        @php
            $last = $steps[count($steps) - 1];
            $lastTitle = (string) ($last['title'] ?? '');
            $doneSkipped = str_contains($lastTitle, 'skipped');
            $donePublish = str_contains($lastTitle, 'media_publish') && ! str_contains($lastTitle, 'skipped') && ! empty($last['ok']);
        @endphp
        @if ($doneSkipped || $donePublish)
            <div class="ok">
                <strong>Completed successfully.</strong>
                @if ($doneSkipped) Container is FINISHED; publish was skipped. @else Published media id in the last response. @endif
            </div>
        @endif
    @endif

    <h2>Run test</h2>
    @if ($accounts->isEmpty())
        <p class="err">No Instagram accounts for this user. Connect one under <a href="{{ route('panel.accounts.instagram') }}">Accounts → Instagram</a>.</p>
    @else
        <form method="post" action="{{ route('panel.debug.instagram-video-publish-test.run') }}" enctype="multipart/form-data">
            @csrf
            <label for="instagram_account_id">Instagram account</label>
            <select name="instagram_account_id" id="instagram_account_id" required>
                @foreach ($accounts as $acc)
                    <option value="{{ $acc->id }}" @selected((int) old('instagram_account_id', $accounts->first()->id) === (int) $acc->id)>
                        @ {{ $acc->username ?? '—' }} (ig_user_id {{ $acc->ig_user_id }})
                    </option>
                @endforeach
            </select>

            <label for="video">Video file</label>
            <input type="file" name="video" id="video" required accept="video/mp4,video/quicktime,video/webm,.mp4,.mov,.webm">
            <p class="muted">Max 100&nbsp;MB. MP4 (H.264) recommended for Instagram.</p>
            @error('video')
                <p class="err" style="margin-top:0.5rem;padding:0.5rem">{{ $message }}</p>
            @enderror

            <label for="caption">Caption (optional)</label>
            <textarea name="caption" id="caption" maxlength="2200" placeholder="Optional caption">{{ old('caption') }}</textarea>

            <div class="row-inline">
                <div>
                    <label for="max_poll_attempts">Max status polls</label>
                    <input type="number" name="max_poll_attempts" id="max_poll_attempts" min="1" max="200" value="{{ old('max_poll_attempts', 80) }}">
                </div>
                <div>
                    <label for="poll_interval_seconds">Seconds between polls</label>
                    <input type="number" name="poll_interval_seconds" id="poll_interval_seconds" min="1" max="60" value="{{ old('poll_interval_seconds', 5) }}">
                </div>
            </div>

            <div class="check">
                <input type="hidden" name="stop_before_media_publish" value="0">
                <input type="checkbox" name="stop_before_media_publish" id="stop_before_media_publish" value="1" @checked((string) old('stop_before_media_publish', '1') === '1')>
                <label for="stop_before_media_publish">Stop before <code>media_publish</code> (create + wait until FINISHED only)</label>
            </div>

            <button type="submit">Run API test</button>
        </form>
    @endif

    @if (is_array($steps ?? null) && count($steps) > 0)
        <h2>Steps ({{ count($steps) }})</h2>
        @foreach ($steps as $step)
            <div class="step">
                <div class="step-head">
                    <strong>{{ $step['title'] ?? 'Step' }}</strong>
                    <span class="muted">{{ $step['method'] ?? '' }}</span>
                    @if (array_key_exists('ok', $step) && $step['ok'] !== null)
                        <span class="badge {{ !empty($step['ok']) ? 'badge-ok' : 'badge-bad' }}">{{ !empty($step['ok']) ? 'OK' : 'Error' }}</span>
                    @endif
                    @if (!empty($step['duration_ms']))
                        <span class="step-meta">{{ $step['duration_ms'] }} ms</span>
                    @endif
                </div>
                <div class="step-body">
                    @if (!empty($step['url']))
                        <div class="step-meta"><strong>URL</strong></div>
                        <pre>{{ $step['url'] }}</pre>
                    @endif
                    @if (!empty($step['request']))
                        <div class="step-meta" style="margin-top:0.75rem"><strong>Request</strong></div>
                        <pre>{{ json_encode($step['request'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
                    @endif
                    @if (array_key_exists('response_status', $step) && $step['response_status'] !== null)
                        <div class="step-meta" style="margin-top:0.75rem"><strong>HTTP status</strong> {{ $step['response_status'] }}</div>
                    @endif
                    @if (isset($step['response_body']))
                        <div class="step-meta" style="margin-top:0.75rem"><strong>Response body</strong></div>
                        <pre>@if (is_array($step['response_body'])){{ json_encode($step['response_body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}@else{{ $step['response_body'] }}@endif</pre>
                    @endif
                </div>
            </div>
        @endforeach
    @endif

    <p class="muted" style="margin-top:2rem">
        Route: <code>/panel/debug/instagram-video-publish-test</code> · <a href="{{ route('panel.accounts') }}">← Accounts</a>
    </p>
</body>
</html>
