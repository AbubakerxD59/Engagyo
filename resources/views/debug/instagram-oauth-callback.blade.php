<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Instagram OAuth callback (debug)</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 56rem; margin: 2rem auto; padding: 0 1rem; line-height: 1.5; }
        h1 { font-size: 1.25rem; }
        .warn { background: #fff3cd; border: 1px solid #ffc107; padding: 0.75rem 1rem; border-radius: 6px; margin-bottom: 1.5rem; }
        pre { background: #f4f4f5; padding: 1rem; border-radius: 6px; overflow-x: auto; font-size: 0.875rem; }
        table { border-collapse: collapse; width: 100%; font-size: 0.875rem; }
        th, td { text-align: left; border: 1px solid #e4e4e7; padding: 0.5rem 0.75rem; vertical-align: top; }
        th { background: #fafafa; }
        code { word-break: break-all; }
        .muted { color: #71717a; font-size: 0.8125rem; }
    </style>
</head>
<body>
    <h1>Instagram Login — redirect callback (test)</h1>
    <p class="warn">
        <strong>INSTAGRAM_LOGIN_CALLBACK_DEBUG</strong> is enabled. OAuth is <strong>not</strong> completed on this request.
        Turn the flag off in <code>.env</code> for normal behavior.
    </p>

    <p class="muted">Request: <code>{{ $method }}</code> · Full URL below</p>
    <pre>{{ $fullUrl }}</pre>

    <h2>Query string parameters</h2>
    @if (empty($query))
        <p class="muted">No query parameters.</p>
    @else
        <table>
            <thead>
                <tr><th>Name</th><th>Value</th></tr>
            </thead>
            <tbody>
                @foreach ($query as $name => $value)
                    <tr>
                        <td><code>{{ $name }}</code></td>
                        <td><code>{{ is_scalar($value) ? $value : json_encode($value) }}</code></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <h2>All request input</h2>
    <pre>{{ json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>

    <h2>OAuth <code>state</code> check (session not consumed)</h2>
    <table>
        <tbody>
            <tr>
                <th><code>state</code> from redirect</th>
                <td><code>{{ $stateFromRequest !== '' ? $stateFromRequest : '(empty)' }}</code></td>
            </tr>
            <tr>
                <th><code>instagram_oauth_state</code> in session</th>
                <td><code>{{ $sessionState !== null && $sessionState !== '' ? $sessionState : '(missing or empty)' }}</code></td>
            </tr>
            <tr>
                <th>Match</th>
                <td><strong>{{ $stateMatches ? 'yes' : 'no' }}</strong></td>
            </tr>
        </tbody>
    </table>

    <p class="muted" style="margin-top: 2rem;">
        Authenticated as panel user: {{ $authenticated ? 'yes' : 'no' }}
    </p>
</body>
</html>
