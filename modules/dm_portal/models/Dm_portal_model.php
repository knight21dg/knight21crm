<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Dm_portal_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Task scoping condition - a Digital Marketing employee sees only
     * their own assigned tasks (tbltask_assigned, the same multi-assignee
     * table core Perfex already uses). No Admin cross-department branch -
     * this portal builds no Admin-facing view, per the approved
     * architecture; if Admin browses here directly they simply see their
     * own (typically empty) data, same as any other staff member would.
     *
     * @param  int $staff_id
     * @return string a single SQL condition, safe to AND into a WHERE clause
     */
    public function get_my_tasks_where($staff_id)
    {
        return db_prefix() . 'tasks.id IN (SELECT taskid FROM ' . db_prefix() . 'task_assigned WHERE staffid = ' . (int) $staff_id . ')';
    }

    /**
     * Project scoping condition - real tblproject_members membership,
     * the same table core Perfex's own permission checks use.
     *
     * @param  int $staff_id
     * @return string
     */
    public function get_my_projects_where($staff_id)
    {
        return db_prefix() . 'projects.id IN (SELECT project_id FROM ' . db_prefix() . 'project_members WHERE staff_id = ' . (int) $staff_id . ')';
    }

    /**
     * Project id => name, scoped to this staff member's own membership -
     * the My Work page's Project filter dropdown.
     *
     * @param  int $staff_id
     * @return array
     */
    public function get_my_projects_for_filter($staff_id)
    {
        $this->db->select('id, name');
        $this->db->where($this->get_my_projects_where($staff_id), null, false);
        $this->db->order_by('name', 'asc');

        return $this->db->get(db_prefix() . 'projects')->result_array();
    }

    /**
     * Task id => name (+ owning project id, so the Daily Work Update
     * form's Task dropdown can be labelled with its project) - scoped to
     * this staff member's own assignments, same get_my_tasks_where()
     * used everywhere else in this module.
     *
     * @param  int $staff_id
     * @return array
     */
    public function get_my_tasks_for_filter($staff_id)
    {
        $this->db->select(db_prefix() . 'tasks.id, ' . db_prefix() . 'tasks.name, ' . db_prefix() . 'tasks.rel_id as project_id, ' . db_prefix() . 'tasks.rel_type');
        $this->db->from(db_prefix() . 'tasks');
        $this->db->where($this->get_my_tasks_where($staff_id), null, false);
        $this->db->order_by(db_prefix() . 'tasks.name', 'asc');

        return $this->db->get()->result_array();
    }

    /**
     * Dashboard summary - Assigned Projects, Pending Tasks, Completed
     * Tasks, Today's Tasks (count + list), Upcoming Deadlines. Every
     * count reuses get_my_projects_where()/get_my_tasks_where() above.
     *
     * @param  int $staff_id
     * @return array
     */
    public function get_dashboard_summary($staff_id)
    {
        $tasks_where    = $this->get_my_tasks_where($staff_id);
        $projects_where = $this->get_my_projects_where($staff_id);

        $this->db->where($projects_where, null, false);
        $assigned_projects = $this->db->count_all_results(db_prefix() . 'projects');

        $this->db->where($tasks_where, null, false);
        $this->db->where('status !=', Tasks_model::STATUS_COMPLETE);
        $pending_tasks = $this->db->count_all_results(db_prefix() . 'tasks');

        $this->db->where($tasks_where, null, false);
        $this->db->where('status', Tasks_model::STATUS_COMPLETE);
        $completed_tasks = $this->db->count_all_results(db_prefix() . 'tasks');

        $this->db->where($tasks_where, null, false);
        $this->db->where('duedate', date('Y-m-d'));
        $this->db->where('status !=', Tasks_model::STATUS_COMPLETE);
        $todays_tasks = $this->db->count_all_results(db_prefix() . 'tasks');

        return [
            'assigned_projects'  => $assigned_projects,
            'pending_tasks'      => $pending_tasks,
            'completed_tasks'    => $completed_tasks,
            'todays_tasks'       => $todays_tasks,
            'today_task_list'    => $this->get_today_task_list($staff_id),
            'upcoming_deadlines' => $this->get_upcoming_deadlines($staff_id),
        ];
    }

    /**
     * Today's Work Queue - the actual task rows behind the Today's Tasks
     * count above, for the dashboard list below the cards.
     *
     * @param  int $staff_id
     * @param  int $limit
     * @return array
     */
    public function get_today_task_list($staff_id, $limit = 5)
    {
        $this->db->select(db_prefix() . 'tasks.id, ' . db_prefix() . 'tasks.name, ' . db_prefix() . 'tasks.priority, ' . db_prefix() . 'tasks.status, ' . db_prefix() . 'tasks.duedate, ' . db_prefix() . 'projects.name as project_name');
        $this->db->from(db_prefix() . 'tasks');
        $this->db->join(db_prefix() . 'projects', db_prefix() . 'projects.id = ' . db_prefix() . "tasks.rel_id AND " . db_prefix() . "tasks.rel_type = 'project'", 'left');
        $this->db->where($this->get_my_tasks_where($staff_id), null, false);
        $this->db->where(db_prefix() . 'tasks.duedate', date('Y-m-d'));
        $this->db->where(db_prefix() . 'tasks.status !=', Tasks_model::STATUS_COMPLETE);
        $this->db->order_by(db_prefix() . 'tasks.priority', 'desc');
        $this->db->limit($limit);

        return $this->db->get()->result_array();
    }

    /**
     * Tasks due and projects deadlined in the next 7 days (today
     * included), merged into one date-ordered list - same approach
     * dev_portal's own get_upcoming_deadlines() uses.
     *
     * @param  int $staff_id
     * @param  int $limit
     * @return array [['type' => 'task'|'project', 'name' => string, 'date' => Y-m-d, 'url' => string], ...]
     */
    public function get_upcoming_deadlines($staff_id, $limit = 6)
    {
        $today   = date('Y-m-d');
        $horizon = date('Y-m-d', strtotime('+7 days'));

        $this->db->select('id, name, duedate');
        $this->db->where($this->get_my_tasks_where($staff_id), null, false);
        $this->db->where('duedate >=', $today);
        $this->db->where('duedate <=', $horizon);
        $this->db->where('status !=', Tasks_model::STATUS_COMPLETE);
        $this->db->order_by('duedate', 'asc');
        $tasks = $this->db->get(db_prefix() . 'tasks')->result_array();

        // 4 = Finished, 5 = Cancelled (application/language/english/english_lang.php
        // project_status_4/_5) - both excluded, a deadline on an already
        // closed-out project isn't "upcoming".
        $this->db->select('id, name, deadline');
        $this->db->where($this->get_my_projects_where($staff_id), null, false);
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
                'url'  => admin_url('dm_portal/my_work'),
            ];
        }

        foreach ($projects as $project) {
            $deadlines[] = [
                'type' => 'project',
                'name' => $project['name'],
                'date' => $project['deadline'],
                'url'  => admin_url('dm_portal/my_work'),
            ];
        }

        usort($deadlines, function ($a, $b) {
            return strcmp($a['date'], $b['date']);
        });

        return array_slice($deadlines, 0, $limit);
    }

    /**
     * "My Assigned Projects" dashboard modal - one row per project this
     * staff member belongs to, with everything the summary list needs.
     * Reuses get_my_projects_where() - same scoping every other project
     * query in this module already uses.
     *
     * @param  int $staff_id
     * @return array
     */
    public function get_my_projects_detailed($staff_id)
    {
        $this->db->select(db_prefix() . 'projects.id, ' . db_prefix() . 'projects.name, ' . db_prefix() . 'clients.company, '
            . db_prefix() . 'projects.start_date, ' . db_prefix() . 'projects.deadline, ' . db_prefix() . 'projects.status, '
            . db_prefix() . 'projects.progress, ' . db_prefix() . 'projects.assigned_work');
        $this->db->from(db_prefix() . 'projects');
        $this->db->join(db_prefix() . 'clients', db_prefix() . 'clients.userid = ' . db_prefix() . 'projects.clientid', 'left');
        $this->db->where($this->get_my_projects_where($staff_id), null, false);
        $this->db->order_by(db_prefix() . 'projects.name', 'asc');

        return $this->db->get()->result_array();
    }

    /**
     * Single project row for the dashboard expand panel - intentionally
     * NOT Projects_model::get(), which also bundles vault entries, tabs
     * and settings this read-only summary never uses.
     *
     * @param  int $project_id
     * @return object|null
     */
    public function get_project_basic($project_id)
    {
        $this->db->select(db_prefix() . 'projects.*, ' . db_prefix() . 'clients.company');
        $this->db->join(db_prefix() . 'clients', db_prefix() . 'clients.userid = ' . db_prefix() . 'projects.clientid', 'left');
        $this->db->where(db_prefix() . 'projects.id', $project_id);

        return $this->db->get(db_prefix() . 'projects')->row();
    }

    /**
     * @param  int $project_id
     * @return array
     */
    public function get_project_team_members($project_id)
    {
        $this->db->select(db_prefix() . 'staff.staffid, ' . db_prefix() . 'staff.firstname, ' . db_prefix() . 'staff.lastname, ' . db_prefix() . 'staff.email');
        $this->db->from(db_prefix() . 'project_members');
        $this->db->join(db_prefix() . 'staff', db_prefix() . 'staff.staffid = ' . db_prefix() . 'project_members.staff_id');
        $this->db->where(db_prefix() . 'project_members.project_id', $project_id);
        $this->db->order_by(db_prefix() . 'staff.firstname', 'asc');

        return $this->db->get()->result_array();
    }

    /**
     * @param  int $project_id
     * @return array
     */
    public function get_project_files($project_id)
    {
        $this->db->where('rel_id', $project_id);
        $this->db->where('rel_type', 'project');
        $this->db->order_by('dateadded', 'desc');

        return $this->db->get(db_prefix() . 'files')->result_array();
    }

    /**
     * This staff member's own tasks within the project - same
     * self-scoping principle as everywhere else in this module, not
     * every task in the project.
     *
     * @param  int $project_id
     * @param  int $staff_id
     * @return array
     */
    public function get_project_current_tasks($project_id, $staff_id)
    {
        $this->db->select(db_prefix() . 'tasks.id, ' . db_prefix() . 'tasks.name, ' . db_prefix() . 'tasks.status, ' . db_prefix() . 'tasks.priority, ' . db_prefix() . 'tasks.duedate');
        $this->db->from(db_prefix() . 'tasks');
        $this->db->where($this->get_my_tasks_where($staff_id), null, false);
        $this->db->where(db_prefix() . 'tasks.rel_id', $project_id);
        $this->db->where(db_prefix() . 'tasks.rel_type', 'project');
        $this->db->order_by(db_prefix() . 'tasks.duedate', 'asc');

        return $this->db->get()->result_array();
    }

    /**
     * "My Pending Tasks" dashboard modal. dateadded is shown as "Assigned
     * Date" - tbltask_assigned itself has no assignment-date column, and
     * a task's own creation date is the closest real data point this
     * schema has, rather than inventing one.
     *
     * @param  int $staff_id
     * @return array
     */
    public function get_my_pending_tasks_detailed($staff_id)
    {
        $this->db->select(db_prefix() . 'tasks.id, ' . db_prefix() . 'tasks.name, ' . db_prefix() . 'tasks.priority, '
            . db_prefix() . 'tasks.duedate, ' . db_prefix() . 'tasks.dateadded, ' . db_prefix() . 'tasks.status, '
            . db_prefix() . 'projects.name as project_name');
        $this->db->from(db_prefix() . 'tasks');
        $this->db->join(db_prefix() . 'projects', db_prefix() . 'projects.id = ' . db_prefix() . "tasks.rel_id AND " . db_prefix() . "tasks.rel_type = 'project'", 'left');
        $this->db->where($this->get_my_tasks_where($staff_id), null, false);
        $this->db->where(db_prefix() . 'tasks.status !=', Tasks_model::STATUS_COMPLETE);
        $this->db->order_by(db_prefix() . 'tasks.duedate IS NULL, ' . db_prefix() . 'tasks.duedate', '', false);

        return $this->db->get()->result_array();
    }

    /**
     * "My Completed Tasks" dashboard modal. description is shown as
     * "Notes" - tbltasks has no dedicated notes field, and the task
     * description is the closest existing free-text field.
     *
     * @param  int $staff_id
     * @param  int $limit
     * @return array
     */
    public function get_my_completed_tasks_detailed($staff_id, $limit = 50)
    {
        $this->db->select(db_prefix() . 'tasks.id, ' . db_prefix() . 'tasks.name, ' . db_prefix() . 'tasks.datefinished, '
            . db_prefix() . 'tasks.description, ' . db_prefix() . 'projects.name as project_name');
        $this->db->from(db_prefix() . 'tasks');
        $this->db->join(db_prefix() . 'projects', db_prefix() . 'projects.id = ' . db_prefix() . "tasks.rel_id AND " . db_prefix() . "tasks.rel_type = 'project'", 'left');
        $this->db->where($this->get_my_tasks_where($staff_id), null, false);
        $this->db->where(db_prefix() . 'tasks.status', Tasks_model::STATUS_COMPLETE);
        $this->db->order_by(db_prefix() . 'tasks.datefinished', 'desc');
        $this->db->limit($limit);

        return $this->db->get()->result_array();
    }
}
