@extends('admin.layouts.secure')
@section('page_title', 'Users')
@section('page_content')
    @can('view_user')
        <div class="page-content">
            <div class="content-header clearfix">
                <h1 class="float-left">Users</h1>
                <div class="float-right">
                    <a class="btn btn-primary" href="{{ route('admin.users.create') }}">
                        <i class="fas fa-plus-square"></i> Add new</a>
                </div>
            </div>
            <section class="content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <div class="table-list">
                                        <div class="row">
                                            <div class="col-12">
                                                <div class="table-responsive">
                                                    <table class="table table-striped table-bordered" id="dataTable">
                                                        <thead>
                                                            <th data-data="id">ID</th>
                                                            <th data-data="name_link">Full Name</th>
                                                            <th data-data="email">Email</th>
                                                            <th data-data="package">Package</th>
                                                            <th data-data="role">Role</th>
                                                            <th data-data="status_span">Status</th>
                                                            <th data-data="action">Action</th>
                                                        </thead>
                                                        <tbody>
                                                            <tr>
                                                                <td>1</td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
        </div>

        {{-- Social Accounts Modal --}}
        <div class="modal fade" id="socialAccountsModal" tabindex="-1" role="dialog" aria-labelledby="socialAccountsModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="socialAccountsModalLabel">
                            <i class="fas fa-share-alt mr-2"></i>Connected Social Accounts
                            <span id="modalUserName" class="text-muted font-weight-normal ml-2"></span>
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div id="socialAccountsLoading" class="text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="sr-only">Loading...</span>
                            </div>
                            <p class="mt-2">Loading accounts...</p>
                        </div>
                        <div id="socialAccountsContent" style="display: none;">
                            <div id="socialAccountsSummary" class="alert alert-info mb-3"></div>
                            {{-- Facebook --}}
                            <div class="card platform-card modal-platform-card mb-3">
                                <div class="card-header with-border clearfix">
                                    <div class="card-title">
                                        <img src="{{ asset('assets/img/icons/facebook-circle.svg') }}" loading="lazy" alt="">
                                        <span>Facebook Pages</span>
                                        <span class="badge badge-primary ml-2" id="facebookCount">0</span>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="accounts-grid-wrapper">
                                        <div class="accounts-grid" data-platform="facebook" id="facebookAccountsList"></div>
                                    </div>
                                </div>
                            </div>
                            {{-- Pinterest --}}
                            <div class="card platform-card modal-platform-card mb-3">
                                <div class="card-header with-border clearfix">
                                    <div class="card-title">
                                        <img src="{{ asset('assets/img/icons/pinterest-circle.svg') }}" loading="lazy" alt="">
                                        <span>Pinterest Boards</span>
                                        <span class="badge badge-danger ml-2" id="pinterestCount">0</span>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="accounts-grid-wrapper">
                                        <div class="accounts-grid" data-platform="pinterest" id="pinterestAccountsList"></div>
                                    </div>
                                </div>
                            </div>
                            {{-- TikTok --}}
                            <div class="card platform-card modal-platform-card mb-3">
                                <div class="card-header with-border clearfix">
                                    <div class="card-title">
                                        <img src="{{ asset('assets/img/icons/tiktok-circle.svg') }}" loading="lazy" alt="">
                                        <span>TikTok Accounts</span>
                                        <span class="badge badge-dark ml-2" id="tiktokCount">0</span>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="accounts-grid-wrapper">
                                        <div class="accounts-grid" data-platform="tiktok" id="tiktokAccountsList"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div id="socialAccountsError" class="alert alert-danger" style="display: none;"></div>
                    </div>
                </div>
            </div>
        </div>
    @endcan
@endsection
@push('styles')
    <style>
        /* Admin Social Accounts Modal - same design as user/accounts */
        #socialAccountsModal .platform-card .card-body { padding: 1.25rem; }
        #socialAccountsModal .accounts-grid-wrapper { position: relative; overflow: visible; }
        #socialAccountsModal .accounts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 16px;
            position: relative;
            overflow: visible;
        }
        #socialAccountsModal .account-card {
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
        #socialAccountsModal .account-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            border-color: transparent;
        }
        #socialAccountsModal .account-card-accent {
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            transition: width 0.3s ease;
            z-index: 1;
            border-radius: 12px 0 0 12px;
        }
        #socialAccountsModal .facebook-card .account-card-accent { background: linear-gradient(180deg, #1877f2, #0d65d9); }
        #socialAccountsModal .pinterest-card .account-card-accent { background: linear-gradient(180deg, #e60023, #bd081c); }
        #socialAccountsModal .tiktok-card .account-card-accent { background: linear-gradient(180deg, #000000, #333333); }
        #socialAccountsModal .account-card:hover .account-card-accent { width: 6px; }
        #socialAccountsModal .account-card-link {
            flex: 1;
            text-decoration: none;
            color: inherit;
            padding: 10px 12px 10px 20px;
        }
        #socialAccountsModal .account-card-link:hover { text-decoration: none; color: inherit; }
        #socialAccountsModal .account-card-content { display: flex; align-items: center; gap: 14px; }
        #socialAccountsModal .account-avatar-wrapper { position: relative; flex-shrink: 0; }
        #socialAccountsModal .account-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #f0f0f0;
            transition: border-color 0.3s ease;
        }
        #socialAccountsModal .facebook-card:hover .account-avatar { border-color: #1877f2; }
        #socialAccountsModal .pinterest-card:hover .account-avatar { border-color: #e60023; }
        #socialAccountsModal .tiktok-card:hover .account-avatar { border-color: #000000; }
        #socialAccountsModal .platform-indicator {
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
        #socialAccountsModal .facebook-indicator { background: #1877f2; }
        #socialAccountsModal .pinterest-indicator { background: #e60023; }
        #socialAccountsModal .tiktok-indicator { background: #000000; }
        #socialAccountsModal .account-info { flex: 1; min-width: 0; }
        #socialAccountsModal .account-username {
            font-weight: 600;
            font-size: 14px;
            color: #1a1a1a;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-bottom: 2px;
            line-height: 1.3;
        }
        #socialAccountsModal .modal-platform-card .card-title {
            padding-inline: 10px;
            border-right: 1px solid rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
        }
        #socialAccountsModal .modal-platform-card .card-title img { width: 30px; }
        #socialAccountsModal .modal-platform-card .card-title span { font-weight: 600; }
        /* Tooltip for account cards */
        #socialAccountsModal .has-tooltip { position: relative; cursor: pointer; }
        #socialAccountsModal .has-tooltip::after {
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
        #socialAccountsModal .has-tooltip:hover::after {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
            visibility: visible;
        }
    </style>
