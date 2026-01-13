@extends('user.layout.main')
@section('title', 'Invite Team Member')
@section('page_content')
    <div class="page-content">
        <div class="content-header clearfix">
            <h1 class="m-0">Invite Team Member</h1>
        </div>
        <section class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <form action="{{ route('panel.team-members.store') }}" method="POST">
                                    @csrf

                                    <div class="form-group">
                                        <label for="email">Email Address *</label>
                                        <input type="email" class="form-control @error('email') is-invalid @enderror"
                                            id="email" name="email" value="{{ old('email') }}" required>
                                        @error('email')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="row">
                                        <!-- Menus Access Column -->
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="font-weight-bold mb-3">Menus Access</label>
                                                <p class="text-muted small mb-3">Select the menu items this team member can access.</p>
                                                <div class="menus-container"
                                                    style="max-height: 600px; overflow-y: auto; padding: 10px 0;">
                                                    <div class="menu-items-grid">
                                                        @foreach ($menuItems as $menuItem)
                                                            <div class="menu-item-card" data-menu-id="{{ $menuItem['id'] }}">
                                                                <div class="menu-item-content">
                                                                    <div class="menu-item-icon-wrapper">
                                                                        <i class="{{ $menuItem['icon'] }} menu-item-icon"></i>
                                                                    </div>
                                                                    <div class="menu-item-info">
                                                                        <div class="menu-item-name">{{ $menuItem['name'] }}</div>
                                                                        <div class="menu-item-route">{{ $menuItem['route'] }}</div>
                                                                    </div>
                                                                    <div class="menu-item-checkbox-wrapper">
                                                                        <input
                                                                            class="menu-checkbox"
                                                                            type="checkbox" name="menu_access[]"
                                                                            value="{{ $menuItem['id'] }}"
                                                                            id="menu_{{ $menuItem['id'] }}">
                                                                        <label for="menu_{{ $menuItem['id'] }}" class="menu-checkbox-label">
                                                                            <span class="checkmark"></span>
                                                                        </label>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                    @if (empty($menuItems))
                                                        <div class="text-center py-5">
                                                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                                            <p class="text-muted">No menu items available.</p>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Features Column -->
                                        <div class="col-md-6 shadow-sm">
                                            <div class="form-group">
                                                <label>Feature Limits</label>
                                                <p class="text-muted small">Leave empty to use your package limits.</p>
                                                <div class="features-container"
                                                    style="max-height: 600px; overflow-y: auto;">
                                                    @if ($features->count() > 0)
                                                        @foreach ($features as $feature)
                                                            <div class="form-group mb-3">
                                                                <label
                                                                    for="feature_{{ $feature->id }}">{{ $feature->name }}</label>
                                                                <input type="number" class="form-control"
                                                                    id="feature_{{ $feature->id }}"
                                                                    name="feature_limits[{{ $feature->id }}]"
                                                                    placeholder="Leave empty for package default">
                                                                @if ($feature->description)
                                                                    <small
                                                                        class="form-text text-muted">{{ $feature->description }}</small>
                                                                @endif
                                                            </div>
                                                        @endforeach
                                                    @else
                                                        <p class="text-muted">No features available.</p>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label>Account Access</label>
                                        <p class="text-muted small">Select the accounts this team member can access.</p>
                                        @if ($accounts->isEmpty())
                                            <p class="text-muted">No accounts available. Please connect accounts first.</p>
                                        @else
                                            @php
                                                $facebookAccounts = $accounts->where('type', 'page');
                                                $pinterestAccounts = $accounts->where('type', 'board');
                                                $tiktokAccounts = $accounts->where('type', 'tiktok');
                                            @endphp

                                            @if ($facebookAccounts->count() > 0)
                                                <div class="mb-4">
                                                    <h6 class="mb-3">
                                                        <img src="{{ social_logo('facebook') }}"
                                                            style="width: 24px; height: 24px; margin-right: 8px;">
                                                        Facebook Pages
                                                    </h6>
                                                    <div class="accounts-grid-wrapper">
                                                        <div class="accounts-grid" data-platform="facebook">
                                                            @foreach ($facebookAccounts as $account)
                                                                <article
                                                                    class="account-card facebook-card account-select-card"
                                                                    data-account-id="{{ $account['id'] }}"
                                                                    data-account-type="{{ $account['type'] }}">
                                                                    <div class="account-card-accent"></div>
                                                                    <div class="account-card-content">
                                                                        <div class="account-avatar-wrapper">
                                                                            <img src="{{ $account['profile_image'] }}"
                                                                                class="account-avatar"
                                                                                onerror="this.onerror=null; this.src='{{ social_logo('facebook') }}';">
                                                                            <span
                                                                                class="platform-indicator facebook-indicator">
                                                                                <i class="fab fa-facebook-f"></i>
                                                                            </span>
                                                                        </div>
                                                                        <div class="account-info">
                                                                            <div class="account-username">
                                                                                {{ Str::limit($account['username'], 20) }}
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="account-card-checkbox">
                                                                        <input type="checkbox" class="account-checkbox"
                                                                            name="accounts[{{ $account['id'] }}][type]"
                                                                            value="{{ $account['type'] }}"
                                                                            id="acc_{{ $account['type'] }}_{{ $account['id'] }}"
                                                                            data-account-id="{{ $account['id'] }}">
                                                                        <input type="hidden"
                                                                            name="accounts[{{ $account['id'] }}][id]"
                                                                            value="{{ $account['id'] }}">
                                                                    </div>
                                                                </article>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif

                                            @if ($pinterestAccounts->count() > 0)
                                                <div class="mb-4">
                                                    <h6 class="mb-3">
                                                        <img src="{{ social_logo('pinterest') }}"
                                                            style="width: 24px; height: 24px; margin-right: 8px;">
                                                        Pinterest Boards
                                                    </h6>
                                                    <div class="accounts-grid-wrapper">
                                                        <div class="accounts-grid" data-platform="pinterest">
                                                            @foreach ($pinterestAccounts as $account)
                                                                <article
                                                                    class="account-card pinterest-card account-select-card"
                                                                    data-account-id="{{ $account['id'] }}"
                                                                    data-account-type="{{ $account['type'] }}">
                                                                    <div class="account-card-accent"></div>
                                                                    <div class="account-card-content">
                                                                        <div class="account-avatar-wrapper">
                                                                            <img src="{{ $account['profile_image'] }}"
                                                                                class="account-avatar"
                                                                                onerror="this.onerror=null; this.src='{{ social_logo('pinterest') }}';">
                                                                            <span
                                                                                class="platform-indicator pinterest-indicator">
                                                                                <i class="fab fa-pinterest-p"></i>
                                                                            </span>
                                                                        </div>
                                                                        <div class="account-info">
                                                                            <div class="account-username">
                                                                                {{ Str::limit($account['username'], 20) }}
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="account-card-checkbox">
                                                                        <input type="checkbox" class="account-checkbox"
                                                                            name="accounts[{{ $account['id'] }}][type]"
                                                                            value="{{ $account['type'] }}"
                                                                            id="acc_{{ $account['type'] }}_{{ $account['id'] }}"
                                                                            data-account-id="{{ $account['id'] }}">
                                                                        <input type="hidden"
                                                                            name="accounts[{{ $account['id'] }}][id]"
                                                                            value="{{ $account['id'] }}">
                                                                    </div>
                                                                </article>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif

                                            @if ($tiktokAccounts->count() > 0)
                                                <div class="mb-4">
                                                    <h6 class="mb-3">
                                                        <img src="{{ social_logo('tiktok') }}"
                                                            style="width: 24px; height: 24px; margin-right: 8px;">
                                                        TikTok Accounts
                                                    </h6>
                                                    <div class="accounts-grid-wrapper">
                                                        <div class="accounts-grid" data-platform="tiktok">
                                                            @foreach ($tiktokAccounts as $account)
                                                                <article
                                                                    class="account-card tiktok-card account-select-card"
                                                                    data-account-id="{{ $account['id'] }}"
                                                                    data-account-type="{{ $account['type'] }}">
                                                                    <div class="account-card-accent"></div>
                                                                    <div class="account-card-content">
                                                                        <div class="account-avatar-wrapper">
                                                                            <img src="{{ $account['profile_image'] }}"
                                                                                class="account-avatar"
                                                                                onerror="this.onerror=null; this.src='{{ social_logo('tiktok') }}';">
                                                                            <span
                                                                                class="platform-indicator tiktok-indicator">
                                                                                <i class="fab fa-tiktok"></i>
                                                                            </span>
                                                                        </div>
                                                                        <div class="account-info">
                                                                            <div class="account-username">
                                                                                {{ Str::limit($account['username'], 20) }}
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="account-card-checkbox">
                                                                        <input type="checkbox" class="account-checkbox"
                                                                            name="accounts[{{ $account['id'] }}][type]"
                                                                            value="{{ $account['type'] }}"
                                                                            id="acc_{{ $account['type'] }}_{{ $account['id'] }}"
                                                                            data-account-id="{{ $account['id'] }}">
                                                                        <input type="hidden"
                                                                            name="accounts[{{ $account['id'] }}][id]"
                                                                            value="{{ $account['id'] }}">
                                                                    </div>
                                                                </article>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        @endif
                                    </div>

                                    <div class="form-group">
                                        <button type="submit" class="btn btn-primary">Send Invitation</button>
                                        <a href="{{ route('panel.team-members.index') }}"
                                            class="btn btn-secondary">Cancel</a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
