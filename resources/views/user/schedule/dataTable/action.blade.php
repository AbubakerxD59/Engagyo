<div class="d-flex">
    <div>
        @if ($post->status != 1)
            <button type="button" class="btn btn-outline-primary btn-sm edit_btn" data-id="{{ $post->id }}">
                Edit
            </button>
        @endif
        <button type="button" class="btn btn-outline-danger btn-sm delete_btn" data-id="{{ $post->id }}">
            Delete
        </button>
    </div>
</div>
