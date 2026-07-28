<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_366 extends CI_Migration
{
    public function up(): void
    {
        // Digital Marketing Portal - Daily Work Update. One row per staff
        // member per day (enforced via UNIQUE KEY staff_date) - confirmed
        // via a live SHOW TABLES audit that no existing table covers this;
        // tbldev_portal_work_logs (Development Portal) is per-project and
        // allows multiple entries per day, which is a different shape than
        // "exactly one update per employee per day" required here.
        if (!$this->db->table_exists(db_prefix() . 'dm_portal_work_updates')) {
            $this->db->query('
                CREATE TABLE `' . db_prefix() . 'dm_portal_work_updates` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `staff_id` int(11) NOT NULL,
                    `work_date` date NOT NULL,
                    `project_id` int(11) NULL DEFAULT NULL,
                    `task_id` int(11) NULL DEFAULT NULL,
                    `today_work` text NOT NULL,
                    `tomorrow_plan` text NOT NULL,
                    `issues` text NULL DEFAULT NULL,
                    `status` varchar(20) NOT NULL DEFAULT \'Submitted\',
                    `created_at` datetime NOT NULL,
                    `updated_at` datetime NULL DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `staff_date` (`staff_id`, `work_date`),
                    KEY `project_id` (`project_id`),
                    KEY `task_id` (`task_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
            ');
        }
    }
}
