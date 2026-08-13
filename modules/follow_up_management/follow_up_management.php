<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Follow-up Management
Description: Standalone Follow-up Case tracking. The primary integration point with Leads is the core lead_status_changed hook - when a lead's status becomes "Follow-up", a Case is created automatically. No Leads controller/model/view file is ever edited. One narrow, explicitly-authorized exception exists: a self-guarding footer script (see follow_up_management_lead_followup_date_toggle_script()) that shows/hides the Lead form's existing "Next Follow-up Date" custom field based on the Lead Status dropdown - injected via the same official app_admin_footer hook every other footer script in this module already uses, not by editing any Leads file. Reminder/notification/calendar delivery is reused from the existing Reminders system, not duplicated.
Version: 2.0.0
Requires at least: 3.3.*
*/

require_once __DIR__ . '/helpers/follow_up_management_helper.php';

hooks()->add_action('admin_init', 'follow_up_management_module_init_menu_items');
hooks()->add_action('admin_init', 'follow_up_management_reports_menu_item');
hooks()->add_action('admin_init', 'follow_up_management_permissions');
hooks()->add_filter('get_dashboard_widgets', 'follow_up_management_add_dashboard_widget');
hooks()->add_filter('get_dashboard_widgets', 'follow_up_management_add_todays_followups_widget');
hooks()->add_filter('get_dashboard_widgets', 'follow_up_management_remove_admin_operational_widgets');
hooks()->add_filter('get_dashboard_widgets', 'follow_up_management_remove_telecaller_ticket_widgets');

// Calendar integration (Phase 5) - three official, additive extension
// points already exposed by core, none of them requiring a core file edit:
//   before_fetch_events    - Utilities_model::get_calendar_data()'s own
//                             filter for adding new event sources (the
//                             exact mechanism invoices/estimates/proposals/
//                             tasks/contracts/projects already use).
//   after_calendar_filters - application/views/admin/utilities/
//                             calendar_filters.php's own action hook for
//                             adding filter controls to the existing panel.
//   app_admin_footer       - fires on every admin page's footer
//                             (application/helpers/admin_helper.php); used
//                             here only for the Department/Staff/Priority/
//                             Case Status value-filters, which (unlike
//                             plain checkboxes) main.js's calendar
//                             eventSources closure has no mechanism to
//                             forward as query params - see
//                             follow_up_management_calendar_filter_script()
//                             for why this couldn't be done server-side
//                             without editing assets/js/main.js.
hooks()->add_filter('before_fetch_events', 'follow_up_management_calendar_events', 10, 2);
hooks()->add_action('after_calendar_filters', 'follow_up_management_calendar_filters_ui');
hooks()->add_action('app_admin_footer', 'follow_up_management_calendar_filter_script');

// Issue 1 fix (explicitly authorized, narrow exception - see the module
// header comment above): dynamic show/hide of the Lead form's existing
// "Next Follow-up Date" custom field, keyed off the Lead Status dropdown.
// Same app_admin_footer injection pattern as the Calendar filter script
// above - self-guarding, no Leads file edited.
hooks()->add_action('app_admin_footer', 'follow_up_management_lead_followup_date_toggle_script');

// Dependent State -> District dropdowns on the Lead add/edit form - same
// narrow, self-guarding app_admin_footer exception as the line above, not
// role-gated (a plain data-entry improvement for whoever adds/edits a
// lead). See follow_up_management_lead_state_district_cascade_script()
// for the full reasoning.
hooks()->add_action('app_admin_footer', 'follow_up_management_lead_state_district_cascade_script');

// Notification Center (Phase 6) - reuses relation_helper.php's own
// get_relation_data/relation_values filters (both already exist and are
// applied there for every rel_type, core or custom - confirmed by reading
// application/helpers/relation_helper.php:145 and :331) to teach the
// existing entity-resolution system about the new 'follow_up_case' rel_type
// that sync_reminder() now stores reminders under (see that method's
// docblock in Follow_ups_model.php for why). This is what makes
// Cron_model::staff_reminders() - which is rel_type-agnostic and calls
// exactly these two functions - resolve a working Case Detail link/name
// for follow-up reminders with zero changes to Cron_model.php.
hooks()->add_filter('get_relation_data', 'follow_up_management_relation_data', 10, 2);
hooks()->add_filter('relation_values', 'follow_up_management_relation_values');

// Reminder Overdue notification - piggybacks on the existing cron pass via
// register_cron_task() (= hooks()->add_action('after_cron_run', ...), the
// official module convention for this) rather than registering a new
// scheduled task, per "reuse the existing Cron process."
register_cron_task('follow_up_management_check_overdue_cases');

// Missed-follow-up auto-forward (Feature 3) - same cron pass, same
// register_cron_task() convention, no new scheduled task. See
// follow_up_management_forward_missed_followups()'s own docblock.
register_cron_task('follow_up_management_forward_missed_followups');

// Registers the one shared email template this module sends for
// assignment/status-type events (Case Assigned/Reassigned/High Priority/
// Reopened - see Follow_ups_model::notify_case_event()). Fires on module
// (re)activation for fresh installs, and is also applied once via
// migration 357 for this already-active install - both call the same
// idempotent create_email_template() (no-op if the slug already exists).
register_activation_hook('follow_up_management', 'follow_up_management_register_email_template');

// The ONLY integration point with the Leads module. This is a core Perfex
// hook (application/models/Leads_model.php - fired from both update() and
// update_lead_status()) - not something added to Leads for this module. No
// Leads file, view, menu, or JS is touched anywhere in this module.
hooks()->add_action('lead_status_changed', 'follow_up_management_on_lead_status_changed');

// Lead -> Case -> Task synchronization: lead_status_changed only fires on
// an actual status transition, so it only ever creates a Case once.
// after_lead_updated (also core Leads_model.php, fired on every successful
// Lead save) keeps an already-existing Case - and its linked Task - in
// sync with the Lead's current assigned staff, next follow-up date, and
// department whenever staff edit the Lead again without changing status.
hooks()->add_action('after_lead_updated', 'follow_up_management_on_lead_updated');

// Telecaller Workspace - turns a Follow-up Task's own detail view (the
// AJAX-loaded modal every task already opens into, application/views/
// admin/tasks/view_task_template.php) into a self-contained working
// screen, with zero core Tasks file edits. Both are official, pre-existing
// extension points already fired by core on every single task load:
//   get_task                       - Tasks_model::get()'s own closing
//                                     filter; used here to attach the
//                                     linked Case (if any) onto the task
//                                     object before the view ever renders.
//   before_task_description_section - view_task_template.php's own action
//                                     hook, positioned above the
//                                     Description heading; the callback
//                                     no-ops immediately for any task that
//                                     isn't a Follow-up Task, so every
//                                     other task's modal is byte-for-byte
//                                     unchanged.
// app_admin_footer is the same script-injection hook this module already
// uses for the Calendar filters and the Lead form's date-field toggle -
// here it wires the workspace's Reschedule/Add Note/Complete/Mark
// Pending/Cancel actions, refreshing the modal in place afterward instead
// of a full page reload (which would just close the modal).
hooks()->add_filter('get_task', 'follow_up_management_attach_case_to_task');
hooks()->add_action('before_task_description_section', 'follow_up_management_render_task_workspace');
hooks()->add_action('app_admin_footer', 'follow_up_management_task_workspace_script');

// Bug fix, confirmed by reading Leads::convert_to_customer() in full:
// that native form writes the Lead's status via a raw $this->db->update()
// call, bypassing both Leads_model::update() and update_lead_status() -
// the only two places that fire lead_status_changed. Rule 6
// (complete_case_for_lead(), already built and working for the
// kanban-drag/status-dropdown path) therefore never ran for a Lead
// converted through this specific native form. convert_to_customer()
// DOES fire its own distinct hook, lead_converted_to_customer
// (application/controllers/admin/Leads.php) - wiring that straight to
// the SAME existing complete_case_for_lead() closes the gap with no new
// completion logic, just a second trigger for it.
hooks()->add_action('lead_converted_to_customer', 'follow_up_management_on_lead_converted_to_customer');

// Dashboard "My Tasks" tab replacement for a plain Telecaller. Confirmed
// by reading the core dashboard widget system in full: "My Tasks" isn't
// its own registered widget in get_dashboard_widgets()
// (application/helpers/widgets_helper.php) - it's one tab fused inside
// the single shared core view admin/dashboard/widgets/user_data.php
// (Tasks/Projects/Reminders/Tickets/Announcements/Activity Log all in
// one file), with no hook wrapping or replacing just that tab. Rather
// than edit that core file, this reuses the exact same no-core-edit
// app_admin_footer self-guarding pattern already used three times above
// in this file - a no-op everywhere except the dashboard, and even
// there only for a plain Telecaller (no is_admin()/view_department
// capability) - Admins/Managers keep the native My Tasks tab untouched,
// per "generic Perfex Tasks page should remain available for Admin and
// project management".
hooks()->add_action('app_admin_footer', 'follow_up_management_dashboard_tab_swap_script');

// Phase 6: Customer Origin tab on the customer profile
hooks()->add_action('admin_init', 'follow_up_management_customer_origin_tab');

// Phase 6: Notify Admin of key events
hooks()->add_action('lead_converted_to_customer', 'follow_up_management_notify_admin_conversion', 10, 2);
hooks()->add_action('after_lead_updated', 'follow_up_management_notify_admin_high_priority_lead');
hooks()->add_action('app_admin_footer', 'follow_up_management_notify_admin_overdue_followup_script');

// Hides the native Dashboard's Projects In Progress card, My Projects
// tab, and Statistics by Project Status widget for a plain Telecaller -
// "Telecallers never use Projects." Same self-guarding footer-script
// pattern as the tab swap above; see
// follow_up_management_hide_project_widgets_script()'s own docblock for
// why this has to be client-side (neither piece is its own toggleable
// dashboard widget).
hooks()->add_action('app_admin_footer', 'follow_up_management_hide_project_widgets_script');

// Bug fix: the My Follow-ups page's Lead Status dropdown reload was
// silently targeting the wrong table (see
// follow_up_management_my_follow_ups_status_refresh_script()'s own
// docblock for the root cause) - not role-gated, applies to whoever
// uses this page.
hooks()->add_action('app_admin_footer', 'follow_up_management_my_follow_ups_status_refresh_script');

