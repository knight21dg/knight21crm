<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_355 extends CI_Migration
{
    public function up(): void
    {
        // =====================================================================
        // FOLLOW-UP MANAGEMENT — polymorphic follow-up history/outcome log.
        // This is deliberately separate from, but linked to, the existing
        // generic tblreminders due-date system: a reminder is a one-shot
        // future alert with a free-text description; a follow-up is a logged
        // attempt (type/outcome/notes) that can optionally schedule its own
        // next due-date, which is synced into tblreminders so all existing
        // due-date/notification/email/SMS/calendar machinery is reused as-is
        // (see modules/follow_up_management/models/Follow_ups_model.php).
        //
        // rel_id/rel_type follow the exact same polymorphic convention
        // tblreminders already uses, so this table needs no schema change to
        // support future Customer/Project/Student follow-ups - only a new
        // rel_type value and a get_relation_data()/get_relation_values() case
        // (already present for 'lead' and 'customer').
        // =====================================================================
        if (!$this->db->table_exists(db_prefix() . 'followups')) {
            $this->db->query('
                CREATE TABLE `' . db_prefix() . 'followups` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `rel_id` int(11) NOT NULL,
                    `rel_type` varchar(40) NOT NULL,
                    `department_id` int(11) NULL DEFAULT NULL,
                    `staff_id` int(11) NOT NULL,
                    `follow_up_type` varchar(50) NOT NULL,
                    `outcome` varchar(50) NULL DEFAULT NULL,
                    `notes` text NULL DEFAULT NULL,
                    `follow_up_date` datetime NOT NULL,
                    `next_follow_up_date` datetime NULL DEFAULT NULL,
                    `reminder_id` int(11) NULL DEFAULT NULL,
                    `status` varchar(20) NOT NULL DEFAULT "pending",
                    `created_by` int(11) NOT NULL,
                    `date_created` datetime NOT NULL,
                    `date_updated` datetime NULL DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    KEY `rel_id` (`rel_id`),
                    KEY `rel_type` (`rel_type`),
                    KEY `staff_id` (`staff_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
            ');
        }

        // Auto-activate this module on deployment - same pattern as migration
        // 354 for business_departments/bank_details. This module's Lead-tab
        // integration calls its helper functions unguarded, so it must never
        // ship dormant (see migration 354's comment for the bug this avoids).
        $this->app_modules->initialize();

        if (!$this->app_modules->is_active('follow_up_management')) {
            $this->app_modules->activate('follow_up_management');
        }
    }
}
