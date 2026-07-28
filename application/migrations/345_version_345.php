<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_345 extends CI_Migration
{
    public function up(): void
    {
        // =====================================================================
        // CUSTOMERS — Progress + Last Updated
        // =====================================================================
        if (!$this->db->field_exists('progress', db_prefix() . 'clients')) {
            $this->db->query('ALTER TABLE `' . db_prefix() . 'clients` ADD `progress` INT NOT NULL DEFAULT 0 AFTER `due_date`;');
        }
        if (!$this->db->field_exists('last_updated', db_prefix() . 'clients')) {
            $this->db->query('ALTER TABLE `' . db_prefix() . 'clients` ADD `last_updated` DATETIME NULL DEFAULT NULL AFTER `progress`;');
        }
    }
}
