@extends('user.layout.main')
@section('title', 'URL Tracking')
@section('page_content')
    <div class="page-content">
        @include('user.layout.feature-limit-alert')
        <div class="content-header clearfix"></div>
        <section class="content">
            <div class="container-fluid">
                <div class="card">
                    <div class="card-header with-border clearfix">
                        <div class="card-title">
                            <span>Url Tracking</span>
                            <button type="button" class="btn btn-primary btn-sm mr-2" data-toggle="modal"
                                data-target="#createUtmModal">
                                <i class="fas fa-plus mr-1"></i> Add UTM Code
                            </button>
                        </div>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        {{-- UTM Codes List --}}
                        @if ($utmCodes->count() > 0)
                            <div class="table-responsive">
                                <table id="utmCodesTable" class="table table-hover">
                                    <thead class="thead-light">
                                        <tr>
                                            <th style="width: 20%;">Domain</th>
                                            <th style="width: 60%;">UTM Codes</th>
                                            <th style="width: 20%;" class="text-center">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($utmCodes as $domainName => $codes)
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        {{ $domainName }}
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="d-flex flex-wrap align-items-center">
                                                        @foreach ($codes as $code)
                                                            <div
                                                                class="d-inline-flex align-items-center bg-light px-3 py-2 rounded border mr-2 mb-2">
                                                                <code
                                                                    class="text-primary font-weight-bold">{{ $code->utm_key }}</code>
                                                                <span class="mx-2">=</span>
                                                                <code class="text-dark">{{ $code->value }}</code>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    <div class="btn-group btn-group-sm">
                                                        <button type="button" class="btn btn-primary edit-domain-btn"
                                                            data-domain="{{ $domainName }}" title="Edit Domain UTM Codes">
                                                            <i class="fas fa-edit"></i> Edit
                                                        </button>
                                                        <button type="button" class="btn btn-danger delete-domain-btn"
                                                            data-domain="{{ $domainName }}"
                                                            title="Delete All UTM Codes for This Domain">
                                                            <i class="fas fa-trash"></i> Delete
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-5">
                                <i class="fas fa-link fa-4x text-muted mb-3"></i>
                                <h5 class="text-muted">No UTM Codes Configured</h5>
                                <p class="text-muted mb-3">Add your first UTM code to start tracking URLs automatically</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </section>
    </div>

    @include('user.url-tracking.modals.create-update-modal')
@endsection

@push('scripts')
    @include('user.url-tracking.assets.script')
@endpush
