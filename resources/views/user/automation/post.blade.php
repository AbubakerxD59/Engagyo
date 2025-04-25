<div class="d-flex justify-content-center">
    <div class="card col-md-6">
        <div class="card-header with-border clearfix d-flex justify-content-center">
            <div class="card-title">
                <a href="{{ $post->url }}" target="_blank">
                    <img src="{{ $post->image }}" alt="{{ no_image() }}" width="150px">
                </a>
            </div>
        </div>
        <div class="card-body p-2">
            <div>
                <div class="form-group font-weight-bold">
                    <p>{{ $post->title }}</p>
                </div>
                <div class="form-group font-weight-bold">
                    <span class="row">
                        <i class="fa fa-clock m-1"></i>
                        <p>{{ date('Y-m-d H:i A', strtotime($post->publish_date)) }}</p>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>
