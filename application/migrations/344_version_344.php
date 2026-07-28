<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_344 extends CI_Migration
{
    public function up(): void
    {
        // =====================================================================
        // CUSTOMERS — Department / Employee assignment + Due Date
        // =====================================================================
        if (!$this->db->field_exists('department', db_prefix() . 'clients')) {
            $this->db->query('ALTER TABLE `' . db_prefix() . 'clients` ADD `department` VARCHAR(100) NULL DEFAULT NULL AFTER `assigned_to`;');
        }
        if (!$this->db->field_exists('employee_name', db_prefix() . 'clients')) {
            $this->db->query('ALTER TABLE `' . db_prefix() . 'clients` ADD `employee_name` VARCHAR(150) NULL DEFAULT NULL AFTER `department`;');
        }
        if (!$this->db->field_exists('due_date', db_prefix() . 'clients')) {
            $this->db->query('ALTER TABLE `' . db_prefix() . 'clients` ADD `due_date` DATE NULL DEFAULT NULL AFTER `work_status`;');
        }

        // Backfill department from the legacy assigned_to role dropdown
        $this->db->query('UPDATE `' . db_prefix() . 'clients` SET `department` = `assigned_to` WHERE (`department` IS NULL OR `department` = \'\') AND `assigned_to` IS NOT NULL AND `assigned_to` != \'\';');
    }
}