@endpush
@push('scripts')
    <script type="text/javascript">
        var socialLogoFacebook = "{{ asset('assets/img/icons/facebook-circle.svg') }}";
        var socialLogoPinterest = "{{ asset('assets/img/icons/pinterest-circle.svg') }}";
        var socialLogoTiktok = "{{ asset('assets/img/icons/tiktok-circle.svg') }}";

        $(document).on('click', '.btn-view-accounts', function() {
            var userId = $(this).data('user-id');
            var userName = $(this).data('user-name');
            $('#modalUserName').text('— ' + userName);
            $('#socialAccountsModal').modal('show');
            $('#socialAccountsLoading').show();
            $('#socialAccountsContent').hide();
            $('#socialAccountsError').hide();

            $.ajax({
                url: "{{ route('admin.users.socialAccounts', ['user' => '__ID__']) }}".replace('__ID__', userId),
                type: 'GET',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function(response) {
                    $('#socialAccountsLoading').hide();
                    if (response.success) {
                        $('#socialAccountsContent').show();
                        var summary = response.summary;
                        var summaryText = 'Total: <strong>' + summary.total + '</strong> connected account(s)';
                        if (summary.limit !== null && summary.limit > 0) {
                            summaryText += ' | Limit: ' + summary.limit + ' | Remaining: <strong>' + summary.remaining + '</strong>';
                        }
                        $('#socialAccountsSummary').html(summaryText);

                        renderAccounts('facebook', response.accounts.facebook, '#facebookAccountsList', '#facebookCount', socialLogoFacebook, 'facebook');
                        renderAccounts('pinterest', response.accounts.pinterest, '#pinterestAccountsList', '#pinterestCount', socialLogoPinterest, 'pinterest');
                        renderAccounts('tiktok', response.accounts.tiktok, '#tiktokAccountsList', '#tiktokCount', socialLogoTiktok, 'tiktok');
                    } else {
                        $('#socialAccountsError').text(response.message || 'Failed to load accounts').show();
                    }
                },
                error: function(xhr) {
                    $('#socialAccountsLoading').hide();
                    var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Failed to load social accounts.';
                    $('#socialAccountsError').text(msg).show();
                }
            });
        });

        function renderAccounts(platform, accounts, gridSelector, countSelector, fallbackLogo, platformClass) {
            $(gridSelector).empty();
            $(countSelector).text(accounts.length);
            if (accounts.length === 0) {
                var emptyHtml = '<div class="text-muted small py-2">No accounts connected</div>';
                $(gridSelector).append(emptyHtml);
            } else {
                accounts.forEach(function(acc) {
                    var name = (acc.name || acc.username || 'Unknown').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
                    var imgSrc = acc.profile_image || fallbackLogo;
                    var cardHtml = '<article class="account-card ' + platformClass + '-card has-tooltip" data-tooltip="' + (acc.username || name).replace(/"/g, '&quot;') + '">' +
                        '<div class="account-card-accent"></div>' +
                        '<div class="account-card-link">' +
                        '<div class="account-card-content">' +
                        '<div class="account-avatar-wrapper">' +
                        '<img src="' + imgSrc + '" class="account-avatar" loading="lazy" onerror="this.onerror=null; this.src=\'' + fallbackLogo + '\'">' +
                        '<span class="platform-indicator ' + platformClass + '-indicator"><i class="fab fa-' + (platformClass === 'facebook' ? 'facebook-f' : (platformClass === 'pinterest' ? 'pinterest-p' : 'tiktok')) + '"></i></span>' +
                        '</div>' +
                        '<div class="account-info">' +
                        '<div class="account-username">' + name + '</div>' +
                        '</div>' +
                        '</div>' +
                        '</div>' +
                        '</article>';
                    $(gridSelector).append(cardHtml);
                });
            }
        }

        // server side dataTable
        $('#dataTable').DataTable({
            "paging": true,
            'iDisplayLength': 10,
            "lengthChange": true,
            "searching": true,
            "ordering": true,
            "info": true,
            "autoWidth": false,
            "responsive": true,
            "processing": true,
            "serverSide": true,
            ajax: {
                url: "{{ route('admin.users.dataTable') }}",
            },
            order: [[0, 'desc']],
            columns: [{
                    data: 'id'
                },
                {
                    data: 'name_link',
                    orderable: false
                },
                {
                    data: 'email'
                },
                {
                    data: 'package_html',
                    name: 'package_html',
                    orderable: false
                },
                {
                    data: 'role_name',
                    name: 'role_name'
                },
                {
                    data: 'status_span',
                    orderable: false
                },
                {
                    data: 'action',
                    orderable: false,
                    searchable: false
                }
            ],
        });
    </script>
@endpush
