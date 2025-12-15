@extends('admin.layouts.secure')
@section('page_title', 'Dashboard')
@section('page_content')
    @php
        use App\Models\Package;
        use App\Models\Feature;
        use App\Models\UserPackage;
        use App\Models\Post;

        $totalPackages = Package::where('is_active', true)->count();
        $totalFeatures = Feature::where('is_active', true)->count();
        $activeUserPackages = UserPackage::where('is_active', true)->count();
        $totalScheduledPosts = Post::where('scheduled', 1)->count();
    @endphp

    <div class="page-content">
        <section class="content">
            <div class="container-fluid">
                <!-- Page Header -->
                <div class="row mb-4">
                    <div class="col-12">
                        <h1 class="h3 mb-0 text-dark font-weight-bold">DASHBOARD</h1>
                    </div>
                </div>

                <!-- Stats Cards Row -->
                <div class="row mb-4">
                    <!-- Users Card -->
                    <div class="col-lg-3 col-md-6 col-sm-6 mb-4">
                        <div class="stats-card card border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <h6 class="text-uppercase text-muted mb-2 font-weight-bold small">USERS</h6>
                                        <h2 class="mb-0 font-weight-bold text-dark">{{ get_total_users() }}</h2>
                                    </div>
                                    <div class="stats-icon bg-info">
                                        <i class="fas fa-users text-white"></i>
                                    </div>
                                </div>
                                <a href="{{ route('admin.users.index') }}" class="stats-link text-decoration-none">
                                    More info <i class="fas fa-arrow-circle-right ml-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Packages Card -->
                    <div class="col-lg-3 col-md-6 col-sm-6 mb-4">
                        <div class="stats-card card border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <h6 class="text-uppercase text-muted mb-2 font-weight-bold small">PACKAGES</h6>
                                        <h2 class="mb-0 font-weight-bold text-dark">{{ $totalPackages }}</h2>
                                    </div>
                                    <div class="stats-icon bg-success">
                                        <i class="fas fa-credit-card text-white"></i>
                                    </div>
                                </div>
                                <a href="{{ route('admin.packages.index') }}" class="stats-link text-decoration-none">
                                    More info <i class="fas fa-arrow-circle-right ml-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Features Card -->
                    <div class="col-lg-3 col-md-6 col-sm-6 mb-4">
                        <div class="stats-card card border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <h6 class="text-uppercase text-muted mb-2 font-weight-bold small">FEATURES</h6>
                                        <h2 class="mb-0 font-weight-bold text-dark">{{ $totalFeatures }}</h2>
                                    </div>
                                    <div class="stats-icon bg-warning">
                                        <i class="fas fa-star text-white"></i>
                                    </div>
                                </div>
                                <a href="{{ route('admin.features.index') }}" class="stats-link text-decoration-none">
                                    More info <i class="fas fa-arrow-circle-right ml-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Active User Packages Card -->
                    <div class="col-lg-3 col-md-6 col-sm-6 mb-4">
                        <div class="stats-card card border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <h6 class="text-uppercase text-muted mb-2 font-weight-bold small">ACTIVE PACKAGES
                                        </h6>
                                        <h2 class="mb-0 font-weight-bold text-dark">{{ $activeUserPackages }}</h2>
                                    </div>
                                    <div class="stats-icon bg-danger">
                                        <i class="fas fa-check-circle text-white"></i>
                                    </div>
                                </div>
                                <a href="{{ route('admin.users.index') }}" class="stats-link text-decoration-none">
                                    More info <i class="fas fa-arrow-circle-right ml-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="row">
                    <!-- Users Chart -->
                    <div class="col-lg-12 col-md-12 mb-4">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white border-bottom">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0 font-weight-bold text-dark">
                                        <i class="fas fa-chart-line mr-2 text-primary"></i>USERS
                                    </h5>
                                    <ul class="nav nav-pills chart-tabs">
                                        <li class="nav-item">
                                            <a class="nav-link active" href="#weekly-user-chart" data-toggle="tab">WEEK</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" href="#monthly-user-chart" data-toggle="tab">MONTH</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" href="#yearly-user-chart" data-toggle="tab">YEAR</a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="tab-content">
                                    <!-- Weekly Users Chart -->
                                    <div class="tab-pane fade show active" id="weekly-user-chart">
                                        <div class="chart-container" style="position: relative; height: 300px;">
                                            <canvas id="weekly-user-chart-canvas"></canvas>
                                        </div>
                                    </div>
                                    <!-- Monthly Users Chart -->
                                    <div class="tab-pane fade" id="monthly-user-chart">
                                        <div class="chart-container" style="position: relative; height: 300px;">
                                            <canvas id="monthly-user-chart-canvas"></canvas>
                                        </div>
                                    </div>
                                    <!-- Yearly Users Chart -->
                                    <div class="tab-pane fade" id="yearly-user-chart">
                                        <div class="chart-container" style="position: relative; height: 300px;">
                                            <canvas id="yearly-user-chart-canvas"></canvas>
                                        </div>
                                    </div>
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
        .stats-card {
            transition: transform 0.2s, box-shadow 0.2s;
            border-radius: 8px;
        }

        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
        }

        .stats-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .stats-link {
            color: #6c757d;
            font-size: 0.875rem;
            font-weight: 500;
            transition: color 0.2s;
        }

        .stats-link:hover {
            color: #007bff;
        }

        .chart-tabs .nav-link {
            padding: 0.375rem 0.75rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            color: #6c757d;
            border-radius: 4px;
            margin: 0 2px;
            transition: all 0.2s;
        }

        .chart-tabs .nav-link:hover {
            background-color: #f8f9fa;
            color: #495057;
        }

        .chart-tabs .nav-link.active {
            background-color: #007bff;
            color: #ffffff;
        }

        .card-header {
            padding: 1rem 1.25rem;
        }

        .card-body {
            padding: 1.25rem;
        }

        h2 {
            font-size: 2rem;
        }

        h6 {
            font-size: 0.75rem;
            letter-spacing: 0.5px;
        }
    </style>