@push('styles')
    <style>
        /* Account Cards Styling - Matching accounts/index */
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
            cursor: pointer;
        }

        .account-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            border-color: transparent;
        }

        .account-card.selected {
            border-color: #1877f2;
            box-shadow: 0 4px 12px rgba(24, 119, 242, 0.2);
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

        .tiktok-card .account-card-accent {
            background: linear-gradient(180deg, #000000, #333333);
        }

        .account-card:hover .account-card-accent {
            width: 6px;
        }

        .account-card-content {
            flex: 1;
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 10px 12px 10px 20px;
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

        .tiktok-card:hover .account-avatar {
            border-color: #000000;
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

        .tiktok-indicator {
            background: #000000;
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

        .account-card-checkbox {
            display: flex;
            align-items: center;
            padding-right: 12px;
        }

        .account-card-checkbox input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }

        @media (max-width: 576px) {
            .accounts-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Menu Items Styling */
        .menu-items-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
        }

        .menu-item-card {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
            border: 2px solid #e8e8e8;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            overflow: hidden;
            position: relative;
        }

        .menu-item-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(180deg, #007bff, #0056b3);
            transform: scaleY(0);
            transition: transform 0.3s ease;
            transform-origin: bottom;
        }

        .menu-item-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12);
            border-color: #007bff;
        }

        .menu-item-card:hover::before {
            transform: scaleY(1);
        }

        .menu-item-card.selected {
            border-color: #007bff;
            box-shadow: 0 4px 16px rgba(0, 123, 255, 0.2);
            background: linear-gradient(to right, rgba(0, 123, 255, 0.03), #fff);
        }

        .menu-item-card.selected::before {
            transform: scaleY(1);
        }

        .menu-item-content {
            display: flex;
            align-items: center;
            padding: 16px;
            gap: 16px;
        }

        .menu-item-icon-wrapper {
            flex-shrink: 0;
            width: 48px;
            height: 48px;
            border-radius: 10px;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .menu-item-card:hover .menu-item-icon-wrapper {
            background: linear-gradient(135deg, #e7f3ff, #cfe2ff);
            transform: scale(1.05);
        }

        .menu-item-card.selected .menu-item-icon-wrapper {
            background: linear-gradient(135deg, #007bff, #0056b3);
        }

        .menu-item-icon {
            font-size: 20px;
            color: #6c757d;
            transition: all 0.3s ease;
        }

        .menu-item-card:hover .menu-item-icon {
            color: #007bff;
        }

        .menu-item-card.selected .menu-item-icon {
            color: #fff;
        }

        .menu-item-info {
            flex: 1;
            min-width: 0;
        }

        .menu-item-name {
            font-weight: 600;
            font-size: 15px;
            color: #1a1a1a;
            margin-bottom: 4px;
            line-height: 1.4;
        }

        .menu-item-route {
            font-size: 12px;
            color: #6c757d;
            font-family: 'Courier New', monospace;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .menu-item-checkbox-wrapper {
            flex-shrink: 0;
            position: relative;
        }

        .menu-checkbox {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
        }

        .menu-checkbox-label {
            display: block;
            width: 24px;
            height: 24px;
            cursor: pointer;
            position: relative;
        }

        .checkmark {
            display: block;
            width: 24px;
            height: 24px;
            border: 2px solid #dee2e6;
            border-radius: 6px;
            background: #fff;
            transition: all 0.3s ease;
            position: relative;
        }

        .menu-checkbox:checked + .menu-checkbox-label .checkmark {
            background: #007bff;
            border-color: #007bff;
        }

        .menu-checkbox:checked + .menu-checkbox-label .checkmark::after {
            content: '';
            position: absolute;
            left: 7px;
            top: 3px;
            width: 6px;
            height: 10px;
            border: solid white;
            border-width: 0 2px 2px 0;
            transform: rotate(45deg);
        }

        .menu-item-card:hover .checkmark {
            border-color: #007bff;
        }

        @media (max-width: 768px) {
            .menu-items-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush
@push('scripts')
    <script>
        $(document).ready(function() {
            // Handle card click to toggle checkbox
            $('.account-select-card').on('click', function(e) {
                // Don't toggle if clicking directly on the checkbox
                if ($(e.target).is('input[type="checkbox"]') || $(e.target).closest(
                        '.account-card-checkbox').length) {
                    return;
                }

                var $card = $(this);
                var $checkbox = $card.find('.account-checkbox');
                $checkbox.prop('checked', !$checkbox.prop('checked'));
                $card.toggleClass('selected', $checkbox.prop('checked'));
            });

            // Handle checkbox change to update card state
            $('.account-checkbox').on('change', function() {
                var $checkbox = $(this);
                var $card = $checkbox.closest('.account-select-card');
                $card.toggleClass('selected', $checkbox.prop('checked'));
            });

            // Initialize selected state for checked checkboxes
            $('.account-checkbox:checked').each(function() {
                $(this).closest('.account-select-card').addClass('selected');
            });

            // Handle menu item card click to toggle checkbox
            $('.menu-item-card').on('click', function(e) {
                // Don't toggle if clicking directly on the checkbox
                if ($(e.target).is('input[type="checkbox"]') || $(e.target).closest('.menu-item-checkbox-wrapper').length) {
                    return;
                }

                var $card = $(this);
                var $checkbox = $card.find('.menu-checkbox');
                $checkbox.prop('checked', !$checkbox.prop('checked'));
                $card.toggleClass('selected', $checkbox.prop('checked'));
            });

            // Handle menu checkbox change to update card state
            $('.menu-checkbox').on('change', function() {
                var $checkbox = $(this);
                var $card = $checkbox.closest('.menu-item-card');
                $card.toggleClass('selected', $checkbox.prop('checked'));
            });

            // Initialize selected state for checked menu checkboxes
            $('.menu-checkbox:checked').each(function() {
                $(this).closest('.menu-item-card').addClass('selected');
            });
        });
    </script>
@endpush
