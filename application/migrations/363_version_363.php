<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_363 extends CI_Migration
{
    public function up(): void
    {
        // My Tasks (Development Portal) needs a per-task estimate - only
        // tblprojects.estimated_hours exists today, nothing at task level.
        if (!$this->db->field_exists('estimated_hours', db_prefix() . 'tasks')) {
            $this->db->query('ALTER TABLE `' . db_prefix() . 'tasks` ADD `estimated_hours` DECIMAL(15,2) NULL DEFAULT NULL AFTER `hourly_rate`;');
        }

        // "UI/UX Design" department for the Development Portal - a plain
        // data row on the existing tblbusiness_departments table, same
        // as the tblleads_status rename/insert done for the Telecaller
        // workflow - not a schema change, so no CREATE/ALTER here.
        $exists = $this->db->query(
            'SELECT 1 FROM `' . db_prefix() . 'business_departments` WHERE `name` = "UI/UX Design"'
        )->row();

        if (!$exists) {
            $this->db->query(
                'INSERT INTO `' . db_prefix() . 'business_departments` (`name`, `description`, `active`, `display_order`, `created_at`) VALUES ("UI/UX Design", "UI/UX Design", 1, 10, NOW());'
            );
        }
    }
}
