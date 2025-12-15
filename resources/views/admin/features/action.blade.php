<div class="d-flex">
    @can('edit_feature')
        <div>
            <a class="btn btn-outline-primary btn-sm" href="{{ route('admin.features.edit', $feature->id) }}">Edit</a>
        </div>
    @endcan
    @can('delete_feature')
        <div class="ml-2">
            <form action="{{ route('admin.features.destroy', $feature->id) }}" method="POST" class="delete_form">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-danger btn-sm delete-btn"
                    onclick="confirmDelete(event)">Delete</button>
            </form>
        </div>
    @endcan
</div>