// Native "Tasks" sidebar item redirect for a plain Telecaller. Confirmed
// by reading App_menu::get() in full: every sidebar item registered via
// add_sidebar_menu_item() (core's own 'tasks' item included -
// application/helpers/menu_helper.php) passes through
// hooks()->apply_filters('sidebar_menu_items', $items) before render -
// an official, pre-existing extension point, not something added to
// core. A plain Telecaller should work exclusively from the Follow-up
// Management queue, per "the telecaller should be able to complete the
// entire sales follow-up workflow without ever opening the generic
// Perfex Tasks page" - so this rewrites the existing 'tasks' item's href
// (and label, to avoid a "Tasks" link opening a Lead queue) in place,
// same guard as the Dashboard tab swap above. Admins/Managers keep the
// native item untouched. No core file edited, no new sidebar item added.
hooks()->add_filter('sidebar_menu_items', 'follow_up_management_redirect_tasks_sidebar_item');

// Sidebar cleanup for a plain Telecaller - pure UI/menu visibility, same
// sidebar_menu_items filter point as the redirect above, kept as a
// separate callback (distinct concern: that one rewrites an item, this
// one hides items) rather than folded into it. Nothing here touches a
// route, controller, permission, or DB row - every hidden page/action
// stays fully reachable by direct URL and fully functional for whoever
// can still see its menu link (Admin/Manager).
//
// Hides 'projects' and 'support' - core sidebar items
// (application/helpers/menu_helper.php), neither part of the Telecaller
// workflow. Their controllers/routes are completely untouched - Support
// tickets stay fully reachable by direct URL for anyone who still needs
// them (e.g. a lead's ticket history opened from elsewhere), and every
// other role's own Support menu link is unaffected.
//
// 'customers' is intentionally NOT hidden (reversed from an earlier phase's
// decision): the Lead Management workflow (this module's Customer Ready
// action, and field_portal's own conversion) now routinely produces
// Telecaller-owned customers via the existing
// auto_assign_customer_admin_after_lead_convert grant (a real
// tblcustomer_admins row), and that native Customers page already scopes
// its list to customers the logged-in staff is assigned/permitted to see -
// nothing new needed here beyond letting the existing menu link through.
//
// 'follow_up_management' (the whole top-level group) IS hidden here for
// a plain Telecaller - finalized navigation: this role should have no
// "Follow-up Management" parent at all, only flat top-level items
// (Dashboard/Leads/My Follow-ups/Follow-up History/Utilities). 'support'
// is likewise hidden - not part of a Telecaller's workflow, Admin-oriented.
// My Follow-ups stays reachable via the 'tasks' slot redirect above;
// Follow-up History is injected below as its own synthetic top-level
// item (application/views/admin/tables/all_follow_ups.php/its own view
// already render the simplified interface for this role regardless of
// which menu link reaches it - this is purely how it's surfaced in the
// sidebar). Admin/Manager keep the full "Follow-up Management" group,
// trimmed to 3 children by follow_up_management_simplify_menu_children_for_admin()
// below.
function follow_up_management_hide_menus_for_telecaller($items)
{
    if (!is_staff_member() || is_admin() || staff_can('view_department', 'follow_up_management') || !follow_up_management_is_telecalling_department_member()) {
        return $items;
    }

    unset($items['projects']);
    unset($items['follow_up_management']);
    unset($items['support']);

    // Synthetic top-level item - not registered via
    // add_sidebar_menu_item() (that would show for every role; this
    // needs to exist for a plain Telecaller only), built with the exact
    // key shape application/views/admin/includes/aside.php reads
    // (confirmed by reading it in full: slug/name/href/icon/children,
    // badge is isset()-guarded so safe to omit). Position 48 - see
    // follow_up_management_redirect_tasks_sidebar_item()'s own comment
    // for the full intended order this and the repositioned 'tasks' item
    // (47) are both part of.
    $items['follow_up_management_history'] = [
        'slug'     => 'follow_up_management_history',
        'name'     => _l('followup_history'),
        'href'     => admin_url('follow_up_management/all_follow_ups'),
        'icon'     => 'fa-regular fa-clock',
        'position' => 48,
        'children' => [],
    ];

    return $items;
}
hooks()->add_filter('sidebar_menu_items', 'follow_up_management_hide_menus_for_telecaller');

/**
 * Hides the entire "Follow-up Management" top-level sidebar item for
 * Admin. The only 6 children Admin ever saw under this group (Admin
 * Sales Dashboard/Sales Pipeline/Employee Performance/Lead Reports/
 * Today's Follow-ups/Follow-up History - see
 * follow_up_management_simplify_menu_children_for_admin() below) are all
 * operational Telecaller/Sales-workflow pages that no longer belong in
 * the native Admin Portal. Unlike the Telecaller-hide filter above, no
 * synthetic replacement item is injected - Admin simply loses this menu
 * group, nothing is put in its place.
 *
 * Manager (the 'view_department' capability holder) is deliberately left
 * untouched - only literal is_admin() loses this menu; none of the
 * pages/controllers/routes/permissions themselves change, and every
 * direct URL keeps working exactly as before for every role.
 *
 * @param  array $items
 * @return array
 */
function follow_up_management_hide_menu_for_admin($items)
{
    if (!is_admin()) {
        return $items;
    }

    unset($items['follow_up_management']);

    return $items;
}
hooks()->add_filter('sidebar_menu_items', 'follow_up_management_hide_menu_for_admin');

// Trims the "Follow-up Management" group's own children for Admin/Manager
// down to exactly Performance Dashboard/Today's Follow-ups/Follow-up
// History (in that order) - hides My Follow-ups/Dashboard/My Cases/
// Notification Center from the sidebar only; none of their controllers,
// routes, or the pages themselves are touched, all four stay fully
// reachable by direct URL. A plain Telecaller never reaches this
// function's filtering logic at all (guard below returns unmodified) -
// that role has no "Follow-up Management" parent to have children of in
// the first place (see follow_up_management_hide_menus_for_telecaller()
// above, which unsets the whole group for that role).
//
// Positions are reassigned here (0/1/2) purely for sidebar ordering -
// the base registration's own position values (follow_up_management.php's
// follow_up_management_module_init_menu_items(), still 0-6 for all 7
// children, completely unchanged) are irrelevant once this filter has
// already reduced the array to these 3 - app_sort_by_position() (called
// by App_menu::get_child() right after this filter runs) sorts by
// whatever 'position' value survives here, not by the original one.
//
// App_menu::get_child() applies hooks()->apply_filters(
// 'sidebar_menu_child_items', $children, $parent_slug) for every parent
// - a second, per-parent-children extension point distinct from the
// top-level sidebar_menu_items filter above (confirmed by reading
// App_menu.php in full), guarded here to only ever touch the
// 'follow_up_management' parent's own children.
function follow_up_management_simplify_menu_children_for_admin($children, $parent_slug)
{
    if ($parent_slug !== 'follow_up_management') {
        return $children;
    }

    // Admin no longer gets any of this module's pages in the native
    // Admin Portal sidebar - see follow_up_management_hide_menu_for_admin()
    // below, which hides the whole parent item for this same role. This
    // branch is defense-in-depth in case something else ever queries this
    // parent's children directly for an Admin viewer without going
    // through the top-level sidebar_menu_items filter first. Manager
    // (the 'view_department' capability holder) is untouched - falls
    // through to the existing trim-to-6 behavior below exactly as before.
    if (is_admin()) {
        return [];
    }

    // Same three-tier check as everywhere else in this module, inverted:
    // a plain Telecaller returns here unmodified (that role's parent is
    // already hidden entirely, so this would be a no-op anyway) - only
    // Manager gets the trim now (Admin is handled above).
    $is_plain_telecaller = is_staff_member() && !is_admin() && !staff_can('view_department', 'follow_up_management') && follow_up_management_is_telecalling_department_member();
    if (!is_staff_member() || $is_plain_telecaller) {
        return $children;
    }

    $positions = [
        'follow_up_management-admin-sales'         => 0,
        'follow_up_management-sales-pipeline'      => 1,
        'follow_up_management-employee-performance' => 2,
        'follow_up_management-lead-reports'        => 3,
        'follow_up_management-todays-followups'    => 4,
        'follow_up_management-all-followups'       => 5,
    ];

    $filtered = [];
    foreach ($children as $child) {
        if (!isset($positions[$child['slug']])) {
            continue;
        }

        $child['position'] = $positions[$child['slug']];
        $filtered[]         = $child;
    }

    return $filtered;
}
hooks()->add_filter('sidebar_menu_child_items', 'follow_up_management_simplify_menu_children_for_admin', 10, 2);

