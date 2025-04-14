@extends('frontend.layout.main')
@section('body')
    <!-- Blog Banner -->
    <div class="container-fluid mt-48 py-24">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-12 col-lg-6">
                    <div class="featured-images">
                        <img class="img-fluid w-100 h-100" alt="Featured Image about Calender"
                            src="{{ asset('assets/frontend/images/FilterMajor.jpg') }}">
                    </div>
                </div>
                <div class="col-12 col-lg-6">
                    <div class="blog-banner-content px-3">
                        <div class="col-12">
                            <div class="blog-hashtag-holder">
                                <div class="blog-hashtag lg-blight">
                                    <span class="blog-title">LinkedIn</span>
                                </div>
                                <div class="blog-hashtag lg-zephorn">
                                    <span class="blog-title">Product Updates</span>
                                </div>
                                <div class="blog-hashtag lg-seraphin">
                                    <span class="blog-title">Tiktok</span>
                                </div>
                            </div>
                        </div>
                        <h1>
                            <a href="blogpages/tiktok.html">
                                Boost Your Engagement:
                                <br class="d-none d-lg-block">
                                Schedule 35-Image TikTok
                                <br class="d-none d-lg-block">
                                Carousels with {{env("APP_NAME", "Engagyo")}}
                            </a>
                        </h1>
                        <p>
                            Discover how to schedule TikTok carousels with up to 35 photos using {{env("APP_NAME", "Engagyo")}}.
                            <br class="d-none d-lg-block">
                            Enhance your engagement with our comprehensive guide and elevate your content strategy.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Latest Articles -->
    <div class="container-fluid py-5 bg-light-white">
        <div class="container">
            <div class="blog-containers">
                <div class="row">
                    <div class="col-12 d-flex align-items-center justify-content-between">
                        <div class="text-wrapper w-75">
                            <h3>
                                Latest Articles
                            </h3>
                            <p>
                                Explore the Latest News, Trends, and Updates on Social Media & {{env("APP_NAME", "Engagyo")}}
                            </p>
                        </div>
                        <div class="view-btn-holder d-none d-xl-flex">
                            <button class="btn btn-over-dark">
                                <a href="latest.html">View All</a>
                            </button>
                        </div>
                    </div>
                    <!-- Blogs Are Added here -->
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="blog-content-manage">
                            <div class="blog-contentImage">
                                <img src="{{ asset('assets/frontend/images/blog10.jpg') }}">
                            </div>
                            <div class="col-12">
                                <div class="blog-hashtag-holder">
                                    <div class="blog-hashtag lg-blight">
                                        <span class="blog-title">LinkedIn</span>
                                    </div>
                                    <div class="blog-hashtag lg-zephorn">
                                        <span class="blog-title">Product Updates</span>
                                    </div>
                                    <div class="blog-hashtag lg-seraphin">
                                        <span class="blog-title">Tiktok</span>
                                    </div>
                                </div>
                            </div>
                            <div class="blog-contentArea">
                                <a href="#">
                                    Boost Your Engagement: Schedule 35-Image TikTok Carousels With {{env("APP_NAME", "Engagyo")}}
                                </a>
                                <p>
                                    Discover how to schedule TikTok carousels with up to 35 photos using Publer. Enhance
                                    your engagement with our comprehensive guide and elevate your content strategy.
                                </p>
                                <span>
                                    September 03, 2024
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="blog-content-manage">
                            <div class="blog-contentImage">
                                <img src="{{ asset('assets/frontend/images/blog3.jpg') }}">
                            </div>
                            <div class="col-12">
                                <div class="blog-hashtag-holder">
                                    <div class="blog-hashtag lg-blight">
                                        <span class="blog-title">LinkedIn</span>
                                    </div>
                                    <div class="blog-hashtag lg-zephorn">
                                        <span class="blog-title">Product Updates</span>
                                    </div>
                                    <div class="blog-hashtag lg-seraphin">
                                        <span class="blog-title">Tiktok</span>
                                    </div>
                                </div>
                            </div>
                            <div class="blog-contentArea">
                                <a href="#">
                                    Boost Your Engagement: Schedule 35-Image TikTok Carousels With {{env("APP_NAME", "Engagyo")}}
                                </a>
                                <p>
                                    Discover how to schedule TikTok carousels with up to 35 photos using Publer. Enhance
                                    your engagement with our comprehensive guide and elevate your content strategy.
                                </p>
                                <span>
                                    September 03, 2024
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="newsletter-colored d-flex flex-column justify-content-center align-items-center ">
                            <div class="text-wrapper">
                                <h2>
                                    Subscribe to our Newsletter
                                </h2>
                                <p>
                                    The latest product updates and social media news, straight to your inbox.
                                </p>
                            </div>
                            <div class="col-12">
                                <div class="newsletter-btn-holder">
                                    <input type="email" placeholder="Your email here....">
                                    <button class="btn btn-letter">Subcribe</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="blog-content-manage">
                            <div class="blog-contentImage">
                                <img src="{{ asset('assets/frontend/images/blog6.jpg') }}">
                            </div>
                            <div class="col-12">
                                <div class="blog-hashtag-holder">
                                    <div class="blog-hashtag lg-blight">
                                        <span class="blog-title">LinkedIn</span>
                                    </div>
                                    <div class="blog-hashtag lg-zephorn">
                                        <span class="blog-title">Product Updates</span>
                                    </div>
                                    <div class="blog-hashtag lg-seraphin">
                                        <span class="blog-title">Tiktok</span>
                                    </div>
                                </div>
                            </div>
                            <div class="blog-contentArea">
                                <a href="#">
                                    Boost Your Engagement: Schedule 35-Image TikTok Carousels With {{env("APP_NAME", "Engagyo")}}
                                </a>
                                <p>
                                    Discover how to schedule TikTok carousels with up to 35 photos using Publer. Enhance
                                    your engagement with our comprehensive guide and elevate your content strategy.
                                </p>
                                <span>
                                    September 03, 2024
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="blog-content-manage">
                            <div class="blog-contentImage">
                                <img src="{{ asset('assets/frontend/images/blog5.jpg') }}">
                            </div>
                            <div class="col-12">
                                <div class="blog-hashtag-holder">
                                    <div class="blog-hashtag lg-blight">
                                        <span class="blog-title">LinkedIn</span>
                                    </div>
                                    <div class="blog-hashtag lg-zephorn">
                                        <span class="blog-title">Product Updates</span>
                                    </div>
                                    <div class="blog-hashtag lg-seraphin">
                                        <span class="blog-title">Tiktok</span>
                                    </div>
                                </div>
                            </div>
                            <div class="blog-contentArea">
                                <a href="#">
                                    Boost Your Engagement: Schedule 35-Image TikTok Carousels With {{env("APP_NAME", "Engagyo")}}
                                </a>
                                <p>
                                    Discover how to schedule TikTok carousels with up to 35 photos using Publer. Enhance
                                    your engagement with our comprehensive guide and elevate your content strategy.
                                </p>
                                <span>
                                    September 03, 2024
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="blog-content-manage">
                            <div class="blog-contentImage">
                                <img src="{{ asset('assets/frontend/images/blog13.jpg') }}">
                            </div>
                            <div class="col-12">
                                <div class="blog-hashtag-holder">
                                    <div class="blog-hashtag lg-blight">
                                        <span class="blog-title">LinkedIn</span>
                                    </div>
                                    <div class="blog-hashtag lg-zephorn">
                                        <span class="blog-title">Product Updates</span>
                                    </div>
                                    <div class="blog-hashtag lg-seraphin">
                                        <span class="blog-title">Tiktok</span>
                                    </div>
                                </div>
                            </div>
                            <div class="blog-contentArea">
                                <a href="#">
                                    Boost Your Engagement: Schedule 35-Image TikTok Carousels With {{env("APP_NAME", "Engagyo")}}
                                </a>
                                <p>
                                    Discover how to schedule TikTok carousels with up to 35 photos using Publer. Enhance
                                    your engagement with our comprehensive guide and elevate your content strategy.
                                </p>
                                <span>
                                    September 03, 2024
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Instagram -->
    <div class="container-fluid py-5">
        <div class="container">
            <div class="blog-containers">
                <div class="row">
                    <div class="col-12 d-flex align-items-center justify-content-between">
                        <div class="text-wrapper w-75">
                            <h3>
                                Instagram
                            </h3>
                            <p>
                                Explore strategies and tips for effectively scheduling and managing Instagram content to
                                enhance engagement and visibility.
                            </p>
                        </div>
                        <div class="view-btn-holder">
                            <button class="btn btn-over-dark d-none d-md-flex">
                                <a href="instagram.html">View All</a>
                            </button>
                        </div>
                    </div>
                    <!-- Blogs Are Added here -->
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="blog-content-manage">
                            <div class="blog-contentImage">
                                <img src="{{ asset('assets/frontend/images/blog11.jpg') }}">
                            </div>
                            <div class="col-12">
                                <div class="blog-hashtag-holder">
                                    <div class="blog-hashtag lg-blight">
                                        <span class="blog-title">LinkedIn</span>
                                    </div>
                                    <div class="blog-hashtag lg-zephorn">
                                        <span class="blog-title">Product Updates</span>
                                    </div>
                                    <div class="blog-hashtag lg-seraphin">
                                        <span class="blog-title">Tiktok</span>
                                    </div>
                                </div>
                            </div>
                            <div class="blog-contentArea">
                                <a href="#">
                                    Boost Your Engagement: Schedule 35-Image TikTok Carousels With {{env("APP_NAME", "Engagyo")}}
                                </a>
                                <p>
                                    Discover how to schedule TikTok carousels with up to 35 photos using Publer. Enhance
                                    your engagement with our comprehensive guide and elevate your content strategy.
                                </p>
                                <span>
                                    September 03, 2024
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="blog-content-manage">
                            <div class="blog-contentImage">
                                <img src="{{ asset('assets/frontend/images/blog6.jpg') }}">
                            </div>
                            <div class="col-12">
                                <div class="blog-hashtag-holder">
                                    <div class="blog-hashtag lg-blight">
                                        <span class="blog-title">LinkedIn</span>
                                    </div>
                                    <div class="blog-hashtag lg-zephorn">
                                        <span class="blog-title">Product Updates</span>
                                    </div>
                                    <div class="blog-hashtag lg-seraphin">
                                        <span class="blog-title">Tiktok</span>
                                    </div>
                                </div>
                            </div>
                            <div class="blog-contentArea">
                                <a href="#">
                                    Boost Your Engagement: Schedule 35-Image TikTok Carousels With {{env("APP_NAME", "Engagyo")}}
                                </a>
                                <p>
                                    Discover how to schedule TikTok carousels with up to 35 photos using Publer. Enhance
                                    your engagement with our comprehensive guide and elevate your content strategy.
                                </p>
                                <span>
                                    September 03, 2024
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="blog-content-manage">
                            <div class="blog-contentImage">
                                <img src="{{ asset('assets/frontend/images/blog9.jpg') }}">
                            </div>
                            <div class="col-12">
                                <div class="blog-hashtag-holder">
                                    <div class="blog-hashtag lg-blight">
                                        <span class="blog-title">LinkedIn</span>
                                    </div>
                                    <div class="blog-hashtag lg-zephorn">
                                        <span class="blog-title">Product Updates</span>
                                    </div>
                                    <div class="blog-hashtag lg-seraphin">
                                        <span class="blog-title">Tiktok</span>
                                    </div>
                                </div>
                            </div>
                            <div class="blog-contentArea">
                                <a href="#">
                                    Boost Your Engagement: Schedule 35-Image TikTok Carousels With {{env("APP_NAME", "Engagyo")}}
                                </a>
                                <p>
                                    Discover how to schedule TikTok carousels with up to 35 photos using Publer. Enhance
                                    your engagement with our comprehensive guide and elevate your content strategy.
                                </p>
                                <span>
                                    September 03, 2024
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Social Media Holiday Calendar -->
    <div class="container-fluid py-5 bg-light-white">
        <div class="container">
            <div class="blog-containers">
                <div class="row">
                    <div class="col-12 d-flex align-items-center justify-content-between">
                        <div class="text-wrapper w-75">
                            <h3>
                                Social Media Holiday Calendar
                            </h3>
                            <p>
                                Plan your content around social media holidays and events with the Social Media Holiday
                                Calendar.
                            </p>
                        </div>
                        <div class="view-btn-holder">
                            <button class="btn btn-over-dark">
                                <a href="social-media.html">View All</a>
                            </button>
                        </div>
                    </div>
                    <!-- Blogs Are Added here -->
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="blog-content-manage">
                            <div class="blog-contentImage">
                                <img src="{{ asset('assets/frontend/images/blog10.jpg') }}">
                            </div>
                            <div class="col-12">
                                <div class="blog-hashtag-holder">
                                    <div class="blog-hashtag lg-blight">
                                        <span class="blog-title">LinkedIn</span>
                                    </div>
                                    <div class="blog-hashtag lg-zephorn">
                                        <span class="blog-title">Product Updates</span>
                                    </div>
                                    <div class="blog-hashtag lg-seraphin">
                                        <span class="blog-title">Tiktok</span>
                                    </div>
                                </div>
                            </div>
                            <div class="blog-contentArea">
                                <a href="#">
                                    Boost Your Engagement: Schedule 35-Image TikTok Carousels With {{env("APP_NAME", "Engagyo")}}
                                </a>
                                <p>
                                    Discover how to schedule TikTok carousels with up to 35 photos using Publer. Enhance
                                    your engagement with our comprehensive guide and elevate your content strategy.
                                </p>
                                <span>
                                    September 03, 2024
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="blog-content-manage">
                            <div class="blog-contentImage">
                                <img src="{{ asset('assets/frontend/images/blog5.jpg') }}">
                            </div>
                            <div class="col-12">
                                <div class="blog-hashtag-holder">
                                    <div class="blog-hashtag lg-blight">
                                        <span class="blog-title">LinkedIn</span>
                                    </div>
                                    <div class="blog-hashtag lg-zephorn">
                                        <span class="blog-title">Product Updates</span>
                                    </div>
                                    <div class="blog-hashtag lg-seraphin">
                                        <span class="blog-title">Tiktok</span>
                                    </div>
                                </div>
                            </div>
                            <div class="blog-contentArea">
                                <a href="#">
                                    Boost Your Engagement: Schedule 35-Image TikTok Carousels With {{env("APP_NAME", "Engagyo")}}
                                </a>
                                <p>
                                    Discover how to schedule TikTok carousels with up to 35 photos using Publer. Enhance
                                    your engagement with our comprehensive guide and elevate your content strategy.
                                </p>
                                <span>
                                    September 03, 2024
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="blog-content-manage">
                            <div class="blog-contentImage">
                                <img src="{{ asset('assets/frontend/images/blog7.jpg') }}">
                            </div>
                            <div class="col-12">
                                <div class="blog-hashtag-holder">
                                    <div class="blog-hashtag lg-blight">
                                        <span class="blog-title">LinkedIn</span>
                                    </div>
                                    <div class="blog-hashtag lg-zephorn">
                                        <span class="blog-title">Product Updates</span>
                                    </div>
                                    <div class="blog-hashtag lg-seraphin">
                                        <span class="blog-title">Tiktok</span>
                                    </div>
                                </div>
                            </div>
                            <div class="blog-contentArea">
                                <a href="#">
                                    Boost Your Engagement: Schedule 35-Image TikTok Carousels With {{env("APP_NAME", "Engagyo")}}
                                </a>
                                <p>
                                    Discover how to schedule TikTok carousels with up to 35 photos using Publer. Enhance
                                    your engagement with our comprehensive guide and elevate your content strategy.
                                </p>
                                <span>
                                    September 03, 2024
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Newsletter -->
    <div class="container-fluid ">
        <div class="container my-48">
            <div class="row">
                <div class="col-12">
                    <div
                        class="newsletter-colored full-width d-flex flex-column justify-content-center align-items-center ">
                        <div class="text-wrapper">
                            <h2>
                                Subscribe to our Newsletter
                            </h2>
                            <p>
                                The latest product updates and social media news, straight to your inbox.
                            </p>
                        </div>
                        <div class="col-12">
                            <div
                                class="newsletter-btn-holder d-block d-lg-flex align-items-center col-12 col-md-6 mx-auto">
                                <input type="email" class="w-100" placeholder="Your email here....">
                                <button class="btn btn-letter">Subcribe</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Popular Articles -->
    <div class="container-fluid">
        <div class="container">
            <div class="blog-containers">
                <div class="row">
                    <div class="col-12 d-flex align-items-center justify-content-between">
                        <div class="text-wrapper">
                            <h3>
                                Popular Articles
                            </h3>
                            <p>
                                What everyone's talking about
                            </p>
                        </div>
                    </div>
                    <!-- Blogs Are Added here -->
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="blog-content-manage">
                            <div class="blog-contentImage">
                                <img src="{{ asset('assets/frontend/images/blog12.jpg') }}">
                            </div>
                            <div class="col-12">
                                <div class="blog-hashtag-holder">
                                    <div class="blog-hashtag lg-blight">
                                        <span class="blog-title">LinkedIn</span>
                                    </div>
                                    <div class="blog-hashtag lg-zephorn">
                                        <span class="blog-title">Product Updates</span>
                                    </div>
                                    <div class="blog-hashtag lg-seraphin">
                                        <span class="blog-title">Tiktok</span>
                                    </div>
                                </div>
                            </div>
                            <div class="blog-contentArea">
                                <a href="#">
                                    Boost Your Engagement: Schedule 35-Image TikTok Carousels With {{env("APP_NAME", "Engagyo")}}
                                </a>
                                <p>
                                    Discover how to schedule TikTok carousels with up to 35 photos using Publer. Enhance
                                    your engagement with our comprehensive guide and elevate your content strategy.
                                </p>
                                <span>
                                    September 03, 2024
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="blog-content-manage">
                            <div class="blog-contentImage">
                                <img src="{{ asset('assets/frontend/images/blog13.jpg') }}">
                            </div>
                            <div class="col-12">
                                <div class="blog-hashtag-holder">
                                    <div class="blog-hashtag lg-blight">
                                        <span class="blog-title">LinkedIn</span>
                                    </div>
                                    <div class="blog-hashtag lg-zephorn">
                                        <span class="blog-title">Product Updates</span>
                                    </div>
                                    <div class="blog-hashtag lg-seraphin">
                                        <span class="blog-title">Tiktok</span>
                                    </div>
                                </div>
                            </div>
                            <div class="blog-contentArea">
                                <a href="#">
                                    Boost Your Engagement: Schedule 35-Image TikTok Carousels With {{env("APP_NAME", "Engagyo")}}
                                </a>
                                <p>
                                    Discover how to schedule TikTok carousels with up to 35 photos using Publer. Enhance
                                    your engagement with our comprehensive guide and elevate your content strategy.
                                </p>
                                <span>
                                    September 03, 2024
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="blog-content-manage">
                            <div class="blog-contentImage">
                                <img src="{{ asset('assets/frontend/images/blog8.jpg') }}">
                            </div>
                            <div class="col-12">
                                <div class="blog-hashtag-holder">
                                    <div class="blog-hashtag lg-blight">
                                        <span class="blog-title">LinkedIn</span>
                                    </div>
                                    <div class="blog-hashtag lg-zephorn">
                                        <span class="blog-title">Product Updates</span>
                                    </div>
                                    <div class="blog-hashtag lg-seraphin">
                                        <span class="blog-title">Tiktok</span>
                                    </div>
                                </div>
                            </div>
                            <div class="blog-contentArea">
                                <a href="#">
                                    Boost Your Engagement: Schedule 35-Image TikTok Carousels With {{env("APP_NAME", "Engagyo")}}
                                </a>
                                <p>
                                    Discover how to schedule TikTok carousels with up to 35 photos using Publer. Enhance
                                    your engagement with our comprehensive guide and elevate your content strategy.
                                </p>
                                <span>
                                    September 03, 2024
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer Head -->
    <div class="container-fluid border-top my-48">
        <div class="container py-5">
            <div class="row align-items-center">
                <div class="col-12 col-lg-6 pxr-24">
                    <div class="featured-images">
                        <img class="w-100 h-100" alt="Featured Image about Calender"
                            src="{{ asset('assets/frontend/images/AdublisherLOGO.jpg') }}">
                    </div>
                </div>
                <div class="col-12 col-lg-6 pxl-24 mx-auto px-5">
                    <div class="text-wrapper center">
                        <div class="logo">
                            <img src="{{ asset('assets/frontend/images/logo.svg') }}" alt="">
                        </div>
                        <p class="m-2">
                            Powerful Social Media Management Platform
                        </p>
                        <p class="desc-small">
                            Trusted by 350,000+ social media managers, marketers, and global brands
                        </p>
                        <button class="btn btn-colored">
                            <a href="../users/signup.html">Try it for free</a>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
