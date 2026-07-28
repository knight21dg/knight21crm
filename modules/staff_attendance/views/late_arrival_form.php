<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">&times;</button>
    <h4 class="modal-title"><?= _l('staff_attendance_late_arrival_detected'); ?></h4>
</div>
<?php if (!$record || !staff_attendance_is_late_arrival($record->login_time)) { ?>
<div class="modal-body">
    <p class="text-muted"><?= _l('staff_attendance_request_not_eligible'); ?></p>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-default" data-dismiss="modal"><?= _l('close'); ?></button>
</div>
<?php } else { ?>
<form onsubmit="return attendance_submit_late_arrival_request(this);" enctype="multipart/form-data">
    <div class="modal-body">
        <p><?= _l('staff_attendance_late_arrival_please_explain'); ?></p>
        <div class="row">
            <div class="col-md-4">
                <label><?= _l('staff_attendance_column_date'); ?></label>
                <p class="tw-font-semibold"><?= _d($record->attendance_date); ?></p>
            </div>
            <div class="col-md-4">
                <label><?= _l('staff_attendance_column_login'); ?></label>
                <p class="tw-font-semibold"><?= _dt($record->login_time); ?></p>
            </div>
            <div class="col-md-4">
                <label><?= _l('staff_attendance_late_delay'); ?></label>
                <p class="tw-font-semibold"><?= (int) staff_attendance_late_minutes($record->login_time); ?> <?= _l('staff_attendance_minutes'); ?></p>
            </div>
        </div>
        <div class="form-group">
            <label for="reason"><?= _l('staff_attendance_late_reason'); ?></label>
            <textarea id="reason" name="reason" class="form-control" rows="3" required></textarea>
        </div>
        <div class="form-group">
            <label for="attachment"><?= _l('staff_attendance_leave_attachment'); ?></label>
            <input type="file" id="attachment" name="attachment" class="form-control" />
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal"><?= _l('close'); ?></button>
        <button type="submit" class="btn btn-primary"><?= _l('staff_attendance_submit_request'); ?></button>
    </div>
</form>
<?php } ?>
