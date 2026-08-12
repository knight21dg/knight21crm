<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Digital Marketing Portal
Description: Simple work portal for Digital Marketing department staff - Dashboard and My Work (a single combined projects+tasks workspace, no separate My Projects/My Tasks pages), gated by Business Department membership. Daily Work Update is a plain deep link to the shared modules/daily_work_update controller (no implementation of its own here anymore); Attendance and Profile are plain deep links to the already-built Staff Attendance and Profile pages - no new code for any of the three.
Version: 1.0.0
Requires at least: 3.6.3
*/

require_once __DIR__ . '/helpers/dm_portal_helper.php';

hooks()->add_action('admin_init', 'dm_portal_module_init_menu_items');
hooks()->add_action('admin_init', 'dm_portal_permissions');

// Same extension points modules/dev_portal already uses for its own
// department-only sidebar isolation - not a new mechanism, not CSS/
// client-side hiding.
hooks()->add_filter('sidebar_menu_items', 'dm_portal_hide_native_menus_for_department_staff');
hooks()->add_filter('show_setup_menu', 'dm_portal_hide_setup_menu_for_department_staff');

// Dashboard as the default landing page for Digital Marketing staff -
// admin_init redirect (not after_staff_login), same reasoning as
// dev_portal_redirect_native_dashboard(): after_staff_login callbacks run
// in sequence and staff_attendance's own login-tracking callback on that
// same action must not be short-circuited by an early redirect+exit here.
hooks()->add_action('admin_init', 'dm_portal_redirect_native_dashboard');

// Sidebar section-header styling - see
// dm_portal_sidebar_section_style_script()'s own docblock.
hooks()->add_action('app_admin_footer', 'dm_portal_sidebar_section_style_script');

// Hard, controller-level access gate - see
// dm_portal_restrict_native_controller_access()'s own docblock for why
// the sidebar/Setup-menu hiding above isn't enough on its own.
hooks()->add_action('admin_init', 'dm_portal_restrict_native_controller_access');

// Daily Work Update (page, model, upload handling - including the
// get_upload_path_by_type filter that used to be registered here) now
// lives entirely in modules/daily_work_update, reused by this portal's
// sidebar link above instead of duplicated in this module.

/**
 * "Digital Marketing Portal" top-level sidebar group - visible only to
 * staff belonging to the Digital Marketing department
 * (is_dm_portal_department_staff()). Exactly 5 children: Dashboard, My
 * Work, Daily Work Update, Attendance (deep link), Profile (deep link) -
 * no My Projects/My Tasks, no Reports, no Settings, per the approved
 * architecture.
 */
function dm_portal_module_init_menu_items()
{
    $CI = &get_instance();

    // Menu is department-staff-only, same as dev_portal - Admin remains
    // authorized to reach the controller directly (is_dm_portal_member()
    // still allows that) but must never see this sidebar group.
    if (!is_dm_portal_department_staff()) {
        return;
    }

    // admin_init fires on every admin page (not just this module's own
    // controller), so the language file has to load right here or _l()
    // below resolves to the raw key on any other page.
    $CI->lang->load('dm_portal/dm_portal', 'english');

    $CI->app_menu->add_sidebar_menu_item('dm_portal', [
        'name'     => _l('dm_portal'),
        'href'     => admin_url('dm_portal'),
        'icon'     => 'fa-solid fa-bullhorn',
        'position' => 66,
        'badge'    => [],
    ]);

    $CI->app_menu->add_sidebar_children_item('dm_portal', [
        'slug'     => 'dm_portal-dashboard',
        'name'     => _l('dm_portal_dashboard'),
        'href'     => admin_url('dm_portal'),
        'position' => 0,
        'badge'    => [],
    ]);

    $CI->app_menu->add_sidebar_children_item('dm_portal', [
        'slug'     => 'dm_portal-my_work',
        'name'     => _l('dm_portal_my_work'),
        'href'     => admin_url('dm_portal/my_work'),
        'position' => 1,
        'badge'    => [],
    ]);

    // Daily Work Update - now the shared modules/daily_work_update
    // controller, not a page of this module's own; slug/position kept
    // unchanged so the sidebar item itself (and the employee's
    // experience of finding it) doesn't move.
    $CI->app_menu->add_sidebar_children_item('dm_portal', [
        'slug'     => 'dm_portal-daily_update',
        'name'     => _l('dm_portal_daily_update'),
        'href'     => admin_url('daily_work_update'),
        'position' => 2,
        'badge'    => [],
    ]);

    // Attendance - deep link to the existing Attendance module
    // (modules/staff_attendance), already self-scoping ("My Attendance"
    // for non-admin) - no new controller/view here.
    $CI->app_menu->add_sidebar_children_item('dm_portal', [
        'slug'     => 'dm_portal-attendance',
        'name'     => _l('dm_portal_attendance'),
        'href'     => admin_url('staff_attendance'),
        'position' => 3,
        'badge'    => [],
    ]);

    // Profile - deep link to the native staff profile page.
    $CI->app_menu->add_sidebar_children_item('dm_portal', [
        'slug'     => 'dm_portal-profile',
        'name'     => _l('dm_portal_profile'),
        'href'     => admin_url('profile'),
        'position' => 4,
        'badge'    => [],
    ]);
}

