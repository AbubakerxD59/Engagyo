@extends('admin.layouts.secure')
@section('page_title', 'Test Case Details')
@section('page_content')
    <div class="page-content">
        <div class="content-header clearfix">
            <h1 class="float-left">Test Case Details
                <small>
                    <i class="fas fa-arrow-circle-left"></i>
                    <a href="{{ route('admin.facebook-tests.index') }}">back to Test Cases list</a>
                </small>
            </h1>
        </div>
        <section class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Test Case #{{ $testCase->id }}</h3>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <table class="table table-bordered">
                                            <tr>
                                                <th width="40%">Test Type</th>
                                                <td>{{ ucfirst($testCase->test_type) }}</td>
                                            </tr>
                                            <tr>
                                                <th>Status</th>
                                                <td>{!! $testCase->status_badge !!}</td>
                                            </tr>
                                            <tr>
                                                <th>Ran At</th>
                                                <td>{{ $testCase->ran_at ? $testCase->ran_at->format('Y-m-d H:i:s') : 'N/A' }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Created At</th>
                                                <td>{{ $testCase->created_at->format('Y-m-d H:i:s') }}</td>
                                            </tr>
                                            <tr>
                                                <th>Updated At</th>
                                                <td>{{ $testCase->updated_at->format('Y-m-d H:i:s') }}</td>
                                            </tr>
                                        </table>
                                    </div>
                                    <div class="col-md-6">
                                        <table class="table table-bordered">
                                            <tr>
                                                <th width="40%">Facebook Page</th>
                                                <td>
                                                    @if ($testCase->facebookPage)
                                                        {{ $testCase->facebookPage->name }}
                                                    @else
                                                        N/A
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Test Post</th>
                                                <td>
                                                    @if ($testCase->testPost)
                                                        <a href="#" target="_blank">
                                                            Post #{{ $testCase->testPost->id }}
                                                            @if ($testCase->testPost->post_id)
                                                                (Facebook ID: {{ $testCase->testPost->post_id }})
                                                            @endif
                                                        </a>
                                                    @else
                                                        N/A
                                                    @endif
                                                </td>
                                            </tr>
                                            @if ($testCase->failure_reason)
                                                <tr>
                                                    <th>Failure Reason</th>
                                                    <td>
                                                        <div class="alert alert-danger mb-0">
                                                            {{ $testCase->failure_reason }}
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endif
                                        </table>
                                    </div>
                                </div>

                                @if ($testCase->test_data)
                                    <div class="row mt-3">
                                        <div class="col-12">
                                            <h5>Test Data</h5>
                                            <div class="card">
                                                <div class="card-body">
                                                    <pre class="mb-0" style="max-height: 400px; overflow-y: auto;">{{ json_encode($testCase->test_data, JSON_PRETTY_PRINT) }}</pre>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                @if ($testCase->testPost)
                                    <div class="row mt-3">
                                        <div class="col-12">
                                            <h5>Test Post Details</h5>
                                            <table class="table table-bordered">
                                                <tr>
                                                    <th width="20%">Post ID</th>
                                                    <td>{{ $testCase->testPost->id }}</td>
                                                </tr>
                                                <tr>
                                                    <th>Facebook Post ID</th>
                                                    <td>{{ $testCase->testPost->post_id ?? 'N/A' }}</td>
                                                </tr>
                                                <tr>
                                                    <th>Type</th>
                                                    <td>{{ ucfirst($testCase->testPost->type) }}</td>
                                                </tr>
                                                <tr>
                                                    <th>Title</th>
                                                    <td>{{ $testCase->testPost->title ?? 'N/A' }}</td>
                                                </tr>
                                                <tr>
                                                    <th>Status</th>
                                                    <td>
                                                        @if ($testCase->testPost->status == 1)
                                                            <span class="badge badge-success">Published</span>
                                                        @elseif($testCase->testPost->status == -1)
                                                            <span class="badge badge-danger">Failed</span>
                                                        @else
                                                            <span class="badge badge-warning">Pending</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                                @if ($testCase->testPost->response)
                                                    <tr>
                                                        <th>Response</th>
                                                        <td>
                                                            <pre style="text-wrap: auto;max-height: 200px;overflow-y: auto;">{{ is_string($testCase->testPost->response) ? $testCase->testPost->response : json_encode($testCase->testPost->response, JSON_PRETTY_PRINT) }}</pre>
                                                        </td>
                                                    </tr>
                                                @endif
                                                @if ($testCase->testPost->published_at)
                                                    <tr>
                                                        <th>Published At</th>
                                                        <td>{{ \Carbon\Carbon::parse($testCase->testPost->published_at)->format('Y-m-d H:i:s') }}
                                                        </td>
                                                    </tr>
                                                @endif
                                            </table>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
