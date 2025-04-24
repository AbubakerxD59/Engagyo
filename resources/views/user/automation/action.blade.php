<div class="d-flex">
    <div>
        <a href="{{ route('panel.automation.posts.edit', $post->id) }}" class="btn btn-outline-primary btn-sm">Edit</a>
    </div>
    <div>
        <button type="submit" class="btn btn-outline-danger btn-sm delete-btn post-delete" data-id="{{ $post->id }}">
            Delete
        </button>
    </div>
</div>
