<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Digital Marketing Portal - Dashboard and My Work. Daily Work Update is
 * no longer implemented here - it was extracted into the shared
 * modules/daily_work_update module (reused by every department portal),
 * and this module's own sidebar now links straight to it. Every query is
 * scoped server-side to the logged-in staff member via
 * Dm_portal_model::get_my_tasks_where()/get_my_projects_where(), not
 * just hidden UI.
 */
class Dm_portal extends AdminController
{
    public function __construct()
    {
        parent::__construct();

        if (!is_dm_portal_member()) {
            access_denied('Digital Marketing Portal');
        }

        $this->load->model('dm_portal/dm_portal_model');
        // Daily Work Update is now the shared module's sole
        // implementation - dm_portal no longer has its own Dm_work_update_model
        // or daily_update() actions, it just reuses this one for the
        // Dashboard's "Recent Daily Work Updates" list.
        $this->load->model('daily_work_update/daily_work_update_model');
        $this->load->model('tasks_model');
        $this->load->model('projects_model');
        $this->lang->load('dm_portal/dm_portal', 'english');
    }

    /**
     * Shared authorization for every task-scoped My Work action - Admin
     * or a real tbltask_assigned row (Tasks_model::is_task_assignee(),
     * the exact same check the native task detail view uses), never
     * trusting the client. Direct URL access to another employee's task
     * is denied identically everywhere, not re-implemented per action.
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

        access_denied('Digital Marketing Portal');
    }

    public function index()
    {
        $this->dashboard();
    }

    /**
     * Assigned Projects / Pending Tasks / Completed Tasks / Today's Tasks
     * from Dm_portal_model::get_dashboard_summary(); Recent Daily Work
     * Updates reuses the shared daily_work_update module's
     * Daily_work_update_model::get_recent() - Daily Work Update itself is
     * no longer implemented in this module, see the sidebar's own child
     * item (below) which now points straight at the shared controller.
     * Attendance Status and Today's Work Update summary cards were
     * removed from the dashboard per a later simplification pass -
     * Attendance and Daily Work Update remain reachable from the
     * sidebar, unchanged.
     */
    public function dashboard()
    {
        $staff_id = get_staff_user_id();

        $data['title']         = _l('dm_portal_dashboard');
        $data['summary']       = $this->dm_portal_model->get_dashboard_summary($staff_id);
        $data['recent_updates'] = $this->daily_work_update_model->get_recent($staff_id, 5);

        $this->load->view('dashboard', $data);
    }

    /**
     * My Work - the single combined workspace (no separate My Projects/
     * My Tasks pages). Filter dropdown data only; the table itself loads
     * via my_work_table() below.
     */
    public function my_work()
    {
        $staff_id = get_staff_user_id();

        $data['title']    = _l('dm_portal_my_work');
        $data['projects'] = $this->dm_portal_model->get_my_projects_for_filter($staff_id);
        $data['statuses'] = $this->tasks_model->get_statuses();
        $data['priorities'] = get_tasks_priorities();

        $this->load->view('my_work', $data);
    }

    /**
     * My Work DataTable source - task-rooted (one row per assigned
     * task), LEFT JOINed to its project + that project's client so
     * Project/Customer can render even though they live on other tables -
     * Project and Customer are never stored on the task itself, only
     * derived through the existing task -> project -> client relationship
     * (tasks.rel_id/rel_type -> projects -> projects.clientid); a task's
     * rel_type isn't always 'project' (this is real, live data - one of
     * the 4 already-assigned Digital Marketing staff has a task linked to
     * a lead, not a project), so this is a LEFT JOIN, not an INNER JOIN -
     * those rows still show, just with blank Project/Customer columns.
     *
     * Notes reuses the existing tbltask_comments table (same table
     * Tasks_model::add_task_comment() already writes to) - a correlated
     * subquery for "most recent comment", same technique the native
     * projects table source already uses for its own tags/members
     * subquery columns, so no extra query per row is needed.
     *
     * Progress has no real column (tbltasks has none) - it's rendered
     * from the SAME status value already selected for the Status column,
     * via dm_portal_task_progress_percent(), so no extra query/column is
     * needed; Notes, Progress and Actions are all appended after the
     * loop, same "derived columns go last" convention dev_portal's own
     * table sources already use.
     */
    public function my_work_table()
    {
        if (!$this->input->is_ajax_request()) {
            return;
        }

        $staff_id = get_staff_user_id();

        $aColumns = [
            db_prefix() . 'projects.name as project_name',
            db_prefix() . 'clients.company',
            db_prefix() . 'tasks.name',
            db_prefix() . 'tasks.priority',
            db_prefix() . 'tasks.duedate',
            db_prefix() . 'tasks.status',
            '(SELECT content FROM ' . db_prefix() . 'task_comments WHERE taskid = ' . db_prefix() . 'tasks.id ORDER BY dateadded DESC LIMIT 1) as latest_note',
        ];

        $sIndexColumn = 'id';
        $sTable       = db_prefix() . 'tasks';

        $join = [
            'LEFT JOIN ' . db_prefix() . 'projects ON ' . db_prefix() . 'projects.id = ' . db_prefix() . "tasks.rel_id AND " . db_prefix() . "tasks.rel_type = 'project'",
            'LEFT JOIN ' . db_prefix() . 'clients ON ' . db_prefix() . 'clients.userid = ' . db_prefix() . 'projects.clientid',
        ];

        $where = [
            'AND ' . $this->dm_portal_model->get_my_tasks_where($staff_id),
        ];

        // Filters - Project / Status / Priority (Search is DataTables'
        // own built-in search box, already covered by data_tables_init()).
        $project_id = $this->input->post('project_id');
        $status     = $this->input->post('status');
        $priority   = $this->input->post('priority');

        if ($project_id) {
            $where[] = 'AND ' . db_prefix() . 'tasks.rel_id = ' . (int) $project_id . " AND " . db_prefix() . "tasks.rel_type = 'project'";
        }
        if ($status) {
            $where[] = 'AND ' . db_prefix() . 'tasks.status = ' . (int) $status;
        }
        if ($priority) {
            $where[] = 'AND ' . db_prefix() . 'tasks.priority = ' . (int) $priority;
        }

        $result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, [
            db_prefix() . 'tasks.id',
        ]);

