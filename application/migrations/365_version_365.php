<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_365 extends CI_Migration
{
    public function up(): void
    {
        // Development Portal - Project Workspace "Daily Work Updates".
        // Confirmed via a full live SHOW TABLES audit that no existing
        // Perfex table covers a per-day/per-project/per-staff work
        // entry (tbltaskstimers is a per-task start/stop timer with no
        // date/hours/remarks narrative fields; tblproject_activity is a
        // system-generated audit trail, not staff-authored). This is
        // therefore the only genuinely new table this module needs.
        if (!$this->db->table_exists(db_prefix() . 'dev_portal_work_logs')) {
            $this->db->query('
                CREATE TABLE `' . db_prefix() . 'dev_portal_work_logs` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `project_id` int(11) NOT NULL,
                    `staff_id` int(11) NOT NULL,
                    `work_date` date NOT NULL,
                    `work_performed` text NOT NULL,
                    `hours_worked` decimal(5,2) NULL DEFAULT NULL,
                    `remarks` text NULL DEFAULT NULL,
                    `created_at` datetime NOT NULL,
                    `updated_at` datetime NULL DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    KEY `project_id` (`project_id`),
                    KEY `staff_id` (`staff_id`),
                    KEY `work_date` (`work_date`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
            ');
        }
    }
}