function follow_up_management_module_init_menu_items()
{
    $CI = &get_instance();

    follow_up_management_load_language();

    if (staff_can('view', 'leads')) {
        // Standalone top-level sidebar item - never nested under 'leads'.
        // application/views/admin/includes/aside.php:73 hard-codes that any
        // sidebar item with 1+ children loses its own direct link and
        // becomes a toggle-only dropdown. 'leads' is registered in core
        // (application/helpers/menu_helper.php:185) as a plain, childless,
        // directly-clickable item and must stay that way. This module's own
        // top-level item gaining children here is unrelated to that bug -
        // it never nests under 'leads', it just becomes a normal
        // parent-with-submenu item the same way 'Reports' already is.
        $CI->app_menu->add_sidebar_menu_item('follow_up_management', [
            'name'     => _l('follow_up_management'),
            'href'     => admin_url('follow_up_management/dashboard'),
            'icon'     => 'fa-regular fa-clock',
            'position' => 47,
            'badge'    => [],
        ]);

        // Telecaller work queue - deliberately position 0, ahead of every
        // existing child item (all of which keep their own untouched
        // position values) - this is the intended primary entry point for
        // a Telecaller's whole workflow, per "should work exclusively
        // from the dedicated My Leads / My Follow-ups queue" rather than
        // the generic Tasks list.
        $CI->app_menu->add_sidebar_children_item('follow_up_management', [
            'slug'     => 'follow_up_management-my-leads',
            'name'     => _l('my_leads'),
            'href'     => admin_url('follow_up_management/my_leads'),
            'position' => 0,
            'badge'    => [],
        ]);

        $CI->app_menu->add_sidebar_children_item('follow_up_management', [
            'slug'     => 'follow_up_management-my-follow-ups',
            'name'     => _l('my_follow_ups'),
            'href'     => admin_url('follow_up_management/my_follow_ups'),
            'position' => 1,
            'badge'    => [],
        ]);

        $CI->app_menu->add_sidebar_children_item('follow_up_management', [
            'slug'     => 'follow_up_management-dashboard',
            'name'     => _l('dashboard'),
            'href'     => admin_url('follow_up_management/dashboard'),
            'position' => 2,
            'badge'    => [],
        ]);

        $CI->app_menu->add_sidebar_children_item('follow_up_management', [
            'slug'     => 'follow_up_management-todays-followups',
            'name'     => _l('todays_followups'),
            'href'     => admin_url('follow_up_management/todays_followups'),
            'position' => 3,
            'badge'    => [],
        ]);

        $CI->app_menu->add_sidebar_children_item('follow_up_management', [
            'slug'     => 'follow_up_management-tomorrow-followups',
            'name'     => _l('followup_filter_tomorrow'),
            'href'     => admin_url('follow_up_management/followups_filtered/tomorrow'),
            'position' => 4,
            'badge'    => [],
        ]);

        $CI->app_menu->add_sidebar_children_item('follow_up_management', [
            'slug'     => 'follow_up_management-upcoming-followups',
            'name'     => _l('followup_filter_upcoming'),
            'href'     => admin_url('follow_up_management/followups_filtered/upcoming'),
            'position' => 5,
            'badge'    => [],
        ]);

        $CI->app_menu->add_sidebar_children_item('follow_up_management', [
            'slug'     => 'follow_up_management-overdue-followups',
            'name'     => _l('followup_filter_overdue'),
            'href'     => admin_url('follow_up_management/followups_filtered/overdue'),
            'position' => 6,
            'badge'    => [],
        ]);

        $CI->app_menu->add_sidebar_children_item('follow_up_management', [
            'slug'     => 'follow_up_management-completed-followups',
            'name'     => _l('followup_filter_completed'),
            'href'     => admin_url('follow_up_management/followups_filtered/completed'),
            'position' => 7,
            'badge'    => [],
        ]);

        $CI->app_menu->add_sidebar_children_item('follow_up_management', [
            'slug'     => 'follow_up_management-customers',
            'name'     => _l('followup_customers'),
            'href'     => admin_url('follow_up_management/customers'),
            'position' => 8,
            'badge'    => [],
        ]);

        $CI->app_menu->add_sidebar_children_item('follow_up_management', [
            'slug'     => 'follow_up_management-notifications',
            'name'     => _l('notification_center'),
            'href'     => admin_url('follow_up_management/notifications'),
            'position' => 9,
            'badge'    => [],
        ]);

        $CI->app_menu->add_sidebar_children_item('follow_up_management', [
            'slug'     => 'follow_up_management-performance',
            'name'     => _l('performance_dashboard'),
            'href'     => admin_url('follow_up_management/performance'),
            'position' => 10,
            'badge'    => [],
        ]);

        $CI->app_menu->add_sidebar_children_item('follow_up_management', [
            'slug'     => 'follow_up_management-all-followups',
            'name'     => _l('followup_history'),
            'href'     => admin_url('follow_up_management/all_follow_ups'),
            'position' => 11,
            'badge'    => [],
        ]);

        // ----------------------------------------------------------------
        // Admin Sales CRM (Phase 6) - visible to Admin/Manager only
        // ----------------------------------------------------------------
        if (is_admin() || staff_can('view_department', 'follow_up_management')) {
            $CI->app_menu->add_sidebar_children_item('follow_up_management', [
                'slug'     => 'follow_up_management-admin-sales',
                'name'     => _l('followup_admin_sales_dashboard'),
                'href'     => admin_url('follow_up_management/admin_sales_dashboard'),
                'position' => 20,
                'badge'    => [],
            ]);

            $CI->app_menu->add_sidebar_children_item('follow_up_management', [
                'slug'     => 'follow_up_management-sales-pipeline',
                'name'     => _l('followup_sales_pipeline'),
                'href'     => admin_url('follow_up_management/sales_pipeline'),
                'position' => 21,
                'badge'    => [],
            ]);

            $CI->app_menu->add_sidebar_children_item('follow_up_management', [
                'slug'     => 'follow_up_management-employee-performance',
                'name'     => _l('followup_employee_performance'),
                'href'     => admin_url('follow_up_management/employee_performance'),
                'position' => 22,
                'badge'    => [],
            ]);

            $CI->app_menu->add_sidebar_children_item('follow_up_management', [
                'slug'     => 'follow_up_management-lead-reports',
                'name'     => _l('followup_lead_reports'),
                'href'     => admin_url('follow_up_management/lead_reports'),
                'position' => 23,
                'badge'    => [],
            ]);
        }
    }
}

/**
 * One new capability, additive to the existing base gate this module has
 * always used (staff_cant('view', 'leads')) so no currently-granted access
 * is revoked by this phase. Admin already bypasses every staff_can() check
 * via is_admin() (Perfex core convention, already relied on throughout this
 * module) - Manager is simply "can view leads AND has this capability
 * checked", Telecaller is the default (can view leads, capability
 * unchecked). See get_followup_case_visibility_where() in the helper.
 */
function follow_up_management_permissions()
{
    $capabilities                    = [];
    $capabilities['capabilities'] = [
        'view_department' => _l('followup_permission_view_department'),
    ];

    register_staff_capabilities('follow_up_management', $capabilities, _l('follow_up_management'));
}

/**
 * Registers under the existing core Reports sidebar section, following the
 * exact same add_sidebar_children_item('reports', ...) pattern the built-in
 * Leads report menu item uses (application/helpers/menu_helper.php) - no
 * core file edit needed to add a new report entry.
 */
function follow_up_management_reports_menu_item()
{
    $CI = &get_instance();

    if (staff_cant('view', 'reports')) {
        return;
    }

    $CI->app_menu->add_sidebar_children_item('reports', [
        'slug'     => 'followups-reports',
        'name'     => _l('als_reports_followups_submenu'),
        'href'     => admin_url('reports/followups'),
        'position' => 25,
        'badge'    => [],
    ]);
}

/**
 * "My Follow-ups Due" dashboard widget - mirrors modules/goals/goals.php's
 * registration call exactly. Telecaller-only (same three-tier guard as
 * follow_up_management_hide_project_widgets_script() below): Admin no
 * longer performs day-to-day follow-up work from the native Dashboard, so
 * this personal queue card has no reason to register there. This is a
 * role-gated no-op, not a deletion - the widget file, its language
 * strings, and this registration function all stay intact so nothing
 * needs re-adding if a future role also needs this card.
 *
 * @param  array $widgets
 * @return array
 */
function follow_up_management_add_dashboard_widget($widgets)
{
    if (!is_staff_member() || is_admin() || staff_can('view_department', 'follow_up_management') || !follow_up_management_is_telecalling_department_member()) {
        return $widgets;
    }

    $widgets[] = [
        'path'      => 'follow_up_management/widget',
        'container' => 'right-4',
    ];

    return $widgets;
}

/**
 * Removes the core "My Tasks/My Projects/My Reminders/Tickets/
 * Announcements/Latest Activity" widget (application/views/admin/
 * dashboard/widgets/user_data.php) from the native Admin Dashboard only.
 * Per the updated architecture, Admin no longer performs day-to-day
 * task/follow-up/reminder work from this dashboard - Field Executives use
 * the Field Executive Portal and Telecallers use the Follow-up
 * Management / Telecaller workflow.
 *
 * This is a get_dashboard_widgets filter, the same official extension
 * point every widget in this file already registers through - no core
 * file is edited, the widget file itself is never touched, and nothing is
 * deleted. For every other viewer (Telecaller included) this filter is a
 * complete no-op: the widget keeps rendering exactly as before, along
 * with this module's own existing Telecaller-only footer scripts that
 * already customize it (the Tasks tab renamed to "My Follow-ups" - see
 * the app_admin_footer tab-swap registered further below - and the
 * Projects tab/card hidden by
 * follow_up_management_hide_project_widgets_script()).
 *
 * @param  array $widgets
 * @return array
 */
function follow_up_management_remove_admin_operational_widgets($widgets)
{
    if (!is_admin()) {
        return $widgets;
    }

    return array_values(array_filter($widgets, function ($widget) {
        return $widget['path'] !== 'admin/dashboard/widgets/user_data';
    }));
}

/**
 * Removes the two Admin-oriented ticket widgets - "Tickets Awaiting
 * Reply by Status" and "Tickets Awaiting Reply by Department" - from a
 * plain Telecaller's native Dashboard. Both charts are rendered together
 * by the one standalone admin/dashboard/widgets/tickets_chart.php file
 * (confirmed by reading it: a single `if` wraps both chart blocks), and
 * that file is registered as its own single entry in
 * widgets_helper.php's get_dashboard_widgets() - unlike the
 * Projects-related pieces this module already hides for Telecallers
 * (see follow_up_management_hide_project_widgets_script()'s own
 * docblock), which are fused into shared multi-purpose widget files
 * with no clean per-piece removal point, tickets_chart.php genuinely IS
 * removable through this filter alone - no JS/CSS DOM-hiding needed,
 * and render_dashboard_widgets() (widgets_helper.php) never generates
 * this widget's HTML at all when its entry is absent from the array, so
 * no blank space is ever left in the "right-4" column layout. Sibling
 * widgets in that same column (Leads Overview / leads_chart, Statistics
 * by Project Status / projects_chart, Projects Activity) are separate
 * registered entries and are untouched.
 *
 * Same "plain Telecaller" guard as every other Telecaller-only
 * customization in this file (follow_up_management_dashboard_tab_swap_script(),
 * follow_up_management_hide_project_widgets_script()) - Admin and any
 * Manager-level staff (staff_can('view_department', ...)) keep the
 * unmodified native Dashboard, and every other department's own portal
 * dashboard (Digital Marketing/Web Development/Field Executive) never
 * calls this filter's Telecalling-membership check true, so is
 * completely unaffected.
 *
 * @param  array $widgets
 * @return array
 */
function follow_up_management_remove_telecaller_ticket_widgets($widgets)
{
    if (!is_staff_member() || is_admin() || staff_can('view_department', 'follow_up_management') || !follow_up_management_is_telecalling_department_member()) {
        return $widgets;
    }

    return array_values(array_filter($widgets, function ($widget) {
        return $widget['path'] !== 'admin/dashboard/widgets/tickets_chart';
    }));
}

