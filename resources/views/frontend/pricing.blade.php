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
                @forelse($packages as $package)
                    <div class="col-12 col-md-6 col-xl-4">
                        <div class="plan-card monthly-plan {{ strtolower(str_replace(' ', '-', $package->name)) }}" style="height: 100%; display: flex; flex-direction: column;">
                            @if($package->icon)
                                <div class="text-center mb-3">
                                    <img src="{{ $package->icon }}" alt="{{ $package->name }}" style="max-width: 60px; height: auto;">
                        </div>
                            @endif
                        <div class="plan-header border-bottom">
                                <h2>{{ $package->name }}</h2>
                                @if($package->description)
                                    <span>{{ $package->description }}</span>
                                @endif
                                <h1>
                                    ${{ number_format($package->price / 100, 2) }}
                                    @if($package->date_type)
                                        <span style="font-size: 0.6em; font-weight: normal;">
                                            / {{ $package->duration }} {{ $package->date_type }}
                                    </span>
                                    @endif
                            </h1>
                        </div>
                            <div class="plan-content" style="flex: 1;">
                                <h3>Included:</h3>
                                <ul>
                                    @forelse($package->features as $feature)
                                        @if($feature->pivot->is_enabled)
                                <li>
                                    <i class='bx bx-check'></i>
                                                {{ $feature->name }}
                                                @if($feature->pivot->limit_value && !$feature->pivot->is_unlimited)
                                                    <span style="font-size: 0.85em; color: #666;">
                                                        ({{ $feature->pivot->limit_value }})
                            </span>
                                                @elseif($feature->pivot->is_unlimited)
                                                    <span style="font-size: 0.85em; color: #666;">
                                                        (Unlimited)
                                </span>
                                                @endif
                                </li>
                                        @endif
                                    @empty
                                        <li>No features available</li>
                                    @endforelse
                            </ul>
                        </div>
                            <button class="btn btn-plans mt-auto">
                                <a href="{{ route('frontend.showRegister') }}">
                                    @if($package->price == 0)
                                        Get Started - It's free
                                    @else
                                        Start a free {{ $package->trial_days ?? 7 }}-day trial
                                    @endif
                                </a>
                        </button>
                    </div>
                            </div>
                @empty
                    <div class="col-12 text-center">
                        <p>No packages available at the moment.</p>
                                </div>
                @endforelse
                                </div>
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
