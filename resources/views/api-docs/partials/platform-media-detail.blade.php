@php
    $supported = !empty($media['supported']);
    $methodLabel = $supported ? $media['http_method'] : 'N/A';
    $methodClass = $supported && isset($media['http_method']) ? 'method-' . strtolower((string) $media['http_method']) : 'method-ref';
@endphp
<div class="endpoint-detail platform-doc-detail" id="{{ $docId }}">
    <div class="content-wrapper">
        <div class="detail-header">
            <span class="method-badge {{ $methodClass }}">{{ $methodLabel }}</span>
            <div class="endpoint-title-wrapper">
                <h2>{{ $platform['name'] }} — {{ $media['label'] }}</h2>
                @if ($supported && !empty($media['http_path']))
                    <button class="endpoint-copy-btn-header" type="button"
                        onclick="copyEndpointUrl('{{ $baseUrl }}{{ $media['http_path'] }}')" title="Copy endpoint URL">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                        </svg>
                    </button>
                @endif
            </div>
            @if ($supported && !empty($media['http_path']))
                <p class="platform-doc-path"><span class="platform-doc-path-m">{{ $media['http_method'] }}</span> <code>{{ $baseUrl }}{{ $media['http_path'] }}</code></p>
            @endif
            <p>{{ $media['description'] }}</p>
        </div>

        <div class="detail-section">
            <h3 class="section-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 2L11 13"></path>
                    <path d="M22 2L15 22L11 13L2 9L22 2Z"></path>
                </svg>
                Request
            </h3>

            @if ($supported && !empty($media['request']['curl']))
                <div class="code-block" style="margin-bottom: 16px;">
                    <div class="code-header">
                        <span>cURL</span>
                        <button type="button" class="copy-btn" onclick="copyCodeBlockPlain(this)">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                                <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                            </svg>
                            Copy
                        </button>
                    </div>
                    <div class="code-content">
                        <pre>{{ str_replace('{base_url}', $baseUrl, $media['request']['curl']) }}</pre>
                    </div>
                </div>
            @endif

            <div class="code-block">
                <div class="code-header">
                    <span>Headers</span>
                    @if (!empty($media['request']['headers']) && count($media['request']['headers']) > 0)
                        <button type="button" class="copy-btn" onclick="copyCodeBlockPlain(this)">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                                <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                            </svg>
                            Copy
                        </button>
                    @endif
                </div>
                <div class="code-content">
                    <pre>@foreach ($media['request']['headers'] as $key => $value)
<span class="json-key">{{ $key }}</span>: <span class="json-string">{{ $value }}</span>
@endforeach</pre>
                </div>
            </div>

            @if ($supported && !empty($media['request']['body']))
                <div class="code-block" style="margin-top: 16px;">
                    <div class="code-header">
                        <span>Body</span>
                        <button type="button" class="copy-btn" onclick="copyCodeBlockPlain(this)">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                                <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                            </svg>
                            Copy
                        </button>
                    </div>
                    <div class="code-content">
                        <pre>{!! formatJson($media['request']['body']) !!}</pre>
                    </div>
                </div>
            @endif
        </div>

        <div class="detail-section">
            <h3 class="section-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                </svg>
                Response
            </h3>
            <div class="code-block">
                <div class="code-header">
                    <span>JSON</span>
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <span class="status-badge status-200">{{ $supported ? '200 OK (queued)' : 'Not applicable' }}</span>
                        <button type="button" class="copy-btn" onclick="copyCodeBlockPlain(this)">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                                <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                            </svg>
                            Copy
                        </button>
                    </div>
                </div>
                <div class="code-content">
                    <pre>{!! formatJson($media['response']) !!}</pre>
                </div>
            </div>
        </div>

        @if (!empty($media['notes']))
            <div class="notes-box">
                <strong>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="16" x2="12" y2="12"></line>
                        <line x1="12" y1="8" x2="12.01" y2="8"></line>
                    </svg>
                    Note
                </strong>
                <p>{{ $media['notes'] }}</p>
            </div>
        @endif
    </div>
</div>