        $output  = $result['output'];
        $rResult = $result['rResult'];

        // Fetched once, not per row - get_statuses() applies a hooks
        // filter and sorts on every call, and none of that varies by row.
        $statuses = $this->tasks_model->get_statuses();

        foreach ($rResult as $aRow) {
            $row = [];
            $task_id    = (int) $aRow['id'];
            $status_val = (int) $aRow[db_prefix() . 'tasks.status'];

            $row[] = $aRow['project_name'] ? e($aRow['project_name']) : '-';
            $row[] = $aRow[db_prefix() . 'clients.company'] ? e($aRow[db_prefix() . 'clients.company']) : '-';
            $row[] = '<a href="#" onclick="dm_view_task(' . $task_id . '); return false;" class="tw-font-medium">' . e($aRow[db_prefix() . 'tasks.name']) . '</a>';
            $row[] = '<span class="label" style="color:' . task_priority_color($aRow[db_prefix() . 'tasks.priority']) . ';">' . e(task_priority($aRow[db_prefix() . 'tasks.priority'])) . '</span>';
            $row[] = $aRow[db_prefix() . 'tasks.duedate'] ? e(_d($aRow[db_prefix() . 'tasks.duedate'])) : '-';
            $row[] = '<span id="dm-status-' . $task_id . '">' . format_task_status($status_val) . '</span>';

            // Latest note preview - clicking it opens the same View modal,
            // scrolled/focused to its Comments section (dm_view_task's
            // existing focusNotes argument, already used by My Work's own
            // View action - no separate note-fetching endpoint needed).
            $note_text    = $aRow['latest_note'] ? trim(str_replace('[task_attachment]', '', $aRow['latest_note'])) : '';
            $note_preview = $note_text !== '' ? mb_strimwidth(strip_tags($note_text), 0, 50, '...') : '';
            $row[] = '<div id="dm-notes-' . $task_id . '" class="dm-clickable-row" style="cursor:pointer;max-width:180px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" onclick="dm_view_task(' . $task_id . ', true)" title="' . e($note_text) . '">'
                . ($note_preview !== '' ? e($note_preview) : '<span class="text-muted">' . _l('dm_portal_no_notes') . '</span>')
                . '</div>';

            // Progress - view state (bar + % + edit icon) and edit state
            // (slider + save/cancel only - notes are deliberately NOT
            // editable here, the View modal's Comments section is the
            // single source for notes) both rendered up front, edit
            // state hidden; toggled client-side, no extra fetch needed
            // to start editing. The slider has one stop per real status
            // (Tasks_model::get_statuses(), already fetched once above)
            // - there is no numeric progress column to set independently
            // of status, so "editing progress" and "changing status" are
            // the same operation here, exactly like the Status column
            // already was.
            $progress = dm_portal_task_progress_percent($status_val);

            $stop_index = 0;
            foreach ($statuses as $i => $s) {
                if ((int) $s['id'] === $status_val) {
                    $stop_index = $i;

                    break;
                }
            }

            $row[] = '<div id="dm-progress-view-' . $task_id . '">'
                    . '<div class="progress" style="margin-bottom:0;display:inline-block;width:65%;vertical-align:middle;"><div class="progress-bar" role="progressbar" style="width:' . $progress . '%;" aria-valuenow="' . $progress . '" aria-valuemin="0" aria-valuemax="100">' . $progress . '%</div></div>'
                    . ' <button type="button" class="btn btn-link btn-xs" style="padding:0;" title="' . _l('dm_portal_edit_progress') . '" onclick="dm_toggle_progress_edit(' . $task_id . ')"><i class="fa-solid fa-pen"></i></button>'
                    . '</div>'
                    . '<div id="dm-progress-edit-' . $task_id . '" style="display:none;min-width:190px;">'
                    . '<input type="range" min="0" max="' . (count($statuses) - 1) . '" step="1" value="' . $stop_index . '" id="dm-progress-slider-' . $task_id . '" class="dm-progress-slider" style="width:100%;" oninput="dm_progress_slider_label(' . $task_id . ')">'
                    . '<div class="text-center tw-text-xs tw-font-medium" id="dm-progress-label-' . $task_id . '">' . format_task_status($status_val, false, true) . '</div>'
                    . '<div class="tw-flex tw-gap-1 tw-mt-1">'
                    . '<button type="button" class="btn btn-success btn-xs" title="' . _l('save') . '" onclick="dm_save_progress(' . $task_id . ')"><i class="fa-solid fa-check"></i></button>'
                    . '<button type="button" class="btn btn-default btn-xs" title="' . _l('cancel') . '" onclick="dm_cancel_progress_edit(' . $task_id . ')"><i class="fa-solid fa-xmark"></i></button>'
                    . '</div>'
                    . '</div>';

            $is_complete = $status_val === Tasks_model::STATUS_COMPLETE;
            $row[] = '<div class="tw-flex tw-items-center tw-gap-1">'
                . '<button type="button" class="btn btn-default btn-sm" title="' . _l('dm_portal_action_view_details') . '" onclick="dm_view_task(' . $task_id . ')"><i class="fa-solid fa-eye"></i></button>'
                . (!$is_complete ? '<button type="button" class="btn btn-success btn-sm" title="' . _l('dm_portal_action_mark_complete') . '" onclick="dm_mark_complete(' . $task_id . ')"><i class="fa-solid fa-check"></i></button>' : '')
                . '</div>';

            $row['DT_RowId'] = 'dm_task_' . $task_id;
            $output['aaData'][] = $row;
        }

