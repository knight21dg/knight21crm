<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Manager Portal access/scope resolution - single, company-wide
 * Operations Manager, not department-scoped (revised architecture; the
 * original per-department-manager design, driven by a
 * tblbusiness_department_staff.is_manager flag, was reverted - see
 * migration 376).
 *
 * The Operations Manager is identified purely through Perfex's native
 * Staff Permissions capability system - the `view` capability under the
 * `manager_portal` feature, registered by manager_portal_permissions()
 * and granted the normal way, at Setup -> Staff -> Edit Staff ->
 * Permissions, exactly like any other module's capability. No new table,
 * no new admin UI - this reuses machinery Perfex already has.
 */

/**
 * Is this staff member the designated Operations Manager - pure
 * capability check, no admin bypass. This is the MENU-visibility gate
 * (Admin keeps its own native dashboard/menu, same convention as every
 * other portal module's "*_department_staff()"-style gate).
 *
 * Deliberately does NOT call staff_can() directly - that function
 * short-circuits to true for any is_admin() account regardless of which
 * capability is asked for (application/helpers/admin_helper.php), which
 * would make this return true for Admin too and leak the Manager Portal
 * menu into the native Admin sidebar alongside its own. Reading the raw
 * granted-permissions list via Staff_model::get_staff_permissions()
 * (same table staff_can() itself reads, just without that bypass) keeps
 * this function's meaning literally "this specific staff row has the
 * capability granted" - the same unbypassed semantics every sibling
 * portal's "*_department_staff()" membership check already has.
 *
 * @param  int|null $staff_id defaults to the logged-in staff member
 * @return bool
 */
function is_manager_portal_operations_manager($staff_id = null)
{
    $CI = &get_instance();
    $CI->load->model('staff_model');

    $staff_id    = $staff_id ?: get_staff_user_id();
    $permissions = $CI->staff_model->get_staff_permissions($staff_id);

    foreach ($permissions as $permission) {
        if ($permission['feature'] === 'manager_portal' && $permission['capability'] === 'view') {
            return true;
        }
    }

    return false;
}

/**
 * CONTROLLER-access gate - Admin (the CEO account) bypasses everything,
 * same superuser convention every other portal module uses.
 *
 * @param  int|null $staff_id defaults to the logged-in staff member
 * @return bool
 */
function is_manager_portal_member($staff_id = null)
{
    return is_admin($staff_id) || is_manager_portal_operations_manager($staff_id);
}

/**
 * The reverse lookup of is_manager_portal_operations_manager() - WHICH
 * staff id holds the capability, not whether a given one does. Added for
 * the Attendance Module Enhancement (Leave/Late Arrival/Early Exit
 * Requests, modules/staff_attendance/), which needs to notify the single
 * Operations Manager without itself being part of this module - reused
 * cross-module exactly like is_manager_portal_operations_manager()
 * already is by dev_portal/dm_portal/field_portal (this module's helper
 * auto-loads globally for every active module, same as any other).
 * "There is only ONE Operations Manager" per this feature's own
 * requirement - LIMIT 1 is a defensive constraint, not an assumption the
 * capability could safely be granted to more than one staff member.
 *
 * @return int|null staffid, or null if nobody currently holds the capability
 */
function get_operations_manager_staff_id()
{
    $CI = &get_instance();
    $CI->db->select('staff_id');
    $CI->db->where('feature', 'manager_portal');
    $CI->db->where('capability', 'view');
    $CI->db->limit(1);
    $row = $CI->db->get(db_prefix() . 'staff_permissions')->row();

    return $row ? (int) $row->staff_id : null;
}

/**
 * Every active staff id company-wide, excluding Admin accounts (the CEO
 * tier is out of scope for "employees the Operations Manager manages")
 * and, by default, the caller themselves (so "Total Team Members"/
 * "Team" never counts the Operations Manager as their own report).
 *
 * @param  bool     $exclude_self
 * @param  int|null $staff_id     defaults to the logged-in staff member
 * @return array staff ids
 */
function manager_portal_all_staff_ids($exclude_self = true, $staff_id = null)
{
    $CI = &get_instance();
    $CI->db->select('staffid');
    $CI->db->where('active', 1);
    $CI->db->where('admin', 0);
    $rows      = $CI->db->get(db_prefix() . 'staff')->result_array();
    $staff_ids = array_map('intval', array_column($rows, 'staffid'));

    if ($exclude_self) {
        $staff_id  = $staff_id ?: get_staff_user_id();
        $staff_ids = array_values(array_diff($staff_ids, [(int) $staff_id]));
    }

    return $staff_ids;
}

