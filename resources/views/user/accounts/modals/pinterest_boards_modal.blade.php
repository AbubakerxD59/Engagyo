  <div class="modal fade" id="connectPinterestModal" tabindex="-1" role="dialog" aria-labelledby="connectPinterestModal"
      aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered" role="document">
          <div class="modal-content">
              <div class="modal-header">
                  <h5 class="modal-title" id="exampleModalLongTitle">
                      <img src="{{ social_logo(session_get('account')) }}" alt="{{ no_image() }}"
                          class="rounded-pill" height="25px" width="25px">
                      <span class="acc_title">{{ session_get('account') }}</span>
                  </h5>
                  <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                      <span aria-hidden="true">&times;</span>
                  </button>
              </div>
              <div class="modal-body">
                  <span class="text-muted">Select the accounts that you want to add.</span>
                  @foreach (session_get('items') as $key => $item)
                      <div class="d-flex justify-content-between item_count">
                          <div class="d-flex">
                              <img src="{{ @$pinterest->profile_image }}"
                                  alt="{{ social_logo(session_get('account')) }}" class="rounded-pill mr-2"
                                  height="25px" width="25px">
                              <span>{{ $item['name'] }}</span>
                          </div>
                          <div>
                              @if (@$item['connected'])
                                  <span data-id="{{ $key }}"
                                      data-pin-id="{{ @$pinterest->pin_id }}">Connected</span>
                              @else
                                  <span class="pinterest_connect pointer" data-id="{{ $key }}"
                                      data-pin-id="{{ @$pinterest->pin_id }}">Connect</span>
                              @endif
                          </div>
                      </div>
                  @endforeach
              </div>
              <div class="modal-footer">
                  <button type="button" class="btn btn-primary" data-dismiss="modal"
                      aria-label="Close">Continue</button>
              </div>
          </div>
      </div>
  </div>
