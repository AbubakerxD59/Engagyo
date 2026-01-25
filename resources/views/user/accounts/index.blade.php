@extends('user.layout.main')
@section('title', 'Accounts')
@section('page_content')
    <div class="page-content">
        @include('user.layout.feature-limit-alert')
        <div class="content-header clearfix"></div>
        <section class="content">
            <div class="container-fluid">
                {{-- Facebook --}}
                <div class="card platform-card">
                    <div class="card-header with-border clearfix">
                        <div class="card-title">
                            <input type="hidden" id="facebookAcc" value="{{ session_check('facebook_auth') ? 1 : 0 }}">
                            <img src="{{ social_logo('facebook') }}">
                            <span>Facebook</span>
                        </div>
                        <a href="{{ $facebookUrl }}" class="btn btn-outline-primary btn-sm mx-2">+ Connect</a>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="accounts-grid-wrapper">
                            <div class="accounts-grid" data-platform="facebook">
                                @forelse ($user->facebook as $index => $fb)
                                    <article class="account-card facebook-card has-tooltip" @style(['display:none;' => $index >= 12])
                                        data-tooltip="{{ $fb->username }}" data-index="{{ $index }}">
                                        <div class="account-card-accent"></div>
                                        <a href="{{ route('panel.accounts.facebook', $fb->fb_id) }}"
                                            class="account-card-link">
                                            <div class="account-card-content">
                                                <div class="account-avatar-wrapper">
                                                    <img src="{{ $fb->profile_image }}" class="account-avatar"
                                                        onerror="this.onerror=null; this.src='{{ social_logo('facebook') }}';">
                                                    <span class="platform-indicator facebook-indicator">
                                                        <i class="fab fa-facebook-f"></i>
                                                    </span>
                                                </div>
                                                <div class="account-info">
                                                    <div class="account-username">
                                                        {{ Str::limit($fb->username, 12) }}
                                                    </div>
                                                </div>
                                            </div>
                                        </a>
                                        <div class="account-card-actions">
                                            <button class="btn-account-delete delete-btn" title="Delete Account">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                            <form action="{{ route('panel.accounts.facebook.delete', $fb->fb_id) }}"
                                                method="POST" class="delete_form">
                                                @csrf
                                                @method('DELETE')
                                            </form>
                                        </div>
                                    </article>
                                @empty
                                    <div class="empty-state-wrapper">
                                        <div class="empty-state">
                                            <div class="empty-state-icon facebook-empty">
                                                <i class="fab fa-facebook-f"></i>
                                            </div>
                                            <h4>No Facebook Account Connected</h4>
                                            <p>Connect your Facebook account to start scheduling and publishing posts.</p>
                                            <a href="{{ $facebookUrl }}" class="btn btn-facebook">
                                                <i class="fab fa-facebook-f mr-2"></i> Connect Facebook
                                            </a>
                                        </div>
                                    </div>
                                @endforelse
                            </div>
                            @if (count($user->facebook) > 12)
                                <div class="accounts-toggle-wrapper">
                                    <button class="btn-accounts-toggle" data-platform="facebook" type="button">
                                        <span class="toggle-text">Show All</span>
                                        <i class="fas fa-chevron-down toggle-icon"></i>
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                {{-- Pinterest --}}
                <div class="card platform-card">
                    <div class="card-header with-border clearfix">
                        <div class="card-title">
                            <input type="hidden" id="pinterestAcc" value="{{ session_check('pinterest_auth') ? 1 : 0 }}">
                            <img src="{{ social_logo('pinterest') }}">
                            <span>Pinterest</span>
                        </div>
                        <a href="{{ $pinterestUrl }}" class="btn btn-outline-primary btn-sm mx-2">+ Connect</a>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="accounts-grid-wrapper">
                            <div class="accounts-grid" data-platform="pinterest">
                                @forelse ($user->pinterest as $index => $pin)
                                    <article class="account-card pinterest-card has-tooltip" @style(['display:none;' => $index >= 12])
                                        data-tooltip="{{ $pin->username }}" data-index="{{ $index }}">
                                        <div class="account-card-accent"></div>
                                        <a href="{{ route('panel.accounts.pinterest', $pin->pin_id) }}"
                                            class="account-card-link">
                                            <div class="account-card-content">
                                                <div class="account-avatar-wrapper">
                                                    <img src="{{ $pin->profile_image }}" class="account-avatar"
                                                        onerror="this.onerror=null; this.src='{{ social_logo('pinterest') }}';">
                                                    <span class="platform-indicator pinterest-indicator">
                                                        <i class="fab fa-pinterest-p"></i>
                                                    </span>
                                                </div>
                                                <div class="account-info">
                                                    <div class="account-username">
                                                        {{ Str::limit($pin->username, 12) }}
                                                    </div>
                                                </div>
                                            </div>
                                        </a>
                                        <div class="account-card-actions">
                                            <button class="btn-account-delete delete-btn" title="Delete Account">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                            <form action="{{ route('panel.accounts.pinterest.delete', $pin->pin_id) }}"
                                                method="POST" class="delete_form">
                                                @csrf
                                                @method('DELETE')
                                            </form>
                                        </div>
                                    </article>
                                @empty
                                    <div class="empty-state-wrapper">
                                        <div class="empty-state">
                                            <div class="empty-state-icon pinterest-empty">
                                                <i class="fab fa-pinterest-p"></i>
                                            </div>
                                            <h4>No Pinterest Account Connected</h4>
                                            <p>Connect your Pinterest account to start scheduling pins to your boards.</p>
                                            <a href="{{ $pinterestUrl }}" class="btn btn-pinterest">
                                                <i class="fab fa-pinterest-p mr-2"></i> Connect Pinterest
                                            </a>
                                        </div>
                                    </div>
                                @endforelse
                            </div>
                            @if (count($user->pinterest) > 12)
                                <div class="accounts-toggle-wrapper">
                                    <button class="btn-accounts-toggle" data-platform="pinterest" type="button">
                                        <span class="toggle-text">Show All</span>
                                        <i class="fas fa-chevron-down toggle-icon"></i>
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                {{-- TikTok --}}
                <div class="card platform-card">
                    <div class="card-header with-border clearfix">
                        <div class="card-title">
                            <input type="hidden" id="tiktokAcc" value="{{ session_check('tiktok_auth') ? 1 : 0 }}">
                            <img src="{{ social_logo('tiktok') }}">
                            <span>TikTok</span>
                        </div>
                        <a href="{{ $tiktokUrl }}" class="btn btn-outline-primary btn-sm mx-2">+ Connect</a>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="accounts-grid-wrapper">
                            <div class="accounts-grid" data-platform="tiktok">
                                @forelse ($user->tiktok as $index => $tiktok)
                                    <article class="account-card tiktok-card has-tooltip" @style(['display:none;' => $index >= 12])
                                        data-tooltip="{{ $tiktok->username }}" data-index="{{ $index }}">
                                        <div class="account-card-accent"></div>
                                        <a href="{{ route('panel.accounts.tiktok', $tiktok->tiktok_id) }}"
                                            class="account-card-link">
                                            <div class="account-card-content">
                                                <div class="account-avatar-wrapper">
                                                    <img src="{{ $tiktok->profile_image }}" class="account-avatar"
                                                        onerror="this.onerror=null; this.src='{{ social_logo('tiktok') }}';">
                                                    <span class="platform-indicator tiktok-indicator">
                                                        <i class="fab fa-tiktok"></i>
                                                    </span>
                                                </div>
                                                <div class="account-info">
                                                    <div class="account-username">
                                                        {{ Str::limit($tiktok->username, 12) }}
                                                    </div>
                                                </div>
                                            </div>
                                        </a>
                                        <div class="account-card-actions">
                                            <button class="btn-account-delete delete-btn" title="Delete Account">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                            <form action="{{ route('panel.accounts.tiktok.delete', $tiktok->tiktok_id) }}"
                                                method="POST" class="delete_form">
                                                @csrf
                                                @method('DELETE')
                                            </form>
                                        </div>
                                    </article>
                                @empty
                                    <div class="empty-state-wrapper">
                                        <div class="empty-state">
                                            <div class="empty-state-icon tiktok-empty">
                                                <i class="fab fa-tiktok"></i>
                                            </div>
                                            <h4>No TikTok Account Connected</h4>
                                            <p>Connect your TikTok account to start scheduling and publishing videos.</p>
                                            <a href="{{ $tiktokUrl }}" class="btn btn-tiktok">
                                                <i class="fab fa-tiktok mr-2"></i> Connect TikTok
                                            </a>
                                        </div>
                                    </div>
                                @endforelse
                            </div>
                            @if (count($user->tiktok) > 12)
                                <div class="accounts-toggle-wrapper">
                                    <button class="btn-accounts-toggle" data-platform="tiktok" type="button">
                                        <span class="toggle-text">Show All</span>
                                        <i class="fas fa-chevron-down toggle-icon"></i>
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

            </div>
        </section>
    </div>
    @if (!empty(session_get('items')))
        @if (session_get('account') == 'Pinterest')
            @include('user.accounts.modals.pinterest_boards_modal', [
            ])
        @elseif(session_get('account') == 'Facebook')
            @include('user.accounts.modals.facebook_pages_modal', [
            ])
        @elseif(session_get('account') == 'TikTok')
            {{-- TikTok doesn't need a modal like Pinterest/Facebook --}}
        @endif
    @endif
@endsection
@push('styles')
    <style>
        .card-title {
            padding-inline: 10px;
            border-right: 1px solid black;
        }

        .card-title img {
            width: 30px;
        }

        .card-title span {
            font-weight: 600
        }


        /* New Account Cards Styling */
        .platform-card .card-body {
            padding: 1.25rem;
        }

        .accounts-grid-wrapper {
            position: relative;
        }

        .accounts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 16px;
            position: relative;
            overflow: visible;
        }

        .accounts-grid-wrapper {
            overflow: visible;
        }

        /* Ensure smooth transitions for account cards */
        .account-card {
            transition: opacity 0.3s ease, transform 0.3s ease;
        }

        /* Tooltip Styling */
        .has-tooltip {
            position: relative;
            cursor: pointer;
        }

        .has-tooltip::after {
            content: attr(data-tooltip);
            position: absolute;
            bottom: calc(100% + 10px);
            left: 50%;
            transform: translateX(-50%);
            padding: 8px 12px;
            background: #333;
            color: #fff;
            font-size: 12px;
            font-weight: 500;
            white-space: nowrap;
            border-radius: 6px;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease, transform 0.3s ease, visibility 0.3s ease;
            transform: translateX(-50%) translateY(-5px);
            z-index: 9999;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            visibility: hidden;
            min-width: max-content;
        }

        .has-tooltip::before {
            content: '';
            position: absolute;
            bottom: calc(100% + 4px);
            left: 50%;
            transform: translateX(-50%);
            border: 6px solid transparent;
            border-top-color: #333;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease, transform 0.3s ease, visibility 0.3s ease;
            transform: translateX(-50%) translateY(-5px);
            z-index: 10000;
            visibility: hidden;
        }

        .has-tooltip:hover::after,
        .has-tooltip:hover::before {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
            visibility: visible;
        }

        .account-card {
            position: relative;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            border: 1px solid #e8e8e8;
            overflow: visible;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: stretch;
        }

        .account-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            border-color: transparent;
        }

        .account-card-accent {
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            transition: width 0.3s ease;
            z-index: 1;
            border-radius: 12px 0 0 12px;
        }

        .facebook-card .account-card-accent {
            background: linear-gradient(180deg, #1877f2, #0d65d9);
        }

        .pinterest-card .account-card-accent {
            background: linear-gradient(180deg, #e60023, #bd081c);
        }

        .account-card:hover .account-card-accent {
            width: 6px;
        }

        .account-card-link {
            flex: 1;
            text-decoration: none;
            color: inherit;
            padding: 10px 12px 10px 20px;
        }

        .account-card-link:hover {
            text-decoration: none;
            color: inherit;
        }

        .account-card-content {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .account-avatar-wrapper {
            position: relative;
            flex-shrink: 0;
        }

        .account-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #f0f0f0;
            transition: border-color 0.3s ease;
        }

        .facebook-card:hover .account-avatar {
            border-color: #1877f2;
        }

        .pinterest-card:hover .account-avatar {
            border-color: #e60023;
        }

        .platform-indicator {
            position: absolute;
            bottom: -2px;
            right: -2px;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 10px;
            border: 2px solid #fff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
        }

        .facebook-indicator {
            background: #1877f2;
        }

        .pinterest-indicator {
            background: #e60023;
        }

        .account-info {
            flex: 1;
            min-width: 0;
        }

        .account-username {
            font-weight: 600;
            font-size: 14px;
            color: #1a1a1a;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-bottom: 2px;
            line-height: 1.3;
        }

        .account-type {
            font-size: 11px;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 500;
        }

        .account-card-actions {
            display: flex;
            align-items: center;
            padding-right: 12px;
        }

        .btn-account-delete {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            border: none;
            background: transparent;
            color: #ccc;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            font-size: 14px;
        }

        .btn-account-delete:hover {
            background: #fee2e2;
            color: #dc2626;
        }

        /* Empty State Styling */
        .empty-state-wrapper {
            grid-column: 1 / -1;
            padding: 40px 20px;
        }

        .empty-state {
            text-align: center;
            max-width: 400px;
            margin: 0 auto;
        }

        .empty-state-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 32px;
        }

        .empty-state-icon.facebook-empty {
            background: linear-gradient(135deg, #e8f0fe, #d4e4fc);
            color: #1877f2;
        }

        .empty-state-icon.pinterest-empty {
            background: linear-gradient(135deg, #fce8eb, #fcd4da);
            color: #e60023;
        }

        .empty-state h4 {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }

        .empty-state p {
            font-size: 14px;
            color: #666;
            margin-bottom: 20px;
            line-height: 1.5;
        }

        .btn-facebook {
            background: #1877f2;
            color: #fff;
            border: none;
            padding: 10px 24px;
            border-radius: 8px;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.2s ease;
        }

        .btn-facebook:hover {
            background: #0d65d9;
            color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(24, 119, 242, 0.35);
        }

        .btn-pinterest {
            background: #e60023;
            color: #fff;
            border: none;
            padding: 10px 24px;
            border-radius: 8px;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.2s ease;
        }

        .btn-pinterest:hover {
            background: #bd081c;
            color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(230, 0, 35, 0.35);
        }

        .btn-tiktok {
            background: #000000;
            color: #fff;
            border: none;
            padding: 10px 24px;
            border-radius: 8px;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.2s ease;
        }

        .btn-tiktok:hover {
            background: #333333;
            color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.35);
        }

        .tiktok-card .account-card-accent {
            background: linear-gradient(180deg, #000000, #333333);
        }

        .tiktok-card:hover .account-avatar {
            border-color: #000000;
        }

        .tiktok-indicator {
            background: #000000;
        }

        .empty-state-icon.tiktok-empty {
            background: linear-gradient(135deg, #e8e8e8, #d4d4d4);
            color: #000000;
        }

        /* Toggle Button Styling */
        .accounts-toggle-wrapper {
            text-align: center;
            margin-top: 20px;
            padding-top: 16px;
            border-top: 1px solid #e8e8e8;
        }

        .btn-accounts-toggle {
            background: transparent;
            border: 1px solid #d0d0d0;
            color: #666;
            padding: 10px 24px;
            border-radius: 8px;
            font-weight: 500;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-accounts-toggle:hover {
            background: #f5f5f5;
            border-color: #999;
            color: #333;
        }

        .btn-accounts-toggle .toggle-icon {
            transition: transform 0.3s ease;
            font-size: 12px;
        }

        /* Responsive adjustments */
        @media (max-width: 576px) {
            .accounts-grid {
                grid-template-columns: 1fr;
            }

            .account-card-link {
                padding: 14px 10px 14px 16px;
            }

            .account-avatar {
                width: 44px;
                height: 44px;
            }
        }

        /* Connect Account Modal Styles */
        .connect-account-modal {
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .connect-account-modal .modal-header {
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
            border-bottom: 1px solid #e9ecef;
            padding: 24px 30px;
        }

        .modal-header-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
        }

        .modal-title-wrapper {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .platform-icon-wrapper {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: #fff;
            flex-shrink: 0;
        }

        .platform-icon-wrapper.facebook-icon {
            background: linear-gradient(135deg, #1877f2, #0d65d9);
        }

        .platform-icon-wrapper.pinterest-icon {
            background: linear-gradient(135deg, #e60023, #bd081c);
        }

        .modal-title-info {
            flex: 1;
        }

        .modal-title {
            font-size: 20px;
            font-weight: 700;
            color: #1a1a1a;
            margin: 0;
            line-height: 1.2;
        }

        .modal-subtitle {
            font-size: 13px;
            color: #6c757d;
            margin: 4px 0 0 0;
        }

        .connect-account-modal .modal-body {
            padding: 24px 30px;
            max-height: 60vh;
            overflow-y: auto;
        }

        .connect-account-modal .modal-body::-webkit-scrollbar {
            width: 6px;
        }

        .connect-account-modal .modal-body::-webkit-scrollbar-thumb {
            background: #ddd;
            border-radius: 3px;
        }

        .accounts-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .account-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 20px;
            background: #fff;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            transition: all 0.3s ease;
        }

        .account-item:hover {
            border-color: #d0d7de;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            transform: translateY(-2px);
        }

        .account-item-content {
            display: flex;
            align-items: center;
            gap: 16px;
            flex: 1;
            min-width: 0;
        }

        .account-avatar-section {
            flex-shrink: 0;
        }

        .account-item-avatar {
            width: 56px;
            height: 56px;
            border-radius: 12px;
            object-fit: cover;
            border: 2px solid #f0f0f0;
        }

        .account-item-avatar-placeholder {
            width: 56px;
            height: 56px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            color: #6c757d;
            font-size: 24px;
        }

        .account-item-avatar-placeholder.pinterest-placeholder {
            background: linear-gradient(135deg, #fce8eb, #fcd4da);
            color: #e60023;
        }

        .account-item-info {
            flex: 1;
            min-width: 0;
        }

        .account-item-name {
            font-size: 16px;
            font-weight: 600;
            color: #1a1a1a;
            margin: 0 0 4px 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .account-item-type {
            font-size: 12px;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 500;
        }

        .account-item-action {
            flex-shrink: 0;
            margin-left: 16px;
        }

        .btn-connect {
            background: linear-gradient(135deg, #1877f2, #0d65d9);
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            cursor: pointer;
            min-width: 120px;
            justify-content: center;
        }

        .btn-connect:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(24, 119, 242, 0.4);
            color: #fff;
        }

        .btn-connect:active {
            transform: translateY(0);
        }

        .btn-connect.pinterest_connect {
            background: linear-gradient(135deg, #e60023, #bd081c);
        }

        .btn-connect.pinterest_connect:hover {
            box-shadow: 0 6px 20px rgba(230, 0, 35, 0.4);
        }

        .btn-connect:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .btn-connected {
            background: linear-gradient(135deg, #10b981, #059669);
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: default;
            min-width: 120px;
            justify-content: center;
        }

        .btn-connected i {
            font-size: 16px;
        }

        .connect-account-modal .modal-footer {
            background: #f8f9fa;
            border-top: 1px solid #e9ecef;
            padding: 20px 30px;
            display: flex;
            justify-content: flex-end;
        }

        .btn-continue {
            background: linear-gradient(135deg, #1877f2, #0d65d9);
            color: #fff;
            border: none;
            padding: 12px 28px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 15px;
            display: inline-flex;
            align-items: center;
            transition: all 0.3s ease;
        }

        .btn-continue:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(24, 119, 242, 0.4);
            color: #fff;
        }

        .connect-account-modal .close {
            font-size: 28px;
            font-weight: 300;
            color: #6c757d;
            opacity: 0.7;
            transition: all 0.2s ease;
            padding: 0;
            margin: 0;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
        }

        .connect-account-modal .close:hover {
            opacity: 1;
            background: #f0f0f0;
            color: #333;
        }

        /* Loading state for connect button */
        .btn-connect.loading {
            position: relative;
            color: transparent;
            pointer-events: none;
        }

        .btn-connect.loading::after {
            content: '';
            position: absolute;
            width: 18px;
            height: 18px;
            top: 50%;
            left: 50%;
            margin-left: -9px;
            margin-top: -9px;
            border: 2px solid #fff;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spin 0.6s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* Success animation */
        .account-item.connecting .btn-connect {
            background: linear-gradient(135deg, #10b981, #059669);
        }

        @media (max-width: 768px) {
            .connect-account-modal .modal-dialog {
                margin: 10px;
            }

            .connect-account-modal .modal-header,
            .connect-account-modal .modal-body,
            .connect-account-modal .modal-footer {
                padding: 20px;
            }

            .account-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }

            .account-item-action {
                width: 100%;
                margin-left: 0;
            }

            .btn-connect,
            .btn-connected {
                width: 100%;
            }
        }
    </style>
@endpush
@push('scripts')
    <script>
        $(document).ready(function() {
            // Function to remove modal backdrop
            function removeModalBackdrop() {
                $('.modal-backdrop').remove();
                $('body').removeClass('modal-open');
                $('body').css('padding-right', '');
            }

            // Cleanup any orphaned backdrops on page load
            removeModalBackdrop();

            // Also cleanup on window focus (in case user navigated away and came back)
            $(window).on('focus', function() {
                // Only remove if no modal is actually showing
                if (!$('.modal.show').length) {
                    removeModalBackdrop();
                }
            });

            // Initialize tooltips for account cards
            function initAccountTooltips() {
                $('.has-tooltip').each(function() {
                    var $element = $(this);
                    var tooltipText = $element.data('tooltip');

                    if (tooltipText) {
                        // Ensure tooltip text is set
                        $element.attr('data-tooltip', tooltipText);
                    }
                });
            }

            // Initialize tooltips
            initAccountTooltips();

            // Re-initialize tooltips after toggle (for newly shown cards)
            $(document).on('click', '.btn-accounts-toggle', function() {
                setTimeout(function() {
                    initAccountTooltips();
                }, 450); // After fade animation completes
            });

            // Legacy tooltip initialization if function exists
            if (typeof initTooltips === 'function') {
                initTooltips();
            }

            // Pinterest Modal
            var pinAcc = $('#pinterestAcc').val();
            if (pinAcc == 1) {
                // Ensure no leftover backdrops before showing
                removeModalBackdrop();
                $('#connectPinterestModal').modal('show');
                {{ session_delete('pinterest_auth') }}
            }

            // Pinterest Connect Handler
            $(document).on('click', '.pinterest_connect', function() {
                var button = $(this);
                var $accountItem = button.closest('.account-item');
                var id = button.data('id');
                var pin_id = button.data('pin-id');
                var board_data = button.data('board-data');
                var token = $('meta[name="csrf-token"]').attr('content');

                // Disable button and show loading state
                button.addClass('loading').prop('disabled', true);
                $accountItem.addClass('connecting');

                $.ajax({
                    url: "{{ route('panel.accounts.addBoard') }}",
                    type: 'POST',
                    data: {
                        "id": id,
                        "pin_id": pin_id,
                        "board_data": board_data,
                        "_token": token
                    },
                    success: function(response) {
                        if (response.success) {
                            // Update button to connected state
                            button.removeClass('pinterest_connect loading')
                                .addClass('btn-connected')
                                .html(
                                    '<i class="fas fa-check-circle"></i><span>Connected</span>')
                                .prop('disabled', true);

                            $accountItem.removeClass('connecting');
                            toastr.success("Board Connected Successfully!");
                        } else {
                            button.removeClass('loading').prop('disabled', false);
                            $accountItem.removeClass('connecting');
                            toastr.error(response.message || "Failed to connect board");
                        }
                    },
                    error: function(xhr) {
                        button.removeClass('loading').prop('disabled', false);
                        $accountItem.removeClass('connecting');
                        var errorMsg = xhr.responseJSON?.message ||
                            "Something went wrong. Please try again.";
                        toastr.error(errorMsg);
                    }
                });
            });

            // Pinterest Modal handlers
            $("#connectPinterestModal").on("hide.bs.modal", function() {
                {{ session_delete('account') }}
                {{ session_delete('items') }}
            });

            $("#connectPinterestModal").on("hidden.bs.modal", function() {
                removeModalBackdrop();
            });

            // Facebook Modal
            var facAcc = $('#facebookAcc').val();
            if (facAcc == 1) {
                // Ensure no leftover backdrops before showing
                removeModalBackdrop();
                $('#connectFacebookModal').modal('show');
                {{ session_delete('facebook_auth') }}
            }

            // Facebook Connect Handler
            $(document).on('click', '.facebook_connect', function() {
                var button = $(this);
                var $accountItem = button.closest('.account-item');
                var id = button.data('id');
                var fb_id = button.data('fb-id');
                var page_data = button.data('page-data');
                var token = $('meta[name="csrf-token"]').attr('content');

                // Disable button and show loading state
                button.addClass('loading').prop('disabled', true);
                $accountItem.addClass('connecting');

                $.ajax({
                    url: "{{ route('panel.accounts.addPage') }}",
                    type: 'POST',
                    data: {
                        "id": id,
                        "fb_id": fb_id,
                        "page_data": page_data,
                        "_token": token
                    },
                    success: function(response) {
                        if (response.success) {
                            // Update button to connected state
                            button.removeClass('facebook_connect loading')
                                .addClass('btn-connected')
                                .html(
                                    '<i class="fas fa-check-circle"></i><span>Connected</span>')
                                .prop('disabled', true);

                            $accountItem.removeClass('connecting');
                            toastr.success("Page Connected Successfully!");
                        } else {
                            button.removeClass('loading').prop('disabled', false);
                            $accountItem.removeClass('connecting');
                            toastr.error(response.message || "Failed to connect page");
                        }
                    },
                    error: function(xhr) {
                        button.removeClass('loading').prop('disabled', false);
                        $accountItem.removeClass('connecting');
                        var errorMsg = xhr.responseJSON?.message ||
                            "Something went wrong. Please try again.";
                        toastr.error(errorMsg);
                    }
                });
            });

            $("#connectFacebookModal").on("hide.bs.modal", function() {
                {{ session_delete('account') }}
                {{ session_delete('items') }}
            });

            $("#connectFacebookModal").on("hidden.bs.modal", function() {
                removeModalBackdrop();
            });

            // Accounts Toggle Functionality
            $('.btn-accounts-toggle').on('click', function() {
                var $button = $(this);
                var platform = $button.data('platform');
                var $grid = $('.accounts-grid[data-platform="' + platform + '"]');
                var $hiddenCards = $grid.find('.account-card[data-index]').filter(function() {
                    return parseInt($(this).data('index')) >= 12;
                });
                $button.find('.toggle-icon').toggleClass('fa-chevron-down fa-chevron-up');
                $hiddenCards.toggle();
                $button.find('.toggle-text').text($hiddenCards.is(':visible') ? 'Show Less' : 'Show All');
            });

        });
    </script>
@endpush
