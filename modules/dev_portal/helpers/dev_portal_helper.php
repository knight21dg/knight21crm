<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Development Portal - a single shared portal for Web Development, App
 * Development, UI/UX Design and Testing, gated by Business Department
 * membership (modules/business_departments) rather than a native Perfex
 * permission capability - see follow_up_management's staff_can()-based
 * tiering for the alternative pattern this deliberately does NOT reuse
 * (that one gates by capability, this one by department membership, per
 * the approved architecture).
 */

/**
 * The 4 departments this portal is scoped to. Names must match
 * tblbusiness_departments.name exactly (Web Development/App Development/
 * Testing already existed before this module; UI/UX Design was added by
 * migration 363).
 *
 * @return array
 */
function dev_portal_department_names()
{
    return [
        'Web Development',
        'App Development',
        'UI/UX Design',
        'Testing',
    ];
}

/**
 * Resolves dev_portal_department_names() to their live
 * tblbusiness_departments ids - looked up by name rather than hardcoded,
 * so this keeps working regardless of insertion order/id values.
 *
 * @return array int[]
 */
function dev_portal_department_ids()
{
    static $ids = null;

    if ($ids !== null) {
        return $ids;
    }

    $CI = &get_instance();
    $CI->db->select('id');
    $CI->db->where_in('name', dev_portal_department_names());
    $rows = $CI->db->get(db_prefix() . 'business_departments')->result_array();

    $ids = array_column($rows, 'id');

    return $ids;
}

/**
 * Every staff id belonging to any of the 4 dev departments - the
 * Admin-tier scope for My Projects/My Tasks (cross-department overview),
 * built from the already-existing get_department_staff_ids() rather than
 * a new query shape.
 *
 * @return array int[]
 */
function dev_portal_staff_ids()
{
    $CI = &get_instance();
    $CI->load->model('business_departments/business_departments_model');

    $staff_ids = [];
    foreach (dev_portal_department_ids() as $department_id) {
        $staff_ids = array_merge($staff_ids, $CI->business_departments_model->get_department_staff_ids($department_id));
    }

    return array_unique(array_map('intval', $staff_ids));
}

/**
 * Pure department-membership check - no Admin bypass. This is the
 * MENU-visibility gate: Admin continues using the original Perfex CRM
 * (Projects/Tasks/Calendar/Attendance/Tickets/Profile) unchanged and
 * must never see a "Development Portal" sidebar group of their own -
 * per the approved architecture, Admin gets zero duplicate navigation
 * surface. Reuses the existing reverse lookup helper
 * get_staff_business_departments()
 * (modules/business_departments/helpers/business_departments_helper.php)
 * rather than adding a second one.
 *
 * @param  mixed $staff_id defaults to the current staff member
 * @return bool
 */
function is_dev_portal_department_staff($staff_id = null)
{
    if (!is_staff_member()) {
        return false;
    }

    $staff_id = $staff_id ?: get_staff_user_id();

    $departments  = get_staff_business_departments($staff_id);
    $my_dept_ids  = array_column($departments, 'id');
    $dev_dept_ids = dev_portal_department_ids();

    return count(array_intersect($my_dept_ids, $dev_dept_ids)) > 0;
}

/**
 * CONTROLLER access gate (Dev_portal::__construct()) - Admin is always
 * authorized (standard superuser bypass used throughout this codebase,
 * e.g. every other module's own access check), even though Admin never
 * sees the menu (is_dev_portal_department_staff() above governs that).
 * Everyone else must be a real dev-department member.
 *
 * @param  mixed $staff_id defaults to the current staff member
 * @return bool
 */
function is_dev_portal_member($staff_id = null)
{
    if (!is_staff_member()) {
        return false;
    }

    return is_admin($staff_id) || is_dev_portal_department_staff($staff_id);
}

/**
 * The 4 statuses a Development Portal user is allowed to SET, mapped to
 * their real Projects_model::get_project_statuses() ids (confirmed live:
 * 1 Not Started, 2 In Progress, 3 On Hold, 4 Finished, 5 Cancelled) -
 * simplified labels only, the underlying tblprojects.status value and
 * Projects_model::mark_as() are unchanged. Id 5 (Cancelled) is
 * deliberately excluded here - Admin/Manager only, per the approved
 * scope.
 *
 * @return array [id => label]
 */
function dev_portal_project_status_options()
{
    return [
        1 => _l('dev_portal_status_pending'),
        2 => _l('dev_portal_status_in_progress'),
        3 => _l('dev_portal_status_on_hold'),
        4 => _l('dev_portal_status_completed'),
    ];
}

/**
 * Display label for ANY real status id, including 5 (Cancelled) - used
 * for the read-only badge shown when a project was already cancelled by
 * Admin (never a selectable option, see dev_portal_project_status_options()
 * above).
 *
 * @param  int $status_id
 * @return string
 */
function dev_portal_project_status_label($status_id)
{
    $options = dev_portal_project_status_options();

    if (isset($options[$status_id])) {
        return $options[$status_id];
    }

    if ((int) $status_id === 5) {
        return _l('dev_portal_status_cancelled');
    }

    return _l('dev_portal_status_pending');
}
