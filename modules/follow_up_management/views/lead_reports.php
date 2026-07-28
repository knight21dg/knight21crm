<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="tw-flex tw-items-center tw-justify-between tw-mb-4">
                            <h4 class="no-margin"><?= _l('followup_lead_reports'); ?></h4>
                            <a href="<?= admin_url('follow_up_management/export_lead_reports' . ($this->input->server('QUERY_STRING') ? '?' . $this->input->server('QUERY_STRING') : '')); ?>" class="btn btn-success btn-sm"><i class="fa-solid fa-download"></i> <?= _l('followup_export_csv'); ?></a>
                        </div>

                        <div class="row tw-mb-3">
                            <div class="col-md-3">
                                <label class="control-label"><?= _l('followup_filter_date_from'); ?></label>
                                <input type="text" class="form-control datepicker" name="date_from" autocomplete="off" value="<?= e($this->input->get('date_from')); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="control-label"><?= _l('followup_filter_date_to'); ?></label>
                                <input type="text" class="form-control datepicker" name="date_to" autocomplete="off" value="<?= e($this->input->get('date_to')); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="control-label"><?= _l('followup_filter_by_staff'); ?></label>
                                <select class="selectpicker" name="staff_id" data-width="100%">
                                    <option value=""><?= _l('followup_filter_all_staff'); ?></option>
                                    <?php foreach ($staff as $s) { ?>
                                    <option value="<?= (int) $s['staffid']; ?>" <?= $this->input->get('staff_id') == $s['staffid'] ? 'selected' : ''; ?>><?= e($s['firstname'] . ' ' . $s['lastname']); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="control-label"><?= _l('followup_filter_by_role'); ?></label>
                                <select class="selectpicker" name="role" data-width="100%">
                                    <option value=""><?= _l('followup_filter_all_roles'); ?></option>
                                    <option value="fe" <?= $this->input->get('role') === 'fe' ? 'selected' : ''; ?>><?= _l('followup_field_executive'); ?></option>
                                    <option value="tc" <?= $this->input->get('role') === 'tc' ? 'selected' : ''; ?>><?= _l('followup_telecaller'); ?></option>
                                </select>
                            </div>
                        </div>

                        <div class="row tw-mb-3">
                            <div class="col-md-3">
                                <label class="control-label"><?= _l('followup_filter_by_status'); ?></label>
                                <select class="selectpicker" name="status_id" data-width="100%" data-live-search="true">
                                    <option value=""><?= _l('followup_filter_all_statuses'); ?></option>
                                    <?php foreach ($statuses as $st) { ?>
                                    <option value="<?= (int) $st['id']; ?>" <?= $this->input->get('status_id') == $st['id'] ? 'selected' : ''; ?>><?= e($st['name']); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="control-label"><?= _l('followup_filter_by_source'); ?></label>
                                <select class="selectpicker" name="source_id" data-width="100%" data-live-search="true">
                                    <option value=""><?= _l('followup_filter_all_sources'); ?></option>
                                    <?php foreach ($sources as $src) { ?>
                                    <option value="<?= (int) $src['id']; ?>" <?= $this->input->get('source_id') == $src['id'] ? 'selected' : ''; ?>><?= e($src['name']); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="control-label"><?= _l('followup_filter_status'); ?></label>
                                <select class="selectpicker" name="status_filter" data-width="100%">
                                    <option value=""><?= _l('followup_filter_all_statuses'); ?></option>
                                    <option value="converted" <?= $this->input->get('is_converted') ? 'selected' : ''; ?>><?= _l('followup_filter_converted'); ?></option>
                                    <option value="lost" <?= $this->input->get('is_lost') ? 'selected' : ''; ?>><?= _l('followup_filter_lost'); ?></option>
                                </select>
                            </div>
                            <div class="col-md-3 tw-flex tw-items-end">
                                <button type="button" class="btn btn-primary" onclick="applyFilters()"><i class="fa-solid fa-filter"></i> <?= _l('followup_apply_filters'); ?></button>
                                <a href="<?= admin_url('follow_up_management/lead_reports'); ?>" class="btn btn-default tw-ml-2"><?= _l('followup_clear_filters'); ?></a>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover table-bordered" id="lead-reports-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th><?= _l('followup_lead_name'); ?></th>
                                        <th><?= _l('followup_company'); ?></th>
                                        <th><?= _l('followup_phone'); ?></th>
                                        <th><?= _l('followup_email'); ?></th>
                                        <th><?= _l('followup_date_added'); ?></th>
                                        <th><?= _l('followup_status'); ?></th>
                                        <th><?= _l('followup_source'); ?></th>
                                        <th><?= _l('followup_field_executive'); ?></th>
                                        <th><?= _l('followup_telecaller'); ?></th>
                                        <th><?= _l('followup_converted_date'); ?></th>
                                        <th><?= _l('followup_lost_reason'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($reports)) { ?>
                                    <tr><td colspan="12" class="text-center text-muted"><?= _l('followup_no_data'); ?></td></tr>
                                    <?php } else { $idx = 1; foreach ($reports as $row) { ?>
                                    <tr>
                                        <td><?= $idx++; ?></td>
                                        <td><?= e($row['name']); ?></td>
                                        <td><?= e($row['company'] ?: '-'); ?></td>
                                        <td><?= e($row['phonenumber'] ?: '-'); ?></td>
                                        <td><?= e($row['email'] ?: '-'); ?></td>
                                        <td><?= e(_dt($row['dateadded'])); ?></td>
                                        <td><?= e($row['status_name'] ?: '-'); ?></td>
                                        <td><?= e($row['source_name'] ?: '-'); ?></td>
                                        <td><?= e(($row['fe_firstname'] ?? '') . ' ' . ($row['fe_lastname'] ?? '')); ?></td>
                                        <td><?= e(($row['tc_firstname'] ?? '') . ' ' . ($row['tc_lastname'] ?? '')); ?></td>
                                        <td><?= $row['date_converted'] ? e(_d($row['date_converted'])) : '-'; ?></td>
                                        <td><?= $row['is_lost'] ? e($row['lost_reason'] ?: _l('followup_yes')) : '-'; ?></td>
                                    </tr>
                                    <?php } } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script>
    function applyFilters() {
        var params = {};
        $('[name="date_from"], [name="date_to"], [name="staff_id"], [name="status_id"], [name="source_id"], [name="role"]').each(function() {
            if ($(this).val()) {
                params[$(this).attr('name')] = $(this).val();
            }
        });
        var statusFilter = $('[name="status_filter"]').val();
        if (statusFilter === 'converted') { params.is_converted = '1'; }
        if (statusFilter === 'lost') { params.is_lost = '1'; }
        location.href = admin_url + 'follow_up_management/lead_reports?' + $.param(params);
    }
</script>
</body>
</html>
