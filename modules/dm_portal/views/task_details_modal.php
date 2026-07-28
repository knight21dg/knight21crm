<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<script>
(function () {
    // Guarded - this partial is reused inside the Dashboard's own modal
    // (dashboard_pending_tasks_modal.php's "View" action), which has no
    // #dm-task-modal-title element of its own; a no-op there is correct,
    // My Work's own #dm-task-modal still gets its title set as before.
    var titleEl = document.getElementById('dm-task-modal-title');
    if (titleEl) {
        titleEl.textContent = <?= json_encode($task->name); ?>;
    }
})();
</script>

<?php if ($project) { ?>
<h5 class="no-margin tw-mb-2"><?= _l('dm_portal_modal_project_info'); ?></h5>
<div class="row tw-mb-3">
    <div class="col-sm-4"><span class="text-muted"><?= _l('dm_portal_column_project'); ?>:</span> <?= e($project->name); ?></div>
    <div class="col-sm-4"><span class="text-muted"><?= _l('dm_portal_modal_customer'); ?>:</span> <?= $client_name ? e($client_name) : '-'; ?></div>
    <div class="col-sm-4"><span class="text-muted"><?= _l('dm_portal_modal_deadline'); ?>:</span> <?= $project->deadline ? e(_d($project->deadline)) : '-'; ?></div>
</div>

<h5 class="no-margin tw-mb-2"><?= _l('dm_portal_modal_assigned_work'); ?></h5>
<p class="tw-mb-3"><?= $project->assigned_work ? e($project->assigned_work) : '<span class="text-muted">' . _l('dm_portal_modal_no_assigned_work') . '</span>'; ?></p>
<?php } else { ?>
<p class="text-muted tw-mb-3"><?= _l('dm_portal_modal_not_linked'); ?></p>
<?php } ?>

<hr class="hr-panel-separator" />

<h5 class="no-margin tw-mb-2"><?= _l('dm_portal_modal_task_details'); ?></h5>
<div class="row tw-mb-2">
    <div class="col-sm-4"><span class="text-muted"><?= _l('dm_portal_column_priority'); ?>:</span> <span class="label" style="color:<?= task_priority_color($task->priority); ?>;"><?= e(task_priority($task->priority)); ?></span></div>
    <div class="col-sm-4"><span class="text-muted"><?= _l('dm_portal_column_status'); ?>:</span> <?= format_task_status($task->status); ?></div>
    <div class="col-sm-4"><span class="text-muted"><?= _l('dm_portal_modal_estimated_hours'); ?>:</span> <?= $task->estimated_hours !== null ? e($task->estimated_hours) . 'h' : '-'; ?></div>
</div>
<div class="tw-mb-3">
    <span class="text-muted"><?= _l('dm_portal_modal_description'); ?>:</span>
    <?php if (!empty($task->description)) {
        // Description is TinyMCE-authored HTML, sanitized at save time by
        // Tasks::add()/update_task_description() via html_purify()
        // (application/controllers/admin/Tasks.php) - by the time it's
        // read back here it's already safe HTML, so it's rendered the
        // same way the native Task View renders it
        // (application/views/admin/tasks/view_task_template.php),
        // check_for_links() and no further escaping. Escaping it again
        // here (e()/htmlspecialchars) would print the tags literally
        // instead of rendering them, which was the actual bug.
        ?>
    <div><?= check_for_links($task->description); ?></div>
    <?php } else { ?>
    <div class="text-muted"><?= _l('task_no_description'); ?></div>
    <?php } ?>
</div>

<hr class="hr-panel-separator" />

<h5 class="no-margin tw-mb-2"><?= _l('dm_portal_modal_files'); ?></h5>
<?php if (empty($task->attachments)) { ?>
<p class="text-muted"><?= _l('dm_portal_modal_no_files'); ?></p>
<?php } else { ?>
<ul class="list-unstyled tw-mb-2">
    <?php foreach ($task->attachments as $file) { ?>
    <li class="tw-mb-1"><i class="fa-regular fa-file tw-text-gray-400"></i> <?= e($file['file_name']); ?></li>
    <?php } ?>
</ul>
<?php } ?>
<div class="tw-flex tw-items-center tw-gap-2 tw-mb-3">
    <input type="file" id="dm-file-input" multiple />
    <button type="button" class="btn btn-default btn-sm" onclick="dm_upload_file(<?= (int) $task->id; ?>)"><?= _l('dm_portal_modal_upload'); ?></button>
</div>

<hr class="hr-panel-separator" />

<h5 class="no-margin tw-mb-2"><?= _l('dm_portal_modal_comments'); ?></h5>
<?php if (empty($task->comments)) { ?>
<p class="text-muted"><?= _l('dm_portal_modal_no_comments'); ?></p>
<?php } else { ?>
<ul class="list-unstyled tw-mb-3">
    <?php foreach ($task->comments as $comment) { ?>
    <li class="tw-mb-2">
        <span class="tw-font-medium"><?= e(trim($comment['firstname'] . ' ' . $comment['lastname'])); ?></span>
        <span class="text-muted tw-text-xs"><?= e(_dt($comment['dateadded'])); ?></span>
        <div><?= nl2br(e(str_replace('[task_attachment]', '', $comment['content']))); ?></div>
    </li>
    <?php } ?>
</ul>
<?php } ?>
<div class="tw-flex tw-items-start tw-gap-2">
    <textarea class="form-control dm-note-input" rows="2" placeholder="<?= _l('dm_portal_modal_add_note_placeholder'); ?>"></textarea>
    <button type="button" class="btn btn-primary btn-sm" onclick="dm_add_note(<?= (int) $task->id; ?>)"><?= _l('dm_portal_action_add_notes'); ?></button>
</div>
