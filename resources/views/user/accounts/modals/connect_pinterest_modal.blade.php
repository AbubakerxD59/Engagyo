  <div class="modal fade" id="connectPinterestModal" tabindex="-1" role="dialog" aria-labelledby="connectPinterestModal"
      aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered" role="document">
          <div class="modal-content">
              <div class="modal-header">
                  <h5 class="modal-title" id="exampleModalLongTitle">
                      <img src="{{ social_logo(session_get('account')) }}" alt="{{ no_image() }}"
                          class="rounded-pill" height="25px" width="25px">
                      <p class="acc_title">{{ session_get('account') }}</p>
                  </h5>
                  <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                      <span aria-hidden="true">&times;</span>
                  </button>
              </div>
              <div class="modal-body">
                  <span class="text-muted">Select the accounts that you want to add.</span>
                  @foreach (session_get('items') as $item)
                      <div class="d-flex justify-content-between item_count">
                          <div class="d-flex">
                              <img src="{{ $pinterest->profile_image }}" alt="{{ social_logo(session_get('account')) }}"
                                  class="rounded-pill mr-3" height="25px" width="25px">
                              <p>{{ $item['name'] }}</p>
                          </div>
                          <div>
                              <span class="pinterest_connect" data-id="{{ $item['id'] }}">Connect</span>
                          </div>
                      </div>
                  @endforeach
              </div>
              <div class="modal-footer">
                  <button type="button" class="btn btn-primary btn-xs">Continue</button>
              </div>
          </div>
      </div>
  </div>