/**
 * "Today's Follow-ups" widget for the native Dashboard's main/left column -
 * this is what moves the FullCalendar out of the first-major-content spot:
 * the new widget (modules/follow_up_management/views/
 * dashboard_todays_followups.php) is spliced into get_dashboard_widgets()
 * IMMEDIATELY BEFORE the core 'admin/dashboard/widgets/calendar' entry, so
 * with the default (non-customized) widget order the Telecaller's daily
 * queue renders above the calendar in the same left-8 container. The
 * calendar widget itself is completely untouched - every one of its
 * existing controls (Month/Week/Day/Today/Expand/Filter By/Previous/Next)
 * keeps working exactly as before, it simply renders after this card.
 *
 * Plain Telecaller only - the same three-tier guard every other
 * Telecaller-only customization in this file uses (Admin/Manager keep the
 * unmodified native dashboard, other departments never match the
 * Telecalling-membership check). The widget's rows reuse
 * Follow_ups_model::get_todays_followups_grouped(), which already applies
 * get_followup_case_visibility_where() - a plain Telecaller therefore only
 * ever sees their OWN assigned follow-ups, with no staff/department IDs
 * hardcoded anywhere. If the logged-in staff has a saved custom dashboard
 * order, the widget system appends this brand-new widget at the end of the
 * column instead - still fully draggable via the existing dashboard
 * options, and the default order (the common case) is exactly as required.
 *
 * @param  array $widgets
 * @return array
 */
function follow_up_management_add_todays_followups_widget($widgets)
{
    if (!is_staff_member() || is_admin() || staff_can('view_department', 'follow_up_management') || !follow_up_management_is_telecalling_department_member()) {
        return $widgets;
    }

    $entry = [
        'path'      => 'follow_up_management/dashboard_todays_followups',
        'container' => 'left-8',
    ];

    $calendar_index = null;
    foreach ($widgets as $key => $widget) {
        if (($widget['path'] ?? '') === 'admin/dashboard/widgets/calendar') {
            $calendar_index = $key;
            break;
        }
    }

    // Calendar entry missing (future core change) - append at the end
    // rather than break the dashboard render.
    if ($calendar_index === null) {
        $widgets[] = $entry;
    } else {
        array_splice($widgets, $calendar_index, 0, [$entry]);
    }

    return $widgets;
}

/**
 * Adds one calendar event per open Case with a scheduled
 * next_follow_up_date, in the date range FullCalendar is currently
 * displaying - reusing Follow_ups_model::get_calendar_events() (the same
 * method the Dashboard's Upcoming Follow-ups calls, so there is exactly one
 * query implementation, not two). Never runs for the customer-portal
 * calendar (client_data) - Follow-up Cases are a staff-only concept.
 *
 * Event type (Follow-up Reminder / Meeting / Call Schedule) is derived from
 * the case's own follow_up_type, each with a distinct color from the same
 * get_system_favourite_colors() palette every other chart in this module
 * already uses. Each event's url points straight at the Case Detail page -
 * never the Lead - satisfying "each calendar item must open the related
 * Case Detail page."
 *
 * Follows the exact same "$ff" show-if-not-filtering / show-if-checked
 * convention every native source in Utilities_model::get_calendar_data()
 * already uses, reading the three checkboxes
 * follow_up_management_calendar_filters_ui() renders into the same panel.
 *
 * @param array $data
 * @param array $hook ['client_data' => bool, ...]
 * @return array
 */
function follow_up_management_calendar_events($data, $hook)
{
    if (!empty($hook['client_data'])) {
        return $data;
    }

    $CI    = &get_instance();
    $start = $CI->input->get('start');
    $end   = $CI->input->get('end');

    if (!$start || !$end) {
        return $data;
    }

    $filters = $CI->input->get();
    $ff      = (count($filters) > 1 && isset($filters['calendar_filters']));

    $show = [
        'reminder' => (!$ff) || ($ff && array_key_exists('followup_reminder', $filters)),
        'meeting'  => (!$ff) || ($ff && array_key_exists('followup_meeting', $filters)),
        'call'     => (!$ff) || ($ff && array_key_exists('followup_call', $filters)),
    ];

    if (!$show['reminder'] && !$show['meeting'] && !$show['call']) {
        return $data;
    }

    follow_up_management_load_language();
    $CI->load->model('follow_up_management/follow_ups_model');

    $cases = $CI->follow_ups_model->get_calendar_events(
        date('Y-m-d', strtotime($start)),
        date('Y-m-d', strtotime($end))
    );

    $colors      = get_system_favourite_colors();
    $type_labels = [
        'meeting'  => _l('followup_calendar_type_meeting'),
        'call'     => _l('followup_calendar_type_call'),
        'reminder' => _l('followup_calendar_type_reminder'),
    ];
    $type_colors = [
        'meeting'  => $colors[4],
        'call'     => $colors[1],
        'reminder' => $colors[0],
    ];

    foreach ($cases as $case) {
        $event_kind = $case->follow_up_type === 'meeting' ? 'meeting' : ($case->follow_up_type === 'call' ? 'call' : 'reminder');

        if (!$show[$event_kind]) {
            continue;
        }

        $rel_data   = get_relation_data($case->rel_type, $case->rel_id);
        $rel_values = get_relation_values($rel_data, $case->rel_type);
        $title      = $type_labels[$event_kind] . ' - ' . $rel_values['name'];

        $data[] = [
            'title'     => $title,
            'date'      => $case->next_follow_up_date,
            'color'     => $type_colors[$event_kind],
            '_tooltip'  => $title . ' (' . get_followup_priority_label($case->priority) . ')',
            'url'       => admin_url('follow_up_management/view/' . $case->id),
            // Consumed client-side by follow_up_management_calendar_filter_script()
            // for the Department/Staff/Priority/Case Status filters - see
            // that function's docblock for why this couldn't be a normal
            // server-side query-param filter.
            'className' => [
                'followup-event',
                'followup-dept-' . ($case->department_id ?: 0),
                'followup-staff-' . $case->assigned_staff_id,
                'followup-priority-' . $case->priority,
                'followup-status-' . $case->status,
            ],
        ];
    }

    return $data;
}

/**
 * Renders into calendar_filters.php's existing after_calendar_filters
 * action hook - not a separate filter panel. The three checkboxes follow
 * the panel's own convention exactly (main.js's calendar eventSources
 * closure already harvests EVERY checked checkbox inside #calendar_filters
 * generically, by name, not a hardcoded list - so these work through the
 * native mechanism with no JS of any kind). The four <select> value-filters
 * cannot go through that same mechanism (main.js only harvests checkboxes)
 * - see follow_up_management_calendar_filter_script() for how those are
 * applied instead.
 */
function follow_up_management_calendar_filters_ui()
{
    $CI = &get_instance();
    follow_up_management_load_language();
    ?>
    <div class="checkbox">
        <input type="checkbox" value="1" name="followup_reminder" id="cf_followup_reminder"<?php if ($CI->input->post('followup_reminder')) {
            echo ' checked';
        } ?>>
        <label for="cf_followup_reminder"><?= _l('followup_calendar_type_reminder'); ?></label>
    </div>
    <div class="checkbox">
        <input type="checkbox" value="1" name="followup_meeting" id="cf_followup_meeting"<?php if ($CI->input->post('followup_meeting')) {
            echo ' checked';
        } ?>>
        <label for="cf_followup_meeting"><?= _l('followup_calendar_type_meeting'); ?></label>
    </div>
    <div class="checkbox">
        <input type="checkbox" value="1" name="followup_call" id="cf_followup_call"<?php if ($CI->input->post('followup_call')) {
            echo ' checked';
        } ?>>
        <label for="cf_followup_call"><?= _l('followup_calendar_type_call'); ?></label>
    </div>
    <div class="form-group tw-mt-2">
        <label for="followup_filter_department"><?= _l('case_field_department'); ?></label>
        <select id="followup_filter_department" class="form-control">
            <option value=""><?= _l('followup_filter_all_departments'); ?></option>
            <?php foreach (get_business_departments() as $department) { ?>
            <option value="<?= e($department['id']); ?>"><?= e($department['name']); ?></option>
            <?php } ?>
        </select>
    </div>
    <div class="form-group">
        <label for="followup_filter_staff"><?= _l('case_field_assigned_staff'); ?></label>
        <?php $CI->load->model('staff_model'); ?>
        <select id="followup_filter_staff" class="form-control">
            <option value=""><?= _l('followup_filter_all_staff'); ?></option>
            <?php foreach ($CI->staff_model->get('', ['active' => 1]) as $member) { ?>
            <option value="<?= e($member['staffid']); ?>"><?= e($member['firstname'] . ' ' . $member['lastname']); ?></option>
            <?php } ?>
        </select>
    </div>
    <div class="form-group">
        <label for="followup_filter_priority"><?= _l('case_field_priority'); ?></label>
        <select id="followup_filter_priority" class="form-control">
            <option value=""><?= _l('followup_filter_all_priorities'); ?></option>
            <?php foreach (get_followup_priorities() as $key => $label) { ?>
            <option value="<?= e($key); ?>"><?= e($label); ?></option>
            <?php } ?>
        </select>
    </div>
    <div class="form-group">
        <label for="followup_filter_case_status"><?= _l('case_field_case_status'); ?></label>
        <select id="followup_filter_case_status" class="form-control">
            <option value=""><?= _l('followup_filter_all_case_statuses'); ?></option>
            <?php foreach (get_followup_statuses() as $key => $label) { ?>
            <option value="<?= e($key); ?>"><?= e($label); ?></option>
            <?php } ?>
        </select>
    </div>
    <?php
}

/**
 * Applies the Department/Staff/Priority/Case Status filters client-side by
 * toggling visibility of the already-rendered .fc-event.followup-event
 * elements based on the className tags
 * follow_up_management_calendar_events() attaches to each event.
 *
 * Why client-side: FullCalendar's data source is a hardcoded closure inside
 * assets/js/main.js (calendar_settings.eventSources) that reads exactly one
 * thing - checked checkboxes inside #calendar_filters, by name - and sends
 * them as query params to utilities/get_calendar_data. It has no mechanism
 * to also forward arbitrary <select> values, and that file is explicitly
 * off-limits for this module to edit. So instead of a second server round
 * trip, the four selects filter the events already present in the DOM.
 *
 * Self-guarding: this fires on app_admin_footer, which runs on every admin
 * page, but does nothing unless #calendar_filters actually exists on the
 * current page - it is a no-op everywhere except Utilities::calendar().
 * A MutationObserver re-applies the filters whenever FullCalendar
 * re-renders events (month/week/day navigation), since each navigation
 * fetches fresh event DOM nodes.
 */
