<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="tw-flex tw-items-center tw-justify-between tw-flex-wrap">
                            <h4 class="no-margin"><?= e($task->name); ?></h4>
                            <a href="<?= admin_url('dev_portal/my_tasks'); ?>" class="btn btn-default btn-sm"><i class="fa-solid fa-arrow-left"></i> <?= _l('dev_portal_my_tasks'); ?></a>
                        </div>
                        <hr class="hr-panel-separator" />

                        <!-- Description = original/general task instructions,
                             kept visually separate from Notes below (ongoing
                             updates/communication) - same distinction the
                             Admin task modal's own Description/Notes split
                             uses. Reads the SAME tbltasks.description column
                             Admin Task Create/Edit writes, no separate field. -->
                        <h5><?= _l('task_view_description'); ?></h5>
                        <?php if (! empty($task->description)) { ?>
                        <div class="tw-mb-3"><?= check_for_links($task->description); ?></div>
                        <?php } else { ?>
                        <div class="tw-mb-3 text-muted"><?= _l('task_no_description'); ?></div>
                        <?php } ?>

                        <hr class="hr-panel-separator" />

                        <!-- Task Information -->
                        <h5><?= _l('task_info'); ?></h5>
                        <div class="row">
                            <div class="col-md-4 col-sm-6 tw-mb-3">
                                <label class="text-muted"><?= _l('dev_portal_column_client'); ?></label>
                                <div><?= $customer_name ? e($customer_name) : '-'; ?></div>
                            </div>
                            <div class="col-md-4 col-sm-6 tw-mb-3">
                                <label class="text-muted"><?= _l('dev_portal_column_project'); ?></label>
                                <div><?= $project_name ? e($project_name) : '-'; ?></div>
                            </div>
                            <div class="col-md-4 col-sm-6 tw-mb-3">
                                <label class="text-muted"><?= _l('task_single_assignees'); ?></label>
                                <div><?= count($task->assignees) > 0 ? e(implode(', ', array_column($task->assignees, 'full_name'))) : '-'; ?></div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 col-sm-6 tw-mb-3">
                                <label class="text-muted"><?= _l('task_single_priority'); ?></label>
                                <div><span style="color:<?= e(task_priority_color($task->priority)); ?>;"><?= e(task_priority($task->priority)); ?></span></div>
                            </div>
                            <div class="col-md-4 col-sm-6 tw-mb-3">
                                <label class="text-muted"><?= _l('task_single_due_date'); ?></label>
                                <div><?= $task->duedate ? e(_d($task->duedate)) : '-'; ?></div>
                            </div>
                            <div class="col-md-4 col-sm-6 tw-mb-3">
                                <label class="text-muted"><?= _l('task_status'); ?></label>
                                <div><?= format_task_status($task->status); ?></div>
                            </div>
                        </div>

                        <hr class="hr-panel-separator" />

                        <!-- Task Notes - shared with Admin (tbltask_notes) -->
                        <h5><?= _l('task_notes'); ?></h5>
                        <?php $latest_note = $task_notes[0] ?? null; ?>
                        <div class="row">
                            <div class="col-md-12 tw-mb-2">
                                <label class="text-muted"><?= _l('task_notes_latest_note'); ?></label>
                                <div id="workspace-latest-note-text"><?= $latest_note ? e($latest_note['content']) : '<span class="text-muted">-</span>'; ?></div>
                                <div class="text-muted tw-text-xs" id="workspace-latest-note-meta">
                                    <?= $latest_note ? _l('task_notes_by', e(trim($latest_note['firstname'] . ' ' . $latest_note['lastname']))) . ' &middot; ' . e(time_ago($latest_note['dateadded'])) : ''; ?>
                                </div>
                            </div>
                        </div>
                        <?php if ($task->status != Tasks_model::STATUS_COMPLETE) { ?>
                        <div class="tw-flex tw-gap-2 tw-mb-3">
                            <textarea id="workspace-note-input" class="form-control" rows="2" placeholder="<?= _l('task_notes_placeholder'); ?>"></textarea>
                            <button type="button" class="btn btn-primary" onclick="workspace_add_task_note();"><?= _l('task_notes_add'); ?></button>
                        </div>
                        <?php } ?>
                        <div class="text-muted tw-text-xs"><?= _l('task_notes_history'); ?></div>
                        <ul class="list-unstyled">
                            <?php if (empty($task_notes)) { ?>
                            <li class="text-muted"><?= _l('dev_portal_workspace_no_notes'); ?></li>
                            <?php } else { ?>
                            <?php foreach ($task_notes as $note) { ?>
                            <li class="tw-mb-2">
                                <span class="text-muted tw-text-xs"><?= e(_dt($note['dateadded'])); ?></span>
                                <?= _l('task_notes_by', e(trim($note['firstname'] . ' ' . $note['lastname']))); ?>
                                <div><?= nl2br(e($note['content'])); ?></div>
                            </li>
                            <?php } ?>
                            <?php } ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script>
    var WORKSPACE_TASK_ID = <?= (int) $task->id; ?>;

    function workspace_add_task_note() {
        var note = $.trim($('#workspace-note-input').val());
        if (note === '') {
            alert_float('danger', <?= json_encode(_l('task_notes_enter_note')); ?>);
            return;
        }
        $.post(admin_url + 'dev_portal/task_add_note/' + WORKSPACE_TASK_ID, {
            note: note
        }).done(function(response) {
            var data = typeof response === 'string' ? JSON.parse(response) : response;
            if (data.success) {
                location.reload();
            } else {
                alert_float('danger', data.message);
            }
        });
    }
</script>
</body>
</html>
