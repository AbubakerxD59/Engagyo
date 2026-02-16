<!-- Footer -->
<div id="footer" class="container-fluid">
    <div class="container pt-5">
        <div class="row">
            <div class="footer-container col-12 pb-5">
                <div class="row text-center text-md-start mx-auto">
                    <div class="col-12 col-md-4 col-xl-2 my-4 m-md-0">
                        <div class="footer-high">
                            <h2>
                                Company
                            </h2>
                            <ul class="p-0">
                                <li>
                                    <a href="#">About Us</a>
                                </li>
                                <li>
                                    <a href="plans.html">Plans & Pricing</a>
                                </li>
                                <li>
                                    <a href="#">Give Feedback</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-12 col-md-4 col-xl-2 my-4 m-md-0">
                        <div class="footer-high">
                            <h2>
                                Features
                            </h2>
                            <ul class="p-0">
                                <li>
                                    <a href="pages/features/calender-view.html">Calender View</a>
                                </li>
                                <li>
                                    <a href="pages/features/analytics.html">Analytics</a>
                                </li>
                                <li>
                                    <a href="pages/features/curate-posts.html">Curate Post</a>
                                </li>
                                <li>
                                    <a href="pages/features/bulk-schedule.html">Bulk Schedule</a>
                                </li>
                                <li>
                                    <a href="pages/features/recycle.html">Recycle</a>
                                </li>
                                <li>
                                    <a href="pages/features/rss-feed.html">RSS Feeds</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-12 col-md-4 col-xl-2 my-4 m-md-0">
                        <div class="footer-high">
                            <h2>
                                Integrations
                            </h2>
                            <ul class="p-0">
                                <li>
                                    <a href="#">Youtube</a>
                                </li>
                                <li>
                                    <a href="#">Facebook</a>
                                </li>
                                <li>
                                    <a href="#">Instagram</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-12 col-md-4 col-xl-2 my-4 m-md-0">
                        <div class="footer-high">
                            <h2>
                                Resources
                            </h2>
                            <ul class="p-0">
                                <li>
                                    <a href="pages/blog/blog.html">Blog</a>
                                </li>
                                <li>
                                    <a href="contact.html">Book a Call</a>
                                </li>
                                <li>
                                    <a href="pages/blog/productupdates.html">Product Updates</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-12 col-md-4 col-xl-2 my-4 m-md-0">
                        <div class="footer-high">
                            <h2>
                                Compare
                            </h2>
                            <ul class="p-0">
                                <li>
                                    <a href="#">{{ env('APP_NAME', 'Engagyo') }} vs.Hootsuite</a>
                                </li>
                                <li>
                                    <a href="#">{{ env('APP_NAME', 'Engagyo') }} vs.Sprout Social</a>
                                </li>
                                <li>
                                    <a href="#">{{ env('APP_NAME', 'Engagyo') }} vs.Later</a>
                                </li>
                                <li>
                                    <a href="#">{{ env('APP_NAME', 'Engagyo') }} vs.Buffer</a>
                                </li>
                                <li>
                                    <a href="#">{{ env('APP_NAME', 'Engagyo') }} vs.SocialPilot</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-12 col-md-4 col-xl-2 my-4 m-md-0">
                        <div class="footer-high">
                            <h2>
                                Free Tools
                            </h2>
                            <ul class="p-0">
                                <li>
                                    <a href="pages/tools/urlshortner.html">URL Link Shortner</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="footer-lower d-md-flex text-center justify-content-between align-center border-top py-4">
                <p class="foot-content">
                    {{ '©. ' . date('Y') . ' ' . env('APP_NAME', 'Engagyo') }} Limited | All Rights Reserved.
                </p>
                <div class="foot-end-links">
                    <a href="{{ route('frontend.terms') }}">Terms of Services</a>
                    <a href="{{ route('frontend.privacy') }}">Privacy Policy</a>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- jQuery -->
<script src="{{ asset('assets/plugins/jquery/jquery.min.js') }}"></script>
<!-- jQuery UI 1.11.4 -->
<script src="{{ asset('assets/plugins/jquery-ui/jquery-ui.min.js') }}"></script>
{{-- Lenis --}}
<script src="https://unpkg.com/lenis@1.1.9/dist/lenis.min.js"></script>
<!-- Bootstrap 5 -->
<script src="https://stackpath.bootstrapcdn.com/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
<!-- GSAP -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.11.5/gsap.min.js"></script>
<!-- Scroll Trigger -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.11.5/ScrollTrigger.min.js"></script>
<!-- Popper -->
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
<!-- Toastr -->
<script src="{{ asset('assets/plugins/toastr/toastr.min.js') }}"></script>
<!-- SweetAlert2-->
<script src="{{ asset('assets/plugins/sweetalert2/sweetalert2.min.js') }}"></script>
<!-- ClipBoard -->
<script src="{{ asset('assets/plugins/clipboard/clipboard.min.js') }}"></script>
<!-- Custom JS -->
<script src="{{ asset('assets/frontend/js/main.js') }}"></script>
@stack('scripts')
