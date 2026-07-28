<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_352 extends CI_Migration
{
    public function up(): void
    {
        // =====================================================================
        // BUSINESS DEPARTMENTS — new foundation module. Replaces the temporary
        // get_department_employee_map()/get_service_departments()/
        // get_department_employees() hardcoded array (application/helpers/
        // clients_helper.php) with a real, admin-manageable master table plus
        // a Department <-> Staff many-to-many mapping backed by the real
        // tblstaff table (mirrors the existing tbldepartments/
        // tblstaff_departments *pattern*, but intentionally NOT that table -
        // tbldepartments is IMAP/ticket-routing configuration, unrelated to
        // business/service categorization - see the architecture analysis).
        // =====================================================================

        if (!$this->db->table_exists(db_prefix() . 'business_departments')) {
            $this->db->query('
                CREATE TABLE `' . db_prefix() . 'business_departments` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `name` varchar(150) NOT NULL,
                    `description` text NULL DEFAULT NULL,
                    `active` tinyint(1) NOT NULL DEFAULT 1,
                    `display_order` int(11) NOT NULL DEFAULT 0,
                    `created_at` datetime NOT NULL,
                    `updated_at` datetime NULL DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `name` (`name`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
            ');
        }

        if (!$this->db->table_exists(db_prefix() . 'business_department_staff')) {
            $this->db->query('
                CREATE TABLE `' . db_prefix() . 'business_department_staff` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `department_id` int(11) NOT NULL,
                    `staff_id` int(11) NOT NULL,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `department_staff` (`department_id`,`staff_id`),
                    KEY `staff_id` (`staff_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
            ');
        }

        // Seed with the exact same department names the old hardcoded map used,
        // so existing free-text department values on clients/projects can be
        // backfilled to the matching new department record below.
        $legacy_department_names = [
            'CEO', 'Marketing Manager', 'Field Executive', 'Telecaller',
            'Digital Marketer', 'SEO Specialist', 'Web Developer', 'App Developer', 'Tester',
        ];

        foreach ($legacy_department_names as $order => $name) {
            $exists = $this->db->where('name', $name)->get(db_prefix() . 'business_departments')->row();
            if (!$exists) {
                $this->db->insert(db_prefix() . 'business_departments', [
                    'name'          => $name,
                    'active'        => 1,
                    'display_order' => $order + 1,
                    'created_at'    => date('Y-m-d H:i:s'),
                ]);
            }
        }

        // =====================================================================
        // CUSTOMERS — migrate `department` (free text) to a real FK-by-
        // convention int column, matching the exact same field name so no
        // form/JS/model call site needs to change. `employee_name` becomes an
        // int staff_id FK too, but keeps its legacy column name for the same
        // backward-compatibility reason (documented here to avoid confusion:
        // despite the name, this column stores tblstaff.staffid, not a name
        // string, from this migration forward).
        // =====================================================================
        if ($this->db->field_exists('department', db_prefix() . 'clients') && $this->db->field_exists('department', db_prefix() . 'clients')) {
            $column_info = $this->db->field_data(db_prefix() . 'clients');
            $department_is_text = false;
            foreach ($column_info as $col) {
                if ($col->name === 'department' && stripos($col->type, 'char') !== false) {
                    $department_is_text = true;
                }
            }

            if ($department_is_text) {
                $this->db->query('ALTER TABLE `' . db_prefix() . 'clients` ADD `department_id_tmp` INT(11) NULL DEFAULT NULL AFTER `department`;');
                $this->db->query('
                    UPDATE `' . db_prefix() . 'clients` c
                    JOIN `' . db_prefix() . 'business_departments` bd ON bd.name = c.department
                    SET c.department_id_tmp = bd.id
                    WHERE c.department IS NOT NULL AND c.department != "";
                ');

                $this->db->query('ALTER TABLE `' . db_prefix() . 'clients` ADD `employee_name_tmp` INT(11) NULL DEFAULT NULL AFTER `employee_name`;');
                // Only preserve values that already happen to be a valid, existing staff id;
                // dummy placeholder names (e.g. "Marketing Employee 2") have no honest real-staff
                // match and are intentionally left unset for an admin to re-select.
                $this->db->query('
                    UPDATE `' . db_prefix() . 'clients` c
                    JOIN `' . db_prefix() . 'staff` s ON s.staffid = c.employee_name
                    SET c.employee_name_tmp = s.staffid
                    WHERE c.employee_name REGEXP "^[0-9]+$";
                ');

                $this->db->query('ALTER TABLE `' . db_prefix() . 'clients` DROP COLUMN `department`;');
                $this->db->query('ALTER TABLE `' . db_prefix() . 'clients` DROP COLUMN `employee_name`;');
                $this->db->query('ALTER TABLE `' . db_prefix() . 'clients` CHANGE `department_id_tmp` `department` INT(11) NULL DEFAULT NULL;');
                $this->db->query('ALTER TABLE `' . db_prefix() . 'clients` CHANGE `employee_name_tmp` `employee_name` INT(11) NULL DEFAULT NULL;');
            }
        }

        // =====================================================================
        // PROJECTS — same treatment. `department` and `assigned_employee` both
        // become int FK-by-convention columns, keeping their existing names.
        // =====================================================================
        if ($this->db->field_exists('department', db_prefix() . 'projects')) {
            $column_info = $this->db->field_data(db_prefix() . 'projects');
            $department_is_text = false;
            foreach ($column_info as $col) {
                if ($col->name === 'department' && stripos($col->type, 'char') !== false) {
                    $department_is_text = true;
                }
            }

            if ($department_is_text) {
                $this->db->query('ALTER TABLE `' . db_prefix() . 'projects` ADD `department_id_tmp` INT(11) NULL DEFAULT NULL AFTER `department`;');
                $this->db->query('
                    UPDATE `' . db_prefix() . 'projects` p
                    JOIN `' . db_prefix() . 'business_departments` bd ON bd.name = p.department
                    SET p.department_id_tmp = bd.id
                    WHERE p.department IS NOT NULL AND p.department != "";
                ');

                $this->db->query('ALTER TABLE `' . db_prefix() . 'projects` ADD `assigned_employee_tmp` INT(11) NULL DEFAULT NULL AFTER `assigned_employee`;');
                $this->db->query('
                    UPDATE `' . db_prefix() . 'projects` p
                    JOIN `' . db_prefix() . 'staff` s ON s.staffid = p.assigned_employee
                    SET p.assigned_employee_tmp = s.staffid
                    WHERE p.assigned_employee REGEXP "^[0-9]+$";
                ');

                $this->db->query('ALTER TABLE `' . db_prefix() . 'projects` DROP COLUMN `department`;');
                $this->db->query('ALTER TABLE `' . db_prefix() . 'projects` DROP COLUMN `assigned_employee`;');
                $this->db->query('ALTER TABLE `' . db_prefix() . 'projects` CHANGE `department_id_tmp` `department` INT(11) NULL DEFAULT NULL;');
                $this->db->query('ALTER TABLE `' . db_prefix() . 'projects` CHANGE `assigned_employee_tmp` `assigned_employee` INT(11) NULL DEFAULT NULL;');
            }
        }

        // =====================================================================
        // LEADS — new, purely additive column. Leads already has a real staff
        // FK for assignment (`assigned`, pre-existing core Perfex column) -
        // only Department is new here.
        // =====================================================================
        if (!$this->db->field_exists('department', db_prefix() . 'leads')) {
            $this->db->query('ALTER TABLE `' . db_prefix() . 'leads` ADD `department` INT(11) NULL DEFAULT NULL AFTER `assigned`;');
        }
    }
}
