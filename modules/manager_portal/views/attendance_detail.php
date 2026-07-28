<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<?php
    $staff    = $detail['staff'];
    $calendar = $detail['calendar'];
    $summary  = $detail['summary'];

    $month_label    = date('F Y', mktime(0, 0, 0, $month, 1, $year));
    $days_in_month  = (int) date('t', mktime(0, 0, 0, $month, 1, $year));
    $today          = date('Y-m-d');
    $first_day_dow  = (int) date('N', mktime(0, 0, 0, $month, 1, $year));
    $leading_blanks = $first_day_dow - 1;

    $prev = mktime(0, 0, 0, $month - 1, 1, $year);
    $next = mktime(0, 0, 0, $month + 1, 1, $year);
?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="tw-flex tw-items-center tw-justify-between tw-flex-wrap tw-gap-2">
                            <h4 class="no-margin"><?= e(trim($staff->firstname . ' ' . $staff->lastname)); ?></h4>
                            <a href="<?= admin_url('manager_portal/attendance'); ?>" class="btn btn-default"><i class="fa-solid fa-arrow-left"></i> <?= _l('manager_portal_attendance_back'); ?></a>
                        </div>
                        <hr class="hr-panel-separator" />

                        <div class="row tw-mb-4">
                            <div class="col-md-2">
                                <strong><?= _l('manager_portal_attendance_present_days'); ?></strong>
                                <p><?= (int) $summary['counts']['Present']; ?></p>
                            </div>
                            <div class="col-md-2">
                                <strong><?= _l('manager_portal_attendance_absent_days'); ?></strong>
                                <p><?= (int) $summary['counts']['Absent']; ?></p>
                            </div>
                            <div class="col-md-2">
                                <strong><?= _l('manager_portal_attendance_late_days'); ?></strong>
                                <p><?= (int) $summary['counts']['Late']; ?></p>
                            </div>
                            <div class="col-md-3">
                                <strong><?= _l('manager_portal_attendance_working_hours'); ?></strong>
                                <p><?= e(format_working_minutes($summary['working_hours']['total'])); ?></p>
                            </div>
                            <div class="col-md-3">
                                <strong><?= _l('manager_portal_attendance_average_hours'); ?></strong>
                                <p><?= e(format_working_minutes($summary['working_hours']['average'])); ?></p>
                            </div>
                        </div>

                        <div class="row tw-mb-4">
                            <div class="col-md-6">
                                <label class="text-muted"><?= _l('manager_portal_attendance_percentage'); ?></label>
                                <div class="tw-text-3xl tw-font-semibold tw-mb-2"><?= (int) $summary['attendance_rate']; ?>%</div>
                                <div class="progress" style="margin-bottom:0;">
                                    <div class="progress-bar no-percent-text not-dynamic" role="progressbar" aria-valuenow="<?= (int) $summary['attendance_rate']; ?>" aria-valuemin="0" aria-valuemax="100" data-percent="<?= (int) $summary['attendance_rate']; ?>" style="width:<?= (int) $summary['attendance_rate']; ?>%;background:<?= get_attendance_status_color('Present'); ?>;"></div>
                                </div>
                            </div>
                        </div>

                        <hr class="hr-panel-separator" />

                        <div class="tw-flex tw-items-center tw-justify-between tw-flex-wrap tw-mb-3">
                            <h5 class="no-margin"><?= _l('manager_portal_attendance_monthly_calendar'); ?> - <?= e($month_label); ?></h5>
                            <div class="btn-group">
                                <a href="<?= admin_url('manager_portal/attendance_detail/' . $staff->staffid . '?year=' . date('Y', $prev) . '&month=' . date('n', $prev)); ?>" class="btn btn-default btn-sm"><i class="fa-solid fa-chevron-left"></i></a>
                                <a href="<?= admin_url('manager_portal/attendance_detail/' . $staff->staffid); ?>" class="btn btn-default btn-sm"><?= _l('manager_portal_attendance_this_month'); ?></a>
                                <a href="<?= admin_url('manager_portal/attendance_detail/' . $staff->staffid . '?year=' . date('Y', $next) . '&month=' . date('n', $next)); ?>" class="btn btn-default btn-sm"><i class="fa-solid fa-chevron-right"></i></a>
                            </div>
                        </div>

                        <table class="table table-bordered" style="table-layout:fixed;">
                            <thead>
                                <tr>
                                    <?php foreach ([_l('manager_portal_day_mon'), _l('manager_portal_day_tue'), _l('manager_portal_day_wed'), _l('manager_portal_day_thu'), _l('manager_portal_day_fri'), _l('manager_portal_day_sat'), _l('manager_portal_day_sun')] as $day_name) { ?>
                                    <th class="text-center"><?= e($day_name); ?></th>
                                    <?php } ?>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <?php for ($i = 0; $i < $leading_blanks; $i++) { ?>
                                    <td></td>
                                    <?php } ?>
                                    <?php
                                        $cell_in_row = $leading_blanks;
                                        for ($day = 1; $day <= $days_in_month; $day++) {
                                            $date   = sprintf('%04d-%02d-%02d', $year, $month, $day);
                                            $record = $calendar[$date] ?? null;

                                            if ($record) {
                                                $status = $record['attendance_status'];
                                                $color  = get_attendance_status_color($status);
                                            } elseif (staff_attendance_is_weekend_day($date)) {
                                                $status = 'Weekend';
                                                $color  = get_attendance_status_color('Weekend');
                                            } elseif ($date <= $today) {
                                                $status = 'Absent';
                                                $color  = get_attendance_status_color('Absent');
                                            } else {
                                                $status = _l('manager_portal_attendance_not_marked');
                                                $color  = '#e5e7eb';
                                            }

                                            $is_today = $date === $today;
                                    ?>
                                    <td class="<?= $is_today ? 'attendance-day-today' : ''; ?>"
                                        style="background:<?= adjust_hex_brightness($color, 0.08); ?>;<?= $is_today ? 'border:2px solid ' . $color . ' !important;' : ''; ?>vertical-align:top;height:70px;"
                                        title="<?= e($status); ?>">
                                        <div class="tw-flex tw-items-center tw-justify-between">
                                            <strong><?= $day; ?></strong>
                                            <span class="tw-inline-block tw-rounded-full" style="width:9px;height:9px;background:<?= $color; ?>;"></span>
                                        </div>
                                        <?php if ($record) { ?>
                                        <div class="tw-text-xs tw-mt-1" style="color:<?= $color; ?>;"><?= e($status); ?></div>
                                        <?php } ?>
                                    </td>
                                    <?php
                                            $cell_in_row++;
                                            if ($cell_in_row % 7 === 0 && $day < $days_in_month) {
                                                echo '</tr><tr>';
                                            }
                                        }
                                        $trailing_blanks = (7 - ($cell_in_row % 7)) % 7;
                                        for ($i = 0; $i < $trailing_blanks; $i++) {
                                            echo '<td></td>';
                                        }
                                    ?>
                                </tr>
                            </tbody>
                        </table>

                        <hr class="hr-panel-separator" />

                        <h5><?= _l('manager_portal_attendance_recent'); ?></h5>
                        <?php if (empty($detail['recent'])) { ?>
                        <span class="text-muted"><?= _l('manager_portal_attendance_empty'); ?></span>
                        <?php } else { ?>
                        <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th><?= _l('manager_portal_dwu_column_date'); ?></th>
                                    <th><?= _l('manager_portal_dwu_column_checkin'); ?></th>
                                    <th><?= _l('manager_portal_dwu_column_checkout'); ?></th>
                                    <th><?= _l('manager_portal_attendance_column_total_hours'); ?></th>
                                    <th><?= _l('manager_portal_attendance_column_status'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($detail['recent'] as $record) { ?>
                                <tr>
                                    <td><?= e(_d($record['attendance_date'])); ?></td>
                                    <td><?= $record['login_time'] ? e(_dt($record['login_time'])) : '-'; ?></td>
                                    <td><?= $record['logout_time'] ? e(_dt($record['logout_time'])) : '-'; ?></td>
                                    <td><?= e(format_working_minutes($record['working_minutes'])); ?></td>
                                    <td><span class="label" style="color:<?= get_attendance_status_color($record['attendance_status']); ?>;border:1px solid <?= adjust_hex_brightness(get_attendance_status_color($record['attendance_status']), 0.4); ?>;background:<?= adjust_hex_brightness(get_attendance_status_color($record['attendance_status']), 0.04); ?>;"><?= e($record['attendance_status']); ?></span></td>
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
