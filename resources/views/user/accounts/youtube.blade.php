@extends('user.layout.main')
@section('title', 'YouTube')
@section('page_content')
    <div class="page-content">
        <div class="content-header clearfix"></div>
        <section class="content">
            <div class="container-fluid">
                <div class="card">
                    <div class="card-header with-border clearfix">
                        <div class="card-title">
                            <img src="{{ social_logo('youtube') }}" loading="lazy">
                            <span>{{ $youtube->username }}</span>
                        </div>
                        <a href="{{ $youtubeUrl }}" class="btn btn-outline-primary btn-sm mx-2">Reauthorize</a>
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
                                    <td>Channel Name</td>
                                    <td>Custom URL</td>
                                    <td>Channel ID</td>
                                    <td>Token Status</td>
                                    <td>Token Expires</td>
                                    <td>Action</td>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>{{ $youtube->id }}</td>
                                    <td>{{ $youtube->username }}</td>
                                    <td>{{ $youtube->custom_url ? '@'.$youtube->custom_url : 'N/A' }}</td>
                                    <td>{{ $youtube->channel_id }}</td>
                                    <td>
                                        @if ($youtube->validToken())
                                            <span class="badge badge-success">Valid</span>
                                        @else
                                            <span class="badge badge-danger">Expired</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($youtube->expires_in)
                                            {{ date('Y-m-d H:i:s', $youtube->expires_in) }}
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                    <td>
                                        <div>
                                            <button class="btn btn-outline-danger btn-sm delete-btn">Delete</button>
                                            <form action="{{ route('panel.accounts.youtube.delete', $youtube->channel_id) }}"
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
            width: 30px;
            height: 30px;
            margin-right: 10px;
        }
    </style>
@endpush
@push('scripts')
    <script>
        $(document).ready(function() {
            $('.delete-btn').on('click', function() {
                if (confirm('Are you sure you want to delete this YouTube channel?')) {
                    $(this).siblings('.delete_form').submit();
                }
            });
        });
    </script>
@endpush
