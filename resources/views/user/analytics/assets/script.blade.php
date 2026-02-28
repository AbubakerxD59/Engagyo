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
            var currentPostsSearchQuery = '';
            var currentPostsSortBy = 'post_impressions';
            var currentPostsSortOrder = 'desc';
            var isLoadingAnalytics = false;

            function formatMetric(val) {
                return (val !== null && val !== undefined && !isNaN(val)) ? parseInt(val).toLocaleString() : 'N/A';
            }

            function renderDurationDropdown(duration, since, until) {
                var customStyle = duration === 'custom' ? '' : ' style="display: none !important;"';
                return '<div class="analytics-duration-controls d-flex align-items-center gap-2 flex-wrap">' +
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
                    '</div>';
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

            var chartMetricOptions = [{
                    key: 'followers',
                    label: 'Followers',
                    byDayKey: 'followers_by_day'
                },
                {
                    key: 'reach',
                    label: 'Reach',
                    byDayKey: 'reach_by_day'
                },
                {
                    key: 'video_views',
                    label: 'Video Views',
                    byDayKey: 'video_views_by_day'
                },
                {
                    key: 'engagements',
                    label: 'Engagements',
                    byDayKey: 'engagements_by_day'
                }
            ];

            function renderEngagementsChart(insights, comp, selectedMetricKey) {
                selectedMetricKey = selectedMetricKey || 'engagements';
                var opt = chartMetricOptions.find(function(o) {
                    return o.key === selectedMetricKey;
                }) || chartMetricOptions[3];
                var byDay = insights[opt.byDayKey] || {};
                var dates = Object.keys(byDay).sort();
                var total = 0;
                dates.forEach(function(d) {
                    total += byDay[d] || 0;
                });
                var dailyAvg = dates.length > 0 ? Math.round(total / dates.length) : 0;
                var metricComp = comp[opt.key] || {};
                var pctChange = metricComp.change != null ? metricComp.change : null;
                var pctStr = (pctChange != null ? ' ' + pctChange + '%' : '');
                var chartHtml = dates.length > 0 ?
                    '<div class="chart-container" style="position: relative; height: 280px;"><canvas id="engagementsChartCanvas"></canvas></div>' :
                    '<div class="alert alert-light border text-muted mb-0"><i class="fas fa-info-circle mr-2"></i>No daily ' +
                    opt.label.toLowerCase() + ' data available for this period.</div>';
                var dropdownItems = chartMetricOptions.map(function(o) {
                    var isSelected = o.key === selectedMetricKey;
                    return '<a class="chart-metric-option' + (isSelected ? ' active' : '') +
                        '" href="#" data-metric="' + o.key + '"><span class="chart-metric-option-circle' + (
                            isSelected ? ' selected' : '') + '"></span><span>' + o.label + '</span></a>';
                }).join('');
                return '<div class="mt-4 pt-4 border-top chart-metric-section" data-selected-metric="' +
                    selectedMetricKey + '">' +
                    '<div class="d-flex align-items-center flex-wrap gap-2 mb-3">' +
                    '<h6 class="text-muted mb-0"><i class="fas fa-chart-bar mr-1"></i>Average <div class="dropdown chart-metric-dropdown-wrap">' +
                    '<button type="button" class="chart-metric-trigger dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">' +
                    '<span class="chart-metric-trigger-label">' + opt.label +
                    '</span><i class="fas fa-chevron-down chart-metric-trigger-chevron"></i></button>' +
                    '<div class="dropdown-menu chart-metric-dropdown">' + dropdownItems +
                    '</div></div></h6></div>' +
                    '<p class="small text-muted mb-2">(daily average: ' + dailyAvg.toLocaleString() + pctStr +
                    ')</p>' +
                    chartHtml + '</div>';
            }

            function initEngagementsChart(insights, metricKey) {
                metricKey = metricKey || 'engagements';
                var opt = chartMetricOptions.find(function(o) {
                    return o.key === metricKey;
                }) || chartMetricOptions[3];
                var byDay = insights[opt.byDayKey] || {};
                var dates = Object.keys(byDay).sort();
                if (dates.length === 0 || typeof Chart === 'undefined') return;
                if (window.engagementsChartInstance) {
                    window.engagementsChartInstance.destroy();
                    window.engagementsChartInstance = null;
                }
                var labels = dates.map(function(d) {
                    var dt = new Date(d + 'T12:00:00');
                    return dt.toLocaleDateString('en-GB', {
                        day: 'numeric',
                        month: 'short'
                    });
                });
                var data = dates.map(function(d) {
                    return byDay[d] || 0;
                });
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
                                            return dt.toLocaleDateString('en-GB', {
                                                day: 'numeric',
                                                month: 'long'
                                            });
                                        }
                                        return '';
                                    },
                                    label: function(ctx) {
                                        return ctx.raw.toLocaleString() + ' total';
                                    }
                                }
                            },
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true
                            },
                            x: {
                                grid: {
                                    display: false
                                }
                            }
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

            function formatPostDate(createdTime) {
                var val = createdTime && (typeof createdTime === 'object' ? createdTime.date : createdTime);
                if (!val) return '';
                var d = new Date(val);
                if (isNaN(d.getTime())) return '';
                var datePart = d.toLocaleDateString('en-US', {
                    month: 'long',
                    day: 'numeric',
                    year: 'numeric'
                });
                var timePart = d.toLocaleTimeString('en-US', {
                    hour: 'numeric',
                    minute: '2-digit',
                    hour12: true
                }).toLowerCase().replace(/\s/g, '');
                return datePart + ' ' + timePart;
            }

            var postInsightLabels = {
                post_clicks: 'Post Clicks',
                post_reactions: 'Reactions',
                post_impressions: 'Impressions',
                post_reach: 'Reach',
                post_engagement_rate: 'Engagement Rate'
            };

            var postInsightDisplayOrder = ['post_clicks', 'post_reactions', 'post_impressions', 'post_reach', 'post_engagement_rate'];

            var postSortOptions = [
                { key: 'post_impressions', label: 'Impressions' },
                { key: 'post_reach', label: 'Reach' },
                { key: 'post_clicks', label: 'Post Clicks' },
                { key: 'post_reactions', label: 'Reactions' },
                { key: 'post_engagement_rate', label: 'Eng. Rate' },
                { key: 'created_time', label: 'Date' }
            ];

            function renderPostsList(posts, since, until, searchQuery, sortBy, sortOrder) {
                searchQuery = (searchQuery || '').trim().toLowerCase();
                sortBy = sortBy || 'post_impressions';
                sortOrder = sortOrder || 'desc';
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
                var filtered = posts;
                if (searchQuery) {
                    filtered = posts.filter(function(p) {
                        var msg = (p.message || '').toLowerCase();
                        var story = (p.story || '').toLowerCase();
                        return msg.indexOf(searchQuery) !== -1 || story.indexOf(searchQuery) !== -1;
                    });
                }
                filtered = filtered.slice();
                filtered.sort(function(a, b) {
                    var va, vb;
                    if (sortBy === 'created_time') {
                        var aTime = a.created_time && (a.created_time.date || a.created_time);
                        var bTime = b.created_time && (b.created_time.date || b.created_time);
                        va = new Date(aTime || 0).getTime();
                        vb = new Date(bTime || 0).getTime();
                    } else {
                        va = parseInt((a.insights || {})[sortBy], 10) || 0;
                        vb = parseInt((b.insights || {})[sortBy], 10) || 0;
                    }
                    if (sortOrder === 'asc') return va > vb ? 1 : (va < vb ? -1 : 0);
                    return va < vb ? 1 : (va > vb ? -1 : 0);
                });
                var searchBar =
                    '<div class="analytics-posts-search-wrap">' +
                    '<div class="input-group input-group-sm">' +
                    '<div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-search text-muted"></i></span></div>' +
                    '<input type="search" id="analyticsPostsSearch" class="form-control" placeholder="Search posts by message..." aria-label="Search posts" value="' +
                    escapeHtml(searchQuery) + '">' +
                    '</div></div>';
                var sortLabel = (postSortOptions.find(function(o) { return o.key === sortBy; }) || postSortOptions[0]).label;
                var sortDropdown = '<div class="analytics-posts-sort-wrap">' +
                    '<label class="analytics-posts-sort-label">Sort by</label>' +
                    '<div class="dropdown d-inline-block">' +
                    '<button type="button" class="btn btn-sm btn-light dropdown-toggle analytics-posts-sort-btn d-flex align-items-center" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">' +
                    escapeHtml(sortLabel) + ' <i class="fas fa-chevron-down ml-1"></i></button>' +
                    '<div class="dropdown-menu dropdown-menu-right">' +
                    postSortOptions.map(function(o) {
                        return '<a class="dropdown-item" href="#" data-sort="' + o.key + '">' + (o.key === sortBy ? '<i class="fas fa-check mr-2 text-primary"></i>' : '<span class="mr-2" style="width:1em;display:inline-block;"></span>') + escapeHtml(o.label) + '</a>';
                    }).join('') + '</div></div>' +
                    '<label class="analytics-posts-sort-label mt-2">Order</label>' +
                    '<div class="btn-group btn-group-sm" role="group">' +
                    '<button type="button" class="btn btn-sm ' + (sortOrder === 'desc' ? 'btn-primary' : 'btn-outline-secondary') + ' analytics-posts-order-btn" data-order="desc" title="Descending"><i class="fas fa-sort-amount-down"></i></button>' +
                    '<button type="button" class="btn btn-sm ' + (sortOrder === 'asc' ? 'btn-primary' : 'btn-outline-secondary') + ' analytics-posts-order-btn" data-order="asc" title="Ascending"><i class="fas fa-sort-amount-up"></i></button>' +
                    '</div></div>';
                var html = '<div class="analytics-posts-tab-content">' +
                    '<div class="analytics-posts-header mb-3">' +
                    '<div class="analytics-posts-header-left">' +
                    '<div class="analytics-posts-title mb-2"><i class="fas fa-newspaper mr-2"></i>POSTS (' + filtered.length + (
                        searchQuery ? ' of ' + posts.length + ')' : ')') + '</div>' +
                    searchBar + '</div>' +
                    '<div class="analytics-posts-header-right">' + sortDropdown + '</div></div>' +
                    '<div class="analytics-posts-list">';
                filtered.forEach(function(post) {
                    var rawMsg = post.message || post.story || '';
                    var msg = escapeHtml(rawMsg.substring(0, 200));
                    if (rawMsg.length > 200) msg += '...';
                    var createdTimeVal = post.created_time && (post.created_time.date || post.created_time);
                    var created = formatPostDate(createdTimeVal);
                    var img = post.full_picture ? '<img src="' + escapeHtml(post.full_picture) +
                        '" alt="" class="analytics-post-thumb" loading="lazy">' :
                        '<div class="analytics-post-thumb-placeholder"><i class="fas fa-image text-muted"></i></div>';
                    var insights = post.insights || {};
                    var insightItems = [];
                    var order = postInsightDisplayOrder;
                    for (var k = 0; k < order.length; k++) {
                        var key = order[k];
                        if (key in insights) {
                            var val = insights[key];
                            var displayVal = (key === 'post_engagement_rate') ? (val || 0) + '%' : (val ||
                                0).toLocaleString();
                            insightItems.push(
                                '<div class="analytics-post-insight-item"><span class="analytics-post-insight-value">' +
                                displayVal + '</span><span class="analytics-post-insight-label">' + (
                                    postInsightLabels[key] || key) + '</span></div>');
                        }
                    }
                    for (var key in insights) {
                        if (insights.hasOwnProperty(key) && order.indexOf(key) === -1) {
                            var val = insights[key];
                            var displayVal = (key === 'post_engagement_rate') ? (val || 0) + '%' : (val ||
                                0).toLocaleString();
                            insightItems.push(
                                '<div class="analytics-post-insight-item"><span class="analytics-post-insight-value">' +
                                displayVal + '</span><span class="analytics-post-insight-label">' + (
                                    postInsightLabels[key] || key) + '</span></div>');
                        }
                    }
                    var insightHtml = insightItems.length > 0 ?
                        '<div class="analytics-post-insights-grid">' + insightItems.join('') + '</div>' :
                        '<p class="text-muted small mb-0">No insights available</p>';
                    var link = post.permalink_url ? '<a href="' + escapeHtml(post.permalink_url) +
                        '" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary analytics-post-view-btn"><i class="fas fa-external-link-alt mr-1"></i>View on Facebook</a>' :
                        '';
                    html += '<div class="analytics-post-card card mb-3">' +
                        '<div class="card-body">' +
                        '<div class="analytics-post-card-inner">' +
                        '<div class="analytics-post-thumb-wrap">' + img + '</div>' +
                        '<div class="analytics-post-content">' +
                        '<p class="analytics-post-date text-muted mb-2"><i class="far fa-clock mr-1"></i>' +
                        created + '</p>' +
                        '<p class="analytics-post-message mb-3">' + (msg ||
                        '<em class="text-muted"></em>') + '</p>' +
                        '<div class="analytics-post-insights-wrap mb-3">' + insightHtml + '</div>' +
                        link +
                        '</div></div></div></div>';
                });
                html += '</div></div>';
                if (filtered.length === 0 && searchQuery) {
                    html +=
                        '<p class="text-muted text-center py-3 mb-0"><i class="fas fa-search mr-1"></i>No posts match your search.</p>';
                }
                return html;
            }

            function renderPageInsights(insights, pageName, duration, since, until, pagePosts) {
                duration = duration || 'last_28';
                since = since || '';
                until = until || '';
                var overviewContent = '<div class="analytics-page-insights mb-4">';
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
                    var note =
                        '<p class="small mb-3" style="color: #856404;"><i class="fas fa-info-circle mr-1"></i>' +
                        'Page Insights data is only available on Pages with 100 or more likes.</p>';
                    overviewContent += note + '<div class="analytics-insight-cards">';
                    cards.forEach(function(c) {
                        overviewContent += renderInsightCard(insights[c[0]], c[1], comp[c[0]], c[2]);
                    });
                    overviewContent += '</div>';
                    overviewContent += renderEngagementsChart(insights, comp);
                    overviewContent += '</div>';
                }
                var postsContent = renderPostsList(pagePosts, since, until, currentPostsSearchQuery, currentPostsSortBy, currentPostsSortOrder);
                var durationDropdown = renderDurationDropdown(duration, since, until);
                return '<div class="analytics-tabs-row d-flex flex-wrap align-items-center justify-content-between mb-3">' +
                    '<ul class="nav nav-tabs analytics-insight-tabs mb-0" role="tablist">' +
                    '<li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#analyticsOverviewTab" role="tab">Overview</a></li>' +
                    '<li class="nav-item"><a class="nav-link" data-toggle="tab" href="#analyticsPostsTab" role="tab">Posts</a></li>' +
                    '</ul>' +
                    '<div class="analytics-tabs-duration">' + durationDropdown + '</div>' +
                    '</div>' +
                    '<div class="tab-content">' +
                    '<div class="tab-pane fade show active" id="analyticsOverviewTab" role="tabpanel">' +
                    overviewContent + '</div>' +
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

            function loadAnalytics(pageId, duration, since, until) {
                currentPageId = pageId || currentPageId;
                currentDuration = duration || currentDuration;
                currentSince = since || currentSince;
                currentUntil = until || currentUntil;

                var wasPostsTabActive = $content.find('a[href="#analyticsPostsTab"]').hasClass('active');

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
                        if (wasPostsTabActive) {
                            $content.find('a[href="#analyticsOverviewTab"]').removeClass('active');
                            $content.find('a[href="#analyticsPostsTab"]').addClass('active');
                            $content.find('#analyticsOverviewTab').removeClass('show active');
                            $content.find('#analyticsPostsTab').addClass('show active');
                        }
                        window.currentAnalyticsInsights = res.pageInsights || null;
                        window.currentPagePosts = res.pagePosts || null;
                        bindDurationHandlers();
                        bindChartMetricHandlers();
                        bindPostsSearchHandler();
                        bindPostsSortHandler();
                        var selectedMetric = $('.chart-metric-section').data('selected-metric') ||
                        'engagements';
                        if (res.selectedPage && res.pageInsights) {
                            var byDayKey = chartMetricOptions.find(function(o) {
                                return o.key === selectedMetric;
                            });
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
                    var opt = chartMetricOptions.find(function(o) {
                        return o.key === metric;
                    });
                    var byDay = opt ? (insights[opt.byDayKey] || {}) : {};
                    if (Object.keys(byDay).length > 0 && typeof Chart !== 'undefined') {
                        initEngagementsChart(insights, metric);
                    }
                });
            }

            function refreshPostsTab() {
                var posts = window.currentPagePosts;
                if (posts === null || !Array.isArray(posts)) return;
                var query = $('#analyticsPostsSearch').length ? $('#analyticsPostsSearch').val().trim() : currentPostsSearchQuery;
                currentPostsSearchQuery = query;
                var postsContent = renderPostsList(posts, currentSince, currentUntil, query, currentPostsSortBy, currentPostsSortOrder);
                $('#analyticsPostsTab').html(postsContent);
                bindPostsSearchHandler();
                bindPostsSortHandler();
            }

            function bindPostsSearchHandler() {
                $content.off('input', '#analyticsPostsSearch');
                $content.on('input', '#analyticsPostsSearch', function() {
                    currentPostsSearchQuery = $(this).val().trim();
                    refreshPostsTab();
                });
            }

            function bindPostsSortHandler() {
                $content.off('click', '.analytics-posts-sort-btn, .dropdown-item[data-sort], .analytics-posts-order-btn');
                $content.on('click', '.dropdown-item[data-sort]', function(e) {
                    e.preventDefault();
                    var sort = $(this).data('sort');
                    if (!sort) return;
                    currentPostsSortBy = sort;
                    refreshPostsTab();
                });
                $content.on('click', '.analytics-posts-order-btn', function(e) {
                    e.preventDefault();
                    var order = $(this).data('order');
                    if (!order) return;
                    currentPostsSortOrder = order;
                    refreshPostsTab();
                });
            }

            function bindDurationHandlers() {
                $(document).off('change', '#analyticsDuration');
                $(document).off('click', '#analyticsApplyCustom');
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