/**
 * Strips every native top-level sidebar item for a Digital Marketing
 * staff member (not Admin), same list dev_portal's own hiding function
 * uses (confirmed by reading application/helpers/menu_helper.php's own
 * add_sidebar_menu_item() calls for their exact slugs), so the Digital
 * Marketing Portal group added above is the ONLY thing they see.
 *
 * @param  array $items
 * @return array
 */
function dm_portal_hide_native_menus_for_department_staff($items)
{
    if (is_admin() || !is_dm_portal_department_staff()) {
        return $items;
    }

    $native_top_level_slugs = [
        'dashboard',
        'customers',
        'sales',
        'subscriptions',
        'expenses',
        'contracts',
        'projects',
        'tasks',
        'support',
        'leads',
        'estimate_request',
        'knowledge-base',
        'utilities',
        'reports',
        'operations',
        'follow_up_management',
    ];

    foreach ($native_top_level_slugs as $slug) {
        unset($items[$slug]);
    }

    return $items;
}

/**
 * "Setup" is rendered directly in application/views/admin/includes/
 * aside.php via $this->app->show_setup_menu(), not through the
 * sidebar_menu_items array the function above filters - same separate
 * extension point dev_portal already uses.
 *
 * @param  bool $show
 * @return bool
 */
function dm_portal_hide_setup_menu_for_department_staff($show)
{
    if (is_admin() || !is_dm_portal_department_staff()) {
        return $show;
    }

    return false;
}

/**
 * Dashboard as the default landing page for Digital Marketing staff.
 * admin_init redirect on the native Dashboard's own page load - see
 * dev_portal_redirect_native_dashboard()'s docblock (mirrored here) for
 * why this isn't hooked to after_staff_login.
 *
 * @return void
 */
function dm_portal_redirect_native_dashboard()
{
    $CI = &get_instance();

    // is_manager_portal_operations_manager() bypass added when Manager
    // Portal was introduced: a staff member who is BOTH a plain DM Portal
    // employee AND the designated Operations Manager (the same person can
    // be both) must land on the Manager Portal, not here - that oversight
    // view is the one that matters once someone holds that role, even if
    // they also still belong to this portal's own staff list.
    if (is_admin() || !is_dm_portal_department_staff() || is_manager_portal_operations_manager()) {
        return;
    }

    if (strtolower($CI->router->class) === 'dashboard') {
        redirect(admin_url('dm_portal'));
    }
}

/**
 * Restyles the "Digital Marketing Portal" top-level sidebar item as a
 * plain, non-clickable section heading with children always visible -
 * identical mechanism to dev_portal_sidebar_section_style_script(), just
 * targeting this module's own menu-item class.
 *
 * @return void
 */
function dm_portal_sidebar_section_style_script()
{
    if (is_admin() || !is_dm_portal_department_staff()) {
        return;
    }
    ?>
    <style>
        .menu-item-dm_portal > a { cursor: default; }
        .menu-item-dm_portal > a:hover,
        .menu-item-dm_portal > a:focus { background: transparent !important; }
        .menu-item-dm_portal > a .fa.arrow { display: none !important; }
        .menu-item-dm_portal > a .menu-text {
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: .05em;
            opacity: .6;
        }
        .menu-item-dm_portal > ul.nav-second-level {
            display: block !important;
            height: auto !important;
        }
    </style>
    <script>
    (function () {
        var $item = $('.menu-item-dm_portal');
        if (!$item.length) {
            return;
        }

        $item.find('> a').on('click', function (e) {
            e.preventDefault();
        });
        $item.find('> ul.nav-second-level').css('display', 'block');
    })();
    </script>
    <?php
}

