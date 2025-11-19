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
        <label for="edit_post_comment">Comment</label>
        <input type="text" name="edit_post_comment" id="edit_post_comment" class="form-control"
            value="{{ $post->comment }}">
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
            <img src="{{ $post->image }}" alt="Product post image" class="rounded" id="edit_post_image_preview"
                onerror="this.onerror=null; this.src='{{ no_image() }}';" width="100%">
        </div>
    @else
        <div class="col-md-6 mt-2">
            <label for="edit_post_publish_video">Video</label>
            <br>
            <a href="{{ $post->video_key }}" target="_blank">Preview <i class="fas fa-video"></i></a>
        </div>
    @endif
</div>
