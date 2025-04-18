  <div class="modal fade" id="connectPinterestModal" tabindex="-1" role="dialog" aria-labelledby="connectPinterestModal"
      aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered" role="document">
          <div class="modal-content">
              <div class="modal-header">
                  <h5 class="modal-title" id="exampleModalLongTitle">
                      <img src="{{ social_logo(session_get('account')) }}" alt="{{ no_image() }}"
                          class="rounded-pill" height="50px" width="50px">
                      {{ session_get('account') }}
                  </h5>
                  <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                      <span aria-hidden="true">&times;</span>
                  </button>
              </div>
              <div class="modal-body">
                  <p class="text-muted">Select the accounts that you want to add.</p>
                  @foreach (session_get('items') as $item)
                      <div class="row justify-content-between">
                          <div class="d-flex">
                              <img src="{{ $pinterest->profile_image }}" alt="{{ social_logo(session_get('account')) }}"
                                  class="rounded-pill mr-3" height="25px" width="25px">
                              <p>{{ $item["name"] }}</p>
                          </div>
                          <div>
                              <button class="btn btn-primary btn-sm">Connect</button>
                          </div>
                      </div>
                  @endforeach
              </div>
              <div class="modal-footer">
                  <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                  <button type="button" class="btn btn-primary">Save</button>
              </div>
          </div>
      </div>
  </div>
