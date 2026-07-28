<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Manager Portal
Description: Company-wide operational oversight portal for the Operations Manager - the bridge between the CEO (Admin) and every employee across every department. Identified through the native Perfex Staff Permissions `view` capability under the `manager_portal` feature (register_staff_capabilities()), granted the normal way at Setup -> Staff -> Edit Staff -> Permissions - no department scoping, no per-department manager assignment. Deliberately reuses Projects, Tasks, Staff Attendance, Daily Work Update, and the shared staff_dashboard_helper components instead of re-implementing them - this module's own pages are Dashboard and Team (Phase 1); Projects/Tasks, Daily Work Update review, Attendance/Performance/Reports, and Notifications follow in later phases per the approved build order.
Version: 1.0.0
Requires at least: 3.6.3
*/

require_once __DIR__ . '/helpers/manager_portal_helper.php';

hooks()->add_action('admin_init', 'manager_portal_module_init_menu_items');
hooks()->add_action('admin_init', 'manager_portal_permissions');

// Same sidebar_menu_items/show_setup_menu filter pair dev_portal/dm_portal/
// follow_up_management already use for their own department-only hiding -
// not a new mechanism.
hooks()->add_filter('sidebar_menu_items', 'manager_portal_hide_native_menus_for_managers');
hooks()->add_filter('show_setup_menu', 'manager_portal_hide_setup_menu_for_managers');

// Dashboard as the default landing page - same admin_init (not
// after_staff_login) reasoning as dev_portal_redirect_native_dashboard()'s
// own docblock (avoids short-circuiting Staff Attendance's login-tracking
// callback on that same action).
hooks()->add_action('admin_init', 'manager_portal_redirect_native_dashboard');

// Hard, controller-level access gate - sidebar/Setup-menu hiding alone
// doesn't stop a direct URL hit, same reasoning as
// dev_portal_restrict_native_controller_access()'s own docblock.
hooks()->add_action('admin_init', 'manager_portal_restrict_native_controller_access');

/**
 * "Manager Portal" top-level sidebar group - visible only to the
 * designated Operations Manager (is_manager_portal_operations_manager(),
 * no admin bypass - Admin keeps its own native dashboard/menu, same
 * convention as every other portal module). Phase 1 wires Dashboard,
 * Team, and a Profile deep link (needs no new code); Projects/Tasks/Daily
 * Work Updates/Attendance/Performance/Reports/Notifications are added to
 * this same function as each phase's page goes live, keeping position
 * numbers stable so they slot into place without renumbering later ones.
 */
function manager_portal_module_init_menu_items()
{
    $CI = &get_instance();

    if (!is_manager_portal_operations_manager()) {
        return;
    }

    // admin_init fires on every admin page, not just this module's own
    // controller - the language file has to be loaded right here or _l()
    // below resolves to the raw key on any page other than this one's.
    $CI->lang->load('manager_portal/manager_portal', 'english');

    $CI->app_menu->add_sidebar_menu_item('manager_portal', [
        'name'     => _l('manager_portal'),
        'href'     => admin_url('manager_portal'),
        'icon'     => 'fa-solid fa-user-tie',
        'position' => 63,
        'badge'    => [],
    ]);

    $CI->app_menu->add_sidebar_children_item('manager_portal', [
        'slug'     => 'manager_portal-dashboard',
        'name'     => _l('manager_portal_dashboard'),
        'href'     => admin_url('manager_portal'),
        'position' => 0,
        'badge'    => [],
    ]);

    $CI->app_menu->add_sidebar_children_item('manager_portal', [
        'slug'     => 'manager_portal-team',
        'name'     => _l('manager_portal_team'),
        'href'     => admin_url('manager_portal/team'),
        'position' => 1,
        'badge'    => [],
    ]);

    $CI->app_menu->add_sidebar_children_item('manager_portal', [
        'slug'     => 'manager_portal-projects',
        'name'     => _l('manager_portal_projects'),
        'href'     => admin_url('manager_portal/projects'),
        'position' => 2,
        'badge'    => [],
    ]);

    $CI->app_menu->add_sidebar_children_item('manager_portal', [
        'slug'     => 'manager_portal-tasks',
        'name'     => _l('manager_portal_tasks'),
        'href'     => admin_url('manager_portal/tasks'),
        'position' => 3,
        'badge'    => [],
    ]);

    $CI->app_menu->add_sidebar_children_item('manager_portal', [
        'slug'     => 'manager_portal-daily-work-updates',
        'name'     => _l('manager_portal_daily_work_updates'),
        'href'     => admin_url('manager_portal/daily_work_updates'),
        'position' => 4,
        'badge'    => [],
    ]);

    $CI->app_menu->add_sidebar_children_item('manager_portal', [
        'slug'     => 'manager_portal-attendance',
        'name'     => _l('manager_portal_attendance'),
        'href'     => admin_url('manager_portal/attendance'),
        'position' => 5,
        'badge'    => [],
    ]);

    $CI->app_menu->add_sidebar_children_item('manager_portal', [
        'slug'     => 'manager_portal-reports',
        'name'     => _l('manager_portal_reports_menu'),
        'href'     => admin_url('manager_portal/reports'),
        'position' => 6,
        'badge'    => [],
    ]);

    // Profile - deep link to the native staff profile page, the manager's
    // own (no wrapper needed - same one-line pattern every sibling portal
    // already uses).
    $CI->app_menu->add_sidebar_children_item('manager_portal', [
        'slug'     => 'manager_portal-profile',
        'name'     => _l('manager_portal_profile'),
        'href'     => admin_url('profile'),
        'position' => 9,
        'badge'    => [],
    ]);
}

