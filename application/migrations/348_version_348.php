<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_348 extends CI_Migration
{
    public function up(): void
    {
        // =====================================================================
        // PROJECTS — Service management fields (Department, Assigned Employee,
        // Assigned Work, Work Status, Status Description, Last Updated).
        // Start Date, Due Date (deadline) and Progress already exist on
        // tblprojects and are reused as-is.
        // =====================================================================
        if (!$this->db->field_exists('department', db_prefix() . 'projects')) {
            $this->db->query('ALTER TABLE `' . db_prefix() . 'projects` ADD `department` VARCHAR(100) NULL DEFAULT NULL AFTER `clientid`;');
        }
        if (!$this->db->field_exists('assigned_employee', db_prefix() . 'projects')) {
            $this->db->query('ALTER TABLE `' . db_prefix() . 'projects` ADD `assigned_employee` INT(11) NULL DEFAULT NULL AFTER `department`;');
        }
        if (!$this->db->field_exists('assigned_work', db_prefix() . 'projects')) {
            $this->db->query('ALTER TABLE `' . db_prefix() . 'projects` ADD `assigned_work` VARCHAR(255) NULL DEFAULT NULL AFTER `assigned_employee`;');
        }
        if (!$this->db->field_exists('work_status', db_prefix() . 'projects')) {
            $this->db->query('ALTER TABLE `' . db_prefix() . 'projects` ADD `work_status` VARCHAR(50) NULL DEFAULT NULL AFTER `assigned_work`;');
        }
        if (!$this->db->field_exists('status_description', db_prefix() . 'projects')) {
            $this->db->query('ALTER TABLE `' . db_prefix() . 'projects` ADD `status_description` TEXT NULL DEFAULT NULL AFTER `work_status`;');
        }
        if (!$this->db->field_exists('last_updated', db_prefix() . 'projects')) {
            $this->db->query('ALTER TABLE `' . db_prefix() . 'projects` ADD `last_updated` DATETIME NULL DEFAULT NULL AFTER `status_description`;');
        }
    }
}
