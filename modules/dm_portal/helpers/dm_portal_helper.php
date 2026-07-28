<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Digital Marketing Portal - a simple work portal for the single
 * "Digital Marketing" business department (modules/business_departments),
 * gated by department membership rather than a permission capability -
 * same pattern modules/dev_portal already established for its own 4
 * departments, just scoped to one department here.
 */

/**
 * Name must match tblbusiness_departments.name exactly (already exists
 * live, id 2, with 4 active staff assigned).
 *
 * @return string
 */
function dm_portal_department_name()
{
    return 'Digital Marketing';
}

/**
 * Resolves dm_portal_department_name() to its live
 * tblbusiness_departments id - looked up by name rather than hardcoded,
 * same reasoning as dev_portal_department_ids().
 *
 * @return int
 */
function dm_portal_department_id()
{
    static $id = null;

    if ($id !== null) {
        return $id;
    }

    $CI = &get_instance();
    $CI->db->select('id');
    $CI->db->where('name', dm_portal_department_name());
    $row = $CI->db->get(db_prefix() . 'business_departments')->row();

    $id = $row ? (int) $row->id : 0;

    return $id;
}

/**
 * Pure department-membership check - no Admin bypass. This is the MENU-
 * visibility gate: Admin never sees a "Digital Marketing Portal" sidebar
 * group. Reuses get_staff_business_departments()
 * (modules/business_departments/helpers/business_departments_helper.php).
 *
 * @param  mixed $staff_id defaults to the current staff member
 * @return bool
 */
function is_dm_portal_department_staff($staff_id = null)
{
    if (!is_staff_member()) {
        return false;
    }

    $staff_id = $staff_id ?: get_staff_user_id();

    $departments = get_staff_business_departments($staff_id);
    $my_dept_ids = array_column($departments, 'id');

    return in_array(dm_portal_department_id(), $my_dept_ids);
}

/**
 * CONTROLLER access gate (Dm_portal::__construct()) - Admin is always
 * authorized (standard superuser bypass used throughout this codebase),
 * even though Admin never sees the menu. Everyone else must be a real
 * Digital Marketing department member.
 *
 * @param  mixed $staff_id defaults to the current staff member
 * @return bool
 */
function is_dm_portal_member($staff_id = null)
{
    if (!is_staff_member()) {
        return false;
    }

    return is_admin($staff_id) || is_dm_portal_department_staff($staff_id);
}

/**
 * Progress percentage derived from a task's real status - tbltasks has
 * no numeric progress column (confirmed via a live schema check), so
 * this maps the 5 real Perfex task statuses onto their natural pipeline
 * order (Tasks_model::get_statuses()'s own 'order' field: Not Started ->
 * In Progress -> Testing -> Awaiting Feedback -> Complete), rather than
 * introducing a new stored value.
 *
 * @param  int $status
 * @return int 0-100
 */
function dm_portal_task_progress_percent($status)
{
    $map = [
        Tasks_model::STATUS_NOT_STARTED       => 0,
        Tasks_model::STATUS_IN_PROGRESS       => 35,
        Tasks_model::STATUS_TESTING           => 65,
        Tasks_model::STATUS_AWAITING_FEEDBACK => 85,
        Tasks_model::STATUS_COMPLETE          => 100,
    ];

    return $map[(int) $status] ?? 0;
}
