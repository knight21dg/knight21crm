<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
// Monthly Attendance Dashboard - AJAX fragment, admin-only, loaded above
// the existing Attendance table whenever exactly one employee is
// selected in the Employee filter (never for "All Employees" - see
// manage.php's own JS for the show/hide + AJAX-load wiring). Every
// number here comes from Staff_attendance_model::get_month_summary()/
// get_month_calendar_data() (Staff_attendance.php::month_dashboard()) -
// no business logic lives in this view.
$month_label   = date('F Y', mktime(0, 0, 0, $month, 1, $year));
$days_in_month = (int) date('t', mktime(0, 0, 0, $month, 1, $year));
$today         = date('Y-m-d');

// Monday-first grid - ISO day-of-week (1=Monday..7=Sunday) of the 1st,
// used only to pad leading blank cells.
$first_day_dow  = (int) date('N', mktime(0, 0, 0, $month, 1, $year));
$leading_blanks = $first_day_dow - 1;

$not_marked_color = '#e5e7eb';
?>
<div id="attendance-month-dashboard-inner" data-year="<?= (int) $year; ?>" data-month="<?= (int) $month; ?>" data-staff-id="<?= (int) $staff_id; ?>">
    <div class="panel_s">
        <div class="panel-body">
            <div class="tw-flex tw-items-center tw-justify-between tw-flex-wrap tw-mb-3">
                <h4 class="no-margin"><?= e(trim($staff->firstname . ' ' . $staff->lastname)); ?> - <?= _l('staff_attendance_month_dashboard'); ?></h4>
                <div class="btn-group">
                    <button type="button" class="btn btn-default btn-sm attendance-month-nav" data-direction="prev"><i class="fa-solid fa-chevron-left"></i> <?= _l('staff_attendance_month_prev'); ?></button>
                    <button type="button" class="btn btn-default btn-sm attendance-month-nav" data-direction="current"><?= _l('staff_attendance_month_current'); ?></button>
                    <button type="button" class="btn btn-default btn-sm attendance-month-nav" data-direction="next"><?= _l('staff_attendance_month_next'); ?></button>
                </div>
            </div>
            <h5 class="text-muted tw-mb-3"><?= e($month_label); ?></h5>

            <!-- Summary cards -->
            <div class="row">
                <?php
                $cards = [
                    ['label' => _l('staff_attendance_summary_present'), 'value' => $summary['counts']['Present'], 'color' => get_attendance_status_color('Present')],
                    ['label' => _l('staff_attendance_summary_absent'), 'value' => $summary['counts']['Absent'], 'color' => get_attendance_status_color('Absent')],
                    ['label' => _l('staff_attendance_summary_leave'), 'value' => $summary['counts']['Leave'], 'color' => get_attendance_status_color('Leave')],
                    ['label' => _l('staff_attendance_summary_half_day'), 'value' => $summary['counts']['Half Day'], 'color' => get_attendance_status_color('Half Day')],
                    ['label' => _l('staff_attendance_summary_late_login'), 'value' => $summary['counts']['Late'], 'color' => get_attendance_status_color('Late')],
                    ['label' => _l('staff_attendance_summary_working_days'), 'value' => $summary['working_days'], 'color' => '#334155'],
                    ['label' => _l('staff_attendance_summary_weekends'), 'value' => $summary['weekend_days'], 'color' => get_attendance_status_color('Weekend')],
                ];
                foreach ($cards as $card) { ?>
                <div class="col-md-3 col-sm-4 col-xs-6 tw-mb-3">
                    <div class="tw-flex tw-items-center tw-gap-2">
                        <span class="tw-inline-block tw-rounded-full" style="width:8px;height:8px;background:<?= $card['color']; ?>;"></span>
                        <span class="text-muted"><?= $card['label']; ?></span>
                    </div>
                    <div class="tw-text-2xl tw-font-semibold"><?= (int) $card['value']; ?></div>
                </div>
                <?php } ?>
            </div>

            <hr class="hr-panel-separator" />

            <div class="row">
                <!-- Attendance Rate -->
                <div class="col-md-4 tw-mb-3">
                    <label class="text-muted"><?= _l('staff_attendance_rate'); ?></label>
                    <div class="tw-text-3xl tw-font-semibold tw-mb-2"><?= (int) $summary['attendance_rate']; ?>%</div>
                    <div class="progress" style="margin-bottom:0;">
                        <div class="progress-bar" role="progressbar" style="width:<?= (int) $summary['attendance_rate']; ?>%;background:<?= get_attendance_status_color('Present'); ?>;"></div>
                    </div>
                    <div class="text-muted tw-text-xs tw-mt-1"><?= _l('staff_attendance_rate_hint'); ?></div>
                </div>

                <!-- Working Hours summary -->
                <div class="col-md-8 tw-mb-3">
                    <label class="text-muted"><?= _l('staff_attendance_working_hours_summary'); ?></label>
                    <div class="row">
                        <div class="col-xs-3">
                            <div class="text-muted tw-text-xs"><?= _l('staff_attendance_hours_total'); ?></div>
                            <div class="tw-text-lg tw-font-semibold"><?= format_working_minutes($summary['working_hours']['total']); ?></div>
                        </div>
                        <div class="col-xs-3">
                            <div class="text-muted tw-text-xs"><?= _l('staff_attendance_hours_average'); ?></div>
                            <div class="tw-text-lg tw-font-semibold"><?= format_working_minutes($summary['working_hours']['average']); ?></div>
                        </div>
                        <div class="col-xs-3">
                            <div class="text-muted tw-text-xs"><?= _l('staff_attendance_hours_highest'); ?></div>
                            <div class="tw-text-lg tw-font-semibold"><?= format_working_minutes($summary['working_hours']['highest']); ?></div>
                        </div>
                        <div class="col-xs-3">
                            <div class="text-muted tw-text-xs"><?= _l('staff_attendance_hours_lowest'); ?></div>
                            <div class="tw-text-lg tw-font-semibold"><?= format_working_minutes($summary['working_hours']['lowest']); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <hr class="hr-panel-separator" />

            <!-- Calendar grid -->
            <table class="table table-bordered attendance-calendar-grid" style="table-layout:fixed;">
                <thead>
                    <tr>
                        <?php foreach ([_l('staff_attendance_day_mon'), _l('staff_attendance_day_tue'), _l('staff_attendance_day_wed'), _l('staff_attendance_day_thu'), _l('staff_attendance_day_fri'), _l('staff_attendance_day_sat'), _l('staff_attendance_day_sun')] as $day_name) { ?>
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
                            $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
                            $record = $calendar[$date] ?? null;

                            if ($record) {
                                $status = $record['attendance_status'];
                                $color  = get_attendance_status_color($status);
                                $has_popup = true;
                            } elseif (staff_attendance_is_weekend_day($date)) {
                                $status = 'Weekend';
                                $color  = get_attendance_status_color('Weekend');
                                $has_popup = false;
                            } elseif ($date <= $today) {
                                // Working day, no record, not in the future -
                                // automatic Absent (matches
                                // Staff_attendance_model::get_month_summary()'s
                                // own calculation exactly, never written to
                                // the database).
                                $status = 'Absent';
                                $color  = get_attendance_status_color('Absent');
                                $has_popup = false;
                            } else {
                                // Future working day - attendance can't have
                                // happened yet, so neither Present nor Absent.
                                $status = _l('staff_attendance_not_marked');
                                $color  = $not_marked_color;
                                $has_popup = false;
                            }

                            $login  = $record['login_time'] ? _dt($record['login_time']) : '-';
                            $logout = $record['logout_time'] ? _dt($record['logout_time']) : '-';
                            $hours  = $record ? format_working_minutes($record['working_minutes']) : '-';
                            $remarks = $record && $record['remarks'] ? $record['remarks'] : '-';
                            $is_today = $date === $today;
                            ?>
                        <td class="attendance-day-cell <?= $is_today ? 'attendance-day-today' : ''; ?>"
                            style="background:<?= adjust_hex_brightness($color, 0.08); ?>;<?= $is_today ? 'border:2px solid ' . $color . ' !important;' : ''; ?>cursor:<?= $has_popup ? 'pointer' : 'default'; ?>;vertical-align:top;height:70px;"
                            <?php if ($has_popup) { ?>
                            onclick='attendance_show_day_details(<?= json_encode([
                                'date'    => e(_d($date)),
                                'login'   => e($login),
                                'logout'  => e($logout),
                                'hours'   => e($hours),
                                'status'  => e($status),
                                'remarks' => e($remarks),
                            ]); ?>)'
                            data-toggle="attendance-day-tooltip"
                            data-html="true"
                            title="<?= e($status); ?><br><?= _l('staff_attendance_column_login'); ?>: <?= e($login); ?><br><?= _l('staff_attendance_column_logout'); ?>: <?= e($logout); ?><br><?= _l('staff_attendance_column_working_hours'); ?>: <?= e($hours); ?>"
                            <?php } ?>
                        >
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
                        // Trailing blanks to complete the last row.
                        $trailing_blanks = (7 - ($cell_in_row % 7)) % 7;
                        for ($i = 0; $i < $trailing_blanks; $i++) {
                            echo '<td></td>';
                        }
                        ?>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Date Details popup -->
