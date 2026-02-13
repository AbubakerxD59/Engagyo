<script>
    $(document).ready(function() {
        // Show all rows when modal opens
        $('#createUtmModal').on('show.bs.modal', function() {
            // Always show all rows
            $('.utm-code-row').show();
        });

        // Toggle custom value field when UTM value dropdown changes
        $(document).on('change', '.utm-value-input', function() {
            const $select = $(this);
            if ($select.is('select')) {
                const $row = $select.closest('.utm-code-row');
                const $valueCol = $row.find('.utm-value-col');
                const $customCol = $row.find('.utm-custom-value-col');
                if ($select.val() === 'custom') {
                    $valueCol.removeClass('col-md-8').addClass('col-md-4');
                    $customCol.removeClass('d-none').addClass('col-md-4');
                } else {
                    $valueCol.removeClass('col-md-4').addClass('col-md-8');
                    $customCol.addClass('d-none').removeClass('col-md-4');
                }
            }
        });

        // Reset form when modal is closed
        $('#createUtmModal').on('hidden.bs.modal', function() {
            $('#utmCodeForm')[0].reset();
            $('#utmCodeId').val('');
            $('#modalTitle').text('Add UTM Code');
            $('#saveUtmBtn').html('<i class="fas fa-save mr-1"></i> Save UTM Code');
            // Show all rows for create mode
            $('.utm-code-row').show();
            // Reset custom value columns: hide and restore value column width
            $('.utm-value-col').removeClass('col-md-4').addClass('col-md-8');
            $('.utm-custom-value-col').addClass('d-none').removeClass('col-md-4');
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
                const valueInput = $(this).find('.utm-value-input');
                let value = valueInput.val();
                // Use custom value input when "Custom" is selected
                if (valueInput.is('select') && value === 'custom') {
                    const customVal = $(this).find('.utm-custom-value-input').val();
                    value = (customVal && customVal.trim() !== '') ? customVal.trim() : '';
                } else if (value) {
                    value = value.trim();
                }
                if (value !== '') {
                    utmCodes.push({
                        key: key,
                        value: value
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
                            const $valueCol = $(this).find('.utm-value-col');
                            const $customCol = $(this).find('.utm-custom-value-col');
                            
                            if (utmCodesMap.hasOwnProperty(key)) {
                                const savedValue = utmCodesMap[key];
                                if (key === 'utm_source') {
                                    valueInput.val(savedValue);
                                    valueInput.prop('readonly', true);
                                } else if (valueInput.is('select')) {
                                    const optionExists = valueInput.find('option').filter(function() { return $(this).val() === savedValue; }).length > 0;
                                    if (optionExists) {
                                        valueInput.val(savedValue);
                                        $valueCol.removeClass('col-md-4').addClass('col-md-8');
                                        $customCol.addClass('d-none').removeClass('col-md-4');
                                    } else {
                                        valueInput.val('custom');
                                        $(this).find('.utm-custom-value-input').val(savedValue);
                                        $valueCol.removeClass('col-md-8').addClass('col-md-4');
                                        $customCol.removeClass('d-none').addClass('col-md-4');
                                    }
                                }
                            } else {
                                if (key === 'utm_source') {
                                    valueInput.val('Engagyo');
                                    valueInput.prop('readonly', true);
                                } else {
                                    valueInput.val('');
                                    $valueCol.removeClass('col-md-4').addClass('col-md-8');
                                    $customCol.addClass('d-none').removeClass('col-md-4');
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
