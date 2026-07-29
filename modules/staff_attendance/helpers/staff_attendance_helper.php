<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * The 6 allowed Attendance Status values - the single source of truth
 * reused by the admin status-edit dropdown, the DataTable badge render,
 * and the Dashboard summary widget, so no list can ever drift out of
 * sync with another. Stored verbatim as these strings in
 * tblstaff_attendance.attendance_status (same "store the human label"
 * convention this fork already uses for Customer Work Status).
 *
 * @return array
 */
function get_attendance_statuses()
{
    return [
        'Present',
        'Absent',
        'Late',
        'Half Day',
        'Leave',
        'Weekend',
    ];
}

/**
 * Single reusable source for the Attendance Status badge color.
 *
 * @param string $status
 * @return string hex color
 */
function get_attendance_status_color($status)
{
    $colors = [
        'Present'  => '#22c55e',
        'Absent'   => '#dc2626',
        'Late'     => '#f97316',
        'Half Day' => '#eab308',
        'Leave'    => '#3b82f6',
        'Weekend'  => '#64748b',
    ];

    return $colors[$status] ?? '#64748b';
}

/**
 * Formats a total-minutes integer as "8h 35m" for display - the raw
 * total-minutes value stays what's actually stored in the DB
 * (tblstaff_attendance.working_minutes); this is a display-only
 * formatter, reused by both the Admin list and the Staff "My
 * Attendance" view so the format can't drift between the two.
 *
 * @param int|null $minutes
 * @return string
 */
function format_working_minutes($minutes)
{
    if ($minutes === null || $minutes === '') {
        return '-';
    }

    $minutes = (int) $minutes;
    $hours   = intdiv($minutes, 60);
    $mins    = $minutes % 60;

    return $hours . 'h ' . $mins . 'm';
}

/**
 * Weekend definition for the Monthly Attendance Dashboard - Sunday only
 * (confirmed no native Perfex weekend-day setting exists anywhere in
 * this install; this is this module's own, documented convention, not
 * something read from options). Used to color a calendar day that has
 * no tblstaff_attendance row at all, and to compute "Working Days" for
 * the Attendance Rate stat.
 *
 * @param  string $date Y-m-d
 * @return bool
 */
function staff_attendance_is_weekend_day($date)
{
    return (int) date('w', strtotime($date)) === 0;
}

/**
 * Count of working days (non-Sunday) in a given month - the denominator
 * for "Attendance Rate = Present / Working Days".
 *
 * @param  int $year
 * @param  int $month
 * @return int
 */
function staff_attendance_month_working_days($year, $month)
{
    $days_in_month = (int) date('t', mktime(0, 0, 0, $month, 1, $year));
    $working_days  = 0;

    for ($day = 1; $day <= $days_in_month; $day++) {
        $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
        if (!staff_attendance_is_weekend_day($date)) {
            $working_days++;
        }
    }

    return $working_days;
}

/**
 * Expected office hours - no native Perfex office-hours setting exists
 * anywhere in this install; this is this module's own documented
 * convention (originally introduced by Manager Portal, relocated here as
 * the central attendance-domain home so the Late Arrival/Early Exit
 * Request feature can reuse the exact same threshold Manager Portal's
 * own attendance review page already displays, instead of a second
 * definition drifting out of sync). manager_portal_helper.php's own
 * manager_portal_attendance_is_late_arrival()/_is_early_exit() now
 * delegate to these.
 */
defined('STAFF_ATTENDANCE_EXPECTED_LOGIN') or define('STAFF_ATTENDANCE_EXPECTED_LOGIN', '09:30:00');
defined('STAFF_ATTENDANCE_EXPECTED_LOGOUT') or define('STAFF_ATTENDANCE_EXPECTED_LOGOUT', '18:30:00');

/**
 * @param  string|null $login_time datetime string, or null
 * @return bool
 */
function staff_attendance_is_late_arrival($login_time)
{
    if (!$login_time) {
        return false;
    }

    return date('H:i:s', strtotime($login_time)) > STAFF_ATTENDANCE_EXPECTED_LOGIN;
}

/**
 * @param  string|null $logout_time datetime string, or null
 * @return bool
 */
function staff_attendance_is_early_exit($logout_time)
{
    if (!$logout_time) {
        return false;
    }

    return date('H:i:s', strtotime($logout_time)) < STAFF_ATTENDANCE_EXPECTED_LOGOUT;
}

/**
 * Minutes late, 0 if not late/no login time - the delay duration shown
 * on the Late Arrival Request prompt/form.
 *
 * @param  string|null $login_time datetime string, or null
 * @return int
 */
function staff_attendance_late_minutes($login_time)
{
    if (!staff_attendance_is_late_arrival($login_time)) {
        return 0;
    }

    $expected = date('Y-m-d', strtotime($login_time)) . ' ' . STAFF_ATTENDANCE_EXPECTED_LOGIN;

    return (int) round((strtotime($login_time) - strtotime($expected)) / 60);
}

