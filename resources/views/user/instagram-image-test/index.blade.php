@extends('user.layout.main')
@section('title', 'Instagram image publish test')
@section('page_content')
    <div class="page-content">
        <div class="content-header clearfix"></div>
        <section class="content">
            <div class="container-fluid">
                <div class="card card-warning card-outline">
                    <div class="card-header with-border">
                        <h3 class="card-title">
                            <i class="fab fa-instagram mr-2"></i>Instagram image publish test
                        </h3>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">
                            This uses the same publishing flow as scheduled posts (photo → Content Publishing API).
                            Enable with <code>APP_DEBUG=true</code> or <code>INSTAGRAM_IMAGE_PUBLISH_TEST=true</code> in <code>.env</code>.
                            Requires a public HTTPS URL for your app so Meta can fetch the image.
                        </p>

                        @if (session('test_success'))
                            <div class="alert alert-success">
                                <strong>{{ session('test_success.message') }}</strong>
                                <ul class="mb-0 mt-2">
                                    <li>Instagram media ID: <code>{{ session('test_success.media_id') }}</code></li>
                                    <li>Local post row: <code>{{ session('test_success.local_post_id') }}</code></li>
                                </ul>
                            </div>
                        @endif

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
                            <div class="alert alert-info mb-0">
                                No Instagram accounts found. Connect one under <a href="{{ route('panel.accounts.instagram') }}">Accounts → Instagram</a>.
                            </div>
                        @else
                            <form method="post" action="{{ route('panel.instagram.image-test.publish') }}" enctype="multipart/form-data">
                                @csrf
                                <div class="form-group">
                                    <label for="instagram_account_id">Instagram account</label>
                                    <select name="instagram_account_id" id="instagram_account_id" class="form-control @error('instagram_account_id') is-invalid @enderror" required>
                                        <option value="">— Select —</option>
                                        @foreach ($accounts as $acc)
                                            <option value="{{ $acc->id }}" @selected(old('instagram_account_id') == $acc->id)>
                                                {{ $acc->name ?: $acc->username ?: ('Account #'.$acc->id) }}
                                                @if ($acc->username)
                                                    ({{ '@'.$acc->username }})
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="image">Image</label>
                                    <input type="file" name="image" id="image" class="form-control-file @error('image') is-invalid @enderror" accept="image/*" required>
                                    <small class="form-text text-muted">JPEG/PNG/WebP etc. Non-JPEG types are converted for Instagram when publishing.</small>
                                </div>
                                <div class="form-group">
                                    <label for="caption">Caption (optional)</label>
                                    <textarea name="caption" id="caption" class="form-control @error('caption') is-invalid @enderror" rows="3" maxlength="2200" placeholder="Optional caption">{{ old('caption') }}</textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane mr-1"></i> Publish to Instagram
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
