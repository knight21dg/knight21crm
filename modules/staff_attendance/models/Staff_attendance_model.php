<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Staff_attendance_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param mixed $id
     * @return object|null
     */
    public function get($id)
    {
        $this->db->where('id', $id);

        return $this->db->get(db_prefix() . 'staff_attendance')->row();
    }

    /**
     * @param mixed  $staff_id
     * @param string $date Y-m-d, defaults to today
     * @return object|null
     */
    public function get_staff_date_record($staff_id, $date = null)
    {
        $date = $date ?: date('Y-m-d');

        $this->db->where('staff_id', $staff_id);
        $this->db->where('attendance_date', $date);

        return $this->db->get(db_prefix() . 'staff_attendance')->row();
    }

    /**
     * Login hook target - reused verbatim by every successful-login path
     * (normal, 2FA email, 2FA app all fire the same core `after_staff_login`
     * action, see staff_attendance.php).
     *
     * Smart Attendance v2: unlimited sessions per day. tblstaff_attendance
     * stays exactly one row per staff per day (UNIQUE KEY from migration
     * 362, untouched) and now means "day summary" - login_time = first
     * login of the day, logout_time = last logout, working_minutes = sum
     * of completed sessions, total_sessions = session count. Each actual
     * login/logout pair is its own row in tblstaff_attendance_sessions
     * (migration 380). A repeat login while a session is still open (no
     * logout_time yet) is a duplicate tab/session-restore, not a new
     * session, and is left alone - same dedupe as before. Once the open
     * session is closed by record_logout(), any further login that day
     * opens session_no+1, and the day-row's own login_time (first login)
     * is deliberately left untouched here - only synced/day-level fields
     * (total_sessions, updated_at) change, since "first login" must never
     * move once set.
     *
     * @param mixed $staff_id
     * @return void
     */
    public function record_login($staff_id)
    {
        $today = date('Y-m-d');
        $now   = date('Y-m-d H:i:s');

        $open_session = $this->get_open_session($staff_id, $today);
        if ($open_session) {
            // Still an open session (duplicate tab/session-restore) - leave it alone.
            return;
        }

        $record      = $this->get_staff_date_record($staff_id, $today);
        $next_number = $this->next_session_number($staff_id, $today);

        if (!$record) {
            $this->db->insert(db_prefix() . 'staff_attendance', [
                'staff_id'          => $staff_id,
                'attendance_date'   => $today,
                'login_time'        => $now,
                'attendance_status' => 'Present',
                'total_sessions'    => 0,
                'created_at'        => $now,
            ]);
            $record = $this->get_staff_date_record($staff_id, $today);
        }

        $this->db->insert(db_prefix() . 'staff_attendance_sessions', [
            'attendance_id'   => $record->id,
            'staff_id'        => $staff_id,
            'attendance_date' => $today,
            'session_no'      => $next_number,
            'login_time'      => $now,
            'created_at'      => $now,
        ]);

        // Every login marks the day Present, same as before session support
        // existed - a genuine login always overrides whatever status
        // (including a stale automatic Absent-on-display) the day would
        // otherwise show.
        $this->db->where('id', $record->id);
        $this->db->update(db_prefix() . 'staff_attendance', ['attendance_status' => 'Present']);

        $this->sync_day_summary_from_sessions($staff_id, $today);
    }

    /**
     * Logout hook target - closes today's OPEN session only (per Smart
     * Attendance rule: "Never overwrite previous logout. Current active
     * login continues until logout."). If there's no open session (e.g. a
     * session that predates this module and has no session row, or a
     * logout with no matching login), this is a safe no-op rather than
     * fabricating a session with no login_time.
     *
     * working_minutes is computed from that session's own login_time, so
     * it always reflects this specific login->logout pair, not the whole
     * day's span.
     *
     * @param mixed $staff_id
     * @return void
     */
    public function record_logout($staff_id)
    {
        $today        = date('Y-m-d');
        $open_session = $this->get_open_session($staff_id, $today);

        if (!$open_session) {
            return;
        }

        $logout_time     = date('Y-m-d H:i:s');
        $working_minutes = max(0, (int) round((strtotime($logout_time) - strtotime($open_session->login_time)) / 60));

        $this->db->where('id', $open_session->id);
        $this->db->update(db_prefix() . 'staff_attendance_sessions', [
            'logout_time'     => $logout_time,
            'working_minutes' => $working_minutes,
            'updated_at'      => $logout_time,
        ]);

        $this->sync_day_summary_from_sessions($staff_id, $today);
    }

    /**
     * The currently-open session for a staff/date, if any (logout_time
     * IS NULL) - "Current active login continues until logout" means
     * there can only ever be one of these per staff per day.
     *
     * @param  mixed  $staff_id
     * @param  string $date Y-m-d
     * @return object|null
     */
    public function get_open_session($staff_id, $date)
    {
        $this->db->where('staff_id', $staff_id);
        $this->db->where('attendance_date', $date);
        $this->db->where('logout_time IS NULL');
        $this->db->order_by('session_no', 'desc');
        $this->db->limit(1);

        return $this->db->get(db_prefix() . 'staff_attendance_sessions')->row();
    }

    /**
     * @param  mixed  $staff_id
     * @param  string $date Y-m-d
     * @return int next session_no to use (1 if none exist yet)
     */
    protected function next_session_number($staff_id, $date)
    {
        $this->db->select_max('session_no');
        $this->db->where('staff_id', $staff_id);
        $this->db->where('attendance_date', $date);
        $row = $this->db->get(db_prefix() . 'staff_attendance_sessions')->row();

        return $row && $row->session_no !== null ? ((int) $row->session_no) + 1 : 1;
    }

    /**
     * Every session for one staff/date, in order - the "Attendance
     * Sessions" breakdown (Session 1, Session 2, ...).
     *
     * @param  mixed  $staff_id
     * @param  string $date Y-m-d
     * @return array
     */
    public function get_sessions_for_date($staff_id, $date)
    {
        $this->db->where('staff_id', $staff_id);
        $this->db->where('attendance_date', $date);
        $this->db->order_by('session_no', 'asc');

        return $this->db->get(db_prefix() . 'staff_attendance_sessions')->result();
    }

    /**
     * Recomputes tblstaff_attendance's summary columns from its sessions -
     * called after every session insert/close so the day-row (and every
     * existing consumer that reads it: dashboards, Manager Portal,
     * late/early detection, month summaries) always reflects current
     * session data without any of those callers needing to know sessions
     * exist at all.
     *
     * Total Working Hours = sum of COMPLETED sessions only (per spec) -
     * an open session contributes nothing to working_minutes until it's
     * closed. Last Logout = the most recent completed session's logout;
     * while a session is open, Last Logout deliberately keeps showing the
     * previous completed session's time (or stays null before any session
     * has closed) rather than blanking - "Last Logout" describes the last
     * completed session, not "no logout yet today".
     *
     * @param  mixed  $staff_id
     * @param  string $date Y-m-d
     * @return void
     */
    protected function sync_day_summary_from_sessions($staff_id, $date)
    {
        $sessions = $this->get_sessions_for_date($staff_id, $date);

        if (empty($sessions)) {
            return;
        }

        $first_login     = $sessions[0]->login_time;
        $last_logout      = null;
        $total_minutes    = 0;

        foreach ($sessions as $session) {
            if ($session->logout_time !== null) {
                $last_logout    = $last_logout === null || $session->logout_time > $last_logout ? $session->logout_time : $last_logout;
                $total_minutes += (int) $session->working_minutes;
            }
        }

        $this->db->where('staff_id', $staff_id);
        $this->db->where('attendance_date', $date);
        $this->db->update(db_prefix() . 'staff_attendance', [
            'login_time'      => $first_login,
            'logout_time'     => $last_logout,
            'working_minutes' => $total_minutes,
            'total_sessions'  => count($sessions),
            'updated_at'      => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Admin manual status change (list inline dropdown or edit form).
     * Deliberately touches ONLY attendance_status - "Changing the status
     * should NOT remove login/logout information."
     *
     * @param mixed  $id
     * @param string $status one of get_attendance_statuses()
     * @return bool
     */
    public function update_status($id, $status)
    {
        if (!in_array($status, get_attendance_statuses(), true)) {
            return false;
        }

        $before = $this->get($id);

        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'staff_attendance', [
            'attendance_status' => $status,
            'updated_at'        => date('Y-m-d H:i:s'),
        ]);

        $updated = $this->db->affected_rows() > 0;

        if ($updated) {
            log_activity('Staff Attendance Status Updated [ID: ' . $id . ', Status: ' . $status . ']');
            $this->log_attendance_audit('attendance_status_updated', 'attendance', $id, $before->staff_id ?? null, $before->attendance_status ?? null, $status);
        }

        return $updated;
    }

    /**
     * Admin remarks edit - touches ONLY remarks, same reasoning as
     * update_status() above.
     *
     * @param mixed  $id
     * @param string $remarks
     * @return bool
     */
    public function update_remarks($id, $remarks)
    {
        $before = $this->get($id);

        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'staff_attendance', [
            'remarks'    => $remarks,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $updated = $this->db->affected_rows() > 0;

        if ($updated) {
            $this->log_attendance_audit('attendance_remarks_updated', 'attendance', $id, $before->staff_id ?? null, $before->remarks ?? null, $remarks);
        }

        return $updated;
    }

    /**
     * Admin-only manual record creation, for a (staff, date) that has no
     * login-created row yet - the only way to mark someone Absent/Leave/
     * Weekend on a day they never logged in at all, since every other
     * record only ever originates from record_login() above. Blocked if
     * a record already exists for that staff/date (same UNIQUE KEY the
     * login path relies on) - "no duplicate attendance records" applies
     * here too, surfaced as a normal validation failure rather than a DB
     * error.
     *
     * @param array $data ['staff_id', 'attendance_date', 'attendance_status', 'remarks']
     * @return int|false insert id, or false if a record already exists
     */
    public function add_manual($data)
    {
        if ($this->get_staff_date_record($data['staff_id'], $data['attendance_date'])) {
            return false;
        }

        $status = in_array($data['attendance_status'], get_attendance_statuses(), true) ? $data['attendance_status'] : 'Present';

        $this->db->insert(db_prefix() . 'staff_attendance', [
            'staff_id'          => $data['staff_id'],
            'attendance_date'   => $data['attendance_date'],
            // A manually-added record (e.g. backfilling a known Leave/
            // Weekend day) has no real login event - midday placeholder
            // keeps the NOT NULL column satisfied without implying a
            // real punch-in time; Working Hours simply stays "-" (no
            // logout_time/working_minutes) until/unless an admin edits it.
            'login_time'        => $data['attendance_date'] . ' 00:00:00',
            'attendance_status' => $status,
            'remarks'           => $data['remarks'] ?? null,
            'created_at'        => date('Y-m-d H:i:s'),
        ]);

        $insert_id = $this->db->insert_id();

        if ($insert_id) {
            log_activity('Staff Attendance Manually Added [ID: ' . $insert_id . ', Staff: ' . $data['staff_id'] . ', Date: ' . $data['attendance_date'] . ']');
            $this->log_attendance_audit('attendance_manually_added', 'attendance', $insert_id, $data['staff_id'], null, $status . ' (' . $data['attendance_date'] . ')');
        }

        return $insert_id;
    }

    /**
     * Today's status counts for the Admin Dashboard summary widget -
     * staff-driven (every active employee), LEFT JOIN'd to today's
     * attendance row if one exists, so an employee who never logged in
     * today is counted as Absent here too - consistent with the Attendance
     * list's own admin_table_single_date() and never writing a database
     * row just to make the count right.
     *
     * @return array status => count
     */
    public function get_today_summary()
    {
        $this->db->select(db_prefix() . 'staff.staffid, ' . db_prefix() . 'staff_attendance.attendance_status');
        $this->db->from(db_prefix() . 'staff');
        $this->db->join(db_prefix() . 'staff_attendance', db_prefix() . 'staff_attendance.staff_id = ' . db_prefix() . 'staff.staffid AND ' . db_prefix() . 'staff_attendance.attendance_date = "' . date('Y-m-d') . '"', 'left');
        $this->db->where(db_prefix() . 'staff.active', 1);
        $rows = $this->db->get()->result_array();

        $summary = array_fill_keys(get_attendance_statuses(), 0);
        foreach ($rows as $row) {
            $status = $row['attendance_status'] ?: 'Absent';
            if (isset($summary[$status])) {
                $summary[$status]++;
            }
        }

        return $summary;
    }

    /**
     * Average completed working minutes across everyone Present today -
     * the Admin Dashboard widget's "Average Working Hours" figure.
     * Sessions still open (no logout yet) don't contribute a
     * working_minutes value, which is correct here for the same reason
     * sync_day_summary_from_sessions() only sums completed sessions.
     *
     * @return int
     */
    public function get_today_avg_working_minutes()
    {
        $this->db->select_avg('working_minutes');
        $this->db->where('attendance_date', date('Y-m-d'));
        $this->db->where('working_minutes IS NOT NULL');
        $row = $this->db->get(db_prefix() . 'staff_attendance')->row();

        return $row && $row->working_minutes !== null ? (int) round($row->working_minutes) : 0;
    }

    /**
     * Every real tblstaff_attendance row for one staff member within one
     * calendar month, keyed by date - the Monthly Attendance Dashboard's
     * single data-fetch (one query, not one per day). Confirmed live:
     * most calendar days will have no row at all (only real logins/
     * manual admin entries create a row) - the caller (the view) decides
     * how to render a day with no entry here (Sunday vs. a genuinely
     * unmarked working day), this method just returns what's real.
     *
     * @param  int $staff_id
     * @param  int $year
     * @param  int $month
     * @return array ['Y-m-d' => row array, ...]
     */
    public function get_month_calendar_data($staff_id, $year, $month)
    {
        $start = sprintf('%04d-%02d-01', $year, $month);
        $end   = date('Y-m-t', strtotime($start));

        $this->db->where('staff_id', $staff_id);
        $this->db->where('attendance_date >=', $start);
        $this->db->where('attendance_date <=', $end);
        $rows = $this->db->get(db_prefix() . 'staff_attendance')->result_array();

        $map = [];
        foreach ($rows as $row) {
            $map[$row['attendance_date']] = $row;
        }

        return $map;
    }

    /**
     * Monthly Attendance Dashboard summary - status counts, Working
     * Days/Attendance Rate, and Working Hours stats (total/average/
     * highest/lowest), all derived from the same get_month_calendar_data()
     * fetch above rather than separate queries per card.
     *
     * Automatic Absent calculation (explicit requirement, reversing an
     * earlier "leave it blank" choice for this same dashboard): every
     * working day (non-Sunday, staff_attendance_is_weekend_day()) up to
     * and including today with no real tblstaff_attendance row is
     * counted as Absent here - purely a computed display/summary value,
     * never written to the database. Future working days (after today)
     * are excluded from this - attendance can't have happened yet, so
     * they're neither Present nor Absent, just not counted either way.
     * "Weekend days" is still the computed count of Sundays in the
     * month, unrelated to this calculation.
     *
     * @param  int $staff_id
     * @param  int $year
     * @param  int $month
     * @return array
     */
    public function get_month_summary($staff_id, $year, $month)
    {
        $calendar = $this->get_month_calendar_data($staff_id, $year, $month);

        $counts                 = array_fill_keys(get_attendance_statuses(), 0);
        $working_minutes_values = [];
        $days_in_month          = (int) date('t', mktime(0, 0, 0, $month, 1, $year));
        $today                  = date('Y-m-d');

        for ($day = 1; $day <= $days_in_month; $day++) {
            $date = sprintf('%04d-%02d-%02d', $year, $month, $day);

            if (staff_attendance_is_weekend_day($date)) {
                continue;
            }

            $row = $calendar[$date] ?? null;

            if ($row) {
                if (isset($counts[$row['attendance_status']])) {
                    $counts[$row['attendance_status']]++;
                }

                if ($row['working_minutes'] !== null && $row['working_minutes'] !== '') {
                    $working_minutes_values[] = (int) $row['working_minutes'];
                }
            } elseif ($date <= $today) {
                // Working day, no record, not in the future - automatic Absent.
                $counts['Absent']++;
            }
        }

        $working_days = staff_attendance_month_working_days($year, $month);

        $present         = $counts['Present'];
        $attendance_rate = $working_days > 0 ? (int) round(($present / $working_days) * 100) : 0;

        $has_hours = count($working_minutes_values) > 0;

        return [
            'counts'          => $counts,
            'weekend_days'    => $days_in_month - $working_days,
            'working_days'    => $working_days,
            'attendance_rate' => $attendance_rate,
            'working_hours'   => [
                'total'   => $has_hours ? array_sum($working_minutes_values) : 0,
                'average' => $has_hours ? (int) round(array_sum($working_minutes_values) / count($working_minutes_values)) : 0,
                'highest' => $has_hours ? max($working_minutes_values) : 0,
                'lowest'  => $has_hours ? min($working_minutes_values) : 0,
            ],
        ];
    }

    /* =====================================================================
     * Leave Requests - Attendance Module Enhancement. Reviewed exclusively
     * by the single company-wide Operations Manager (is_manager_portal_
     * operations_manager(), modules/manager_portal/) - no department-
     * manager concept here, matching this feature's explicit requirement.
     * ===================================================================== */

    /**
     * Inclusive day count between two Y-m-d dates - the "Number of Days
     * (Auto Calculate)" field, server-side (mirrors the client-side JS
     * calc on the Request Leave form so a tampered/omitted POST value can
     * never be trusted as-is).
     *
     * @param  string $start_date Y-m-d
     * @param  string $end_date   Y-m-d
     * @return float
     */
    public function calculate_leave_days($start_date, $end_date)
    {
        $start = strtotime($start_date);
        $end   = strtotime($end_date);

        if (!$start || !$end || $end < $start) {
            return 0;
        }

        return (float) (floor(($end - $start) / 86400) + 1);
    }

    /**
     * @param  array $data staff_id, leave_type, start_date, end_date, reason, attachment(optional)
     * @return int|false insert id
     */
    public function add_leave_request($data)
    {
        if (!in_array($data['leave_type'], get_leave_types(), true)) {
            return false;
        }

        $days = $this->calculate_leave_days($data['start_date'], $data['end_date']);

        if ($days <= 0) {
            return false;
        }

        $this->db->insert(db_prefix() . 'staff_leave_requests', [
            'staff_id'   => $data['staff_id'],
            'leave_type' => $data['leave_type'],
            'start_date' => $data['start_date'],
            'end_date'   => $data['end_date'],
            'days'       => $days,
            'reason'     => $data['reason'],
            'attachment' => $data['attachment'] ?? null,
            'status'     => 'Pending',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $insert_id = $this->db->insert_id();

        if ($insert_id) {
            log_activity('Leave Request Submitted [ID: ' . $insert_id . ', Staff: ' . $data['staff_id'] . ']');
        }

        return $insert_id;
    }

    /**
     * @param  mixed $id
     * @return object|null
     */
    public function get_leave_request($id)
    {
        $this->db->select(db_prefix() . 'staff_leave_requests.*, staff.firstname, staff.lastname, reviewer.firstname as reviewer_firstname, reviewer.lastname as reviewer_lastname');
        $this->db->from(db_prefix() . 'staff_leave_requests');
        $this->db->join(db_prefix() . 'staff staff', 'staff.staffid = ' . db_prefix() . 'staff_leave_requests.staff_id', 'left');
        $this->db->join(db_prefix() . 'staff reviewer', 'reviewer.staffid = ' . db_prefix() . 'staff_leave_requests.reviewed_by', 'left');
        $this->db->where(db_prefix() . 'staff_leave_requests.id', $id);

        return $this->db->get()->row();
    }

    /**
     * Employee self-service cancel - "Can cancel only while Pending",
     * enforced here (not just hidden in the UI) via the status/ownership
     * WHERE clause, so affected_rows() is 0 for any attempt outside that
     * window regardless of what the client sends.
     *
     * @param  mixed $id
     * @param  int   $staff_id owner, self-scoped
     * @return bool
     */
    public function cancel_leave_request($id, $staff_id)
    {
        $this->db->where('id', $id);
        $this->db->where('staff_id', $staff_id);
        $this->db->where('status', 'Pending');
        $this->db->update(db_prefix() . 'staff_leave_requests', ['status' => 'Cancelled']);

        return $this->db->affected_rows() > 0;
    }

    /**
     * Approve/Reject - Operations Manager only (gated by the caller, see
     * Manager_portal.php). Only ever acts on a currently-Pending request,
     * same "can't re-decide an already-decided request" guard as
     * cancel_leave_request() above.
     *
     * @param  mixed  $id
     * @param  string $decision   'Approved'|'Rejected'
     * @param  int    $reviewer_id
     * @param  string $remarks
     * @return object|false the updated request row (for notification), or false
     */
    public function review_leave_request($id, $decision, $reviewer_id, $remarks)
    {
        if (!in_array($decision, ['Approved', 'Rejected'], true)) {
            return false;
        }

        $before = $this->get_leave_request($id);

        $this->db->where('id', $id);
        $this->db->where('status', 'Pending');
        $this->db->update(db_prefix() . 'staff_leave_requests', [
            'status'          => $decision,
            'manager_remarks' => $remarks,
            'reviewed_by'     => $reviewer_id,
            'reviewed_at'     => date('Y-m-d H:i:s'),
        ]);

        if ($this->db->affected_rows() === 0) {
            return false;
        }

        $after = $this->get_leave_request($id);
        $this->log_attendance_audit('leave_request_reviewed', 'leave_request', $id, $before->staff_id, $before->status, $decision, $reviewer_id);

        return $after;
    }

    /**
     * Admin-only equivalent of review_leave_request() above, with one
     * difference: no "Pending only" guard, so Admin can re-decide a
     * request the Operations Manager (or a previous Admin action) already
     * Approved/Rejected - "Admin can Override Operations Manager
     * decision" (explicit requirement). Every call is audit-logged with
     * the request's status/remarks immediately before this change, so
     * the override itself, not just the end state, is always visible in
     * the Audit Log.
     *
     * @param  mixed  $id
     * @param  string $decision 'Approved'|'Rejected'
     * @param  int    $admin_id
     * @param  string $remarks
     * @return object|false the updated request row (for notification), or false
     */
    public function admin_review_leave_request($id, $decision, $admin_id, $remarks)
    {
        if (!in_array($decision, ['Approved', 'Rejected'], true)) {
            return false;
        }

        $before = $this->get_leave_request($id);

        if (!$before) {
            return false;
        }

        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'staff_leave_requests', [
            'status'          => $decision,
            'manager_remarks' => $remarks,
            'reviewed_by'     => $admin_id,
            'reviewed_at'     => date('Y-m-d H:i:s'),
        ]);

        $after = $this->get_leave_request($id);

        $this->log_attendance_audit(
            $before->status === 'Pending' ? 'leave_request_admin_reviewed' : 'leave_request_admin_overridden',
            'leave_request',
            $id,
            $before->staff_id,
            $before->status . ($before->manager_remarks ? ' (' . $before->manager_remarks . ')' : ''),
            $decision . ($remarks ? ' (' . $remarks . ')' : ''),
            $admin_id
        );

        return $after;
    }

    /**
     * Company-wide count of currently-Approved leave requests that cover
     * today - the "Employees On Leave Today" Operations Manager widget.
     *
     * @return int
     */
    public function get_employees_on_leave_today_count()
    {
        $today = date('Y-m-d');

        $this->db->where('status', 'Approved');
        $this->db->where('start_date <=', $today);
        $this->db->where('end_date >=', $today);

        return $this->db->count_all_results(db_prefix() . 'staff_leave_requests');
    }

    /**
     * @return int
     */
    public function get_pending_leave_requests_count()
    {
        $this->db->where('status', 'Pending');

        return $this->db->count_all_results(db_prefix() . 'staff_leave_requests');
    }

    /* =====================================================================
     * Holidays - read-only for staff, Admin-maintained.
     * ===================================================================== */

    /**
     * @param  array $filters year(optional)
     * @return array
     */
    public function get_holidays($filters = [])
    {
        if (!empty($filters['year'])) {
            $this->db->where('YEAR(date)', (int) $filters['year']);
        }

        $this->db->order_by('date', 'asc');

        return $this->db->get(db_prefix() . 'holidays')->result_array();
    }

    /**
     * @param  int $limit
     * @return array
     */
    public function get_upcoming_holidays($limit = 5)
    {
        $this->db->where('date >=', date('Y-m-d'));
        $this->db->order_by('date', 'asc');
        $this->db->limit($limit);

        return $this->db->get(db_prefix() . 'holidays')->result_array();
    }

    /**
     * @param  mixed $id
     * @return object|null
     */
    public function get_holiday($id)
    {
        $this->db->where('id', $id);

        return $this->db->get(db_prefix() . 'holidays')->row();
    }

    /**
     * @param  array $data name, date, description(optional), created_by
     * @return int insert id
     */
    public function add_holiday($data)
    {
        $this->db->insert(db_prefix() . 'holidays', [
            'name'        => $data['name'],
            'date'        => $data['date'],
            'description' => $data['description'] ?? null,
            'created_by'  => $data['created_by'],
            'created_at'  => date('Y-m-d H:i:s'),
        ]);

        $insert_id = $this->db->insert_id();

        if ($insert_id) {
            $this->log_attendance_audit('holiday_added', 'holiday', $insert_id, null, null, $data['name'] . ' (' . $data['date'] . ')', $data['created_by']);
        }

        return $insert_id;
    }

    /**
     * @param  mixed $id
     * @param  array $data name, date, description(optional)
     * @return bool
     */
    public function update_holiday($id, $data)
    {
        $before = $this->get_holiday($id);

        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'holidays', [
            'name'        => $data['name'],
            'date'        => $data['date'],
            'description' => $data['description'] ?? null,
        ]);

        $updated = $this->db->affected_rows() > 0;

        if ($updated && $before) {
            $this->log_attendance_audit('holiday_updated', 'holiday', $id, null, $before->name . ' (' . $before->date . ')', $data['name'] . ' (' . $data['date'] . ')');
        }

        return $updated;
    }

    /**
     * @param  mixed $id
     * @return bool
     */
    public function delete_holiday($id)
    {
        $before = $this->get_holiday($id);

        if ($before) {
            $this->log_attendance_audit('holiday_deleted', 'holiday', $id, null, $before->name . ' (' . $before->date . ')', null);
        }

        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'holidays');

        return $this->db->affected_rows() > 0;
    }

    /* =====================================================================
     * Late Arrival / Early Exit Requests - one request per (staff,
     * attendance_date), enforced by each table's own UNIQUE KEY. Reuses
     * staff_attendance_is_late_arrival()/_is_early_exit()/_late_minutes()/
     * _early_minutes() (staff_attendance_helper.php) for detection - the
     * exact same tblstaff_attendance login_time/logout_time columns this
     * module already writes, never a second/duplicated attendance
     * calculation.
     * ===================================================================== */

    /**
     * @param  array $data staff_id, attendance_date, login_time, reason, attachment(optional)
     * @return int|false insert id, or false if a request already exists for that date
     */
    public function add_late_arrival_request($data)
    {
        if ($this->get_late_arrival_request_for_date($data['staff_id'], $data['attendance_date'])) {
            return false;
        }

        $this->db->insert(db_prefix() . 'staff_late_arrival_requests', [
            'staff_id'        => $data['staff_id'],
            'attendance_date' => $data['attendance_date'],
            'login_time'      => $data['login_time'],
            'reason'          => $data['reason'],
            'attachment'      => $data['attachment'] ?? null,
            'status'          => 'Pending',
            'created_at'      => date('Y-m-d H:i:s'),
        ]);

        $insert_id = $this->db->insert_id();

        if ($insert_id) {
            log_activity('Late Arrival Request Submitted [ID: ' . $insert_id . ', Staff: ' . $data['staff_id'] . ']');
        }

        return $insert_id;
    }

    /**
     * @param  int    $staff_id
     * @param  string $date Y-m-d
     * @return object|null
     */
    public function get_late_arrival_request_for_date($staff_id, $date)
    {
        $this->db->where('staff_id', $staff_id);
        $this->db->where('attendance_date', $date);

        return $this->db->get(db_prefix() . 'staff_late_arrival_requests')->row();
    }

    /**
     * @param  mixed $id
     * @return object|null
     */
    public function get_late_arrival_request($id)
    {
        $this->db->select(db_prefix() . 'staff_late_arrival_requests.*, staff.firstname, staff.lastname, reviewer.firstname as reviewer_firstname, reviewer.lastname as reviewer_lastname');
        $this->db->from(db_prefix() . 'staff_late_arrival_requests');
        $this->db->join(db_prefix() . 'staff staff', 'staff.staffid = ' . db_prefix() . 'staff_late_arrival_requests.staff_id', 'left');
        $this->db->join(db_prefix() . 'staff reviewer', 'reviewer.staffid = ' . db_prefix() . 'staff_late_arrival_requests.reviewed_by', 'left');
        $this->db->where(db_prefix() . 'staff_late_arrival_requests.id', $id);

        return $this->db->get()->row();
    }

    /**
     * @param  mixed  $id
     * @param  string $decision 'Approved'|'Rejected'
     * @param  int    $reviewer_id
     * @param  string $remarks
     * @return object|false
     */
    public function review_late_arrival_request($id, $decision, $reviewer_id, $remarks)
    {
        if (!in_array($decision, ['Approved', 'Rejected'], true)) {
            return false;
        }

        $before = $this->get_late_arrival_request($id);

        $this->db->where('id', $id);
        $this->db->where('status', 'Pending');
        $this->db->update(db_prefix() . 'staff_late_arrival_requests', [
            'status'          => $decision,
            'manager_remarks' => $remarks,
            'reviewed_by'     => $reviewer_id,
            'reviewed_at'     => date('Y-m-d H:i:s'),
        ]);

        if ($this->db->affected_rows() === 0) {
            return false;
        }

        $after = $this->get_late_arrival_request($id);
        $this->log_attendance_audit('late_arrival_request_reviewed', 'late_arrival_request', $id, $before->staff_id, $before->status, $decision, $reviewer_id);

        return $after;
    }

    /**
     * Admin-only override equivalent - see admin_review_leave_request()
     * for the full reasoning (same shape, same audit behavior).
     *
     * @param  mixed  $id
     * @param  string $decision 'Approved'|'Rejected'
     * @param  int    $admin_id
     * @param  string $remarks
     * @return object|false
     */
    public function admin_review_late_arrival_request($id, $decision, $admin_id, $remarks)
    {
        if (!in_array($decision, ['Approved', 'Rejected'], true)) {
            return false;
        }

        $before = $this->get_late_arrival_request($id);

        if (!$before) {
            return false;
        }

        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'staff_late_arrival_requests', [
            'status'          => $decision,
            'manager_remarks' => $remarks,
            'reviewed_by'     => $admin_id,
            'reviewed_at'     => date('Y-m-d H:i:s'),
        ]);

        $after = $this->get_late_arrival_request($id);

        $this->log_attendance_audit(
            $before->status === 'Pending' ? 'late_arrival_request_admin_reviewed' : 'late_arrival_request_admin_overridden',
            'late_arrival_request',
            $id,
            $before->staff_id,
            $before->status . ($before->manager_remarks ? ' (' . $before->manager_remarks . ')' : ''),
            $decision . ($remarks ? ' (' . $remarks . ')' : ''),
            $admin_id
        );

        return $after;
    }

    /**
     * @return int
     */
    public function get_pending_late_arrival_requests_count()
    {
        $this->db->where('status', 'Pending');

        return $this->db->count_all_results(db_prefix() . 'staff_late_arrival_requests');
    }

    /**
     * Self-scoped history for the Late/Early Requests tab - small,
     * unpaginated list (unlike Leave History, this isn't a DataTable per
     * the spec), newest first.
     *
     * @param  int $staff_id
     * @return array
     */
    public function get_staff_late_arrival_requests($staff_id)
    {
        $this->db->where('staff_id', $staff_id);
        $this->db->order_by('attendance_date', 'desc');

        return $this->db->get(db_prefix() . 'staff_late_arrival_requests')->result_array();
    }

    /**
     * @param  array $data staff_id, attendance_date, logout_time, reason, attachment(optional)
     * @return int|false insert id, or false if a request already exists for that date
     */
    public function add_early_exit_request($data)
    {
        if ($this->get_early_exit_request_for_date($data['staff_id'], $data['attendance_date'])) {
            return false;
        }

        $this->db->insert(db_prefix() . 'staff_early_exit_requests', [
            'staff_id'        => $data['staff_id'],
            'attendance_date' => $data['attendance_date'],
            'logout_time'     => $data['logout_time'],
            'reason'          => $data['reason'],
            'attachment'      => $data['attachment'] ?? null,
            'status'          => 'Pending',
            'created_at'      => date('Y-m-d H:i:s'),
        ]);

        $insert_id = $this->db->insert_id();

        if ($insert_id) {
            log_activity('Early Exit Request Submitted [ID: ' . $insert_id . ', Staff: ' . $data['staff_id'] . ']');
        }

        return $insert_id;
    }

    /**
     * @param  int    $staff_id
     * @param  string $date Y-m-d
     * @return object|null
     */
    public function get_early_exit_request_for_date($staff_id, $date)
    {
        $this->db->where('staff_id', $staff_id);
        $this->db->where('attendance_date', $date);

        return $this->db->get(db_prefix() . 'staff_early_exit_requests')->row();
    }

    /**
     * @param  mixed $id
     * @return object|null
     */
    public function get_early_exit_request($id)
    {
        $this->db->select(db_prefix() . 'staff_early_exit_requests.*, staff.firstname, staff.lastname, reviewer.firstname as reviewer_firstname, reviewer.lastname as reviewer_lastname');
        $this->db->from(db_prefix() . 'staff_early_exit_requests');
        $this->db->join(db_prefix() . 'staff staff', 'staff.staffid = ' . db_prefix() . 'staff_early_exit_requests.staff_id', 'left');
        $this->db->join(db_prefix() . 'staff reviewer', 'reviewer.staffid = ' . db_prefix() . 'staff_early_exit_requests.reviewed_by', 'left');
        $this->db->where(db_prefix() . 'staff_early_exit_requests.id', $id);

        return $this->db->get()->row();
    }

    /**
     * @param  mixed  $id
     * @param  string $decision 'Approved'|'Rejected'
     * @param  int    $reviewer_id
     * @param  string $remarks
     * @return object|false
     */
    public function review_early_exit_request($id, $decision, $reviewer_id, $remarks)
    {
        if (!in_array($decision, ['Approved', 'Rejected'], true)) {
            return false;
        }

        $before = $this->get_early_exit_request($id);

        $this->db->where('id', $id);
        $this->db->where('status', 'Pending');
        $this->db->update(db_prefix() . 'staff_early_exit_requests', [
            'status'          => $decision,
            'manager_remarks' => $remarks,
            'reviewed_by'     => $reviewer_id,
            'reviewed_at'     => date('Y-m-d H:i:s'),
        ]);

        if ($this->db->affected_rows() === 0) {
            return false;
        }

        $after = $this->get_early_exit_request($id);
        $this->log_attendance_audit('early_exit_request_reviewed', 'early_exit_request', $id, $before->staff_id, $before->status, $decision, $reviewer_id);

        return $after;
    }

    /**
     * Admin-only override equivalent - see admin_review_leave_request()
     * for the full reasoning (same shape, same audit behavior).
     *
     * @param  mixed  $id
     * @param  string $decision 'Approved'|'Rejected'
     * @param  int    $admin_id
     * @param  string $remarks
     * @return object|false
     */
    public function admin_review_early_exit_request($id, $decision, $admin_id, $remarks)
    {
        if (!in_array($decision, ['Approved', 'Rejected'], true)) {
            return false;
        }

        $before = $this->get_early_exit_request($id);

        if (!$before) {
            return false;
        }

        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'staff_early_exit_requests', [
            'status'          => $decision,
            'manager_remarks' => $remarks,
            'reviewed_by'     => $admin_id,
            'reviewed_at'     => date('Y-m-d H:i:s'),
        ]);

        $after = $this->get_early_exit_request($id);

        $this->log_attendance_audit(
            $before->status === 'Pending' ? 'early_exit_request_admin_reviewed' : 'early_exit_request_admin_overridden',
            'early_exit_request',
            $id,
            $before->staff_id,
            $before->status . ($before->manager_remarks ? ' (' . $before->manager_remarks . ')' : ''),
            $decision . ($remarks ? ' (' . $remarks . ')' : ''),
            $admin_id
        );

        return $after;
    }

    /**
     * @return int
     */
    public function get_pending_early_exit_requests_count()
    {
        $this->db->where('status', 'Pending');

        return $this->db->count_all_results(db_prefix() . 'staff_early_exit_requests');
    }

    /**
     * @param  int $staff_id
     * @return array
     */
    public function get_staff_early_exit_requests($staff_id)
    {
        $this->db->where('staff_id', $staff_id);
        $this->db->order_by('attendance_date', 'desc');

        return $this->db->get(db_prefix() . 'staff_early_exit_requests')->result_array();
    }

    /**
     * Employee Dashboard widget counts - "Leave Requests" (all-time
     * submitted count), "Pending Requests" (leave + late + early
     * combined), "Approved Requests" (leave + late + early combined),
     * scoped to one staff member.
     *
     * @param  int $staff_id
     * @return array
     */
    public function get_staff_request_summary($staff_id)
    {
        $this->db->where('staff_id', $staff_id);
        $leave_total = $this->db->count_all_results(db_prefix() . 'staff_leave_requests');

        $this->db->where('staff_id', $staff_id);
        $this->db->where('status', 'Pending');
        $leave_pending = $this->db->count_all_results(db_prefix() . 'staff_leave_requests');

        $this->db->where('staff_id', $staff_id);
        $this->db->where('status', 'Pending');
        $late_pending = $this->db->count_all_results(db_prefix() . 'staff_late_arrival_requests');

        $this->db->where('staff_id', $staff_id);
        $this->db->where('status', 'Pending');
        $early_pending = $this->db->count_all_results(db_prefix() . 'staff_early_exit_requests');

        $this->db->where('staff_id', $staff_id);
        $this->db->where('status', 'Approved');
        $leave_approved = $this->db->count_all_results(db_prefix() . 'staff_leave_requests');

        $this->db->where('staff_id', $staff_id);
        $this->db->where('status', 'Approved');
        $late_approved = $this->db->count_all_results(db_prefix() . 'staff_late_arrival_requests');

        $this->db->where('staff_id', $staff_id);
        $this->db->where('status', 'Approved');
        $early_approved = $this->db->count_all_results(db_prefix() . 'staff_early_exit_requests');

        return [
            'leave_requests'    => $leave_total,
            'pending_requests'  => $leave_pending + $late_pending + $early_pending,
            'approved_requests' => $leave_approved + $late_approved + $early_approved,
        ];
    }

    /* =====================================================================
     * Operations Manager review listings - company-wide (there is only
     * ONE Operations Manager, per the feature's own requirement, so
     * these are never scoped by department/team like Manager_portal_model's
     * own attendance queries are - every request from every department
     * goes to the same reviewer). Kept here, not duplicated into
     * Manager_portal_model, since this is the canonical home for every
     * other leave/late/early read in this feature.
     * ===================================================================== */

    /**
     * @param  array $filters status(default 'Pending'), employee_id
     * @return array
     */
    public function get_leave_requests_for_review($filters = [])
    {
        $this->db->select(db_prefix() . 'staff_leave_requests.*, staff.firstname, staff.lastname, reviewer.firstname as reviewer_firstname, reviewer.lastname as reviewer_lastname');
        $this->db->from(db_prefix() . 'staff_leave_requests');
        $this->db->join(db_prefix() . 'staff staff', 'staff.staffid = ' . db_prefix() . 'staff_leave_requests.staff_id', 'left');
        $this->db->join(db_prefix() . 'staff reviewer', 'reviewer.staffid = ' . db_prefix() . 'staff_leave_requests.reviewed_by', 'left');

        if (!empty($filters['status'])) {
            $this->db->where(db_prefix() . 'staff_leave_requests.status', $filters['status']);
        }

        if (!empty($filters['employee_id'])) {
            $this->db->where(db_prefix() . 'staff_leave_requests.staff_id', $filters['employee_id']);
        }

        if (!empty($filters['staff_ids'])) {
            $this->db->where_in(db_prefix() . 'staff_leave_requests.staff_id', $filters['staff_ids']);
        }

        if (!empty($filters['date_from'])) {
            $this->db->where(db_prefix() . 'staff_leave_requests.start_date >=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $this->db->where(db_prefix() . 'staff_leave_requests.start_date <=', $filters['date_to']);
        }

        $this->db->order_by(db_prefix() . 'staff_leave_requests.created_at', 'desc');

        return $this->db->get()->result_array();
    }

    /**
     * @param  array $filters status(default 'Pending'), employee_id
     * @return array
     */
    public function get_late_arrival_requests_for_review($filters = [])
    {
        $this->db->select(db_prefix() . 'staff_late_arrival_requests.*, staff.firstname, staff.lastname, reviewer.firstname as reviewer_firstname, reviewer.lastname as reviewer_lastname');
        $this->db->from(db_prefix() . 'staff_late_arrival_requests');
        $this->db->join(db_prefix() . 'staff staff', 'staff.staffid = ' . db_prefix() . 'staff_late_arrival_requests.staff_id', 'left');
        $this->db->join(db_prefix() . 'staff reviewer', 'reviewer.staffid = ' . db_prefix() . 'staff_late_arrival_requests.reviewed_by', 'left');

        if (!empty($filters['status'])) {
            $this->db->where(db_prefix() . 'staff_late_arrival_requests.status', $filters['status']);
        }

        if (!empty($filters['employee_id'])) {
            $this->db->where(db_prefix() . 'staff_late_arrival_requests.staff_id', $filters['employee_id']);
        }

        if (!empty($filters['staff_ids'])) {
            $this->db->where_in(db_prefix() . 'staff_late_arrival_requests.staff_id', $filters['staff_ids']);
        }

        if (!empty($filters['date_from'])) {
            $this->db->where(db_prefix() . 'staff_late_arrival_requests.attendance_date >=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $this->db->where(db_prefix() . 'staff_late_arrival_requests.attendance_date <=', $filters['date_to']);
        }

        $this->db->order_by(db_prefix() . 'staff_late_arrival_requests.created_at', 'desc');

        return $this->db->get()->result_array();
    }

    /**
     * @param  array $filters status(default 'Pending'), employee_id
     * @return array
     */
    public function get_early_exit_requests_for_review($filters = [])
    {
        $this->db->select(db_prefix() . 'staff_early_exit_requests.*, staff.firstname, staff.lastname, reviewer.firstname as reviewer_firstname, reviewer.lastname as reviewer_lastname');
        $this->db->from(db_prefix() . 'staff_early_exit_requests');
        $this->db->join(db_prefix() . 'staff staff', 'staff.staffid = ' . db_prefix() . 'staff_early_exit_requests.staff_id', 'left');
        $this->db->join(db_prefix() . 'staff reviewer', 'reviewer.staffid = ' . db_prefix() . 'staff_early_exit_requests.reviewed_by', 'left');

        if (!empty($filters['status'])) {
            $this->db->where(db_prefix() . 'staff_early_exit_requests.status', $filters['status']);
        }

        if (!empty($filters['employee_id'])) {
            $this->db->where(db_prefix() . 'staff_early_exit_requests.staff_id', $filters['employee_id']);
        }

        if (!empty($filters['staff_ids'])) {
            $this->db->where_in(db_prefix() . 'staff_early_exit_requests.staff_id', $filters['staff_ids']);
        }

        if (!empty($filters['date_from'])) {
            $this->db->where(db_prefix() . 'staff_early_exit_requests.attendance_date >=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $this->db->where(db_prefix() . 'staff_early_exit_requests.attendance_date <=', $filters['date_to']);
        }

        $this->db->order_by(db_prefix() . 'staff_early_exit_requests.created_at', 'desc');

        return $this->db->get()->result_array();
    }

    /* =====================================================================
     * Audit Log - Admin Portal Attendance Module (Part 2). Every
     * administrative decision (leave/late/early review including Admin
     * overrides, holiday CRUD, manual attendance add/status/remarks
     * edits) is recorded here. Deliberately excludes routine login/logout
     * events - those are already fully reconstructable from
     * tblstaff_attendance_sessions, and logging every punch here would be
     * noise, not a decision trail.
     * ===================================================================== */

    /**
     * @param  string $action          short machine key, e.g. 'leave_request_admin_overridden'
     * @param  string $target_type     'leave_request'|'late_arrival_request'|'early_exit_request'|'attendance'|'holiday'
     * @param  mixed  $target_id
     * @param  mixed  $target_staff_id whose record this action affects, null for holidays
     * @param  mixed  $old_value
     * @param  mixed  $new_value
     * @param  mixed  $actor_staff_id  defaults to the current staff user
     * @return void
     */
    public function log_attendance_audit($action, $target_type, $target_id, $target_staff_id, $old_value, $new_value, $actor_staff_id = null)
    {
        $this->db->insert(db_prefix() . 'attendance_audit_log', [
            'action'          => $action,
            'target_type'     => $target_type,
            'target_id'       => $target_id,
            'target_staff_id' => $target_staff_id,
            'actor_staff_id'  => $actor_staff_id ?: get_staff_user_id(),
            'old_value'       => is_scalar($old_value) || $old_value === null ? $old_value : json_encode($old_value),
            'new_value'       => is_scalar($new_value) || $new_value === null ? $new_value : json_encode($new_value),
            'created_at'      => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Admin-facing Audit Log listing - "View Complete History".
     *
     * @param  array $filters target_type, target_staff_id, date_from, date_to
     * @return array
     */
    public function get_audit_log($filters = [])
    {
        $this->db->select(db_prefix() . 'attendance_audit_log.*, staff.firstname as actor_firstname, staff.lastname as actor_lastname');
        $this->db->from(db_prefix() . 'attendance_audit_log');
        $this->db->join(db_prefix() . 'staff staff', 'staff.staffid = ' . db_prefix() . 'attendance_audit_log.actor_staff_id', 'left');

        if (!empty($filters['target_type'])) {
            $this->db->where(db_prefix() . 'attendance_audit_log.target_type', $filters['target_type']);
        }

        if (!empty($filters['target_staff_id'])) {
            $this->db->where(db_prefix() . 'attendance_audit_log.target_staff_id', $filters['target_staff_id']);
        }

        if (!empty($filters['date_from'])) {
            $this->db->where(db_prefix() . 'attendance_audit_log.created_at >=', $filters['date_from'] . ' 00:00:00');
        }

        if (!empty($filters['date_to'])) {
            $this->db->where(db_prefix() . 'attendance_audit_log.created_at <=', $filters['date_to'] . ' 23:59:59');
        }

        $this->db->order_by(db_prefix() . 'attendance_audit_log.created_at', 'desc');
        $this->db->limit($filters['limit'] ?? 200);

        return $this->db->get()->result_array();
    }

    /* =====================================================================
     * Admin Portal Reports (Part 2) - department/employee-wise summaries
     * built on the same get_month_summary()/get_month_calendar_data() the
     * Monthly Attendance Dashboard already uses, just looped per staff
     * instead of for one - no new aggregation primitives, just reuse at
     * a wider scope.
     * ===================================================================== */

    /**
     * One row per active staff member for a given month - the
     * Employee-wise / Working Hours report's data source.
     *
     * @param  int        $year
     * @param  int        $month
     * @param  array|null $staff_ids optional restriction, null = every active staff
     * @return array
     */
    public function get_employee_wise_month_report($year, $month, $staff_ids = null)
    {
        $this->db->select('staffid, firstname, lastname');
        $this->db->where('active', 1);
        if ($staff_ids !== null) {
            $this->db->where_in('staffid', $staff_ids);
        }
        $staff = $this->db->get(db_prefix() . 'staff')->result();

        $report = [];
        foreach ($staff as $member) {
            $summary   = $this->get_month_summary($member->staffid, $year, $month);
            $report[] = [
                'staff_id'        => $member->staffid,
                'name'            => trim($member->firstname . ' ' . $member->lastname),
                'present'         => $summary['counts']['Present'],
                'absent'          => $summary['counts']['Absent'],
                'late'            => $summary['counts']['Late'],
                'half_day'        => $summary['counts']['Half Day'],
                'leave'           => $summary['counts']['Leave'],
                'attendance_rate' => $summary['attendance_rate'],
                'working_minutes' => $summary['working_hours']['total'],
            ];
        }

        return $report;
    }

    /**
     * Same shape as get_employee_wise_month_report(), grouped by
     * department instead of listed per employee - the Department-wise
     * report's data source. Reuses Business_departments_model for staff
     * membership rather than duplicating department lookup here.
     *
     * @param  int $year
     * @param  int $month
     * @return array
     */
    public function get_department_wise_month_report($year, $month)
    {
        $this->load->model('business_departments/business_departments_model');
        $departments = $this->business_departments_model->get('');

        $report = [];
        foreach ($departments as $department) {
            $staff_ids = $this->business_departments_model->get_department_staff_ids($department['id']);
            if (empty($staff_ids)) {
                continue;
            }

            $rows              = $this->get_employee_wise_month_report($year, $month, $staff_ids);
            $working_minutes   = array_sum(array_column($rows, 'working_minutes'));
            $attendance_rates  = array_column($rows, 'attendance_rate');

            $report[] = [
                'department_id'      => $department['id'],
                'department_name'    => $department['name'],
                'employee_count'     => count($rows),
                'present'            => array_sum(array_column($rows, 'present')),
                'absent'             => array_sum(array_column($rows, 'absent')),
                'late'               => array_sum(array_column($rows, 'late')),
                'leave'              => array_sum(array_column($rows, 'leave')),
                'avg_attendance_rate' => count($attendance_rates) ? (int) round(array_sum($attendance_rates) / count($attendance_rates)) : 0,
                'total_working_minutes' => $working_minutes,
            ];
        }

        return $report;
    }

    /**
     * Company-wide Late Report for a month - every Late-status day across
     * every staff member, with the late-by-minutes detail.
     *
     * @param  int $year
     * @param  int $month
     * @return array
     */
    public function get_late_report($year, $month)
    {
        $start = sprintf('%04d-%02d-01', $year, $month);
        $end   = date('Y-m-t', strtotime($start));

        $this->db->select(db_prefix() . 'staff_attendance.*, staff.firstname, staff.lastname');
        $this->db->from(db_prefix() . 'staff_attendance');
        $this->db->join(db_prefix() . 'staff staff', 'staff.staffid = ' . db_prefix() . 'staff_attendance.staff_id', 'left');
        $this->db->where(db_prefix() . 'staff_attendance.attendance_status', 'Late');
        $this->db->where(db_prefix() . 'staff_attendance.attendance_date >=', $start);
        $this->db->where(db_prefix() . 'staff_attendance.attendance_date <=', $end);
        $this->db->order_by(db_prefix() . 'staff_attendance.attendance_date', 'desc');

        return $this->db->get()->result_array();
    }

    /**
     * Company-wide Leave Report for a month - every leave request whose
     * range overlaps the month, regardless of current status (Admin
     * wants the full picture, not just Approved).
     *
     * @param  int $year
     * @param  int $month
     * @return array
     */
    public function get_leave_report($year, $month)
    {
        $start = sprintf('%04d-%02d-01', $year, $month);
        $end   = date('Y-m-t', strtotime($start));

        $this->db->select(db_prefix() . 'staff_leave_requests.*, staff.firstname, staff.lastname');
        $this->db->from(db_prefix() . 'staff_leave_requests');
        $this->db->join(db_prefix() . 'staff staff', 'staff.staffid = ' . db_prefix() . 'staff_leave_requests.staff_id', 'left');
        $this->db->where(db_prefix() . 'staff_leave_requests.start_date <=', $end);
        $this->db->where(db_prefix() . 'staff_leave_requests.end_date >=', $start);
        $this->db->order_by(db_prefix() . 'staff_leave_requests.start_date', 'desc');

        return $this->db->get()->result_array();
    }
}
