@extends('frontend.layout.features')
@section('body')
    <!-- RSS Banner -->
    <div id="calendar" class="container-fluid">
        <div class="container pt-5 my-48">
            <div class="row align-items-center">
                <div class="col-12 col-lg-6">
                    <div class="text-wrapper featured-text">
                        <span>RSS Feed</span>
                        <h2>
                            Automatically Post and
                            <br class="d-none d-lg-block">
                            Schedule from RSS Feeds
                        </h2>
                        <p>
                            It's time to get your social media posts under control by promoting interesting and
                            thumb-stopping content for your audience.
                        </p>
                        <p>
                            Import, archive, filter, and schedule new articles from any global website you prefer.
                        </p>
                        <button class="btn btn-colored">Create account</button>
                    </div>
                </div>
                <div class="col-12 col-lg-6 my-5">
                    <div class="banner-illustration-image">
                        <img src="{{ asset('assets/frontend/images/BioLink.svg') }}" class="w-100">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- RSS Features -->
    <div class="container-fluid bg-light-white">
        <div class="container py-24">
            <!-- First -->
            <div class="row g-4 align-items-center mx-auto px-auto">
                <div class="col-12 col-lg-6 pxr-24">
                    <div class="text-wrapper featured-text">
                        <h2>
                            What is RSS Feed?
                        </h2>
                        <p>
                            RSS Feed is an incredible feature that helps create communication with a specific source.
                        </p>
                        <p>
                            With {{ env('APP_NAME', 'Engagyo') }}, you can sync old posts and pull/filter real-time updates
                            from all your favorite
                            websites.
                        </p>
                        <p>
                            This is a highly effective way to keep all your social media schedule consistently populated
                            with fresh content for daily engagement.
                        </p>
                        <button class="btn btn-featured">Learn more</button>
                    </div>
                </div>
                <div class="col-12 col-lg-6 pxl-24">
                    <div class="featured-images">
                        <img class="w-100 h-100" alt="Featured Image about Calender"
                            src="{{ asset('assets/frontend/images/Features/RSS-Feeds.jpg') }}">
                    </div>
                </div>
            </div>
        </div>
        <!-- Second -->
        <div class="container py-24">
            <div class="row reverse g-4 align-items-center mx-auto px-auto">
                <div class="col-12 col-lg-6 pxl-24">
                    <div class="text-wrapper featured-text">
                        <h2>
                            Benefits of Automating New
                            <br class="d-none d-md-block">
                            RSS Feed items
                        </h2>
                        <p>
                            Now that you understand what RSS Feed does, let's learn why every marketer should use it.
                        </p>
                        <p>
                            Simply put: Automation is an easy way to share blogs from your favorite websites and you'll be
                            able to enjoy the results hassle-free.
                        </p>
                        <ul class="feature-lister">
                            <li>
                                <i class='bx bx-check'></i>
                                Build up your reputation by sharing trustworthy content.
                            </li>
                            <li>
                                <i class='bx bx-check'></i>
                                Increase website clicks and convert readers into customers.
                            </li>
                            <li>
                                <i class='bx bx-check'></i>
                                Save lots of time and effort, freeing up your schedule for other campaigns!
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="col-12 col-lg-6 pxr-24">
                    <div class="featured-images">
                        <img class="w-100 h-100" alt="Link in Bio illustration Image"
                            src="{{ asset('assets/frontend/images/Features/RSS-Automate.jpg') }}">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- SuperPowers -->
    <div class="container-fluid">
        <div class="container py-24 my-5 text-center justify-content-center">
            <div class="col-12">
                <div class="text-wrapper center">
                    <h2>
                        1. Find RSS Feed URLs Across Different Websites
                    </h2>
                    <p class="description">
                        If can't find the RSS Feed URL on another website, <a href="#">contact us</a>.
                    </p>
                </div>
            </div>
            <div class="row">
                <div class="col-12 col-lg-4">
                    <div class="review-container left">
                        <i class='bx bxl-wordpress'></i>
                        <h3>Wordpress</h3>
                        <p>
                            Add a '/feed' at the end of the URL. Like this: 'engagyo.io/blog/feed'
                        </p>
                    </div>
                </div>
                <div class="col-12 col-lg-4">
                    <div class="review-container left">
                        <i class='bx bxl-medium'></i>
                        <h3>Medium</h3>
                        <p>
                            Add /feed/ before the article's title. Like this: medium.com/feed/some-title
                        </p>
                    </div>
                </div>
                <div class="col-12 col-lg-4">
                    <div class="review-container left">
                        <i class='bx bx-polygon'></i>
                        <h3>GoDaddy</h3>
                        <p>
                            Add 'f.rss' at the end of a link.Like this: website.com/blog/f.rss
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid bg-light-white">
        <div class="container py-24">
            <!-- Third -->
            <div class="row g-4 align-items-center mx-auto px-auto">
                <div class="col-12 col-lg-6 pxr-24">
                    <div class="text-wrapper featured-text">
                        <h2>
                            2. Set up the RSS Feed URL
                            <br class="d-none d-md-block">
                            on {{ env('APP_NAME', 'Engagyo') }}
                        </h2>
                        <p>
                            After finding the correct RSS Feed URL, it's time to add it to {{ env('APP_NAME', 'Engagyo') }}
                            and set all the settings
                            up.
                        </p>
                        <p>
                            Adding all the important details will help you stay organized when scheduling posts. Add the
                            name of the blog, the URL and start including all keywords you want the new articles to contain
                            (or keywords you want to exclude).
                        </p>
                        <p>
                            Stay consistent and deliver fresh content through RSS Feed automation like a true professional.
                        </p>
                    </div>
                </div>
                <div class="col-12 col-lg-6 pxl-24">
                    <div class="featured-images">
                        <img class="w-100 h-100" alt="About Workspaces in {{ env('APP_NAME', 'Engagyo') }}"
                            src="{{ asset('assets/frontend/images/Features/RSS-URL.jpg') }}">
                    </div>
                </div>
            </div>
        </div>
        <div class="container py-24">
            <!-- Fourth -->
            <div class="row reverse g-4 align-items-center mx-auto px-auto">
                <div class="col-12 col-lg-6 pxl-24">
                    <div class="text-wrapper featured-text">
                        <h2>
                            3. Manage Actions
                            <br class="d-none d-md-block">
                            Towards New Articles
                        </h2>
                        <p>
                            Select and manage all new items based on your preferences and keep a populated social media
                            feed, forever
                        </p>
                        <ul class="feature-lister">
                            <li>
                                <i class='bx bx-check'></i>
                                <b>No Action</b>: Keep new items organized and use them whenever you want.
                            </li>
                            <li>
                                <i class='bx bx-check'></i>
                                <b>Auto Post</b>: Every new item that is published will be automatically posted across
                                social accounts that you select.
                            </li>
                            <li>
                                <i class='bx bx-check'></i>
                                <b>Auto Schedule</b>: Every new item that is published will be automatically scheduled
                                according to a specific posting schedule you decide.
                            </li>
                        </ul>
                        <p>
                            There is absolutely no limit on the number of items you keep organized or posting schedules that
                            you create!
                        </p>
                    </div>
                </div>
                <div class="col-12 col-lg-6 pxr-24">
                    <div class="featured-images">
                        <img class="img-fluid w-100 h-100"
                            alt="Analytics illustration Image of {{ env('APP_NAME', 'Engagyo') }}"
                            src="{{ asset('assets/frontend/images/Features/RSS-URLManage.jpg') }}">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <div class="container py-24 my-5 text-center justify-content-center">
            <div class="row">
                <div class="col-12">
                    <div class="text-wrapper center">
                        <h2>
                            Extra Tools That Help Maximize Your Efforts.
                        </h2>
                        <p>
                            Keep up with the news and help your marketing team out by searching through all RSS Feeds
                            <br class="d-none d-md-block">
                            and sharing or deleting articles right from the dashboard.
                            <br class="d-none d-md-block">
                            Want to be more efficient?
                        </p>
                        <div class="btn btn-featured">Try bulk-scheduling!</div>
                    </div>
                </div>
                <div class="col-12 mt-5">
                    <div class="container img-container-center">
                        <img src="{{ asset('assets/frontend/images/Extra-Tool.svg') }}" class="h-100 w-100">
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
