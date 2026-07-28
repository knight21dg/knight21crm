<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Staff Attendance
Description: Simple login/logout-based Employee Attendance tracking - Present/Absent/Late/Half Day/Leave/Weekend status, working hours, and Admin/Staff views. Deliberately NOT payroll, leave management, shift management, biometric/GPS/face-recognition attendance, overtime, or holiday management. Hooks into the EXISTING staff login/logout flow (after_staff_login / before_staff_logout, both already fired by application/controllers/admin/Authentication.php for every login path - normal, 2FA email, 2FA app) - no Authentication controller/model file is ever edited.
Version: 1.0.0
Requires at least: 3.3.*
*/

require_once __DIR__ . '/helpers/staff_attendance_helper.php';

hooks()->add_action('admin_init', 'staff_attendance_module_init_menu_items');
hooks()->add_action('admin_init', 'staff_attendance_permissions');
hooks()->add_filter('get_dashboard_widgets', 'staff_attendance_add_dashboard_widget');

// LOGIN: after_staff_login fires once the staff session is already fully
// established (get_staff_user_id() is valid) - the exact same hook used
// for every successful login path (normal, 2FA email, 2FA app), see
// Authentication.php lines 87/121/132. Find-or-create is handled inside
// record_login() itself (Staff_attendance_model) - safe to call on every
// login without producing duplicates.
hooks()->add_action('after_staff_login', 'staff_attendance_on_login');

// LOGOUT: before_staff_logout fires BEFORE the session is unset/destroyed
// and is the only staff-specific logout hook (Authentication_model::logout()
// branches staff vs. client and passes the staff id as a parameter,
// avoiding any dependency on session state at hook-time) - see
// Authentication_model.php line 146.
hooks()->add_action('before_staff_logout', 'staff_attendance_on_logout');

/**
 * Single, flat "Staff Attendance" top-level sidebar link - no children
 * registered for this slug, which is what makes
 * application/views/admin/includes/aside.php render it as a plain,
 * directly-clickable item with no expand arrow and no submenu (same
 * markup path core's own childless 'leads' item already goes through -
 * confirmed by reading that template: the arrow and the <ul> submenu are
 * both wrapped in `if (count($item['children']) > 0)`). Attendance is
 * the only real page this module has, so the former
 * Operations > Attendance two-level structure was pure overhead; this
 * replaces it with one link, visible to every logged-in staff member.
 * The controller itself (Staff_attendance::index()) still branches Admin
 * (full list + filters) vs. Staff (My Attendance, self-scoped), so one
 * menu entry/route continues to serve both roles.
 *
 * Slug stays 'operations' (unchanged from before this simplification) -
 * purely an internal App_menu registry key, never shown to users, but
 * modules/dm_portal/dm_portal.php and modules/dev_portal/dev_portal.php
 * both explicitly hide THIS EXACT slug from their own department staff's
 * sidebar (`unset($items['operations'])`, since those staff reach
 * Attendance via their own portal's deep link instead) - renaming it
 * would silently break that hiding and leak a stray item into their
 * otherwise fully walled-off navigation.
 */
function staff_attendance_module_init_menu_items()
{
    $CI = &get_instance();

    if (!is_staff_member()) {
        return;
    }

    // Root cause of the "staff_attendance_operations" raw-key flash:
    // admin_init fires on EVERY admin page, not just this module's own
    // controller, but this module's language file is only ever loaded
    // by Staff_attendance::__construct() (its own controller) and
    // dashboard_widget.php (the Admin dashboard widget) - neither of
    // which runs for e.g. Customers, Projects, or any other admin page.
    // _l() below would then have nothing loaded to translate against and
    // falls back to echoing the raw key. Core's own menu labels (Reports,
    // Utilities, etc. - application/helpers/menu_helper.php) never hit
    // this because application/config/autoload.php autoloads core's
    // english_lang.php on every single request; custom module language
    // files are never autoloaded, so each module must load its own here
    // - exactly what dm_portal/dev_portal/follow_up_management's own
    // menu-registration functions already do for the same reason.
    $CI->lang->load('staff_attendance/staff_attendance', 'english');

    $CI->app_menu->add_sidebar_menu_item('operations', [
        'name'     => _l('staff_attendance_menu_title'),
        'href'     => admin_url('staff_attendance'),
        'icon'     => 'fa-regular fa-clock',
        'position' => 60,
        'badge'    => [],
    ]);
}

/**
 * No capability system used deliberately - the spec is a strict "Only
 * for Admin" gate on the full list/edit actions (Staff_attendance
 * controller checks is_admin() directly), not a grantable permission a
 * non-admin could be given. This capability exists only so Setup ->
 * Staff Permissions has *something* to show if another module ever
 * wants to check "can this staff member see Attendance at all" (every
 * staff member can, via My Attendance) - matches the same registration
 * pattern every other module in this codebase already follows, kept
 * minimal since nothing currently reads it.
 */
function staff_attendance_permissions()
{
    $capabilities = [];

    $capabilities['capabilities'] = [
        'view' => _l('permission_view') . '(' . _l('permission_global') . ')',
    ];

    register_staff_capabilities('staff_attendance', $capabilities, _l('staff_attendance'));
}

/**
 * Today's Attendance summary - Admin only (self-guarding inside the
 * widget view itself, same convention as every other role-gated widget
 * in this codebase), reusing Staff_attendance_model::get_today_summary()
 * (a single grouped-by-status query, not one COUNT per status).
 */
function staff_attendance_add_dashboard_widget($widgets)
{
    $widgets[] = [
        'path'      => 'staff_attendance/dashboard_widget',
        'container' => 'right-4',
    ];

    return $widgets;
}

/**
 * after_staff_login callback - see registration above for exactly which
 * core login paths this covers.
 */
function staff_attendance_on_login()
{
    if (!is_staff_logged_in()) {
        return;
    }

    $CI = &get_instance();
    $CI->load->model('staff_attendance/staff_attendance_model');
    $CI->staff_attendance_model->record_login(get_staff_user_id());
}

/**
 * before_staff_logout callback - $staff_id is passed directly by the
 * hook (Authentication_model::logout() line 146), not read from session,
 * so this works correctly regardless of session-destroy timing.
 *
 * @param mixed $staff_id
 */
function staff_attendance_on_logout($staff_id)
{
    if (!$staff_id) {
        return;
    }

    $CI = &get_instance();
    $CI->load->model('staff_attendance/staff_attendance_model');
    $CI->staff_attendance_model->record_logout($staff_id);
}
