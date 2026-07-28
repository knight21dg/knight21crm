<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Daily Work Update - a shared, company-wide feature used by exactly 9
 * Business Departments (modules/business_departments). Membership is
 * gated by department, same pattern modules/dm_portal and modules/dev_portal
 * already established for their own department scopes - this module is
 * the first to span more than one department at once, so the list of 9
 * names is centralized here as the single source of truth (also reused
 * later by the centralized Admin page's Department filter).
 */

/**
 * Review status badge for a submission - shown on the employee's own
 * "Recent Daily Work Updates" panel (views/index.php) so they can see
 * whether a reviewer (Manager Portal or, in a later phase, the Admin
 * report's own detail modal) has approved it or asked for changes.
 * 'pending' is the migration 375 column default, before any reviewer has
 * acted.
 *
 * @param  string $status 'pending'|'approved'|'needs_revision'
 * @return string HTML
 */
function daily_work_update_review_badge($status)
{
    $map = [
        'pending'        => ['label' => _l('daily_work_update_review_pending'), 'color' => '#fb8c00'],
        'approved'       => ['label' => _l('daily_work_update_review_approved'), 'color' => '#43a047'],
        'needs_revision' => ['label' => _l('daily_work_update_review_needs_revision'), 'color' => '#e53935'],
    ];

    $entry = $map[$status] ?? $map['pending'];

    return '<span class="label" style="color:' . $entry['color'] . ';border:1px solid ' . adjust_hex_brightness($entry['color'], 0.4) . ';background:' . adjust_hex_brightness($entry['color'], 0.04) . ';">' . e($entry['label']) . '</span>';
}

/**
 * Exact tblbusiness_departments.name values this feature is scoped to -
 * confirmed live against the table (ids 1-4, 6-10; no id 5). Names must
 * match exactly, same as dm_portal_department_name()/dev_portal's own
 * department name constants.
 *
 * @return array<string>
 */
function daily_work_update_target_departments()
{
    return [
        'CEO',
        'Digital Marketing',
        'Field Executing',
        'Telecalling',
        'SEO',
        'Web Development',
        'App Development',
        'Testing',
        'UI/UX Design',
    ];
}

/**
 * Resolves daily_work_update_target_departments() to their live
 * tblbusiness_departments ids - looked up by name rather than hardcoded,
 * same reasoning as dm_portal_department_id()/dev_portal_department_ids().
 * Cached per-request; a department that doesn't exist (e.g. renamed) is
 * silently skipped rather than erroring.
 *
 * @return array<int>
 */
function daily_work_update_target_department_ids()
{
    static $ids = null;

    if ($ids !== null) {
        return $ids;
    }

    $CI = &get_instance();
    $CI->db->select('id');
    $CI->db->where_in('name', daily_work_update_target_departments());
    $rows = $CI->db->get(db_prefix() . 'business_departments')->result_array();

    $ids = array_map('intval', array_column($rows, 'id'));

    return $ids;
}

/**
 * Pure department-membership check - no Admin bypass. This is the MENU-
 * visibility gate, same role is_dm_portal_department_staff()/
 * is_dev_portal_department_staff() play for their own modules.
 *
 * @param  mixed $staff_id defaults to the current staff member
 * @return bool
 */
function is_daily_work_update_department_staff($staff_id = null)
{
    if (!is_staff_member()) {
        return false;
    }

    $staff_id = $staff_id ?: get_staff_user_id();

    $departments = get_staff_business_departments($staff_id);
    $my_dept_ids = array_column($departments, 'id');

    return count(array_intersect($my_dept_ids, daily_work_update_target_department_ids())) > 0;
}

/**
 * CONTROLLER access gate (Daily_work_update::__construct()) - Admin is
 * always authorized, even though Admin never sees the menu item this
 * module itself would add. Everyone else must belong to one of the 9
 * target departments.
 *
 * @param  mixed $staff_id defaults to the current staff member
 * @return bool
 */
function is_daily_work_update_member($staff_id = null)
{
    if (!is_staff_member()) {
        return false;
    }

    return is_admin($staff_id) || is_daily_work_update_department_staff($staff_id);
}

/**
 * The subset of daily_work_update_target_departments() that already have
 * their own department portal with a Daily Work Update sidebar entry
 * pointing at this same shared controller (modules/dm_portal for Digital
 * Marketing, modules/dev_portal for these 4, modules/field_portal for
 * Field Executing) - kept here, not in any of those modules, so this
 * module stays the single place that decides who still needs a menu link
 * added.
 *
 * @return array<string>
 */
function daily_work_update_departments_with_own_portal()
{
    return [
        'Digital Marketing',
        'Web Development',
        'App Development',
        'UI/UX Design',
        'Testing',
        'Field Executing',
    ];
}

/**
 * MENU-visibility gate for this module's OWN top-level sidebar link
 * (daily_work_update_module_init_menu_item(), below) - true only for a
 * target-department staff member whose departments do NOT include any of
 * daily_work_update_departments_with_own_portal() above, i.e. CEO/
 * Telecalling/SEO staff, who have no portal of their own to carry this
 * link. Digital Marketing / dev-department / Field Executing staff
 * already see it via their own portal's sidebar (dm_portal/dev_portal/
 * field_portal) and must not get a second, duplicate entry here.
 *
 * @param  mixed $staff_id defaults to the current staff member
 * @return bool
 */
function is_daily_work_update_menu_eligible_staff($staff_id = null)
{
    if (!is_staff_member()) {
        return false;
    }

    $staff_id    = $staff_id ?: get_staff_user_id();
    $departments = array_column(get_staff_business_departments($staff_id), 'name');

    if (count(array_intersect($departments, daily_work_update_target_departments())) === 0) {
        return false;
    }

    return count(array_intersect($departments, daily_work_update_departments_with_own_portal())) === 0;
}

/**
 * Every ACTIVE staff id belonging to any of the 9 target departments -
 * the Admin report's scope for "all staff who should be submitting" (the
 * Employee filter dropdown and the "Not Submitted Today" comparison both
 * need this same set). Mirrors dev_portal_staff_ids()'s exact pattern -
 * reuses the existing get_department_staff_ids() rather than a new query
 * shape, and de-dupes staff who belong to more than one target
 * department.
 *
 * @return array<int>
 */
function daily_work_update_target_department_staff_ids()
{
    $CI = &get_instance();
    $CI->load->model('business_departments/business_departments_model');

    $staff_ids = [];
    foreach (daily_work_update_target_department_ids() as $department_id) {
        $staff_ids = array_merge($staff_ids, $CI->business_departments_model->get_department_staff_ids($department_id));
    }

    return array_unique(array_map('intval', $staff_ids));
}
