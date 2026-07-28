<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php if (empty($tasks)) { ?>
<p class="text-muted"><?= _l('dm_portal_no_completed_tasks'); ?></p>
<?php } else { ?>
<div class="table-responsive">
<table class="table">
    <thead>
        <tr>
            <th><?= _l('dm_portal_column_task'); ?></th>
            <th><?= _l('dm_portal_column_project'); ?></th>
            <th><?= _l('dm_portal_col_completed_date'); ?></th>
            <th><?= _l('dm_portal_col_final_progress'); ?></th>
            <th><?= _l('dm_portal_col_notes'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($tasks as $task) { ?>
        <tr>
            <td><?= e($task['name']); ?></td>
            <td><?= $task['project_name'] ? e($task['project_name']) : '-'; ?></td>
            <td><?= $task['datefinished'] ? e(_dt($task['datefinished'])) : '-'; ?></td>
            <td>100%</td>
            <td><?= $task['description'] ? e(mb_strimwidth(strip_tags($task['description']), 0, 120, '...')) : '<span class="text-muted">' . _l('dm_portal_no_notes') . '</span>'; ?></td>
        </tr>
        <?php } ?>
    </tbody>
</table>
</div>
<?php } ?>
