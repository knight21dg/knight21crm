<?php

defined('BASEPATH') or exit('No direct script access allowed');

use app\services\utilities\Arr;

class Business_departments_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param  int|string $id
     * @return object|array
     */
    public function get($id = '')
    {
        if (is_numeric($id)) {
            $this->db->where('id', $id);

            return $this->db->get(db_prefix() . 'business_departments')->row();
        }

        $this->db->order_by('display_order', 'asc');
        $this->db->order_by('name', 'asc');

        return $this->db->get(db_prefix() . 'business_departments')->result_array();
    }

    /**
     * Staff count per department, for the list view - single query, avoids
     * an N+1 count-per-row.
     *
     * @return array [department_id => count]
     */
    public function get_staff_counts()
    {
        $this->db->select('department_id, COUNT(*) as total');
        $this->db->group_by('department_id');
        $rows   = $this->db->get(db_prefix() . 'business_department_staff')->result_array();
        $counts = [];
        foreach ($rows as $row) {
            $counts[$row['department_id']] = (int) $row['total'];
        }

        return $counts;
    }

    /**
     * @param  int $department_id
     * @return array staffid's currently mapped to this department
     */
    public function get_department_staff_ids($department_id)
    {
        $this->db->select('staff_id');
        $this->db->where('department_id', $department_id);
        $rows = $this->db->get(db_prefix() . 'business_department_staff')->result_array();

        return array_column($rows, 'staff_id');
    }

    /**
     * @param  array $data
     * @return int|false insert id
     */
    public function add($data)
    {
        $staff_ids = Arr::pull($data, 'staff_ids') ?? [];

        $data['active']     = isset($data['active']) ? 1 : 0;
        $data['created_at'] = date('Y-m-d H:i:s');

        $this->db->insert(db_prefix() . 'business_departments', $data);
        $insert_id = $this->db->insert_id();

        if ($insert_id) {
            $this->sync_department_staff($insert_id, $staff_ids);
            log_activity('New Business Department Created [ID: ' . $insert_id . ']');
        }

        return $insert_id;
    }

    /**
     * @param  array $data
     * @param  int   $id
     * @return bool
     */
    public function update($data, $id)
    {
        $staff_ids = Arr::pull($data, 'staff_ids') ?? [];

        $data['active']     = isset($data['active']) ? 1 : 0;
        $data['updated_at'] = date('Y-m-d H:i:s');

        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'business_departments', $data);

        $updated = $this->db->affected_rows() > 0;

        if ($this->sync_department_staff($id, $staff_ids)) {
            $updated = true;
        }

        if ($updated) {
            log_activity('Business Department Updated [ID: ' . $id . ']');
        }

        return $updated;
    }

    /**
     * Diff the posted staff id list against current mappings and
     * insert/delete only what changed - same pattern as
     * Projects_model::add_edit_members().
     *
     * @param  int   $department_id
     * @param  array $staff_ids
     * @return bool  whether anything changed
     */
    public function sync_department_staff($department_id, $staff_ids)
    {
        $staff_ids = array_filter(array_map('intval', (array) $staff_ids));
        $current   = $this->get_department_staff_ids($department_id);

        $to_add    = array_diff($staff_ids, $current);
        $to_remove = array_diff($current, $staff_ids);

        $changed = false;

        foreach ($to_add as $staff_id) {
            $this->db->insert(db_prefix() . 'business_department_staff', [
                'department_id' => $department_id,
                'staff_id'      => $staff_id,
            ]);
            $changed = true;
        }

        if (count($to_remove) > 0) {
            $this->db->where('department_id', $department_id);
            $this->db->where_in('staff_id', $to_remove);
            $this->db->delete(db_prefix() . 'business_department_staff');
            $changed = true;
        }

        return $changed;
    }

    /**
     * @param  int $id
     * @return bool
     */
    public function delete($id)
    {
        $this->db->where('department_id', $id);
        $this->db->delete(db_prefix() . 'business_department_staff');

        // Existing Customer/Project/Lead records referencing this department
        // keep their stored id (no FK constraint, matching this codebase's
        // convention) - they'll simply show as unset until re-assigned.
        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'business_departments');

        $deleted = $this->db->affected_rows() > 0;

        if ($deleted) {
            log_activity('Business Department Deleted [ID: ' . $id . ']');
        }

        return $deleted;
    }
}
