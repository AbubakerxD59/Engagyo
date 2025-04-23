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
                            <img src="{{ social_logo('facebook') }}">
                            <span>Facebook</span>
                        </div>
                        <button class="btn btn-outline-primary btn-sm mx-2">+ Connect</button>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">

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
                                <a href="{{ route('panel.accounts.pinterest', $pin->id) }}"
                                    class="account_box col-md-3 mx-1">
                                    <article>
                                        <picture>
                                            <img src="{{ $pin->profile_image }}" alt="{{ no_image() }}"
                                                class="rounded-pill">
                                        </picture>
                                        <div>
                                            <div class="account_name">{{ $pin->username }}</div>
                                        </div>
                                        <a href="{{ route('panel.accounts.pinterest.delete', $pin->id) }}"
                                            class="btn btn-outline-primary">
                                            Delete
                                        </a>
                                    </article>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
    @if (!empty(session_get('items')))
        @include('user.accounts.modals.pinterest_boards_modal', [
            'pinterest' => $user->pinterest()->latest()->first(),
        ])
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

        .pinterest_connect {
            color: #008100 !important;
        }
    </style>
@endpush
@push('scripts')
    <script>
        $(document).ready(function() {
            var pinAcc = $('#pinterestAcc').val();
            if (pinAcc == 1) {
                $('#connectPinterestModal').modal('toggle');
                {{ session_delete('pinterest_auth') }}
            }
            $('.pinterest_connect').on('click', function() {
                var button = $(this);
                var id = button.data('id');
                var pin_id = button.data('pin-id');
                var token = $('meta[name="csrf-token"]').attr('content');
                $.ajax({
                    url: "{{ route('pinterest.addBoard') }}",
                    type: 'POST',
                    data: {
                        "id": id,
                        "pin_id": pin_id,
                        "_token": token
                    },
                    success: function(response) {
                        console.log(response);
                        toastr.success("Board Connected Successfully!");
                        button.text('Connected').removeClass('pinterest_connect pointer');
                    },
                });
            });
        });
    </script>
@endpush
