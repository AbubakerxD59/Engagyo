@if (!in_array($role->name, ['User']))
<div class="d-flex">
    @can('assign_role_permissions')
        <div>
            <button type="button" class="btn btn-outline-success btn-sm" data-toggle="modal"
                data-target=".get_role_permissions{{ $role->id }}">Permissions</button>
        </div>
    @endcan
    @if (!in_array($role->name, ['Super Admin', 'Admin', 'Staff']))
        @can('edit_role')
            <div>
                <a href="{{ route('roles.edit', $role->id) }}" class="btn btn-outline-primary btn-sm">Edit</a>
            </div>
        @endcan
        @can('delete_role')
            <div>
                <button class="btn btn-outline-danger btn-sm delete-btn" onclick="confirmDelete(event)">Delete</button>
                <form action="{{ route('roles.destroy', $role->id) }}" method="POST" class="delete_form">
                    @csrf
                    @method('DELETE')
                </form>
            </div>
        @endcan
    @endif
</div>
@include('admin.layouts.modals.user_permissions', [
    'permission_head' => $permission_head,
    'role_id' => $role->id,
])
@endif