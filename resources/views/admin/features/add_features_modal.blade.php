<div class="modal fade" id="add_features_modal" style="padding-right: 17px;" aria-modal="true" role="dialog">
    <div class="modal-dialog">
        <form method="POST" id="featureForm">
            @csrf
            <input type="hidden" id="feature_id" value="">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Add Service</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="card-body">
                        <div class="form-group row">
                            <div class="col-md-3">
                                <label for="name" class="form-label">Name</label>
                            </div>
                            <div class="col-md-9">
                                <input type="text" class="form-control" name="name" id="feature_name"
                                    placeholder="Enter feature name" value="{{ old('name') }}" required>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="saveBtn">Save</button>
                </div>
            </div>
        </form>
        <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
</div>
