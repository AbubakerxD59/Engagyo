<div class="d-flex">
    @if ($role != 'Super Admin')
        @can('edit_user')
            <div>
                <a href="{{ route('users.edit', $user->id) }}" class="btn btn-outline-primary btn-sm">Edit</a>
            </div>
        @endcan
        @can('delete_user')
            <div>
                <form action="{{ route('users.destroy', $user->id) }}" method="POST" class="delete_form">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger btn-sm delete-btn"
                        onclick="confirmDelete(event)">Delete</button>
                </form>
            </div>
        @endcan
    @endif
</div>
