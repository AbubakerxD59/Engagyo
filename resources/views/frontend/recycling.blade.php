@extends('frontend.layout.features')
@section('body')
    <!-- recycle Banner -->
    <div id="recycle" class="container-fluid">
        <div class="container pt-5 my-48">
            <div class="row align-items-center">
                <div class="col-12 col-lg-6">
                    <div class="text-wrapper featured-text">
                        <span>Recycling</span>
                        <h2>
                            Automatically Recycle Your Social Media Posts
                        </h2>
                        <p>
                            Bring attention to your top-performing posts and drive brand awareness simply by recycling.
                            Manage the frequency, start/end date, and keep an eye on your virtual recycle.
                        </p>
                        <p>
                            Reach people at different time zones and invest less time distributing your creative work
                        </p>
                        <a href="{{ route('frontend.showRegister') }}" class="btn btn-colored">Create account</a>
                    </div>
                </div>
                <div class="col-12 col-lg-6 my-5">
                    <div class="banner-illustration-image">
                        <img src="{{ asset('assets/frontend/images/Recycle-Illustration.png') }}" class="w-100">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- recycle Features -->
    <div class="container-fluid bg-light-white">
        <div class="container py-24">
            <!-- First -->
            <div class="row g-4 align-items-center mx-auto px-auto">
                <div class="col-12 col-lg-6 pxr-24">
                    <div class="text-wrapper featured-text">
                        <h2>
                            What is Evergreen Content?
                        </h2>
                        <p>
                            Evergreen content is timeless content that helps reach a wider audience and reinforce the
                            purpose.
                        </p>
                        <p>
                            Its quality is never affected by any update - that’s why we can automate the same content again
                            and again while maximizing interest!
                        </p>
                        <p>
                            Get consistent and organic traffic to potential posts. Avoid seasonal trends and focus more on
                            their uniqueness.
                        </p>
                        <button class="btn btn-featured">Learn more</button>
                    </div>
                </div>
                <div class="col-12 col-lg-6 pxl-24">
                    <div class="featured-images">
                        <img class="w-100 h-100" alt="Featured Image about Calender"
                            src="{{ asset('assets/frontend/images/Features/Recycle-Content.jpg') }}">
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
                            Don’t Let Your Content
                            <br class="d-none d-md-block">
                            Settle For Less
                        </h2>
                        <p>
                            Pick your top-performing posts and choose a posting schedule to set up Recycling.
                        </p>
                        <ul class="feature-lister">
                            <li>
                                <i class='bx bx-check'></i>
                                Increase traffic and boost engagement organically.
                            </li>
                            <li>
                                <i class='bx bx-check'></i>
                                Populate the social media calendar with less effort.
                            </li>
                            <li>
                                <i class='bx bx-check'></i>
                                Get discovered and rank higher on search engines.
                            </li>
                        </ul>
                        <p>
                            Your best content deserves more attention! Struggle less with non-expiring curated pieces.
                        </p>
                    </div>
                </div>
                <div class="col-12 col-lg-6 pxr-24">
                    <div class="featured-images">
                        <img class="w-100 h-100" alt="Link in Bio illustration Image"
                            src="{{ asset('assets/frontend/images/Features/Recycle-Post.jpg') }}">
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
                        How to Recycle a Post
                    </h2>
                    <p class="description">
                        Whether it’s honest feedback from a customer, a delicious traditional recipe, an effective
                        <br class="d-none d-xl-block">
                        productivity hack, or a service/product you provide, you can recycle your best posts.
                    </p>
                    <button class="btn btn-featured">Learn More</button>
                </div>
            </div>
            <div class="row">

                <div class="col-12 col-lg-4">
                    <div class="review-container left">
                        <i class='mb-2 bx bx-menu'></i>
                        <h3>Identify</h3>
                        <p>
                            Find top-performing content using filters & ranking on the analytics dashboard.
                        </p>
                    </div>
                </div>
                <div class="col-12 col-lg-4">
                    <div class="review-container left">
                        <i class='mb-2 bx bx-customize'></i>
                        <h3>Reuse</h3>
                        <p>
                            Create new recycling posts under the desired settings and frequencies.
                        </p>
                    </div>
                </div>
                <div class="col-12 col-lg-4">
                    <div class="review-container left">
                        <i class='mb-2 bx bx-rss'></i>
                        <h3>Manage</h3>
                        <p>
                            View and manage all upcoming posts predicted in the virtual calendar.
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
                            Recycle vs Recurring
                            <br class="d-none d-md-block">
                            What’s the Difference?
                        </h2>
                        <p>
                            Put simply: Recurring posts have expiration dates, while recycling is timeless.
                        </p>
                        <p>
                            Recurring settings allow you to share your seasonal and time-sensitive giveaways, events and
                            sales on a set schedule.
                        </p>
                        <p>
                            Recycling content is considered timeless, an ongoing campaign to reshare your best content with
                            no end in sight. You can manage how often recycled posts are shared and for how long they’re
                            posted.
                        </p>
                    </div>
                </div>
                <div class="col-12 col-lg-6 pxl-24">
                    <div class="featured-images">
                        <img class="w-100 h-100" alt="About Workspaces in {{ env('APP_NAME', 'Engagyo') }}"
                            src="{{ asset('assets/frontend/images/Features/Recycle-Recurring.jpg') }}">
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
                            Build credibility with Spintax
                        </h2>
                        <p>
                            {{ env('APP_NAME', 'Engagyo') }} proudly integrates with the Spintax generator, which allows every user to prepare
                            tons of content in less time than you can imagine.
                        </p>
                        <p>
                            By adding proper {keywords | synonyms} between brackets and pipes, the generator will be able to
                            build new sentences that still make sense and have the exact meaning.
                        </p>
                        <p>
                            Spintax helps you save time and create human-like social media posts. Don’t forget to use it
                            while you prepare your evergreen content!
                        </p>
                        <div class="btn btn-featured">Learn more</div>
                    </div>
                </div>
                <div class="col-12 col-lg-6 pxr-24">
                    <div class="featured-images">
                        <img class="img-fluid w-100 h-100" alt="Analytics illustration Image of {{ env('APP_NAME', 'Engagyo') }}"
                            src="{{ asset('assets/frontend/images/Features/Recycle-Spintax.jpg') }}">
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
                <div class="col-12 col-md-4">
                    <div class="review-container">
                        <i class='bx bxs-dashboard'></i>
                        <h3>Dashboard</h3>
                        <p>
                            Go to dashboard look for posts.
                        </p>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="review-container">
                        <i class='bx bx-recycle'></i>
                        <h3>Select Recycle</h3>
                        <p>
                            Select recycle to the post you want.
                        </p>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="review-container">
                        <i class='bx bx-repost'></i>
                        <h3>Click repost</h3>
                        <p>
                            Repost it with current time/date.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
