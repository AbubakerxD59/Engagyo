<div class="modal fade" id="fetchPostsModal" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLongTitle">Fetch Posts</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-4 form-group">
                        <label for="fetch_account">Accounts</label>
                        <select name="account" id="fetch_account" class="form-control" required>
                            <option value="">All Accounts</option>
                            @foreach ($user->getAccounts() as $key => $account)
                                <option value="{{ $account->id }}">{{ $account->username }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 form-group">
                        <label for="time">Time</label>
                        <input type="time" name="time" id="time" class="form-control" required>
                    </div>
                    <div class="col-md-4 form-group">
                        <label for="feed_url">Feed Url</label>
                        <input type="text" name="feed_url" id="feed_url" class="form-control" required>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-primary" id="fetchPostsBtn">Fetch</button>
                <button type="button" class="btn btn-outline-danger" data-dismiss="modal"
                    aria-label="Close">Close</button>
            </div>
        </div>
    </div>
</div>
