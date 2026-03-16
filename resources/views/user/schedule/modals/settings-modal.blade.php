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

                <div class="queue-settings-list">
                    @foreach ($accounts as $account)
                        <div class="queue-settings-item">
                            <div class="queue-settings-account">
                                <div class="queue-settings-avatar-wrap">
                                    @if ($account->type == 'facebook')
                                        <img class="queue-settings-avatar" src="{{ $account->profile_image }}"
                                            onerror="this.onerror=null; this.src='{{ social_logo('facebook') }}';" alt="" loading="lazy">
                                        <span class="queue-settings-platform-badge queue-settings-badge-facebook"><i class="fab fa-facebook-f"></i></span>
                                    @elseif($account->type == 'pinterest')
                                        <img class="queue-settings-avatar" src="{{ $account->pinterest?->profile_image }}"
                                            onerror="this.onerror=null; this.src='{{ social_logo('pinterest') }}';" alt="" loading="lazy">
                                        <span class="queue-settings-platform-badge queue-settings-badge-pinterest"><i class="fab fa-pinterest-p"></i></span>
                                    @elseif($account->type == 'tiktok')
                                        <img class="queue-settings-avatar" src="{{ $account->profile_image }}"
                                            onerror="this.onerror=null; this.src='{{ social_logo('tiktok') }}';" alt="" loading="lazy">
                                        <span class="queue-settings-platform-badge queue-settings-badge-tiktok"><i class="fab fa-tiktok"></i></span>
                                    @endif
                                </div>
                                <div class="queue-settings-account-info">
                                    <span class="queue-settings-account-name">{{ $account->name }}</span>
                                    <span class="queue-settings-account-type">
                                        @if ($account->type == 'facebook')
                                            {{ $account->facebook?->username ?? 'Facebook' }}
                                        @elseif($account->type == 'pinterest')
                                            {{ $account->pinterest?->username ?? 'Pinterest' }}
                                        @elseif($account->type == 'tiktok')
                                            {{ $account->username ?? $account->display_name ?? 'TikTok' }}
                                        @else
                                            {{ ucfirst($account->type) }}
                                        @endif
                                    </span>
                                </div>
                            </div>
                            @if ($account->type == 'facebook')
                            <div class="queue-settings-shuffle">
                                <label class="queue-settings-shuffle-label">
                                    <i class="fas fa-random"></i> Daily shuffle
                                </label>
                                <label class="queue-settings-shuffle-switch">
                                    <input type="checkbox" class="queue-settings-shuffle-input"
                                        data-id="{{ $account->id }}" data-type="{{ $account->type }}"
                                        {{ ($account->schedule_shuffle ?? 0) ? 'checked' : '' }}>
                                    <span class="queue-settings-shuffle-slider"></span>
                                </label>
                            </div>
                            @endif
                            <div class="queue-settings-hours">
                                <label class="queue-settings-hours-label">
                                    <i class="far fa-clock"></i> Posting hours
                                </label>
                                @php $account_timeslots = $account->timeslots->pluck("time")->toArray(); @endphp
                                <select name="time[]" class="form-control select2 timeslot queue-settings-select"
                                    data-id="{{ $account->id }}" data-type="{{ $account->type }}" multiple>
                                    @foreach (timeslots() as $timeslot)
                                        <option value="{{ $timeslot }}"
                                            {{ in_array($timeslot, $account_timeslots) ? 'selected' : '' }}>
                                            {{ $timeslot }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    @endforeach
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
