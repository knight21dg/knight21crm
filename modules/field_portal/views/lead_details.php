<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<?php
/**
 * Lead Details - the central workspace: everything about one Lead lives
 * on this single page (info, notes, attachments, activity timeline,
 * convert action) instead of scattered across separate pages, per the
 * requirement. Meetings/Follow-ups sections are honest placeholders
 * until Steps 3-4 add their backing data.
 */
$priority_value        = get_custom_field_value($lead->id, 'leads_priority', 'leads');
$business_category_value = get_custom_field_value($lead->id, 'leads_service_required', 'leads');
$budget_value           = get_custom_field_value($lead->id, 'leads_budget', 'leads');
$alt_phone_value        = get_custom_field_value($lead->id, 'leads_alternate_phone', 'leads');
$maps_value             = get_custom_field_value($lead->id, 'leads_google_maps_location', 'leads');
$remarks_value          = get_custom_field_value($lead->id, 'leads_remarks', 'leads');
$next_followup_value    = get_custom_field_value($lead->id, 'leads_next_follow_up_date', 'leads');
?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-8">
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="tw-flex tw-items-center tw-justify-between tw-flex-wrap">
                            <div>
                                <h4 class="no-margin"><?= e($lead->name); ?></h4>
                                <span class="text-muted"><?= $lead->company ? e($lead->company) : '-'; ?></span>
                            </div>
                            <div class="tw-flex tw-items-center tw-gap-2 tw-flex-wrap">
                                <?php if ($is_converted) { ?>
                                <span class="label label-success"><?= _l('field_portal_converted'); ?></span>
                                <a href="<?= admin_url('field_portal/customers'); ?>" class="btn btn-default btn-sm"><i class="fa-solid fa-users"></i> <?= _l('field_portal_view_customer'); ?></a>
                                <?php } elseif ($active_case) { ?>
                                <span class="label label-warning"><i class="fa-solid fa-headset"></i> <?= _l('field_portal_with_telecaller', [e(get_staff_full_name($active_case->assigned_staff_id))]); ?></span>
                                <?php } else { ?>
                                <button type="button" class="btn btn-success btn-sm" onclick="field_portal_convert_lead(<?= (int) $lead->id; ?>)"><i class="fa-solid fa-circle-check"></i> <?= _l('field_portal_convert_to_customer'); ?></button>
                                <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#field-portal-assign-telecaller-modal"><i class="fa-solid fa-headset"></i> <?= _l('field_portal_assign_to_telecaller'); ?></button>
                                <button type="button" class="btn btn-danger btn-sm" data-toggle="modal" data-target="#field-portal-mark-lost-modal"><i class="fa-solid fa-circle-xmark"></i> <?= _l('field_portal_mark_lost'); ?></button>
                                <?php } ?>
                            </div>
                        </div>
                        <hr class="hr-panel-separator" />

                        <div class="row">
                            <div class="col-xs-6 col-sm-4 tw-mb-3">
                                <div class="text-muted"><?= _l('field_portal_field_phone'); ?></div>
                                <div><?= $lead->phonenumber ? e($lead->phonenumber) : '-'; ?></div>
                            </div>
                            <div class="col-xs-6 col-sm-4 tw-mb-3">
                                <div class="text-muted"><?= _l('field_portal_field_alt_phone'); ?></div>
                                <div><?= $alt_phone_value ? e($alt_phone_value) : '-'; ?></div>
                            </div>
                            <div class="col-xs-6 col-sm-4 tw-mb-3">
                                <div class="text-muted"><?= _l('field_portal_field_email'); ?></div>
                                <div><?= $lead->email ? e($lead->email) : '-'; ?></div>
                            </div>
                            <div class="col-xs-12 col-sm-4 tw-mb-3">
                                <div class="text-muted"><?= _l('field_portal_field_address'); ?></div>
                                <div><?= $lead->address ? nl2br(e($lead->address)) : '-'; ?></div>
                            </div>
                            <div class="col-xs-12 col-sm-4 tw-mb-3">
                                <div class="text-muted"><?= _l('field_portal_field_maps_location'); ?></div>
                                <div><?php if ($maps_value) { ?><a href="<?= e($maps_value); ?>" target="_blank"><?= e($maps_value); ?></a><?php } else { ?>-<?php } ?></div>
                            </div>
                            <div class="col-xs-6 col-sm-4 tw-mb-3">
                                <div class="text-muted"><?= _l('field_portal_field_source'); ?></div>
                                <div><?= $lead->source_name ? e($lead->source_name) : '-'; ?></div>
                            </div>
                            <div class="col-xs-6 col-sm-4 tw-mb-3">
                                <div class="text-muted"><?= _l('field_portal_field_business_category'); ?></div>
                                <div><?= $business_category_value ? e($business_category_value) : '-'; ?></div>
                            </div>
                            <div class="col-xs-6 col-sm-4 tw-mb-3">
                                <div class="text-muted"><?= _l('field_portal_field_priority'); ?></div>
                                <div><?= $priority_value ? e($priority_value) : '-'; ?></div>
                            </div>
                            <div class="col-xs-6 col-sm-4 tw-mb-3">
                                <div class="text-muted"><?= _l('field_portal_field_budget'); ?></div>
                                <div><?= $budget_value !== '' ? e($budget_value) : '-'; ?></div>
                            </div>
                            <div class="col-xs-6 col-sm-4 tw-mb-3">
                                <div class="text-muted"><?= _l('field_portal_field_next_followup'); ?></div>
                                <div><?= $next_followup_value ? e($next_followup_value) : '-'; ?></div>
                            </div>
                            <div class="col-xs-12 tw-mb-3">
                                <div class="text-muted"><?= _l('field_portal_field_requirements'); ?></div>
                                <div><?= $lead->description ? nl2br(e(strip_tags($lead->description))) : '-'; ?></div>
                            </div>
                            <div class="col-xs-12 tw-mb-3">
                                <div class="text-muted"><?= _l('field_portal_field_remarks'); ?></div>
                                <div><?= $remarks_value ? nl2br(e($remarks_value)) : '-'; ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <?php render_lead_workspace_meetings($meetings, $lead->id, $task_statuses, false, $maps_value); ?>

                <?php render_lead_workspace_followup_history($followup_history); ?>

                <div class="panel_s">
                    <div class="panel-body">
                        <h5 class="no-margin tw-mb-3"><i class="fa-solid fa-note-sticky text-muted"></i> <?= _l('field_portal_notes'); ?></h5>

                        <div class="tw-flex tw-gap-2 tw-mb-3">
                            <textarea id="field-portal-note-input" class="form-control" rows="2" placeholder="<?= _l('field_portal_add_note_placeholder'); ?>"></textarea>
                            <button type="button" class="btn btn-primary" onclick="field_portal_add_note(<?= (int) $lead->id; ?>)"><i class="fa-solid fa-paper-plane"></i></button>
                        </div>

                        <div id="field-portal-notes-list">
                            <?php if (empty($notes)) { ?>
                            <span class="text-muted"><?= _l('field_portal_no_notes'); ?></span>
                            <?php } else { foreach ($notes as $note) { ?>
                            <div class="tw-mb-2 tw-pb-2" style="border-bottom:1px solid #eee;" id="field-portal-note-<?= (int) $note['id']; ?>">
                                <div class="tw-flex tw-items-center tw-justify-between">
                                    <span class="tw-font-medium"><?= e(trim(($note['firstname'] ?? '') . ' ' . ($note['lastname'] ?? ''))); ?></span>
                                    <div>
                                        <span class="text-muted tw-text-xs"><?= e(_dt($note['dateadded'])); ?></span>
                                        <button type="button" class="btn btn-link btn-xs text-danger" onclick="field_portal_delete_note(<?= (int) $note['id']; ?>, <?= (int) $lead->id; ?>)"><i class="fa-solid fa-trash"></i></button>
                                    </div>
                                </div>
                                <div><?= nl2br(e($note['description'])); ?></div>
                            </div>
                            <?php } } ?>
                        </div>
                    </div>
                </div>

                <div class="panel_s">
                    <div class="panel-body">
                        <div class="tw-flex tw-items-center tw-justify-between tw-mb-3">
                            <h5 class="no-margin"><i class="fa-solid fa-paperclip text-muted"></i> <?= _l('field_portal_attachments'); ?></h5>
                            <label class="btn btn-default btn-sm no-margin" style="cursor:pointer;">
                                <i class="fa-solid fa-upload"></i> <?= _l('field_portal_upload'); ?>
                                <input type="file" id="field-portal-attachment-input" multiple style="display:none;" onchange="field_portal_upload_attachment(<?= (int) $lead->id; ?>)">
                            </label>
                        </div>

                        <div id="field-portal-attachments-list">
                            <?php if (empty($lead->attachments)) { ?>
                            <span class="text-muted"><?= _l('field_portal_no_attachments'); ?></span>
                            <?php } else { foreach ($lead->attachments as $attachment) { ?>
                            <div class="tw-flex tw-items-center tw-justify-between tw-mb-2" id="field-portal-attachment-<?= (int) $attachment->id; ?>">
                                <a href="<?= base_url('uploads/leads/' . $lead->id . '/' . $attachment->file_name); ?>" target="_blank"><i class="fa-solid fa-file tw-text-gray-400"></i> <?= e($attachment->file_name); ?></a>
                                <button type="button" class="btn btn-link btn-xs text-danger" onclick="field_portal_delete_attachment(<?= (int) $attachment->id; ?>, <?= (int) $lead->id; ?>)"><i class="fa-solid fa-trash"></i></button>
                            </div>
                            <?php } } ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <?php render_lead_workspace_progress_tracker($overview['current_status_name'] ?? '', $is_converted, $overview['lost_reason'] ?? ''); ?>
                <?php render_lead_workspace_overview($overview); ?>
                <?php if ($is_converted && isset($customer_data)) { ?>
                <?php render_lead_workspace_customer_summary($customer_data); ?>
                <?php } ?>
                <?php render_lead_workspace_timeline($timeline); ?>
            </div>
        </div>
    </div>
