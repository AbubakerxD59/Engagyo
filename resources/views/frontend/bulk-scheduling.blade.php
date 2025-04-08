@extends('frontend.layout.features')
@section('body')
    <!-- Bulk Banner -->
    <div id="bulk-schedule" class="container-fluid pt-5">
        <div class="container pt-5 my-48">
            <div class="col-12 justify-content-center text-center">
                <div class="text-wrapper center">
                    <h2>
                        Bulk Scheduling
                    </h2>
                    <p>
                        Save mountains of time by creating and scheduling social media posts in bulk.
                    </p>
                    <a href="{{route("frontend.showRegister")}}" class="btn btn-colored">Create Account</a>
                </div>
            </div>
            <div class="col-12">
                <div class="container img-container-center">
                    <img src="{{ asset('assets/frontend/images/Bulk-Scheduling.png') }}" class="h-100 w-100">
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid bg-light-white">
        <div class="container py-24">
            <div class="row g-4 align-items-center mx-auto px-auto">
                <div class="col-12 col-lg-6 pxr-24">
                    <div class="text-wrapper featured-text">
                        <span>Top Advantages</span>
                        <h2>
                            Benefits of Bulk-Scheduling
                            <br class="d-none d-lg-block">
                            with {{ env('APP_NAME', 'Engagyo') }}
                        </h2>
                        <p>
                            Preparing months-worth of posts in less time than ever before.
                        </p>
                        <ul class="feature-lister">
                            <li>
                                <i class='bx bx-check'></i>
                                Stay consistent with posting schedules.
                            </li>
                            <li>
                                <i class='bx bx-check'></i>
                                Keep an active social media feed.
                            </li>
                            <li>
                                <i class='bx bx-check'></i>
                                Free up time to spend on other marketing efforts.
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="col-12 col-lg-6 pxl-24">
                    <div class="featured-images">
                        <img class="img-fluid w-100 h-100" alt="Featured Image about Calender"
                            src="{{ asset('assets/frontend/images/Features/Bulk-Benefits.jpg') }}">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container py-24 my-48 text-center justify-content-center">
        <div class="box-shadowed">
            <div class="row">
                <div class="col-12">
                    <div class="text-wrapper center">
                        <h2>
                            How it works?
                        </h2>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="review-container">
                        <i class='bx bx-plus-circle'></i>
                        <h3>Add Post</h3>
                        <p>
                            Increase post number manually.
                        </p>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="review-container">
                        <i class='bx bx-layer'></i>
                        <h3>Bulk Upload Media</h3>
                        <p>
                            Upload up to 500 visual content at once.
                        </p>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="review-container">
                        <i class='bx bx-file-blank'></i>
                        <h3>CSV File</h3>
                        <p>
                            Prepare all posts in a unique CSV file.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid bg-light-white">
        <div class="container py-24">
            <div class="row g-4 align-items-center mx-auto px-auto">
                <div class="col-12 col-lg-6 pxr-24">
                    <div class="text-wrapper featured-text">
                        <span>Option One</span>
                        <h2>
                            Add Post
                        </h2>
                        <p>
                            Get the right inspiration and start creating new posts right away, simply by adding posts
                            manually.
                        </p>
                        <p>
                            We know that when creativity hits, you have to write everything down! That’s why you can create
                            new posts on the editor and manage them individually.
                        </p>
                        <p>
                            You can choose the desired social networks, customize each post, and schedule or save them!
                        </p>
                    </div>
                </div>
                <div class="col-12 col-lg-6 pxl-24">
                    <div class="featured-images">
                        <img class="img-fluid w-100 h-100" alt="Featured Image about Calender"
                            src="{{ asset('assets/frontend/images/Features/Bulk-Add.jpg') }}">
                    </div>
                </div>
            </div>
        </div>
        <div class="container py-24">
            <div class="row reverse align-items-center mx-auto px-auto">
                <div class="col-12 col-lg-6 pxl-24">
                    <div class="text-wrapper featured-text">
                        <span>Option Two</span>
                        <h2>
                            Bulk Upload
                        </h2>
                        <p>
                            Upload up to 500 photos, videos, and GIFs at once.
                        </p>
                        <p>
                            Easily upload media files from Google Drive, Dropbox, OneDrive, Unsplash, external URLs, or your
                            device.
                        </p>
                    </div>
                </div>
                <div class="col-12 col-lg-6 pxr-24 ">
                    <div class="featured-images">
                        <img class="img-fluid w-100 h-100" alt="Link in Bio illustration Image"
                            src="{{ asset('assets/frontend/images/Features/Bulk-Upload.jpg') }}">
                    </div>
                </div>
            </div>
        </div>
        <div class="container py-24">
            <div class="row align-items-center mx-auto px-auto">
                <div class="col-12 col-lg-6 pxr-24">
                    <div class="text-wrapper featured-text">
                        <span>Option Three</span>
                        <h2>
                            Import & Schedule from CSV
                        </h2>
                        <p>
                            Save time by creating up to 500 posts with a single CSV file.
                        </p>
                        <p>
                            Being account-specific, CSVs are a fast way to populate your calendar and stay active on your
                            favorite social networks.
                        </p>
                        <p>
                            You can add descriptions, links and media. CSV upload is an extremely powerful tool that will
                            allow you to stay on top of your social media content.
                        </p>
                        <div class="btn btn-featured">Learn more</div>
                    </div>
                </div>
                <div class="col-12 col-lg-6 pxl-24 ">
                    <div class="featured-images">
                        <img class="img-fluid w-100 h-100" alt="Link in Bio illustration Image"
                            src="{{ asset('assets/frontend/images/FilterExtension.jpg') }}">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="powers">
        <div class="container justify-content-center text-center py-5 my-5">
            <div class="col-12">
                <div class="text-wrapper center">
                    <h2>
                        Customize each post, individually.
                    </h2>
                    <p class="description">
                        The ability to modify and update each post individually within the same dashboard is a no-brainer!
                        <br class="d-none d-lg-block">
                        When you’re satisfied with how each post looks, you can schedule all your posts at once in bulk
                        <br class="d-none d-lg-block">
                        and put them in the right spotlight.
                    </p>
                    <div class="btn btn-colored">Explore Analytics</div>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid bg-light-white">
        <div class="container py-24">
            <div class="reverse row align-items-center mx-auto px-auto">
                <div class="col-12 col-lg-6 pxl-24">
                    <div class="text-wrapper featured-text">
                        <h2>
                            Scheduling Modes
                        </h2>
                        <p>
                            {{ env('APP_NAME', 'Engagyo') }} offers 4 scheduling options for you to choose from:
                        </p>
                        <ul class="feature-lister">
                            <li>
                                <i class='bx bx-check'></i>
                                <b>Schedule</b>: Manually choose a time to share your post.
                            </li>
                            <li>
                                <i class='bx bx-check'></i>
                                <b>Auto-Schedule</b>: Add new posts to a schedule that you set for each social account.
                            </li>
                            <li>
                                <i class='bx bx-check'></i>
                                <b>Recycle</b>: Share your post multiple times on a schedule that you choose.
                            </li>
                            <li>
                                <i class='bx bx-check'></i>
                                <b>Recurring</b>: Share your post periodically over time until you turn it off.
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="col-12 col-lg-6 pxr-24 ">
                    <div class="featured-images">
                        <img class="img-fluid w-100 h-100" alt="Link in Bio illustration Image"
                            src="{{ asset('assets/frontend/images/Features/Bulk-Scheduling.jpg') }}">
                    </div>
                </div>
            </div>
        </div>
        <div class="container py-24">
            <div class="row align-items-center mx-auto px-auto">
                <div class="col-12 col-lg-6 pxr-24">
                    <div class="text-wrapper featured-text">
                        <h2>
                            Extra tools to vitalize
                            <br class="d-none d-lg-block">
                            your posts
                        </h2>
                        <p>
                            We know it’s all fun and games until you realize that your posts look way too basic and don’t
                            deliver the right message you initially wanted.
                        </p>
                        <p>
                            Therefore, {{ env('APP_NAME', 'Engagyo') }} helps curate each post individually by allowing you to add the right
                            location and reach even more people in your local area; add general information as signatures;
                            keep the content clean by putting extra content as follow-up comments; auto-share across other
                            socials; and automate the deletion of a post after a specific time.
                        </p>
                    </div>
                </div>
                <div class="col-12 col-lg-6 pxl-24">
                    <div class="featured-images">
                        <img class="img-fluid w-100 h-100" alt="About Workspaces in {{ env('APP_NAME', 'Engagyo') }}"
                            src="{{ asset('assets/frontend/images/Features/Bulk-Tools.jpg') }}">
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
                            Bulk Scheduling Hidden Superpowers
                        </h2>
                        <p>
                            Extra features that will make bulk scheduling even more powerful.
                        </p>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <div class="review-container left">
                        <i class='mb-2 bx bx-menu'></i>
                        <h3>Posts</h3>
                        <p>
                            Reuse posts that were already shared and reach more people.
                        </p>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <div class="review-container left">
                        <i class='mb-2 bx bx-customize'></i>
                        <h3>Media Library</h3>
                        <p>
                            Bulk-select and share photos, videos, and Gifs across socials at once.
                        </p>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <div class="review-container left">
                        <i class='mb-2 bx bx-rss'></i>
                        <h3>RSS Feed</h3>
                        <p>
                            Share new articles from your personal website and other favorite blogs.
                        </p>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <div class="review-container left">
                        <i class='mb-2 bx bxs-book-content'></i>
                        <h3>Content Suggestions</h3>
                        <p>
                            Promote multiple trending articles from famous websites on the internet.
                        </p>
                    </div>
                </div>
                <div class="col-12 mt-5">
                    <div class="container img-container-center">
                        <img src="{{ asset('assets/frontend/images/Features/Bulk-Superpowertools.png') }}"
                            class="h-100 w-100">
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