function follow_up_management_calendar_filter_script()
{
    ?>
    <script>
    (function () {
        if (!document.getElementById('calendar_filters')) {
            return;
        }

        var filterIds = [
            'followup_filter_department',
            'followup_filter_staff',
            'followup_filter_priority',
            'followup_filter_case_status'
        ];

        function applyFollowupCalendarFilters() {
            var deptEl = document.getElementById('followup_filter_department');
            if (!deptEl) {
                return;
            }

            var deptVal   = deptEl.value;
            var staffVal  = document.getElementById('followup_filter_staff').value;
            var prioVal   = document.getElementById('followup_filter_priority').value;
            var statusVal = document.getElementById('followup_filter_case_status').value;

            var events = document.querySelectorAll('.fc-event.followup-event');
            for (var i = 0; i < events.length; i++) {
                var el   = events[i];
                var show = true;
                if (deptVal && !el.classList.contains('followup-dept-' + deptVal)) { show = false; }
                if (staffVal && !el.classList.contains('followup-staff-' + staffVal)) { show = false; }
                if (prioVal && !el.classList.contains('followup-priority-' + prioVal)) { show = false; }
                if (statusVal && !el.classList.contains('followup-status-' + statusVal)) { show = false; }
                el.style.display = show ? '' : 'none';
            }
        }

        filterIds.forEach(function (id) {
            var el = document.getElementById(id);
            if (el) {
                el.addEventListener('change', applyFollowupCalendarFilters);
            }
        });

        var target = document.querySelector('.fc-view-harness') || document.getElementById('calendar');
        if (target && window.MutationObserver) {
            new MutationObserver(applyFollowupCalendarFilters).observe(target, { childList: true, subtree: true });
        }

        setTimeout(applyFollowupCalendarFilters, 500);
    })();
    </script>
    <?php
}

/**
 * Issue 1 fix. The "Next Follow-up Date" field is the existing Lead
 * custom field (tblcustomfields, fieldto='leads', type='date_picker') -
 * application/views/admin/leads/profile.php now renders it beside Status
 * using the generic render_date_input(), given that field's own POST
 * name, so it still saves/loads through the existing generic custom
 * field handling with no backend change. Its position and initial
 * shown/hidden state (based on the lead's current status) are decided
 * once, server-side, at render time - see that file. The only thing left
 * for JS to do is react to the user changing Status afterward: one
 * delegated change handler, no DOM searching/moving/observing.
 */
function follow_up_management_lead_followup_date_toggle_script()
{
    $CI = &get_instance();

    $field = get_custom_fields('leads', ['name' => 'Next Follow-up Date']);

    if (empty($field)) {
        return;
    }

    $field_name = 'custom_fields[leads][' . (int) $field[0]['id'] . ']';

    $CI->db->where('name', 'Follow-up');
    $status = $CI->db->get(db_prefix() . 'leads_status')->row();

    if (!$status) {
        return;
    }

    $follow_up_status_id = (int) $status->id;
    ?>
    <script>
    (function () {
        var FOLLOW_UP_STATUS_ID = '<?= $follow_up_status_id; ?>';
        var FIELD_NAME = '<?= e($field_name); ?>';

        $(document).on('changed.bs.select change', '#lead_form #status', function () {
            var $wrapper = $('#lead_form [app-field-wrapper="' + FIELD_NAME + '"]');
            if (!$wrapper.length) {
                return;
            }

            if (String($(this).val()) === FOLLOW_UP_STATUS_ID) {
                $wrapper.show();
            } else {
                $wrapper.hide();
            }
        });
    })();
    </script>
    <?php
}

/**
 * Dependent State -> District dropdowns on the Lead add/edit form. Reuses
 * the two EXISTING "State"/"District" select-type Custom Fields (fieldto
 * 'leads', ids looked up by name below - not hardcoded) - no new DB
 * columns, no change to how a Lead gets saved (still the generic
 * handle_custom_fields_post() path every custom field already goes
 * through - Leads.php/Leads_model.php are not touched). Migration 361
 * set the District field's own `options` column to the full union of
 * every AP + Telangana district (was a small unscoped mixed sample
 * before), which is what keeps the existing generic render_custom_fields()
 * renderer working completely unchanged, and keeps every already-saved
 * lead's district value found/pre-selected exactly as before - backward
 * compatible. This script only narrows the ALREADY-rendered <option>
 * list down to the selected state's subset; it never changes what gets
 * submitted/saved beyond that narrowing.
 *
 * The State->District grouping below must stay in sync with the District
 * field's `options` column (Setup -> Custom Fields -> Leads, or migration
 * 361) if districts are ever added/renamed - there is deliberately no
 * single source of truth for the grouping without a schema change, which
 * this task explicitly ruled out ("do not create new database columns").
 *
 * Same app_admin_footer self-guarding pattern, and the same
 * `$(document).on(..., '#lead_form ...', ...)` document-delegated timing
 * approach, as follow_up_management_lead_followup_date_toggle_script()
 * above - delegation means this does not need to know/guess when the
 * Lead modal's AJAX-loaded content actually lands in the DOM. `loaded.bs.select`
 * (bootstrap-select's own one-time-per-init lifecycle event, confirmed in
 * assets/plugins/bootstrap-select/js/bootstrap-select.js) is used for the
 * *initial* population specifically because it fires once the State
 * select's value is already known - correctly covering both Add (no
 * state yet -> District starts disabled/empty) and Edit (state already
 * has a saved value -> District populates with the previously-saved
 * district kept selected) with one code path, instead of guessing which
 * case applies.
 *
 * @return void
 */
function follow_up_management_lead_state_district_cascade_script()
{
    $state_field    = get_custom_fields('leads', ['name' => 'State']);
    $district_field = get_custom_fields('leads', ['name' => 'District']);

    if (empty($state_field) || empty($district_field)) {
        return;
    }

    $state_field_id    = (int) $state_field[0]['id'];
    $district_field_id = (int) $district_field[0]['id'];

    // Kept in sync with migration 361's District `options` value - see
    // this function's docblock for why this can't be derived from the DB
    // alone without a schema change.
    $state_districts = [
        'Andhra Pradesh' => [
            'Alluri Sitharama Raju', 'Anakapalli', 'Anantapur', 'Annamayya', 'Bapatla',
            'Chittoor', 'Dr. B.R. Ambedkar Konaseema', 'East Godavari', 'Eluru', 'Guntur',
            'Kakinada', 'Krishna', 'Kurnool', 'Nandyal', 'NTR', 'Palnadu',
            'Parvathipuram Manyam', 'Prakasam', 'SPSR Nellore', 'Srikakulam', 'Tirupati',
            'Visakhapatnam', 'Vizianagaram', 'West Godavari', 'YSR Kadapa',
        ],
        'Telangana' => [
            'Adilabad', 'Bhadradri Kothagudem', 'Hanamkonda', 'Hyderabad', 'Jagtial',
            'Jangaon', 'Jayashankar Bhupalpally', 'Jogulamba Gadwal', 'Kamareddy', 'Karimnagar',
            'Khammam', 'Komaram Bheem Asifabad', 'Mahabubabad', 'Mahabubnagar', 'Mancherial',
            'Medak', 'Medchal–Malkajgiri', 'Mulugu', 'Nagarkurnool', 'Nalgonda', 'Narayanpet',
            'Nirmal', 'Nizamabad', 'Peddapalli', 'Rajanna Sircilla', 'Ranga Reddy', 'Sangareddy',
            'Siddipet', 'Suryapet', 'Vikarabad', 'Wanaparthy', 'Warangal', 'Yadadri Bhuvanagiri',
        ],
    ];
    ?>
    <script>
    (function () {
        var STATE_FIELD_ID    = <?= $state_field_id; ?>;
        var DISTRICT_FIELD_ID = <?= $district_field_id; ?>;
        var STATE_DISTRICTS   = <?= json_encode($state_districts); ?>;

        var STATE_SELECTOR    = '#lead_form select[data-fieldto="leads"][data-fieldid="' + STATE_FIELD_ID + '"]';
        var DISTRICT_SELECTOR = 'select[data-fieldto="leads"][data-fieldid="' + DISTRICT_FIELD_ID + '"]';

        function populateDistricts($state, keepExistingValue) {
            var $district = $state.closest('form').find(DISTRICT_SELECTOR);
            if (!$district.length) {
                return;
            }

            var existingValue = keepExistingValue ? $district.val() : null;
            var districts = STATE_DISTRICTS[$state.val()] || [];

            $district.empty().append($('<option>', { value: '' }));
            $.each(districts, function (i, name) {
                $district.append($('<option>', {
                    value: name,
                    text: name,
                    selected: existingValue === name,
                }));
            });

            $district.prop('disabled', districts.length === 0);

            if (typeof $district.selectpicker === 'function') {
                $district.selectpicker('refresh');
            }
        }

        // Initial population - Add: state empty -> District stays
        // disabled/empty. Edit: state already has a saved value ->
        // District populates with the previously-saved district kept
        // selected.
        $(document).on('loaded.bs.select', STATE_SELECTOR, function () {
            populateDistricts($(this), true);
        });

        // User changes State manually - District resets, per spec a
        // state change must clear any previously chosen district rather
        // than carry over a value that belongs to the old state.
        $(document).on('changed.bs.select change', STATE_SELECTOR, function () {
            populateDistricts($(this), false);
        });
    })();
    </script>
    <?php
}

/**
 * get_relation_data filter handler for the 'follow_up_case' rel_type -
 * resolves a reminder's rel_id (a Case id, not a Lead id - see
 * Follow_ups_model::sync_reminder()) to the Case row.
 *
 * @param mixed $data
 * @param array $args ['type', 'rel_id', 'extra']
 * @return mixed
 */
function follow_up_management_relation_data($data, $args)
{
    if ($args['type'] !== 'follow_up_case') {
        return $data;
    }

    $CI = &get_instance();
    $CI->load->model('follow_up_management/follow_ups_model');

    $case = $CI->follow_ups_model->get($args['rel_id']);

    return $case ?: $data;
}

/**
 * relation_values filter handler for 'follow_up_case' - resolves the
 * display name (the underlying lead's name, via the existing 'lead'
 * resolution - not duplicated) and, critically, the link: the Case Detail
 * page, not the Lead. This is what makes every calendar/notification
 * surface that goes through get_relation_data()/get_relation_values() (core
 * Cron_model::staff_reminders() included) open the Case, satisfying "each
 * calendar item must open the related Case Detail page."
 *
 * @param array $values
 * @return array
 */
function follow_up_management_relation_values($values)
{
    if (($values['type'] ?? '') !== 'follow_up_case' || empty($values['relation'])) {
        return $values;
    }

    $case    = $values['relation'];
    $case_id = is_array($case) ? $case['id'] : $case->id;

    $rel_data   = get_relation_data($case->rel_type, $case->rel_id);
    $rel_values = get_relation_values($rel_data, $case->rel_type);

    $values['id']   = $case_id;
    $values['name'] = $rel_values['name'];
    $values['link'] = admin_url('follow_up_management/view/' . $case_id);

    return $values;
}

