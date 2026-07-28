<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Business Departments
Description: Central Department <-> Staff foundation shared by Customers, Projects and Leads for Department / Assigned Employee assignment. Replaces the temporary hardcoded get_department_employee_map().
Version: 1.0.0
Requires at least: 3.3.*
*/

require_once __DIR__ . '/helpers/business_departments_helper.php';

hooks()->add_action('admin_init', 'business_departments_module_init_menu_items');
hooks()->add_action('admin_init', 'business_departments_permissions');
hooks()->add_action('staff_member_deleted', 'business_departments_staff_member_deleted');

function business_departments_module_init_menu_items()
{
    $CI = &get_instance();
    business_departments_load_language();

    if (staff_can('view', 'business_departments')) {
        $CI->app_menu->add_setup_menu_item('business-departments', [
            'name'     => _l('business_departments'),
            'href'     => admin_url('business_departments'),
            'position' => 27,
            'icon'     => 'fa-regular fa-building',
        ]);
    }
}

function business_departments_permissions()
{
    business_departments_load_language();
    $capabilities = [];

    $capabilities['capabilities'] = [
        'view'   => _l('permission_view') . '(' . _l('permission_global') . ')',
        'create' => _l('permission_create'),
        'edit'   => _l('permission_edit'),
        'delete' => _l('permission_delete'),
    ];

    register_staff_capabilities('business_departments', $capabilities, _l('business_departments'));
}

/**
 * A deleted staff member should no longer appear as mapped to any
 * Business Department - this is a pure many-to-many mapping (not an
 * "owner" field), so the mapping row is simply removed rather than
 * transferred to another staff member.
 */
function business_departments_staff_member_deleted($data)
{
    $CI = &get_instance();
    $CI->db->where('staff_id', $data['id']);
    $CI->db->delete(db_prefix() . 'business_department_staff');
}
