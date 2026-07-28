<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_370 extends CI_Migration
{
    public function up(): void
    {
        // Field Executive Portal - Step 4 (Telecaller Integration). No
        // "Converted By" staff column exists anywhere in core Perfex
        // (tblleads/tblclients) - required for the reporting fields this
        // step introduces (Conversion By, alongside the already-existing
        // Original Field Executive/addedfrom and Current Handler/assigned).
        // Captured via a hook listener on the existing
        // 'lead_converted_to_customer' hook, not a new conversion path.
        if (!$this->db->field_exists('converted_by', db_prefix() . 'leads')) {
            $this->db->query('ALTER TABLE ' . db_prefix() . 'leads ADD COLUMN converted_by INT NULL DEFAULT NULL AFTER date_converted');
        }
    }
}
