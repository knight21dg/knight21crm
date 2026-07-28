<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Manager Portal foundation - two independent, additive schema changes:
 *
 * 1. tblbusiness_department_staff.is_manager - flags a staff member as a
 *    manager of the department row they're already mapped to. Pure
 *    extension of the existing many-to-many join (modules/business_departments) -
 *    no new table, no change to any existing row's meaning. A staff member
 *    can be flagged manager on more than one department row, which is all
 *    "manage multiple departments" ever needs (every Manager Portal
 *    scoping helper already aggregates via an IN clause over department
 *    ids, per modules/dev_portal's existing multi-department pattern).
 *
 * 2. tbldm_portal_work_updates review columns - lets a Manager (or Admin)
 *    Approve/Reject a Daily Work Update submission with a comment. Kept on
 *    the existing shared table rather than a new one, matching this
 *    module's own established precedent (attachments, task_id linkage,
 *    etc. were all added to existing tables, never split out).
 */
class Migration_Version_375 extends CI_Migration
{
    public function up(): void
    {
        $prefix = db_prefix();

        if (!$this->db->field_exists('is_manager', $prefix . 'business_department_staff')) {
            $this->db->query('ALTER TABLE ' . $prefix . 'business_department_staff ADD COLUMN is_manager TINYINT(1) NOT NULL DEFAULT 0');
        }

        if (!$this->db->field_exists('review_status', $prefix . 'dm_portal_work_updates')) {
            $this->db->query("ALTER TABLE " . $prefix . "dm_portal_work_updates ADD COLUMN review_status VARCHAR(20) NOT NULL DEFAULT 'pending'");
        }

        if (!$this->db->field_exists('review_comment', $prefix . 'dm_portal_work_updates')) {
            $this->db->query('ALTER TABLE ' . $prefix . 'dm_portal_work_updates ADD COLUMN review_comment TEXT NULL DEFAULT NULL');
        }

        if (!$this->db->field_exists('reviewed_by', $prefix . 'dm_portal_work_updates')) {
            $this->db->query('ALTER TABLE ' . $prefix . 'dm_portal_work_updates ADD COLUMN reviewed_by INT NULL DEFAULT NULL');
        }

        if (!$this->db->field_exists('reviewed_at', $prefix . 'dm_portal_work_updates')) {
            $this->db->query('ALTER TABLE ' . $prefix . 'dm_portal_work_updates ADD COLUMN reviewed_at DATETIME NULL DEFAULT NULL');
        }
    }
}
