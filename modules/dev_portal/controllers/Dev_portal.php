<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Development Portal - Dashboard, My Projects, My Tasks (Phase 1). One
 * controller for the whole module, same convention as
 * modules/follow_up_management and modules/staff_attendance - Admin vs.
 * portal-staff scoping is enforced server-side in every table query via
 * Dev_portal_model::get_my_projects_where()/get_my_tasks_where(), not
 * just hidden UI.
 */
class Dev_portal extends AdminController
{
    public function __construct()
    {
        parent::__construct();

        if (!is_dev_portal_member()) {
            access_denied('Development Portal');
        }

        $this->load->model('dev_portal/dev_portal_model');
        $this->load->model('dev_portal/dev_worklog_model');
        $this->load->model('tasks_model');
        $this->load->model('projects_model');
        $this->lang->load('dev_portal/dev_portal', 'english');
    }

    /**
     * Shared authorization check for every Project Workspace action -
     * Admin or a real tblproject_members row (Projects_model::is_member(),
     * the exact same check Projects::view() itself uses), never trusting
     * the client. Every workspace action below calls this before doing
     * anything else, so direct URL access to another employee's project
     * (or to a project belonging to a department outside this portal)
     * is denied identically everywhere, not re-implemented per action.
     *
     * @param  int  $project_id
     * @param  bool $is_ajax
     * @return void dies via access_denied()/ajax_access_denied() if unauthorized
     */
    private function authorize_project_access($project_id, $is_ajax = false)
    {
        if (is_admin() || $this->projects_model->is_member($project_id)) {
            return;
        }

        if ($is_ajax) {
            ajax_access_denied();
        }

        access_denied('Development Portal');
    }

    /**
     * Finds (or creates, on first use) the single shared discussion
     * thread this Workspace's "Comments" section posts to - reuses
     * Perfex's real Discussions machinery (tblprojectdiscussions/
     * tblprojectdiscussioncomments, Projects_model::add_discussion()/
     * add_discussion_comment()) rather than a new comments table. A
     * fixed marker subject identifies "the" Development Portal thread
     * for a project so repeated calls reuse the same thread instead of
     * creating a new one every time; Admin sees this exact same thread
     * under the native Projects -> Discussions tab, since it's the same
     * underlying data.
     *
     * @param  int $project_id
     * @return int discussion id
     */
    private function get_or_create_workspace_discussion($project_id)
    {
        $marker = 'Development Portal';

        foreach ($this->projects_model->get_discussions($project_id) as $discussion) {
            if ($discussion['subject'] === $marker) {
                return (int) $discussion['id'];
            }
        }

        $this->projects_model->add_discussion([
            'project_id'       => $project_id,
            'subject'          => $marker,
            'description'      => 'Development Portal comment thread.',
            'show_to_customer' => 0,
        ]);

        foreach ($this->projects_model->get_discussions($project_id) as $discussion) {
            if ($discussion['subject'] === $marker) {
                return (int) $discussion['id'];
            }
        }

        return 0;
    }

    public function index()
    {
        $this->dashboard();
    }

    /**
     * Today's Tasks / Assigned Projects / Completed Tasks / Upcoming
     * Deadlines from Dev_portal_model::get_dashboard_summary(); Today's
     * Attendance reuses Staff_attendance_model directly rather than a
     * wrapper - Admin gets the cross-department summary
     * (get_today_summary(), already built for that module's own
     * dashboard widget), a portal staff member gets just their own
     * status for today.
     */
    public function dashboard()
    {
        $staff_id = get_staff_user_id();
        $is_admin = is_admin();

        $data['title']   = _l('dev_portal_dashboard');
        $data['summary'] = $this->dev_portal_model->get_dashboard_summary($staff_id, $is_admin);

        $this->load->model('staff_attendance/staff_attendance_model');

        if ($is_admin) {
            $data['attendance_summary'] = $this->staff_attendance_model->get_today_summary();
            $data['attendance_is_admin'] = true;
        } else {
            $today_record = $this->staff_attendance_model->get_staff_date_record($staff_id);
            $data['attendance_summary']  = $today_record ? $today_record->attendance_status : null;
            $data['attendance_is_admin'] = false;
        }

        $this->load->view('dashboard', $data);
    }

