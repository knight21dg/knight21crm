<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Daily_work_update_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Task scoping condition - real tbltask_assigned rows, the same table
     * core Perfex's own task-permission checks use. Moved here verbatim
     * from Dm_portal_model - the canonical copy for this shared feature;
     * dm_portal_model's own copy (still used by My Work) is left as-is
     * for this step and will be pointed at this one in Step 2, per the
     * approved step-by-step plan.
     *
     * @param  int $staff_id
     * @return string
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
     * the Daily Work Update form's Project dropdown.
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
     * this staff member's own assignments.
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
     * Today's Daily Work Update row for one staff member, or null if
     * they haven't submitted one yet today - drives the page's
     * create-vs-edit-mode decision. Still backed by tbldm_portal_work_updates
     * (the same physical table modules/dm_portal's own Dm_work_update_model
     * reads/writes) - a department-neutral rename is deferred to Step 2,
     * bundled with dm_portal's own refactor onto this shared model, so
     * dm_portal's existing Daily Work Update page is never left pointing
     * at a table that no longer exists.
     *
     * @param  int $staff_id
     * @return object|null
     */
    public function get_today($staff_id)
    {
        $this->db->select(db_prefix() . 'dm_portal_work_updates.*, ' . db_prefix() . 'projects.name as project_name, ' . db_prefix() . 'tasks.name as task_name');
        $this->db->from(db_prefix() . 'dm_portal_work_updates');
        $this->db->join(db_prefix() . 'projects', db_prefix() . 'projects.id = ' . db_prefix() . 'dm_portal_work_updates.project_id', 'left');
        $this->db->join(db_prefix() . 'tasks', db_prefix() . 'tasks.id = ' . db_prefix() . 'dm_portal_work_updates.task_id', 'left');
        $this->db->where(db_prefix() . 'dm_portal_work_updates.staff_id', $staff_id);
        $this->db->where(db_prefix() . 'dm_portal_work_updates.work_date', date('Y-m-d'));

        return $this->db->get()->row();
    }

    /**
     * Most recent Daily Work Update rows for one staff member, joined to
     * the project name for display.
     *
     * @param  int $staff_id
     * @param  int $limit
     * @return array
     */
    public function get_recent($staff_id, $limit = 5)
    {
        // reviewer.firstname/lastname added for Manager Portal Phase 4's
        // "Reviewed By" employee-visibility requirement - the employee's
        // own history view needed the reviewer's name, not just their id
        // (already selected via the .* below).
        $this->db->select(db_prefix() . 'dm_portal_work_updates.*, ' . db_prefix() . 'projects.name as project_name, reviewer.firstname as reviewer_firstname, reviewer.lastname as reviewer_lastname');
        $this->db->from(db_prefix() . 'dm_portal_work_updates');
        $this->db->join(db_prefix() . 'projects', db_prefix() . 'projects.id = ' . db_prefix() . 'dm_portal_work_updates.project_id', 'left');
        $this->db->join(db_prefix() . 'staff reviewer', 'reviewer.staffid = ' . db_prefix() . 'dm_portal_work_updates.reviewed_by', 'left');
        $this->db->where(db_prefix() . 'dm_portal_work_updates.staff_id', $staff_id);
        $this->db->order_by(db_prefix() . 'dm_portal_work_updates.work_date', 'desc');
        $this->db->limit($limit);

        return $this->db->get()->result_array();
    }

    /**
     * Create-or-update today's Daily Work Update for one staff member -
     * the UNIQUE KEY(staff_id, work_date) on tbldm_portal_work_updates is
     * the structural backstop enforcing exactly one submission per
     * employee per day; this is the normal find-or-create path.
     *
     * @param  int   $staff_id
     * @param  array $data ['project_id','task_id','today_work','tomorrow_plan','issues']
     * @return int the row id (existing or newly inserted)
     */
    public function upsert($staff_id, $data)
    {
        $existing = $this->get_today($staff_id);

        $payload = [
            'project_id'    => $data['project_id'] ?: null,
            'task_id'       => $data['task_id'] ?: null,
            'today_work'    => $data['today_work'],
            'tomorrow_plan' => $data['tomorrow_plan'],
            'issues'        => $data['issues'] !== '' ? $data['issues'] : null,
        ];

        if ($existing) {
            $payload['updated_at'] = date('Y-m-d H:i:s');
            $this->db->where('id', $existing->id);
            $this->db->update(db_prefix() . 'dm_portal_work_updates', $payload);

            return (int) $existing->id;
        }

        $payload['staff_id']   = $staff_id;
        $payload['work_date']  = date('Y-m-d');
        $payload['status']     = 'Submitted';
        $payload['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert(db_prefix() . 'dm_portal_work_updates', $payload);

        return (int) $this->db->insert_id();
    }

    /**
     * tblfiles rows attached to one Daily Work Update, via the same
     * rel_id/rel_type polymorphic pattern already used throughout Perfex
     * core. rel_type stays 'dm_work_update' (internal key, never shown to
     * users, same value dm_portal's own attachments already use) - no
     * reason to rename it.
     *
     * @param  int $work_update_id
     * @return array
     */
    public function get_attachments($work_update_id)
    {
        $this->db->where('rel_id', $work_update_id);
        $this->db->where('rel_type', 'dm_work_update');
        $this->db->order_by('dateadded', 'desc');

        return $this->db->get(db_prefix() . 'files')->result_array();
    }

    /**
     * One tblfiles row (scoped to rel_type='dm_work_update' only, so a
     * caller can never be handed some other entity's attachment by id) -
     * the lookup delete_attachment() and the controller's ownership
     * check both use.
     *
     * @param  int $id
     * @return object|null
     */
    public function get_attachment($id)
    {
        $this->db->where('id', $id);
        $this->db->where('rel_type', 'dm_work_update');

        return $this->db->get(db_prefix() . 'files')->row();
    }

    /**
     * Delete one Daily Work Update attachment - same shape every core
     * entity's own delete_attachment() already uses (Clients_model,
     * Estimates_model, Invoices_model, Proposals_model): unlink the
     * physical file (via the existing get_upload_path_by_type('dm_work_update')
     * filter, not a new path calculation), delete the tblfiles row, then
     * remove the now-possibly-empty per-record subfolder - reusing
     * list_files()/delete_dir() (application/helpers/files_helper.php,
     * globally autoloaded) rather than a new directory-cleanup routine.
     * Caller (the controller) is responsible for the ownership check;
     * this method only knows how to delete a row it's given.
     *
     * @param  int $id
     * @return bool
     */
    public function delete_attachment($id)
    {
        $attachment = $this->get_attachment($id);

        if (!$attachment) {
            return false;
        }

        $dir  = get_upload_path_by_type('dm_work_update') . $attachment->rel_id . '/';
        $path = $dir . $attachment->file_name;

        if (empty($attachment->external) && file_exists($path)) {
            unlink($path);
        }

        $this->db->where('id', $attachment->id);
        $this->db->delete(db_prefix() . 'files');

        $deleted = $this->db->affected_rows() > 0;

        if ($deleted && is_dir($dir) && count(list_files($dir)) === 0) {
            delete_dir($dir);
        }

        return $deleted;
    }

    /**
     * One Daily Work Update row, unscoped - used by callers (Manager
     * Portal's dwu_review()) that need to check ownership (staff_id)
     * before allowing a review action, without pulling in the joined
     * display columns get_report_row() adds for the Admin report modal.
     *
     * @param  int $id
     * @return object|null
     */
    public function get($id)
    {
        $this->db->where('id', $id);

        return $this->db->get(db_prefix() . 'dm_portal_work_updates')->row();
    }

    /**
     * Sets the Approve/Needs Revision review outcome on one submission -
     * the single write path for this, reused by every reviewer surface
     * (Manager Portal's Employee Profile now; the Admin report's own
     * detail modal in a later phase) so the review columns added in
     * migration 375 are never written from more than one place. Caller
     * is responsible for the ownership/scope check (same convention as
     * delete_attachment() above) - this method only knows how to write
     * the row it's given.
     *
     * @param  int    $id
     * @param  string $status     'approved' or 'needs_revision'
     * @param  string $comment
     * @param  int    $reviewer_staff_id
     * @return bool
     */
    public function set_review_status($id, $status, $comment, $reviewer_staff_id)
    {
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'dm_portal_work_updates', [
            'review_status'  => $status,
            'review_comment' => $comment !== '' ? $comment : null,
            'reviewed_by'    => $reviewer_staff_id,
            'reviewed_at'    => date('Y-m-d H:i:s'),
        ]);

        return $this->db->affected_rows() > 0;
    }

    /**
     * Department filter options for the Admin report - only the 9 target
     * departments (daily_work_update_target_departments()), never every
     * department in the system.
     *
     * @return array
     */
    public function get_report_filter_departments()
    {
        $this->db->select('id, name');
        $this->db->where_in('id', daily_work_update_target_department_ids());
        $this->db->order_by('name', 'asc');

        return $this->db->get(db_prefix() . 'business_departments')->result_array();
    }

    /**
     * Employee filter options for the Admin report - every active staff
     * member across the 9 target departments (same set
     * report_table()'s own "Not Submitted Today" comparison uses), not
     * every staff member in the system.
     *
     * @return array
     */
    public function get_report_filter_employees()
    {
        $staff_ids = daily_work_update_target_department_staff_ids();

        if (empty($staff_ids)) {
            return [];
        }

        $this->db->select('staffid, firstname, lastname');
        $this->db->where_in('staffid', $staff_ids);
        $this->db->where('active', 1);
        $this->db->order_by('firstname', 'asc');

        return $this->db->get(db_prefix() . 'staff')->result_array();
    }

    /**
     * Project filter options - only projects that actually appear in at
     * least one submitted Daily Work Update, not every project in the
     * system (which would mostly be irrelevant noise here).
     *
     * @return array
     */
    public function get_report_filter_projects()
    {
        $this->db->distinct();
        $this->db->select(db_prefix() . 'projects.id, ' . db_prefix() . 'projects.name');
        $this->db->from(db_prefix() . 'dm_portal_work_updates');
        $this->db->join(db_prefix() . 'projects', db_prefix() . 'projects.id = ' . db_prefix() . 'dm_portal_work_updates.project_id');
        $this->db->order_by(db_prefix() . 'projects.name', 'asc');

        return $this->db->get()->result_array();
    }

    /**
     * Task filter options - same reasoning as get_report_filter_projects()
     * above, only tasks that actually appear in a submitted update.
     *
     * @return array
     */
    public function get_report_filter_tasks()
    {
        $this->db->distinct();
        $this->db->select(db_prefix() . 'tasks.id, ' . db_prefix() . 'tasks.name');
        $this->db->from(db_prefix() . 'dm_portal_work_updates');
        $this->db->join(db_prefix() . 'tasks', db_prefix() . 'tasks.id = ' . db_prefix() . 'dm_portal_work_updates.task_id');
        $this->db->order_by(db_prefix() . 'tasks.name', 'asc');

        return $this->db->get()->result_array();
    }

    /**
     * One full Daily Work Update row for the Admin report's detail modal -
     * staff name/department, project/task names, and every field of the
     * update itself. Department is the same comma-joined,
     * target-departments-only list report_table() renders inline (kept
     * as a single reusable subquery expression here so the two never
     * drift apart).
     *
     * @param  int $id
     * @return object|null
     */
    public function get_report_row($id)
    {
        $names = array_map(function ($name) {
            return "'" . $this->db->escape_str($name) . "'";
        }, daily_work_update_target_departments());

        $this->db->select(
            db_prefix() . 'dm_portal_work_updates.*, '
            . db_prefix() . 'staff.firstname, ' . db_prefix() . 'staff.lastname, '
            . db_prefix() . 'projects.name as project_name, ' . db_prefix() . 'tasks.name as task_name, '
            . '(SELECT GROUP_CONCAT(bd.name SEPARATOR ", ") FROM ' . db_prefix() . 'business_department_staff bds JOIN ' . db_prefix() . 'business_departments bd ON bd.id = bds.department_id WHERE bds.staff_id = ' . db_prefix() . 'dm_portal_work_updates.staff_id AND bd.name IN (' . implode(',', $names) . ')) as departments'
        );
        $this->db->from(db_prefix() . 'dm_portal_work_updates');
        $this->db->join(db_prefix() . 'staff', db_prefix() . 'staff.staffid = ' . db_prefix() . 'dm_portal_work_updates.staff_id');
        $this->db->join(db_prefix() . 'projects', db_prefix() . 'projects.id = ' . db_prefix() . 'dm_portal_work_updates.project_id', 'left');
        $this->db->join(db_prefix() . 'tasks', db_prefix() . 'tasks.id = ' . db_prefix() . 'dm_portal_work_updates.task_id', 'left');
        $this->db->where(db_prefix() . 'dm_portal_work_updates.id', $id);

        return $this->db->get()->row();
    }
}
