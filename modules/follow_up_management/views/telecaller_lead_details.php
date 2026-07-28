<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<?php
/**
 * Telecaller Lead page - Step 4. Lead Information/Meeting History/Meeting
 * Notes/Attachments/Timeline are the exact same shared components
 * modules/field_portal's own Lead Details page uses
 * (application/helpers/lead_workspace_helper.php), rendered read-only
 * here per the spec ("Everything should be read-only except Telecaller
 * actions") - only Call History/Follow-up History/Current Status/
 * Original Field Executive and the 4 outcome actions are new to this
 * page. Add Call/Continue Follow-up/Not Interested/Customer Ready all
 * submit to the EXISTING follow_up_management/save_workspace/<case id>
 * endpoint (the same one the embedded Task-modal Workspace already
 * uses) - only "Needs Another Visit" needed a new action.
 */
$next_followup_value = $case->next_follow_up_date ? _d($case->next_follow_up_date) : '';
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
                                <span class="label label-info"><?= e($lead->status_name); ?></span>
                                <span class="label label-default"><?= e(get_followup_priority_label($case->priority)); ?></span>
                                <?php if ($is_converted) { ?>
                                <span class="label label-success"><?= _l('field_portal_converted'); ?></span>
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
                                <div class="text-muted"><?= _l('field_portal_field_email'); ?></div>
                                <div><?= $lead->email ? e($lead->email) : '-'; ?></div>
                            </div>
                            <div class="col-xs-12 col-sm-4 tw-mb-3">
                                <div class="text-muted"><?= _l('field_portal_field_address'); ?></div>
                                <div><?= $lead->address ? nl2br(e($lead->address)) : '-'; ?></div>
                            </div>
                            <div class="col-xs-12 tw-mb-3">
                                <div class="text-muted"><?= _l('field_portal_field_requirements'); ?></div>
                                <div><?= $lead->description ? nl2br(e(strip_tags($lead->description))) : '-'; ?></div>
                            </div>
                            <div class="col-xs-6 col-sm-4 tw-mb-3">
                                <div class="text-muted"><?= _l('followup_workspace_next_follow_up'); ?></div>
                                <div><?= $next_followup_value ? e($next_followup_value) : '-'; ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <?php render_lead_workspace_followup_history($followup_history); ?>

                <?php render_lead_workspace_meetings($meetings, $lead->id, $task_statuses, true); ?>
            </div>

            <div class="col-md-4">
                <?php render_lead_workspace_overview($overview); ?>

                <?php render_lead_workspace_progress_tracker($lead->status_name ?? '', $is_converted, $case->lost_reason ?? ''); ?>

                <?php if ($is_converted) { ?>
                <?php render_lead_workspace_customer_summary($customer_data); ?>
                <?php } ?>

                <div class="panel_s">
                    <div class="panel-body">
                        <h5 class="no-margin tw-mb-3"><i class="fa-solid fa-bolt text-muted"></i> <?= _l('telecaller_actions'); ?></h5>
                        <div class="tw-flex tw-flex-col tw-gap-2">
                            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#telecaller-add-call-modal"><i class="fa-solid fa-phone"></i> <?= _l('telecaller_add_call'); ?></button>
                            <button type="button" class="btn btn-default btn-sm" data-toggle="modal" data-target="#telecaller-continue-followup-modal"><i class="fa-solid fa-calendar-plus"></i> <?= _l('telecaller_continue_followup'); ?></button>
                            <button type="button" class="btn btn-success btn-sm" onclick="telecaller_customer_ready(<?= (int) $case->id; ?>)"><i class="fa-solid fa-circle-check"></i> <?= _l('telecaller_customer_ready'); ?></button>
                            <?php if ((int) $lead->status === (int) $customer_confirmed_status_id && !$is_converted) { ?>
                            <button type="button" class="btn btn-info btn-sm" onclick="telecaller_convert_customer(<?= (int) $lead->id; ?>)"><i class="fa-solid fa-building"></i> <?= _l('followup_convert_to_customer'); ?></button>
                            <?php } ?>
                            <button type="button" class="btn btn-warning btn-sm" onclick="telecaller_needs_another_visit(<?= (int) $lead->id; ?>)"><i class="fa-solid fa-person-walking-arrow-loop-left"></i> <?= _l('telecaller_needs_another_visit'); ?></button>
                            <button type="button" class="btn btn-danger btn-sm" data-toggle="modal" data-target="#telecaller-not-interested-modal"><i class="fa-solid fa-circle-xmark"></i> <?= _l('telecaller_not_interested'); ?></button>
                        </div>
                    </div>
                </div>

                <?php render_lead_workspace_timeline($timeline); ?>
            </div>
        </div>
    </div>
</div>

<div id="telecaller-add-call-modal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="telecaller-add-call-form">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title"><?= _l('telecaller_add_call'); ?></h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-xs-6">
                            <div class="form-group">
                                <label class="control-label"><?= _l('telecaller_call_date'); ?></label>
                                <input type="text" class="form-control datepicker" name="call_date" autocomplete="off">
                            </div>
                        </div>
                        <div class="col-xs-6">
                            <div class="form-group">
                                <label class="control-label"><?= _l('telecaller_call_time'); ?></label>
                                <input type="text" class="form-control" name="call_time" placeholder="<?= _l('field_portal_meeting_time_placeholder'); ?>">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="control-label"><?= _l('telecaller_call_outcome'); ?></label>
                        <select class="selectpicker" name="outcome" data-width="100%">
                            <?php foreach ($outcomes as $outcome_key => $outcome_label) { ?>
                            <option value="<?= e($outcome_key); ?>"><?= e($outcome_label); ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="control-label"><?= _l('field_portal_column_status'); ?></label>
                        <select class="selectpicker" name="lead_status" data-width="100%">
                            <option value=""><?= _l('field_portal_keep_current_status'); ?></option>
                            <?php foreach ($leads_statuses as $st) { ?>
                            <option value="<?= (int) $st['id']; ?>" <?= (int) $st['id'] === (int) $lead->status ? 'selected' : ''; ?>><?= e($st['name']); ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="control-label"><?= _l('telecaller_customer_response'); ?></label>
                        <textarea class="form-control" name="customer_response" rows="2"></textarea>
                    </div>
                    <div class="form-group">
                        <label class="control-label"><?= _l('field_portal_field_discussion_summary'); ?></label>
                        <textarea class="form-control" name="discussion_summary" rows="2"></textarea>
                    </div>
                    <div class="form-group">
                        <label class="control-label"><?= _l('telecaller_next_followup'); ?></label>
                        <input type="text" class="form-control datepicker" name="next_follow_up_date" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label class="control-label"><?= _l('field_portal_field_remarks'); ?></label>
                        <textarea class="form-control" name="remarks" rows="2"></textarea>
                    </div>
                    <div class="form-group">
                        <label class="control-label"><?= _l('field_portal_field_attachments'); ?></label>
                        <input type="file" class="form-control" id="telecaller-call-files" multiple>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?= _l('close'); ?></button>
                    <button type="submit" class="btn btn-primary" id="telecaller-add-call-submit"><?= _l('telecaller_add_call'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="telecaller-continue-followup-modal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="telecaller-continue-followup-form">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title"><?= _l('telecaller_continue_followup'); ?></h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label class="control-label"><?= _l('telecaller_next_followup'); ?> <span class="text-danger">*</span></label>
                        <input type="text" class="form-control datepicker" name="next_follow_up_date" required autocomplete="off">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?= _l('close'); ?></button>
                    <button type="submit" class="btn btn-primary" id="telecaller-continue-followup-submit"><?= _l('telecaller_continue_followup'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="telecaller-not-interested-modal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="telecaller-not-interested-form">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title"><?= _l('telecaller_not_interested'); ?></h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label class="control-label"><?= _l('field_portal_lost_reason'); ?> <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="lost_reason" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?= _l('close'); ?></button>
                    <button type="submit" class="btn btn-danger" id="telecaller-not-interested-submit"><?= _l('telecaller_not_interested'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php init_tail(); ?>
<script>
    var TELECALLER_CASE_ID = <?= (int) $case->id; ?>;
    var TELECALLER_LEAD_ID = <?= (int) $lead->id; ?>;
    var TELECALLER_CUSTOMER_CONFIRMED_STATUS_ID = <?= (int) $customer_confirmed_status_id; ?>;
    var TELECALLER_LOST_STATUS_ID = <?= (int) $lost_status_id; ?>;

    function telecaller_csrf_data(data) {
        data = data || {};
        if (typeof(csrfData) !== 'undefined') {
            data[csrfData['token_name']] = csrfData['hash'];
        }
        return data;
    }

    function telecaller_save_workspace(data, onDone) {
        $.post(admin_url + 'follow_up_management/save_workspace/' + TELECALLER_CASE_ID, telecaller_csrf_data(data)).done(function(response) {
            var result = typeof response === 'string' ? JSON.parse(response) : response;
            if (result.alert_type === 'success') {
                onDone();
            } else {
                alert_float('danger', result.message);
            }
        });
    }

    $(function() {
        init_selectpicker();
        init_datepicker();

        $('#telecaller-add-call-form').on('submit', function(e) {
            e.preventDefault();

            var form = $(this);
            var notesParts = [];
            var callDate = form.find('[name="call_date"]').val();
            var callTime = form.find('[name="call_time"]').val();

            if (callDate || callTime) {
                notesParts.push('<?= _l('telecaller_call_date'); ?>/<?= _l('telecaller_call_time'); ?>: ' + callDate + ' ' + callTime);
            }
            if (form.find('[name="customer_response"]').val()) {
                notesParts.push('<?= _l('telecaller_customer_response'); ?>: ' + form.find('[name="customer_response"]').val());
            }
            if (form.find('[name="discussion_summary"]').val()) {
                notesParts.push('<?= _l('field_portal_field_discussion_summary'); ?>: ' + form.find('[name="discussion_summary"]').val());
            }
            if (form.find('[name="remarks"]').val()) {
                notesParts.push('<?= _l('field_portal_field_remarks'); ?>: ' + form.find('[name="remarks"]').val());
            }

            telecaller_save_workspace({
                outcome: form.find('[name="outcome"]').val(),
                notes: notesParts.join("\n\n"),
                next_follow_up_date: form.find('[name="next_follow_up_date"]').val(),
                lead_status: form.find('[name="lead_status"]').val()
            }, function() {
                var files = document.getElementById('telecaller-call-files').files;
                if (files.length > 0) {
                    var uploadData = new FormData();
                    for (var i = 0; i < files.length; i++) {
                        uploadData.append('file[]', files[i]);
                    }
                    if (typeof(csrfData) !== 'undefined') {
                        uploadData.append(csrfData['token_name'], csrfData['hash']);
                    }
                    $.ajax({
                        url: admin_url + 'follow_up_management/lead_add_call_attachment/' + TELECALLER_LEAD_ID,
                        type: 'POST',
                        data: uploadData,
                        contentType: false,
                        processData: false,
                    }).always(function() {
                        location.reload();
                    });
                } else {
                    location.reload();
                }
            });
        });

        $('#telecaller-continue-followup-form').on('submit', function(e) {
            e.preventDefault();
            telecaller_save_workspace({
                next_follow_up_date: $(this).find('[name="next_follow_up_date"]').val()
            }, function() {
                location.reload();
            });
        });

        $('#telecaller-not-interested-form').on('submit', function(e) {
            e.preventDefault();
            telecaller_save_workspace({
                lead_status: TELECALLER_LOST_STATUS_ID,
                lost_reason: $(this).find('[name="lost_reason"]').val()
            }, function() {
                location.reload();
            });
        });
    });

    function telecaller_customer_ready(caseId) {
        if (!confirm('<?= _l('telecaller_confirm_customer_ready'); ?>')) {
            return;
        }
        telecaller_save_workspace({
            lead_status: TELECALLER_CUSTOMER_CONFIRMED_STATUS_ID
        }, function() {
            alert_float('success', '<?= _l('field_portal_convert_success'); ?>');
            location.reload();
        });
    }

    function telecaller_convert_customer(leadId) {
        if (!confirm('<?= _l('telecaller_confirm_convert_customer'); ?>')) {
            return;
        }
        $.post(admin_url + 'follow_up_management/convert_customer/' + leadId, telecaller_csrf_data()).done(function(response) {
            var data = typeof response === 'string' ? JSON.parse(response) : response;
            if (data.success) {
                alert_float('success', '<?= _l('field_portal_convert_success'); ?>');
                if (data.redirect) {
                    location.href = data.redirect;
                } else {
                    location.reload();
                }
            } else {
                alert_float('danger', data.message);
            }
        });
    }

    function telecaller_needs_another_visit(leadId) {
        if (!confirm('<?= _l('telecaller_confirm_needs_another_visit'); ?>')) {
            return;
        }
        $.post(admin_url + 'follow_up_management/return_to_field_executive/' + leadId, telecaller_csrf_data()).done(function(response) {
            var data = typeof response === 'string' ? JSON.parse(response) : response;
            if (data.success) {
                location.href = admin_url + 'follow_up_management/my_follow_ups';
            } else {
                alert_float('danger', data.message);
            }
        });
    }
</script>
</body>
</html>
