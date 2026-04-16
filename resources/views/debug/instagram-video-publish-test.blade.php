<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Instagram photo / video publish test</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 64rem; margin: 2rem auto; padding: 0 1rem; line-height: 1.5; color: #18181b; }
        h1 { font-size: 1.35rem; margin-top: 0; }
        h2 { font-size: 1.05rem; margin-top: 2rem; }
        .warn { background: #fef3c7; border: 1px solid #f59e0b; padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 1rem; }
        .ok { background: #ecfdf5; border: 1px solid #10b981; padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 1rem; }
        .err { background: #fef2f2; border: 1px solid #ef4444; padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 1rem; }
        label { display: block; font-weight: 600; font-size: 0.875rem; margin-top: 1rem; }
        input[type="text"], input[type="file"], input[type="number"], select, textarea {
            width: 100%; max-width: 40rem; padding: 0.5rem 0.65rem; border: 1px solid #d4d4d8; border-radius: 6px; font-size: 0.9375rem; box-sizing: border-box;
        }
        input[type="file"] { padding: 0.4rem; background: #fff; }
        textarea { min-height: 4rem; font-family: inherit; }
        .row-inline { display: flex; gap: 1.5rem; flex-wrap: wrap; margin-top: 0.5rem; }
        .row-inline > div label { margin-top: 0; font-weight: 500; }
        .check { display: flex; align-items: flex-start; gap: 0.5rem; margin-top: 1rem; }
        .check input { width: auto; margin-top: 0.2rem; }
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
    <h1>Instagram photo / video — Content Publishing API test (live publish)</h1>
    <p class="muted">Save file under <code>public/uploads/instagram-publish-test/</code>. <strong>Photo:</strong> <code>image_url</code>, optional <code>alt_text</code> (per <a href="https://developers.facebook.com/docs/instagram-platform/instagram-graph-api/reference/ig-user/media/">IG User Media</a>), optional prep via <code>InstagramImagePrepService</code> → poll → <code>media_publish</code>. <strong>Video:</strong> <code>media_type=REELS</code> + <code>video_url</code> + <code>share_to_feed</code> (feed + Reels vs Reels-only) → poll → <code>media_publish</code>. Tokens are redacted in the log.</p>

    <div class="warn">
        <strong>Public URL required.</strong> Set <code>APP_URL</code> to an <strong>HTTPS</strong> origin Instagram can reach (production or a tunnel). Local-only URLs will fail when Meta downloads the video.
    </div>

    <div class="warn">
        <strong>This posts to the real account.</strong> Use a test Instagram account. The request may run several minutes while video is processed.
    </div>

    @php
        $displayError = $fatalError ?? session('fatalError');
    @endphp
    @if (!empty($displayError))
        <div class="err"><strong>Error:</strong> {{ $displayError }}</div>
    @endif

    @if (!empty($publishedMediaId) && empty($displayError))
        <div class="ok">
            <strong>Published.</strong> Instagram media id: <code>{{ $publishedMediaId }}</code>
        </div>
    @endif

    <h2>Publish</h2>
    @if ($accounts->isEmpty())
        <p class="err">No Instagram accounts. Connect one under <a href="{{ route('panel.accounts.instagram') }}">Accounts → Instagram</a>.</p>
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

            <label for="media">Photo or video file</label>
            <input type="file" name="media" id="media" required accept="image/jpeg,image/png,image/webp,image/gif,video/mp4,video/quicktime,video/webm,.jpg,.jpeg,.png,.webp,.gif,.mp4,.mov,.webm">
            <p class="muted">Max 100&nbsp;MB. Images: JPEG, PNG, WebP, GIF. Video: MP4 (H.264 + AAC) recommended.</p>
            @error('media')
                <p class="err" style="margin-top:0.5rem;padding:0.5rem">{{ $message }}</p>
            @enderror

            <label for="caption">Caption (optional)</label>
            <textarea name="caption" id="caption" maxlength="2200" placeholder="Caption">{{ old('caption') }}</textarea>

            <label for="alt_text">Alt text (optional, photos only, max 1000)</label>
            <input type="text" name="alt_text" id="alt_text" maxlength="1000" value="{{ old('alt_text') }}" placeholder="Accessibility description for the image">

            <div class="check">
                <input type="hidden" name="share_video_to_feed" value="0">
                <input type="checkbox" name="share_video_to_feed" id="share_video_to_feed" value="1" @checked((string) old('share_video_to_feed', '1') !== '0')>
                <label for="share_video_to_feed">For video uploads: also allow the reel in the main feed (<code>share_to_feed=true</code>). Uncheck for Reels tab only.</label>
            </div>

            <div class="row-inline">
                <div>
                    <label for="max_poll_attempts">Max status polls</label>
                    <input type="number" name="max_poll_attempts" id="max_poll_attempts" min="1" max="200" value="{{ old('max_poll_attempts', 200) }}">
                </div>
                <div>
                    <label for="poll_interval_seconds">Seconds between polls</label>
                    <input type="number" name="poll_interval_seconds" id="poll_interval_seconds" min="1" max="60" value="{{ old('poll_interval_seconds', 5) }}">
                </div>
            </div>

            <div class="check">
                <input type="hidden" name="confirm_publish" value="0">
                <input type="checkbox" name="confirm_publish" id="confirm_publish" value="1" @checked(old('confirm_publish'))>
                <label for="confirm_publish">I understand this will publish the photo or video to the selected Instagram account.</label>
            </div>
            @error('confirm_publish')
                <p class="err" style="margin-top:0.5rem;padding:0.5rem">{{ $message }}</p>
            @enderror

            <button type="submit">Upload &amp; publish</button>
        </form>
    @endif

    @if (is_array($steps ?? null) && count($steps) > 0)
        <h2>API steps ({{ count($steps) }})</h2>
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
        <code>/panel/debug/instagram-video-publish-test</code> · <a href="{{ route('panel.accounts') }}">← Accounts</a>
    </p>
</body>
</html>
