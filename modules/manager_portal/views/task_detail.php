<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-8">
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="tw-flex tw-items-center tw-justify-between tw-flex-wrap tw-gap-2">
                            <h4 class="no-margin"><?= e($task->name); ?></h4>
                            <a href="<?= admin_url('manager_portal/tasks'); ?>" class="btn btn-default"><i class="fa-solid fa-arrow-left"></i> <?= _l('manager_portal_back_to_tasks'); ?></a>
                        </div>
                        <?php if (!empty($task->project_data)) { ?>
                        <p class="text-muted tw-mb-0">
                            <a href="<?= admin_url('manager_portal/project/' . $task->rel_id); ?>"><?= e($task->project_data->name); ?></a>
                            <?= $task->milestone_name ? ' - ' . e($task->milestone_name) : ''; ?>
                        </p>
                        <?php } ?>
                        <hr class="hr-panel-separator" />

                        <div class="row tw-mb-4">
                            <div class="col-md-3">
                                <strong><?= _l('manager_portal_tasks_column_status'); ?></strong>
                                <p><?= format_task_status($task->status, true); ?></p>
                            </div>
                            <div class="col-md-3">
                                <strong><?= _l('manager_portal_tasks_column_priority'); ?></strong>
                                <p><span style="color:<?= task_priority_color($task->priority); ?>"><?= e(task_priority($task->priority)); ?></span></p>
                            </div>
                            <div class="col-md-3">
                                <strong><?= _l('manager_portal_tasks_column_due_date'); ?></strong>
                                <p><?= $task->duedate ? e(_d($task->duedate)) : '-'; ?></p>
                            </div>
                            <div class="col-md-3">
                                <strong><?= _l('manager_portal_tasks_column_assigned'); ?></strong>
                                <p>
                                    <?php if (empty($task->assignees)) { ?>
                                    -
                                    <?php } else { ?>
                                    <?= e(implode(', ', array_column($task->assignees, 'full_name'))); ?>
                                    <?php } ?>
                                </p>
                            </div>
                        </div>

                        <h5><?= _l('manager_portal_tab_overview'); ?></h5>
                        <div class="tw-mb-4"><?= $task->description ? $task->description : '<span class="text-muted">' . _l('manager_portal_no_description') . '</span>'; ?></div>

                        <h5><?= _l('manager_portal_task_checklist'); ?> (<?= count($task->checklist_items); ?>)</h5>
                        <?php if (empty($task->checklist_items)) { ?>
                        <p class="text-muted"><?= _l('manager_portal_no_checklist'); ?></p>
                        <?php } else { ?>
                        <ul class="list-unstyled tw-mb-4">
                            <?php foreach ($task->checklist_items as $item) { ?>
                            <li>
                                <i class="fa-regular <?= $item['finished'] ? 'fa-square-check' : 'fa-square'; ?>"></i>
                                <?= $item['finished'] ? '<s class="text-muted">' . e($item['description']) . '</s>' : e($item['description']); ?>
                            </li>
                            <?php } ?>
                        </ul>
                        <?php } ?>

                        <h5><?= _l('manager_portal_task_attachments'); ?> (<?= count($task->attachments); ?>)</h5>
                        <?php if (empty($task->attachments)) { ?>
                        <p class="text-muted"><?= _l('manager_portal_no_attachments'); ?></p>
                        <?php } else { ?>
                        <ul class="list-unstyled tw-mb-4">
                            <?php foreach ($task->attachments as $attachment) { ?>
                            <li><i class="fa-regular fa-paperclip"></i> <?= e($attachment['file_name']); ?></li>
                            <?php } ?>
                        </ul>
                        <?php } ?>

                        <h5><?= _l('manager_portal_task_comments'); ?> (<?= count($task->comments); ?>)</h5>
                        <?php if (empty($task->comments)) { ?>
                        <p class="text-muted"><?= _l('manager_portal_no_comments'); ?></p>
                        <?php } else { ?>
                        <ul class="list-unstyled tw-mb-3">
                            <?php foreach ($task->comments as $comment) { ?>
                            <li class="tw-mb-3">
                                <div class="tw-font-medium"><?= e($comment['staff_full_name']); ?> <span class="text-muted tw-text-sm tw-font-normal"><?= e(time_ago($comment['dateadded'])); ?></span></div>
                                <div><?= $comment['content']; ?></div>
                            </li>
                            <?php } ?>
                        </ul>
                        <?php } ?>

                        <form id="mp-task-comment-form" data-taskid="<?= (int) $task->id; ?>">
                            <div class="form-group">
                                <textarea id="mp-task-comment-content" class="form-control" rows="3" placeholder="<?= e(_l('manager_portal_task_comment_placeholder')); ?>"></textarea>
                            </div>
                            <button type="button" class="btn btn-primary" onclick="mp_task_add_comment()"><?= _l('manager_portal_task_comment_submit'); ?></button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="panel_s">
                    <div class="panel-body">
                        <h5 class="no-margin"><?= _l('manager_portal_manager_note'); ?></h5>
                        <p class="text-muted tw-text-sm"><?= _l('manager_portal_manager_note_hint'); ?></p>
                        <form id="mp-task-note-form" data-taskid="<?= (int) $task->id; ?>">
                            <div class="form-group">
                                <textarea id="mp-task-note-content" class="form-control" rows="5"><?= e((string) $task->manager_note); ?></textarea>
                            </div>
                            <button type="button" class="btn btn-primary" onclick="mp_task_save_note()"><?= _l('manager_portal_manager_note_save'); ?></button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script>
    function mp_task_add_comment() {
        var $form = $('#mp-task-comment-form');
        var content = $('#mp-task-comment-content').val().trim();
        if (!content) {
            return;
        }
        $.post(admin_url + 'manager_portal/task_comment', {
            taskid: $form.data('taskid'),
            content: content,
        }).done(function(response) {
            var data = typeof response === 'string' ? JSON.parse(response) : response;
            if (data.success) {
                location.reload();
            } else {
                alert_float('danger', data.message);
            }
        });
    }

    function mp_task_save_note() {
        var $form = $('#mp-task-note-form');
        $.post(admin_url + 'manager_portal/task_note', {
            taskid: $form.data('taskid'),
            note: $('#mp-task-note-content').val(),
        }).done(function(response) {
            var data = typeof response === 'string' ? JSON.parse(response) : response;
            if (data.success) {
                alert_float('success', data.message);
            } else {
                alert_float('danger', data.message);
            }
        });
    }
</script>
</body>
</html>
