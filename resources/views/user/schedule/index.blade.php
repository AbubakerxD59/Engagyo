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
                <div class="card">
                    <div class="card-header with-border clearfix">
                        <div class="card-title">
                            <span>Posts</span>
                        </div>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row m-0 p-0 mb-5">
                            <div class="col-md-3">
                                <label for="account">Account</label>
                                <select name="account" id="account" class="form-control select2 filter" multiple>
                                    @foreach ($accounts as $account)
                                        <option value="{{ $account->account_id }}">{{ ucfirst($account->name) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="type">Social type</label>
                                <select name="type" id="type" class="form-control select2 filter" multiple>
                                    @foreach (get_options('social_accounts') as $social_account)
                                        <option value="{{ $social_account }}">{{ ucfirst($social_account) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="post_type">Post Type</label>
                                <select name="post_type" id="post_type" class="form-control select2 filter" multiple>
                                    <option value="photo">Image</option>
                                    <option value="content_only">Quote</option>
                                    <option value="link">Link</option>
                                    <option value="video">Video</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="status">Status</label>
                                <select name="status" id="status" class="form-control select2 filter" multiple>
                                    <option value="0">Pending</option>
                                    <option value="1">Published</option>
                                    <option value="-1">Failed</option>
                                </select>
                            </div>
                        </div>
                        <table class="table table-bordered mt-3" id="postsTable">
                            <thead>
                                <tr>
                                    <th>Post <small>(Details)</small> </th>
                                    <th>Account</th>
                                    <th>Publish Date/Time</th>
                                    <th>Status</th>
                                    <th style="max-width:200px;">Response</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </section>
    </div>
    @include('user.schedule.modals.settings-modal')
    @include('user.schedule.modals.schedule-modal')
@endsection

@push('styles')
    {{-- styling --}}
    @include('user.schedule.assets.style')
    @include('user.schedule.assets.facebook_post')
    @include('user.schedule.assets.pinterest_post')
@endpush

@push('scripts')
    {{-- scripts --}}
    @include('user.schedule.assets.script')
@endpush
