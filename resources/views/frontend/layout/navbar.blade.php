<!-- Navbar -->
<nav class="navbar navbar-expand-lg fixed-top bg-lighter-light container-fluid">
    <div class="container">
        <a class="navbar-brand" href="{{ route('frontend.home') }}">
            <img src="{{ site_logo() }}" alt="Logo">
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
            aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse px-4" id="navbarNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 justify-content-between">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" aria-expanded="false"
                        data-bs-toggle="collapse" data-bs-target="#featuresDropdown">
                        Features
                    </a>
                    <ul class="nav-show-hide collapse dropdown-menu row" id="featuresDropdown">
                        <li class="col-12 col-lg-4">
                            <a href="{{ route('frontend.calendarView') }}" class="nav-link nav-inner-link">
                                <div class="inner-link-icon d-none d-lg-block">
                                    <i class='bx bx-customize'></i>
                                </div>
                                <div class="inner-link-content">
                                    <h4>Calender View</h4>
                                    <p class=" d-none d-lg-block">
                                        Easily schedule, manage, and visualize your events.
                                    </p>
                                </div>
                            </a>
                        </li>
                        <li class="col-12 col-lg-4">
                            <a href="{{ route('frontend.analytics') }}" class="nav-link nav-inner-link">
                                <div class="inner-link-icon d-none d-lg-block">
                                    <i class='bx bxs-bar-chart-alt-2'></i>
                                </div>
                                <div class="inner-link-content">
                                    <h4>Analytics</h4>
                                    <p class=" d-none d-lg-block">
                                        Track, analyze, and optimize your data.
                                    </p>
                                </div>
                            </a>
                        </li>
                        <li class="col-12 col-lg-4">
                            <a href="{{ route('frontend.rssFeeds') }}" class="nav-link nav-inner-link">
                                <div class="inner-link-icon d-none d-lg-block">
                                    <i class='bx bx-link'></i>
                                </div>
                                <div class="inner-link-content">
                                    <h4>RSS Feeds</h4>
                                    <p class=" d-none d-lg-block">
                                        Stay updated with the latest content through RSS.
                                    </p>
                                </div>
                            </a>
                        </li>
                        <li class="col-12 col-lg-4">
                            <a href="{{ route('frontend.bulkScheduling') }}" class="nav-link nav-inner-link">
                                <div class="inner-link-icon d-none d-lg-block">
                                    <i class='bx bxs-layer'></i>
                                </div>
                                <div class="inner-link-content">
                                    <h4>Bulk Scheduling</h4>
                                    <p class=" d-none d-lg-block">
                                        Plan and schedule multiple posts at once.
                                    </p>
                                </div>
                            </a>
                        </li>
                        <li class="col-12 col-lg-4">
                            <a href="{{ route('frontend.recycling') }}" class="nav-link nav-inner-link">
                                <div class="inner-link-icon d-none d-lg-block">
                                    <i class='bx bx-link'></i>
                                </div>
                                <div class="inner-link-content">
                                    <h4>Recycling</h4>
                                    <p class=" d-none d-lg-block">
                                        Convert waste into reusable resources.
                                    </p>
                                </div>
                            </a>
                        </li>
                        <li class="col-12 col-lg-4">
                            <a href="{{ route('frontend.curatePost') }}" class="nav-link nav-inner-link">
                                <div class="inner-link-icon d-none d-lg-block">
                                    <i class='bx bx-list-ul'></i>
                                </div>
                                <div class="inner-link-content">
                                    <h4>Curate Post</h4>
                                    <p class=" d-none d-lg-block">
                                        Discover, organize, and publish the best posts.
                                    </p>
                                </div>
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" aria-expanded="false"
                        data-bs-toggle="collapse" data-bs-target="#toolsdropdown">
                        Free tools
                    </a>
                    <ul class="nav-show-hide small collapse dropdown-menu row" id="toolsdropdown">
                        <li class="col-12">
                            <a href="pages/tools/urlshortner.html" class="nav-link nav-inner-link">
                                <div class="inner-link-icon d-none d-lg-block">
                                    <i class='bx bx-customize'></i>
                                </div>
                                <div class="inner-link-content">
                                    <h4>URL Link Shortner</h4>
                                    <p class=" d-none d-lg-block">
                                        Easily schedule, manage, and visualize your events.
                                    </p>
                                </div>
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="plans.html">Pricing</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="pages/blog/blog.html">Blog</a>
                </li>
            </ul>

            <div class="d-block d-lg-flex">
                @if (Auth::check())
                    <a href="{{ route('panel.accounts') }}">
                        <button class="btn nav-btn btn-colored" type="button">
                            DASHBOARD
                        </button>
                    </a>
                @else
                    <a href="{{ route('frontend.showLogin') }}">
                        <button class="btn nav-btn btn-transparent align-items-center" href="#features" type="button">
                            <i class='bx bx-log-in-circle'></i> Login
                        </button>
                    </a>
                    <a href="{{ route('frontend.showRegister') }}">
                        <button class="btn nav-btn btn-colored" type="button">
                            Sign up
                        </button>
                    </a>
                @endif
            </div>
        </div>
    </div>
</nav>
