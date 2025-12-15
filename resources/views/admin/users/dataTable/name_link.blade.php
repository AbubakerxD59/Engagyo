@if(!empty($user->full_name))
    <a href="{{ route('admin.users.edit', $user->id) }}">{{ $user->full_name }}</a>
@else
    -
@endif

