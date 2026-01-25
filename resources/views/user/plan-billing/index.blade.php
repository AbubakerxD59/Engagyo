@extends('user.layout.main')
@section('title', 'Plan & Billing')
@section('page_content')
    <div class="page-content">
        <div class="content-header clearfix"></div>
        <section class="content">
            <div class="container-fluid">
                
                {{-- Page Header --}}
                <div class="row mb-4">
                    <div class="col-12">
                        <h1 class="m-0 text-dark" style="font-size: 24px; font-weight: 700;">Plan & Billing</h1>
                        <p class="text-muted">Manage your subscription and view usage limits.</p>
                    </div>
                </div>

                <div class="card card-primary card-outline card-outline-tabs shadow-none border-0 bg-transparent">
                    <div class="card-header p-0 border-bottom-0">
                        <ul class="nav nav-tabs" id="planBillingTabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="overview-tab" data-toggle="pill" href="#overview" role="tab"
                                    aria-controls="overview" aria-selected="true">
                                    <i class="fas fa-chart-pie mr-2"></i>My Plan & Usage
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="upgrade-tab" data-toggle="pill" href="#upgrade" role="tab"
                                    aria-controls="upgrade" aria-selected="false">
                                    <i class="fas fa-rocket mr-2"></i>Available Plans
                                </a>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body p-0 mt-3">
                        <div class="tab-content" id="planBillingTabsContent">
                            
                            {{-- Overview Tab --}}
                            <div class="tab-pane fade show active" id="overview" role="tabpanel" aria-labelledby="overview-tab">
                                <div class="row">
                                    {{-- Current Plan Details --}}
                                    <div class="col-md-4">
                                        <div class="card shadow-sm h-100">
                                            <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                                                <h5 class="card-title mb-0 font-weight-bold text-primary">
                                                    <i class="fas fa-crown mr-2"></i>Current Subscription
                                                </h5>
                                            </div>
                                            <div class="card-body">
                                                @if ($package)
                                                    <div class="text-center py-4">
                                                        <div class="mb-3">
                                                            @if($package->icon)
                                                                <img src="{{ $package->icon }}" alt="{{ $package->name }}" class="img-fluid" style="max-height: 80px;">
                                                            @else
                                                                <div class="d-inline-flex align-items-center justify-content-center bg-light rounded-circle" style="width: 80px; height: 80px;">
                                                                    <i class="fas fa-box-open fa-3x text-primary"></i>
                                                                </div>
                                                            @endif
                                                        </div>
                                                        <h3 class="font-weight-bold mb-1">{{ $package->name }}</h3>
                                                        <div class="mb-3">
                                                            <span class="badge badge-pill badge-{{ $packageStatus === 'Active' ? 'success' : ($packageStatus === 'Expired' ? 'danger' : ($packageStatus === 'Lifetime' ? 'info' : 'secondary')) }} px-3 py-2">
                                                                {{ $packageStatus }}
                                                            </span>
                                                        </div>
                                                        
                                                        <div class="border-top pt-3 mt-4 text-left">
                                                            @if ($expiryDate && $packageStatus !== 'Lifetime')
                                                                <div class="d-flex justify-content-between mb-2">
                                                                    <span class="text-muted"><i class="far fa-calendar-alt mr-2"></i>Expires On:</span>
                                                                    <span class="font-weight-bold">{{ $expiryDate->format('M j, Y') }}</span>
                                                                </div>
                                                                <div class="d-flex justify-content-between">
                                                                    <span class="text-muted"><i class="far fa-clock mr-2"></i>Days Left:</span>
                                                                    <span class="font-weight-bold {{ $expiryDate->diffInDays(now()) < 5 ? 'text-danger' : 'text-success' }}">
                                                                        {{ max(0, ceil(now()->floatDiffInDays($expiryDate, false))) }} Days
                                                                    </span>
                                                                </div>
                                                            @elseif ($packageStatus === 'Lifetime')
                                                                <div class="text-center text-success font-weight-bold">
                                                                    <i class="fas fa-infinity mr-1"></i> Lifetime Access
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @else
                                                    <div class="text-center py-5">
                                                        <div class="mb-3 text-muted">
                                                            <i class="fas fa-box-open fa-4x opacity-50"></i>
                                                        </div>
                                                        <h5 class="text-muted">No Active Plan</h5>
                                                        <p class="small text-muted mb-4">You are currently on the free tier or have no active subscription.</p>
                                                        <button class="btn btn-primary btn-sm" onclick="$('#upgrade-tab').tab('show')">
                                                            View Plans
                                                        </button>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Feature Usage --}}
                                    <div class="col-md-8">
                                        <div class="card shadow-sm h-100">
                                            <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                                                <h5 class="card-title mb-0 font-weight-bold text-dark">
                                                    <i class="fas fa-chart-bar mr-2 text-primary"></i>Resource Usage
                                                </h5>
                                            </div>
                                            <div class="card-body">
                                                @if ($package && count($featuresWithUsage) > 0)
                                                    <div class="row">
                                                        @foreach ($featuresWithUsage as $feature)
                                                            <div class="col-md-6 mb-4">
                                                                <div class="p-3 rounded bg-light h-100 border-left-primary position-relative overflow-hidden feature-card">
                                                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                                                        <h6 class="font-weight-bold mb-0 text-dark">{{ $feature['name'] }}</h6>
                                                                        @if ($feature['is_unlimited'])
                                                                            <span class="badge badge-success badge-pill"><i class="fas fa-infinity"></i></span>
                                                                        @endif
                                                                    </div>
                                                                    
                                                                    @if (!$feature['is_unlimited'] && $feature['limit'] !== null)
                                                                        <div class="d-flex justify-content-between small text-muted mb-1">
                                                                            <span>{{ number_format($feature['usage']) }} used</span>
                                                                            <span>{{ number_format($feature['limit']) }} limit</span>
                                                                        </div>
                                                                        <div class="progress" style="height: 6px;">
                                                                            @php
                                                                                $usagePercentage = $feature['limit'] > 0 ? min(100, round(($feature['usage'] / $feature['limit']) * 100, 2)) : 0;
                                                                                $progressClass = $usagePercentage >= 100 ? 'bg-danger' : ($usagePercentage >= 75 ? 'bg-warning' : 'bg-primary');
                                                                            @endphp
                                                                            <div class="progress-bar {{ $progressClass }}" role="progressbar" 
                                                                                style="width: {{ $usagePercentage }}%" 
                                                                                aria-valuenow="{{ $usagePercentage }}" 
                                                                                aria-valuemin="0" 
                                                                                aria-valuemax="100">
                                                                            </div>
                                                                        </div>
                                                                        @if($feature['remaining'] !== null && $feature['remaining'] <= ($feature['limit'] * 0.1))
                                                                            <small class="text-danger mt-1 d-block">
                                                                                <i class="fas fa-exclamation-circle mr-1"></i>Running low!
                                                                            </small>
                                                                        @endif
                                                                    @else
                                                                       <div class="mt-2 text-muted small">
                                                                           <i class="fas fa-check-circle text-success mr-1"></i> Unlimited access
                                                                       </div>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @else
                                                    <div class="text-center py-5">
                                                        <p class="text-muted">No usage data available.</p>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Upgrade Tab --}}
                            <div class="tab-pane fade" id="upgrade" role="tabpanel" aria-labelledby="upgrade-tab">
                                <div class="row justify-content-center">
                                    <div class="col-12 text-center mb-5">
                                        <h2 class="font-weight-bold text-dark">Choose the Perfect Plan for You</h2>
                                        <p class="text-muted" style="font-size: 1.1rem;">Unlock more features and power up your workflow</p>
                                    </div>
                                </div>
                                <div class="row justify-content-center">
                                    @foreach($packages as $pkg)
                                        @php
                                            $isCurrentInfo = $package && $pkg->id == $package->id;
                                            $isLifetime = $pkg->is_lifetime;
                                        @endphp
                                        <div class="col-md-6 col-lg-4 mb-4">
                                            <div class="card h-100 pricing-card border-0 shadow-sm {{ $isCurrentInfo ? 'current-plan-card ring-primary' : '' }}">
                                                @if($isCurrentInfo)
                                                    <div class="current-plan-badge">Current Plan</div>
                                                @endif
                                                <div class="card-body p-4 d-flex flex-column">
                                                    <div class="text-center mb-4">
                                                        @if($pkg->icon)
                                                            <img src="{{ $pkg->icon }}" alt="{{ $pkg->name }}" class="mb-3" style="height: 64px; object-fit: contain;">
                                                        @else
                                                            <div class="d-inline-flex align-items-center justify-content-center bg-light rounded-circle mb-3" style="width: 64px; height: 64px;">
                                                                <i class="fas fa-star fa-2x text-warning"></i>
                                                            </div>
                                                        @endif
                                                        <h4 class="font-weight-bold mb-1">{{ $pkg->name }}</h4>
                                                        <p class="text-muted small mb-0">{{ $pkg->description }}</p>
                                                    </div>

                                                    <div class="pricing-price text-center mb-4">
                                                        <span class="currency h3 font-weight-bold align-top">$</span>
                                                        <span class="amount display-4 font-weight-bold text-dark">{{ number_format($pkg->price / 100, 2) }}</span>
                                                        @if(!$isLifetime)
                                                            <span class="period text-muted">/ {{ $pkg->duration }} {{ Str::plural($pkg->date_type, $pkg->duration) }}</span>
                                                        @else
                                                            <span class="period text-muted">/ Lifetime</span>
                                                        @endif
                                                    </div>

                                                    <div class="plan-features mb-4 flex-grow-1">
                                                        <ul class="list-unstyled">
                                                            @foreach($pkg->features as $feature)
                                                                <li class="mb-3 d-flex align-items-start {{ !$feature->pivot->is_enabled ? 'text-muted' : '' }}">
                                                                    @if($feature->pivot->is_enabled)
                                                                        <i class="fas fa-check-circle text-success mt-1 mr-2"></i>
                                                                    @else
                                                                        <i class="fas fa-times-circle text-muted mt-1 mr-2"></i>
                                                                    @endif
                                                                    <span style="font-size: 0.95rem;">
                                                                        {{ $feature->name }}
                                                                        @if($feature->pivot->is_unlimited)
                                                                            <span class="badge badge-light border ml-1">Unlimited</span>
                                                                        @elseif($feature->pivot->limit_value > 0)
                                                                            <span class="badge badge-light border ml-1">{{ $feature->pivot->limit_value }}</span>
                                                                        @endif
                                                                    </span>
                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                    </div>

                                                    <div class="text-center mt-auto">
                                                        @if($isCurrentInfo)
                                                            <button class="btn btn-outline-primary btn-block btn-lg" disabled>
                                                                <i class="fas fa-check mr-2"></i> Active Plan
                                                            </button>
                                                        @else
                                                            <a href="{{ route('payment.checkout', $pkg->id) }}" class="btn btn-primary btn-block btn-lg shadow-sm hover-lift">
                                                                Choose Plan
                                                            </a>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

