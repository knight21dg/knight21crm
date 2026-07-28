<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Shared Business Department service - the single source of truth for
 * Department / Assigned Employee data, consumed by the Customers, Projects
 * and Leads modules. Replaces the old hardcoded get_department_employee_map()
 * (application/helpers/clients_helper.php, now removed).
 *
 * All lookups are backed by real tables:
 * - tblbusiness_departments      (the department master list)
 * - tblbusiness_department_staff (department <-> tblstaff many-to-many map)
 */

/**
 * Explicitly loads modules/business_departments/language/english/
 * business_departments_lang.php. Needed because this module's _l() keys
 * are used from admin_init hook callbacks (menu item + permission group
 * registration) that run on every admin page, not just this module's own
 * routed controller, so MX's "auto-load the current module's language
 * file" convention doesn't reach them - same reasoning and fix pattern as
 * follow_up_management_load_language() in
 * modules/follow_up_management/follow_up_management.php.
 */
function business_departments_load_language()
{
    $CI = &get_instance();
    $CI->lang->load('business_departments', 'english', false, true, '', 'business_departments');
}

/**
 * All departments available for the Department dropdown.
 *
 * @param  bool $active_only
 * @return array [['id' => int, 'name' => string], ...]
 */
function get_business_departments($active_only = true)
{
    $CI = &get_instance();
    $CI->db->select('id, name');
    if ($active_only) {
        $CI->db->where('active', 1);
    }
    $CI->db->order_by('display_order', 'asc');
    $CI->db->order_by('name', 'asc');

    return $CI->db->get(db_prefix() . 'business_departments')->result_array();
}

/**
 * Single department name lookup, for display purposes wherever only the
 * department id is stored (Customers/Projects/Leads records).
 *
 * @param  int $department_id
 * @return string
 */
function get_business_department_name($department_id)
{
    if (empty($department_id)) {
        return '';
    }

    $CI = &get_instance();
    $CI->db->select('name');
    $CI->db->where('id', $department_id);
    $department = $CI->db->get(db_prefix() . 'business_departments')->row();

    return $department ? $department->name : '';
}

/**
 * Real, active staff members mapped to a given Business Department - the
 * data source for the Assigned Employee dropdown, scoped by the currently
 * selected Department (mirrors the department -> employee cascade already
 * used on the Customers/Projects forms and lists, just backed by real
 * tblstaff records instead of a hardcoded name string).
 *
 * @param  int $department_id
 * @return array [['staffid' => int, 'name' => string], ...]
 */
function get_business_department_staff($department_id)
{
    if (empty($department_id)) {
        return [];
    }

    $CI = &get_instance();
    $CI->db->select('staff.staffid, staff.firstname, staff.lastname');
    $CI->db->from(db_prefix() . 'business_department_staff bds');
    $CI->db->join(db_prefix() . 'staff staff', 'staff.staffid = bds.staff_id');
    $CI->db->where('bds.department_id', $department_id);
    $CI->db->where('staff.active', 1);
    $CI->db->order_by('staff.firstname', 'asc');
    $rows = $CI->db->get()->result_array();

    $staff = [];
    foreach ($rows as $row) {
        $staff[] = [
            'staffid' => $row['staffid'],
            'name'    => trim($row['firstname'] . ' ' . $row['lastname']),
        ];
    }

    return $staff;
}

/**
 * The full department -> staff map, in the exact [id => [{staffid,name}, ...]]
 * shape the Add/Edit form's client-side cascading dropdown JS needs (same
 * role the old hardcoded departmentEmployeeMap JS variable played, now
 * sourced from real data).
 *
 * @return array
 */
function get_business_department_staff_map()
{
    $departments = get_business_departments(false);
    $map         = [];

    foreach ($departments as $department) {
        $map[$department['id']] = get_business_department_staff($department['id']);
    }

    return $map;
}

/**
 * Which Business Departments a given staff member belongs to - reverse
 * lookup, useful for the staff profile and for scoping "my department"
 * style views in future integrations.
 *
 * @param  int $staff_id
 * @return array [['id' => int, 'name' => string], ...]
 */
function get_staff_business_departments($staff_id)
{
    $CI = &get_instance();
    $CI->db->select('bd.id, bd.name');
    $CI->db->from(db_prefix() . 'business_department_staff bds');
    $CI->db->join(db_prefix() . 'business_departments bd', 'bd.id = bds.department_id');
    $CI->db->where('bds.staff_id', $staff_id);
    $CI->db->order_by('bd.name', 'asc');

    return $CI->db->get()->result_array();
}
