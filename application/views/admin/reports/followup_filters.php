<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
// Shared filter toolbar for every Follow-up Management report (Phase 8) -
// one partial instead of duplicating this form in 5 separate report views.
// $action, $filters, $departments are always passed; $members is optional
// (only reports with a Staff filter pass it).
?>
<div class="panel_s">
    <div class="panel-body">
        <form method="get" class="row">
            <div class="col-md-2">
                <?= render_date_input('date_from', 'followup_filter_date_from', $filters['date_from_raw']); ?>
            </div>
            <div class="col-md-2">
                <?= render_date_input('date_to', 'followup_filter_date_to', $filters['date_to_raw']); ?>
            </div>
            <div class="col-md-2">
                <select name="department_id" class="selectpicker" data-width="100%"
                    data-none-selected-text="<?= _l('followup_filter_all_departments'); ?>">
                    <option value=""><?= _l('followup_filter_all_departments'); ?></option>
                    <?php foreach ($departments as $department) { ?>
                    <option value="<?= e($department['id']); ?>" <?= $filters['department_id'] == $department['id'] ? 'selected' : ''; ?>><?= e($department['name']); ?></option>
                    <?php } ?>
                </select>
            </div>
            <?php if (isset($members)) { ?>
            <div class="col-md-2">
                <select name="staff_id" class="selectpicker" data-live-search="true" data-width="100%"
                    data-none-selected-text="<?= _l('followup_filter_all_staff'); ?>">
                    <option value=""><?= _l('followup_filter_all_staff'); ?></option>
                    <?php foreach ($members as $member) { ?>
                    <option value="<?= e($member['staffid']); ?>" <?= $filters['staff_id'] == $member['staffid'] ? 'selected' : ''; ?>><?= e($member['firstname'] . ' ' . $member['lastname']); ?></option>
                    <?php } ?>
                </select>
            </div>
            <?php } ?>
            <div class="col-md-2">
                <select name="priority" class="selectpicker" data-width="100%"
                    data-none-selected-text="<?= _l('followup_filter_all_priorities'); ?>">
                    <option value=""><?= _l('followup_filter_all_priorities'); ?></option>
                    <?php foreach (get_followup_priorities() as $key => $label) { ?>
                    <option value="<?= e($key); ?>" <?= $filters['priority'] === $key ? 'selected' : ''; ?>><?= e($label); ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="case_status" class="selectpicker" data-width="100%"
                    data-none-selected-text="<?= _l('followup_filter_all_statuses'); ?>">
                    <option value=""><?= _l('followup_filter_all_statuses'); ?></option>
                    <?php foreach (get_followup_statuses() as $key => $label) { ?>
                    <option value="<?= e($key); ?>" <?= $filters['case_status'] === $key ? 'selected' : ''; ?>><?= e($label); ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="col-md-2 tw-mt-2">
                <button type="submit" class="btn btn-primary"><?= _l('apply'); ?></button>
            </div>
        </form>
    </div>
</div>
