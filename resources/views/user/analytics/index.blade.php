@extends('user.layout.main')
@section('title', 'Analytics')
@section('page_content')
    <div class="page-content">
        <div class="content-header clearfix"></div>
        <section class="content">
            <div class="container-fluid">
                <div class="row analytics-layout">
                    {{-- Sub-sidebar: Facebook pages (fixed height, searchable) --}}
                    @if ($facebookPages->count() > 0)
                        <aside class="analytics-sidebar">
                            <div class="analytics-sidebar-inner">
                                <div class="analytics-sidebar-search">
                                    <div class="input-group">
                                        <input type="text" id="analyticsPageSearch" class="form-control form-control-sm"
                                            placeholder="Search pages...">
                                        <div class="input-group-append">
                                            <span class="input-group-text bg-white">
                                                <i class="fas fa-search text-muted"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="analytics-sidebar-cards">
                                    @foreach ($facebookPages as $page)
                                        <div class="analytics-page-card {{ isset($pageId) && $pageId == $page->id ? 'active' : '' }}"
                                            data-page-id="{{ $page->id }}"
                                            data-search="{{ strtolower($page->name . ' ' . ($page->facebook?->username ?? '')) }}"
                                            role="button" tabindex="0">
                                            <div class="analytics-page-card-inner">
                                                <div class="analytics-page-avatar">
                                                    <img src="{{ $page->profile_image ?? social_logo('facebook') }}"
                                                        onerror="this.onerror=null; this.src='{{ social_logo('facebook') }}';">
                                                    <span class="platform-badge facebook">
                                                        <i class="fab fa-facebook-f"></i>
                                                    </span>
                                                </div>
                                                <div class="analytics-page-details">
                                                    <span class="analytics-page-name">{{ $page->name }}</span>
                                                    <span
                                                        class="analytics-page-username">{{ $page->facebook?->username ?? '' }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </aside>
                    @endif

                    {{-- Main content: posts --}}
                    <div class="analytics-main {{ $facebookPages->count() > 0 ? '' : 'analytics-main-full' }}">
                        <div class="card">
                            <div class="card-header with-border clearfix">
                                <div class="card-title d-flex align-items-center">
                                    <i class="fas fa-chart-line mr-2 text-primary"></i>
                                    <span>Page Insights</span>
                                </div>
                                <div class="card-tools">
                                    <button type="button" class="btn btn-tool" data-card-widget="collapse"
                                        title="Collapse">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div id="analyticsContent" data-analytics-url="{{ route('panel.analytics.data') }}">
                                    {{-- Page-level insights (when a page is selected) --}}
                                    @if ($pageInsights && $selectedPage)
                                        <div class="analytics-page-insights mb-4">
                                            <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
                                                <h6 class="text-muted mb-0">
                                                    <i class="fas fa-chart-pie mr-1"></i>
                                                    Page Insights
                                                    @if (isset($selectedPage->name))
                                                        <span class="font-weight-normal text-dark">—
                                                            {{ $selectedPage->name }}</span>
                                                    @endif
                                                </h6>
                                                <div
                                                    class="analytics-duration-controls d-flex align-items-center gap-2 flex-wrap">
                                                    <select id="analyticsDuration" class="form-control form-control-sm"
                                                        style="width: auto; min-width: 140px;">
                                                        <option value="last_7"
                                                            {{ ($duration ?? 'last_28') == 'last_7' ? 'selected' : '' }}>
                                                            Last 7 days</option>
                                                        <option value="last_28"
                                                            {{ ($duration ?? 'last_28') == 'last_28' ? 'selected' : '' }}>
                                                            Last 28 days</option>
                                                        <option value="last_90"
                                                            {{ ($duration ?? '') == 'last_90' ? 'selected' : '' }}>Last 90
                                                            days</option>
                                                        <option value="this_month"
                                                            {{ ($duration ?? '') == 'this_month' ? 'selected' : '' }}>This
                                                            month</option>
                                                        <option value="this_year"
                                                            {{ ($duration ?? '') == 'this_year' ? 'selected' : '' }}>This
                                                            year</option>
                                                        <option value="custom"
                                                            {{ ($duration ?? '') == 'custom' ? 'selected' : '' }}>Custom
                                                            Range</option>
                                                    </select>
                                                    <div id="analyticsCustomRange" class="d-flex align-items-center gap-2"
                                                        @if (($duration ?? '') !== 'custom') style="display: none !important;" @endif>
                                                        <input type="date" id="analyticsSince"
                                                            class="form-control form-control-sm"
                                                            value="{{ $since ?? '' }}" style="width: auto;">
                                                        <span class="text-muted">to</span>
                                                        <input type="date" id="analyticsUntil"
                                                            class="form-control form-control-sm"
                                                            value="{{ $until ?? now()->format('Y-m-d') }}"
                                                            placeholder="Today" style="width: auto;">
                                                        <button type="button" id="analyticsApplyCustom"
                                                            class="btn btn-sm btn-primary">Apply</button>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                @php
                                                    $metrics = [
                                                        'followers' => ['label' => 'Followers', 'format' => 'number'],
                                                        'reach' => ['label' => 'Reach', 'format' => 'number'],
                                                        'video_views' => [
                                                            'label' => 'Video Views',
                                                            'format' => 'number',
                                                        ],
                                                        'engagements' => [
                                                            'label' => 'Engagements',
                                                            'format' => 'number',
                                                        ],
                                                        'link_clicks' => [
                                                            'label' => 'Link Clicks',
                                                            'format' => 'number',
                                                        ],
                                                        'click_through_rate' => [
                                                            'label' => 'Click Through Rate',
                                                            'format' => 'percent',
                                                        ],
                                                    ];
                                                @endphp
                                                @foreach ($metrics as $key => $meta)
                                                    @php
                                                        $val = $pageInsights[$key] ?? null;
                                                        $comp = $pageInsights['comparison'][$key] ?? null;
                                                        $displayVal =
                                                            $meta['format'] === 'percent'
                                                                ? (is_numeric($val)
                                                                    ? $val . '%'
                                                                    : 'N/A')
                                                                : (is_numeric($val)
                                                                    ? number_format($val)
                                                                    : 'N/A');
                                                    @endphp
                                                    <div class="col-6 col-md-4 col-lg-2 mb-3">
                                                        <div class="page-insight-card">
                                                            <div
                                                                class="d-flex align-items-center justify-content-between flex-wrap gap-1">
                                                                <span class="page-insight-value">{{ $displayVal }}</span>
                                                                @if ($comp && $comp['change'] !== null)
                                                                    @php
                                                                        $diff =
                                                                            $comp['diff'] ??
                                                                            ($pageInsights[$key] ?? 0) - 0;
                                                                        $dir = $comp['direction'] ?? 'neutral';
                                                                        $diffFormatted =
                                                                            $meta['format'] === 'percent'
                                                                                ? number_format(abs($diff), 1) . '%'
                                                                                : number_format(abs($diff));
                                                                        $tooltip =
                                                                            $dir === 'up'
                                                                                ? 'Increased by ' . $diffFormatted
                                                                                : ($dir === 'down'
                                                                                    ? 'Decreased by ' . $diffFormatted
                                                                                    : '');
                                                                    @endphp
                                                                    <span
                                                                        class="insight-comparison insight-comparison-{{ $dir }}"
                                                                        title="{{ $tooltip }}">
                                                                        @if ($dir === 'up')
                                                                            <i class="fas fa-arrow-up"></i>
                                                                        @elseif ($dir === 'down')
                                                                            <i class="fas fa-arrow-down"></i>
                                                                        @endif
                                                                        {{ abs($comp['change']) }}%
                                                                    </span>
                                                                @endif
                                                            </div>
                                                            <span class="page-insight-label">{{ $meta['label'] }}</span>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif

                                    @if (!$pageInsights || !$selectedPage)
                                        <div class="empty-state text-center py-5">
                                            <div class="empty-state-icon mb-3">
                                                <i class="fas fa-chart-pie fa-4x text-muted"></i>
                                            </div>
                                            <h4>{{ $facebookPages->count() > 0 ? 'Select a Page' : 'No Facebook Pages Connected' }}
                                            </h4>
                                            <p class="text-muted">
                                                @if ($facebookPages->count() > 0)
                                                    Select a Facebook page from the sidebar to view page insights.
                                                @else
                                                    Connect your Facebook account to see page insights here.
                                                @endif
                                            </p>
                                            <a href="{{ route('panel.accounts') }}" class="btn btn-primary mt-2">
                                                <i class="fas fa-user-circle mr-2"></i> Go to Accounts
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

@endsection

@push('styles')
    @include('user.analytics.assets.style')
@endpush

@push('scripts')
    @include('user.analytics.assets.script')
@endpush
