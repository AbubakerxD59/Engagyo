@if ($facebookPages->count() > 0)
    <script>
        $(document).ready(function() {
            var $content = $('#analyticsContent');
            var analyticsUrl = $content.data('analytics-url');
            var hasPages = {{ $facebookPages->count() > 0 ? 'true' : 'false' }};
            var currentPageId = $('.analytics-page-card.active').data('page-id') || '';
            var currentDuration = '{{ $duration ?? "last_28" }}';
            var currentSince = '{{ $since ?? "" }}';
            var currentUntil = '{{ $until ?? "" }}';

            function formatMetric(val) {
                return (val !== null && val !== undefined && !isNaN(val)) ? parseInt(val).toLocaleString() : 'N/A';
            }

            function renderDurationDropdown(duration, since, until) {
                var customStyle = duration === 'custom' ? '' : ' style="display: none !important;"';
                return '<div class="d-flex flex-wrap align-items-center justify-content-between mb-3">' +
                    '<h6 class="text-muted mb-0"><i class="fas fa-chart-pie mr-1"></i>Page Insights</h6>' +
                    '<div class="analytics-duration-controls d-flex align-items-center gap-2 flex-wrap">' +
                    '<select id="analyticsDuration" class="form-control form-control-sm" style="width: auto; min-width: 140px;">' +
                    '<option value="last_7"' + (duration === 'last_7' ? ' selected' : '') + '>Last 7 days</option>' +
                    '<option value="last_28"' + (duration === 'last_28' ? ' selected' : '') + '>Last 28 days</option>' +
                    '<option value="last_90"' + (duration === 'last_90' ? ' selected' : '') + '>Last 90 days</option>' +
                    '<option value="this_month"' + (duration === 'this_month' ? ' selected' : '') + '>This month</option>' +
                    '<option value="this_year"' + (duration === 'this_year' ? ' selected' : '') + '>This year</option>' +
                    '<option value="custom"' + (duration === 'custom' ? ' selected' : '') + '>Custom Range</option>' +
                    '</select>' +
                    '<div id="analyticsCustomRange" class="d-flex align-items-center gap-2"' + customStyle + '>' +
                    '<input type="date" id="analyticsSince" class="form-control form-control-sm" value="' + (since || '') + '" style="width: auto;">' +
                    '<span class="text-muted">to</span>' +
                    '<input type="date" id="analyticsUntil" class="form-control form-control-sm" value="' + (until || '') + '" style="width: auto;">' +
                    '<button type="button" id="analyticsApplyCustom" class="btn btn-sm btn-primary">Apply</button>' +
                    '</div></div></div>';
            }

            function renderPageInsights(insights, pageName, duration, since, until) {
                if (!insights) return '';
                duration = duration || 'last_28';
                since = since || '';
                until = until || '';
                return '<div class="analytics-page-insights mb-4">' +
                    renderDurationDropdown(duration, since, until) +
                    '<div class="row">' +
                    '<div class="col-6 col-md-4 col-lg-2 mb-3"><div class="page-insight-card">' +
                    '<span class="page-insight-value">' + formatMetric(insights.followers) + '</span>' +
                    '<span class="page-insight-label">Followers</span></div></div>' +
                    '<div class="col-6 col-md-4 col-lg-2 mb-3"><div class="page-insight-card">' +
                    '<span class="page-insight-value">' + formatMetric(insights.reach) + '</span>' +
                    '<span class="page-insight-label">Reach</span></div></div>' +
                    '<div class="col-6 col-md-4 col-lg-2 mb-3"><div class="page-insight-card">' +
                    '<span class="page-insight-value">' + formatMetric(insights.video_views) + '</span>' +
                    '<span class="page-insight-label">Video Views</span></div></div>' +
                    '<div class="col-6 col-md-4 col-lg-2 mb-3"><div class="page-insight-card">' +
                    '<span class="page-insight-value">' + formatMetric(insights.engagements) + '</span>' +
                    '<span class="page-insight-label">Engagements (reactions, comments, shares)</span></div></div>' +
                    '<div class="col-6 col-md-4 col-lg-2 mb-3"><div class="page-insight-card">' +
                    '<span class="page-insight-value">' + formatMetric(insights.link_clicks) + '</span>' +
                    '<span class="page-insight-label">Link Clicks</span></div></div>' +
                    '<div class="col-6 col-md-4 col-lg-2 mb-3"><div class="page-insight-card">' +
                    '<span class="page-insight-value">' + (insights.click_through_rate != null ? insights
                        .click_through_rate + '%' : 'N/A') + '</span>' +
                    '<span class="page-insight-label">Click Through Rate</span></div></div>' +
                    '</div></div>';
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

                $content.html(
                    '<div class="text-center py-5"><i class="fas fa-spinner fa-spin fa-2x text-muted"></i><p class="mt-2 text-muted">Loading analytics...</p></div>'
                );
                var params = { page_id: currentPageId || '' };
                if (currentDuration) params.duration = currentDuration;
                if (currentDuration === 'custom' && currentSince) {
                    params.duration = 'custom';
                    params.since = currentSince;
                    params.until = currentUntil || new Date().toISOString().split('T')[0];
                }
                $.get(analyticsUrl, params)
                    .done(function(res) {
                        if (!res.success) {
                            $content.html(renderEmptyState(!!currentPageId));
                            return;
                        }
                        var html = '';
                        if (res.pageInsights && res.selectedPage) {
                            if (res.since) currentSince = res.since;
                            if (res.until) currentUntil = res.until;
                            html += renderPageInsights(res.pageInsights, res.selectedPage.name, currentDuration, currentSince, currentUntil);
                        } else {
                            html += renderEmptyState(!!currentPageId);
                        }
                        $content.html(html);
                        bindDurationHandlers();
                        if (typeof history !== 'undefined' && history.pushState) {
                            var url = '{{ route('panel.analytics') }}' + (currentPageId ? '?page_id=' + currentPageId : '');
                            if (currentDuration && currentDuration !== 'last_28') url += (url.indexOf('?') >= 0 ? '&' : '?') + 'duration=' + currentDuration;
                            if (currentDuration === 'custom' && currentSince) {
                                url += (url.indexOf('?') >= 0 ? '&' : '?') + 'since=' + currentSince + '&until=' + (currentUntil || new Date().toISOString().split('T')[0]);
                            }
                            history.pushState({ pageId: currentPageId }, '', url);
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
                $(document).on('change', '#analyticsDuration', function() {
                    var val = $(this).val();
                    var $custom = $('#analyticsCustomRange');
                    if (val === 'custom') {
                        $custom.show();
                        var today = new Date().toISOString().split('T')[0];
                        $('#analyticsSince').val(currentSince || new Date(Date.now() - 28*24*60*60*1000).toISOString().split('T')[0]);
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
