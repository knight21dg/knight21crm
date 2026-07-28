<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <?php if (is_admin()) { ?>
                        <div class="tw-flex tw-items-center tw-justify-between tw-flex-wrap">
                            <h4 class="no-margin"><?= _l('staff_attendance'); ?></h4>
                            <div class="tw-flex tw-items-center tw-gap-2">
                                <!-- Attendance Settings - was previously reached via a
                                separate "Operations > Attendance" sidebar submenu, now
                                simplified to a single flat sidebar link (see
                                staff_attendance.php's own menu registration); this
                                reuses the exact same native Setup slide-out panel every
                                other "Setup" gear icon in the app already opens
                                (.open-customizer, assets/js/main.js) rather than a new
                                settings page/controller - admin-only, matching the
                                existing "Setup" gear's own visibility. -->
                                <a href="#" class="btn btn-default btn-sm open-customizer" title="<?= _l('staff_attendance_settings'); ?>"><i class="fa-solid fa-gear"></i></a>
                                <button type="button" class="btn btn-primary btn-sm" onclick="attendance_open_add_modal();"><i class="fa-solid fa-plus"></i> <?= _l('staff_attendance_add'); ?></button>
                            </div>
                        </div>
                        <hr class="hr-panel-separator" />

                        <ul class="nav nav-tabs nav-tabs-horizontal" role="tablist">
                            <li class="active"><a href="#tab_admin_attendance_records" data-toggle="tab"><?= _l('staff_attendance_tab_history'); ?></a></li>
                            <li><a href="#tab_admin_holidays" data-toggle="tab"><?= _l('staff_attendance_tab_holidays'); ?></a></li>
                        </ul>
                        <div class="tab-content tw-pt-4">
                        <div class="tab-pane active" id="tab_admin_attendance_records">

                        <div class="row attendance-filters">
                            <div class="col-md-3">
                                <select id="attendance-filter-staff" class="selectpicker" data-width="100%" data-live-search="true"
                                    data-none-selected-text="<?= _l('staff_attendance_filter_all_employees'); ?>">
                                    <option value=""><?= _l('staff_attendance_filter_all_employees'); ?></option>
                                    <?php foreach ($staff as $member) { ?>
                                    <option value="<?= (int) $member['staffid']; ?>"><?= e($member['firstname'] . ' ' . $member['lastname']); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select id="attendance-filter-department" class="selectpicker" data-width="100%"
                                    data-none-selected-text="<?= _l('staff_attendance_filter_all_departments'); ?>">
                                    <option value=""><?= _l('staff_attendance_filter_all_departments'); ?></option>
                                    <?php foreach ($departments as $department) { ?>
                                    <option value="<?= (int) $department['id']; ?>"><?= e($department['name']); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select id="attendance-filter-status" class="selectpicker" data-width="100%"
                                    data-none-selected-text="<?= _l('staff_attendance_filter_all_statuses'); ?>">
                                    <option value=""><?= _l('staff_attendance_filter_all_statuses'); ?></option>
                                    <?php foreach (get_attendance_statuses() as $status) { ?>
                                    <option value="<?= e($status); ?>"><?= e($status); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <div class="input-group date">
                                    <?= render_date_input('attendance_date_from', '', '', ['placeholder' => _l('staff_attendance_filter_date_from')]); ?>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="input-group date">
                                    <?= render_date_input('attendance_date_to', '', '', ['placeholder' => _l('staff_attendance_filter_date_to')]); ?>
                                </div>
                            </div>
                        </div>
                        <div class="clearfix mtop15"></div>

                        <!-- Monthly Attendance Dashboard - only populated/shown
                        when exactly one employee is selected in the filter
                        above (never for "All Employees") - see this file's own
                        JS below. Empty by default, filled via AJAX. -->
                        <div id="attendance-month-dashboard" style="display:none;" class="tw-mb-4"></div>

                        <?php render_datatable([
                            _l('staff_attendance_column_employee'),
                            _l('staff_attendance_column_date'),
                            _l('staff_attendance_column_login'),
                            _l('staff_attendance_column_logout'),
                            _l('staff_attendance_column_working_hours'),
                            _l('staff_attendance_column_status'),
                            _l('staff_attendance_column_remarks'),
                        ], 'staff-attendance'); ?>

                        </div>

                        <div class="tab-pane" id="tab_admin_holidays">
                            <div class="tw-flex tw-justify-end tw-mb-3">
                                <button type="button" class="btn btn-primary btn-sm" onclick="attendance_open_holiday_modal();"><i class="fa-solid fa-plus"></i> <?= _l('staff_attendance_holiday_add'); ?></button>
                            </div>
                            <?php if (empty($holidays)) { ?>
                            <p class="text-muted"><?= _l('staff_attendance_no_holidays'); ?></p>
                            <?php } else { ?>
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th><?= _l('staff_attendance_holiday_column_name'); ?></th>
                                        <th><?= _l('staff_attendance_holiday_column_date'); ?></th>
                                        <th><?= _l('staff_attendance_holiday_column_day'); ?></th>
                                        <th><?= _l('staff_attendance_holiday_column_description'); ?></th>
                                        <th><?= _l('options'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($holidays as $holiday) { ?>
                                    <tr>
                                        <td><?= e($holiday['name']); ?></td>
                                        <td><?= _d($holiday['date']); ?></td>
                                        <td><?= e(date('l', strtotime($holiday['date']))); ?></td>
                                        <td><?= $holiday['description'] ? e($holiday['description']) : '-'; ?></td>
                                        <td>
                                            <button type="button" class="btn btn-default btn-icon" onclick="attendance_open_holiday_modal(<?= (int) $holiday['id']; ?>);"><i class="fa-regular fa-pen-to-square"></i></button>
                                            <button type="button" class="btn btn-danger btn-icon" onclick="attendance_delete_holiday(<?= (int) $holiday['id']; ?>);"><i class="fa-regular fa-trash-can"></i></button>
                                        </td>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                            <?php } ?>
                        </div>

                        </div>

                        <?php } else { ?>
                        <h4 class="no-margin"><?= _l('staff_attendance_my_attendance'); ?></h4>
                        <hr class="hr-panel-separator" />

                        <?php if ($late_arrival_prompt) { ?>
                        <div class="alert alert-warning tw-flex tw-items-center tw-justify-between tw-flex-wrap tw-gap-2">
                            <div>
                                <strong><?= _l('staff_attendance_late_arrival_detected'); ?></strong>
                                <div><?= _l('staff_attendance_late_arrival_please_explain'); ?></div>
                            </div>
                            <button type="button" class="btn btn-warning btn-sm" onclick="attendance_open_late_arrival_modal();"><?= _l('staff_attendance_submit_request'); ?></button>
                        </div>
                        <?php } ?>

                        <?php if ($early_exit_prompt) { ?>
                        <div class="alert alert-warning tw-flex tw-items-center tw-justify-between tw-flex-wrap tw-gap-2">
                            <div>
                                <strong><?= _l('staff_attendance_early_exit_detected'); ?></strong>
                                <div><?= _l('staff_attendance_early_exit_please_explain'); ?></div>
                            </div>
                            <button type="button" class="btn btn-warning btn-sm" onclick="attendance_open_early_exit_modal();"><?= _l('staff_attendance_submit_request'); ?></button>
                        </div>
                        <?php } ?>

                        <ul class="nav nav-tabs nav-tabs-horizontal" role="tablist">
                            <li class="active"><a href="#tab_attendance_history" data-toggle="tab"><?= _l('staff_attendance_tab_history'); ?></a></li>
                            <li><a href="#tab_leave_requests" data-toggle="tab"><?= _l('staff_attendance_tab_leave_requests'); ?></a></li>
                            <li><a href="#tab_holiday_calendar" data-toggle="tab"><?= _l('staff_attendance_tab_holidays'); ?></a></li>
                            <li><a href="#tab_late_early_requests" data-toggle="tab"><?= _l('staff_attendance_tab_late_early'); ?></a></li>
                        </ul>
                        <div class="tab-content tw-pt-4">
                            <div class="tab-pane active" id="tab_attendance_history">
                                <?php render_datatable([
                                    _l('staff_attendance_column_date'),
                                    _l('staff_attendance_column_login'),
                                    _l('staff_attendance_column_logout'),
                                    _l('staff_attendance_column_working_hours'),
                                    _l('staff_attendance_column_status'),
                                    _l('staff_attendance_column_remarks'),
                                ], 'staff-attendance'); ?>
                            </div>

                            <div class="tab-pane" id="tab_leave_requests">
                                <div class="tw-flex tw-items-center tw-justify-between tw-flex-wrap tw-gap-2 tw-mb-3">
                                    <div class="col-md-3 no-padding">
                                        <select id="leave-request-filter-status" class="selectpicker" data-width="100%"
                                            data-none-selected-text="<?= _l('staff_attendance_leave_filter_all_statuses'); ?>">
                                            <option value=""><?= _l('staff_attendance_leave_filter_all_statuses'); ?></option>
                                            <?php foreach (['Pending', 'Approved', 'Rejected', 'Cancelled'] as $leave_status) { ?>
                                            <option value="<?= e($leave_status); ?>"><?= e($leave_status); ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <button type="button" class="btn btn-primary btn-sm" onclick="attendance_open_leave_request_modal();"><i class="fa-solid fa-plus"></i> <?= _l('staff_attendance_request_leave'); ?></button>
                                </div>
                                <?php render_datatable([
                                    _l('staff_attendance_leave_column_type'),
                                    _l('staff_attendance_leave_column_from'),
                                    _l('staff_attendance_leave_column_to'),
                                    _l('staff_attendance_leave_column_days'),
                                    _l('staff_attendance_leave_column_applied'),
                                    _l('staff_attendance_leave_column_status'),
                                    _l('staff_attendance_leave_column_remarks'),
                                    _l('staff_attendance_leave_column_reviewed'),
                                    _l('options'),
                                ], 'staff-leave-requests'); ?>
                            </div>

                            <div class="tab-pane" id="tab_holiday_calendar">
                                <div class="tw-flex tw-justify-end tw-mb-3">
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-default btn-sm active" id="holiday-view-list-btn" onclick="attendance_toggle_holiday_view('list');"><?= _l('staff_attendance_holiday_list_view'); ?></button>
                                        <button type="button" class="btn btn-default btn-sm" id="holiday-view-calendar-btn" onclick="attendance_toggle_holiday_view('calendar');"><?= _l('staff_attendance_holiday_calendar_view'); ?></button>
                                    </div>
                                </div>

                                <div id="holiday-view-list">
                                    <?php if (empty($holidays)) { ?>
                                    <p class="text-muted"><?= _l('staff_attendance_no_holidays'); ?></p>
                                    <?php } else { ?>
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th><?= _l('staff_attendance_holiday_column_name'); ?></th>
                                                <th><?= _l('staff_attendance_holiday_column_date'); ?></th>
                                                <th><?= _l('staff_attendance_holiday_column_day'); ?></th>
                                                <th><?= _l('staff_attendance_holiday_column_description'); ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($holidays as $holiday) { ?>
                                            <tr>
                                                <td><?= e($holiday['name']); ?></td>
                                                <td><?= _d($holiday['date']); ?></td>
                                                <td><?= e(date('l', strtotime($holiday['date']))); ?></td>
                                                <td><?= $holiday['description'] ? e($holiday['description']) : '-'; ?></td>
                                            </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                    <?php } ?>
                                </div>

                                <div id="holiday-view-calendar" style="display:none;">
                                    <?php
                                    $holiday_current_month = (int) date('n');
                                    $holiday_current_year   = (int) date('Y');
                                    $holiday_days_in_month  = (int) date('t');
                                    $holiday_first_day_dow  = (int) date('N', mktime(0, 0, 0, $holiday_current_month, 1, $holiday_current_year));
                                    $holiday_leading_blanks = $holiday_first_day_dow - 1;
                                    $holidays_by_date       = [];
                                    foreach ($holidays as $holiday) {
                                        $holidays_by_date[$holiday['date']][] = $holiday;
                                    }
                                    ?>
                                    <h5 class="text-muted"><?= e(date('F Y')); ?></h5>
                                    <table class="table table-bordered" style="table-layout:fixed;">
                                        <thead>
                                            <tr>
                                                <?php foreach ([_l('staff_attendance_day_mon'), _l('staff_attendance_day_tue'), _l('staff_attendance_day_wed'), _l('staff_attendance_day_thu'), _l('staff_attendance_day_fri'), _l('staff_attendance_day_sat'), _l('staff_attendance_day_sun')] as $day_name) { ?>
                                                <th class="text-center"><?= e($day_name); ?></th>
                                                <?php } ?>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <?php for ($i = 0; $i < $holiday_leading_blanks; $i++) { ?>
                                                <td></td>
                                                <?php } ?>
                                                <?php
                                                $holiday_cell_in_row = $holiday_leading_blanks;
                                                for ($day = 1; $day <= $holiday_days_in_month; $day++) {
                                                    $date          = sprintf('%04d-%02d-%02d', $holiday_current_year, $holiday_current_month, $day);
                                                    $day_holidays  = $holidays_by_date[$date] ?? [];
                                                    ?>
                                                <td style="vertical-align:top;height:60px;<?= !empty($day_holidays) ? 'background:' . adjust_hex_brightness('#dc2626', 0.08) . ';' : ''; ?>">
                                                    <strong><?= $day; ?></strong>
                                                    <?php foreach ($day_holidays as $day_holiday) { ?>
                                                    <div class="tw-text-xs" style="color:#dc2626;" title="<?= e($day_holiday['name']); ?>"><?= e($day_holiday['name']); ?></div>
                                                    <?php } ?>
                                                </td>
                                                    <?php
                                                    $holiday_cell_in_row++;
                                                    if ($holiday_cell_in_row % 7 === 0 && $day < $holiday_days_in_month) {
                                                        echo '</tr><tr>';
                                                    }
                                                }
                                                $holiday_trailing_blanks = (7 - ($holiday_cell_in_row % 7)) % 7;
                                                for ($i = 0; $i < $holiday_trailing_blanks; $i++) {
                                                    echo '<td></td>';
                                                }
                                                ?>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="tab-pane" id="tab_late_early_requests">
                                <h5><?= _l('staff_attendance_late_arrival_tab_title'); ?></h5>
                                <?php if (empty($late_arrival_requests)) { ?>
                                <p class="text-muted"><?= _l('staff_attendance_no_late_arrival_requests'); ?></p>
                                <?php } else { ?>
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th><?= _l('staff_attendance_column_date'); ?></th>
                                            <th><?= _l('staff_attendance_column_login'); ?></th>
                                            <th><?= _l('staff_attendance_late_delay'); ?></th>
                                            <th><?= _l('staff_attendance_late_reason'); ?></th>
                                            <th><?= _l('staff_attendance_column_status'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($late_arrival_requests as $late_request) { ?>
                                        <tr>
                                            <td><?= _d($late_request['attendance_date']); ?></td>
                                            <td><?= _dt($late_request['login_time']); ?></td>
                                            <td><?= (int) staff_attendance_late_minutes($late_request['login_time']); ?> <?= _l('staff_attendance_minutes'); ?></td>
                                            <td><?= e($late_request['reason']); ?></td>
                                            <td><span class="label" style="color:<?= staff_attendance_request_status_color($late_request['status']); ?>;border:1px solid <?= adjust_hex_brightness(staff_attendance_request_status_color($late_request['status']), 0.4); ?>;background:<?= adjust_hex_brightness(staff_attendance_request_status_color($late_request['status']), 0.04); ?>;"><?= e($late_request['status']); ?></span></td>
                                        </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                                <?php } ?>

                                <hr class="hr-panel-separator" />

                                <h5><?= _l('staff_attendance_early_exit_tab_title'); ?></h5>
                                <?php if (empty($early_exit_requests)) { ?>
                                <p class="text-muted"><?= _l('staff_attendance_no_early_exit_requests'); ?></p>
                                <?php } else { ?>
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th><?= _l('staff_attendance_column_date'); ?></th>
                                            <th><?= _l('staff_attendance_column_logout'); ?></th>
                                            <th><?= _l('staff_attendance_early_duration'); ?></th>
                                            <th><?= _l('staff_attendance_early_reason'); ?></th>
                                            <th><?= _l('staff_attendance_column_status'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($early_exit_requests as $early_request) { ?>
                                        <tr>
                                            <td><?= _d($early_request['attendance_date']); ?></td>
                                            <td><?= _dt($early_request['logout_time']); ?></td>
                                            <td><?= (int) staff_attendance_early_minutes($early_request['logout_time']); ?> <?= _l('staff_attendance_minutes'); ?></td>
                                            <td><?= e($early_request['reason']); ?></td>
                                            <td><span class="label" style="color:<?= staff_attendance_request_status_color($early_request['status']); ?>;border:1px solid <?= adjust_hex_brightness(staff_attendance_request_status_color($early_request['status']), 0.4); ?>;background:<?= adjust_hex_brightness(staff_attendance_request_status_color($early_request['status']), 0.04); ?>;"><?= e($early_request['status']); ?></span></td>
                                        </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                                <?php } ?>
                            </div>
                        </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (is_admin()) { ?>
<div id="attendance-add-modal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-body"></div>
        </div>
    </div>
</div>

<div id="attendance-remarks-modal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><?= _l('staff_attendance_edit_remarks'); ?></h4>
            </div>
            <div class="modal-body">
                <input type="hidden" id="attendance-remarks-id" value="" />
                <textarea id="attendance-remarks-value" class="form-control" rows="3"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?= _l('close'); ?></button>
                <button type="button" class="btn btn-primary" onclick="attendance_save_remarks();"><?= _l('save'); ?></button>
            </div>
        </div>
    </div>
</div>

<div id="attendance-holiday-modal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-body"></div>
        </div>
    </div>
</div>
<?php } else { ?>
<div id="leave-request-modal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-body"></div>
        </div>
    </div>
</div>

<div id="late-arrival-modal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-body"></div>
        </div>
    </div>
</div>

<div id="early-exit-modal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-body"></div>
        </div>
    </div>
</div>
<?php } ?>

<?php init_tail(); ?>
<script>
    $(function() {
        <?php if (is_admin()) { ?>
        // Date (index 1) is not searchable/sortable - in the default
        // "single date" mode (see Staff_attendance::table()'s own
        // docblock) it's a fixed value shared by every row, not a real
        // per-row column. Default sort is by Employee name instead of
        // Date, since sorting by an identical value would be a no-op.
        initDataTable('.table-staff-attendance', admin_url + 'staff_attendance/table', [1], [1], {
            staff_id: '#attendance-filter-staff',
            department_id: '#attendance-filter-department',
            status: '#attendance-filter-status',
            date_from: 'input[name="attendance_date_from"]',
            date_to: 'input[name="attendance_date_to"]',
        }, [0, 'asc']);

        $('.attendance-filters select').on('changed.bs.select', function() {
            $('.table-staff-attendance').DataTable().ajax.reload();
        });
        $('.attendance-filters input[name="attendance_date_from"], .attendance-filters input[name="attendance_date_to"]').on('change', function() {
            $('.table-staff-attendance').DataTable().ajax.reload();
        });

        // Monthly Attendance Dashboard - only for exactly one selected
        // employee, never "All Employees" (requirement #8). Re-fires on
        // every Employee filter change; year/month default to the
        // current month on first load, then persist via the container's
        // own data attributes across the Prev/Current/Next nav clicks
        // (delegated below, since this container's contents are replaced
        // wholesale on every AJAX load).
        $('#attendance-filter-staff').on('changed.bs.select', function() {
            var staffId = $(this).val();
            if (!staffId) {
                $('#attendance-month-dashboard').hide().empty();

                return;
            }
            attendance_load_month_dashboard(staffId, null, null);
        });

        $(document).on('click', '.attendance-month-nav', function() {
            var $inner = $('#attendance-month-dashboard-inner');
            if (!$inner.length) {
                return;
            }

            var staffId = $inner.data('staff-id');
            var year = parseInt($inner.data('year'), 10);
            var month = parseInt($inner.data('month'), 10);
            var direction = $(this).data('direction');

            if (direction === 'current') {
                year = null;
                month = null;
            } else if (direction === 'prev') {
                month--;
                if (month < 1) {
                    month = 12;
                    year--;
                }
            } else if (direction === 'next') {
                month++;
                if (month > 12) {
                    month = 1;
                    year++;
                }
            }

            attendance_load_month_dashboard(staffId, year, month);
        });

        $(document).on('click', '.editable-remarks', function() {
            $('#attendance-remarks-id').val($(this).data('id'));
            $('#attendance-remarks-value').val($(this).data('value'));
            $('#attendance-remarks-modal').modal('show');
        });
        <?php } else { ?>
        initDataTable('.table-staff-attendance', admin_url + 'staff_attendance/table', [], [], {}, [0, 'desc']);
        initDataTable('.table-staff-leave-requests', admin_url + 'staff_attendance/leave_requests_table', [], [8], {
            status: '#leave-request-filter-status',
        }, [4, 'desc']);

        $('#leave-request-filter-status').on('changed.bs.select', function() {
            $('.table-staff-leave-requests').DataTable().ajax.reload();
        });

        // DataTables inside a hidden .tab-pane (display:none at init)
        // compute zero-width columns - re-adjusting on the Bootstrap
        // tab-shown event is the standard fix, needed here since Leave
        // Requests is not the default-active tab.
        $('a[data-toggle="tab"]').on('shown.bs.tab', function() {
            $($(this).attr('href')).find('table.dataTable').DataTable().columns.adjust();
        });

        // Dashboard widget deep links (Leave Requests/Pending/Approved/
        // Holidays cards) arrive as #tab_xxx - Bootstrap 3 tabs don't
        // auto-activate from location.hash, so do it once on load.
        if (location.hash) {
            var $targetTab = $('a[data-toggle="tab"][href="' + location.hash + '"]');
            if ($targetTab.length) {
                $targetTab.tab('show');
            }
        }
        <?php } ?>
    });

    <?php if (is_admin()) { ?>
    function attendance_load_month_dashboard(staffId, year, month) {
        var params = {};
        if (year && month) {
            params.year = year;
            params.month = month;
        }

        $.post(admin_url + 'staff_attendance/month_dashboard/' + staffId, params).done(function(response) {
            $('#attendance-month-dashboard').html(response).show();
        });
    }

    function attendance_mark_as(id, status) {
        $.post(admin_url + 'staff_attendance/update_status/' + id, { status: status }).done(function() {
            $('.table-staff-attendance').DataTable().ajax.reload(null, false);
        });
    }

    function attendance_save_remarks() {
        var id = $('#attendance-remarks-id').val();
        $.post(admin_url + 'staff_attendance/update_remarks/' + id, { remarks: $('#attendance-remarks-value').val() }).done(function() {
            $('#attendance-remarks-modal').modal('hide');
            $('.table-staff-attendance').DataTable().ajax.reload(null, false);
        });
    }

    function attendance_open_add_modal() {
        $('#attendance-add-modal .modal-body').load(admin_url + 'staff_attendance/add', function() {
            $('#attendance-add-modal').modal('show');
            appSelectPicker();
            init_datepicker();
        });
    }

    function attendance_submit_add(form) {
        $.post(admin_url + 'staff_attendance/add', $(form).serialize()).done(function(response) {
            var data = typeof response === 'string' ? JSON.parse(response) : response;
            if (data.success) {
                $('#attendance-add-modal').modal('hide');
                $('.table-staff-attendance').DataTable().ajax.reload(null, false);
            } else {
                alert_float('danger', data.message);
            }
        });

        return false;
    }

    function attendance_open_holiday_modal(id) {
        var url = admin_url + 'staff_attendance/holiday_form' + (id ? '?id=' + id : '');
        $('#attendance-holiday-modal .modal-body').load(url, function() {
            $('#attendance-holiday-modal').modal('show');
            init_datepicker();
        });
    }

    function attendance_submit_holiday(form) {
        // $.ajaxSetup() (general_helper.php) injects the CSRF token as
        // the default `data` for every AJAX call, but jQuery only merges
        // that default with a per-call `data` that is ALSO a plain
        // object - a serialized query string (or a FormData instance,
        // see the 3 FormData-based submits below) isn't a plain object,
        // so it replaces the default outright and silently drops the
        // token, producing a 419 on submit. Posting a plain object
        // (built from serializeArray()) instead lets jQuery deep-merge
        // it with the CSRF default, the same way this app's own
        // pre-existing $.post(url, {status: status}) calls already do.
        var formObj = {};
        $(form).serializeArray().forEach(function(field) {
            formObj[field.name] = field.value;
        });

        $.post(admin_url + 'staff_attendance/holiday_form', formObj).done(function(response) {
            var data = typeof response === 'string' ? JSON.parse(response) : response;
            if (data.success) {
                location.reload();
            } else {
                alert_float('danger', data.message);
            }
        });

        return false;
    }

    function attendance_delete_holiday(id) {
        if (!confirm('<?= _l('staff_attendance_holiday_delete_confirm'); ?>')) {
            return;
        }

        $.post(admin_url + 'staff_attendance/delete_holiday/' + id).done(function(response) {
            var data = typeof response === 'string' ? JSON.parse(response) : response;
            if (data.success) {
                location.reload();
            }
        });
    }
    <?php } else { ?>
    function attendance_open_leave_request_modal() {
        $('#leave-request-modal .modal-body').load(admin_url + 'staff_attendance/submit_leave_request', function() {
            $('#leave-request-modal').modal('show');
            appSelectPicker();
            init_datepicker();
        });
    }

    function attendance_submit_leave_request(form) {
        var formData = new FormData(form);
        // FormData isn't a plain object, so $.ajaxSetup()'s CSRF default
        // data never merges into it (see attendance_submit_holiday()'s
        // own comment above) - append the token directly.
        formData.append(csrfData.token_name, csrfData.hash);

        $.ajax({
            url: admin_url + 'staff_attendance/submit_leave_request',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
        }).done(function(response) {
            var data = typeof response === 'string' ? JSON.parse(response) : response;
            if (data.success) {
                $('#leave-request-modal').modal('hide');
                $('.table-staff-leave-requests').DataTable().ajax.reload();
                alert_float('success', data.message);
            } else {
                alert_float('danger', data.message);
            }
        });

        return false;
    }

    function attendance_cancel_leave_request(id) {
        if (!confirm('<?= _l('staff_attendance_cancel_request_confirm'); ?>')) {
            return;
        }

        $.post(admin_url + 'staff_attendance/cancel_leave_request/' + id).done(function(response) {
            var data = typeof response === 'string' ? JSON.parse(response) : response;
            if (data.success) {
                $('.table-staff-leave-requests').DataTable().ajax.reload();
            }
        });
    }

    function attendance_open_late_arrival_modal() {
        $('#late-arrival-modal .modal-body').load(admin_url + 'staff_attendance/submit_late_arrival_request', function() {
            $('#late-arrival-modal').modal('show');
        });
    }

    function attendance_submit_late_arrival_request(form) {
        var formData = new FormData(form);
        // FormData isn't a plain object, so $.ajaxSetup()'s CSRF default
        // data never merges into it (see attendance_submit_holiday()'s
        // own comment above) - append the token directly.
        formData.append(csrfData.token_name, csrfData.hash);

        $.ajax({
            url: admin_url + 'staff_attendance/submit_late_arrival_request',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
        }).done(function(response) {
            var data = typeof response === 'string' ? JSON.parse(response) : response;
            if (data.success) {
                $('#late-arrival-modal').modal('hide');
                alert_float('success', data.message);
                setTimeout(function() {
                    location.reload();
                }, 800);
            } else {
                alert_float('danger', data.message);
            }
        });

        return false;
    }

    function attendance_open_early_exit_modal() {
        $('#early-exit-modal .modal-body').load(admin_url + 'staff_attendance/submit_early_exit_request', function() {
            $('#early-exit-modal').modal('show');
        });
    }

    function attendance_submit_early_exit_request(form) {
        var formData = new FormData(form);
        // FormData isn't a plain object, so $.ajaxSetup()'s CSRF default
        // data never merges into it (see attendance_submit_holiday()'s
        // own comment above) - append the token directly.
        formData.append(csrfData.token_name, csrfData.hash);

        $.ajax({
            url: admin_url + 'staff_attendance/submit_early_exit_request',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
        }).done(function(response) {
            var data = typeof response === 'string' ? JSON.parse(response) : response;
            if (data.success) {
                $('#early-exit-modal').modal('hide');
                alert_float('success', data.message);
                setTimeout(function() {
                    location.reload();
                }, 800);
            } else {
                alert_float('danger', data.message);
            }
        });

        return false;
    }

    function attendance_toggle_holiday_view(view) {
        var isList = view === 'list';
        $('#holiday-view-list').toggle(isList);
        $('#holiday-view-calendar').toggle(!isList);
        $('#holiday-view-list-btn').toggleClass('active', isList);
        $('#holiday-view-calendar-btn').toggleClass('active', !isList);
    }
    <?php } ?>
</script>
</body>
</html>
