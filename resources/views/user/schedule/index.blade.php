@extends('user.layout.main')
@section('title', 'Schedule')
@section('page_content')
    <div class="page-content">
        <div class="content-header clearfix"></div>
        <section class="content">
            <div class="container-fluid">
                <div class="card">
                    <div class="card-header with-border clearfix">
                        <div class="card-title">
                            <span>Schedule</span>
                        </div>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            @foreach ($accounts as $account)
                                @if ($account->type == 'facebook')
                                    <button
                                        class="btn btn-sm btn-rounded border-right rounded-lg mr-1 account 
                                        @if ($account->schedule_status == 'active') shadow border-success @endif"
                                        data-type="{{ $account->type }}" data-id="{{ $account->id }}">
                                        <img style="width:35px;height:35px;" src="{{ $account->facebook?->profile_image }}"
                                            class="rounded-circle" alt="{{ social_logo('facebook') }}"
                                            onerror="this.onerror=null; this.src='{{ social_logo('facebook') }}';">
                                        <img src="{{ social_logo('facebook') }}" alt=""
                                            style="width: 15px; position:relative;">
                                        <b>{{ $account->name }}</b>
                                    </button>
                                @elseif($account->type == 'pinterest')
                                    <button
                                        class="btn btn-sm btn-rounded border-right rounded-lg mr-1 account 
                                    @if ($account->schedule_status == 'active') shadow border-success @endif"
                                        data-type="{{ $account->type }}" data-id="{{ $account->id }}">
                                        <img style="width:35px;height:35px;" src="{{ $account->pinterest?->profile_image }}"
                                            class="rounded-circle" alt="{{ social_logo('pinterest') }}"
                                            onerror="this.onerror=null; this.src='{{ social_logo('pinterest') }}';">
                                        <img src="{{ social_logo('pinterest') }}" alt=""
                                            style="width: 15px; position:relative;">
                                        <b>{{ $account->name }}</b>
                                    </button>
                                @endif
                            @endforeach
                        </div>
                        <div class="card-body px-0">
                            <div class="row">
                                <textarea name="content" id="content" class="form-control col-md-12 check_count" placeholder="Paste your link here!"
                                    rows="3" data-max="100"></textarea>
                                <span id="characterCount" class="text-muted"></span>
                            </div>
                            <div id="article-container" class="card-container"></div>
                            <div class="row">
                                <div class="form-control col-md-12 dropzone" id="dropZone">
                                </div>
                            </div>
                            <div class="row">
                                <textarea name="comment" id="comment" class="form-control col-md-12" placeholder="Comment here!" rows="1"
                                    data-max="100"></textarea>
                            </div>
                            <div class="row justify-content-between mt-2">
                                <div>
                                    <button type="button" class="btn btn-outline-info btn-sm setting_btn">
                                        SETTINGS
                                    </button>
                                </div>
                                <div class="d-flex">
                                    <button type="button" class="btn btn-outline-danger btn-sm action_btn" href="schedule">
                                        SCHEDULE
                                    </button>
                                    <button type="button" class="btn btn-outline-success btn-sm action_btn mx-1"
                                        href="queue">
                                        QUEUE
                                    </button>
                                    <button type="button" class="btn btn-outline-primary btn-sm action_btn" href="publish">
                                        PUBLISH
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
    @include('user.schedule.modals.settings-modal')
    @include('user.schedule.modals.schedule-modal')
@endsection

@push('styles')
    <style>
        /* --- Custom Skeleton Styling --- */

        /* Animation Definition */
        @keyframes pulse-slow {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: .5;
            }
        }

        .animate-pulse-slow {
            animation: pulse-slow 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        /* Card Container (Main wrapper) */
        .card-container {
            width: 100%;
            max-width: 40rem;
            /* max-w-xl equivalent */
            background-color: white;
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
            border-radius: 0.75rem;
            /* rounded-xl */
            position: relative;
        }

        /* Skeleton Wrapper (Inner content box) */
        .skeleton-wrapper {
            display: flex;
            padding: 1.5rem;
            /* p-6 */
            background-color: #F3F4F6;
            /* skeleton-bg */
            border-radius: 0.75rem;
            overflow: hidden;
            /* Ensure rounded corners clip content */
        }

        /* Left Column (Text area) */
        .content-col {
            flex-grow: 1;
            padding-right: 1.5rem;
            /* pr-6 */
        }

        /* Right Column (Image/Sidebar block) */
        .image-col {
            width: 25%;
            /* w-1/4 */
            flex-shrink: 0;
            position: relative;
        }

        /* Skeleton Bars (The actual pulsing lines) */
        .skeleton-bar {
            background-color: #E5E7EB;
            /* skeleton-bar */
            border-radius: 0.25rem;
            /* rounded-sm to rounded-md */
            margin-bottom: 1rem;
            /* space-y-4 converted to margin-bottom */
        }

        /* Skeleton Bar Dimensions */
        .bar-title {
            height: 1.5rem;
            width: 75%;
            border-radius: 0.375rem;
        }

        /* h-6 w-3/4 */
        .bar-full {
            height: 1rem;
            width: 100%;
        }

        .bar-medium {
            height: 1rem;
            width: 85%;
        }

        /* w-5/6 */
        .bar-short {
            height: 1rem;
            width: 33.333%;
        }

        /* w-1/3 */
        .image-placeholder {
            height: 100%;
            width: 100%;
            border-radius: 0.5rem;
        }

        /* Close Button Styling */
        .close-btn-placeholder {
            position: absolute;
            top: -0.75rem;
            /* -top-3 */
            right: -0.75rem;
            /* -right-3 */
            width: 1.5rem;
            /* w-6 */
            height: 1.5rem;
            /* h-6 */
            background-color: black;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 0.75rem;
            /* text-xs */
            cursor: pointer;
            border: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        /* Real Content Styling */
        .real-article-wrapper {
            display: flex;
            padding: 1.5rem;
            background-color: white;
            border-radius: 0.75rem;
            border: 1px solid #E5E7EB;
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
            color: #1F2937;
            opacity: 0;
            transition: opacity 1s;
        }

        .real-article-wrapper .title {
            font-size: 1.25rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
            color: #4338CA;
            /* Indigo-700 equivalent */
        }

        .real-article-wrapper .summary {
            font-size: 0.875rem;
            color: #4B5563;
            line-height: 1.625;
        }

        .real-article-wrapper img {
            width: 100%;
            height: auto;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
    </style>
@endpush

@push('scripts')
    {{-- scripts --}}
    @include('user.schedule.assets.script')
@endpush
