<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Field_portal_expenses_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function get($id)
    {
        $this->db->where('id', $id);
        return $this->db->get(db_prefix() . 'field_expenses')->row();
    }

    public function get_by_staff($staff_id, $filters = [])
    {
        $this->db->where('staff_id', $staff_id);

        if (!empty($filters['category'])) {
            $this->db->where('category', $filters['category']);
        }
        if (!empty($filters['from_date'])) {
            $this->db->where('expense_date >=', $filters['from_date']);
        }
        if (!empty($filters['to_date'])) {
            $this->db->where('expense_date <=', $filters['to_date']);
        }
        if (!empty($filters['month'])) {
            $this->db->where('MONTH(expense_date)', (int) $filters['month']);
        }
        if (!empty($filters['year'])) {
            $this->db->where('YEAR(expense_date)', (int) $filters['year']);
        }

        $this->db->order_by('expense_date', 'desc');
        $this->db->order_by('created_at', 'desc');

        return $this->db->get(db_prefix() . 'field_expenses')->result_array();
    }

    /**
     * @param  int|null $lead_id Pass null explicitly to scope to entries with
     *                           no Lead; omit (default 'ANY') to skip lead
     *                           scoping entirely. A day's expenses are keyed
     *                           by (staff_id, expense_date, lead_id) - two
     *                           different Leads can each have their own
     *                           entry on the same date, so callers that
     *                           mean to operate on one specific daily entry
     *                           must always pass the lead_id.
     */
    public function get_by_staff_date($staff_id, $date, $lead_id = 'ANY')
    {
        $this->db->where('staff_id', $staff_id);
        $this->db->where('expense_date', $date);
        if ($lead_id !== 'ANY') {
            if ($lead_id === null || $lead_id === 0) {
                $this->db->where('lead_id IS NULL');
            } else {
                $this->db->where('lead_id', $lead_id);
            }
        }
        return $this->db->get(db_prefix() . 'field_expenses')->result_array();
    }

    public function add($data)
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['staff_id']   = $data['staff_id'] ?? get_staff_user_id();
        $this->db->insert(db_prefix() . 'field_expenses', $data);
        return $this->db->insert_id();
    }

    public function update($id, $data)
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'field_expenses', $data);
        return $this->db->affected_rows() > 0;
    }

    public function delete($id)
    {
        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'field_expenses');
        return $this->db->affected_rows() > 0;
    }

    /**
     * Save a full day's expenses.
     *
     * For each non-zero category amount, an individual record is inserted or
     * updated.  Existing records whose category is now zero or absent are
     * deleted.  A daily entry's identity is (staff_id, date, lead_id) - if
     * either the date or the Lead changes from what was originally being
     * edited (original_date/original_lead_id), every record under the OLD
     * identity is removed first, so editing one Lead's day never merges
     * into or clobbers a different Lead's entry that happens to share a
     * date.
     */
    public function save_day($staff_id, $date, $lead_id, $remarks, $amounts, $original_date = null, $original_lead_id = 'ANY')
    {
        if ($original_date && ($original_date !== $date || $original_lead_id !== $lead_id)) {
            $this->db->where('staff_id', $staff_id);
            $this->db->where('expense_date', $original_date);
            if ($original_lead_id === null || $original_lead_id === 0) {
                $this->db->where('lead_id IS NULL');
            } elseif ($original_lead_id !== 'ANY') {
                $this->db->where('lead_id', $original_lead_id);
            }
            $this->db->delete(db_prefix() . 'field_expenses');
        }

        $existing = $this->get_by_staff_date($staff_id, $date, $lead_id);
        $existing_by_cat = [];
        foreach ($existing as $r) {
            $existing_by_cat[$r['category']] = $r;
        }

        $inserted = 0;
        $updated  = 0;
        $deleted  = 0;
        $now      = date('Y-m-d H:i:s');

        foreach ($amounts as $category => $amount) {
            if ((float) $amount > 0) {
                $data = [
                    'staff_id'       => $staff_id,
                    'lead_id'        => $lead_id,
                    'expense_date'   => $date,
                    'category'       => $category,
                    'amount'         => (float) $amount,
                    'remarks'        => $remarks,
                    'description'    => $category . ' expense',
                    'payment_method' => '',
                ];

                if (isset($existing_by_cat[$category])) {
                    $data['updated_at'] = $now;
                    $this->db->where('id', $existing_by_cat[$category]['id']);
                    $this->db->update(db_prefix() . 'field_expenses', $data);
                    $updated++;
                    unset($existing_by_cat[$category]);
                } else {
                    $data['created_at'] = $now;
                    $this->db->insert(db_prefix() . 'field_expenses', $data);
                    $inserted++;
                }
            } elseif (isset($existing_by_cat[$category])) {
                $this->db->where('id', $existing_by_cat[$category]['id']);
                $this->db->delete(db_prefix() . 'field_expenses');
                $deleted++;
                unset($existing_by_cat[$category]);
            }
        }

        foreach ($existing_by_cat as $r) {
            $this->db->where('id', $r['id']);
            $this->db->delete(db_prefix() . 'field_expenses');
            $deleted++;
        }

        return ['inserted' => $inserted, 'updated' => $updated, 'deleted' => $deleted];
    }

    /**
     * Delete a whole daily entry - every category record for
     * (staff_id, date, lead_id), plus every attachment for (staff_id, date).
     * Attachments have no lead_id column (schema unchanged), so they are
     * scoped by date only, matching how they've always been stored/fetched
     * in this module.
     */
    public function delete_day($staff_id, $date, $lead_id)
    {
        $this->db->where('staff_id', $staff_id);
        $this->db->where('expense_date', $date);
        if ($lead_id === null || $lead_id === 0) {
            $this->db->where('lead_id IS NULL');
        } else {
            $this->db->where('lead_id', $lead_id);
        }
        $this->db->delete(db_prefix() . 'field_expenses');
        $deleted_records = $this->db->affected_rows() > 0;

        foreach ($this->get_attachments($staff_id, $date) as $att) {
            $this->delete_attachment($att['id']);
        }

        return $deleted_records;
    }

    // -------------------------------------------------------------------------
    // Attachments
    // -------------------------------------------------------------------------

    public function get_attachments($staff_id, $date)
    {
        $this->db->where('staff_id', $staff_id);
        $this->db->where('expense_date', $date);
        $this->db->order_by('created_at', 'asc');
        return $this->db->get(db_prefix() . 'field_expense_attachments')->result_array();
    }

    public function get_attachment($id)
    {
        $this->db->where('id', $id);
        return $this->db->get(db_prefix() . 'field_expense_attachments')->row();
    }

    public function add_attachment($data)
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert(db_prefix() . 'field_expense_attachments', $data);
        return $this->db->insert_id();
    }

    public function update_attachment($id, $data)
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'field_expense_attachments', $data);
        return $this->db->affected_rows() > 0;
    }

    public function delete_attachment($id)
    {
        $att = $this->get_attachment($id);
        if ($att && $att->file_name) {
            $path = FCPATH . 'uploads/field_portal/expenses/' . $att->file_name;
            if (file_exists($path)) {
                unlink($path);
            }
        }
        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'field_expense_attachments');
        return $this->db->affected_rows() > 0;
    }

    public function get_summary($staff_id, $filters = [])
    {
        $result = [
            'today'  => 0,
            'month'  => 0,
            'year'   => 0,
            'total'  => 0,
        ];

        $result['today'] = (float) $this->_sum_where($staff_id, ['expense_date' => date('Y-m-d')], $filters);
        $result['month'] = (float) $this->_sum_where($staff_id, [
            'YEAR(expense_date)'  => date('Y'),
            'MONTH(expense_date)' => date('m'),
        ], $filters);
        $result['year']  = (float) $this->_sum_where($staff_id, ['YEAR(expense_date)' => date('Y')], $filters);
        $result['total'] = (float) $this->_sum_where($staff_id, [], $filters);

        return $result;
    }

    public function get_category_totals($staff_id, $filters = [], $categories = [])
    {
        if (empty($categories)) {
            $categories = ['Travel', 'Fuel', 'Food', 'Tea / Snacks', 'Parking', 'Toll', 'Hotel', 'Client Meeting', 'Miscellaneous'];
        }
        $totals = [];
        $grand_total = 0;

        foreach ($categories as $cat) {
            $total = (float) $this->_sum_where($staff_id, ['category' => $cat], $filters);
            $totals[$cat] = $total;
            $grand_total += $total;
        }

        return ['categories' => $totals, 'grand_total' => $grand_total];
    }

    private function _sum_where($staff_id, $conditions, $extra_filters = [])
    {
        $this->db->select('COALESCE(SUM(amount), 0) as total');
        $this->db->where('staff_id', $staff_id);

        foreach ($conditions as $field => $value) {
            $this->db->where($field, $value);
        }

        if (!empty($extra_filters['category'])) {
            $this->db->where('category', $extra_filters['category']);
        }
        if (!empty($extra_filters['from_date'])) {
            $this->db->where('expense_date >=', $extra_filters['from_date']);
        }
        if (!empty($extra_filters['to_date'])) {
            $this->db->where('expense_date <=', $extra_filters['to_date']);
        }
        if (!empty($extra_filters['month'])) {
            $this->db->where('MONTH(expense_date)', (int) $extra_filters['month']);
        }
        if (!empty($extra_filters['year'])) {
            $this->db->where('YEAR(expense_date)', (int) $extra_filters['year']);
        }

        $row = $this->db->get(db_prefix() . 'field_expenses')->row();
        return $row ? $row->total : 0;
    }
}
