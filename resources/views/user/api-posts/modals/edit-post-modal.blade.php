<div class="modal fade" id="editPostModal" tabindex="-1" aria-labelledby="editPostModalLabel" style="display: none;"
    aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editPostModalLabel">
                    <i class="fas fa-edit mr-2"></i>Edit API Post
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="" method="POST" id="edit_post_form" enctype="multipart/form-data">
                @csrf
                <div class="modal-body" id="edit_post_body">
                    <div class="text-center py-4">
                        <i class="fas fa-spinner fa-spin fa-2x"></i>
                        <p class="mt-2">Loading...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-dismiss="modal" aria-label="Close">
                        <i class="fas fa-times mr-1"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i> Update Post
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

