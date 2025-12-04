<div class="row">
    <input type="hidden" id="post_id" value="{{ $post->id }}">
    <div class="col-md-6 mt-2">
        <label for="edit_post_title">Title</label>
        <input type="text" name="edit_post_title" id="edit_post_title" class="form-control" value="{{ $post->title }}">
    </div>
    <div class="col-md-6 mt-2">
        <label for="edit_post_link">Link</label>
        <input type="text" name="edit_post_link" id="edit_post_link" class="form-control"
            value="{{ $post->url }}">
    </div>
    <div class="col-md-6 mt-2">
        <label for="edit_post_publish_date">Publish Date</label>
        <input type="date" name="edit_post_publish_date" id="edit_post_publish_date" class="form-control"
            value="{{ date('Y-m-d', strtotime($post->publish_date)) }}">
    </div>
    <div class="col-md-6 mt-2">
        <label for="edit_post_publish_time">Publish Time</label>
        <input type="time" name="edit_post_publish_time" id="edit_post_publish_time" class="form-control"
            value="{{ date('H:i', strtotime($post->publish_date)) }}">
    </div>
    @if (empty($post->video))
        <div class="col-md-6 mt-2">
            <span>Image <label for="edit_post_publish_image"
                    class="pointer btn btn-sm btn-outline-primary">Change</label></span>
            <input type="file" name="edit_post_publish_image" id="edit_post_publish_image" style="display: none;"
                accept="image/*">
            <br>
            <img src="{{ $post->image }}" alt="Post image" class="rounded mt-2" id="edit_post_image_preview"
                onerror="this.onerror=null; this.src='{{ no_image() }}';" width="100%">
        </div>
    @else
        <div class="col-md-6 mt-2">
            <label for="edit_post_publish_video">Video</label>
            <br>
            <a href="{{ $post->video_key }}" target="_blank">Preview <i class="fas fa-video"></i></a>
        </div>
    @endif
    <div class="col-md-12 mt-3">
        <div class="alert alert-info mb-0">
            <small>
                <i class="fas fa-info-circle mr-1"></i>
                <strong>Platform:</strong> {{ ucfirst($post->social_type) }} |
                <strong>Type:</strong> {{ ucfirst($post->type) }} |
                <strong>Status:</strong> 
                @if($post->status == 0)
                    <span class="badge badge-warning">Pending</span>
                @elseif($post->status == 1)
                    <span class="badge badge-success">Published</span>
                @else
                    <span class="badge badge-danger">Failed</span>
                @endif
            </small>
        </div>
    </div>
</div>

<script>
    // Image preview on change
    document.getElementById('edit_post_publish_image').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('edit_post_image_preview').src = e.target.result;
            };
            reader.readAsDataURL(file);
        }
    });
</script>

