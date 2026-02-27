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
                                        @php
                                            $followers = $pageInsights['followers'] ?? null;
                                            $followersValid = $followers !== null && is_numeric($followers) && $followers >= 100;
                                        @endphp
                                        @if ($followersValid)
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
                                                        $displayVal = $meta['format'] === 'percent'
                                                            ? (is_numeric($val) ? $val . '%' : 'N/A')
                                                            : (is_numeric($val) ? number_format($val) : 'N/A');
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
                                                Insights can't be displayed. Followers &lt; 100 or no data returned.
                                            </div>
                                        @endif
                                    @else
                                        <div class="alert alert-warning">
                                            <i class="fas fa-exclamation-triangle mr-2"></i>
                                            No insights data returned (token invalid, API error, or page &lt; 100 likes).
                                        </div>
                                    @endif
                                </div>

                                @if ($pagePosts !== null)
                                    <div class="mb-4">
                                        <h5><i class="fas fa-newspaper mr-1"></i> Page Posts ({{ count($pagePosts) }})</h5>
                                        <p class="text-muted small">Date range: {{ $since }} to {{ $until }}</p>
                                        @if (count($pagePosts) > 0)
                                            <div class="table-responsive">
                                                <table class="table table-sm table-bordered">
                                                    <thead>
                                                        <tr>
                                                            <th>Date</th>
                                                            <th>Message</th>
                                                            <th>Impressions</th>
                                                            <th>Impressions (Unique)</th>
                                                            <th>Engaged Users</th>
                                                            <th>Clicks</th>
                                                            <th>Link</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($pagePosts as $post)
                                                            @php
                                                                $insights = $post['insights'] ?? [];
                                                                $impressions = $insights['post_impressions'] ?? 0;
                                                                $impressionsUnique = $insights['post_impressions_unique'] ?? 0;
                                                                $engaged = $insights['post_engaged_users'] ?? 0;
                                                                $clicks = $insights['post_clicks'] ?? 0;
                                                                $msg = \Illuminate\Support\Str::limit($post['message'] ?? $post['story'] ?? '—', 80);
                                                                $created = isset($post['created_time']) ? \Carbon\Carbon::parse($post['created_time'])->format('F j, Y g:ia') : '—';
                                                            @endphp
                                                            <tr>
                                                                <td class="text-nowrap">{{ $created }}</td>
                                                                <td>{{ $msg }}</td>
                                                                <td>{{ number_format($impressions) }}</td>
                                                                <td>{{ number_format($impressionsUnique) }}</td>
                                                                <td>{{ number_format($engaged) }}</td>
                                                                <td>{{ number_format($clicks) }}</td>
                                                                <td>
                                                                    @if (!empty($post['permalink_url']))
                                                                        <a href="{{ $post['permalink_url'] }}" target="_blank" rel="noopener"><i class="fas fa-external-link-alt"></i></a>
                                                                    @else
                                                                        —
                                                                    @endif
                                                                </td>
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
