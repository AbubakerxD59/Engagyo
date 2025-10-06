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
                                        class="btn btn-sm btn-rounded p-1 pr-3 m-1 border-right rounded-lg account 
                                        @if ($account->schedule_status == 'active') shadow border-success @endif"
                                        data-type="{{ $account->type }}" data-id="{{ $account->id }}">
                                        <img style="width:35px;height:35px;" src="{{ $account->facebook?->profile_image }}"
                                            class="rounded-circle mr-2" alt="{{ social_logo('facebook') }}"
                                            onerror="this.onerror=null; this.src='{{ social_logo('facebook') }}';">
                                        <img src="{{ social_logo('facebook') }}" alt=""
                                            style="width: 20px; position:relative; right: 15%; top: 50%;">
                                        <b>{{ $account->name }}</b>
                                    </button>
                                @elseif($account->type == 'pinterest')
                                    <button
                                        class="btn btn-sm btn-rounded p-1 pr-3 m-1 border-right rounded-lg account 
                                    @if ($account->schedule_status == 'active') shadow border-success @endif"
                                        data-type="{{ $account->type }}" data-id="{{ $account->id }}">
                                        <img style="width:35px;height:35px;" src="{{ $account->pinterest?->profile_image }}"
                                            class="rounded-circle mr-2" alt="{{ social_logo('pinterest') }}"
                                            onerror="this.onerror=null; this.src='{{ social_logo('pinterest') }}';">
                                        <img src="{{ social_logo('pinterest') }}" alt=""
                                            style="width: 20px; position:relative; right: 15%; top: 50%;">
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
                            <div class="row justify-content-end mt-2">
                                <button type="button" class="btn btn-outline-success btn-sm publish_btn">
                                    PUBLISH
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    @include('user.schedule.assets.script')
@endpush
