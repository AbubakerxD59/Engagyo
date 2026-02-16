{{-- Create Short Link Modal --}}
<div class="modal fade" id="createShortLinkModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-compress-alt mr-2"></i> Shorten a Link
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="createShortLinkForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="createOriginalUrl">Original URL <span class="text-danger">*</span></label>
                        <input type="url" class="form-control" id="createOriginalUrl" name="original_url"
                            placeholder="https://example.com/your-long-url" required>
                        <small class="form-text text-muted">
                            Enter the full URL you want to shorten (e.g. https://example.com/page).
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="createShortLinkBtn">
                        <i class="fas fa-link mr-1"></i> Create Short Link
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
