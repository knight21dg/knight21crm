<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_356 extends CI_Migration
{
    public function up(): void
    {
        // =====================================================================
        // TELECALLER / STAFF PORTAL — Case Management architecture (Phase 1).
        //
        // tblfollowups changes role: it stops being a flat one-row-per-attempt
        // log and becomes the CASE (header/master) table. All existing columns
        // are kept exactly as-is (nothing dropped, nothing renamed, no existing
        // data touched) so every current query/model/view keeps working
        // unmodified. Six new columns are added purely additively:
        //
        //   priority          - case priority, not tracked before now
        //   case_status       - true case lifecycle (open/closed), distinct
        //                       from the pre-existing `status` column (which
        //                       remains as the legacy per-attempt pending/
        //                       completed/no_further_action value and is left
        //                       untouched for backward compatibility)
        //   assigned_staff_id - the CURRENT owner of the case (support
        //                       Transfer Case changing this later without
        //                       overwriting the original `staff_id`)
        //   opened_at         - when the case was opened
        //   closed_at         - when the case was closed (null while open)
        //   last_activity_at  - last time any interaction/activity happened
        //
        // Two new child tables are added:
        //   tblfollowup_history  - one row per logged interaction (the
        //                          chronological Timeline)
        //   tblfollowup_activity - one row per system/audit event (the
        //                          Activity Log: created, assigned,
        //                          reassigned, status changed, closed,
        //                          reopened, reminder created/completed)
        //
        // Every existing tblfollowups row is safely backfilled: new columns
        // get sensible defaults derived from existing data, and one
        // tblfollowup_history row + one tblfollowup_activity row is created
        // for each existing case so no historical interaction is lost when
        // the table becomes a Case header. All backfill statements are
        // idempotent (guarded) so re-running this migration is harmless.
        // =====================================================================

        if (!$this->db->field_exists('priority', db_prefix() . 'followups')) {
            $this->db->query('ALTER TABLE `' . db_prefix() . 'followups` ADD `priority` VARCHAR(10) NOT NULL DEFAULT "medium" AFTER `status`;');
        }

        if (!$this->db->field_exists('case_status', db_prefix() . 'followups')) {
            $this->db->query('ALTER TABLE `' . db_prefix() . 'followups` ADD `case_status` VARCHAR(20) NOT NULL DEFAULT "open" AFTER `priority`;');
        }

        if (!$this->db->field_exists('assigned_staff_id', db_prefix() . 'followups')) {
            $this->db->query('ALTER TABLE `' . db_prefix() . 'followups` ADD `assigned_staff_id` INT(11) NULL DEFAULT NULL AFTER `staff_id`;');
        }

        if (!$this->db->field_exists('opened_at', db_prefix() . 'followups')) {
            $this->db->query('ALTER TABLE `' . db_prefix() . 'followups` ADD `opened_at` DATETIME NULL DEFAULT NULL AFTER `date_updated`;');
        }

        if (!$this->db->field_exists('closed_at', db_prefix() . 'followups')) {
            $this->db->query('ALTER TABLE `' . db_prefix() . 'followups` ADD `closed_at` DATETIME NULL DEFAULT NULL AFTER `opened_at`;');
        }

        if (!$this->db->field_exists('last_activity_at', db_prefix() . 'followups')) {
            $this->db->query('ALTER TABLE `' . db_prefix() . 'followups` ADD `last_activity_at` DATETIME NULL DEFAULT NULL AFTER `closed_at`;');
        }

        if (!$this->db->table_exists(db_prefix() . 'followup_history')) {
            $this->db->query('
                CREATE TABLE `' . db_prefix() . 'followup_history` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `followup_id` int(11) NOT NULL,
                    `activity_type` varchar(30) NOT NULL DEFAULT "follow_up",
                    `follow_up_type` varchar(50) NULL DEFAULT NULL,
                    `outcome` varchar(50) NULL DEFAULT NULL,
                    `notes` text NULL DEFAULT NULL,
                    `event_datetime` datetime NOT NULL,
                    `created_by` int(11) NOT NULL,
                    `reminder_id` int(11) NULL DEFAULT NULL,
                    `calendar_event_id` int(11) NULL DEFAULT NULL,
                    `created_at` datetime NOT NULL,
                    PRIMARY KEY (`id`),
                    KEY `followup_id` (`followup_id`),
                    KEY `event_datetime` (`event_datetime`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
            ');
        }

        if (!$this->db->table_exists(db_prefix() . 'followup_activity')) {
            $this->db->query('
                CREATE TABLE `' . db_prefix() . 'followup_activity` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `followup_id` int(11) NOT NULL,
                    `action_type` varchar(30) NOT NULL,
                    `description` text NULL DEFAULT NULL,
                    `old_value` varchar(100) NULL DEFAULT NULL,
                    `new_value` varchar(100) NULL DEFAULT NULL,
                    `created_by` int(11) NULL DEFAULT NULL,
                    `created_at` datetime NOT NULL,
                    PRIMARY KEY (`id`),
                    KEY `followup_id` (`followup_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
            ');
        }

        // ---------------------------------------------------------------
        // Safe backfill of the new tblfollowups columns for every row that
        // existed before this migration (guarded per-column via WHERE ...
        // IS NULL / default-value checks so this is safe to run more than
        // once and never overwrites a value a later phase may have set).
        // ---------------------------------------------------------------
        $this->db->query('UPDATE `' . db_prefix() . 'followups` SET `assigned_staff_id` = `staff_id` WHERE `assigned_staff_id` IS NULL;');
        $this->db->query('UPDATE `' . db_prefix() . 'followups` SET `opened_at` = `date_created` WHERE `opened_at` IS NULL;');
        $this->db->query('UPDATE `' . db_prefix() . 'followups` SET `last_activity_at` = COALESCE(`date_updated`, `date_created`) WHERE `last_activity_at` IS NULL;');
        $this->db->query('
            UPDATE `' . db_prefix() . 'followups`
            SET `case_status` = "closed", `closed_at` = COALESCE(`date_updated`, `date_created`)
            WHERE `status` IN ("completed", "no_further_action") AND `closed_at` IS NULL;
        ');

        // ---------------------------------------------------------------
        // Preserve every existing case's logged attempt as its first
        // Timeline entry (tblfollowup_history) and record a "Case Created"
        // audit entry (tblfollowup_activity), so no historical data is lost
        // when tblfollowups becomes a Case header. Guarded with NOT EXISTS
        // so a second run of this migration does not duplicate rows.
        // ---------------------------------------------------------------
        $this->db->query('
            INSERT INTO `' . db_prefix() . 'followup_history`
                (`followup_id`, `activity_type`, `follow_up_type`, `outcome`, `notes`, `event_datetime`, `created_by`, `reminder_id`, `created_at`)
            SELECT f.`id`, "follow_up", f.`follow_up_type`, f.`outcome`, f.`notes`, f.`follow_up_date`, f.`created_by`, f.`reminder_id`, f.`date_created`
            FROM `' . db_prefix() . 'followups` f
            WHERE NOT EXISTS (
                SELECT 1 FROM `' . db_prefix() . 'followup_history` h WHERE h.`followup_id` = f.`id`
            );
        ');

        $this->db->query('
            INSERT INTO `' . db_prefix() . 'followup_activity`
                (`followup_id`, `action_type`, `description`, `created_by`, `created_at`)
            SELECT f.`id`, "case_created", "Case created", f.`created_by`, f.`date_created`
            FROM `' . db_prefix() . 'followups` f
            WHERE NOT EXISTS (
                SELECT 1 FROM `' . db_prefix() . 'followup_activity` a WHERE a.`followup_id` = f.`id` AND a.`action_type` = "case_created"
            );
        ');
    }
}
