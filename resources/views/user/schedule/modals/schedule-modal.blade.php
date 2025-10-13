<div class="modal fade schedule-modal" tabindex="-1" aria-labelledby="myLargeModalLabel" style="display: none;"
    aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLongTitle">SCHEDULE POST</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">
                <p class="h5">Select date/time to Shedule Post!</p>
                <div class="d-flex justify-content-center">
                    <div class="col-md-4">
                        <label for="schedule_date">Date</label>
                        <div>
                            <input type="date" name="schedule_date" id="schedule_date" value="{{ date('Y-m-d') }}"
                                min="{{ date('Y-m-d') }}" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label for="schedule_time">Time</label>
                        <div>
                            <input type="time" name="schedule_time" id="schedule_time" class="form-control">
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-danger" data-dismiss="modal" aria-label="Close">
                    Close
                </button>
                <button type="button" class="btn btn-outline-info schedule_btn">
                    Schedule
                </button>
            </div>
        </div>
    </div>
</div>
