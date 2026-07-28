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
     * action, see staff_attendance.php). Find-or-create: if today's record
     * already exists (staff logging in a second/third time today), this is
     * a pure no-op - "Do NOT create duplicate records if the employee logs
     * in multiple times." The UNIQUE KEY(staff_id, attendance_date) from
     * migration 362 is the structural backstop if two requests ever race.
     *
     * @param mixed $staff_id
     * @return void
     */
    public function record_login($staff_id)
    {
        $today = date('Y-m-d');

        if ($this->get_staff_date_record($staff_id, $today)) {
            return;
        }

        $this->db->insert(db_prefix() . 'staff_attendance', [
            'staff_id'          => $staff_id,
            'attendance_date'   => $today,
            'login_time'        => date('Y-m-d H:i:s'),
            'attendance_status' => 'Present',
            'created_at'        => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Logout hook target - updates today's record only, never creates a
     * new row (per spec: "Do not create another row. Update today's
     * attendance record only."). If no record exists for today (e.g. a
     * session that predates this module, or spans midnight without a
     * fresh login), this is a safe no-op rather than fabricating a record
     * with no login_time.
     *
     * working_minutes is computed from the stored login_time, not from
     * whatever the session/request clock might otherwise suggest, so it
     * always reflects this specific login->logout pair.
     *
     * @param mixed $staff_id
     * @return void
     */
    public function record_logout($staff_id)
    {
        $record = $this->get_staff_date_record($staff_id, date('Y-m-d'));

        if (!$record) {
            return;
        }

        $logout_time     = date('Y-m-d H:i:s');
        $working_minutes = max(0, (int) round((strtotime($logout_time) - strtotime($record->login_time)) / 60));

        $this->db->where('id', $record->id);
        $this->db->update(db_prefix() . 'staff_attendance', [
            'logout_time'     => $logout_time,
            'working_minutes' => $working_minutes,
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

        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'staff_attendance', [
            'attendance_status' => $status,
            'updated_at'        => date('Y-m-d H:i:s'),
        ]);

        $updated = $this->db->affected_rows() > 0;

        if ($updated) {
            log_activity('Staff Attendance Status Updated [ID: ' . $id . ', Status: ' . $status . ']');
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
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'staff_attendance', [
            'remarks'    => $remarks,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->db->affected_rows() > 0;
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

        return $this->get_leave_request($id);
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

        return $this->db->insert_id();
    }

    /**
     * @param  mixed $id
     * @param  array $data name, date, description(optional)
     * @return bool
     */
    public function update_holiday($id, $data)
    {
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'holidays', [
            'name'        => $data['name'],
            'date'        => $data['date'],
            'description' => $data['description'] ?? null,
        ]);

        return $this->db->affected_rows() > 0;
    }

    /**
     * @param  mixed $id
     * @return bool
     */
    public function delete_holiday($id)
    {
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

        return $this->get_late_arrival_request($id);
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

        return $this->get_early_exit_request($id);
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
        $this->db->select(db_prefix() . 'staff_leave_requests.*, staff.firstname, staff.lastname');
        $this->db->from(db_prefix() . 'staff_leave_requests');
        $this->db->join(db_prefix() . 'staff staff', 'staff.staffid = ' . db_prefix() . 'staff_leave_requests.staff_id', 'left');

        if (!empty($filters['status'])) {
            $this->db->where(db_prefix() . 'staff_leave_requests.status', $filters['status']);
        }

        if (!empty($filters['employee_id'])) {
            $this->db->where(db_prefix() . 'staff_leave_requests.staff_id', $filters['employee_id']);
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
        $this->db->select(db_prefix() . 'staff_late_arrival_requests.*, staff.firstname, staff.lastname');
        $this->db->from(db_prefix() . 'staff_late_arrival_requests');
        $this->db->join(db_prefix() . 'staff staff', 'staff.staffid = ' . db_prefix() . 'staff_late_arrival_requests.staff_id', 'left');

        if (!empty($filters['status'])) {
            $this->db->where(db_prefix() . 'staff_late_arrival_requests.status', $filters['status']);
        }

        if (!empty($filters['employee_id'])) {
            $this->db->where(db_prefix() . 'staff_late_arrival_requests.staff_id', $filters['employee_id']);
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
        $this->db->select(db_prefix() . 'staff_early_exit_requests.*, staff.firstname, staff.lastname');
        $this->db->from(db_prefix() . 'staff_early_exit_requests');
        $this->db->join(db_prefix() . 'staff staff', 'staff.staffid = ' . db_prefix() . 'staff_early_exit_requests.staff_id', 'left');

        if (!empty($filters['status'])) {
            $this->db->where(db_prefix() . 'staff_early_exit_requests.status', $filters['status']);
        }

        if (!empty($filters['employee_id'])) {
            $this->db->where(db_prefix() . 'staff_early_exit_requests.staff_id', $filters['employee_id']);
        }

        $this->db->order_by(db_prefix() . 'staff_early_exit_requests.created_at', 'desc');

        return $this->db->get()->result_array();
    }
}
