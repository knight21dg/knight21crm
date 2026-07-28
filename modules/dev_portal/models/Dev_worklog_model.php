<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Development Portal - Daily Work Updates, scoped per-project. The
 * single new table this feature needed (confirmed via a full live
 * schema audit that nothing in core Perfex or this fork already tracks
 * a per-day/per-project/per-staff work entry - tbltaskstimers is an
 * unrelated per-task start/stop timer).
 */
class Dev_worklog_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param  array $data ['project_id', 'staff_id', 'work_date', 'work_performed', 'hours_worked', 'remarks']
     * @return int|false insert id
     */
    public function add($data)
    {
        $insert = [
            'project_id'     => (int) $data['project_id'],
            'staff_id'       => (int) $data['staff_id'],
            'work_date'      => $data['work_date'],
            'work_performed' => $data['work_performed'],
            'hours_worked'   => $data['hours_worked'] !== '' && $data['hours_worked'] !== null ? (float) $data['hours_worked'] : null,
            'remarks'        => $data['remarks'] ?? null,
            'created_at'     => date('Y-m-d H:i:s'),
        ];

        $this->db->insert(db_prefix() . 'dev_portal_work_logs', $insert);

        return $this->db->insert_id();
    }

    /**
     * Chronological (most recent first) work log entries for a single
     * project - the Workspace's "Daily Work Updates" history / Timeline
     * source.
     *
     * @param  int $project_id
     * @return array
     */
    public function get_for_project($project_id)
    {
        $this->db->select(db_prefix() . 'dev_portal_work_logs.*, ' . db_prefix() . 'staff.firstname, ' . db_prefix() . 'staff.lastname');
        $this->db->from(db_prefix() . 'dev_portal_work_logs');
        $this->db->join(db_prefix() . 'staff', db_prefix() . 'staff.staffid = ' . db_prefix() . 'dev_portal_work_logs.staff_id');
        $this->db->where(db_prefix() . 'dev_portal_work_logs.project_id', $project_id);
        $this->db->order_by(db_prefix() . 'dev_portal_work_logs.work_date', 'desc');
        $this->db->order_by(db_prefix() . 'dev_portal_work_logs.id', 'desc');

        return $this->db->get()->result_array();
    }
}
