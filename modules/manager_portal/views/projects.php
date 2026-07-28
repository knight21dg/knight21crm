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

                        <form method="get" class="tw-mb-4">
                            <div class="row">
                                <div class="col-md-2 form-group">
                                    <label><?= _l('manager_portal_filter_department'); ?></label>
                                    <select name="department_id" class="selectpicker" data-width="100%" data-live-search="true">
                                        <option value=""><?= _l('manager_portal_filter_all'); ?></option>
                                        <?php foreach ($options['departments'] as $department) { ?>
                                        <option value="<?= (int) $department['id']; ?>" <?= ((string) $filters['department_id'] === (string) $department['id']) ? 'selected' : ''; ?>><?= e($department['name']); ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <div class="col-md-2 form-group">
                                    <label><?= _l('manager_portal_filter_status'); ?></label>
                                    <select name="status" class="selectpicker" data-width="100%">
                                        <option value=""><?= _l('manager_portal_filter_all'); ?></option>
                                        <?php foreach ($options['statuses'] as $status) { ?>
                                        <option value="<?= (int) $status['id']; ?>" <?= ((string) $filters['status'] === (string) $status['id']) ? 'selected' : ''; ?>><?= e($status['name']); ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <div class="col-md-2 form-group">
                                    <label><?= _l('manager_portal_filter_project_manager'); ?></label>
                                    <select name="assigned_employee" class="selectpicker" data-width="100%" data-live-search="true">
                                        <option value=""><?= _l('manager_portal_filter_all'); ?></option>
                                        <?php foreach ($options['employees'] as $employee) { ?>
                                        <option value="<?= (int) $employee['staffid']; ?>" <?= ((string) $filters['assigned_employee'] === (string) $employee['staffid']) ? 'selected' : ''; ?>><?= e(trim($employee['firstname'] . ' ' . $employee['lastname'])); ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <div class="col-md-2 form-group">
                                    <label><?= _l('manager_portal_filter_employee'); ?></label>
                                    <select name="employee_id" class="selectpicker" data-width="100%" data-live-search="true">
                                        <option value=""><?= _l('manager_portal_filter_all'); ?></option>
                                        <?php foreach ($options['employees'] as $employee) { ?>
                                        <option value="<?= (int) $employee['staffid']; ?>" <?= ((string) $filters['employee_id'] === (string) $employee['staffid']) ? 'selected' : ''; ?>><?= e(trim($employee['firstname'] . ' ' . $employee['lastname'])); ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <div class="col-md-2 form-group">
                                    <label><?= _l('manager_portal_filter_customer'); ?></label>
                                    <select name="client_id" class="selectpicker" data-width="100%" data-live-search="true">
                                        <option value=""><?= _l('manager_portal_filter_all'); ?></option>
                                        <?php foreach ($options['customers'] as $customer) { ?>
                                        <option value="<?= (int) $customer['userid']; ?>" <?= ((string) $filters['client_id'] === (string) $customer['userid']) ? 'selected' : ''; ?>><?= e($customer['company']); ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <div class="col-md-2 form-group">
                                    <label><?= _l('manager_portal_filter_active_completed'); ?></label>
                                    <select name="active" class="selectpicker" data-width="100%">
                                        <option value=""><?= _l('manager_portal_filter_all'); ?></option>
                                        <option value="active" <?= ($filters['active'] === 'active') ? 'selected' : ''; ?>><?= _l('manager_portal_filter_active'); ?></option>
                                        <option value="completed" <?= ($filters['active'] === 'completed') ? 'selected' : ''; ?>><?= _l('manager_portal_filter_completed'); ?></option>
                                    </select>
                                </div>
                                <div class="col-md-2 form-group">
                                    <label><?= _l('manager_portal_filter_date_from'); ?></label>
                                    <input type="text" name="date_from" class="form-control datepicker" autocomplete="off" value="<?= e((string) $filters['date_from']); ?>" />
                                </div>
                                <div class="col-md-2 form-group">
                                    <label><?= _l('manager_portal_filter_date_to'); ?></label>
                                    <input type="text" name="date_to" class="form-control datepicker" autocomplete="off" value="<?= e((string) $filters['date_to']); ?>" />
                                </div>
                                <div class="col-md-2 form-group tw-flex tw-items-end">
                                    <button type="submit" class="btn btn-primary tw-w-full"><?= _l('manager_portal_filter_apply'); ?></button>
                                </div>
                                <div class="col-md-2 form-group tw-flex tw-items-end">
                                    <a href="<?= admin_url('manager_portal/projects'); ?>" class="btn btn-default tw-w-full"><?= _l('manager_portal_filter_clear'); ?></a>
                                </div>
                            </div>
                        </form>

                        <?php if (empty($projects)) { ?>
                        <span class="text-muted"><?= _l('manager_portal_projects_empty'); ?></span>
                        <?php } else { ?>
                        <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th><?= _l('manager_portal_projects_column_name'); ?></th>
                                    <th><?= _l('manager_portal_projects_column_customer'); ?></th>
                                    <th><?= _l('manager_portal_projects_column_department'); ?></th>
                                    <th><?= _l('manager_portal_projects_column_manager'); ?></th>
                                    <th><?= _l('manager_portal_projects_column_assigned'); ?></th>
                                    <th><?= _l('manager_portal_projects_column_status'); ?></th>
                                    <th><?= _l('manager_portal_projects_column_progress'); ?></th>
                                    <th><?= _l('manager_portal_projects_column_start_date'); ?></th>
                                    <th><?= _l('manager_portal_projects_column_deadline'); ?></th>
                                    <th><?= _l('manager_portal_projects_column_overdue'); ?></th>
                                    <th><?= _l('manager_portal_projects_column_actions'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($projects as $project) { ?>
                                <?php
                                    $status_meta = null;
                                    foreach ($options['statuses'] as $status) {
                                        if ((int) $status['id'] === (int) $project['status']) {
                                            $status_meta = $status;
                                            break;
                                        }
                                    }
                                ?>
                                <tr>
                                    <td><?= e($project['name']); ?></td>
                                    <td><?= $project['company'] ? e($project['company']) : '-'; ?></td>
                                    <td><?= $project['department_name'] ? e($project['department_name']) : '-'; ?></td>
                                    <td><?= $project['manager_name'] ? e($project['manager_name']) : '-'; ?></td>
                                    <td>
                                        <?php if (empty($project['member_names'])) { ?>
                                        <span class="text-muted">-</span>
                                        <?php } else { ?>
                                        <?= e(implode(', ', $project['member_names'])); ?>
                                        <?php } ?>
                                    </td>
                                    <td>
                                        <?php if ($status_meta) { ?>
                                        <span class="label" style="color:<?= $status_meta['color']; ?>;border:1px solid <?= adjust_hex_brightness($status_meta['color'], 0.4); ?>;background:<?= adjust_hex_brightness($status_meta['color'], 0.04); ?>;"><?= e($status_meta['name']); ?></span>
                                        <?php } else { ?>
                                        -
                                        <?php } ?>
                                    </td>
                                    <td><?= (int) $project['progress']; ?>%</td>
                                    <td><?= $project['start_date'] ? e(_d($project['start_date'])) : '-'; ?></td>
                                    <td><?= $project['deadline'] ? e(_d($project['deadline'])) : '-'; ?></td>
                                    <td>
                                        <?php if ($project['overdue']) { ?>
                                        <span class="label label-danger"><?= _l('manager_portal_overdue'); ?></span>
                                        <?php } else { ?>
                                        <span class="text-muted">-</span>
                                        <?php } ?>
                                    </td>
                                    <td>
                                        <a href="<?= admin_url('manager_portal/project/' . $project['id']); ?>" class="btn btn-default btn-icon"><i class="fa-regular fa-eye"></i></a>
                                    </td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                        </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
</body>
</html>
