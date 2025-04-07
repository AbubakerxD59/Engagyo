<div class="modal fade" id="add_features_modal" style="padding-right: 17px;" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-lg">
        <form method="POST" id="featureForm">
            @csrf
            <input type="hidden" id="package_id" value="{{ $package->id }}">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Add Services</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="card-body">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th><input type="checkbox" id="check_all"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($features as $feature)
                                    <tr>
                                        <td>
                                            <label for="{{ $feature->id }}">{{ $feature->name }}</label>
                                        </td>
                                        <td>
                                            <input type="checkbox" id="{{ $feature->id }}"
                                                class="form-check input feature_id" name="feature_id[]"
                                                value="{{ $feature->id }}"
                                                {{ in_array($feature->id, $selected_features) ? 'checked' : '' }}>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
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
