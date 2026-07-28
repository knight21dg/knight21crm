<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?= form_hidden('rel_id', $rel_id); ?>
<?= form_hidden('rel_type', $rel_type); ?>
<div class="row">
    <div class="col-md-6">
        <?= render_select('follow_up_type', get_followup_types(), ['id', 'name'], 'followup_type_label', (isset($followup) ? $followup->follow_up_type : ''), ['required' => 'required']); ?>
    </div>
    <div class="col-md-6">
        <?= render_select('outcome', get_followup_outcomes(), ['id', 'name'], 'followup_outcome_label', (isset($followup) ? $followup->outcome : '')); ?>
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <?= render_datetime_input('follow_up_date', 'followup_date_label', (isset($followup) ? $followup->follow_up_date : _dt(date('Y-m-d H:i:s'))), ['required' => 'required']); ?>
    </div>
    <div class="col-md-6">
        <?= render_datetime_input('next_follow_up_date', 'followup_next_follow_up_date_label', (isset($followup) ? $followup->next_follow_up_date : ''), ['data-date-min-date' => _d(date('Y-m-d'))]); ?>
    </div>
</div>
<?= render_select('staff_id', $members, ['staffid', ['firstname', 'lastname']], 'followup_staff_label', (isset($followup) ? $followup->staff_id : get_staff_user_id()), ['data-current-staff' => get_staff_user_id()]); ?>
<?= render_textarea('notes', 'followup_notes_label', (isset($followup) ? $followup->notes : '')); ?>
