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
                    <img src="{{ auth()->user()->profile_pic ? getImage('users', auth()->user()->profile_pic) : '' }}"
                        class="userimg" alt="User Image">
                </div>
                <div class="info">
                    <a class="nav-link" href="{{ route('admin.users.edit', auth()->id()) }}"
                        class="d-block">{{ auth()->user()->full_name }}</a>
                </div>
            </div>
        </li>
    </ul>
</nav>
