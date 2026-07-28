<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php if (empty($tasks)) { ?>
<p class="text-muted"><?= _l('dm_portal_no_pending_tasks'); ?></p>
<?php } else { ?>
<div class="table-responsive">
<table class="table">
    <thead>
        <tr>
            <th><?= _l('dm_portal_column_task'); ?></th>
            <th><?= _l('dm_portal_column_project'); ?></th>
            <th><?= _l('dm_portal_column_priority'); ?></th>
            <th><?= _l('dm_portal_column_deadline'); ?></th>
            <th><?= _l('dm_portal_col_assigned_date'); ?></th>
            <th><?= _l('dm_portal_column_progress'); ?></th>
            <th><?= _l('dm_portal_column_status'); ?></th>
            <th><?= _l('dm_portal_column_actions'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($tasks as $task) {
            $task_id  = (int) $task['id'];
            $progress = dm_portal_task_progress_percent($task['status']);
        ?>
        <tr id="dm-dash-pending-<?= $task_id; ?>">
            <td><?= e($task['name']); ?></td>
            <td><?= $task['project_name'] ? e($task['project_name']) : '-'; ?></td>
            <td><span class="label" style="color:<?= task_priority_color($task['priority']); ?>;"><?= e(task_priority($task['priority'])); ?></span></td>
            <td><?= $task['duedate'] ? e(_d($task['duedate'])) : '-'; ?></td>
            <td><?= $task['dateadded'] ? e(_d($task['dateadded'])) : '-'; ?></td>
            <td style="min-width:110px;">
                <div class="progress" style="margin-bottom:0;"><div class="progress-bar" role="progressbar" style="width:<?= $progress; ?>%;"><?= $progress; ?>%</div></div>
            </td>
            <td><?= format_task_status($task['status']); ?></td>
            <td class="tw-flex tw-items-center tw-gap-1">
                <button type="button" class="btn btn-default btn-sm" title="<?= _l('dm_portal_action_view_details'); ?>" onclick="dm_dash_view_task(<?= $task_id; ?>)"><i class="fa-solid fa-eye"></i></button>
                <select class="form-control input-sm dm-status-select" style="width:auto;display:inline-block;" onchange="dm_dash_update_status(<?= $task_id; ?>, this.value)">
                    <?php foreach ($statuses as $s) {
                        if ((int) $s['id'] === Tasks_model::STATUS_COMPLETE) {
                            continue;
                        }
                    ?>
                    <option value="<?= (int) $s['id']; ?>" <?= (int) $s['id'] === (int) $task['status'] ? 'selected' : ''; ?>><?= e($s['name']); ?></option>
                    <?php } ?>
                </select>
                <button type="button" class="btn btn-success btn-sm" title="<?= _l('dm_portal_action_mark_complete'); ?>" onclick="dm_dash_mark_complete(<?= $task_id; ?>)"><i class="fa-solid fa-check"></i></button>
            </td>
        </tr>
        <?php } ?>
    </tbody>
</table>
</div>
<?php } ?>
