<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php if (!$project) { ?>
<p class="text-muted">-</p>
<?php } else { ?>
<h5 class="no-margin tw-mb-2"><?= _l('dm_portal_modal_description'); ?></h5>
<p class="tw-mb-3"><?= $project->description ? nl2br(e($project->description)) : '<span class="text-muted">-</span>'; ?></p>

<h5 class="no-margin tw-mb-2"><?= _l('dm_portal_team_members'); ?></h5>
<?php if (empty($team_members)) { ?>
<p class="text-muted tw-mb-3"><?= _l('dm_portal_no_team_members'); ?></p>
<?php } else { ?>
<ul class="list-unstyled tw-mb-3">
    <?php foreach ($team_members as $member) { ?>
    <li class="tw-mb-1"><i class="fa-solid fa-user tw-text-gray-400"></i> <?= e(trim($member['firstname'] . ' ' . $member['lastname'])); ?> <span class="text-muted tw-text-xs"><?= e($member['email']); ?></span></li>
    <?php } ?>
</ul>
<?php } ?>

<h5 class="no-margin tw-mb-2"><?= _l('dm_portal_modal_files'); ?></h5>
<?php if (empty($files)) { ?>
<p class="text-muted tw-mb-3"><?= _l('dm_portal_modal_no_files'); ?></p>
<?php } else { ?>
<ul class="list-unstyled tw-mb-3">
    <?php foreach ($files as $file) { ?>
    <li class="tw-mb-1"><i class="fa-regular fa-file tw-text-gray-400"></i> <?= e($file['file_name']); ?></li>
    <?php } ?>
</ul>
<?php } ?>

<h5 class="no-margin tw-mb-2"><?= _l('dm_portal_current_tasks'); ?></h5>
<?php if (empty($current_tasks)) { ?>
<p class="text-muted"><?= _l('dm_portal_no_current_tasks'); ?></p>
<?php } else { ?>
<ul class="list-unstyled">
    <?php foreach ($current_tasks as $task) { ?>
    <li class="tw-mb-2">
        <i class="fa-solid fa-list-check tw-text-gray-400"></i>
        <?= e($task['name']); ?>
        <span class="label" style="color:<?= task_priority_color($task['priority']); ?>;"><?= e(task_priority($task['priority'])); ?></span>
        <?= format_task_status($task['status']); ?>
        <?php if ($task['duedate']) { ?><span class="text-muted tw-text-xs"><?= e(_d($task['duedate'])); ?></span><?php } ?>
    </li>
    <?php } ?>
</ul>
<?php } ?>
<?php } ?>
