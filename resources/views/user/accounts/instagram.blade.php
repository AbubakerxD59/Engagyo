@extends('user.layout.main')
@section('title', 'Instagram')
@section('page_content')
    <div class="page-content">
        <div class="content-header clearfix"></div>
        <section class="content">
            <div class="container-fluid">
                <div class="card">
                    <div class="card-header with-border clearfix">
                        <div class="card-title">
                            <img src="{{ social_logo('instagram') }}" loading="lazy">
                            <span>{{ $instagram->username }}</span>
                        </div>
                        <a href="{{ $instagramUrl }}" class="btn btn-outline-primary btn-sm mx-2">Reconnect</a>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <table class="table table-striped table-bordered" id="dataTable">
                            <thead>
                                <tr>
                                    <td>ID</td>
                                    <td>Username</td>
                                    <td>Name</td>
                                    <td>Instagram User ID</td>
                                    <td>Facebook Page ID</td>
                                    <td>Token Status</td>
                                    <td>Token Expires</td>
                                    <td>Action</td>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>{{ $instagram->id }}</td>
                                    <td>{{ $instagram->username }}</td>
                                    <td>{{ $instagram->name ?? 'N/A' }}</td>
                                    <td>{{ $instagram->ig_user_id }}</td>
                                    <td>{{ $instagram->page_id }}</td>
                                    <td>
                                        @if ($instagram->validToken())
                                            <span class="badge badge-success">Valid</span>
                                        @else
                                            <span class="badge badge-danger">Expired</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($instagram->expires_in)
                                            {{ date('Y-m-d H:i:s', $instagram->expires_in) }}
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                    <td>
                                        <div>
                                            <button class="btn btn-outline-danger btn-sm delete-btn">Delete</button>
                                            <form action="{{ route('panel.accounts.instagram.delete', $instagram->ig_user_id) }}"
                                                method="POST" class="delete_form">
                                                @csrf
                                                @method('DELETE')
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
@push('styles')
    <style>
        .card-title {
            padding-inline: 10px;
            border-right: 1px solid black;
        }

        .card-title img {
            max-height: 28px;
            margin-right: 8px;
        }
    </style>
@endpush
