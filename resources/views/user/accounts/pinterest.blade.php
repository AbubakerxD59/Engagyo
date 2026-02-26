@extends('user.layout.main')
@section('title', 'Pinterest')
@section('page_content')
    <div class="page-content">
        <div class="content-header clearfix"></div>
        <section class="content">
            <div class="container-fluid">
                {{-- Pinterest --}}
                <div class="card">
                    <div class="card-header with-border clearfix">
                        <div class="card-title">
                            <img src="{{ social_logo('pinterest') }}" loading="lazy">
                            <span>{{ $pinterest->username }}</span>
                        </div>
                        <a href="{{ $pinterestUrl }}" class="btn btn-outline-primary btn-sm mx-2">Reauthorize</a>
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
                                    <td>Pin ID</td>
                                    <td>Board ID</td>
                                    <td>Token Status</td>
                                    <td>Token Expires</td>
                                    <td>Action</td>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($pinterest->load('boards')->boards as $board)
                                    <tr>
                                        <td>{{ $board->id }}</td>
                                        <td>{{ $board->name }}</td>
                                        <td>{{ $board->pin_id }}</td>
                                        <td>{{ $board->board_id }}</td>
                                        <td>
                                            @if ($pinterest->validToken())
                                                <span class="badge badge-success">Valid</span>
                                            @else
                                                <span class="badge badge-danger">Expired</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($pinterest->expires_in)
                                                {{ date('Y-m-d H:i:s', $pinterest->expires_in) }}
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                        <td>
                                            <div>
                                                <button class="btn btn-outline-danger btn-sm delete-btn">Delete</button>
                                                <form action="{{ route('panel.accounts.board.delete', $board->board_id) }}"
                                                    method="POST" class="delete_form">
                                                    @csrf
                                                    @method('DELETE')
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
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
        var dataTable = $('#dataTable').DataTable({
            "paging": true,
            'iDisplayLength': 10,
            "lengthChange": true,
            "searching": true,
            "ordering": false,
            "info": false,
        });
    </script>
@endpush