        echo json_encode($output);
    }

    /**
     * View Details modal content - Project Information / Assigned Work /
     * Task Details / Files / Comments for one task. Every field comes
     * straight from Tasks_model::get() (already bundles comments and
     * attachments, no separate queries needed) plus the parent project
     * row when the task actually belongs to one.
     */
    public function task_details($id)
    {
        $this->authorize_task_access($id, true);

        $task = $this->tasks_model->get($id);

        if (!$task) {
            echo json_encode(['success' => false]);

            return;
        }

        $data['task']         = $task;
        $data['project']      = null;
        $data['client_name']  = null;

        if ($task->rel_type === 'project') {
            $data['project'] = $this->projects_model->get($task->rel_id);

            if ($data['project']) {
                $this->db->select('company');
                $this->db->where('userid', $data['project']->clientid);
                $client = $this->db->get(db_prefix() . 'clients')->row();
                $data['client_name'] = $client ? $client->company : null;
            }
        }

        echo $this->load->view('task_details_modal', $data, true);
    }

    /**
     * Update Progress - a status change restricted to the 4 non-complete
     * statuses (Complete has its own dedicated action below), reusing
     * Tasks_model::mark_as() directly - the exact method the native task
     * UI's own status dropdown calls, so activity logging/notifications/
     * timer-stopping all behave identically to a native status change.
     */
    public function task_update_progress($id)
    {
        $this->authorize_task_access($id, true);

        $status = (int) $this->input->post('status');

        $allowed = array_column($this->tasks_model->get_statuses(), 'id');
        $allowed = array_diff($allowed, [Tasks_model::STATUS_COMPLETE]);

        if (!in_array($status, $allowed, true)) {
            echo json_encode(['success' => false, 'message' => _l('dm_portal_invalid_status')]);

            return;
        }

        $this->tasks_model->mark_as($status, $id);

        echo json_encode(['success' => true]);
    }

    /**
     * Mark Complete - a dedicated one-click shortcut to the same
     * Tasks_model::mark_as(), fixed to STATUS_COMPLETE.
     */
    public function task_mark_complete($id)
    {
        $this->authorize_task_access($id, true);

        $this->tasks_model->mark_as(Tasks_model::STATUS_COMPLETE, $id);

        echo json_encode(['success' => true]);
    }

    /**
     * Add Notes - reuses Tasks_model::add_task_comment() directly (the
     * exact method the native task comment box calls), so Admin sees the
     * same note under the native Task view's own Comments tab - no
     * separate notes table.
     */
    public function task_add_note($id)
    {
        $this->authorize_task_access($id, true);

        $content = trim((string) $this->input->post('content'));

        if ($content === '') {
            echo json_encode(['success' => false, 'message' => _l('dm_portal_note_missing_content')]);

            return;
        }

        $this->tasks_model->add_task_comment([
            'taskid'  => $id,
            'content' => $content,
        ]);

        echo json_encode(['success' => true]);
    }

    /**
     * Files (upload) - reuses the exact native combination
     * handle_task_attachments_array() + Misc_model::add_attachment_to_database()
     * the native Tasks controller's own add_attachment() action uses, so
     * files land in tblfiles identically to a native upload.
     */
    public function task_upload_file($id)
    {
        $this->authorize_task_access($id, true);

        $files = handle_task_attachments_array($id, 'file');

        if ($files && is_array($files)) {
            $this->load->model('misc_model');
            foreach ($files as $file) {
                $this->misc_model->add_attachment_to_database($id, 'task', [$file]);
            }
        }

        // handle_task_attachments_array() silently skips files with a
        // disallowed extension rather than erroring - report that as a
        // failure here so the upload isn't shown as successful when
        // nothing was actually attached.
        if (!$files || !is_array($files) || count($files) === 0) {
            echo json_encode(['success' => false, 'message' => _l('validation_extension_not_allowed')]);

            return;
        }

        echo json_encode(['success' => true]);
    }

    /**
     * Dashboard "Assigned Projects" card modal - list only. Row expand
     * (Description/Team Members/Files/Current Tasks) is a separate,
     * lazy-loaded call (dashboard_project_detail()) so opening this list
     * never fetches more than the summary columns it actually shows.
     */
    public function dashboard_projects()
    {
        if (!$this->input->is_ajax_request()) {
            return;
        }

        $data['projects'] = $this->dm_portal_model->get_my_projects_detailed(get_staff_user_id());

        echo $this->load->view('dashboard_projects_modal', $data, true);
    }

    /**
     * Expand panel for one project inside the "Assigned Projects" modal -
     * Description, Team Members, Files, Current Tasks. is_member() is the
     * same real membership check used everywhere else project access is
     * gated in this codebase - a Digital Marketing staff member can never
     * expand a project they don't belong to just by knowing its id.
     */
    public function dashboard_project_detail($project_id)
    {
        if (!$this->input->is_ajax_request()) {
            return;
        }

        if (!is_admin() && !$this->projects_model->is_member($project_id)) {
            ajax_access_denied();
        }

        $staff_id = get_staff_user_id();

        $data['project']       = $this->dm_portal_model->get_project_basic($project_id);
        $data['team_members']  = $this->dm_portal_model->get_project_team_members($project_id);
        $data['files']         = $this->dm_portal_model->get_project_files($project_id);
        $data['current_tasks'] = $this->dm_portal_model->get_project_current_tasks($project_id, $staff_id);

        echo $this->load->view('dashboard_project_detail', $data, true);
    }

    /**
     * Dashboard "Pending Tasks" card modal. Row actions (View/Update
     * Progress/Mark Complete) reuse the exact same task_details()/
     * task_update_progress()/task_mark_complete() endpoints My Work
     * already uses - no separate action logic for the dashboard version.
     */
    public function dashboard_pending_tasks()
    {
        if (!$this->input->is_ajax_request()) {
            return;
        }

        $data['tasks']    = $this->dm_portal_model->get_my_pending_tasks_detailed(get_staff_user_id());
        $data['statuses'] = $this->tasks_model->get_statuses();

        echo $this->load->view('dashboard_pending_tasks_modal', $data, true);
    }

    /**
     * Dashboard "Completed Tasks" card modal - read-only per the approved
     * spec (no row actions).
     */
    public function dashboard_completed_tasks()
    {
        if (!$this->input->is_ajax_request()) {
            return;
        }

        $data['tasks'] = $this->dm_portal_model->get_my_completed_tasks_detailed(get_staff_user_id());

        echo $this->load->view('dashboard_completed_tasks_modal', $data, true);
    }

    /**
     * Dashboard "Today's Tasks" card modal - reuses
     * Dm_portal_model::get_today_task_list(), the same query already
     * backing the dashboard's own "Today's Work Queue" list, just without
     * that list's 5-row preview cap.
     */
    public function dashboard_today_tasks()
    {
        if (!$this->input->is_ajax_request()) {
            return;
        }

        $data['tasks'] = $this->dm_portal_model->get_today_task_list(get_staff_user_id(), 100);

        echo $this->load->view('dashboard_today_tasks_modal', $data, true);
    }
}