/**
 * Registers the shared Follow_up_case_notification email template
 * (modules/follow_up_management/libraries/mails/Follow_up_case_notification.php) -
 * idempotent (create_email_template() no-ops if the slug already exists),
 * so calling it from both an activation hook and migration 357 is safe.
 */
function follow_up_management_register_email_template()
{
    create_email_template(
        '{case_event_title} - {companyname}',
        '<p>Hello,</p><p>{case_event_description}</p><p><a href="{case_link}">' . _l('followup_case') . '</a></p>',
        'follow_up_management',
        _l('followup_email_template_name'),
        'follow_up_case_notification'
    );
}

/**
 * "Reminder Overdue" trigger - runs inside the existing cron pass
 * (register_cron_task() above = after_cron_run, see this function's
 * registration comment) rather than a new scheduled task. Finds open cases
 * whose next_follow_up_date has passed, notifies the assigned staff exactly
 * once per case using tblfollowup_activity as the idempotency ledger (a
 * 'reminder_overdue_notified' entry marks a case as already notified, reusing
 * the existing Activity Log table instead of adding new schema).
 */
function follow_up_management_check_overdue_cases()
{
    $CI = &get_instance();
    $CI->load->model('follow_up_management/follow_ups_model');
    follow_up_management_load_language();

    $CI->db->where('case_status', 'open');
    $CI->db->where('next_follow_up_date IS NOT NULL', null, false);
    $CI->db->where('next_follow_up_date <', date('Y-m-d H:i:s'));
    $overdue_cases = $CI->db->get(db_prefix() . 'followups')->result();

    foreach ($overdue_cases as $case) {
        $CI->db->where('followup_id', $case->id);
        $CI->db->where('action_type', 'reminder_overdue_notified');
        $CI->db->where('DATE(created_at) = "' . date('Y-m-d', strtotime($case->next_follow_up_date)) . '"', null, false);
        $already_notified = $CI->db->get(db_prefix() . 'followup_activity')->row();

        if ($already_notified) {
            continue;
        }

        $CI->follow_ups_model->notify_overdue_case($case);
    }
}

/**
 * "Move missed follow-ups to tomorrow" - runs inside the same existing cron
 * pass as follow_up_management_check_overdue_cases() above (register_cron_task()
 * = after_cron_run), once per day. Finds open cases whose Next Follow-up
 * Date is today and that were never genuinely actioned today, then moves
 * each to tomorrow via Follow_ups_model::forward_case_to_tomorrow().
 *
 * "Actioned today" is read from tblfollowup_history (written exclusively by
 * add_history_entry(), called from log_case_interaction()/change_case_status()/
 * reschedule_case()) rather than the Case's own last_activity_at column,
 * because sync_case_from_lead() (fired on every unrelated Lead field edit
 * via after_lead_updated) can also bump last_activity_at without any real
 * Telecaller action - tblfollowup_history only ever grows from a genuine
 * interaction, so a NOT EXISTS against it is the reliable signal here. This
 * intentionally reuses a different table than the overdue-notification
 * idempotency check above (tblfollowup_activity, an internal system log),
 * which answers a different question ("was this case already notified
 * today") - not a duplicate mechanism, just the correct table for each
 * question.
 *
 * If a case gets forwarded, its own new 'auto_forwarded' history row means
 * a second run later the same day (e.g. after a manual "Run Cron Manually")
 * will no longer match next_follow_up_date = today, so this is naturally
 * idempotent without a separate guard.
 */
function follow_up_management_forward_missed_followups()
{
    $CI = &get_instance();
    $CI->load->model('follow_up_management/follow_ups_model');
    follow_up_management_load_language();

    $today = date('Y-m-d');

    $CI->db->where('case_status', 'open');
    $CI->db->where('rel_type', 'lead');
    $CI->db->where('next_follow_up_date IS NOT NULL', null, false);
        $CI->db->where('next_follow_up_date >=', $today . ' 00:00:00');
    $CI->db->where('next_follow_up_date <', date('Y-m-d', strtotime($today . ' +1 day')) . ' 00:00:00');
    $CI->db->where(
        'NOT EXISTS (SELECT 1 FROM ' . db_prefix() . 'followup_history h
            WHERE h.followup_id = ' . db_prefix() . "followups.id
            AND h.created_at >= \"{$today} 00:00:00\"
            AND h.created_at < \"" . date('Y-m-d', strtotime($today . ' +1 day')) . " 00:00:00\"
            AND h.activity_type IN ('call', 'meeting', 'reminder', 'note', 'status_change'))",
        null,
        false
    );
    $missed_cases = $CI->db->get(db_prefix() . 'followups')->result();

    foreach ($missed_cases as $case) {
        $CI->follow_ups_model->forward_case_to_tomorrow($case);
    }
}

/**
 * Fires from Leads_model::update() and Leads_model::update_lead_status()
 * (kanban drag-drop) - both already call
 * hooks()->do_action('lead_status_changed', ['lead_id', 'old_status', 'new_status']).
 * This is the sole integration point with Leads: create a Case when status
 * becomes "Follow-up". Nothing else - no UI, no tab, no JS, no menu change.
 *
 * Known core inconsistency (verified by reading both call sites): the
 * update() path passes old_status/new_status as status IDs, while
 * update_lead_status() passes them as status NAME strings. Normalized below
 * so both call sites are handled identically.
 *
 * Rule 6: also the sole integration point for completing a Case (and its
 * linked Task) when the same lead transitions to "Converted" - matched by
 * status name the same way "Follow-up" already is, since Lead statuses are
 * admin-configurable rows (tblleads_status) with no stable id/constant to
 * hardcode against.
 *
 * @param array $data ['lead_id', 'old_status', 'new_status']
 */
function follow_up_management_on_lead_status_changed($data)
{
    if (empty($data['lead_id']) || !isset($data['new_status'])) {
        return;
    }

    $new_status_name = strtolower(follow_up_management_resolve_lead_status_name($data['new_status']));

    $CI = &get_instance();
    $CI->load->model('follow_up_management/follow_ups_model');

    if ($new_status_name === 'follow-up' || $new_status_name === 'interested') {
        // Both statuses keep a Case open and awaiting the next follow-up -
        // create_case_for_lead() is dedup-guarded, so a lead moving
        // Follow-up -> Interested (case already open) is a no-op here.
        $CI->follow_ups_model->create_case_for_lead($data['lead_id']);
    } elseif ($new_status_name === 'customer confirmed') {
        // Close/complete/cancel first (same reuse as 'lost' below), then
        // execute the canonical conversion - see auto_convert_confirmed_lead()
        // for why this isn't a second conversion implementation.
        $CI->follow_ups_model->complete_case_for_lead($data['lead_id']);
        $CI->follow_ups_model->auto_convert_confirmed_lead($data['lead_id']);
    } elseif ($new_status_name === 'lost') {
        $CI->follow_ups_model->complete_case_for_lead($data['lead_id']);

        // Admin notification - Lead Lost. Fires regardless of which
        // portal set the status (Field Executive's own Mark Lost, or a
        // Telecaller's Not Interested outcome via save_workspace()) since
        // both funnel through the same core update_lead_status() call
        // that triggers this dispatcher.
        $CI->db->select('name');
        $CI->db->where('id', $data['lead_id']);
        $lead = $CI->db->get(db_prefix() . 'leads')->row();

        lead_workspace_notify_admins(
            'not_lead_lost_admin_notification',
            'field_portal/lead_details/' . $data['lead_id'],
            [$lead ? $lead->name : '', get_staff_full_name()]
        );
    }
}

/**
 * Rule 6, second trigger - see the hook registration's comment above for
 * why this is needed alongside follow_up_management_on_lead_status_changed().
 * Reuses the exact same complete_case_for_lead() (idempotent - it's a
 * no-op if there's no open Case for this lead, e.g. it never entered
 * Follow-up status at all).
 *
 * @param array $data ['lead_id', 'customer_id']
 */
function follow_up_management_on_lead_converted_to_customer($data)
{
    if (empty($data['lead_id'])) {
        return;
    }

    $CI = &get_instance();
    $CI->load->model('follow_up_management/follow_ups_model');
    $CI->follow_ups_model->complete_case_for_lead($data['lead_id']);
}

/**
 * Lead -> Case -> Task synchronization. Fires on every Lead save (core
 * Leads_model.php's after_lead_updated), not just a status transition -
 * keeps an already-existing open Case, and its linked Task, matching the
 * Lead's current assigned staff, "Next Follow-up Date" custom field, and
 * department whenever staff edit the Lead again without changing status.
 * Does nothing if no open Case exists yet for this lead; never creates
 * one - that remains follow_up_management_on_lead_status_changed()'s job.
 *
 * @param mixed $lead_id
 */
function follow_up_management_on_lead_updated($lead_id)
{
    if (empty($lead_id)) {
        return;
    }

    $CI = &get_instance();
    $CI->load->model('follow_up_management/follow_ups_model');
    $CI->follow_ups_model->sync_case_from_lead($lead_id);
}

/**
 * Normalizes a lead_status_changed payload value (sometimes a status ID,
 * sometimes a status name string, depending on which core code path fired
 * the hook) into a plain status name for comparison.
 *
 * @param mixed $status
 * @return string
 */
function follow_up_management_resolve_lead_status_name($status)
{
    if (is_numeric($status)) {
        $CI = &get_instance();
        $CI->load->model('leads_model');
        $status_obj = $CI->leads_model->get_status($status);

        return $status_obj ? $status_obj->name : '';
    }

    return (string) $status;
}

/**
 * Telecaller Workspace - get_task filter callback. Fires from
 * Tasks_model::get() on EVERY task load in the whole app, so the
 * rel_type check is a cheap in-memory early-exit before the one indexed
 * tblfollowups.task_id lookup even runs (get_case_by_task_id(), migration
 * 359). Attaches null for every ordinary task - the render callback below
 * treats that as "not a Follow-up Task" and no-ops.
 *
 * @param object|null $task
 * @return object|null
 */
function follow_up_management_attach_case_to_task($task)
{
    if (!$task || $task->rel_type !== 'lead') {
        return $task;
    }

    $CI = &get_instance();
    $CI->load->model('follow_up_management/follow_ups_model');

    $task->followup_case = $CI->follow_ups_model->get_case_by_task_id($task->id);

    return $task;
}

/**
 * Telecaller Workspace - before_task_description_section callback
 * (view_task_template.php's own action hook, fired inside the Task
 * modal's body, above the Description heading). No-ops for any task
 * follow_up_management_attach_case_to_task() didn't attach a Case to.
 *
 * Assembles the full workspace bundle here (not in the get_task filter
 * above) so the extra Lead/tags/custom-fields/reminder/history queries
 * only ever run when a Follow-up Task's modal is actually being
 * rendered, not on every task load in the app.
 *
 * @param object $task
 * @return void
 */
