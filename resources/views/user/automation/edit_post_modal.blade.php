<div class="modal fade" id="editPostModal" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLongTitle">Edit Post</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="" method="POST" id="editPostForm">
                <div class="modal-body">
                    <div class="card-body">
                        <input type="hidden" name="post_id" id="post_id">
                        <div class="form-group row">
                            <div class="col-md-3">
                                <label for="post_title">Title</label>
                            </div>
                            <div class="col-md-9">
                                <input type="text" name="post_title" id="post_title" class="form-control" required>
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-md-3">
                                <label for="post_url">Url</label>
                            </div>
                            <div class="col-md-9">
                                <input type="text" name="post_url" id="post_url" class="form-control" required>
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-md-3">
                                <label for="post_date">Date</label>
                            </div>
                            <div class="col-md-9">
                                <input type="date" name="post_date" id="post_date" class="form-control"
                                    onfocus="this.showPicker()" required>
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-md-3">
                                <label for="post_time">Time</label>
                            </div>
                            <div class="col-md-9">
                                <input type="time" name="post_time" id="post_time" class="form-control"
                                    onfocus="this.showPicker()" required>
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-md-3">
                                <label for="post_image">Image</label>
                            </div>
                            <div class="col-md-9">
                                <input type="file" name="post_image" id="post_image" class="form-control">
                                <img id="post_image_preview" class="rounded" width="100px">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-outline-primary update_post_btn">Submit</button>
                    <button type="button" class="btn btn-outline-danger" data-dismiss="modal"
                        aria-label="Close">Close</button>
                </div>
            </form>
        </div>
    </div>
</div>
