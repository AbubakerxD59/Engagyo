<div class="d-flex">
    <div>
        <button class="btn btn-outline-danger btn-sm delete-btn publish-post" data-id="{{ $post->id }}">
            Publish
        </button>
    </div>
    <div>
        <a class="btn btn-outline-primary btn-sm post_edit" data-toggle="modal" data-target="#editPostModal"
            data-body="{{ $post }}">
            Edit
        </a>
    </div>
    <div>
        <button type="submit" class="btn btn-outline-danger btn-sm delete-btn post-delete" data-id="{{ $post->id }}">
            Delete
        </button>
    </div>
</div>
