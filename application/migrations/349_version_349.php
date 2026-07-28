<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_349 extends CI_Migration
{
    public function up(): void
    {
        // =====================================================================
        // PROJECTS — Remove `work_status`, added in 348_version_348.php.
        // Reviewed against the existing core Project `status` field
        // (Not Started/In Progress/On Hold/Finished/Cancelled) and found to be
        // a duplicate concept - the core Project status is now the single
        // source of truth for a project's current state. Department,
        // Assigned Employee, Assigned Work and Status Description are kept.
        // =====================================================================
        if ($this->db->field_exists('work_status', db_prefix() . 'projects')) {
            $this->db->query('ALTER TABLE `' . db_prefix() . 'projects` DROP COLUMN `work_status`;');
        }
    }
}
