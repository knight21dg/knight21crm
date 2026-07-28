<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">&times;</button>
    <h4 class="modal-title"><?= _l('staff_attendance_add'); ?></h4>
</div>
<form onsubmit="return attendance_submit_add(this);">
    <div class="modal-body">
        <div class="form-group">
            <label for="add-attendance-staff"><?= _l('staff_attendance_column_employee'); ?></label>
            <select id="add-attendance-staff" name="staff_id" class="selectpicker" data-width="100%" data-live-search="true" required>
                <option value=""></option>
                <?php foreach ($staff as $member) { ?>
                <option value="<?= (int) $member['staffid']; ?>"><?= e($member['firstname'] . ' ' . $member['lastname']); ?></option>
                <?php } ?>
            </select>
        </div>
        <div class="form-group">
            <?= render_date_input('attendance_date', 'staff_attendance_column_date', ''); ?>
        </div>
        <div class="form-group">
            <label for="add-attendance-status"><?= _l('staff_attendance_column_status'); ?></label>
            <select id="add-attendance-status" name="attendance_status" class="selectpicker" data-width="100%">
                <?php foreach (get_attendance_statuses() as $status) { ?>
                <option value="<?= e($status); ?>"><?= e($status); ?></option>
                <?php } ?>
            </select>
        </div>
        <div class="form-group">
            <label for="add-attendance-remarks"><?= _l('staff_attendance_column_remarks'); ?></label>
            <textarea id="add-attendance-remarks" name="remarks" class="form-control" rows="2"></textarea>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal"><?= _l('close'); ?></button>
        <button type="submit" class="btn btn-primary"><?= _l('save'); ?></button>
    </div>
</form>
