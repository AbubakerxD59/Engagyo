<div class="modal fade settings-modal queue-settings-modal-redesign" tabindex="-1" aria-labelledby="queueSettingsModalTitle"
    aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content queue-settings-modal-content">
            <div class="modal-header queue-settings-modal-header">
                <div class="queue-settings-header-left">
                    <div class="queue-settings-header-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div>
                        <h5 class="modal-title queue-settings-modal-title" id="queueSettingsModalTitle">Queue Settings</h5>
                        <p class="queue-settings-modal-subtitle">Configure posting schedules for your channels</p>
                    </div>
                </div>
                <button type="button" class="btn btn-icon queue-settings-modal-close" data-dismiss="modal" aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="queue-settings-modal-body">
                <div class="queue-settings-info-bar">
                    <i class="fas fa-info-circle"></i>
                    <span>Select the hours when posts should be published for each connected channel.</span>
                </div>

                <div id="queue-settings-content-wrap" class="queue-settings-content-wrap">
                    <div id="queue-settings-skeleton" class="queue-settings-skeleton" style="display: none;">
                        <div class="queue-settings-skeleton-item">
                            <div class="queue-settings-skeleton-avatar"></div>
                            <div class="queue-settings-skeleton-info">
                                <div class="queue-settings-skeleton-line queue-settings-skeleton-name"></div>
                                <div class="queue-settings-skeleton-line queue-settings-skeleton-sub"></div>
                            </div>
                            <div class="queue-settings-skeleton-select"></div>
                        </div>
                        <div class="queue-settings-skeleton-item">
                            <div class="queue-settings-skeleton-avatar"></div>
                            <div class="queue-settings-skeleton-info">
                                <div class="queue-settings-skeleton-line queue-settings-skeleton-name"></div>
                                <div class="queue-settings-skeleton-line queue-settings-skeleton-sub"></div>
                            </div>
                            <div class="queue-settings-skeleton-select"></div>
                        </div>
                        <div class="queue-settings-skeleton-item">
                            <div class="queue-settings-skeleton-avatar"></div>
                            <div class="queue-settings-skeleton-info">
                                <div class="queue-settings-skeleton-line queue-settings-skeleton-name"></div>
                                <div class="queue-settings-skeleton-line queue-settings-skeleton-sub"></div>
                            </div>
                            <div class="queue-settings-skeleton-select"></div>
                        </div>
                    </div>
                    <div id="queue-settings-list-wrap" class="queue-settings-list-wrap" style="display: none;">
                        <div class="queue-settings-list" id="queue-settings-list"></div>
                    </div>
                </div>
            </div>

            <div class="modal-footer queue-settings-modal-footer">
                <button type="button" class="btn queue-settings-btn-cancel" data-dismiss="modal">
                    Cancel
                </button>
                <button type="button" class="btn queue-settings-btn-save" id="saveQueueSettings" style="display: none;">
                    <i class="fas fa-check mr-2"></i>Save Changes
                </button>
            </div>
        </div>
    </div>
</div>