/**
 * Strips every native top-level sidebar item for the Operations Manager
 * so the Manager Portal group added above is the only thing they see -
 * same denylist shape as dev_portal_hide_native_menus_for_department_staff(),
 * guarded so it only ever fires for the real Operations Manager (never
 * Admin, never a plain employee who happens to hit this filter).
 *
 * @param  array $items
 * @return array
 */
function manager_portal_hide_native_menus_for_managers($items)
{
    if (is_admin() || !is_manager_portal_operations_manager()) {
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
        // A staff member can be BOTH a plain department-portal employee
        // AND hold the manager_portal capability - Manager Portal is the
        // one menu they should see once they hold that role, not a
        // duplicate "Digital Marketing Portal" group alongside it. The
        // matching dashboard-redirect bypass lives in each sibling
        // module's own *_redirect_native_dashboard() (see dm_portal.php's
        // comment).
        'dev_portal',
        'dm_portal',
        'field_portal',
        // Same reasoning, for an Operations Manager whose own account
        // happens to sit in a department with no dedicated execution
        // portal (e.g. SEO/App Development/UI-UX Design/Testing) and
        // would otherwise see daily_work_update's own
        // portal-less-department top-level item alongside Manager Portal.
        'daily_work_update',
        // Company configuration - explicitly out of scope for the
        // Operations Manager per the approved architecture ("cannot
        // access Business Departments Management").
        'business_departments',
    ];

    foreach ($native_top_level_slugs as $slug) {
        unset($items[$slug]);
    }

    return $items;
}

/**
 * "Setup" is rendered via a separate filter (show_setup_menu, not
 * sidebar_menu_items) - see dev_portal_hide_setup_menu_for_department_staff()'s
 * own docblock for why this needs its own callback.
 *
 * @param  bool $show
 * @return bool
 */
function manager_portal_hide_setup_menu_for_managers($show)
{
    if (is_admin() || !is_manager_portal_operations_manager()) {
        return $show;
    }

    return false;
}

/**
 * Dashboard as the default landing page for the Operations Manager - see
 * dev_portal_redirect_native_dashboard()'s own docblock for the full
 * reasoning (admin_init redirect, not after_staff_login).
 *
 * @return void
 */
function manager_portal_redirect_native_dashboard()
{
    $CI = &get_instance();

    if (is_admin() || !is_manager_portal_operations_manager()) {
        return;
    }

    if (strtolower($CI->router->class) === 'dashboard') {
        redirect(admin_url('manager_portal'));
    }
}

