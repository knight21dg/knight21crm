<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Field Executive Portal - a work portal for the single "Field Executing"
 * business department (modules/business_departments), gated by
 * department membership rather than a permission capability - same
 * pattern modules/dm_portal already established for its own single
 * department.
 */

/**
 * Name must match tblbusiness_departments.name exactly (already exists
 * live, id 3, created by migration 352 alongside CEO/Digital Marketing/
 * Telecalling/SEO/Web Development/App Development/Testing/UI-UX Design).
 *
 * @return string
 */
function field_portal_department_name()
{
    return 'Field Executing';
}

/**
 * Resolves field_portal_department_name() to its live
 * tblbusiness_departments id - looked up by name rather than hardcoded,
 * same reasoning as dm_portal_department_id()/dev_portal_department_ids().
 *
 * @return int
 */
function field_portal_department_id()
{
    static $id = null;

    if ($id !== null) {
        return $id;
    }

    $CI = &get_instance();
    $CI->db->select('id');
    $CI->db->where('name', field_portal_department_name());
    $row = $CI->db->get(db_prefix() . 'business_departments')->row();

    $id = $row ? (int) $row->id : 0;

    return $id;
}

/**
 * Pure department-membership check - no Admin bypass. This is the MENU-
 * visibility gate: Admin never sees a "Field Executive Portal" sidebar
 * group. Reuses get_staff_business_departments()
 * (modules/business_departments/helpers/business_departments_helper.php).
 *
 * @param  mixed $staff_id defaults to the current staff member
 * @return bool
 */
function is_field_portal_department_staff($staff_id = null)
{
    if (!is_staff_member()) {
        return false;
    }

    $staff_id = $staff_id ?: get_staff_user_id();

    $departments = get_staff_business_departments($staff_id);
    $my_dept_ids = array_column($departments, 'id');

    return in_array(field_portal_department_id(), $my_dept_ids);
}

/**
 * CONTROLLER access gate (Field_portal::__construct()) - Admin is always
 * authorized (standard superuser bypass used throughout this codebase),
 * even though Admin never sees the menu. Everyone else must be a real
 * Field Executing department member.
 *
 * @param  mixed $staff_id defaults to the current staff member
 * @return bool
 */
function is_field_portal_member($staff_id = null)
{
    if (!is_staff_member()) {
        return false;
    }

    return is_admin($staff_id) || is_field_portal_department_staff($staff_id);
}

/**
 * Lead ownership gate for every Lead Details action (add note/attachment,
 * delete note/attachment, convert) - deliberately NOT
 * Leads_model::staff_can_access_lead() (which also grants access via the
 * native 'leads' 'view' permission capability, a system this portal never
 * touches, per the same "department membership, not capability grants"
 * philosophy as the rest of this module). A Field Executive may act on a
 * lead only if they created it or are still its assigned staff member -
 * matches exactly how "My Leads" itself is scoped
 * (field_portal_my_leads_where()).
 *
 * @param  int $lead_id
 * @param  mixed $staff_id defaults to the current staff member
 * @return bool
 */
function is_owned_lead($lead_id, $staff_id = null)
{
    if (is_admin()) {
        return true;
    }

    $staff_id = $staff_id ?: get_staff_user_id();

    $CI = &get_instance();
    $CI->db->where('id', $lead_id);
    $CI->db->group_start();
    $CI->db->where('addedfrom', $staff_id);
    $CI->db->or_where('assigned', $staff_id);
    $CI->db->group_end();

    return (bool) $CI->db->count_all_results(db_prefix() . 'leads');
}

/**
 * The "my leads" WHERE fragment shared by every Lead-rooted query in this
 * portal (My Leads table, dashboard counts, Lead Details access) - a
 * lead belongs to a Field Executive if they created it OR are its
 * currently assigned staff member. Admin gets no restriction (same
 * superuser bypass used everywhere else in this codebase).
 *
 * @param  int $staff_id
 * @param  string $table_alias defaults to the prefixed leads table name
 * @return string empty string for Admin, otherwise a ready-to-append
 *                'AND (...)' SQL fragment
 */
function field_portal_my_leads_where($staff_id, $table_alias = null)
{
    if (is_admin()) {
        return '';
    }

    $prefix = $table_alias ? $table_alias . '.' : db_prefix() . 'leads.';

    return 'AND (' . $prefix . 'addedfrom = ' . (int) $staff_id . ' OR ' . $prefix . 'assigned = ' . (int) $staff_id . ')';
}

/**
 * A Meeting IS a Perfex Task (tbltasks.rel_type='lead') - see
 * modules/field_portal/field_portal.php's own module docblock for the
 * full architecture rationale. Ownership of a meeting is therefore just
 * the ownership of the Lead it's linked to - this resolves the task's
 * own rel_id/rel_type first, then delegates to the exact same
 * is_owned_lead() check every other Lead Details action already uses,
 * rather than a second, parallel ownership rule.
 *
 * @param  int $task_id
 * @param  mixed $staff_id defaults to the current staff member
 * @return bool
 */
function is_owned_meeting_task($task_id, $staff_id = null)
{
    if (is_admin()) {
        return true;
    }

    $CI = &get_instance();
    $CI->db->select('rel_id, rel_type');
    $CI->db->where('id', $task_id);
    $task = $CI->db->get(db_prefix() . 'tasks')->row();

    if (!$task || $task->rel_type !== 'lead') {
        return false;
    }

    return is_owned_lead($task->rel_id, $staff_id);
}

/**
 * Builds the Task `description` as a structured, labeled template for a
 * meeting's discussion details - deliberately plain text sections instead
 * of one Task custom field per attribute (Discussion Summary/Products
 * Discussed/Customer Requirements/Budget/Competitor Info/Next Action),
 * per the explicit instruction to avoid cluttering the native Task form
 * (used CRM-wide, not just by this portal) with Lead-meeting-only custom
 * fields. Blank sections are omitted entirely rather than shown empty.
 *
 * @param  array $fields associative: label => value
 * @return string
 */
