<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_359 extends CI_Migration
{
    public function up(): void
    {
        // =====================================================================
        // TELECALLER WORKSPACE. The get_task filter hook (see
        // follow_up_management.php) now looks up "WHERE task_id = ?" on
        // every single Task load across the whole app (not just Follow-up
        // Tasks) to decide whether to render the workspace - task_id has
        // been a plain unindexed column since migration 358, so this makes
        // that lookup a real index seek instead of a table scan.
        // =====================================================================

        $index_exists = $this->db->query(
            "SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '" . db_prefix() . "followups' AND INDEX_NAME = 'task_id'"
        )->row();

        if (!$index_exists) {
            $this->db->query('ALTER TABLE `' . db_prefix() . 'followups` ADD INDEX `task_id` (`task_id`);');
        }
    }
}