<div id="attendance-day-details-modal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title" id="attendance-day-details-date"></h4>
            </div>
            <div class="modal-body">
                <p><strong><?= _l('staff_attendance_column_status'); ?>:</strong> <span id="attendance-day-details-status"></span></p>
                <p><strong><?= _l('staff_attendance_column_login'); ?>:</strong> <span id="attendance-day-details-login"></span></p>
                <p><strong><?= _l('staff_attendance_column_logout'); ?>:</strong> <span id="attendance-day-details-logout"></span></p>
                <p><strong><?= _l('staff_attendance_column_working_hours'); ?>:</strong> <span id="attendance-day-details-hours"></span></p>
                <p><strong><?= _l('staff_attendance_column_remarks'); ?>:</strong> <span id="attendance-day-details-remarks"></span></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?= _l('close'); ?></button>
            </div>
        </div>
    </div>
</div>
<script>
    (function() {
        // Root cause of two real bugs this fixes:
        //
        // 1) Bootstrap's tooltip defaults to container:false, which
        //    inserts the shown tooltip via $tip.insertAfter($element)
        //    (bootstrap.js Tooltip.prototype.show()) - i.e. as a raw DOM
        //    sibling of the trigger. For a <td> trigger, that makes the
        //    tooltip an invalid extra child of its <tr>, sitting between
        //    this day's cell and the next one - confirmed live: the next
        //    cell's own getBoundingClientRect() shifts and shrinks the
        //    instant the tooltip renders, which is exactly why that cell
        //    visually "goes blank"/displaced when a day is hovered or
        //    clicked (click is preceded by the mouse entering the cell,
        //    which is what actually shows the tooltip in the first
        //    place). container: 'body' below appends the tooltip to
        //    <body> instead, the standard fix for tooltips on table
        //    cells - the DOM insertion never touches the table again.
        //
        // 2) assets/js/main.js already does
        //    $("body").tooltip({selector: '[data-toggle="tooltip"]'})
        //    app-wide - a SECOND, independent tooltip binding that also
        //    matches any [data-toggle="tooltip"] element, this calendar
        //    included. Two bindings reacting to the same hover, combined
        //    with the layout churn from bug (1) constantly changing
        //    what's under the cursor, is what produced the rapid
        //    show/hide flicker. Renaming this attribute to
        //    data-toggle="attendance-day-tooltip" (see the <td> markup
        //    above) takes these cells out of that global selector's
        //    match entirely, so exactly one tooltip binding - this one -
        //    ever handles them.
        $('#attendance-month-dashboard [data-toggle="attendance-day-tooltip"]').tooltip({ container: 'body' });
    })();

    function attendance_show_day_details(data) {
        $('#attendance-day-details-date').text(data.date);
        $('#attendance-day-details-status').text(data.status);
        $('#attendance-day-details-login').text(data.login);
        $('#attendance-day-details-logout').text(data.logout);
        $('#attendance-day-details-hours').text(data.hours);
        $('#attendance-day-details-remarks').text(data.remarks);
        $('#attendance-day-details-modal').modal('show');
    }
</script>