function follow_up_management_render_task_workspace($task)
{
    if (empty($task->followup_case)) {
        return;
    }

    $CI = &get_instance();
    $CI->load->model('follow_up_management/follow_ups_model');

    $case    = $task->followup_case;
    $bundle  = $CI->follow_ups_model->get_task_workspace_data($case);
    $history = $CI->follow_ups_model->get_history($case->id);

    // Same relation-resolution case.php's own "Open Lead" link already
    // uses - reused rather than hand-building the Lead URL a second way.
    $rel_data   = get_relation_data($case->rel_type, $case->rel_id);
    $rel_values = get_relation_values($rel_data, $case->rel_type);

    // Lead Status dropdown (simplified Workspace, item 6) - the exact
    // same core lookup the Telecaller queue page's status dropdown
    // already uses (Leads_model::get_status()), not a second list.
    $CI->load->model('leads_model');
    $statuses = $CI->leads_model->get_status();

    $data = $bundle + [
        'case'      => $case,
        'task'      => $task,
        'history'   => $history,
        'rel_link'  => $rel_values['link'],
        'rel_name'  => $rel_values['name'],
        'statuses'  => $statuses,
    ];

    echo $CI->load->view('follow_up_management/task_workspace', $data, true);
}

/**
 * Telecaller Workspace - footer script. Same app_admin_footer injection
 * pattern as follow_up_management_calendar_filter_script() /
 * follow_up_management_lead_followup_date_toggle_script() above, but
 * uses event delegation on document (rather than an
 * element-exists-on-page-load guard) since the workspace's markup is
 * injected asynchronously into the Task modal well after this footer
 * script has already run.
 *
 * Reuses the exact withCsrf()/handleActionResponse() shape
 * case.php's own inline script already established for these same
 * hand-written <form> tags (CI's csrf_jquery_token() auto-attach doesn't
 * reach a pre-serialized string payload) - the one difference is the
 * success behavior: case.php reloads the whole page, this refreshes only
 * the Task modal in place via init_task_modal(), since a full reload
 * would close it.
 *
 * @return void
 */
function follow_up_management_task_workspace_script()
{
    ?>
    <script>
    (function () {
        function withCsrf(serializedData) {
            if (typeof csrfData === 'undefined') {
                return serializedData;
            }
            var token = encodeURIComponent(csrfData.token_name) + '=' + encodeURIComponent(csrfData.hash);
            return serializedData ? serializedData + '&' + token : token;
        }

        function refreshWorkspace($el) {
            var taskId = $el.closest('.followup-task-workspace').data('task-id');
            if (taskId && typeof init_task_modal === 'function') {
                init_task_modal(taskId);
            }
        }

        function handleActionResponse(promise, $el) {
            promise.done(function (response) {
                response = (typeof response === 'string') ? JSON.parse(response) : response;
                if (response.message) {
                    alert_float(response.alert_type, response.message);
                }
                setTimeout(function () {
                    refreshWorkspace($el);
                }, 500);
            }).fail(function () {
                alert_float('danger', '<?= _l('case_action_failed'); ?>');
            });
        }

        $(document).on('submit', '.followup-workspace-action-form', function (e) {
            e.preventDefault();
            var $form = $(this);
            $form.closest('.modal').modal('hide');
            handleActionResponse($.post($form.data('url'), withCsrf($form.serialize())), $form);
        });

        $(document).on('click', '.followup-workspace-status-btn', function (e) {
            e.preventDefault();
            var $btn = $(this);
            var confirmMsg = $btn.data('confirm');
            if (confirmMsg && !confirm(confirmMsg)) {
                return;
            }
            handleActionResponse($.post($btn.data('url'), withCsrf('status=' + encodeURIComponent($btn.data('status')))), $btn);
        });

        // The workspace's date/datetime fields (Section 5 Next Follow-up,
        // Section 7 Appointment) are injected long after the page's own
        // initial init_datepicker() call (main.js) already ran, exactly
        // the same "AJAX-loaded content needs its own datepicker init"
        // situation main.js itself re-solves in several other places
        // (e.g. after a task/invoice/proposal modal loads). Scoped to the
        // Task modal's own shown event, which fires every time
        // _task_append_html() re-shows it (initial open AND every
        // post-action refresh via init_task_modal()) - both inline
        // sections' pickers, not a nested modal, since neither is a
        // modal anymore.
        $('#task-modal').on('shown.bs.modal', function () {
            if (typeof init_datepicker === 'function') {
                init_datepicker();
            }
        });

        // Lead Status dropdown (Workspace item 6) - purely a UX hint,
        // showing/hiding Next Follow-up Date and Lost Reason based on the
        // selected status; save_workspace() enforces the actual
        // requiredness server-side regardless of this toggle.
        function toggleWorkspaceConditionalFields($select) {
            var name = ($select.find('option:selected').data('name') || '').toString();
            var $form = $select.closest('form');
            $form.find('.workspace-next-date-wrap').toggle(name === 'follow-up' || name === 'interested');
            $form.find('.workspace-lost-reason-wrap').toggle(name === 'lost');
        }

        $(document).on('change', '.workspace-lead-status-select', function () {
            toggleWorkspaceConditionalFields($(this));
        });

        $('#task-modal').on('shown.bs.modal', function () {
            $('.workspace-lead-status-select').each(function () {
                toggleWorkspaceConditionalFields($(this));
            });
        });

        // Quick Actions "Copy Number" - this class already exists on the
        // core Leads page and the Telecaller queue's phone dropdown with
        // no handler anywhere in the app (confirmed: neither assets/js/
        // main.js nor any other file wires it up) - this single delegated
        // handler makes all three surfaces work, not just the Workspace.
        $(document).on('click', '.lead-phone-copy', function (e) {
            e.preventDefault();
            var phone = $(this).data('phone');
            if (!phone || typeof navigator === 'undefined' || !navigator.clipboard) {
                return;
            }
            navigator.clipboard.writeText(String(phone)).then(function () {
                if (typeof alert_float === 'function') {
                    alert_float('success', '<?= _l('followup_workspace_copied'); ?>');
                }
            });
        });
    })();
    </script>
    <?php
}

/**
 * Dashboard "My Tasks" tab replacement for a plain Telecaller - see the
 * hook registration's comment for why this is a client-side swap rather
 * than a core file edit: "My Tasks" isn't its own registered dashboard
 * widget (application/helpers/widgets_helper.php's get_dashboard_widgets()),
 * it's one tab fused inside the single shared core
 * admin/dashboard/widgets/user_data.php view alongside Projects/
 * Reminders/Tickets/Announcements/Activity Log, with no hook that wraps
 * or replaces just that one tab. Self-guards exactly like this module's
 * other footer scripts - a no-op on every page except the dashboard,
 * and even there, only for a plain Telecaller (Admins/Managers keep the
 * native My Tasks tab).
 *
 * Root-cause fix: this used to skip the staff_cant('view', 'leads')
 * check that every other Telecaller-facing entry point in this module
 * requires (my_follow_ups_widget() below - the AJAX endpoint that fills
 * in the tab this swaps in - has always required it). A real Telecalling
 * department member who exists in the department but hasn't (yet) been
 * granted the native "Leads -> View" staff permission would still get
 * this tab swapped in here, then watch its content request fail with a
 * 401 every time - a broken/permanently-loading tab instead of the tab
 * simply not appearing, which is how every other gate in this module
 * already degrades for that same staff member (the sidebar item itself,
 * my_follow_ups(), dashboard(), etc. all already require this same
 * capability). Matching the guard here keeps the Dashboard consistent
 * with the rest of the module instead of exposing a dead tab.
 *
 * @return void
 */
function follow_up_management_dashboard_tab_swap_script()
{
    if (!is_staff_member() || is_admin() || staff_can('view_department', 'follow_up_management') || !follow_up_management_is_telecalling_department_member() || staff_cant('view', 'leads')) {
        return;
    }
    ?>
    <script>
    (function () {
        var tasksTabLink = document.querySelector('#widget-user_data a[href="#home_tab_tasks"]');
        var tasksTabPane  = document.getElementById('home_tab_tasks');

        if (!tasksTabLink || !tasksTabPane) {
            return;
        }

        tasksTabLink.innerHTML = '<i class="fa-solid fa-headset menu-icon"></i> <?= _l('my_follow_ups'); ?>';

        requestGet('follow_up_management/my_follow_ups_widget').done(function (response) {
            tasksTabPane.innerHTML = response;
        });
    })();
    </script>
    <?php
}

/**
 * Native Dashboard Projects-related widget hide for a plain Telecaller -
 * "Telecallers never use Projects." Same no-core-edit constraint as the
 * tab swap above: confirmed by reading widgets_helper.php's
 * get_dashboard_widgets()/render_dashboard_widgets() in full that
 * neither the "Projects In Progress" KPI card nor the "My Projects" tab
 * is its own registered widget - both are hardcoded pieces fused inside
 * shared multi-card/multi-tab widget files (top_stats.php, user_data.php
 * respectively, the same file the tab swap above already targets) with
 * no permission gate of their own (confirmed live: a Telecaller with
 * zero 'projects' capability still sees both, just scoped to empty
 * data) - so a get_dashboard_widgets filter can't remove just these
 * pieces without also removing sibling cards/tabs (Tasks/Reminders/
 * Tickets/Announcements/Leads-Converted) this role must keep. The one
 * exception, "Statistics by Project Status", genuinely is its own
 * standalone widget file (widgets/projects_chart.php, id
 * "widget-projects_chart") and could in principle be removed via that
 * filter instead - handled here anyway, in the same footer script as
 * the other two, so all three Projects-hides share one convention
 * instead of splitting across two different hook mechanisms. Same
 * three-tier guard as every other role-gated script in this file -
 * Admin/Manager dashboards are completely untouched.
 *
 * @return void
 */
