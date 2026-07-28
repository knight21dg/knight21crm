<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Permanent Lead->Customer conversion metadata. Replaces every attempt to
 * reconstruct "which department generated this customer" and "who
 * performed the conversion" from tblleads/tblfollowups at Customers-list
 * read time (application/helpers/lead_workspace_helper.php) - that
 * reconstruction changed answers across rounds purely because different
 * queries saw different, incomplete slices of history, even though the
 * underlying data never changed. From this migration forward,
 * Leads_model::record_conversion_metadata() writes these columns once, at
 * the moment a customer is actually created from a Lead, and the
 * Customers list simply reads them back.
 */
class Migration_Version_374 extends CI_Migration
{
    public function up(): void
    {
        $prefix = db_prefix();

        if (!$this->db->field_exists('converted_from_role', $prefix . 'clients')) {
            $this->db->query('ALTER TABLE ' . $prefix . 'clients ADD COLUMN converted_from_role VARCHAR(20) NULL DEFAULT NULL AFTER leadid');
        }

        if (!$this->db->field_exists('converted_by_staff_id', $prefix . 'clients')) {
            $this->db->query('ALTER TABLE ' . $prefix . 'clients ADD COLUMN converted_by_staff_id INT NULL DEFAULT NULL AFTER converted_from_role');
        }

        if (!$this->db->field_exists('converted_at', $prefix . 'clients')) {
            $this->db->query('ALTER TABLE ' . $prefix . 'clients ADD COLUMN converted_at DATETIME NULL DEFAULT NULL AFTER converted_by_staff_id');
        }

        $this->backfill_existing_customers();
    }

    /**
     * One-time backfill for every customer that already existed before
     * this migration - the exact same classification rule
     * Leads_model::record_conversion_metadata() applies going forward,
     * run once here instead of live on every page load. A customer with
     * no matching Lead (leadid NULL, or pointing at a Lead row since
     * deleted) is left with these columns NULL - the query layer reads
     * that as 'admin' via a static default, not a guess.
     *
     * @return void
     */
    private function backfill_existing_customers(): void
    {
        $prefix = db_prefix();

        $telecaller_dept = $this->db->select('id')->where('name', 'Telecalling')->get($prefix . 'business_departments')->row();
        $telecaller_dept_id = $telecaller_dept ? (int) $telecaller_dept->id : 0;

        $customers = $this->db->select('userid, leadid, addedfrom, datecreated')->get($prefix . 'clients')->result();

        foreach ($customers as $customer) {
            if (!$customer->leadid) {
                continue;
            }

            $lead = $this->db->where('id', $customer->leadid)->get($prefix . 'leads')->row();

            if (!$lead) {
                continue;
            }

            $telecaller_case = $this->db
                ->select('COALESCE(assigned_staff_id, staff_id) as staff_id')
                ->where('rel_type', 'lead')
                ->where('rel_id', $customer->leadid)
                ->where('COALESCE(assigned_staff_id, staff_id) IN (SELECT staff_id FROM ' . $prefix . 'business_department_staff WHERE department_id = ' . $telecaller_dept_id . ')', null, false)
                ->order_by('id', 'desc')
                ->limit(1)
                ->get($prefix . 'followups')
                ->row();

            if ($telecaller_case) {
                $role         = 'telecaller';
                $converted_by = (int) $telecaller_case->staff_id;
            } else {
                $role         = 'field_executive';
                $converted_by = $lead->converted_by ? (int) $lead->converted_by : (int) $lead->addedfrom;
            }

            $this->db->where('userid', $customer->userid);
            $this->db->update($prefix . 'clients', [
                'converted_from_role'   => $role,
                'converted_by_staff_id' => $converted_by,
                'converted_at'          => $lead->date_converted ?: $customer->datecreated,
            ]);
        }
    }
}
