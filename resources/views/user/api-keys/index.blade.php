@extends('user.layout.main')
@section('title', 'API Keys')
@section('page_content')
    <div class="page-content">
        <div class="content-header clearfix"></div>
        <section class="content">
            <div class="container-fluid">
                {{-- Header Section --}}
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card bg-gradient-primary">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h4 class="mb-1 text-white">
                                            <i class="fas fa-key mr-2"></i>API Keys Management
                                        </h4>
                                        <p class="mb-0 text-white-50">
                                            Manage your API keys for Pabbly Connect and other integrations
                                        </p>
                                    </div>
                                    <button type="button" class="btn btn-light" data-toggle="modal"
                                        data-target="#createApiKeyModal">
                                        <i class="fas fa-plus mr-1"></i> Create New API Key
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- API Documentation Card --}}
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-book mr-2"></i>API Documentation
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6><i class="fas fa-link mr-1"></i> Base URL</h6>
                                        <div class="input-group mb-3">
                                            <input type="text" class="form-control bg-light" id="baseUrl"
                                                value="{{ url('/api/v1') }}" readonly>
                                            <div class="input-group-append">
                                                <button class="btn btn-outline-secondary copy-btn" type="button"
                                                    data-target="baseUrl">
                                                    <i class="fas fa-copy"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <h6><i class="fas fa-shield-alt mr-1"></i> Authentication</h6>
                                        <code class="d-block bg-light p-2 rounded mt-2">
                                            Authorization: Bearer your_api_key_here
                                        </code>
                                    </div>
                                </div>
                                <hr>
                                <h6><i class="fas fa-list mr-1"></i> Available Endpoints</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered mb-0">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>Method</th>
                                                <th>Endpoint</th>
                                                <th>Description</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach (getApiEndpoints() as $endpoint)
                                                <tr>
                                                    <td><span
                                                            class="badge badge-{{ $endpoint['method'] == 'GET' ? 'success' : 'primary' }}">{{ $endpoint['method'] }}</span>
                                                    </td>
                                                    <td><code>{{ $endpoint['endpoint'] }}</code></td>
                                                    <td>{{ $endpoint['description'] }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- API Keys List --}}
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-list mr-2"></i>Your API Keys
                        </h5>
                    </div>
                    <div class="card-body">
                        @if ($apiKeys->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover" id="apiKeysTable">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>App Name</th>
                                            <th>API Key</th>
                                            <th>Status</th>
                                            <th>Last Used</th>
                                            <th>Created</th>
                                            <th class="text-center">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($apiKeys as $key)
                                            <tr data-id="{{ $key->id }}">
                                                <td>
                                                    <strong>{{ $key->name }}</strong>
                                                </td>
                                                <td>
                                                    <div class="input-group input-group-sm" style="max-width: 300px;">
                                                        <input type="text" class="form-control api-key-input bg-light"
                                                            value="{{ substr($key->key, 0, 20) }}..." readonly
                                                            data-full-key="{{ $key->key }}">
                                                        <div class="input-group-append">
                                                            <button class="btn btn-outline-secondary copy-key-btn"
                                                                type="button" data-key="{{ $key->key }}"
                                                                title="Copy API Key">
                                                                <i class="fas fa-copy"></i>
                                                            </button>
                                                            <button class="btn btn-outline-secondary toggle-key-btn"
                                                                type="button" title="Show/Hide Key">
                                                                <i class="fas fa-eye"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    @if ($key->is_active)
                                                        <span class="badge badge-success status-badge">Active</span>
                                                    @else
                                                        <span class="badge badge-danger status-badge">Inactive</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    {{ $key->last_used_at ? $key->last_used_at->format('M d, Y H:i') : 'Never' }}
                                                </td>
                                                <td>{{ $key->created_at->format('M d, Y H:i') }}</td>
                                                <td class="text-center">
                                                    <div class="btn-group btn-group-sm">
                                                        <button type="button" class="btn btn-outline-warning refresh-btn"
                                                            data-id="{{ $key->id }}" title="Refresh Key">
                                                            <i class="fas fa-sync-alt"></i>
                                                        </button>
                                                        <button type="button"
                                                            class="btn btn-outline-{{ $key->is_active ? 'secondary' : 'success' }} toggle-status-btn"
                                                            data-id="{{ $key->id }}"
                                                            title="{{ $key->is_active ? 'Deactivate' : 'Activate' }}">
                                                            <i class="fas fa-{{ $key->is_active ? 'pause' : 'play' }}"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-outline-danger delete-key-btn"
                                                            data-id="{{ $key->id }}" title="Delete">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-5">
                                <i class="fas fa-key fa-4x text-muted mb-3"></i>
                                <h5 class="text-muted">No API Keys Yet</h5>
                                <p class="text-muted mb-3">Create your first API key to start integrating with Pabbly
                                    Connect</p>
                                <button type="button" class="btn btn-primary" data-toggle="modal"
                                    data-target="#createApiKeyModal">
                                    <i class="fas fa-plus mr-1"></i> Create API Key
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </section>
    </div>

    {{-- Create API Key Modal --}}
    <div class="modal fade" id="createApiKeyModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-plus-circle mr-2"></i>Create New API Key
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form id="createApiKeyForm">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="appName">App Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="appName" name="name"
                                placeholder="e.g., Pabbly Connect, Zapier, My App" required>
                            <small class="form-text text-muted">
                                Give your API key a descriptive name to identify its purpose
                            </small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="createKeyBtn">
                            <i class="fas fa-key mr-1"></i> Create API Key
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Show New Key Modal --}}
    <div class="modal fade" id="showNewKeyModal" tabindex="-1" role="dialog" data-backdrop="static">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-check-circle mr-2"></i>API Key Created Successfully
                    </h5>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <strong>Important:</strong> Copy your API key now. You won't be able to see it again!
                    </div>
                    <div class="form-group">
                        <label><strong>Your New API Key:</strong></label>
                        <div class="input-group">
                            <input type="text" class="form-control bg-light" id="newApiKey" readonly>
                            <div class="input-group-append">
                                <button class="btn btn-primary copy-new-key-btn" type="button">
                                    <i class="fas fa-copy mr-1"></i> Copy
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success" data-dismiss="modal" onclick="location.reload()">
                        <i class="fas fa-check mr-1"></i> I've Copied My Key
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Show Refreshed Key Modal --}}
    <div class="modal fade" id="showRefreshedKeyModal" tabindex="-1" role="dialog" data-backdrop="static">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title">
                        <i class="fas fa-sync-alt mr-2"></i>API Key Refreshed
                    </h5>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <strong>Important:</strong> Your old API key has been invalidated. Copy your new API key now!
                    </div>
                    <div class="form-group">
                        <label><strong>Your New API Key:</strong></label>
                        <div class="input-group">
                            <input type="text" class="form-control bg-light" id="refreshedApiKey" readonly>
                            <div class="input-group-append">
                                <button class="btn btn-primary copy-refreshed-key-btn" type="button">
                                    <i class="fas fa-copy mr-1"></i> Copy
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success" data-dismiss="modal" onclick="location.reload()">
                        <i class="fas fa-check mr-1"></i> I've Copied My Key
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .api-key-input {
            font-family: 'Courier New', monospace;
            font-size: 0.85rem;
        }

        #newApiKey,
        #refreshedApiKey {
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
        }

        .card-header h5 {
            font-size: 1rem;
        }

        code {
            font-size: 0.85rem;
        }

        .table td {
            vertical-align: middle;
        }

        .btn-group-sm .btn {
            padding: 0.25rem 0.5rem;
        }
    </style>
