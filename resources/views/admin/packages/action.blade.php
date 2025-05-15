<div class="d-flex">
    @can('edit_package')
        <div>
            <a href="{{ route('admin.packages.edit', $package->id) }}" class="btn btn-outline-primary btn-sm">Edit</a>
        </div>
    @endcan
    @can('delete_package')
        <div>
            <form action="{{ route('admin.packages.destroy', $package->id) }}" method="POST" class="delete_form">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-danger btn-sm delete-btn"
                    onclick="confirmDelete(event)">Delete</button>
            </form>
        </div>
    @endcan
</div>
