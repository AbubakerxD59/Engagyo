@if (($analyticsAccounts ?? collect())->count() > 0)
    <script>
        $(document).ready(function() {
            var $content = $('#analyticsContent');
            var analyticsUrl = $content.data('analytics-url');
            var hasPages = {{ ($analyticsAccounts ?? collect())->count() > 0 ? 'true' : 'false' }};
            var currentAccountRef = $('.analytics-page-card.active').data('account-ref') || 'facebook:all';
            var currentPlatform = ($('.analytics-page-card.active').data('platform') || 'facebook').toString();
            var currentDuration = '{{ $duration ?? 'last_28' }}';
            var currentSince = '{{ $since ?? '' }}';
            var currentUntil = '{{ $until ?? '' }}';
            var currentPostsSearchQuery = '';
            var currentPostsSortBy = 'created_time';
            var currentPostsSortOrder = 'desc';
            var currentPostsFetching = false;
            var currentPostsFetchingMessage = '';
            var isLoadingAnalytics = false;
            var analyticsRequest = null;
            var userTimezone = "{{ $userTimezoneName ?? 'UTC' }}";
            var analyticsPostsOffset = 0;
            var analyticsPostsLimit = 10;
            var analyticsPostsHasMore = false;
            var analyticsPostsLoadingMore = false;
            var analyticsPostsScrollBindVersion = 0;
            var currentPostsTotal = 0;

            function formatInUserTimezone(date, options) {
                if (!date || isNaN(date.getTime())) return '';
                try {
                    return new Intl.DateTimeFormat('en-US', Object.assign({
                        timeZone: userTimezone
                    }, options || {})).format(date);
                } catch (e) {
                    return new Intl.DateTimeFormat('en-US', options || {}).format(date);
                }
            }

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

            /** Pinterest: only daily series that come from Pinterest pin analytics (not app-derived totals). */
            var chartMetricOptionsPinterest = [{
                    key: 'reach',
                    label: 'Impressions',
                    byDayKey: 'reach_by_day'
                },
                {
                    key: 'video_views',
                    label: 'Video Views',
                    byDayKey: 'video_views_by_day'
                }
            ];

            /** TikTok: period aggregates from video.list (TikTok API field names). */
            var chartMetricOptionsTiktok = [{
                    key: 'view_count',
                    label: 'Views',
                    byDayKey: 'view_count_by_day'
                },
                {
                    key: 'like_count',
                    label: 'Likes',
                    byDayKey: 'like_count_by_day'
                },
                {
                    key: 'comment_count',
                    label: 'Comments',
                    byDayKey: 'comment_count_by_day'
                },
                {
                    key: 'share_count',
                    label: 'Shares',
                    byDayKey: 'share_count_by_day'
                }
            ];

            function chartMetricOptionsForPlatform(platform) {
                if (platform === 'pinterest') return chartMetricOptionsPinterest;
                if (platform === 'tiktok') return chartMetricOptionsTiktok;
                return chartMetricOptions;
            }

            function renderEngagementsChart(insights, comp, selectedMetricKey, platform) {
                platform = platform || 'facebook';
                var opts = chartMetricOptionsForPlatform(platform);
                selectedMetricKey = selectedMetricKey || (platform === 'pinterest' ? 'reach' : (platform === 'tiktok' ? 'view_count' : 'engagements'));
                var opt = opts.find(function(o) {
                    return o.key === selectedMetricKey;
                }) || (platform === 'pinterest' ? opts[0] : (platform === 'tiktok' ? chartMetricOptionsTiktok[0] : chartMetricOptions[3]));
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
                var dropdownItems = opts.map(function(o) {
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

            function initEngagementsChart(insights, metricKey, platform) {
                platform = platform || 'facebook';
                var opts = chartMetricOptionsForPlatform(platform);
                metricKey = metricKey || (platform === 'pinterest' ? 'reach' : (platform === 'tiktok' ? 'view_count' : 'engagements'));
                var opt = opts.find(function(o) {
                    return o.key === metricKey;
                }) || (platform === 'pinterest' ? opts[0] : (platform === 'tiktok' ? chartMetricOptionsTiktok[0] : chartMetricOptions[3]));
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

            function hasMeaningfulInsights(insights, platform) {
                platform = platform || 'facebook';
                if (!insights) return false;
                if (platform === 'pinterest') {
                    var pk = ['followers', 'reach', 'video_views'];
                    for (var i = 0; i < pk.length; i++) {
                        var pv = insights[pk[i]];
                        if (pv != null && !isNaN(pv)) return true;
                    }
                    if (insights.reach_by_day && Object.keys(insights.reach_by_day).length > 0) return true;
                    if (insights.video_views_by_day && Object.keys(insights.video_views_by_day).length > 0) return true;
                    return false;
                }
                if (platform === 'tiktok') {
                    var tk = ['follower_count', 'view_count', 'profile_view_count', 'like_count', 'comment_count', 'share_count'];
                    for (var t = 0; t < tk.length; t++) {
                        var tv = insights[tk[t]];
                        if (tv != null && !isNaN(tv)) return true;
                    }
                    if (insights.view_count_by_day && Object.keys(insights.view_count_by_day).length > 0) return true;
                    return false;
                }
                var keys = ['followers', 'reach', 'video_views', 'engagements'];
                for (var j = 0; j < keys.length; j++) {
                    var v = insights[keys[j]];
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

            function formatPostDateParts(createdTime) {
                var d = null;
                if (createdTime instanceof Date) {
                    d = createdTime;
                } else if (createdTime) {
                    d = parseCreatedTime(createdTime);
                }
                if (!d || isNaN(d.getTime())) return {
                    time: '',
                    date: ''
                };
                var datePart = formatInUserTimezone(d, {
                    month: 'long',
                    day: 'numeric',
                    year: 'numeric'
                });
                var timePart = formatInUserTimezone(d, {
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: true
                });
                return {
                    time: timePart,
                    date: datePart
                };
            }

            var postInsightLabels = {
                post_clicks: 'Post Clicks',
                post_reactions: 'Reactions',
                post_impressions: 'Impressions',
                post_reach: 'Reach',
                post_engagement_rate: 'Engagement Rate',
                pin_saves: 'Saves',
                outbound_clicks: 'Outbound Clicks',
                pin_clicks: 'Pin Clicks',
                video_mrc_view: 'Video Views',
                total_comments: 'Comments',
                total_reactions: 'Pin Reactions',
                view_count: 'Views',
                like_count: 'Likes',
                comment_count: 'Comments',
                share_count: 'Shares'
            };

            var postInsightDisplayOrder = ['post_clicks', 'post_reactions', 'post_impressions', 'post_reach', 'post_engagement_rate'];

            var postInsightDisplayOrderPinterest = ['post_impressions', 'pin_saves', 'outbound_clicks', 'pin_clicks', 'video_mrc_view', 'total_comments', 'total_reactions'];

            var postInsightDisplayOrderTiktok = ['view_count', 'like_count', 'comment_count', 'share_count'];

            var postSortOptions = [
                { key: 'post_impressions', label: 'Impressions' },
                { key: 'post_reach', label: 'Reach' },
                { key: 'post_clicks', label: 'Post Clicks' },
                { key: 'post_reactions', label: 'Reactions' },
                { key: 'post_engagement_rate', label: 'Eng. Rate' },
                { key: 'created_time', label: 'Date' }
            ];

            var postSortOptionsPinterest = [
                { key: 'post_impressions', label: 'Impressions' },
                { key: 'pin_saves', label: 'Saves' },
                { key: 'outbound_clicks', label: 'Outbound clicks' },
                { key: 'pin_clicks', label: 'Pin clicks' },
                { key: 'video_mrc_view', label: 'Video views' },
                { key: 'total_comments', label: 'Comments' },
                { key: 'total_reactions', label: 'Reactions' },
                { key: 'created_time', label: 'Date' }
            ];

            var postSortOptionsTiktok = [
                { key: 'view_count', label: 'Views' },
                { key: 'like_count', label: 'Likes' },
                { key: 'comment_count', label: 'Comments' },
                { key: 'share_count', label: 'Shares' },
                { key: 'created_time', label: 'Date' }
            ];

            function parseCreatedTime(ct) {
                if (!ct) return null;
                if (typeof ct === 'string') return new Date(ct);
                if (typeof ct === 'object' && ct.date) return new Date(ct.date.replace(' ', 'T') + 'Z');
                return null;
            }

            function renderPostsList(posts, since, until, searchQuery, sortBy, sortOrder, platform, totalPosts) {
                platform = platform || currentPlatform || 'facebook';
                searchQuery = (searchQuery || '').trim().toLowerCase();
                sortBy = sortBy || 'created_time';
                sortOrder = sortOrder || 'desc';
                totalPosts = Number(totalPosts || 0);
                var insightOrder = platform === 'pinterest' ? postInsightDisplayOrderPinterest :
                    (platform === 'tiktok' ? postInsightDisplayOrderTiktok : postInsightDisplayOrder);
                var sortOptionsList = platform === 'pinterest' ? postSortOptionsPinterest :
                    (platform === 'tiktok' ? postSortOptionsTiktok : postSortOptions);
                if (posts === null) {
                    return '<div class="analytics-posts-placeholder text-center py-5">' +
                        '<i class="fas fa-th-large fa-4x text-muted mb-3"></i>' +
                        '<p class="text-muted mb-0">Select an account to view posts.</p></div>';
                }
                if (!posts || posts.length === 0) {
                    if (currentPostsFetching) {
                        var fetchingMsg = currentPostsFetchingMessage || 'Posts for this page are being fetched. Please check back shortly.';
                        return '<div class="analytics-posts-placeholder text-center py-5">' +
                            '<i class="fas fa-sync-alt fa-spin fa-3x text-muted mb-3"></i>' +
                            '<p class="text-muted mb-0">' + escapeHtml(fetchingMsg) + '</p></div>';
                    }
                    return '<div class="analytics-posts-placeholder text-center py-5">' +
                        '<i class="fas fa-newspaper fa-4x text-muted mb-3"></i>' +
                        '<p class="text-muted mb-0">No posts in this period.</p></div>';
                }
                var filtered = posts;
                if (searchQuery) {
                    filtered = posts.filter(function(p) {
                        var msg = (p.message || '').toLowerCase();
                        var story = (p.story || '').toLowerCase();
                        var desc = (p.video_description || '').toLowerCase();
                        var title = (p.title || '').toLowerCase();
                        return msg.indexOf(searchQuery) !== -1 || story.indexOf(searchQuery) !== -1 ||
                            desc.indexOf(searchQuery) !== -1 || title.indexOf(searchQuery) !== -1;
                    });
                }
                filtered = filtered.slice();
                filtered.sort(function(a, b) {
                    var va, vb;
                    if (sortBy === 'created_time') {
                        var aDate = parseCreatedTime(a.created_time);
                        var bDate = parseCreatedTime(b.created_time);
                        va = aDate && !isNaN(aDate.getTime()) ? aDate.getTime() : 0;
                        vb = bDate && !isNaN(bDate.getTime()) ? bDate.getTime() : 0;
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
                var sortLabel = (sortOptionsList.find(function(o) { return o.key === sortBy; }) || sortOptionsList[0]).label;
                var sortDropdown = '<div class="analytics-posts-sort-wrap">' +
                    '<div class="analytics-posts-sort-group">' +
                    '<label class="analytics-posts-sort-label">Sort by</label>' +
                    '<div class="dropdown d-inline-block">' +
                    '<button type="button" class="btn btn-sm btn-light dropdown-toggle analytics-posts-sort-btn d-flex align-items-center" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">' +
                    escapeHtml(sortLabel) + ' <i class="fas fa-chevron-down ml-1"></i></button>' +
                    '<div class="dropdown-menu dropdown-menu-right">' +
                    sortOptionsList.map(function(o) {
                        return '<a class="dropdown-item" href="#" data-sort="' + o.key + '">' + (o.key === sortBy ? '<i class="fas fa-check mr-2 text-primary"></i>' : '<span class="mr-2" style="width:1em;display:inline-block;"></span>') + escapeHtml(o.label) + '</a>';
                    }).join('') + '</div></div></div>' +
                    '<div class="analytics-posts-sort-group">' +
                    '<label class="analytics-posts-sort-label">Order</label>' +
                    '<div class="btn-group btn-group-sm" role="group">' +
                    '<button type="button" class="btn btn-sm ' + (sortOrder === 'desc' ? 'btn-primary' : 'btn-outline-secondary') + ' analytics-posts-order-btn" data-order="desc" title="Descending"><i class="fas fa-sort-amount-down"></i></button>' +
                    '<button type="button" class="btn btn-sm ' + (sortOrder === 'asc' ? 'btn-primary' : 'btn-outline-secondary') + ' analytics-posts-order-btn" data-order="asc" title="Ascending"><i class="fas fa-sort-amount-up"></i></button>' +
                    '</div></div></div>';
                var html = '<div class="analytics-posts-tab-content">' +
                    '<div class="analytics-posts-header mb-3">' +
                    '<div class="analytics-posts-header-left">' +
                    '<div class="analytics-posts-title mb-2"><i class="fas fa-newspaper mr-2"></i>POSTS (' + totalPosts +
                    (searchQuery ? ', filtered ' + filtered.length : '') + ')</div>' +
                    searchBar + '</div>' +
                    '<div class="analytics-posts-header-right">' + sortDropdown + '</div></div>' +
                    '<div class="analytics-posts-list">';
                filtered.forEach(function(post) {
                    var rawMsg = post.message || post.story || '';
                    var msg = escapeHtml(rawMsg.substring(0, 200));
                    if (rawMsg.length > 200) msg += '...';
                    var ct = parseCreatedTime(post.created_time);
                    var created = formatPostDateParts(ct);
                    var createdHtml = created.time ?
                        '<span class="analytics-post-time">' + escapeHtml(created.time) + '</span><span class="analytics-post-day">' + escapeHtml(created.date) + '</span>' :
                        '<span class="analytics-post-day">-</span>';
                    var postMediaType = (post.media_type || '').toString().toLowerCase();
                    var isThreadsVideo = platform === 'threads' && (postMediaType === 'video' || (post.type || '').toString().toLowerCase() === 'video');
                    var img = '';
                    if (isThreadsVideo && post.full_picture) {
                        var vSrc = escapeHtml(post.full_picture);
                        img = '<video class="analytics-post-thumb" preload="metadata" playsinline controls>' +
                            '<source src="' + vSrc + '" type="video/mp4">' +
                            '</video>';
                    } else if (post.full_picture) {
                        img = '<img src="' + escapeHtml(post.full_picture) +
                            '" alt="" class="analytics-post-thumb" loading="lazy">';
                    } else {
                        img = '<div class="analytics-post-thumb-placeholder"><i class="fas fa-image text-muted"></i></div>';
                    }
                    var insights = post.insights || {};
                    var insightItems = [];
                    var order = insightOrder;
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
                        if (platform === 'pinterest' || platform === 'tiktok') {
                            break;
                        }
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
                    var viewLabel = platform === 'threads' ? 'View on Threads' :
                        (platform === 'pinterest' ? 'View on Pinterest' :
                            (platform === 'tiktok' ? 'View on TikTok' : 'View on Facebook'));
                    var link = post.permalink_url ? '<a href="' + escapeHtml(post.permalink_url) +
                        '" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary analytics-post-view-btn"><i class="fas fa-external-link-alt mr-1"></i>' + viewLabel + '</a>' :
                        '';
                    html += '<div class="analytics-post-card card mb-3">' +
                        '<div class="card-body">' +
                        '<div class="analytics-post-card-inner">' +
                        '<div class="analytics-post-thumb-wrap">' + img + '</div>' +
                        '<div class="analytics-post-content">' +
                        '<p class="analytics-post-date mb-2">' + createdHtml + '</p>' +
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

            function renderPageInsights(insights, pageName, duration, since, until, pagePosts, platform) {
                platform = platform || 'facebook';
                duration = duration || 'last_28';
                since = since || '';
                until = until || '';
                var overviewContent = '<div class="analytics-page-insights mb-4">';
                if (!hasMeaningfulInsights(insights, platform)) {
                    overviewContent += '<div class="alert alert-info mb-0" role="alert">' +
                        '<strong><i class="fas fa-clock mr-2"></i>Insights will appear shortly</strong>' +
                        '<p class="mb-2 mt-2 mb-0">Account metrics are synced on a schedule. Check back in a few minutes—data usually shows up soon after you connect an account or after the next sync run.</p>' +
                        '<p class="small mb-0 mt-2">If nothing appears after a longer wait, open <a href="{{ route('panel.accounts') }}" class="alert-link font-weight-bold">Accounts</a> to confirm permissions or reconnect.</p>' +
                        '</div></div>';
                } else {
                    var comp = insights.comparison || {};
                    var cards = platform === 'pinterest' ? [
                        ['followers', 'Followers', false],
                        ['reach', 'Impressions', false],
                        ['video_views', 'Video Views', false]
                    ] : (platform === 'tiktok' ? [
                        ['follower_count', 'Followers', false],
                        ['view_count', 'Video Views', false],
                        ['profile_view_count', 'Profile Views', false],
                        ['like_count', 'Likes', false],
                        ['comment_count', 'Comments', false],
                        ['share_count', 'Shares', false]
                    ] : [
                        ['followers', 'Followers', false],
                        ['reach', 'Reach', false],
                        ['video_views', 'Video Views', false],
                        ['engagements', 'Engagements', false]
                    ]);
                    var note = platform === 'facebook' ?
                        '<p class="small mb-3" style="color: #856404;"><i class="fas fa-info-circle mr-1"></i>Page Insights data is only available on Pages with 100 or more likes.</p>' :
                        (platform === 'pinterest' ?
                            '<p class="small mb-3 text-muted"><i class="fas fa-info-circle mr-1"></i>Pinterest board metrics aggregate pin analytics (impressions, saves, clicks) for pins on this board. Pinterest limits analytics history (typically up to ~90 days).</p>' :
                            (platform === 'tiktok' ?
                                '<p class="small mb-3 text-muted"><i class="fas fa-info-circle mr-1"></i>Followers come from TikTok user.info.stats. Video views, likes, comments, and shares are totals for public videos published in the selected period (video.list). Profile views show N/A when TikTok’s API does not return that field—reconnect TikTok after scope updates if needed.</p>' :
                                '<p class="small mb-3 text-muted"><i class="fas fa-info-circle mr-1"></i>Threads insights are aggregated from available media metrics for the selected date range.</p>'));
                    overviewContent += note + '<div class="analytics-insight-cards' +
                        (platform === 'tiktok' ? ' analytics-insight-cards--tiktok' : '') + '">';
                    cards.forEach(function(c) {
                        overviewContent += renderInsightCard(insights[c[0]], c[1], comp[c[0]], c[2]);
                    });
                    overviewContent += '</div>';
                    overviewContent += renderEngagementsChart(insights, comp,
                        platform === 'pinterest' ? 'reach' : (platform === 'tiktok' ? 'view_count' : undefined), platform);
                    overviewContent += '</div>';
                }
                var postsContent = renderPostsList(pagePosts, since, until, currentPostsSearchQuery, currentPostsSortBy, currentPostsSortOrder, platform, currentPostsTotal);
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
                var msg = hasPageSelected ? 'Unable to load account insights.' :
                    'Select an account from the sidebar to view analytics.';
                if (!hasPages) msg = 'Connect Facebook, Threads, Pinterest, or TikTok to see analytics here.';
                var title = hasPages ? 'Select an Account' : 'No Accounts Connected';
                return '<div class="empty-state text-center py-5">' +
                    '<div class="empty-state-icon mb-3"><i class="fas fa-chart-bar fa-4x text-muted"></i></div>' +
                    '<h4>' + title + '</h4><p class="text-muted">' + msg + '</p>' +
                    '<a href="{{ route('panel.accounts') }}" class="btn btn-primary mt-2"><i class="fas fa-user-circle mr-2"></i> Go to Accounts</a></div>';
            }

            function loadAnalytics(accountRef, duration, since, until, postsOffset) {
                if (analyticsRequest && analyticsRequest.readyState !== 4) {
                    analyticsRequest.abort();
                }
                var isLoadMoreRequest = Number(postsOffset || 0) > 0;
                currentAccountRef = accountRef || currentAccountRef;
                currentDuration = duration || currentDuration;
                currentSince = since || currentSince;
                currentUntil = until || currentUntil;
                if (postsOffset === undefined || postsOffset === null) {
                    postsOffset = 0;
                    analyticsPostsOffset = 0;
                    analyticsPostsHasMore = false;
                }

                var wasPostsTabActive = $content.find('a[href="#analyticsPostsTab"]').hasClass('active');

                if (!isLoadMoreRequest) {
                    $content.html(
                        '<div class="text-center py-5"><i class="fas fa-spinner fa-spin fa-2x text-muted"></i><p class="mt-2 text-muted">Loading analytics...</p></div>'
                    );
                }
                var params = {
                    account_ref: currentAccountRef || '',
                    posts_offset: postsOffset,
                    posts_limit: analyticsPostsLimit
                };
                if (currentDuration) params.duration = currentDuration;
                if (currentDuration === 'custom' && currentSince) {
                    params.duration = 'custom';
                    params.since = currentSince;
                    params.until = currentUntil || new Date().toISOString().split('T')[0];
                }
                analyticsRequest = $.get(analyticsUrl, params)
                    .always(function() {
                        isLoadingAnalytics = false;
                        analyticsRequest = null;
                    })
                    .done(function(res) {
                        if (!res.success) {
                            if (!isLoadMoreRequest) {
                                $content.html(renderEmptyState(!!currentAccountRef));
                            } else {
                                analyticsPostsHasMore = false;
                            }
                            currentPostsTotal = 0;
                            return;
                        }
                        var incomingPosts = Array.isArray(res.pagePosts) ? res.pagePosts : [];
                        currentPostsTotal = Number(res.pagePostsTotal || 0);
                        var mergedPosts;
                        if (isLoadMoreRequest && Array.isArray(window.currentPagePosts)) {
                            mergedPosts = window.currentPagePosts.concat(incomingPosts);
                        } else {
                            mergedPosts = incomingPosts;
                        }
                        window.currentPagePosts = mergedPosts;
                        var html = '';
                        if (res.selectedPage) {
                            if (res.since) currentSince = res.since;
                            if (res.until) currentUntil = res.until;
                            currentPlatform = res.platform || currentPlatform;
                            html += renderPageInsights(res.pageInsights, res.selectedPage.name, currentDuration,
                                currentSince, currentUntil, mergedPosts, currentPlatform);
                        } else {
                            html += renderEmptyState(!!currentAccountRef);
                        }
                        if (!isLoadMoreRequest) {
                            $content.html(html);
                            if (wasPostsTabActive) {
                                $content.find('a[href="#analyticsOverviewTab"]').removeClass('active');
                                $content.find('a[href="#analyticsPostsTab"]').addClass('active');
                                $content.find('#analyticsOverviewTab').removeClass('show active');
                                $content.find('#analyticsPostsTab').addClass('show active');
                            }
                        } else {
                            refreshPostsTab();
                        }
                        window.currentAnalyticsInsights = res.pageInsights || null;
                        currentPostsFetching = !!res.posts_fetching;
                        currentPostsFetchingMessage = res.posts_fetching_message || '';
                        analyticsPostsOffset = Number(res.pagePostsNextOffset || (Array.isArray(mergedPosts) ? mergedPosts.length : 0));
                        analyticsPostsHasMore = !!res.pagePostsHasMore;
                        analyticsPostsLoadingMore = false;
                        bindDurationHandlers();
                        bindChartMetricHandlers();
                        bindPostsSearchHandler();
                        bindPostsSortHandler();
                        bindAnalyticsPostsInfiniteScroll();
                        var plt = res.platform || currentPlatform || 'facebook';
                        var chartOpts = chartMetricOptionsForPlatform(plt);
                        var selectedMetric = $('.chart-metric-section').data('selected-metric') ||
                            (plt === 'pinterest' ? 'reach' : (plt === 'tiktok' ? 'view_count' : 'engagements'));
                        if (!chartOpts.find(function(o) {
                                return o.key === selectedMetric;
                            })) {
                            selectedMetric = plt === 'pinterest' ? 'reach' : (plt === 'tiktok' ? 'view_count' : 'engagements');
                        }
                        if (res.selectedPage && res.pageInsights) {
                            var optRow = chartOpts.find(function(o) {
                                return o.key === selectedMetric;
                            });
                            var byDayKeyResolved = optRow ? optRow.byDayKey :
                                (plt === 'pinterest' ? 'reach_by_day' : (plt === 'tiktok' ? 'view_count_by_day' : 'engagements_by_day'));
                            var byDay = (res.pageInsights[byDayKeyResolved] || {});
                            if (Object.keys(byDay).length > 0) {
                                initEngagementsChart(res.pageInsights, selectedMetric, plt);
                            }
                        }
                    })
                    .fail(function(xhr, textStatus) {
                        if (textStatus === 'abort') return;
                        analyticsPostsLoadingMore = false;
                        if (!isLoadMoreRequest) {
                            $content.html(renderEmptyState(!!currentAccountRef));
                        }
                        if (typeof toastr !== 'undefined') toastr.error('Failed to load analytics.');
                    });
            }

            function loadMoreAnalyticsPosts() {
                if (analyticsPostsLoadingMore || !analyticsPostsHasMore) return;
                if (!currentAccountRef || currentAccountRef === 'facebook:all') return;
                if (currentPostsSearchQuery && currentPostsSearchQuery.trim() !== '') return;
                analyticsPostsLoadingMore = true;
                loadAnalytics(currentAccountRef, currentDuration, currentSince, currentUntil, analyticsPostsOffset);
            }

            function bindChartMetricHandlers() {
                $(document).off('click', '.chart-metric-option');
                $(document).on('click', '.chart-metric-option', function(e) {
                    e.preventDefault();
                    var metric = $(this).data('metric');
                    var $section = $(this).closest('.chart-metric-section');
                    var insights = window.currentAnalyticsInsights;
                    if (!insights || !$section.length) return;
                    var plt = (typeof currentPlatform !== 'undefined' && currentPlatform) ? currentPlatform : 'facebook';
                    var comp = insights.comparison || {};
                    var newHtml = renderEngagementsChart(insights, comp, metric, plt);
                    $section.replaceWith(newHtml);
                    var opts = chartMetricOptionsForPlatform(plt);
                    var opt = opts.find(function(o) {
                        return o.key === metric;
                    });
                    var byDay = opt ? (insights[opt.byDayKey] || {}) : {};
                    if (Object.keys(byDay).length > 0 && typeof Chart !== 'undefined') {
                        initEngagementsChart(insights, metric, plt);
                    }
                });
            }

            function refreshPostsTab() {
                var posts = window.currentPagePosts;
                if (posts === null || !Array.isArray(posts)) return;
                var query = $('#analyticsPostsSearch').length ? $('#analyticsPostsSearch').val().trim() : currentPostsSearchQuery;
                currentPostsSearchQuery = query;
                var postsContent = renderPostsList(posts, currentSince, currentUntil, query, currentPostsSortBy, currentPostsSortOrder, currentPlatform, currentPostsTotal);
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

            function bindAnalyticsPostsInfiniteScroll() {
                analyticsPostsScrollBindVersion += 1;
                var bindVersion = analyticsPostsScrollBindVersion;
                $(window).off('scroll.analyticsPostsLoadMore');
                $(window).on('scroll.analyticsPostsLoadMore', function() {
                    var $postsTabLink = $content.find('a[href="#analyticsPostsTab"]');
                    if (!$postsTabLink.hasClass('active')) return;
                    var $cards = $content.find('#analyticsPostsTab .analytics-post-card');
                    if (!$cards.length) return;
                    var triggerIndex = Math.max(0, $cards.length - 5);
                    var $triggerCard = $cards.eq(triggerIndex);
                    if (!$triggerCard.length) return;
                    var scrollBottom = $(window).scrollTop() + $(window).height();
                    var triggerPoint = $triggerCard.offset().top + ($triggerCard.outerHeight() / 2);
                    if (scrollBottom >= triggerPoint && bindVersion === analyticsPostsScrollBindVersion) {
                        loadMoreAnalyticsPosts();
                    }
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
                        loadAnalytics(currentAccountRef, val);
                    }
                });
                $(document).on('click', '#analyticsApplyCustom', function() {
                    var since = $('#analyticsSince').val();
                    var until = $('#analyticsUntil').val() || new Date().toISOString().split('T')[0];
                    if (!since) {
                        if (typeof toastr !== 'undefined') toastr.warning('Please select a start date.');
                        return;
                    }
                    loadAnalytics(currentAccountRef, 'custom', since, until);
                });
            }

            $('.analytics-page-card').on('click', function() {
                var accountRef = $(this).data('account-ref');
                if (String(accountRef) === String(currentAccountRef)) return;
                $('.analytics-page-card').removeClass('active');
                $(this).addClass('active');
                currentPlatform = ($(this).data('platform') || 'facebook').toString();
                loadAnalytics(accountRef || 'facebook:all', currentDuration, currentSince, currentUntil, 0);
            }).on('keydown', function(e) {
                if (e.which === 13 || e.which === 32) {
                    e.preventDefault();
                    $(this).click();
                }
            });

            bindDurationHandlers();

            if (hasPages && currentAccountRef) {
                loadAnalytics(currentAccountRef, currentDuration, currentSince, currentUntil, 0);
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
