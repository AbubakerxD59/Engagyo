<div class="d-flex">
    <div>
        <a href="{{ route('admin.permissions.edit', $permission->id) }}" class="btn btn-outline-primary btn-sm">Edit</a>
    </div>

    <div>
        <button class="btn btn-outline-danger btn-sm delete-btn" onclick="confirmDelete(event)">Delete</button>
        <form action="{{ route('admin.permissions.destroy', $permission->id) }}" method="POST" class="delete_form">
            @csrf
            @method('DELETE')
        </form>
    </div>
</div>
