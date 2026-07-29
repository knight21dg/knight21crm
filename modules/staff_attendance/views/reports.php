<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="tw-flex tw-items-center tw-justify-between tw-flex-wrap">
                            <h4 class="no-margin"><?= _l('staff_attendance_reports'); ?></h4>
                            <div class="tw-flex tw-items-center tw-gap-2">
                                <a href="<?= admin_url('staff_attendance'); ?>" class="btn btn-default btn-sm"><i class="fa-solid fa-arrow-left"></i> <?= _l('staff_attendance'); ?></a>
                            </div>
                        </div>
                        <hr class="hr-panel-separator" />

                        <form method="get" class="tw-flex tw-items-end tw-gap-2 tw-mb-4">
                            <div class="form-group tw-mb-0">
                                <label><?= _l('staff_attendance_column_date'); ?></label>
                                <select name="month" class="form-control">
                                    <?php for ($m = 1; $m <= 12; $m++) { ?>
                                    <option value="<?= $m; ?>" <?= $m === $month ? 'selected' : ''; ?>><?= e(date('F', mktime(0, 0, 0, $m, 1))); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="form-group tw-mb-0">
                                <select name="year" class="form-control">
                                    <?php for ($y = (int) date('Y'); $y >= (int) date('Y') - 3; $y--) { ?>
                                    <option value="<?= $y; ?>" <?= $y === $year ? 'selected' : ''; ?>><?= $y; ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary"><?= _l('submit'); ?></button>
                        </form>

                        <ul class="nav nav-tabs nav-tabs-horizontal" role="tablist">
                            <li class="active"><a href="#tab_report_employee" data-toggle="tab"><?= _l('staff_attendance_report_employee_wise'); ?></a></li>
                            <li><a href="#tab_report_department" data-toggle="tab"><?= _l('staff_attendance_report_department_wise'); ?></a></li>
                            <li><a href="#tab_report_late" data-toggle="tab"><?= _l('staff_attendance_report_late'); ?></a></li>
                            <li><a href="#tab_report_leave" data-toggle="tab"><?= _l('staff_attendance_report_leave'); ?></a></li>
                        </ul>
                        <div class="tab-content tw-pt-4">

                        <div class="tab-pane active" id="tab_report_employee">
                            <?php if (empty($employee_report)) { ?>
                            <p class="text-muted"><?= _l('staff_attendance_report_empty'); ?></p>
                            <?php } else { ?>
                            <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th><?= _l('staff_attendance_column_employee'); ?></th>
                                        <th><?= _l('staff_attendance_report_column_present'); ?></th>
                                        <th><?= _l('staff_attendance_report_column_absent'); ?></th>
                                        <th><?= _l('staff_attendance_report_column_late'); ?></th>
                                        <th><?= _l('staff_attendance_report_column_leave'); ?></th>
                                        <th><?= _l('staff_attendance_report_column_attendance_rate'); ?></th>
                                        <th><?= _l('staff_attendance_report_column_working_hours'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($employee_report as $row) { ?>
                                    <tr>
                                        <td><?= e($row['name']); ?></td>
                                        <td><?= (int) $row['present']; ?></td>
                                        <td><?= (int) $row['absent']; ?></td>
                                        <td><?= (int) $row['late']; ?></td>
                                        <td><?= (int) $row['leave']; ?></td>
                                        <td><?= (int) $row['attendance_rate']; ?>%</td>
                                        <td><?= format_working_minutes($row['working_minutes']); ?></td>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                            </div>
                            <?php } ?>
                        </div>

                        <div class="tab-pane" id="tab_report_department">
                            <?php if (empty($department_report)) { ?>
                            <p class="text-muted"><?= _l('staff_attendance_report_empty'); ?></p>
                            <?php } else { ?>
                            <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th><?= _l('staff_attendance_report_column_department'); ?></th>
                                        <th><?= _l('staff_attendance_report_column_employees'); ?></th>
                                        <th><?= _l('staff_attendance_report_column_present'); ?></th>
                                        <th><?= _l('staff_attendance_report_column_absent'); ?></th>
                                        <th><?= _l('staff_attendance_report_column_late'); ?></th>
                                        <th><?= _l('staff_attendance_report_column_leave'); ?></th>
                                        <th><?= _l('staff_attendance_report_column_attendance_rate'); ?></th>
                                        <th><?= _l('staff_attendance_report_column_working_hours'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($department_report as $row) { ?>
                                    <tr>
                                        <td><?= e($row['department_name']); ?></td>
                                        <td><?= (int) $row['employee_count']; ?></td>
                                        <td><?= (int) $row['present']; ?></td>
                                        <td><?= (int) $row['absent']; ?></td>
                                        <td><?= (int) $row['late']; ?></td>
                                        <td><?= (int) $row['leave']; ?></td>
                                        <td><?= (int) $row['avg_attendance_rate']; ?>%</td>
                                        <td><?= format_working_minutes($row['total_working_minutes']); ?></td>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                            </div>
                            <?php } ?>
                        </div>

                        <div class="tab-pane" id="tab_report_late">
                            <?php if (empty($late_report)) { ?>
                            <p class="text-muted"><?= _l('staff_attendance_report_empty'); ?></p>
                            <?php } else { ?>
                            <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th><?= _l('staff_attendance_column_employee'); ?></th>
                                        <th><?= _l('staff_attendance_column_date'); ?></th>
                                        <th><?= _l('staff_attendance_column_login'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($late_report as $row) { ?>
                                    <tr>
                                        <td><?= e(trim($row['firstname'] . ' ' . $row['lastname'])); ?></td>
                                        <td><?= _d($row['attendance_date']); ?></td>
                                        <td><?= _dt($row['login_time']); ?></td>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                            </div>
                            <?php } ?>
                        </div>

                        <div class="tab-pane" id="tab_report_leave">
                            <?php if (empty($leave_report)) { ?>
                            <p class="text-muted"><?= _l('staff_attendance_report_empty'); ?></p>
                            <?php } else { ?>
                            <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th><?= _l('staff_attendance_column_employee'); ?></th>
                                        <th><?= _l('staff_attendance_leave_column_type'); ?></th>
                                        <th><?= _l('staff_attendance_leave_column_from'); ?></th>
                                        <th><?= _l('staff_attendance_leave_column_to'); ?></th>
                                        <th><?= _l('staff_attendance_leave_column_days'); ?></th>
                                        <th><?= _l('staff_attendance_column_status'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($leave_report as $row) { ?>
                                    <tr>
                                        <td><?= e(trim($row['firstname'] . ' ' . $row['lastname'])); ?></td>
                                        <td><?= e($row['leave_type']); ?></td>
                                        <td><?= _d($row['start_date']); ?></td>
                                        <td><?= _d($row['end_date']); ?></td>
                                        <td><?= rtrim(rtrim(number_format((float) $row['days'], 1), '0'), '.'); ?></td>
                                        <td><span class="label" style="color:<?= staff_attendance_request_status_color($row['status']); ?>;border:1px solid <?= adjust_hex_brightness(staff_attendance_request_status_color($row['status']), 0.4); ?>;background:<?= adjust_hex_brightness(staff_attendance_request_status_color($row['status']), 0.04); ?>;"><?= e($row['status']); ?></span></td>
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
<?php init_tail(); ?>
