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

@push('scripts')
    @include('user.schedule.assets.script')
@endpush
