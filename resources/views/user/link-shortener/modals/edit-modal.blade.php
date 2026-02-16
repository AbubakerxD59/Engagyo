{{-- Edit Short Link Modal --}}
<div class="modal fade" id="editShortLinkModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-edit mr-2"></i> Edit Short Link
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="editShortLinkForm">
                <input type="hidden" id="editLinkId" name="id">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="editOriginalUrl">Destination URL <span class="text-danger">*</span></label>
                        <input type="url" class="form-control" id="editOriginalUrl" name="original_url"
                            placeholder="https://example.com/your-long-url" required>
                        <small class="form-text text-muted">
                            The short link will redirect to this URL. The short link itself cannot be changed.
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="editShortLinkBtn">
                        <i class="fas fa-save mr-1"></i> Update
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
