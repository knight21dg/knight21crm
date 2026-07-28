<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-7 tw-mb-4">
                <div class="panel_s tw-mb-0 tw-h-full">
                    <div class="panel-body">
                        <h4 class="no-margin"><?= e($title); ?></h4>
                        <p class="text-muted">
                            <?= $today_update ? _l('daily_work_update_editing_today') : _l('daily_work_update_creating_today'); ?>
                        </p>
                        <hr class="hr-panel-separator" />

                        <form id="dwu-form">
                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <label><?= _l('daily_work_update_field_date'); ?></label>
                                    <input type="text" class="form-control" value="<?= e(_d(date('Y-m-d'))); ?>" disabled />
                                </div>
                                <div class="col-md-6 form-group">
                                    <label><?= _l('daily_work_update_field_project'); ?></label>
                                    <select id="dwu-project" class="selectpicker" data-width="100%" data-live-search="true">
                                        <option value=""><?= _l('daily_work_update_field_none'); ?></option>
                                        <?php foreach ($projects as $project) { ?>
                                        <option value="<?= (int) $project['id']; ?>" <?= ($today_update && (int) $today_update->project_id === (int) $project['id']) ? 'selected' : ''; ?>><?= e($project['name']); ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label><?= _l('daily_work_update_field_task'); ?></label>
                                <select id="dwu-task" class="selectpicker" data-width="100%" data-live-search="true">
                                    <option value=""><?= _l('daily_work_update_field_none'); ?></option>
                                    <?php foreach ($tasks as $task) { ?>
                                    <option value="<?= (int) $task['id']; ?>" data-project="<?= (int) $task['project_id']; ?>" <?= ($today_update && (int) $today_update->task_id === (int) $task['id']) ? 'selected' : ''; ?>><?= e($task['name']); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label><?= _l('daily_work_update_field_today_work'); ?> *</label>
                                <textarea id="dwu-today-work" class="form-control" rows="3" required><?= $today_update ? e($today_update->today_work) : ''; ?></textarea>
                            </div>
                            <div class="form-group">
                                <label><?= _l('daily_work_update_field_tomorrow_plan'); ?> *</label>
                                <textarea id="dwu-tomorrow-plan" class="form-control" rows="3" required><?= $today_update ? e($today_update->tomorrow_plan) : ''; ?></textarea>
                            </div>
                            <div class="form-group">
                                <label><?= _l('daily_work_update_field_issues'); ?></label>
                                <textarea id="dwu-issues" class="form-control" rows="2"><?= ($today_update && $today_update->issues) ? e($today_update->issues) : ''; ?></textarea>
                            </div>

                            <?php if ($today_update) { ?>
                            <div class="form-group">
                                <label><?= _l('daily_work_update_field_attachment'); ?></label>
                                <div id="dwu-attachments-container" data-work-update-id="<?= (int) $today_update->id; ?>"><?= $attachments_html; ?></div>
                                <div class="tw-flex tw-items-center tw-gap-2 tw-mt-2">
                                    <input type="file" id="dwu-file-input" />
                                    <button type="button" class="btn btn-default btn-sm" onclick="dwu_upload_file(<?= (int) $today_update->id; ?>)"><?= _l('daily_work_update_upload'); ?></button>
                                </div>
                            </div>
                            <?php } else { ?>
                            <div class="form-group">
                                <label><?= _l('daily_work_update_field_attachment'); ?></label>
                                <p class="text-muted"><small><?= _l('daily_work_update_attachment_after_save'); ?></small></p>
                            </div>
                            <?php } ?>

                            <button type="button" class="btn btn-primary" onclick="dwu_save()"><?= _l('daily_work_update_submit'); ?></button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-5 tw-mb-4">
                <div class="panel_s tw-mb-0 tw-h-full">
                    <div class="panel-body">
                        <h5 class="no-margin tw-mb-3"><?= _l('daily_work_update_recent'); ?></h5>
                        <?php if (empty($recent_updates)) { ?>
                        <span class="text-muted"><?= _l('daily_work_update_no_recent'); ?></span>
                        <?php } else { ?>
                        <ul class="list-unstyled">
                            <?php foreach ($recent_updates as $update) { ?>
                            <li class="tw-mb-3">
                                <div class="tw-font-medium">
                                    <?= e(_d($update['work_date'])); ?> <span class="text-muted tw-font-normal">- <?= $update['project_name'] ? e($update['project_name']) : '-'; ?></span>
                                    <?= daily_work_update_review_badge($update['review_status']); ?>
                                </div>
                                <div class="tw-text-sm"><?= e(mb_strimwidth($update['today_work'], 0, 140, '...')); ?></div>
                                <?php if (!empty($update['review_comment'])) { ?>
                                <div class="tw-text-sm text-muted"><strong><?= _l('daily_work_update_review_comment'); ?>:</strong> <?= e($update['review_comment']); ?></div>
                                <?php } ?>
                                <?php if (!empty($update['reviewed_by'])) { ?>
                                <div class="tw-text-sm text-muted">
                                    <strong><?= _l('daily_work_update_reviewed_by'); ?>:</strong> <?= e(trim($update['reviewer_firstname'] . ' ' . $update['reviewer_lastname'])); ?>
                                    <?php if (!empty($update['reviewed_at'])) { ?>
                                    - <?= e(_dt($update['reviewed_at'])); ?>
                                    <?php } ?>
                                </div>
                                <?php } ?>
                            </li>
                            <?php } ?>
                        </ul>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script>
    $(function() {
        $('#dwu-project').on('changed.bs.select', function() {
            var projectId = $(this).val();
            $('#dwu-task option').each(function() {
                var $opt = $(this);
                if ($opt.val() === '') { return; }
                var taskProject = $opt.data('project');
                $opt.prop('hidden', projectId && String(taskProject) !== String(projectId));
            });
            $('#dwu-task').selectpicker('refresh');
        });
    });

    function dwu_save() {
        var todayWork = $('#dwu-today-work').val().trim();
        var tomorrowPlan = $('#dwu-tomorrow-plan').val().trim();

        if (!todayWork || !tomorrowPlan) {
            alert_float('danger', <?= json_encode(_l('daily_work_update_missing_fields')); ?>);
            return;
        }

        $.post(admin_url + 'daily_work_update/save', {
            project_id: $('#dwu-project').val(),
            task_id: $('#dwu-task').val(),
            today_work: todayWork,
            tomorrow_plan: tomorrowPlan,
            issues: $('#dwu-issues').val(),
        }).done(function(response) {
            var data = typeof response === 'string' ? JSON.parse(response) : response;
            if (data.success) {
                alert_float('success', <?= json_encode(_l('daily_work_update_saved')); ?>);
                setTimeout(function() { location.reload(); }, 600);
            } else {
                alert_float('danger', data.message);
            }
        });
    }

    function dwu_upload_file(id) {
        var fileInput = document.getElementById('dwu-file-input');
        if (!fileInput.files.length) {
            return;
        }
        var formData = new FormData();
        formData.append('file', fileInput.files[0]);
        if (typeof(csrfData) !== 'undefined') {
            formData.append(csrfData['token_name'], csrfData['hash']);
        }
        $.ajax({
            url: admin_url + 'daily_work_update/upload/' + id,
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
        }).done(function(response) {
            var data = typeof response === 'string' ? JSON.parse(response) : response;
            if (data.success === false) {
                alert_float('danger', data.message);
                return;
            }
            alert_float('success', <?= json_encode(_l('daily_work_update_file_uploaded')); ?>);
            fileInput.value = '';
            dwu_refresh_attachments();
        });
    }

    // Re-fetches just the attachments list partial (Daily_work_update::
    // attachments_fragment()) and swaps it in - no full page reload,
    // used after both upload and delete.
    function dwu_refresh_attachments() {
        var workUpdateId = $('#dwu-attachments-container').data('work-update-id');
        $.get(admin_url + 'daily_work_update/attachments_fragment/' + workUpdateId).done(function(response) {
            $('#dwu-attachments-container').html(response);
        });
    }

    function dwu_delete_attachment(id) {
        if (!confirm(<?= json_encode(_l('daily_work_update_confirm_delete_attachment')); ?>)) {
            return;
        }

        $.post(admin_url + 'daily_work_update/delete_attachment/' + id).done(function(response) {
            var data = typeof response === 'string' ? JSON.parse(response) : response;
            if (data.success) {
                alert_float('success', <?= json_encode(_l('daily_work_update_attachment_deleted')); ?>);
                dwu_refresh_attachments();
            } else {
                alert_float('danger', data.message || <?= json_encode(_l('daily_work_update_attachment_delete_failed')); ?>);
            }
        });
    }
</script>

<style>
    .dwu-attachment-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        padding: 6px 0;
    }
    .dwu-attachment-info {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .dwu-attachment-meta {
        font-size: 12px;
        margin-left: 4px;
    }
    .dwu-attachment-delete {
        white-space: nowrap;
        color: #dc3545;
        opacity: 0;
        transition: opacity 0.15s ease;
    }
    .dwu-attachment-item:hover .dwu-attachment-delete,
    .dwu-attachment-item:focus-within .dwu-attachment-delete {
        opacity: 1;
    }
    /* Delete stays visible on touch devices - no hover to reveal it there. */
    @media (hover: none) {
        .dwu-attachment-delete {
            opacity: 1;
        }
    }
</style>
