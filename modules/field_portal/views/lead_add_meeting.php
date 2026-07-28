<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<?php
/**
 * Log Meeting - a fast form creating ONE Task (rel_type='lead') behind the
 * scenes. Discussion Summary/Products Discussed/Customer Requirements/
 * Budget/Competitor Info/Next Action are plain textareas that get folded
 * into the Task's own `description` as a structured template
 * (field_portal_meeting_template()) rather than becoming Task custom
 * fields - see this module's own docblock for why. Only Meeting Time/
 * Meeting Type map to real custom_fields[tasks][<id>] keys.
 */
$type_field_id = $meeting_type_field ? (int) $meeting_type_field['id'] : 0;
?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-8 col-md-offset-2">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin"><?= _l('field_portal_log_meeting'); ?></h4>
                        <p class="text-muted"><?= e($lead->name); ?><?= $lead->company ? ' - ' . e($lead->company) : ''; ?></p>
                        <hr class="hr-panel-separator" />

                        <form id="field-portal-add-meeting-form">
                            <div class="row">
                                <div class="col-xs-6">
                                    <div class="form-group">
                                        <label class="control-label"><?= _l('field_portal_meeting_date'); ?> <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control input-lg datepicker" name="meeting_date" required autocomplete="off" placeholder="<?= _l('field_portal_meeting_date'); ?>">
                                    </div>
                                </div>
                                <div class="col-xs-6">
                                    <div class="form-group">
                                        <label class="control-label"><?= _l('field_portal_meeting_time'); ?></label>
                                        <input type="text" class="form-control input-lg" name="meeting_time" placeholder="<?= _l('field_portal_meeting_time_placeholder'); ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label"><?= _l('field_portal_meeting_type'); ?></label>
                                <select class="selectpicker" name="meeting_type" data-width="100%" data-none-selected-text="<?= _l('dropdown_non_selected_tex'); ?>">
                                    <option value=""></option>
                                    <?php if ($meeting_type_field) { foreach (explode(',', $meeting_type_field['options']) as $option) { $option = trim($option); if ($option === '') { continue; } ?>
                                    <option value="<?= e($option); ?>"><?= e($option); ?></option>
                                    <?php } } ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="control-label"><?= _l('field_portal_field_discussion_summary'); ?></label>
                                <textarea class="form-control" name="discussion_summary" rows="3" placeholder="<?= _l('field_portal_field_discussion_summary'); ?>"></textarea>
                            </div>
                            <div class="form-group">
                                <label class="control-label"><?= _l('field_portal_field_products_discussed'); ?></label>
                                <textarea class="form-control" name="products_discussed" rows="2" placeholder="<?= _l('field_portal_field_products_discussed'); ?>"></textarea>
                            </div>
                            <div class="form-group">
                                <label class="control-label"><?= _l('field_portal_field_requirements'); ?></label>
                                <textarea class="form-control" name="customer_requirements" rows="2" placeholder="<?= _l('field_portal_field_requirements'); ?>"></textarea>
                            </div>
                            <div class="row">
                                <div class="col-xs-6">
                                    <div class="form-group">
                                        <label class="control-label"><?= _l('field_portal_field_meeting_budget'); ?></label>
                                        <input type="text" class="form-control input-lg" name="budget" placeholder="<?= _l('field_portal_field_meeting_budget'); ?>">
                                    </div>
                                </div>
                                <div class="col-xs-6">
                                    <div class="form-group">
                                        <label class="control-label"><?= _l('field_portal_field_competitor_info'); ?></label>
                                        <input type="text" class="form-control input-lg" name="competitor_info" placeholder="<?= _l('field_portal_field_competitor_info'); ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label"><?= _l('field_portal_field_next_action'); ?></label>
                                <textarea class="form-control" name="next_action" rows="2" placeholder="<?= _l('field_portal_field_next_action'); ?>"></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary btn-lg btn-block" id="field-portal-add-meeting-submit">
                                <i class="fa-solid fa-check"></i> <?= _l('field_portal_save_meeting'); ?>
                            </button>
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
        init_datepicker();

        $('#field-portal-add-meeting-form').on('submit', function(e) {
            e.preventDefault();

            var $submit = $('#field-portal-add-meeting-submit');
            $submit.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> <?= _l('wait_text'); ?>');

            var formData = $(this).serializeArray();
            if (typeof(csrfData) !== 'undefined') {
                formData.push({ name: csrfData['token_name'], value: csrfData['hash'] });
            }

            $.post(admin_url + 'field_portal/lead_add_meeting_submit/<?= (int) $lead->id; ?>', $.param(formData)).done(function(response) {
                var data = typeof response === 'string' ? JSON.parse(response) : response;

                if (!data.success) {
                    alert_float('danger', data.message);
                    $submit.prop('disabled', false).html('<i class="fa-solid fa-check"></i> <?= _l('field_portal_save_meeting'); ?>');
                    return;
                }

                window.location.href = data.redirect;
            }).fail(function() {
                alert_float('danger', '<?= _l('field_portal_meeting_create_failed'); ?>');
                $submit.prop('disabled', false).html('<i class="fa-solid fa-check"></i> <?= _l('field_portal_save_meeting'); ?>');
            });
        });
    });
</script>
</body>
</html>
