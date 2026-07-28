<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin"><?= e($title); ?></h4>
                        <hr class="hr-panel-separator" />

                        <div class="row dm-work-filters">
                            <div class="col-md-4 col-sm-6 tw-mb-3">
                                <select id="dm-filter-project" class="selectpicker" data-width="100%" data-live-search="true"
                                    data-none-selected-text="<?= _l('dm_portal_filter_all_projects'); ?>">
                                    <option value=""><?= _l('dm_portal_filter_all_projects'); ?></option>
                                    <?php foreach ($projects as $project) { ?>
                                    <option value="<?= (int) $project['id']; ?>"><?= e($project['name']); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="col-md-4 col-sm-6 tw-mb-3">
                                <select id="dm-filter-status" class="selectpicker" data-width="100%"
                                    data-none-selected-text="<?= _l('dm_portal_filter_all_statuses'); ?>">
                                    <option value=""><?= _l('dm_portal_filter_all_statuses'); ?></option>
                                    <?php foreach ($statuses as $status) { ?>
                                    <option value="<?= (int) $status['id']; ?>"><?= e($status['name']); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="col-md-4 col-sm-6 tw-mb-3">
                                <select id="dm-filter-priority" class="selectpicker" data-width="100%"
                                    data-none-selected-text="<?= _l('dm_portal_filter_all_priorities'); ?>">
                                    <option value=""><?= _l('dm_portal_filter_all_priorities'); ?></option>
                                    <?php foreach ($priorities as $priority) { ?>
                                    <option value="<?= (int) $priority['id']; ?>"><?= e($priority['name']); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                        <div class="clearfix mtop15"></div>

                        <?php render_datatable([
                            _l('dm_portal_column_project'),
                            _l('dm_portal_column_customer'),
                            _l('dm_portal_column_task'),
                            _l('dm_portal_column_priority'),
                            _l('dm_portal_column_deadline'),
                            _l('dm_portal_column_status'),
                            _l('dm_portal_col_notes'),
                            _l('dm_portal_column_progress'),
                            _l('dm_portal_column_actions'),
                        ], 'dm-my-work'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="dm-task-modal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title" id="dm-task-modal-title"></h4>
            </div>
            <div class="modal-body" id="dm-task-modal-body">
                <div class="text-center tw-py-4"><i class="fa-solid fa-spinner fa-spin"></i></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?= _l('close'); ?></button>
            </div>
        </div>
    </div>
</div>

<?php init_tail(); ?>
<script>
    // One stop per real task status (Tasks_model::get_statuses(), same
    // list the page's own Status filter already uses) - there is no
    // numeric progress column, so the Progress slider moves through
    // these 5 stops rather than a free 0-100 range; each stop carries
    // its own pre-rendered format_task_status() badge so the Status
    // cell can be patched after save without duplicating badge/color
    // logic in JS.
    var dmProgressStops = <?php
        $stops = [];
        foreach ($statuses as $s) {
            $stops[] = [
                'status'  => (int) $s['id'],
                'name'    => $s['name'],
                'percent' => dm_portal_task_progress_percent($s['id']),
                'badge'   => format_task_status($s['id']),
            ];
        }
        echo json_encode($stops);
    ?>;
    var DM_STATUS_COMPLETE = <?= Tasks_model::STATUS_COMPLETE; ?>;

    $(function() {
        // Notes/Progress/Actions (indices 6,7,8) have no real backing
        // column - same "derived columns, not sortable/searchable"
        // convention used throughout this fork's other module table
        // sources.
        initDataTable('.table-dm-my-work', admin_url + 'dm_portal/my_work_table', [6, 7, 8], [6, 7, 8], {
            project_id: '#dm-filter-project',
            status: '#dm-filter-status',
            priority: '#dm-filter-priority',
        }, [4, 'asc']);

        $('.dm-work-filters select').on('changed.bs.select', function() {
            $('.table-dm-my-work').DataTable().ajax.reload();
        });
    });

    function dm_reload_table() {
        $('.table-dm-my-work').DataTable().ajax.reload(null, false);
    }

    function dm_view_task(id, focusNotes) {
        $('#dm-task-modal-body').html('<div class="text-center tw-py-4"><i class="fa-solid fa-spinner fa-spin"></i></div>');
        $('#dm-task-modal').modal('show');
        $('#dm-task-modal').data('task-id', id);

        $.get(admin_url + 'dm_portal/task_details/' + id).done(function(response) {
            $('#dm-task-modal-body').html(response);
            if (focusNotes === true) {
                var $notes = $('#dm-task-modal-body .dm-note-input');
                if ($notes.length) {
                    $('html, body').animate({ scrollTop: 0 }, 0);
                    $notes.focus();
                }
            }
        });
    }

    // Progress column - view/edit toggle, both states already rendered
    // server-side (my_work_table()), just shown/hidden here.
    function dm_toggle_progress_edit(id) {
        $('#dm-progress-view-' + id).hide();
        $('#dm-progress-edit-' + id).show();
    }

    function dm_cancel_progress_edit(id) {
        $('#dm-progress-edit-' + id).hide();
        $('#dm-progress-view-' + id).show();
    }

    function dm_progress_slider_label(id) {
        var idx = parseInt($('#dm-progress-slider-' + id).val(), 10);
        $('#dm-progress-label-' + id).text(dmProgressStops[idx].name);
    }

    // Progress + Status only - no notes here, the View modal's Comments
    // section is the single place notes are entered. Reuses
    // task_update_progress()/task_mark_complete() exactly as the Actions
    // column's own buttons do (Complete is routed to task_mark_complete()
    // since task_update_progress() itself rejects it - same rule the old
    // Status dropdown already followed). On success, patches only this
    // row's Status/Progress cells directly - no DataTable reload.
    function dm_save_progress(id) {
        var idx  = parseInt($('#dm-progress-slider-' + id).val(), 10);
        var stop = dmProgressStops[idx];

        var statusRequest = (stop.status === DM_STATUS_COMPLETE)
            ? $.post(admin_url + 'dm_portal/task_mark_complete/' + id)
            : $.post(admin_url + 'dm_portal/task_update_progress/' + id, { status: stop.status });

        statusRequest.done(function(response) {
            var data = typeof response === 'string' ? JSON.parse(response) : response;
            if (data && data.success === false) {
                alert_float('danger', data.message);
                return;
            }

            $('#dm-progress-view-' + id + ' .progress-bar')
                .css('width', stop.percent + '%')
                .attr('aria-valuenow', stop.percent)
                .text(stop.percent + '%');
            $('#dm-status-' + id).html(stop.badge);
            $('#dm-progress-edit-' + id).hide();
            $('#dm-progress-view-' + id).show();

            if (stop.status === DM_STATUS_COMPLETE) {
                $('#dm_task_' + id + ' .btn-success').remove();
            }

            alert_float('success', '<?= _l('dm_portal_status_updated'); ?>');
        });
    }

    function dm_mark_complete(id) {
        if (!confirm('<?= _l('dm_portal_action_mark_complete'); ?>?')) {
            return;
        }
        $.post(admin_url + 'dm_portal/task_mark_complete/' + id).done(function() {
            alert_float('success', '<?= _l('dm_portal_task_completed'); ?>');
            dm_reload_table();
        });
    }

    function dm_add_note(id) {
        var content = $('#dm-task-modal-body .dm-note-input').val();
        $.post(admin_url + 'dm_portal/task_add_note/' + id, { content: content }).done(function(response) {
            var data = typeof response === 'string' ? JSON.parse(response) : response;
            if (data.success) {
                alert_float('success', '<?= _l('dm_portal_note_added'); ?>');
                dm_view_task(id);

                // The View modal's Comments section is the only place
                // notes are entered - patch the row's Notes column here
                // so it reflects the new latest note immediately, without
                // a DataTable reload.
                var trimmed = content.trim();
                if (trimmed) {
                    var preview = trimmed.length > 50 ? trimmed.slice(0, 50) + '...' : trimmed;
                    $('#dm-notes-' + id).text(preview).attr('title', trimmed);
                }
            } else {
                alert_float('danger', data.message);
            }
        });
    }

    function dm_upload_file(id) {
        var formData = new FormData();
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
            dm_view_task(id);
        });
    }
</script>
