@extends('user.layout.main')
@section('title', 'Analytics Test - Page Insights')
@section('page_content')
    <div class="page-content">
        <div class="content-header clearfix"></div>
        <section class="content">
            <div class="container-fluid">
                <div class="card">
                    <div class="card-header with-border">
                        <h3 class="card-title"><i class="fas fa-vial mr-2"></i>Page Insights Test</h3>
                    </div>
                    <div class="card-body">
                        <form method="get" action="{{ route('panel.analytics.test') }}" class="mb-4">
                            <div class="row align-items-end">
                                <div class="col-md-4">
                                    <label for="page_id">Page ID</label>
                                    <select name="page_id" id="page_id" class="form-control">
                                        <option value="">-- Select a page --</option>
                                        @foreach ($facebookPages as $page)
                                            <option value="{{ $page->id }}" {{ ($pageId ?? '') == $page->id ? 'selected' : '' }}>
                                                {{ $page->name }} (ID: {{ $page->id }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label for="duration">Duration</label>
                                    <select name="duration" id="duration" class="form-control">
                                        <option value="last_7" {{ request('duration') == 'last_7' ? 'selected' : '' }}>Last 7 days</option>
                                        <option value="last_28" {{ (request('duration') ?: 'last_28') == 'last_28' ? 'selected' : '' }}>Last 28 days</option>
                                        <option value="last_90" {{ request('duration') == 'last_90' ? 'selected' : '' }}>Last 90 days</option>
                                        <option value="this_month" {{ request('duration') == 'this_month' ? 'selected' : '' }}>This month</option>
                                        <option value="this_year" {{ request('duration') == 'this_year' ? 'selected' : '' }}>This year</option>
                                        <option value="full_year" {{ request('duration') == 'full_year' ? 'selected' : '' }}>Full year</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search mr-1"></i> Fetch
                                    </button>
                                </div>
                            </div>
                        </form>

                        @if ($pageId)
                            @if ($selectedPage)
                                <div class="mb-4">
                                    <h5><i class="fas fa-chart-pie mr-1"></i> Page Insights — {{ $selectedPage->name }}</h5>
                                    <p class="text-muted small">Date range: {{ $since }} to {{ $until }}</p>
                                    @if ($pageInsights)
                                        <div class="row">
                                            @php
                                                $metrics = [
                                                    'followers' => ['label' => 'Followers', 'format' => 'number'],
                                                    'reach' => ['label' => 'Reach', 'format' => 'number'],
                                                    'video_views' => ['label' => 'Video Views', 'format' => 'number'],
                                                    'engagements' => ['label' => 'Engagements', 'format' => 'number'],
                                                ];
                                            @endphp
                                            @foreach ($metrics as $key => $meta)
                                                @php
                                                    $val = $pageInsights[$key] ?? null;
                                                    $displayVal = is_numeric($val) ? number_format($val) : 'N/A';
                                                @endphp
                                                <div class="col-6 col-md-4 col-lg-2 mb-3">
                                                    <div class="small text-muted">{{ $meta['label'] }}</div>
                                                    <div class="font-weight-bold">{{ $displayVal }}</div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="alert alert-warning">
                                            <i class="fas fa-exclamation-triangle mr-2"></i>
                                            No insights data returned.
                                        </div>
                                    @endif
                                </div>

                                @if ($pagePosts !== null)
                                    <div class="mb-4">
                                        <h5><i class="fas fa-newspaper mr-1"></i> Page Posts ({{ count($pagePosts) }})</h5>
                                        @if (count($pagePosts) > 0)
                                            <div class="table-responsive">
                                                <table class="table table-sm table-bordered">
                                                    <thead>
                                                        <tr>
                                                            <th>Date</th>
                                                            <th>Message</th>
                                                            <th>Post Clicks</th>
                                                            <th>Reactions</th>
                                                            <th>Impressions</th>
                                                            <th>Reach</th>
                                                            <th>Eng. Rate</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($pagePosts as $post)
                                                            @php
                                                                $insights = $post['insights'] ?? [];
                                                                $createdTimeRaw = $post['created_time'] ?? null;
                                                                $createdTimeValue = is_array($createdTimeRaw) ? ($createdTimeRaw['date'] ?? $createdTimeRaw['datetime'] ?? null) : $createdTimeRaw;
                                                                $created = '—';
                                                                if (is_string($createdTimeValue) && trim($createdTimeValue) !== '') {
                                                                    try {
                                                                        $created = \Carbon\Carbon::parse($createdTimeValue)->format('F j, Y g:ia');
                                                                    } catch (\Throwable $e) {
                                                                        $created = '—';
                                                                    }
                                                                }
                                                            @endphp
                                                            <tr>
                                                                <td>{{ $created }}</td>
                                                                <td>{{ \Illuminate\Support\Str::limit($post['message'] ?? $post['story'] ?? '—', 80) }}</td>
                                                                <td>{{ number_format($insights['post_clicks'] ?? 0) }}</td>
                                                                <td>{{ number_format($insights['post_reactions'] ?? 0) }}</td>
                                                                <td>{{ number_format($insights['post_impressions'] ?? 0) }}</td>
                                                                <td>{{ number_format($insights['post_reach'] ?? 0) }}</td>
                                                                <td>{{ $insights['post_engagement_rate'] ?? 0 }}%</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @else
                                            <p class="text-muted mb-0">No posts in this period.</p>
                                        @endif
                                    </div>
                                @endif
                            @else
                                <div class="alert alert-danger">
                                    <i class="fas fa-times-circle mr-2"></i>
                                    Page not found or you don't have access to it.
                                </div>
                            @endif

                            <div class="mt-4">
                                <h5><i class="fas fa-code mr-1"></i> Raw API Response</h5>
                                <pre class="bg-dark text-light p-3 rounded" style="max-height: 400px; overflow: auto;"><code>{{ $apiResponse ? json_encode($apiResponse, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : 'No response' }}</code></pre>
                            </div>
                        @else
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle mr-2"></i>
                                Select a page and click Fetch to view insights and the raw API response.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection