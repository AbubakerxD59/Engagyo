@extends('user.layout.main')
@section('title', 'Test Publish Facebook Post')
@section('page_content')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Test Publish Facebook Post #{{ $postId }}</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a></a>Schedule</a></li>
                        <li class="breadcrumb-item active">Test Publish</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            @if (isset($error))
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> {{ $error }}
                </div>
            @endif

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Publish Steps</h3>
                    <div class="card-tools">
                        <a class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Schedule
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @forelse($steps ?? [] as $step)
                        <div
                            class="test-step mb-4 p-3 rounded border
                    @if ($step['status'] === 'success') border-success bg-light
                    @elseif($step['status'] === 'error') border-danger bg-light
                    @else border-info bg-light @endif">
                            <div class="d-flex align-items-center mb-2">
                                <span
                                    class="badge
                            @if ($step['status'] === 'success') badge-success
                            @elseif($step['status'] === 'error') badge-danger
                            @else badge-info @endif
                            mr-2">Step
                                    {{ $step['id'] }}</span>
                                <h5 class="mb-0">{{ $step['title'] }}</h5>
                                <span
                                    class="badge badge-{{ $step['status'] === 'success' ? 'success' : ($step['status'] === 'error' ? 'danger' : 'info') }} ml-2">
                                    {{ strtoupper($step['status']) }}
                                </span>
                            </div>
                            <div class="test-step-data mt-2">
                                <pre class="mb-0 p-3 bg-dark text-light rounded" style="max-height: 400px; overflow: auto; font-size: 12px;"><code>{{ json_encode($step['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</code></pre>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted">No steps to display.</p>
                    @endforelse
                </div>
            </div>

            @if (isset($post) && $post)
                <div class="card mt-3">
                    <div class="card-header">
                        <h3 class="card-title">Post Summary</h3>
                    </div>
                    <div class="card-body">
                        <dl class="row">
                            <dt class="col-sm-3">ID</dt>
                            <dd class="col-sm-9">{{ $post->id }}</dd>

                            <dt class="col-sm-3">Type</dt>
                            <dd class="col-sm-9">{{ $post->type }}</dd>

                            <dt class="col-sm-3">Status</dt>
                            <dd class="col-sm-9">
                                @if ($post->status == 1)
                                    <span class="badge badge-success">Published</span>
                                @elseif($post->status == -1)
                                    <span class="badge badge-danger">Failed</span>
                                @else
                                    <span class="badge badge-warning">Pending</span>
                                @endif
                            </dd>

                            <dt class="col-sm-3">Facebook Post ID</dt>
                            <dd class="col-sm-9">{{ $post->post_id ?? '—' }}</dd>

                            <dt class="col-sm-3">Title</dt>
                            <dd class="col-sm-9">{{ Str::limit($post->title, 100) }}</dd>

                            <dt class="col-sm-3">Page</dt>
                            <dd class="col-sm-9">{{ $post->page?->name ?? '—' }}</dd>
                        </dl>
                    </div>
                </div>
            @endif
        </div>
    </section>
@endsection