/**
 * The sidebar/Setup-menu hiding above only stops a Digital Marketing
 * employee from navigating to a native module by clicking a link - it
 * does nothing to stop a direct URL request, since none of these native
 * controllers check department membership themselves (confirmed live:
 * admin/leads renders the full Leads Kanban - New Lead, Export, Bulk
 * Actions - for this department, which has no tblstaff_permissions row
 * for 'leads' at all). Reuses Perfex's own access_denied() (redirect +
 * "Permission denied" alert + activity log) - the same mechanism this
 * module's own controller already calls (see Dm_portal::my_work()) -
 * instead of inventing a new denial page.
 *
 * Deliberately an allow-everything-else denylist, not an allowlist:
 * mirrors dm_portal_hide_native_menus_for_department_staff()'s own
 * $native_top_level_slugs one-for-one (minus 'dashboard' and
 * 'operations', which have no controller of their own / are already
 * handled by dm_portal_redirect_native_dashboard()) plus the Setup-only
 * controllers the Setup-menu hiding above already keeps out of their
 * sidebar. Leaves alone: Dashboard (own redirect above), Misc/Todo/
 * Announcements (shared navbar chrome every admin page depends on -
 * notifications, search, personal todo list), Authentication (logout),
 * and Staff (admin/profile routes through Staff::profile(), which this
 * same audit fixed separately to check staff_cant('view','staff') for
 * any id other than the viewer's own - blocking the whole controller
 * here would have broken their own Profile page instead of just
 * closing the hole).
 *
 * Also deliberately excludes 'tasks': the sibling dev_portal module's
 * own My Tasks page uses main.js's global init_task_modal(), which
 * calls tasks/get_task_data/{id} to render task details - confirmed
 * live that blocking it broke that modal for a task genuinely assigned
 * to the viewing staff member ("Access denied" instead of the task).
 * Any DM Portal surface that opens a task detail via that same global
 * function would hit the identical wall, so this module is fixed
 * proactively rather than waiting to reproduce it here too. Safe to
 * leave open: Tasks::get_task_data() already falls back to
 * get_tasks_where_string() scoping (assigned-to-me or
 * staff_can('view','tasks')) whenever the blanket permission is
 * missing, and 404s for a task outside that scope - the same
 * ownership check this whole gate exists to enforce, just implemented
 * natively per-task instead of per-controller.
 *
 * @return void
 */
function dm_portal_restrict_native_controller_access()
{
    $CI = &get_instance();

    if (is_admin() || !is_dm_portal_department_staff()) {
        return;
    }

    $restricted_controllers = [
        'clients', 'leads', 'proposals', 'estimates', 'invoices', 'credit_notes',
        'payments', 'invoice_items', 'subscriptions', 'expenses', 'contracts',
        'projects', 'tickets', 'estimate_request', 'knowledge_base',
        'utilities', 'reports', 'follow_up_management',
        'roles', 'departments', 'settings', 'custom_fields',
        'templates', 'emails', 'currencies', 'taxes', 'paymentmodes', 'mods',
        'auto_update', 'smtp_oauth_google', 'smtp_oauth_microsoft',
        'spam_filters', 'filters', 'ai', 'ai_tickets', 'gdpr', 'newsfeed',
        'email_schedule_invoice', 'email_schedule_estimate',
    ];

    if (in_array(strtolower($CI->router->class), $restricted_controllers)) {
        // AJAX-aware - see the identical comment in
        // modules/manager_portal/manager_portal.php's own
        // *_restrict_native_controller_access() for the root cause this
        // guards against (a background/footer-script AJAX request
        // hitting a restricted controller class getting the flash+
        // redirect access_denied(), which then surfaces as a stray
        // "Access denied" toast on an unrelated later page).
        if ($CI->input->is_ajax_request()) {
            ajax_access_denied();
        }

        access_denied('Digital Marketing Portal');
    }
}

/**
 * No capability system used deliberately, same reasoning as
 * modules/dev_portal and modules/staff_attendance - access is gated by
 * Business Department membership (is_dm_portal_member()), not a
 * grantable permission. Exists only so Setup -> Staff Permissions has
 * something to show, matching every other module's registration
 * pattern; nothing currently reads it.
 */
function dm_portal_permissions()
{
    $capabilities = [];

    $capabilities['capabilities'] = [
        'view' => _l('permission_view') . '(' . _l('permission_global') . ')',
    ];

    register_staff_capabilities('dm_portal', $capabilities, _l('dm_portal'));
}
