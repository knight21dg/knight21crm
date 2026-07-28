<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Daily Work Update - shared company-wide implementation (Phase 8, Step 1).
 * One controller/model/view reused by every portal for the 9 target
 * departments (modules/daily_work_update/helpers - daily_work_update_target_departments()),
 * instead of a per-department copy. Extracted from modules/dm_portal's
 * original Daily Work Update actions, generalized to gate on department
 * membership across all 9 departments rather than just Digital Marketing.
 *
 * Step 1 scope: this controller is reachable directly
 * (admin/daily_work_update) and fully functional on its own, verified in
 * isolation. Wiring it INTO each department portal's own sidebar/dashboard
 * (dm_portal included) is Step 2+ - not done here.
 */
class Daily_work_update extends AdminController
{
    public function __construct()
    {
        parent::__construct();

        if (!is_daily_work_update_member()) {
            access_denied('Daily Work Update');
        }

        $this->load->model('daily_work_update/daily_work_update_model');
        $this->load->model('tasks_model');
        $this->load->model('projects_model');
        $this->lang->load('daily_work_update/daily_work_update', 'english');
    }

    /**
     * The Daily Work Update page - role-branched. Admin's job here is
     * reviewing everyone else's submissions, not submitting their own
     * (see report() below), so the same menu link/route that opens the
     * employee form for a department staff member opens the reporting
     * page instead for Admin - one route, no separate "Reports" menu
     * entry needed. report() is also reachable directly, in case a future
     * menu wants to link straight to it.
     *
     * For an employee: if today's row already exists, the view opens in
     * edit mode pre-filled with it (get_today()), never a second create
     * form - "one update per employee per day", enforced together with
     * the UNIQUE KEY on the table and the upsert() model method used by
     * save() below.
     */
    public function index()
    {
        if (is_admin()) {
            $this->report();

            return;
        }

        $staff_id = get_staff_user_id();

        $data['title']          = _l('daily_work_update_title');
        $data['today_update']   = $this->daily_work_update_model->get_today($staff_id);
        $data['projects']       = $this->daily_work_update_model->get_my_projects_for_filter($staff_id);
        $data['tasks']          = $this->daily_work_update_model->get_my_tasks_for_filter($staff_id);
        $data['recent_updates'] = $this->daily_work_update_model->get_recent($staff_id, 10);
        $data['today_files']    = $data['today_update'] ? $this->daily_work_update_model->get_attachments($data['today_update']->id) : [];
        // Rendered once here, through the exact same partial
        // attachments_fragment() returns for the AJAX refresh after
        // upload/delete - one rendering path, not two copies of the
        // list markup.
        $data['attachments_html'] = $data['today_update'] ? $this->load->view('attachments_list', ['attachments' => $data['today_files']], true) : '';

        $this->load->view('index', $data);
    }

    /**
     * Save (create-or-update) today's Daily Work Update. Admin-blocked -
     * Admin's role here is reporting only, never submitting (see
     * index()'s own docblock) - this is enforced here too, not just by
     * hiding the form, since the endpoint would otherwise still be
     * directly reachable. project_id/task_id are optional but, when
     * posted, are validated against this staff member's own
     * projects/tasks - never trusted blindly.
     */
    public function save()
    {
        if (is_admin()) {
            ajax_access_denied();
        }

        $staff_id = get_staff_user_id();

        $project_id    = $this->input->post('project_id') ?: null;
        $task_id       = $this->input->post('task_id') ?: null;
        $today_work    = trim((string) $this->input->post('today_work'));
        $tomorrow_plan = trim((string) $this->input->post('tomorrow_plan'));
        $issues        = trim((string) $this->input->post('issues'));

        if ($today_work === '' || $tomorrow_plan === '') {
            echo json_encode(['success' => false, 'message' => _l('daily_work_update_missing_fields')]);

            return;
        }

        if ($project_id && !$this->projects_model->is_member($project_id)) {
            echo json_encode(['success' => false, 'message' => _l('daily_work_update_invalid_project')]);

            return;
        }

        if ($task_id && !$this->tasks_model->is_task_assignee($staff_id, $task_id)) {
            echo json_encode(['success' => false, 'message' => _l('daily_work_update_invalid_task')]);

            return;
        }

        $id = $this->daily_work_update_model->upsert($staff_id, [
            'project_id'    => $project_id,
            'task_id'       => $task_id,
            'today_work'    => $today_work,
            'tomorrow_plan' => $tomorrow_plan,
            'issues'        => $issues,
        ]);

        echo json_encode(['success' => true, 'id' => $id]);
    }

