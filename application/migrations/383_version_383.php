<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Optional per-project Company label (Admin Projects list + Add/Edit form).
 * Display/denormalized convenience only - the project's relationship to its
 * customer stays on `clientid` (tblclients.userid). The column is nullable
 * so an empty Company input on the form is stored as NULL, which keeps the
 * 'Unknown' display fallback consistent everywhere.
 */
class Migration_Version_383 extends CI_Migration
{
    public function up(): void
    {
        $prefix = db_prefix();

        if (! $this->db->field_exists('company', $prefix . 'projects')) {
            $this->db->query('ALTER TABLE `' . $prefix . 'projects` ADD `company` VARCHAR(255) NULL DEFAULT NULL AFTER `clientid`;');
        }
    }
}