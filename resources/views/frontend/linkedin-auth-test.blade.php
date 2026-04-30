<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LinkedIn OAuth Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 24px;
            line-height: 1.4;
            color: #1f2937;
            background: #f9fafb;
        }
        .container {
            max-width: 960px;
            margin: 0 auto;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 20px;
        }
        .btn {
            display: inline-block;
            padding: 10px 14px;
            border-radius: 6px;
            background: #0a66c2;
            color: #fff;
            text-decoration: none;
            font-weight: 600;
        }
        .step {
            margin-top: 18px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            overflow: hidden;
        }
        .step-title {
            background: #f3f4f6;
            padding: 10px 12px;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: .04em;
        }
        pre {
            margin: 0;
            padding: 12px;
            background: #111827;
            color: #d1fae5;
            overflow-x: auto;
            font-size: 12px;
        }
        .empty {
            margin-top: 14px;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>LinkedIn Authentication Test Flow</h1>
        <p>Use this page to test LinkedIn OAuth login and inspect each response step.</p>
        <p>
            <a class="btn" href="{{ route('linkedin.test.start') }}">Start LinkedIn Login Test</a>
        </p>

        @if(empty($steps))
            <p class="empty">No test responses yet. Click "Start LinkedIn Login Test".</p>
        @else
            @foreach($steps as $step)
                <div class="step">
                    <div class="step-title">{{ $step['step'] ?? 'step' }}</div>
                    <pre>{{ json_encode($step['response'] ?? null, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                </div>
            @endforeach
        @endif
    </div>
</body>
</html>

