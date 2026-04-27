@extends('user.layout.main')
@section('title', 'LinkedIn')
@section('page_content')
    <div class="page-content">
        <div class="content-header clearfix"></div>
        <section class="content">
            <div class="container-fluid">
                <div class="card">
                    <div class="card-header with-border clearfix">
                        <div class="card-title">
                            <img src="{{ social_logo('linkedin') }}" loading="lazy">
                            <span>{{ $linkedin->username }}</span>
                        </div>
                        <a href="{{ $linkedinUrl }}" class="btn btn-outline-primary btn-sm mx-2">Reauthorize</a>
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
                                    <td>Name</td>
                                    <td>Email</td>
                                    <td>LinkedIn ID</td>
                                    <td>Token Status</td>
                                    <td>Token Expires</td>
                                    <td>Action</td>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>{{ $linkedin->id }}</td>
                                    <td>{{ $linkedin->username }}</td>
                                    <td>{{ $linkedin->email ?? 'N/A' }}</td>
                                    <td>{{ $linkedin->linkedin_id }}</td>
                                    <td>
                                        @if ($linkedin->validToken())
                                            <span class="badge badge-success">Valid</span>
                                        @else
                                            <span class="badge badge-danger">Expired</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($linkedin->expires_in)
                                            {{ date('Y-m-d H:i:s', $linkedin->expires_in) }}
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                    <td>
                                        <div>
                                            <button class="btn btn-outline-danger btn-sm delete-btn">Delete</button>
                                            <form action="{{ route('panel.accounts.linkedin.delete', $linkedin->linkedin_id) }}"
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
        }

        .card-title span {
            font-weight: 600
        }
    </style>
@endpush
@push('scripts')
    <script>
        $(document).ready(function() {
            $('#dataTable').DataTable({
                "paging": true,
                'iDisplayLength': 10,
                "lengthChange": true,
                "searching": true,
                "ordering": false,
                "info": false,
            });
        });
    </script>
@endpush
