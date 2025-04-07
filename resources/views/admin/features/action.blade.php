<div class="d-flex">
    @can('edit_feature')
        <div>
            <a class="btn btn-outline-primary btn-sm edit_feature" data-feature-id="{{ $feature->id }}"
                data-edit-url="{{ route('features.edit', $feature->id) }}">Edit</a>
        </div>
    @endcan
    @can('delete_feature')
        <div>
            <form action="{{ route('features.destroy', $feature->id) }}" method="POST" class="delete_form">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-danger btn-sm delete-btn"
                    onclick="confirmDelete(event)">Delete</button>
            </form>
        </div>
    @endcan
</div>
