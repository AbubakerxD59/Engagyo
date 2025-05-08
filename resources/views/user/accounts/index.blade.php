@extends('user.layout.main')
@section('title', 'Accounts')
@section('page_content')
    <div class="page-content">
        <div class="content-header clearfix"></div>
        <section class="content">
            <div class="container-fluid">
                {{-- Facebook --}}
                <div class="card">
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
                        <div class="d-flex">
                            @foreach ($user->load('facebook')->facebook as $fb)
                                <article class="account_box col-md-3 mx-1">
                                    <a href="{{ route('panel.accounts.facebook', $fb->fb_id) }}">
                                        <div class="d-flex align-items-center">
                                            <picture>
                                                <img src="{{ $fb->profile_image }}" alt="{{ no_image() }}"
                                                    class="rounded-pill logo">
                                            </picture>
                                            <div class="account_name">{{ $fb->username }}</div>
                                        </div>
                                    </a>
                                    <div>
                                        <button class="btn btn-outline-danger btn-sm delete-btn border-0"
                                            onclick="confirmDelete(event)">
                                            <i class="fa fa-trash px-2"></i>
                                        </button>
                                        <form action="{{ route('panel.accounts.facebook.delete', $fb->fb_id) }}"
                                            method="POST" class="delete_form">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </div>
                </div>
                {{-- Pinterest --}}
                <div class="card">
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
                        <div class="d-flex">
                            @foreach ($user->load('pinterest')->pinterest as $pin)
                                <article class="account_box col-md-3 mx-1">
                                    <a href="{{ route('panel.accounts.pinterest', $pin->pin_id) }}">
                                        <div class="d-flex align-items-center">
                                            <picture>
                                                <img src="{{ $pin->profile_image }}" alt="{{ no_image() }}"
                                                    class="rounded-pill logo">
                                            </picture>
                                            <div class="account_name">{{ $pin->username }}</div>
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
                </div>
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
    </style>
@endpush
@push('scripts')
    <script>
        $(document).ready(function() {
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
