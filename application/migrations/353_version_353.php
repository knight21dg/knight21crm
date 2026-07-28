<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_353 extends CI_Migration
{
    public function up(): void
    {
        // =====================================================================
        // CUSTOMERS — repair columns that migrations 344/345 silently failed to
        // create. Migration 344 added `due_date` with `AFTER work_status`, but
        // `work_status`/`assigned_work` were never added to tblclients (only
        // tblprojects has them) - that ALTER TABLE fails with "Unknown column"
        // and, since db_debug is off in production, CI swallows the error and
        // continues. Because migration 345's `progress`/`last_updated` columns
        // were in turn anchored `AFTER due_date`/`AFTER progress`, the same
        // silent failure cascades to them too. Net effect: none of
        // assigned_work / work_status / due_date / progress / last_updated are
        // guaranteed to exist on a real deployment, even though later code
        // (admin/tables/clients.php, admin/clients/groups/profile.php,
        // Clients_model) assumes all five are present.
        //
        // Each column below is added independently (no AFTER-column
        // dependency on any other column added here), guarded by
        // field_exists(), so this is safe to run regardless of which of the
        // five already exist on a given install - nothing is recreated and no
        // existing data is touched.
        // =====================================================================
        if (!$this->db->field_exists('assigned_work', db_prefix() . 'clients')) {
            $this->db->query('ALTER TABLE `' . db_prefix() . 'clients` ADD `assigned_work` VARCHAR(255) NULL DEFAULT NULL;');
        }

        if (!$this->db->field_exists('work_status', db_prefix() . 'clients')) {
            $this->db->query('ALTER TABLE `' . db_prefix() . 'clients` ADD `work_status` VARCHAR(50) NULL DEFAULT NULL;');
        }

        if (!$this->db->field_exists('due_date', db_prefix() . 'clients')) {
            $this->db->query('ALTER TABLE `' . db_prefix() . 'clients` ADD `due_date` DATE NULL DEFAULT NULL;');
        }

        if (!$this->db->field_exists('progress', db_prefix() . 'clients')) {
            $this->db->query('ALTER TABLE `' . db_prefix() . 'clients` ADD `progress` INT NOT NULL DEFAULT 0;');
        }

        if (!$this->db->field_exists('last_updated', db_prefix() . 'clients')) {
            $this->db->query('ALTER TABLE `' . db_prefix() . 'clients` ADD `last_updated` DATETIME NULL DEFAULT NULL;');
        }
    }
}
