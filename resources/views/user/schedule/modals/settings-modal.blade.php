<div class="modal fade settings-modal" tabindex="-1" aria-labelledby="myLargeModalLabel" style="display: none;"
    aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLongTitle">QUEUE SETTINGS</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body"></div>
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <td>Channel Name</td>
                        <td>Posting Hour</td>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($accounts as $account)
                        <tr>
                            <td>
                                <div>
                                    @if ($account->type == 'facebook')
                                        <img style="width:35px;height:35px;"
                                            src="{{ $account->facebook?->profile_image }}" class="rounded-circle"
                                            alt="{{ social_logo('facebook') }}"
                                            onerror="this.onerror=null; this.src='{{ social_logo('facebook') }}';">
                                        <img src="{{ social_logo('facebook') }}" alt=""
                                            style="width: 15px; position:relative;">
                                    @elseif($account->type == 'pinterest')
                                        <img style="width:35px;height:35px;"
                                            src="{{ $account->pinterest?->profile_image }}" class="rounded-circle"
                                            alt="{{ social_logo('pinterest') }}"
                                            onerror="this.onerror=null; this.src='{{ social_logo('pinterest') }}';">
                                        <img src="{{ social_logo('pinterest') }}" alt=""
                                            style="width: 15px; position:relative;">
                                    @endif
                                    <b>{{ $account->name }}</b>
                                </div>
                            </td>
                            <td>
                                @php $account_timeslots = $account->timeslots->pluck("timeslot")->toArray(); @endphp
                                <select name="time[]" class="form-control select2 timeslot"
                                    data-id="{{ $account->id }}" data-type="{{ $account->type }}" multiple>
                                    @foreach (timeslots() as $timeslot)
                                        <option value="{{ $timeslot }}"
                                            {{ in_array($timeslot, $account_timeslots) ? 'selected' : '' }}>
                                            {{ $timeslot }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline-danger" data-dismiss="modal" aria-label="Close">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>
