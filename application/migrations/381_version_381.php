<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Smart Attendance v2 Part 2 - Admin Portal attendance module.
 *
 * Audit log for administrative attendance actions (manual record add,
 * status/remarks edits, leave/late/early review decisions including
 * Admin overrides of an Operations Manager decision, holiday CRUD).
 * Deliberately does not log routine login/logout events - those are
 * already fully reconstructable from tblstaff_attendance_sessions and
 * logging every punch-in/out here would just be noise, not an audit
 * trail of decisions.
 */
class Migration_Version_381 extends CI_Migration
{
    public function up(): void
    {
        $prefix = db_prefix();

        if (!$this->db->table_exists($prefix . 'attendance_audit_log')) {
            $this->db->query('CREATE TABLE `' . $prefix . 'attendance_audit_log` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `action` VARCHAR(60) NOT NULL,
                `target_type` VARCHAR(30) NOT NULL,
                `target_id` INT(11) NOT NULL,
                `target_staff_id` INT(11) NULL DEFAULT NULL,
                `actor_staff_id` INT(11) NOT NULL,
                `old_value` TEXT NULL DEFAULT NULL,
                `new_value` TEXT NULL DEFAULT NULL,
                `created_at` DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                KEY `target` (`target_type`, `target_id`),
                KEY `target_staff_id` (`target_staff_id`),
                KEY `actor_staff_id` (`actor_staff_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
        }
    }
}
