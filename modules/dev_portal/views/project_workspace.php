<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="tw-flex tw-items-center tw-justify-between tw-flex-wrap">
                            <h4 class="no-margin"><?= e($project->name); ?></h4>
                            <a href="<?= admin_url('dev_portal/my_projects'); ?>" class="btn btn-default btn-sm"><i class="fa-solid fa-arrow-left"></i> <?= _l('dev_portal_my_projects'); ?></a>
                        </div>
                        <hr class="hr-panel-separator" />

                        <!-- Project Information -->
                        <div class="row">
                            <div class="col-md-3 col-sm-6 tw-mb-3">
                                <label class="text-muted"><?= _l('dev_portal_column_client'); ?></label>
                                <div><?= e(get_company_name($project->clientid)); ?></div>
                            </div>
                            <div class="col-md-3 col-sm-6 tw-mb-3">
                                <label class="text-muted"><?= _l('dev_portal_workspace_start_date'); ?></label>
                                <div><?= $project->start_date ? e(_d($project->start_date)) : '-'; ?></div>
                            </div>
                            <div class="col-md-3 col-sm-6 tw-mb-3">
                                <label class="text-muted"><?= _l('dev_portal_column_due_date'); ?></label>
                                <div><?= $project->deadline ? e(_d($project->deadline)) : '-'; ?></div>
                            </div>
                            <div class="col-md-3 col-sm-6 tw-mb-3">
                                <label class="text-muted"><?= _l('dev_portal_workspace_department'); ?></label>
                                <div><?= e($department_name ?: '-'); ?></div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-3 col-sm-6 tw-mb-3">
                                <label class="text-muted"><?= _l('dev_portal_workspace_assigned_employee'); ?></label>
                                <div><?= $project->assigned_employee ? e(get_staff_full_name($project->assigned_employee)) : '-'; ?></div>
                            </div>
                            <div class="col-md-3 col-sm-6 tw-mb-3">
                                <label class="text-muted"><?= _l('dev_portal_workspace_assigned_work'); ?></label>
                                <div><?= $project->assigned_work != '' ? e($project->assigned_work) : '-'; ?></div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-3 col-sm-6 tw-mb-3">
                                <label class="text-muted"><?= _l('dev_portal_column_status'); ?></label>
                                <div>
                                    <span id="workspace-status-badge" class="label label-info"><?= e($status_label); ?></span>
                                </div>
                            </div>
                            <div class="col-md-9 tw-mb-3">
                                <label class="text-muted"><?= _l('dev_portal_workspace_progress'); ?></label>
                                <div class="progress" style="margin-bottom:5px;">
                                    <div id="workspace-progress-bar" class="progress-bar" role="progressbar" data-percent="<?= (int) $effective_progress; ?>" aria-valuenow="<?= (int) $effective_progress; ?>" aria-valuemin="0" aria-valuemax="100" style="width:<?= (int) $effective_progress; ?>%;"><?= (int) $effective_progress; ?>%</div>
                                </div>
                            </div>
                        </div>

                        <hr class="hr-panel-separator" />

                        <!-- Progress + Status update -->
                        <div class="row">
                            <div class="col-md-6 tw-mb-4">
                                <label><?= _l('dev_portal_workspace_update_progress'); ?></label>
                                <div class="tw-flex tw-gap-2">
                                    <select id="workspace-progress-select" class="form-control">
                                        <?php foreach (get_progress_options() as $option) { ?>
                                        <option value="<?= (int) $option; ?>" <?= (int) $effective_progress === (int) $option ? 'selected' : ''; ?>><?= (int) $option; ?>%</option>
                                        <?php } ?>
                                    </select>
                                    <button type="button" class="btn btn-primary" onclick="workspace_update_progress();"><?= _l('save'); ?></button>
                                </div>
                            </div>
                            <div class="col-md-6 tw-mb-4">
                                <label><?= _l('dev_portal_workspace_change_status'); ?></label>
                                <?php if ($is_cancelled) { ?>
                                <div><span class="label label-default"><?= e($status_label); ?></span> <span class="text-muted"><?= _l('dev_portal_workspace_cancelled_readonly'); ?></span></div>
                                <?php } else { ?>
                                <div class="tw-flex tw-gap-2">
                                    <select id="workspace-status-select" class="form-control">
                                        <?php foreach ($status_options as $status_id => $status_name) { ?>
                                        <option value="<?= (int) $status_id; ?>" <?= (int) $project->status === (int) $status_id ? 'selected' : ''; ?>><?= e($status_name); ?></option>
                                        <?php } ?>
                                    </select>
                                    <button type="button" class="btn btn-primary" onclick="workspace_update_status();"><?= _l('save'); ?></button>
                                </div>
                                <?php } ?>
                            </div>
                        </div>

                        <hr class="hr-panel-separator" />

                        <!-- Notes - shared with Admin (tblproject_notes) -->
                        <h5><?= _l('dev_portal_workspace_notes'); ?></h5>
                        <?php $latest_note = $project_notes[0] ?? null; ?>
                        <div class="row">
                            <div class="col-md-12 tw-mb-2">
                                <label class="text-muted"><?= _l('dev_portal_workspace_latest_note'); ?></label>
                                <div id="workspace-latest-note-text"><?= $latest_note ? e($latest_note['content']) : '<span class="text-muted">-</span>'; ?></div>
                                <div class="text-muted tw-text-xs" id="workspace-latest-note-meta">
                                    <?= $latest_note ? _l('dev_portal_workspace_note_by', e(trim($latest_note['firstname'] . ' ' . $latest_note['lastname']))) . ' &middot; ' . e(time_ago($latest_note['dateadded'])) : ''; ?>
                                </div>
                            </div>
                        </div>
                        <div class="tw-flex tw-gap-2 tw-mb-3">
                            <textarea id="workspace-note-input" class="form-control" rows="2" placeholder="<?= _l('dev_portal_workspace_note_placeholder'); ?>"></textarea>
                            <button type="button" class="btn btn-primary" onclick="workspace_add_note();"><?= _l('dev_portal_workspace_add_note'); ?></button>
                        </div>
                        <div class="text-muted tw-text-xs"><?= _l('dev_portal_workspace_note_history'); ?></div>
                        <ul class="list-unstyled">
                            <?php if (empty($project_notes)) { ?>
                            <li class="text-muted"><?= _l('dev_portal_workspace_no_notes'); ?></li>
                            <?php } else { ?>
                            <?php foreach ($project_notes as $note) { ?>
                            <li class="tw-mb-2">
                                <span class="text-muted tw-text-xs"><?= e(_dt($note['dateadded'])); ?></span>
                                <?= _l('dev_portal_workspace_note_by', e(trim($note['firstname'] . ' ' . $note['lastname']))); ?>
                                <div><?= nl2br(e($note['content'])); ?></div>
                            </li>
                            <?php } ?>
                            <?php } ?>
                        </ul>

                        <hr class="hr-panel-separator" />

                        <!-- Daily Work Updates -->
                        <h5><?= _l('dev_portal_workspace_work_updates'); ?></h5>
                        <div class="row">
                            <div class="col-md-2 tw-mb-2">
                                <?php
                                // render_date_input() always sets the rendered
                                // input's id to its $name argument (confirmed by
                                // reading application/helpers/fields_helper.php) -
                                // an 'id' key in the 4th ($input_attrs) argument is
                                // NOT used for id at all (it overrides name=
                                // instead, and gets echoed a second time as a raw,
                                // browser-ignored duplicate id attribute). Passing
                                // the id we actually want as $name directly is the
                                // only way to get workspace_add_worklog() below to
                                // find this field via $('#workspace-work-date').
                                echo render_date_input('workspace-work-date', '', date('Y-m-d'));
                                ?>
                            </div>
                            <div class="col-md-4 tw-mb-2">
                                <input type="text" id="workspace-work-performed" class="form-control" placeholder="<?= _l('dev_portal_workspace_work_performed'); ?>">
                            </div>
                            <div class="col-md-2 tw-mb-2">
                                <input type="number" step="0.5" min="0" id="workspace-hours-worked" class="form-control" placeholder="<?= _l('dev_portal_workspace_hours_worked'); ?>">
                            </div>
                            <div class="col-md-3 tw-mb-2">
                                <input type="text" id="workspace-remarks" class="form-control" placeholder="<?= _l('dev_portal_workspace_remarks'); ?>">
                            </div>
                            <div class="col-md-1 tw-mb-2">
                                <button type="button" class="btn btn-primary" onclick="workspace_add_worklog();"><?= _l('save'); ?></button>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th><?= _l('dev_portal_column_due_date'); ?></th>
                                        <th><?= _l('dev_portal_workspace_work_performed'); ?></th>
                                        <th><?= _l('dev_portal_workspace_hours_worked'); ?></th>
                                        <th><?= _l('dev_portal_workspace_remarks'); ?></th>
                                        <th><?= _l('dev_portal_column_task'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($work_logs)) { ?>
                                    <tr><td colspan="5" class="text-muted"><?= _l('dev_portal_workspace_no_work_updates'); ?></td></tr>
                                    <?php } else { ?>
                                    <?php foreach ($work_logs as $log) { ?>
                                    <tr>
                                        <td><?= e(_d($log['work_date'])); ?></td>
                                        <td><?= e($log['work_performed']); ?></td>
                                        <td><?= $log['hours_worked'] !== null ? e($log['hours_worked']) . 'h' : '-'; ?></td>
                                        <td><?= $log['remarks'] ? e($log['remarks']) : '-'; ?></td>
                                        <td><?= e(trim($log['firstname'] . ' ' . $log['lastname'])); ?></td>
                                    </tr>
                                    <?php } ?>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>

                        <hr class="hr-panel-separator" />

                        <!-- Files -->
                        <h5><?= _l('dev_portal_workspace_files'); ?></h5>
                        <form id="workspace-file-form" enctype="multipart/form-data" onsubmit="return workspace_upload_file(this);">
                            <div class="tw-flex tw-gap-2 tw-mb-3">
                                <input type="file" name="file" class="form-control" required>
                                <button type="submit" class="btn btn-primary"><?= _l('dev_portal_workspace_upload'); ?></button>
                            </div>
                        </form>
                        <ul class="list-unstyled">
                            <?php if (empty($files)) { ?>
                            <li class="text-muted"><?= _l('dev_portal_workspace_no_files'); ?></li>
                            <?php } else { ?>
                            <?php foreach ($files as $file) { ?>
                            <li class="tw-mb-1">
                                <i class="fa-regular fa-file"></i>
                                <a href="<?= site_url('uploads/projects/' . $file['project_id'] . '/' . $file['file_name']); ?>" target="_blank" download="<?= e($file['original_file_name']); ?>"><?= e($file['original_file_name']); ?></a>
                                <span class="text-muted"> - <?= e(_dt($file['dateadded'])); ?></span>
                            </li>
                            <?php } ?>
                            <?php } ?>
                        </ul>

                        <hr class="hr-panel-separator" />

                        <!-- Comments / Timeline -->
                        <h5><?= _l('dev_portal_workspace_comments'); ?></h5>
                        <div class="tw-flex tw-gap-2 tw-mb-3">
                            <input type="text" id="workspace-comment-content" class="form-control" placeholder="<?= _l('dev_portal_workspace_add_comment'); ?>">
                            <button type="button" class="btn btn-primary" onclick="workspace_add_comment();"><?= _l('save'); ?></button>
                        </div>
                        <ul class="list-unstyled">
                            <?php if (empty($comments)) { ?>
                            <li class="text-muted"><?= _l('dev_portal_workspace_no_comments'); ?></li>
                            <?php } else { ?>
                            <?php foreach ($comments as $comment) { ?>
                            <li class="tw-mb-2">
                                <strong><?= e($comment['fullname']); ?></strong>
                                <span class="text-muted"> - <?= e(_dt($comment['created'])); ?></span>
                                <div><?= $comment['content']; ?></div>
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
    var WORKSPACE_PROJECT_ID = <?= (int) $project->id; ?>;

    function workspace_update_progress() {
        $.post(admin_url + 'dev_portal/project_update_progress/' + WORKSPACE_PROJECT_ID, {
            progress: $('#workspace-progress-select').val()
        }).done(function(response) {
            var data = typeof response === 'string' ? JSON.parse(response) : response;
            if (data.success) {
                location.reload();
            } else {
                alert_float('danger', data.message);
            }
        });
    }

    function workspace_update_status() {
        $.post(admin_url + 'dev_portal/project_update_status/' + WORKSPACE_PROJECT_ID, {
            status_id: $('#workspace-status-select').val()
        }).done(function(response) {
            var data = typeof response === 'string' ? JSON.parse(response) : response;
            if (data.success) {
                location.reload();
            } else {
                alert_float('danger', data.message);
            }
        });
    }

    function workspace_add_worklog() {
        $.post(admin_url + 'dev_portal/project_add_worklog/' + WORKSPACE_PROJECT_ID, {
            work_date: $('#workspace-work-date').val(),
            work_performed: $('#workspace-work-performed').val(),
            hours_worked: $('#workspace-hours-worked').val(),
            remarks: $('#workspace-remarks').val()
        }).done(function(response) {
            var data = typeof response === 'string' ? JSON.parse(response) : response;
            if (data.success) {
                location.reload();
            } else {
                alert_float('danger', data.message);
            }
        });
    }

    function workspace_upload_file(form) {
        var formData = new FormData(form);
        // FormData(form) only captures actual form controls - it never
        // picks up the CSRF token, since there's no hidden input for it
        // in this form, which is why every submission here was rejected
        // as "Page expired" (confirmed live). Same explicit-append fix
        // already used by modules/daily_work_update's own file upload
        // JS, which hits this exact same FormData gap.
        if (typeof(csrfData) !== 'undefined') {
            formData.append(csrfData['token_name'], csrfData['hash']);
        }
        $.ajax({
            url: admin_url + 'dev_portal/project_upload_file/' + WORKSPACE_PROJECT_ID,
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false
        }).done(function(response) {
            var data = typeof response === 'string' ? JSON.parse(response) : response;
            if (data.success) {
                location.reload();
            } else {
                alert_float('danger', data.message);
            }
        });

        return false;
    }

    function workspace_add_note() {
        var note = $.trim($('#workspace-note-input').val());
        if (note === '') {
            alert_float('danger', <?= json_encode(_l('dev_portal_note_enter_note')); ?>);
            return;
        }
        $.post(admin_url + 'dev_portal/project_add_note/' + WORKSPACE_PROJECT_ID, {
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

    function workspace_add_comment() {
        $.post(admin_url + 'dev_portal/project_add_comment/' + WORKSPACE_PROJECT_ID, {
            content: $('#workspace-comment-content').val()
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
