<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Operations -> Attendance (Admin, full list + filters) and My Attendance
 * (any logged-in staff, self-scoped, read-only) - one controller, the view
 * branches on is_admin() exactly like this module's Dashboard summary
 * widget and every role-branched page elsewhere in this codebase.
 */
class Staff_attendance extends AdminController
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('staff_attendance/staff_attendance_model');
        $this->load->model('staff_model');
        $this->lang->load('staff_attendance/staff_attendance', 'english');
    }

    /**
     * Admin: full Attendance list with filters. Staff: My Attendance
     * (own records only, no filters, no Employee column - see manage.php's
     * own $is_admin branch for the exact rendering split).
     *
     * Attendance Module Enhancement: the Staff branch now also loads
     * everything the 3 new tabs (Leave Requests/Holiday Calendar/Late-
     * Early Requests) need - all in this one page load, since Leave
     * History is the only piece that needs its own DataTable AJAX
     * endpoint (leave_requests_table() below); the rest are small,
     * unpaginated per-staff lists rendered server-side like every other
     * "My X" list in this codebase.
     */
    public function index()
    {
        if (!is_staff_member()) {
            access_denied('Attendance');
        }

        $data['title'] = is_admin() ? _l('staff_attendance') : _l('staff_attendance_my_attendance');

        if (is_admin()) {
            $data['staff']       = $this->staff_model->get('', ['active' => 1]);
            $this->load->model('business_departments/business_departments_model');
            $data['departments'] = $this->business_departments_model->get();
            // Holiday Calendar is Admin-maintained (spec #4) - the Admin
            // branch gets its own CRUD tab on this same page, reusing the
            // get_holidays() read every employee's own read-only Holiday
            // Calendar tab already uses.
            $data['holidays'] = $this->staff_attendance_model->get_holidays();

            // Smart Attendance v2 Part 2 - Admin becomes Super Controller:
            // the same 3 review queues Manager Portal already has, plus
            // the counts for the review-tab badges. Default filter is
            // 'Pending' so Admin lands on exactly what needs action,
            // matching Manager Portal's own default.
            $data['leave_review']      = $this->staff_attendance_model->get_leave_requests_for_review(['status' => 'Pending']);
            $data['late_review']       = $this->staff_attendance_model->get_late_arrival_requests_for_review(['status' => 'Pending']);
            $data['early_review']      = $this->staff_attendance_model->get_early_exit_requests_for_review(['status' => 'Pending']);
            $data['pending_leave']     = $this->staff_attendance_model->get_pending_leave_requests_count();
            $data['pending_late']      = $this->staff_attendance_model->get_pending_late_arrival_requests_count();
            $data['pending_early']     = $this->staff_attendance_model->get_pending_early_exit_requests_count();
            $data['recent_audit_log']  = $this->staff_attendance_model->get_audit_log(['limit' => 100]);
        } else {
            $staff_id = get_staff_user_id();

            $data['leave_types']          = get_leave_types();
            $data['holidays']             = $this->staff_attendance_model->get_holidays();
            $data['late_arrival_requests'] = $this->staff_attendance_model->get_staff_late_arrival_requests($staff_id);
            $data['early_exit_requests']   = $this->staff_attendance_model->get_staff_early_exit_requests($staff_id);

            // Auto-detection prompt (spec #5/#6): today's own attendance
            // record run through staff_attendance_is_late_arrival()/
            // _is_early_exit() (staff_attendance_helper.php - the exact
            // same login_time/logout_time this module already writes,
            // never a duplicated calculation), shown only once per day -
            // suppressed if a request already exists for today.
            $today_record = $this->staff_attendance_model->get_staff_date_record($staff_id);

            $data['late_arrival_prompt'] = null;
            $data['early_exit_prompt']   = null;

            if ($today_record) {
                if (staff_attendance_is_late_arrival($today_record->login_time)
                    && !$this->staff_attendance_model->get_late_arrival_request_for_date($staff_id, $today_record->attendance_date)) {
                    $data['late_arrival_prompt'] = $today_record;
                }

                if ($today_record->logout_time
                    && staff_attendance_is_early_exit($today_record->logout_time)
                    && !$this->staff_attendance_model->get_early_exit_request_for_date($staff_id, $today_record->attendance_date)) {
                    $data['early_exit_prompt'] = $today_record;
                }
            }
        }

        $this->load->view('manage', $data);
    }

    /**
     * Leave History DataTable AJAX source - self-scoped only, mirrors
     * table()'s own DataTable pattern above (same data_tables_init()
     * helper, same get_datatable_buttons() export mechanism the view's
     * JS wires up - no new export code).
     */
    public function leave_requests_table()
    {
        if (!is_staff_member()) {
            ajax_access_denied();
        }

        $aColumns = [
            db_prefix() . 'staff_leave_requests.leave_type',
            db_prefix() . 'staff_leave_requests.start_date',
            db_prefix() . 'staff_leave_requests.end_date',
            db_prefix() . 'staff_leave_requests.days',
            db_prefix() . 'staff_leave_requests.created_at',
            db_prefix() . 'staff_leave_requests.status',
            db_prefix() . 'staff_leave_requests.manager_remarks',
            db_prefix() . 'staff_leave_requests.reviewed_at',
        ];

        $sIndexColumn = 'id';
        $sTable       = db_prefix() . 'staff_leave_requests';
        $where        = ['AND ' . db_prefix() . 'staff_leave_requests.staff_id = ' . (int) get_staff_user_id()];

        $status = $this->input->post('status');
        if ($status && in_array($status, ['Pending', 'Approved', 'Rejected', 'Cancelled'])) {
            $where[] = 'AND ' . db_prefix() . 'staff_leave_requests.status = "' . $this->db->escape_str($status) . '"';
        }

        $result = data_tables_init($aColumns, $sIndexColumn, $sTable, [], $where, [
            db_prefix() . 'staff_leave_requests.id',
        ]);

        $output  = $result['output'];
        $rResult = $result['rResult'];

        foreach ($rResult as $aRow) {
            $row   = [];
            $row[] = e($aRow[db_prefix() . 'staff_leave_requests.leave_type']);
            $row[] = _d($aRow[db_prefix() . 'staff_leave_requests.start_date']);
            $row[] = _d($aRow[db_prefix() . 'staff_leave_requests.end_date']);
            $row[] = rtrim(rtrim(number_format((float) $aRow[db_prefix() . 'staff_leave_requests.days'], 1), '0'), '.');
            $row[] = _d($aRow[db_prefix() . 'staff_leave_requests.created_at']);

            $status = $aRow[db_prefix() . 'staff_leave_requests.status'];
            $row[]  = '<span class="label" style="color:' . staff_attendance_request_status_color($status) . ';border:1px solid ' . adjust_hex_brightness(staff_attendance_request_status_color($status), 0.4) . ';background:' . adjust_hex_brightness(staff_attendance_request_status_color($status), 0.04) . ';">' . e($status) . '</span>';

            $row[] = $aRow[db_prefix() . 'staff_leave_requests.manager_remarks'] ? e($aRow[db_prefix() . 'staff_leave_requests.manager_remarks']) : '-';
            $row[] = $aRow[db_prefix() . 'staff_leave_requests.reviewed_at'] ? _dt($aRow[db_prefix() . 'staff_leave_requests.reviewed_at']) : '-';

            if ($status === 'Pending') {
                $row[] = '<button type="button" class="btn btn-danger btn-icon" onclick="attendance_cancel_leave_request(' . (int) $aRow['id'] . ');"><i class="fa-solid fa-ban"></i></button>';
            } else {
                $row[] = '-';
            }

            $row['DT_RowId'] = 'leave_request_' . $aRow['id'];
            $output['aaData'][] = $row;
        }

        echo json_encode($output);
    }

    /**
     * Request Leave - GET renders the modal form partial (mirrors
     * Staff_attendance::add()'s own GET/POST split), POST validates +
     * uploads the optional attachment + inserts + notifies the single
     * Operations Manager (get_operations_manager_staff_id(),
     * manager_portal_helper.php - reused cross-module, see that
     * function's own docblock).
     */
    public function submit_leave_request()
    {
        if (!is_staff_member()) {
            ajax_access_denied();
        }

        if (!$this->input->post()) {
            $data['leave_types'] = get_leave_types();
            $this->load->view('leave_request_form', $data);

            return;
        }

        $start_date = to_sql_date($this->input->post('start_date'));
        $end_date   = to_sql_date($this->input->post('end_date'));
        $leave_type = $this->input->post('leave_type');
        $reason     = trim((string) $this->input->post('reason'));

        if (!$start_date || !$end_date || !$leave_type || $reason === '') {
            echo json_encode(['success' => false, 'message' => _l('staff_attendance_request_missing_fields')]);

            return;
        }

        $attachment = $this->handle_request_attachment('attachment', 'leave_requests');

        if ($attachment === false) {
            echo json_encode(['success' => false, 'message' => _l('staff_attendance_request_invalid_attachment')]);

            return;
        }

        $staff_id  = get_staff_user_id();
        $insert_id = $this->staff_attendance_model->add_leave_request([
            'staff_id'   => $staff_id,
            'leave_type' => $leave_type,
            'start_date' => $start_date,
            'end_date'   => $end_date,
            'reason'     => $reason,
            'attachment' => $attachment,
        ]);

        if (!$insert_id) {
            echo json_encode(['success' => false, 'message' => _l('staff_attendance_request_invalid_dates')]);

            return;
        }

        $this->notify_operations_manager('not_leave_request_submitted', 'staff_attendance', [
            trim(get_staff_full_name($staff_id)) . ' - ' . $leave_type,
        ]);

        echo json_encode(['success' => true, 'message' => _l('staff_attendance_leave_request_submitted')]);
    }

    /**
     * Employee self-service cancel - "can cancel only while Pending",
     * enforced in the model, not just this controller (see
     * cancel_leave_request()'s own docblock).
     *
     * @param mixed $id
     */
    public function cancel_leave_request($id)
    {
        if (!is_staff_member()) {
            ajax_access_denied();
        }

        $cancelled = $this->staff_attendance_model->cancel_leave_request($id, get_staff_user_id());

        echo json_encode(['success' => $cancelled]);
    }

    /**
     * Late Arrival Request - GET renders the modal form (prefilled from
     * today's own attendance record, the only date this form ever
     * targets - "If an employee checks in after office time" is always
     * about today, not a historical backfill). POST validates + uploads
     * + inserts + notifies the Operations Manager.
     */
    public function submit_late_arrival_request()
    {
        if (!is_staff_member()) {
            ajax_access_denied();
        }

        $staff_id = get_staff_user_id();
        $record   = $this->staff_attendance_model->get_staff_date_record($staff_id);

        if (!$this->input->post()) {
            $data['record'] = $record;
            $this->load->view('late_arrival_form', $data);

            return;
        }

        if (!$record || !staff_attendance_is_late_arrival($record->login_time)) {
            echo json_encode(['success' => false, 'message' => _l('staff_attendance_request_not_eligible')]);

            return;
        }

        $reason = trim((string) $this->input->post('reason'));

        if ($reason === '') {
            echo json_encode(['success' => false, 'message' => _l('staff_attendance_request_missing_fields')]);

            return;
        }

        $attachment = $this->handle_request_attachment('attachment', 'late_arrival_requests');

        if ($attachment === false) {
            echo json_encode(['success' => false, 'message' => _l('staff_attendance_request_invalid_attachment')]);

            return;
        }

        $insert_id = $this->staff_attendance_model->add_late_arrival_request([
            'staff_id'        => $staff_id,
            'attendance_date' => $record->attendance_date,
            'login_time'      => $record->login_time,
            'reason'          => $reason,
            'attachment'      => $attachment,
        ]);

        if (!$insert_id) {
            echo json_encode(['success' => false, 'message' => _l('staff_attendance_request_already_submitted')]);

            return;
        }

        $this->notify_operations_manager('not_late_arrival_request_submitted', 'staff_attendance', [
            trim(get_staff_full_name($staff_id)) . ' - ' . _d($record->attendance_date),
        ]);

        echo json_encode(['success' => true, 'message' => _l('staff_attendance_late_arrival_request_submitted')]);
    }

    /**
     * Early Exit Request - same shape as submit_late_arrival_request()
     * above, targeting logout_time instead of login_time.
     */
    public function submit_early_exit_request()
    {
        if (!is_staff_member()) {
            ajax_access_denied();
        }

        $staff_id = get_staff_user_id();
        $record   = $this->staff_attendance_model->get_staff_date_record($staff_id);

        if (!$this->input->post()) {
            $data['record'] = $record;
            $this->load->view('early_exit_form', $data);

            return;
        }

        if (!$record || !$record->logout_time || !staff_attendance_is_early_exit($record->logout_time)) {
            echo json_encode(['success' => false, 'message' => _l('staff_attendance_request_not_eligible')]);

            return;
        }

        $reason = trim((string) $this->input->post('reason'));

        if ($reason === '') {
            echo json_encode(['success' => false, 'message' => _l('staff_attendance_request_missing_fields')]);

            return;
        }

        $attachment = $this->handle_request_attachment('attachment', 'early_exit_requests');

        if ($attachment === false) {
            echo json_encode(['success' => false, 'message' => _l('staff_attendance_request_invalid_attachment')]);

            return;
        }

        $insert_id = $this->staff_attendance_model->add_early_exit_request([
            'staff_id'        => $staff_id,
            'attendance_date' => $record->attendance_date,
            'logout_time'     => $record->logout_time,
            'reason'          => $reason,
            'attachment'      => $attachment,
        ]);

        if (!$insert_id) {
            echo json_encode(['success' => false, 'message' => _l('staff_attendance_request_already_submitted')]);

            return;
        }

        $this->notify_operations_manager('not_early_exit_request_submitted', 'staff_attendance', [
            trim(get_staff_full_name($staff_id)) . ' - ' . _d($record->attendance_date),
        ]);

        echo json_encode(['success' => true, 'message' => _l('staff_attendance_early_exit_request_submitted')]);
    }

    /**
     * Holiday Calendar CRUD - Admin-maintained per spec #4 ("Holiday data
     * will be maintained by the Admin"), the one write surface Admin gets
     * on this page (employees' own Holiday Calendar tab is read-only).
     * GET renders the modal form partial (mirrors add()'s own GET/POST
     * split above), POST validates + saves via Staff_attendance_model's
     * add_holiday()/update_holiday().
     */
    public function holiday_form()
    {
        if (!is_admin()) {
            ajax_access_denied();
        }

        $id = $this->input->post('id') ?: $this->input->get('id');

        if (!$this->input->post()) {
            $data['holiday'] = $id ? $this->staff_attendance_model->get_holiday($id) : null;
            $this->load->view('holiday_form', $data);

            return;
        }

        $name = trim((string) $this->input->post('name'));
        $date = to_sql_date($this->input->post('date'));

        if ($name === '' || !$date) {
            echo json_encode(['success' => false, 'message' => _l('staff_attendance_request_missing_fields')]);

            return;
        }

        $payload = [
            'name'        => $name,
            'date'        => $date,
            'description' => trim((string) $this->input->post('description')),
        ];

        if ($id) {
            $updated = $this->staff_attendance_model->update_holiday($id, $payload);
        } else {
            $payload['created_by'] = get_staff_user_id();
            $updated = (bool) $this->staff_attendance_model->add_holiday($payload);
        }

        echo json_encode([
            'success' => $updated,
            'message' => $updated ? _l('staff_attendance_holiday_saved') : _l('staff_attendance_holiday_save_failed'),
        ]);
    }

    /**
     * @param mixed $id
     */
    public function delete_holiday($id)
    {
        if (!is_admin()) {
            ajax_access_denied();
        }

        $deleted = $this->staff_attendance_model->delete_holiday($id);

        echo json_encode(['success' => $deleted]);
    }

    /**
     * Single optional-attachment handler shared by all 3 request types -
     * validates the extension via the existing global
     * _upload_extension_allowed() helper (upload_helper.php, the same
     * allowlist every other upload path in this app already enforces),
     * creates its uploads subfolder on first use via the existing global
     * _maybe_create_upload_path() helper. Deliberately a plain uploads/
     * folder + stored relative path, not the full tblfiles file-manager
     * subsystem (get_upload_path_by_type()) - each request has at most
     * one attachment, the same footprint as every other single-file
     * column already in this codebase (e.g. tblclients.assigned_work-
     * adjacent columns).
     *
     * @param  string $field_name POST file field name
     * @param  string $subfolder  under uploads/
     * @return string|null|false relative path to store, null if no file was
     *                            uploaded (attachment is optional), false if
     *                            the uploaded file's extension isn't allowed
     */
    private function handle_request_attachment($field_name, $subfolder)
    {
        if (!isset($_FILES[$field_name]) || $_FILES[$field_name]['error'] === UPLOAD_ERR_NO_FILE || $_FILES[$field_name]['name'] === '') {
            return null;
        }

        if ($_FILES[$field_name]['error'] !== UPLOAD_ERR_OK) {
            return false;
        }

        if (!_upload_extension_allowed($_FILES[$field_name]['name'])) {
            return false;
        }

        $upload_path = FCPATH . 'uploads/' . $subfolder . '/';
        _maybe_create_upload_path($upload_path);

        $extension     = strtolower(pathinfo($_FILES[$field_name]['name'], PATHINFO_EXTENSION));
        $stored_name   = md5(uniqid((string) get_staff_user_id(), true)) . '.' . $extension;
        $destination   = $upload_path . $stored_name;

        if (!move_uploaded_file($_FILES[$field_name]['tmp_name'], $destination)) {
            return false;
        }

        return $subfolder . '/' . $stored_name;
    }

    /**
     * @param  string $description_key core english_lang.php key
     * @param  string $link            admin_url(...)-relative route
     * @param  array  $additional_data sprintf args for the description string
     * @return void
     */
    private function notify_operations_manager($description_key, $link, $additional_data = [])
    {
        $manager_id = get_operations_manager_staff_id();

        if (!$manager_id) {
            return;
        }

        $notified = add_notification([
            'description'     => $description_key,
            'touserid'        => $manager_id,
            'link'            => $link,
            'additional_data' => serialize($additional_data),
        ]);

        if ($notified) {
            pusher_trigger_notification([$manager_id]);
        }
    }

    /**
     * Monthly Attendance Dashboard - Admin-only AJAX fragment, loaded
     * above the existing table whenever exactly one employee is
     * selected in the Employee filter (never for "All Employees" - see
     * manage.php's own JS). Reuses Staff_attendance_model::get_month_summary()/
     * get_month_calendar_data() (both single-query-per-fetch, scoped to
     * this one staff member and month only, per the "load only one
     * employee's attendance" requirement) - no new table, no duplicated
     * attendance logic.
     *
     * @param mixed $staff_id
     */
    public function month_dashboard($staff_id)
    {
        if (!is_admin()) {
            ajax_access_denied();
        }

        $year  = (int) ($this->input->post('year') ?: date('Y'));
        $month = (int) ($this->input->post('month') ?: date('n'));

        $data['staff_id']  = (int) $staff_id;
        $data['staff']     = $this->staff_model->get($staff_id);
        $data['year']      = $year;
        $data['month']     = $month;
        $data['calendar']  = $this->staff_attendance_model->get_month_calendar_data($staff_id, $year, $month);
        $data['summary']   = $this->staff_attendance_model->get_month_summary($staff_id, $year, $month);

        $this->load->view('month_dashboard', $data);
    }

    /**
     * DataTable AJAX source. Staff (My Attendance): unchanged - own
     * historical records only, no filters, real rows only (a personal
     * history list is a different use case from the Admin roster below).
     *
     * Admin: two modes, chosen from the posted date filter.
     *
     * "Single-date mode" (the default - no date filter posted at all, or
     * "Date From"/"Date To" resolve to exactly one day) is the fix for
     * the reported bug: the query is now rooted on tblstaff (every
     * active employee), LEFT JOIN tblstaff_attendance scoped to that one
     * date, so an employee with no login that day still gets a row -
     * rendered as Absent/"-"/"-"/"0h 0m"/"No Login" in PHP, never written
     * to the database (see admin_table_single_date()). This is the path
     * exercised on every normal page load/filter change.
     *
     * "Range mode" (a genuine multi-day range - both dates set and
     * different) falls back to the original attendance-table-rooted
     * query further down: real recorded rows only, for historical
     * export/reporting, where an "every employee x every day" cross
     * product would be a very different, much larger feature than what
     * was asked for here.
     */
    public function table()
    {
        if (!is_staff_member()) {
            ajax_access_denied();
        }

        if (is_admin()) {
            $single_date = $this->resolve_single_attendance_date();

            if ($single_date !== null) {
                $this->admin_table_single_date($single_date);

                return;
            }
        }

        // Column/index note: $aColumns MUST list its entries in the exact
        // same left-to-right order they're rendered below, since
        // data_tables_init()'s sort handling maps a clicked column's
        // numeric index straight back into $aColumns[$index] to build
        // ORDER BY - the Admin variant leads with staff.firstname (a
        // real, sortable/searchable column) so the "Employee" column can
        // be first visually (matching the requested column order)
        // without breaking that alignment, unlike this module's
        // sibling-page convention of only ever appending non-1:1 derived
        // columns at the end.
        if (is_admin()) {
            $aColumns = [
                db_prefix() . 'staff.firstname',
                db_prefix() . 'staff_attendance.attendance_date',
                db_prefix() . 'staff_attendance.login_time',
                db_prefix() . 'staff_attendance.logout_time',
                db_prefix() . 'staff_attendance.working_minutes',
                db_prefix() . 'staff_attendance.total_sessions',
                db_prefix() . 'staff_attendance.attendance_status',
                db_prefix() . 'staff_attendance.remarks',
            ];
        } else {
            $aColumns = [
                db_prefix() . 'staff_attendance.attendance_date',
                db_prefix() . 'staff_attendance.login_time',
                db_prefix() . 'staff_attendance.logout_time',
                db_prefix() . 'staff_attendance.working_minutes',
                db_prefix() . 'staff_attendance.total_sessions',
                db_prefix() . 'staff_attendance.attendance_status',
                db_prefix() . 'staff_attendance.remarks',
            ];
        }

        $sIndexColumn = 'id';
        $sTable       = db_prefix() . 'staff_attendance';

        $join = [
            'LEFT JOIN ' . db_prefix() . 'staff ON ' . db_prefix() . 'staff.staffid = ' . db_prefix() . 'staff_attendance.staff_id',
        ];

        $where = [];

        if (is_admin()) {
            if ($this->input->post('staff_id')) {
                $where[] = 'AND ' . db_prefix() . 'staff_attendance.staff_id = ' . (int) $this->input->post('staff_id');
            }

            if ($this->input->post('department_id')) {
                $this->load->model('business_departments/business_departments_model');
                $staff_ids = $this->business_departments_model->get_department_staff_ids((int) $this->input->post('department_id'));
                $where[]   = 'AND ' . db_prefix() . 'staff_attendance.staff_id IN (' . (count($staff_ids) ? implode(',', array_map('intval', $staff_ids)) : '0') . ')';
            }

            if ($this->input->post('status') && in_array($this->input->post('status'), get_attendance_statuses(), true)) {
                $where[] = 'AND ' . db_prefix() . 'staff_attendance.attendance_status = "' . $this->db->escape_str($this->input->post('status')) . '"';
            }

            $date_from = $this->input->post('date_from');
            $date_to   = $this->input->post('date_to');

            if ($date_from) {
                $where[] = 'AND ' . db_prefix() . 'staff_attendance.attendance_date >= "' . $this->db->escape_str(to_sql_date($date_from)) . '"';
            }

            if ($date_to) {
                $where[] = 'AND ' . db_prefix() . 'staff_attendance.attendance_date <= "' . $this->db->escape_str(to_sql_date($date_to)) . '"';
            }
        } else {
            // Staff view - always self-scoped, no filter bypass possible.
            $where[] = 'AND ' . db_prefix() . 'staff_attendance.staff_id = ' . (int) get_staff_user_id();
        }

        $result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, [
            db_prefix() . 'staff_attendance.id',
            db_prefix() . 'staff_attendance.staff_id',
            db_prefix() . 'staff.lastname',
        ]);

        $output  = $result['output'];
        $rResult = $result['rResult'];

        foreach ($rResult as $aRow) {
            $row = [];

            if (is_admin()) {
                // firstname comes from $aColumns (auto-aliased to its
                // full "table.column" key, see the note above) - lastname
                // is display-only, from additionalSelect (bare-keyed).
                $row[] = e(trim($aRow[db_prefix() . 'staff.firstname'] . ' ' . $aRow['lastname']));
            }

            $row[] = _d($aRow[db_prefix() . 'staff_attendance.attendance_date']);
            $row[] = $aRow[db_prefix() . 'staff_attendance.login_time'] ? _dt($aRow[db_prefix() . 'staff_attendance.login_time']) : '-';
            $row[] = $aRow[db_prefix() . 'staff_attendance.logout_time'] ? _dt($aRow[db_prefix() . 'staff_attendance.logout_time']) : '-';
            $row[] = format_working_minutes($aRow[db_prefix() . 'staff_attendance.working_minutes']);

            $sessions = (int) $aRow[db_prefix() . 'staff_attendance.total_sessions'];
            $row[]    = $sessions > 0
                ? '<a href="#" onclick="attendance_view_sessions(' . (int) $aRow['id'] . '); return false;">' . $sessions . ' <i class="fa-regular fa-clock tw-text-xs"></i></a>'
                : '-';

            $status = $aRow[db_prefix() . 'staff_attendance.attendance_status'];

            if (is_admin()) {
                $outputStatus = '<div class="dropdown inline-block">';
                $outputStatus .= '<a href="#" class="dropdown-toggle tw-flex tw-items-center tw-gap-1 tw-flex-nowrap hover:tw-opacity-80 tw-align-middle label" style="color:' . get_attendance_status_color($status) . ';border:1px solid ' . adjust_hex_brightness(get_attendance_status_color($status), 0.4) . ';background:' . adjust_hex_brightness(get_attendance_status_color($status), 0.04) . ';" data-toggle="dropdown">';
                $outputStatus .= e($status) . '<i class="chevron"></i></a>';
                $outputStatus .= '<ul class="dropdown-menu dropdown-menu-right">';
                foreach (get_attendance_statuses() as $statusOption) {
                    if ($statusOption !== $status) {
                        $outputStatus .= '<li><a href="#" onclick="attendance_mark_as(' . (int) $aRow['id'] . ',\'' . e($statusOption) . '\'); return false;">' . e($statusOption) . '</a></li>';
                    }
                }
                $outputStatus .= '</ul></div>';
                $row[] = $outputStatus;
            } else {
                $row[] = '<span class="label" style="color:' . get_attendance_status_color($status) . ';border:1px solid ' . adjust_hex_brightness(get_attendance_status_color($status), 0.4) . ';background:' . adjust_hex_brightness(get_attendance_status_color($status), 0.04) . ';">' . e($status) . '</span>';
            }

            if (is_admin()) {
                $remarks = e((string) $aRow[db_prefix() . 'staff_attendance.remarks']);
                $row[]   = '<span class="editable-remarks" data-id="' . (int) $aRow['id'] . '" data-value="' . $remarks . '" title="' . _l('staff_attendance_click_to_edit_remarks') . '">' . ($remarks !== '' ? $remarks : '<i class="text-muted">' . _l('staff_attendance_no_remarks') . '</i>') . ' <i class="fa-regular fa-pen-to-square tw-text-xs tw-text-gray-400"></i></span>';
            } else {
                $row[] = $aRow[db_prefix() . 'staff_attendance.remarks'] ? e($aRow[db_prefix() . 'staff_attendance.remarks']) : '-';
            }

            $row['DT_RowId'] = 'attendance_' . $aRow['id'];
            $output['aaData'][] = $row;
        }

        echo json_encode($output);
    }

    /**
     * Decides single-date vs. range mode from the posted date filter -
     * see table()'s own docblock for what each mode means.
     *
     * @return string|null Y-m-d if single-date mode applies, null for range mode
     */
    private function resolve_single_attendance_date()
    {
        $date_from = $this->input->post('date_from');
        $date_to   = $this->input->post('date_to');

        $sql_from = $date_from ? to_sql_date($date_from) : null;
        $sql_to   = $date_to ? to_sql_date($date_to) : null;

        if (!$sql_from && !$sql_to) {
            return date('Y-m-d');
        }

        if ($sql_from && $sql_to && $sql_from === $sql_to) {
            return $sql_from;
        }

        if ($sql_from && !$sql_to) {
            return $sql_from;
        }

        if ($sql_to && !$sql_from) {
            return $sql_to;
        }

        // A genuine range (both set, and different) - range mode.
        return null;
    }

    /**
     * The actual fix: every active employee, LEFT JOIN'd to their
     * attendance row (if any) for exactly one date - so an employee who
     * never logged in still gets a row, rendered as Absent with no
     * database write (requirement: "Do NOT create fake attendance
     * records"). $aColumns keeps a literal-date pseudo-column at index 1
     * so the visual column order (Employee/Date/Login/Logout/Working
     * Hours/Status/Remarks) still lines up 1:1 with data_tables_init()'s
     * index-based sort mapping, even though Date has no real per-row
     * value to sort by here (every row shares the same date) - manage.php
     * marks that index not sortable/searchable for exactly this reason.
     *
     * @param string $single_date Y-m-d
     */
    private function admin_table_single_date($single_date)
    {
        $aColumns = [
            db_prefix() . 'staff.firstname',
            '"' . $this->db->escape_str($single_date) . '" as fixed_date',
            db_prefix() . 'staff_attendance.login_time',
            db_prefix() . 'staff_attendance.logout_time',
            db_prefix() . 'staff_attendance.working_minutes',
            db_prefix() . 'staff_attendance.total_sessions',
            db_prefix() . 'staff_attendance.attendance_status',
            db_prefix() . 'staff_attendance.remarks',
        ];

        $sIndexColumn = 'staffid';
        $sTable       = db_prefix() . 'staff';

        $join = [
            'LEFT JOIN ' . db_prefix() . 'staff_attendance ON ' . db_prefix() . 'staff_attendance.staff_id = ' . db_prefix() . 'staff.staffid AND ' . db_prefix() . 'staff_attendance.attendance_date = "' . $this->db->escape_str($single_date) . '"',
        ];

        $where = ['AND ' . db_prefix() . 'staff.active = 1'];

        if ($this->input->post('staff_id')) {
            $where[] = 'AND ' . db_prefix() . 'staff.staffid = ' . (int) $this->input->post('staff_id');
        }

        if ($this->input->post('department_id')) {
            $this->load->model('business_departments/business_departments_model');
            $staff_ids = $this->business_departments_model->get_department_staff_ids((int) $this->input->post('department_id'));
            $where[]   = 'AND ' . db_prefix() . 'staff.staffid IN (' . (count($staff_ids) ? implode(',', array_map('intval', $staff_ids)) : '0') . ')';
        }

        if ($this->input->post('status') && in_array($this->input->post('status'), get_attendance_statuses(), true)) {
            $filter_status = $this->input->post('status');

            // "Absent" must also match employees with no real row at all
            // for this date (attendance_status IS NULL from the LEFT
            // JOIN) - every other status only ever matches a real row.
            if ($filter_status === 'Absent') {
                $where[] = 'AND (' . db_prefix() . 'staff_attendance.attendance_status = "Absent" OR ' . db_prefix() . 'staff_attendance.id IS NULL)';
            } else {
                $where[] = 'AND ' . db_prefix() . 'staff_attendance.attendance_status = "' . $this->db->escape_str($filter_status) . '"';
            }
        }

        $result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, [
            db_prefix() . 'staff.staffid',
            db_prefix() . 'staff.lastname',
            db_prefix() . 'staff_attendance.id as attendance_id',
        ]);

        $output  = $result['output'];
        $rResult = $result['rResult'];

        foreach ($rResult as $aRow) {
            $row = [];

            $row[] = e(trim($aRow[db_prefix() . 'staff.firstname'] . ' ' . $aRow['lastname']));
            $row[] = _d($single_date);

            $has_record = !empty($aRow['attendance_id']);

            $row[] = ($has_record && $aRow[db_prefix() . 'staff_attendance.login_time']) ? _dt($aRow[db_prefix() . 'staff_attendance.login_time']) : '-';
            $row[] = ($has_record && $aRow[db_prefix() . 'staff_attendance.logout_time']) ? _dt($aRow[db_prefix() . 'staff_attendance.logout_time']) : '-';
            $row[] = $has_record ? format_working_minutes($aRow[db_prefix() . 'staff_attendance.working_minutes']) : '0h 0m';

            $sessions = $has_record ? (int) $aRow[db_prefix() . 'staff_attendance.total_sessions'] : 0;
            $row[]    = $sessions > 0
                ? '<a href="#" onclick="attendance_view_sessions(' . (int) $aRow['attendance_id'] . '); return false;">' . $sessions . ' <i class="fa-regular fa-clock tw-text-xs"></i></a>'
                : '-';

            $status = $has_record ? $aRow[db_prefix() . 'staff_attendance.attendance_status'] : 'Absent';

            if ($has_record) {
                $outputStatus = '<div class="dropdown inline-block">';
                $outputStatus .= '<a href="#" class="dropdown-toggle tw-flex tw-items-center tw-gap-1 tw-flex-nowrap hover:tw-opacity-80 tw-align-middle label" style="color:' . get_attendance_status_color($status) . ';border:1px solid ' . adjust_hex_brightness(get_attendance_status_color($status), 0.4) . ';background:' . adjust_hex_brightness(get_attendance_status_color($status), 0.04) . ';" data-toggle="dropdown">';
                $outputStatus .= e($status) . '<i class="chevron"></i></a>';
                $outputStatus .= '<ul class="dropdown-menu dropdown-menu-right">';
                foreach (get_attendance_statuses() as $statusOption) {
                    if ($statusOption !== $status) {
                        $outputStatus .= '<li><a href="#" onclick="attendance_mark_as(' . (int) $aRow['attendance_id'] . ',\'' . e($statusOption) . '\'); return false;">' . e($statusOption) . '</a></li>';
                    }
                }
                $outputStatus .= '</ul></div>';
                $row[] = $outputStatus;
            } else {
                // No real row to update yet - a plain badge, not the
                // interactive dropdown (nothing to write to without
                // creating a database record first, which this feature
                // deliberately never does automatically).
                $row[] = '<span class="label" style="color:' . get_attendance_status_color($status) . ';border:1px solid ' . adjust_hex_brightness(get_attendance_status_color($status), 0.4) . ';background:' . adjust_hex_brightness(get_attendance_status_color($status), 0.04) . ';">' . e($status) . '</span>';
            }

            if ($has_record) {
                $remarks = e((string) $aRow[db_prefix() . 'staff_attendance.remarks']);
                $row[]   = '<span class="editable-remarks" data-id="' . (int) $aRow['attendance_id'] . '" data-value="' . $remarks . '" title="' . _l('staff_attendance_click_to_edit_remarks') . '">' . ($remarks !== '' ? $remarks : '<i class="text-muted">' . _l('staff_attendance_no_remarks') . '</i>') . ' <i class="fa-regular fa-pen-to-square tw-text-xs tw-text-gray-400"></i></span>';
            } else {
                $row[] = '<i class="text-muted">' . _l('staff_attendance_no_login') . '</i>';
            }

            $row['DT_RowId'] = 'attendance_staff_' . $aRow[db_prefix() . 'staff.staffid'];
            $output['aaData'][] = $row;
        }

        echo json_encode($output);
    }

    /**
     * Admin-only inline status change - mirrors the exact
     * Customers/Leads inline-dropdown AJAX pattern already proven
     * elsewhere in this codebase (single POST, DataTable reload on
     * success, no full page refresh).
     *
     * @param mixed $id
     */
    public function update_status($id)
    {
        if (!is_admin()) {
            ajax_access_denied();
        }

        $status = $this->input->post('status');

        if (!$this->staff_attendance_model->update_status($id, $status)) {
            echo json_encode(['success' => false]);

            return;
        }

        echo json_encode(['success' => true]);
    }

    /**
     * Admin-only remarks edit.
     *
     * @param mixed $id
     */
    public function update_remarks($id)
    {
        if (!is_admin()) {
            ajax_access_denied();
        }

        $this->staff_attendance_model->update_remarks($id, $this->input->post('remarks'));

        echo json_encode(['success' => true]);
    }

    /**
     * Smart Attendance session breakdown - AJAX modal partial for one
     * day's attendance row (Session 1, Session 2, ...). Ownership-gated
     * the same way attendance_detail-style views are elsewhere in this
     * codebase: Admin can view anyone's, a staff member only their own.
     *
     * @param mixed $id tblstaff_attendance.id
     */
    public function sessions_modal($id)
    {
        if (!is_staff_member()) {
            ajax_access_denied();
        }

        $record = $this->staff_attendance_model->get($id);

        if (!$record) {
            echo _l('staff_attendance_no_sessions');

            return;
        }

        if (!is_admin() && (int) $record->staff_id !== (int) get_staff_user_id()) {
            ajax_access_denied();
        }

        $data['record']   = $record;
        $data['sessions'] = $this->staff_attendance_model->get_sessions_for_date($record->staff_id, $record->attendance_date);

        $this->load->view('sessions_modal', $data);
    }

    /**
     * Admin-only manual add - the only path that can create an
     * attendance row without a real login event (e.g. backfilling a
     * known Absent/Leave/Weekend day).
     */
    public function add()
    {
        if (!is_admin()) {
            ajax_access_denied();
        }

        if (!$this->input->post()) {
            $this->load->model('business_departments/business_departments_model');
            $data['staff'] = $this->staff_model->get('', ['active' => 1]);
            $this->load->view('add', $data);

            return;
        }

        $staff_id = (int) $this->input->post('staff_id');
        $date     = to_sql_date($this->input->post('attendance_date'));

        if (!$staff_id || !$date) {
            echo json_encode(['success' => false, 'message' => _l('staff_attendance_add_missing_fields')]);

            return;
        }

        $insert_id = $this->staff_attendance_model->add_manual([
            'staff_id'          => $staff_id,
            'attendance_date'   => $date,
            'attendance_status' => $this->input->post('attendance_status'),
            'remarks'           => $this->input->post('remarks'),
        ]);

        if (!$insert_id) {
            echo json_encode(['success' => false, 'message' => _l('staff_attendance_add_duplicate')]);

            return;
        }

        echo json_encode(['success' => true]);
    }

    /**
     * Admin Portal Attendance Module (Part 2) - "Admin becomes Super
     * Controller". Mirrors Manager_portal::review_request() but calls the
     * admin_review_*_request() model methods instead, which have no
     * "Pending only" guard - so this both handles a still-Pending request
     * (normal review) and re-decides one the Operations Manager (or a
     * previous Admin action) already Approved/Rejected ("Override
     * Operations Manager decision", explicit requirement). Every call is
     * audit-logged inside the model layer itself, and the Operations
     * Manager is notified either way, same cross-notification shape
     * Manager_portal::review_request() already sends to every Admin.
     */
    public function review_request()
    {
        if (!is_admin()) {
            ajax_access_denied();
        }

        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $type     = $this->input->post('type');
        $id       = (int) $this->input->post('id');
        $decision = $this->input->post('decision');
        $remarks  = trim((string) $this->input->post('remarks'));

        if (!in_array($decision, ['Approved', 'Rejected'], true)) {
            echo json_encode(['success' => false, 'message' => _l('staff_attendance_invalid_decision')]);

            return;
        }

        $map = [
            'leave'        => ['admin_review_leave_request', 'Leave'],
            'late_arrival' => ['admin_review_late_arrival_request', 'Late Arrival'],
            'early_exit'   => ['admin_review_early_exit_request', 'Early Exit'],
        ];

        if (!isset($map[$type])) {
            show_404();
        }

        [$method, $type_label] = $map[$type];

        $updated = $this->staff_attendance_model->$method($id, $decision, get_staff_user_id(), $remarks);

        if ($updated) {
            $description_key = $decision === 'Approved'
                ? ['leave' => 'not_leave_request_approved', 'late_arrival' => 'not_late_arrival_request_approved', 'early_exit' => 'not_early_exit_request_approved'][$type]
                : ['leave' => 'not_leave_request_rejected', 'late_arrival' => 'not_late_arrival_request_rejected', 'early_exit' => 'not_early_exit_request_rejected'][$type];

            $notified = add_notification([
                'description'     => $description_key,
                'touserid'        => $updated->staff_id,
                'link'            => 'staff_attendance',
                'additional_data' => serialize([_d($updated->attendance_date ?? $updated->start_date)]),
            ]);

            if ($notified) {
                pusher_trigger_notification([$updated->staff_id]);
            }

            // "If Admin approves, Operations Manager receives notification."
            $manager_id = get_operations_manager_staff_id();

            if ($manager_id) {
                $requester = $this->staff_model->get($updated->staff_id);
                $manager_notified = add_notification([
                    'description'     => 'not_attendance_request_decision_cross_notify',
                    'touserid'        => $manager_id,
                    'link'            => 'staff_attendance',
                    'additional_data' => serialize([$decision, $type_label, $requester ? ($requester->firstname . ' ' . $requester->lastname) : '', _d($updated->attendance_date ?? $updated->start_date)]),
                ]);

                if ($manager_notified) {
                    pusher_trigger_notification([$manager_id]);
                }
            }
        }

        echo json_encode([
            'success' => (bool) $updated,
            'message' => $updated ? _l('staff_attendance_review_saved') : _l('staff_attendance_review_failed'),
        ]);
    }

    /**
     * AJAX - re-filters one of the 3 Admin review queues (Leave/Late
     * Arrival/Early Exit) by status/employee/department/date without a
     * full page reload, returning the same table body markup the initial
     * page render used so the JS can just swap it in.
     */
    public function review_requests_filter()
    {
        if (!is_admin()) {
            ajax_access_denied();
        }

        $type    = $this->input->post('type');
        $filters = [
            'status'      => $this->input->post('status') ?: null,
            'employee_id' => $this->input->post('employee_id') ?: null,
            'date_from'   => $this->input->post('date_from') ? to_sql_date($this->input->post('date_from')) : null,
            'date_to'     => $this->input->post('date_to') ? to_sql_date($this->input->post('date_to')) : null,
        ];

        if ($this->input->post('department_id')) {
            $this->load->model('business_departments/business_departments_model');
            $filters['staff_ids'] = $this->business_departments_model->get_department_staff_ids((int) $this->input->post('department_id'));
        }

        $methods = [
            'leave'        => 'get_leave_requests_for_review',
            'late_arrival' => 'get_late_arrival_requests_for_review',
            'early_exit'   => 'get_early_exit_requests_for_review',
        ];

        if (!isset($methods[$type])) {
            show_404();
        }

        $data['type']    = $type;
        $data['records'] = $this->staff_attendance_model->{$methods[$type]}($filters);

        $this->load->view('review_requests_rows', $data);
    }

    /**
     * Admin Portal Reports - Daily/Weekly/Monthly, Employee-wise,
     * Department-wise, Attendance Summary, Late Report, Leave Report,
     * Working Hours Report. One page, a report-type selector, all built
     * on the model methods added for this Part - no per-report-type
     * page, matching this module's existing "one page, tabs" convention
     * (Attendance/Holidays already work this way).
     */
    public function reports()
    {
        if (!is_admin()) {
            access_denied('Attendance Reports');
        }

        $year  = (int) ($this->input->get('year') ?: date('Y'));
        $month = (int) ($this->input->get('month') ?: date('n'));

        $data['title']              = _l('staff_attendance_reports');
        $data['year']               = $year;
        $data['month']              = $month;
        $data['employee_report']    = $this->staff_attendance_model->get_employee_wise_month_report($year, $month);
        $data['department_report']  = $this->staff_attendance_model->get_department_wise_month_report($year, $month);
        $data['late_report']        = $this->staff_attendance_model->get_late_report($year, $month);
        $data['leave_report']       = $this->staff_attendance_model->get_leave_report($year, $month);

        $this->load->view('reports', $data);
    }

    /**
     * Admin-facing Audit Log - "View Complete History". Filterable by
     * target type / employee / date range, all via
     * Staff_attendance_model::get_audit_log().
     */
    public function audit_log()
    {
        if (!is_admin()) {
            ajax_access_denied();
        }

        $filters = [
            'target_type'     => $this->input->post('target_type') ?: null,
            'target_staff_id' => $this->input->post('employee_id') ?: null,
            'date_from'       => $this->input->post('date_from') ? to_sql_date($this->input->post('date_from')) : null,
            'date_to'         => $this->input->post('date_to') ? to_sql_date($this->input->post('date_to')) : null,
        ];

        $data['log'] = $this->staff_attendance_model->get_audit_log($filters);

        $this->load->view('audit_log_rows', $data);
    }
}
