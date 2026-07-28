<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php if (empty($tasks)) { ?>
<p class="text-muted"><?= _l('dm_portal_dashboard_no_today_tasks'); ?></p>
<?php } else { ?>
<div class="table-responsive">
<table class="table">
    <thead>
        <tr>
            <th><?= _l('dm_portal_column_task'); ?></th>
            <th><?= _l('dm_portal_column_project'); ?></th>
            <th><?= _l('dm_portal_column_priority'); ?></th>
            <th><?= _l('dm_portal_col_due_date'); ?></th>
            <th><?= _l('dm_portal_column_status'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($tasks as $task) { ?>
        <tr>
            <td><?= e($task['name']); ?></td>
            <td><?= $task['project_name'] ? e($task['project_name']) : '-'; ?></td>
            <td><span class="label" style="color:<?= task_priority_color($task['priority']); ?>;"><?= e(task_priority($task['priority'])); ?></span></td>
            <td><?= $task['duedate'] ? e(_d($task['duedate'])) : '-'; ?></td>
            <td><?= format_task_status($task['status']); ?></td>
        </tr>
        <?php } ?>
    </tbody>
</table>
</div>
<?php } ?>
