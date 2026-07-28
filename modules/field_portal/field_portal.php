<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Field Executive Portal
Description: Work portal for the "Field Executing" business department - lead-to-customer field sales workflow (create Lead, record Meetings, hand off to a Telecaller, or convert directly to Customer), gated by Business Department membership like modules/dm_portal and modules/dev_portal. Deliberately reuses the existing Leads module (creation, notes, attachments, activity log, Lead->Customer conversion) instead of re-implementing any of it - Field_portal's own controller actions are thin, ownership-gated wrappers around Leads_model/Misc_model methods, not a parallel Lead system. Step 2 of a phased build: Dashboard, My Leads, Create Lead, and Lead Details (the central workspace for notes/attachments/activity/conversion) are wired; Meetings and Follow-ups are honest placeholder pages until Steps 3-4 add Meeting recording and Telecaller assignment.
Version: 1.0.0
Requires at least: 3.6.3
*/

require_once __DIR__ . '/helpers/field_portal_helper.php';

hooks()->add_action('admin_init', 'field_portal_module_init_menu_items');
hooks()->add_action('admin_init', 'field_portal_permissions');

// Same extension points modules/dm_portal and modules/dev_portal already
// use for their own department-only sidebar isolation - not a new
// mechanism, not CSS/client-side hiding.
hooks()->add_filter('sidebar_menu_items', 'field_portal_hide_native_menus_for_department_staff');
hooks()->add_filter('show_setup_menu', 'field_portal_hide_setup_menu_for_department_staff');

// Dashboard as the default landing page for Field Executive staff -
// admin_init redirect (not after_staff_login), same reasoning as
// dm_portal_redirect_native_dashboard()/dev_portal_redirect_native_dashboard():
// after_staff_login callbacks run in sequence and staff_attendance's own
// login-tracking callback on that same action must not be short-circuited
// by an early redirect+exit here.
hooks()->add_action('admin_init', 'field_portal_redirect_native_dashboard');

// Sidebar section-header styling - see
// field_portal_sidebar_section_style_script()'s own docblock.
hooks()->add_action('app_admin_footer', 'field_portal_sidebar_section_style_script');

// Step 4 - Conversion By reporting field (tblleads.converted_by,
// migration 370). Fires on every Lead->Customer conversion regardless of
// which portal (or the native Admin form) triggered it - the one place
// Leads_model::convert_to_customer() itself already fires a hook after
// completing, so this captures "who actually performed the conversion"
// without adding a second conversion path.
hooks()->add_action('lead_converted_to_customer', 'field_portal_on_lead_converted_to_customer');

/**
 * "Field Executive Portal" top-level sidebar group - visible only to
 * staff belonging to the "Field Executing" business department
 * (is_field_portal_department_staff()). Step 1 only wires Dashboard;
 * My Leads/Create Lead/Today's Meetings land in Steps 2-3, Customers in
 * Step 5 - position numbers below are pre-reserved so those additions
 * slot into the right place without renumbering the rest.
 */
function field_portal_module_init_menu_items()
{
    $CI = &get_instance();

    // Menu is department-staff-only, same as dm_portal/dev_portal - Admin
    // remains authorized to reach the controller directly
    // (is_field_portal_member() still allows that) but must never see
    // this sidebar group.
    if (!is_field_portal_department_staff()) {
        return;
    }

    // admin_init fires on every admin page (not just this module's own
    // controller), so the language file has to load right here or _l()
    // below resolves to the raw key on any other page.
    $CI->lang->load('field_portal/field_portal', 'english');

    $CI->app_menu->add_sidebar_menu_item('field_portal', [
        'name'     => _l('field_portal'),
        'href'     => admin_url('field_portal'),
        'icon'     => 'fa-solid fa-person-walking-luggage',
        'position' => 67,
        'badge'    => [],
    ]);

    $CI->app_menu->add_sidebar_children_item('field_portal', [
        'slug'     => 'field_portal-dashboard',
        'name'     => _l('field_portal_dashboard'),
        'href'     => admin_url('field_portal'),
        'position' => 0,
        'badge'    => [],
    ]);

    // My Leads / Create Lead - the requested structure groups these under
    // a "Leads" parent, but application/views/admin/includes/aside.php
    // (confirmed by direct read) only ever renders TWO menu levels - a
    // top-level item plus one flat <ul class="nav-second-level"> of
    // children, with no third level for a child to have children of its
    // own. "Field Executive Portal" already occupies the top-level slot,
    // so "My Leads"/"Create Lead" are flattened to direct, adjacent
    // children here rather than nested under their own "Leads" header -
    // both destinations are still one click away, just not visually
    // grouped under a shared label.
    $CI->app_menu->add_sidebar_children_item('field_portal', [
        'slug'     => 'field_portal-my_leads',
        'name'     => _l('field_portal_my_leads'),
        'href'     => admin_url('field_portal/my_leads'),
        'position' => 1,
        'badge'    => [],
    ]);

    $CI->app_menu->add_sidebar_children_item('field_portal', [
        'slug'     => 'field_portal-create_lead',
        'name'     => _l('field_portal_create_lead'),
        'href'     => admin_url('field_portal/create_lead'),
        'position' => 2,
        'badge'    => [],
    ]);

    // Meetings - portal-wide overview page (distinct from a single Lead's
    // own Meetings section inside Lead Details).
    $CI->app_menu->add_sidebar_children_item('field_portal', [
        'slug'     => 'field_portal-meetings',
        'name'     => _l('field_portal_meetings'),
        'href'     => admin_url('field_portal/meetings'),
        'position' => 3,
        'badge'    => [],
    ]);

    // Customers - real page, reusing the exact same converted-leads query
    // the Dashboard's own "Recent Customer Conversions" panel already
    // uses (Field_portal_model::get_my_customers()), just without the
    // 5-row limit.
    $CI->app_menu->add_sidebar_children_item('field_portal', [
        'slug'     => 'field_portal-customers',
        'name'     => _l('field_portal_customers'),
        'href'     => admin_url('field_portal/customers'),
        'position' => 4,
        'badge'    => [],
    ]);

    // Daily Work Update - deep link to the shared modules/daily_work_update
    // controller, same pattern dm_portal/dev_portal's own sidebar already
    // uses - no page/model/controller of this module's own.
    $CI->app_menu->add_sidebar_children_item('field_portal', [
        'slug'     => 'field_portal-daily_work_update',
        'name'     => _l('field_portal_daily_work_update'),
        'href'     => admin_url('daily_work_update'),
        'position' => 5,
        'badge'    => [],
    ]);

    // Daily Expenses - real page inside this module's own controller,
    // follows the exact same page-header / DataTable / modal pattern as
    // My Leads, Meetings, and Customers.
    $CI->app_menu->add_sidebar_children_item('field_portal', [
        'slug'     => 'field_portal-expenses',
        'name'     => _l('field_portal_daily_expenses'),
        'href'     => admin_url('field_portal/expenses'),
        'position' => 6,
        'badge'    => [],
    ]);

    // Attendance - deep link to the existing Attendance module
    // (modules/staff_attendance), already self-scoping ("My Attendance"
    // for non-admin) - no new controller/view here.
    $CI->app_menu->add_sidebar_children_item('field_portal', [
        'slug'     => 'field_portal-attendance',
        'name'     => _l('field_portal_attendance'),
        'href'     => admin_url('staff_attendance'),
        'position' => 7,
        'badge'    => [],
    ]);

    // Profile - deep link to the native staff profile page.
    $CI->app_menu->add_sidebar_children_item('field_portal', [
        'slug'     => 'field_portal-profile',
        'name'     => _l('field_portal_profile'),
        'href'     => admin_url('profile'),
        'position' => 8,
        'badge'    => [],
    ]);
}

/**
 * Strips every native top-level sidebar item for a Field Executing staff
 * member (not Admin), same list dm_portal/dev_portal's own hiding
 * functions use, so the Field Executive Portal group added above is the
 * ONLY thing they see.
 *
 * @param  array $items
 * @return array
 */
function field_portal_hide_native_menus_for_department_staff($items)
{
    if (is_admin() || !is_field_portal_department_staff()) {
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
 * extension point dm_portal/dev_portal already use.
 *
 * @param  bool $show
 * @return bool
 */
function field_portal_hide_setup_menu_for_department_staff($show)
{
    if (is_admin() || !is_field_portal_department_staff()) {
        return $show;
    }

    return false;
}

/**
 * Dashboard as the default landing page for Field Executive staff.
 * admin_init redirect on the native Dashboard's own page load - see
 * dm_portal_redirect_native_dashboard()'s docblock (mirrored here) for
 * why this isn't hooked to after_staff_login.
 *
 * @return void
 */
function field_portal_redirect_native_dashboard()
{
    $CI = &get_instance();

    // is_manager_portal_operations_manager() bypass added when Manager
    // Portal was introduced - see dm_portal_redirect_native_dashboard()'s
    // own comment (mirrored here): a staff member who is BOTH a plain
    // Field Portal employee AND the designated Operations Manager must
    // land on the Manager Portal, not here.
    if (is_admin() || !is_field_portal_department_staff() || is_manager_portal_operations_manager()) {
        return;
    }

    if (strtolower($CI->router->class) === 'dashboard') {
        redirect(admin_url('field_portal'));
    }
}

/**
 * Restyles the "Field Executive Portal" top-level sidebar item as a
 * plain, non-clickable section heading with children always visible -
 * identical mechanism to dm_portal_sidebar_section_style_script()/
 * dev_portal_sidebar_section_style_script(), just targeting this
 * module's own menu-item class.
 *
 * The extra .active-state overrides below exist because this item's own
 * href is admin_url('field_portal') - the Dashboard landing page - so on
 * that exact page main.js's sidebar-active-link logic (assets/js/main.js,
 * "Check for active class in sidebar links") always adds the theme's
 * normal .active class to this <li>, which otherwise paints it with the
 * same rounded, colored "current page" box as any other top-level nav
 * item (assets/css/style.css: ".sidebar>ul.nav>li.active a:first-child"
 * and friends) - full-strength "active" chrome on what's meant to read
 * as a quiet section label, not a real destination.
 *
 * @return void
 */
function field_portal_sidebar_section_style_script()
{
    if (is_admin() || !is_field_portal_department_staff()) {
        return;
    }
    ?>
    <style>
        .menu-item-field_portal > a { cursor: default; }
        .menu-item-field_portal > a:hover,
        .menu-item-field_portal > a:focus { background: transparent !important; }
        .menu-item-field_portal > a .fa.arrow { display: none !important; }
        .menu-item-field_portal > a .menu-text {
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: .05em;
            opacity: .6;
        }
        .menu-item-field_portal > ul.nav-second-level {
            display: block !important;
            height: auto !important;
        }
        /* Neutralize the theme's "current page" active styling - this
           item is never actually a clickable destination (see JS below),
           so it must never look like one. Selectors are written to match
           (and slightly exceed) the specificity of style.css's own
           ".sidebar>ul.nav>li.active>a:first-child" et al - both sides
           use !important, so on a tie the theme's rule (loaded earlier,
           in <head>) would otherwise still win on source order alone. */
        .sidebar > ul.nav > li.menu-item-field_portal.active { border-left-color: transparent !important; }
        .sidebar > ul.nav > li.menu-item-field_portal.active > a:first-child {
            background: transparent !important;
            border: 0 !important;
            border-left: 0 !important;
            box-shadow: none !important;
        }
        .sidebar ul.nav li.menu-item-field_portal.active > a,
        .sidebar ul.nav li.menu-item-field_portal.active > a .menu-icon {
            color: var(--sidebar-icon) !important;
        }
    </style>
    <script>
    (function () {
        var $item = $('.menu-item-field_portal');
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
 * lead_converted_to_customer hook listener - sets tblleads.converted_by
 * to whichever staff member is logged in at the moment conversion
 * completes (Field Executive quick-convert, Telecaller's Customer Ready
 * outcome, or the native Admin Convert to Customer button - the hook
 * fires identically from all three since they all funnel through the
 * one Leads_model::convert_to_customer()). date_converted is already set
 * by that same method - only converted_by is new here.
 *
 * @param  array $data ['lead_id' => int, 'customer_id' => int]
 * @return void
 */
function field_portal_on_lead_converted_to_customer($data)
{
    if (empty($data['lead_id'])) {
        return;
    }

    $CI = &get_instance();
    $CI->db->where('id', $data['lead_id']);
    $CI->db->update(db_prefix() . 'leads', ['converted_by' => get_staff_user_id()]);
}

/**
 * No capability system used deliberately, same reasoning as
 * modules/dm_portal/modules/dev_portal - access is gated by Business
 * Department membership (is_field_portal_member()), not a grantable
 * permission. Exists only so Setup -> Staff Permissions has something to
 * show, matching every other module's registration pattern; nothing
 * currently reads it.
 */
function field_portal_permissions()
{
    $capabilities = [];

    $capabilities['capabilities'] = [
        'view' => _l('permission_view') . '(' . _l('permission_global') . ')',
    ];

    register_staff_capabilities('field_portal', $capabilities, _l('field_portal'));
}
