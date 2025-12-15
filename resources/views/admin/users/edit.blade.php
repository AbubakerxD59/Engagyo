@extends('admin.layouts.secure')
@section('page_title', $user->full_name)
@section('page_content')
    @can('edit_user')
        <div class="page-content">
            <form method="POST" action="{{ route('admin.users.update', $user->id) }}" class="form-horizontal">
                @csrf
                @method('PUT')
                <div class="content-header clearfix">
                    <h1 class="float-left"> Edit User
                        <small>
                            <i class="fas fa-arrow-circle-left"></i>
                            <a href="{{ route('admin.users.index') }}">back to Users list</a>
                        </small>
                    </h1>
                    <div class="float-right">
                        <button type="submit" name="action" value="save" class="btn btn-primary">
                            <i class="far fa-save"></i>
                            Save
                        </button>
                    </div>
                </div>
                <section class="content">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header p-0 border-bottom-0">
                                        <ul class="nav nav-tabs" id="user-tabs" role="tablist">
                                            <li class="nav-item">
                                                <a class="nav-link active" id="info-tab" data-toggle="tab" href="#info"
                                                    role="tab" aria-controls="info" aria-selected="true">
                                                    <i class="fas fa-info-circle mr-2"></i>Info
                                                </a>
                                            </li>
                                            <li class="nav-item">
                                                <a class="nav-link" id="features-usage-tab" data-toggle="tab"
                                                    href="#features-usage" role="tab" aria-controls="features-usage"
                                                    aria-selected="false">
                                                    <i class="fas fa-star mr-2"></i>Features & Usage
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                    <div class="card-body">
                                        <div class="tab-content" id="user-tabs-content">
                                            <!-- Info Tab -->
                                            <div class="tab-pane fade show active" id="info" role="tabpanel"
                                                aria-labelledby="info-tab">
                                                @include('admin.users.tabs.info')
                                            </div>
                                            <!-- Features & Usage Tab -->
                                            <div class="tab-pane fade" id="features-usage" role="tabpanel"
                                                aria-labelledby="features-usage-tab">
                                                @include('admin.users.tabs.features_usage')
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </form>
        </div>
    @endcan
@endsection
@push('scripts')
    <script>
        $(document).ready(function() {
            $('#package_id').on('change', function() {
                var packageId = $(this).val();
                if (packageId) {
                    $('#full_access_row').slideDown();
                } else {
                    $('#full_access_row').slideUp();
                    $('#full_access').prop('checked', false);
                }
            });

            // Trigger on page load if package is already selected
            if ($('#package_id').val()) {
                $('#full_access_row').show();
            }
        });
    </script>
@endpush