/**
 * The sidebar/Setup-menu hiding above only stops link clicks, not a direct
 * URL request - see dev_portal_restrict_native_controller_access()'s own
 * docblock for the full reasoning. Deliberately excludes 'tickets' and
 * 'utilities' (native Support/Calendar, reachable the same way every
 * sibling portal leaves them), and 'profile' (this module's own Profile
 * link). Unlike dev_portal, 'tasks' IS blocked here (Phase 3) - this
 * module's own Task Detail page (manager_portal/task/{id}) calls
 * Tasks_model directly rather than reusing any native tasks/* endpoint,
 * so there was never a carve-out reason to leave it open, and leaving it
 * open let the Operations Manager reach the native Tasks page's
 * Create/Edit/Delete/Bulk Actions - a real permission leak caught during
 * Phase 3 live verification and closed here, matching 'projects' below.
 *
 * 'daily_work_update' and 'staff_attendance' are ALSO deliberately left
 * open, but not for the reason an earlier draft of this comment claimed
 * ("this module deep-links into them") - Phase 4/5 built their own
 * self-contained pages instead, same as Projects/Tasks. They stay open
 * because, unlike native admin/tasks, BOTH native controllers already
 * gate their own company-wide/mutate actions behind is_admin() internally
 * (Daily_work_update::report()/report_table()/save()/upload(); Staff_attendance's
 * month_dashboard()/update_status()/update_remarks()/add_manual()) -
 * confirmed by reading both controllers line by line before Phase 5.
 * A non-admin hitting either directly only ever reaches their OWN
 * personal read-only attendance/work-update page, the same self-service
 * every other staff member already has - not a leak. This is
 * structurally different from 'tasks', which had zero internal role
 * check on its own Create/Edit/Delete/Bulk Actions.
 *
 * Explicitly blocks every company-level administration surface the
 * approved architecture calls out by name: Settings, Roles, Staff
 * Permissions, Payment Gateways, Finance Configuration, Business
 * Departments Management, System Configuration, Email Settings, Modules
 * Management, and Migration/System Maintenance - the Operations Manager
 * never holds any capability other than `manager_portal`, so these would
 * already be blocked by each native controller's own staff_can() gate in
 * practice, but this denylist is the same belt-and-braces direct-URL
 * backstop every sibling portal already relies on rather than trusting
 * that alone.
 *
 * @return void
 */
function manager_portal_restrict_native_controller_access()
{
    $CI = &get_instance();

    if (is_admin() || !is_manager_portal_operations_manager()) {
        return;
    }

    // 'staff' deliberately excluded, same reasoning as dev_portal's own
    // docblock: admin/profile routes THROUGH Staff::profile() (see
    // application/config/routes.php:72 - 'admin/profile' => 'admin/staff/profile'),
    // which already allows viewing one's own profile unconditionally and
    // gates every other staff id behind staff_cant('view','staff') -
    // blocking the 'staff' controller here would break this module's own
    // Profile link, not just close a hole. Staff Permissions management
    // itself lives under 'roles' (already blocked below), not 'staff'.
    $restricted_controllers = [
        'clients', 'leads', 'proposals', 'estimates', 'invoices', 'credit_notes',
        'payments', 'invoice_items', 'subscriptions', 'expenses', 'contracts',
        'projects', 'tasks', 'estimate_request', 'knowledge_base', 'reports',
        'follow_up_management',
        'roles', 'departments', 'business_departments', 'settings', 'custom_fields',
        'templates', 'emails', 'currencies', 'taxes', 'paymentmodes', 'mods',
        'auto_update', 'smtp_oauth_google', 'smtp_oauth_microsoft',
        'spam_filters', 'filters', 'ai', 'ai_tickets', 'gdpr', 'newsfeed',
        'email_schedule_invoice', 'email_schedule_estimate',
    ];

    if (in_array(strtolower($CI->router->class), $restricted_controllers)) {
        access_denied('Manager Portal');
    }
}

/**
 * Registers the `view` capability under the `manager_portal` feature via
 * Perfex's native register_staff_capabilities() - this is the ENTIRE
 * designation mechanism for the Operations Manager
 * (is_manager_portal_operations_manager() reads it via staff_can()).
 * Grant it to exactly one staff member at Setup -> Staff -> Edit Staff ->
 * Permissions to make them the Operations Manager; revoke it to remove
 * the role. No department mapping, no separate admin screen - this
 * reuses the same capability grant UI every other Perfex module already
 * has.
 */
function manager_portal_permissions()
{
    $capabilities = [];

    $capabilities['capabilities'] = [
        'view' => _l('permission_view') . '(' . _l('permission_global') . ')',
    ];

    register_staff_capabilities('manager_portal', $capabilities, _l('manager_portal'));
}
