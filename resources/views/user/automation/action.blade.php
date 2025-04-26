<div class="d-flex">
    @if ($post->status != 1)
        <div>
            <button class="btn btn-outline-success btn-sm publish-post" data-id="{{ $post->id }}"
                data-type="{{ $post->type }}">
                Publish
            </button>
        </div>
    @endif
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
