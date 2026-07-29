<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Manager_portal extends AdminController
{
    public function __construct()
    {
        parent::__construct();

        if (!is_manager_portal_member()) {
            access_denied('Manager Portal');
        }

        $this->load->model('manager_portal/manager_portal_model');
        $this->load->model('staff_model');

        // The trailing 'manager_portal' module hint is required, not
        // decorative: App_Lang::load()'s auto-detected module (via
        // CI::$APP->router->fetch_module()) was verified unreliable for
        // some of this controller's AJAX POST actions (task_comment(),
        // specifically - confirmed via direct debugging), silently
        // failing to load this file and leaving _l() to fall back to
        // printing raw keys instead of translated text. Passing the
        // module explicitly bypasses that auto-detection entirely.
        $this->lang->load('manager_portal/manager_portal', 'english', false, true, '', 'manager_portal');
    }

    /**
     * Operations Manager Dashboard - every KPI plus the operational
     * section (DWU awaiting review, absent today, not submitted today,
     * overdue tasks, overdue projects, upcoming deadlines), built
     * entirely from Manager_portal_model - no business logic lives in
     * this controller. Company-wide, no department scoping.
     */
    public function index()
    {
        $staff_id = get_staff_user_id();

        $data['title']            = _l('manager_portal_dashboard');
        $data['summary']          = $this->manager_portal_model->get_dashboard_summary($staff_id);
        $data['operational']      = $this->manager_portal_model->get_dashboard_operational_section($staff_id);
        $data['dwu_review']       = $this->manager_portal_model->get_dwu_review_summary($staff_id);
        $data['attendance_review'] = $this->manager_portal_model->get_attendance_review_summary($staff_id);
        $data['attendance_missing'] = $this->manager_portal_model->get_attendance_missing($staff_id);

        $this->load->model('staff_attendance/staff_attendance_model');
        $data['attendance_requests'] = [
            'pending_leave'        => $this->staff_attendance_model->get_pending_leave_requests_count(),
            'pending_late_arrival' => $this->staff_attendance_model->get_pending_late_arrival_requests_count(),
            'pending_early_exit'   => $this->staff_attendance_model->get_pending_early_exit_requests_count(),
            'on_leave_today'       => $this->staff_attendance_model->get_employees_on_leave_today_count(),
            'upcoming_holidays'    => count($this->staff_attendance_model->get_upcoming_holidays()),
        ];

        $this->load->view('dashboard', $data);
    }

    /**
     * Team List - every active employee company-wide, with the full
     * 11-column rollup (department, designation, attendance, tasks,
     * projects, Daily Work Update status, performance status).
     */
    public function team()
    {
        $data['title']   = _l('manager_portal_team');
        $data['members'] = $this->manager_portal_model->get_team_members(
            $this->manager_portal_model->get_scope_staff_ids()
        );

        $this->load->view('team', $data);
    }

    /**
     * Employee Profile - reuses Staff_model::get() (the same read
     * Staff::profile_view() uses) for basic info, plus
     * Manager_portal_model::get_employee_profile_bundle() for every
     * other section (Attendance Summary/Assigned Projects/Assigned
     * Tasks/Daily Work Updates/Recent Activity/Performance Summary),
     * itself built entirely from existing models (Staff_attendance_model,
     * Daily_work_update_model, tblproject_members/tbltask_assigned/
     * tblproject_activity). Gated by this module's own membership check
     * instead of the native staff_cant('view','staff') capability, which
     * is a different, company-wide-by-design permission this module
     * deliberately never grants (the Operations Manager can view every
     * employee through this page without ever holding native Staff
     * capabilities that would also let them edit/delete/reset passwords -
     * see manager_portal_restrict_native_controller_access()'s own
     * docblock for the full reasoning).
     *
     * @param int $id
     */
    public function team_member($id)
    {
        if (!is_admin() && !in_array((int) $id, $this->manager_portal_model->get_scope_staff_ids())) {
            access_denied('Manager Portal');
        }

        $this->load->model('staff_model');
        $data['staff'] = $this->staff_model->get($id);

        if (!$data['staff']) {
            show_404();
        }

        $data['title']  = _l('manager_portal_team_member');
        $data['bundle'] = $this->manager_portal_model->get_employee_profile_bundle($id);

        $this->load->view('team_member', $data);
    }

    /**
     * Approve / Needs Revision on one Daily Work Update submission, with
     * an optional comment - the one write action this whole module
     * performs against another entity's data, deliberately narrow: it
     * can only ever change review_status/review_comment/reviewed_by/
     * reviewed_at (via Daily_work_update_model::set_review_status(), the
     * single shared write path also intended for the Admin-side reviewer
     * UI in a later phase) - never the submission's own content, never
     * anything on the employee's staff record itself. Ownership-checked
     * the same way team_member() is: the submission's owner must be in
     * this manager's scope, or the caller must be Admin.
     */
    public function dwu_review()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $id      = (int) $this->input->post('id');
        $status  = $this->input->post('status');
        $comment = trim((string) $this->input->post('comment'));

        if (!in_array($status, ['approved', 'needs_revision'], true)) {
            echo json_encode(['success' => false, 'message' => _l('manager_portal_dwu_invalid_status')]);

            return;
        }

        $this->load->model('daily_work_update/daily_work_update_model');
        $submission = $this->daily_work_update_model->get($id);

        if (!$submission) {
            show_404();
        }

        if (!is_admin() && !in_array((int) $submission->staff_id, $this->manager_portal_model->get_scope_staff_ids())) {
            ajax_access_denied();
        }

        $updated = $this->daily_work_update_model->set_review_status($id, $status, $comment, get_staff_user_id());

        if ($updated) {
            // Reuses the same add_notification()/pusher_trigger_notification()
            // pair every other in-app notification in this codebase already
            // uses (see Tasks_model's own @mention/comment notifications) -
            // no new notification system. The submitting employee is
            // notified regardless of whether they're currently online;
            // pusher_trigger_notification() only drives the live badge
            // update for an open session.
            $description_key = $status === 'approved' ? 'not_daily_work_update_approved' : 'not_daily_work_update_needs_revision';
            $notified = add_notification([
                'description'     => $description_key,
                'touserid'        => $submission->staff_id,
                'link'            => 'daily_work_update',
                'additional_data' => serialize([_d($submission->work_date)]),
            ]);

            if ($notified) {
                pusher_trigger_notification([$submission->staff_id]);
            }
        }

        // Same defensive re-merge as task_comment() above - add_notification()
        // triggers hooks (notification_created) that can disturb this
        // module's own loaded language array on some request paths (see
        // task_comment()'s own docblock for the confirmed root cause).
        $this->lang->load('manager_portal/manager_portal', 'english', false, true, '', 'manager_portal');

        echo json_encode([
            'success' => $updated,
            'message' => $updated ? _l('manager_portal_dwu_review_saved') : _l('manager_portal_dwu_review_failed'),
        ]);
    }

    /**
     * Daily Work Updates - every submission company-wide (same
     * get_scope_staff_ids() population as the Dashboard's own DWU
     * figures), filterable via GET params: department_id, employee_id,
     * review_status, date_preset ('today'|'yesterday'|'this_week'),
     * date_from, date_to, plus the Dashboard-only deep-link param
     * missing=1 (renders the "hasn't submitted today" list instead -
     * same duality the native report_table()'s own "Not Submitted Today"
     * branch already uses).
     */
    public function daily_work_updates()
    {
        $staff_id = get_staff_user_id();
        $filters  = [
            'department_id'  => $this->input->get('department_id'),
            'employee_id'    => $this->input->get('employee_id'),
            'review_status'  => $this->input->get('review_status'),
            'date_preset'    => $this->input->get('date_preset'),
            'date_from'      => $this->input->get('date_from'),
            'date_to'        => $this->input->get('date_to'),
            'reviewed_today' => $this->input->get('reviewed_today'),
        ];
        $missing = (bool) $this->input->get('missing');

        $data['title']   = _l('manager_portal_daily_work_updates');
        $data['filters'] = $filters;
        $data['missing'] = $missing;
        $data['options'] = $this->manager_portal_model->get_dwu_filter_options();

        if ($missing) {
            $data['rows'] = $this->manager_portal_model->get_dwu_missing($staff_id, $filters);
        } else {
            $data['rows'] = $this->manager_portal_model->get_dwu_list($staff_id, $filters);
        }

        $this->load->view('daily_work_updates', $data);
    }

    /**
     * Daily Work Update Detail - reuses Daily_work_update_model's own
     * get_report_row()/get_attachments() verbatim, the exact same reads
     * the native Admin report's own detail modal already uses. Ownership
     * gated the same way dwu_review() is.
     *
     * @param int $id
     */
    public function daily_work_update_detail($id)
    {
        $detail = $this->manager_portal_model->get_dwu_detail($id);

        if (!$detail) {
            show_404();
        }

        if (!is_admin() && !in_array((int) $detail['row']->staff_id, $this->manager_portal_model->get_scope_staff_ids())) {
            access_denied('Manager Portal');
        }

        // Working Hours reuses Staff_attendance_model::get_staff_date_record()
        // verbatim - the same per-day attendance row the Monthly Attendance
        // Dashboard already reads, joined here by staff_id + this
        // submission's own work_date rather than duplicating any
        // check-in/check-out query logic.
        $this->load->model('staff_attendance/staff_attendance_model');
        $detail['attendance'] = $this->staff_attendance_model->get_staff_date_record($detail['row']->staff_id, $detail['row']->work_date);

        $data['title']  = _l('manager_portal_daily_work_update_detail');
        $data['detail'] = $detail;

        $this->load->view('daily_work_update_detail', $data);
    }

    /**
     * Attendance - company-wide, filterable via GET params: department_id,
     * employee_id, status ('Present'|'Absent'|'Late'|'Half Day'), date
     * (explicit single date), date_preset ('today'|'yesterday'|
     * 'this_week'|'this_month'), plus the Dashboard-only deep-link param
     * missing=1 (renders "no check-in today" instead - same duality as
     * Projects/Tasks/Daily Work Updates' own missing/overdue deep links).
     */
    public function attendance()
    {
        $staff_id = get_staff_user_id();
        $filters  = [
            'department_id' => $this->input->get('department_id'),
            'employee_id'   => $this->input->get('employee_id'),
            'status'        => $this->input->get('status'),
            'date'          => $this->input->get('date'),
            'date_preset'   => $this->input->get('date_preset'),
        ];
        $missing = (bool) $this->input->get('missing');

        $data['title']   = _l('manager_portal_attendance');
        $data['filters'] = $filters;
        $data['missing'] = $missing;
        $data['options'] = $this->manager_portal_model->get_attendance_filter_options();

        if ($missing) {
            $data['rows'] = $this->manager_portal_model->get_attendance_missing($staff_id);
        } else {
            $data['rows'] = $this->manager_portal_model->get_attendance_list($staff_id, $filters);
        }

        // Attendance Module Enhancement - Leave/Late Arrival/Early Exit
        // review tabs, added to this same Attendance page rather than a
        // new sidebar item, per the feature's own "everything stays
        // inside the Attendance page" requirement. Company-wide (there
        // is only ONE Operations Manager), so these reuse
        // Staff_attendance_model's own review-listing methods directly -
        // no department/team scoping like this page's own Attendance
        // Records tab has.
        $this->load->model('staff_attendance/staff_attendance_model');

        $review_status = $this->input->get('review_status') ?: 'Pending';
        $review_status = $review_status === 'All' ? '' : $review_status;

        $data['review_status']         = $this->input->get('review_status') ?: 'Pending';
        $data['leave_requests']        = $this->staff_attendance_model->get_leave_requests_for_review(['status' => $review_status]);
        $data['late_arrival_requests'] = $this->staff_attendance_model->get_late_arrival_requests_for_review(['status' => $review_status]);
        $data['early_exit_requests']   = $this->staff_attendance_model->get_early_exit_requests_for_review(['status' => $review_status]);
        $data['pending_leave_count']   = $this->staff_attendance_model->get_pending_leave_requests_count();
        $data['pending_late_count']    = $this->staff_attendance_model->get_pending_late_arrival_requests_count();
        $data['pending_early_count']   = $this->staff_attendance_model->get_pending_early_exit_requests_count();

        $this->load->view('attendance', $data);
    }

    /**
     * Approve / Reject a Leave, Late Arrival, or Early Exit request - the
     * one write action this module performs on the new attendance-request
     * tables, deliberately narrow like dwu_review() above: only ever
     * changes status/manager_remarks/reviewed_by/reviewed_at via
     * Staff_attendance_model's own review_*_request() methods (which also
     * enforce "only a currently-Pending request can be decided"), never
     * touches the underlying attendance record itself (spec #8: "The
     * Operations Manager cannot modify attendance records. Only
     * approve/reject requests."). No ownership/scope check is needed
     * here - every request from every department targets this same
     * single Operations Manager, unlike dwu_review()/team_member()'s own
     * department-scoped checks.
     */
    public function review_request()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $type     = $this->input->post('type');
        $id       = (int) $this->input->post('id');
        $decision = $this->input->post('decision');
        $remarks  = trim((string) $this->input->post('remarks'));

        if (!in_array($decision, ['Approved', 'Rejected'], true)) {
            echo json_encode(['success' => false, 'message' => _l('manager_portal_attendance_invalid_decision')]);

            return;
        }

        $this->load->model('staff_attendance/staff_attendance_model');

        $map = [
            'leave'       => ['review_leave_request', 'not_leave_request_approved', 'not_leave_request_rejected'],
            'late_arrival' => ['review_late_arrival_request', 'not_late_arrival_request_approved', 'not_late_arrival_request_rejected'],
            'early_exit'  => ['review_early_exit_request', 'not_early_exit_request_approved', 'not_early_exit_request_rejected'],
        ];

        if (!isset($map[$type])) {
            show_404();
        }

        [$method, $approved_key, $rejected_key] = $map[$type];

        $updated = $this->staff_attendance_model->$method($id, $decision, get_staff_user_id(), $remarks);

        if ($updated) {
            $description_key = $decision === 'Approved' ? $approved_key : $rejected_key;
            $notified = add_notification([
                'description'     => $description_key,
                'touserid'        => $updated->staff_id,
                'link'            => 'staff_attendance',
                'additional_data' => serialize([_d($updated->attendance_date ?? $updated->start_date)]),
            ]);

            if ($notified) {
                pusher_trigger_notification([$updated->staff_id]);
            }

            // Admin <-> Operations Manager cross-notification (Smart
            // Attendance v2 Part 2): "If Operations Manager approves,
            // Admin receives notification." Every active admin, not just
            // one - see attendance_get_admin_staff_ids()'s own docblock.
            $type_label   = ['leave' => 'Leave', 'late_arrival' => 'Late Arrival', 'early_exit' => 'Early Exit'][$type];
            $requester    = $this->staff_model->get($updated->staff_id);
            $admin_ids    = attendance_get_admin_staff_ids();

            foreach ($admin_ids as $admin_id) {
                $admin_notified = add_notification([
                    'description'     => 'not_attendance_request_decision_cross_notify',
                    'touserid'        => $admin_id,
                    'link'            => 'staff_attendance',
                    'additional_data' => serialize([$decision, $type_label, $requester ? ($requester->firstname . ' ' . $requester->lastname) : '', _d($updated->attendance_date ?? $updated->start_date)]),
                ]);

                if ($admin_notified) {
                    pusher_trigger_notification([$admin_id]);
                }
            }
        }

        $this->lang->load('manager_portal/manager_portal', 'english', false, true, '', 'manager_portal');

        echo json_encode([
            'success' => (bool) $updated,
            'message' => $updated ? _l('manager_portal_attendance_review_saved') : _l('manager_portal_attendance_review_failed'),
        ]);
    }

    /**
     * Employee Attendance Detail - Monthly Calendar/Present-Absent-Late
     * Days/Working Hours/Attendance %/Recent Attendance, all built from
     * Manager_portal_model::get_attendance_detail(), itself a thin
     * wrapper around Staff_attendance_model's own get_month_calendar_data()/
     * get_month_summary() (the exact reads the native Monthly Attendance
     * Dashboard fragment already uses). Ownership-gated the same way
     * team_member()/daily_work_update_detail() are.
     *
     * @param int $id
     */
    public function attendance_detail($id)
    {
        if (!is_admin() && !in_array((int) $id, $this->manager_portal_model->get_scope_staff_ids())) {
            access_denied('Manager Portal');
        }

        $year  = (int) ($this->input->get('year') ?: date('Y'));
        $month = (int) ($this->input->get('month') ?: date('n'));

        $detail = $this->manager_portal_model->get_attendance_detail($id, $year, $month);

        if (!$detail['staff']) {
            show_404();
        }

        $data['title']  = _l('manager_portal_attendance_detail');
        $data['detail'] = $detail;
        $data['year']   = $year;
        $data['month']  = $month;

        $this->load->view('attendance_detail', $data);
    }

    /**
     * Projects - every company project, filterable via GET params (all
     * optional): department_id, status, assigned_employee, client_id,
     * employee_id, active ('active'|'completed'), date_from, date_to.
     * Built entirely from Manager_portal_model::get_projects()/
     * get_project_filter_options() - no business logic here, same shape
     * as team().
     */
    public function projects()
    {
        $filters = [
            'department_id'     => $this->input->get('department_id'),
            'status'            => $this->input->get('status'),
            'assigned_employee' => $this->input->get('assigned_employee'),
            'client_id'         => $this->input->get('client_id'),
            'employee_id'       => $this->input->get('employee_id'),
            'active'            => $this->input->get('active'),
            'date_from'         => $this->input->get('date_from'),
            'date_to'           => $this->input->get('date_to'),
        ];

        $data['title']    = _l('manager_portal_projects');
        $data['filters']  = $filters;
        $data['options']  = $this->manager_portal_model->get_project_filter_options();
        $data['projects'] = $this->manager_portal_model->get_projects($filters);

        $this->load->view('projects', $data);
    }

    /**
     * Project Detail - read-only monitoring bundle (Overview/Members/
     * Milestones/Tasks/Files/Discussions/Activity), built from
     * Manager_portal_model::get_project_detail() which itself is a thin
     * wrapper around Projects_model's own bundle methods. No admin-only
     * action (edit/delete/billing/customer/archive) is exposed anywhere
     * in this action or its view - this is Approach B from the Phase 3
     * report: a Manager Portal-owned page that reuses Projects_model
     * directly, rather than unblocking the native admin/projects
     * controller (which has no per-action capability gating this module
     * could safely scope down to read-only).
     *
     * @param int $id
     */
    public function project($id)
    {
        $detail = $this->manager_portal_model->get_project_detail($id);

        if (!$detail) {
            show_404();
        }

        $data['title']  = _l('manager_portal_project_detail');
        $data['detail'] = $detail;

        $this->load->view('project_detail', $data);
    }

    /**
     * Tasks - every company task, filterable via GET params (all
     * optional): department_id, employee_id, status, priority,
     * project_id, date_from, date_to, plus the Dashboard-only deep-link
     * params active/pending/overdue/completed_today (see
     * Manager_portal_model::get_tasks()'s own docblock) - not rendered as
     * filter-bar controls, only ever arrive via a KPI card href.
     */
    public function tasks()
    {
        $filters = [
            'department_id'    => $this->input->get('department_id'),
            'employee_id'      => $this->input->get('employee_id'),
            'status'           => $this->input->get('status'),
            'priority'         => $this->input->get('priority'),
            'project_id'       => $this->input->get('project_id'),
            'date_from'        => $this->input->get('date_from'),
            'date_to'          => $this->input->get('date_to'),
            'active'           => $this->input->get('active'),
            'pending'          => $this->input->get('pending'),
            'overdue'          => $this->input->get('overdue'),
            'completed_today'  => $this->input->get('completed_today'),
        ];

        $data['title']   = _l('manager_portal_tasks');
        $data['filters'] = $filters;
        $data['options'] = $this->manager_portal_model->get_task_filter_options();
        $data['tasks']   = $this->manager_portal_model->get_tasks($filters);

        $this->load->view('tasks', $data);
    }

    /**
     * Task Detail - reuses Tasks_model::get($id) verbatim (description/
     * assignee/priority/status/progress/comments/checklist/attachments/
     * activity all included already). The view exposes exactly two write
     * actions (task_comment(), task_note() below) and nothing else -
     * delete/reassign/change-project/change-customer are never rendered.
     *
     * @param int $id
     */
    public function task($id)
    {
        $task = $this->manager_portal_model->get_task_detail($id);

        if (!$task) {
            show_404();
        }

        $data['title'] = _l('manager_portal_task_detail');
        $data['task']  = $task;

        $this->load->view('task_detail', $data);
    }

    /**
     * Add a Task Comment - the one public-thread write this module
     * performs, delegated straight to Tasks_model::add_task_comment(),
     * the same insert path the native task modal's own comment box uses.
     */
    public function task_comment()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $taskid  = (int) $this->input->post('taskid');
        $content = trim((string) $this->input->post('content'));

        $this->load->model('tasks_model');
        $task = $this->tasks_model->get($taskid);

        if (!$task) {
            show_404();
        }

        if ($content === '') {
            echo json_encode(['success' => false, 'message' => _l('manager_portal_task_comment_empty')]);

            return;
        }

        $this->tasks_model->add_task_comment(['taskid' => $taskid, 'content' => $content]);

        // add_task_comment() triggers the native @mention/assignee
        // notification pipeline, which reloads $this->lang for its own
        // notification/email language context - confirmed via live
        // debugging to leave this module's own merged translations gone
        // by the time we get back here. Re-merging them in is cheap and
        // makes the response message reliable regardless of what that
        // pipeline did internally.
        $this->lang->load('manager_portal/manager_portal', 'english', false, true, '', 'manager_portal');

        echo json_encode(['success' => true, 'message' => _l('manager_portal_task_comment_saved')]);
    }

    /**
     * Set the Manager Note - the one private, Manager-only field this
     * module may write on a task (manager_note, migration 377). Never
     * touches assignee/project/customer/status.
     */
    public function task_note()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $taskid = (int) $this->input->post('taskid');
        $note   = trim((string) $this->input->post('note'));

        $this->load->model('tasks_model');
        $task = $this->tasks_model->get($taskid);

        if (!$task) {
            show_404();
        }

        $updated = $this->manager_portal_model->set_task_manager_note($taskid, $note);

        echo json_encode([
            'success' => $updated,
            'message' => $updated ? _l('manager_portal_task_note_saved') : _l('manager_portal_task_note_failed'),
        ]);
    }

    /**
     * Employee Productivity Report - the default landing page under
     * Reports. Filterable via GET: department_id, employee_id, date_from
     * (picks the target month - see get_employee_productivity_report()'s
     * own docblock). Filter options reuse
     * get_attendance_filter_options() verbatim (Phase 5) - same
     * Department/Employee lists, no duplicate filter-option query.
     */
    public function reports()
    {
        $staff_id = get_staff_user_id();
        $filters  = [
            'department_id' => $this->input->get('department_id'),
            'employee_id'   => $this->input->get('employee_id'),
            'date_from'     => $this->input->get('date_from'),
        ];

        $data['title']   = _l('manager_portal_reports_productivity');
        $data['filters'] = $filters;
        $data['options'] = $this->manager_portal_model->get_attendance_filter_options();
        $data['rows']    = $this->manager_portal_model->get_employee_productivity_report($staff_id, $filters);

        $this->load->view('reports', $data);
    }

    /**
     * Department Performance Report - filterable via GET: department_id.
     */
    public function reports_departments()
    {
        $filters = ['department_id' => $this->input->get('department_id')];

        $this->load->model('business_departments/business_departments_model');

        $data['title']       = _l('manager_portal_reports_departments');
        $data['filters']     = $filters;
        $data['departments'] = $this->business_departments_model->get();
        $data['rows']        = $this->manager_portal_model->get_department_performance_report($filters, $data['departments']);

        $this->load->view('reports_departments', $data);
    }

    /**
     * Project Performance Report - a thin wrapper reusing Projects'
     * own filter set (department_id, status, active) via
     * get_project_filter_options()/get_project_performance_report(),
     * same options Phase 3's Projects page already exposes.
     */
    public function reports_projects()
    {
        $filters = [
            'department_id' => $this->input->get('department_id'),
            'status'        => $this->input->get('status'),
            'active'        => $this->input->get('active'),
        ];

        $data['title']   = _l('manager_portal_reports_projects');
        $data['filters'] = $filters;
        $data['options'] = $this->manager_portal_model->get_project_filter_options();
        $data['rows']    = $this->manager_portal_model->get_project_performance_report($filters);

        $this->load->view('reports_projects', $data);
    }

    /**
     * Attendance Analytics - Present vs Absent/Late Arrivals/Trend/
     * Monthly Attendance % by department, all built from
     * Manager_portal_model::get_attendance_analytics().
     */
    public function reports_attendance_analytics()
    {
        $data['title']    = _l('manager_portal_reports_attendance_analytics');
        $data['analytics'] = $this->manager_portal_model->get_attendance_analytics(get_staff_user_id());

        $this->load->view('reports_attendance_analytics', $data);
    }

    /**
     * Daily Work Update Analytics - Submitted/Pending/Approved/Needs
     * Revision + department-wise summary.
     */
    public function reports_dwu_analytics()
    {
        $data['title']    = _l('manager_portal_reports_dwu_analytics');
        $data['analytics'] = $this->manager_portal_model->get_dwu_analytics(get_staff_user_id());

        $this->load->view('reports_dwu_analytics', $data);
    }

    /**
     * Task Analytics - Completed/Pending/Overdue + Priority Distribution.
     */
    public function reports_task_analytics()
    {
        $data['title']    = _l('manager_portal_reports_task_analytics');
        $data['analytics'] = $this->manager_portal_model->get_task_analytics(get_staff_user_id());

        $this->load->view('reports_task_analytics', $data);
    }

    /**
     * Project Analytics - Project Status/Department Distribution/
     * Completion Trend. Company-wide, no per-manager staff scoping (same
     * as the Projects page itself).
     */
    public function reports_project_analytics()
    {
        $data['title']    = _l('manager_portal_reports_project_analytics');
        $data['analytics'] = $this->manager_portal_model->get_project_analytics();

        $this->load->view('reports_project_analytics', $data);
    }
}
