@extends('user.layout.main')
@section('title', 'LinkedIn Publish Test')

@section('page_content')
    <div class="page-content">
        <section class="content pt-3">
            <div class="container-fluid">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title mb-0">LinkedIn Content Publishing - Manual Test</h3>
                    </div>
                    <div class="card-body">
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0 pl-3">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('panel.linkedin-publish-test.publish') }}">
                            @csrf
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="font-weight-bold">LinkedIn Account</label>
                                    <select name="linkedin_account_id" class="form-control" required>
                                        <option value="">Select account</option>
                                        @foreach ($accounts as $account)
                                            <option value="{{ $account->id }}" {{ (string) old('linkedin_account_id') === (string) $account->id ? 'selected' : '' }}>
                                                {{ $account->username }} ({{ $account->linkedin_id }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="font-weight-bold">Post Type</label>
                                    <select name="type" class="form-control" required>
                                        @php($type = old('type', 'content_only'))
                                        <option value="content_only" {{ $type === 'content_only' ? 'selected' : '' }}>Text</option>
                                        <option value="photo" {{ $type === 'photo' ? 'selected' : '' }}>Image</option>
                                        <option value="video" {{ $type === 'video' ? 'selected' : '' }}>Video</option>
                                        <option value="carousel" {{ $type === 'carousel' ? 'selected' : '' }}>Carousel (multiple images)</option>
                                        <option value="document" {{ $type === 'document' ? 'selected' : '' }}>Document</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="font-weight-bold">Title</label>
                                    <input type="text" name="title" class="form-control" value="{{ old('title') }}" placeholder="Optional title">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="font-weight-bold">Comment</label>
                                    <input type="text" name="comment" class="form-control" value="{{ old('comment') }}" placeholder="Optional comment">
                                </div>
                                <div class="col-md-12">
                                    <hr>
                                    <p class="text-muted mb-2">Fill media fields based on selected type.</p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="font-weight-bold">Image URL (photo)</label>
                                    <input type="url" name="image_url" class="form-control" value="{{ old('image_url') }}" placeholder="https://...">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="font-weight-bold">Video URL (video)</label>
                                    <input type="url" name="video_url" class="form-control" value="{{ old('video_url') }}" placeholder="https://...">
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label class="font-weight-bold">Carousel Image URLs (carousel)</label>
                                    <textarea name="carousel_urls" rows="4" class="form-control" placeholder="One image URL per line">{{ old('carousel_urls') }}</textarea>
                                </div>
                                <div class="col-md-8 mb-3">
                                    <label class="font-weight-bold">Document URL (document)</label>
                                    <input type="url" name="document_url" class="form-control" value="{{ old('document_url') }}" placeholder="https://...">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="font-weight-bold">Document Name</label>
                                    <input type="text" name="document_name" class="form-control" value="{{ old('document_name') }}" placeholder="Optional display name">
                                </div>
                                <div class="col-md-12">
                                    <button type="submit" class="btn btn-primary">Run Publish Test</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                @if (! empty($steps))
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title mb-0">Step Responses</h3>
                        </div>
                        <div class="card-body">
                            @foreach ($steps as $step)
                                <div class="mb-3">
                                    <div>
                                        <span class="badge badge-{{ ($step['status'] ?? '') === 'ok' ? 'success' : (($step['status'] ?? '') === 'error' ? 'danger' : 'secondary') }}">
                                            {{ strtoupper($step['status'] ?? 'info') }}
                                        </span>
                                        <strong class="ml-2">{{ $step['step'] ?? 'step' }}</strong>
                                    </div>
                                    <pre class="json-block mt-2">{{ json_encode($step['payload'] ?? null, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if (! empty($result))
                    <div class="alert alert-{{ !empty($result['success']) ? 'success' : 'danger' }}">
                        {{ !empty($result['success']) ? 'LinkedIn publish test succeeded.' : 'LinkedIn publish test failed.' }}
                    </div>
                @endif
            </div>
        </section>
    </div>
@endsection

@push('styles')
    <style>
        .json-block {
            background: #111827;
            color: #d1fae5;
            border-radius: 6px;
            padding: 12px;
            font-size: 12px;
            overflow: auto;
            margin: 0;
        }
    </style>
@endpush

