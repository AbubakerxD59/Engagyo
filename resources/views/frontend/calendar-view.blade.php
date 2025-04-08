@extends('frontend.layout.features')
@section('body')
    <!-- Calendar Banner -->
    <div id="calendar" class="container-fluid pt-5">
        <div class="container pt-5 my-48">
            <div class="row align-items-center">
                <div class="col-12 col-lg-6">
                    <div class="text-wrapper featured-text">
                        <span>Calender View</span>
                        <h2>
                            Let the Smooth Scheduling Experience Begin!
                        </h2>
                        <p>
                            Craft new social media posts individually or in bulk and aesthetically organize them on the
                            calendar. Drag & drop, find new content ideas, filter, and search as needed.
                        </p>
                        <button class="btn btn-colored">Create account</button>
                    </div>
                </div>
                <div class="col-12 col-lg-6 my-5">
                    <div class="banner-illustration-image">
                        <img src="{{ asset('assets/frontend/images/Calendar_View.png') }}" class="w-100">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Calendar Features -->
    <div class="container-fluid bg-light-white">
        <div class="container py-24">
            <!-- First -->
            <div class="row g-4 align-items-center mx-auto px-auto">
                <div class="col-12 col-lg-6 pxr-24">
                    <div class="featured-images">
                        <img class="w-100 h-100" alt="Featured Image about Calender"
                            src="{{ asset('assets/frontend/images/Features/Calendar-Manage.jpg') }}">
                    </div>
                </div>
                <div class="col-12 col-lg-6 pxl-24">
                    <div class="text-wrapper featured-text">
                        <h2>
                            Manage Posts Easily
                        </h2>
                        <p>
                            {{ env('APP_NAME', 'Engagyo') }}’s virtual calendar simplifies your work management. Posts will display on the calendar
                            with their respective icons (draft, manually scheduled,
                            recycling, recurring, and already posted).
                        </p>
                        <p>
                            You can create brand new posts right from the calendar. Pick the desired social network, click
                            the + icon below a date, and start composing!
                        </p>
                        <p>
                            There’s also a drag & drop superpower that will help you change the date of a post in a second.
                        </p>
                        <button class="btn btn-featured">Read more</button>
                    </div>
                </div>
            </div>
        </div>
        <!-- Second -->
        <div class="container py-24">
            <div class="row reverse g-4 align-items-center mx-auto px-auto">
                <div class="col-12 col-lg-6 pxl-24">
                    <div class="featured-images">
                        <img class="w-100 h-100" alt="Link in Bio illustration Image"
                            src="{{ asset('assets/frontend/images/Features/Calendar-Filter.jpg') }}">
                    </div>
                </div>
                <div class="col-12 col-lg-6 pxr-24">
                    <div class="text-wrapper featured-text">
                        <h2>
                            Filter Posts
                        </h2>
                        <p>
                            Don’t let yourself get distracted by a jungle of posts your members and you have prepared for
                            the following months. If you want to focus solely on a few upcoming posts, {{ env('APP_NAME', 'Engagyo') }} gives you
                            filter options for organizing, categorizing, and prioritizing your work.
                        </p>
                        <ul class="feature-lister">
                            <li>
                                <i class='bx bx-check'></i>
                                Filter posts by social account or member.
                            </li>
                            <li>
                                <i class='bx bx-check'></i>
                                Switch between months, weeks, & days in a click.
                            </li>
                            <li>
                                <i class='bx bx-check'></i>
                                Search by text content and keywords.
                            </li>
                        </ul>
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
                        Every Day is a Holiday!
                    </h2>
                    <p class="description">
                        Keeping content fresh and creative is key. To make things easier for you, we've created a visual
                        calendar with
                        <br class="d-none d-xl-block">
                        national, international and awareness social media holidays, along with their related keywords.
                        <br class="d-none d-xl-block">
                        Keep up with our holiday calendar monthly updates.
                    </p>
                </div>
            </div>
            <div class="col-12 mt-5">
                <div class="container img-container-center">
                    <img src="{{ asset('assets/frontend/images/Holiday.png') }}" class="h-100 w-100">
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid bg-light-white">
        <div class="container py-24">
            <!-- Third -->
            <div class="row g-4 align-items-center mx-auto px-auto">
                <div class="col-12 col-lg-6 pxr-24">
                    <div class="featured-images">
                        <img class="w-100 h-100" alt="About Workspaces in {{ env('APP_NAME', 'Engagyo') }}"
                            src="{{ asset('assets/frontend/images/Features/Calendar-Predict.jpg') }}">
                    </div>
                </div>
                <div class="col-12 col-lg-6 pxl-24">
                    <div class="text-wrapper featured-text">
                        <h2>
                            Predict Evergreen Content
                        </h2>
                        <p>
                            Make your content valuable and keep your business' online reputation flourishing.
                        </p>
                        <p>
                            Examine evergreen content you've prepared in advance and view any previously automated content.
                        </p>
                        <p>
                            Boost SEO efforts, constantly reach potential people, build an audience, and keep having an
                            active social media feed with timeless, relevant, and valuable information.
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <div class="container py-24">
            <!-- Fourth -->
            <div class="row reverse g-4 align-items-center mx-auto px-auto">
                <div class="col-12 col-lg-6 pxl-24">
                    <div class="featured-images">
                        <img class="img-fluid w-100 h-100" alt="Analytics illustration Image of {{ env('APP_NAME', 'Engagyo') }}"
                            src="{{ asset('assets/frontend/images/Features/Calendar-Time.jpg') }}">
                    </div>
                </div>
                <div class="col-12 col-lg-6 pxr-24">
                    <div class="text-wrapper featured-text">
                        <h2>
                            View Timeslots and
                            <br class="d-none d-lg-block">
                            Best Times to Post
                        </h2>
                        <p>
                            {{ env('APP_NAME', 'Engagyo') }} makes it easier to boost engagement, improve ROI, and run your business better - by
                            displaying the best times to post.
                        </p>
                        <p>
                            The algorithm allows you to select the daily peak engagement based on previous posts. We collect
                            the posts that have generated the most engagement and determine the best-performing time of the
                            day.
                        </p>
                        <p>
                            If you have prepared a detailed posting schedule, every timeslot will appear on the virtual
                            calendar so you can manage future posts with peace of mind.
                        </p>
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
                        Instagram Feed Preview
                    </h2>
                    <p class="description">
                        Refine an aesthetic Instagram feed by planning out visuals one by one or in bulk. Import or design
                        brand new marketing
                        <br class="d-none d-lg-block">
                        visuals, easily filter, and automatically schedule posts with pre-set posting timeslots.
                        <br class="d-none d-lg-block">
                        One drag and drop away from creating a visually appealing Instagram feed.
                    </p>
                </div>
            </div>
            <div class="col-12 mt-5">
                <div class="container img-container-center">
                    <img src="{{ asset('assets/frontend/images/Android.png') }}" class="h-100 w-100">
                </div>
            </div>
        </div>
    </div>
@endsection
