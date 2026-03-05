<div class="modal fade settings-modal queue-settings-modal-redesign" tabindex="-1" aria-labelledby="queueSettingsModalTitle"
    aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content queue-settings-modal-content">
            <div class="modal-header queue-settings-modal-header">
                <h5 class="modal-title queue-settings-modal-title" id="queueSettingsModalTitle">Queue settings</h5>
                <button type="button" class="btn btn-icon queue-settings-modal-close" data-dismiss="modal" aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="queue-settings-modal-body">
                <p class="queue-settings-modal-description">Choose the hours when posts can be added to the queue for each channel.</p>
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
                                <span class="queue-settings-account-name">{{ $account->name }}</span>
                            </div>
                            <div class="queue-settings-hours">
                                <label class="queue-settings-hours-label">Posting hours</label>
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
                <button type="button" class="btn btn-outline-secondary queue-settings-btn-cancel" data-dismiss="modal">
                    Cancel
                </button>
                <button type="button" class="btn btn-primary queue-settings-btn-save" id="saveQueueSettings" style="display: none;">
                    <i class="fas fa-save mr-2"></i>Save changes
                </button>
            </div>
        </div>
    </div>
</div>
