<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_351 extends CI_Migration
{
    public function up(): void
    {
        // =====================================================================
        // PROJECTS — Assigned Employee now reuses the exact same Customers
        // Department -> Employee cascading dropdown (get_service_departments()/
        // get_department_employees()), matching tblclients.employee_name's
        // type exactly. Previously an INT staff_id FK; existing numeric
        // values are harmless leftovers that simply won't match any option
        // in the new dropdown until re-selected.
        // =====================================================================
        $this->db->query('ALTER TABLE `' . db_prefix() . 'projects` MODIFY `assigned_employee` VARCHAR(150) NULL DEFAULT NULL;');
    }
}
