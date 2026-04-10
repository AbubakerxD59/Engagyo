{{-- Queue New Post: same layout as Create Post, single account from sidebar only (no channel picker) --}}
<div class="modal fade create-post-modal queue-new-post-modal" id="queueNewPostModal" tabindex="-1" role="dialog"
    aria-labelledby="queueNewPostModalLabel" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-dialog-centered create-post-modal-dialog" role="document">
        <div class="modal-content create-post-modal-content">
            <div class="create-post-modal-body">
                <button type="button" class="create-post-body-close-btn create-post-close-btn" data-dismiss="modal"
                    aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
                {{-- Same spacing/row pattern as Create Post modal channel row --}}
                <div class="create-post-channels-row queue-new-post-top-bar">
                    <div class="queue-new-post-top-left">
                        <div class="create-post-channels-dropdown-wrap queue-new-post-single-channel-wrap">
                            <div class="create-post-channels-real">
                                <div class="create-post-selected-channels" id="queueNewPostSelectedChannels">
                                    {{-- Single sidebar account as create-post-selected-channel-chip (JS) --}}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="queue-new-post-slot-info" id="queueNewPostSlotInfo" aria-live="polite">
                        <span class="queue-new-post-slot-label" id="queueNewPostSlotLabel">Queued for</span>
                        <span class="queue-new-post-slot-datetime" id="queueNewPostSlotDatetime">—</span>
                    </div>
                </div>

                <div class="create-post-main-content" id="queueNewPostMainContent">
                    <div class="create-post-empty-state" id="queueNewPostEmptyState">
                        <p class="create-post-empty-message">Select a single account in the sidebar (not All channels) to
                            queue a post for that account.</p>
                    </div>
                    <div class="create-post-editor-wrap" id="queueNewPostEditorWrap" style="display: none;">
                        <div>
                            <div class="create-post-facebook-format-wrap" id="queueNewPostFacebookFormatWrap"
                                style="display: none;" role="group" aria-label="Facebook content format">
                                <div class="create-post-facebook-format-bar">
                                    <span class="create-post-facebook-format-facebook-icon" aria-hidden="true"><i
                                            class="fab fa-facebook-f"></i></span>
                                    <div class="create-post-facebook-format-radios">
                                        <label class="create-post-format-option">
                                            <input type="checkbox" name="create_post_facebook_formats[]"
                                                id="queueNewPostFormatPost" value="post" checked>
                                            <span>Post</span>
                                        </label>
                                        <label class="create-post-format-option">
                                            <input type="checkbox" name="create_post_facebook_formats[]"
                                                id="queueNewPostFormatReel" value="reel">
                                            <span>Reel</span>
                                        </label>
                                        <label class="create-post-format-option">
                                            <input type="checkbox" name="create_post_facebook_formats[]"
                                                id="queueNewPostFormatStory" value="story">
                                            <span>Story</span>
                                        </label>
                                    </div>
                                    <div class="create-post-format-help-wrap" id="queueNewPostFormatHelpWrap">
                                        <button type="button" class="create-post-format-help-btn"
                                            id="queueNewPostFormatHelpBtn">
                                            <i class="far fa-question-circle"></i>
                                        </button>
                                        <div class="create-post-format-help-popover" role="tooltip"
                                            aria-hidden="true">
                                            <ul class="create-post-format-help-list">
                                                <li><strong>Files</strong>: Image / Video files.
                                                </li>
                                                <li><strong>Post</strong>: Caption + Files + optional first comment.
                                                </li>
                                                <li><strong>Reel</strong>: Caption + Files only.</li>
                                                <li><strong>Story</strong>: Files only.</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="create-post-facebook-format-wrap" id="queueNewPostInstagramFormatWrap"
                                style="display: none;" role="group" aria-label="Instagram content format">
                                <div class="create-post-facebook-format-bar">
                                    <span class="create-post-facebook-format-facebook-icon" aria-hidden="true"><i
                                            class="fab fa-instagram"></i></span>
                                    <div class="create-post-facebook-format-radios">
                                        <label class="create-post-format-option">
                                            <input type="radio" name="create_post_instagram_format"
                                                id="queueNewPostInstagramFormatPost" value="post" checked>
                                            <span>Post</span>
                                        </label>
                                        <label class="create-post-format-option">
                                            <input type="radio" name="create_post_instagram_format"
                                                id="queueNewPostInstagramFormatReel" value="reel">
                                            <span>Reel</span>
                                        </label>
                                        <label class="create-post-format-option">
                                            <input type="radio" name="create_post_instagram_format"
                                                id="queueNewPostInstagramFormatCarousel" value="carousel">
                                            <span>Carousel</span>
                                        </label>
                                    </div>
                                    <div class="create-post-format-help-wrap" id="queueNewPostInstagramFormatHelpWrap">
                                        <button type="button" class="create-post-format-help-btn"
                                            id="queueNewPostInstagramFormatHelpBtn">
                                            <i class="far fa-question-circle"></i>
                                        </button>
                                        <div class="create-post-format-help-popover" role="tooltip"
                                            aria-hidden="true">
                                            <ul class="create-post-format-help-list">
                                                <li><strong>Post</strong>: One image or video per publish.</li>
                                                <li><strong>Reel</strong>: One video (shown when you add a video).</li>
                                                <li><strong>Carousel</strong>: 2–10 images and/or videos in one post.</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <textarea class="create-post-editor-textarea" id="queueNewPostEditorTextarea"
                                placeholder="Paste your link or write something..."></textarea>
                        </div>
                        <div class="create-post-editor-bottom">
                            <div id="queueNewPostLinkPreview" class="create-post-link-preview"></div>
                            <input type="file" id="queueNewPostFileInput"
                                accept="image/jpeg,image/jpg,image/png,image/bmp,image/gif,image/tiff,image/webp,video/mp4,video/x-matroska,video/quicktime,video/mpeg,video/webm"
                                multiple hidden>
                            <div class="create-post-emoji-trigger-wrap">
                                <button type="button" class="create-post-action-btn" id="queueNewPostEmojiBtn"
                                    title="Emoji" aria-label="Emoji"><i class="far fa-smile"></i></button>
                                <div class="create-post-emoji-picker-wrap" id="queueNewPostEmojiPickerWrap"></div>
                            </div>
                            <div class="create-post-upload-zone" id="queueNewPostUploadZone">
                                <div class="create-post-upload-previews" id="queueNewPostUploadPreviews"></div>
                                <div class="create-post-upload-prompt" id="queueNewPostUploadPrompt">
                                    <div class="create-post-upload-icon"><i class="fas fa-image"></i></div>
                                    <p class="create-post-upload-text">Drag & drop or <span
                                            class="create-post-upload-link" id="queueNewPostUploadLink">select a
                                            file</span></p>
                                </div>
                            </div>
                            <div id="queueNewPostTikTokSettingsWrap" class="mt-3" style="display:none;">
                                <div class="border rounded p-3" style="background:#f8fafc;color:#1f2937;border-color:#e5e7eb !important;">
                                    <h6 class="mb-3" style="color:#111827;">TikTok Post Settings</h6>
                                    <div class="mb-2 small" style="color:#4b5563;">
                                        Posting as:
                                        <strong id="queueNewPostTikTokCreatorNickname">—</strong>
                                    </div>
                                    <div id="queueNewPostTikTokError" class="alert alert-danger py-2 px-3 mb-3"
                                        style="display:none;"></div>
                                    <div class="form-group mb-3">
                                        <label for="queueNewPostTikTokPrivacyLevel" class="mb-1" style="color:#374151;">Who can view this post?</label>
                                        <select id="queueNewPostTikTokPrivacyLevel" class="form-control w-50">
                                            <option value="">Select privacy</option>
                                        </select>
                                    </div>
                                    <div class="form-group mb-2">
                                        <label class="d-block mb-2" style="color:#374151;">Allow users to</label>
                                        <label class="mb-0 d-flex align-items-center">
                                            <input type="checkbox" id="queueNewPostTikTokAllowComment" class="mr-2">
                                            <span>Comment</span>
                                        </label>
                                    </div>
                                    <div class="form-group mb-2">
                                        <label class="mb-0 d-flex align-items-center">
                                            <input type="checkbox" id="queueNewPostTikTokCommercialToggle" class="mr-2">
                                            <span>Disclose post content</span>
                                            <div class="create-post-format-help-wrap ml-2">
                                                <button type="button" class="create-post-format-help-btn"
                                                    aria-label="Disclose post content help">
                                                    <i class="fas fa-question-circle"></i>
                                                </button>
                                                <div class="create-post-format-help-popover" role="tooltip"
                                                    aria-hidden="true">
                                                    <ul class="create-post-format-help-list">
                                                        <li>Branded content visibility cannot be set to private.</li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                    <div id="queueNewPostTikTokCommercialOptions" class="form-group mb-2 pl-4" style="display:none;">
                                        <label class="mb-0 d-flex align-items-center">
                                            <input type="checkbox" id="queueNewPostTikTokYourBrand" class="mr-2">
                                            <span>Your brand</span>
                                            <div class="create-post-format-help-wrap ml-2">
                                                <button type="button" class="create-post-format-help-btn"
                                                    aria-label="Your brand help">
                                                    <i class="fas fa-question-circle"></i>
                                                </button>
                                                <div class="create-post-format-help-popover" role="tooltip"
                                                    aria-hidden="true">
                                                    <ul class="create-post-format-help-list">
                                                        <li>Your photo/video will be labeled as 'Promotional content'</li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </label>
                                        <label class="mt-2 mb-0 d-flex align-items-center">
                                            <input type="checkbox" id="queueNewPostTikTokBrandedContent" class="mr-2">
                                            <span>Branded content</span>
                                            <div class="create-post-format-help-wrap ml-2">
                                                <button type="button" class="create-post-format-help-btn"
                                                    aria-label="Branded content help">
                                                    <i class="fas fa-question-circle"></i>
                                                </button>
                                                <div class="create-post-format-help-popover" role="tooltip"
                                                    aria-hidden="true">
                                                    <ul class="create-post-format-help-list">
                                                        <li>Your photo/video will be labeled as 'Paid partnership'</li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                    <div class="form-group mb-2">
                                        <label class="mb-0 d-flex align-items-center">
                                            <input type="checkbox" id="queueNewPostTikTokAddMusic" class="mr-2">
                                            <span>Add recommended music from TikTok</span>
                                        </label>
                                    </div>
                                    <small class="d-block mt-2" style="color:#4b5563;">
                                        By posting, you agree to TikTok's
                                        <span id="queueNewPostTikTokPolicyPrefix" style="display:none;"><a href="https://www.tiktok.com/legal/page/global/bc-policy/en" target="_blank" rel="noopener noreferrer">Branded Content Policy</a> and </span>
                                        <a href="https://www.tiktok.com/legal/page/global/music-usage-confirmation/en"
                                            target="_blank" rel="noopener noreferrer">Music Usage Confirmation</a>.
                                    </small>
                                    <label class="mb-0 d-flex align-items-center mt-2" style="color:#374151;">
                                        <input type="checkbox" id="queueNewPostTikTokConsentConfirm" class="mr-2">
                                        <span>I agree and consent to upload this content to TikTok.</span>
                                    </label>
                                </div>
                            </div>
                            <div class="create-post-editor-actions">
                                <div id="queueNewPostCommentWrap"
                                    class="create-post-comment-wrap create-post-comment-facebook"
                                    style="display: none;">
                                    <hr class="create-post-comment-hr">
                                    <textarea class="create-post-comment-input" id="queueNewPostComment" placeholder="Comment here!" rows="1"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

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
                    <div class="create-post-schedule-dropdown" id="queueNewPostScheduleDropdown">
                        <div class="create-post-schedule-picker">
                            <div class="create-post-schedule-row">
                                <div class="create-post-schedule-field">
                                    <label for="queueNewPostScheduleDate">Date</label>
                                    <input type="date" id="queueNewPostScheduleDate"
                                        class="create-post-schedule-input" min="{{ date('Y-m-d') }}"
                                        value="{{ date('Y-m-d') }}">
                                </div>
                                <div class="create-post-schedule-field">
                                    <label for="queueNewPostScheduleTime">Time</label>
                                    <input type="time" id="queueNewPostScheduleTime"
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