@push('styles')
    <style>
        .card {
            border-radius: 12px;
        }

        .border-left-primary {
            border-left: 4px solid #007bff;
        }

        .nav-tabs .nav-link {
            border: none;
            border-bottom: 2px solid transparent;
            color: #6c757d;
            font-weight: 600;
            padding: 12px 20px;
            font-size: 1rem;
        }

        .nav-tabs .nav-link.active {
            color: #007bff;
            border-bottom: 2px solid #007bff;
            background: transparent;
        }

        .nav-tabs .nav-link:hover {
            color: #0056b3;
            border-color: transparent;
        }

        /* Pricing Card Styles */
        .pricing-card {
            transition: all 0.3s ease;
        }

        .pricing-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 1rem 3rem rgba(0,0,0,.1) !important;
        }

        .current-plan-card {
            border: 2px solid #007bff !important;
            position: relative;
        }
        
        .current-plan-badge {
            position: absolute;
            top: -12px;
            left: 50%;
            transform: translateX(-50%);
            background: #007bff;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            z-index: 10;
        }

        .hover-lift {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .hover-lift:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,123,255,0.3);
        }

        .feature-card {
            transition: background-color 0.2s;
        }
        
        .feature-card:hover {
            background-color: #f1f3f5 !important;
        }

        .display-4 {
            font-size: 2.5rem;
        }
    </style>
@endpush

@push('scripts')
    <script>
        $(document).ready(function() {
            $('[data-toggle="tooltip"]').tooltip();
            
            // Auto switch to upgrade tab if URL hash is #upgrade
            if(window.location.hash === '#upgrade') {
                $('#upgrade-tab').tab('show');
            }
            
            // Update URL hash on tab change
            $('a[data-toggle="pill"]').on('shown.bs.tab', function (e) {
                history.pushState(null, null, e.target.hash);
            });
        });
    </script>
@endpush
