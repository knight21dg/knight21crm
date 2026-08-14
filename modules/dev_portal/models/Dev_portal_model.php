<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Dev_portal_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Project scoping condition, shared by the My Projects table source
     * and the dashboard "Assigned Projects"/"Upcoming Deadlines" counts -
     * one definition, not one per caller. Admin sees every project
     * belonging to the 4 dev departments (tblprojects.department, the
     * same column this fork's Assigned Employee cascade already uses);
     * a portal staff member sees only projects they're a real member of
     * (tblproject_members - the same table core Perfex's own permission
     * checks use, per the audit).
     *
     * @param  int  $staff_id
     * @param  bool $is_admin
     * @return string a single SQL condition, safe to AND into a WHERE clause
     */
    public function get_my_projects_where($staff_id, $is_admin)
    {
        if ($is_admin) {
            $ids = dev_portal_department_ids();

            return db_prefix() . 'projects.department IN (' . (count($ids) ? implode(',', array_map('intval', $ids)) : '0') . ')';
        }

        return db_prefix() . 'projects.id IN (SELECT project_id FROM ' . db_prefix() . 'project_members WHERE staff_id = ' . (int) $staff_id . ')';
    }

    /**
     * Task scoping condition, shared by the My Tasks table source and the
     * dashboard's Today's Tasks/Completed Tasks/Upcoming Deadlines
     * counts. Admin sees tasks assigned to any of the 4 departments'
     * staff; a portal staff member sees only their own assigned tasks -
     * both via tbltask_assigned, the same multi-assignee table core
     * Perfex already uses (get_sql_select_task_assignees_ids() etc. in
     * application/helpers/tasks_helper.php).
     *
     * @param  int  $staff_id
     * @param  bool $is_admin
     * @return string
     */
    public function get_my_tasks_where($staff_id, $is_admin)
    {
        if ($is_admin) {
            $ids = dev_portal_staff_ids();

            return db_prefix() . 'tasks.id IN (SELECT taskid FROM ' . db_prefix() . 'task_assigned WHERE staffid IN (' . (count($ids) ? implode(',', $ids) : '0') . '))';
        }

        return db_prefix() . 'tasks.id IN (SELECT taskid FROM ' . db_prefix() . 'task_assigned WHERE staffid = ' . (int) $staff_id . ')';
    }

    /**
     * Dashboard summary - Today's Tasks, Assigned Projects, Completed
     * Tasks, Upcoming Deadlines. Every count reuses
     * get_my_projects_where()/get_my_tasks_where() above rather than a
     * third, dashboard-only scoping rule.
     *
     * @param  int  $staff_id
     * @param  bool $is_admin
     * @return array
     */
    public function get_dashboard_summary($staff_id, $is_admin)
    {
        $tasks_where    = $this->get_my_tasks_where($staff_id, $is_admin);
        $projects_where = $this->get_my_projects_where($staff_id, $is_admin);

        $this->db->where($tasks_where, null, false);
        $this->db->where('duedate', date('Y-m-d'));
        $this->db->where('status !=', Tasks_model::STATUS_COMPLETE);
        $todays_tasks = $this->db->count_all_results(db_prefix() . 'tasks');

        $this->db->where($projects_where, null, false);
        $assigned_projects = $this->db->count_all_results(db_prefix() . 'projects');

        $this->db->where($tasks_where, null, false);
        $this->db->where('status', Tasks_model::STATUS_COMPLETE);
        $completed_tasks = $this->db->count_all_results(db_prefix() . 'tasks');

        return [
            'todays_tasks'       => $todays_tasks,
            'assigned_projects'  => $assigned_projects,
            'completed_tasks'    => $completed_tasks,
            'upcoming_deadlines' => $this->get_upcoming_deadlines($staff_id, $is_admin),
        ];
    }

    /**
     * Tasks due and projects deadlined in the next 7 days (today
     * included), merged into one date-ordered list for the dashboard's
     * "Upcoming Deadlines" card. Two small queries unioned in PHP rather
     * than a SQL UNION, since the two source tables have different
     * column shapes and this only ever needs a handful of rows.
     *
     * @param  int  $staff_id
     * @param  bool $is_admin
     * @param  int  $limit
     * @return array [['type' => 'task'|'project', 'name' => string, 'date' => Y-m-d, 'url' => string], ...]
     */
    public function get_upcoming_deadlines($staff_id, $is_admin, $limit = 6)
    {
        $today   = date('Y-m-d');
        $horizon = date('Y-m-d', strtotime('+7 days'));

        $this->db->select('id, name, duedate');
        $this->db->where($this->get_my_tasks_where($staff_id, $is_admin), null, false);
        $this->db->where('duedate >=', $today);
        $this->db->where('duedate <=', $horizon);
        $this->db->where('status !=', Tasks_model::STATUS_COMPLETE);
        $this->db->order_by('duedate', 'asc');
        $tasks = $this->db->get(db_prefix() . 'tasks')->result_array();

        // Projects_model has no named status constants (unlike Tasks_model) -
        // 4 = Finished, 5 = Cancelled (application/language/english/english_lang.php
        // project_status_4/_5) - both excluded, a deadline on an already
        // closed-out project isn't "upcoming".
        $this->db->select('id, name, deadline');
        $this->db->where($this->get_my_projects_where($staff_id, $is_admin), null, false);
        $this->db->where('deadline >=', $today);
        $this->db->where('deadline <=', $horizon);
        $this->db->where_not_in('status', [4, 5]);
        $this->db->order_by('deadline', 'asc');
        $projects = $this->db->get(db_prefix() . 'projects')->result_array();

        $deadlines = [];

        foreach ($tasks as $task) {
            $deadlines[] = [
                'type' => 'task',
                'name' => $task['name'],
                'date' => $task['duedate'],
                // Links straight to the Task Workspace (own page - see
                // Dev_portal::task_workspace()), not the task list.
                'url'  => admin_url('dev_portal/task_workspace/' . $task['id']),
            ];
        }

        foreach ($projects as $project) {
            $deadlines[] = [
                'type' => 'project',
                'name' => $project['name'],
                'date' => $project['deadline'],
                // Project Workspace is this portal's Project Details page -
                // deep link straight to the specific project, not the list.
                'url'  => admin_url('dev_portal/project/' . $project['id']),
            ];
        }

        usort($deadlines, function ($a, $b) {
            return strcmp($a['date'], $b['date']);
        });

        return array_slice($deadlines, 0, $limit);
    }
}