    /**
     * Attachment upload for a Daily Work Update - reuses the existing
     * generic handle_sales_attachments($rel_id, $rel_type) core helper,
     * extended via the get_upload_path_by_type filter
     * (daily_work_update_upload_path() in daily_work_update.php) rather
     * than writing new upload/tblfiles-insert logic - same mechanism
     * dm_portal's own daily_update_upload() already used, same physical
     * uploads/dm_work_updates/ folder, rel_type, and underlying
     * tbldm_portal_work_updates table (see Daily_work_update_model's own
     * docblocks for why a department-neutral rename is deferred to Step 2).
     * Ownership is checked first - only the staff member who owns this
     * Daily Work Update row may attach files to it. Admin-blocked, same
     * reasoning as save() above - Admin reviews attachments through the
     * report's detail modal, never uploads one.
     */
    public function upload($id)
    {
        if (is_admin()) {
            ajax_access_denied();
        }

        $this->db->where('id', $id);
        $this->db->where('staff_id', get_staff_user_id());
        $owned = $this->db->get(db_prefix() . 'dm_portal_work_updates')->row();

        if (!$owned) {
            ajax_access_denied();
        }

        if (isset($_FILES['file']['name']) && !_upload_extension_allowed($_FILES['file']['name'])) {
            echo json_encode(['success' => false, 'message' => _l('validation_extension_not_allowed')]);

            return;
        }

        handle_sales_attachments($id, 'dm_work_update');
    }

    /**
     * Attachments list fragment for one Daily Work Update - re-rendered
     * after upload/delete so the page can refresh just this section
     * instead of a full reload. Same ownership rule as every other
     * per-record action in this controller (owning staff member, or
     * Admin).
     */
    public function attachments_fragment($id)
    {
        if (!$this->is_owned_work_update($id)) {
            ajax_access_denied();
        }

        $data['work_update_id'] = (int) $id;
        $data['attachments']    = $this->daily_work_update_model->get_attachments($id);

        echo $this->load->view('attachments_list', $data, true);
    }

    /**
     * Delete one Daily Work Update attachment. Reuses
     * Daily_work_update_model::delete_attachment() for the actual
     * unlink+tblfiles-delete (same shape as core's own
     * Clients_model/Estimates_model/Invoices_model/Proposals_model
     * delete_attachment() methods - no new delete logic invented here).
     * This action's own job is authorization: the attachment must (a)
     * actually exist and be a 'dm_work_update' attachment, and (b)
     * belong to a Daily Work Update owned by the current staff member
     * (or Admin) - an invalid or someone-else's attachment id is
     * rejected before delete_attachment() is ever called. JSON
     * responses only, per spec - not ajax_access_denied() (plain text).
     */
    public function delete_attachment($id)
    {
        $attachment = $this->daily_work_update_model->get_attachment($id);

        if (!$attachment || !$this->is_owned_work_update($attachment->rel_id)) {
            echo json_encode(['success' => false, 'message' => _l('access_denied')]);

            return;
        }

        $deleted = $this->daily_work_update_model->delete_attachment($id);

        echo json_encode(['success' => $deleted]);
    }

