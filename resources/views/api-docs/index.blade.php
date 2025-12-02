<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'Engagyo') }} - API Documentation</title>
    <link
        href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600&family=Inter:wght@400;500;600;700&display=swap"
        rel="stylesheet">
    <style>
        :root {
            --bg-primary: #0d1117;
            --bg-secondary: #161b22;
            --bg-tertiary: #21262d;
            --bg-hover: #1c2128;
            --border-color: #30363d;
            --text-primary: #e6edf3;
            --text-secondary: #8b949e;
            --text-muted: #6e7681;
            --accent-blue: #58a6ff;
            --accent-green: #3fb950;
            --accent-purple: #a371f7;
            --accent-orange: #d29922;
            --accent-red: #f85149;
            --accent-cyan: #39c5cf;
            --method-get: #3fb950;
            --method-post: #58a6ff;
            --method-put: #d29922;
            --method-patch: #a371f7;
            --method-delete: #f85149;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            line-height: 1.6;
            height: 100vh;
            overflow: hidden;
        }

        /* Navbar */
        .navbar {
            height: 60px;
            background: var(--bg-secondary);
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 24px;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 100;
        }

        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }

        .navbar-brand-icon {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, var(--accent-blue), var(--accent-purple));
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .navbar-brand-text {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-primary);
        }

        .navbar-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .nav-btn {
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.15s ease;
            cursor: pointer;
            border: none;
        }

        .nav-btn-outline {
            background: transparent;
            border: 1px solid var(--border-color);
            color: var(--text-primary);
        }

        .nav-btn-outline:hover {
            background: var(--bg-tertiary);
            border-color: var(--text-muted);
        }

        .nav-btn-primary {
            background: var(--accent-blue);
            color: white;
        }

        .nav-btn-primary:hover {
            background: #4c9aed;
        }

        .nav-btn-success {
            background: var(--accent-green);
            color: white;
        }

        .nav-btn-success:hover {
            background: #36a348;
        }

        /* Layout */
        .layout {
            display: flex;
            height: calc(100vh - 60px);
            margin-top: 60px;
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            width: 380px;
            min-width: 380px;
            background: var(--bg-secondary);
            border-right: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
            height: calc(100vh - 60px);
            overflow: hidden;
        }

        .sidebar-header {
            padding: 16px;
            border-bottom: 1px solid var(--border-color);
            background: var(--bg-secondary);
        }

        .sidebar-title {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 12px;
            color: var(--text-secondary);
            font-size: 13px;
            font-weight: 600;
        }

        .sidebar-title svg {
            color: var(--accent-blue);
        }

        .version-tag {
            background: var(--bg-tertiary);
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 11px;
            color: var(--accent-green);
            margin-left: auto;
        }

        .base-url {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .base-url-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-muted);
        }

        .base-url-box {
            background: var(--bg-primary);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            padding: 8px 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .base-url-box code {
            font-family: 'JetBrains Mono', monospace;
            font-size: 11px;
            color: var(--accent-cyan);
            flex: 1;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .base-url-box .copy-btn {
            background: transparent;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            padding: 4px;
            border-radius: 4px;
            display: flex;
            align-items: center;
        }

        .base-url-box .copy-btn:hover {
            color: var(--accent-blue);
            background: var(--bg-tertiary);
        }

        /* Endpoints List */
        .endpoints-list {
            flex: 1;
            overflow-y: auto;
            padding: 12px;
        }

        .category-title {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-muted);
            padding: 12px 12px 8px;
            margin-top: 8px;
        }

        .category-title:first-child {
            margin-top: 0;
        }

        .endpoint-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.15s ease;
            margin-bottom: 4px;
        }

        .endpoint-item:hover {
            background: var(--bg-hover);
        }

        .endpoint-item.active {
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
        }

        .method-badge {
            font-family: 'JetBrains Mono', monospace;
            font-size: 10px;
            font-weight: 600;
            padding: 4px 8px;
            border-radius: 4px;
            min-width: 50px;
            text-align: center;
            text-transform: uppercase;
        }

        .method-get {
            background: rgba(63, 185, 80, 0.15);
            color: var(--method-get);
        }

        .method-post {
            background: rgba(88, 166, 255, 0.15);
            color: var(--method-post);
        }

        .method-put {
            background: rgba(210, 153, 34, 0.15);
            color: var(--method-put);
        }

        .method-patch {
            background: rgba(163, 113, 247, 0.15);
            color: var(--method-patch);
        }

        .method-delete {
            background: rgba(248, 81, 73, 0.15);
            color: var(--method-delete);
        }

        .endpoint-info {
            flex: 1;
            min-width: 0;
        }

        .endpoint-path {
            font-family: 'JetBrains Mono', monospace;
            font-size: 13px;
            color: var(--text-primary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .endpoint-desc {
            font-size: 12px;
            color: var(--text-secondary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-top: 2px;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            overflow-y: auto;
            background: var(--bg-primary);
        }

        .content-wrapper {
            max-width: 900px;
            margin: 0 auto;
            padding: 32px 40px;
        }

        /* Welcome Screen */
        .welcome-screen {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: calc(100vh - 124px);
            text-align: center;
            color: var(--text-secondary);
        }

        .welcome-screen svg {
            width: 80px;
            height: 80px;
            color: var(--text-muted);
            margin-bottom: 24px;
        }

        .welcome-screen h2 {
            font-size: 24px;
            color: var(--text-primary);
            margin-bottom: 8px;
        }

        .welcome-screen p {
            font-size: 14px;
            max-width: 400px;
        }

        /* Endpoint Detail */
        .endpoint-detail {
            display: none;
        }

        .endpoint-detail.active {
            display: block;
        }

        .detail-header {
            margin-bottom: 32px;
        }

        .detail-header .method-badge {
            font-size: 12px;
            padding: 6px 12px;
            margin-bottom: 12px;
            display: inline-block;
        }

        .detail-header h2 {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 8px;
            font-family: 'JetBrains Mono', monospace;
            color: var(--text-primary);
        }

        .detail-header p {
            font-size: 16px;
            color: var(--text-secondary);
        }

        /* Sections */
        .detail-section {
            margin-bottom: 32px;
        }

        .section-title {
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-secondary);
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-title svg {
            width: 16px;
            height: 16px;
        }

        /* Parameters Table */
        .params-table {
            width: 100%;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            overflow: hidden;
        }

        .params-table th,
        .params-table td {
            padding: 12px 16px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        .params-table th {
            background: var(--bg-tertiary);
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-secondary);
        }

        .params-table tr:last-child td {
            border-bottom: none;
        }

        .params-table td {
            font-size: 13px;
            background: var(--bg-secondary);
        }

        .param-name {
            font-family: 'JetBrains Mono', monospace;
            color: var(--accent-cyan);
        }

        .param-type {
            font-family: 'JetBrains Mono', monospace;
            font-size: 12px;
            color: var(--accent-purple);
        }

        .param-required {
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: 500;
        }

        .param-required.yes {
            background: rgba(248, 81, 73, 0.15);
            color: var(--accent-red);
        }

        .param-required.no {
            background: rgba(139, 148, 158, 0.15);
            color: var(--text-secondary);
        }

        /* Code Block */
        .code-block {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            overflow: hidden;
        }

        .code-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 16px;
            background: var(--bg-tertiary);
            border-bottom: 1px solid var(--border-color);
        }

        .code-header span {
            font-size: 12px;
            font-weight: 500;
            color: var(--text-secondary);
        }

        .code-header .copy-btn {
            background: transparent;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 4px;
            transition: all 0.15s;
        }

        .code-header .copy-btn:hover {
            background: var(--bg-hover);
            color: var(--accent-blue);
        }

        .code-header .copy-btn.copied {
            color: var(--accent-green);
        }

        .code-content {
            padding: 16px;
            overflow-x: auto;
        }

        .code-content pre {
            margin: 0;
            font-family: 'JetBrains Mono', monospace;
            font-size: 13px;
            line-height: 1.6;
        }

        .json-key {
            color: var(--accent-purple);
        }

        .json-string {
            color: var(--accent-green);
        }

        .json-number {
            color: var(--accent-orange);
        }

        .json-boolean {
            color: var(--accent-blue);
        }

        .json-null {
            color: var(--text-muted);
        }

        /* Notes */
        .notes-box {
            background: rgba(88, 166, 255, 0.1);
            border: 1px solid rgba(88, 166, 255, 0.3);
            border-radius: 8px;
            padding: 16px;
            margin-top: 16px;
        }

        .notes-box strong {
            color: var(--accent-blue);
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 8px;
        }

        .notes-box p {
            color: var(--text-secondary);
            font-size: 13px;
            margin: 0;
        }

        /* Status Badge */
        .status-badge {
            font-size: 11px;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: 500;
        }

        .status-200 {
            background: rgba(63, 185, 80, 0.15);
            color: var(--accent-green);
        }

        .status-401 {
            background: rgba(248, 81, 73, 0.15);
            color: var(--accent-red);
        }

        /* Toast */
        .toast {
            position: fixed;
            bottom: 24px;
            right: 24px;
            background: var(--accent-green);
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            transform: translateY(100px);
            opacity: 0;
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .toast.show {
            transform: translateY(0);
            opacity: 1;
        }

        /* Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: var(--bg-primary);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--border-color);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--text-muted);
        }

        /* Responsive */
        @media (max-width: 900px) {
            .sidebar {
                width: 320px;
                min-width: 320px;
            }
        }

        @media (max-width: 768px) {
            .navbar {
                padding: 0 16px;
            }

            .navbar-brand-text {
                display: none;
            }

            .nav-btn {
                padding: 8px 12px;
                font-size: 13px;
            }

            .layout {
                flex-direction: column;
                height: calc(100vh - 60px);
            }

            .sidebar {
                width: 100%;
                min-width: 100%;
                height: auto;
                max-height: 45vh;
            }

            .main-content {
                height: 55vh;
            }

            .content-wrapper {
                padding: 24px 20px;
            }
        }

        @media (max-width: 480px) {
            .navbar-actions .nav-btn span {
                display: none;
            }

            .nav-btn {
                padding: 8px 10px;
            }
        }
    </style>
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar">
        <a href="{{ route('frontend.home') }}" class="navbar-brand">
            <div class="navbar-brand-icon">⚡</div>
            <span class="navbar-brand-text">{{ config('app.name', 'Engagyo') }}</span>
        </a>
        <div class="navbar-actions">
            @auth
                <a href="{{ route('panel.schedule') }}" class="nav-btn nav-btn-success">
                    <span>Dashboard</span>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2" style="margin-left: 4px;">
                        <path d="M5 12h14M12 5l7 7-7 7" />
                    </svg>
                </a>
            @else
                <a href="{{ route('frontend.showLogin') }}" class="nav-btn nav-btn-outline">
                    <span>Login</span>
                </a>
                <a href="{{ route('frontend.showRegister') }}" class="nav-btn nav-btn-primary">
                    <span>Sign Up</span>
                </a>
            @endauth
        </div>
    </nav>

    <div class="layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-title">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                    </svg>
                    <span>API Endpoints</span>
                    <span class="version-tag">v1.0</span>
                </div>
                <div class="base-url">
                    <span class="base-url-label">Base URL</span>
                    <div class="base-url-box">
                        <code id="baseUrl">{{ $baseUrl }}</code>
                        <button class="copy-btn" onclick="copyToClipboard('{{ $baseUrl }}')" title="Copy Base URL">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                                <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <div class="endpoints-list">
                @php
                    $currentCategory = '';
                @endphp

                @foreach ($endpoints as $index => $endpoint)
                    @if ($endpoint['category'] !== $currentCategory)
                        @php $currentCategory = $endpoint['category']; @endphp
                        <div class="category-title">{{ $currentCategory }}</div>
                    @endif

                    <div class="endpoint-item" data-endpoint="{{ $endpoint['id'] }}"
                        onclick="showEndpoint('{{ $endpoint['id'] }}')">
                        <span class="method-badge method-{{ strtolower($endpoint['method']) }}">
                            {{ $endpoint['method'] }}
                        </span>
                        <div class="endpoint-info">
                            <div class="endpoint-path">{{ $endpoint['endpoint'] }}</div>
                            <div class="endpoint-desc">{{ $endpoint['description'] }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Welcome Screen -->
            <div class="welcome-screen" id="welcomeScreen">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                    <polyline points="10 9 9 9 8 9"></polyline>
                </svg>
                <h2>Welcome to the API Docs</h2>
                <p>Select an endpoint from the sidebar to view its documentation, including request parameters and
                    response examples.</p>
            </div>

            <!-- Endpoint Details -->
            @foreach ($endpoints as $endpoint)
                <div class="endpoint-detail" id="detail-{{ $endpoint['id'] }}">
                    <div class="content-wrapper">
                        <div class="detail-header">
                            <span class="method-badge method-{{ strtolower($endpoint['method']) }}">
                                {{ $endpoint['method'] }}
                            </span>
                            <h2>{{ $endpoint['endpoint'] }}</h2>
                            <p>{{ $endpoint['description'] }}</p>
                        </div>

                        <!-- Parameters -->
                        @if (!empty($endpoint['parameters']))
                            <div class="detail-section">
                                <h3 class="section-title">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <line x1="12" y1="16" x2="12" y2="12"></line>
                                        <line x1="12" y1="8" x2="12.01" y2="8"></line>
                                    </svg>
                                    Parameters
                                </h3>
                                <table class="params-table">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Type</th>
                                            <th>Required</th>
                                            <th>Description</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($endpoint['parameters'] as $param)
                                            <tr>
                                                <td><span class="param-name">{{ $param['name'] }}</span></td>
                                                <td><span class="param-type">{{ $param['type'] }}</span></td>
                                                <td>
                                                    <span
                                                        class="param-required {{ $param['required'] ? 'yes' : 'no' }}">
                                                        {{ $param['required'] ? 'Required' : 'Optional' }}
                                                    </span>
                                                </td>
                                                <td>{{ $param['description'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif

                        <!-- Request -->
                        <div class="detail-section">
                            <h3 class="section-title">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M22 2L11 13"></path>
                                    <path d="M22 2L15 22L11 13L2 9L22 2Z"></path>
                                </svg>
                                Request
                            </h3>

                            @if (isset($endpoint['request']['curl']))
                                <div class="code-block" style="margin-bottom: 16px;">
                                    <div class="code-header">
                                        <span>cURL</span>
                                        <button class="copy-btn"
                                            onclick="copyCode(this, `{{ str_replace('{base_url}', $baseUrl, $endpoint['request']['curl']) }}`)">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                                                stroke="currentColor" stroke-width="2">
                                                <rect x="9" y="9" width="13" height="13" rx="2"
                                                    ry="2"></rect>
                                                <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1">
                                                </path>
                                            </svg>
                                            Copy
                                        </button>
                                    </div>
                                    <div class="code-content">
                                        <pre>{{ str_replace('{base_url}', $baseUrl, $endpoint['request']['curl']) }}</pre>
                                    </div>
                                </div>
                            @endif

                            <div class="code-block">
                                <div class="code-header">
                                    <span>Headers</span>
                                </div>
                                <div class="code-content">
                                    <pre>
@foreach ($endpoint['request']['headers'] as $key => $value)
<span class="json-key">{{ $key }}</span>: <span class="json-string">{{ $value }}</span>
@endforeach
</pre>
                                </div>
                            </div>

                            @if (isset($endpoint['request']['body']) && $endpoint['method'] !== 'GET')
                                <div class="code-block" style="margin-top: 16px;">
                                    <div class="code-header">
                                        <span>Body</span>
                                        <button class="copy-btn"
                                            onclick="copyCode(this, '{{ json_encode($endpoint['request']['body'], JSON_PRETTY_PRINT) }}')">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                                                stroke="currentColor" stroke-width="2">
                                                <rect x="9" y="9" width="13" height="13" rx="2"
                                                    ry="2"></rect>
                                                <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1">
                                                </path>
                                            </svg>
                                            Copy
                                        </button>
                                    </div>
                                    <div class="code-content">
                                        <pre>{!! formatJson($endpoint['request']['body']) !!}</pre>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <!-- Response -->
                        <div class="detail-section">
                            <h3 class="section-title">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                                </svg>
                                Response
                            </h3>
                            <div class="code-block">
                                <div class="code-header">
                                    <span>JSON Response</span>
                                    <div style="display: flex; align-items: center; gap: 12px;">
                                        <span class="status-badge status-200">200 OK</span>
                                        <button class="copy-btn"
                                            onclick="copyCode(this, '{{ json_encode($endpoint['response'], JSON_PRETTY_PRINT) }}')">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                                                stroke="currentColor" stroke-width="2">
                                                <rect x="9" y="9" width="13" height="13" rx="2"
                                                    ry="2"></rect>
                                                <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1">
                                                </path>
                                            </svg>
                                            Copy
                                        </button>
                                    </div>
                                </div>
                                <div class="code-content">
                                    <pre>{!! formatJson($endpoint['response']) !!}</pre>
                                </div>
                            </div>

                            @if (isset($endpoint['notes']))
                                <div class="notes-box">
                                    <strong>
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor" stroke-width="2">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <line x1="12" y1="16" x2="12" y2="12">
                                            </line>
                                            <line x1="12" y1="8" x2="12.01" y2="8">
                                            </line>
                                        </svg>
                                        Note
                                    </strong>
                                    <p>{{ $endpoint['notes'] }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </main>
    </div>

    <!-- Toast -->
    <div class="toast" id="toast">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
            stroke-width="2">
            <polyline points="20 6 9 17 4 12"></polyline>
        </svg>
        Copied to clipboard!
    </div>

    <script>
        let activeEndpoint = null;

        function showEndpoint(id) {
            // Hide welcome screen
            document.getElementById('welcomeScreen').style.display = 'none';

            // Remove active class from all items
            document.querySelectorAll('.endpoint-item').forEach(item => {
                item.classList.remove('active');
            });

            // Hide all endpoint details
            document.querySelectorAll('.endpoint-detail').forEach(detail => {
                detail.classList.remove('active');
            });

            // Show selected endpoint
            const item = document.querySelector(`.endpoint-item[data-endpoint="${id}"]`);
            const detail = document.getElementById(`detail-${id}`);

            if (item && detail) {
                item.classList.add('active');
                detail.classList.add('active');
                activeEndpoint = id;

                // Scroll main content to top
                document.querySelector('.main-content').scrollTop = 0;
            }
        }

        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                showToast();
            }).catch(function() {
                // Fallback
                const textArea = document.createElement('textarea');
                textArea.value = text;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                showToast();
            });
        }

        function copyCode(button, text) {
            // Decode HTML entities
            const textarea = document.createElement('textarea');
            textarea.innerHTML = text;
            const decodedText = textarea.value;

            navigator.clipboard.writeText(decodedText).then(function() {
                button.classList.add('copied');
                button.innerHTML = `
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    Copied!
                `;
                showToast();
                setTimeout(() => {
                    button.classList.remove('copied');
                    button.innerHTML = `
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                        </svg>
                        Copy
                    `;
                }, 2000);
            });
        }

        function showToast() {
            const toast = document.getElementById('toast');
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 2000);
        }

        // Show first endpoint by default on larger screens
        if (window.innerWidth > 768) {
            const firstEndpoint = document.querySelector('.endpoint-item');
            if (firstEndpoint) {
                const id = firstEndpoint.getAttribute('data-endpoint');
                showEndpoint(id);
            }
        }
    </script>
</body>

</html>

@php
    function formatJson($data, $indent = 0)
    {
        $html = '';
        $spaces = str_repeat('    ', $indent);

        if (is_array($data)) {
            $isAssoc = array_keys($data) !== range(0, count($data) - 1);

            if ($isAssoc) {
                $html .= "{\n";
                $items = [];
                foreach ($data as $key => $value) {
                    $formattedValue = formatJson($value, $indent + 1);
                    $items[] =
                        str_repeat('    ', $indent + 1) .
                        '<span class="json-key">"' .
                        $key .
                        '"</span>: ' .
                        $formattedValue;
                }
                $html .= implode(",\n", $items) . "\n";
                $html .= $spaces . '}';
            } else {
                $html .= "[\n";
                $items = [];
                foreach ($data as $value) {
                    $items[] = str_repeat('    ', $indent + 1) . formatJson($value, $indent + 1);
                }
                $html .= implode(",\n", $items) . "\n";
                $html .= $spaces . ']';
            }
        } elseif (is_string($data)) {
            $html .= '<span class="json-string">"' . htmlspecialchars($data) . '"</span>';
        } elseif (is_numeric($data)) {
            $html .= '<span class="json-number">' . $data . '</span>';
        } elseif (is_bool($data)) {
            $html .= '<span class="json-boolean">' . ($data ? 'true' : 'false') . '</span>';
        } elseif (is_null($data)) {
            $html .= '<span class="json-null">null</span>';
        }

        return $html;
    }
@endphp
