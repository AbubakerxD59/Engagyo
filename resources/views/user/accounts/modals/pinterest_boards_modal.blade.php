<div class="modal fade" id="connectPinterestModal" tabindex="-1" data-backdrop="static" role="dialog"
    aria-labelledby="connectPinterestModal" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content connect-account-modal">
            <div class="modal-header">
                <div class="modal-header-content">
                    <div class="modal-title-wrapper">
                        <div class="platform-icon-wrapper pinterest-icon">
                            <i class="fab fa-pinterest-p"></i>
                        </div>
                        <div class="modal-title-info">
                            <h5 class="modal-title">{{ $pinterest->username }}</h5>
                            <p class="modal-subtitle">Select the boards you want to connect</p>
                        </div>
                    </div>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            </div>
            <div class="modal-body">
                <div class="accounts-list">
                    @foreach (session_get('items') as $key => $item)
                        <div class="account-item" data-item-key="{{ $key }}">
                            <div class="account-item-content">
                                <div class="account-avatar-section">
                                    @if (!empty($item['image_cover_url']) || !empty($item['cover_image_url']))
                                        <img src="{{ $item['image_cover_url'] ?? $item['cover_image_url'] }}" 
                                            alt="{{ $item['name'] }}" 
                                            class="account-item-avatar"
                                            onerror="this.onerror=null; this.src='{{ social_logo('pinterest') }}';">
                                    @else
                                        <div class="account-item-avatar-placeholder pinterest-placeholder">
                                            <i class="fab fa-pinterest-p"></i>
                                        </div>
                                    @endif
                                </div>
                                <div class="account-item-info">
                                    <h6 class="account-item-name">{{ $item['name'] }}</h6>
                                    <span class="account-item-type">Pinterest Board</span>
                                </div>
                            </div>
                            <div class="account-item-action">
                                @if (@$item['connected'])
                                    <button class="btn btn-connected" disabled>
                                        <i class="fas fa-check-circle"></i>
                                        <span>Connected</span>
                                    </button>
                                @else
                                    <button class="btn btn-connect pinterest_connect" 
                                        data-id="{{ $key }}"
                                        data-pin-id="{{ @$pinterest->pin_id }}"
                                        data-board-data="{{ json_encode($item) }}">
                                        <i class="fas fa-plus"></i>
                                        <span>Connect</span>
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary btn-continue" data-dismiss="modal">
                    <span>Continue</span>
                    <i class="fas fa-arrow-right ml-2"></i>
                </button>
            </div>
        </div>
    </div>
</div>
