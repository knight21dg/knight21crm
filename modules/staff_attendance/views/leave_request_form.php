<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">&times;</button>
    <h4 class="modal-title"><?= _l('staff_attendance_request_leave'); ?></h4>
</div>
<form onsubmit="return attendance_submit_leave_request(this);" enctype="multipart/form-data">
    <div class="modal-body">
        <div class="form-group">
            <label for="leave_type"><?= _l('staff_attendance_leave_type'); ?></label>
            <select id="leave_type" name="leave_type" class="selectpicker" data-width="100%" required>
                <option value=""></option>
                <?php foreach ($leave_types as $type) { ?>
                <option value="<?= e($type); ?>"><?= e($type); ?></option>
                <?php } ?>
            </select>
        </div>
        <div class="row">
            <div class="col-md-6">
                <?= render_date_input('start_date', 'staff_attendance_leave_start_date', '', ['required' => true]); ?>
            </div>
            <div class="col-md-6">
                <?= render_date_input('end_date', 'staff_attendance_leave_end_date', '', ['required' => true]); ?>
            </div>
        </div>
        <p class="text-muted"><i class="fa-regular fa-circle-question"></i> <?= _l('staff_attendance_leave_days_hint'); ?></p>
        <div class="form-group">
            <label for="reason"><?= _l('staff_attendance_leave_reason'); ?></label>
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