@endpush

@push('scripts')
    <script>
        $(document).ready(function() {
            // Copy to clipboard function
            function copyToClipboard(text) {
                navigator.clipboard.writeText(text).then(function() {
                    toastr.success('Copied to clipboard!');
                }).catch(function() {
                    // Fallback for older browsers
                    var $temp = $("<input>");
                    $("body").append($temp);
                    $temp.val(text).select();
                    document.execCommand("copy");
                    $temp.remove();
                    toastr.success('Copied to clipboard!');
                });
            }

            // Copy base URL
            $('.copy-btn').on('click', function() {
                var target = $(this).data('target');
                var text = $('#' + target).val();
                copyToClipboard(text);
            });

            // Copy API key
            $('.copy-key-btn').on('click', function() {
                var key = $(this).data('key');
                copyToClipboard(key);
            });

            // Toggle show/hide key
            $('.toggle-key-btn').on('click', function() {
                var input = $(this).closest('.input-group').find('.api-key-input');
                var fullKey = input.data('full-key');
                var icon = $(this).find('i');

                if (input.attr('type') === 'text' && input.val() === fullKey) {
                    input.val(fullKey.substring(0, 20) + '...');
                    icon.removeClass('fa-eye-slash').addClass('fa-eye');
                } else {
                    input.val(fullKey);
                    icon.removeClass('fa-eye').addClass('fa-eye-slash');
                }
            });

            // Create API Key
            $('#createApiKeyForm').on('submit', function(e) {
                e.preventDefault();
                var btn = $('#createKeyBtn');
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Creating...');

                $.ajax({
                    url: "{{ route('panel.api-keys.store') }}",
                    type: 'POST',
                    data: $(this).serialize(),
                    success: function(response) {
                        if (response.success) {
                            $('#createApiKeyModal').modal('hide');
                            $('#newApiKey').val(response.data.key);
                            $('#showNewKeyModal').modal('show');
                            $('#createApiKeyForm')[0].reset();
                        } else {
                            toastr.error(response.message);
                        }
                    },
                    error: function(xhr) {
                        var message = xhr.responseJSON?.message || 'An error occurred';
                        toastr.error(message);
                    },
                    complete: function() {
                        btn.prop('disabled', false).html(
                            '<i class="fas fa-key mr-1"></i> Create API Key');
                    }
                });
            });

            // Copy new key
            $('.copy-new-key-btn').on('click', function() {
                var key = $('#newApiKey').val();
                copyToClipboard(key);
            });

            // Copy refreshed key
            $('.copy-refreshed-key-btn').on('click', function() {
                var key = $('#refreshedApiKey').val();
                copyToClipboard(key);
            });

            // Refresh API Key
            $('.refresh-btn').on('click', function() {
                var id = $(this).data('id');
                var btn = $(this);

                if (!confirm(
                        'Are you sure you want to refresh this API key? The old key will stop working immediately.'
                    )) {
                    return;
                }

                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

                $.ajax({
                    url: "{{ route('panel.api-keys.refresh', '') }}/" + id,
                    type: 'POST',
                    success: function(response) {
                        if (response.success) {
                            $('#refreshedApiKey').val(response.data.key);
                            $('#showRefreshedKeyModal').modal('show');
                        } else {
                            toastr.error(response.message);
                        }
                    },
                    error: function(xhr) {
                        var message = xhr.responseJSON?.message || 'An error occurred';
                        toastr.error(message);
                    },
                    complete: function() {
                        btn.prop('disabled', false).html('<i class="fas fa-sync-alt"></i>');
                    }
                });
            });

            // Toggle API Key Status
            $('.toggle-status-btn').on('click', function() {
                var id = $(this).data('id');
                var btn = $(this);
                var row = btn.closest('tr');

                btn.prop('disabled', true);

                $.ajax({
                    url: "{{ route('panel.api-keys.toggle', '') }}/" + id,
                    type: 'POST',
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message);
                            // Update UI
                            var isActive = response.data.is_active;
                            var statusBadge = row.find('.status-badge');
                            var icon = btn.find('i');

                            if (isActive) {
                                statusBadge.removeClass('badge-danger').addClass(
                                        'badge-success')
                                    .text('Active');
                                btn.removeClass('btn-outline-success').addClass(
                                    'btn-outline-secondary');
                                icon.removeClass('fa-play').addClass('fa-pause');
                                btn.attr('title', 'Deactivate');
                            } else {
                                statusBadge.removeClass('badge-success').addClass(
                                        'badge-danger')
                                    .text('Inactive');
                                btn.removeClass('btn-outline-secondary').addClass(
                                    'btn-outline-success');
                                icon.removeClass('fa-pause').addClass('fa-play');
                                btn.attr('title', 'Activate');
                            }
                        } else {
                            toastr.error(response.message);
                        }
                    },
                    error: function(xhr) {
                        var message = xhr.responseJSON?.message || 'An error occurred';
                        toastr.error(message);
                    },
                    complete: function() {
                        btn.prop('disabled', false);
                    }
                });
            });

            // Delete API Key
            $('.delete-key-btn').on('click', function() {
                var id = $(this).data('id');
                var btn = $(this);
                var row = btn.closest('tr');

                if (!confirm(
                        'Are you sure you want to delete this API key? This action cannot be undone.')) {
                    return;
                }

                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

                $.ajax({
                    url: "{{ route('panel.api-keys.destroy', '') }}/" + id,
                    type: 'DELETE',
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message);
                            row.fadeOut(300, function() {
                                $(this).remove();
                                // Check if table is empty
                                if ($('#apiKeysTable tbody tr').length === 0) {
                                    location.reload();
                                }
                            });
                        } else {
                            toastr.error(response.message);
                        }
                    },
                    error: function(xhr) {
                        var message = xhr.responseJSON?.message || 'An error occurred';
                        toastr.error(message);
                    },
                    complete: function() {
                        btn.prop('disabled', false).html('<i class="fas fa-trash"></i>');
                    }
                });
            });
        });
    </script>
@endpush
