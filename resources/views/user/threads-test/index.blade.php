@extends('user.layout.main')
@section('title', 'Threads Publish Test')

@section('page_content')
    <div class="page-content">
        <section class="content">
            <div class="container-fluid">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Threads Publish Test</h3>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">
                            Upload multiple files, choose a Threads post type, and publish while viewing each step.
                        </p>

                        <form action="{{ route('panel.threads-test.publish') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="form-group">
                                <label for="thread_id">Threads Account</label>
                                <select class="form-control" id="thread_id" name="thread_id" required>
                                    <option value="">Select account</option>
                                    @foreach ($threadsAccounts as $thread)
                                        <option value="{{ $thread->id }}" {{ (string) old('thread_id') === (string) $thread->id ? 'selected' : '' }}>
                                            {{ '@' . ($thread->username ?? 'threads') }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="post_type">Post Type</label>
                                <select class="form-control" id="post_type" name="post_type" required>
                                    @php($oldType = old('post_type', 'text'))
                                    <option value="text" {{ $oldType === 'text' ? 'selected' : '' }}>Text</option>
                                    <option value="image" {{ $oldType === 'image' ? 'selected' : '' }}>Image</option>
                                    <option value="video" {{ $oldType === 'video' ? 'selected' : '' }}>Video</option>
                                    <option value="carousel" {{ $oldType === 'carousel' ? 'selected' : '' }}>Carousel</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="caption">Caption</label>
                                <textarea class="form-control" id="caption" name="caption" rows="3"
                                    placeholder="Write your post text...">{{ old('caption') }}</textarea>
                            </div>

                            <div class="form-group">
                                <label for="files">Files</label>
                                <input type="file" class="form-control" id="files" name="files[]" multiple>
                                <small class="form-text text-muted">
                                    Image/Video needs at least 1 file. Carousel needs 2-20 files. Text ignores files.
                                </small>
                            </div>

                            <button type="submit" class="btn btn-primary">Publish Test Post</button>
                        </form>
                    </div>
                </div>

                @if ($errors->any())
                    <div class="card">
                        <div class="card-header bg-danger text-white">
                            <strong>Validation Errors</strong>
                        </div>
                        <div class="card-body">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                @php($result = session('threads_test_result'))
                @if ($result)
                    <div class="card">
                        <div class="card-header {{ !empty($result['success']) ? 'bg-success' : 'bg-danger' }} text-white">
                            <strong>{{ !empty($result['success']) ? 'Publish Succeeded' : 'Publish Failed' }}</strong>
                        </div>
                        <div class="card-body">
                            @if (!empty($result['post_id']))
                                <p><strong>Post ID:</strong> {{ $result['post_id'] }}</p>
                            @endif

                            <h5>Steps</h5>
                            <ol class="pl-3">
                                @foreach (($result['steps'] ?? []) as $step)
                                    <li class="mb-3">
                                        <div>
                                            <strong>{{ $step['title'] ?? 'Step' }}</strong>
                                            <span class="badge {{ ($step['status'] ?? '') === 'ok' ? 'badge-success' : 'badge-danger' }}">
                                                {{ strtoupper($step['status'] ?? 'info') }}
                                            </span>
                                        </div>
                                        @if (!empty($step['meta']))
                                            <pre class="bg-light p-2 mt-2 mb-0" style="white-space: pre-wrap;">{{ json_encode($step['meta'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                        @endif
                                    </li>
                                @endforeach
                            </ol>
                        </div>
                    </div>
                @endif
            </div>
        </section>
    </div>
@endsection
