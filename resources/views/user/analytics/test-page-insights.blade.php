@extends('user.layout.main')
@section('title', 'Test: Page Posts & Insights (Last 7 Days)')
@section('page_content')
<style>
    .test-page-insights .page-header-card { background: linear-gradient(135deg, #1a237e 0%, #3949ab 100%); color: #fff; border: none; border-radius: 12px; }
    .test-page-insights .insight-metric { background: #f8f9fa; border-radius: 8px; padding: 1rem; text-align: center; }
    .test-page-insights .insight-metric .value { font-size: 1.5rem; font-weight: 700; color: #1a237e; }
    .test-page-insights .post-card { border: 1px solid #e0e0e0; border-radius: 12px; overflow: hidden; margin-bottom: 1.5rem; box-shadow: 0 2px 8px rgba(0,0,0,.06); }
    .test-page-insights .post-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,.08); }
    .test-page-insights .post-card .card-section { padding: 1rem 1.25rem; border-bottom: 1px solid #eee; }
    .test-page-insights .post-card .card-section:last-child { border-bottom: none; }
    .test-page-insights .post-card .section-title { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: #666; margin-bottom: 0.5rem; font-weight: 600; }
    .test-page-insights .data-row { display: flex; flex-wrap: wrap; gap: 1rem 2rem; align-items: baseline; }
    .test-page-insights .data-item { display: flex; flex-direction: column; gap: 0.15rem; }
    .test-page-insights .data-item .key { font-size: 0.8rem; color: #666; }
    .test-page-insights .data-item .val { font-weight: 500; word-break: break-word; }
    .test-page-insights .post-message { background: #fafafa; padding: 1rem; border-radius: 8px; white-space: pre-wrap; word-break: break-word; }
    .test-page-insights .post-image { max-width: 100%; max-height: 320px; border-radius: 8px; object-fit: contain; }
    .test-page-insights .engagement-pills { display: flex; flex-wrap: wrap; gap: 0.5rem; }
    .test-page-insights .engagement-pill { background: #e3f2fd; color: #1565c0; padding: 0.35rem 0.75rem; border-radius: 999px; font-size: 0.875rem; font-weight: 500; }
    .test-page-insights .badge-raw { font-family: monospace; font-size: 0.75rem; }
    .test-page-insights .permalink-btn { display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.4rem 0.8rem; background: #3949ab; color: #fff; border-radius: 6px; text-decoration: none; font-size: 0.875rem; }
    .test-page-insights .permalink-btn:hover { background: #303f9f; color: #fff; }
    .test-page-insights .error-box { background: #ffebee; border: 1px solid #ef5350; border-radius: 8px; padding: 1rem; color: #c62828; }
</style>
<div class="page-content test-page-insights">
    <div class="content-header clearfix"></div>
    <section class="content">
        <div class="container-fluid">
            <div class="card page-header-card mb-4">
                <div class="card-body">
                    <h4 class="mb-1"><i class="fas fa-chart-line mr-2"></i>{{ $page->name ?? 'Page' }}</h4>
                    <p class="mb-0 opacity-90">Last 7 days — {{ $since ?? '—' }} to {{ $until ?? '—' }}</p>
                </div>
            </div>

            @if ($error)
                <div class="error-box mb-4"><i class="fas fa-exclamation-circle mr-2"></i>{{ $error }}</div>
            @endif

            @if ($pageInsights)
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-chart-pie mr-2"></i>Page insights</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            @foreach (['followers' => 'Followers', 'reach' => 'Reach', 'video_views' => 'Video views', 'engagements' => 'Engagements'] as $key => $label)
                                @php $v = $pageInsights[$key] ?? null; @endphp
                                <div class="col-6 col-md-3">
                                    <div class="insight-metric">
                                        <div class="value">{{ is_numeric($v) ? number_format($v) : ($v ?? '—') }}</div>
                                        <div class="text-muted small">{{ $label }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @if (!empty($pageInsights['comparison']))
                            <div class="mt-3 pt-3 border-top">
                                <div class="section-title">Comparison (vs previous period)</div>
                                <div class="data-row">
                                    @foreach ($pageInsights['comparison'] as $metric => $data)
                                        <div class="data-item">
                                            <span class="key">{{ $metric }}</span>
                                            <span class="val">{{ ($data['change'] ?? 0) }}% {{ $data['direction'] ?? '' }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <h5 class="mb-3"><i class="fas fa-newspaper mr-2"></i>Posts ({{ count($posts ?? []) }})</h5>

            @if (empty($posts))
                <div class="alert alert-info">No posts in this period.</div>
            @else
                @foreach ($posts as $index => $post)
                    @php
                        $ins = $post['insights'] ?? [];
                        $created = isset($post['created_time']) ? \Carbon\Carbon::parse($post['created_time'])->format('M j, Y g:i A') : '—';
                    @endphp
                    <div class="post-card card">
                        <div class="card-section d-flex justify-content-between align-items-start flex-wrap gap-2">
                            <div>
                                <span class="section-title">Post #{{ $index + 1 }}</span>
                                <div class="data-row mt-1">
                                    <div class="data-item">
                                        <span class="key">Created</span>
                                        <span class="val">{{ $created }}</span>
                                    </div>
                                    <div class="data-item">
                                        <span class="key">ID</span>
                                        <span class="val badge-raw">{{ $post['id'] ?? '—' }}</span>
                                    </div>
                                    <div class="data-item">
                                        <span class="key">post_id</span>
                                        <span class="val badge-raw">{{ $post['post_id'] ?? $post['id'] ?? '—' }}</span>
                                    </div>
                                    <div class="data-item">
                                        <span class="key">Type</span>
                                        <span class="val">{{ $post['type'] ?? '—' }}</span>
                                    </div>
                                    <div class="data-item">
                                        <span class="key">Status type</span>
                                        <span class="val">{{ $post['status_type'] ?? '—' }}</span>
                                    </div>
                                    <div class="data-item">
                                        <span class="key">Is popular</span>
                                        <span class="val">{{ isset($post['is_popular']) ? ($post['is_popular'] ? 'Yes' : 'No') : '—' }}</span>
                                    </div>
                                </div>
                            </div>
                            @if (!empty($post['permalink_url']))
                                <a href="{{ $post['permalink_url'] }}" target="_blank" rel="noopener" class="permalink-btn">
                                    <i class="fas fa-external-link-alt"></i> View on Facebook
                                </a>
                            @endif
                        </div>

                        @if (!empty($post['message']) || !empty($post['story']))
                            <div class="card-section">
                                <div class="section-title">Message / Story</div>
                                <div class="post-message">{{ $post['message'] ?? $post['story'] ?? '—' }}</div>
                            </div>
                        @endif

                        @if (!empty($post['full_picture']))
                            <div class="card-section">
                                <div class="section-title">Image</div>
                                <img src="{{ $post['full_picture'] }}" alt="Post" class="post-image" loading="lazy">
                            </div>
                        @endif

                        <div class="card-section">
                            <div class="section-title">Engagement</div>
                            <div class="engagement-pills">
                                <span class="engagement-pill"><i class="fas fa-share mr-1"></i> Shares: {{ (int) ($post['shares'] ?? 0) }}</span>
                                <span class="engagement-pill"><i class="fas fa-comment mr-1"></i> Comments: {{ (int) ($post['comments'] ?? 0) }}</span>
                                <span class="engagement-pill"><i class="fas fa-mouse-pointer mr-1"></i> Clicks: {{ (int) ($ins['post_clicks'] ?? 0) }}</span>
                                <span class="engagement-pill"><i class="fas fa-heart mr-1"></i> Reactions: {{ (int) ($ins['post_reactions'] ?? 0) }}</span>
                                <span class="engagement-pill"><i class="fas fa-eye mr-1"></i> Impressions: {{ (int) ($ins['post_impressions'] ?? 0) }}</span>
                                <span class="engagement-pill"><i class="fas fa-chart-line mr-1"></i> Reach: {{ (int) ($ins['post_reach'] ?? 0) }}</span>
                                <span class="engagement-pill">Eng. rate: {{ $ins['post_engagement_rate'] ?? 0 }}%</span>
                            </div>
                        </div>

                        <div class="card-section">
                            <div class="section-title">Insights (raw)</div>
                            <pre class="mb-0 bg-light p-2 rounded small" style="max-height: 180px; overflow: auto;">{{ json_encode($ins, JSON_PRETTY_PRINT) }}</pre>
                        </div>

                        @if (!empty($post['icon']))
                            <div class="card-section">
                                <div class="section-title">Icon URL</div>
                                <a href="{{ $post['icon'] }}" target="_blank" rel="noopener" class="small">{{ $post['icon'] }}</a>
                            </div>
                        @endif
                    </div>
                @endforeach
            @endif
        </div>
    </section>
</div>
@endsection
