<div class="modal fade tiktok-post-modal" tabindex="-1" aria-labelledby="tiktokPostModalLabel" style="display: none;"
    aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tiktokPostModalLabel">Post to TikTok</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Creator Info Display -->
                <div class="creator-info-section mb-3 p-3 bg-light rounded">
                    <div class="d-flex align-items-center">
                        <img id="creator-avatar" src="" alt="Creator Avatar" class="rounded-circle mr-2" style="width: 40px; height: 40px; display: none;">
                        <div>
                            <small class="text-muted d-block">Posting to:</small>
                            <strong id="creator-nickname">Loading...</strong>
                        </div>
                    </div>
                    <div id="creator-error" class="alert alert-danger mt-2" style="display: none;"></div>
                </div>

                <!-- Content Preview -->
                <div class="content-preview-section mb-3" id="content-preview" style="display: none;">
                    <label class="font-weight-bold">Content Preview:</label>
                    <div class="preview-container border rounded p-2">
                        <div id="preview-image" class="text-center mb-2" style="display: none;">
                            <img src="" alt="Preview" class="img-fluid" style="max-height: 200px;">
                        </div>
                        <div id="preview-video" class="text-center mb-2" style="display: none;">
                            <video src="" controls class="img-fluid" style="max-height: 200px;"></video>
                        </div>
                        <div id="preview-title" class="text-muted"></div>
                    </div>
                </div>

                <!-- Title Field -->
                <div class="form-group">
                    <label for="tiktok-title">Title <span class="text-danger">*</span></label>
                    <textarea name="tiktok-title" id="tiktok-title" class="form-control" rows="3" 
                        placeholder="Enter your post caption (max 2200 characters)" maxlength="2200"></textarea>
                    <small class="text-muted">
                        <span id="title-char-count">0</span>/2200 characters
                    </small>
                </div>

                <!-- Privacy Level -->
                <div class="form-group">
                    <label for="tiktok-privacy-level">Privacy Status <span class="text-danger">*</span></label>
                    <select name="tiktok-privacy-level" id="tiktok-privacy-level" class="form-control" required>
                        <option value="">-- Select Privacy Level --</option>
                    </select>
                    <small class="text-muted">You must manually select a privacy level</small>
                </div>

                <!-- Interaction Settings -->
                <div class="form-group">
                    <label class="font-weight-bold">Interaction Settings</label>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="tiktok-allow-comment" name="tiktok-allow-comment">
                        <label class="form-check-label" for="tiktok-allow-comment">
                            Allow Comment
                        </label>
                    </div>
                    <div class="form-check" id="duet-container">
                        <input class="form-check-input" type="checkbox" id="tiktok-allow-duet" name="tiktok-allow-duet">
                        <label class="form-check-label" for="tiktok-allow-duet">
                            Allow Duet
                        </label>
                    </div>
                    <div class="form-check" id="stitch-container">
                        <input class="form-check-input" type="checkbox" id="tiktok-allow-stitch" name="tiktok-allow-stitch">
                        <label class="form-check-label" for="tiktok-allow-stitch">
                            Allow Stitch
                        </label>
                    </div>
                    <small class="text-muted d-block mt-1">All interaction settings are off by default. You must manually enable them.</small>
                </div>

                <!-- Commercial Content Disclosure -->
                <div class="form-group">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="tiktok-commercial-toggle" name="tiktok-commercial-toggle">
                        <label class="form-check-label font-weight-bold" for="tiktok-commercial-toggle">
                            Content Disclosure Setting
                        </label>
                        <small class="text-muted d-block">Indicate whether this content promotes yourself, a brand, product or service</small>
                    </div>

                    <!-- Commercial Content Options (shown when toggle is on) -->
                    <div id="commercial-options" style="display: none;" class="mt-3 pl-4 border-left">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="tiktok-your-brand" name="tiktok-your-brand">
                            <label class="form-check-label" for="tiktok-your-brand">
                                Your Brand
                            </label>
                            <small class="text-muted d-block">You are promoting yourself or your own business. This content will be classified as Brand Organic.</small>
                        </div>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" id="tiktok-branded-content" name="tiktok-branded-content">
                            <label class="form-check-label" for="tiktok-branded-content">
                                Branded Content
                            </label>
                            <small class="text-muted d-block">You are promoting another brand or a third party. This content will be classified as Branded Content.</small>
                        </div>
                        <div id="commercial-prompts" class="mt-2"></div>
                        <div id="commercial-error" class="alert alert-warning mt-2" style="display: none;">
                            <i class="fas fa-info-circle"></i> You need to indicate if your content promotes yourself, a third party, or both.
                        </div>
                    </div>
                </div>

                <!-- Privacy Management for Branded Content -->
                <div id="branded-content-privacy-warning" class="alert alert-info" style="display: none;">
                    <i class="fas fa-info-circle"></i> Branded content visibility cannot be set to private. Privacy will be automatically set to public.
                </div>

                <!-- Declaration -->
                <div class="form-group">
                    <div class="alert alert-info" id="tiktok-declaration">
                        <i class="fas fa-exclamation-circle"></i>
                        <strong>By posting, you agree to TikTok's Music Usage Confirmation</strong>
                    </div>
                </div>

                <!-- Processing Time Notification -->
                <div class="alert alert-warning">
                    <i class="fas fa-clock"></i>
                    <strong>Note:</strong> After you finish publishing your content, it may take a few minutes for the content to process and be visible on your profile.
                </div>

                <!-- Hidden Fields -->
                <input type="hidden" id="tiktok-account-id" name="tiktok-account-id">
                <input type="hidden" id="tiktok-post-type" name="tiktok-post-type">
                <input type="hidden" id="tiktok-file-url" name="tiktok-file-url">
                <input type="hidden" id="tiktok-video-duration" name="tiktok-video-duration">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-danger" data-dismiss="modal" aria-label="Close">
                    Cancel
                </button>
                <button type="button" class="btn btn-outline-primary" id="tiktok-publish-btn" disabled>
                    <i class="fas fa-paper-plane mr-2"></i>Publish
                </button>
            </div>
        </div>
    </div>
</div>