    /**
     * Whether $work_update_id is a real tbldm_portal_work_updates row
     * owned by the current staff member - or, for Admin, simply that it
     * exists. Same check upload() already applies inline; centralized
     * here since attachments_fragment()/delete_attachment() both need
     * the identical rule.
     *
     * @param  mixed $work_update_id
     * @return bool
     */
    private function is_owned_work_update($work_update_id)
    {
        $this->db->where('id', $work_update_id);
        if (!is_admin()) {
            $this->db->where('staff_id', get_staff_user_id());
        }

        return (bool) $this->db->get(db_prefix() . 'dm_portal_work_updates')->row();
    }

    /**
     * Admin-only reporting page - "Staff Daily Work Updates". Filter
     * dropdown data only; the table itself loads via report_table()
     * below, same "page loads shell, table loads its own data" split
     * every other DataTable page in this codebase already uses.
     */
    public function report()
    {
        if (!is_admin()) {
            access_denied('Daily Work Update');
        }

        $data['title']       = _l('daily_work_update_report_title');
        $data['departments'] = $this->daily_work_update_model->get_report_filter_departments();
        $data['employees']   = $this->daily_work_update_model->get_report_filter_employees();
        $data['projects']    = $this->daily_work_update_model->get_report_filter_projects();
        $data['tasks']       = $this->daily_work_update_model->get_report_filter_tasks();

        $this->load->view('report', $data);
    }