@endpush

@push('scripts')
    <script>
        $(document).ready(function() {
            // Store chart instances
            const chartInstances = {};

            // Helper function to create a chart
            function createChart(canvasId, chartData, label) {
                const ctx = document.getElementById(canvasId);
                if (!ctx) return null;

                // Destroy existing chart if it exists
                if (chartInstances[canvasId]) {
                    chartInstances[canvasId].destroy();
                }

                const chart = new Chart(ctx.getContext('2d'), {
                    type: 'line',
                    data: chartData,
                    options: {
                        maintainAspectRatio: false,
                        responsive: true,
                        interaction: {
                            intersect: false,
                            mode: 'index',
                        },
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top',
                                labels: {
                                    usePointStyle: true,
                                    padding: 15,
                                    font: {
                                        size: 12,
                                        weight: '500'
                                    }
                                }
                            },
                            tooltip: {
                                enabled: true,
                                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                padding: 12,
                                titleFont: {
                                    size: 13,
                                    weight: '600'
                                },
                                bodyFont: {
                                    size: 12
                                },
                                callbacks: {
                                    title: function(tooltipItems) {
                                        return tooltipItems[0].label;
                                    },
                                    label: function(tooltipItem) {
                                        return label + ': ' + tooltipItem.raw;
                                    }
                                }
                            }
                        },
                        elements: {
                            point: {
                                radius: 4,
                                hoverRadius: 6,
                                borderWidth: 2,
                                backgroundColor: '#ffffff'
                            },
                            line: {
                                tension: 0.3,
                                borderWidth: 2,
                                fill: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                min: 0,
                                ticks: {
                                    stepSize: 1,
                                    precision: 0,
                                    font: {
                                        size: 11
                                    },
                                    color: '#6c757d'
                                },
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.05)',
                                    drawBorder: false
                                }
                            },
                            x: {
                                ticks: {
                                    font: {
                                        size: 11
                                    },
                                    color: '#6c757d'
                                },
                                grid: {
                                    display: false,
                                    drawBorder: false
                                }
                            }
                        }
                    }
                });

                chartInstances[canvasId] = chart;
                return chart;
            }

            // Initialize charts when tabs are shown
            $('a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
                const target = $(e.target).attr('href');

                // Users charts
                if (target === '#weekly-user-chart' && !chartInstances['weekly-user-chart-canvas']) {
                    createChart('weekly-user-chart-canvas', {
                        labels: [{!! getPreviousWeekDates() !!}],
                        datasets: [{
                            label: 'Users',
                            backgroundColor: 'rgba(0, 123, 255, 0.1)',
                            borderColor: 'rgba(0, 123, 255, 1)',
                            pointBackgroundColor: '#ffffff',
                            pointBorderColor: 'rgba(0, 123, 255, 1)',
                            data: [{!! getPreviousWeeksUsers('users') !!}]
                        }]
                    }, 'Users');
                } else if (target === '#monthly-user-chart' && !chartInstances[
                    'monthly-user-chart-canvas']) {
                    createChart('monthly-user-chart-canvas', {
                        labels: [{!! getCurrentMonthDates() !!}],
                        datasets: [{
                            label: 'Users',
                            backgroundColor: 'rgba(0, 123, 255, 0.1)',
                            borderColor: 'rgba(0, 123, 255, 1)',
                            pointBackgroundColor: '#ffffff',
                            pointBorderColor: 'rgba(0, 123, 255, 1)',
                            data: [{!! getCurrentMonthUsers('users') !!}]
                        }]
                    }, 'Users');
                } else if (target === '#yearly-user-chart' && !chartInstances['yearly-user-chart-canvas']) {
                    createChart('yearly-user-chart-canvas', {
                        labels: [{!! getPreviousMonths() !!}],
                        datasets: [{
                            label: 'Users',
                            backgroundColor: 'rgba(0, 123, 255, 0.1)',
                            borderColor: 'rgba(0, 123, 255, 1)',
                            pointBackgroundColor: '#ffffff',
                            pointBorderColor: 'rgba(0, 123, 255, 1)',
                            data: [{!! getPreviousMonthsUsers('users') !!}]
                        }]
                    }, 'Users');
                }
            });

            // Initialize active chart on page load
            createChart('weekly-user-chart-canvas', {
                labels: [{!! getPreviousWeekDates() !!}],
                datasets: [{
                    label: 'Users',
                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
                    borderColor: 'rgba(0, 123, 255, 1)',
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: 'rgba(0, 123, 255, 1)',
                    data: [{!! getPreviousWeeksUsers('users') !!}]
                }]
            }, 'Users');
        });
    </script>
@endpush
