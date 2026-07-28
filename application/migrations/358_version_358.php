<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_358 extends CI_Migration
{
    public function up(): void
    {
        // =====================================================================
        // LEAD -> CASE -> TASK SYNCHRONIZATION. Adds one purely additive
        // column: tblfollowups.task_id, linking a Case to the standard
        // Perfex Task (tbltasks) automatically created for it - the
        // permanent Case <-> Task link referenced throughout
        // Follow_ups_model.php (create_case_for_lead(), sync_case_from_lead(),
        // follow_up_management_on_lead_status_changed()). Nothing existing
        // dropped, renamed, or backfilled with guessed data - existing Cases
        // simply have task_id = NULL until their next sync, at which point
        // sync_case_from_lead() creates the missing Task for them too.
        // =====================================================================

        if (!$this->db->field_exists('task_id', db_prefix() . 'followups')) {
            $this->db->query('ALTER TABLE `' . db_prefix() . 'followups` ADD `task_id` INT(11) NULL DEFAULT NULL AFTER `reminder_id`;');
        }
    }
}