    /**
     * Admin-only DataTable source for the reporting page. Two distinct
     * query shapes, chosen by the "status" filter:
     *
     * - Normal / "Submitted Today": rooted at tbldm_portal_work_updates
     *   (one row per actual submission), joined out to staff/department/
     *   project/task for display.
     * - "Not Submitted Today": rooted at tblstaff instead (every active
     *   staff member in the 9 target departments), LEFT JOINed to
     *   *today's* work-update row and filtered to where that join found
     *   nothing - the only way to represent an absence of a row, same
     *   "staff-driven LEFT JOIN" restructuring already used for
     *   Attendance's own single-date view (Staff_attendance_model).
     *
     * Both shapes go through the same data_tables_init() every other
     * table source in this codebase uses - no separate pagination/search
     * implementation for the second shape, just a different
     * table/join/column set fed into it.
     */
    public function report_table()
    {
        if (!is_admin() || !$this->input->is_ajax_request()) {
            return;
        }

        $status         = $this->input->post('status');
        $department_id  = $this->input->post('department_id');
        $employee_id    = $this->input->post('employee_id');
        $project_id     = $this->input->post('project_id');
        $task_id        = $this->input->post('task_id');
        $date_from      = $this->input->post('date_from');
        $date_to        = $this->input->post('date_to');
        $has_attachments = $this->input->post('has_attachments');

        $target_staff_ids = daily_work_update_target_department_staff_ids();
        if (empty($target_staff_ids)) {
            $target_staff_ids = [0];
        }

        if ($status === 'not_submitted_today') {
            $sTable = db_prefix() . 'staff';
            $join   = [
                'LEFT JOIN ' . db_prefix() . 'dm_portal_work_updates ON ' . db_prefix() . 'dm_portal_work_updates.staff_id = ' . db_prefix() . 'staff.staffid AND ' . db_prefix() . 'dm_portal_work_updates.work_date = CURDATE()',
            ];
            $aColumns = [
                db_prefix() . 'staff.staffid',
                db_prefix() . 'staff.firstname',
                db_prefix() . 'staff.lastname',
                $this->report_department_subquery(db_prefix() . 'staff.staffid'),
            ];
            $where = [
                'AND ' . db_prefix() . 'staff.active = 1',
                'AND ' . db_prefix() . 'staff.staffid IN (' . implode(',', array_map('intval', $target_staff_ids)) . ')',
                'AND ' . db_prefix() . 'dm_portal_work_updates.id IS NULL',
            ];

            if ($department_id) {
                $where[] = 'AND ' . db_prefix() . 'staff.staffid IN (SELECT staff_id FROM ' . db_prefix() . 'business_department_staff WHERE department_id = ' . (int) $department_id . ')';
            }
            if ($employee_id) {
                $where[] = 'AND ' . db_prefix() . 'staff.staffid = ' . (int) $employee_id;
            }

            $result = data_tables_init($aColumns, 'staffid', $sTable, $join, $where);
            $output = $result['output'];

            foreach ($result['rResult'] as $aRow) {
                $row   = [];
                $today = date('Y-m-d');

                $row[] = e(_d($today));
                $row[] = e(trim($aRow[db_prefix() . 'staff.firstname'] . ' ' . $aRow[db_prefix() . 'staff.lastname']));
                $row[] = $aRow['departments'] ? e($aRow['departments']) : '-';
                $row[] = '<span class="label label-default">' . _l('daily_work_update_report_not_submitted') . '</span>';
                $row[] = '-';
                $row[] = '-';
                $row[] = '-';
                $row[] = '-';
                $row[] = '-';
                $row[] = '-';
                $row[] = '-';

                $output['aaData'][] = $row;
            }

            echo json_encode($output);

            return;
        }

        $sTable = db_prefix() . 'dm_portal_work_updates';
        $join   = [
            'JOIN ' . db_prefix() . 'staff ON ' . db_prefix() . 'staff.staffid = ' . db_prefix() . 'dm_portal_work_updates.staff_id',
            'LEFT JOIN ' . db_prefix() . 'projects ON ' . db_prefix() . 'projects.id = ' . db_prefix() . 'dm_portal_work_updates.project_id',
            'LEFT JOIN ' . db_prefix() . 'tasks ON ' . db_prefix() . 'tasks.id = ' . db_prefix() . 'dm_portal_work_updates.task_id',
        ];
        $aColumns = [
            db_prefix() . 'dm_portal_work_updates.id',
            db_prefix() . 'dm_portal_work_updates.work_date',
            db_prefix() . 'staff.firstname',
            db_prefix() . 'staff.lastname',
            $this->report_department_subquery(db_prefix() . 'dm_portal_work_updates.staff_id'),
            db_prefix() . 'projects.name as project_name',
            db_prefix() . 'tasks.name as task_name',
            db_prefix() . 'dm_portal_work_updates.today_work',
            db_prefix() . 'dm_portal_work_updates.tomorrow_plan',
            db_prefix() . 'dm_portal_work_updates.issues',
            '(SELECT COUNT(*) FROM ' . db_prefix() . 'files f WHERE f.rel_id = ' . db_prefix() . "dm_portal_work_updates.id AND f.rel_type = 'dm_work_update') as attachment_count",
            db_prefix() . 'dm_portal_work_updates.created_at',
            db_prefix() . 'dm_portal_work_updates.updated_at',
        ];
        $where = [
            'AND ' . db_prefix() . 'dm_portal_work_updates.staff_id IN (' . implode(',', array_map('intval', $target_staff_ids)) . ')',
        ];

        if ($status === 'submitted_today') {
            $where[] = 'AND ' . db_prefix() . 'dm_portal_work_updates.work_date = CURDATE()';
        }
        if ($department_id) {
            $where[] = 'AND ' . db_prefix() . 'dm_portal_work_updates.staff_id IN (SELECT staff_id FROM ' . db_prefix() . 'business_department_staff WHERE department_id = ' . (int) $department_id . ')';
        }
        if ($employee_id) {
            $where[] = 'AND ' . db_prefix() . 'dm_portal_work_updates.staff_id = ' . (int) $employee_id;
        }
        if ($project_id) {
            $where[] = 'AND ' . db_prefix() . 'dm_portal_work_updates.project_id = ' . (int) $project_id;
        }
        if ($task_id) {
            $where[] = 'AND ' . db_prefix() . 'dm_portal_work_updates.task_id = ' . (int) $task_id;
        }
        if ($date_from) {
            $where[] = 'AND ' . db_prefix() . "dm_portal_work_updates.work_date >= '" . $this->db->escape_str(to_sql_date($date_from)) . "'";
        }
        if ($date_to) {
            $where[] = 'AND ' . db_prefix() . "dm_portal_work_updates.work_date <= '" . $this->db->escape_str(to_sql_date($date_to)) . "'";
        }
        if ($has_attachments) {
            $where[] = "AND EXISTS (SELECT 1 FROM " . db_prefix() . 'files f WHERE f.rel_id = ' . db_prefix() . "dm_portal_work_updates.id AND f.rel_type = 'dm_work_update')";
        }

        $result = data_tables_init($aColumns, 'id', $sTable, $join, $where);
        $output = $result['output'];

        foreach ($result['rResult'] as $aRow) {
            $id  = (int) $aRow[db_prefix() . 'dm_portal_work_updates.id'];
            $row = [];

            $row[] = '<a href="#" class="daily-work-update-report-row" data-id="' . $id . '">' . e(_d($aRow[db_prefix() . 'dm_portal_work_updates.work_date'])) . '</a>';
            $row[] = e(trim($aRow[db_prefix() . 'staff.firstname'] . ' ' . $aRow[db_prefix() . 'staff.lastname']));
            $row[] = $aRow['departments'] ? e($aRow['departments']) : '-';
            $row[] = $aRow['project_name'] ? e($aRow['project_name']) : '-';
            $row[] = $aRow['task_name'] ? e($aRow['task_name']) : '-';
            $row[] = e(mb_strimwidth((string) $aRow[db_prefix() . 'dm_portal_work_updates.today_work'], 0, 80, '...'));
            $row[] = e(mb_strimwidth((string) $aRow[db_prefix() . 'dm_portal_work_updates.tomorrow_plan'], 0, 80, '...'));
            $row[] = $aRow[db_prefix() . 'dm_portal_work_updates.issues'] ? e(mb_strimwidth((string) $aRow[db_prefix() . 'dm_portal_work_updates.issues'], 0, 80, '...')) : '-';
            $row[] = (int) $aRow['attachment_count'] > 0 ? (int) $aRow['attachment_count'] : '-';
            $row[] = $aRow[db_prefix() . 'dm_portal_work_updates.created_at'] ? e(_dt($aRow[db_prefix() . 'dm_portal_work_updates.created_at'])) : '-';
            $row[] = $aRow[db_prefix() . 'dm_portal_work_updates.updated_at'] ? e(_dt($aRow[db_prefix() . 'dm_portal_work_updates.updated_at'])) : '-';

            $row['DT_RowId'] = 'daily_work_update_report_' . $id;
            $output['aaData'][] = $row;
        }

        echo json_encode($output);
    }

