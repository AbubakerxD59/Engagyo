<nav class="main-header navbar navbar-expand-md">
    <!-- Left navbar links -->
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#" role="button">
                <i class="fas fa-bars"></i>
                <i class="fa fa-times"></i>
            </a>
        </li>
    </ul>
    <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto">
        <li class="nav-item">
            <div class="user-panel d-flex">
                <div class="image">
                    @php
                        $user = auth()->user();
                        $rawProfilePic = $user->getAttributes()['profile_pic'] ?? null;
                        $profilePic = !empty($rawProfilePic) && file_exists(public_path('uploads/users/' . $rawProfilePic)) 
                            ? getImage('users', $rawProfilePic) 
                            : default_user_avatar($user->id, $user->full_name);
                    @endphp
                    <img src="{{ $profilePic }}" class="userimg" alt="User Image"
                        onerror="this.onerror=null; this.src='{{ default_user_avatar($user->id, $user->full_name) }}';">
                </div>
                <div class="info">
                    <a class="nav-link" href="{{ route('admin.users.edit', auth()->id()) }}"
                        class="d-block">{{ auth()->user()->full_name }}</a>
                </div>
            </div>
        </li>
    </ul>
</nav>
