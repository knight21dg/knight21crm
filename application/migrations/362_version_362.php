<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_362 extends CI_Migration
{
    public function up(): void
    {
        // =====================================================================
        // Simple Employee Attendance module (modules/staff_attendance/). One
        // new table only - no payroll/leave/shift/biometric/GPS/face-recognition/
        // overtime/holiday tables, per the explicit "keep it simple" scope.
        // A UNIQUE KEY on (staff_id, attendance_date) structurally enforces
        // "no duplicate attendance records" at the DB level, on top of the
        // find-or-create app-level guard in Staff_attendance_model::record_login().
        // =====================================================================

        $table_exists = $this->db->query(
            "SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '" . db_prefix() . "staff_attendance'"
        )->row();

        if (!$table_exists) {
            $this->db->query(
                'CREATE TABLE `' . db_prefix() . 'staff_attendance` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `staff_id` int(11) NOT NULL,
                    `attendance_date` date NOT NULL,
                    `login_time` datetime NOT NULL,
                    `logout_time` datetime NULL DEFAULT NULL,
                    `working_minutes` int(11) NULL DEFAULT NULL,
                    `attendance_status` varchar(20) NOT NULL DEFAULT "Present",
                    `remarks` varchar(255) NULL DEFAULT NULL,
                    `created_at` datetime NOT NULL,
                    `updated_at` datetime NULL DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `unique_staff_date` (`staff_id`, `attendance_date`),
                    KEY `attendance_date` (`attendance_date`),
                    KEY `attendance_status` (`attendance_status`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;'
            );
        }
    }
}
