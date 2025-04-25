<div class="card">
    <div class="card-header with-border clearfix">
        <div class="card-title">
            <a href="{{ $post->url }}" target="_blank">
                <img src="{{ $post->image }}" alt="{{ no_image() }}" width="120px">
            </a>
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4 form-group">
                <p>{{ $post->title }}</p>
            </div>
            <div class="col-md-4 form-group">
                <p>{{ $post->title }}</p>
            </div>
        </div>
    </div>
</div>
