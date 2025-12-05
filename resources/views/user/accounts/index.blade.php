@extends('user.layout.main')
@section('title', 'Accounts')
@section('page_content')
    <div class="page-content">
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
                        <div class="accounts-grid">
                            @forelse ($user->facebook as $fb)
                                <article class="account-card facebook-card has-tooltip" data-tooltip="{{ $fb->username }}">
                                    <div class="account-card-accent"></div>
                                    <a href="{{ route('panel.accounts.facebook', $fb->fb_id) }}" class="account-card-link">
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
                                                <div class="account-type">Facebook Account</div>
                                            </div>
                                        </div>
                                    </a>
                                    <div class="account-card-actions">
                                        <button class="btn-account-delete" onclick="confirmDelete(event)"
                                            title="Delete Account">
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
                        <div class="accounts-grid">
                            @forelse ($user->pinterest as $pin)
                                <article class="account-card pinterest-card has-tooltip"
                                    data-tooltip="{{ $pin->username }}">
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
                                                <div class="account-type">Pinterest Account</div>
                                            </div>
                                        </div>
                                    </a>
                                    <div class="account-card-actions">
                                        <button class="btn-account-delete" onclick="confirmDelete(event)"
                                            title="Delete Account">
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
                    </div>
                </div>
                {{-- TikTok --}}
                {{-- <div class="card">
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
                        <div class="row">
                            @foreach ($user->pinterest as $pin)
                                <article class="account_box col-md-3 m-1">
                                    <a href="{{ route('panel.accounts.pinterest', $pin->pin_id) }}">
                                        <div class="d-flex align-items-center">
                                            <picture>
                                                <img src="{{ $pin->profile_image }}" class="rounded-pill logo"
                                                    onerror="this.onerror=null; this.src='{{ social_logo('pinterest') }}';">
                                            </picture>
                                            <div class="account_name has-tooltip" data-tooltip="{{ $pin->username }}">{{ Str::limit($pin->username, 25) }}</div>
                                        </div>
                                    </a>
                                    <div>
                                        <button class="btn btn-outline-danger btn-sm delete-btn border-0"
                                            onclick="confirmDelete(event)">
                                            <i class="fa fa-trash px-2"></i>
                                        </button>
                                        <form action="{{ route('panel.accounts.pinterest.delete', $pin->pin_id) }}"
                                            method="POST" class="delete_form">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </div>
                </div> --}}

            </div>
        </section>
    </div>
    @if (!empty(session_get('items')))
        @if (session_get('account') == 'Pinterest')
            @include('user.accounts.modals.pinterest_boards_modal', [
                'pinterest' => $user->pinterest()->latest()->first(),
            ])
        @elseif(session_get('account') == 'Facebook')
            @include('user.accounts.modals.facebook_pages_modal', [
                'facebook' => $user->facebook()->latest()->first(),
            ])
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

        .acc_title {
            font-weight: 600
        }

        .item_count {
            padding: 10px;
            margin-block: 5px;
            border: 1px solid grey;
            border-radius: 10px;
            font-weight: 600
        }

        .pinterest_connect,
        .facebook_connect {
            color: #008100 !important;
        }

        /* New Account Cards Styling */
        .platform-card .card-body {
            padding: 1.25rem;
        }

        .accounts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 16px;
        }

        .account-card {
            position: relative;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            border: 1px solid #e8e8e8;
            overflow: hidden;
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
            padding: 16px 12px 16px 20px;
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
    </style>
@endpush
@push('scripts')
    <script>
        $(document).ready(function() {
            // Initialize tooltips
            if (typeof initTooltips === 'function') {
                initTooltips();
            }

            // Pinterest Modal
            var pinAcc = $('#pinterestAcc').val();
            if (pinAcc == 1) {
                $('#connectPinterestModal').modal('toggle');
                {{ session_delete('pinterest_auth') }}
            }
            $('.pinterest_connect').on('click', function() {
                var button = $(this);
                var id = button.data('id');
                var pin_id = button.data('pin-id');
                var board_data = button.data('board-data');
                var token = $('meta[name="csrf-token"]').attr('content');
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
                            toastr.success("Board Connected Successfully!");
                            button.text('Connected').removeClass('pinterest_connect pointer');
                        } else {
                            toastr.error(response.message);
                        }
                    },
                });
            });
            $("#connectPinterestModal").on("hide.bs.modal", function() {
                {{ session_delete('account') }}
                {{ session_delete('items') }}
            });
            // Facebook Modal
            var facAcc = $('#facebookAcc').val();
            if (facAcc == 1) {
                $('#connectFacebookModal').modal('toggle');
                {{ session_delete('facebook_auth') }}
            }
            $('.facebook_connect').on('click', function() {
                var button = $(this);
                var id = button.data('id');
                var fb_id = button.data('fb-id');
                var page_data = button.data('page-data');
                var token = $('meta[name="csrf-token"]').attr('content');
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
                            toastr.success("Page Connected Successfully!");
                            button.text('Connected').removeClass('facebook_connect pointer');
                        } else {
                            toastr.error(response.message);
                        }
                    },
                });
            });
            $("#connectFacebookModal").on("hide.bs.modal", function() {
                {{ session_delete('account') }}
                {{ session_delete('items') }}
            });

        });
    </script>
@endpush