    /**
     * Admin-only detail modal for one submitted Daily Work Update.
     * Reuses get_report_row() (staff/department/project/task already
     * joined, same shape report_table() renders from) and the existing
     * get_attachments() - no separate attachment-listing logic.
     */
    public function report_detail($id)
    {
        if (!is_admin()) {
            ajax_access_denied();
        }

        $row = $this->daily_work_update_model->get_report_row($id);

        if (!$row) {
            echo json_encode(['success' => false]);

            return;
        }

        $data['row']         = $row;
        $data['attachments'] = $this->daily_work_update_model->get_attachments($id);

        echo $this->load->view('report_detail', $data, true);
    }

    /**
     * Correlated subquery for the comma-joined list of this staff
     * member's OWN target-department memberships (a staff member can
     * belong to more than one) - reused identically by both
     * report_table() query shapes above, restricted to
     * daily_work_update_target_departments() so a staff member's
     * membership in some unrelated department never leaks into this
     * report.
     *
     * @param  string $staff_id_column fully-qualified column to correlate against
     * @return string
     */
    private function report_department_subquery($staff_id_column)
    {
        $names = array_map(function ($name) {
            return "'" . $this->db->escape_str($name) . "'";
        }, daily_work_update_target_departments());

        return '(SELECT GROUP_CONCAT(bd.name SEPARATOR ", ") FROM ' . db_prefix() . 'business_department_staff bds JOIN ' . db_prefix() . 'business_departments bd ON bd.id = bds.department_id WHERE bds.staff_id = ' . $staff_id_column . ' AND bd.name IN (' . implode(',', $names) . ')) as departments';
    }
}
