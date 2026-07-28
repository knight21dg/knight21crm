<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">&times;</button>
    <h4 class="modal-title"><?= $holiday ? _l('staff_attendance_holiday_edit') : _l('staff_attendance_holiday_add'); ?></h4>
</div>
<form onsubmit="return attendance_submit_holiday(this);">
    <div class="modal-body">
        <input type="hidden" name="id" value="<?= $holiday ? (int) $holiday->id : ''; ?>" />
        <div class="form-group">
            <label for="name"><?= _l('staff_attendance_holiday_column_name'); ?></label>
            <input type="text" id="name" name="name" class="form-control" value="<?= $holiday ? e($holiday->name) : ''; ?>" required />
        </div>
        <?= render_date_input('date', 'staff_attendance_holiday_column_date', $holiday ? _d($holiday->date) : '', ['required' => true]); ?>
        <div class="form-group">
            <label for="description"><?= _l('staff_attendance_holiday_column_description'); ?></label>
            <textarea id="description" name="description" class="form-control" rows="2"><?= $holiday ? e($holiday->description) : ''; ?></textarea>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal"><?= _l('close'); ?></button>
        <button type="submit" class="btn btn-primary"><?= _l('save'); ?></button>
    </div>
</form>
