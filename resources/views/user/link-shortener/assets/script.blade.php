<script>
    $(document).ready(function() {
        const storeUrl = "{{ route('panel.link-shortener.store') }}";
        const platformUrlShortenerStatusUrl = "{{ route('panel.link-shortener.platform.urlShortenerStatus') }}";

        // Platform URL shortener - multi-select dropdown
        $('#urlShortenerPlatforms').on('change', function() {
            var platforms = $(this).val() || [];

            $.ajax({
                url: platformUrlShortenerStatusUrl,
                type: 'POST',
                data: {
                    platforms: platforms,
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                    } else {
                        toastr.error(response.message || 'Something went wrong');
                    }
                },
                error: function(xhr) {
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Something went wrong';
                    toastr.error(msg);
                }
            });
        });

        const updateUrlBase = "{{ route('panel.link-shortener.update', ['id' => 0]) }}";
        const destroyUrlBase = "{{ route('panel.link-shortener.destroy', ['id' => 0]) }}";

        // Reset create form when modal is closed
        $('#createShortLinkModal').on('hidden.bs.modal', function() {
            $('#createShortLinkForm')[0].reset();
        });

        // Create short link
        $('#createShortLinkForm').on('submit', function(e) {
            e.preventDefault();
            const $btn = $('#createShortLinkBtn');
            const originalHtml = $btn.html();
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Creating...');

            $.ajax({
                url: storeUrl,
                type: 'POST',
                data: {
                    original_url: $('#createOriginalUrl').val().trim(),
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        $('#createShortLinkModal').modal('hide');
                        setTimeout(function() {
                            location.reload();
                        }, 800);
                    } else {
                        toastr.error(response.message);
                        $btn.prop('disabled', false).html(originalHtml);
                    }
                },
                error: function(xhr) {
                    let message = 'An error occurred';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                        const errors = xhr.responseJSON.errors;
                        message = Object.values(errors).flat().join(', ');
                    }
                    toastr.error(message);
                    $btn.prop('disabled', false).html(originalHtml);
                }
            });
        });

        // Copy short link to clipboard
        $(document).on('click', '.copy-btn', function() {
            const url = $(this).data('url');
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(url).then(function() {
                    toastr.success('Short link copied to clipboard!');
                }).catch(function() {
                    fallbackCopy(url);
                });
            } else {
                fallbackCopy(url);
            }
        });

        function fallbackCopy(text) {
            const $input = $('<input>').val(text).appendTo('body').select();
            try {
                document.execCommand('copy');
                toastr.success('Short link copied to clipboard!');
            } catch (err) {
                toastr.error('Could not copy. Please select and copy manually.');
            }
            $input.remove();
        }

        // Open edit modal
        $(document).on('click', '.edit-link-btn', function() {
            const id = $(this).data('id');
            const originalUrl = $(this).data('original-url');
            $('#editLinkId').val(id);
            $('#editOriginalUrl').val(originalUrl);
            $('#editShortLinkModal').modal('show');
        });

        // Reset edit form when modal is closed
        $('#editShortLinkModal').on('hidden.bs.modal', function() {
            $('#editShortLinkForm')[0].reset();
            $('#editLinkId').val('');
        });

        // Update short link
        $('#editShortLinkForm').on('submit', function(e) {
            e.preventDefault();
            const id = $('#editLinkId').val();
            const $btn = $('#editShortLinkBtn');
            const originalHtml = $btn.html();
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Updating...');

            $.ajax({
                url: updateUrlBase.replace(/\/0$/, '/' + id),
                type: 'POST',
                data: {
                    original_url: $('#editOriginalUrl').val().trim(),
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    _method: 'PUT'
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        $('#editShortLinkModal').modal('hide');
                        setTimeout(function() {
                            location.reload();
                        }, 800);
                    } else {
                        toastr.error(response.message);
                        $btn.prop('disabled', false).html(originalHtml);
                    }
                },
                error: function(xhr) {
                    let message = 'An error occurred';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                        const errors = xhr.responseJSON.errors;
                        message = Object.values(errors).flat().join(', ');
                    }
                    toastr.error(message);
                    $btn.prop('disabled', false).html(originalHtml);
                }
            });
        });

        // Delete short link
        $(document).on('click', '.delete-link-btn', function() {
            const id = $(this).data('id');
            const originalUrl = $(this).data('original-url');

            if (!confirm('Are you sure you want to delete this short link?\n\n' + originalUrl)) {
                return;
            }

            $.ajax({
                url: destroyUrlBase.replace(/\/0$/, '/' + id),
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    _method: 'DELETE'
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        $('tr[data-id="' + id + '"]').fadeOut(300, function() {
                            $(this).remove();
                        });
                    } else {
                        toastr.error(response.message || 'Failed to delete');
                    }
                },
                error: function(xhr) {
                    const message = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON
                        .message : 'Failed to delete short link';
                    toastr.error(message);
                }
            });
        });
    });
</script>
