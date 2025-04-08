@extends('frontend.layout.main')
@section('body')
    <!-- Banner -->
    <div class="banner py-24">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-12 col-lg-6 my-5">
                    <div class="title-description">
                        <div class="text-wrapper">
                            <h2>
                                A Social Media Management Tool That Puts Customers First
                            </h2>
                            <p>
                                {{ env('APP_NAME', 'Engagyo') }} is designed with you in mind, offering intuitive features
                                that scale to your
                                unique needs. With personalized support and powerful tools, we're here to help you succeed
                                effortlessly.
                            </p>
                            <a href="{{ route('frontend.showRegister') }}">
                                <button class="btn banner-btn btn-colored">
                                    Start 14-day Free Trial

                                </button>
                            </a>
                            <button class="btn banner-btn btn-transparent">
                                <i class='bx bxs-right-arrow'></i>
                                Watch Demo
                            </button>
                            <ul class="banner-cc m-0 p-0">
                                <li>
                                    No CC required
                                </li>
                                <li class="user-select-none">
                                    |
                                </li>
                                <li>
                                    Cancel anytime
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-6 my-5">
                    <div class="banner-illustration-image">
                        <img src="{{ asset('assets/frontend/images/Main Image.png') }}" class="w-100">
                    </div>
                </div>
            </div>
            <div class="blur1"></div>
            <div class="blur2"></div>
            <div class="blur3"></div>
        </div>
    </div>

    <!-- Schedule -->
    <div id="schedule" class="py-24 container-fluid bg-img-holder">
        <div class="container">
            <div class="col-12">
                <div class="text-wrapper align-items-center justify-content-center text-center">
                    <h2>
                        Schedule your social media posts
                    </h2>
                    <p>
                        With a suite of powerful tools and a user-friendly interface, you will be able to craft, preview,
                        <br class="d-none d-md-flex">
                        schedule, and analyze your online content with ease.
                    </p>
                    <button class="btn btn-colored"><a href="{{ route('frontend.showRegister') }}">Get started for
                            free</a></button>
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="container img-container-center">
                <img src="{{ asset('assets/frontend/images/composer.png') }}" class="h-100 w-100">
            </div>
        </div>
    </div>

    <!-- Testimonials -->
    <div class="container my-48 text-center justify-content-center">
        <div class="row">
            <div class="col-12 p-0 m-0">
                <div class="text-wrapper center">
                    <h2>
                        Trusted by Agencies and Brands Worldwide
                    </h2>
                    <p>
                        {{ env('APP_NAME', 'Engagyo') }} is part of daily lives of thousands of social media marketers
                        <br class="d-none d-md-block">
                        and highly recoomended for it's capabilities.
                    </p>
                </div>
            </div>
            <!-- Slider -->
            <div class="col-12 col-md-6 col-lg-4">
                <div class="review-container">
                    <i class='bx bxs-group' style="color:#1e97f3 ;"></i>
                    <h2>13K+</h2>
                    <p>
                        Customers across Industries
                    </p>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <div class="review-container">
                    <i class='bx bxs-star' style="color:#f4c315 ;"></i>
                    <h2>4.5</h2>
                    <p>
                        Rated on G2 for ease of use
                    </p>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <div class="review-container">
                    <i class='bx bxs-shapes' style="color:rgb(21, 152, 54) ;"></i>
                    <h2>10M+</h2>
                    <p>
                        Posts published monthly
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Features -->
    <div class="container-fluid bg-light-white p-0 overflow-hidden">
        <!-- First -->
        <div class="container-fluid content-img-section m-0 py-5">
            <div class="container">
                <div class="row align-items-center mx-auto px-auto">
                    <div class="col-12 col-lg-6 pxr-24">
                        <div class="text-wrapper">
                            <span>Calender</span>
                            <h2>
                                Visualize All Your Posts in an Interactive View
                            </h2>
                            <p>
                                Craft new social media posts individually or in bulk and visually organize them using the
                                old but gold way of drag & drop. Reach more people with the suggested best times to post or
                                select your customized pre-defined timeslots.
                            </p>
                            <ul class="feature-lister">
                                <li>
                                    <i class='bx bx-check'></i>
                                    Filter posts by labels, social accounts, or members.
                                </li>
                                <li>
                                    <i class='bx bx-check'></i>
                                    Preview posts and make real-time changes for any possible typo.
                                </li>
                                <li>
                                    <i class='bx bx-check'></i>
                                    Get inspired and creative with the displayed social media holidays.
                                </li>
                            </ul>
                            <button class="btn btn-featured"><a href="{{ route('frontend.showRegister') }}">Check it
                                    out</a></button>
                        </div>
                    </div>
                    <div class="col-12 col-lg-6 pxl-24">
                        <div class="featured-images">
                            <img class="img-fluid w-100 h-100" alt="Featured Image about Calender"
                                src="{{ asset('assets/frontend/images/Calendar_View.png') }}">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Second -->
        <div class="container-fluid content-img-section m-0 py-5">
            <div class="container">
                <div class="row reverse g-4 align-items-center mx-auto px-auto">
                    <div class="col-12 col-lg-6 pxl-24">
                        <div class="text-wrapper featured-text">
                            <span>RSS Feed</span>
                            <h2>
                                Give more Power by Automation
                            </h2>
                            <p>
                                Improve your social media performance by automating your tasks. Use RSS Automation features
                                to automate post scheduling on your social media and automate your <b>Shopify</b> and
                                <b>Youtube</b> content easily.
                            </p>
                            <button class="btn btn-featured">
                                <a href="{{ route('frontend.showRegister') }}">Try it now</a>
                            </button>
                        </div>
                    </div>
                    <div class="col-12 col-lg-6 pxr-24">
                        <div class="featured-images">
                            <img class="img-fluid w-100 h-100" alt="Link in Bio illustration Image"
                                src="{{ asset('assets/frontend/images/BioLink.svg') }}">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Third -->
        <div class="container-fluid content-img-section m-0 py-5">
            <div class="container">
                <div class="row align-items-center mx-auto px-auto">
                    <div class="col-12 col-lg-6 pxr-24">
                        <div class="text-wrapper featured-text">
                            <span>Bulk Scheduling</span>
                            <h2>
                                Organize Social Accounts In Different Workspaces
                            </h2>
                            <p>
                                Manage several brands, businesses, or clients without risking to mix their content and
                                invite other members on board to help you with the process.
                            </p>
                            <p>
                                Whether you want to add full-time social media managers, marketing assistants, freelancers,
                                or guest writers - simply assign accounts and hierarchies to each and every one of them.
                            </p>
                            <p>
                                Keep an eye on everyone’s work and set approval workflows.
                            </p>
                            <button class="btn btn-featured"><a href="{{ route('frontend.showRegister') }}">Create a
                                    workspace</a></button>
                        </div>
                    </div>
                    <div class="col-12 col-lg-6 pxl-24">
                        <div class="featured-images">
                            <img class="img-fluid w-100 h-100" alt="About Workspaces in {{ env('APP_NAME', 'Engagyo') }}"
                                src="{{ asset('assets/frontend/images/Bulk-Scheduling.png') }}">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Fourth -->
        <div class="container-fluid content-img-section m-0 py-5">
            <div class="container">
                <div class="row reverse align-items-center mx-auto px-auto">
                    <div class="col-12 col-lg-6 pxl-24">
                        <div class="text-wrapper featured-text">
                            <span>Analytics</span>
                            <h2>
                                Track Your Social Media Performance
                            </h2>
                            <p>
                                Identify your top performing content, best times to post, and who is your most engaged
                                audience. Measure your brand’s success and share in-depth visual analytics reports with your
                                marketing team and clients.
                            </p>
                            <ul class="feature-lister">
                                <li>
                                    <i class='bx bx-check'></i>
                                    Collect essential data from every post you’ve shared on socials.
                                </li>
                                <li>
                                    <i class='bx bx-check'></i>
                                    Stay ahead of competition by planning a strategic marketing plan.
                                </li>
                                <li>
                                    <i class='bx bx-check'></i>
                                    Leverage insights to find repurposable content across all channels.
                                </li>
                            </ul>
                            <button class="btn btn-featured"><a href="{{ route('frontend.showRegister') }}">Take a
                                    look</a></button>
                        </div>
                    </div>
                    <div class="col-12 col-lg-6 pxr-24">
                        <div class="featured-images">
                            <img class="img-fluid w-100 h-100"
                                alt="Analytics illustration Image of {{ env('APP_NAME', 'Engagyo') }}"
                                src="{{ asset('assets/frontend/images/Analytics-Illustration.png') }}">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Fifth -->
        <div class="container-fluid content-img-section m-0 py-5">
            <div class="container">
                <div class="row align-items-center mx-auto px-auto">
                    <div class="col-12 col-lg-6 pxr-24">
                        <div class="text-wrapper featured-text">
                            <span>Recycling</span>
                            <h2>
                                Content Recycling
                            </h2>
                            <p>
                                Recycle your top performing content and automate it on social media by this powerful feature
                                of content recycling.
                            </p>
                            <ul class="feature-lister">
                                <li>
                                    <i class='bx bx-check'></i>
                                    Save your posts.
                                </li>
                                <li>
                                    <i class='bx bx-check'></i>
                                    Filter your top performing post from the dashboard.
                                </li>
                                <li>
                                    <i class='bx bx-check'></i>
                                    Recycle your content by domains.
                                </li>
                            </ul>
                            <button class="btn btn-featured">
                                <a href="{{ route('frontend.showRegister') }}">Organize your media</a>
                            </button>
                        </div>
                    </div>
                    <div class="col-12 col-lg-6 pxl-24">
                        <div class="featured-images">
                            <img class="img-fluid w-100 h-100"
                                alt="Media Library in {{ env('APP_NAME', 'Engagyo') }} illustration"
                                src="{{ asset('assets/frontend/images/Recycle-Illustration.png') }}">
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- SuperPowers -->
    <div id="powers" class="py-24 container-fluid bg-img-holder">
        <div class="container">
            <div class="col-12">
                <div class="text-wrapper align-items-center justify-content-center text-center">
                    <h2>
                        More Superpowers
                    </h2>
                    <p>
                        Besides scheduling, {{ env('APP_NAME', 'Engagyo') }} provides a huge range of tools that can help
                        scale
                        <br class="d-none d-md-block">
                        your social media marketing efforts, in a few clicks.
                    </p>
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="container img-container-center">
                <img src="{{ asset('assets/frontend/images/illustration1.svg') }}" class="h-100 w-100">
            </div>
        </div>
    </div>

    <!-- Blogs -->
    <div id="blogs">
        <div class="container">
            <div class="col-12">
                <div class="text-wrapper center">
                    <h2>
                        Latest Updates
                    </h2>
                    <button class="btn btn-blob"><a href="blog.html">Click to view all updates</a></button>
                </div>
            </div>
            <div class="row">
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="blog-contain">
                        <div class="blog-content">
                            <div class="img-container blog">
                                <img src="{{ asset('assets/frontend/images/blog13.jpg') }}" alt="">
                            </div>
                            <a href="#">
                                Discover The Latest Social Media Trends: Explore Trending Posts in
                                {{ env('APP_NAME', 'Engagyo') }}
                            </a>
                            <p>
                                Post directly to Threads and access insightful analytics with
                                {{ env('APP_NAME', 'Engagyo') }}. Learn how to create
                                engaging content, leverage {{ env('APP_NAME', 'Engagyo') }} features, and track what
                                resonates
                            </p>
                        </div>
                        <span>July 20, 2023</span>
                    </div>
                </div>
                <div class="d-none d-md-block col-md-6 col-lg-4">
                    <div class="blog-contain">
                        <div class="blog-content">
                            <div class="img-container blog">
                                <img src="{{ asset('assets/frontend/images/blog7.jpg') }}" alt="">
                            </div>
                            <a href="pages/blog/blogpages/tiktok.html">
                                The All-New Explore Tab in {{ env('APP_NAME', 'Engagyo') }}:Your One-Stop Shop for Content
                                Ideas and Industry News
                            </a>
                            <p>
                                Introducing Trending Posts, your key to finding the hottest content on social media, and get
                                inspired to create your own.
                            </p>
                        </div>
                        <span>July 20, 2023</span>
                    </div>
                </div>
                <div class="col-12 col-md-6 d-none d-lg-block col-lg-4">
                    <div class="blog-contain">
                        <div class="blog-content">
                            <div class="img-container blog">
                                <img src="{{ asset('assets/frontend/images/blog5.jpg') }}" alt="">
                            </div>
                            <a href="#">
                                Post Directly to Threads and Get Insights with {{ env('APP_NAME', 'Engagyo') }} – New API
                                Update
                            </a>
                            <p>
                                Struggling to find fresh content &amp; stay on top of industry news? Discover
                                {{ env('APP_NAME', 'Engagyo') }}'s
                                all-new Explore Tab! It streamlines content discovery, curates trending
                            </p>
                        </div>
                        <span>July 20, 2023</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
