@if ($facebookPages->count() > 0)
    <script>
        $(document).ready(function() {
            var $content = $('#analyticsContent');
            var analyticsUrl = $content.data('analytics-url');
            var hasPages = {{ $facebookPages->count() > 0 ? 'true' : 'false' }};
            var currentPageId = $('.analytics-page-card.active').data('page-id') || '';
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

            function renderEngagementsChart(insights, comp) {
                var byDay = insights.engagements_by_day || {};
                var dates = Object.keys(byDay).sort();
                if (dates.length === 0) return '';
                var total = 0;
                dates.forEach(function(d) { total += byDay[d] || 0; });
                var dailyAvg = Math.round(total / dates.length);
                var engComp = comp.engagements || {};
                var pctChange = engComp.change != null ? engComp.change : null;
                var pctStr = (pctChange != null ? ' ' + pctChange + '%' : '');
                return '<div class="mt-4 pt-4 border-top">' +
                    '<h6 class="text-muted mb-3"><i class="fas fa-chart-bar mr-1"></i>Average engagements</h6>' +
                    '<p class="small text-muted mb-2">(daily average: ' + dailyAvg.toLocaleString() + pctStr + ')</p>' +
                    '<div class="chart-container" style="position: relative; height: 280px;">' +
                    '<canvas id="engagementsChartCanvas"></canvas></div></div>';
            }

            function initEngagementsChart(insights) {
                var byDay = insights.engagements_by_day || {};
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
                            label: 'Engagements',
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
                var followers = insights.followers;
                if (followers == null || isNaN(followers) || followers < 100) return false;
                var keys = ['followers', 'reach', 'video_views', 'engagements'];
                for (var i = 0; i < keys.length; i++) {
                    var v = insights[keys[i]];
                    if (v != null && !isNaN(v)) return true;
                }
                return false;
            }

            function renderPageInsights(insights, pageName, duration, since, until) {
                duration = duration || 'last_28';
                since = since || '';
                until = until || '';
                var html = '<div class="analytics-page-insights mb-4">' + renderDurationDropdown(duration, since,
                    until);
                if (!hasMeaningfulInsights(insights)) {
                    html += '<div class="alert alert-info mb-0" role="alert">' +
                        '<strong><i class="fas fa-info-circle mr-2"></i>Insights can\'t be fetched for this page.</strong>' +
                        '<ol class="mb-0 mt-2 pl-3">' +
                        '<li>Page Insights data is only available on Pages with 100 or more likes.</li>' +
                        '<li>The connected account may not have the required permissions. <a href="{{ route('panel.accounts') }}" class="alert-link font-weight-bold">Reconnect your account</a> to grant access.</li>' +
                        '</ol></div></div>';
                    return html;
                }
                var comp = insights.comparison || {};
                var cards = [
                    ['followers', 'Followers', false],
                    ['reach', 'Reach', false],
                    ['video_views', 'Video Views', false],
                    ['engagements', 'Engagements', false]
                ];
                var note = '<p class="small mb-3" style="color: #856404;"><i class="fas fa-info-circle mr-1"></i>' +
                    'Page Insights data is only available on Pages with 100 or more likes.</p>';
                html += note + '<div class="row">';
                cards.forEach(function(c) {
                    html += '<div class="col-6 col-md-4 col-lg-2 mb-3">' +
                        renderInsightCard(insights[c[0]], c[1], comp[c[0]], c[2]) + '</div>';
                });
                html += '</div>';
                html += renderEngagementsChart(insights, comp);
                html += '</div>';
                return html;
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
                                currentSince, currentUntil);
                        } else {
                            html += renderEmptyState(!!currentPageId);
                        }
                        $content.html(html);
                        bindDurationHandlers();
                        if (res.selectedPage && res.pageInsights && res.pageInsights.engagements_by_day &&
                            Object.keys(res.pageInsights.engagements_by_day).length > 0) {
                            initEngagementsChart(res.pageInsights);
                        }
                    })
                    .fail(function() {
                        $content.html(renderEmptyState(!!currentPageId));
                        if (typeof toastr !== 'undefined') toastr.error('Failed to load analytics.');
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
                loadAnalytics(pageId, currentDuration, currentSince, currentUntil);
            }).on('keydown', function(e) {
                if (e.which === 13 || e.which === 32) {
                    e.preventDefault();
                    $(this).click();
                }
            });

            bindDurationHandlers();

            $('#analyticsPageSearch').on('input', function() {
                var query = $(this).val().toLowerCase().trim();
                $('.analytics-page-card').each(function() {
                    var text = $(this).data('search') || '';
                    $(this).toggle(query === '' || text.indexOf(query) !== -1);
                });
            });
        });
    </script>
@endif