function follow_up_management_hide_project_widgets_script()
{
    if (!is_staff_member() || is_admin() || staff_can('view_department', 'follow_up_management') || !follow_up_management_is_telecalling_department_member()) {
        return;
    }
    ?>
    <script>
    (function () {
        // "Projects In Progress" KPI card - one of four fused into the
        // single #widget-top_stats file; Invoices/Leads/Tasks stay.
        var projectsCard = document.querySelector('#widget-top_stats .quick-stats-projects');
        if (projectsCard) {
            projectsCard.style.display = 'none';
        }

        // "My Projects" tab - one of six fused into #widget-user_data
        // (the same file/id the Dashboard tab swap above already
        // targets); Reminders/Tickets/Announcements/Activity Log stay.
        var projectsTabLink = document.querySelector('#widget-user_data a[href="#home_my_projects"]');
        var projectsTabPane = document.getElementById('home_my_projects');
        if (projectsTabLink && projectsTabLink.closest('li')) {
            projectsTabLink.closest('li').style.display = 'none';
        }
        if (projectsTabPane) {
            projectsTabPane.style.display = 'none';
        }

        // "Statistics by Project Status" - its own standalone widget;
        // the adjacent "Leads Overview" widget (#widget-leads_chart) is
        // a distinct sibling and is deliberately left alone.
        var projectsChart = document.getElementById('widget-projects_chart');
        if (projectsChart) {
            projectsChart.style.display = 'none';
        }
    })();
    </script>
    <?php
}

/**
 * Fixes a real, confirmed bug (not a UX enhancement) on the My Follow-ups
 * page: the inline Lead Status dropdown calls core's global
 * lead_mark_as(status_id, lead_id) (assets/js/main.js), whose only
 * post-success action is `table_leads.DataTable().ajax.reload(null,
 * false)` - but `table_leads` is a page-global bound only to
 * `table.table-leads` (core's own Leads list), which doesn't exist on
 * this page (the table's class is `table-my-follow-ups`), so that
 * reload call silently targets nothing (confirmed live: DataTables'
 * `.DataTable()` accessor on a zero-length jQuery selection returns an
 * API bound to no tables, so `.ajax.reload()` on it is a harmless no-op -
 * not an exception, just nothing happening).
 *
 * First attempt (superseded, see history) delegated a click listener on
 * the `onclick="lead_mark_as("` anchors and reloaded after a hardcoded
 * `setTimeout(..., 500)`. Live testing proved that timer races against
 * the real `update_lead_status` AJAX call: the delay is a guess,
 * decoupled from when the request actually finishes, so on a slower
 * response the reload could fire (and settle) before the server had
 * committed the change - reloading with the still-old data and then
 * doing nothing further, which looks identical to "reload never ran."
 *
 * Fixed version: listen for the exact `update_lead_status` XHR to finish
 * via jQuery's own `$(document).ajaxComplete()` global hook, filtered to
 * that URL. This reuses jQuery's own request-completion signal instead
 * of guessing a delay - the reload can only fire after the server has
 * actually finished the write, for any request to that URL regardless of
 * what triggered it (this page's own dropdown, or core's, since both
 * call the identical lead_mark_as()/update_lead_status() endpoint). No
 * new AJAX endpoint, no change to update_lead_status()/lead_status_changed
 * sync - reuses the exact same `.ajax.reload()` idiom already proven in
 * modules/follow_up_management/views/my_cases.php and all_follow_ups.php's
 * own filter-change handlers, just triggered by the correct event.
 * Not role-gated: this is a plain bug fix for whoever uses this page,
 * not a Telecaller-only UX simplification.
 *
 * Second, identical bug found alongside the "Latest Note" column
 * (modules/follow_up_management/views/my_follow_ups.php +
 * application/views/admin/tables/my_follow_ups.php): "Options -> Notes"
 * (a plain <a href> to follow_up_management/view/<id>?action=add_note)
 * navigates away to the Case Detail page, so the next time a user is
 * back on My Follow-ups it's a fresh page load with fresh data - looks
 * "immediate" but is really just a normal reload. "Actions -> Open
 * Workspace" is different: init_task_modal() opens the Task Workspace
 * as a modal OVERLAID on this same, still-loaded My Follow-ups page (no
 * navigation), and its save handler
 * ($(document).on('submit', '.followup-workspace-action-form', ...)
 * a few hundred lines above in this file) posts to
 * follow_up_management/save_workspace/<id> via plain $.post() - which,
 * same as any jQuery AJAX call, still fires the global ajaxComplete
 * event this script already listens on. So the underlying
 * .table-my-follow-ups DataTable was simply never told to reload after
 * that specific URL's request completes; nothing about save_workspace()
 * itself (or the log_case_interaction()/add_history_entry() write path
 * it calls) was broken or duplicated here - only the URL match below
 * was extended to also cover it.
 *
 * @return void
 */
function follow_up_management_my_follow_ups_status_refresh_script()
{
    ?>
    <script>
    (function () {
        if (!document.querySelector('.table-my-follow-ups')) {
            return;
        }

        var reloadUrlPatterns = ['leads/update_lead_status', 'follow_up_management/save_workspace'];

        $(document).on('ajaxComplete', function (event, xhr, settings) {
            var url = settings.url || '';
            var matched = reloadUrlPatterns.some(function (pattern) {
                return url.indexOf(pattern) !== -1;
            });

            if (!matched) {
                return;
            }

            var $table = $('.table-my-follow-ups');
            if ($table.length && $.fn.DataTable.isDataTable($table[0])) {
                $table.DataTable().ajax.reload(null, false);
            }
        });
    })();
    </script>
    <?php
}

/**
 * sidebar_menu_items filter (App_menu::get()) - rewrites the native
 * 'tasks' top-level item's href/name in place for a plain Telecaller,
 * same three-tier guard as the Dashboard tab swap above (plus the same
 * staff_cant('view', 'leads') addition - see that function's docblock
 * for the root cause: without it, a real Telecalling department member
 * who hasn't yet been granted the native Leads capability would have
 * their working native "Tasks" link silently rewritten to point at
 * my_follow_ups(), which denies them - trading a page that worked for
 * one that doesn't, for someone this module is not yet able to serve).
 * $items is keyed by slug with each value an item array ('name'/'href'/
 * 'icon'/... - 'children' is computed separately by get_child(), not
 * present here), exactly what App_menu::add_sidebar_menu_item() stored -
 * confirmed by reading App_menu.php in full. Every other sidebar item
 * (including this module's own 'follow_up_management' item) passes
 * through untouched.
 *
 * @param array $items
 * @return array
 */
function follow_up_management_redirect_tasks_sidebar_item($items)
{
    if (!is_staff_member() || is_admin() || staff_can('view_department', 'follow_up_management') || !follow_up_management_is_telecalling_department_member() || staff_cant('view', 'leads')) {
        return $items;
    }

    if (isset($items['tasks'])) {
        $items['tasks']['href'] = admin_url('follow_up_management/my_follow_ups');
        $items['tasks']['name'] = _l('my_follow_ups');
        $items['tasks']['icon'] = 'fa-solid fa-headset';
        // Repositioned (was core's own 'tasks' position, 35 - right after
        // Projects) to sit after Leads(45) and before the synthetic
        // "Follow-up History" item this role also gets (see
        // follow_up_management_hide_menus_for_telecaller(), position 48) -
        // "Dashboard, Support, Leads, My Follow-ups, Follow-up History,
        // Utilities(55)".
        $items['tasks']['position'] = 47;
    }

    return $items;
}

/**
 * Phase 6: Registers the "Origin" tab on the customer profile page.
 */
function follow_up_management_customer_origin_tab()
{
    if (!is_admin() && !staff_can('view', 'customers')) {
        return;
    }

    $CI = &get_instance();
    $CI->app_tabs->add_customer_profile_tab('origin', [
        'name'     => _l('followup_customer_origin'),
        'icon'     => 'fa fa-diagram-project',
        'view'     => 'follow_up_management/customer_origin_tab',
        'position' => 55,
    ]);
}

/**
 * Phase 6: Notifies all Admin users when a Lead is converted to Customer.
 */
function follow_up_management_notify_admin_conversion($data)
{
    $lead_id     = $data['lead_id'] ?? 0;
    $customer_id = $data['customer_id'] ?? 0;
    if (!$lead_id) { return; }

    $CI = &get_instance();
    $CI->db->select('name');
    $CI->db->where('id', $lead_id);
    $lead = $CI->db->get(db_prefix() . 'leads')->row();

    lead_workspace_notify_admins(
        'not_admin_lead_converted',
        'clients/client/' . $customer_id,
        [$lead ? $lead->name : '', get_staff_full_name()]
    );
}

/**
 * Phase 6: Highlights overdue follow-ups in the Admin footer via a badge
 * on the sidebar. Self-guarding: only runs for Admin/Manager.
 */
function follow_up_management_notify_admin_overdue_followup_script()
{
    if (!is_admin() && !staff_can('view_department', 'follow_up_management')) {
        return;
    }

    $CI = &get_instance();
    $CI->load->model('follow_up_management/follow_ups_model');
    $counts = $CI->follow_ups_model->get_case_counts();
    $overdue = (int) ($counts['overdue_cases'] ?? 0);
    ?>
    <script>
    (function () {
        var overdue = <?= $overdue; ?>;
        if (overdue > 0) {
            var link = document.querySelector('a[href*="follow_up_management/followups_filtered/overdue"]');
            if (link) {
                var badge = document.createElement('span');
                badge.className = 'badge bg-danger pull-right';
                badge.textContent = overdue;
                link.appendChild(badge);
            }
        }
    })();
    </script>
    <?php
}

/**
 * Phase 6: Notifies Admin when a lead is marked High Priority.
 */
function follow_up_management_notify_admin_high_priority_lead($lead_id)
{
    if (!$lead_id) { return; }

    $CI = &get_instance();
    $CI->load->model('follow_up_management/follow_ups_model');

    $CI->db->select('name');
    $CI->db->where('id', $lead_id);
    $lead = $CI->db->get(db_prefix() . 'leads')->row();
    if (!$lead) { return; }

    $CI->db->where('rel_id', $lead_id);
    $CI->db->where("rel_type = 'lead'");
    $CI->db->where('priority', 'high');
    $case = $CI->db->get(db_prefix() . 'followups')->row();
    $is_high = (bool) $case;

    if (!$is_high) {
        $priority_field = get_custom_fields('leads', ['name' => 'Priority']);
        if (!empty($priority_field)) {
            $CI->db->select('value');
            $CI->db->where('relid', $lead_id);
            $CI->db->where('fieldto', 'leads');
            $CI->db->where('fieldid', (int) $priority_field[0]['id']);
            $val = $CI->db->get(db_prefix() . 'customfieldsvalues')->row();
            if ($val && strtolower($val->value) === 'high') {
                $is_high = true;
            }
        }
    }

    if ($is_high) {
        lead_workspace_notify_admins(
            'not_admin_high_priority_lead',
            'follow_up_management/lead_details/' . $lead_id,
            [$lead->name, get_staff_full_name()]
        );
    }
}