function field_portal_meeting_template($fields)
{
    $sections = [];

    foreach ($fields as $label => $value) {
        $value = trim((string) $value);

        if ($value === '') {
            continue;
        }

        $sections[] = $label . ':' . "\n" . $value;
    }

    return implode("\n\n", $sections);
}

/**
 * Resolves the "Telecalling" business department to its live
 * tblbusiness_departments id - looked up by name rather than hardcoded,
 * same reasoning as field_portal_department_id(). Extracted from
 * field_portal_get_telecallers() so other callers (e.g. the native
 * Customers list's "Converted From" classification) can get just the id
 * without re-fetching the whole staff roster.
 *
 * @return int
 */
function field_portal_telecaller_department_id()
{
    static $id = null;

    if ($id !== null) {
        return $id;
    }

    $CI = &get_instance();
    $CI->db->select('id');
    $CI->db->where('name', 'Telecalling');
    $row = $CI->db->get(db_prefix() . 'business_departments')->row();

    $id = $row ? (int) $row->id : 0;

    return $id;
}

/**
 * Step 4 - Lead Assignment. Real, active staff in the "Telecalling"
 * business department (id looked up by name, not hardcoded) - reuses
 * modules/business_departments's own get_business_department_staff()
 * directly, the exact function the Customers/Projects Assigned Employee
 * dropdowns already use, rather than a second department->staff join.
 *
 * @return array [['staffid' => int, 'name' => string], ...]
 */
function field_portal_get_telecallers()
{
    $dept_id = field_portal_telecaller_department_id();

    if (!$dept_id) {
        return [];
    }

    return get_business_department_staff($dept_id);
}

/**
 * Consistent list-page header (Title + Breadcrumb + Refresh, optionally a
 * "Create" button) reused by every drill-down page this portal's
 * Dashboard KPI cards/panels link to (My Leads, Meetings, Follow-ups,
 * Assigned to Telecaller, Customers, Lead Activity) - one place for this
 * markup instead of copy-pasted per view. The Refresh button doesn't wire
 * up its own click handler here (each page's own DataTable selector
 * differs) - it carries a `data-target-table` attribute that
 * field_portal_page_header_script()'s one delegated handler reads.
 *
 * @param  string      $title
 * @param  array       $breadcrumb   ordered list of ['label' => string, 'href' => string|null]
 * @param  string|null $target_table CSS class of the DataTable to reload on Refresh (omit to hide the button)
 * @param  string|null $create_url
 * @param  string|null $create_label
 * @return void
 */
function field_portal_page_header($title, $breadcrumb, $target_table = null, $create_url = null, $create_label = null)
{
    field_portal_page_header_script();
    ?>
    <div class="tw-flex tw-items-start tw-justify-between tw-flex-wrap tw-gap-2">
        <div>
            <h4 class="no-margin"><?= e($title); ?></h4>
            <ol class="breadcrumb tw-mt-1 tw-mb-0" style="background:transparent;padding-left:0;">
                <?php
                $last_index = count($breadcrumb) - 1;
                foreach ($breadcrumb as $i => $crumb) {
                    if ($i === $last_index || empty($crumb['href'])) {
                        ?>
                <li class="active"><?= e($crumb['label']); ?></li>
                        <?php
                    } else {
                        ?>
                <li><a href="<?= e($crumb['href']); ?>"><?= e($crumb['label']); ?></a></li>
                        <?php
                    }
                }
                ?>
            </ol>
        </div>
        <div class="tw-flex tw-items-center tw-gap-2">
            <?php if ($target_table) { ?>
            <button type="button" class="btn btn-default btn-sm field-portal-refresh-btn" data-target-table="<?= e($target_table); ?>" title="<?= _l('field_portal_refresh'); ?>"><i class="fa-solid fa-rotate-right"></i></button>
            <?php } ?>
            <?php if ($create_url) { ?>
            <a href="<?= e($create_url); ?>" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus"></i> <?= e($create_label); ?></a>
            <?php } ?>
        </div>
    </div>
    <hr class="hr-panel-separator" />
    <?php
}

/**
 * One-time delegated click handler for every field_portal_page_header()'s
 * Refresh button - guarded with a static flag exactly like
 * staff_dashboard_panel_script() so it only prints once per page.
 *
 * @return void
 */
function field_portal_page_header_script()
{
    static $printed = false;

    if ($printed) {
        return;
    }
    $printed = true;
    ?>
    <script>
    $(function() {
        $(document).on('click', '.field-portal-refresh-btn', function() {
            var table = $('.' + $(this).data('target-table'));
            if (table.length && $.fn.DataTable.isDataTable(table[0])) {
                table.DataTable().ajax.reload(null, false);
            } else {
                location.reload();
            }
        });
    });
    </script>
    <?php
}

/**
 * Step 4 - the currently open Follow-up Case for a Lead, if any -
 * "with a Telecaller" for every purpose in this portal (hiding the
 * Assign/Convert/Mark Lost quick actions, showing the "Currently with
 * Telecaller" banner). Reuses Follow_ups_model::get_for_entity()
 * directly rather than a second Case query.
 *
 * @param  int $lead_id
 * @return object|null
 */
function field_portal_get_active_case($lead_id)
{
    $CI = &get_instance();
    $CI->load->model('follow_up_management/follow_ups_model');

    foreach ($CI->follow_ups_model->get_for_entity($lead_id, 'lead') as $case) {
        if ($case->case_status === 'open') {
            return $case;
        }
    }

    return null;
}
