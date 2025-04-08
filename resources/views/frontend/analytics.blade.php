@extends('frontend.layout.features')
@section('body')
    <!-- Analytics Banner -->
    <div id="analytics" class="container-fluid pt-5">
        <div class="container pt-5 my-48">
            <div class="col-12 justify-content-center text-center">
                <div class="text-wrapper center">
                    <h2>
                        Powerful Social Media Analytics
                    </h2>
                    <p>
                        Go beyond likes and follows. {{ env('APP_NAME', 'Engagyo') }} Analytics equips you with in-depth
                        performance insights to
                        fuel smarter
                        <br class="d-none d-lg-flex">
                        social decisions. Track progress, identify trends, and dominate your social space with confidence.
                    </p>
                    <button class="btn btn-colored">Unlock Data Driven Success</button>
                </div>
            </div>
            <div class="col-12">
                <div class="container img-container-center">
                    <img src="{{ asset('assets/frontend/images/Analytics-Illustration.PNG') }}" class="h-100 w-100">
                </div>
            </div>
        </div>
    </div>

    <!-- Working -->
    <div class="container py-24 my-48 text-center justify-content-center">
        <div class="box-shadowed">
            <div class="row">
                <div class="col-12">
                    <div class="text-wrapper center">
                        <h2>
                            See What's Working.
                            <br class="d-none d-lg-block">
                            Fix What's Not. Win at Social.
                        </h2>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="review-container">
                        <i class='bx bxs-circle'></i>
                        <h3>Identify What's Working</h3>
                        <p>
                            See top content, times, and campaigns that deliver the most consistent impact.
                        </p>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="review-container">
                        <i class='bx bxs-circle'></i>
                        <h3>Track Progress Over Time</h3>
                        <p>
                            Measure growth, celebrate wins, witness your social media journey unfold.
                        </p>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="review-container">
                        <i class='bx bxs-circle'></i>
                        <h3>Make Informed Decisions</h3>
                        <p>
                            Create and optimize social media strategies, backed by reliable data.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Analytics Features -->
    <div class="container-fluid bg-light-white">
        <!-- First -->
        <div class="container py-24">
            <div class="row g-4 align-items-center mx-auto px-auto">
                <div class="col-12 col-lg-6 pxr-24">
                    <div class="text-wrapper featured-text">
                        <h2>
                            All Social Media Insights,
                            <br class="d-none d-lg-block">
                            Inside One Dashboard.
                        </h2>
                        <p>
                            Find what matters in a heartbeat. Thanks to the compact dashboard, you can find information
                            gathered from all social media networks, into clear actionable insights needed to develop and
                            ace your social media game.
                        </p>
                        <p>
                            This isn't just about vanity metrics or shiny dashboards. It's about empowering you with the
                            knowledge to shape your social media strategy. It's about transforming data into action,
                            insights into growth, and ultimately, success.
                        </p>
                    </div>
                </div>
                <div class="col-12 col-lg-6 pxl-24">
                    <div class="featured-images">
                        <img class="img-fluid w-100 h-100" alt="Featured Image about Calender"
                            src="{{ asset('assets/frontend/images/Features/Analytics-Insights.jpg') }}">
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
                            Focus On Social Media
                            <br class="d-none d-md-block">
                            Metrics That Matter.
                        </h2>
                        <p>
                            Personalize your post insights with metrics you want to see first. Customize the data columns
                            and sort all your posts based on their performance.
                        </p>
                        <p>
                            Filter your data by social accounts, time period, or Workspace members to know exactly what is
                            working, and measure performance.
                        </p>
                        <p>
                            And if you want to dive deeper, click on the post to get a full view and learn more about its
                            individual metrics, activity, and progress.
                        </p>
                    </div>
                </div>
                <div class="col-12 col-lg-6 pxr-24">
                    <div class="featured-images">
                        <img class="img-fluid w-100 h-100" alt="Link in Bio illustration Image"
                            src="{{ asset('assets/frontend/images/Features/Analytics-Mediametrics.webp') }}">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- SuperPowers -->
    <div class="container-fluid">
        <div class="container py-24 my-5">
            <div class="col-12">
                <div class="text-wrapper center">
                    <h2>
                        Find the Best Times to Post on Social Media!
                    </h2>
                    <p class="description">
                        Tired of guessing what and when to post? {{ env('APP_NAME', 'Engagyo') }}'s internal algorithm and
                        AI predict peak engagement
                        times for
                        each social media platform, based on your unique activity and audience. Maximize reach, engagement,
                        and ROI
                        with a strategy based on simple, smart scheduling suggestions.
                    </p>
                    <div class="btn btn-colored">Explore Analytics</div>
                </div>
            </div>
            <div class="col-12 mt-5">
                <div class="container img-container-center">
                    <img src="{{ asset('assets/frontend/images/Time.png') }}" class="h-100 w-100">
                </div>
            </div>
        </div>
    </div>

    <!-- Analytics Features -->
    <div class="container-fluid bg-light-white">
        <!-- Third -->
        <div class="container py-24">
            <div class="row g-4 align-items-center mx-auto px-auto">
                <div class="col-12 col-lg-6 pxr-24">
                    <div class="text-wrapper featured-text">
                        <h2>
                            Exclusive Key Metrics That
                            <br class="d-none d-lg-block">
                            Measure Your Growth
                        </h2>
                        <p>
                            Stop just counting likes - gain actionable insight with our exclusive metrics:
                        </p>
                        <ul class="feature-lister">
                            <li>
                                <i class='bx bx-check'></i>
                                <b>Reach Rate:</b>What social media networks don't want you to know: Understand your post
                                reach - relative to your account's followers.
                            </li>
                            <li>
                                <i class='bx bx-check'></i>
                                <b>Engagement Rate:</b>See if your content truly resonates with your audience - Leverage
                                engaging content & refine your strategy.
                            </li>
                            <li>
                                <i class='bx bx-check'></i>
                                <b>Click-Through Rate:</b>Track link clicks for posts or Link in Bio, understand what drives
                                results & maximize ROI.
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="col-12 col-lg-6 pxl-24">
                    <div class="featured-images">
                        <img class="img-fluid w-100 h-100" alt="About Workspaces in {{ env('APP_NAME', 'Engagyo') }}"
                            src="{{ asset('assets/frontend/images/Features/Analytics-Keymetrics.jpg') }}">
                    </div>
                </div>
            </div>
        </div>
        <!-- Fourth -->
        <div class="container py-24">
            <div class="row reverse g-4 align-items-center mx-auto px-auto">
                <div class="col-12 col-lg-6 pxl-24">
                    <div class="text-wrapper featured-text">
                        <h2>
                            Understand Your Audience
                            with Demographic Insights.
                        </h2>
                        <p>
                            Understand your social media audience like never before! Gain valuable insights into who engages
                            with your content, including:
                        </p>
                        <ul class="feature-lister">
                            <li>
                                <i class='bx bx-check'></i>
                                <b>Location </b>: Discover the top countries and cities where your audience thrives.
                            </li>
                            <li>
                                <i class='bx bx-check'></i>
                                <b>Demographics </b>: Uncover key characteristics like age and gender distribution.
                            </li>
                        </ul>
                        <p>
                            By understanding your audience demographics, you can craft targeted content that drives
                            engagement and results.
                        </p>
                    </div>
                </div>
                <div class="col-12 col-lg-6 pxr-24">
                    <div class="featured-images">
                        <img class="img-fluid w-100 h-100"
                            alt="Analytics illustration Image of {{ env('APP_NAME', 'Engagyo') }}"
                            src="{{ asset('assets/frontend/images/Features/Analytics-Demographicinsights.jpg') }}">
                    </div>
                </div>
            </div>
        </div>
        <!-- Fifth -->
        <div class="container py-24">
            <div class="row g-4 align-items-center mx-auto px-auto">
                <div class="col-12 col-lg-6 pxr-24">
                    <div class="text-wrapper featured-text">
                        <h2>
                            Understand Which Hashtags
                            Work Best.
                        </h2>
                        <p>
                            Gain in-depth insights of the hashtags you use. Hashtag Analysis provides a clear picture of how
                            your hashtags perform across platforms and posts, empowering you to optimize your strategy based
                            on real results.
                        </p>
                        <p>
                            Identify top hashtags: Use the Hashtag Score to measure your precise hashtag impact on the
                            engagement of your posts.
                        </p>
                    </div>
                </div>
                <div class="col-12 col-lg-6 pxl-24">
                    <div class="featured-images">
                        <img class="img-fluid w-100 h-100" alt="Featured Image about Calender"
                            src="{{ asset('assets/frontend/images/Features/Analytics-Hashtag.jpg') }}">
                    </div>
                </div>
            </div>
        </div>
        <!-- Sixth -->
        <div class="container py-24">
            <div class="row reverse g-4 align-items-center mx-auto px-auto">
                <div class="col-12 col-lg-6 pxl-24">
                    <div class="text-wrapper featured-text">
                        <h2>
                            Outsmart Your Competiton
                            <br class="d-none d-lg-block">
                            & Win The Race
                        </h2>
                        <p>
                            <b>Analyze key competitor metrics: </b> Track follower growth, engagement rates, and the content
                            types that resonate most with your competitor's audience.
                        </p>
                        <p>
                            <b>Discover top-performing posts:</b> See what content gets the most traction for your
                            competitors, and use it as inspiration for your own strategy.
                        </p>
                        <p>
                            <b>Optimize your posting schedule:</b> By analyzing when your competitors are most active and
                            generating engagement, {{ env('APP_NAME', 'Engagyo') }} can refine your posting schedule to
                            better reach your target
                            audience.
                        </p>
                    </div>
                </div>
                <div class="col-12 col-lg-6 pxr-24">
                    <div class="featured-images">
                        <img class="img-fluid w-100 h-100" alt="Featured Image about Calender"
                            src="{{ asset('assets/frontend/images/Features/Analtyics-Outsmart.jpg') }}">
                    </div>
                </div>
            </div>
        </div>
        <!-- Seventh -->
        <div class="container py-24">
            <div class="row g-4 align-items-center mx-auto px-auto">
                <div class="col-12 col-lg-6 pxr-24">
                    <div class="text-wrapper featured-text">
                        <h2>
                            Best of Both Worlds:
                            <br class="d-none d-lg-block">
                            PDF & CSV Analytics Reports!
                        </h2>
                        <p>
                            Don't be limited by format choices. Gain complete control over your social
                            media insights with both<b> PDF and CSV formats: </b>
                        </p>
                        <ul class="feature-lister">
                            <li>
                                <i class='bx bx-check'></i>
                                <b>Stunning PDF Reports</b>
                                : Generate presentation-ready reports packed with clear, digestible data visualizations.
                            </li>
                            <button class="btn-featured">View PDF Sample</button>
                            <li>
                                <i class='bx bx-check'></i>
                                <b>Powerful CSV Exports</b>: Access raw data in a fully editable format for advanced
                                analysis, custom calculations, and data manipulation.
                            </li>
                            <button class="btn-featured">Download CSV Sample</button>
                        </ul>
                    </div>
                </div>
                <div class="col-12 col-lg-6 pxl-24">
                    <div class="featured-images">
                        <img class="img-fluid w-100 h-100" alt="Featured Image about Calender"
                            src="{{ asset('assets/frontend/images/Features/Analytics-PDF&CSV.jpg') }}">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- SuperPowers -->
    <div class="container-fluid">
        <div class="container py-24 my-5 text-center justify-content-center">
            <div class="row">
                <div class="col-12">
                    <div class="text-wrapper center">
                        <h2>
                            {{ env('APP_NAME', 'Engagyo') }} Analytics on Your Smartphone
                        </h2>
                        <p class="description">
                            Stay in control even on the move! {{ env('APP_NAME', 'Engagyo') }} Analytics brings all desktop
                            insights into your pocket
                            through the
                            <br class="d-none d-lg-block">
                            {{ env('APP_NAME', 'Engagyo') }} App. Track performance, export reports, and manage data – just
                            like you would on your
                            computer.
                        </p>
                        <div class="btn btn-colored">Download App Now</div>
                    </div>
                </div>
                <div class="col-12 mt-5">
                    <div class="container img-container-center">
                        <img src="{{ asset('assets/frontend/images/Mobile Analytics.png') }}" class="h-100 w-100">
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
