<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_343 extends CI_Migration
{
    public function up(): void
    {
        // =====================================================================
        // CUSTOMER FILES — Subject & Description (required for customer uploads)
        // =====================================================================
        if (!$this->db->field_exists('subject', db_prefix() . 'files')) {
            $this->db->query('ALTER TABLE `' . db_prefix() . 'files` ADD `subject` VARCHAR(255) NULL DEFAULT NULL AFTER `file_name`;');
        }
        if (!$this->db->field_exists('description', db_prefix() . 'files')) {
            $this->db->query('ALTER TABLE `' . db_prefix() . 'files` ADD `description` TEXT NULL DEFAULT NULL AFTER `subject`;');
        }
    }
}
