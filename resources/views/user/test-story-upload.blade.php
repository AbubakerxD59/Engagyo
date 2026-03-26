@extends('user.layout.main')
@section('title', 'Test Facebook Story Upload')
@section('page_content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Test Facebook Story Upload</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('panel.schedule') }}">Schedule</a></li>
                    <li class="breadcrumb-item active">Test Story Upload</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Upload a Story (Image or Video)</h3>
            </div>
            <div class="card-body">
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('panel.test.story-upload.post') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group">
                        <label for="page_id">Facebook Page</label>
                        <select name="page_id" id="page_id" class="form-control" required>
                            <option value="">Select a page</option>
                            @foreach($pages as $page)
                                <option value="{{ $page->id }}">{{ $page->name }} (ID: {{ $page->id }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="file">Story File (image or video)</label>
                        <input type="file" name="file" id="file" class="form-control-file" required>
                        <small class="form-text text-muted">
                            Supported: JPEG, PNG, GIF, WebP, MP4, MOV, MKV, MPEG, WEBM.
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="caption">Caption (optional, used when supported)</label>
                        <textarea name="caption" id="caption" rows="2" class="form-control"
                                  placeholder="Optional caption for the story">{{ old('caption') }}</textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Upload &amp; Publish Story (show steps)
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection

