<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Development Portal
Description: Unified portal for Web Development, App Development, UI/UX Design and Testing - gated by Business Department membership, not a separate permission-capability system. Deliberately reuses the existing Projects, Tasks, Attendance, Calendar and Support Tickets modules instead of re-implementing them - this module's own pages are Dashboard, My Projects, My Tasks (Phase 1), Daily Work Log (Phase 2) and Reports (Phase 3); Attendance/Calendar/Support/Profile are plain deep links to already-built pages.
Version: 1.0.0
Requires at least: 3.6.3
*/

require_once __DIR__ . '/helpers/dev_portal_helper.php';

hooks()->add_action('admin_init', 'dev_portal_module_init_menu_items');
hooks()->add_action('admin_init', 'dev_portal_permissions');

// Dev Portal staff get a dedicated portal, not the native CRM plus an
// extra menu group - every other top-level sidebar item is stripped for
// them (Admin is explicitly excluded from this, per both callbacks'
// own guards, so Admin's navigation is completely untouched). Same
// hooks()->add_filter('sidebar_menu_items', ...) extension point
// follow_up_management already uses for its own Telecaller-only hiding -
// not a new mechanism, and not CSS/client-side hiding.
hooks()->add_filter('sidebar_menu_items', 'dev_portal_hide_native_menus_for_department_staff');
hooks()->add_filter('show_setup_menu', 'dev_portal_hide_setup_menu_for_department_staff');

// Dashboard as the default landing page - see
// dev_portal_redirect_native_dashboard()'s own docblock for why this is
// an admin_init redirect rather than an after_staff_login one.
hooks()->add_action('admin_init', 'dev_portal_redirect_native_dashboard');

// Sidebar section-header styling - see
// dev_portal_sidebar_section_style_script()'s own docblock.
hooks()->add_action('app_admin_footer', 'dev_portal_sidebar_section_style_script');

// Hard, controller-level access gate - see
// dev_portal_restrict_native_controller_access()'s own docblock for why
// the sidebar/Setup-menu hiding above isn't enough on its own.
hooks()->add_action('admin_init', 'dev_portal_restrict_native_controller_access');

/**
 * "Development Portal" top-level sidebar group - visible to Admin or any
 * staff member belonging to one of the 4 dev departments
 * (is_dev_portal_member(), dev_portal_helper.php). Phase 1 only wires
 * Dashboard/My Projects/My Tasks plus deep links to already-existing
 * pages (Attendance/Calendar/Support/Profile) that need no new code;
 * Daily Work Log (Phase 2) and Reports (Phase 3) are added to this same
 * function when those pages exist, keeping position numbers stable so
 * they slot into the right place without renumbering.
 */
function dev_portal_module_init_menu_items()
{
    $CI = &get_instance();

    // is_dev_portal_department_staff(), NOT is_dev_portal_member() - the
    // menu is department-staff-only. Admin remains authorized to reach
    // the controller directly (is_dev_portal_member() still allows that,
    // same superuser bypass every other module uses) but must never see
    // this sidebar group - Admin's navigation stays exactly the native
    // Perfex menus, no duplicate "Development Portal" entry of its own.
    if (!is_dev_portal_department_staff()) {
        return;
    }

    // admin_init fires on every admin page, not just this module's own
    // controller (whose __construct() only loads this same file for
    // itself) - the sidebar/menu template renders on every page, so the
    // language file has to be loaded right here or _l() below resolves
    // to the raw key on any page other than Dev_portal's own.
    $CI->lang->load('dev_portal/dev_portal', 'english');

    $CI->app_menu->add_sidebar_menu_item('dev_portal', [
        'name'     => _l('dev_portal'),
        'href'     => admin_url('dev_portal'),
        'icon'     => 'fa-solid fa-laptop-code',
        'position' => 65,
        'badge'    => [],
    ]);

    $CI->app_menu->add_sidebar_children_item('dev_portal', [
        'slug'     => 'dev_portal-dashboard',
        'name'     => _l('dev_portal_dashboard'),
        'href'     => admin_url('dev_portal'),
        'position' => 0,
        'badge'    => [],
    ]);

    $CI->app_menu->add_sidebar_children_item('dev_portal', [
        'slug'     => 'dev_portal-my_projects',
        'name'     => _l('dev_portal_my_projects'),
        'href'     => admin_url('dev_portal/my_projects'),
        'position' => 1,
        'badge'    => [],
    ]);

    $CI->app_menu->add_sidebar_children_item('dev_portal', [
        'slug'     => 'dev_portal-my_tasks',
        'name'     => _l('dev_portal_my_tasks'),
        'href'     => admin_url('dev_portal/my_tasks'),
        'position' => 2,
        'badge'    => [],
    ]);

    // Daily Work Update - deep link to the shared modules/daily_work_update
    // controller (reused as-is, no page/model/controller of this module's
    // own - same pattern modules/dm_portal's own sidebar link now uses).
    $CI->app_menu->add_sidebar_children_item('dev_portal', [
        'slug'     => 'dev_portal-daily_work_update',
        'name'     => _l('dev_portal_daily_work_update'),
        'href'     => admin_url('daily_work_update'),
        'position' => 3,
        'badge'    => [],
    ]);

    // Attendance - deep link to the existing Attendance module
    // (modules/staff_attendance), already self-scoping ("My Attendance"
    // for non-admin) - no new controller/view here.
    $CI->app_menu->add_sidebar_children_item('dev_portal', [
        'slug'     => 'dev_portal-attendance',
        'name'     => _l('dev_portal_attendance'),
        'href'     => admin_url('staff_attendance'),
        'position' => 4,
        'badge'    => [],
    ]);

    // Calendar - deep link to the native Calendar (project deadlines and
    // task due dates already surface there for whoever has access). The
    // real route is admin/utilities/calendar (Utilities::calendar()) -
    // confirmed by reading application/controllers/admin/Utilities.php
    // directly - core's own sidebar link uses the same path, not a bare
    // "admin/calendar" (which does not exist and 404s).
    $CI->app_menu->add_sidebar_children_item('dev_portal', [
        'slug'     => 'dev_portal-calendar',
        'name'     => _l('dev_portal_calendar'),
        'href'     => admin_url('utilities/calendar'),
        'position' => 5,
        'badge'    => [],
    ]);

    // Support - deep link to the native Tickets list, already scoped by
    // each staff member's own ticket-department permissions.
    $CI->app_menu->add_sidebar_children_item('dev_portal', [
        'slug'     => 'dev_portal-support',
        'name'     => _l('dev_portal_support'),
        'href'     => admin_url('tickets'),
        'position' => 6,
        'badge'    => [],
    ]);

    // Profile - deep link to the native staff profile page.
    $CI->app_menu->add_sidebar_children_item('dev_portal', [
        'slug'     => 'dev_portal-profile',
        'name'     => _l('dev_portal_profile'),
        'href'     => admin_url('profile'),
        'position' => 7,
        'badge'    => [],
    ]);
}

/**
 * Strips every native top-level sidebar item for a Dev Portal staff
 * member (Web Development/App Development/UI/UX Design/Testing, not
 * Admin) so the Development Portal group added above is the ONLY thing
 * they see - Dashboard/Customers/Sales/Subscriptions/Expenses/
 * Contracts/Projects/Tasks/Support/Leads/Estimate Requests/Knowledge
 * Base/Utilities/Reports (all confirmed by reading
 * application/helpers/menu_helper.php's own add_sidebar_menu_item()
 * calls for their exact slugs), plus the two module-registered
 * top-level groups this fork itself added: 'operations' (Attendance -
 * modules/staff_attendance, reached instead via this portal's own
 * Attendance link) and 'follow_up_management' (the Telecalling module's
 * own group - dev staff have no business there, and this module's own
 * hiding logic, after its recent fix, correctly leaves this group alone
 * for non-Telecalling staff, so it must be removed here instead).
 *
 * @param  array $items
 * @return array
 */
function dev_portal_hide_native_menus_for_department_staff($items)
{
    if (is_admin() || !is_dev_portal_department_staff()) {
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
 * aside.php via $this->app->show_setup_menu() (a real filter,
 * application/libraries/App.php:414-417 - hooks()->apply_filters
 * ('show_setup_menu', ...)), not through the sidebar_menu_items array
 * the function above filters - it needs this separate, equally
 * sanctioned extension point rather than being left uncovered.
 *
 * @param  bool $show
 * @return bool
 */
function dev_portal_hide_setup_menu_for_department_staff($show)
{
    if (is_admin() || !is_dev_portal_department_staff()) {
        return $show;
    }

    return false;
}

/**
 * Dashboard as the default landing page for Dev Portal staff. This is
 * deliberately an admin_init redirect on the NATIVE DASHBOARD's OWN page
 * load, not a hook into after_staff_login (application/controllers/
 * admin/Authentication.php calls hooks()->do_action('after_staff_login')
 * then unconditionally redirect(admin_url()) right after - a callback on
 * that action that itself calls redirect()+exit would risk terminating
 * the request before OTHER after_staff_login callbacks registered later
 * in the same do_action() run - specifically
 * modules/staff_attendance's own login-tracking callback, which this
 * portal's own Dashboard depends on for its Attendance Summary card).
 * admin_init fires on every admin page (including this one, safely,
 * before any output) regardless of how the user arrived there, so this
 * catches the post-login landing AND anyone manually typing the native
 * dashboard URL later - consistent with the "block direct URL access"
 * requirement applied everywhere else in this module.
 *
 * @return void
 */
function dev_portal_redirect_native_dashboard()
{
    $CI = &get_instance();

    // is_manager_portal_operations_manager() bypass added when Manager
    // Portal was introduced - see dm_portal_redirect_native_dashboard()'s
    // own comment (mirrored here): a staff member who is BOTH a plain Dev
    // Portal employee AND the designated Operations Manager must land on
    // the Manager Portal, not here.
    if (is_admin() || !is_dev_portal_department_staff() || is_manager_portal_operations_manager()) {
        return;
    }

    // $route['admin'] = 'admin/dashboard' (application/config/routes.php) -
    // both admin_url() and admin_url('dashboard') resolve to this same
    // controller, so checking the router's resolved class is more
    // reliable than string-matching the requested URI.
    if (strtolower($CI->router->class) === 'dashboard') {
        redirect(admin_url('dev_portal'));
    }
}

/**
 * Restyles the "Development Portal" top-level sidebar item as a plain,
 * non-clickable section heading with its children always visible
 * underneath - never a collapsible dropdown, never something that
 * navigates anywhere on click. The native sidebar template
 * (application/views/admin/includes/aside.php) always renders a
 * top-level item with children as href="#" plus a metisMenu-driven
 * collapse/expand toggle - there's no per-item flag in that template
 * for "always expanded, no toggle", so this is a small, scoped
 * presentation override rather than a core-file edit. It is purely
 * cosmetic (this section is already fully visible/authorized for this
 * audience - dev_portal_hide_native_menus_for_department_staff() above
 * is what actually controls access) - unrelated to the earlier
 * "no CSS/client-side hiding" requirement, which was about who can see
 * what, not how an already-visible, already-authorized menu renders.
 * Active-item highlighting needs no extra code here - assets/js/main.js
 * already matches `li > a[href="<current url>"]` and adds `.active`
 * globally for every sidebar link, this module's own included.
 *
 * @return void
 */
function dev_portal_sidebar_section_style_script()
{
    if (is_admin() || !is_dev_portal_department_staff()) {
        return;
    }
    ?>
    <style>
        .menu-item-dev_portal > a { cursor: default; }
        .menu-item-dev_portal > a:hover,
        .menu-item-dev_portal > a:focus { background: transparent !important; }
        .menu-item-dev_portal > a .fa.arrow { display: none !important; }
        .menu-item-dev_portal > a .menu-text {
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: .05em;
            opacity: .6;
        }
        .menu-item-dev_portal > ul.nav-second-level {
            display: block !important;
            height: auto !important;
        }
    </style>
    <script>
    (function () {
        var $item = $('.menu-item-dev_portal');
        if (!$item.length) {
            return;
        }

        // Belt-and-braces alongside the CSS above - metisMenu (initialized
        // for the whole sidebar in main.js) binds its own click handler to
        // every top-level link with children; this doesn't (and can't
        // reliably) unbind that, it just keeps the browser from following
        // the href="#" default and keeps the submenu's inline style from
        // ever landing on "display: none" if metisMenu toggles it closed.
        $item.find('> a').on('click', function (e) {
            e.preventDefault();
        });
        $item.find('> ul.nav-second-level').css('display', 'block');
    })();
    </script>
    <?php
}

/**
 * The sidebar/Setup-menu hiding above only stops a Dev Portal staff
 * member from navigating to a native module by clicking a link - it
 * does nothing to stop a direct URL request, since none of these native
 * controllers check department membership themselves (confirmed live:
 * admin/leads and admin/projects both render in full - the org-wide
 * Projects list, not just this staff member's own - for a department
 * with no tblstaff_permissions rows at all). Reuses Perfex's own
 * access_denied() (redirect + "Permission denied" alert + activity log)
 * instead of inventing a new denial page - same mechanism
 * dm_portal_restrict_native_controller_access() already uses for the
 * identical gap in that sibling module.
 *
 * Deliberately an allow-everything-else denylist, not an allowlist:
 * mirrors dev_portal_hide_native_menus_for_department_staff()'s own
 * $native_top_level_slugs, minus 'dashboard' (own redirect above),
 * 'operations' (no controller of its own), and - unlike dm_portal's
 * denylist - minus 'utilities' and 'tickets', because THIS module's own
 * sidebar deep-links Calendar to utilities/calendar and Support to
 * tickets, so blocking those controllers would break this module's own
 * pages instead of just closing the hole. Also leaves alone: Misc/Todo/
 * Announcements (shared navbar chrome), Authentication (logout), and
 * Staff (admin/profile routes through Staff::profile(), separately
 * fixed to check staff_cant('view','staff') for any id other than the
 * viewer's own).
 *
 * Also deliberately excludes 'tasks': My Tasks' own init_task_modal()
 * (assets/js/main.js) reuses the native tasks/get_task_data/{id} AJAX
 * endpoint to render task details - confirmed live blocking it broke
 * that modal ("Access denied" for a task genuinely assigned to this
 * staff member). Safe to leave open: Tasks::get_task_data() already
 * falls back to get_tasks_where_string() scoping (assigned-to-me or
 * staff_can('view','tasks')) whenever the blanket permission is
 * missing, and 404s for a task outside that scope - the same
 * ownership check this whole gate exists to enforce, just implemented
 * natively per-task instead of per-controller.
 *
 * @return void
 */
function dev_portal_restrict_native_controller_access()
{
    $CI = &get_instance();

    if (is_admin() || !is_dev_portal_department_staff()) {
        return;
    }

    $restricted_controllers = [
        'clients', 'leads', 'proposals', 'estimates', 'invoices', 'credit_notes',
        'payments', 'invoice_items', 'subscriptions', 'expenses', 'contracts',
        'projects', 'estimate_request', 'knowledge_base', 'reports',
        'follow_up_management',
        'roles', 'departments', 'settings', 'custom_fields',
        'templates', 'emails', 'currencies', 'taxes', 'paymentmodes', 'mods',
        'auto_update', 'smtp_oauth_google', 'smtp_oauth_microsoft',
        'spam_filters', 'filters', 'ai', 'ai_tickets', 'gdpr', 'newsfeed',
        'email_schedule_invoice', 'email_schedule_estimate',
    ];

    if (in_array(strtolower($CI->router->class), $restricted_controllers)) {
        access_denied('Development Portal');
    }
}

/**
 * No capability system used deliberately, same reasoning as
 * modules/staff_attendance - access is gated by Business Department
 * membership (is_dev_portal_member()), not a grantable permission. This
 * capability exists only so Setup -> Staff Permissions has something to
 * show, matching every other module's registration pattern; nothing
 * currently reads it.
 */
function dev_portal_permissions()
{
    $capabilities = [];

    $capabilities['capabilities'] = [
        'view' => _l('permission_view') . '(' . _l('permission_global') . ')',
    ];

    register_staff_capabilities('dev_portal', $capabilities, _l('dev_portal'));
}
