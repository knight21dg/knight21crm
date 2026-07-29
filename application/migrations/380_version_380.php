<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Smart Attendance v2 - multi-session support.
 *
 * tblstaff_attendance keeps its UNIQUE KEY(staff_id, attendance_date) from
 * migration 362 untouched - every existing consumer (Admin Dashboard widget,
 * Manager Portal's entire Attendance page, late/early-arrival detection,
 * month summaries, the Attendance History table) depends on exactly one row
 * per staff per day and is left unmodified by this migration. Instead,
 * tblstaff_attendance becomes a synced SUMMARY row - login_time/logout_time
 * now mean "first login of the day" / "last logout of the day",
 * working_minutes means "sum of all completed sessions", and the new
 * total_sessions column counts sessions - kept in sync by
 * Staff_attendance_model whenever a session opens or closes. Individual
 * sessions live in the new child table below.
 *
 * Backfill: every existing tblstaff_attendance row becomes exactly one
 * session (session_no 1) built from its own login_time/logout_time/
 * working_minutes, so no historical data is lost or reinterpreted.
 */
class Migration_Version_380 extends CI_Migration
{
    public function up(): void
    {
        $prefix = db_prefix();

        if (!$this->db->table_exists($prefix . 'staff_attendance_sessions')) {
            $this->db->query('CREATE TABLE `' . $prefix . 'staff_attendance_sessions` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `attendance_id` INT(11) NOT NULL,
                `staff_id` INT(11) NOT NULL,
                `attendance_date` DATE NOT NULL,
                `session_no` TINYINT(3) UNSIGNED NOT NULL,
                `login_time` DATETIME NOT NULL,
                `logout_time` DATETIME NULL DEFAULT NULL,
                `working_minutes` INT(11) NULL DEFAULT NULL,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `staff_date_session` (`staff_id`, `attendance_date`, `session_no`),
                KEY `attendance_id` (`attendance_id`),
                KEY `staff_date` (`staff_id`, `attendance_date`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
        }

        if (!$this->db->field_exists('total_sessions', $prefix . 'staff_attendance')) {
            $this->db->query('ALTER TABLE `' . $prefix . 'staff_attendance` ADD COLUMN `total_sessions` INT(11) NOT NULL DEFAULT 0 AFTER `working_minutes`');
        }

        // Backfill: one session per existing attendance row, only if the
        // sessions table is empty (keeps this migration safely re-runnable).
        $sessionCount = (int) $this->db->count_all($prefix . 'staff_attendance_sessions');

        if ($sessionCount === 0) {
            $rows = $this->db->get($prefix . 'staff_attendance')->result();

            foreach ($rows as $row) {
                $this->db->insert($prefix . 'staff_attendance_sessions', [
                    'attendance_id'    => $row->id,
                    'staff_id'         => $row->staff_id,
                    'attendance_date'  => $row->attendance_date,
                    'session_no'       => 1,
                    'login_time'       => $row->login_time,
                    'logout_time'      => $row->logout_time,
                    'working_minutes'  => $row->working_minutes,
                    'created_at'       => $row->created_at,
                    'updated_at'       => $row->updated_at,
                ]);

                $this->db->where('id', $row->id);
                $this->db->update($prefix . 'staff_attendance', [
                    'total_sessions' => 1,
                ]);
            }
        }
    }
}
