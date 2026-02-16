@extends('user.layout.main')
@section('title', 'Link Shortener')
@section('page_content')
    <div class="page-content">
        @include('user.layout.feature-limit-alert')
        <div class="content-header clearfix"></div>
        <section class="content">
            <div class="container-fluid">
                <div class="card">
                    <div class="card-header with-border clearfix">
                        <div class="card-title">
                            <span>Link Shortener</span>
                            <button type="button" class="btn btn-primary btn-sm ml-2" data-toggle="modal"
                                data-target="#createShortLinkModal">
                                <i class="fas fa-plus mr-1"></i> Shorten a Link
                            </button>
                        </div>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3">
                            Create short links that redirect to your original URLs. Share the short link anywhere; when someone clicks it, they will be redirected to the original URL.
                        </p>
                        @if ($shortLinks->count() > 0)
                            <div class="table-responsive">
                                <table id="shortLinksTable" class="table table-hover">
                                    <thead class="thead-light">
                                        <tr>
                                            <th style="width: 35%;">Original URL</th>
                                            <th style="width: 30%;">Short Link</th>
                                            <th style="width: 10%;" class="text-center">Clicks</th>
                                            <th style="width: 25%;" class="text-center">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($shortLinks as $link)
                                            <tr data-id="{{ $link->id }}">
                                                <td>
                                                    <a href="{{ $link->original_url }}" target="_blank" rel="noopener"
                                                        class="text-break" title="{{ $link->original_url }}">
                                                        {{ Str::limit($link->original_url, 50) }}
                                                    </a>
                                                </td>
                                                <td>
                                                    <div class="input-group input-group-sm">
                                                        <input type="text" class="form-control short-url-input"
                                                            value="{{ url('/s/' . $link->short_code) }}" readonly>
                                                        <div class="input-group-append">
                                                            <button type="button" class="btn btn-outline-primary copy-btn"
                                                                data-url="{{ url('/s/' . $link->short_code) }}"
                                                                title="Copy short link">
                                                                <i class="fas fa-copy"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge badge-info">{{ number_format($link->clicks) }}</span>
                                                </td>
                                                <td class="text-center">
                                                    <div class="btn-group btn-group-sm">
                                                        <button type="button" class="btn btn-primary edit-link-btn"
                                                            data-id="{{ $link->id }}"
                                                            data-original-url="{{ $link->original_url }}"
                                                            title="Edit destination URL">
                                                            <i class="fas fa-edit"></i> Edit
                                                        </button>
                                                        <button type="button" class="btn btn-danger delete-link-btn"
                                                            data-id="{{ $link->id }}"
                                                            data-original-url="{{ Str::limit($link->original_url, 40) }}"
                                                            title="Delete short link">
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
                                <h5 class="text-muted">No Short Links Yet</h5>
                                <p class="text-muted mb-3">Create your first short link to get started</p>
                                <button type="button" class="btn btn-primary" data-toggle="modal"
                                    data-target="#createShortLinkModal">
                                    <i class="fas fa-plus mr-1"></i> Shorten a Link
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </section>
    </div>

    @include('user.link-shortener.modals.create-modal')
    @include('user.link-shortener.modals.edit-modal')
@endsection

@push('scripts')
    @include('user.link-shortener.assets.script')
@endpush
