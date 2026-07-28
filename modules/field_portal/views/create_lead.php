<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<?php
/**
 * Unified Create/Edit Lead form. When $lead is set, the form operates in
 * edit mode (pre-fills every field, shows "Edit Lead" title and
 * Save/Cancel buttons). When $lead is absent, it shows the blank Create
 * Lead form. Every field maps straight onto Leads_model::add/update()
 * -recognized POST keys - native columns (name/company/phonenumber/email/
 * address/source/description) plus custom_fields[leads][<id>] for the
 * 6 custom fields - so submitting is functionally identical to the native
 * Lead form, just with a smaller, portal-specific field set.
 */
$is_edit      = isset($lead) && $lead;
$lead_id      = $is_edit ? (int) $lead->id : 0;
$form_action  = $is_edit ? 'edit_lead_submit/' . $lead_id : 'create_lead_submit';
$page_title   = $is_edit ? _l('field_portal_edit_lead') : _l('field_portal_create_lead');
$page_hint    = $is_edit ? _l('field_portal_edit_lead_hint') : _l('field_portal_create_lead_hint');
$submit_label = $is_edit ? _l('field_portal_save_changes') : _l('field_portal_save_lead');
$cancel_url   = $is_edit ? admin_url('field_portal/lead_details/' . $lead_id) : admin_url('field_portal/my_leads');

// Determine locked state for assigned/converted leads (edit mode only).
$lock_cf_fields = $is_edit && (isset($is_assigned) && $is_assigned || isset($is_converted) && $is_converted);

// Helper: pre-fill a value from the lead object.
function _edit_val($lead, $field, $default = '') {
    return isset($lead) && isset($lead->$field) ? $lead->$field : $default;
}

// Helper: pre-fill a custom field value.
function _edit_cfv($values, $field_id, $default = '') {
    return isset($values) && isset($values[(int) $field_id]) ? $values[(int) $field_id] : $default;
}

// Helper: check if a select option should be selected.
function _edit_selected($lead, $field, $value) {
    return isset($lead) && isset($lead->$field) && (string) $lead->$field === (string) $value;
}

