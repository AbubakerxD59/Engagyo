<script>
    $(document).ready(function() {
        var userTimezone = "{{ $userTimezoneName ?? 'UTC' }}";
        var canAccessAnalytics = {{ isset($canAccessAnalytics) && $canAccessAnalytics ? 'true' : 'false' }};

        function formatInUserTimezone(date, options) {
            if (!date || isNaN(date.getTime())) return '';
            try {
                return new Intl.DateTimeFormat('en-US', Object.assign({ timeZone: userTimezone }, options || {})).format(date);
            } catch (e) {
                return new Intl.DateTimeFormat('en-US', options || {}).format(date);
            }
        }

        function getDatePartsInUserTimezone(date) {
            if (!date || isNaN(date.getTime())) return null;
            try {
                var formatter = new Intl.DateTimeFormat('en-US', { timeZone: userTimezone, year: 'numeric', month: 'long', day: 'numeric', weekday: 'long' });
                var parts = formatter.formatToParts(date);
                var result = {};
                parts.forEach(function(p) { result[p.type] = p.value; });
                return result;
            } catch (e) {
                return { year: String(date.getFullYear()), month: date.toLocaleString('en-US', { month: 'long' }), day: String(date.getDate()), weekday: ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'][date.getDay()] };
            }
        }

        // global variables
        var action_name = '';
        var $createPostActiveButton = null;
        var createPostFromTimeslot = false;
        var createPostSlotDate = '';
        var createPostSlotTime = '';
        var createPostSlotDisplay = '';
        var createPostSlotDisplayFooter = '';
        var current_file = 0;
        var is_link = 0;
        var is_video = 0;
        var currentShortUrl = null;
        var originalUrlInContent = null;
        // TikTok Modal Functions
        var currentTikTokFile = null;
        var currentTikTokAccounts = [];
        // Variables for TikTok link posts
        var currentTikTokLinkUrl = null;
        var currentTikTokLinkImage = null;
        var currentTikTokScheduleDate = null;
        var currentTikTokScheduleTime = null;
        // character count
        getCharacterCount($('.check_count'));
        function getSelectedAccounts() {
            var accountIds = [];
            var accountTypes = [];
            $('.account-card.active:not(.all-channels-card)').each(function() {
                var accountId = $(this).data("id");
                var accountType = $(this).data("type");
                if (accountId && accountType) {
                    accountIds.push(accountId);
                    if (accountTypes.indexOf(accountType) === -1) {
                        accountTypes.push(accountType);
                    }
                }
            });
            return {
                accountIds: accountIds,
                accountTypes: accountTypes
            };
        }

        function isAllChannelsActive() {
            return $('.all-channels-card').hasClass('active');
        }

        function updateUrlFromAccountSelection() {
            var url = new URL(window.location.href);
            if (isAllChannelsActive()) {
                url.searchParams.delete('account_id');
            } else {
                var selected = getSelectedAccounts();
                if (selected.accountIds.length === 1) {
                    url.searchParams.set('account_id', selected.accountIds[0]);
                } else {
                    url.searchParams.delete('account_id');
                }
            }
            var newUrl = url.pathname + (url.search ? url.search : '');
            if (window.location.pathname + window.location.search !== newUrl) {
                window.history.replaceState({}, '', newUrl);
            }
        }

        function applyAccountSelectionFromData(data) {
            try {
                if (!data || !data.type) return false;
                if (data.type === 'all') {
                    $('.account-card').removeClass('active');
                    $('.all-channels-card').addClass('active');
                    $('.account-card:not(.all-channels-card)').addClass('active');
                    return true;
                }
                var accountId = data.id;
                if (!accountId) return false;
                var accType = data.type || 'facebook';
                var $card = $('.account-card:not(.all-channels-card)[data-id="' + accountId + '"][data-type="' + accType + '"]');
                if ($card.length === 0) return false;
                $('.account-card').removeClass('active');
                $card.addClass('active');
                return true;
            } catch (e) {
                return false;
            }
        }

        function saveSelectedAccountToDb() {
            try {
                var payload;
                if (isAllChannelsActive()) {
                    payload = { type: 'all', id: null, _token: "{{ csrf_token() }}" };
                } else {
                    var selected = getSelectedAccounts();
                    if (selected.accountIds.length === 1) {
                        payload = { type: selected.accountTypes[0], id: String(selected.accountIds[0]), _token: "{{ csrf_token() }}" };
                    } else {
                        payload = { type: 'all', id: null, _token: "{{ csrf_token() }}" };
                    }
                }
                $.post("{{ route('panel.schedule.selected-account.save') }}", payload);
            } catch (e) {}
        }

        function applyAccountSelectionFromUrl() {
            var params = new URLSearchParams(window.location.search);
            var accountId = params.get('account_id');
            if (!accountId) return false;
            var $card = $('.account-card:not(.all-channels-card)[data-id="' + accountId + '"]');
            if ($card.length === 0) return false;
            $('.account-card').removeClass('active');
            $card.addClass('active');
            return true;
        }

        function selectFirstConnectedAccount() {
            var $firstCard = $('.account-card:not(.all-channels-card)').first();
            if ($firstCard.length === 0) return false;
            $('.account-card').removeClass('active');
            $firstCard.addClass('active');
            return true;
        }

        function updateSelectedAccountHeader() {
            var $allCh = $('.all-channels-card');
            var $realActive = $('.account-card.active:not(.all-channels-card)');
            var $header = $('#selected-account-header');
            var $tabs = $('#posts-status-tabs');
            var $avatarWrap = $('#selected-account-avatar-wrap');
            var $allchIcon = $('#selected-account-allch-icon');
            var $headerBtns = $('.selected-account-header-buttons');

            if ($allCh.hasClass('active')) {
                $avatarWrap.hide();
                $allchIcon.show();
                $('#selected-account-header-name').text('All Channels');
                $headerBtns.hide();
            } else if ($realActive.length === 1) {
                var $first = $realActive.first();
                var src = $first.find('.account-avatar img').attr('src') || '';
                var name = $first.find('.account-name').text().trim() || 'Account';
                var type = ($first.data('type') || 'facebook').toLowerCase();
                $allchIcon.hide();
                $avatarWrap.show();
                $('#selected-account-header-img').attr('src', src).attr('alt', name);
                $('#selected-account-header-name').text(name);
                var $badge = $('#selected-account-header-badge').removeClass('facebook pinterest tiktok').addClass(type);
                var headerIconClass = 'fab fa-facebook-f';
                if (type === 'pinterest') headerIconClass = 'fab fa-pinterest-p';
                else if (type === 'tiktok') headerIconClass = 'fab fa-tiktok';
                $badge.find('i').attr('class', headerIconClass);
                $headerBtns.show();
            } else if ($realActive.length > 1) {
                var $first = $realActive.first();
                var src = $first.find('.account-avatar img').attr('src') || '';
                $allchIcon.hide();
                $avatarWrap.show();
                $('#selected-account-header-img').attr('src', src);
                $('#selected-account-header-name').text($realActive.length + ' Accounts');
                $headerBtns.hide();
            } else {
                $header.hide();
                $tabs.hide();
                $('#posts-search-wrap').hide();
                $('#postsSearchInput').val('');
                postsSearchQuery = '';
                $('#postsSearchClear').hide();
                return;
            }

            $header.show();
            $tabs.show();
            $('#posts-search-wrap').show();
            cachedSentPagePosts = null;
            loadPostsStatusCounts();
            if (currentPostStatusTab === 'queue') {
                $('#queue-timeslots-section').show();
                $('#postsGrid').hide();
                loadQueueTimeslotsSection(true);
            } else if (currentPostStatusTab === 'sent') {
                $('#queue-timeslots-section').hide();
                $('#postsGrid').show();
                refreshSentTabView();
            }
        }

        /** Facebook-only: Sent tab uses Page/Graph timeline + insights. Pinterest (or mixed) uses posts listing. */
        function shouldUseFacebookSentPageTimeline() {
            var selected = getSelectedAccounts();
            if (selected.accountIds.length === 0) return false;
            for (var i = 0; i < selected.accountTypes.length; i++) {
                if (selected.accountTypes[i] !== 'facebook') return false;
            }
            return true;
        }

        /** Selected accounts are all Pinterest boards (used for sent-tab full DB load). */
        function isPinterestOnlySelection() {
            var selected = getSelectedAccounts();
            if (selected.accountIds.length === 0) return false;
            for (var i = 0; i < selected.accountTypes.length; i++) {
                if (selected.accountTypes[i] !== 'pinterest') return false;
            }
            return true;
        }

        function refreshSentTabView() {
            if (shouldUseFacebookSentPageTimeline()) {
                showSentPosts();
            } else {
                loadPosts(1);
            }
        }

        var currentPostStatusTab = 'queue';
        var cachedSentPagePosts = null;
        var postsSearchQuery = '';
        var postsStatusRequest = null;
        var sentPostsRequest = null;
        var queueSectionRequest = null;
        var publishSentRefreshTimer = null;

        /** After async Facebook publish job completes, Sent tab can show the post from DB; poll briefly so it appears without a manual refresh. */
        function scheduleSentTabRefreshAfterPublish() {
            if (publishSentRefreshTimer) {
                clearInterval(publishSentRefreshTimer);
                publishSentRefreshTimer = null;
            }
            var attempts = 0;
            var maxAttempts = 20;
            publishSentRefreshTimer = setInterval(function() {
                attempts++;
                var selected = getSelectedAccounts();
                if (selected.accountIds.length) {
                    loadPostsStatusCounts();
                    if (currentPostStatusTab === 'queue' && typeof loadQueueTimeslotsSection === 'function') {
                        loadQueueTimeslotsSection();
                    }
                }
                if (attempts >= maxAttempts) {
                    clearInterval(publishSentRefreshTimer);
                    publishSentRefreshTimer = null;
                }
            }, 2000);
        }

        function parseCreatedTime(ct) {
            if (!ct) return null;
            if (typeof ct === 'string') return new Date(ct);
            if (typeof ct === 'object' && ct.date) return new Date(ct.date.replace(' ', 'T') + 'Z');
            return null;
        }

        function loadPostsStatusCounts() {
            var selectedAccounts = getSelectedAccounts();
            if (selectedAccounts.accountIds.length === 0) return;
            if (postsStatusRequest && postsStatusRequest.readyState !== 4) {
                postsStatusRequest.abort();
            }
            postsStatusRequest = $.ajax({
                url: "{{ route('panel.schedule.posts.status.counts') }}",
                type: "GET",
                data: {
                    account_id: selectedAccounts.accountIds,
                    type: selectedAccounts.accountTypes
                },
                success: function(data) {
                    $('#posts-status-tabs [data-count="queue"]').text(data.queue);
                    // Sent count for Facebook timeline comes from sent-page-posts API (posts.length), not DB counts.
                    if (!shouldUseFacebookSentPageTimeline()) {
                        $('#posts-status-tabs [data-count="sent"]').text(data.sent);
                    }
                },
                complete: function() {
                    postsStatusRequest = null;
                }
            });
            if (shouldUseFacebookSentPageTimeline()) {
                loadSentPagePostsCached(selectedAccounts);
            } else {
                cachedSentPagePosts = null;
                if (currentPostStatusTab === 'sent') {
                    loadPosts(1);
                }
            }
        }

        function loadSentPagePostsCached(selectedAccounts) {
            if (!shouldUseFacebookSentPageTimeline()) {
                cachedSentPagePosts = null;
                return;
            }
            cachedSentPagePosts = null;
            if (sentPostsRequest && sentPostsRequest.readyState !== 4) {
                sentPostsRequest.abort();
            }
            sentPostsRequest = $.ajax({
                url: "{{ route('panel.schedule.posts.sent.page') }}",
                type: "GET",
                data: { account_id: selectedAccounts.accountIds },
                success: function(response) {
                    var posts = (response.success && response.posts) ? response.posts : [];
                    cachedSentPagePosts = posts;
                    $('#posts-status-tabs [data-count="sent"]').text(posts.length);
                    if (currentPostStatusTab === 'sent') {
                        showSentPosts();
                    }
                },
                error: function(xhr, textStatus) {
                    if (textStatus === 'abort') return;
                    cachedSentPagePosts = [];
                    $('#posts-status-tabs [data-count="sent"]').text(0);
                    if (currentPostStatusTab === 'sent') {
                        showSentPosts();
                    }
                },
                complete: function() {
                    sentPostsRequest = null;
                }
            });
        }

        var sentPostsGroupedByDay = [];
        var sentDayOffset = 0;
        var sentLoadingMore = false;
        var sentDaysBatchSize = 7;

        function getSentPostsSkeletonHtml() {
            var html = '<div class="sent-posts-skeleton">';
            var statsSkeleton = canAccessAnalytics ? '<div class="sent-card-stats"><div class="skeleton-stat animate-pulse-slow"></div><div class="skeleton-stat animate-pulse-slow"></div><div class="skeleton-stat animate-pulse-slow"></div></div>' : '';
            for (var i = 0; i < 3; i++) {
                html += '<div class="sent-post-row sent-skeleton-row">' +
                    '<div class="sent-post-time-col"><div class="skeleton-line skeleton-time animate-pulse-slow"></div><div class="skeleton-line skeleton-type animate-pulse-slow"></div></div>' +
                    '<div class="sent-post-card-col"><div class="sent-card sent-skeleton-card">' +
                    '<div class="sent-card-body"><div class="sent-card-content">' +
                    '<div class="sent-card-account"><div class="skeleton-avatar animate-pulse-slow"></div><div class="skeleton-line skeleton-name animate-pulse-slow"></div></div>' +
                    '<div class="skeleton-line skeleton-title animate-pulse-slow"></div><div class="skeleton-line skeleton-title-short animate-pulse-slow"></div>' +
                    '</div><div class="skeleton-image animate-pulse-slow"></div></div>' +
                    statsSkeleton +
                    '</div></div></div>';
            }
            return html + '</div>';
        }

        function filterSentPostsByTitle(posts, query) {
            if (!query || !query.trim()) return posts;
            var q = query.trim().toLowerCase();
            return posts.filter(function(post) {
                var title = (post.message || post.story || post.title || '').toString().toLowerCase();
                return title.indexOf(q) !== -1;
            });
        }

        function showSentPosts() {
            if (cachedSentPagePosts === null) {
                $('#postsGrid').html(getSentPostsSkeletonHtml());
                return;
            }
            var postsToShow = filterSentPostsByTitle(cachedSentPagePosts, postsSearchQuery);
            if (postsToShow.length === 0) {
                sentPostsGroupedByDay = [];
                $('#postsGrid').html('<div class="empty-state-box"><i class="far fa-folder-open"></i><p>' + (postsSearchQuery ? 'No sent posts match your search.' : 'No sent posts found.') + '</p></div>');
                return;
            }
            sentPostsGroupedByDay = buildSentPostsGroupedByDay(postsToShow);
            sentDayOffset = 0;
            if (sentPostsGroupedByDay.length === 0) {
                $('#postsGrid').html('<div class="empty-state-box"><i class="far fa-folder-open"></i><p>No sent posts found.</p></div>');
                return;
            }
            renderSentPagePosts(0, Math.min(sentDaysBatchSize, sentPostsGroupedByDay.length));
        }

        function buildSentPostsGroupedByDay(posts) {
            var now = new Date();
            var todayParts = getDatePartsInUserTimezone(now);
            var yesterdayDate = new Date(now.getTime() - 24 * 60 * 60 * 1000);
            var yesterdayParts = getDatePartsInUserTimezone(yesterdayDate);
            var dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            var monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
            var grouped = {};
            posts.forEach(function(post) {
                var d = parseCreatedTime(post.created_time);
                if (!d || isNaN(d.getTime())) return;
                var parts = getDatePartsInUserTimezone(d);
                if (!parts) return;
                var dayPart = '';
                var dateSuffix = parts.month + ' ' + parts.day;
                if (parts.year === todayParts.year && parts.month === todayParts.month && parts.day === todayParts.day) {
                    dayPart = 'Today';
                } else if (parts.year === yesterdayParts.year && parts.month === yesterdayParts.month && parts.day === yesterdayParts.day) {
                    dayPart = 'Yesterday';
                } else {
                    dayPart = parts.weekday;
                }
                var label = dayPart + '|' + dateSuffix;
                var dateKey = d.getTime();
                if (!grouped[label]) grouped[label] = { dateKey: dateKey, posts: [] };
                grouped[label].posts.push(post);
            });
            return Object.keys(grouped)
                .map(function(k) { return { label: k, dateKey: grouped[k].dateKey, posts: grouped[k].posts }; })
                .sort(function(a, b) { return b.dateKey - a.dateKey; });
        }

        function loadMoreSentDays() {
            if (sentLoadingMore || sentDayOffset >= sentPostsGroupedByDay.length) return;
            sentLoadingMore = true;
            var start = sentDayOffset;
            var count = Math.min(sentDaysBatchSize, sentPostsGroupedByDay.length - sentDayOffset);
            appendSentPagePosts(start, count);
            sentDayOffset += count;
            sentLoadingMore = false;
        }

        // Queue tab: load and render timeslots section with post cards
        var queueTimelineData = [];
        var queueDayOffset = 0;
        var queueLoadingMore = false;
        var queueBatchSize = 7;
        var queueHasMore = true;
        var queueLoadRequestId = 0;
        var queueSectionLoading = false;
        var queueSectionDebounceTimer = null;
        var QUEUE_SECTION_DEBOUNCE_MS = 250;
        var socialLogos = {
            facebook: "{{ social_logo('facebook') }}",
            pinterest: "{{ social_logo('pinterest') }}",
            tiktok: "{{ social_logo('tiktok') }}"
        };

        function getSocialIconClass(socialType) {
            if (!socialType) return 'fab fa-facebook-f';
            var t = (socialType || '').toLowerCase();
            if (t.indexOf('pinterest') !== -1) return 'fab fa-pinterest';
            if (t.indexOf('tiktok') !== -1) return 'fab fa-tiktok';
            return 'fab fa-facebook-f';
        }

        var queuePostTextLimit = 120;

        function renderQueuePostCard(slot, post) {
            var isLinkPost = post.type === 'link';
            var title = (post.title || '').trim();
            var imgSrc = post.image || '';
            var profileSrc = post.account_profile || socialLogos[post.social_type] || socialLogos.facebook;
            var accountName = post.account_name || 'Account';
            var iconClass = getSocialIconClass(post.social_type);
            var createdAgo = post.created_at || '';
            var postId = post.id;
            var linkTitle = post.title || post.url || '';
            var linkUrl = post.url || '';
            var linkDesc = (post.description || post.title || '').trim();

            var cardHtml = '<div class="queue-post-card' + (isLinkPost ? ' queue-post-card-link' : '') + '" data-post-id="' + postId + '">';
            cardHtml += '<div class="queue-post-card-inner">';
            cardHtml += '<div class="queue-post-card-header">';
            cardHtml += '<div class="queue-post-account">';
            cardHtml += '<div class="queue-post-avatar-wrap"><img src="' + profileSrc + '" class="queue-post-avatar" loading="lazy" onerror="this.onerror=null;this.src=\'' + socialLogos[post.social_type] + '\';">';
            cardHtml += '<span class="queue-post-platform-badge ' + (post.social_type || 'facebook').toLowerCase().replace(/ /g, '-') + '"><i class="' + iconClass + '"></i></span></div>';
            cardHtml += '<span class="queue-post-account-name">' + escapeHtml(accountName) + '</span>';
            cardHtml += '</div>';
            cardHtml += '<button type="button" class="queue-post-chat-btn" data-post-id="' + postId + '" data-comment="' + escapeHtml(post.comment || '') + '" aria-label="Comments"><i class="far fa-comment"></i></button>';
            cardHtml += '</div>';
            cardHtml += '<div class="queue-post-card-body">';
            if (isLinkPost) {
                cardHtml += '<div class="queue-link-preview' + (imgSrc ? '' : ' queue-link-preview-no-thumb') + '">';
                if (imgSrc) {
                    cardHtml += '<div class="queue-link-thumbnail"><img src="' + imgSrc + '" alt="" loading="lazy" onerror="this.style.display=\'none\'"></div>';
                }
                cardHtml += '<div class="queue-link-content">';
                if (linkTitle) {
                    var titleTrunc = linkTitle.length > queuePostTextLimit ? linkTitle.substring(0, queuePostTextLimit) + '...' : linkTitle;
                    var titleFull = escapeHtml(linkTitle);
                    var titleShort = escapeHtml(titleTrunc);
                    if (linkTitle.length > queuePostTextLimit) {
                        cardHtml += '<div class="queue-link-title queue-text-expandable-wrap"><span class="queue-text-short">' + titleShort + '</span><span class="queue-text-full" style="display:none">' + titleFull + '</span></div>';
                        cardHtml += '<button type="button" class="queue-post-see-more-btn">See more</button>';
                    } else {
                        cardHtml += '<div class="queue-link-title">' + titleFull + '</div>';
                    }
                }
                if (linkUrl) cardHtml += '<div class="queue-link-url"><a href="' + escapeHtml(linkUrl) + '" target="_blank" rel="noopener">' + escapeHtml(linkUrl) + '</a></div>';
                if (linkDesc) {
                    var descTrunc = linkDesc.length > queuePostTextLimit ? linkDesc.substring(0, queuePostTextLimit) + '...' : linkDesc;
                    var descFull = escapeHtml(linkDesc);
                    var descShort = escapeHtml(descTrunc);
                    if (linkDesc.length > queuePostTextLimit) {
                        cardHtml += '<div class="queue-link-desc queue-text-expandable-wrap"><span class="queue-text-short">' + descShort + '</span><span class="queue-text-full" style="display:none">' + descFull + '</span></div>';
                        cardHtml += '<button type="button" class="queue-post-see-more-btn">See more</button>';
                    } else {
                        cardHtml += '<div class="queue-link-desc">' + descFull + '</div>';
                    }
                }
                cardHtml += '</div></div>';
            } else {
                if (title) {
                    var textTrunc = title.length > queuePostTextLimit ? title.substring(0, queuePostTextLimit) + '...' : title;
                    var textFull = escapeHtml(title);
                    var textShort = escapeHtml(textTrunc);
                    if (title.length > queuePostTextLimit) {
                        cardHtml += '<div class="queue-post-text-wrap"><div class="queue-post-text queue-text-expandable-wrap"><span class="queue-text-short">' + textShort + '</span><span class="queue-text-full" style="display:none">' + textFull + '</span></div>';
                        cardHtml += '<button type="button" class="queue-post-see-more-btn">See more</button></div>';
                    } else {
                        cardHtml += '<div class="queue-post-text">' + textFull + '</div>';
                    }
                }
                if (imgSrc) {
                    cardHtml += '<div class="queue-post-image-wrap"><img src="' + imgSrc + '" alt="" class="queue-post-image" loading="lazy" onerror="this.style.display=\'none\'"></div>';
                }
            }
            cardHtml += '</div>';
            cardHtml += '<div class="queue-post-card-footer">';
            cardHtml += '<span class="queue-post-created">' + (createdAgo ? 'You created this ' + createdAgo : '') + '</span>';
            cardHtml += '<div class="queue-post-actions">';
            cardHtml += '<button type="button" class="queue-post-publish-now-btn btn-publish-now" data-id="' + postId + '"><i class="fas fa-paper-plane"></i> Publish Now</button>';
            cardHtml += '<button type="button" class="queue-post-edit-btn" data-id="' + postId + '" aria-label="Edit"><i class="fas fa-pencil-alt"></i></button>';
            cardHtml += '<button type="button" class="queue-post-more-btn" data-id="' + postId + '" aria-label="More options"><i class="fas fa-ellipsis-v"></i></button>';
            cardHtml += '</div>';
            cardHtml += '</div>';
            cardHtml += '</div></div>';
            return cardHtml;
        }

        function escapeHtml(s) {
            if (!s) return '';
            var div = document.createElement('div');
            div.textContent = s;
            return div.innerHTML;
        }

        function formatQueueForDisplay(dateStr, timeDisplay) {
            if (!dateStr || !timeDisplay) return '';
            var parts = dateStr.split('-');
            if (parts.length !== 3) return (dateStr || '') + ' ' + (timeDisplay || '');
            var day = parseInt(parts[2], 10);
            var ord = (day === 1 || day === 21 || day === 31) ? 'st' : (day === 2 || day === 22) ? 'nd' : (day === 3 || day === 23) ? 'rd' : 'th';
            var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            var month = months[parseInt(parts[1], 10) - 1] || '';
            return day + ord + ' ' + month + ', ' + timeDisplay;
        }

        function renderQueueSlotRow(slot, day) {
            var posts = slot.posts || (slot.post ? [slot.post] : []);
            var postsHtml = '';
            if (posts.length > 0) {
                posts.forEach(function(post) {
                    postsHtml += renderQueuePostCard(slot, post);
                });
                return '<div class="queue-timeslots-row queue-timeslots-row-has-post">' +
                    '<div class="queue-timeslots-time-col">' +
                    '<span class="queue-timeslots-time">' + slot.time_display + '</span>' +
                    '</div>' +
                    '<div class="queue-timeslots-post-col queue-timeslots-posts-block">' + postsHtml + '</div>' +
                    '</div>';
            }
            var slotDate = day.date || '';
            var slotTime = slot.time || '';
            var slotDisplay = (day.label || '') + ', ' + (day.date_display || '') + ' ' + (slot.time_display || '');
            var slotDisplayFooter = formatQueueForDisplay(slotDate, slot.time_display || '');
            return '<div class="queue-timeslots-row">' +
                '<div class="queue-timeslots-time-col">' +
                '<span class="queue-timeslots-time">' + slot.time_display + '</span>' +
                '</div>' +
                '<div class="queue-timeslots-post-col"><button type="button" class="queue-timeslots-new-btn" data-slot-date="' + slotDate + '" data-slot-time="' + slotTime + '" data-slot-display="' + escapeHtml(slotDisplay) + '" data-slot-display-footer="' + escapeHtml(slotDisplayFooter) + '">+ New</button></div>' +
                '</div>';
        }

        function filterQueueTimelineByTitle(timeline, query) {
            if (!query || !query.trim()) return timeline;
            var q = query.trim().toLowerCase();
            return timeline.map(function(day) {
                var filteredSlots = day.slots.map(function(slot) {
                    var posts = slot.posts || (slot.post ? [slot.post] : []);
                    var matchingPosts = posts.filter(function(post) {
                        var title = (post.title || post.description || post.url || '').toString().toLowerCase();
                        return title.indexOf(q) !== -1;
                    });
                    if (matchingPosts.length === 0) return null;
                    return { time: slot.time, time_display: slot.time_display, datetime_utc: slot.datetime_utc, has_post: true, posts: matchingPosts };
                }).filter(Boolean);
                if (filteredSlots.length === 0) return null;
                return { date: day.date, label: day.label, date_display: day.date_display, slots: filteredSlots };
            }).filter(Boolean);
        }

        function renderQueueTimeline(timeline) {
            var html = '';
            timeline.forEach(function(day) {
                html += '<div class="queue-timeslots-day-group"><h3 class="queue-timeslots-day-header">' + day.label + ', ' + day.date_display + '</h3>';
                day.slots.forEach(function(slot) {
                    html += renderQueueSlotRow(slot, day);
                });
                html += '</div>';
            });
            return html;
        }

        function getQueueSkeletonHtml() {
            var html = '<div class="queue-skeleton">';
            html += '<div class="queue-timeslots-day-group"><h3 class="queue-timeslots-day-header"><div class="skeleton-line skeleton-day-header animate-pulse-slow"></div></h3>';
            for (var i = 0; i < 3; i++) {
                html += '<div class="queue-timeslots-row queue-skeleton-row">' +
                    '<div class="queue-timeslots-time-col"><div class="skeleton-line skeleton-time animate-pulse-slow"></div><div class="skeleton-line skeleton-type animate-pulse-slow"></div></div>' +
                    '<div class="queue-timeslots-post-col"><div class="queue-post-card queue-skeleton-card">' +
                    '<div class="queue-post-card-inner">' +
                    '<div class="queue-post-card-header"><div class="queue-post-account"><div class="skeleton-avatar animate-pulse-slow"></div><div class="skeleton-line skeleton-name animate-pulse-slow"></div></div><div class="skeleton-icon-btn animate-pulse-slow"></div></div>' +
                    '<div class="queue-post-card-body"><div class="queue-skeleton-body-text"><div class="skeleton-line skeleton-text animate-pulse-slow"></div><div class="skeleton-line skeleton-text-short animate-pulse-slow"></div></div><div class="skeleton-image-sm animate-pulse-slow"></div></div>' +
                    '<div class="queue-post-card-footer"><div class="skeleton-line skeleton-created animate-pulse-slow"></div><div class="skeleton-actions"><div class="skeleton-btn animate-pulse-slow"></div><div class="skeleton-circle animate-pulse-slow"></div><div class="skeleton-circle animate-pulse-slow"></div></div></div>' +
                    '</div></div></div></div>';
            }
            return html + '</div></div>';
        }

        function loadQueueTimeslotsSection(immediate) {
            if (queueSectionDebounceTimer) {
                clearTimeout(queueSectionDebounceTimer);
                queueSectionDebounceTimer = null;
            }
            if (immediate) {
                loadQueueTimeslotsSectionNow();
                return;
            }
            queueSectionDebounceTimer = setTimeout(function() {
                queueSectionDebounceTimer = null;
                loadQueueTimeslotsSectionNow();
            }, QUEUE_SECTION_DEBOUNCE_MS);
        }

        function loadQueueTimeslotsSectionNow() {
            if (queueSectionLoading) return;
            var selectedAccounts = getSelectedAccounts();
            var $content = $('#queue-timeslots-content');
            var $empty = $('#queue-timeslots-empty');
            queueDayOffset = 0;
            queueTimelineData = [];
            queueHasMore = true;
            var requestId = ++queueLoadRequestId;
            if (selectedAccounts.accountIds.length === 0) {
                $content.empty().hide();
                $empty.find('.queue-timeslots-empty-text').text('No queued posts found.');
                $empty.show();
                return;
            }
            queueSectionLoading = true;
            var accountId = selectedAccounts.accountIds[0];
            var accountType = selectedAccounts.accountTypes[0];
            $content.html(getQueueSkeletonHtml()).show();
            $empty.hide();
            if (queueSectionRequest && queueSectionRequest.readyState !== 4) {
                queueSectionRequest.abort();
            }
            queueSectionRequest = $.ajax({
                url: "{{ route('panel.schedule.queue.timeline') }}",
                type: "GET",
                data: { account_id: accountId, type: accountType, days: queueBatchSize, offset: 0, source: 'schedule' },
                success: function(data) {
                    queueSectionLoading = false;
                    if (requestId !== queueLoadRequestId) return;
                    if (!data.success || !data.timeline || data.timeline.length === 0) {
                        $content.empty().hide();
                        $empty.find('.queue-timeslots-empty-text').text(data.message || 'No queued posts found.');
                        $empty.show();
                        return;
                    }
                    queueTimelineData = data.timeline;
                    queueDayOffset = (data.next_offset !== undefined) ? data.next_offset : data.timeline.length;
                    queueHasMore = data.has_more !== false;
                    var toRender = filterQueueTimelineByTitle(data.timeline, postsSearchQuery);
                    $content.empty();
                    if (toRender.length === 0 && postsSearchQuery) {
                        $content.hide();
                        $empty.find('.queue-timeslots-empty-text').text('No posts match your search.');
                        $empty.show();
                    } else {
                        $content.html(renderQueueTimeline(toRender)).show();
                        $empty.hide();
                        bindQueuePostActions();
                    }
                },
                error: function(xhr, textStatus) {
                    if (textStatus === 'abort') {
                        queueSectionLoading = false;
                        return;
                    }
                    queueSectionLoading = false;
                    if (requestId !== queueLoadRequestId) return;
                    $content.empty().hide();
                    $empty.find('.queue-timeslots-empty-text').text('Failed to load queue.');
                    $empty.show();
                },
                complete: function() {
                    queueSectionRequest = null;
                }
            });
        }

        function loadMoreQueueTimeline() {
            if (queueLoadingMore || !queueHasMore) return;
            if (queueTimelineData.length <= 1) return;
            var selectedAccounts = getSelectedAccounts();
            if (selectedAccounts.accountIds.length === 0) return;
            queueLoadingMore = true;
            var accountId = selectedAccounts.accountIds[0];
            var accountType = selectedAccounts.accountTypes[0];
            var currentOffset = queueDayOffset;
            $.ajax({
                url: "{{ route('panel.schedule.queue.timeline') }}",
                type: "GET",
                data: { account_id: accountId, type: accountType, days: queueBatchSize, offset: currentOffset, source: 'schedule' },
                success: function(data) {
                    queueLoadingMore = false;
                    if (data.success && data.timeline && data.timeline.length > 0) {
                        queueTimelineData = queueTimelineData.concat(data.timeline);
                        queueDayOffset = (data.next_offset !== undefined) ? data.next_offset : (currentOffset + data.timeline.length);
                        queueHasMore = (data.has_more !== false) && (data.timeline.length >= queueBatchSize);
                        var toAppend = filterQueueTimelineByTitle(data.timeline, postsSearchQuery);
                        if (toAppend.length > 0) {
                            $('#queue-timeslots-content').append(renderQueueTimeline(toAppend));
                            bindQueuePostActions();
                        }
                    } else {
                        queueHasMore = false;
                    }
                },
                error: function() {
                    queueLoadingMore = false;
                }
            });
        }

        function editPost(id) {
            var modal = $('.edit-post-modal');
            modal.find(".modal-body").empty();
            modal.modal("show");
            $.ajax({
                url: "{{ route('panel.schedule.post.edit') }}",
                type: "GET",
                data: { id: id },
                success: function(response) {
                    if (response.success) {
                        modal.find("#update-post-form").attr("action", response.action);
                        modal.find(".modal-body").html(response.data);
                    } else {
                        toastr.error(response.message || 'Failed to load post');
                    }
                },
                error: function() {
                    toastr.error('Failed to load post');
                }
            });
        }

        function publishNowPost(id) {
            if (!confirm("Do you wish to Publish this Post Now?")) return;
            $.ajax({
                url: "{{ route('panel.schedule.post.publish.now') }}",
                type: "POST",
                data: { id: id, _token: "{{ csrf_token() }}" },
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        if (currentPostStatusTab === 'queue' && typeof loadQueueTimeslotsSection === 'function') {
                            loadQueueTimeslotsSection();
                        }
                        loadPostsStatusCounts();
                        scheduleSentTabRefreshAfterPublish();
                    } else {
                        toastr.error(response.message || 'Failed to publish');
                    }
                },
                error: function() {
                    toastr.error('Failed to publish');
                }
            });
        }

        function togglePostMoreMenu($card, id) {
            var $menu = $card.find('.queue-post-more-menu');
            var wasOpen = $menu.length && $menu.is(':visible');
            $('.queue-post-more-menu').remove();
            if (wasOpen) return;
            var menuHtml = '<div class="queue-post-more-menu"><button type="button" class="queue-post-delete-btn" data-id="' + id + '"><i class="fas fa-trash mr-1"></i> Delete</button></div>';
            var $menuEl = $(menuHtml);
            $card.find('.queue-post-actions').append($menuEl);
            $menuEl.on('click', function(e) {
                if ($(e.target).closest('.queue-post-delete-btn').length) return;
                e.stopPropagation();
            });
            $(document).one('click', function() {
                $('.queue-post-more-menu').remove();
            });
        }

        $(document).on('click', '.queue-post-delete-btn', function(e) {
            e.stopPropagation();
            var id = $(this).data('id');
            if (!id || !confirm("Published post will be delete from Account! Do you wish to Delete this Post?")) return;
            $.ajax({
                url: "{{ route('panel.schedule.post.delete') }}",
                type: "GET",
                data: { id: id },
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        if (currentPostStatusTab === 'queue' && typeof loadQueueTimeslotsSection === 'function') {
                            loadQueueTimeslotsSection();
                        }
                        loadPostsStatusCounts();
                    } else {
                        toastr.error(response.message || 'Failed to delete');
                    }
                }
            });
        });

        function bindQueuePostActions() {
            $(document).off('click.queuePost', '.queue-post-publish-now-btn');
            $(document).off('click.queuePost', '.queue-post-edit-btn');
            $(document).off('click.queuePost', '.queue-post-more-btn');
            $(document).on('click.queuePost', '.queue-post-publish-now-btn', function() {
                var id = $(this).data('id');
                if (id) publishNowPost(id);
            });
            $(document).on('click.queuePost', '.queue-post-edit-btn', function() {
                var id = $(this).data('id');
                if (id) editPost(id);
            });
            $(document).on('click.queuePost', '.queue-post-more-btn', function(e) {
                e.stopPropagation();
                var id = $(this).data('id');
                if (id) {
                    var $card = $(this).closest('.queue-post-card');
                    togglePostMoreMenu($card, id);
                }
            });
        }

        $(document).on('click', '.queue-post-see-more-btn', function() {
            var $btn = $(this);
            var $wrap = $btn.prev('.queue-text-expandable-wrap');
            if (!$wrap.length) return;
            var $short = $wrap.find('.queue-text-short');
            var $full = $wrap.find('.queue-text-full');
            if ($btn.text() === 'See more') {
                $short.hide();
                $full.show();
                $btn.text('See less');
            } else {
                $short.show();
                $full.hide();
                $btn.text('See more');
            }
        });

        // Post comment modal: open on chat button click
        var currentCommentPostId = null;
        $(document).on('click', '.queue-post-chat-btn', function() {
            var postId = $(this).data('post-id');
            var comment = $(this).attr('data-comment') || '';
            if (!postId) return;
            currentCommentPostId = postId;
            $('#postCommentInput').val(comment);
            $('#postCommentModal').modal('show');
            setTimeout(function() { $('#postCommentInput').focus(); }, 300);
        });

        // Save comment
        $('#postCommentSaveBtn').on('click', function() {
            if (!currentCommentPostId) return;
            var comment = $('#postCommentInput').val().trim();
            var $btn = $(this);
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Saving...');
            $.ajax({
                url: "{{ route('panel.schedule.post.update.comment', ['id' => 0]) }}".replace(/\/0$/, '/' + currentCommentPostId),
                type: "POST",
                data: { comment: comment, _token: "{{ csrf_token() }}" },
                success: function(response) {
                    $btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i> Save Comment');
                    if (response.success) {
                        $('#postCommentModal').modal('hide');
                        toastr.success(response.message);
                        $('.queue-post-chat-btn[data-post-id="' + currentCommentPostId + '"]').attr('data-comment', comment);
                        if (currentPostStatusTab === 'queue' && typeof loadQueueTimeslotsSection === 'function') {
                            loadQueueTimeslotsSection();
                        }
                    } else {
                        toastr.error(response.message || 'Failed to save comment');
                    }
                },
                error: function() {
                    $btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i> Save Comment');
                    toastr.error('Failed to save comment');
                }
            });
        });

        $('#queue-timeslots-section').on('scroll', function() {
            var el = this;
            var threshold = Math.max(400, el.clientHeight * 1.5);
            if (el.scrollTop + el.clientHeight >= el.scrollHeight - threshold) {
                loadMoreQueueTimeline();
            }
        });

        $('#postsGrid').on('scroll', function() {
            if (currentPostStatusTab !== 'sent' || sentPostsGroupedByDay.length === 0) return;
            var el = this;
            if (el.scrollTop + el.clientHeight >= el.scrollHeight - 100) {
                loadMoreSentDays();
            }
        });

        function applyPostsSearch() {
            if (currentPostStatusTab === 'queue') {
                var $content = $('#queue-timeslots-content');
                var $empty = $('#queue-timeslots-empty');
                if (queueTimelineData.length === 0 && !queueSectionLoading) return;
                var toRender = filterQueueTimelineByTitle(queueTimelineData, postsSearchQuery);
                if (toRender.length === 0 && postsSearchQuery) {
                    $content.hide();
                    $empty.find('.queue-timeslots-empty-text').text('No posts match your search.');
                    $empty.show();
                } else if (queueTimelineData.length > 0) {
                    $content.empty().html(renderQueueTimeline(toRender)).show();
                    $empty.hide();
                    bindQueuePostActions();
                }
            } else if (currentPostStatusTab === 'sent') {
                refreshSentTabView();
            }
        }

        // Posts search – click icon to expand; click icon again to collapse
        $(document).on('click', '.posts-search-icon', function(e) {
            e.preventDefault();
            var $wrap = $('#posts-search-wrap');
            if ($wrap.hasClass('is-expanded')) {
                $wrap.removeClass('is-expanded');
            } else {
                $wrap.addClass('is-expanded');
            }
        });

        $(document).on('input', '#postsSearchInput', function() {
            postsSearchQuery = $(this).val().trim();
            $('#postsSearchClear').toggle(postsSearchQuery.length > 0);
            applyPostsSearch();
        });

        $(document).on('click', '#postsSearchClear', function() {
            $('#postsSearchInput').val('');
            postsSearchQuery = '';
            $(this).hide();
            applyPostsSearch();
        });

        // Posts status tab click
        $(document).on('click', '.posts-status-tab', function() {
            var tab = $(this).data('tab');
            if (!tab) return;
            currentPostStatusTab = tab;
            $('#posts-status-tabs .posts-status-tab').removeClass('is-active').attr('aria-selected', 'false');
            $(this).addClass('is-active').attr('aria-selected', 'true');
            if (tab === 'queue') {
                $('#queue-timeslots-section').show();
                $('#postsGrid').hide();
                loadQueueTimeslotsSection(true);
            } else if (tab === 'sent') {
                $('#queue-timeslots-section').hide();
                $('#postsGrid').show();
                refreshSentTabView();
            }
        });

        $(document).on("click", ".all-channels-card", function() {
            var $allCh = $(this);
            if ($allCh.hasClass('active')) return;
            $('.account-card').removeClass('active');
            $allCh.addClass('active');
            $('.account-card:not(.all-channels-card)').addClass('active');
            updateSelectedAccountHeader();
            updateUrlFromAccountSelection();
            saveSelectedAccountToDb();
        });

        $(document).on("click", ".account-card:not(.all-channels-card)", function() {
            var $card = $(this);
            if ($card.hasClass('active') && $('.account-card.active:not(.all-channels-card)').length === 1) return;
            $('.account-card').removeClass('active');
            $card.addClass('active');
            updateSelectedAccountHeader();
            updateUrlFromAccountSelection();
            saveSelectedAccountToDb();
        });

        // Search icon click (collapsed state): expand sidebar and focus search
        $('#sidebarSearchIcon').on('click', function() {
            var $sidebar = $('#accounts-sidebar');
            if ($sidebar.hasClass('collapsed')) {
                $sidebar.removeClass('collapsed');
                setTimeout(function() { $('#accountSearchInput').focus(); }, 200);
            }
        });

        // Collapse button (expanded state): collapse sidebar
        $('#sidebarCollapseBtn').on('click', function() {
            var $sidebar = $('#accounts-sidebar');
            $sidebar.addClass('collapsed');
            $('#accountSearchInput').val('');
            $('#accountSearchClear').hide();
            $('.account-card').show();
            if ($(window).width() <= 1024) {
                $sidebar.removeClass('mobile-open');
                $('#sidebarBackdrop').removeClass('is-visible');
            }
        });

        // Mobile sidebar toggle: open sidebar overlay
        $('#sidebarMobileToggle').on('click', function() {
            var $sidebar = $('#accounts-sidebar');
            $sidebar.removeClass('collapsed').addClass('mobile-open');
            $('#sidebarBackdrop').addClass('is-visible');
            $('#accountSearchInput').val('');
            $('#accountSearchClear').hide();
            $('.account-card').show();
        });

        // Backdrop click: close mobile sidebar
        $('#sidebarBackdrop').on('click', function() {
            $('#accounts-sidebar').removeClass('mobile-open');
            $(this).removeClass('is-visible');
        });

        // Close mobile sidebar on account select (when viewport is mobile)
        $(document).on('click', '.account-card', function() {
            if ($(window).width() <= 1024) {
                $('#accounts-sidebar').removeClass('mobile-open');
                $('#sidebarBackdrop').removeClass('is-visible');
            }
        });

        // Close mobile sidebar when resizing to desktop
        $(window).on('resize', function() {
            if ($(window).width() > 1024) {
                $('#accounts-sidebar').removeClass('mobile-open');
                $('#sidebarBackdrop').removeClass('is-visible');
            }
        });

        // Account search input: filter cards by name
        $('#accountSearchInput').on('input', function() {
            var query = $(this).val().toLowerCase().trim();
            $('#accountSearchClear').toggle(query.length > 0);
            $('.account-card').each(function() {
                var name = $(this).find('.account-name').text().toLowerCase();
                var username = $(this).find('.account-username').text().toLowerCase();
                $(this).toggle(name.indexOf(query) !== -1 || username.indexOf(query) !== -1);
            });
        });

        // Clear search
        $('#accountSearchClear').on('click', function() {
            $('#accountSearchInput').val('').trigger('input').focus();
        });

        // Apply account selection: URL param takes precedence, else use saved selection from database
        if (applyAccountSelectionFromUrl()) {
            saveSelectedAccountToDb();
            updateSelectedAccountHeader();
        } else {
            $.get("{{ route('panel.schedule.selected-account') }}", function(res) {
                var appliedFromDb = false;
                if (res.success && res.data) {
                    appliedFromDb = applyAccountSelectionFromData(res.data);
                }
                // Fresh account case: no saved selection in DB -> select first connected account by default.
                if (!appliedFromDb) {
                    if (selectFirstConnectedAccount()) {
                        saveSelectedAccountToDb();
                    }
                }
                updateSelectedAccountHeader();
            }).fail(function() {
                updateSelectedAccountHeader();
            });
        }

        // Header List view button (active state only; calendar removed)
        $(document).on('click', '.selected-account-view-list', function() {
            $(this).addClass('is-active');
        });

        // New Post: open Create Post modal
        $(document).on('click', '.selected-account-new-post', function() {
            $('#createPostModal').modal('show');
        });

        // Queue + New button: open Create Post modal in queue-from-timeslot mode
        $(document).on('click', '.queue-timeslots-new-btn', function() {
            createPostFromTimeslot = true;
            createPostSlotDate = $(this).data('slot-date') || '';
            createPostSlotTime = $(this).data('slot-time') || '';
            createPostSlotDisplay = $(this).data('slot-display') || '';
            createPostSlotDisplayFooter = $(this).data('slot-display-footer') || '';
            $('#createPostModal').modal('show');
        });

        var lastUsedAccountId = null;
        var lastUsedAccountData = null;

        // Channels dropdown: toggle
        $(document).on('click', '#createPostChannelsBtn', function(e) {
            e.stopPropagation();
            var $dropdown = $('#createPostChannelsDropdown');
            $dropdown.toggleClass('is-open');
            if ($dropdown.hasClass('is-open')) {
                syncChannelsDropdownFromSidebar();
                $('#channelsDropdownSearch').val('').trigger('input');
            }
        });

        function syncChannelsDropdownFromSidebar() {
            $('.channels-dropdown-checkbox').each(function() {
                var scheduleStatus = $(this).attr('data-schedule-status');
                $(this).prop('checked', scheduleStatus === 'active');
            });
        }

        function updateCreatePostModalSelection() {
            renderSelectedChannels();
            var checkedCount = $('.channels-dropdown-checkbox:checked').length;
            if (checkedCount > 0) {
                var $firstChecked = $('.channels-dropdown-checkbox:checked').first();
                lastUsedAccountId = $firstChecked.data('id');
                $('#createPostEmptyState').hide();
                $('#createPostEditorWrap').show();
                $('#createPostMainContent').addClass('has-editor');
                var hasNonFacebook = false;
                $('.channels-dropdown-checkbox:checked').each(function() {
                    if ($(this).data('type') === 'pinterest' || $(this).data('type') === 'tiktok') {
                        hasNonFacebook = true;
                    }
                });
                if (hasNonFacebook) {
                    $('#createPostCommentWrap').hide();
                } else {
                    $('#createPostCommentWrap').show();
                }
                var onlyTiktok = true;
                $('.channels-dropdown-checkbox:checked').each(function() {
                    if ($(this).data('type') !== 'tiktok') {
                        onlyTiktok = false;
                    }
                });
                $('.create-post-draft-btn').toggle(!createPostFromTimeslot && onlyTiktok);
            } else {
                $('#createPostEmptyState').show();
                $('#createPostEditorWrap').hide();
                $('#createPostMainContent').removeClass('has-editor');
                $('#createPostCommentWrap').hide();
                $('.create-post-draft-btn').hide();
            }
            renderLastUsed();
            updateCreatePostFacebookFormatRow();
        }

        function createPostHasFacebookChannelSelected() {
            var found = false;
            $('.channels-dropdown-checkbox:checked').each(function() {
                if (($(this).data('type') || '') === 'facebook') {
                    found = true;
                    return false;
                }
            });
            return found;
        }

        function createPostHasVideoInQueue() {
            var vids = ['mp4', 'mkv', 'mov', 'mpeg', 'webm'];
            for (var i = 0; i < createPostFiles.length; i++) {
                var ext = (createPostFiles[i].name || '').split('.').pop().toLowerCase();
                if (vids.indexOf(ext) !== -1) return true;
            }
            return false;
        }

        function updateCreatePostFacebookFormatRow() {
            var show = createPostHasFacebookChannelSelected() && createPostHasVideoInQueue() && !is_link;
            var $wrap = $('#createPostFacebookFormatWrap');
            if (show) {
                $wrap.show();
            } else {
                $wrap.hide();
                $('#createPostFormatPost').prop('checked', true);
            }
        }

        function renderLastUsed() {
            var $container = $('#createPostLastUsed');
            $container.empty().hide();
            var checkedCount = $('.channels-dropdown-checkbox:checked').length;
            if (checkedCount > 0) return;
            var acc = lastUsedAccountData;
            if (!acc || !acc.id) return;
            var accType = acc.type || 'facebook';
            var $item = $('.channels-dropdown-checkbox[data-id="' + acc.id + '"][data-type="' + accType + '"]').closest('.channels-dropdown-item');
            if (!$item.length) return;
            var type = $item.data('type') || accType;
            var src = acc.profile_image || $item.find('.channels-dropdown-item-avatar img').attr('src') || '';
            var socialLogos = { facebook: "{{ social_logo('facebook') }}", pinterest: "{{ social_logo('pinterest') }}", tiktok: "{{ social_logo('tiktok') }}" };
            var iconMap = { facebook: 'fab fa-facebook-f', pinterest: 'fab fa-pinterest-p', tiktok: 'fab fa-tiktok' };
            var logo = socialLogos[type] || socialLogos.facebook;
            var icon = iconMap[type] || iconMap.facebook;
            var html = '<span class="create-post-last-used-label">Last used</span>' +
                '<span class="create-post-last-used-avatar-wrap">' +
                '<img src="' + src + '" alt="" onerror="this.onerror=null; this.src=\'' + logo + '\';">' +
                '<span class="create-post-last-used-badge ' + type + '"><i class="' + icon + '"></i></span>' +
                '</span>';
            $container.html(html).attr('data-id', acc.id).attr('data-type', accType).show();
        }

        function fetchLastUsedAccount() {
            $.get("{{ route('panel.schedule.last-used-account') }}", function(response) {
                if (response.success) {
                    lastUsedAccountData = response.account || null;
                    var accountsStatusList = response.accounts_status || [];
                    var statusMap = {};
                    if (Array.isArray(accountsStatusList)) {
                        accountsStatusList.forEach(function(item) {
                            var key = item.type + '_' + item.id;
                            statusMap[key] = item.schedule_status;
                        });
                    }
                    $('.channels-dropdown-checkbox').each(function() {
                        var id = $(this).data('id');
                        var type = $(this).data('type') || 'facebook';
                        var key = type + '_' + id;
                        var status = statusMap[key];
                        if (status !== undefined) {
                            $(this).attr('data-schedule-status', status).prop('checked', status === 'active');
                        }
                    });
                    updateCreatePostModalSelection();
                } else {
                    lastUsedAccountData = null;
                }
            }).fail(function() {
                lastUsedAccountData = null;
            });
        }

        function fetchAccountsWithStatus() {
            $.get("{{ route('panel.schedule.accounts-with-status') }}", function(response) {
                if (response.success) {
                    lastUsedAccountData = response.account || null;
                    var accountsStatusList = response.accounts_status || [];
                    var statusMap = {};
                    accountsStatusList.forEach(function(item) {
                        var key = item.type + '_' + item.id;
                        statusMap[key] = item.schedule_status;
                    });
                    var sidebarSelectedKeys = [];
                    if (createPostFromTimeslot) {
                        $('.account-card.active:not(.all-channels-card)').each(function() {
                            var id = $(this).data('id');
                            var type = $(this).data('type') || 'facebook';
                            if (id && type) sidebarSelectedKeys.push(type + '_' + id);
                        });
                    }
                    $('.channels-dropdown-checkbox').each(function() {
                        var id = $(this).data('id');
                        var type = $(this).data('type') || 'facebook';
                        var key = type + '_' + id;
                        var status = statusMap[key];
                        if (status !== undefined) {
                            $(this).attr('data-schedule-status', status);
                            if (createPostFromTimeslot && sidebarSelectedKeys.length > 0) {
                                $(this).prop('checked', sidebarSelectedKeys.indexOf(key) !== -1);
                            } else {
                                $(this).prop('checked', status === 'active');
                            }
                        }
                    });
                    updateCreatePostModalSelection();
                } else {
                    lastUsedAccountData = null;
                }
            }).fail(function() {
                lastUsedAccountData = null;
            });
        }

        $(document).on('click', '.create-post-selected-channel-chip-remove', function(e) {
            e.stopPropagation();
            var id = $(this).data('id');
            var type = $(this).closest('.create-post-selected-channel-chip').data('type') || 'facebook';
            var $cb = $('.channels-dropdown-checkbox[data-id="' + id + '"][data-type="' + type + '"]');
            if ($cb.length) {
                $cb.prop('checked', false);
                updateAccountScheduleStatus(id, false, true, type);
                updateCreatePostModalSelection();
            }
        });

        $(document).on('click', '#createPostLastUsed', function(e) {
            e.stopPropagation();
            var id = $(this).attr('data-id');
            var accType = $(this).attr('data-type') || 'facebook';
            if (!id) return;
            var $cb = $('.channels-dropdown-checkbox[data-id="' + id + '"][data-type="' + accType + '"]');
            if ($cb.length) {
                $cb.prop('checked', true);
                lastUsedAccountId = id;
                updateAccountScheduleStatus(id, true, false, accType);
                updateCreatePostModalSelection();
            }
        });

        function renderSelectedChannels() {
            var $container = $('#createPostSelectedChannels');
            $container.empty();
            var socialLogos = { facebook: "{{ social_logo('facebook') }}", pinterest: "{{ social_logo('pinterest') }}", tiktok: "{{ social_logo('tiktok') }}" };
            var iconMap = { facebook: 'fab fa-facebook-f', pinterest: 'fab fa-pinterest-p', tiktok: 'fab fa-tiktok' };
            $('.channels-dropdown-checkbox:checked').each(function() {
                var $item = $(this).closest('.channels-dropdown-item');
                var src = $item.find('.channels-dropdown-item-avatar img').attr('src') || '';
                var type = $item.data('type') || $(this).data('type') || 'facebook';
                var logo = socialLogos[type] || socialLogos.facebook;
                var icon = iconMap[type] || iconMap.facebook;
                var id = $(this).data('id');
                var tooltip = ($item.attr('data-tooltip') || $item.find('.channels-dropdown-item-name').text() || '').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
                var html = '<div class="create-post-selected-channel-chip has-tooltip" data-id="' + id + '" data-type="' + type + '" data-tooltip="' + tooltip + '">' +
                    '<span class="create-post-selected-channel-chip-remove" data-id="' + id + '" aria-label="Remove">' +
                    '<i class="fas fa-times"></i></span>' +
                    '<span class="create-post-selected-channel-chip-inner">' +
                    '<img src="' + src + '" alt="" onerror="this.onerror=null; this.src=\'' + logo + '\';">' +
                    '<span class="create-post-chip-badge ' + type + '"><i class="' + icon + '"></i></span>' +
                    '</span></div>';
                $container.append(html);
            });
        }

        function updateAccountScheduleStatus(accountId, isChecked, showToast, accountType) {
            showToast = showToast !== false;
            var type = accountType || 'facebook';
            var $cbForType = accountType
                ? $('.channels-dropdown-checkbox[data-id="' + accountId + '"][data-type="' + accountType + '"]')
                : $('.channels-dropdown-checkbox[data-id="' + accountId + '"]').first();
            if (!accountType && $cbForType.length) {
                type = $cbForType.data('type') || 'facebook';
            }
            $.ajax({
                url: "{{ route('panel.schedule.account.status') }}",
                type: "GET",
                data: {
                    type: type,
                    id: accountId,
                    status: isChecked ? 1 : 0
                },
                success: function(response) {
                    if (response.success) {
                        var status = isChecked ? 'active' : 'inactive';
                        var $targets = accountType
                            ? $('.channels-dropdown-checkbox[data-id="' + accountId + '"][data-type="' + accountType + '"]')
                            : $('.channels-dropdown-checkbox[data-id="' + accountId + '"]');
                        $targets.attr('data-schedule-status', status);
                    }
                    if (showToast) {
                        if (response.success) {
                            toastr.success(response.message);
                        } else {
                            toastr.error(response.message || "Failed to update account status");
                        }
                    }
                },
                error: function() {
                    if (showToast) toastr.error("Failed to update account status");
                }
            });
        }

        $(document).on('change', '.channels-dropdown-checkbox', function() {
            var id = $(this).data('id');
            var chType = $(this).data('type') || 'facebook';
            var isChecked = $(this).is(':checked');
            if (id) updateAccountScheduleStatus(id, isChecked, true, chType);
            if (isChecked) lastUsedAccountId = id;
            updateCreatePostModalSelection();
        });

        $(document).on('click', '#channelsDropdownDeselect', function(e) {
            e.preventDefault();
            var count = 0;
            $('.channels-dropdown-checkbox:checked').each(function() {
                var id = $(this).data('id');
                var type = $(this).data('type');
                if (id) {
                    updateAccountScheduleStatus(id, false, false, type);
                    count++;
                }
            });
            $('.channels-dropdown-checkbox').prop('checked', false);
            updateCreatePostModalSelection();
            if (count > 0) toastr.success("All accounts deselected");
        });

        $(document).on('input', '#channelsDropdownSearch', function() {
            var q = $(this).val().toLowerCase().trim();
            $('.channels-dropdown-item').each(function() {
                var name = $(this).data('name') || '';
                $(this).toggle(name.indexOf(q) !== -1);
            });
        });

        $(document).on('click', function(e) {
            if (!$(e.target).closest('.create-post-channels-dropdown-wrap').length) {
                $('#createPostChannelsDropdown').removeClass('is-open');
            }
        });

        $(document).on('click', '.channels-dropdown-item', function(e) {
            if ($(e.target).closest('.channels-dropdown-item-checkbox').length) return;
            var $cb = $(this).find('.channels-dropdown-checkbox');
            if ($cb.length) $cb.prop('checked', !$cb.prop('checked')).trigger('change');
        });

        $('#createPostModal').prop('inert', true);
        $('#createPostModal').on('show.bs.modal', function(e) {
            e.target.inert = false;
            fetchAccountsWithStatus();
            if (createPostFromTimeslot) {
                $('.create-post-segmented-btn[href="schedule"]').show();
                $('.create-post-segmented-btn[href="queue"]').show();
                $('.create-post-segmented-btn[href="publish"]').show();
                $('.create-post-draft-btn').hide();
            } else {
                $('.create-post-segmented-btn[href="schedule"]').show();
                $('.create-post-segmented-btn[href="queue"]').show();
                $('.create-post-segmented-btn[href="publish"]').show();
                updateCreatePostModalSelection();
            }
        });
        $('#createPostModal').on('hide.bs.modal', function(e) {
            e.target.inert = true;
            $('#createPostScheduleDropdown').removeClass('is-open');
        });
        $('#createPostModal').on('hidden.bs.modal', function() {
            createPostFromTimeslot = false;
            createPostSlotDate = '';
            createPostSlotTime = '';
            createPostSlotDisplay = '';
            createPostSlotDisplayFooter = '';
            $('.create-post-segmented-btn[href="schedule"]').show();
            $('.create-post-segmented-btn[href="queue"]').show();
            $('.create-post-segmented-btn[href="publish"]').show();
            $('#createPostChannelsDropdown').removeClass('is-open');
            $('#createPostEditorTextarea').val('').css('height', 'auto');
            $('#createPostComment').val('');
            $('#createPostFirstComment').val('');
            $('#createPostLinkPreview').empty();
            createPostFiles = [];
            createPostRevokeUrls();
            $('#createPostUploadPreviews').empty();
            is_link = 0;
        });

        $('#createPostModal').on('shown.bs.modal', function() {
            if (createPostFromTimeslot) {
                var $sidebarActive = $('.account-card.active:not(.all-channels-card)');
                if ($sidebarActive.length === 1) {
                    var accountId = $sidebarActive.first().data('id');
                    var accountType = $sidebarActive.first().data('type');
                    $('.channels-dropdown-checkbox').prop('checked', false);
                    $('.channels-dropdown-checkbox[data-id="' + accountId + '"][data-type="' + accountType + '"]').prop('checked', true);
                } else {
                    syncChannelsDropdownFromSidebar();
                }
            } else {
                syncChannelsDropdownFromSidebar();
            }
            renderSelectedChannels();
        });

        // Create post editor: file upload (drag & drop, click) - extensions from schedule dropZone
        var createPostFiles = [];
        var createPostObjectUrls = [];
        var createPostAllowedExtensions = ['jpg', 'jpeg', 'png', 'bmp', 'gif', 'tiff', 'webp', 'mp4', 'mkv', 'mov', 'mpeg', 'webm'];

        function createPostIsFileAllowed(file) {
            var ext = (file.name || '').split('.').pop().toLowerCase();
            return createPostAllowedExtensions.indexOf(ext) !== -1;
        }

        function createPostRevokeUrls() {
            createPostObjectUrls.forEach(function(url) { URL.revokeObjectURL(url); });
            createPostObjectUrls = [];
        }

        function createPostAddFiles(files) {
            var allowed = Array.from(files || []).filter(createPostIsFileAllowed);
            var rejected = files.length - allowed.length;
            if (allowed.length) {
                createPostFiles = createPostFiles.concat(allowed);
                renderCreatePostUploadPreviews();
                $('#createPostLinkPreview').empty();
                is_link = 0;
                toastr.success((allowed.length === 1 ? '1 file' : allowed.length + ' files') + ' added');
            }
            if (rejected > 0) toastr.error('Some files were not supported. Allowed: ' + createPostAllowedExtensions.join(', '));
            updateCreatePostFacebookFormatRow();
        }

        function renderCreatePostUploadPreviews() {
            createPostRevokeUrls();
            var $container = $('#createPostUploadPreviews');
            $container.empty();
            createPostFiles.forEach(function(file, idx) {
                var ext = (file.name || '').split('.').pop().toLowerCase();
                var isVideo = ['mp4', 'mkv', 'mov', 'mpeg', 'webm'].indexOf(ext) !== -1;
                var url = URL.createObjectURL(file);
                createPostObjectUrls.push(url);
                var $preview = $('<div class="create-post-upload-preview" data-idx="' + idx + '">');
                if (isVideo) {
                    $preview.append($('<video>').attr('src', url).attr('muted', 'true').attr('playsinline', 'true'));
                } else {
                    $preview.append($('<img>').attr('src', url));
                }
                $preview.append('<button type="button" class="create-post-upload-preview-remove" data-idx="' + idx + '" aria-label="Remove"><i class="fas fa-times"></i></button>');
                $container.append($preview);
            });
        }

        $('#createPostUploadZone').on('click', function(e) {
            e.preventDefault();
            if ($(e.target).closest('.create-post-upload-preview-remove').length) return;
            $('#createPostFileInput').trigger('click');
        });

        $('#createPostFileInput').on('change', function() {
            createPostAddFiles(Array.from(this.files || []));
            this.value = '';
        });

        $(document).on('click', '.create-post-upload-preview-remove', function(e) {
            e.stopPropagation();
            var idx = parseInt($(this).data('idx'), 10);
            createPostFiles.splice(idx, 1);
            renderCreatePostUploadPreviews();
            updateCreatePostFacebookFormatRow();
        });

        function createPostSetupDropZone($el) {
            $el.on('dragover', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $('#createPostUploadZone').addClass('is-dragover');
            }).on('dragleave drop', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $('#createPostUploadZone').removeClass('is-dragover');
            }).on('drop', function(e) {
                createPostAddFiles(Array.from(e.originalEvent.dataTransfer.files || []));
            });
        }

        createPostSetupDropZone($('#createPostEditorWrap'));
        createPostSetupDropZone($('#createPostUploadZone'));

        function autoResizeCreatePostTextarea() {
            var el = document.getElementById('createPostEditorTextarea');
            if (!el) return;
            el.style.height = 'auto';
            el.style.height = Math.max(120, el.scrollHeight) + 'px';
        }

        $('#createPostEditorTextarea').on('input', function() {
            autoResizeCreatePostTextarea();
            var value = $(this).val().trim();
            if (!value) {
                $('#createPostLinkPreview').empty();
                is_link = 0;
                updateCreatePostFacebookFormatRow();
                return;
            }
            is_link = 0;
            var linkToFetch = checkLink(value) ? value : extractUrlFromContent(value);
            if (linkToFetch && createPostFiles.length === 0) {
                createPostFetchFromLink(linkToFetch);
            } else {
                $('#createPostLinkPreview').empty();
            }
            updateCreatePostFacebookFormatRow();
        });

        function createPostFetchFromLink(link) {
            if (!link) return;
            var $container = $('#createPostLinkPreview');
            $container.html('<div class="skeleton-wrapper"><div class="content-col"><div class="skeleton-bar bar-title"></div><div class="skeleton-bar bar-full"></div></div><div class="image-col"><div class="skeleton-bar image-placeholder"></div></div></div>');
            $.ajax({
                url: "{{ route('general.previewLink') }}",
                type: "GET",
                data: { link: link },
                success: function(response) {
                    if (response.success) {
                        if (response.no_preview) {
                            $container.html('<div class="real-article-wrapper" style="opacity:1;visibility:visible;"><div class="content-col"><p class="text-muted mb-2" style="font-size: 0.9rem;">' + (response.message || '') + '</p><p class="link_url">' + (response.link || '') + '</p></div></div>');
                            is_link = 1;
                        } else if (response.image) {
                            var html = '<div id="real-article" class="real-article-wrapper" style="opacity:1;visibility:visible;"><div class="content-col"><h5 class="link_title">' + (response.title || '').substring(0, 60) + '...</h5><p class="link_url">' + (response.link || '') + '</p></div><div class="image-col"><img id="link_image" src="' + response.image + '" alt="" loading="lazy"><button type="button" class="close-btn-placeholder">×</button></div></div>';
                            $container.html(html);
                            is_link = 1;
                            if (response.title) {
                                $('#createPostEditorTextarea').val(response.title);
                                setTimeout(autoResizeCreatePostTextarea, 0);
                            }
                        } else {
                            $container.html('<div style="padding: 1rem; color: #DC2626;">Error loading preview.</div>');
                        }
                        updateCreatePostFacebookFormatRow();
                    } else {
                        $container.html('<div style="padding: 1rem; color: #DC2626;">' + (response.message || 'Error') + '</div>');
                        updateCreatePostFacebookFormatRow();
                    }
                },
                error: function() {
                    $container.html('<div style="padding: 1rem; color: #DC2626;">Error loading preview.</div>');
                    updateCreatePostFacebookFormatRow();
                }
            });
        }

        $(document).on('click', '#createPostLinkPreview .close-btn-placeholder', function() {
            $('#createPostLinkPreview').empty();
            $('#createPostEditorTextarea').val('');
            is_link = 0;
            updateCreatePostFacebookFormatRow();
        });

        // publish/queue/schedule post
        $('.action_btn').on('click', function() {
            action_name = $(this).attr("href");
            var inCreatePost = $(this).closest('#createPostModal').length > 0;
            if (inCreatePost && $(this).hasClass('create-post-segmented-btn')) {
                $createPostActiveButton = $(this);
            }
            if (action_name == "schedule") {
                if (inCreatePost) {
                    $('#createPostScheduleDropdown').toggleClass('is-open');
                } else {
                    var schedule_modal = $(".schedule-modal");
                    schedule_modal.modal("toggle");
                }
            } else {
                if (inCreatePost) {
                    if (!checkCreatePostAccounts()) {
                        toastr.error("Please select at least one channel!");
                        return;
                    }
                    if (is_link) {
                        processCreatePostLink();
                    } else if (createPostFiles.length > 0) {
                        processCreatePostPhotoVideo();
                    } else {
                        var content = $('#createPostEditorTextarea').val().trim();
                        if (empty(content)) {
                            toastr.error("Please enter post content or upload a file!");
                            return;
                        }
                        processCreatePostContentOnly();
                    }
                } else {
                    if (is_link) {
                        if (checkAccounts()) {
                            processLink();
                            return true;
                        } else {
                            toastr.error("Please select atleast one channel!");
                        }
                    } else {
                        validateAndProcess();
                    }
                }
            }
        });
        $(document).on('click', '.schedule_btn', function() {
            var schedule_date = $('#schedule_date').val();
            var schedule_time = $('#schedule_time').val();
            if (empty(schedule_date) || empty(schedule_time)) {
                toastr.error("Schedule date & time are required!");
                return false;
            }
            if (!checkPastDateTime(schedule_date, schedule_time)) {
                if ($('#createPostModal').hasClass('show')) {
                    if (is_link) processCreatePostLink();
                    else if (createPostFiles.length > 0) processCreatePostPhotoVideo();
                    else processCreatePostContentOnly();
                } else {
                    if (is_link) processLink();
                    else validateAndProcess();
                }
            }
        });

        $(document).on('click', '.create-post-schedule-confirm', function(e) {
            e.stopPropagation();
            action_name = 'schedule';
            $createPostActiveButton = $('.create-post-schedule-trigger');
            if (!checkCreatePostAccounts()) {
                toastr.error("Please select at least one channel!");
                return;
            }
            if (is_link && !$('#createPostLinkPreview .link_url').text().trim()) {
                toastr.error("Invalid link preview.");
                return;
            }
            if (!is_link && createPostFiles.length === 0) {
                var content = $('#createPostEditorTextarea').val().trim();
                if (empty(content)) {
                    toastr.error("Please enter post content or upload a file!");
                    return;
                }
            }
            var dt = getCreatePostScheduleDateTime();
            var schedule_date = dt.date;
            var schedule_time = dt.time;
            if (empty(schedule_date) || empty(schedule_time)) {
                toastr.error("Schedule date & time are required!");
                return;
            }
            if (!checkPastDateTime(schedule_date, schedule_time)) {
                $('#createPostScheduleDropdown').removeClass('is-open');
                if (is_link) processCreatePostLink();
                else if (createPostFiles.length > 0) processCreatePostPhotoVideo();
                else processCreatePostContentOnly();
            }
        });

        $(document).on('click', function(e) {
            if (!$(e.target).closest('.create-post-schedule-trigger, #createPostScheduleDropdown').length) {
                $('#createPostScheduleDropdown').removeClass('is-open');
            }
        });
        function checkCreatePostAccounts() {
            return $('.channels-dropdown-checkbox:checked').length > 0;
        }

        function getCreatePostSelectedAccounts() {
            var accounts = [];
            $('.channels-dropdown-checkbox:checked').each(function() {
                var id = $(this).data('id');
                var type = $(this).data('type') || 'facebook';
                if (id) accounts.push({ id: id, type: type });
            });
            return accounts;
        }

        function getCreatePostCommentValue() {
            if ($('#createPostFirstCommentWrap').is(':visible')) {
                return $('#createPostFirstComment').val() || '';
            }
            return $('#createPostComment').val() || '';
        }

        function getCreatePostScheduleDateTime() {
            return {
                date: $('#createPostScheduleDate').val() || '',
                time: $('#createPostScheduleTime').val() || ''
            };
        }

        function getScheduleDateTime() {
            if (createPostFromTimeslot && createPostSlotDate && createPostSlotTime) {
                return { date: createPostSlotDate, time: createPostSlotTime };
            }
            if ($('#createPostModal').hasClass('show') && action_name === 'schedule') {
                var dt = getCreatePostScheduleDateTime();
                return { date: dt.date, time: dt.time };
            }
            return { date: $('#schedule_date').val() || '', time: $('#schedule_time').val() || '' };
        }

        function processCreatePostLink() {
            var content = $('#createPostEditorTextarea').val();
            var comment = getCreatePostCommentValue();
            var image = $('#createPostLinkPreview #link_image').attr('src');
            var url = $('#createPostLinkPreview .link_url').text().trim();
            var dt = getScheduleDateTime();
            var schedule_date = dt.date;
            var schedule_time = dt.time;
            var effectiveAction = (createPostFromTimeslot && action_name === 'queue') ? 'schedule' : action_name;
            if (!url) {
                toastr.error("Invalid link preview.");
                return;
            }
            var selectedAccounts = getCreatePostSelectedAccounts();
            var postData = {
                "_token": "{{ csrf_token() }}",
                "content": content,
                "comment": comment,
                "link": 1,
                "url": url,
                "image": image,
                "schedule_date": effectiveAction === 'schedule' ? schedule_date : '',
                "schedule_time": effectiveAction === 'schedule' ? schedule_time : '',
                "action": effectiveAction,
            };
            if (selectedAccounts.length > 0) {
                postData.account_ids = JSON.stringify(selectedAccounts);
            }
            disableActionButton();
            $.ajax({
                url: "{{ route('panel.schedule.process.post') }}",
                type: "POST",
                data: postData,
                success: function(response) {
                    if (response.success) {
                        resetCreatePostArea();
                        toastr.success(response.message);
                    } else {
                        toastr.error(response.message);
                    }
                    enableActionButton();
                },
                error: function() {
                    toastr.error("Failed to process post.");
                    enableActionButton();
                }
            });
        }

        function processCreatePostContentOnly() {
            var content = $('#createPostEditorTextarea').val();
            var comment = getCreatePostCommentValue();
            var dt = getScheduleDateTime();
            var effectiveAction = (createPostFromTimeslot && action_name === 'queue') ? 'schedule' : action_name;
            var data = {
                "_token": "{{ csrf_token() }}",
                "content": content,
                "comment": comment,
                "link": 0,
                "action": effectiveAction
            };
            if (effectiveAction === 'schedule') {
                data.schedule_date = dt.date;
                data.schedule_time = dt.time;
            }
            var selectedAccounts = getCreatePostSelectedAccounts();
            if (selectedAccounts.length > 0) {
                data.account_ids = JSON.stringify(selectedAccounts);
            }
            disableActionButton();
            $.ajax({
                url: "{{ route('panel.schedule.process.post') }}",
                type: "POST",
                data: data,
                success: function(response) {
                    if (response.success) {
                        resetCreatePostArea();
                        toastr.success(response.message);
                    } else {
                        toastr.error(response.message);
                    }
                    enableActionButton();
                },
                error: function() {
                    toastr.error("Failed to process post.");
                    enableActionButton();
                }
            });
        }

        function processCreatePostPhotoVideo() {
            var filesToProcess = createPostFiles.slice();
            if (filesToProcess.length === 0) return;
            disableActionButton();
            processCreatePostFilesQueue(filesToProcess, 0);
        }

        function processCreatePostFilesQueue(filesArray, index) {
            if (index >= filesArray.length) {
                resetCreatePostArea();
                enableActionButton();
                return;
            }
            var file = filesArray[index];
            var content = $('#createPostEditorTextarea').val();
            var comment = getCreatePostCommentValue();
            var dt = getScheduleDateTime();
            var schedule_date = dt.date;
            var schedule_time = dt.time;
            var effectiveAction = (createPostFromTimeslot && action_name === 'queue') ? 'schedule' : action_name;
            var ext = (file.name || '').split('.').pop().toLowerCase();
            var isVideo = ['mp4', 'mkv', 'mov', 'mpeg', 'webm'].indexOf(ext) !== -1;
            var formData = new FormData();
            formData.append("_token", "{{ csrf_token() }}");
            formData.append("content", content);
            formData.append("comment", comment);
            formData.append("link", 0);
            formData.append("video", isVideo ? 1 : 0);
            formData.append("action", effectiveAction);
            formData.append("schedule_date", effectiveAction === 'schedule' ? (schedule_date || '') : '');
            formData.append("schedule_time", effectiveAction === 'schedule' ? (schedule_time || '') : '');
            formData.append("files", file);
            var fbFormat = ($('input[name="create_post_facebook_format"]:checked').val() || 'post');
            formData.append("facebook_content_format", isVideo ? fbFormat : 'post');
            var selectedAccounts = getCreatePostSelectedAccounts();
            if (selectedAccounts.length > 0) {
                formData.append("account_ids", JSON.stringify(selectedAccounts));
            }
            $.ajax({
                url: "{{ route('panel.schedule.process.post') }}",
                type: "POST",
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        processCreatePostFilesQueue(filesArray, index + 1);
                    } else {
                        toastr.error(response.message);
                        enableActionButton();
                    }
                },
                error: function() {
                    toastr.error("Failed to upload post.");
                    enableActionButton();
                }
            });
        }

        function resetCreatePostArea() {
            $('#createPostEditorTextarea').val('').css('height', 'auto');
            $('#createPostComment').val('').attr('rows', 1).css({ height: '', minHeight: '', maxHeight: '' });
            $('#createPostFirstComment').val('');
            $('#createPostLinkPreview').empty();
            createPostFiles = [];
            createPostRevokeUrls();
            $('#createPostUploadPreviews').empty();
            is_link = 0;
            is_video = 0;
            current_file = 0;
            $('#createPostFormatPost').prop('checked', true);
            $('#createPostFacebookFormatWrap').hide();
            $('#createPostScheduleDropdown').removeClass('is-open');
            if (currentPostStatusTab === 'queue' && typeof loadQueueTimeslotsSection === 'function') {
                loadQueueTimeslotsSection();
            }
            loadPostsStatusCounts();
            if (typeof loadPosts === 'function') loadPosts(1);
        }

        // validate and process post
        var validateAndProcess = function() {
            if (!checkAccounts()) {
                toastr.error("Please select atleast one channel!");
                return;
            }
            var content = $("#content").val();
            if (empty(content)) {
                toastr.error("Please enter post content!");
                return;
            }
            processContentOnly();
        }
        // check accounts status
        var checkAccounts = function() {
            var account = false;
            $('.account-card').each(function() {
                if ($(this).hasClass("active")) {
                    account = true;
                }
            });
            return account;
        }
        
        // process content only
        var processContentOnly = function() {
            if ($('#use_short_link_content').is(':checked') && !currentShortUrl) {
                toastr.error('Please wait for the link to shorten.');
                return;
            }
            disableActionButton();
            var content = $('#content').val();
            if ($('#use_short_link_content').is(':checked') && originalUrlInContent && currentShortUrl) {
                content = content.replace(originalUrlInContent, currentShortUrl);
            }
            var comment = $('#comment').val();
            $.ajax({
                url: "{{ route('panel.schedule.process.post') }}",
                type: "POST",
                data: {
                    "_token": "{{ csrf_token() }}",
                    "content": content,
                    "comment": comment,
                    "link": 0,
                    "action": action_name
                },
                success: function(response) {
                    if (response.success) {
                        resetPostArea();
                        toastr.success(response.message);
                    } else {
                        toastr.error(response.message);
                    }
                    enableActionButton();
                }
            })
        }
        // process link post
        var processLink = function() {
            var content = $('#content').val();
            var comment = $('#comment').val();
            var image = $('#link_image').attr('src');
            var title = $('#content').val();
            var originalUrl = $('.link_url').text().trim();
            var url = originalUrl;
            var schedule_date = $("#schedule_date").val();
            var schedule_time = $("#schedule_time").val();

            // Check if TikTok accounts are selected
            var hasTikTokAccounts = false;
            var tiktokAccounts = [];
            $('.account-card.active').each(function() {
                if ($(this).data('type') === 'tiktok') {
                    hasTikTokAccounts = true;
                    tiktokAccounts.push({
                        id: $(this).data('id'),
                        name: $(this).find('.account-name').text().trim()
                    });
                }
            });

            // If TikTok accounts are selected, show TikTok modal for link posts
            if (hasTikTokAccounts && url && image) {
                showTikTokLinkModal(title, url, image, tiktokAccounts, schedule_date, schedule_time);
                return;
            }

            // For non-TikTok accounts, process normally
            $.ajax({
                url: "{{ route('panel.schedule.process.post') }}",
                type: "POST",
                data: {
                    "_token": "{{ csrf_token() }}",
                    "content": content,
                    "comment": comment,
                    "link": 1,
                    "url": url,
                    "image": image,
                    "schedule_date": schedule_date,
                    "schedule_time": schedule_time,
                    "action": action_name,
                },
                success: function(response) {
                    if (response.success) {
                        resetPostArea();
                        toastr.success(response.message);
                    } else {
                        toastr.error(response.message);
                    }
                }
            });
        }
        // reset post area
        var resetPostArea = function() {
            current_file = 0;
            is_link = 0;
            is_video = 0;
            currentShortUrl = null;
            originalUrlInContent = null;
            $('#content').val('');
            $('#comment').val('');
            $('#content').attr('rows', 3).css({ height: '', minHeight: '', maxHeight: '' });
            $('#comment').attr('rows', 1).css({ height: '', minHeight: '', maxHeight: '' });
            $('#characterCount').text('');
            $('#article-container').empty();
            reloadPosts();
            loadPostsStatusCounts();
            enableActionButton();
        }
        // Extract first URL from text (for shortening when link is in content but post is photo/content)
        function extractUrlFromContent(text) {
            if (!text || !text.trim()) return null;
            var m = text.trim().match(/https?:\/\/[^\s"'<>]+/);
            return m ? m[0].replace(/[.,;:!?)]+$/, '') : null;
        }
        // Show/hide content URL shortener (when link is in textarea but post type is photo/content)
        function toggleContentShortenerVisibility() {
            var value = $("#content").val();
            var urlInContent = extractUrlFromContent(value);
            var isPhotoOrContentPost = !is_link;
            if (isPhotoOrContentPost && urlInContent) {
                $('#content-url-shortener-wrap').show();
            } else {
                $('#content-url-shortener-wrap').hide();
                if (!is_link) {
                    $('#use_short_link_content').prop('checked', false);
                    $('#short-link-result-content').hide();
                    $('#short_link_url_display_content').val('');
                    currentShortUrl = null;
                    originalUrlInContent = null;
                }
            }
        }
        // check link for content
        $('#content').on('input', function() {
            var value = $(this).val();
            if(!empty(value)){
                is_link = 0;
                if (checkLink(value)) {
                    fetchFromLink(value);
                }
                toggleContentShortenerVisibility();
            }
        });
        // fetch from link
        var fetchFromLink = function(link) {
            if (link) {
                $('#content-url-shortener-wrap').hide();
                // render skeleton
                renderSkeletonLoader();
                disableActionButton();
                $.ajax({
                    url: "{{ route('general.previewLink') }}",
                    type: "GET",
                    data: {
                        "link": link,
                    },
                    success: function(response) {
                        if (response.success) {
                            var title = response.title;
                            var image = response.image;
                            if (response.no_preview) {
                                if (title) {
                                    $("#content").val(title);
                                }
                                container.html(
                                    '<div id="real-article" class="real-article-wrapper" style="opacity: 1;">' +
                                    '<div class="content-col">' +
                                    '<p class="text-muted mb-2" style="font-size: 0.9rem;">' + (response.message || '') + '</p>' +
                                    '<p class="link_url">' + (response.link || '') + '</p>' +
                                    '<p class="small text-muted mt-1">You can still schedule, queue or publish this link without a preview image.</p>' +
                                    '</div></div>'
                                );
                                is_link = 1;
                                toastr.info(response.message);
                            } else if (!empty(title)) {
                                $("#content").val(response.title);
                                $("#content").trigger("input");
                            }
                            if (!response.no_preview) {
                                if (!empty(image)) {
                                    renderArticleContent(response);
                                    is_link = 1;
                                } else {
                                    container.html(
                                        '<div style="padding: 1rem; color: #DC2626;">Error loading data. Please try again.</div>'
                                    );
                                }
                            }
                        } else {
                            container.html(
                                '<div style="padding: 1rem; color: #DC2626;">Error loading data. Please try again.</div>'
                            );
                            toastr.error(response.message);
                        }
                        setTimeout(function() {
                            enableActionButton();
                        }, 500);
                    }
                });
            }
        };
        // disable action buttons
        var disableActionButton = function() {
            $('.action_btn').attr("disabled", true);
            $('.schedule_btn').attr("disabled", true);
            if ($createPostActiveButton && $createPostActiveButton.length) {
                var $btns = $createPostActiveButton.closest('.create-post-segmented-buttons').find('.create-post-segmented-btn');
                $btns.prop('disabled', true);
                if (!$createPostActiveButton.data('original-html')) {
                    $createPostActiveButton.data('original-html', $createPostActiveButton.html());
                }
                $createPostActiveButton.html('<i class="fas fa-spinner fa-spin mr-1"></i>' + $createPostActiveButton.data('original-html').trim());
            }
        };
        // enable action buttons
        var enableActionButton = function() {
            $('.action_btn').attr("disabled", false);
            $('.schedule_btn').attr("disabled", false);
            if ($createPostActiveButton && $createPostActiveButton.length) {
                var $btns = $createPostActiveButton.closest('.create-post-segmented-buttons').find('.create-post-segmented-btn');
                $btns.prop('disabled', false);
                var originalHtml = $createPostActiveButton.data('original-html');
                if (originalHtml) {
                    $createPostActiveButton.html(originalHtml);
                    $createPostActiveButton.removeData('original-html');
                }
                $createPostActiveButton = null;
            }
            $('.schedule-modal').modal("hide");
        };
        // Open queue settings modal (optionally for one account only)
        function openQueueSettingsModal(filterAccount) {
            var modal = $('.settings-modal');
            if (filterAccount) {
                modal.data('filterAccount', filterAccount);
            } else {
                modal.removeData('filterAccount');
            }

            // Show skeleton, hide content
            $('#queue-settings-skeleton').show();
            $('#queue-settings-list-wrap').hide();
            $('#queue-settings-list').empty();
            originalQueueTimeslots = {};
            queueTimeslotsChanged = false;
            $('#saveQueueSettings').hide();

            modal.modal("toggle");

            $.ajax({
                url: "{{ route('panel.schedule.queue-settings') }}",
                type: "GET",
                success: function(response) {
                    $('#queue-settings-skeleton').hide();
                    if (response.success && response.data) {
                        $('#queue-settings-list').html(response.data);
                        $('#queue-settings-list-wrap').show();
                        applyQueueSettingsFilterAndInit();
                    } else {
                        $('#queue-settings-list-wrap').show();
                        $('#queue-settings-list').html('<div class="text-danger py-3">Failed to load queue settings.</div>');
                    }
                },
                error: function() {
                    $('#queue-settings-skeleton').hide();
                    $('#queue-settings-list-wrap').show();
                    $('#queue-settings-list').html('<div class="text-danger py-3">Failed to load queue settings. Please try again.</div>');
                }
            });
        }

        function applyQueueSettingsFilterAndInit() {
            var modal = $('.settings-modal');
            var filter = modal.data('filterAccount');
            var $rows = modal.find('.queue-settings-item');

            if (filter && filter.id != null && filter.type != null) {
                $rows.each(function() {
                    var $row = $(this);
                    var $select = $row.find('.timeslot');
                    var match = $select.data('id') == filter.id && $select.data('type') === filter.type;
                    $row.toggle(match);
                });
                modal.find('.queue-settings-modal-title').text(filter.name ? ('Queue settings – ' + filter.name) : 'Queue settings');
            } else {
                $rows.show();
                modal.find('.queue-settings-modal-title').text('Queue settings');
            }

            if (typeof $.fn.select2 !== 'undefined') {
                modal.find('.timeslot').each(function() {
                    if (!$(this).hasClass('select2-hidden-accessible')) {
                        $(this).select2({ closeOnSelect: false, width: '100%' });
                    }
                });
            }

            setTimeout(function() {
                $('.timeslot').each(function() {
                    var $select = $(this);
                    if ($select.closest('.queue-settings-item').is(':visible')) {
                        var accountId = $select.data("id");
                        var accountType = $select.data("type");
                        var key = accountType + '_' + accountId;
                        var originalValue = $select.val() ? $select.val().sort().join(',') : '';
                        originalQueueTimeslots[key] = originalValue;
                    }
                });
                $('.queue-settings-shuffle-input').each(function() {
                    var $input = $(this);
                    if ($input.closest('.queue-settings-item').is(':visible')) {
                        var key = $input.data("type") + '_' + $input.data("id");
                        originalScheduleShuffle[key] = $input.prop('checked') ? 1 : 0;
                    }
                });
            }, 300);
        }

        $('.settings-modal').on('hidden.bs.modal', function() {
            $(this).removeData('filterAccount');
        });

        // settings modal – all accounts
        $('.setting_btn').on("click", function() {
            openQueueSettingsModal();
        });

        // Header settings icon – queue settings for the selected account only
        $(document).on('click', '#selected-account-header-settings', function() {
            var $first = $('.account-card.active').first();
            if (!$first.length) return;
            var id = $first.data('id');
            var type = $first.data('type');
            var name = $first.find('.account-name').text().trim() || 'Account';
            openQueueSettingsModal({ id: id, type: type, name: name });
        });

        // Track original timeslots and schedule_shuffle for queue settings modal
        var originalQueueTimeslots = {};
        var originalScheduleShuffle = {};
        var queueTimeslotsChanged = false;

        // Shuffle toggle: update immediately via AJAX (no Save Changes button)
        $(document).on("change", ".queue-settings-shuffle-input", function() {
            var $input = $(this);
            if ($input.data("type") !== 'facebook') return;
            var accountId = $input.data("id");
            var scheduleShuffle = $input.prop('checked') ? 1 : 0;
            var token = "{{ csrf_token() }}";
            originalScheduleShuffle[$input.data("type") + '_' + accountId] = scheduleShuffle;
            $.ajax({
                url: "{{ route('panel.schedule.shuffle-status') }}",
                type: "POST",
                data: {
                    "_token": token,
                    "account_id": accountId,
                    "schedule_shuffle": scheduleShuffle
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success('Shuffle status updated.');
                        if (typeof reloadPosts === 'function') reloadPosts();
                    } else {
                        toastr.error(response.message || 'Failed to update shuffle.');
                    }
                },
                error: function() {
                    toastr.error('Failed to update shuffle status.');
                    $input.prop('checked', scheduleShuffle === 0);
                }
            });
        });

        // Track timeslot changes (don't update immediately)
        $(document).on("change", ".timeslot", function() {
            var $select = $(this);
            var accountId = $select.data("id");
            var accountType = $select.data("type");
            var key = accountType + '_' + accountId;
            var currentValue = $select.val() ? $select.val().sort().join(',') : '';

            // Check if timeslots have changed
            if (originalQueueTimeslots[key] !== currentValue) {
                queueTimeslotsChanged = true;
                $('#saveQueueSettings').show();
            } else {
                // Check if all timeslots match originals
                checkQueueTimeslotChanges();
            }
        });

        // Check if queue timeslots have changed (only visible rows when modal is filtered). Shuffle is updated on change, not via Save.
        function checkQueueTimeslotChanges() {
            queueTimeslotsChanged = false;
            $('.timeslot').each(function() {
                var $select = $(this);
                if (!$select.closest('.queue-settings-item').is(':visible')) return;
                var accountId = $select.data("id");
                var accountType = $select.data("type");
                var key = accountType + '_' + accountId;
                var currentValue = $select.val() ? $select.val().sort().join(',') : '';

                if (originalQueueTimeslots[key] !== currentValue) {
                    queueTimeslotsChanged = true;
                    return false; // break loop
                }
            });
            if (!queueTimeslotsChanged) {
                $('#saveQueueSettings').hide();
            }
        }

        // Save queue settings (timeslots only; shuffle is updated on change)
        $(document).on('click', '#saveQueueSettings', function() {
            var timeslotData = [];
            $('.timeslot').each(function() {
                var $select = $(this);
                var accountId = $select.data("id");
                var accountType = $select.data("type");
                var timeslots = $select.val();

                if (timeslots && timeslots.length > 0) {
                    timeslotData.push({ id: accountId, type: accountType, timeslots: timeslots });
                }
            });

            if (timeslotData.length === 0) {
                toastr.warning("Please select at least one timeslot for an account.");
                return;
            }

            var token = "{{ csrf_token() }}";
            var $saveBtn = $(this);
            $saveBtn.prop('disabled', true).html(
                '<i class="fas fa-spinner fa-spin mr-1"></i> Saving...');

            $.ajax({
                url: "{{ route('panel.schedule.timeslot.setting.save') }}",
                type: "POST",
                data: {
                    "_token": token,
                    "timeslot_data": timeslotData,
                },
                success: function(response) {
                    if (response.success) {
                        finishQueueSettingsSave($saveBtn);
                    } else {
                        $saveBtn.prop('disabled', false).html(
                            '<i class="fas fa-save mr-1"></i> Save Changes');
                        toastr.error(response.message);
                    }
                },
                error: function() {
                    $saveBtn.prop('disabled', false).html(
                        '<i class="fas fa-save mr-1"></i> Save Changes');
                    toastr.error('Something went wrong!');
                }
            });
        });

        function finishQueueSettingsSave($saveBtn) {
            $saveBtn.prop('disabled', false).html(
                '<i class="fas fa-save mr-1"></i> Save Changes');
            toastr.success('Queue settings saved successfully.');
            originalQueueTimeslots = {};
            queueTimeslotsChanged = false;
            $('#saveQueueSettings').hide();
            $('.timeslot').each(function() {
                var $select = $(this);
                var accountId = $select.data("id");
                var accountType = $select.data("type");
                var key = accountType + '_' + accountId;
                var currentValue = $select.val() ? $select.val().sort()
                    .join(',') : '';
                originalQueueTimeslots[key] = currentValue;
            });
            $('.queue-settings-shuffle-input').each(function() {
                var $input = $(this);
                var key = $input.data("type") + '_' + $input.data("id");
                originalScheduleShuffle[key] = $input.prop('checked') ? 1 : 0;
            });
            if (typeof reloadPosts === 'function') {
                reloadPosts();
            }
        }
        // link loading and preview
        const container = $('#article-container');

        function renderSkeletonLoader() {
            const skeletonHTML = `
                    <div id="skeleton-loader" class="skeleton-wrapper">     
                        <!-- Left Column (Text Content) -->
                        <div class="content-col">
                            <!-- Title Line -->
                            <div class="skeleton-bar bar-title animate-pulse-slow"></div>

                            <!-- Body Line 1 (Longest) -->
                            <div class="skeleton-bar bar-full animate-pulse-slow"></div>
                            
                            <!-- Body Line 2 (Medium) -->
                            <div class="skeleton-bar bar-medium animate-pulse-slow"></div>

                            <!-- Body Line 3 (Shortest, like a secondary detail) -->
                            <div class="skeleton-bar bar-short animate-pulse-slow" style="margin-bottom: 0;"></div>
                        </div>
                        <!-- Right Column (Image/Sidebar Block) -->
                        <div class="image-col">
                            <!-- Image block placeholder -->
                            <div class="skeleton-bar image-placeholder animate-pulse-slow"></div>
                            
                            <!-- Close Button placeholder -->
                            <button class="close-btn-placeholder" disabled>
                                X
                            </button>
                        </div>
                    </div>
                `;
            container.html(skeletonHTML);
        }

        function renderArticleContent(data) {
            const articleHTML = `
                    <div id="real-article" class="real-article-wrapper">  
                        <!-- Left Column (Text Content) -->
                        <div class="content-col">
                            <h5 class="link_title" title="${data.title}">${data.title.substring(0, 60)}...</h5>
                            <p class="link_url">${data.link}</p>
                        </div>
                        <!-- Right Column (Image/Sidebar) -->
                        <div class="image-col" style="margin-left: 1rem;">
                            <img id="link_image" src="${data.image}" alt="Feature Icon" loading="lazy">
                            <!-- Close Button (Functional) -->
                            <button class="close-btn-placeholder"
                                style="background-color: black; color: white; cursor: pointer;">
                                X
                            </button>
                        </div>
                    </div>`;
            container.html(articleHTML);
            originalUrlInContent = null;
            $('#content-url-shortener-wrap').hide();
            $('#real-article').animate({
                opacity: 1
            }, 1000);
        }
        $(document).on('click', '.close-btn-placeholder', function() {
            resetPostArea();
        });

        $(document).on('click', '.copy-short-link-content', function() {
            var url = $('#short_link_url_display_content').val();
            if (url && navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(url).then(function() {
                    toastr.success('Short link copied to clipboard.');
                }).catch(function() {
                    fallbackCopyShortLink(url);
                });
            } else {
                fallbackCopyShortLink(url);
            }
        });

        function fallbackCopyShortLink(text) {
            var $input = $('<input>').val(text).appendTo('body').select();
            try {
                document.execCommand('copy');
                toastr.success('Short link copied to clipboard.');
            } catch (e) {
                toastr.info('Short link: ' + text);
            }
            $input.remove();
        }
        // Content shortener (when link is in textarea but post is photo/content)
        $(document).on('change', '#use_short_link_content', function() {
            var $cb = $(this);
            var $result = $('#short-link-result-content');
            var $display = $('#short_link_url_display_content');
            if (!$cb.is(':checked')) {
                currentShortUrl = null;
                originalUrlInContent = null;
                $result.hide();
                $display.val('');
                return;
            }
            var originalUrl = extractUrlFromContent($("#content").val());
            if (!originalUrl) {
                toastr.warning('No link found in your post to shorten.');
                $cb.prop('checked', false);
                return;
            }
            originalUrlInContent = originalUrl;
            $result.show();
            $display.val('Shortening...');
            $.ajax({
                url: "{{ route('general.shorten') }}",
                type: "POST",
                data: {
                    "_token": "{{ csrf_token() }}",
                    "original_url": originalUrl
                },
                success: function(res) {
                    if (res.success && res.short_url) {
                        currentShortUrl = res.short_url;
                        $display.val(res.short_url);
                    } else {
                        currentShortUrl = null;
                        originalUrlInContent = null;
                        $display.val('');
                        $result.hide();
                        toastr.error(res.message || 'Could not shorten link.');
                        $cb.prop('checked', false);
                    }
                },
                error: function(xhr) {
                    currentShortUrl = null;
                    originalUrlInContent = null;
                    $display.val('');
                    $result.hide();
                    toastr.error(xhr.responseJSON && xhr.responseJSON.message ? xhr
                        .responseJSON.message : 'Could not shorten link.');
                    $cb.prop('checked', false);
                }
            });
        });
        // Posts Grid Variables
        var currentPage = 1;
        var perPage = 9;
        var totalPosts = 0;
        /** True when last listing request asked server for all Pinterest sent rows (no pagination). */
        var lastPostsListingWasPinterestSentAll = false;

        function loadPosts(page = 1) {
            currentPage = page;
            if (currentPostStatusTab === 'sent' && !shouldUseFacebookSentPageTimeline()) {
                sentPostsGroupedByDay = [];
                sentDayOffset = 0;
            }

            lastPostsListingWasPinterestSentAll = (currentPostStatusTab === 'sent' && isPinterestOnlySelection());
            var requestStart = lastPostsListingWasPinterestSentAll ? 0 : (page - 1) * perPage;
            var requestLength = lastPostsListingWasPinterestSentAll ? 1 : perPage;

            $('#postsGrid').html(`
                <div class="loading-state text-center py-5" style="grid-column: 1/-1;">
                    <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                    <p class="mt-2 text-muted">Loading posts...</p>
                </div>
            `);

            var selectedAccounts = getSelectedAccounts();

            if (selectedAccounts.accountIds.length === 0) {
                var tabLabel = 'queued';
                $('#postsGrid').html(`
                    <div class="empty-state-box">
                        <i class="far fa-folder-open"></i>
                        <p>No ${tabLabel} posts found.</p>
                    </div>
                `);
                totalPosts = 0;
                renderPagination();
                return;
            }

            $.ajax({
                url: "{{ route('panel.schedule.posts.listing') }}",
                type: "GET",
                data: {
                    draw: 1,
                    start: requestStart,
                    length: requestLength,
                    account_id: selectedAccounts.accountIds,
                    type: selectedAccounts.accountTypes,
                    post_type: $("#filter_post_type").val(),
                    status: getStatusFilterValue(),
                    post_status_tab: currentPostStatusTab,
                    sent_load_all: lastPostsListingWasPinterestSentAll ? 1 : 0
                },
                success: function(response) {
                    totalPosts = response.iTotalDisplayRecords;
                    renderPosts(response.data);
                    renderPagination();
                    loadPostsStatusCounts();
                },
                error: function() {
                    $('#postsGrid').html(`
                        <div class="empty-state-box">
                            <i class="fas fa-exclamation-circle"></i>
                            <p>Failed to load posts. Please try again.</p>
                        </div>
                    `);
                }
            });
        }

        // Render posts grid
        function renderPosts(posts) {
            if (posts.length === 0) {
                var tabLabel = 'queued';
                $('#postsGrid').html(`
                    <div class="empty-state-box">
                        <i class="far fa-folder-open"></i>
                        <p>No ${tabLabel} posts found.</p>
                    </div>
                `);
                return;
            }

            var html = '';
            posts.forEach(function(post) {
                html += renderPostCard(post);
            });
            $('#postsGrid').html(html);
        }

        function renderSentDayGroupsHtml(startOffset, count) {
            var html = '';
            for (var i = startOffset; i < startOffset + count && i < sentPostsGroupedByDay.length; i++) {
                var day = sentPostsGroupedByDay[i];
                var parts = day.label.split('|');
                html += '<div class="sent-day-group">';
                html += '<h3 class="sent-day-header"><strong>' + parts[0] + '</strong>, <span>' + parts[1] + '</span></h3>';
                day.posts.forEach(function(post) {
                    html += renderSentPagePostCard(post);
                });
                html += '</div>';
            }
            return html;
        }

        function renderSentPagePosts(startOffset, count) {
            var html = '<div class="sent-posts-timeline">' + renderSentDayGroupsHtml(startOffset, count) + '</div>';
            $('#postsGrid').html(html);
            sentDayOffset = startOffset + count;
        }

        function appendSentPagePosts(startOffset, count) {
            var html = renderSentDayGroupsHtml(startOffset, count);
            var $timeline = $('#postsGrid .sent-posts-timeline');
            if ($timeline.length) {
                $timeline.append(html);
            }
        }

        function renderSentPagePostCard(post) {
            var ct = parseCreatedTime(post.created_time);
            var timePart = '';
            if (ct && !isNaN(ct.getTime())) {
                timePart = formatInUserTimezone(ct, { hour: '2-digit', minute: '2-digit', hour12: true });
            }

            var statusType = post.status_type || '';
            // Commented out per new design request: hide sent post source label.
            // var sourceLabel = statusType ? '<span class="sent-post-source"><i class="fas fa-layer-group"></i> ' + statusType.replace(/_/g, ' ') + '</span>' : '';
            var sourceLabel = '';

            var profileImg = post.account_profile || '';
            var socialLogo = "{{ social_logo('facebook') }}";
            var accountName = post.account_name || 'Facebook Page';

            var imageHtml = '';
            if (post.full_picture) {
                imageHtml = '<div class="sent-card-image"><img src="' + post.full_picture + '" alt="" loading="lazy" onerror="this.style.display=\'none\'"></div>';
            }

            var message = post.message || post.story || '';
            var escapedMsg = $('<span>').text(message).html();
            var sentTextLimit = 140;
            var isLongMsg = escapedMsg.length > sentTextLimit;
            var truncMsg = isLongMsg ? escapedMsg.substring(0, sentTextLimit) + '...' : escapedMsg;
            var fullMsg = escapedMsg;
            var titleHtml = isLongMsg ?
                '<p class="sent-card-title queue-text-expandable-wrap"><span class="queue-text-short">' + truncMsg + '</span><span class="queue-text-full" style="display:none">' + fullMsg + '</span></p><button type="button" class="queue-post-see-more-btn sent-see-more-btn">See more</button>' :
                '<p class="sent-card-title">' + truncMsg + '</p>';

            var viewPostBtn = '';
            if (post.permalink_url) {
                viewPostBtn = '<a href="' + post.permalink_url + '" target="_blank" class="sent-card-view-btn"><i class="fas fa-external-link-alt"></i> View Post</a>';
            }
            var deleteBtn = '<button type="button" class="sent-card-delete-btn" data-post-id="' + (post.id || '') + '" data-page-id="' + (post.page_db_id || '') + '" title="Delete post"><i class="fas fa-trash-alt"></i> Delete</button>';

            var ins = post.insights || {};
            var reactions = ins.post_reactions ?? 0;
            var comments = post.comments ?? 0;
            var impressions = ins.post_impressions ?? '-';
            var shares = post.shares ?? 0;
            var clicks = ins.post_clicks ?? '-';

            var publishedViaHtml = '';
            if (post.publisher_username) {
                var pubUsername = $('<span>').text(post.publisher_username).html();
                var pubEmailRaw = post.publisher_email || '';
                var pubEmail = pubEmailRaw.replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/'/g, '&#39;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
                publishedViaHtml = 'Published via <span class="sent-card-published-via-tooltip" title="' + pubEmail + '" data-tooltip="' + pubEmail + '">' + pubUsername + '</span>';
            } else {
                publishedViaHtml = 'Published via <span class="sent-card-platform-icon facebook"><i class="fab fa-facebook-f"></i></span> Facebook';
            }

            return `
                <div class="sent-post-row" data-post-id="${post.id || ''}" data-page-id="${post.page_db_id || ''}">
                    <div class="sent-post-time-col">
                        <span class="sent-post-time">${timePart}</span>
                        ${sourceLabel}
                    </div>
                    <div class="sent-post-card-col">
                        <div class="sent-card">
                            <div class="sent-card-body">
                                <div class="sent-card-content">
                                    <div class="sent-card-account">
                                        <div class="sent-card-avatar-wrap">
                                            <img src="${profileImg}" class="sent-card-avatar" onerror="this.onerror=null;this.src='${socialLogo}';" loading="lazy">
                                            <span class="sent-card-platform-badge facebook"><i class="fab fa-facebook-f"></i></span>
                                        </div>
                                        <span class="sent-card-account-name">${accountName}</span>
                                    </div>
                                    ${titleHtml}
                                </div>
                                ${imageHtml}
                            </div>
                            ${canAccessAnalytics ? '<div class="sent-card-stats">' +
                                '<div class="sent-card-stat"><i class="far fa-thumbs-up"></i> <span class="stat-label">Likes</span> <strong>' + reactions + '</strong></div>' +
                                '<div class="sent-card-stat"><i class="far fa-comment"></i> <span class="stat-label">Comments</span> <strong>' + comments + '</strong></div>' +
                                '<div class="sent-card-stat"><i class="far fa-eye"></i> <span class="stat-label">Impressions</span> <strong>' + impressions + '</strong></div>' +
                                '<div class="sent-card-stat"><i class="fas fa-share-alt"></i> <span class="stat-label">Shares</span> <strong>' + shares + '</strong></div>' +
                                '<div class="sent-card-stat"><i class="fas fa-mouse-pointer"></i> <span class="stat-label">Clicks</span> <strong>' + clicks + '</strong></div>' +
                            '</div>' : ''}
                            <div class="sent-card-footer">
                                <div class="sent-card-published-via">${publishedViaHtml}</div>
                                <div class="sent-card-footer-actions">
                                    ${viewPostBtn}
                                    ${deleteBtn}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        // Render single post card
        function renderPostCard(post) {
            var statusClass = post.status == 1 ? 'published' : (post.status == -1 ? 'failed' : 'pending');
            var statusText = post.status == 1 ? 'Published' : (post.status == -1 ? 'Failed' : 'Pending');
            var platformIcon = post.social_type === 'facebook' ? 'fab fa-facebook-f' : (post.social_type ===
                'pinterest' ? 'fab fa-pinterest-p' : 'fab fa-tiktok');
            var platformClass = post.social_type;

            // Source badge
            var sourceBadge = '';
            if (post.source) {
                var sourceIcon = post.source === 'rss' ? 'fa-rss' : (post.source === 'api' ? 'fa-code' :
                    'fa-edit');
                var sourceClass = post.source === 'rss' ? 'rss' : (post.source === 'api' ? 'api' : 'manual');
                sourceBadge =
                    `<span class="source-badge ${sourceClass}"><i class="fas ${sourceIcon}"></i> ${post.source.toUpperCase()}</span>`;
            }

            var publishedAt = post.status == 1 && post.published_at ?
                `<div class="published-at">Published at: ${post.published_at_formatted || post.published_at}</div>` :
                '';

            var responseHtml = '';
            if (post.response) {
                var responseClass = post.status == 1 ? 'success' : (post.status == -1 ? 'error' : '');
                var responseText = post.response_message;
                // var responseText = post.response.length > 100 ? post.response.substring(0, 100) + '...' : post.response;
                responseHtml = `
                    <div class="response-section">
                        <div class="response-label">Response</div>
                        <div class="response-text ${responseClass}">${responseText}</div>
                    </div>
                `;
            }

            var actionButtons = '';
            if (post.status == 0) {
                actionButtons = `
                    <button class="btn btn-outline-primary btn-sm edit_btn" data-id="${post.id}" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-outline-success btn-sm publish_now_btn" data-id="${post.id}" title="Publish Now">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                    <button class="btn btn-outline-danger btn-sm delete_btn" data-id="${post.id}" title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>
                `;
            } else {
                actionButtons = `
                    <button class="btn btn-outline-danger btn-sm delete_btn" data-id="${post.id}" title="Delete">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                `;
            }

            return `
                <div class="schedule-post-card">
                    <div class="post-preview">
                        ${post.post_details}
                    </div>
                    <div class="post-meta">
                        <div class="post-meta-row">
                            <div class="post-account-badge">
                                <span class="platform-icon ${platformClass}">
                                    <i class="${platformIcon}"></i>
                                </span>
                                <span class="post-account-name">${post.account_name || 'Unknown'}</span>
                            </div>
                            ${sourceBadge}
                        </div>
                        <div class="post-meta-row">
                            <div class="datetime-info">
                                <span class="label">Scheduled:</span>
                                <span class="value">${post.publish_datetime}</span>
                            </div>
                            <div>
                                <span class="status-badge ${statusClass}">
                                    <i class="fas fa-${post.status == 1 ? 'check-circle' : (post.status == -1 ? 'times-circle' : 'clock')}"></i>
                                    ${statusText}
                                </span>
                                ${publishedAt}
                            </div>
                        </div>
                        ${responseHtml}
                        <div class="post-actions-bar">
                            ${actionButtons}
                        </div>
                    </div>
                </div>
            `;
        }

        // Render pagination
        function renderPagination() {
            if (lastPostsListingWasPinterestSentAll && currentPostStatusTab === 'sent') {
                if (totalPosts === 0) {
                    $('.pagination-info').html('');
                    $('.pagination').html('');
                    return;
                }
                $('.pagination-info').html('Showing all ' + totalPosts + ' sent posts');
                $('.pagination').html('');
                return;
            }

            var totalPages = Math.ceil(totalPosts / perPage);
            var start = (currentPage - 1) * perPage + 1;
            var end = Math.min(currentPage * perPage, totalPosts);

            if (totalPosts === 0) {
                $('.pagination-info').html('');
                $('.pagination').html('');
                return;
            }

            $('.pagination-info').html(`Showing ${start} to ${end} of ${totalPosts} posts`);

            var paginationHtml = '';

            // Previous button
            paginationHtml += `
                <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="${currentPage - 1}">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                </li>
            `;

            // Page numbers
            var startPage = Math.max(1, currentPage - 2);
            var endPage = Math.min(totalPages, currentPage + 2);

            if (startPage > 1) {
                paginationHtml +=
                    `<li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li>`;
                if (startPage > 2) {
                    paginationHtml += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                }
            }

            for (var i = startPage; i <= endPage; i++) {
                paginationHtml += `
                    <li class="page-item ${i === currentPage ? 'active' : ''}">
                        <a class="page-link" href="#" data-page="${i}">${i}</a>
                    </li>
                `;
            }

            if (endPage < totalPages) {
                if (endPage < totalPages - 1) {
                    paginationHtml += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                }
                paginationHtml +=
                    `<li class="page-item"><a class="page-link" href="#" data-page="${totalPages}">${totalPages}</a></li>`;
            }

            // Next button
            paginationHtml += `
                <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="${currentPage + 1}">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </li>
            `;

            $('.pagination').html(paginationHtml);
        }

        // Sent post 3-dot menu toggle
        $(document).on('click', '.sent-post-menu-btn', function(e) {
            e.stopPropagation();
            var $wrap = $(this).closest('.sent-post-menu-wrap');
            var wasOpen = $wrap.hasClass('open');
            $('.sent-post-menu-wrap.open').removeClass('open');
            if (!wasOpen) $wrap.addClass('open');
        });
        $(document).on('click', function() {
            $('.sent-post-menu-wrap.open').removeClass('open');
        });

        // Sent post Delete button – delete from Facebook and DB
        $(document).on('click', '.sent-card-delete-btn', function() {
            var $btn = $(this);
            var postId = $btn.data('post-id');
            var pageId = $btn.data('page-id');
            if (!postId || !pageId) {
                toastr.error('Cannot delete: missing post or account info.');
                return;
            }
            if (!confirm('Delete this post from Facebook? This cannot be undone.')) return;
            $btn.prop('disabled', true).addClass('is-deleting');
            $.ajax({
                url: "{{ route('panel.schedule.delete-sent-post') }}",
                type: "POST",
                data: {
                    "_token": "{{ csrf_token() }}",
                    "id": postId,
                    "page_id": pageId
                },
                success: function() {
                    var $row = $btn.closest('.sent-post-row');
                    $row.fadeOut(200, function() { $(this).remove(); });
                    toastr.success('Post deleted successfully.');
                    var $tab = $('#posts-status-tabs [data-count="sent"]');
                    var n = parseInt($tab.text(), 10) || 0;
                    if (n > 0) $tab.text(n - 1);
                },
                error: function(xhr) {
                    $btn.prop('disabled', false).removeClass('is-deleting');
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Failed to delete post.';
                    toastr.error(msg);
                }
            });
        });

        // Pagination click
        $(document).on('click', '.pagination .page-link', function(e) {
            e.preventDefault();
            var page = $(this).data('page');
            if (page && !$(this).parent().hasClass('disabled') && !$(this).parent().hasClass(
                    'active')) {
                loadPosts(page);
            }
        });

        // Get status filter value (handle "all" option)
        function getStatusFilterValue() {
            var statusValues = $("#filter_status").val();
            if (!statusValues || statusValues.length === 0) {
                return [];
            }
            // If "all" is selected, return empty array to show all statuses
            if (statusValues.includes('all')) {
                return [];
            }
            return statusValues;
        }

        // Handle "All Status" option in status filter
        $(document).on('change', '#filter_status', function() {
            var selectedValues = $(this).val();
            if (!selectedValues) {
                selectedValues = [];
            }

            var $select = $(this);
            var hasAll = selectedValues.includes('all');
            var individualStatuses = ['0', '1', '-1'];
            var hasIndividualStatuses = individualStatuses.some(function(status) {
                return selectedValues.includes(status);
            });

            // If "all" is selected
            if (hasAll) {
                // If "all" was just selected, deselect individual statuses to avoid confusion
                if (hasIndividualStatuses) {
                    $select.val(['all']).trigger('change.select2');
                }
            } else {
                // If all individual statuses are selected, automatically select "all"
                var allSelected = individualStatuses.every(function(status) {
                    return selectedValues.includes(status);
                });
                if (allSelected && selectedValues.length === 3) {
                    $select.val(['all']).trigger('change.select2');
                    return; // Don't reload yet, let the change event trigger again
                }
            }

            // Reload posts with updated filter
            loadPosts(1);
        });

        // Filter change (for other filters)
        $(document).on('change', '.filter:not(#filter_status)', function() {
            loadPosts(1);
        });

        // Reload posts function (for use after actions)
        var reloadPosts = function() {
            if (currentPostStatusTab === 'queue') {
                if (typeof loadQueueTimeslotsSection === 'function') loadQueueTimeslotsSection();
            } else if (currentPostStatusTab === 'sent') {
                if (shouldUseFacebookSentPageTimeline()) {
                    cachedSentPagePosts = null;
                    loadSentPagePostsCached(getSelectedAccounts());
                } else {
                    loadPosts(1);
                }
            }
        }

        // Track last notification count to detect new notifications
        var lastNotificationCount = 0;
        var notificationCheckInitialized = false;

        // Function to check for new notifications and refresh queue tab
        function checkNotificationsAndRefresh() {
            $.ajax({
                url: '{{ route('panel.notifications.fetch') }}',
                method: 'GET',
                success: function(response) {
                    if (response.success) {
                        var currentCount = response.count || 0;
                        if (!notificationCheckInitialized) {
                            notificationCheckInitialized = true;
                            lastNotificationCount = currentCount;
                            return;
                        }
                        // If notification count increased, refresh queue tab and status counts
                        if (currentCount > lastNotificationCount) {
                            reloadPosts();
                            if (typeof loadPostsStatusCounts === 'function') {
                                loadPostsStatusCounts();
                            }
                        }
                        lastNotificationCount = currentCount;
                    }
                },
                error: function(xhr) {}
            });
        }

        // Set up notification polling to refresh posts when new notifications arrive
        // Poll every 5 seconds (same as notification refresh interval)
        var notificationCheckInterval = setInterval(checkNotificationsAndRefresh, 5000);

        // Initial notification count fetch
        checkNotificationsAndRefresh();

        // delete post
        $(document).on('click', '.delete_btn', function() {
            if (confirm(
                    "Published post will be deleted from your account and Facebook. Do you wish to delete this post?")) {
                var id = $(this).data('id');
                $.ajax({
                    url: "{{ route('panel.schedule.post.delete') }}",
                    type: "GET",
                    data: { id: id },
                    success: function(response) {
                        if (response.success) {
                            reloadPosts();
                            toastr.success(response.message);
                        } else {
                            toastr.error(response.message);
                        }
                    }
                })
            } else {
                return;
            }
        });
        // edit post
        $(document).on('click', '.edit_btn', function() {
            var id = $(this).data('id');
            var modal = $('.edit-post-modal');
            modal.find(".modal-body").empty();
            modal.modal("toggle");
            $.ajax({
                url: "{{ route('panel.schedule.post.edit') }}",
                type: "GET",
                data: {
                    id: id,
                },
                success: function(response) {
                    if (response.success) {
                        modal.find("#update-post-form").attr("action", response.action);
                        modal.find(".modal-body").html(response.data);
                    } else {
                        toastr.error(response.message);
                    }
                }
            })
        });
        // image preview in edit form
        $(document).on('change', '#edit_post_publish_image', function() {
            const files = event.target.files;
            if (files.length > 0) {
                const file = files[0];
                if (file.type.match('image.*')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const dataURL = e.target.result;
                        $('#edit_post_image_preview')
                            .attr('src', dataURL)
                            .show();
                    };
                    reader.readAsDataURL(file);
                } else {
                    alert("Please select a valid image file.");
                }
            }
        });
        // update post
        $(document).on('submit', '#update-post-form', function(e) {
            event.preventDefault();
            var modal = $('.edit-post-modal');
            var date = modal.find('#edit_post_publish_date').val();
            var time = modal.find('#edit_post_publish_time').val();
            if (!checkPastDateTime(date, time)) {
                var url = $(this).attr("action");
                var formData = new FormData(this);
                formData.append("_token", "{{ csrf_token() }}");
                $.ajax({
                    url: url,
                    type: "POST",
                    processData: false,
                    contentType: false,
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            modal.modal("hide");
                            reloadPosts();
                            loadPostsStatusCounts();
                            toastr.success(response.message);
                        } else {
                            toastr.error(response.message);
                        }
                    }
                });
            }
        });
        // publish now
        $(document).on('click', '.publish_now_btn', function() {
            if (confirm("Do you wish to Publish this Post Now?")) {
                var id = $(this).data('id');
                $.ajax({
                    url: "{{ route('panel.schedule.post.publish.now') }}",
                    type: "POST",
                    data: {
                        id: id,
                    },
                    success: function(response) {
                        if (response.success) {
                            reloadPosts();
                            toastr.success(response.message);
                            loadPostsStatusCounts();
                            scheduleSentTabRefreshAfterPublish();
                        } else {
                            toastr.error(response.message);
                        }
                    }
                })
            } else {
                return;
            }
        });

        // Image Lightbox functionality for posts grid
        $(document).on('click',
            '.schedule-post-card .pinterest_card .image-container img.post-image, .schedule-post-card .facebook_card .pronunciation-image-container img',
            function(e) {
                e.preventDefault();
                e.stopPropagation();

                var imgSrc = $(this).attr('src');
                var imgAlt = $(this).attr('alt') || '';

                // Get post title from the card
                var $card = $(this).closest('.pinterest_card, .facebook_card');
                var caption = $card.find('.card-content span:last, .mb-3.px-3 span').first().text().trim();

                $('#lightboxImage').attr('src', imgSrc);
                $('#lightboxCaption').text(caption || imgAlt);
                $('#imageLightbox').addClass('active');

                // Prevent body scroll
                $('body').css('overflow', 'hidden');
            });

        // Close lightbox on close button click
        $('#lightboxClose').on('click', function() {
            closeLightbox();
        });

        // Close lightbox on backdrop click
        $('.lightbox-backdrop').on('click', function() {
            closeLightbox();
        });

        // Close lightbox on ESC key
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && $('#imageLightbox').hasClass('active')) {
                closeLightbox();
            }
        });

        function closeLightbox() {
            $('#imageLightbox').removeClass('active');
            $('body').css('overflow', '');
        }

        function showTikTokModal(file, accounts) {
            currentTikTokFile = file;
            currentTikTokAccounts = Array.isArray(accounts) ? accounts : [accounts];

            // Reset modal
            resetTikTokModal();

            // Set account ID (use first account)
            if (currentTikTokAccounts.length > 0) {
                $('#tiktok-account-id').val(currentTikTokAccounts[0].id);
            }

            // Determine post type
            var isVideo = file.type.startsWith('video/');
            $('#tiktok-post-type').val(isVideo ? 'video' : 'photo');

            // Show preview
            showTikTokPreview(file);

            // Display account names
            displayTikTokAccountNames(currentTikTokAccounts);

            // Populate form options
            populateTikTokFormOptions();

            // Show modal
            $('.tiktok-post-modal').modal('show');
        }

        function resetTikTokModal() {
            $('#tiktok-title').val('');
            $('#tiktok-privacy-level').val('').html('<option value="">-- Select Privacy Level --</option>');
            $('#tiktok-allow-comment').prop('checked', false);
            $('#tiktok-allow-duet').prop('checked', false);
            $('#tiktok-allow-stitch').prop('checked', false);
            $('#tiktok-commercial-toggle').prop('checked', false);
            $('#tiktok-your-brand').prop('checked', false);
            $('#tiktok-branded-content').prop('checked', false);
            $('#commercial-options').hide();
            $('#commercial-prompts').html('');
            $('#commercial-error').hide();
            $('#branded-content-privacy-warning').hide();
            $('#tiktok-publish-btn').prop('disabled', true);
            $('#tiktok-account-names').html('');
            $('#content-preview').hide();
            $('#preview-image').hide();
            $('#preview-video').hide();
            $('#preview-title').text('');
            $('#title-char-count').text('0');
            currentTikTokFile = null;
            currentTikTokLinkUrl = null;
            currentTikTokLinkImage = null;
            currentTikTokScheduleDate = null;
            currentTikTokScheduleTime = null;
        }

        // Show TikTok modal for link posts
        function showTikTokLinkModal(title, url, imageUrl, accounts, scheduleDate, scheduleTime) {
            currentTikTokAccounts = Array.isArray(accounts) ? accounts : [accounts];
            currentTikTokLinkUrl = url;
            currentTikTokLinkImage = imageUrl;
            currentTikTokScheduleDate = scheduleDate;
            currentTikTokScheduleTime = scheduleTime;

            // Set account ID (use first account)
            if (currentTikTokAccounts.length > 0) {
                $('#tiktok-account-id').val(currentTikTokAccounts[0].id);
            }

            // Set post type to photo (since links are converted to photos)
            $('#tiktok-post-type').val('photo');
            $('#tiktok-file-url').val(imageUrl); // Use fetched link image URL

            // Show link image preview
            $('#preview-image').show().find('img').attr('src', imageUrl);
            $('#preview-video').hide();
            $('#preview-title').text(title);
            $('#content-preview').show();

            // Pre-fill title with link URL (user can edit it)
            $('#tiktok-title').val(title);
            $('#title-char-count').text(title.length);

            // Display account names
            displayTikTokAccountNames(currentTikTokAccounts);

            // Populate form options
            populateTikTokFormOptions();

            // Show modal
            $('.tiktok-post-modal').modal('show');
        }

        function displayTikTokAccountNames(accounts) {
            var namesHtml = '';
            accounts.forEach(function(account) {
                var accountCard = $('.account-card[data-type="tiktok"][data-id="' + account.id + '"]');
                var accountName = accountCard.find('.account-name').text().trim() || account.name ||
                    'TikTok Account';
                var accountUsername = accountCard.find('.account-username').text().trim() || account
                    .username || '';

                namesHtml += '<div class="mb-1">';
                namesHtml += '<strong>' + accountName + '</strong>';
                if (accountUsername) {
                    namesHtml += ' <small class="text-muted">(@' + accountUsername + ')</small>';
                }
                namesHtml += '</div>';
            });
            $('#tiktok-account-names').html(namesHtml);
        }

        function populateTikTokFormOptions() {
            // Populate privacy options with defaults
            var select = $('#tiktok-privacy-level');
            select.html('<option value="">-- Select Privacy Level --</option>');
            var defaultOptions = ['PUBLIC_TO_EVERYONE', 'MUTUAL_FOLLOW_FRIENDS', 'FOLLOWER_OF_CREATOR',
                'SELF_ONLY'
            ];
            defaultOptions.forEach(function(option) {
                var label = option.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                select.append($('<option></option>').attr('value', option).text(label));
            });

            // Hide Duet and Stitch for photo posts
            var isPhoto = $('#tiktok-post-type').val() === 'photo';
            if (isPhoto) {
                $('#duet-container').hide();
                $('#stitch-container').hide();
            } else {
                $('#duet-container').show();
                $('#stitch-container').show();
            }

            // Check video duration if video
            if (!isPhoto && currentTikTokFile) {
                checkVideoDuration();
            }
        }

        function checkVideoDuration() {
            if ($('#tiktok-post-type').val() === 'video' && currentTikTokFile) {
                var video = document.createElement('video');
                video.preload = 'metadata';
                video.onloadedmetadata = function() {
                    window.URL.revokeObjectURL(video.src);
                    var duration = video.duration;
                    $('#tiktok-video-duration').val(duration);
                };
                video.src = URL.createObjectURL(currentTikTokFile);
            }
        }

        function showTikTokPreview(file) {
            var previewDiv = $('#content-preview');
            var previewImage = $('#preview-image');
            var previewVideo = $('#preview-video');
            var previewTitle = $('#preview-title');

            previewImage.hide();
            previewVideo.hide();

            if (file.type.startsWith('image/')) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    previewImage.find('img').attr('src', e.target.result);
                    previewImage.show();
                };
                reader.readAsDataURL(file);
            } else if (file.type.startsWith('video/')) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    previewVideo.find('video').attr('src', e.target.result);
                    previewVideo.show();
                };
                reader.readAsDataURL(file);
            }

            previewTitle.text(file.name);
            previewDiv.show();
        }


        // Commercial content toggle handler
        $('#tiktok-commercial-toggle').on('change', function() {
            if ($(this).is(':checked')) {
                $('#commercial-options').show();
                updateCommercialPrompts();
                validateTikTokForm();
            } else {
                $('#commercial-options').hide();
                $('#tiktok-your-brand').prop('checked', false);
                $('#tiktok-branded-content').prop('checked', false);
                updateCommercialPrompts();
                updateDeclaration();
                validateTikTokForm();
            }
        });

        $('#tiktok-your-brand, #tiktok-branded-content').on('change', function() {
            updateCommercialPrompts();
            updateDeclaration();
            validateTikTokForm();
        });

        function updateCommercialPrompts() {
            var yourBrand = $('#tiktok-your-brand').is(':checked');
            var brandedContent = $('#tiktok-branded-content').is(':checked');
            var prompts = $('#commercial-prompts');
            prompts.html('');

            if (yourBrand && !brandedContent) {
                prompts.html(
                    '<div class="alert alert-info"><i class="fas fa-info-circle"></i> Your photo/video will be labeled as \'Promotional content\'</div>'
                );
            } else if (!yourBrand && brandedContent) {
                prompts.html(
                    '<div class="alert alert-info"><i class="fas fa-info-circle"></i> Your photo/video will be labeled as \'Paid partnership\'</div>'
                );
            } else if (yourBrand && brandedContent) {
                prompts.html(
                    '<div class="alert alert-info"><i class="fas fa-info-circle"></i> Your photo/video will be labeled as \'Paid partnership\'</div>'
                );
            }
        }

        function updateDeclaration() {
            var commercialToggle = $('#tiktok-commercial-toggle').is(':checked');
            var yourBrand = $('#tiktok-your-brand').is(':checked');
            var brandedContent = $('#tiktok-branded-content').is(':checked');
            var declaration = $('#tiktok-declaration');

            if (commercialToggle && (yourBrand || brandedContent)) {
                if (brandedContent) {
                    declaration.html(
                        '<i class="fas fa-exclamation-circle"></i> <strong>By posting, you agree to TikTok\'s Branded Content Policy and Music Usage Confirmation</strong>'
                    );
                } else {
                    declaration.html(
                        '<i class="fas fa-exclamation-circle"></i> <strong>By posting, you agree to TikTok\'s Music Usage Confirmation</strong>'
                    );
                }
            } else {
                declaration.html(
                    '<i class="fas fa-exclamation-circle"></i> <strong>By posting, you agree to TikTok\'s Music Usage Confirmation</strong>'
                );
            }
        }

        // Privacy level change handler
        $('#tiktok-privacy-level').on('change', function() {
            validateTikTokForm();
            checkBrandedContentPrivacy();
        });

        function checkBrandedContentPrivacy() {
            var brandedContent = $('#tiktok-branded-content').is(':checked');
            var privacyLevel = $('#tiktok-privacy-level').val();
            var warning = $('#branded-content-privacy-warning');

            if (brandedContent && privacyLevel === 'SELF_ONLY') {
                warning.show();
                // Auto-switch to public
                $('#tiktok-privacy-level').val('PUBLIC_TO_EVERYONE');
            } else {
                warning.hide();
            }
        }

        // Title character count
        $('#tiktok-title').on('input', function() {
            var count = $(this).val().length;
            $('#title-char-count').text(count);
            validateTikTokForm();
        });

        function validateTikTokForm() {
            var isValid = true;

            // Check title
            var title = $('#tiktok-title').val().trim();
            if (!title) {
                isValid = false;
            }

            // Check privacy level
            var privacyLevel = $('#tiktok-privacy-level').val();
            if (!privacyLevel) {
                isValid = false;
            }

            // Check commercial content
            var commercialToggle = $('#tiktok-commercial-toggle').is(':checked');
            if (commercialToggle) {
                var yourBrand = $('#tiktok-your-brand').is(':checked');
                var brandedContent = $('#tiktok-branded-content').is(':checked');
                if (!yourBrand && !brandedContent) {
                    isValid = false;
                    $('#commercial-error').show();
                } else {
                    $('#commercial-error').hide();
                }
            }

            // Check branded content privacy
            if ($('#tiktok-branded-content').is(':checked') && $('#tiktok-privacy-level').val() ===
                'SELF_ONLY') {
                isValid = false;
            }

            $('#tiktok-publish-btn').prop('disabled', !isValid);

            return isValid;
        }

        // Publish button handler
        $('#tiktok-publish-btn').on('click', function() {
            if (!validateTikTokForm()) {
                toastr.error('Please fill in all required fields correctly');
                return;
            }

            var isLinkPost = currentTikTokLinkUrl && currentTikTokLinkImage;

            // Only require file for non-link posts
            if (!isLinkPost && !currentTikTokFile) {
                toastr.error('No file selected');
                return;
            }

            // Prepare form data
            var formData = new FormData();

            if (isLinkPost) {
                var title = $('#tiktok-title').val();
                var comment = $('#comment').val();
                formData.append('content', title); // Use title from modal textarea
                formData.append('comment', comment);
                formData.append('link', 1);
                formData.append('url', currentTikTokLinkUrl);
                formData.append('image', currentTikTokLinkImage);
                if (currentTikTokScheduleDate) {
                    formData.append('schedule_date', currentTikTokScheduleDate);
                }
                if (currentTikTokScheduleTime) {
                    formData.append('schedule_time', currentTikTokScheduleTime);
                }
            } else {
                formData.append('files', currentTikTokFile);
                formData.append('content', $('#tiktok-title').val());
            }

            formData.append('action', action_name);
            formData.append('tiktok_account_id', $('#tiktok-account-id').val());
            formData.append('tiktok_privacy_level', $('#tiktok-privacy-level').val());
            formData.append('tiktok_allow_comment', $('#tiktok-allow-comment').is(':checked') ? 1 : 0);
            formData.append('tiktok_allow_duet', $('#tiktok-allow-duet').is(':checked') ? 1 : 0);
            formData.append('tiktok_allow_stitch', $('#tiktok-allow-stitch').is(':checked') ? 1 : 0);
            formData.append('tiktok_commercial_toggle', $('#tiktok-commercial-toggle').is(':checked') ?
                1 : 0);
            formData.append('tiktok_your_brand', $('#tiktok-your-brand').is(':checked') ? 1 : 0);
            formData.append('tiktok_branded_content', $('#tiktok-branded-content').is(':checked') ? 1 :
                0);
            formData.append('video', $('#tiktok-post-type').val() === 'video' ? 1 : 0);

            // Add CSRF token
            formData.append('_token', '{{ csrf_token() }}');

            // Disable button
            $(this).prop('disabled', true).html(
                '<i class="fas fa-spinner fa-spin mr-2"></i>Publishing...');

            // Submit via AJAX
            $.ajax({
                url: '{{ route('panel.schedule.process.post') }}',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        $('.tiktok-post-modal').modal('hide');
                        currentTikTokLinkUrl = null;
                        currentTikTokLinkImage = null;
                        currentTikTokScheduleDate = null;
                        currentTikTokScheduleTime = null;
                        resetPostArea();
                    } else {
                        toastr.error(response.message);
                        $('#tiktok-publish-btn').prop('disabled', false).html(
                            '<i class="fas fa-paper-plane mr-2"></i>Publish');
                    }
                },
                error: function(xhr) {
                    var errorMsg = 'Failed to publish post';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg = xhr.responseJSON.message;
                    }
                    toastr.error(errorMsg);
                    $('#tiktok-publish-btn').prop('disabled', false).html(
                        '<i class="fas fa-paper-plane mr-2"></i>Publish');
                }
            });
        });

        // Initialize form validation on modal show
        $('.tiktok-post-modal').on('shown.bs.modal', function() {
            validateTikTokForm();
        });
    });
</script>
