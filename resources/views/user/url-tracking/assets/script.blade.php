<script>
    $(document).ready(function() {
        // Show all rows when modal opens
        $('#createUtmModal').on('show.bs.modal', function() {
            // Always show all rows
            $('.utm-code-row').show();
        });

        // Reset form when modal is closed
        $('#createUtmModal').on('hidden.bs.modal', function() {
            $('#utmCodeForm')[0].reset();
            $('#utmCodeId').val('');
            $('#modalTitle').text('Add UTM Code');
            $('#saveUtmBtn').html('<i class="fas fa-save mr-1"></i> Save UTM Code');
            // Show all rows for create mode
            $('.utm-code-row').show();
            // Reset readonly fields
            $('.utm-value-input[readonly]').each(function() {
                const defaultValue = $(this).data('default-value') || $(this).attr('value');
                if (defaultValue) {
                    $(this).val(defaultValue);
                }
            });
            // Remove readonly from all inputs (in case edit mode set them)
            $('.utm-value-input').prop('readonly', function() {
                return $(this).data('key') === 'utm_source';
            });
        });

        // Create/Update UTM Code
        $('#utmCodeForm').on('submit', function(e) {
            e.preventDefault();

            // Collect all UTM codes
            const domainName = $('#domainName').val();
            const utmCodes = [];

            $('.utm-code-row').each(function() {
                const key = $(this).data('utm-key');
                const value = $(this).find('.utm-value-input').val();
                if (value && value.trim() !== '') {
                    utmCodes.push({
                        key: key,
                        value: value.trim()
                    });
                }
            });

            if (utmCodes.length === 0) {
                toastr.error('Please provide at least one UTM value');
                return;
            }

            // Disable submit button to prevent double submission
            const $submitBtn = $('#saveUtmBtn');
            const originalText = $submitBtn.html();
            $submitBtn.prop('disabled', true).html(
                '<i class="fas fa-spinner fa-spin mr-1"></i> Saving...');

            $.ajax({
                url: "{{ route('panel.url-tracking.store') }}",
                type: 'POST',
                data: {
                    domain_name: domainName,
                    utm_codes: utmCodes,
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        $('#createUtmModal').modal('hide');
                        setTimeout(() => {
                            location.reload();
                        }, 1000);
                    } else {
                        toastr.error(response.message);
                        $submitBtn.prop('disabled', false).html(originalText);
                    }
                },
                error: function(xhr) {
                    let message = 'An error occurred';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                        // Handle validation errors
                        const errors = xhr.responseJSON.errors;
                        message = Object.values(errors).flat().join(', ');
                    }
                    toastr.error(message);
                    $submitBtn.prop('disabled', false).html(originalText);
                }
            });
        });

        // Initialize DataTable if table exists
        if ($('#utmCodesTable').length) {
            $('#utmCodesTable').DataTable({
                "paging": true,
                "lengthChange": true,
                "searching": true,
                "ordering": true,
                "info": true,
                "autoWidth": false,
                "responsive": true,
                "pageLength": 10,
                "order": [[0, 'asc']]
            });
        }

        // Edit Domain UTM Codes
        $(document).on('click', '.edit-domain-btn', function() {
            const domainName = $(this).data('domain');
            
            // Show loading
            const $btn = $(this);
            const originalHtml = $btn.html();
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Loading...');

            $.ajax({
                url: "{{ route('panel.url-tracking.getByDomain') }}",
                type: 'POST',
                data: {
                    domain_name: domainName,
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                dataType: 'json',
                success: function(response) {
                    $btn.prop('disabled', false).html(originalHtml);
                    
                    if (response.success) {
                        // Reset form
                        $('#utmCodeForm')[0].reset();
                        $('#utmCodeId').val('');
                        
                        // Set domain name
                        $('#domainName').val(response.domain_name);
                        
                        // Show all rows
                        $('.utm-code-row').show();
                        
                        // Populate all UTM codes
                        const utmCodesMap = {};
                        response.data.forEach(function(code) {
                            utmCodesMap[code.utm_key] = code.utm_value;
                        });
                        
                        // Fill in values for each UTM key
                        $('.utm-code-row').each(function() {
                            const key = $(this).data('utm-key');
                            const valueInput = $(this).find('.utm-value-input');
                            
                            if (utmCodesMap.hasOwnProperty(key)) {
                                valueInput.val(utmCodesMap[key]);
                                // Make editable (remove readonly for utm_source if needed)
                                if (key === 'utm_source') {
                                    valueInput.prop('readonly', false);
                                }
                            } else {
                                // Clear if not found
                                if (key === 'utm_source') {
                                    valueInput.val('Engagyo');
                                    valueInput.prop('readonly', true);
                                } else {
                                    valueInput.val('');
                                }
                            }
                        });
                        
                        $('#modalTitle').text('Edit UTM Codes for ' + response.domain_name);
                        $('#saveUtmBtn').html('<i class="fas fa-save mr-1"></i> Update UTM Codes');
                        $('#createUtmModal').modal('show');
                    } else {
                        toastr.error(response.message || 'Failed to load UTM codes');
                    }
                },
                error: function(xhr) {
                    $btn.prop('disabled', false).html(originalHtml);
                    let message = 'Failed to load UTM codes';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    }
                    toastr.error(message);
                }
            });
        });


        // Delete All UTM Codes for Domain
        $(document).on('click', '.delete-domain-btn', function() {
            const domainName = $(this).data('domain');
            if (confirm(`Are you sure you want to delete all UTM codes for "${domainName}"?`)) {
                $.ajax({
                    url: "{{ route('panel.url-tracking.deleteAllDomain') }}",
                    type: 'POST',
                    data: {
                        domain_name: domainName
                    },
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message);
                            setTimeout(() => {
                                location.reload();
                            }, 1000);
                        } else {
                            toastr.error(response.message);
                        }
                    },
                    error: function() {
                        toastr.error('Failed to delete UTM codes');
                    }
                });
            }
        });
    });
</script>
