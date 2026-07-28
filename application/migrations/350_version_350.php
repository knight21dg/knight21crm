<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_350 extends CI_Migration
{
    public function up(): void
    {
        // =====================================================================
        // PROJECTS — Re-add `work_status` (previously dropped in
        // 349_version_349.php).
        //
        // On further review, Work Status and the core Project `status` field
        // serve different purposes and are both required:
        // - Project `status` (Not Started/In Progress/On Hold/Finished/
        //   Cancelled) is core Perfex plumbing wired into the Kanban board,
        //   task-completion cascades, `date_finished` bookkeeping, member
        //   notifications and billing - changing it goes through the
        //   dedicated Projects_model::mark_as() flow with real side effects.
        // - Work Status (Pending/In Progress/On Hold/Completed/Cancelled) is
        //   a lightweight, customer-facing service-tracking indicator meant
        //   to be quick-editable inline from the Projects list without
        //   triggering any of the above side effects.
        // =====================================================================
        if (!$this->db->field_exists('work_status', db_prefix() . 'projects')) {
            $this->db->query('ALTER TABLE `' . db_prefix() . 'projects` ADD `work_status` VARCHAR(50) NULL DEFAULT NULL AFTER `assigned_work`;');
        }
    }
}