/**
 * Minutes early, 0 if not early/no logout time - the early-exit duration
 * shown on the Early Exit Request prompt/form.
 *
 * @param  string|null $logout_time datetime string, or null
 * @return int
 */
function staff_attendance_early_minutes($logout_time)
{
    if (!staff_attendance_is_early_exit($logout_time)) {
        return 0;
    }

    $expected = date('Y-m-d', strtotime($logout_time)) . ' ' . STAFF_ATTENDANCE_EXPECTED_LOGOUT;

    return (int) round((strtotime($expected) - strtotime($logout_time)) / 60);
}

/**
 * Single reusable source for a request-workflow status badge color
 * (Leave/Late Arrival/Early Exit requests all share the same 4-status
 * vocabulary superset: Pending/Approved/Rejected/Cancelled).
 *
 * @param string $status
 * @return string hex color
 */
function staff_attendance_request_status_color($status)
{
    $colors = [
        'Pending'   => '#eab308',
        'Approved'  => '#22c55e',
        'Rejected'  => '#dc2626',
        'Cancelled' => '#64748b',
    ];

    return $colors[$status] ?? '#64748b';
}

/**
 * The 7 allowed Leave Type values - single source of truth for the
 * Request Leave form dropdown and the Leave History table.
 *
 * @return array
 */
function get_leave_types()
{
    return [
        'Casual Leave',
        'Sick Leave',
        'Earned Leave',
        'Half Day',
        'Work From Home',
        'Permission',
        'Other',
    ];
}

/**
 * Employee Dashboard widgets (spec #9: Present Today/Leave Requests/
 * Pending Requests/Approved Requests/Upcoming Holidays), each deep-linking
 * to its own tab on the Attendance page (manage.php's own #tab_xxx
 * hash-activation script). Built on the shared staff_dashboard_kpi_card()
 * design system (application/helpers/staff_dashboard_helper.php) - the
 * exact same cards every portal dashboard already uses, so this reuses
 * that rendering system rather than inventing new widget markup.
 *
 * @param  int $staff_id
 * @return void
 */
function staff_attendance_dashboard_widgets($staff_id)
{
    $CI = &get_instance();
    $CI->load->model('staff_attendance/staff_attendance_model');
    $CI->lang->load('staff_attendance/staff_attendance', 'english');

    $today_record      = $CI->staff_attendance_model->get_staff_date_record($staff_id);
    $summary            = $CI->staff_attendance_model->get_staff_request_summary($staff_id);
    $upcoming_holidays  = count($CI->staff_attendance_model->get_upcoming_holidays());
    $present_today       = $today_record && $today_record->attendance_status === 'Present';

    $attendance_url = admin_url('staff_attendance');

    staff_dashboard_kpi_grid_open();
    staff_dashboard_kpi_card(_l('staff_attendance_widget_present_today'), $present_today ? _l('staff_attendance_widget_yes') : _l('staff_attendance_widget_no'), 'fa-solid fa-user-check', 'attendance', '', null, $attendance_url . '#tab_attendance_history');
    staff_dashboard_kpi_card(_l('staff_attendance_widget_leave_requests'), $summary['leave_requests'], 'fa-solid fa-calendar-days', 'work_update', '', null, $attendance_url . '#tab_leave_requests');
    staff_dashboard_kpi_card(_l('staff_attendance_widget_pending_requests'), $summary['pending_requests'], 'fa-solid fa-hourglass-half', 'pending', '', null, $attendance_url . '#tab_leave_requests');
    staff_dashboard_kpi_card(_l('staff_attendance_widget_approved_requests'), $summary['approved_requests'], 'fa-solid fa-circle-check', 'completed', '', null, $attendance_url . '#tab_leave_requests');
    staff_dashboard_kpi_card(_l('staff_attendance_widget_upcoming_holidays'), $upcoming_holidays, 'fa-solid fa-umbrella-beach', 'deadlines', '', null, $attendance_url . '#tab_holiday_calendar');
    staff_dashboard_kpi_grid_close();
}

/**
 * Every active Super Admin staff id - used by the Admin <-> Operations
 * Manager cross-notification (Smart Attendance v2 Part 2): whichever
 * side did NOT make a leave/late/early decision gets notified of it.
 * There is no fixed "the" admin the way get_operations_manager_staff_id()
 * has a single Operations Manager - this notifies every admin account.
 *
 * @return array of staff ids
 */
function attendance_get_admin_staff_ids()
{
    $CI = &get_instance();
    $CI->db->select('staffid');
    $CI->db->where('admin', 1);
    $CI->db->where('active', 1);
    $rows = $CI->db->get(db_prefix() . 'staff')->result_array();

    return array_column($rows, 'staffid');
}
