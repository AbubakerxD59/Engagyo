<div class="d-flex flex-wrap">
    @if ($user->getRole() != 'Super Admin')
        @can('view_user')
            <div class="mr-1 mb-1">
                <button type="button" class="btn btn-outline-info btn-sm btn-view-accounts" data-user-id="{{ $user->id }}" data-user-name="{{ $user->full_name }}" title="View connected social accounts">
                    <i class="fas fa-share-alt"></i> Accounts
                </button>
            </div>
        @endcan
        @can('edit_user')
            <div class="mr-1 mb-1">
                <a href="{{ route('admin.users.edit', $user->id) }}" class="btn btn-outline-primary btn-sm">Edit</a>
            </div>
        @endcan
        @can('delete_user')
            <div class="mb-1">
                <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" class="delete_form">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger btn-sm delete-btn"
                        onclick="confirmDelete(event)">Delete</button>
                </form>
            </div>
        @endcan
    @endif
</div>
