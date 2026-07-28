<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_347 extends CI_Migration
{
    public function up(): void
    {
        // =====================================================================
        // CUSTOMERS — Start Date + Status Description (Customer Portal Service History)
        // =====================================================================
        if (!$this->db->field_exists('start_date', db_prefix() . 'clients')) {
            $this->db->query('ALTER TABLE `' . db_prefix() . 'clients` ADD `start_date` DATE NULL DEFAULT NULL AFTER `employee_name`;');
        }
        if (!$this->db->field_exists('status_description', db_prefix() . 'clients')) {
            $this->db->query('ALTER TABLE `' . db_prefix() . 'clients` ADD `status_description` TEXT NULL DEFAULT NULL AFTER `progress`;');
        }
    }
}
