@extends('user.layout.main')
@section('title', 'Test Instagram Image Publish')
@section('page_content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Test Instagram Image Publish</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('panel.schedule') }}">Schedule</a></li>
                    <li class="breadcrumb-item active">Test Instagram Image</li>
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

        @if (!empty($steps ?? []))
            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title mb-0">Publish steps</h3>
                </div>
                <div class="card-body">
                    @foreach ($steps as $step)
                        <div class="test-step mb-4 p-3 rounded border
                            @if ($step['status'] === 'success') border-success bg-light
                            @elseif($step['status'] === 'error') border-danger bg-light
                            @else border-info bg-light @endif">
                            <div class="d-flex align-items-center mb-2 flex-wrap">
                                <span class="badge
                                    @if ($step['status'] === 'success') badge-success
                                    @elseif($step['status'] === 'error') badge-danger
                                    @else badge-info @endif mr-2">Step {{ $step['id'] }}</span>
                                <h5 class="mb-0 mr-2">{{ $step['title'] }}</h5>
                                <span class="badge badge-{{ $step['status'] === 'success' ? 'success' : ($step['status'] === 'error' ? 'danger' : 'info') }}">
                                    {{ strtoupper($step['status']) }}
                                </span>
                            </div>
                            <pre class="mb-0 p-3 bg-dark text-light rounded" style="max-height: 400px; overflow: auto; font-size: 12px;"><code>{{ json_encode($step['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</code></pre>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if (isset($post) && $post)
            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title mb-0">Post summary</h3>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-3">ID</dt>
                        <dd class="col-sm-9">{{ $post->id }}</dd>
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
                        <dt class="col-sm-3">Instagram media id</dt>
                        <dd class="col-sm-9">{{ $post->post_id ?? '—' }}</dd>
                        <dt class="col-sm-3">Image file</dt>
                        <dd class="col-sm-9"><code>uploads/{{ $post->image }}</code></dd>
                    </dl>
                </div>
            </div>
        @endif

        <div class="card">
            <div class="card-header">
                <h3 class="card-title mb-0">Upload image</h3>
            </div>
            <div class="card-body">
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if ($accounts->isEmpty())
                    <p class="text-muted mb-0">No Instagram accounts available. Connect one under <a href="{{ route('panel.accounts.instagram') }}">Accounts → Instagram</a>.</p>
                @else
                    <p class="text-muted small">Meta must fetch your image over <strong>public HTTPS</strong>. Configure <code>APP_URL</code> or <code>INSTAGRAM_IMAGE_PUBLIC_BASE_URL</code> accordingly.</p>
                    <form action="{{ route('panel.test.instagram-image.post') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group">
                            <label for="instagram_account_id">Instagram account</label>
                            <select name="instagram_account_id" id="instagram_account_id" class="form-control" required>
                                <option value="">Select account</option>
                                @foreach ($accounts as $acc)
                                    <option value="{{ $acc->id }}" @selected(old('instagram_account_id') == $acc->id)>
                                        @if ($acc->username)
                                            {{ $acc->username }}
                                        @else
                                            Account #{{ $acc->id }}
                                        @endif
                                        (id {{ $acc->id }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="image">Image</label>
                            <input type="file" name="image" id="image" class="form-control-file" accept="image/jpeg,image/png,image/webp" required>
                            <small class="form-text text-muted">JPEG, PNG, or WebP.</small>
                        </div>
                        <div class="form-group">
                            <label for="caption">Caption (optional)</label>
                            <textarea name="caption" id="caption" rows="3" class="form-control" maxlength="2200" placeholder="Caption">{{ old('caption') }}</textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fab fa-instagram"></i> Create post &amp; publish
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</section>
@endsection
