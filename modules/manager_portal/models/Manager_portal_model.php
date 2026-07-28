<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Manager_portal_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Every staff id the Operations Manager's Team covers - company-wide,
     * every active non-admin staff member, excluding the viewer's own id.
     * Thin wrapper kept as a model method (rather than calling the helper
     * directly from every caller) so every scoped query in this file goes
     * through one place.
     *
     * @param  int|null $staff_id defaults to the logged-in staff member
     * @return array staff ids
     */
    public function get_scope_staff_ids($staff_id = null)
    {
        return manager_portal_all_staff_ids(true, $staff_id);
    }

    /**
     * Task scoping condition - staff-id-based (tbltasks has no department
     * column), same shape as Dev_portal_model::get_my_tasks_where().
     *
     * @param  array $staff_ids
     * @return string
     */
    public function get_tasks_where($staff_ids)
    {
        return db_prefix() . 'tasks.id IN (SELECT taskid FROM ' . db_prefix() . 'task_assigned WHERE staffid IN (' . (count($staff_ids) ? implode(',', array_map('intval', $staff_ids)) : '0') . '))';
    }

    /**
     * Every dashboard KPI in one call - company-wide, no department
     * filtering. Each figure reuses get_tasks_where() rather than a
     * second, dashboard-only scoping rule, same discipline as Dev
     * Portal's own get_dashboard_summary().
     *
     * @param  int $staff_id
     * @return array
     */
    public function get_dashboard_summary($staff_id)
    {
        $team_ids    = $this->get_scope_staff_ids($staff_id);
        $tasks_where = $this->get_tasks_where($team_ids);
        $today       = date('Y-m-d');

        // Projects are company-wide, unfiltered - no department or
        // membership scope at all, per the approved architecture ("do not
        // filter the Manager Portal by Business Department").
        $this->db->where_not_in('status', [4, 5]);
        $active_projects = $this->db->count_all_results(db_prefix() . 'projects');

        $this->db->where($tasks_where, null, false);
        $this->db->where('status !=', Tasks_model::STATUS_COMPLETE);
        $active_tasks = $this->db->count_all_results(db_prefix() . 'tasks');

        $this->db->where($tasks_where, null, false);
        $this->db->where('status', Tasks_model::STATUS_COMPLETE);
        $this->db->where('DATE(datefinished) =', $today);
        $completed_today = $this->db->count_all_results(db_prefix() . 'tasks');

        $this->db->where($tasks_where, null, false);
        $this->db->where('status !=', Tasks_model::STATUS_COMPLETE);
        $this->db->where('(duedate IS NULL OR duedate >= ' . $this->db->escape($today) . ')', null, false);
        $pending_tasks = $this->db->count_all_results(db_prefix() . 'tasks');

        $this->db->where($tasks_where, null, false);
        $this->db->where('status !=', Tasks_model::STATUS_COMPLETE);
        $this->db->where('duedate <', $today);
        $overdue_tasks = $this->db->count_all_results(db_prefix() . 'tasks');

        return [
            'team_count'         => count($team_ids),
            'active_projects'    => $active_projects,
            'active_tasks'       => $active_tasks,
            'completed_today'    => $completed_today,
            'pending_tasks'      => $pending_tasks,
            'overdue_tasks'      => $overdue_tasks,
            'attendance_today'   => $this->get_attendance_today_summary($team_ids),
            'daily_work_update'  => $this->get_daily_work_update_summary($team_ids),
            'productivity'       => $this->get_productivity_percent($tasks_where),
            'upcoming_deadlines' => $this->get_upcoming_deadlines($tasks_where),
            'recent_activity'    => $this->get_recent_activity(),
        ];
    }

    /**
     * The Dashboard's operational section - six drill-down lists (as
     * opposed to the KPI cards above, which are just counts): Daily Work
     * Updates awaiting review, employees absent today, employees who
     * haven't submitted today's update, overdue tasks, overdue projects,
     * upcoming deadlines. Every list reuses the same team scope and
     * underlying tables the KPI cards already query - no new data
     * sources, just row-level detail instead of a count.
     *
     * @param  int $staff_id
     * @param  int $limit per list
     * @return array
     */
    public function get_dashboard_operational_section($staff_id, $limit = 6)
    {
        $team_ids    = $this->get_scope_staff_ids($staff_id);
        $tasks_where = $this->get_tasks_where($team_ids);

        return [
            'dwu_awaiting_review' => $this->get_dwu_awaiting_review($team_ids, $limit),
            'absent_today'        => $this->get_absent_today($team_ids, $limit),
            'not_submitted_today' => $this->get_not_submitted_today($team_ids, $limit),
            'overdue_tasks'       => $this->get_overdue_tasks_list($tasks_where, $limit),
            'overdue_projects'    => $this->get_overdue_projects_list($limit),
            'upcoming_deadlines'  => $this->get_upcoming_deadlines($tasks_where, $limit),
        ];
    }

    /**
     * Today's Present/Absent/Late/Half Day/Leave count across the team -
     * per-staff loop over Staff_attendance_model::get_staff_date_record(),
     * the same per-staff read Dev Portal's own dashboard already uses for
     * a single staff member, just looped (Staff_attendance_model's own
     * get_today_summary() is company-wide with no staff-id filter, but
     * doesn't exclude Admin, so isn't reusable here as-is).
     *
     * @param  array $team_ids
     * @return array ['Present'=>int, 'Absent'=>int, ...]
     */
    private function get_attendance_today_summary($team_ids)
    {
        $this->load->model('staff_attendance/staff_attendance_model');

        $counts = ['Present' => 0, 'Absent' => 0, 'Late' => 0, 'Half Day' => 0, 'Leave' => 0];

        foreach ($team_ids as $staff_id) {
            $record = $this->staff_attendance_model->get_staff_date_record($staff_id);
            $status = $record ? $record->attendance_status : 'Absent';

            if (!isset($counts[$status])) {
                $counts[$status] = 0;
            }
            ++$counts[$status];
        }

        return $counts;
    }

    /**
     * Team members with no attendance row for today, or an explicit
     * 'Absent' status - same per-staff read as get_attendance_today_summary()
     * above, kept as its own query so the operational-section list can
     * show names, not just a count.
     *
     * @param  array $team_ids
     * @param  int   $limit
     * @return array [['staffid'=>int,'name'=>string], ...]
     */
    private function get_absent_today($team_ids, $limit)
    {
        $this->load->model('staff_attendance/staff_attendance_model');

        $absent = [];
        foreach ($team_ids as $staff_id) {
            $record = $this->staff_attendance_model->get_staff_date_record($staff_id);
            if (!$record || $record->attendance_status === 'Absent') {
                $absent[] = $staff_id;
            }
        }

        if (!count($absent)) {
            return [];
        }

        $this->db->select('staffid, firstname, lastname');
        $this->db->where_in('staffid', $absent);
        $this->db->limit($limit);
        $rows = $this->db->get(db_prefix() . 'staff')->result_array();

        foreach ($rows as &$row) {
            $row['name'] = trim($row['firstname'] . ' ' . $row['lastname']);
        }

        return $rows;
    }

    /**
     * Today's Daily Work Update submitted/pending count across the team -
     * a plain staff-driven LEFT JOIN against today's rows, same shape as
     * Staff_attendance_model::get_today_summary()'s own staff-driven query.
     *
     * @param  array $team_ids
     * @return array ['submitted'=>int, 'pending'=>int]
     */
    private function get_daily_work_update_summary($team_ids)
    {
        if (!count($team_ids)) {
            return ['submitted' => 0, 'pending' => 0];
        }

        $rows = $this->get_daily_work_update_rows($team_ids);

        $submitted = count(array_filter($rows, function ($row) {
            return $row['update_id'] !== null;
        }));

        return [
            'submitted' => $submitted,
            'pending'   => count($rows) - $submitted,
        ];
    }

    /**
     * Team members with no Daily Work Update row for today - the list
     * form of get_daily_work_update_summary()'s "pending" count, for the
     * operational section.
     *
     * @param  array $team_ids
     * @param  int   $limit
     * @return array [['staffid'=>int,'name'=>string], ...]
     */
    private function get_not_submitted_today($team_ids, $limit)
    {
        if (!count($team_ids)) {
            return [];
        }

        $rows = array_filter($this->get_daily_work_update_rows($team_ids), function ($row) {
            return $row['update_id'] === null;
        });

        return array_slice(array_values($rows), 0, $limit);
    }

    /**
     * Shared staff-driven LEFT JOIN against today's Daily Work Update
     * rows - the one query get_daily_work_update_summary() and
     * get_not_submitted_today() both build on, so the "pending" count and
     * the "who hasn't submitted" list can never disagree with each other.
     *
     * @param  array $team_ids
     * @return array [['staffid'=>int,'name'=>string,'update_id'=>int|null], ...]
     */
    private function get_daily_work_update_rows($team_ids)
    {
        $this->db->select('staff.staffid, staff.firstname, staff.lastname, dwu.id as update_id');
        $this->db->from(db_prefix() . 'staff staff');
        $this->db->join(
            db_prefix() . 'dm_portal_work_updates dwu',
            'dwu.staff_id = staff.staffid AND dwu.work_date = ' . $this->db->escape(date('Y-m-d')),
            'left'
        );
        $this->db->where_in('staff.staffid', $team_ids);
        $rows = $this->db->get()->result_array();

        foreach ($rows as &$row) {
            $row['name'] = trim($row['firstname'] . ' ' . $row['lastname']);
        }

        return $rows;
    }

    /**
     * Daily Work Update submissions still awaiting the Operations
     * Manager's review (review_status = 'pending', the migration's
     * default for every submission) - company-wide across the team, most
     * recent first.
     *
     * @param  array $team_ids
     * @param  int   $limit
     * @return array
     */
    private function get_dwu_awaiting_review($team_ids, $limit)
    {
        if (!count($team_ids)) {
            return [];
        }

        $this->db->select('dwu.id, dwu.work_date, dwu.today_work, staff.firstname, staff.lastname');
        $this->db->from(db_prefix() . 'dm_portal_work_updates dwu');
        $this->db->join(db_prefix() . 'staff staff', 'staff.staffid = dwu.staff_id');
        $this->db->where_in('dwu.staff_id', $team_ids);
        $this->db->where('dwu.review_status', 'pending');
        $this->db->order_by('dwu.work_date', 'desc');
        $this->db->limit($limit);
        $rows = $this->db->get()->result_array();

        foreach ($rows as &$row) {
            $row['name'] = trim($row['firstname'] . ' ' . $row['lastname']);
        }

        return $rows;
    }

    /**
     * Department Productivity % - simple completed/total ratio over the
     * team's tasks, reusing $tasks_where rather than a new scope. Used by
     * both the Dashboard KPI and (in a later phase) the Performance
     * page's headline number, so it lives here rather than duplicated on
     * both.
     *
     * @param  string $tasks_where
     * @return int 0-100
     */
    private function get_productivity_percent($tasks_where)
    {
        $this->db->where($tasks_where, null, false);
        $total = $this->db->count_all_results(db_prefix() . 'tasks');

        if ($total === 0) {
            return 0;
        }

        $this->db->where($tasks_where, null, false);
        $this->db->where('status', Tasks_model::STATUS_COMPLETE);
        $completed = $this->db->count_all_results(db_prefix() . 'tasks');

        return (int) round(($completed / $total) * 100);
    }

    /**
     * Overdue tasks (duedate passed, not complete), most overdue first -
     * the list form of the Dashboard's "Overdue Tasks" KPI count.
     *
     * @param  string $tasks_where
     * @param  int    $limit
     * @return array
     */
    private function get_overdue_tasks_list($tasks_where, $limit)
    {
        $this->db->select('id, name, duedate');
        $this->db->where($tasks_where, null, false);
        $this->db->where('status !=', Tasks_model::STATUS_COMPLETE);
        $this->db->where('duedate <', date('Y-m-d'));
        $this->db->order_by('duedate', 'asc');
        $this->db->limit($limit);

        return $this->db->get(db_prefix() . 'tasks')->result_array();
    }

    /**
     * Overdue projects (deadline passed, not Finished/Cancelled) -
     * company-wide, no department filter. Not previously computed
     * anywhere in this module; new per the Dashboard's operational
     * section requirement.
     *
     * @param  int $limit
     * @return array
     */
    private function get_overdue_projects_list($limit)
    {
        $this->db->select('id, name, deadline');
        $this->db->where('deadline <', date('Y-m-d'));
        $this->db->where_not_in('status', [4, 5]);
        $this->db->order_by('deadline', 'asc');
        $this->db->limit($limit);

        return $this->db->get(db_prefix() . 'projects')->result_array();
    }

    /**
     * Tasks due and projects deadlined in the next 7 days, merged in PHP -
     * same technique as Dev_portal_model::get_upcoming_deadlines() (the
     * two source tables have different column shapes, so a SQL UNION
     * isn't a clean fit for a handful of rows). Company-wide, no
     * department filter.
     *
     * @param  string $tasks_where
     * @param  int    $limit
     * @return array
     */
    private function get_upcoming_deadlines($tasks_where, $limit = 6)
    {
        $today   = date('Y-m-d');
        $horizon = date('Y-m-d', strtotime('+7 days'));

        $this->db->select('id, name, duedate');
        $this->db->where($tasks_where, null, false);
        $this->db->where('duedate >=', $today);
        $this->db->where('duedate <=', $horizon);
        $this->db->where('status !=', Tasks_model::STATUS_COMPLETE);
        $this->db->order_by('duedate', 'asc');
        $tasks = $this->db->get(db_prefix() . 'tasks')->result_array();

        $this->db->select('id, name, deadline');
        $this->db->where('deadline >=', $today);
        $this->db->where('deadline <=', $horizon);
        $this->db->where_not_in('status', [4, 5]);
        $this->db->order_by('deadline', 'asc');
        $projects = $this->db->get(db_prefix() . 'projects')->result_array();

        $deadlines = [];

        foreach ($tasks as $task) {
            $deadlines[] = ['type' => 'task', 'id' => $task['id'], 'name' => $task['name'], 'date' => $task['duedate']];
        }

        foreach ($projects as $project) {
            $deadlines[] = ['type' => 'project', 'id' => $project['id'], 'name' => $project['name'], 'date' => $project['deadline']];
        }

        usort($deadlines, function ($a, $b) {
            return strcmp($a['date'], $b['date']);
        });

        return array_slice($deadlines, 0, $limit);
    }

    /**
     * Recent project activity company-wide, reusing tblproject_activity -
     * the same feed Projects_model::log_activity() writes to and the
     * native Project view's own Activity tab reads.
     *
     * @param  int $limit
     * @return array
     */
    private function get_recent_activity($limit = 8)
    {
        // Column names verified against tblproject_activity's real schema
        // (description_key/dateadded/staff_id, not description/date/staffid -
        // this table stores a translatable key + serialized placeholder
        // data, same shape Projects_model::get_activity() decodes via
        // _l($description_key) for its own Activity tab; that method
        // takes no company-wide mode, so isn't reusable here as-is, but
        // the description formatting convention is mirrored below).
        $this->db->select('a.description_key, a.additional_data, a.dateadded, a.staff_id, p.name as project_name, p.id as project_id, s.firstname, s.lastname');
        $this->db->from(db_prefix() . 'project_activity a');
        $this->db->join(db_prefix() . 'projects p', 'p.id = a.project_id');
        $this->db->join(db_prefix() . 'staff s', 's.staffid = a.staff_id', 'left');
        $this->db->order_by('a.dateadded', 'desc');
        $this->db->limit($limit);

        $rows = $this->db->get()->result_array();

        foreach ($rows as &$row) {
            $row['description'] = _l($row['description_key']);
            $row['date']        = $row['dateadded'];
        }

        return $rows;
    }

    /**
     * Team List roster - every company-wide staff id, joined to tblstaff
     * plus the full per-member rollup the Team List page's 11 columns
     * need (department, designation, today's/pending tasks, active
     * projects, attendance, Daily Work Update status, performance
     * status), reusing the same scoping/model methods this file's other
     * methods and the shared business_departments/staff_attendance/
     * daily_work_update models already apply - no new query primitives
     * for anything that already had one, just per-row aggregation.
     *
     * @param  array $team_ids
     * @return array
     */
    public function get_team_members($team_ids)
    {
        if (!count($team_ids)) {
            return [];
        }

        $this->load->model('staff_attendance/staff_attendance_model');
        $this->load->model('daily_work_update/daily_work_update_model');

        // tblstaff has no "position"/designation column or custom field of
        // its own (checked directly - neither exists) - tblroles.name
        // (the Setup > Staff Roles a staff member is assigned) is the
        // closest existing native concept to a job designation, reused
        // here via the same tblstaff.role FK every staff-role check
        // already uses, rather than inventing a new field.
        $this->db->select('staff.staffid, staff.firstname, staff.lastname, staff.email, staff.profile_image, staff.active, roles.name as designation');
        $this->db->from(db_prefix() . 'staff staff');
        $this->db->join(db_prefix() . 'roles roles', 'roles.roleid = staff.role', 'left');
        $this->db->where_in('staff.staffid', $team_ids);
        $this->db->order_by('staff.firstname', 'asc');
        $staff = $this->db->get()->result_array();
        $today = date('Y-m-d');

        foreach ($staff as &$member) {
            $staff_id = (int) $member['staffid'];

            $member['departments'] = array_column(get_staff_business_departments($staff_id), 'name');

            $this->db->where('id IN (SELECT taskid FROM ' . db_prefix() . 'task_assigned WHERE staffid = ' . $staff_id . ')', null, false);
            $this->db->where('status !=', Tasks_model::STATUS_COMPLETE);
            $this->db->where('duedate', $today);
            $member['todays_tasks'] = $this->db->count_all_results(db_prefix() . 'tasks');

            $this->db->where('id IN (SELECT taskid FROM ' . db_prefix() . 'task_assigned WHERE staffid = ' . $staff_id . ')', null, false);
            $this->db->where('status !=', Tasks_model::STATUS_COMPLETE);
            $this->db->where('(duedate IS NULL OR duedate >= ' . $this->db->escape($today) . ')', null, false);
            $member['pending_tasks'] = $this->db->count_all_results(db_prefix() . 'tasks');

            $this->db->where('id IN (SELECT project_id FROM ' . db_prefix() . 'project_members WHERE staff_id = ' . $staff_id . ')', null, false);
            $this->db->where_not_in('status', [4, 5]);
            $member['active_projects'] = $this->db->count_all_results(db_prefix() . 'projects');

            $record                      = $this->staff_attendance_model->get_staff_date_record($staff_id);
            $member['attendance_status'] = $record ? $record->attendance_status : 'Absent';

            $today_update             = $this->daily_work_update_model->get_today($staff_id);
            $member['dwu_status']     = $today_update ? $today_update->review_status : 'not_submitted';

            $member['productivity']      = $this->get_staff_productivity_percent($staff_id);
            $member['performance_label'] = $this->performance_label($member['productivity']);
        }

        return $staff;
    }

    /**
     * Completed/total task ratio for one staff member - the per-employee
     * version of get_productivity_percent() (which takes a $tasks_where
     * covering a whole team at once); kept separate since Team List and
     * the Employee Profile Performance Summary both need a single
     * employee's own number, not the aggregate.
     *
     * @param  int $staff_id
     * @return int 0-100
     */
    private function get_staff_productivity_percent($staff_id)
    {
        return $this->get_productivity_percent(db_prefix() . 'tasks.id IN (SELECT taskid FROM ' . db_prefix() . 'task_assigned WHERE staffid = ' . (int) $staff_id . ')');
    }

    /**
     * Productivity % -> a plain-language status, the Team List's
     * "Performance Status" column and the Employee Profile's Performance
     * Summary card. Thresholds are a simple, self-contained heuristic
     * (no external scoring system exists in this codebase to defer to).
     *
     * @param  int $productivity 0-100
     * @return string
     */
    private function performance_label($productivity)
    {
        if ($productivity >= 70) {
            return 'good';
        }

        if ($productivity >= 40) {
            return 'average';
        }

        return 'needs_improvement';
    }

    /**
     * Everything the Employee Profile page needs in one call - basic
     * info is read by the controller directly (Staff_model::get(), the
     * same read Staff::profile_view() uses); every section here reuses
     * an existing model/table rather than a new query shape:
     * Staff_attendance_model for attendance, tblproject_members/
     * tbltask_assigned for assigned work (same join pattern
     * get_team_members() already uses, just row-level instead of
     * count-level), Daily_work_update_model::get_recent() verbatim (the
     * exact method the employee's own Daily Work Update page already
     * calls), and the same tblproject_activity read
     * get_recent_activity() uses, scoped to this one staff member.
     *
     * @param  int $staff_id
     * @return array
     */
    public function get_employee_profile_bundle($staff_id)
    {
        $this->load->model('staff_attendance/staff_attendance_model');
        $this->load->model('daily_work_update/daily_work_update_model');

        $today = date('Y-m-d');

        $this->db->select('roles.name');
        $this->db->from(db_prefix() . 'staff staff');
        $this->db->join(db_prefix() . 'roles roles', 'roles.roleid = staff.role', 'left');
        $this->db->where('staff.staffid', $staff_id);
        $role = $this->db->get()->row();

        $productivity = $this->get_staff_productivity_percent($staff_id);

        return [
            'departments'        => array_column(get_staff_business_departments($staff_id), 'name'),
            'designation'        => $role ? $role->name : null,
            'attendance_today'   => $this->staff_attendance_model->get_staff_date_record($staff_id),
            'attendance_month'   => $this->staff_attendance_model->get_month_summary($staff_id, date('Y'), date('n')),
            'assigned_projects'  => $this->get_staff_assigned_projects($staff_id),
            'assigned_tasks'     => $this->get_staff_assigned_tasks($staff_id),
            'daily_work_updates' => $this->daily_work_update_model->get_recent($staff_id, 10),
            'recent_activity'    => $this->get_staff_recent_activity($staff_id),
            'productivity'       => $productivity,
            'performance_label'  => $this->performance_label($productivity),
        ];
    }

    /**
     * @param  int $staff_id
     * @return array
     */
    private function get_staff_assigned_projects($staff_id)
    {
        $this->db->select('id, name, status, deadline, progress');
        $this->db->where('id IN (SELECT project_id FROM ' . db_prefix() . 'project_members WHERE staff_id = ' . (int) $staff_id . ')', null, false);
        $this->db->order_by('deadline', 'asc');

        return $this->db->get(db_prefix() . 'projects')->result_array();
    }

    /**
     * @param  int $staff_id
     * @return array
     */
    private function get_staff_assigned_tasks($staff_id)
    {
        $this->db->select('id, name, status, duedate, priority');
        $this->db->where('id IN (SELECT taskid FROM ' . db_prefix() . 'task_assigned WHERE staffid = ' . (int) $staff_id . ')', null, false);
        $this->db->order_by('duedate', 'asc');

        return $this->db->get(db_prefix() . 'tasks')->result_array();
    }

    /**
     * Same read as get_recent_activity() above, scoped to one staff
     * member's own actions.
     *
     * @param  int $staff_id
     * @param  int $limit
     * @return array
     */
    private function get_staff_recent_activity($staff_id, $limit = 8)
    {
        $this->db->select('a.description_key, a.additional_data, a.dateadded, p.name as project_name, p.id as project_id');
        $this->db->from(db_prefix() . 'project_activity a');
        $this->db->join(db_prefix() . 'projects p', 'p.id = a.project_id');
        $this->db->where('a.staff_id', $staff_id);
        $this->db->order_by('a.dateadded', 'desc');
        $this->db->limit($limit);

        $rows = $this->db->get()->result_array();

        foreach ($rows as &$row) {
            $row['description'] = _l($row['description_key']);
            $row['date']        = $row['dateadded'];
        }

        return $rows;
    }

    /**
     * Company-wide Projects list, filterable - built directly against
     * tblprojects/tblproject_members (the same tables the native
     * App_table 'projects' registration reads), not the native
     * admin/projects controller, which this module deliberately never
     * grants access to - see manager_portal_restrict_native_controller_access()'s
     * own docblock for why (this containment pattern is unchanged from
     * Phase 1/2; a project detail page reusing Projects_model is added
     * separately below, rather than reusing the native controller).
     * "Project Manager" reuses tblprojects.assigned_employee - this
     * codebase's own existing single-owner-per-project field (see this
     * repo's earlier "Assigned Employee" work) - there is no separate
     * native "project manager" concept to duplicate.
     *
     * @param  array $filters department_id, status, assigned_employee,
     *                        employee_id (member), client_id,
     *                        active ('active'|'completed'), date_from,
     *                        date_to (against start_date)
     * @return array
     */
    public function get_projects($filters = [])
    {
        $this->db->select('p.id, p.name, p.status, p.progress, p.start_date, p.deadline, p.department, p.assigned_employee, p.clientid, c.company');
        $this->db->from(db_prefix() . 'projects p');
        $this->db->join(db_prefix() . 'clients c', 'c.userid = p.clientid', 'left');

        if (!empty($filters['department_id'])) {
            $this->db->where('p.department', $filters['department_id']);
        }
        if (!empty($filters['status'])) {
            $this->db->where('p.status', $filters['status']);
        }
        if (!empty($filters['assigned_employee'])) {
            $this->db->where('p.assigned_employee', $filters['assigned_employee']);
        }
        if (!empty($filters['client_id'])) {
            $this->db->where('p.clientid', $filters['client_id']);
        }
        if (!empty($filters['employee_id'])) {
            $this->db->where('p.id IN (SELECT project_id FROM ' . db_prefix() . 'project_members WHERE staff_id = ' . (int) $filters['employee_id'] . ')', null, false);
        }
        if (!empty($filters['active'])) {
            if ($filters['active'] === 'active') {
                $this->db->where_not_in('p.status', [4, 5]);
            } elseif ($filters['active'] === 'completed') {
                $this->db->where_in('p.status', [4, 5]);
            }
        }
        if (!empty($filters['date_from'])) {
            $this->db->where('p.start_date >=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $this->db->where('p.start_date <=', $filters['date_to']);
        }

        $this->db->order_by('p.deadline', 'asc');
        $projects = $this->db->get()->result_array();
        $today    = date('Y-m-d');

        foreach ($projects as &$project) {
            $project['department_name'] = null;
            if ($project['department']) {
                $this->db->select('name');
                $this->db->where('id', $project['department']);
                $department                 = $this->db->get(db_prefix() . 'business_departments')->row();
                $project['department_name'] = $department ? $department->name : null;
            }

            $project['manager_name'] = null;
            if ($project['assigned_employee']) {
                $this->db->select('firstname, lastname');
                $this->db->where('staffid', $project['assigned_employee']);
                $manager                 = $this->db->get(db_prefix() . 'staff')->row();
                $project['manager_name'] = $manager ? trim($manager->firstname . ' ' . $manager->lastname) : null;
            }

            $this->db->select('s.firstname, s.lastname');
            $this->db->from(db_prefix() . 'project_members pm');
            $this->db->join(db_prefix() . 'staff s', 's.staffid = pm.staff_id');
            $this->db->where('pm.project_id', $project['id']);
            $members                 = $this->db->get()->result_array();
            $project['member_names'] = array_map(function ($member) {
                return trim($member['firstname'] . ' ' . $member['lastname']);
            }, $members);

            $project['overdue'] = $project['deadline'] && $project['deadline'] < $today && !in_array((int) $project['status'], [4, 5]);
        }

        return $projects;
    }

    /**
     * Filter dropdown options for the Projects page - every value sourced
     * from an existing model/table, nothing invented: departments
     * (Business_departments_model, already used throughout this module),
     * statuses (Projects_model::get_project_statuses(), the exact list
     * the native project view already uses), employees (active staff,
     * company-wide - same universe Team already covers), customers
     * (tblclients).
     *
     * @return array
     */
    public function get_project_filter_options()
    {
        $this->load->model('projects_model');
        $this->load->model('business_departments/business_departments_model');

        $this->db->select('staffid, firstname, lastname');
        $this->db->where('active', 1);
        $this->db->order_by('firstname', 'asc');
        $employees = $this->db->get(db_prefix() . 'staff')->result_array();

        $this->db->select('userid, company');
        $this->db->order_by('company', 'asc');
        $customers = $this->db->get(db_prefix() . 'clients')->result_array();

        return [
            'departments' => $this->business_departments_model->get(),
            'statuses'    => $this->projects_model->get_project_statuses(),
            'employees'   => $employees,
            'customers'   => $customers,
        ];
    }

    /**
     * Everything the read-only Project Detail page needs, reusing
     * Projects_model's own bundle methods verbatim - the same reads the
     * native admin/projects/view/{id} page uses for its Overview/
     * Milestones/Files/Discussions/Activity tabs. Tasks are queried
     * directly against tbltasks (rel_id/rel_type='project') - Tasks_model
     * has no single named "tasks for this project" method of its own (the
     * native Tasks tab is rendered via the App_table 'tasks' registration
     * with a raw rel_id filter, not a PHP method), so this mirrors that
     * exact filter rather than inventing a new Tasks_model method for it.
     *
     * @param  int $id
     * @return array|null null if the project doesn't exist
     */
    public function get_project_detail($id)
    {
        $this->load->model('projects_model');

        $project = $this->projects_model->get($id);

        if (!$project) {
            return null;
        }

        $this->db->select('id, name, status, priority, duedate, datefinished');
        $this->db->where('rel_id', $id);
        $this->db->where('rel_type', 'project');
        $this->db->order_by('duedate', 'asc');
        $tasks = $this->db->get(db_prefix() . 'tasks')->result_array();

        $this->db->select('s.firstname, s.lastname, s.staffid, s.profile_image');
        $this->db->from(db_prefix() . 'project_members pm');
        $this->db->join(db_prefix() . 'staff s', 's.staffid = pm.staff_id');
        $this->db->where('pm.project_id', $id);
        $members = $this->db->get()->result_array();

        return [
            'project'     => $project,
            'members'     => $members,
            'milestones'  => $this->projects_model->get_milestones($id),
            'files'       => $this->projects_model->get_files($id),
            'discussions' => $this->projects_model->get_discussions($id),
            'activity'    => $this->projects_model->get_activity($id, 20),
            'tasks'       => $tasks,
        ];
    }

    /**
     * Company-wide Tasks list, filterable - built directly against
     * tbltasks/tbltask_assigned, same containment reasoning as
     * get_projects() above.
     *
     * @param  array $filters department_id, employee_id (assignee),
     *                        status, priority, project_id, date_from,
     *                        date_to (against duedate)
     * @return array
     */
    public function get_tasks($filters = [])
    {
        $this->db->select('t.id, t.name, t.status, t.priority, t.duedate, t.datefinished, t.dateadded, t.rel_id, t.rel_type, t.manager_note');
        $this->db->from(db_prefix() . 'tasks t');

        if (!empty($filters['employee_id'])) {
            $this->db->where('t.id IN (SELECT taskid FROM ' . db_prefix() . 'task_assigned WHERE staffid = ' . (int) $filters['employee_id'] . ')', null, false);
        }
        if (!empty($filters['department_id'])) {
            $this->load->model('business_departments/business_departments_model');
            $staff_ids = $this->business_departments_model->get_department_staff_ids($filters['department_id']);
            $this->db->where('t.id IN (SELECT taskid FROM ' . db_prefix() . 'task_assigned WHERE staffid IN (' . (count($staff_ids) ? implode(',', array_map('intval', $staff_ids)) : '0') . '))', null, false);
        }
        if (!empty($filters['status'])) {
            $this->db->where('t.status', $filters['status']);
        }
        if (!empty($filters['priority'])) {
            $this->db->where('t.priority', $filters['priority']);
        }
        if (!empty($filters['project_id'])) {
            $this->db->where('t.rel_id', $filters['project_id']);
            $this->db->where('t.rel_type', 'project');
        }
        if (!empty($filters['date_from'])) {
            $this->db->where('t.duedate >=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $this->db->where('t.duedate <=', $filters['date_to']);
        }

        $today = date('Y-m-d');

        // The following 4 filters exist purely so the Dashboard's KPI
        // cards can deep-link here with the exact same definitions
        // get_dashboard_summary() already counts by - not exposed as their
        // own filter-bar controls, same GET-param-prefill precedent as
        // Follow-up Management's My Follow-ups widget links.
        if (!empty($filters['active'])) {
            if ($filters['active'] === 'active') {
                $this->db->where('t.status !=', Tasks_model::STATUS_COMPLETE);
            } elseif ($filters['active'] === 'completed') {
                $this->db->where('t.status', Tasks_model::STATUS_COMPLETE);
            }
        }
        if (!empty($filters['pending'])) {
            $this->db->where('t.status !=', Tasks_model::STATUS_COMPLETE);
            $this->db->where('(t.duedate IS NULL OR t.duedate >= ' . $this->db->escape($today) . ')', null, false);
        }
        if (!empty($filters['overdue'])) {
            $this->db->where('t.status !=', Tasks_model::STATUS_COMPLETE);
            $this->db->where('t.duedate <', $today);
        }
        if (!empty($filters['completed_today'])) {
            $this->db->where('t.status', Tasks_model::STATUS_COMPLETE);
            $this->db->where('DATE(t.datefinished) =', $today);
        }

        $this->db->order_by('t.duedate', 'asc');
        $tasks = $this->db->get()->result_array();

        foreach ($tasks as &$task) {
            $this->db->select('s.staffid, s.firstname, s.lastname');
            $this->db->from(db_prefix() . 'task_assigned ta');
            $this->db->join(db_prefix() . 'staff s', 's.staffid = ta.staffid');
            $this->db->where('ta.taskid', $task['id']);
            $assignees            = $this->db->get()->result_array();
            $task['assignee_names'] = array_map(function ($assignee) {
                return trim($assignee['firstname'] . ' ' . $assignee['lastname']);
            }, $assignees);

            $task['project_name'] = null;
            if ($task['rel_type'] === 'project') {
                $this->db->select('name');
                $this->db->where('id', $task['rel_id']);
                $project               = $this->db->get(db_prefix() . 'projects')->row();
                $task['project_name'] = $project ? $project->name : null;
            }

            // tbltasks has no "last updated" column of its own - the most
            // recent comment or checklist change is the closest existing
            // signal, same read the native Activity tab derives its own
            // "last activity" from.
            $this->db->select_max('dateadded', 'last_comment_at');
            $this->db->where('taskid', $task['id']);
            $last_comment                = $this->db->get(db_prefix() . 'task_comments')->row();
            $task['last_updated']        = ($last_comment && $last_comment->last_comment_at) ? $last_comment->last_comment_at : $task['dateadded'];

            $task['overdue'] = $task['duedate'] && $task['duedate'] < $today && (int) $task['status'] !== Tasks_model::STATUS_COMPLETE;
        }

        return $tasks;
    }

    /**
     * Filter dropdown options for the Tasks page - statuses/priorities
     * reuse Tasks_model's own native lists (Tasks_model::get_statuses(),
     * application/helpers/tasks_helper.php's get_tasks_priorities()),
     * projects and employees are the same company-wide lists Projects
     * already exposes.
     *
     * @return array
     */
    public function get_task_filter_options()
    {
        $this->load->model('tasks_model');
        $this->load->model('business_departments/business_departments_model');

        $this->db->select('staffid, firstname, lastname');
        $this->db->where('active', 1);
        $this->db->order_by('firstname', 'asc');
        $employees = $this->db->get(db_prefix() . 'staff')->result_array();

        $this->db->select('id, name');
        $this->db->order_by('name', 'asc');
        $projects = $this->db->get(db_prefix() . 'projects')->result_array();

        return [
            'departments' => $this->business_departments_model->get(),
            'statuses'    => $this->tasks_model->get_statuses(),
            'priorities'  => get_tasks_priorities(),
            'employees'   => $employees,
            'projects'    => $projects,
        ];
    }

    /**
     * Everything the read-only Task Detail page needs - a single call to
     * Tasks_model::get($id), the exact bundle method the native task
     * modal itself uses (comments/assignees/followers/attachments/
     * checklist_items/project_data all included already) - no new query
     * shape invented here at all.
     *
     * @param  int $id
     * @return object|null
     */
    public function get_task_detail($id)
    {
        $this->load->model('tasks_model');

        return $this->tasks_model->get($id);
    }

    /**
     * The one field on tbltasks this module is allowed to write - a
     * private Manager-only annotation (migration 377), distinct from the
     * public tbltask_comments thread that add_task_comment() already
     * covers. Deliberately its own single-column update rather than a
     * generic task-update passthrough, so this module can never touch
     * assignee/project/status/etc.
     *
     * @param  int    $id
     * @param  string $note
     * @return bool
     */
    public function set_task_manager_note($id, $note)
    {
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'tasks', ['manager_note' => $note]);

        return $this->db->affected_rows() > 0;
    }

    /**
     * Company-wide Daily Work Update submissions, filterable - scoped to
     * the same get_scope_staff_ids() population the Dashboard's own
     * "Daily Work Updates Submitted/Pending" cards and "Needs Your
     * Attention" panel already use (every active staff member except the
     * caller), so this page's numbers can never disagree with the
     * Dashboard's. Built directly against tbldm_portal_work_updates - the
     * shared Daily Work Update module's own table - never a copy.
     *
     * @param  int   $staff_id caller (excluded from scope, same as dashboard)
     * @param  array $filters  department_id, employee_id, review_status
     *                         ('pending'|'approved'|'needs_revision'),
     *                         date_preset ('today'|'yesterday'|'this_week'),
     *                         date_from, date_to
     * @return array
     */
    public function get_dwu_list($staff_id, $filters = [])
    {
        $team_ids = $this->get_scope_staff_ids($staff_id);

        if (!count($team_ids)) {
            return [];
        }

        $this->db->select('dwu.id, dwu.staff_id, dwu.work_date, dwu.today_work, dwu.tomorrow_plan, dwu.issues, dwu.created_at, dwu.review_status, dwu.review_comment, dwu.reviewed_by, dwu.reviewed_at, staff.firstname, staff.lastname, reviewer.firstname as reviewer_firstname, reviewer.lastname as reviewer_lastname, att.login_time, att.logout_time');
        $this->db->from(db_prefix() . 'dm_portal_work_updates dwu');
        $this->db->join(db_prefix() . 'staff staff', 'staff.staffid = dwu.staff_id');
        $this->db->join(db_prefix() . 'staff reviewer', 'reviewer.staffid = dwu.reviewed_by', 'left');
        $this->db->join(db_prefix() . 'staff_attendance att', 'att.staff_id = dwu.staff_id AND att.attendance_date = dwu.work_date', 'left');
        $this->db->where_in('dwu.staff_id', $team_ids);

        if (!empty($filters['department_id'])) {
            $this->db->where('dwu.staff_id IN (SELECT staff_id FROM ' . db_prefix() . 'business_department_staff WHERE department_id = ' . (int) $filters['department_id'] . ')', null, false);
        }
        if (!empty($filters['employee_id'])) {
            $this->db->where('dwu.staff_id', $filters['employee_id']);
        }
        if (!empty($filters['review_status'])) {
            $this->db->where('dwu.review_status', $filters['review_status']);
        }
        // Dashboard-only deep-link param (see get_dwu_review_summary()'s
        // "Approved Today" figure) - filters by WHEN it was reviewed,
        // distinct from date_preset/date_from/date_to which filter by the
        // submission's own work_date.
        if (!empty($filters['reviewed_today'])) {
            $this->db->where('DATE(dwu.reviewed_at)', date('Y-m-d'));
        }

        $this->apply_dwu_date_filters('dwu.work_date', $filters);

        $this->db->order_by('dwu.work_date', 'desc');
        $this->db->order_by('dwu.created_at', 'desc');
        $rows = $this->db->get()->result_array();

        foreach ($rows as &$row) {
            $row['employee_name']  = trim($row['firstname'] . ' ' . $row['lastname']);
            $row['reviewer_name']  = $row['reviewed_by'] ? trim($row['reviewer_firstname'] . ' ' . $row['reviewer_lastname']) : null;
            $row['department_name'] = $this->get_staff_department_names($row['staff_id']);
            $row['total_tasks']    = $this->db->where('staffid', $row['staff_id'])->count_all_results(db_prefix() . 'task_assigned');
        }

        return $rows;
    }

    /**
     * Team members scoped by get_scope_staff_ids() who have NOT submitted
     * a Daily Work Update for today - the list form of the Dashboard's
     * existing "Missing Daily Work Updates" figure (get_daily_work_update_summary()'s
     * "pending" count), same staff-driven LEFT JOIN technique the native
     * report_table()'s own "Not Submitted Today" branch already
     * established, reused here rather than reinvented.
     *
     * @param  int   $staff_id
     * @param  array $filters department_id, employee_id
     * @return array
     */
    public function get_dwu_missing($staff_id, $filters = [])
    {
        $team_ids = $this->get_scope_staff_ids($staff_id);

        if (!count($team_ids)) {
            return [];
        }

        $this->db->select('staff.staffid, staff.firstname, staff.lastname');
        $this->db->from(db_prefix() . 'staff staff');
        $this->db->join(
            db_prefix() . 'dm_portal_work_updates dwu',
            'dwu.staff_id = staff.staffid AND dwu.work_date = ' . $this->db->escape(date('Y-m-d')),
            'left'
        );
        $this->db->where_in('staff.staffid', $team_ids);
        $this->db->where('dwu.id IS NULL', null, false);

        if (!empty($filters['department_id'])) {
            $this->db->where('staff.staffid IN (SELECT staff_id FROM ' . db_prefix() . 'business_department_staff WHERE department_id = ' . (int) $filters['department_id'] . ')', null, false);
        }
        if (!empty($filters['employee_id'])) {
            $this->db->where('staff.staffid', $filters['employee_id']);
        }

        $this->db->order_by('staff.firstname', 'asc');
        $rows = $this->db->get()->result_array();

        foreach ($rows as &$row) {
            $row['employee_name']   = trim($row['firstname'] . ' ' . $row['lastname']);
            $row['department_name'] = $this->get_staff_department_names($row['staffid']);
            $row['total_tasks']     = $this->db->where('staffid', $row['staffid'])->count_all_results(db_prefix() . 'task_assigned');
        }

        return $rows;
    }

    /**
     * Comma-joined department names for one staff member - small display
     * lookup shared by get_dwu_list()/get_dwu_missing() so the Department
     * column is never computed two different ways.
     *
     * @param  int $staff_id
     * @return string|null
     */
    private function get_staff_department_names($staff_id)
    {
        $this->db->select('bd.name');
        $this->db->from(db_prefix() . 'business_department_staff bds');
        $this->db->join(db_prefix() . 'business_departments bd', 'bd.id = bds.department_id');
        $this->db->where('bds.staff_id', $staff_id);
        $names = array_column($this->db->get()->result_array(), 'name');

        return count($names) ? implode(', ', $names) : null;
    }

    /**
     * Applies the Today/Yesterday/This Week presets or an explicit
     * date_from/date_to range to whichever query is currently building -
     * shared by get_dwu_list() (and available for any future DWU query
     * that needs the same 3 presets), so the date-window logic exists in
     * exactly one place.
     *
     * @param  string $column  fully-qualified date column
     * @param  array  $filters date_preset, date_from, date_to
     * @return void
     */
    private function apply_dwu_date_filters($column, $filters)
    {
        if (!empty($filters['date_preset'])) {
            if ($filters['date_preset'] === 'today') {
                $this->db->where($column, date('Y-m-d'));
            } elseif ($filters['date_preset'] === 'yesterday') {
                $this->db->where($column, date('Y-m-d', strtotime('-1 day')));
            } elseif ($filters['date_preset'] === 'this_week') {
                $this->db->where($column . ' >=', date('Y-m-d', strtotime('monday this week')));
                $this->db->where($column . ' <=', date('Y-m-d', strtotime('sunday this week')));
            }

            return;
        }

        if (!empty($filters['date_from'])) {
            $this->db->where($column . ' >=', to_sql_date($filters['date_from']));
        }
        if (!empty($filters['date_to'])) {
            $this->db->where($column . ' <=', to_sql_date($filters['date_to']));
        }
    }

    /**
     * Filter dropdown options for the Daily Work Updates page - reuses
     * Daily_work_update_model's own report filter methods verbatim
     * (the exact same Department/Employee lists the native Admin report
     * already offers), no duplicate query logic.
     *
     * @return array
     */
    public function get_dwu_filter_options()
    {
        $this->load->model('daily_work_update/daily_work_update_model');

        return [
            'departments' => $this->daily_work_update_model->get_report_filter_departments(),
            'employees'   => $this->daily_work_update_model->get_report_filter_employees(),
        ];
    }

    /**
     * Full submission detail - a thin wrapper around
     * Daily_work_update_model's own get_report_row()/get_attachments(),
     * the exact same reads the native Admin report's detail modal already
     * uses. No duplicate layout/query.
     *
     * @param  int $id
     * @return array|null
     */
    public function get_dwu_detail($id)
    {
        $this->load->model('daily_work_update/daily_work_update_model');

        $row = $this->daily_work_update_model->get_report_row($id);

        if (!$row) {
            return null;
        }

        // get_report_row() doesn't select the reviewer's name (the native
        // report detail modal it also serves has no need for it) - looked
        // up here rather than widening that shared method's own SELECT.
        $row->reviewer_firstname = null;
        $row->reviewer_lastname  = null;
        if ($row->reviewed_by) {
            $this->db->select('firstname, lastname');
            $this->db->where('staffid', $row->reviewed_by);
            $reviewer = $this->db->get(db_prefix() . 'staff')->row();
            if ($reviewer) {
                $row->reviewer_firstname = $reviewer->firstname;
                $row->reviewer_lastname  = $reviewer->lastname;
            }
        }

        return [
            'row'         => $row,
            'attachments' => $this->daily_work_update_model->get_attachments($id),
        ];
    }

    /**
     * The 5 Daily Work Update review KPIs for the Dashboard - Pending
     * Reviews/Needs Revision are review-queue depth (all-time, not just
     * today - a stale unreviewed submission from last week should still
     * count), Approved Today/Today's Submissions/Missing are today-scoped.
     * Scoped to get_scope_staff_ids() like every other DWU figure on this
     * dashboard.
     *
     * @param  int $staff_id
     * @return array
     */
    public function get_dwu_review_summary($staff_id)
    {
        $team_ids = $this->get_scope_staff_ids($staff_id);
        $today    = date('Y-m-d');

        if (!count($team_ids)) {
            return [
                'pending_reviews'   => 0,
                'approved_today'    => 0,
                'needs_revision'    => 0,
                'todays_submissions' => 0,
                'missing_today'     => 0,
            ];
        }

        $this->db->where_in('staff_id', $team_ids);
        $this->db->where('review_status', 'pending');
        $pending_reviews = $this->db->count_all_results(db_prefix() . 'dm_portal_work_updates');

        $this->db->where_in('staff_id', $team_ids);
        $this->db->where('review_status', 'approved');
        $this->db->where('DATE(reviewed_at)', $today);
        $approved_today = $this->db->count_all_results(db_prefix() . 'dm_portal_work_updates');

        $this->db->where_in('staff_id', $team_ids);
        $this->db->where('review_status', 'needs_revision');
        $needs_revision = $this->db->count_all_results(db_prefix() . 'dm_portal_work_updates');

        $this->db->where_in('staff_id', $team_ids);
        $this->db->where('work_date', $today);
        $todays_submissions = $this->db->count_all_results(db_prefix() . 'dm_portal_work_updates');

        return [
            'pending_reviews'    => $pending_reviews,
            'approved_today'     => $approved_today,
            'needs_revision'     => $needs_revision,
            'todays_submissions' => $todays_submissions,
            'missing_today'      => count($team_ids) - $todays_submissions,
        ];
    }

    /**
     * Company-wide Attendance, filterable - scoped to get_scope_staff_ids()
     * like every other page in this module. Two distinct query shapes,
     * chosen by the date filter, same duality the native
     * Staff_attendance::table()/get_month_calendar_data() split already
     * establishes:
     *
     * - Single date (default: today; or 'yesterday'; or an explicit
     *   $filters['date']): staff-driven LEFT JOIN against
     *   tblstaff_attendance for that one date, so an employee with no row
     *   shows as Absent - the exact technique
     *   Staff_attendance_model::get_today_summary() already uses, applied
     *   here per-row instead of as a count.
     * - 'this_week'/'this_month': real tblstaff_attendance rows only
     *   within that range (no synthetic Absent rows for a multi-day
     *   range - the same "only real rows" limitation
     *   get_month_calendar_data() itself already has for the calendar).
     *
     * @param  int   $staff_id
     * @param  array $filters department_id, employee_id, status
     *                        ('Present'|'Absent'|'Late'|'Half Day'),
     *                        date (explicit single date), date_preset
     *                        ('today'|'yesterday'|'this_week'|'this_month')
     * @return array
     */
    public function get_attendance_list($staff_id, $filters = [])
    {
        $team_ids = $this->get_scope_staff_ids($staff_id);

        if (!count($team_ids)) {
            return [];
        }

        $preset = $filters['date_preset'] ?? '';

        if (in_array($preset, ['this_week', 'this_month'], true)) {
            return $this->get_attendance_range($team_ids, $filters, $preset);
        }

        return $this->get_attendance_single_date($team_ids, $filters);
    }

    /**
     * Single-date branch of get_attendance_list() - see that method's own
     * docblock.
     *
     * @param  array $team_ids
     * @param  array $filters
     * @return array
     */
    private function get_attendance_single_date($team_ids, $filters)
    {
        if (!empty($filters['date'])) {
            $date = to_sql_date($filters['date']);
        } elseif (($filters['date_preset'] ?? '') === 'yesterday') {
            $date = date('Y-m-d', strtotime('-1 day'));
        } else {
            $date = date('Y-m-d');
        }

        $this->db->select('staff.staffid, staff.firstname, staff.lastname, att.login_time, att.logout_time, att.working_minutes, att.attendance_status');
        $this->db->from(db_prefix() . 'staff staff');
        $this->db->join(
            db_prefix() . 'staff_attendance att',
            'att.staff_id = staff.staffid AND att.attendance_date = ' . $this->db->escape($date),
            'left'
        );
        $this->db->where_in('staff.staffid', $team_ids);
        $this->db->where('staff.active', 1);

        if (!empty($filters['department_id'])) {
            $this->db->where('staff.staffid IN (SELECT staff_id FROM ' . db_prefix() . 'business_department_staff WHERE department_id = ' . (int) $filters['department_id'] . ')', null, false);
        }
        if (!empty($filters['employee_id'])) {
            $this->db->where('staff.staffid', $filters['employee_id']);
        }

        $this->db->order_by('staff.firstname', 'asc');
        $rows = $this->db->get()->result_array();

        $result = [];
        foreach ($rows as $row) {
            $status = $row['attendance_status'] ?: 'Absent';

            if (!empty($filters['status']) && $filters['status'] !== $status) {
                continue;
            }

            $result[] = [
                'staff_id'         => $row['staffid'],
                'employee_name'    => trim($row['firstname'] . ' ' . $row['lastname']),
                'department_name'  => $this->get_staff_department_names($row['staffid']),
                'date'             => $date,
                'login_time'       => $row['login_time'],
                'logout_time'      => $row['logout_time'],
                'working_minutes'  => $row['working_minutes'],
                'status'           => $status,
                'late_arrival'     => manager_portal_attendance_is_late_arrival($row['login_time']),
                'early_exit'       => manager_portal_attendance_is_early_exit($row['logout_time']),
            ];
        }

        return $result;
    }

    /**
     * Range branch of get_attendance_list() - see that method's own
     * docblock. Real tblstaff_attendance rows only.
     *
     * @param  array  $team_ids
     * @param  array  $filters
     * @param  string $preset 'this_week'|'this_month'
     * @return array
     */
    private function get_attendance_range($team_ids, $filters, $preset)
    {
        if ($preset === 'this_week') {
            $start = date('Y-m-d', strtotime('monday this week'));
            $end   = date('Y-m-d', strtotime('sunday this week'));
        } else {
            $start = date('Y-m-01');
            $end   = date('Y-m-t');
        }

        $this->db->select('att.staff_id, staff.firstname, staff.lastname, att.attendance_date, att.login_time, att.logout_time, att.working_minutes, att.attendance_status');
        $this->db->from(db_prefix() . 'staff_attendance att');
        $this->db->join(db_prefix() . 'staff staff', 'staff.staffid = att.staff_id');
        $this->db->where_in('att.staff_id', $team_ids);
        $this->db->where('att.attendance_date >=', $start);
        $this->db->where('att.attendance_date <=', $end);

        if (!empty($filters['department_id'])) {
            $this->db->where('att.staff_id IN (SELECT staff_id FROM ' . db_prefix() . 'business_department_staff WHERE department_id = ' . (int) $filters['department_id'] . ')', null, false);
        }
        if (!empty($filters['employee_id'])) {
            $this->db->where('att.staff_id', $filters['employee_id']);
        }
        if (!empty($filters['status'])) {
            $this->db->where('att.attendance_status', $filters['status']);
        }

        $this->db->order_by('att.attendance_date', 'desc');
        $rows = $this->db->get()->result_array();

        $result = [];
        foreach ($rows as $row) {
            $result[] = [
                'staff_id'        => $row['staff_id'],
                'employee_name'   => trim($row['firstname'] . ' ' . $row['lastname']),
                'department_name' => $this->get_staff_department_names($row['staff_id']),
                'date'            => $row['attendance_date'],
                'login_time'      => $row['login_time'],
                'logout_time'     => $row['logout_time'],
                'working_minutes' => $row['working_minutes'],
                'status'          => $row['attendance_status'],
                'late_arrival'    => manager_portal_attendance_is_late_arrival($row['login_time']),
                'early_exit'      => manager_portal_attendance_is_early_exit($row['logout_time']),
            ];
        }

        return $result;
    }

    /**
     * Team members with no check-in today - the list form of the
     * Dashboard's "Missing Attendance" section, same staff-driven
     * LEFT JOIN technique as get_attendance_single_date() above (and the
     * same shape as get_dwu_missing() from Phase 4), scoped to
     * get_scope_staff_ids().
     *
     * @param  int $staff_id
     * @return array
     */
    public function get_attendance_missing($staff_id)
    {
        $team_ids = $this->get_scope_staff_ids($staff_id);

        if (!count($team_ids)) {
            return [];
        }

        $this->db->select('staff.staffid, staff.firstname, staff.lastname');
        $this->db->from(db_prefix() . 'staff staff');
        $this->db->join(
            db_prefix() . 'staff_attendance att',
            'att.staff_id = staff.staffid AND att.attendance_date = ' . $this->db->escape(date('Y-m-d')),
            'left'
        );
        $this->db->where_in('staff.staffid', $team_ids);
        $this->db->where('staff.active', 1);
        $this->db->where('att.id IS NULL', null, false);
        $this->db->order_by('staff.firstname', 'asc');
        $rows = $this->db->get()->result_array();

        foreach ($rows as &$row) {
            $row['staff_id']         = $row['staffid'];
            $row['employee_name']    = trim($row['firstname'] . ' ' . $row['lastname']);
            $row['department_name']  = $this->get_staff_department_names($row['staffid']);
        }

        return $rows;
    }

    /**
     * Filter dropdown options for the Attendance page - departments/
     * employees, same company-wide scope as every other filter-options
     * method in this module.
     *
     * @return array
     */
    public function get_attendance_filter_options()
    {
        $this->load->model('business_departments/business_departments_model');

        $this->db->select('staffid, firstname, lastname');
        $this->db->where('active', 1);
        $this->db->order_by('firstname', 'asc');
        $employees = $this->db->get(db_prefix() . 'staff')->result_array();

        return [
            'departments' => $this->business_departments_model->get(),
            'employees'   => $employees,
        ];
    }

    /**
     * The 6 Attendance KPIs for the Dashboard - Present/Absent/Late/Half
     * Day Today reuse get_attendance_today_summary() verbatim (the exact
     * same per-team-id read the Dashboard's existing "Attendance Today"
     * card and "Employees Absent Today" panel already use - not a second
     * counting query). Attendance % and Average Working Hours are the
     * only genuinely new figures here, derived from a light aggregate
     * over today's real tblstaff_attendance rows.
     *
     * @param  int $staff_id
     * @return array
     */
    public function get_attendance_review_summary($staff_id)
    {
        $team_ids = $this->get_scope_staff_ids($staff_id);
        $counts   = $this->get_attendance_today_summary($team_ids);

        $total          = count($team_ids);
        $present        = $counts['Present'] ?? 0;
        $attendance_pct = $total > 0 ? (int) round(($present / $total) * 100) : 0;

        $avg_minutes = 0;
        if (count($team_ids)) {
            $this->db->select_avg('working_minutes');
            $this->db->where_in('staff_id', $team_ids);
            $this->db->where('attendance_date', date('Y-m-d'));
            $this->db->where('working_minutes IS NOT NULL', null, false);
            $avg_row     = $this->db->get(db_prefix() . 'staff_attendance')->row();
            $avg_minutes = $avg_row && $avg_row->working_minutes !== null ? (int) round($avg_row->working_minutes) : 0;
        }

        return [
            'present_today'       => $present,
            'absent_today'        => $counts['Absent'] ?? 0,
            'late_today'          => $counts['Late'] ?? 0,
            'half_day_today'      => $counts['Half Day'] ?? 0,
            'attendance_percent'  => $attendance_pct,
            'average_hours_label' => format_working_minutes($avg_minutes),
        ];
    }

    /**
     * Employee Attendance Detail bundle - a thin wrapper around
     * Staff_attendance_model's own get_month_calendar_data()/
     * get_month_summary() (the exact same reads the native Monthly
     * Attendance Dashboard fragment already uses) plus the staff record
     * and a "Recent Attendance" slice of the same calendar data, newest
     * first. No new attendance query logic - just reuse and formatting.
     *
     * @param  int $staff_id
     * @param  int $year
     * @param  int $month
     * @return array
     */
    public function get_attendance_detail($staff_id, $year, $month)
    {
        $this->load->model('staff_attendance/staff_attendance_model');
        $this->load->model('staff_model');

        $calendar = $this->staff_attendance_model->get_month_calendar_data($staff_id, $year, $month);
        $summary  = $this->staff_attendance_model->get_month_summary($staff_id, $year, $month);

        $recent = array_values($calendar);
        usort($recent, function ($a, $b) {
            return strcmp($b['attendance_date'], $a['attendance_date']);
        });

        return [
            'staff'    => $this->staff_model->get($staff_id),
            'calendar' => $calendar,
            'summary'  => $summary,
            'recent'   => array_slice($recent, 0, 10),
        ];
    }

    /**
     * Employee Productivity Report - one row per scoped staff member.
     * Attendance %/DWU Submitted are month-scoped (the date_from filter,
     * when given, picks WHICH month via the same get_month_summary()
     * every other attendance figure in this module already uses - this
     * report doesn't invent arbitrary-range attendance math). Tasks
     * Assigned/Completed/Active Projects reuse the exact same
     * subquery shapes get_team_members() already uses. Completion Rate
     * reuses get_staff_productivity_percent() verbatim. "Productivity
     * Score" is this module's own composite (no external scoring system
     * exists to defer to, same reasoning as performance_label()'s own
     * docblock) - a simple average of Completion Rate/Attendance %/DWU
     * submission rate, documented here rather than left unexplained.
     *
     * @param  int   $staff_id
     * @param  array $filters department_id, employee_id, date_from
     *                        (picks the target month; defaults to the
     *                        current month)
     * @return array
     */
    public function get_employee_productivity_report($staff_id, $filters = [])
    {
        $team_ids = $this->get_scope_staff_ids($staff_id);

        if (!empty($filters['department_id'])) {
            $this->load->model('business_departments/business_departments_model');
            $dept_staff_ids = $this->business_departments_model->get_department_staff_ids($filters['department_id']);
            $team_ids       = array_values(array_intersect($team_ids, $dept_staff_ids));
        }
        if (!empty($filters['employee_id'])) {
            $team_ids = in_array((int) $filters['employee_id'], $team_ids, true) ? [(int) $filters['employee_id']] : [];
        }

        if (!count($team_ids)) {
            return [];
        }

        $target_date = !empty($filters['date_from']) ? to_sql_date($filters['date_from']) : date('Y-m-d');
        $year        = (int) date('Y', strtotime($target_date));
        $month       = (int) date('n', strtotime($target_date));
        $month_start = sprintf('%04d-%02d-01', $year, $month);
        $month_end   = date('Y-m-t', strtotime($month_start));

        $this->load->model('staff_attendance/staff_attendance_model');

        $this->db->select('staffid, firstname, lastname');
        $this->db->where_in('staffid', $team_ids);
        $this->db->order_by('firstname', 'asc');
        $staff_rows = $this->db->get(db_prefix() . 'staff')->result_array();

        $rows = [];
        foreach ($staff_rows as $staff) {
            $sid = (int) $staff['staffid'];

            $attendance = $this->staff_attendance_model->get_month_summary($sid, $year, $month);

            $this->db->where('staff_id', $sid);
            $this->db->where('work_date >=', $month_start);
            $this->db->where('work_date <=', $month_end);
            $dwu_submitted = $this->db->count_all_results(db_prefix() . 'dm_portal_work_updates');

            $this->db->where('id IN (SELECT taskid FROM ' . db_prefix() . 'task_assigned WHERE staffid = ' . $sid . ')', null, false);
            $tasks_assigned = $this->db->count_all_results(db_prefix() . 'tasks');

            $this->db->where('id IN (SELECT taskid FROM ' . db_prefix() . 'task_assigned WHERE staffid = ' . $sid . ')', null, false);
            $this->db->where('status', Tasks_model::STATUS_COMPLETE);
            $tasks_completed = $this->db->count_all_results(db_prefix() . 'tasks');

            $this->db->where('id IN (SELECT project_id FROM ' . db_prefix() . 'project_members WHERE staff_id = ' . $sid . ')', null, false);
            $this->db->where_not_in('status', [4, 5]);
            $active_projects = $this->db->count_all_results(db_prefix() . 'projects');

            $completion_rate = $this->get_staff_productivity_percent($sid);
            $dwu_rate        = $attendance['working_days'] > 0 ? min(100, (int) round(($dwu_submitted / $attendance['working_days']) * 100)) : 0;
            $productivity    = (int) round(($completion_rate + $attendance['attendance_rate'] + $dwu_rate) / 3);

            $rows[] = [
                'staff_id'           => $sid,
                'employee_name'      => trim($staff['firstname'] . ' ' . $staff['lastname']),
                'department_name'    => $this->get_staff_department_names($sid),
                'attendance_percent' => $attendance['attendance_rate'],
                'dwu_submitted'      => $dwu_submitted,
                'tasks_assigned'     => $tasks_assigned,
                'tasks_completed'    => $tasks_completed,
                'active_projects'    => $active_projects,
                'completion_rate'    => $completion_rate,
                'productivity_score' => $productivity,
            ];
        }

        return $rows;
    }

    /**
     * Department Performance Report - one row per business department
     * (or a single department when filtered), aggregating figures that
     * are each reused from an existing primitive: Attendance %/
     * Productivity % are the average of get_month_summary()'s
     * attendance_rate / get_staff_productivity_percent() across the
     * department's staff (same per-staff reads Employee Productivity
     * Report above and Team List already use); Active/Completed Projects
     * reuse tblprojects.department directly (the same column
     * get_projects() itself filters on).
     *
     * @param  array      $filters         department_id (optional, single department)
     * @param  array|null $all_departments pre-fetched Business_departments_model::get() result,
     *                                     to avoid re-running the same query the controller
     *                                     already ran for the filter dropdown; fetched internally
     *                                     when not supplied
     * @return array
     */
    public function get_department_performance_report($filters = [], $all_departments = null)
    {
        $this->load->model('business_departments/business_departments_model');
        $this->load->model('staff_attendance/staff_attendance_model');

        $departments = $all_departments !== null ? $all_departments : $this->business_departments_model->get();

        if (!empty($filters['department_id'])) {
            $departments = array_values(array_filter($departments, function ($department) use ($filters) {
                return (int) $department['id'] === (int) $filters['department_id'];
            }));
        }

        $today = date('Y-m-d');
        $year  = (int) date('Y');
        $month = (int) date('n');
        $rows  = [];

        foreach ($departments as $department) {
            $staff_ids      = $this->business_departments_model->get_department_staff_ids($department['id']);
            $employee_count = count($staff_ids);

            $this->db->where('department', $department['id']);
            $this->db->where_not_in('status', [4, 5]);
            $active_projects = $this->db->count_all_results(db_prefix() . 'projects');

            $this->db->where('department', $department['id']);
            $this->db->where_in('status', [4, 5]);
            $completed_projects = $this->db->count_all_results(db_prefix() . 'projects');

            $pending_tasks   = 0;
            $completed_tasks = 0;
            $attendance_pct  = 0;
            $productivity_pct = 0;
            $dwu_pct         = 0;

            if ($employee_count) {
                $ids_csv = implode(',', array_map('intval', $staff_ids));

                $this->db->where('id IN (SELECT taskid FROM ' . db_prefix() . 'task_assigned WHERE staffid IN (' . $ids_csv . '))', null, false);
                $this->db->where('status !=', Tasks_model::STATUS_COMPLETE);
                $pending_tasks = $this->db->count_all_results(db_prefix() . 'tasks');

                $this->db->where('id IN (SELECT taskid FROM ' . db_prefix() . 'task_assigned WHERE staffid IN (' . $ids_csv . '))', null, false);
                $this->db->where('status', Tasks_model::STATUS_COMPLETE);
                $completed_tasks = $this->db->count_all_results(db_prefix() . 'tasks');

                $attendance_total  = 0;
                $productivity_total = 0;
                foreach ($staff_ids as $sid) {
                    $attendance_total  += $this->staff_attendance_model->get_month_summary($sid, $year, $month)['attendance_rate'];
                    $productivity_total += $this->get_staff_productivity_percent($sid);
                }
                $attendance_pct  = (int) round($attendance_total / $employee_count);
                $productivity_pct = (int) round($productivity_total / $employee_count);

                $this->db->where_in('staff_id', $staff_ids);
                $this->db->where('work_date', $today);
                $dwu_submitted_today = $this->db->count_all_results(db_prefix() . 'dm_portal_work_updates');
                $dwu_pct             = (int) round(($dwu_submitted_today / $employee_count) * 100);
            }

            $rows[] = [
                'department_id'       => $department['id'],
                'department_name'     => $department['name'],
                'employees'           => $employee_count,
                'active_projects'     => $active_projects,
                'completed_projects'  => $completed_projects,
                'pending_tasks'       => $pending_tasks,
                'completed_tasks'     => $completed_tasks,
                'attendance_percent'  => $attendance_pct,
                'dwu_percent'         => $dwu_pct,
                'productivity_percent' => $productivity_pct,
            ];
        }

        return $rows;
    }

    /**
     * Project Performance Report - a thin wrapper around get_projects()
     * (Phase 3, reused verbatim - same columns/filters), enriched with
     * per-project Completed/Pending/Overdue task counts (the same
     * rel_id/rel_type='project' query project_detail() already uses for
     * its own Tasks tab, just aggregated instead of listed).
     *
     * @param  array $filters same shape as get_projects()
     * @return array
     */
    public function get_project_performance_report($filters = [])
    {
        $projects = $this->get_projects($filters);
        $today    = date('Y-m-d');

        foreach ($projects as &$project) {
            $this->db->where('rel_id', $project['id']);
            $this->db->where('rel_type', 'project');
            $this->db->where('status', Tasks_model::STATUS_COMPLETE);
            $project['completed_tasks'] = $this->db->count_all_results(db_prefix() . 'tasks');

            $this->db->where('rel_id', $project['id']);
            $this->db->where('rel_type', 'project');
            $this->db->where('status !=', Tasks_model::STATUS_COMPLETE);
            $project['pending_tasks'] = $this->db->count_all_results(db_prefix() . 'tasks');

            $this->db->where('rel_id', $project['id']);
            $this->db->where('rel_type', 'project');
            $this->db->where('status !=', Tasks_model::STATUS_COMPLETE);
            $this->db->where('duedate <', $today);
            $project['overdue_task_count'] = $this->db->count_all_results(db_prefix() . 'tasks');
        }

        return $projects;
    }

    /**
     * Attendance Analytics - Present vs Absent Today reuses
     * get_attendance_today_summary() verbatim (same figure the Dashboard's
     * own "Attendance Today" card already shows). Late Arrivals/Trend are
     * genuinely new per-day aggregates (7/14 days) built on real
     * tblstaff_attendance rows - no existing method covers a multi-day
     * series. Monthly Attendance % per department reuses the exact same
     * per-staff get_month_summary() average as Department Performance
     * Report's own Attendance % column.
     *
     * @param  int $staff_id
     * @return array
     */
    public function get_attendance_analytics($staff_id)
    {
        $team_ids = $this->get_scope_staff_ids($staff_id);

        $today_summary = $this->get_attendance_today_summary($team_ids);

        $late_by_day = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime('-' . $i . ' days'));
            $late_count = 0;

            if (count($team_ids)) {
                $this->db->select('login_time');
                $this->db->where_in('staff_id', $team_ids);
                $this->db->where('attendance_date', $date);
                $day_rows = $this->db->get(db_prefix() . 'staff_attendance')->result_array();

                foreach ($day_rows as $day_row) {
                    if (manager_portal_attendance_is_late_arrival($day_row['login_time'])) {
                        $late_count++;
                    }
                }
            }

            $late_by_day[] = ['date' => $date, 'count' => $late_count];
        }

        $trend = [];
        for ($i = 13; $i >= 0; $i--) {
            $date  = date('Y-m-d', strtotime('-' . $i . ' days'));
            $count = 0;

            if (count($team_ids)) {
                $this->db->where_in('staff_id', $team_ids);
                $this->db->where('attendance_date', $date);
                $this->db->where('attendance_status', 'Present');
                $count = $this->db->count_all_results(db_prefix() . 'staff_attendance');
            }

            $trend[] = ['date' => $date, 'count' => $count];
        }

        $this->load->model('business_departments/business_departments_model');
        $this->load->model('staff_attendance/staff_attendance_model');
        $year        = (int) date('Y');
        $month       = (int) date('n');
        $departments = $this->business_departments_model->get();
        $dept_rates  = [];

        foreach ($departments as $department) {
            $staff_ids = array_values(array_intersect($this->business_departments_model->get_department_staff_ids($department['id']), $team_ids));

            if (!count($staff_ids)) {
                $dept_rates[] = ['name' => $department['name'], 'rate' => 0];

                continue;
            }

            $total = 0;
            foreach ($staff_ids as $sid) {
                $total += $this->staff_attendance_model->get_month_summary($sid, $year, $month)['attendance_rate'];
            }

            $dept_rates[] = ['name' => $department['name'], 'rate' => (int) round($total / count($staff_ids))];
        }

        return [
            'today_summary'    => $today_summary,
            'late_by_day'      => $late_by_day,
            'trend'            => $trend,
            'department_rates' => $dept_rates,
        ];
    }

    /**
     * Daily Work Update Analytics - Pending/Approved/Needs Revision are
     * plain review_status counts (company-wide review-queue state, same
     * definitions get_dwu_review_summary()'s Dashboard KPIs already use);
     * "Submitted" here is the all-time submission count (distinct from
     * that method's "todays_submissions"). Department-wise summary is
     * this month's submission count per department.
     *
     * @param  int $staff_id
     * @return array
     */
    public function get_dwu_analytics($staff_id)
    {
        $team_ids = $this->get_scope_staff_ids($staff_id);

        $total_submitted = 0;
        $pending         = 0;
        $approved        = 0;
        $needs_revision  = 0;

        if (count($team_ids)) {
            $this->db->where_in('staff_id', $team_ids);
            $total_submitted = $this->db->count_all_results(db_prefix() . 'dm_portal_work_updates');

            $this->db->where_in('staff_id', $team_ids);
            $this->db->where('review_status', 'pending');
            $pending = $this->db->count_all_results(db_prefix() . 'dm_portal_work_updates');

            $this->db->where_in('staff_id', $team_ids);
            $this->db->where('review_status', 'approved');
            $approved = $this->db->count_all_results(db_prefix() . 'dm_portal_work_updates');

            $this->db->where_in('staff_id', $team_ids);
            $this->db->where('review_status', 'needs_revision');
            $needs_revision = $this->db->count_all_results(db_prefix() . 'dm_portal_work_updates');
        }

        $this->load->model('business_departments/business_departments_model');
        $departments  = $this->business_departments_model->get();
        $month_start  = date('Y-m-01');
        $month_end    = date('Y-m-t');
        $dept_summary = [];

        foreach ($departments as $department) {
            $staff_ids = array_values(array_intersect($this->business_departments_model->get_department_staff_ids($department['id']), $team_ids));
            $count     = 0;

            if (count($staff_ids)) {
                $this->db->where_in('staff_id', $staff_ids);
                $this->db->where('work_date >=', $month_start);
                $this->db->where('work_date <=', $month_end);
                $count = $this->db->count_all_results(db_prefix() . 'dm_portal_work_updates');
            }

            $dept_summary[] = ['name' => $department['name'], 'count' => $count];
        }

        return [
            'total_submitted' => $total_submitted,
            'pending'         => $pending,
            'approved'        => $approved,
            'needs_revision'  => $needs_revision,
            'department_summary' => $dept_summary,
        ];
    }

    /**
     * Task Analytics - Completed/Pending/Overdue reuse get_tasks_where()
     * verbatim (the exact same team scoping condition the Dashboard's own
     * task KPIs already build on). Priority Distribution reuses
     * get_tasks_priorities() (application/helpers/tasks_helper.php) for
     * both the label set and the color, rather than a new priority-name
     * lookup.
     *
     * @param  int $staff_id
     * @return array
     */
    public function get_task_analytics($staff_id)
    {
        $team_ids   = $this->get_scope_staff_ids($staff_id);
        $tasks_where = $this->get_tasks_where($team_ids);

        $this->db->where($tasks_where, null, false);
        $this->db->where('status', Tasks_model::STATUS_COMPLETE);
        $completed = $this->db->count_all_results(db_prefix() . 'tasks');

        $this->db->where($tasks_where, null, false);
        $this->db->where('status !=', Tasks_model::STATUS_COMPLETE);
        $pending = $this->db->count_all_results(db_prefix() . 'tasks');

        $this->db->where($tasks_where, null, false);
        $this->db->where('status !=', Tasks_model::STATUS_COMPLETE);
        $this->db->where('duedate <', date('Y-m-d'));
        $overdue = $this->db->count_all_results(db_prefix() . 'tasks');

        $priority_distribution = [];
        foreach (get_tasks_priorities() as $priority) {
            $this->db->where($tasks_where, null, false);
            $this->db->where('priority', $priority['id']);
            $count = $this->db->count_all_results(db_prefix() . 'tasks');

            $priority_distribution[] = ['name' => $priority['name'], 'count' => $count, 'color' => $priority['color']];
        }

        return [
            'completed'             => $completed,
            'pending'               => $pending,
            'overdue'               => $overdue,
            'priority_distribution' => $priority_distribution,
        ];
    }

    /**
     * Project Analytics - Project Status reuses
     * Projects_model::get_project_statuses() for both labels and colors
     * (the exact list the native project view already uses). Department
     * Distribution reuses tblprojects.department directly, same as
     * Department Performance Report's own Active/Completed Projects
     * counts. Completion Trend (6 months) is genuinely new - no existing
     * method aggregates date_finished by month.
     *
     * @return array
     */
    public function get_project_analytics()
    {
        $this->load->model('projects_model');

        $status_distribution = [];
        foreach ($this->projects_model->get_project_statuses() as $status) {
            $this->db->where('status', $status['id']);
            $count = $this->db->count_all_results(db_prefix() . 'projects');

            $status_distribution[] = ['name' => $status['name'], 'count' => $count, 'color' => $status['color']];
        }

        $this->load->model('business_departments/business_departments_model');
        $dept_distribution = [];
        foreach ($this->business_departments_model->get() as $department) {
            $this->db->where('department', $department['id']);
            $count = $this->db->count_all_results(db_prefix() . 'projects');

            $dept_distribution[] = ['name' => $department['name'], 'count' => $count];
        }

        $trend = [];
        for ($i = 5; $i >= 0; $i--) {
            $month_start = date('Y-m-01', strtotime('-' . $i . ' months'));
            $month_end   = date('Y-m-t', strtotime('-' . $i . ' months'));

            $this->db->where('date_finished >=', $month_start . ' 00:00:00');
            $this->db->where('date_finished <=', $month_end . ' 23:59:59');
            $count = $this->db->count_all_results(db_prefix() . 'projects');

            $trend[] = ['month' => date('M Y', strtotime($month_start)), 'count' => $count];
        }

        return [
            'status_distribution'     => $status_distribution,
            'department_distribution' => $dept_distribution,
            'completion_trend'        => $trend,
        ];
    }
}
