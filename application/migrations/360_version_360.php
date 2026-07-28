<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_360 extends CI_Migration
{
    public function up(): void
    {
        // =====================================================================
        // TELECALLER WORKFLOW SIMPLIFICATION. The "Lost" status now requires a
        // reason from the Telecaller (Workspace Save action) - no existing
        // core or module column captures this today, following the same
        // small targeted-column precedent as migration 358 (task_id).
        // =====================================================================

        $column_exists = $this->db->query(
            "SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '" . db_prefix() . "followups' AND COLUMN_NAME = 'lost_reason'"
        )->row();

        if (!$column_exists) {
            $this->db->query('ALTER TABLE `' . db_prefix() . 'followups` ADD COLUMN `lost_reason` VARCHAR(255) NULL DEFAULT NULL AFTER `outcome`;');
        }
    }
}
