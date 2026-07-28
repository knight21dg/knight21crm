<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<?php
/**
 * Standard Staff Dashboard template (application/helpers/staff_dashboard_helper.php)
 * - identical layout/design system used by dev_portal and the Telecaller
 * dashboard; only the data fed into the shared renderers differs. The
 * $summary/$recent_updates arrays below come from the exact same
 * Dm_portal::dashboard() controller/model calls as before this redesign -
 * this file only changed how they're rendered, not what's queried.
 */
$staff_departments  = get_staff_business_departments(get_staff_user_id());
$department_label   = $staff_departments ? implode(', ', array_column($staff_departments, 'name')) : '';
?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <?php staff_dashboard_header(get_staff_full_name(get_staff_user_id()), $department_label); ?>

                <?php staff_dashboard_kpi_grid_open(); ?>
                <?php staff_dashboard_kpi_card(
                    _l('dm_portal_dashboard_assigned_projects'),
                    (int) $summary['assigned_projects'],
                    'fa-solid fa-diagram-project',
                    'projects',
                    '',
                    null,
                    null,
                    "dm_open_dashboard_modal('projects')"
                ); ?>
                <?php staff_dashboard_kpi_card(
                    _l('dm_portal_dashboard_pending_tasks'),
                    (int) $summary['pending_tasks'],
                    'fa-solid fa-hourglass-half',
                    'pending',
                    '',
                    null,
                    null,
                    "dm_open_dashboard_modal('pending')"
                ); ?>
                <?php staff_dashboard_kpi_card(
                    _l('dm_portal_dashboard_completed_tasks'),
                    (int) $summary['completed_tasks'],
                    'fa-solid fa-circle-check',
                    'completed',
                    '',
                    null,
                    null,
                    "dm_open_dashboard_modal('completed')"
                ); ?>
                <?php staff_dashboard_kpi_card(
                    _l('dm_portal_dashboard_todays_tasks'),
                    (int) $summary['todays_tasks'],
                    'fa-solid fa-list-check',
                    'notifications',
                    '',
                    null,
                    null,
                    "dm_open_dashboard_modal('today_tasks')"
                ); ?>
                <?php staff_dashboard_kpi_grid_close(); ?>

                <div class="row">
                    <div class="col-md-6">
                        <?php staff_dashboard_panel_open(_l('dm_portal_dashboard_today_queue'), 'fa-solid fa-list-check'); ?>
                        <?php if (empty($summary['today_task_list'])) { ?>
                        <span class="text-muted"><?= _l('dm_portal_dashboard_no_today_tasks'); ?></span>
                        <?php } else { ?>
                        <ul class="list-unstyled">
                            <?php foreach ($summary['today_task_list'] as $task) { ?>
                            <li class="tw-mb-2">
                                <i class="fa-solid fa-list-check tw-text-gray-400"></i>
                                <a href="<?= admin_url('dm_portal/my_work'); ?>"><?= e($task['name']); ?></a>
                                <span class="text-muted"> - <?= $task['project_name'] ? e($task['project_name']) : '-'; ?></span>
                                <span class="label" style="color:<?= task_priority_color($task['priority']); ?>;"><?= e(task_priority($task['priority'])); ?></span>
                            </li>
                            <?php } ?>
                        </ul>
                        <?php } ?>
                        <?php staff_dashboard_panel_close(); ?>

                        <?php staff_dashboard_panel_open(_l('dm_portal_dashboard_upcoming_deadlines'), 'fa-solid fa-calendar-days'); ?>
                        <?php if (empty($summary['upcoming_deadlines'])) { ?>
                        <span class="text-muted"><?= _l('dm_portal_dashboard_no_deadlines'); ?></span>
                        <?php } else { ?>
                        <ul class="list-unstyled">
                            <?php foreach ($summary['upcoming_deadlines'] as $deadline) { ?>
                            <li class="tw-mb-2">
                                <i class="<?= $deadline['type'] === 'project' ? 'fa-solid fa-diagram-project' : 'fa-solid fa-list-check'; ?> tw-text-gray-400"></i>
                                <a href="<?= $deadline['url']; ?>"><?= e($deadline['name']); ?></a>
                                <span class="text-muted"> - <?= e(_d($deadline['date'])); ?></span>
                            </li>
                            <?php } ?>
                        </ul>
                        <?php } ?>
                        <?php staff_dashboard_panel_close(); ?>
                    </div>
                    <div class="col-md-6">
                        <?php staff_dashboard_panel_open(_l('dm_portal_dashboard_recent_updates'), 'fa-solid fa-note-sticky'); ?>
                        <?php if (empty($recent_updates)) { ?>
                        <span class="text-muted"><?= _l('dm_portal_dashboard_no_recent_updates'); ?></span>
                        <?php } else { ?>
                        <ul class="list-unstyled">
                            <?php foreach ($recent_updates as $update) { ?>
                            <li class="tw-mb-2">
                                <i class="fa-solid fa-note-sticky tw-text-gray-400"></i>
                                <span class="tw-font-medium"><?= e(_d($update['work_date'])); ?></span>
                                <span class="text-muted"> - <?= $update['project_name'] ? e($update['project_name']) : '-'; ?></span>
                            </li>
                            <?php } ?>
                        </ul>
                        <?php } ?>
                        <?php staff_dashboard_panel_close(); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="dm-dashboard-modal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title" id="dm-dashboard-modal-title"></h4>
            </div>
            <div class="modal-body" id="dm-dashboard-modal-body">
                <div class="text-center tw-py-4"><i class="fa-solid fa-spinner fa-spin"></i></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?= _l('close'); ?></button>
            </div>
        </div>
    </div>