</div>

<?php if (!$is_converted && !$active_case) { ?>
<div id="field-portal-assign-telecaller-modal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="field-portal-assign-telecaller-form">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title"><?= _l('field_portal_assign_to_telecaller'); ?></h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label class="control-label"><?= _l('field_portal_telecaller'); ?> <span class="text-danger">*</span></label>
                        <select class="selectpicker" name="telecaller_id" data-width="100%" data-none-selected-text="<?= _l('dropdown_non_selected_tex'); ?>" required>
                            <option value=""></option>
                            <?php foreach ($telecallers as $telecaller) { ?>
                            <option value="<?= (int) $telecaller['staffid']; ?>"><?= e($telecaller['name']); ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="control-label"><?= _l('field_portal_priority'); ?></label>
                        <select class="selectpicker" name="priority" data-width="100%">
                            <?php foreach (get_followup_priorities() as $priority_key => $priority_label) { ?>
                            <option value="<?= e($priority_key); ?>" <?= $priority_key === 'medium' ? 'selected' : ''; ?>><?= e($priority_label); ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="control-label"><?= _l('field_portal_assign_reason'); ?></label>
                        <textarea class="form-control" name="reason" rows="2" placeholder="<?= _l('field_portal_assign_reason'); ?>"></textarea>
                    </div>
                    <div class="form-group">
                        <label class="control-label"><?= _l('field_portal_meeting_date'); ?></label>
                        <input type="text" class="form-control datepicker" name="follow_up_date" autocomplete="off" placeholder="<?= _l('field_portal_meeting_date'); ?>">
                    </div>
                    <div class="form-group">
                        <label class="control-label"><?= _l('field_portal_field_remarks'); ?></label>
                        <textarea class="form-control" name="remarks" rows="2" placeholder="<?= _l('field_portal_field_remarks'); ?>"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?= _l('close'); ?></button>
                    <button type="submit" class="btn btn-primary" id="field-portal-assign-telecaller-submit"><?= _l('field_portal_assign_to_telecaller'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="field-portal-mark-lost-modal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="field-portal-mark-lost-form">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title"><?= _l('field_portal_mark_lost'); ?></h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label class="control-label"><?= _l('field_portal_lost_reason'); ?> <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="reason" rows="3" required placeholder="<?= _l('field_portal_lost_reason'); ?>"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?= _l('close'); ?></button>
                    <button type="submit" class="btn btn-danger" id="field-portal-mark-lost-submit"><?= _l('field_portal_mark_lost'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php } ?>

<?php init_tail(); ?>
<script>
    // Every plain $.post() below needs the CSRF token manually merged into
    // its data object - unlike DataTables' own ajax.data callback
    // (assets/js/main.js), there is no global jQuery interceptor that
    // injects it automatically for arbitrary POSTs.
    function field_portal_csrf_data(data) {
        data = data || {};
        if (typeof(csrfData) !== 'undefined') {
            data[csrfData['token_name']] = csrfData['hash'];
        }
        return data;
    }

    function field_portal_add_note(leadId) {
        var content = $('#field-portal-note-input').val();
        if (!content.trim()) {
            return;
        }
        $.post(admin_url + 'field_portal/lead_add_note/' + leadId, field_portal_csrf_data({ description: content })).done(function(response) {
            var data = typeof response === 'string' ? JSON.parse(response) : response;
            if (data.success) {
                location.reload();
            } else {
                alert_float('danger', data.message);
            }
        });
    }

    function field_portal_delete_note(id, leadId) {
        if (!confirm('<?= _l('field_portal_confirm_delete_note'); ?>')) {
            return;
        }
        $.post(admin_url + 'field_portal/lead_delete_note/' + id + '/' + leadId, field_portal_csrf_data()).done(function() {
            $('#field-portal-note-' + id).remove();
        });
    }

    function field_portal_upload_attachment(leadId) {
        var files = document.getElementById('field-portal-attachment-input').files;
        if (!files.length) {
            return;
        }
        var formData = new FormData();
        for (var i = 0; i < files.length; i++) {
            formData.append('file[]', files[i]);
        }
        if (typeof(csrfData) !== 'undefined') {
            formData.append(csrfData['token_name'], csrfData['hash']);
        }
        $.ajax({
            url: admin_url + 'field_portal/lead_add_attachment/' + leadId,
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
        }).done(function() {
            location.reload();
        });
    }

    function field_portal_delete_attachment(id, leadId) {
        if (!confirm('<?= _l('field_portal_confirm_delete_attachment'); ?>')) {
            return;
        }
        $.post(admin_url + 'field_portal/lead_delete_attachment/' + id + '/' + leadId, field_portal_csrf_data()).done(function() {
            $('#field-portal-attachment-' + id).remove();
        });
    }

    function field_portal_convert_lead(leadId) {
        if (!confirm('<?= _l('field_portal_confirm_convert'); ?>')) {
            return;
        }
        $.post(admin_url + 'field_portal/lead_convert/' + leadId, field_portal_csrf_data()).done(function(response) {
            var data = typeof response === 'string' ? JSON.parse(response) : response;
            if (data.success) {
                alert_float('success', '<?= _l('field_portal_convert_success'); ?>');
                location.reload();
            } else {
                alert_float('danger', data.message);
            }
        });
    }

    function field_portal_meeting_status(taskId, status) {
        $.post(admin_url + 'field_portal/meeting_update_status/' + taskId, field_portal_csrf_data({ status: status })).done(function() {
            alert_float('success', '<?= _l('field_portal_meeting_status_updated'); ?>');
        });
    }

    function field_portal_meeting_add_note(taskId) {
        var content = $('#meeting-note-input-' + taskId).val();
        if (!content.trim()) {
            return;
        }
        $.post(admin_url + 'field_portal/meeting_add_note/' + taskId, field_portal_csrf_data({ content: content })).done(function(response) {
            var data = typeof response === 'string' ? JSON.parse(response) : response;
            if (data.success) {
                location.reload();
            } else {
                alert_float('danger', data.message);
            }
        });
    }

    function field_portal_meeting_upload_attachment(taskId) {
        var files = document.getElementById('meeting-attachment-input-' + taskId).files;
        if (!files.length) {
            return;
        }
        var formData = new FormData();
        for (var i = 0; i < files.length; i++) {
            formData.append('file[]', files[i]);
        }
        if (typeof(csrfData) !== 'undefined') {
            formData.append(csrfData['token_name'], csrfData['hash']);
        }
        $.ajax({
            url: admin_url + 'field_portal/meeting_add_attachment/' + taskId,
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
        }).done(function() {
            location.reload();
        });
    }

    function field_portal_meeting_delete_attachment(id, taskId) {
        if (!confirm('<?= _l('field_portal_confirm_delete_attachment'); ?>')) {
            return;
        }
        $.post(admin_url + 'field_portal/meeting_delete_attachment/' + id + '/' + taskId, field_portal_csrf_data()).done(function() {
            $('#meeting-attachment-' + id).remove();
        });
    }

    $(function() {
        init_selectpicker();
        init_datepicker();

        $('#field-portal-assign-telecaller-form').on('submit', function(e) {
            e.preventDefault();

            var $submit = $('#field-portal-assign-telecaller-submit');
            $submit.prop('disabled', true);

            var formData = $(this).serializeArray();
            if (typeof(csrfData) !== 'undefined') {
                formData.push({ name: csrfData['token_name'], value: csrfData['hash'] });
            }

            $.post(admin_url + 'field_portal/lead_assign_to_telecaller_submit/<?= (int) $lead->id; ?>', $.param(formData)).done(function(response) {
                var data = typeof response === 'string' ? JSON.parse(response) : response;
                if (data.success) {
                    location.reload();
                } else {
                    alert_float('danger', data.message);
                    $submit.prop('disabled', false);
                }
            }).fail(function() {
                alert_float('danger', '<?= _l('field_portal_lead_create_failed'); ?>');
                $submit.prop('disabled', false);
            });
        });

        $('#field-portal-mark-lost-form').on('submit', function(e) {
            e.preventDefault();

            var $submit = $('#field-portal-mark-lost-submit');
            $submit.prop('disabled', true);

            var formData = $(this).serializeArray();
            if (typeof(csrfData) !== 'undefined') {
                formData.push({ name: csrfData['token_name'], value: csrfData['hash'] });
            }

            $.post(admin_url + 'field_portal/lead_mark_lost_submit/<?= (int) $lead->id; ?>', $.param(formData)).done(function(response) {
                var data = typeof response === 'string' ? JSON.parse(response) : response;
                if (data.success) {
                    location.reload();
                } else {
                    alert_float('danger', data.message);
                    $submit.prop('disabled', false);
                }
            }).fail(function() {
                alert_float('danger', '<?= _l('field_portal_lead_create_failed'); ?>');
                $submit.prop('disabled', false);
            });
        });
    });
</script>
</body>
</html>