    public function my_projects()
    {
        $data['title'] = _l('dev_portal_my_projects');
        $this->load->view('my_projects', $data);
    }

    /**
     * My Projects DataTable source - self-contained (query built here
     * directly, same style as modules/staff_attendance's own table()
     * method) rather than a separate file under
     * application/views/admin/tables/, keeping this module fully
     * contained.
     *
     * Column order note: $aColumns lists the 7 real, 1:1 SQL columns in
     * the exact order they're rendered (Project Name/Client/Project
     * Type/Assigned Date/Due Date/Status/Progress). Priority has no real
     * column (see get_my_projects_priority() below) and is appended
     * after the loop, same "derived columns go last" convention already
     * used in application/views/admin/tables/my_follow_ups.php.
     */
    public function my_projects_table()
    {
        if (!$this->input->is_ajax_request()) {
            return;
        }

        $staff_id = get_staff_user_id();
        $is_admin = is_admin();

        $aColumns = [
            db_prefix() . 'projects.name',
            db_prefix() . 'clients.company',
            db_prefix() . 'service_types.name as service_type_name',
            db_prefix() . 'projects.project_created',
            db_prefix() . 'projects.deadline',
            db_prefix() . 'projects.status',
            db_prefix() . 'projects.progress',
            // Latest note - the single-value cache on tblprojects that every
            // note write keeps synced from tblproject_notes (see
            // Projects_model::_refresh_latest_note_cache()), so this column
            // shows the same latest note as the Admin Projects list.
            db_prefix() . 'projects.status_description as latest_note',
        ];

        $sIndexColumn = 'id';
        $sTable       = db_prefix() . 'projects';

        $join = [
            'LEFT JOIN ' . db_prefix() . 'clients ON ' . db_prefix() . 'clients.userid = ' . db_prefix() . 'projects.clientid',
            'LEFT JOIN ' . db_prefix() . 'service_types ON ' . db_prefix() . 'service_types.id = ' . db_prefix() . 'projects.service_type_id',
        ];

        $where = [
            'AND ' . $this->dev_portal_model->get_my_projects_where($staff_id, $is_admin),
        ];

        $result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, [
            db_prefix() . 'projects.id',
            // progress_from_tasks - raw flag only (no visible column),
            // consumed by Projects_model::resolve_progress_value() below
            // so this table shows the SAME effective progress as the Admin
            // Projects list and the Customer Portal (single resolver).
            db_prefix() . 'projects.progress_from_tasks',
            // Priority - the highest priority among this project's own
            // tasks (tbltasks.rel_type='project'), since tblprojects has
            // no priority column of its own - reuses the exact
            // priority scale/colors Tasks already defines
            // (get_tasks_priorities()/task_priority()/task_priority_color()
            // in application/helpers/tasks_helper.php), not a new one.
            '(SELECT MAX(t.priority) FROM ' . db_prefix() . "tasks t WHERE t.rel_type = 'project' AND t.rel_id = " . db_prefix() . 'projects.id) as derived_priority',
            // Assigned Team - every staff member on tblproject_members for
            // this project, comma-joined. No new column/table - reuses the
            // same membership table authorize_project_access() already
            // checks via Projects_model::is_member().
            '(SELECT GROUP_CONCAT(CONCAT(s.firstname, " ", s.lastname) SEPARATOR ", ") FROM ' . db_prefix() . 'project_members pm JOIN ' . db_prefix() . 'staff s ON s.staffid = pm.staff_id WHERE pm.project_id = ' . db_prefix() . 'projects.id) as assigned_team',
        ]);

        $output  = $result['output'];
        $rResult = $result['rResult'];

