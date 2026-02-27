@if ($facebookPages->count() > 0)
    <script>
        $(document).ready(function() {
            var $content = $('#analyticsContent');
            var analyticsUrl = $content.data('analytics-url');
            var hasPages = {{ $facebookPages->count() > 0 ? 'true' : 'false' }};
            var currentPageId = $('.analytics-page-card.active').data('page-id') || 'all';
            var currentDuration = '{{ $duration ?? 'last_28' }}';
            var currentSince = '{{ $since ?? '' }}';
            var currentUntil = '{{ $until ?? '' }}';
            var isLoadingAnalytics = false;

            function formatMetric(val) {
                return (val !== null && val !== undefined && !isNaN(val)) ? parseInt(val).toLocaleString() : 'N/A';
            }

            function renderDurationDropdown(duration, since, until) {
                var customStyle = duration === 'custom' ? '' : ' style="display: none !important;"';
                return '<div class="d-flex flex-wrap align-items-center justify-content-between mb-3">' +
                    '<h6 class="text-muted mb-0"><i class="fas fa-chart-pie mr-1"></i>Page Insights</h6>' +
                    '<div class="analytics-duration-controls d-flex align-items-center gap-2 flex-wrap">' +
                    '<select id="analyticsDuration" class="form-control form-control-sm" style="width: auto; min-width: 140px;">' +
                    '<option value="last_7"' + (duration === 'last_7' ? ' selected' : '') +
                    '>Last 7 days</option>' +
                    '<option value="last_28"' + (duration === 'last_28' ? ' selected' : '') +
                    '>Last 28 days</option>' +
                    '<option value="last_90"' + (duration === 'last_90' ? ' selected' : '') +
                    '>Last 90 days</option>' +
                    '<option value="this_month"' + (duration === 'this_month' ? ' selected' : '') +
                    '>This month</option>' +
                    '<option value="this_year"' + (duration === 'this_year' ? ' selected' : '') +
                    '>This year</option>' +
                    '<option value="custom"' + (duration === 'custom' ? ' selected' : '') +
                    '>Custom Range</option>' +
                    '</select>' +
                    '<div id="analyticsCustomRange" class="d-flex align-items-center gap-2"' + customStyle + '>' +
                    '<input type="date" id="analyticsSince" class="form-control form-control-sm" value="' + (
                        since || '') + '" style="width: auto;">' +
                    '<span class="text-muted">to</span>' +
                    '<input type="date" id="analyticsUntil" class="form-control form-control-sm" value="' + (
                        until || '') + '" style="width: auto;">' +
                    '<button type="button" id="analyticsApplyCustom" class="btn btn-sm btn-primary">Apply</button>' +
                    '</div>' +
                    '<button type="button" id="analyticsRefresh" class="btn btn-sm btn-outline-secondary" title="Refresh insights"><i class="fas fa-sync-alt"></i></button>' +
                    '</div></div>';
            }

            function renderComparisonBadge(comp, isPercent) {
                if (!comp || comp.change === null) return '';
                var dir = comp.direction || 'neutral';
                var arrow = dir === 'up' ? '<i class="fas fa-arrow-up"></i>' : (dir === 'down' ?
                    '<i class="fas fa-arrow-down"></i>' : '');
                var diff = comp.diff != null ? Math.abs(comp.diff) : 0;
                var diffFormatted = isPercent ? diff.toFixed(1) + '%' : diff.toLocaleString();
                var tooltip = dir === 'up' ? 'Increased by ' + diffFormatted : (dir === 'down' ? 'Decreased by ' +
                    diffFormatted : '');
                var dataAttr = tooltip ? ' data-tooltip="' + tooltip.replace(/"/g, '&quot;') + '"' : '';
                return '<span class="insight-comparison insight-comparison-' + dir + ' has-tooltip"' + dataAttr +
                    '>' + arrow +
                    ' ' + Math.abs(comp.change) + '%</span>';
            }

            function renderInsightCard(value, label, comp, isPercent) {
                var displayVal = isPercent ? (value != null ? value + '%' : 'N/A') : formatMetric(value);
                var badge = renderComparisonBadge(comp, isPercent);
                return '<div class="page-insight-card">' +
                    '<div class="d-flex align-items-center justify-content-between flex-wrap gap-1">' +
                    '<span class="page-insight-value">' + displayVal + '</span>' + badge + '</div>' +
                    '<span class="page-insight-label">' + label + '</span></div>';
            }

            var chartMetricOptions = [
                { key: 'followers', label: 'Followers', byDayKey: 'followers_by_day' },
                { key: 'reach', label: 'Reach', byDayKey: 'reach_by_day' },
                { key: 'video_views', label: 'Video Views', byDayKey: 'video_views_by_day' },
                { key: 'engagements', label: 'Engagements', byDayKey: 'engagements_by_day' }
            ];

            function renderEngagementsChart(insights, comp, selectedMetricKey) {
                selectedMetricKey = selectedMetricKey || 'engagements';
                var opt = chartMetricOptions.find(function(o) { return o.key === selectedMetricKey; }) || chartMetricOptions[3];
                var byDay = insights[opt.byDayKey] || {};
                var dates = Object.keys(byDay).sort();
                var total = 0;
                dates.forEach(function(d) { total += byDay[d] || 0; });
                var dailyAvg = dates.length > 0 ? Math.round(total / dates.length) : 0;
                var metricComp = comp[opt.key] || {};
                var pctChange = metricComp.change != null ? metricComp.change : null;
                var pctStr = (pctChange != null ? ' ' + pctChange + '%' : '');
                var chartHtml = dates.length > 0
                    ? '<div class="chart-container" style="position: relative; height: 280px;"><canvas id="engagementsChartCanvas"></canvas></div>'
                    : '<div class="alert alert-light border text-muted mb-0"><i class="fas fa-info-circle mr-2"></i>No daily ' + opt.label.toLowerCase() + ' data available for this period.</div>';
                var dropdownItems = chartMetricOptions.map(function(o) {
                    var isSelected = o.key === selectedMetricKey;
                    return '<a class="chart-metric-option' + (isSelected ? ' active' : '') + '" href="#" data-metric="' + o.key + '"><span class="chart-metric-option-circle' + (isSelected ? ' selected' : '') + '"></span><span>' + o.label + '</span></a>';
                }).join('');
                return '<div class="mt-4 pt-4 border-top chart-metric-section" data-selected-metric="' + selectedMetricKey + '">' +
                    '<div class="d-flex align-items-center flex-wrap gap-2 mb-3">' +
                    '<h6 class="text-muted mb-0"><i class="fas fa-chart-bar mr-1"></i>Average <div class="dropdown chart-metric-dropdown-wrap">' +
                    '<button type="button" class="chart-metric-trigger dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">' +
                    '<span class="chart-metric-trigger-label">' + opt.label + '</span><i class="fas fa-chevron-down chart-metric-trigger-chevron"></i></button>' +
                    '<div class="dropdown-menu chart-metric-dropdown">' + dropdownItems + '</div></div></h6></div>' +
                    '<p class="small text-muted mb-2">(daily average: ' + dailyAvg.toLocaleString() + pctStr + ')</p>' +
                    chartHtml + '</div>';
            }

            function initEngagementsChart(insights, metricKey) {
                metricKey = metricKey || 'engagements';
                var opt = chartMetricOptions.find(function(o) { return o.key === metricKey; }) || chartMetricOptions[3];
                var byDay = insights[opt.byDayKey] || {};
                var dates = Object.keys(byDay).sort();
                if (dates.length === 0 || typeof Chart === 'undefined') return;
                if (window.engagementsChartInstance) {
                    window.engagementsChartInstance.destroy();
                    window.engagementsChartInstance = null;
                }
                var labels = dates.map(function(d) {
                    var dt = new Date(d + 'T12:00:00');
                    return dt.toLocaleDateString('en-GB', { day: 'numeric', month: 'short' });
                });
                var data = dates.map(function(d) { return byDay[d] || 0; });
                var ctx = document.getElementById('engagementsChartCanvas');
                if (!ctx) return;
                window.engagementsChartInstance = new Chart(ctx.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: opt.label,
                            data: data,
                            backgroundColor: 'rgba(24, 119, 242, 0.7)',
                            borderColor: 'rgba(24, 119, 242, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            tooltip: {
                                callbacks: {
                                    title: function(items) {
                                        var idx = items[0] && items[0].dataIndex;
                                        if (idx != null && dates[idx]) {
                                            var dt = new Date(dates[idx] + 'T12:00:00');
                                            return dt.toLocaleDateString('en-GB', { day: 'numeric', month: 'long' });
                                        }
                                        return '';
                                    },
                                    label: function(ctx) {
                                        return ctx.raw.toLocaleString() + ' total';
                                    }
                                }
                            },
                            legend: { display: false }
                        },
                        scales: {
                            y: { beginAtZero: true },
                            x: { grid: { display: false } }
                        }
                    }
                });
            }

            function hasMeaningfulInsights(insights) {
                if (!insights) return false;
                var keys = ['followers', 'reach', 'video_views', 'engagements'];
                for (var i = 0; i < keys.length; i++) {
                    var v = insights[keys[i]];
                    if (v != null && !isNaN(v)) return true;
                }
                if (insights.engagements_by_day && Object.keys(insights.engagements_by_day).length > 0) return true;
                return false;
            }

            function escapeHtml(s) {
                if (!s) return '';
                var div = document.createElement('div');
                div.textContent = s;
                return div.innerHTML;
            }

            function renderPostsList(posts, since, until) {
                if (posts === null) {
                    return '<div class="analytics-posts-placeholder text-center py-5">' +
                        '<i class="fas fa-th-large fa-4x text-muted mb-3"></i>' +
                        '<p class="text-muted mb-0">Select a page to view posts.</p></div>';
                }
                if (!posts || posts.length === 0) {
                    return '<div class="analytics-posts-placeholder text-center py-5">' +
                        '<i class="fas fa-newspaper fa-4x text-muted mb-3"></i>' +
                        '<p class="text-muted mb-0">No posts in this period.</p></div>';
                }
                var html = '<div class="analytics-posts-list">';
                posts.forEach(function(post) {
                    var rawMsg = post.message || post.story || '';
                    var msg = escapeHtml(rawMsg.substring(0, 150));
                    if (rawMsg.length > 150) msg += '...';
                    var created = post.created_time ? new Date(post.created_time).toLocaleDateString('en-GB', {
                        day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit'
                    }) : '';
                    var img = post.full_picture ? '<img src="' + escapeHtml(post.full_picture) + '" alt="" class="analytics-post-thumb" loading="lazy">' :
                        '<div class="analytics-post-thumb-placeholder"><i class="fas fa-image text-muted"></i></div>';
                    var insights = post.insights || {};
                    var impressions = insights.post_impressions || insights.post_impressions_unique || 0;
                    var engaged = insights.post_engaged_users || 0;
                    var clicks = insights.post_clicks || 0;
                    var link = post.permalink_url ? '<a href="' + escapeHtml(post.permalink_url) + '" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary mt-2"><i class="fas fa-external-link-alt mr-1"></i>View on Facebook</a>' : '';
                    html += '<div class="analytics-post-card card mb-3">' +
                        '<div class="card-body">' +
                        '<div class="d-flex gap-3">' +
                        '<div class="analytics-post-thumb-wrap flex-shrink-0">' + img + '</div>' +
                        '<div class="flex-grow-1 min-w-0">' +
                        '<p class="mb-2 text-muted small">' + created + '</p>' +
                        '<p class="mb-2">' + (msg || '<em class="text-muted">No message</em>') + '</p>' +
                        '<div class="analytics-post-metrics d-flex flex-wrap gap-3 mb-2">' +
                        '<span><strong>' + impressions.toLocaleString() + '</strong> <span class="text-muted small">Impressions</span></span>' +
                        '<span><strong>' + engaged.toLocaleString() + '</strong> <span class="text-muted small">Engaged</span></span>' +
                        '<span><strong>' + clicks.toLocaleString() + '</strong> <span class="text-muted small">Clicks</span></span>' +
                        '</div>' + link + '</div></div></div></div>';
                });
                html += '</div>';
                return html;
            }

            function renderPageInsights(insights, pageName, duration, since, until, pagePosts) {
                duration = duration || 'last_28';
                since = since || '';
                until = until || '';
                var overviewContent = '<div class="analytics-page-insights mb-4">' + renderDurationDropdown(duration, since,
                    until);
                if (!hasMeaningfulInsights(insights)) {
                    overviewContent += '<div class="alert alert-info mb-0" role="alert">' +
                        '<strong><i class="fas fa-info-circle mr-2"></i>Insights can\'t be fetched for this page.</strong>' +
                        '<ol class="mb-0 mt-2 pl-3">' +
                        '<li>Page Insights data is only available on Pages with 100 or more likes.</li>' +
                        '<li>The connected account may not have the required permissions. <a href="{{ route('panel.accounts') }}" class="alert-link font-weight-bold">Reconnect your account</a> to grant access.</li>' +
                        '</ol></div></div>';
                } else {
                    var comp = insights.comparison || {};
                    var cards = [
                        ['followers', 'Followers', false],
                        ['reach', 'Reach', false],
                        ['video_views', 'Video Views', false],
                        ['engagements', 'Engagements', false]
                    ];
                    var note = '<p class="small mb-3" style="color: #856404;"><i class="fas fa-info-circle mr-1"></i>' +
                        'Page Insights data is only available on Pages with 100 or more likes.</p>';
                    overviewContent += note + '<div class="analytics-insight-cards">';
                    cards.forEach(function(c) {
                        overviewContent += renderInsightCard(insights[c[0]], c[1], comp[c[0]], c[2]);
                    });
                    overviewContent += '</div>';
                    overviewContent += renderEngagementsChart(insights, comp);
                    overviewContent += '</div>';
                }
                var postsContent = renderPostsList(pagePosts, since, until);
                return '<ul class="nav nav-tabs analytics-insight-tabs mb-3" role="tablist">' +
                    '<li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#analyticsOverviewTab" role="tab">Overview</a></li>' +
                    '<li class="nav-item"><a class="nav-link" data-toggle="tab" href="#analyticsPostsTab" role="tab">Posts</a></li>' +
                    '</ul>' +
                    '<div class="tab-content">' +
                    '<div class="tab-pane fade show active" id="analyticsOverviewTab" role="tabpanel">' + overviewContent + '</div>' +
                    '<div class="tab-pane fade" id="analyticsPostsTab" role="tabpanel">' + postsContent + '</div>' +
                    '</div>';
            }

            function renderEmptyState(hasPageSelected) {
                var msg = hasPageSelected ? 'Unable to load page insights.' :
                    'Select a Facebook page from the sidebar to view page insights.';
                if (!hasPages) msg = 'Connect your Facebook account to see page insights here.';
                var title = hasPages ? 'Select a Page' : 'No Facebook Pages Connected';
                return '<div class="empty-state text-center py-5">' +
                    '<div class="empty-state-icon mb-3"><i class="fas fa-chart-bar fa-4x text-muted"></i></div>' +
                    '<h4>' + title + '</h4><p class="text-muted">' + msg + '</p>' +
                    '<a href="{{ route('panel.accounts') }}" class="btn btn-primary mt-2"><i class="fas fa-user-circle mr-2"></i> Go to Accounts</a></div>';
            }

            function loadAnalytics(pageId, duration, since, until, refresh) {
                currentPageId = pageId || currentPageId;
                currentDuration = duration || currentDuration;
                currentSince = since || currentSince;
                currentUntil = until || currentUntil;

                $content.html(
                    '<div class="text-center py-5"><i class="fas fa-spinner fa-spin fa-2x text-muted"></i><p class="mt-2 text-muted">Loading analytics...</p></div>'
                );
                var params = {
                    page_id: currentPageId || ''
                };
                if (currentDuration) params.duration = currentDuration;
                if (currentDuration === 'custom' && currentSince) {
                    params.duration = 'custom';
                    params.since = currentSince;
                    params.until = currentUntil || new Date().toISOString().split('T')[0];
                }
                if (refresh) params.refresh = 1;
                $.get(analyticsUrl, params)
                    .always(function() {
                        isLoadingAnalytics = false;
                    })
                    .done(function(res) {
                        if (!res.success) {
                            $content.html(renderEmptyState(!!currentPageId));
                            return;
                        }
                        var html = '';
                        if (res.selectedPage) {
                            if (res.since) currentSince = res.since;
                            if (res.until) currentUntil = res.until;
                            html += renderPageInsights(res.pageInsights, res.selectedPage.name, currentDuration,
                                currentSince, currentUntil, res.pagePosts);
                        } else {
                            html += renderEmptyState(!!currentPageId);
                        }
                        $content.html(html);
                        window.currentAnalyticsInsights = res.pageInsights || null;
                        bindDurationHandlers();
                        bindChartMetricHandlers();
                        var selectedMetric = $('.chart-metric-section').data('selected-metric') || 'engagements';
                        if (res.selectedPage && res.pageInsights) {
                            var byDayKey = chartMetricOptions.find(function(o) { return o.key === selectedMetric; });
                            byDayKey = byDayKey ? byDayKey.byDayKey : 'engagements_by_day';
                            var byDay = (res.pageInsights[byDayKey] || {});
                            if (Object.keys(byDay).length > 0) {
                                initEngagementsChart(res.pageInsights, selectedMetric);
                            }
                        }
                    })
                    .fail(function() {
                        $content.html(renderEmptyState(!!currentPageId));
                        if (typeof toastr !== 'undefined') toastr.error('Failed to load analytics.');
                    });
            }

            function bindChartMetricHandlers() {
                $(document).off('click', '.chart-metric-option');
                $(document).on('click', '.chart-metric-option', function(e) {
                    e.preventDefault();
                    var metric = $(this).data('metric');
                    var $section = $(this).closest('.chart-metric-section');
                    var insights = window.currentAnalyticsInsights;
                    if (!insights || !$section.length) return;
                    var comp = insights.comparison || {};
                    var newHtml = renderEngagementsChart(insights, comp, metric);
                    $section.replaceWith(newHtml);
                    var opt = chartMetricOptions.find(function(o) { return o.key === metric; });
                    var byDay = opt ? (insights[opt.byDayKey] || {}) : {};
                    if (Object.keys(byDay).length > 0 && typeof Chart !== 'undefined') {
                        initEngagementsChart(insights, metric);
                    }
                });
            }

            function bindDurationHandlers() {
                $(document).off('change', '#analyticsDuration');
                $(document).off('click', '#analyticsApplyCustom');
                $(document).off('click', '#analyticsRefresh');
                $(document).on('change', '#analyticsDuration', function() {
                    var val = $(this).val();
                    var $custom = $('#analyticsCustomRange');
                    if (val === 'custom') {
                        $custom.show();
                        var today = new Date().toISOString().split('T')[0];
                        $('#analyticsSince').val(currentSince || new Date(Date.now() - 28 * 24 * 60 * 60 *
                            1000).toISOString().split('T')[0]);
                        $('#analyticsUntil').val(currentUntil || today);
                    } else {
                        $custom.hide();
                        loadAnalytics(currentPageId, val);
                    }
                });
                $(document).on('click', '#analyticsApplyCustom', function() {
                    var since = $('#analyticsSince').val();
                    var until = $('#analyticsUntil').val() || new Date().toISOString().split('T')[0];
                    if (!since) {
                        if (typeof toastr !== 'undefined') toastr.warning('Please select a start date.');
                        return;
                    }
                    loadAnalytics(currentPageId, 'custom', since, until);
                });
                $(document).on('click', '#analyticsRefresh', function() {
                    loadAnalytics(currentPageId, currentDuration, currentSince, currentUntil, true);
                });
            }

            $('.analytics-page-card').on('click', function() {
                var pageId = $(this).data('page-id');
                if (String(pageId) === String(currentPageId)) return;
                $('.analytics-page-card').removeClass('active');
                $(this).addClass('active');
                loadAnalytics(pageId || 'all', currentDuration, currentSince, currentUntil);
            }).on('keydown', function(e) {
                if (e.which === 13 || e.which === 32) {
                    e.preventDefault();
                    $(this).click();
                }
            });

            bindDurationHandlers();

            if (hasPages && currentPageId) {
                loadAnalytics(currentPageId, currentDuration, currentSince, currentUntil);
            }

            $('#analyticsPageSearch').on('input', function() {
                var query = $(this).val().toLowerCase().trim();
                $('.analytics-page-card').each(function() {
                    var text = ($(this).data('search') || '').toLowerCase();
                    $(this).toggle(query === '' || text.indexOf(query) !== -1);
                });
            });
        });
    </script>
@endif
