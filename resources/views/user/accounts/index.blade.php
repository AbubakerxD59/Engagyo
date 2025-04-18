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
                            <input type="hidden" id="pinterestAcc" value="{{ session_check('items') ? 1 : 0 }}">
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
                                <a href="" class="account_box col-md-3">
                                    <article>
                                        <picture>
                                            <img src="{{ $pin->profile_image }}" alt="{{ no_image() }}"
                                                class="rounded-pill">
                                        </picture>
                                        <div>
                                            <div class="account_name">{{ $pin->username }}</div>
                                        </div>
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
        @include('user.accounts.modals.connect_pinterest_modal', [
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
    </style>
@endpush
@push('scripts')
    <script>
        $(document).ready(function() {
            var pinAcc = $('#pinterestAcc').val();
            console.log(pinAcc);
            if (pinAcc) {
                $('#connect_pinterest_modal').modal('toggle');
            }
        });
    </script>
@endpush
