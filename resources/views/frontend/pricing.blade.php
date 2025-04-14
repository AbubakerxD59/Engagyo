@extends('frontend.layout.features')
@section('body')
    <!-- Plans Banner -->
    <div id="plans" class="container-fluid">
        <div class="container pt-5 my-48">
            <div class="col-12 d-block justify-content-center text-center">
                <div class="text-wrapper center align-items-center">
                    <h2>
                        Simple pricing for everyone
                    </h2>
                    <p>
                        From personal use to small businesses to enterprises.
                        <br class="d-none d-lg-block">
                        There's a Superhero for everyone!
                    </p>
                </div>
                <div class="btn-switcher mx-auto">
                    <button type="button" class="btn-switch switched" data-period="monthly">Monthly</button>
                    <button type="button" class="btn-switch" data-period="yearly">Anually <b>2 Months Free</b></button>
                </div>
            </div>
            <!-- Planing Cards -->
            <div class="row g-4 py-24">
                <div class="col-12 col-xl-4">
                    <div class="plan-card monthly-plan stan">
                        <div class="plan-header border-bottom">
                            <h2>
                                Free
                            </h2>
                            <span>
                                Build a momentum on social media
                            </span>
                            <h1>
                                $0.00
                            </h1>
                        </div>
                        <div class="account-changer d-block d-xl-flex">
                            <div class="col-12 col-xl-6">
                                <div class="changer-container">
                                    <p>
                                        Social Accounts
                                    </p>
                                    <div class="auto-changer">
                                        <p class="my-auto">
                                            3
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-xl-6">
                                <div class="changer-container">
                                    <p>
                                        Additional Members
                                    </p>
                                    <div class="auto-changer">
                                        <p class="my-auto">
                                            0
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="plan-content">
                            <h3>
                                Included:
                            </h3>
                            <ul>
                                <li>
                                    <i class='bx bx-check'></i>
                                    3 social accounts
                                    <span>
                                        (limited to one Twitter / X account)
                                    </span>
                                </li>
                                <li>
                                    <i class='bx bx-check'></i>
                                    1 workspace
                                </li>
                                <li>
                                    <i class='bx bx-check'></i>
                                    10 pending scheduled posts per account
                                </li>
                                <li>
                                    <i class='bx bx-check'></i>
                                    25 saved drafts
                                </li>
                                <li>
                                    <i class='bx bx-check'></i>
                                    24 hours posts history
                                </li>
                                <li>
                                    <i class='bx bx-check'></i>
                                    Free trials on paid features
                                </li>
                                <li>
                                    <i class='bx bx-check'></i>
                                    Link in Bio for Instagram
                                </li>
                            </ul>
                        </div>
                        <button class="btn btn-plans">
                            <a href="pages/users/signup.html">Get Started - It's free</a>
                        </button>
                    </div>
                    <div class="plan-card yearly-plan d-none stan">
                        <div class="plan-header border-bottom">
                            <h2>
                                Free
                            </h2>
                            <span>
                                Build a momentum on social media
                            </span>
                            <h1>
                                $0.00
                            </h1>
                        </div>
                        <div class="account-changer d-block d-xl-flex">
                            <div class="col-12 col-xl-6">
                                <div class="changer-container">
                                    <p>
                                        Social Accounts
                                    </p>
                                    <div class="auto-changer">
                                        <p class="my-auto">
                                            3
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-xl-6">
                                <div class="changer-container">
                                    <p>
                                        Additional Members
                                    </p>
                                    <div class="auto-changer">
                                        <p class="my-auto">
                                            0
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="plan-content">
                            <h3>
                                Included:
                            </h3>
                            <ul>
                                <li>
                                    <i class='bx bx-check'></i>
                                    3 social accounts
                                    <span>
                                        (limited to one Twitter / X account)
                                    </span>
                                </li>
                                <li>
                                    <i class='bx bx-check'></i>
                                    1 workspace
                                </li>
                                <li>
                                    <i class='bx bx-check'></i>
                                    10 pending scheduled posts per account
                                </li>
                                <li>
                                    <i class='bx bx-check'></i>
                                    25 saved drafts
                                </li>
                                <li>
                                    <i class='bx bx-check'></i>
                                    24 hours posts history
                                </li>
                                <li>
                                    <i class='bx bx-check'></i>
                                    Free trials on paid features
                                </li>
                                <li>
                                    <i class='bx bx-check'></i>
                                    Link in Bio for Instagram
                                </li>
                            </ul>
                        </div>
                        <button class="btn btn-plans">
                            <a href="pages/users/signup.html">Get Started - It's free</a>
                        </button>
                    </div>
                    <!-- For Medium Screen -->
                    <div class="col-12 d-block d-lg-none">
                        <div class="plan-smd-item">
                            <div class="plan-smd-link">
                                <h2>Common Features</h2>
                                <i class='bx bx-plus'></i>
                            </div>
                            <div class="plan-smd-content">
                                <div class="plan-list-item">
                                    <p>
                                        Twitter / X integration
                                    </p>
                                    <i class='bx bx-check'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Two-Factor authentication (2FA)
                                    </p>
                                    <i class='bx bx-check'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        OpenAI integration
                                    </p>
                                    <i class='bx bx-check'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Customize posts
                                    </p>
                                    <i class='bx bx-check'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Preview posts
                                    </p>
                                    <i class='bx bx-check'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Automatically schedule posts
                                    </p>
                                    <i class='bx bx-check'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Calendar view
                                    </p>
                                    <i class='bx bx-check'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Instagram feed preview
                                    </p>
                                    <i class='bx bx-check'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Link in Bio
                                    </p>
                                    <i class='bx bx-check'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Mobile app
                                    </p>
                                    <i class='bx bx-check'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Browser extension
                                    </p>
                                    <i class='bx bx-check'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Design with Canva
                                    </p>
                                    <i class='bx bx-check'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Built-in photo editor
                                    </p>
                                    <i class='bx bx-check'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Import from Drive, Unsplash
                                    </p>
                                    <i class='bx bx-check'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Shortcodes
                                    </p>
                                    <i class='bx bx-check'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Link shortening
                                    </p>
                                    <i class='bx bx-check'></i>
                                </div>
                            </div>
                        </div>
                        <div class="plan-smd-item">
                            <div class="plan-smd-link">
                                <h2>Paid Features</h2>
                                <i class='bx bx-plus'></i>
                            </div>
                            <div class="plan-smd-content">
                                <div class="plan-list-item">
                                    <p>
                                        Unlimited scheduling & drafts
                                    </p>
                                    <i class='bx bx-x'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Unlimited workspaces
                                    </p>
                                    <i class='bx bx-x'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Bulk scheduling
                                    </p>
                                    <i class='bx bx-x'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Media library
                                    </p>
                                    <i class='bx bx-x'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Link in Bio
                                    </p>
                                    <i class='bx bx-x'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Discover trending social media posts
                                    </p>
                                    <i class='bx bx-x'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Discover trending industry news
                                    </p>
                                    <i class='bx bx-x'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Automatic RSS posting
                                    </p>
                                    <i class='bx bx-x'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Watermark photos
                                    </p>
                                    <i class='bx bx-x'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Signatures
                                    </p>
                                    <i class='bx bx-x'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Schedule comments
                                    </p>
                                    <i class='bx bx-x'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Auto-share & auto-delete
                                    </p>
                                    <i class='bx bx-x'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Eternal post history
                                    </p>
                                    <i class='bx bx-x'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Unlimited AI prompts
                                    </p>
                                    <i class='bx bx-x'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        In-depth analytics insights
                                    </p>
                                    <i class='bx bx-x'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Best times to post
                                    </p>
                                    <i class='bx bx-x'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Hashtag analysis
                                    </p>
                                    <i class='bx bx-x'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Competitor analysis
                                    </p>
                                    <i class='bx bx-x'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        PDF & CSV analytics reports
                                    </p>
                                    <i class='bx bx-x'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Hashtag suggestions
                                    </p>
                                    <i class='bx bx-x'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Automatically recycle posts
                                    </p>
                                    <i class='bx bx-x'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Schedule recurring posts
                                    </p>
                                    <i class='bx bx-x'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Spintax support
                                    </p>
                                    <i class='bx bx-x'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Design videos
                                    </p>
                                    <i class='bx bx-x'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Watermark videos
                                    </p>
                                    <i class='bx bx-x'></i>
                                </div>
                            </div>
                        </div>
                        <div class="plan-smd-item">
                            <div class="plan-smd-link">
                                <h2>Daily Post Limits</h2>
                                <i class='bx bx-plus'></i>
                            </div>
                            <div class="plan-smd-content">
                                <div class="plan-list-item">
                                    <p>
                                        Facebook Posts & Reels
                                    </p>
                                    <i>12 / day / profile</i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Facebook Stories
                                    </p>
                                    <i>12</i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Instagram Posts & Reels
                                    </p>
                                    <i>15</i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Instagram Stories
                                    </p>
                                    <i>15</i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Threads App Posts
                                    </p>
                                    <i>150</i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        TikTok Videos
                                    </p>
                                    <i>15</i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Twitter / X Posts
                                    </p>
                                    <i>25</i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Mastodon Posts
                                    </p>
                                    <i>25</i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        LinkedIn Profile Posts
                                    </p>
                                    <i>10</i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        LinkedIn Page Posts
                                    </p>
                                    <i>12</i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Pinterest Pins
                                    </p>
                                    <i>12</i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Google Business Updates
                                    </p>
                                    <i>5</i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        YouTube Videos & Shorts
                                    </p>
                                    <i>5</i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        WordPress Articles
                                    </p>
                                    <i>12</i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Telegram Messages
                                    </p>
                                    <i>5</i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-xl-4">
                    <div class="plan-card monthly-plan prof">
                        <div class="plan-header border-bottom">
                            <h2>
                                Professional
                            </h2>
                            <span>
                                Scale your social media efforts
                            </span>
                            <h1>
                                $12.00
                            </h1>
                        </div>
                        <div class="account-changer d-block d-xl-flex">
                            <div class="col-12 col-xl-6">
                                <div class="changer-container">
                                    <p>
                                        Social Accounts
                                    </p>
                                    <div class="auto-changer">
                                        <p class="my-auto">
                                            3
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-xl-6">
                                <div class="changer-container">
                                    <p>
                                        Additional Members
                                    </p>
                                    <div class="auto-changer">
                                        <p class="my-auto">
                                            0
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="plan-content">
                            <h3>
                                Included:
                            </h3>
                            <ul>
                                <li>
                                    <i class='bx bx-check'></i>
                                    Multiple social accounts
                                </li>
                                <li>
                                    <i class='bx bx-check'></i>
                                    Unlimited workspace
                                </li>
                                <li>
                                    <i class='bx bx-check'></i>
                                    Unlimited scheduling & media storage
                                </li>
                                <li>
                                    <i class='bx bx-check'></i>
                                    Eternal posts history
                                </li>
                                <li>
                                    <i class='bx bx-check'></i>
                                    Discover trending social media posts & news
                                </li>
                                <li>
                                    <i class='bx bx-check'></i>
                                    Unlimited RSS Feed automations
                                </li>
                                <li>
                                    <i class='bx bx-check'></i>
                                    Unbranded Link in Bio for Instagram
                                </li>
                            </ul>
                        </div>
                        <button class="btn btn-plans">
                            <a href="pages/users/signup.html">Start a free 7-day trial</a>
                        </button>
                    </div>
                    <div class="plan-card yearly-plan d-none prof">
                        <div class="plan-header border-bottom">
                            <h2>
                                Professional
                            </h2>
                            <span>
                                Scale your social media efforts
                            </span>
                            <h1>
                                $9.50
                                <span>
                                    / month, billed annually
                                </span>
                            </h1>
                        </div>
                        <div class="account-changer d-block d-xl-flex">
                            <div class="col-12 col-xl-6">
                                <div class="changer-container">
                                    <p>
                                        Social Accounts
                                    </p>
                                    <div class="auto-changer">
                                        <p class="my-auto">
                                            3
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-xl-6">
                                <div class="changer-container">
                                    <p>
                                        Additional Members
                                    </p>
                                    <div class="auto-changer">
                                        <p class="my-auto">
                                            0
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="plan-content">
                            <h3>
                                Included:
                            </h3>
                            <ul>
                                <li>
                                    <i class='bx bx-check'></i>
                                    Multiple social accounts
                                </li>
                                <li>
                                    <i class='bx bx-check'></i>
                                    Unlimited workspace
                                </li>
                                <li>
                                    <i class='bx bx-check'></i>
                                    Unlimited scheduling & media storage
                                </li>
                                <li>
                                    <i class='bx bx-check'></i>
                                    Eternal posts history
                                </li>
                                <li>
                                    <i class='bx bx-check'></i>
                                    Discover trending social media posts & news
                                </li>
                                <li>
                                    <i class='bx bx-check'></i>
                                    Unlimited RSS Feed automations
                                </li>
                                <li>
                                    <i class='bx bx-check'></i>
                                    Unbranded Link in Bio for Instagram
                                </li>
                            </ul>
                        </div>
                        <button class="btn btn-plans">
                            <a href="pages/users/signup.html">Start a free 7-day trial</a>
                        </button>
                    </div>
                    <!-- For Medium Screen -->
                    <div class="col-12 d-block d-lg-none">
                        <div class="plan-smd-item">
                            <div class="plan-smd-link">
                                <h2>Common Features</h2>
                                <i class='bx bx-plus'></i>
                            </div>
                            <div class="plan-smd-content">
                                <div class="plan-list-item">
                                    <p>
                                        Twitter / X integration
                                    </p>
                                    <i class='bx bx-check'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Two-Factor authentication (2FA)
                                    </p>
                                    <i class='bx bx-check'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        OpenAI integration
                                    </p>
                                    <i class='bx bx-check'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Customize posts
                                    </p>
                                    <i class='bx bx-check'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Preview posts
                                    </p>
                                    <i class='bx bx-check'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Automatically schedule posts
                                    </p>
                                    <i class='bx bx-check'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Calendar view
                                    </p>
                                    <i class='bx bx-check'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Instagram feed preview
                                    </p>
                                    <i class='bx bx-check'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Link in Bio
                                    </p>
                                    <i class='bx bx-check'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Mobile app
                                    </p>
                                    <i class='bx bx-check'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Browser extension
                                    </p>
                                    <i class='bx bx-check'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Design with Canva
                                    </p>
                                    <i class='bx bx-check'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Built-in photo editor
                                    </p>
                                    <i class='bx bx-check'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Import from Drive, Unsplash
                                    </p>
                                    <i class='bx bx-check'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Shortcodes
                                    </p>
                                    <i class='bx bx-check'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Link shortening
                                    </p>
                                    <i class='bx bx-check'></i>
                                </div>
                            </div>
                        </div>
                        <div class="plan-smd-item">
                            <div class="plan-smd-link">
                                <h2>Paid Features</h2>
                                <i class='bx bx-plus'></i>
                            </div>
                            <div class="plan-smd-content">
                                <div class="plan-list-item">
                                    <p>
                                        Unlimited scheduling & drafts
                                    </p>
                                    <i class='bx bx-x'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Unlimited workspaces
                                    </p>
                                    <i class='bx bx-x'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Bulk scheduling
                                    </p>
                                    <i class='bx bx-x'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Media library
                                    </p>
                                    <i class='bx bx-x'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Link in Bio
                                    </p>
                                    <i class='bx bx-x'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Discover trending social media posts
                                    </p>
                                    <i class='bx bx-x'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Discover trending industry news
                                    </p>
                                    <i class='bx bx-x'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Automatic RSS posting
                                    </p>
                                    <i class='bx bx-x'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Watermark photos
                                    </p>
                                    <i class='bx bx-x'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Signatures
                                    </p>
                                    <i class='bx bx-x'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Schedule comments
                                    </p>
                                    <i class='bx bx-x'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Auto-share & auto-delete
                                    </p>
                                    <i class='bx bx-x'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Eternal post history
                                    </p>
                                    <i class='bx bx-x'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Unlimited AI prompts
                                    </p>
                                    <i class='bx bx-x'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        In-depth analytics insights
                                    </p>
                                    <i class='bx bx-x'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Best times to post
                                    </p>
                                    <i class='bx bx-x'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Hashtag analysis
                                    </p>
                                    <i class='bx bx-x'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Competitor analysis
                                    </p>
                                    <i class='bx bx-x'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        PDF & CSV analytics reports
                                    </p>
                                    <i class='bx bx-x'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Hashtag suggestions
                                    </p>
                                    <i class='bx bx-x'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Automatically recycle posts
                                    </p>
                                    <i class='bx bx-x'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Schedule recurring posts
                                    </p>
                                    <i class='bx bx-x'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Spintax support
                                    </p>
                                    <i class='bx bx-x'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Design videos
                                    </p>
                                    <i class='bx bx-x'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Watermark videos
                                    </p>
                                    <i class='bx bx-x'></i>
                                </div>
                            </div>
                        </div>
                        <div class="plan-smd-item">
                            <div class="plan-smd-link">
                                <h2>Daily Post Limits</h2>
                                <i class='bx bx-plus'></i>
                            </div>
                            <div class="plan-smd-content">
                                <div class="plan-list-item">
                                    <p>
                                        Facebook Posts & Reels
                                    </p>
                                    <i>12 / day / profile</i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Facebook Stories
                                    </p>
                                    <i>12</i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Instagram Posts & Reels
                                    </p>
                                    <i>15</i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Instagram Stories
                                    </p>
                                    <i>15</i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Threads App Posts
                                    </p>
                                    <i>150</i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        TikTok Videos
                                    </p>
                                    <i>15</i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Twitter / X Posts
                                    </p>
                                    <i>25</i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Mastodon Posts
                                    </p>
                                    <i>25</i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        LinkedIn Profile Posts
                                    </p>
                                    <i>10</i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        LinkedIn Page Posts
                                    </p>
                                    <i>12</i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Pinterest Pins
                                    </p>
                                    <i>12</i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Google Business Updates
                                    </p>
                                    <i>5</i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        YouTube Videos & Shorts
                                    </p>
                                    <i>5</i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        WordPress Articles
                                    </p>
                                    <i>12</i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Telegram Messages
                                    </p>
                                    <i>5</i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-xl-4">
                    <div class="plan-card monthly-plan business">
                        <div class="plan-header border-bottom">
                            <h2>
                                Business
                            </h2>
                            <span>
                                Unleash the power of social media
                            </span>
                            <h1>
                                $21.00
                            </h1>
                        </div>
                        <div class="account-changer d-block d-xl-flex">
                            <div class="col-12 col-xl-6">
                                <div class="changer-container">
                                    <p>
                                        Social Accounts
                                    </p>
                                    <div class="auto-changer">
                                        <p class="my-auto">
                                            3
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-xl-6">
                                <div class="changer-container">
                                    <p>
                                        Additional Members
                                    </p>
                                    <div class="auto-changer">
                                        <p class="my-auto">
                                            0
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="plan-content">
                            <h3>
                                Included:
                            </h3>
                            <ul>
                                <li>
                                    <i class='bx bx-check'></i>
                                    All in Professional
                                </li>
                                <li>
                                    <i class='bx bx-check'></i>
                                    Unlimited AI prompts
                                </li>
                                <li>
                                    <i class='bx bx-check'></i>
                                    In-depth analytics insights
                                </li>
                                <li>
                                    <i class='bx bx-check'></i>
                                    Competitor analysis
                                </li>
                                <li>
                                    <i class='bx bx-check'></i>
                                    Hashtag suggestions & analytics
                                </li>
                                <li>
                                    <i class='bx bx-check'></i>
                                    PDF & CSV analytics reports
                                </li>
                                <li>
                                    <i class='bx bx-check'></i>
                                    Spintax-powered post recycling
                                </li>
                            </ul>
                        </div>
                        <button class="btn btn-plans">
                            <a href="pages/users/signup.html">Start a free 14-days trial</a>
                        </button>
                    </div>
                    <div class="plan-card yearly-plan d-none business">
                        <div class="plan-header border-bottom">
                            <h2>
                                Business
                            </h2>
                            <span>
                                Unleash the power of social media
                            </span>
                            <h1>
                                $16.80
                                <span>
                                    / month, billed annually
                                </span>
                            </h1>
                        </div>
                        <div class="account-changer d-block d-xl-flex">
                            <div class="col-12 col-xl-6">
                                <div class="changer-container">
                                    <p>
                                        Social Accounts
                                    </p>
                                    <div class="auto-changer">
                                        <p class="my-auto">
                                            3
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-xl-6">
                                <div class="changer-container">
                                    <p>
                                        Additional Members
                                    </p>
                                    <div class="auto-changer">
                                        <p class="my-auto">
                                            0
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="plan-content">
                            <h3>
                                Included:
                            </h3>
                            <ul>
                                <li>
                                    <i class='bx bx-check'></i>
                                    All in Professional
                                </li>
                                <li>
                                    <i class='bx bx-check'></i>
                                    Unlimited AI prompts
                                </li>
                                <li>
                                    <i class='bx bx-check'></i>
                                    In-depth analytics insights
                                </li>
                                <li>
                                    <i class='bx bx-check'></i>
                                    Competitor analysis
                                </li>
                                <li>
                                    <i class='bx bx-check'></i>
                                    Hashtag suggestions & analytics
                                </li>
                                <li>
                                    <i class='bx bx-check'></i>
                                    PDF & CSV analytics reports
                                </li>
                                <li>
                                    <i class='bx bx-check'></i>
                                    Spintax-powered post recycling
                                </li>
                            </ul>
                        </div>
                        <button class="btn btn-plans">
                            <a href="pages/users/signup.html">
                                Start a free 14-days trial
                            </a>
                        </button>
                    </div>
                    <!-- For Medium Screen -->
                    <div class="col-12 d-block d-lg-none">
                        <div class="plan-smd-item">
                            <div class="plan-smd-link">
                                <h2>Common Features</h2>
                                <i class='bx bx-plus'></i>
                            </div>
                            <div class="plan-smd-content">
                                <div class="plan-list-item">
                                    <p>
                                        Twitter / X integration
                                    </p>
                                    <i class='bx bx-check'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Two-Factor authentication (2FA)
                                    </p>
                                    <i class='bx bx-check'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        OpenAI integration
                                    </p>
                                    <i class='bx bx-check'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Customize posts
                                    </p>
                                    <i class='bx bx-check'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Preview posts
                                    </p>
                                    <i class='bx bx-check'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Automatically schedule posts
                                    </p>
                                    <i class='bx bx-check'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Calendar view
                                    </p>
                                    <i class='bx bx-check'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Instagram feed preview
                                    </p>
                                    <i class='bx bx-check'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Link in Bio
                                    </p>
                                    <i class='bx bx-check'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Mobile app
                                    </p>
                                    <i class='bx bx-check'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Browser extension
                                    </p>
                                    <i class='bx bx-check'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Design with Canva
                                    </p>
                                    <i class='bx bx-check'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Built-in photo editor
                                    </p>
                                    <i class='bx bx-check'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Import from Drive, Unsplash
                                    </p>
                                    <i class='bx bx-check'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Shortcodes
                                    </p>
                                    <i class='bx bx-check'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Link shortening
                                    </p>
                                    <i class='bx bx-check'></i>
                                </div>
                            </div>
                        </div>
                        <div class="plan-smd-item">
                            <div class="plan-smd-link">
                                <h2>Paid Features</h2>
                                <i class='bx bx-plus'></i>
                            </div>
                            <div class="plan-smd-content">
                                <div class="plan-list-item">
                                    <p>
                                        Unlimited scheduling & drafts
                                    </p>
                                    <i class='bx bx-x'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Unlimited workspaces
                                    </p>
                                    <i class='bx bx-x'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Bulk scheduling
                                    </p>
                                    <i class='bx bx-x'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Media library
                                    </p>
                                    <i class='bx bx-x'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Link in Bio
                                    </p>
                                    <i class='bx bx-x'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Discover trending social media posts
                                    </p>
                                    <i class='bx bx-x'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Discover trending industry news
                                    </p>
                                    <i class='bx bx-x'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Automatic RSS posting
                                    </p>
                                    <i class='bx bx-x'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Watermark photos
                                    </p>
                                    <i class='bx bx-x'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Signatures
                                    </p>
                                    <i class='bx bx-x'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Schedule comments
                                    </p>
                                    <i class='bx bx-x'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Auto-share & auto-delete
                                    </p>
                                    <i class='bx bx-x'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Eternal post history
                                    </p>
                                    <i class='bx bx-x'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Unlimited AI prompts
                                    </p>
                                    <i class='bx bx-x'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        In-depth analytics insights
                                    </p>
                                    <i class='bx bx-x'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Best times to post
                                    </p>
                                    <i class='bx bx-x'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Hashtag analysis
                                    </p>
                                    <i class='bx bx-x'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Competitor analysis
                                    </p>
                                    <i class='bx bx-x'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        PDF & CSV analytics reports
                                    </p>
                                    <i class='bx bx-x'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Hashtag suggestions
                                    </p>
                                    <i class='bx bx-x'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Automatically recycle posts
                                    </p>
                                    <i class='bx bx-x'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Schedule recurring posts
                                    </p>
                                    <i class='bx bx-x'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Spintax support
                                    </p>
                                    <i class='bx bx-x'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Design videos
                                    </p>
                                    <i class='bx bx-x'></i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Watermark videos
                                    </p>
                                    <i class='bx bx-x'></i>
                                </div>
                            </div>
                        </div>
                        <div class="plan-smd-item">
                            <div class="plan-smd-link">
                                <h2>Daily Post Limits</h2>
                                <i class='bx bx-plus'></i>
                            </div>
                            <div class="plan-smd-content">
                                <div class="plan-list-item">
                                    <p>
                                        Facebook Posts & Reels
                                    </p>
                                    <i>12 / day / profile</i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Facebook Stories
                                    </p>
                                    <i>12</i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Instagram Posts & Reels
                                    </p>
                                    <i>15</i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Instagram Stories
                                    </p>
                                    <i>15</i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Threads App Posts
                                    </p>
                                    <i>150</i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        TikTok Videos
                                    </p>
                                    <i>15</i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Twitter / X Posts
                                    </p>
                                    <i>25</i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Mastodon Posts
                                    </p>
                                    <i>25</i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        LinkedIn Profile Posts
                                    </p>
                                    <i>10</i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        LinkedIn Page Posts
                                    </p>
                                    <i>12</i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Pinterest Pins
                                    </p>
                                    <i>12</i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Google Business Updates
                                    </p>
                                    <i>5</i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        YouTube Videos & Shorts
                                    </p>
                                    <i>5</i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        WordPress Articles
                                    </p>
                                    <i>12</i>
                                </div>
                                <div class="plan-list-item">
                                    <p>
                                        Telegram Messages
                                    </p>
                                    <i>5</i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="text-wrapper center">
                    <p>
                        Payments handled securely by <a href="#">PayPro Global</a> and <a href="#">Paddle</a>.
                        <br class="d-none d-md-block">
                        VAT not included. 14 days money back guarantee!
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Plans Supports -->
    <div id="plans" class="container-fluid bg-light-white py-5">
        <div class="container">
            <div class="col-12 justify-center text-center">
                <div class="text-wrapper center">
                    <h2>
                        All major social platforms supported
                    </h2>
                    <p>
                        We support all post types including YT Shorts, IG Reels & Shorts.
                        <br class="d-none d-lg-block">
                        Click to view the <a href="#">whole list</a> .
                    </p>
                </div>
                <div class="row">
                    <div class="col-12 col-md-6 col-lg-3">
                        <div class="support-list-container">
                            <i class='bx bxl-facebook-circle'></i>
                            <h3>Facebook</h3>
                            <a href="#">Profiles, Pages, Locations, Groups</a>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-3">
                        <div class="support-list-container">
                            <i class='bx bxl-instagram-alt'></i>
                            <h3>Instagram</h3>
                            <a href="#">Business & Creator</a>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-3">
                        <div class="support-list-container">
                            <i class='bx bxl-pinterest'></i>
                            <h3>Pinterest</h3>
                            <a href="#">Personal & Business</a>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-3">
                        <div class="support-list-container">
                            <i class='bx bxl-youtube'></i>
                            <h3>Youtube</h3>
                            <a href="#">Channels</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid d-none d-lg-block py-5 p-0">
        <div class="sticky-section py-5">
            <div class="sticky-row container-fluid">
                <div class="row container text-center mx-auto">
                    <div class="col-3 text-start">
                        <h1>
                            Included In All
                        </h1>
                    </div>
                    <div class="col-3">
                        <h1>
                            Free
                        </h1>
                    </div>
                    <div class="col-3">
                        <h1>
                            Professional
                        </h1>
                    </div>
                    <div class="col-3">
                        <h1>
                            Business
                        </h1>
                    </div>
                </div>
            </div>
            <table class="table table-striped container">
                <tbody class="table-content-hold">
                    <tr>
                        <td>Twitter / X Integration</td>
                        <td><i class='bx bx-check'></i></td>
                        <td><i class='bx bx-check'></i></td>
                        <td><i class='bx bx-check'></i></td>
                    </tr>
                    <tr>
                        <td>Two-Factor Authentication (2FA)</td>
                        <td><i class='bx bx-check'></i></td>
                        <td><i class='bx bx-check'></i></td>
                        <td><i class='bx bx-check'></i></td>
                    </tr>
                    <tr>
                        <td>OpenAI Integration</td>
                        <td><i class='bx bx-check'></i></td>
                        <td><i class='bx bx-check'></i></td>
                        <td><i class='bx bx-check'></i></td>
                    </tr>
                    <tr>
                        <td>Customize Posts</td>
                        <td><i class='bx bx-check'></i></td>
                        <td><i class='bx bx-check'></i></td>
                        <td><i class='bx bx-check'></i></td>
                    </tr>
                    <tr>
                        <td>Preview Posts</td>
                        <td><i class='bx bx-check'></i></td>
                        <td><i class='bx bx-check'></i></td>
                        <td><i class='bx bx-check'></i></td>
                    </tr>
                    <tr>
                        <td>Automatically Schedule Posts</td>
                        <td><i class='bx bx-check'></i></td>
                        <td><i class='bx bx-check'></i></td>
                        <td><i class='bx bx-check'></i></td>
                    </tr>
                    <tr>
                        <td>Calendar View</td>
                        <td><i class='bx bx-check'></i></td>
                        <td><i class='bx bx-check'></i></td>
                        <td><i class='bx bx-check'></i></td>
                    </tr>
                    <tr>
                        <td>Instagram Feed Preview</td>
                        <td><i class='bx bx-check'></i></td>
                        <td><i class='bx bx-check'></i></td>
                        <td><i class='bx bx-check'></i></td>
                    </tr>
                    <tr>
                        <td>Link in Bio</td>
                        <td><i class='bx bx-check'></i></td>
                        <td><i class='bx bx-check'></i></td>
                        <td><i class='bx bx-check'></i></td>
                    </tr>
                    <tr>
                        <td>Mobile App</td>
                        <td><i class='bx bx-check'></i></td>
                        <td><i class='bx bx-check'></i></td>
                        <td><i class='bx bx-check'></i></td>
                    </tr>
                    <tr>
                        <td>Browser Extension</td>
                        <td><i class='bx bx-check'></i></td>
                        <td><i class='bx bx-check'></i></td>
                        <td><i class='bx bx-check'></i></td>
                    </tr>
                    <tr>
                        <td>Design with Canva</td>
                        <td><i class='bx bx-check'></i></td>
                        <td><i class='bx bx-check'></i></td>
                        <td><i class='bx bx-check'></i></td>
                    </tr>
                    <tr>
                        <td>Built-in Photo Editor</td>
                        <td><i class='bx bx-check'></i></td>
                        <td><i class='bx bx-check'></i></td>
                        <td><i class='bx bx-check'></i></td>
                    </tr>
                    <tr>
                        <td>Import from Drive, Unsplash, etc</td>
                        <td><i class='bx bx-check'></i></td>
                        <td><i class='bx bx-check'></i></td>
                        <td><i class='bx bx-check'></i></td>
                    </tr>
                    <tr>
                        <td>Shortcodes</td>
                        <td><i class='bx bx-check'></i></td>
                        <td><i class='bx bx-check'></i></td>
                        <td><i class='bx bx-check'></i></td>
                    </tr>
                    <tr>
                        <td>Link Shortening</td>
                        <td><i class='bx bx-check'></i></td>
                        <td><i class='bx bx-check'></i></td>
                        <td><i class='bx bx-check'></i></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="sticky-section py-5">
            <div class="sticky-row container-fluid">
                <div class="row container text-center mx-auto">
                    <div class="col-3 text-start">
                        <h1>
                            Paid Features
                        </h1>
                    </div>
                    <div class="col-3">
                        <h1>
                            Free
                        </h1>
                    </div>
                    <div class="col-3">
                        <h1>
                            Professional
                        </h1>
                    </div>
                    <div class="col-3">
                        <h1>
                            Business
                        </h1>
                    </div>
                </div>
            </div>
            <table class="table table-striped container">
                <tbody class="table-content-hold">
                    <tr>
                        <td>Unlimited scheduling & drafts</td>
                        <td><i class='bx bx-x'></i></td>
                        <td><i class='bx bx-check'></i></td>
                        <td><i class='bx bx-check'></i></td>
                    </tr>
                    <tr>
                        <td>Unlimited workspaces</td>
                        <td><i class='bx bx-x'></i></td>
                        <td><i class='bx bx-check'></i></td>
                        <td><i class='bx bx-check'></i></td>
                    </tr>
                    <tr>
                        <td>Bulk scheduling</td>
                        <td><i class='bx bx-x'></i></td>
                        <td><i class='bx bx-check'></i></td>
                        <td><i class='bx bx-check'></i></td>
                    </tr>
                    <tr>
                        <td>Media library</td>
                        <td><i class='bx bx-x'></i></td>
                        <td><i class='bx bx-check'></i></td>
                        <td><i class='bx bx-check'></i></td>
                    </tr>
                    <tr>
                        <td>Link in Bio</td>
                        <td><i class='bx bx-x'></i></td>
                        <td><i class='bx bx-check'></i></td>
                        <td><i class='bx bx-check'></i></td>
                    </tr>
                    <tr>
                        <td>Discover trending social media posts</td>
                        <td><i class='bx bx-x'></i></td>
                        <td><i class='bx bx-check'></i></td>
                        <td><i class='bx bx-check'></i></td>
                    </tr>
                    <tr>
                        <td>Discover trending industry news</td>
                        <td><i class='bx bx-x'></i></td>
                        <td><i class='bx bx-check'></i></td>
                        <td><i class='bx bx-check'></i></td>
                    </tr>
                    <tr>
                        <td>Automatic RSS posting</td>
                        <td><i class='bx bx-x'></i></td>
                        <td><i class='bx bx-check'></i></td>
                        <td><i class='bx bx-check'></i></td>
                    </tr>
                    <tr>
                        <td>Watermark photos</td>
                        <td><i class='bx bx-x'></i></td>
                        <td><i class='bx bx-check'></i></td>
                        <td><i class='bx bx-check'></i></td>
                    </tr>
                    <tr>
                        <td>Signatures</td>
                        <td><i class='bx bx-x'></i></td>
                        <td><i class='bx bx-check'></i></td>
                        <td><i class='bx bx-check'></i></td>
                    </tr>
                    <tr>
                        <td>Schedule comments/ Twitter threads</td>
                        <td><i class='bx bx-x'></i></td>
                        <td><i class='bx bx-check'></i></td>
                        <td><i class='bx bx-check'></i></td>
                    </tr>
                    <tr>
                        <td>Auto-share & auto-delete</td>
                        <td><i class='bx bx-x'></i></td>
                        <td><i class='bx bx-check'></i></td>
                        <td><i class='bx bx-check'></i></td>
                    </tr>
                    <tr>
                        <td>Eternal post history</td>
                        <td><i class='bx bx-x'></i></td>
                        <td><i class='bx bx-check'></i></td>
                        <td><i class='bx bx-check'></i></td>
                    </tr>
                    <tr>
                        <td>Unlimited AI prompts</td>
                        <td><i class='bx bx-x'></i></td>
                        <td><i class='bx bx-x'></i></td>
                        <td><i class='bx bx-check'></i></td>
                    </tr>
                    <tr>
                        <td>In-depth analytics insights</td>
                        <td><i class='bx bx-x'></i></td>
                        <td><i class='bx bx-x'></i></td>
                        <td><i class='bx bx-check'></i></td>
                    </tr>
                    <tr>
                        <td>Best times to post</td>
                        <td><i class='bx bx-x'></i></td>
                        <td><i class='bx bx-x'></i></td>
                        <td><i class='bx bx-check'></i></td>
                    </tr>
                    <tr>
                        <td>Hashtag analysis</td>
                        <td><i class='bx bx-x'></i></td>
                        <td><i class='bx bx-x'></i></td>
                        <td><i class='bx bx-check'></i></td>
                    </tr>
                    <tr>
                        <td>Competitor analysis</td>
                        <td><i class='bx bx-x'></i></td>
                        <td><i class='bx bx-x'></i></td>
                        <td><i class='bx bx-check'></i></td>
                    </tr>
                    <tr>
                        <td>PDF & CSV analytics reports</td>
                        <td><i class='bx bx-x'></i></td>
                        <td><i class='bx bx-x'></i></td>
                        <td><i class='bx bx-check'></i></td>
                    </tr>
                    <tr>
                        <td>Hashtag suggestions</td>
                        <td><i class='bx bx-x'></i></td>
                        <td><i class='bx bx-x'></i></td>
                        <td><i class='bx bx-check'></i></td>
                    </tr>
                    <tr>
                        <td>Automatically recycle posts</td>
                        <td><i class='bx bx-x'></i></td>
                        <td><i class='bx bx-x'></i></td>
                        <td><i class='bx bx-check'></i></td>
                    </tr>
                    <tr>
                        <td>Schedule recurring posts</td>
                        <td><i class='bx bx-x'></i></td>
                        <td><i class='bx bx-x'></i></td>
                        <td><i class='bx bx-check'></i></td>
                    </tr>
                    <tr>
                        <td>Spintax support</td>
                        <td><i class='bx bx-x'></i></td>
                        <td><i class='bx bx-x'></i></td>
                        <td><i class='bx bx-check'></i></td>
                    </tr>
                    <tr>
                        <td>Design videos</td>
                        <td><i class='bx bx-x'></i></td>
                        <td><i class='bx bx-x'></i></td>
                        <td><i class='bx bx-check'></i></td>
                    </tr>
                    <tr>
                        <td>Watermark videos</td>
                        <td><i class='bx bx-x'></i></td>
                        <td><i class='bx bx-x'></i></td>
                        <td><i class='bx bx-check'></i></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="sticky-section py-5">
            <div class="sticky-row container-fluid">
                <div class="row container text-center mx-auto">
                    <div class="col-3 text-start">
                        <h1>
                            Daily Posts Limits
                        </h1>
                    </div>
                    <div class="col-3">
                        <h1>
                            Free
                        </h1>
                    </div>
                    <div class="col-3">
                        <h1>
                            Professional
                        </h1>
                    </div>
                    <div class="col-3">
                        <h1>
                            Business
                        </h1>
                    </div>
                </div>
            </div>
            <table class="table table-striped container">
                <tbody class="table-content-hold">
                    <tr>
                        <td>Facebook Posts & Reels</td>
                        <td>12 / day / profile</td>
                        <td>24 / day / profile</td>
                        <td>36 / day / profile</td>
                    </tr>
                    <tr>
                        <td>Facebook Stories</td>
                        <td>12</td>
                        <td>20</td>
                        <td>25</td>
                    </tr>
                    <tr>
                        <td>Instagram Posts & Reels</td>
                        <td>15</td>
                        <td>20</td>
                        <td>25</td>
                    </tr>
                    <tr>
                        <td>Instagram Stories</td>
                        <td>15</td>
                        <td>200</td>
                        <td>250</td>
                    </tr>
                    <tr>
                        <td>Threads App Posts</td>
                        <td>150</td>
                        <td>20</td>
                        <td>25</td>
                    </tr>
                    <tr>
                        <td>TikTok Videos</td>
                        <td>15</td>
                        <td>50</td>
                        <td>100</td>
                    </tr>
                    <tr>
                        <td>Twitter / X Posts</td>
                        <td>25</td>
                        <td>50</td>
                        <td>100</td>
                    </tr>
                    <tr>
                        <td>Mastodon Posts</td>
                        <td>25</td>
                        <td>12</td>
                        <td>14</td>
                    </tr>
                    <tr>
                        <td>LinkedIn Profile Posts</td>
                        <td>10</td>
                        <td>18</td>
                        <td>24</td>
                    </tr>
                    <tr>
                        <td>LinkedIn Page Posts</td>
                        <td>12</td>
                        <td>24</td>
                        <td>36</td>
                    </tr>
                    <tr>
                        <td>Pinterest Pins</td>
                        <td>12</td>
                        <td>10</td>
                        <td>15</td>
                    </tr>
                    <tr>
                        <td>Google Business Updates</td>
                        <td>5</td>
                        <td>10</td>
                        <td>15</td>
                    </tr>
                    <tr>
                        <td>YouTube Videos & Shorts</td>
                        <td>5</td>
                        <td>18</td>
                        <td>24</td>
                    </tr>
                    <tr>
                        <td>WordPress Articles</td>
                        <td>12</td>
                        <td>18</td>
                        <td>15</td>
                    </tr>
                    <tr>
                        <td>Telegram Messages</td>
                        <td>5</td>
                        <td>10</td>
                        <td>15</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Testimonials -->
    <div class="container my-48 text-center justify-content-center">
        <div class="row justify-center text-center">
            <div class="col-12 p-0 m-0">
                <div class="text-wrapper center">
                    <h2>
                        Trusted by Agencies and Brands Worldwide
                    </h2>
                    <p>
                        Adublishers is part of daily lives of thousands of social media marketers
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
            <button class="btn btn-colored col-12 col-md-7 col-lg-3 mx-auto">
                <a href="pages/users/signup.html">Join our happy customers</a>
            </button>
        </div>
    </div>
@endsection
