{{-- Create Post Modal - Design matches reference image 100% --}}
<div class="modal fade create-post-modal" id="createPostModal" tabindex="-1" role="dialog"
    aria-labelledby="createPostModalLabel" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-dialog-centered create-post-modal-dialog" role="document">
        <div class="modal-content create-post-modal-content">
            {{-- Dark grey header --}}
            {{-- <div class="create-post-modal-header">
                <div class="create-post-header-left">
                    <h5 class="create-post-modal-title" id="createPostModalLabel">Create Post</h5>
                </div>
                <div class="create-post-header-actions">
                    <button type="button" class="create-post-header-icon-btn create-post-close-btn"
                        data-dismiss="modal" aria-label="Close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div> --}}

            {{-- White content area --}}
            <div class="create-post-modal-body">
                <button type="button" class="create-post-body-close-btn create-post-close-btn" data-dismiss="modal"
                    aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
                {{-- Channel selection row --}}
                <div class="create-post-channels-row">
                    <div class="create-post-channels-dropdown-wrap">
                        <div class="create-post-selected-channels" id="createPostSelectedChannels">
                            {{-- Selected channel icons rendered by JS --}}
                        </div>
                        <button type="button" class="create-post-channels-btn" id="createPostChannelsBtn"
                            title="Add channel" aria-label="Add channel">
                            <i class="fas fa-plus"></i>
                        </button>
                        <div class="create-post-channels-dropdown" id="createPostChannelsDropdown">
                            <div class="channels-dropdown-search">
                                <i class="fas fa-search channels-dropdown-search-icon"></i>
                                <input type="text" class="channels-dropdown-search-input" id="channelsDropdownSearch"
                                    placeholder="Search channels" autocomplete="off">
                            </div>
                            <div class="channels-dropdown-header">
                                <span class="channels-dropdown-title">CHANNELS</span>
                                <button type="button" class="channels-dropdown-deselect"
                                    id="channelsDropdownDeselect">Deselect all</button>
                            </div>
                            <div class="channels-dropdown-list" id="channelsDropdownList">
                                @foreach ($accounts as $account)
                                    @php
                                        $type = $account->type ?? 'facebook';
                                    @endphp
                                    @if ($type === 'facebook')
                                        @php
                                            $name = $account->name ?? '';
                                            $username = $account->facebook?->username ?? '';
                                            $profileImg = $account->profile_image ?? '';
                                            $tooltip = $name . ($username ? ' - ' . $username : '');
                                        @endphp
                                        <div class="channels-dropdown-item" data-id="{{ $account->id }}"
                                            data-type="{{ $type }}"
                                            data-name="{{ Str::lower($name . ' ' . $username) }}"
                                            data-tooltip="{{ $tooltip }}">
                                            <div class="channels-dropdown-item-avatar">
                                                <img src="{{ $profileImg }}"
                                                    onerror="this.onerror=null; this.src='{{ social_logo($type) }}';"
                                                    loading="lazy" alt="">
                                                <span class="channels-dropdown-item-badge {{ $type }}"><i
                                                        class="fab fa-facebook-f"></i></span>
                                            </div>
                                            <span class="channels-dropdown-item-name">{{ $name }}</span>
                                            <label class="channels-dropdown-item-checkbox">
                                                <input type="checkbox" class="channels-dropdown-checkbox"
                                                    data-id="{{ $account->id }}" data-type="{{ $type }}"
                                                    data-schedule-status="{{ $account->schedule_status ?? 'inactive' }}">
                                                <span class="channels-dropdown-checkbox-icon"><i
                                                        class="fas fa-check"></i></span>
                                            </label>
                                        </div>
                                    @elseif ($type === 'pinterest')
                                        @php
                                            $name = $account->name ?? '';
                                            $username = $account->pinterest?->username ?? '';
                                            $profileImg = $account->pinterest?->profile_image ?? '';
                                            $tooltip = $name . ($username ? ' - ' . $username : '');
                                        @endphp
                                        <div class="channels-dropdown-item" data-id="{{ $account->id }}"
                                            data-type="{{ $type }}"
                                            data-name="{{ Str::lower($name . ' ' . $username) }}"
                                            data-tooltip="{{ $tooltip }}">
                                            <div class="channels-dropdown-item-avatar">
                                                <img src="{{ $profileImg }}"
                                                    onerror="this.onerror=null; this.src='{{ social_logo('pinterest') }}';"
                                                    loading="lazy" alt="">
                                                <span class="channels-dropdown-item-badge pinterest"><i
                                                        class="fab fa-pinterest-p"></i></span>
                                            </div>
                                            <span class="channels-dropdown-item-name">{{ $name }}</span>
                                            <label class="channels-dropdown-item-checkbox">
                                                <input type="checkbox" class="channels-dropdown-checkbox"
                                                    data-id="{{ $account->id }}" data-type="{{ $type }}"
                                                    data-schedule-status="{{ $account->schedule_status ?? 'inactive' }}">
                                                <span class="channels-dropdown-checkbox-icon"><i
                                                        class="fas fa-check"></i></span>
                                            </label>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                        <div class="create-post-last-used" id="createPostLastUsed" title="Last used"
                            style="display: none;">
                            {{-- Rendered by JS --}}
                        </div>
                    </div>
                </div>

                {{-- Main content area --}}
                <div class="create-post-main-content" id="createPostMainContent">
                    <div class="create-post-empty-state" id="createPostEmptyState">
                        <p class="create-post-empty-message">Your work has been saved. Select a Channel to create a
                            post.</p>
                    </div>
                    <div class="create-post-editor-wrap" id="createPostEditorWrap" style="display: none;">
                        <div class="create-post-facebook-format-wrap" id="createPostFacebookFormatWrap"
                            style="display: none;" role="group" aria-label="Facebook content format">
                            <span class="create-post-facebook-format-label">Facebook format</span>
                            <div class="create-post-facebook-format-radios">
                                <label class="create-post-format-option">
                                    <input type="radio" name="create_post_facebook_format"
                                        id="createPostFormatPost" value="post" checked>
                                    <span>Post</span>
                                </label>
                                <label class="create-post-format-option">
                                    <input type="radio" name="create_post_facebook_format"
                                        id="createPostFormatReel" value="reel">
                                    <span>Reel</span>
                                </label>
                                <label class="create-post-format-option">
                                    <input type="radio" name="create_post_facebook_format"
                                        id="createPostFormatStory" value="story">
                                    <span>Story</span>
                                </label>
                            </div>
                        </div>
                        <textarea class="create-post-editor-textarea" id="createPostEditorTextarea"
                            placeholder="Paste your link or write something..."></textarea>
                        <div class="create-post-editor-bottom">
                            <div id="createPostLinkPreview" class="create-post-link-preview"></div>
                            <input type="file" id="createPostFileInput"
                                accept="image/jpeg,image/jpg,image/png,image/bmp,image/gif,image/tiff,image/webp,video/mp4,video/x-matroska,video/quicktime,video/mpeg,video/webm"
                                multiple hidden>
                            <div class="create-post-emoji-trigger-wrap">
                                <button type="button" class="create-post-action-btn" id="createPostEmojiBtn"
                                    title="Emoji" aria-label="Emoji"><i class="far fa-smile"></i></button>
                                <div class="create-post-emoji-picker-wrap" id="createPostEmojiPickerWrap"></div>
                            </div>
                            <div class="create-post-upload-zone" id="createPostUploadZone">
                                <div class="create-post-upload-previews" id="createPostUploadPreviews"></div>
                                <div class="create-post-upload-prompt" id="createPostUploadPrompt">
                                    <div class="create-post-upload-icon"><i class="fas fa-image"></i></div>
                                    <p class="create-post-upload-text">Drag & drop or <span
                                            class="create-post-upload-link" id="createPostUploadLink">select a
                                            file</span></p>
                                </div>
                            </div>
                            <div class="create-post-editor-actions">
                                <div id="createPostCommentWrap"
                                    class="create-post-comment-wrap create-post-comment-facebook"
                                    style="display: none;">
                                    <hr class="create-post-comment-hr">
                                    <textarea class="create-post-comment-input" id="createPostComment" placeholder="Comment here!" rows="1"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Dark grey footer --}}
            <div class="create-post-modal-footer">
                <div class="create-post-footer-left"></div>
                <div class="create-post-footer-right create-post-footer-actions-wrap">
                    <div class="create-post-segmented-buttons">
                        <button type="button"
                            class="create-post-segmented-btn action_btn create-post-schedule-trigger"
                            href="schedule">Schedule</button>
                        <button type="button" class="create-post-segmented-btn action_btn"
                            href="queue">Queue</button>
                        <button type="button"
                            class="create-post-segmented-btn create-post-segmented-btn-primary action_btn"
                            href="publish">Publish Now</button>
                        <button type="button" class="create-post-segmented-btn create-post-draft-btn action_btn"
                            href="draft" style="display: none;">Draft</button>
                    </div>
                    <div class="create-post-schedule-dropdown" id="createPostScheduleDropdown">
                        <div class="create-post-schedule-picker">
                            <div class="create-post-schedule-row">
                                <div class="create-post-schedule-field">
                                    <label for="createPostScheduleDate">Date</label>
                                    <input type="date" id="createPostScheduleDate"
                                        class="create-post-schedule-input" min="{{ date('Y-m-d') }}"
                                        value="{{ date('Y-m-d') }}">
                                </div>
                                <div class="create-post-schedule-field">
                                    <label for="createPostScheduleTime">Time</label>
                                    <input type="time" id="createPostScheduleTime"
                                        class="create-post-schedule-input">
                                </div>
                            </div>
                            <button type="button"
                                class="create-post-schedule-confirm-btn create-post-schedule-confirm">Schedule</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