        foreach ($rResult as $aRow) {
            $row = [];

            // Links to this portal's own Project Workspace
            // (dev_portal/project/<id>), not the native admin project
            // page - the Workspace is a secure, member-scoped interface
            // onto the same tblprojects row, not a second project page.
            $row[] = '<a href="' . admin_url('dev_portal/project/' . $aRow['id']) . '" class="tw-font-medium">' . e($aRow[db_prefix() . 'projects.name']) . '</a>';
            $row[] = $aRow[db_prefix() . 'clients.company'] ? e($aRow[db_prefix() . 'clients.company']) : '-';
            $row[] = $aRow['service_type_name'] ? e($aRow['service_type_name']) : '-';
            $row[] = $aRow[db_prefix() . 'projects.project_created'] ? e(_d($aRow[db_prefix() . 'projects.project_created'])) : '-';
            $row[] = $aRow[db_prefix() . 'projects.deadline'] ? e(_d($aRow[db_prefix() . 'projects.deadline'])) : '-';

            // get_project_status_by_id() is a global helper
            // (application/helpers/projects_helper.php), not a
            // Projects_model method - calling it as $this->projects_model->...
            // was a fatal "call to undefined method" that broke this
            // endpoint's JSON response entirely (DataTables then hangs on
            // "Processing" forever, since it never gets valid JSON back).
            $status = get_project_status_by_id($aRow[db_prefix() . 'projects.status']);
            $row[]  = '<span class="label" style="color:' . $status['color'] . ';border:1px solid ' . adjust_hex_brightness($status['color'], 0.4) . ';background:' . adjust_hex_brightness($status['color'], 0.04) . ';">' . e($status['name']) . '</span>';

            // Single shared resolver (Projects_model::resolve_progress_value())
            // - the exact same effective-progress rule the Admin Projects
            // list and the Customer Portal use. Reads the raw stored fields
            // from this row (status / progress / progress_from_tasks) and
            // returns the same percentage every other panel shows, so a
            // manual progress write (which also clears
            // progress_from_tasks) or a status change via mark_as() both
            // land here instantly.
            $progressValue = $this->projects_model->resolve_progress_value([
                'id'                  => $aRow['id'],
                'status'              => $aRow[db_prefix() . 'projects.status'],
                'progress'            => $aRow[db_prefix() . 'projects.progress'],
                'progress_from_tasks' => $aRow['progress_from_tasks'],
            ]);
            $row[]    = '<div class="progress" style="margin-bottom:0;"><div class="progress-bar" role="progressbar" style="width:' . $progressValue . '%;" aria-valuenow="' . $progressValue . '" aria-valuemin="0" aria-valuemax="100">' . $progressValue . '%</div></div>';

            // Latest Note - compact truncated cell (full text in tooltip),
            // same convention as the Admin Projects list's Note column.
            $row[] = $aRow['latest_note'] != '' ? '<span data-toggle="tooltip" data-title="' . e($aRow['latest_note']) . '">' . e(mb_strimwidth($aRow['latest_note'], 0, 40, '...')) . '</span>' : '-';

            if (!empty($aRow['derived_priority'])) {
                $row[] = '<span class="label" style="color:' . task_priority_color($aRow['derived_priority']) . ';">' . e(task_priority($aRow['derived_priority'])) . '</span>';
            } else {
                $row[] = '-';
            }

            $row[] = $aRow['assigned_team'] ? e($aRow['assigned_team']) : '-';

            // Open - explicit action button into the Workspace, in
            // addition to the Project Name link above (same URL either
            // way) - derived, appended last, same convention as Priority
            // above.
            $row[] = '<a href="' . admin_url('dev_portal/project/' . $aRow['id']) . '" class="btn btn-default btn-sm">' . _l('dev_portal_open') . '</a>';

            $row['DT_RowId'] = 'dev_portal_project_' . $aRow['id'];
            $output['aaData'][] = $row;
        }

