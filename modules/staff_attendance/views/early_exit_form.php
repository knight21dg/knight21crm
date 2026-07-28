<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">&times;</button>
    <h4 class="modal-title"><?= _l('staff_attendance_early_exit_detected'); ?></h4>
</div>
<?php if (!$record || !$record->logout_time || !staff_attendance_is_early_exit($record->logout_time)) { ?>
<div class="modal-body">
    <p class="text-muted"><?= _l('staff_attendance_request_not_eligible'); ?></p>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-default" data-dismiss="modal"><?= _l('close'); ?></button>
</div>
<?php } else { ?>
<form onsubmit="return attendance_submit_early_exit_request(this);" enctype="multipart/form-data">
    <div class="modal-body">
        <p><?= _l('staff_attendance_early_exit_please_explain'); ?></p>
        <div class="row">
            <div class="col-md-4">
                <label><?= _l('staff_attendance_column_date'); ?></label>
                <p class="tw-font-semibold"><?= _d($record->attendance_date); ?></p>
            </div>
            <div class="col-md-4">
                <label><?= _l('staff_attendance_column_logout'); ?></label>
                <p class="tw-font-semibold"><?= _dt($record->logout_time); ?></p>
            </div>
            <div class="col-md-4">
                <label><?= _l('staff_attendance_early_duration'); ?></label>
                <p class="tw-font-semibold"><?= (int) staff_attendance_early_minutes($record->logout_time); ?> <?= _l('staff_attendance_minutes'); ?></p>
            </div>
        </div>
        <div class="form-group">
            <label for="reason"><?= _l('staff_attendance_early_reason'); ?></label>
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
