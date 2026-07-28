<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Reverses the department-manager half of migration 375. The Manager
 * Portal architecture changed from "one manager per Business Department"
 * to a single, company-wide Operations Manager identified through the
 * native Perfex Staff Permissions capability system (staff_can('view',
 * 'manager_portal')) instead of a per-department flag - see
 * modules/manager_portal/helpers/manager_portal_helper.php. The
 * tblbusiness_department_staff.is_manager column this replaces is no
 * longer written or read anywhere.
 *
 * The Daily Work Update review columns migration 375 also added
 * (review_status/review_comment/reviewed_by/reviewed_at on
 * tbldm_portal_work_updates) are untouched - that Approve/Needs Revision
 * workflow is still required under the new architecture.
 */
class Migration_Version_376 extends CI_Migration
{
    public function up(): void
    {
        $prefix = db_prefix();

        if ($this->db->field_exists('is_manager', $prefix . 'business_department_staff')) {
            $this->db->query('ALTER TABLE ' . $prefix . 'business_department_staff DROP COLUMN is_manager');
        }
    }
}