/**
 * Daily Work Update status badge for the Team List's "Daily Work Update
 * Status" column and the Employee Profile - Pending/Approved/Needs
 * Revision map to tbldm_portal_work_updates.review_status (the migration
 * 375 columns); 'not_submitted' is this module's own status for "no row
 * for today at all", not a value ever stored in that column.
 *
 * @param  string $status 'not_submitted'|'pending'|'approved'|'needs_revision'
 * @return string HTML
 */
function manager_portal_dwu_status_badge($status)
{
    $map = [
        'not_submitted'  => ['label' => _l('manager_portal_dwu_status_not_submitted'), 'color' => '#9e9e9e'],
        'pending'        => ['label' => _l('manager_portal_dwu_status_pending'), 'color' => '#fb8c00'],
        'approved'       => ['label' => _l('manager_portal_dwu_status_approved'), 'color' => '#43a047'],
        'needs_revision' => ['label' => _l('manager_portal_dwu_status_needs_revision'), 'color' => '#e53935'],
    ];

    $entry = $map[$status] ?? $map['not_submitted'];

    return '<span class="label" style="color:' . $entry['color'] . ';border:1px solid ' . adjust_hex_brightness($entry['color'], 0.4) . ';background:' . adjust_hex_brightness($entry['color'], 0.04) . ';">' . e($entry['label']) . '</span>';
}

/**
 * Performance status badge for the Team List and Employee Profile -
 * derived from Manager_portal_model's own productivity % thresholds
 * (Good >=70%, Average >=40%, Needs Improvement below), shown alongside
 * the raw percentage so the Operations Manager can see the number behind
 * the label, not just a bucket.
 *
 * @param  string $label        'good'|'average'|'needs_improvement'
 * @param  int    $productivity 0-100
 * @return string HTML
 */
function manager_portal_performance_badge($label, $productivity)
{
    $map = [
        'good'              => ['label' => _l('manager_portal_performance_good'), 'color' => '#43a047'],
        'average'           => ['label' => _l('manager_portal_performance_average'), 'color' => '#fb8c00'],
        'needs_improvement' => ['label' => _l('manager_portal_performance_needs_improvement'), 'color' => '#e53935'],
    ];

    $entry = $map[$label] ?? $map['average'];

    return '<span class="label" style="color:' . $entry['color'] . ';border:1px solid ' . adjust_hex_brightness($entry['color'], 0.4) . ';background:' . adjust_hex_brightness($entry['color'], 0.04) . ';">' . e($entry['label']) . ' (' . (int) $productivity . '%)</span>';
}

/**
 * Phase 5 (Attendance Monitoring) - "Late Arrival" and "Early Exit" are
 * requested as their own columns, distinct from the existing "Late"/
 * "Half Day" attendance_status values (which are admin-set labels, not
 * derived from a time comparison). Research confirmed no standard
 * office-hours setting exists anywhere in this install (no option, no
 * config, nothing in modules/staff_attendance) - these thresholds were
 * this module's own documented assumption (09:30 check-in / 18:30
 * check-out, a common convention), used ONLY to compute a display
 * indicator from the existing login_time/logout_time columns. Nothing
 * is written to the database and Staff_attendance_model itself is
 * untouched.
 *
 * Relocated to staff_attendance_helper.php (STAFF_ATTENDANCE_EXPECTED_LOGIN/
 * _LOGOUT + staff_attendance_is_late_arrival()/_is_early_exit()) as the
 * central attendance-domain home once the Leave/Late Arrival/Early Exit
 * Request feature needed the exact same threshold - these two functions
 * are now thin delegating wrappers so every existing manager_portal call
 * site keeps working unchanged.
 *
 * @param  string|null $login_time datetime string, or null
 * @return bool
 */
function manager_portal_attendance_is_late_arrival($login_time)
{
    return staff_attendance_is_late_arrival($login_time);
}

/**
 * @param  string|null $logout_time datetime string, or null
 * @return bool
 */
function manager_portal_attendance_is_early_exit($logout_time)
{
    return staff_attendance_is_early_exit($logout_time);
}
