@extends('admin.layouts.secure')
@section('page_title', 'Dashboard')
@section('page_content')
    <div class="page-content">
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1>Dashboard</h1>
                    </div>

                </div>
                <div class="row">
                    <div class="col-lg-3 col-6">
                        <div class="small-box bg-info">
                            <div class="inner">
                                <h3>{{ get_total_users() }}</h3>
                                <p>USERS</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <a href="{{ route('admin.users.index') }}" class="small-box-footer">More info
                                <i class="fas fa-arrow-circle-right"></i>
                            </a>
                        </div>
                    </div>
                    <div class="col-lg-3 col-6">
                        <div class="small-box bg-success">
                            <div class="inner">
                                <h3>10</h3>
                                <p>Payments</p>
                            </div>
                            <div class="icon">
                                <i class="fa fa-credit-card"></i>
                            </div>
                            <a href="" class="small-box-footer">More info
                                <i class="fas fa-arrow-circle-right"></i>
                            </a>
                        </div>
                    </div>
                    <div class="col-lg-3 col-6">
                        <div class="small-box bg-danger">
                            <div class="inner">
                                <h3>500 £</h3>
                                <p>Earnings</p>
                            </div>
                            <div class="icon">
                                <i class="fa fa-wallet"></i>
                            </div>
                            <a href="#" class="small-box-footer">More info
                                <i class="fas fa-arrow-circle-right"></i>
                            </a>
                        </div>
                    </div>
                    <div class="col-lg-3 col-6">
                        <div class="small-box bg-warning">
                            <div class="inner">
                                <h3>5</h3>
                                <p>Orders</p>
                            </div>
                            <div class="icon">
                                <i class="fa fa-store"></i>
                            </div>
                            <a href="#" class="small-box-footer">More info
                                <i class="fas fa-arrow-circle-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-6">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-chart-line mr-1"></i>
                                    Users
                                </h3>
                                <div class="card-tools">
                                    <ul class="nav nav-pills ml-auto">
                                        <li class="nav-item">
                                            <a class="nav-link active" href="#weekly-user-chart" data-toggle="tab">Week</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" href="#monthly-user-chart" data-toggle="tab">Month</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" href="#yearly-user-chart" data-toggle="tab">Year</a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <!-- /.card-header -->
                            <div class="card-body">
                                <div class="tab-content p-0">
                                    <!-- Weekly Users Chart -->
                                    <div class="chart tab-pane active" id="weekly-user-chart"
                                        style="position: relative; height: 300px;">
                                        <div class="chartjs-size-monitor">
                                            <div class="chartjs-size-monitor-expand">
                                                <div class=""></div>
                                            </div>
                                            <div class="chartjs-size-monitor-shrink">
                                                <div class=""></div>
                                            </div>
                                        </div>
                                        <canvas id="weekly-user-chart-canvas" height="300"
                                            style="height: 300px; display: block; width: 475px;" width="475"
                                            class="chartjs-render-monitor"></canvas>
                                    </div>
                                    <!-- Monthly Users Chart -->
                                    <div class="chart tab-pane" id="monthly-user-chart"
                                        style="position: relative; height: 300px;">
                                        <canvas id="monthly-user-chart-canvas" height="0"
                                            style="height: 0px; display: block; width: 0px;" class="chartjs-render-monitor"
                                            width="0"></canvas>
                                    </div>
                                    <!-- Yearly Users Chart -->
                                    <div class="chart tab-pane" id="yearly-user-chart"
                                        style="position: relative; height: 300px;">
                                        <canvas id="yearly-user-chart-canvas" height="0"
                                            style="height: 0px; display: block; width: 0px;" class="chartjs-render-monitor"
                                            width="0"></canvas>
                                    </div>
                                </div>
                            </div>
                            <!-- /.card-body -->
                        </div>
                    </div>

                    <div class="col-sm-6">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-chart-line mr-1"></i>
                                    Orders
                                </h3>
                                <div class="card-tools">
                                    <ul class="nav nav-pills ml-auto">
                                        <li class="nav-item">
                                            <a class="nav-link active" href="#weekly-order-chart" data-toggle="tab">Week</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" href="#monthly-order-chart" data-toggle="tab">Month</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" href="#yearly-order-chart" data-toggle="tab">Year</a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <!-- /.card-header -->
                            <div class="card-body">
                                <div class="tab-content p-0">
                                    <!-- Weekly Orders Chart -->
                                    <div class="chart tab-pane active" id="weekly-order-chart"
                                        style="position: relative; height: 300px;">
                                        <div class="chartjs-size-monitor">
                                            <div class="chartjs-size-monitor-expand">
                                                <div class=""></div>
                                            </div>
                                            <div class="chartjs-size-monitor-shrink">
                                                <div class=""></div>
                                            </div>
                                        </div>
                                        <canvas id="weekly-order-chart-canvas" height="300"
                                            style="height: 300px; display: block; width: 475px;" width="475"
                                            class="chartjs-render-monitor"></canvas>
                                    </div>
                                    <!-- Monthly Orders Chart -->
                                    <div class="chart tab-pane" id="monthly-order-chart"
                                        style="position: relative; height: 300px;">
                                        <canvas id="monthly-order-chart-canvas" height="0"
                                            style="height: 0px; display: block; width: 0px;"
                                            class="chartjs-render-monitor" width="0"></canvas>
                                    </div>
                                    <!-- Yearly Orders Chart -->
                                    <div class="chart tab-pane" id="yearly-order-chart"
                                        style="position: relative; height: 300px;">
                                        <canvas id="yearly-order-chart-canvas" height="0"
                                            style="height: 0px; display: block; width: 0px;"
                                            class="chartjs-render-monitor" width="0"></canvas>
                                    </div>
                                </div>
                            </div>
                            <!-- /.card-body -->
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
@push('scripts')
    <script>
        $(document).ready(function() {
            // Helper function to create a chart
            function createChart(canvasId, chartData, label) {
                var ctx = document.getElementById(canvasId).getContext('2d');
                return new Chart(ctx, {
                    type: 'line',
                    data: chartData,
                    options: {
                        maintainAspectRatio: false,
                        responsive: true,
                        plugins: {
                            tooltip: {
                                enabled: true,
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
                                radius: 5, // Size of data points
                                hoverRadius: 7, // Size of data points on hover
                                backgroundColor: '#fff' // Color of the data points
                            },
                            line: {
                                tension: 0.1 // Smoothness of the line
                            }
                        },
                        scales: {
                            yAxes: [{
                                ticks: {
                                    beginAtZero: true, // Start the y-axis at 0
                                    min: 0, // Set the minimum value of the y-axis to 0
                                    stepSize: 1, // Ensure the steps between labels are 1
                                    callback: function(value) {
                                        return value; // Ensure the label is a whole number
                                    }
                                }
                            }]
                        }
                    }
                });
            }

            // Weekly Users Chart
            createChart('weekly-user-chart-canvas', {
                labels: [{!! getPreviousWeekDates() !!}],
                datasets: [{
                    label: 'Users',
                    backgroundColor: 'rgba(60,141,188,0.9)',
                    borderColor: 'rgba(60,141,188,0.8)',
                    data: [{!! getPreviousWeeksUsers('users') !!}]
                }]
            }, 'Users');

            // Monthly Users Chart
            createChart('monthly-user-chart-canvas', {
                labels: [{!! getCurrentMonthDates() !!}],
                datasets: [{
                    label: 'Users',
                    backgroundColor: 'rgba(60,141,188,0.9)',
                    borderColor: 'rgba(60,141,188,0.8)',
                    data: [{!! getCurrentMonthUsers('users') !!}]
                }]
            }, 'Users');

            // Yearly Users Chart
            createChart('yearly-user-chart-canvas', {
                labels: [{!! getPreviousMonths() !!}],
                datasets: [{
                    label: 'Users',
                    backgroundColor: 'rgba(60,141,188,0.9)',
                    borderColor: 'rgba(60,141,188,0.8)',
                    data: [{!! getPreviousMonthsUsers('users') !!}]
                }]
            }, 'Users');

            // Weekly Order Chart
            createChart('weekly-order-chart-canvas', {
                labels: [{!! getPreviousWeekDates() !!}],
                datasets: [{
                    label: 'Order',
                    backgroundColor: 'rgba(255,159,64,0.9)',
                    borderColor: 'rgba(255,159,64,0.8)',
                    data: [{!! getPreviousWeeksUsers('users') !!}]
                }]
            }, 'Order');

            // Monthly Order Chart
            createChart('monthly-order-chart-canvas', {
                labels: [{!! getCurrentMonthDates() !!}],
                datasets: [{
                    label: 'Order',
                    backgroundColor: 'rgba(255,159,64,0.9)',
                    borderColor: 'rgba(255,159,64,0.8)',
                    data: [{!! getCurrentMonthUsers('users') !!}]
                }]
            }, 'Order');

            // Yearly Order Chart
            createChart('yearly-order-chart-canvas', {
                labels: [{!! getPreviousMonths() !!}],
                datasets: [{
                    label: 'Order',
                    backgroundColor: 'rgba(255,159,64,0.9)',
                    borderColor: 'rgba(255,159,64,0.8)',
                    data: [{!! getPreviousMonthsUsers('users') !!}]
                }]
            }, 'Order');
        });
    </script>
@endpush
