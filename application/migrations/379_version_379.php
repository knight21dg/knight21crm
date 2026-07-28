<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Attendance Module Enhancement - extends the existing Staff Attendance
 * module (modules/staff_attendance/) with Leave Requests, a read-only
 * Holiday Calendar, and Late Arrival/Early Exit Requests. Not a new
 * module - no new sidebar entries, every staff portal already deep-links
 * into this same shared `staff_attendance` controller (see
 * modules/staff_attendance/staff_attendance.php and each sibling
 * portal's own menu registration), so extending it here reaches every
 * portal at once.
 *
 * All 3 request tables share the same review shape (staff_id, a Pending/
 * Approved/Rejected[/Cancelled] status, an optional single attachment,
 * manager_remarks + reviewed_by + reviewed_at) reviewed exclusively by
 * the single company-wide Operations Manager already established by
 * modules/manager_portal/ (is_manager_portal_operations_manager()) - no
 * new manager/department-manager concept, matching the explicit
 * requirement not to reintroduce the department-scoped design this
 * engagement already reverted once.
 *
 * tblholidays has no `day` column - Day of week is always derivable from
 * `date` (date('l', strtotime($date))), storing it would just be a
 * value that could drift out of sync with the date it describes.
 */
class Migration_Version_379 extends CI_Migration
{
    public function up(): void
    {
        $prefix = db_prefix();

        if (!$this->db->table_exists($prefix . 'staff_leave_requests')) {
            $this->db->query('CREATE TABLE `' . $prefix . 'staff_leave_requests` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `staff_id` INT(11) NOT NULL,
                `leave_type` VARCHAR(30) NOT NULL,
                `start_date` DATE NOT NULL,
                `end_date` DATE NOT NULL,
                `days` DECIMAL(4,1) NOT NULL,
                `reason` TEXT NOT NULL,
                `attachment` VARCHAR(255) NULL DEFAULT NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT "Pending",
                `manager_remarks` TEXT NULL DEFAULT NULL,
                `reviewed_by` INT(11) NULL DEFAULT NULL,
                `reviewed_at` DATETIME NULL DEFAULT NULL,
                `created_at` DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                KEY `staff_id` (`staff_id`),
                KEY `status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
        }

        if (!$this->db->table_exists($prefix . 'holidays')) {
            $this->db->query('CREATE TABLE `' . $prefix . 'holidays` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(150) NOT NULL,
                `date` DATE NOT NULL,
                `description` VARCHAR(255) NULL DEFAULT NULL,
                `created_by` INT(11) NULL DEFAULT NULL,
                `created_at` DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                KEY `date` (`date`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
        }

        if (!$this->db->table_exists($prefix . 'staff_late_arrival_requests')) {
            $this->db->query('CREATE TABLE `' . $prefix . 'staff_late_arrival_requests` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `staff_id` INT(11) NOT NULL,
                `attendance_date` DATE NOT NULL,
                `login_time` DATETIME NOT NULL,
                `reason` TEXT NOT NULL,
                `attachment` VARCHAR(255) NULL DEFAULT NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT "Pending",
                `manager_remarks` TEXT NULL DEFAULT NULL,
                `reviewed_by` INT(11) NULL DEFAULT NULL,
                `reviewed_at` DATETIME NULL DEFAULT NULL,
                `created_at` DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `staff_date` (`staff_id`, `attendance_date`),
                KEY `status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
        }

        if (!$this->db->table_exists($prefix . 'staff_early_exit_requests')) {
            $this->db->query('CREATE TABLE `' . $prefix . 'staff_early_exit_requests` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `staff_id` INT(11) NOT NULL,
                `attendance_date` DATE NOT NULL,
                `logout_time` DATETIME NOT NULL,
                `reason` TEXT NOT NULL,
                `attachment` VARCHAR(255) NULL DEFAULT NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT "Pending",
                `manager_remarks` TEXT NULL DEFAULT NULL,
                `reviewed_by` INT(11) NULL DEFAULT NULL,
                `reviewed_at` DATETIME NULL DEFAULT NULL,
                `created_at` DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `staff_date` (`staff_id`, `attendance_date`),
                KEY `status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
        }
    }
}
