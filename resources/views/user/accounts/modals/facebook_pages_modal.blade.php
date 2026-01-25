<div class="modal fade" id="connectFacebookModal" data-backdrop="static" tabindex="-1" role="dialog"
    aria-labelledby="connectFacebookModal" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content connect-account-modal">
            <div class="modal-header">
                <div class="modal-header-content">
                    <div class="modal-title-wrapper">
                        <div class="platform-icon-wrapper facebook-icon">
                            <i class="fab fa-facebook-f"></i>
                        </div>
                        @php $facebook = session_get('facebook'); @endphp
                        <div class="modal-title-info">
                            <h5 class="modal-title">{{ $facebook->username }}</h5>
                            <p class="modal-subtitle">Select the pages you want to connect</p>
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
                                    @if (!empty($item['profile_image']))
                                        <img src="{{ asset('images/' . $item['profile_image']) }}"
                                            alt="{{ $item['name'] }}" class="account-item-avatar"
                                            onerror="this.onerror=null; this.src='{{ social_logo('facebook') }}';">
                                    @else
                                        <div class="account-item-avatar-placeholder">
                                            <i class="fab fa-facebook-f"></i>
                                        </div>
                                    @endif
                                </div>
                                <div class="account-item-info">
                                    <h6 class="account-item-name">{{ $item['name'] }}</h6>
                                    <span class="account-item-type">Facebook Page</span>
                                </div>
                            </div>
                            <div class="account-item-action">
                                @if (!empty($item['connected']) && $item['connected'] === true)
                                    <button class="btn btn-connected" disabled>
                                        <i class="fas fa-check-circle"></i>
                                        <span>Connected</span>
                                    </button>
                                @else
                                    <button class="btn btn-connect facebook_connect" data-id="{{ $key }}"
                                        data-fb-id="{{ @$facebook->fb_id }}" data-page-data="{{ json_encode($item) }}">
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