        echo json_encode($output);
    }

    /**
     * Dashboard-card deep-link filters (?filter=today, ?status=completed) -
     * read once here so the view can show an active-filter badge, and
     * again in my_tasks_table() (a separate AJAX request - see that
     * method's own note) so the DataTable itself opens pre-filtered.
     * Both call sites read the same two GET params, never a posted/
     * trusted value, and only ever narrow further within
     * get_my_tasks_where()'s own staff scoping - never widen it.
     */
    public function my_tasks()
    {
        $data['title']  = _l('dev_portal_my_tasks');
        $data['filter'] = $this->input->get('filter');
        $data['status']  = $this->input->get('status');
        $this->load->view('my_tasks', $data);
    }

    /**
     * My Tasks DataTable source - same self-contained convention as
     * my_projects_table() above. $aColumns' 6 real columns (Task/
     * Priority/Status/Due Date/Estimated Hours match render order;
     * Project is rendered second (per the required column order) but has
     * no 1:1 column on tbltasks itself - a real LEFT JOIN to tblprojects
     * (rel_id/rel_type='project') puts it in $aColumns at the correct
     * visual index instead of resolving it via additionalSelect, exactly
     * the "real sortable proxy column at the correct position" pattern
     * application/views/admin/tables/my_follow_ups.php already
     * established (staff.firstname there) - the alternative (a
     * correlated subquery in additionalSelect, appended after the loop)
     * would silently break sort/search on every column after it, since
     * Project isn't the last rendered column here. Hours Worked has no
     * such option (it's a SUM aggregate, not a single joinable column)
     * so it stays a derived, appended-last column.
     */
    public function my_tasks_table()
    {
        if (!$this->input->is_ajax_request()) {
            return;
        }

        $staff_id = get_staff_user_id();
        $is_admin = is_admin();

        $aColumns = [
            db_prefix() . 'tasks.name',
            db_prefix() . 'projects.name as project_name',
            db_prefix() . 'tasks.priority',
            db_prefix() . 'tasks.status',
            db_prefix() . 'tasks.duedate',
            db_prefix() . 'tasks.estimated_hours',
        ];

        $sIndexColumn = 'id';
        $sTable       = db_prefix() . 'tasks';

        // rel_type isn't always 'project' (a task can relate to other
        // entities) - the join condition itself, not just the WHERE
        // clause, guards that so non-project tasks simply get a NULL
        // project_name rather than being excluded.
        $join = [
            'LEFT JOIN ' . db_prefix() . 'projects ON ' . db_prefix() . 'projects.id = ' . db_prefix() . "tasks.rel_id AND " . db_prefix() . "tasks.rel_type = 'project'",
        ];

        $where = [
            'AND ' . $this->dev_portal_model->get_my_tasks_where($staff_id, $is_admin),
        ];

        // Dashboard-card deep-link filters - narrow WITHIN the staff scoping
        // above, never instead of it. This is a separate AJAX request from
        // my_tasks() above (DataTables' own fetch), so the GET params are
        // read again here directly from this request's own query string
        // (my_tasks.php's JS appends location.search to the AJAX URL).
        if ($this->input->get('filter') === 'today') {
            $where[] = 'AND ' . db_prefix() . "tasks.duedate = '" . date('Y-m-d') . "'";
            $where[] = 'AND ' . db_prefix() . 'tasks.status != ' . Tasks_model::STATUS_COMPLETE;
        }

        if ($this->input->get('status') === 'completed') {
            $where[] = 'AND ' . db_prefix() . 'tasks.status = ' . Tasks_model::STATUS_COMPLETE;
        }

        $result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, [
            db_prefix() . 'tasks.id',
            // Hours Worked - same SUM(CASE end_time IS NULL ...) expression
            // application/helpers/tasks_helper.php's own
            // get_sql_calc_task_logged_time() already uses for a single
            // task, restructured as a correlated subquery (that helper
            // takes one fixed $task_id and can't run per-row in a
            // listing) - same business logic, not reinvented.
            '(SELECT SUM(CASE WHEN end_time IS NULL THEN ' . time() . '-start_time ELSE end_time-start_time END) FROM ' . db_prefix() . 'taskstimers WHERE task_id = ' . db_prefix() . 'tasks.id) as logged_seconds',
            // Latest Note - same correlated-subquery convention the Admin
            // Tasks list's own Latest Note column uses (no cache column on
            // tbltasks - see migration 385's docblock).
            '(SELECT content FROM ' . db_prefix() . 'task_notes WHERE task_id = ' . db_prefix() . 'tasks.id ORDER BY dateadded DESC, id DESC LIMIT 1) as latest_note',
        ]);

        $output  = $result['output'];
        $rResult = $result['rResult'];

        foreach ($rResult as $aRow) {
            $row = [];

            // Links to the Task Workspace (own page, matching how the My
            // Projects table links to project_workspace) instead of the
            // generic admin init_task_modal() popup - Description and
            // Task Notes both live there in the clean, staff-facing
            // layout this module already established for Projects.
            $row[] = '<a href="' . admin_url('dev_portal/task_workspace/' . (int) $aRow['id']) . '" class="tw-font-medium">' . e($aRow[db_prefix() . 'tasks.name']) . '</a>';
            $row[] = $aRow['project_name'] ? e($aRow['project_name']) : '-';
            $row[] = '<span class="label" style="color:' . task_priority_color($aRow[db_prefix() . 'tasks.priority']) . ';">' . e(task_priority($aRow[db_prefix() . 'tasks.priority'])) . '</span>';
            $row[] = format_task_status($aRow[db_prefix() . 'tasks.status']);
            $row[] = $aRow[db_prefix() . 'tasks.duedate'] ? e(_d($aRow[db_prefix() . 'tasks.duedate'])) : '-';
            $row[] = $aRow[db_prefix() . 'tasks.estimated_hours'] !== null ? e($aRow[db_prefix() . 'tasks.estimated_hours']) . 'h' : '-';

            // seconds_to_time_format() (application/helpers/func_helper.php) -
            // the exact same helper Perfex's own task timesheet views use
            // to render tbltaskstimers totals, not a new formatter.
            $logged_seconds = (int) $aRow['logged_seconds'];
            $row[] = $logged_seconds > 0 ? e(seconds_to_time_format($logged_seconds)) : '-';

            $row[] = $aRow['latest_note'] ? '<span data-toggle="tooltip" data-title="' . e($aRow['latest_note']) . '">' . e(mb_strimwidth($aRow['latest_note'], 0, 40, '...')) . '</span>' : '-';

            $row['DT_RowId'] = 'dev_portal_task_' . $aRow['id'];
            $output['aaData'][] = $row;
        }

        echo json_encode($output);
    }

    /**
     * Shared authorization check for the Task Workspace - Admin or an
     * actual assignee (Tasks_model::is_task_assignee(), the exact same
     * table/relationship get_my_tasks_where() already scopes "My Tasks"
     * by), never trusting the client. Deliberately narrower than the
     * admin side's own get_tasks_where_string() (which also allows
     * followers/creators/public tasks) - a task that isn't in this
     * staff member's own My Tasks list (assignee-scoped only) must not
     * be openable here either, by URL guess or otherwise.
     *
     * @param  int  $task_id
     * @param  bool $is_ajax
     * @return void dies via access_denied()/ajax_access_denied() if unauthorized
     */
    private function authorize_task_access($task_id, $is_ajax = false)
    {
        if (is_admin() || $this->tasks_model->is_task_assignee(get_staff_user_id(), $task_id)) {
            return;
        }

        if ($is_ajax) {
            ajax_access_denied();
        }

        access_denied('Development Portal');
    }

    /**
     * Task Workspace - a secure, assignee-scoped view onto the SAME task
     * record the Admin Tasks list and task modal already store and
     * manage (tbltasks/tbltask_assigned/tbltask_notes - no second task
     * system, no duplicated data). Deliberately its own clean page
     * (Task Name / Description / Task Information / Notes) rather than
     * the generic, comment/checklist/timer-heavy admin task modal - same
     * "simplified staff-facing view over the same data" relationship
     * project_workspace() already has to the Admin Project edit page.
     */
    public function task_workspace($id)
    {
        $this->authorize_task_access($id);

        $task = $this->tasks_model->get($id);

        if (!$task) {
            blank_page(_l('task_not_found'));
        }

        $data['title']      = $task->name;
        $data['task']       = $task;
        // Shared task notes (newest first) - the SAME store the Admin
        // Tasks list's Latest Note column and the admin task modal's
        // Notes panel read (tbltask_notes); get_staff_notes() without a
        // staff filter so every note is visible, not just personal ones.
        $data['task_notes'] = $this->tasks_model->get_staff_notes($id);

        $data['customer_name'] = null;
        $data['project_name']  = null;
        if ($task->rel_type == 'project' && $task->project_data) {
            $data['project_name']  = $task->project_data->name;
            $data['customer_name'] = get_company_name($task->project_data->clientid);
        }

        $this->load->view('task_workspace', $data);
    }

    /**
     * Add a shared Task Note from the Workspace. Writes into the SAME
     * store the admin side (Tasks::add_task_note()) writes into
     * (Tasks_model::save_note() -> tbltask_notes) - one note system,
     * both sides. Gated by authorize_task_access() (admin or a real
     * tbltask_assigned row), never a posted staff id.
     */
    public function task_add_note($id)
    {
        $this->authorize_task_access($id, true);

        $note = trim((string) $this->input->post('note'));

        if ($note === '') {
            echo json_encode(['success' => false, 'message' => _l('task_notes_enter_note')]);

            return;
        }

        $inserted = $this->tasks_model->save_note(['title' => null, 'content' => $note], $id);

        echo json_encode([
            'success'  => (bool) $inserted,
            'author'   => get_staff_full_name(),
            'when'     => _dt(date('Y-m-d H:i:s')),
            'when_ago' => time_ago(date('Y-m-d H:i:s')),
        ]);
    }

    /**
     * Project Workspace - a secure, member-scoped interface onto the
     * SAME project the native admin Projects module already stores and
     * manages (tblprojects/tblproject_members/tblprojectdiscussions/
     * tblproject_files - no second project system, no duplicated data).
     * authorize_project_access() is the only thing standing between a
     * direct URL guess and someone else's project - it is checked first,
     * before anything else in this action.
     */
    public function project($id)
    {
        $this->authorize_project_access($id);

        $project = $this->projects_model->get($id);

        if (!$project) {
            blank_page(_l('project_not_found'));
        }

        $data['title']           = $project->name;
        $data['project']         = $project;
        // Shared project notes (newest first) - the SAME store the Admin
        // Projects list's Note column and the admin Notes tab read
        // (tblproject_notes); get_staff_notes() without a staff filter so
        // every team note is visible, not just personal ones.
        $data['project_notes']   = $this->projects_model->get_staff_notes($id);
        // Effective progress via the SAME single resolver every other
        // panel uses (projects_model->resolve_progress_value()) - the
        // workspace's bar and update dropdown must never disagree with
        // the Admin Projects list or the Customer Portal.
        $data['effective_progress'] = $this->projects_model->resolve_progress_value($project);
        $data['status_options']  = dev_portal_project_status_options();
        $data['status_label']    = dev_portal_project_status_label($project->status);
        $data['is_cancelled']    = (int) $project->status === 5;
        $data['department_name'] = get_business_department_name($project->department);
        $data['work_logs']       = $this->dev_worklog_model->get_for_project($id);
        $data['files']           = $this->projects_model->get_files($id);

        $discussion_id       = $this->get_or_create_workspace_discussion($id);
        $data['discussion_id'] = $discussion_id;
        $data['comments']       = $discussion_id ? $this->projects_model->get_discussion_comments($discussion_id, 'regular') : [];

        $this->load->view('project_workspace', $data);
    }

    /**
     * Save Changes - the ONE Project Workspace endpoint for Progress +
     * Status + Note, replacing the three separate actions this used to be
     * (project_update_progress()/project_update_status()/project_add_note(),
     * now removed) so the UI has a single Save button instead of three.
     * Deliberately still built on the exact same model methods those three
     * actions called - Projects_model::update_assignment_field()/mark_as()/
     * save_note() - so the Admin <-> Customer Portal <-> Staff Portal
     * synchronization those methods already provide is completely
     * unchanged; this consolidates the endpoint, not the underlying write
     * path.
     *
     * Each of the three pieces is posted independently (progress/status_id/
     * note are all optional per-request) and is only actually written when
     * BOTH posted AND different from the project's current stored value:
     *   - Progress is compared against resolve_progress_value() (the same
     *     effective-progress resolver the whole app already uses), so
     *     re-saving an unchanged dropdown value never flips the project off
     *     task-derived progress (update_assignment_field()'s 'progress'
     *     branch always clears progress_from_tasks, so it must only run on
     *     a genuine change).
     *   - Status is compared against the project's raw status column
     *     before calling mark_as(), because mark_as() unconditionally logs
     *     activity/fires notifications/hooks whenever its UPDATE reports
     *     affected_rows() > 0 - which it always would here, since it also
     *     always bumps last_updated. Without this guard, clicking Save
     *     Changes for a Progress-only or Note-only edit would spuriously
     *     re-fire "project status updated" activity/notifications every
     *     time.
     *   - Note is only saved when non-empty after trim(), exactly like the
     *     old project_add_note() - never writes an empty note.
     *
     * If none of the three actually changed, no model write happens at
     * all and the response still reports success (nothing to save is not
     * an error).
     */
    public function project_save_changes($id)
    {
        $this->authorize_project_access($id, true);

        $project = $this->projects_model->get($id);

        if (!$project) {
            echo json_encode(['success' => false, 'message' => _l('project_not_found')]);

            return;
        }

        $progress_changed = false;
        $status_changed   = false;
        $note_saved       = false;

        $posted_progress = $this->input->post('progress');

        if ($posted_progress !== null && $posted_progress !== '') {
            if (!is_numeric($posted_progress) || !in_array((int) $posted_progress, get_progress_options(), true)) {
                echo json_encode(['success' => false, 'message' => _l('dev_portal_invalid_progress')]);

                return;
            }

            $current_progress = (int) $this->projects_model->resolve_progress_value($project);

            if ((int) $posted_progress !== $current_progress) {
                $this->projects_model->update_assignment_field($id, 'progress', (int) $posted_progress);
                $progress_changed = true;
            }
        }

        $posted_status = $this->input->post('status_id');

        if ($posted_status !== null && $posted_status !== '') {
            $status_id = (int) $posted_status;

            if (!array_key_exists($status_id, dev_portal_project_status_options())) {
                echo json_encode(['success' => false, 'message' => _l('dev_portal_invalid_status')]);

                return;
            }

            if ($status_id !== (int) $project->status) {
                $this->projects_model->mark_as([
                    'project_id' => $id,
                    'status_id'  => $status_id,
                ]);
                $status_changed = true;
            }
        }

        $note      = trim((string) $this->input->post('note'));
        $note_data = null;

        if ($note !== '') {
            $note_saved = (bool) $this->projects_model->save_note(['title' => null, 'content' => $note], $id);

            if ($note_saved) {
                $note_data = [
                    'author'   => get_staff_full_name(),
                    'when'     => _dt(date('Y-m-d H:i:s')),
                    'when_ago' => time_ago(date('Y-m-d H:i:s')),
                    'content'  => $note,
                ];
            }
        }

        echo json_encode([
            'success'          => true,
            'progress_changed' => $progress_changed,
            'status_changed'   => $status_changed,
            'note_saved'       => $note_saved,
            'note'             => $note_data,
        ]);
    }

    /**
     * Daily Work Update - the one genuinely new piece of data this
     * feature introduces (Dev_worklog_model, tbldev_portal_work_logs).
     * Every entry is tied to project_id + the CURRENT staff id (never a
     * posted staff id), so a member can only ever log work against
     * themselves.
     */
    public function project_add_worklog($id)
    {
        $this->authorize_project_access($id, true);

        $work_date      = $this->input->post('work_date');
        $work_performed = trim((string) $this->input->post('work_performed'));

        if (!$work_date || $work_performed === '') {
            echo json_encode(['success' => false, 'message' => _l('dev_portal_worklog_missing_fields')]);

            return;
        }

        $this->dev_worklog_model->add([
            'project_id'     => $id,
            'staff_id'       => get_staff_user_id(),
            'work_date'      => to_sql_date($work_date),
            'work_performed' => $work_performed,
            'hours_worked'   => $this->input->post('hours_worked'),
            'remarks'        => $this->input->post('remarks'),
        ]);

        echo json_encode(['success' => true]);
    }

    /**
     * File upload - reuses the exact native helper
     * handle_project_file_uploads() (application/helpers/upload_helper.php),
     * the same one Projects::upload_file() calls, so files land in
     * tblproject_files identically to an admin-uploaded file. The native
     * controller action has no permission gate at all; this one adds the
     * membership check that was missing.
     */
    public function project_upload_file($id)
    {
        $this->authorize_project_access($id, true);

        handle_project_file_uploads($id);

        echo json_encode(['success' => true]);
    }

    /**
     * Comments - posts into the shared Workspace discussion thread via
     * the native Projects_model::add_discussion_comment(), so Admin/
     * Manager see it (and can reply) through the ordinary Projects ->
     * Discussions tab - no separate comment system.
     */
    public function project_add_comment($id)
    {
        $this->authorize_project_access($id, true);

        $content = trim((string) $this->input->post('content'));

        if ($content === '') {
            echo json_encode(['success' => false, 'message' => _l('dev_portal_comment_missing_content')]);

            return;
        }

        $discussion_id = $this->get_or_create_workspace_discussion($id);
        $this->projects_model->add_discussion_comment(['content' => $content], $discussion_id, 'regular');

        echo json_encode(['success' => true]);
    }
}