$field_ids = [
    'business_category' => $business_category_field ? (int) $business_category_field['id'] : 0,
    'priority'           => $priority_field ? (int) $priority_field['id'] : 0,
    'budget'             => $budget_field ? (int) $budget_field['id'] : 0,
    'alt_phone'          => $alt_phone_field ? (int) $alt_phone_field['id'] : 0,
    'maps'               => $maps_field ? (int) $maps_field['id'] : 0,
    'remarks'            => $remarks_field ? (int) $remarks_field['id'] : 0,
];
?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-8 col-md-offset-2">
                <div class="panel_s">
                    <div class="panel-body">
                        <?php if ($is_edit) { ?>
                        <a href="<?= $cancel_url; ?>" class="btn btn-default pull-right"><i class="fa-solid fa-arrow-left"></i> <?= _l('field_portal_back_to_lead'); ?></a>
                        <?php } ?>
                        <h4 class="no-margin"><?= $page_title; ?></h4>
                        <p class="text-muted"><?= $page_hint; ?></p>
                        <hr class="hr-panel-separator" />

                        <form id="field-portal-create-lead-form" data-lead-id="<?= $lead_id; ?>">
                            <div class="form-group">
                                <label class="control-label"><?= _l('field_portal_field_company'); ?></label>
                                <input type="text" class="form-control input-lg" name="company" value="<?= e(_edit_val($lead, 'company')); ?>" placeholder="<?= _l('field_portal_field_company'); ?>">
                            </div>
                            <div class="form-group">
                                <label class="control-label"><?= _l('field_portal_field_contact_person'); ?> <span class="text-danger">*</span></label>
                                <input type="text" class="form-control input-lg" name="name" value="<?= e(_edit_val($lead, 'name')); ?>" required placeholder="<?= _l('field_portal_field_contact_person'); ?>">
                            </div>
                            <div class="row">
                                <div class="col-xs-6">
                                    <div class="form-group">
                                        <label class="control-label"><?= _l('field_portal_field_phone'); ?></label>
                                        <input type="text" class="form-control input-lg" name="phonenumber" value="<?= e(_edit_val($lead, 'phonenumber')); ?>" placeholder="<?= _l('field_portal_field_phone'); ?>">
                                    </div>
                                </div>
                                <div class="col-xs-6">
                                    <div class="form-group">
                                        <label class="control-label"><?= _l('field_portal_field_alt_phone'); ?></label>
                                        <input type="text" class="form-control input-lg" name="custom_fields[leads][<?= $field_ids['alt_phone']; ?>]" value="<?= e(_edit_cfv($custom_fields_values ?? [], $field_ids['alt_phone'])); ?>" placeholder="<?= _l('field_portal_field_alt_phone'); ?>" <?= $lock_cf_fields ? 'readonly' : ''; ?>>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label"><?= _l('field_portal_field_email'); ?></label>
                                <input type="email" class="form-control input-lg" name="email" value="<?= e(_edit_val($lead, 'email')); ?>" placeholder="<?= _l('field_portal_field_email'); ?>">
                            </div>
                            <div class="form-group">
                                <label class="control-label"><?= _l('field_portal_field_address'); ?></label>
                                <textarea class="form-control" name="address" rows="2" placeholder="<?= _l('field_portal_field_address'); ?>"><?= e(_edit_val($lead, 'address')); ?></textarea>
                            </div>
                            <div class="form-group">
                                <label class="control-label"><?= _l('field_portal_field_maps_location'); ?></label>
                                <input type="text" class="form-control input-lg" name="custom_fields[leads][<?= $field_ids['maps']; ?>]" value="<?= e(_edit_cfv($custom_fields_values ?? [], $field_ids['maps'])); ?>" placeholder="https://maps.google.com/..." <?= $lock_cf_fields ? 'readonly' : ''; ?>>
                            </div>
                            <div class="row">
                                <div class="col-xs-6">
                                    <div class="form-group">
                                        <label class="control-label"><?= _l('field_portal_field_source'); ?></label>
                                        <select class="selectpicker" name="source" data-width="100%" data-none-selected-text="<?= _l('dropdown_non_selected_tex'); ?>">
                                            <option value=""></option>
                                            <?php foreach ($sources as $source) { ?>
                                            <option value="<?= (int) $source['id']; ?>" <?= _edit_selected($lead, 'source', $source['id']) ? 'selected' : ''; ?>><?= e($source['name']); ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-xs-6">
                                    <div class="form-group">
                                        <label class="control-label"><?= _l('field_portal_field_business_category'); ?></label>
                                        <select class="selectpicker" name="custom_fields[leads][<?= $field_ids['business_category']; ?>]" data-width="100%" data-none-selected-text="<?= _l('dropdown_non_selected_tex'); ?>" <?= $lock_cf_fields ? 'disabled' : ''; ?>>
                                            <option value=""></option>
                                            <?php if ($business_category_field) { foreach (explode(',', $business_category_field['options']) as $option) { $option = trim($option); if ($option === '') { continue; } ?>
                                            <option value="<?= e($option); ?>" <?= _edit_cfv($custom_fields_values ?? [], $field_ids['business_category']) === $option ? 'selected' : ''; ?>><?= e($option); ?></option>
                                            <?php } } ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label"><?= _l('field_portal_field_requirements'); ?></label>
                                <textarea class="form-control" name="description" rows="3" placeholder="<?= _l('field_portal_field_requirements'); ?>"><?= e(_edit_val($lead, 'description')); ?></textarea>
                            </div>
                            <div class="row">
                                <div class="col-xs-4">
                                    <div class="form-group">
                                        <label class="control-label"><?= _l('field_portal_field_priority'); ?></label>
                                        <select class="selectpicker" name="custom_fields[leads][<?= $field_ids['priority']; ?>]" data-width="100%" data-none-selected-text="<?= _l('dropdown_non_selected_tex'); ?>" <?= $is_edit && $lock_cf_fields ? 'disabled' : ''; ?>>
                                            <option value=""></option>
                                            <?php if ($priority_field) { foreach (explode(',', $priority_field['options']) as $option) { $option = trim($option); if ($option === '') { continue; } ?>
                                            <option value="<?= e($option); ?>" <?= _edit_cfv($custom_fields_values ?? [], $field_ids['priority']) === $option ? 'selected' : ''; ?>><?= e($option); ?></option>
                                            <?php } } ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-xs-4">
                                    <div class="form-group">
                                        <label class="control-label"><?= _l('field_portal_field_budget'); ?></label>
                                        <input type="number" step="0.01" class="form-control input-lg" name="custom_fields[leads][<?= $field_ids['budget']; ?>]" value="<?= e(_edit_cfv($custom_fields_values ?? [], $field_ids['budget'])); ?>" placeholder="<?= _l('field_portal_field_budget'); ?>" <?= $lock_cf_fields ? 'readonly' : ''; ?>>
                                    </div>
                                </div>
                                <div class="col-xs-4">
                                    <div class="form-group">
                                        <label class="control-label"><?= _l('field_portal_column_status'); ?></label>
                                        <select class="selectpicker" name="status" data-width="100%" data-none-selected-text="<?= _l('dropdown_non_selected_tex'); ?>" <?= $is_edit && $is_converted ? 'disabled' : ''; ?>>
                                            <option value=""></option>
                                            <?php if (isset($statuses)) { foreach ($statuses as $status) { ?>
                                            <option value="<?= (int) $status['id']; ?>" <?= _edit_selected($lead, 'status', $status['id']) ? 'selected' : ''; ?>><?= e($status['name']); ?></option>
                                            <?php } } ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label"><?= _l('field_portal_field_remarks'); ?></label>
                                <textarea class="form-control" name="custom_fields[leads][<?= $field_ids['remarks']; ?>]" rows="2" placeholder="<?= _l('field_portal_field_remarks'); ?>"><?= e(_edit_cfv($custom_fields_values ?? [], $field_ids['remarks'])); ?></textarea>
                            </div>
                            <?php if ($is_edit && ($is_assigned || $is_converted)) { ?>
                            <div class="alert alert-info">
                                <i class="fa-solid fa-lock"></i> <?= $is_converted ? _l('field_portal_locked_converted') : _l('field_portal_locked_assigned'); ?>
                            </div>
                            <?php } ?>
                            <?php if (!$is_edit) { ?>
                            <div class="form-group">
                                <label class="control-label"><?= _l('field_portal_field_attachments'); ?></label>
                                <input type="file" class="form-control" id="field-portal-lead-files" name="file[]" multiple>
                            </div>
                            <?php } ?>

                            <button type="submit" class="btn btn-primary btn-lg btn-block" id="field-portal-create-lead-submit">
                                <i class="fa-solid fa-check"></i> <?= $submit_label; ?>
                            </button>
                            <?php if ($is_edit) { ?>
                            <a href="<?= $cancel_url; ?>" class="btn btn-default btn-lg btn-block mtop5"><?= _l('field_portal_cancel'); ?></a>
                            <?php } ?>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script>
    $(function() {
        init_selectpicker();

        $('#field-portal-create-lead-form').on('submit', function(e) {
            e.preventDefault();

            var $submit = $('#field-portal-create-lead-submit');
            $submit.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> <?= _l('wait_text'); ?>');

            var leadId = $(this).data('lead-id');
            var actionUrl = leadId ? (admin_url + 'field_portal/edit_lead_submit/' + leadId) : (admin_url + 'field_portal/create_lead_submit');

            var formData = $(this).serializeArray();
            if (typeof(csrfData) !== 'undefined') {
                formData.push({ name: csrfData['token_name'], value: csrfData['hash'] });
            }

            $.post(actionUrl, $.param(formData)).done(function(response) {
                var data = typeof response === 'string' ? JSON.parse(response) : response;

                if (!data.success) {
                    alert_float('danger', data.message);
                    $submit.prop('disabled', false).html('<i class="fa-solid fa-check"></i> <?= $submit_label; ?>');
                    return;
                }

                if (leadId) {
                    // Edit mode: redirect to lead details.
                    window.location.href = data.redirect || (admin_url + 'field_portal/lead_details/' + leadId);
                } else {
                    // Create mode: handle file uploads then redirect.
                    var files = document.getElementById('field-portal-lead-files').files;
                    if (files && files.length > 0) {
                        var uploadData = new FormData();
                        for (var i = 0; i < files.length; i++) {
                            uploadData.append('file[]', files[i]);
                        }
                        if (typeof(csrfData) !== 'undefined') {
                            uploadData.append(csrfData['token_name'], csrfData['hash']);
                        }

                        $.ajax({
                            url: admin_url + 'field_portal/lead_add_attachment/' + data.id,
                            type: 'POST',
                            data: uploadData,
                            contentType: false,
                            processData: false,
                        }).always(function() {
                            window.location.href = data.redirect;
                        });
                    } else {
                        window.location.href = data.redirect;
                    }
                }
            }).fail(function() {
                alert_float('danger', '<?= _l('field_portal_lead_create_failed'); ?>');
                $submit.prop('disabled', false).html('<i class="fa-solid fa-check"></i> <?= $submit_label; ?>');
            });
        });
    });
</script>
</body>
</html>
