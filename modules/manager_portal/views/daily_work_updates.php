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
                            <?php if ($missing) { ?>
                            <input type="hidden" name="missing" value="1" />
                            <?php } ?>
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
                                    <label><?= _l('manager_portal_filter_employee'); ?></label>
                                    <select name="employee_id" class="selectpicker" data-width="100%" data-live-search="true">
                                        <option value=""><?= _l('manager_portal_filter_all'); ?></option>
                                        <?php foreach ($options['employees'] as $employee) { ?>
                                        <option value="<?= (int) $employee['staffid']; ?>" <?= ((string) $filters['employee_id'] === (string) $employee['staffid']) ? 'selected' : ''; ?>><?= e(trim($employee['firstname'] . ' ' . $employee['lastname'])); ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <?php if (!$missing) { ?>
                                <div class="col-md-2 form-group">
                                    <label><?= _l('manager_portal_filter_review_status'); ?></label>
                                    <select name="review_status" class="selectpicker" data-width="100%">
                                        <option value=""><?= _l('manager_portal_filter_all'); ?></option>
                                        <option value="pending" <?= ($filters['review_status'] === 'pending') ? 'selected' : ''; ?>><?= _l('manager_portal_filter_pending_review'); ?></option>
                                        <option value="approved" <?= ($filters['review_status'] === 'approved') ? 'selected' : ''; ?>><?= _l('manager_portal_profile_dwu_approve'); ?></option>
                                        <option value="needs_revision" <?= ($filters['review_status'] === 'needs_revision') ? 'selected' : ''; ?>><?= _l('manager_portal_filter_needs_revision'); ?></option>
                                    </select>
                                </div>
                                <div class="col-md-2 form-group">
                                    <label><?= _l('manager_portal_filter_date_range'); ?></label>
                                    <select name="date_preset" class="selectpicker" data-width="100%">
                                        <option value=""><?= _l('manager_portal_filter_custom_range'); ?></option>
                                        <option value="today" <?= ($filters['date_preset'] === 'today') ? 'selected' : ''; ?>><?= _l('manager_portal_filter_today'); ?></option>
                                        <option value="yesterday" <?= ($filters['date_preset'] === 'yesterday') ? 'selected' : ''; ?>><?= _l('manager_portal_filter_yesterday'); ?></option>
                                        <option value="this_week" <?= ($filters['date_preset'] === 'this_week') ? 'selected' : ''; ?>><?= _l('manager_portal_filter_this_week'); ?></option>
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
                                <?php } ?>
                                <div class="col-md-2 form-group tw-flex tw-items-end">
                                    <button type="submit" class="btn btn-primary tw-w-full"><?= _l('manager_portal_filter_apply'); ?></button>
                                </div>
                                <div class="col-md-2 form-group tw-flex tw-items-end">
                                    <a href="<?= admin_url('manager_portal/daily_work_updates' . ($missing ? '?missing=1' : '')); ?>" class="btn btn-default tw-w-full"><?= _l('manager_portal_filter_clear'); ?></a>
                                </div>
                            </div>
                        </form>

                        <?php if ($missing) { ?>
                        <p class="text-muted"><?= e(_l('manager_portal_dwu_missing_title')); ?></p>
                        <?php } ?>

                        <?php if (empty($rows)) { ?>
                        <span class="text-muted"><?= _l('manager_portal_dwu_empty'); ?></span>
                        <?php } else { ?>
                        <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th><?= _l('manager_portal_dwu_column_employee'); ?></th>
                                    <th><?= _l('manager_portal_dwu_column_department'); ?></th>
                                    <th><?= _l('manager_portal_dwu_column_date'); ?></th>
                                    <th><?= _l('manager_portal_dwu_column_checkin'); ?></th>
                                    <th><?= _l('manager_portal_dwu_column_checkout'); ?></th>
                                    <th><?= _l('manager_portal_dwu_column_total_tasks'); ?></th>
                                    <th><?= _l('manager_portal_dwu_column_submitted_time'); ?></th>
                                    <th><?= _l('manager_portal_dwu_column_review_status'); ?></th>
                                    <th><?= _l('manager_portal_dwu_column_reviewed_by'); ?></th>
                                    <th><?= _l('manager_portal_dwu_column_actions'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($missing) { ?>
                                <?php foreach ($rows as $row) { ?>
                                <tr>
                                    <td><?= e($row['employee_name']); ?></td>
                                    <td><?= $row['department_name'] ? e($row['department_name']) : '-'; ?></td>
                                    <td><?= e(_d(date('Y-m-d'))); ?></td>
                                    <td>-</td>
                                    <td>-</td>
                                    <td><?= (int) $row['total_tasks']; ?></td>
                                    <td>-</td>
                                    <td><?= manager_portal_dwu_status_badge('not_submitted'); ?></td>
                                    <td>-</td>
                                    <td>-</td>
                                </tr>
                                <?php } ?>
                                <?php } else { ?>
                                <?php foreach ($rows as $row) { ?>
                                <tr>
                                    <td><?= e($row['employee_name']); ?></td>
                                    <td><?= $row['department_name'] ? e($row['department_name']) : '-'; ?></td>
                                    <td><?= e(_d($row['work_date'])); ?></td>
                                    <td><?= $row['login_time'] ? e(_dt($row['login_time'])) : '-'; ?></td>
                                    <td><?= $row['logout_time'] ? e(_dt($row['logout_time'])) : '-'; ?></td>
                                    <td><?= (int) $row['total_tasks']; ?></td>
                                    <td><?= $row['created_at'] ? e(_dt($row['created_at'])) : '-'; ?></td>
                                    <td><?= manager_portal_dwu_status_badge($row['review_status']); ?></td>
                                    <td><?= $row['reviewer_name'] ? e($row['reviewer_name']) : '-'; ?></td>
                                    <td>
                                        <a href="<?= admin_url('manager_portal/daily_work_update_detail/' . $row['id']); ?>" class="btn btn-default btn-icon"><i class="fa-regular fa-eye"></i></a>
                                    </td>
                                </tr>
                                <?php } ?>
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
