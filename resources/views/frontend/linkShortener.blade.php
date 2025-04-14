@extends('frontend.layout.features')
@section('body')
    <!-- URL Link Shortner Banner -->
    <div class="container-fluid bg-light-gradient">
        <div class="container mt-48 py-5">
            <div class="text-wrapper center">
                <div class="animated-text">
                    Shorten URL's for <span></span>
                </div>
                <p>
                    Paste the URL of the post or media and press to download in HD.
                </p>
            </div>
            <div class="col-12">
                <div class="url-shortner mx-auto d-block d-lg-flex align-items-center">
                    <input type="text" placeholder="https://" class="short-linker-field">
                    <button class="btn btn-link-shortner">Download</button>
                </div>
            </div>
        </div>
    </div>

    <!-- URL Link Shortner Features -->
    <div class="container-fluid bg-light-white">
        <div class="container py-5">
            <div class="col-12">
                <div class="text-wrapper center">
                    <h2>
                        Signup to use free url tracking?
                    </h2>
                    <p class="desc-small">
                        No need to waste storage & bandwidth as this tool is fully integrated within
                        <a href="{{ route('frontend.home') }}">
                            {{ env('APP_NAME', 'Engagyo') }}
                        </a>!
                    </p>
                </div>
            </div>
            <div class="col-12">
                <div class="row">
                    <div class="col-12 col-lg-5">
                        <div class="share__content__item">
                            <div class="share__content__link">
                                <h3>
                                    <i class='bx bxl-instagram'></i>
                                    Instagram photos, videos, stories and reels
                                </h3>
                                <i class="bx bx-chevron-down"></i>
                            </div>
                            <div class="share__content">
                                <p>
                                    Download photos, videos, stories & reels from Instagram and share them natively on your
                                    social accounts.
                                </p>
                            </div>
                        </div>
                        <div class="share__content__item">
                            <div class="share__content__link">
                                <h3>
                                    <i class='bx bxl-tiktok'></i>
                                    TikTok videos without a watermark.
                                </h3>
                                <i class="bx bx-chevron-down"></i>
                            </div>
                            <div class="share__content">
                                <p>
                                    Download videos from TikTok and share them natively on your social accounts.
                                </p>
                            </div>
                        </div>
                        <div class="share__content__item">
                            <div class="share__content__link">
                                <h3>
                                    <i class='bx bxl-youtube'></i>
                                    Youtube videos and shorts
                                </h3>
                                <i class="bx bx-chevron-down"></i>
                            </div>
                            <div class="share__content">
                                <p>
                                    Download videos and shorts from YouTube and share them natively on your social accounts.
                                </p>
                            </div>
                        </div>
                        <div class="share__content__item">
                            <div class="share__content__link">
                                <h3>
                                    <i class='bx bxl-facebook-square'></i>
                                    Facebook videos
                                </h3>
                                <i class="bx bx-chevron-down"></i>
                            </div>
                            <div class="share__content">
                                <p>
                                    Download videos from Facebook, and share them natively on your social accounts.
                                </p>
                            </div>
                        </div>
                        <div class="share__content__item">
                            <div class="share__content__link">
                                <h3>
                                    <i class='bx bxl-linkedin'></i>
                                    LinkedIn Videos
                                </h3>
                                <i class="bx bx-chevron-down"></i>
                            </div>
                            <div class="share__content">
                                <p>
                                    Download videos from LinkedIn, and share them natively on your social accounts.
                                </p>
                            </div>
                        </div>
                        <div class="share__content__item">
                            <div class="share__content__link">
                                <h3>
                                    <i class='bx bxl-twitter'></i>
                                    Twitter photos and videos
                                </h3>
                                <i class="bx bx-chevron-down"></i>
                            </div>
                            <div class="share__content">
                                <p>
                                    Download videos from LinkedIn, and share them natively on your social accounts.
                                </p>
                            </div>
                        </div>
                        <button class="btn btn-featured">
                            <a href="{{ route('frontend.showRegister') }}">Start using Url Tracking</a>
                        </button>
                    </div>
                    <div class="d-none d-lg-flex col-12 col-md-6 col-lg-7">
                        <div class="featured-images">
                            <img src="{{ asset('assets/frontend/images/LinkShortner/LinksTeaching.jpg') }}">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Browser Integrations -->
    <div class="container-fluid">
        <div class="container py-24">
            <div class="col-12">
                <div class="text-wrapper center">
                    <h2>
                        Use {{ env('APP_NAME', 'Engagyo') }} in many Browers
                    </h2>
                    <p class="desc-small">
                        Install our free <a href="#"> browser extension </a> and easily share links, photos, and
                        quotes across multiple social networks!
                    </p>
                </div>
            </div>
            <div class="row pb-5">
                <div class="col-12 col-md-6 col-lg-3">
                    <div class="review-container left text-center">
                        <i class='bx bxl-google'></i>
                        <h3>Google Chrome</h3>
                        <a href="https://www.google.com/chrome/">Install now</a>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <div class="review-container left text-center">
                        <i class='bx bxl-firefox'></i>
                        <h3>Mozilla Firefox</h3>
                        <a href="https://www.mozilla.org">Install now</a>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <div class="review-container left text-center">
                        <i class='bx bxl-meta'></i>
                        <h3>Safari</h3>
                        <a href="http://apple.com/safari/">Install now</a>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <div class="review-container left text-center">
                        <i class='bx bxl-edge'></i>
                        <h3>Edge</h3>
                        <a href="https://www.microsoft.com/en-us/edge">Install now</a>
                    </div>
                </div>
            </div>
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
                    <button class="btn btn-colored">
                        <a href="{{ route('frontend.showRegister') }}">Get started for free</a>
                    </button>
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
                        {{ env('APP_NAME', 'Engagyo') }}s is part of daily lives of thousands of social media marketers
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
    </div>
@endsection
