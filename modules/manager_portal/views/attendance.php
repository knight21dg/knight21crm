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

                        <ul class="nav nav-tabs nav-tabs-horizontal" role="tablist">
                            <li class="active"><a href="#tab_attendance_records" data-toggle="tab"><?= _l('manager_portal_attendance_tab_records'); ?></a></li>
                            <li><a href="#tab_leave_review" data-toggle="tab"><?= _l('manager_portal_attendance_tab_leave'); ?> <?php if ($pending_leave_count) { ?><span class="badge"><?= (int) $pending_leave_count; ?></span><?php } ?></a></li>
                            <li><a href="#tab_late_review" data-toggle="tab"><?= _l('manager_portal_attendance_tab_late'); ?> <?php if ($pending_late_count) { ?><span class="badge"><?= (int) $pending_late_count; ?></span><?php } ?></a></li>
                            <li><a href="#tab_early_review" data-toggle="tab"><?= _l('manager_portal_attendance_tab_early'); ?> <?php if ($pending_early_count) { ?><span class="badge"><?= (int) $pending_early_count; ?></span><?php } ?></a></li>
                        </ul>
                        <div class="tab-content tw-pt-4">
                        <div class="tab-pane active" id="tab_attendance_records">

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
                                    <label><?= _l('manager_portal_filter_status'); ?></label>
                                    <select name="status" class="selectpicker" data-width="100%">
                                        <option value=""><?= _l('manager_portal_filter_all'); ?></option>
                                        <?php foreach (get_attendance_statuses() as $status) { ?>
                                        <?php if (in_array($status, ['Present', 'Absent', 'Late', 'Half Day'], true)) { ?>
                                        <option value="<?= e($status); ?>" <?= ($filters['status'] === $status) ? 'selected' : ''; ?>><?= e($status); ?></option>
                                        <?php } ?>
                                        <?php } ?>
                                    </select>
                                </div>
                                <div class="col-md-2 form-group">
                                    <label><?= _l('manager_portal_filter_date_range'); ?></label>
                                    <select name="date_preset" class="selectpicker" data-width="100%">
                                        <option value=""><?= _l('manager_portal_filter_custom_range'); ?></option>
                                        <option value="today" <?= ($filters['date_preset'] === 'today') ? 'selected' : ''; ?>><?= _l('manager_portal_filter_today'); ?></option>
                                        <option value="yesterday" <?= ($filters['date_preset'] === 'yesterday') ? 'selected' : ''; ?>><?= _l('manager_portal_filter_yesterday'); ?></option>
                                        <option value="this_week" <?= ($filters['date_preset'] === 'this_week') ? 'selected' : ''; ?>><?= _l('manager_portal_filter_this_week'); ?></option>
                                        <option value="this_month" <?= ($filters['date_preset'] === 'this_month') ? 'selected' : ''; ?>><?= _l('manager_portal_filter_this_month'); ?></option>
                                    </select>
                                </div>
                                <div class="col-md-2 form-group">
                                    <label><?= _l('manager_portal_filter_date'); ?></label>
                                    <input type="text" name="date" class="form-control datepicker" autocomplete="off" value="<?= e((string) $filters['date']); ?>" />
                                </div>
                                <?php } ?>
                                <div class="col-md-2 form-group tw-flex tw-items-end">
                                    <button type="submit" class="btn btn-primary tw-w-full"><?= _l('manager_portal_filter_apply'); ?></button>
                                </div>
                                <div class="col-md-2 form-group tw-flex tw-items-end">
                                    <a href="<?= admin_url('manager_portal/attendance' . ($missing ? '?missing=1' : '')); ?>" class="btn btn-default tw-w-full"><?= _l('manager_portal_filter_clear'); ?></a>
                                </div>
                            </div>
                        </form>

                        <?php if ($missing) { ?>
                        <p class="text-muted"><?= _l('manager_portal_attendance_missing_title'); ?></p>
                        <?php } ?>

                        <?php if (empty($rows)) { ?>
                        <span class="text-muted"><?= _l('manager_portal_attendance_empty'); ?></span>
                        <?php } else { ?>
                        <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th><?= _l('manager_portal_attendance_column_employee'); ?></th>
                                    <th><?= _l('manager_portal_dwu_column_department'); ?></th>
                                    <?php if (!$missing) { ?>
                                    <th><?= _l('manager_portal_dwu_column_date'); ?></th>
                                    <th><?= _l('manager_portal_dwu_column_checkin'); ?></th>
                                    <th><?= _l('manager_portal_dwu_column_checkout'); ?></th>
                                    <th><?= _l('manager_portal_attendance_column_total_hours'); ?></th>
                                    <th><?= _l('manager_portal_attendance_column_status'); ?></th>
                                    <th><?= _l('manager_portal_attendance_column_late_arrival'); ?></th>
                                    <th><?= _l('manager_portal_attendance_column_early_exit'); ?></th>
                                    <?php } else { ?>
                                    <th><?= _l('manager_portal_attendance_column_status'); ?></th>
                                    <?php } ?>
                                    <th><?= _l('manager_portal_projects_column_actions'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rows as $row) { ?>
                                <tr>
                                    <td><?= e($row['employee_name']); ?></td>
                                    <td><?= $row['department_name'] ? e($row['department_name']) : '-'; ?></td>
                                    <?php if (!$missing) { ?>
                                    <td><?= e(_d($row['date'])); ?></td>
                                    <td><?= $row['login_time'] ? e(_dt($row['login_time'])) : '-'; ?></td>
                                    <td><?= $row['logout_time'] ? e(_dt($row['logout_time'])) : '-'; ?></td>
                                    <td><?= e(format_working_minutes($row['working_minutes'])); ?></td>
                                    <td><span class="label" style="color:<?= get_attendance_status_color($row['status']); ?>;border:1px solid <?= adjust_hex_brightness(get_attendance_status_color($row['status']), 0.4); ?>;background:<?= adjust_hex_brightness(get_attendance_status_color($row['status']), 0.04); ?>;"><?= e($row['status']); ?></span></td>
                                    <td><?php if ($row['late_arrival']) { ?><span class="label label-warning"><?= _l('manager_portal_attendance_yes'); ?></span><?php } else { ?><span class="text-muted">-</span><?php } ?></td>
                                    <td><?php if ($row['early_exit']) { ?><span class="label label-warning"><?= _l('manager_portal_attendance_yes'); ?></span><?php } else { ?><span class="text-muted">-</span><?php } ?></td>
                                    <?php } else { ?>
                                    <td><span class="label label-default"><?= _l('manager_portal_dwu_status_not_submitted'); ?></span></td>
                                    <?php } ?>
                                    <td>
                                        <a href="<?= admin_url('manager_portal/attendance_detail/' . $row['staff_id']); ?>" class="btn btn-default btn-icon"><i class="fa-regular fa-eye"></i></a>
                                    </td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                        </div>
                        <?php } ?>

                        </div>

                        <div class="tab-pane" id="tab_leave_review">
                            <form method="get" class="tw-mb-4">
                                <div class="col-md-3 no-padding">
                                    <select name="review_status" class="form-control" onchange="this.form.submit();">
                                        <?php foreach (['Pending', 'Approved', 'Rejected', 'All'] as $status_option) { ?>
                                        <option value="<?= e($status_option); ?>" <?= ($review_status === $status_option) ? 'selected' : ''; ?>><?= e($status_option); ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <div class="clearfix"></div>
                            </form>
                            <?php if (empty($leave_requests)) { ?>
                            <span class="text-muted"><?= _l('manager_portal_attendance_review_empty'); ?></span>
                            <?php } else { ?>
                            <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th><?= _l('manager_portal_attendance_column_employee'); ?></th>
                                        <th><?= _l('staff_attendance_leave_column_type'); ?></th>
                                        <th><?= _l('staff_attendance_leave_column_from'); ?></th>
                                        <th><?= _l('staff_attendance_leave_column_to'); ?></th>
                                        <th><?= _l('staff_attendance_leave_column_days'); ?></th>
                                        <th><?= _l('staff_attendance_leave_reason'); ?></th>
                                        <th><?= _l('manager_portal_attendance_column_status'); ?></th>
                                        <th><?= _l('manager_portal_projects_column_actions'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($leave_requests as $leave_request) { ?>
                                    <tr>
                                        <td><?= e(trim($leave_request['firstname'] . ' ' . $leave_request['lastname'])); ?></td>
                                        <td><?= e($leave_request['leave_type']); ?></td>
                                        <td><?= _d($leave_request['start_date']); ?></td>
                                        <td><?= _d($leave_request['end_date']); ?></td>
                                        <td><?= rtrim(rtrim(number_format((float) $leave_request['days'], 1), '0'), '.'); ?></td>
                                        <td><?= e($leave_request['reason']); ?></td>
                                        <td><span class="label" style="color:<?= staff_attendance_request_status_color($leave_request['status']); ?>;border:1px solid <?= adjust_hex_brightness(staff_attendance_request_status_color($leave_request['status']), 0.4); ?>;background:<?= adjust_hex_brightness(staff_attendance_request_status_color($leave_request['status']), 0.04); ?>;"><?= e($leave_request['status']); ?></span></td>
                                        <td>
                                            <?php if ($leave_request['status'] === 'Pending') { ?>
                                            <button type="button" class="btn btn-success btn-icon" onclick="manager_portal_open_review_modal('leave', <?= (int) $leave_request['id']; ?>, 'Approved');"><i class="fa-solid fa-check"></i></button>
                                            <button type="button" class="btn btn-danger btn-icon" onclick="manager_portal_open_review_modal('leave', <?= (int) $leave_request['id']; ?>, 'Rejected');"><i class="fa-solid fa-xmark"></i></button>
                                            <?php } else { ?>
                                            <span class="text-muted">-</span>
                                            <?php } ?>
                                        </td>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                            </div>
                            <?php } ?>
                        </div>

                        <div class="tab-pane" id="tab_late_review">
                            <form method="get" class="tw-mb-4">
                                <div class="col-md-3 no-padding">
                                    <select name="review_status" class="form-control" onchange="this.form.submit();">
                                        <?php foreach (['Pending', 'Approved', 'Rejected', 'All'] as $status_option) { ?>
                                        <option value="<?= e($status_option); ?>" <?= ($review_status === $status_option) ? 'selected' : ''; ?>><?= e($status_option); ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <div class="clearfix"></div>
                            </form>
                            <?php if (empty($late_arrival_requests)) { ?>
                            <span class="text-muted"><?= _l('manager_portal_attendance_review_empty'); ?></span>
                            <?php } else { ?>
                            <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th><?= _l('manager_portal_attendance_column_employee'); ?></th>
                                        <th><?= _l('staff_attendance_column_date'); ?></th>
                                        <th><?= _l('staff_attendance_column_login'); ?></th>
                                        <th><?= _l('staff_attendance_late_delay'); ?></th>
                                        <th><?= _l('staff_attendance_late_reason'); ?></th>
                                        <th><?= _l('manager_portal_attendance_column_status'); ?></th>
                                        <th><?= _l('manager_portal_projects_column_actions'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($late_arrival_requests as $late_request) { ?>
                                    <tr>
                                        <td><?= e(trim($late_request['firstname'] . ' ' . $late_request['lastname'])); ?></td>
                                        <td><?= _d($late_request['attendance_date']); ?></td>
                                        <td><?= _dt($late_request['login_time']); ?></td>
                                        <td><?= (int) staff_attendance_late_minutes($late_request['login_time']); ?> <?= _l('staff_attendance_minutes'); ?></td>
                                        <td><?= e($late_request['reason']); ?></td>
                                        <td><span class="label" style="color:<?= staff_attendance_request_status_color($late_request['status']); ?>;border:1px solid <?= adjust_hex_brightness(staff_attendance_request_status_color($late_request['status']), 0.4); ?>;background:<?= adjust_hex_brightness(staff_attendance_request_status_color($late_request['status']), 0.04); ?>;"><?= e($late_request['status']); ?></span></td>
                                        <td>
                                            <?php if ($late_request['status'] === 'Pending') { ?>
                                            <button type="button" class="btn btn-success btn-icon" onclick="manager_portal_open_review_modal('late_arrival', <?= (int) $late_request['id']; ?>, 'Approved');"><i class="fa-solid fa-check"></i></button>
                                            <button type="button" class="btn btn-danger btn-icon" onclick="manager_portal_open_review_modal('late_arrival', <?= (int) $late_request['id']; ?>, 'Rejected');"><i class="fa-solid fa-xmark"></i></button>
                                            <?php } else { ?>
                                            <span class="text-muted">-</span>
                                            <?php } ?>
                                        </td>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                            </div>
                            <?php } ?>
                        </div>

                        <div class="tab-pane" id="tab_early_review">
                            <form method="get" class="tw-mb-4">
                                <div class="col-md-3 no-padding">
                                    <select name="review_status" class="form-control" onchange="this.form.submit();">
                                        <?php foreach (['Pending', 'Approved', 'Rejected', 'All'] as $status_option) { ?>
                                        <option value="<?= e($status_option); ?>" <?= ($review_status === $status_option) ? 'selected' : ''; ?>><?= e($status_option); ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <div class="clearfix"></div>
                            </form>
                            <?php if (empty($early_exit_requests)) { ?>
                            <span class="text-muted"><?= _l('manager_portal_attendance_review_empty'); ?></span>
                            <?php } else { ?>
                            <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th><?= _l('manager_portal_attendance_column_employee'); ?></th>
                                        <th><?= _l('staff_attendance_column_date'); ?></th>
                                        <th><?= _l('staff_attendance_column_logout'); ?></th>
                                        <th><?= _l('staff_attendance_early_duration'); ?></th>
                                        <th><?= _l('staff_attendance_early_reason'); ?></th>
                                        <th><?= _l('manager_portal_attendance_column_status'); ?></th>
                                        <th><?= _l('manager_portal_projects_column_actions'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($early_exit_requests as $early_request) { ?>
                                    <tr>
                                        <td><?= e(trim($early_request['firstname'] . ' ' . $early_request['lastname'])); ?></td>
                                        <td><?= _d($early_request['attendance_date']); ?></td>
                                        <td><?= _dt($early_request['logout_time']); ?></td>
                                        <td><?= (int) staff_attendance_early_minutes($early_request['logout_time']); ?> <?= _l('staff_attendance_minutes'); ?></td>
                                        <td><?= e($early_request['reason']); ?></td>
                                        <td><span class="label" style="color:<?= staff_attendance_request_status_color($early_request['status']); ?>;border:1px solid <?= adjust_hex_brightness(staff_attendance_request_status_color($early_request['status']), 0.4); ?>;background:<?= adjust_hex_brightness(staff_attendance_request_status_color($early_request['status']), 0.04); ?>;"><?= e($early_request['status']); ?></span></td>
                                        <td>
                                            <?php if ($early_request['status'] === 'Pending') { ?>
                                            <button type="button" class="btn btn-success btn-icon" onclick="manager_portal_open_review_modal('early_exit', <?= (int) $early_request['id']; ?>, 'Approved');"><i class="fa-solid fa-check"></i></button>
                                            <button type="button" class="btn btn-danger btn-icon" onclick="manager_portal_open_review_modal('early_exit', <?= (int) $early_request['id']; ?>, 'Rejected');"><i class="fa-solid fa-xmark"></i></button>
                                            <?php } else { ?>
                                            <span class="text-muted">-</span>
                                            <?php } ?>
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
    </div>
</div>

<div id="manager-portal-review-modal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title" id="manager-portal-review-modal-title"></h4>
            </div>
            <div class="modal-body">
                <input type="hidden" id="manager-portal-review-type" value="" />
                <input type="hidden" id="manager-portal-review-id" value="" />
                <input type="hidden" id="manager-portal-review-decision" value="" />
                <div class="form-group">
                    <label for="manager-portal-review-remarks"><?= _l('manager_portal_attendance_review_remarks'); ?></label>
                    <textarea id="manager-portal-review-remarks" class="form-control" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?= _l('close'); ?></button>
                <button type="button" class="btn btn-primary" onclick="manager_portal_submit_review();"><?= _l('manager_portal_attendance_review_submit'); ?></button>
            </div>
        </div>
    </div>
</div>

<?php init_tail(); ?>
<script>
    // Both the review-status filter and the Approve/Reject actions
    // reload the whole page (this page has no AJAX table refresh, same
    // GET-based reload pattern the Attendance Records tab's own filters
    // already use) - remembering the last-active tab keeps the reviewer
    // on the tab they were working in instead of bouncing back to
    // Attendance Records every time.
    $(function() {
        var lastTab = localStorage.getItem('manager_portal_attendance_tab');
        if (lastTab) {
            var $tabLink = $('a[data-toggle="tab"][href="' + lastTab + '"]');
            if ($tabLink.length) {
                $tabLink.tab('show');
            }
        }

        $('a[data-toggle="tab"]').on('shown.bs.tab', function() {
            localStorage.setItem('manager_portal_attendance_tab', $(this).attr('href'));
        });
    });

    function manager_portal_open_review_modal(type, id, decision) {
        $('#manager-portal-review-type').val(type);
        $('#manager-portal-review-id').val(id);
        $('#manager-portal-review-decision').val(decision);
        $('#manager-portal-review-remarks').val('');
        $('#manager-portal-review-modal-title').text(decision === 'Approved' ? '<?= _l('manager_portal_attendance_approve_request'); ?>' : '<?= _l('manager_portal_attendance_reject_request'); ?>');
        $('#manager-portal-review-modal').modal('show');
    }

    function manager_portal_submit_review() {
        $.post(admin_url + 'manager_portal/review_request', {
            type: $('#manager-portal-review-type').val(),
            id: $('#manager-portal-review-id').val(),
            decision: $('#manager-portal-review-decision').val(),
            remarks: $('#manager-portal-review-remarks').val(),
        }).done(function(response) {
            var data = typeof response === 'string' ? JSON.parse(response) : response;
            if (data.success) {
                $('#manager-portal-review-modal').modal('hide');
                location.reload();
            } else {
                alert_float('danger', data.message);
            }
        });
    }
</script>
</body>
</html>
