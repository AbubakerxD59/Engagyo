@extends('frontend.layout.features')
@section('body')
    <!-- Calendar Banner -->
    <div id="curate-posts" class="container-fluid pt-5">
        <div class="container pt-5 my-48">
            <div class="col-12 justify-content-center text-center">
                <div class="text-wrapper center">
                    <h2>
                        Stand out with Social Media Content Curation
                    </h2>
                    <p>
                        Develop a strategic social media marketing plan by utilizing all the features that {{ env('APP_NAME', 'Engagyo') }}
                        provides.
                        <br class="d-none d-lg-block">
                        Personalize your online presence by carefully curating your posts: format the text, preview posts,
                        and save time.
                    </p>
                    <a href="{{ route('frontend.showRegister') }}" class="btn btn-colored">Create Account</a>
                </div>
            </div>
            <div class="col-12">
                <div class="container img-container-center">
                    <img src="{{ asset('assets/frontend/images/curateposts.jpg') }}" class="h-100 w-100">
                </div>
            </div>
        </div>
    </div>

    <!-- Calendar Features -->
    <div class="container-fluid bg-light-white">
        <!-- First -->
        <div class="container py-24">
            <div class="row g-4 align-items-center mx-auto px-auto">
                <div class="col-12 col-lg-6 pxr-24">
                    <div class="text-wrapper featured-text">
                        <h2>
                            Curate Text
                        </h2>
                        <p>
                            Build a content marketing strategy by discovering all our helpful and intuitive built-in tools.
                        </p>
                        <ul class="feature-lister">
                            <li>
                                <i class='bx bx-check'></i>
                                Convert pieces of your text into bold and italic with our formatting tools.
                            </li>
                            <li>
                                <i class='bx bx-check'></i>
                                Keep track of the character count in real-time.
                            </li>
                            <li>
                                <i class='bx bx-check'></i>
                                Use the emoji picker to give life to the content and express thoughts better.
                            </li>
                        </ul>
                        <p>
                            Keep your audience focused and make the content sharable by pointing out your thoughts in a
                            simplified way.
                        </p>
                    </div>
                </div>
                <div class="col-12 col-lg-6 pxl-24">
                    <div class="featured-images">
                        <img class="img-fluid w-100 h-100" alt="Featured Image about Calender"
                            src="{{ asset('assets/frontend/images/Features/Curate-Text.jpg') }}">
                    </div>
                </div>
            </div>
        </div>
        <!-- Second -->
        <div class="container py-24">
            <div class="row reverse align-items-center mx-auto px-auto">
                <div class="col-12 col-lg-6 pxl-24">
                    <div class="text-wrapper featured-text">
                        <span>Import or design new media</span>
                        <h2>
                            Curate Visuals
                        </h2>
                        <p>
                            Trigger clicks and reach your online marketing goals easily by implementing a visual content
                            strategy.
                        </p>
                        <ul class="feature-lister">
                            <li>
                                <i class='bx bx-check'></i>
                                Import media from Drive, Dropbox, OneDrive, Unsplash, Media Library, external URLs, or your
                                local storage.
                            </li>
                            <li>
                                <i class='bx bx-check'></i>
                                Design unique photo illustrations with Canva on {{ env('APP_NAME', 'Engagyo') }} using the built-in button with a
                                dedicated dashboard and all features that your plan allows.
                            </li>
                            <li>
                                <i class='bx bx-check'></i>
                                Create visually attractive marketing videos with VistaCreate using some of the Pro features
                                at no extra cost.
                            </li>
                        </ul>
                        <p>
                            Maintain a strong brand presence with visual content that lead to a loyal audience!
                        </p>
                    </div>
                </div>
                <div class="col-12 col-lg-6 pxr-24">
                    <div class="featured-images">
                        <img class="img-fluid w-100 h-100" alt="Link in Bio illustration Image"
                            src="{{ asset('assets/frontend/images/Features/Curate-Visuals.jpg') }}">
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
                            Customize Posts for Each Social Network
                        </h2>
                        <p class="description">
                            Every social network is different, so your posts should be formatted differently
                            <br class="d-none d-lg-block">
                            Why? Tweets can be up to 280 characters long, while LinkedIn supports longer content for
                            example. ach platform displays
                            <br class="d-none d-lg-block">
                            visual content differently too—photos on Instagram will look different than they do on Facebook
                            or Twitter.
                            <br class="d-none d-lg-block">
                            Creating similar, but not identical content for each platform is essential for high engagement
                        </p>
                    </div>
                </div>
                <div class="col-12 mt-5">
                    <div class="container img-container-center">
                        <img src="{{ asset('assets/frontend/images/Features/Curate-Customize.jpg') }}" class="h-100 w-100">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid bg-light-white">
        <!-- Third -->
        <div class="container py-24">
            <div class="row g-4 align-items-center mx-auto px-auto">
                <div class="col-12 col-lg-6 pxr-24">
                    <div class="text-wrapper featured-text">
                        <span>Sneak peek</span>
                        <h2>
                            Preview Posts
                        </h2>
                        <p>
                            Preview posts before scheduling, in real-time.
                        </p>
                        <ul class="feature-lister">
                            <li>
                                <i class='bx bx-check'></i>
                                Check image sizes and see if they fit perfectly for every account.
                            </li>
                            <li>
                                <i class='bx bx-check'></i>
                                View how your content will look on both mobile and desktop.
                            </li>
                            <li>
                                <i class='bx bx-check'></i>
                                Double-check the correct placement of hashtags and all {shortcodes}.
                            </li>
                        </ul>
                        <p>
                            With our Preview Posts feature, you can spot and avoid any typos before they go live. We’ve all
                            been there.
                        </p>
                    </div>
                </div>
                <div class="col-12 col-lg-6 pxl-24">
                    <div class="featured-images">
                        <img class="img-fluid w-100 h-100" alt="About Workspaces in {{ env('APP_NAME', 'Engagyo') }}"
                            src="{{ asset('assets/frontend/images/Features/Curate-Preview.jpg') }}">
                    </div>
                </div>
            </div>
        </div>
        <!-- Fourth -->
        <div class="container py-24">
            <div class="row reverse align-items-center mx-auto px-auto">
                <div class="col-12 col-lg-6 pxl-24">
                    <div class="text-wrapper featured-text">
                        <span>Bulk Schedule</span>
                        <h2>
                            Create Multiple Posts in Bulk
                        </h2>
                        <p>
                            {{ env('APP_NAME', 'Engagyo') }}’s composer is part of an intuitive dashboard, where you can create unique posts
                            effortlessly.
                        </p>
                        <p>
                            Each post can be individually modified by using the corresponding content, links, and visuals.
                            Besides that, you can pick a different scheduling method for each of the posts: manually, adding
                            the time and date; automatically, using a specific time slot; recycling and recurring.
                        </p>
                    </div>
                </div>
                <div class="col-12 col-lg-6 pxr-24">
                    <div class="featured-images">
                        <img class="img-fluid w-100 h-100" alt="Analytics illustration Image of {{ env('APP_NAME', 'Engagyo') }}"
                            src="{{ asset('assets/frontend/images/Features/Curate-MultiplePosts.jpg') }}">
                    </div>
                </div>
            </div>
        </div>
        <!-- Fifth -->
        <div class="container py-24">
            <div class="row align-items-center mx-auto px-auto">
                <div class="col-12 col-lg-6 pxr-24">
                    <div class="text-wrapper featured-text">
                        <span>Curating visuals with the built-in editor</span>
                        <h2>
                            Built-in Photo Editor
                        </h2>
                        <p>
                            Choose a channel-specific aspect ratio and quickly edit your visuals.
                        </p>
                        <ul class="feature-lister">
                            <li>
                                <i class='bx bx-check'></i>
                                You don’t need to worry about creating several versions of an image for each platform.
                            </li>
                            <li>
                                <i class='bx bx-check'></i>
                                Resize, filter, and add visual elements to your photo and instantly schedule the design to
                                all your social accounts in {{ env('APP_NAME', 'Engagyo') }}.
                            </li>
                            <li>
                                <i class='bx bx-check'></i>
                                Ensure consistency for your Instagram Feed by editing your photos from within the Feed
                                Preview for last minute changes.
                            </li>
                        </ul>
                        <p>
                            Identify top hashtags: Use the Hashtag Score to measure your precise hashtag impact on the
                            engagement of your posts.
                        </p>
                    </div>
                </div>
                <div class="col-12 col-lg-6 pxl-24">
                    <div class="featured-images">
                        <img class="w-100 h-100" alt="Featured Image about Calender"
                            src="{{ asset('assets/frontend/images/Features/Curate-PhotoEditor.jpg') }}">
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
                        <h2 class="pb-5">
                            Tools You Can’t Afford To Miss Out
                        </h2>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="review-container left">
                        <i class='mb-2 bx bx-menu'></i>
                        <h3>Location</h3>
                        <p>
                            Adding a location on your posts can improve engagement and reach.
                        </p>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="review-container left">
                        <i class='mb-2 bx bx-customize'></i>
                        <h3>Media Options</h3>
                        <p>
                            Protect your visual content by using customizable watermarks.
                        </p>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="review-container left">
                        <i class='mb-2 bx bx-rss'></i>
                        <h3>Signatures</h3>
                        <p>
                            Add contact info or repetitive hashtags by default at the bottom of your content.
                        </p>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="review-container left">
                        <i class='mb-2 bx bxs-book-content'></i>
                        <h3>Follow-up Comments</h3>
                        <p>
                            Keep your content nice and clean by adding extra information in the comments.
                        </p>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="review-container left">
                        <i class='mb-2 bx bx-rss'></i>
                        <h3>Auto Share</h3>
                        <p>
                            Cross-promote your content to reach potential customers on multiple socials.
                        </p>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="review-container left">
                        <i class='mb-2 bx bxs-book-content'></i>
                        <h3>Auto Delete</h3>
                        <p>
                            Automate the deletion of posts by setting an expiration date. Great for limited time offers!
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