</div>

<style>
    .dm-clickable-row {
        cursor: pointer;
    }
    .dm-clickable-row:hover {
        background: #f8f9fb;
    }
</style>

<?php init_tail(); ?>
<script>
    var dmDashCardConfig = {
        projects:    { endpoint: 'dashboard_projects',        title: <?= json_encode(_l('dm_portal_modal_my_assigned_projects')); ?> },
        pending:     { endpoint: 'dashboard_pending_tasks',    title: <?= json_encode(_l('dm_portal_modal_my_pending_tasks')); ?> },
        completed:   { endpoint: 'dashboard_completed_tasks',  title: <?= json_encode(_l('dm_portal_modal_my_completed_tasks')); ?> },
        today_tasks: { endpoint: 'dashboard_today_tasks',      title: <?= json_encode(_l('dm_portal_modal_todays_tasks')); ?> }
    };

    var dmDashCurrentCard = null;

    function dm_open_dashboard_modal(type) {
        var config = dmDashCardConfig[type];
        dmDashCurrentCard = type;

        $('#dm-dashboard-modal-title').text(config.title);
        $('#dm-dashboard-modal-body').html('<div class="text-center tw-py-4"><i class="fa-solid fa-spinner fa-spin"></i></div>');
        $('#dm-dashboard-modal').modal('show');

        $.get(admin_url + 'dm_portal/' + config.endpoint).done(function(response) {
            $('#dm-dashboard-modal-body').html(response);
        });
    }

    function dm_dash_reload() {
        if (!dmDashCurrentCard) {
            return;
        }
        var config = dmDashCardConfig[dmDashCurrentCard];
        $('#dm-dashboard-modal-title').text(config.title);
        $('#dm-dashboard-modal-body').html('<div class="text-center tw-py-4"><i class="fa-solid fa-spinner fa-spin"></i></div>');
        $.get(admin_url + 'dm_portal/' + config.endpoint).done(function(response) {
            $('#dm-dashboard-modal-body').html(response);
        });
    }

    // "Assigned Projects" row expand - lazy-loaded once per project, then
    // just toggled (no repeat fetch on subsequent clicks of the same row).
    function dm_toggle_project_detail(projectId) {
        var $detail  = $('#dm-project-detail-' + projectId);
        var $chevron = $('#dm-project-chevron-' + projectId);

        if ($detail.is(':visible')) {
            $detail.slideUp(150);
            $chevron.removeClass('fa-chevron-up').addClass('fa-chevron-down');
            return;
        }

        $chevron.removeClass('fa-chevron-down').addClass('fa-chevron-up');
        $detail.slideDown(150);

        if ($detail.data('loaded')) {
            return;
        }

        $.get(admin_url + 'dm_portal/dashboard_project_detail/' + projectId).done(function(response) {
            $detail.html(response);
            $detail.data('loaded', true);
        });
    }

    // "Pending Tasks" row actions - View/Update Progress/Mark Complete all
    // reuse the exact same task_details()/task_update_progress()/
    // task_mark_complete() endpoints My Work already uses.
    function dm_dash_view_task(id, focusNotes) {
        $('#dm-dashboard-modal-title').text('<?= _l('dm_portal_action_view_details'); ?>');
        $('#dm-dashboard-modal-body').html('<div class="text-center tw-py-4"><i class="fa-solid fa-spinner fa-spin"></i></div>');

        $.get(admin_url + 'dm_portal/task_details/' + id).done(function(response) {
            var back = '<button type="button" class="btn btn-default btn-sm tw-mb-3" onclick="dm_dash_reload()">'
                + '<i class="fa-solid fa-arrow-left"></i> <?= _l('dm_portal_back_to_list'); ?></button>';
            $('#dm-dashboard-modal-body').html(back + response);
            $('#dm-dashboard-modal-body').data('task-id', id);

            if (focusNotes === true) {
                var $notes = $('#dm-dashboard-modal-body .dm-note-input');
                if ($notes.length) {
                    $notes.focus();
                }
            }
        });
    }

    function dm_dash_update_status(id, status) {
        $.post(admin_url + 'dm_portal/task_update_progress/' + id, { status: status }).done(function(response) {
            var data = typeof response === 'string' ? JSON.parse(response) : response;
            if (data.success) {
                alert_float('success', '<?= _l('dm_portal_status_updated'); ?>');
                dm_dash_reload();
            } else {
                alert_float('danger', data.message);
            }
        });
    }

    function dm_dash_mark_complete(id) {
        if (!confirm('<?= _l('dm_portal_action_mark_complete'); ?>?')) {
            return;
        }
        $.post(admin_url + 'dm_portal/task_mark_complete/' + id).done(function() {
            alert_float('success', '<?= _l('dm_portal_task_completed'); ?>');
            dm_dash_reload();
        });
    }

    // Reused by task_details_modal.php's own Add Note / Upload buttons
    // when that partial is embedded here (same function names it already
    // calls on My Work, same backend endpoints - just refreshes back into
    // this single-task view instead of My Work's own modal).
    function dm_add_note(id) {
        var content = $('#dm-dashboard-modal-body .dm-note-input').val();
        $.post(admin_url + 'dm_portal/task_add_note/' + id, { content: content }).done(function(response) {
            var data = typeof response === 'string' ? JSON.parse(response) : response;
            if (data.success) {
                alert_float('success', '<?= _l('dm_portal_note_added'); ?>');
                dm_dash_view_task(id);
            } else {
                alert_float('danger', data.message);
            }
        });
    }

    function dm_upload_file(id) {
        var formData  = new FormData();
        var fileInput = document.getElementById('dm-file-input');
        if (!fileInput.files.length) {
            return;
        }
        for (var i = 0; i < fileInput.files.length; i++) {
            formData.append('file[]', fileInput.files[i]);
        }
        if (typeof(csrfData) !== 'undefined') {
            formData.append(csrfData['token_name'], csrfData['hash']);
        }
        $.ajax({
            url: admin_url + 'dm_portal/task_upload_file/' + id,
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
            alert_float('success', '<?= _l('dm_portal_file_uploaded'); ?>');
            dm_dash_view_task(id);
        });
    }
</script>
</body>
</html>
