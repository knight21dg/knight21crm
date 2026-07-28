<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Daily Work Update
Description: Shared, company-wide Daily Work Update feature for the 9 departments in scope (CEO, Digital Marketing, Field Executing, Telecalling, SEO, Web Development, App Development, Testing, UI/UX Design) - one controller/model/view, reused by every department portal rather than duplicated per department. Digital Marketing, Web Development/App Development/Testing/UI-UX Design, and Field Executing reach it via their own portal's sidebar (modules/dm_portal, modules/dev_portal, modules/field_portal); this module registers its own top-level sidebar link for the remaining departments (CEO, Telecalling, SEO), which have no portal of their own.
Version: 1.0.0
Requires at least: 3.6.3
*/

require_once __DIR__ . '/helpers/daily_work_update_helper.php';

hooks()->add_action('admin_init', 'daily_work_update_module_init_menu_item');

// Daily Work Update attachments - extends the real, sanctioned
// get_upload_path_by_type() filter (application/helpers/upload_helper.php)
// with the 'dm_work_update' type - the same type key and physical
// uploads/dm_work_updates/ folder modules/dm_portal originally
// registered, kept unchanged since it's an internal key never shown to
// users. This module is now the sole owner of this registration -
// dm_portal's own copy was removed once it started reusing this
// controller for uploads instead of its own.
hooks()->add_filter('get_upload_path_by_type', 'daily_work_update_upload_path', 10, 2);

/**
 * Top-level "Daily Work Update" sidebar link for staff in one of the 9
 * target departments who have no portal of their own to carry this link
 * (CEO/Field Executing/Telecalling/SEO - see
 * is_daily_work_update_menu_eligible_staff()'s own docblock). A single,
 * childless, directly-clickable item - not a portal shell: no native
 * menus are hidden for this audience, they keep their normal Perfex
 * navigation plus this one extra link, unlike dm_portal/dev_portal staff.
 */
function daily_work_update_module_init_menu_item()
{
    $CI = &get_instance();

    if (!is_daily_work_update_menu_eligible_staff()) {
        return;
    }

    // admin_init fires on every admin page, not just this module's own
    // controller - the sidebar renders on every page, so the language
    // file has to load right here or _l() below resolves to the raw key
    // anywhere else.
    $CI->lang->load('daily_work_update/daily_work_update', 'english');

    $CI->app_menu->add_sidebar_menu_item('daily_work_update', [
        'name'     => _l('daily_work_update_title'),
        'href'     => admin_url('daily_work_update'),
        'icon'     => 'fa-solid fa-calendar-check',
        'position' => 67,
        'badge'    => [],
    ]);
}

/**
 * get_upload_path_by_type filter - see docblock above for why this
 * mirrors dm_portal_work_update_upload_path() exactly (same type key,
 * same physical folder).
 *
 * @param  string $path
 * @param  string $type
 * @return string
 */
function daily_work_update_upload_path($path, $type)
{
    if ($type !== 'dm_work_update') {
        return $path;
    }

    $upload_path = FCPATH . 'uploads/dm_work_updates/';

    if (!file_exists($upload_path)) {
        mkdir($upload_path, 0755, true);
    }

    return $upload_path;
}
