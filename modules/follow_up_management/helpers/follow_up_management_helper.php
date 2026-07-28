<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Explicitly loads modules/follow_up_management/language/english/
 * follow_up_management_lang.php. Needed because this module's _l() keys are
 * used from views rendered by the Leads module and the Dashboard controller,
 * not this module's own routed controller, so MX's "auto-load the current
 * module's language file" convention doesn't reach them.
 *
 * The idiom ($lang) argument is passed explicitly as 'english' rather than
 * '' - App_Lang::load() falls back to $this->config->item('language') when
 * $lang is blank, and this install's config.php deliberately leaves
 * $config['language'] = '' (the active language is resolved dynamically
 * elsewhere, not via that config item). Passing '' therefore built the path
 * as ".../language//follow_up_management_lang.php" (missing the "english"
 * segment entirely), which is the exact cause of the "Unable to load the
 * requested language file" error - confirmed by reproducing both paths
 * directly against the filesystem before this fix.
 */
function follow_up_management_load_language()
{
    $CI = &get_instance();
    $CI->lang->load('follow_up_management', 'english', false, true, '', 'follow_up_management');
}

/**
 * Real fix for a confirmed leak: every "plain Telecaller" check in this
 * file used to be simply "!is_admin() && !staff_can('view_department',
 * 'follow_up_management')" - which is true for ANY non-admin/non-manager
 * staff member, regardless of whether they have anything to do with
 * Follow-up Management at all. Confirmed live: tblstaff_permissions has
 * zero rows for this feature (no one has ever been granted the Manager
 * capability), so literally every non-admin staff member - Web
 * Development, App Development, UI/UX Design, Testing, anyone - was
 * being misclassified as a Telecaller and getting the Telecaller menu
 * injection/Tasks-item hijack/dashboard swap. This narrows "plain
 * Telecaller" to also require real membership in the "Telecalling"
 * Business Department (modules/business_departments), reusing the
 * existing get_staff_business_departments() reverse-lookup helper -
 * exactly the check modules/dev_portal/helpers/dev_portal_helper.php
 * uses for its own department gate, not a new mechanism.
 *
 * @param  mixed $staff_id defaults to the current staff member
 * @return bool
 */
function follow_up_management_is_telecalling_department_member($staff_id = null)
{
    if (!is_staff_member()) {
        return false;
    }

    $staff_id = $staff_id ?: get_staff_user_id();

    foreach (get_staff_business_departments($staff_id) as $department) {
        if ($department['name'] === 'Telecalling') {
            return true;
        }
    }

    return false;
}

/**
 * CONTROLLER access gate for the Telecaller-exclusive pages (My
 * Follow-ups, its widgets, the simplified Telecaller Dashboard) - Admin
 * and Manager-capability holders remain authorized for oversight (same
 * superuser/manager bypass this module already used everywhere else),
 * everyone else must be a real Telecalling department member. Composed
 * from the same three checks dashboard()/the menu-visibility functions
 * already use, kept as one function so the controller's direct-URL
 * gates and this file's menu gates can't drift apart.
 *
 * @param  mixed $staff_id defaults to the current staff member
 * @return bool
 */
function follow_up_management_can_access_telecaller_pages($staff_id = null)
{
    return is_admin($staff_id) || staff_can('view_department', 'follow_up_management') || follow_up_management_is_telecalling_department_member($staff_id);
}

/**
 * Central Admin/Manager/Telecaller case-visibility rule, as a raw SQL WHERE
 * fragment in the exact same string-array style data_tables_init() already
 * uses ($where[] = 'AND ...') - so this drops directly into the My Cases
 * DataTable source, Today's Follow-ups, the KPI cards, and the dashboard
 * widget without four separate implementations of the same rule.
 *
 * - Admin (is_admin()): '' - no restriction, matching the is_admin()
 *   bypass convention already used everywhere else in this module.
 * - Manager (has the new 'view_department' capability): department cases
 *   (via the existing get_staff_business_departments() helper) OR own.
 * - Telecaller (default): own cases only (assigned_staff_id).
 *
 * @param string|null $table_alias defaults to the unaliased followups table
 * @return string
 */
function get_followup_case_visibility_where($table_alias = null)
{
    if (is_admin()) {
        return '';
    }

    $prefix   = $table_alias ? $table_alias . '.' : db_prefix() . 'followups.';
    $staff_id = (int) get_staff_user_id();

    if (staff_can('view_department', 'follow_up_management')) {
        $department_ids = array_map('intval', array_column(get_staff_business_departments($staff_id), 'id'));

        if (empty($department_ids)) {
            // Manager capability granted but not mapped to any department yet -
            // fall back to own cases only rather than showing everything.
            return 'AND ' . $prefix . 'assigned_staff_id = ' . $staff_id;
        }

        return 'AND (' . $prefix . 'department_id IN (' . implode(',', $department_ids) . ') OR ' . $prefix . 'assigned_staff_id = ' . $staff_id . ')';
    }

    return 'AND ' . $prefix . 'assigned_staff_id = ' . $staff_id;
}

/**
 * Lead-centric counterpart to get_followup_case_visibility_where() - same
 * Admin/Manager/Telecaller three-tier shape, but scoped by
 * `tblleads.assigned` rather than a Case's `assigned_staff_id`. Added as
 * a SEPARATE function rather than a parameter/branch on the existing one
 * so every existing caller of that function (My Cases, dashboard KPIs,
 * reports) keeps its exact current Case-centric behavior untouched. Used
 * by the Telecaller Work Queue (application/views/admin/tables/
 * telecaller_leads.php), which is rooted on tblleads, not tblfollowups.
 *
 * @param string|null $table_alias defaults to db_prefix().'leads'
 * @return string raw SQL "AND ..." fragment, or '' for admins
 */
function get_followup_lead_visibility_where($table_alias = null)
{
    if (is_admin()) {
        return '';
    }

    $prefix   = $table_alias ? $table_alias . '.' : db_prefix() . 'leads.';
    $staff_id = (int) get_staff_user_id();

    if (staff_can('view_department', 'follow_up_management')) {
        $department_ids = array_map('intval', array_column(get_staff_business_departments($staff_id), 'id'));

        if (empty($department_ids)) {
            return 'AND ' . $prefix . 'assigned = ' . $staff_id;
        }

        return 'AND (' . $prefix . 'department IN (' . implode(',', $department_ids) . ') OR ' . $prefix . 'assigned = ' . $staff_id . ')';
    }

    return 'AND ' . $prefix . 'assigned = ' . $staff_id;
}

/**
 * Section 5-style "Next Follow-up" badge for the Telecaller Work Queue -
 * Overdue (red) / Today (orange) / Tomorrow (blue) / plain date, so a
 * Telecaller can triage the whole list at a glance without reading every
 * date. Pure presentation - reads a value already resolved elsewhere
 * (Follow_ups_model::get_lead_next_follow_up_date() or
 * tblfollowups.next_follow_up_date), never a new data source.
 *
 * @param string|null $sql_datetime SQL datetime string, or null/empty
 * @return string HTML
 */
function format_followup_next_date_badge($sql_datetime)
{
    if (empty($sql_datetime)) {
        return '<span class="text-muted">' . _l('case_not_set') . '</span>';
    }

    $date_only = substr($sql_datetime, 0, 10);
    $today     = date('Y-m-d');
    $tomorrow  = date('Y-m-d', strtotime('+1 day'));

    if ($date_only < $today) {
        return '<span class="label label-danger">' . _l('followup_workspace_overdue') . '</span> ' . e(_d($date_only));
    }

    if ($date_only === $today) {
        return '<span class="label label-warning">' . _l('followup_due_today') . '</span>';
    }

    if ($date_only === $tomorrow) {
        return '<span class="label label-info">' . _l('followup_due_tomorrow') . '</span>';
    }

    return e(_d($date_only));
}

/**
 * Formats a second count from Follow_ups_model::get_dashboard_kpis()'s
 * avg_response_time as a short human string ("45m", "2h 15m", "3d 4h").
 * Null (no responded cases yet in scope) renders as the same "not set"
 * copy used elsewhere on the Case Detail page.
 *
 * @param int|null $seconds
 * @return string
 */
function get_followup_format_duration($seconds)
{
    if ($seconds === null) {
        return _l('case_not_set');
    }

    if ($seconds < 60) {
        return $seconds . 's';
    }

    if ($seconds < 3600) {
        return round($seconds / 60) . 'm';
    }

    if ($seconds < 86400) {
        $hours   = floor($seconds / 3600);
        $minutes = round(($seconds % 3600) / 60);

        return $hours . 'h ' . $minutes . 'm';
    }

    $days  = floor($seconds / 86400);
    $hours = round(($seconds % 86400) / 3600);

    return $days . 'd ' . $hours . 'h';
}

/**
 * Fixed list of follow-up contact channels. Filterable so a future module
 * (or a future rel_type's own needs) can extend it without a core edit.
 *
 * @return array
 */
function get_followup_types()
{
    return hooks()->apply_filters('follow_up_types', [
        'call'     => _l('followup_type_call'),
        'email'    => _l('followup_type_email'),
        'meeting'  => _l('followup_type_meeting'),
        'whatsapp' => _l('followup_type_whatsapp'),
        'other'    => _l('followup_type_other'),
    ]);
}

/**
 * Fixed list of follow-up outcomes - the single reusable lookup behind
 * both the Telecaller Workspace's Call Outcome section (Section 3) and
 * the Lead Pipeline stage derivation (Section 6, get_pipeline_stage()) -
 * one enum serving both, per the "only one reusable lookup" instruction,
 * not two parallel ones. `connected` through `call_back_later` are
 * call-result outcomes; `sales_discussion`/`proposal_sent`/`negotiation`
 * are deal-stage outcomes staff can log on any interaction (not just a
 * call) to advance the visible Pipeline - both sets already coexist in
 * the same `tblfollowups.outcome`/`tblfollowup_history.outcome` columns
 * (varchar(50), pre-existing, no schema change). The original six keys
 * are kept unmodified for backward compatibility with any existing rows.
 *
 * @return array
 */
function get_followup_outcomes()
{
    return hooks()->apply_filters('follow_up_outcomes', [
        'connected'        => _l('followup_outcome_connected'),
        'no_answer'        => _l('followup_outcome_no_answer'),
        'busy'             => _l('followup_outcome_busy'),
        'switched_off'     => _l('followup_outcome_switched_off'),
        'wrong_number'     => _l('followup_outcome_wrong_number'),
        'call_back_later'  => _l('followup_outcome_call_back_later'),
        'interested'       => _l('followup_outcome_interested'),
        'not_interested'   => _l('followup_outcome_not_interested'),
        'rescheduled'      => _l('followup_outcome_rescheduled'),
        'other'            => _l('followup_outcome_other'),
    ]);
}

/**
 * Meeting Type for the Appointment section (Section 7) - a small in-code
 * list, not a DB-backed lookup, since nothing like it exists yet
 * anywhere in this module or core Perfex. Stored as free text inside the
 * meeting's notes/reminder description (see log_case_interaction()'s
 * 'meeting' branch) - no schema change.
 *
 * @return array
 */
function get_followup_meeting_types()
{
    return hooks()->apply_filters('follow_up_meeting_types', [
        'in_person'  => _l('followup_meeting_type_in_person'),
        'video_call' => _l('followup_meeting_type_video_call'),
        'phone_call' => _l('followup_meeting_type_phone_call'),
    ]);
}

/**
 * Ordered Lead Pipeline stages for the Section 6 stepper. Purely a
 * display/ordering list - get_pipeline_stage() (Follow_ups_model.php)
 * is what derives which one is "current" for a given Case, from
 * existing signals only (Lead status, Case outcome, History entries) -
 * no stage is ever stored, so there is nothing here to keep in sync.
 *
 * @return array ordered stage_key => label
 */
function get_followup_pipeline_stages()
{
    return hooks()->apply_filters('follow_up_pipeline_stages', [
        'lead_assigned'        => _l('followup_pipeline_lead_assigned'),
        'call_attempted'       => _l('followup_pipeline_call_attempted'),
        'interested'           => _l('followup_pipeline_interested'),
        'follow_up_scheduled'  => _l('followup_pipeline_follow_up_scheduled'),
        'appointment_scheduled' => _l('followup_pipeline_appointment_scheduled'),
        'sales_discussion'     => _l('followup_pipeline_sales_discussion'),
        'proposal_sent'        => _l('followup_pipeline_proposal_sent'),
        'negotiation'          => _l('followup_pipeline_negotiation'),
        'won_customer'         => _l('followup_pipeline_won_customer'),
        'converted_customer'   => _l('followup_pipeline_converted_customer'),
    ]);
}

/**
 * @return array
 */
function get_followup_statuses()
{
    return [
        'pending'           => _l('followup_status_pending'),
        'completed'         => _l('followup_status_completed'),
        'no_further_action' => _l('followup_status_no_further_action'),
    ];
}

/**
 * All entity types Follow-up Management currently supports. Purely additive -
 * extending to Customers/Projects/Students later means adding a key here
 * (plus, if it doesn't already exist, a get_relation_data()/
 * get_relation_values() case in application/helpers/relation_helper.php -
 * 'lead' and 'customer' already have one) - no schema change on tblfollowups.
 *
 * @return array
 */
function get_followup_supported_rel_types()
{
    return hooks()->apply_filters('follow_up_supported_rel_types', [
        'lead' => _l('lead'),
    ]);
}

function get_followup_status_label($status)
{
    $statuses = get_followup_statuses();

    return isset($statuses[$status]) ? $statuses[$status] : $status;
}

function get_followup_type_label($type)
{
    $types = get_followup_types();

    return isset($types[$type]) ? $types[$type] : $type;
}

function get_followup_outcome_label($outcome)
{
    if (empty($outcome)) {
        return '';
    }

    $outcomes = get_followup_outcomes();

    return isset($outcomes[$outcome]) ? $outcomes[$outcome] : $outcome;
}

/**
 * @return array
 */
function get_followup_priorities()
{
    return [
        'low'    => _l('followup_priority_low'),
        'medium' => _l('followup_priority_medium'),
        'high'   => _l('followup_priority_high'),
        'urgent' => _l('followup_priority_urgent'),
    ];
}

function get_followup_priority_label($priority)
{
    $priorities = get_followup_priorities();

    return isset($priorities[$priority]) ? $priorities[$priority] : $priority;
}

/**
 * Maps a Case priority (this module's own low/medium/high/urgent strings)
 * to a standard Perfex Task priority id (Tasks_model::get_statuses()'s
 * sibling, get_tasks_priorities() in application/helpers/tasks_helper.php
 * - 1=Low, 2=Medium, 3=High, 4=Urgent, confirmed from that function, not
 * guessed), for the linked Task this module creates/syncs. No new priority
 * system - reuses Perfex's own existing one.
 *
 * @param string $priority
 * @return int
 */
function follow_up_case_priority_to_task_priority($priority)
{
    $map = [
        'low'    => 1,
        'medium' => 2,
        'high'   => 3,
        'urgent' => 4,
    ];

    return $map[$priority] ?? 2;
}

/**
 * Bootstrap label class per priority, used consistently by the Case Detail
 * header badge and (later) the My Cases grid.
 *
 * @param string $priority
 * @return string
 */
function get_followup_priority_class($priority)
{
    $classes = [
        'low'    => 'default',
        'medium' => 'info',
        'high'   => 'warning',
        'urgent' => 'danger',
    ];

    return $classes[$priority] ?? 'default';
}

/**
 * The true Case lifecycle (open/closed) added in migration 356, distinct
 * from the legacy per-attempt `status` column above.
 *
 * @return array
 */
function get_followup_case_statuses()
{
    return [
        'open'   => _l('followup_case_status_open'),
        'closed' => _l('followup_case_status_closed'),
    ];
}

function get_followup_case_status_label($case_status)
{
    $statuses = get_followup_case_statuses();

    return isset($statuses[$case_status]) ? $statuses[$case_status] : $case_status;
}

/**
 * Timeline (tblfollowup_history) entry types this module's Case Detail page
 * writes going forward. `follow_up` is kept for the legacy rows carried over
 * from before migration 356 (see get_followup_history_label()/_icon() below,
 * which fall back to the row's own follow_up_type for that one).
 *
 * @return array
 */
function get_followup_history_types()
{
    return [
        'call'          => _l('followup_history_call'),
        'meeting'       => _l('followup_history_meeting'),
        'reminder'      => _l('followup_history_reminder'),
        'note'          => _l('followup_history_note'),
        'status_change'  => _l('followup_history_status_change'),
        'reschedule'     => _l('followup_history_reschedule'),
        'auto_forwarded' => _l('followup_history_auto_forwarded'),
    ];
}

/**
 * Human label for one Timeline row. Legacy `follow_up` rows (created before
 * this module tracked a dedicated activity_type) fall back to their own
 * follow_up_type (call/email/meeting/whatsapp/other) so nothing in the
 * Timeline ever displays a raw internal key.
 *
 * @param object $history
 * @return string
 */
function get_followup_history_label($history)
{
    $types = get_followup_history_types();

    if (isset($types[$history->activity_type])) {
        return $types[$history->activity_type];
    }

    return get_followup_type_label($history->follow_up_type);
}

/**
 * Font Awesome icon class per Timeline entry, same fallback rule as
 * get_followup_history_label().
 *
 * @param object $history
 * @return string
 */
function get_followup_history_icon($history)
{
    $icons = [
        'call'          => 'fa-solid fa-phone',
        'meeting'       => 'fa-regular fa-calendar-check',
        'reminder'      => 'fa-regular fa-bell',
        'note'          => 'fa-regular fa-note-sticky',
        'status_change'  => 'fa-solid fa-arrows-rotate',
        'reschedule'     => 'fa-regular fa-clock',
        'auto_forwarded' => 'fa-solid fa-forward',
    ];

    if (isset($icons[$history->activity_type])) {
        return $icons[$history->activity_type];
    }

    $type_icons = [
        'call'     => 'fa-solid fa-phone',
        'email'    => 'fa-regular fa-envelope',
        'meeting'  => 'fa-regular fa-calendar-check',
        'whatsapp' => 'fa-brands fa-whatsapp',
        'other'    => 'fa-regular fa-note-sticky',
    ];

    return $type_icons[$history->follow_up_type] ?? 'fa-regular fa-circle';
}

/**
 * Activity Log (tblfollowup_activity) action types this module currently
 * writes. "Reminder Completed" is intentionally not produced yet - it would
 * require a new hook inside core Cron_model.php (which fires the due-date
 * cron), and adding one is out of scope until that's explicitly approved;
 * flagged here rather than silently faked.
 *
 * @return array
 */
function get_followup_activity_types()
{
    return [
        'case_created'              => _l('followup_activity_case_created'),
        'reassigned'                => _l('followup_activity_reassigned'),
        'priority_changed'          => _l('followup_activity_priority_changed'),
        'status_changed'            => _l('followup_activity_status_changed'),
        'reminder_created'          => _l('followup_activity_reminder_created'),
        'meeting_created'           => _l('followup_activity_meeting_created'),
        'meeting_cancelled'         => _l('followup_activity_meeting_cancelled'),
        'reminder_cancelled'        => _l('followup_activity_reminder_cancelled'),
        'reminder_overdue_notified' => _l('followup_activity_reminder_overdue'),
        'case_closed'               => _l('followup_activity_case_closed'),
        'case_reopened'             => _l('followup_activity_case_reopened'),
    ];
}

function get_followup_activity_label($activity)
{
    $types = get_followup_activity_types();

    return $types[$activity->action_type] ?? $activity->action_type;
}

function get_followup_activity_icon($activity)
{
    $icons = [
        'case_created'              => 'fa-solid fa-circle-plus',
        'reassigned'                => 'fa-solid fa-user-check',
        'priority_changed'          => 'fa-solid fa-flag',
        'status_changed'            => 'fa-solid fa-arrows-rotate',
        'reminder_created'          => 'fa-regular fa-bell',
        'meeting_created'           => 'fa-regular fa-calendar-check',
        'meeting_cancelled'         => 'fa-regular fa-calendar-xmark',
        'reminder_cancelled'        => 'fa-regular fa-bell-slash',
        'reminder_overdue_notified' => 'fa-solid fa-triangle-exclamation',
        'case_closed'               => 'fa-solid fa-circle-check',
        'case_reopened'             => 'fa-solid fa-rotate-left',
    ];

    return $icons[$activity->action_type] ?? 'fa-regular fa-circle';
}

/**
 * The 11 in-app notification description keys this module ever inserts
 * into tblnotifications (see Follow_ups_model::notify_case_event()) -
 * doubles as both the Notification Center's title lookup and the
 * description IN(...) filter that isolates this module's own rows from
 * every other module's notifications sharing the same core table.
 *
 * @return array
 */
function get_followup_notification_types()
{
    return [
        'not_followup_case_assigned'      => _l('followup_notification_case_assigned'),
        'not_followup_case_reassigned'    => _l('followup_notification_case_reassigned'),
        'not_followup_reminder_created'   => _l('followup_notification_reminder_created'),
        'not_followup_reminder_overdue'   => _l('followup_notification_reminder_overdue'),
        'not_followup_meeting_scheduled'  => _l('followup_notification_meeting_scheduled'),
        'not_followup_meeting_updated'    => _l('followup_notification_meeting_updated'),
        'not_followup_meeting_cancelled'  => _l('followup_notification_meeting_cancelled'),
        'not_followup_reminder_cancelled' => _l('followup_notification_reminder_cancelled'),
        'not_followup_case_closed'        => _l('followup_notification_case_closed'),
        'not_followup_case_reopened'      => _l('followup_notification_case_reopened'),
        'not_followup_case_high_priority' => _l('followup_notification_high_priority'),
    ];
}

function get_followup_notification_title($description_key)
{
    $types = get_followup_notification_types();

    return $types[$description_key] ?? $description_key;
}

function get_followup_notification_icon($description_key)
{
    $icons = [
        'not_followup_case_assigned'      => 'fa-solid fa-circle-plus',
        'not_followup_case_reassigned'    => 'fa-solid fa-user-check',
        'not_followup_reminder_created'   => 'fa-regular fa-bell',
        'not_followup_reminder_overdue'   => 'fa-solid fa-triangle-exclamation',
        'not_followup_meeting_scheduled'  => 'fa-regular fa-calendar-check',
        'not_followup_meeting_updated'    => 'fa-solid fa-calendar-day',
        'not_followup_meeting_cancelled'  => 'fa-regular fa-calendar-xmark',
        'not_followup_reminder_cancelled' => 'fa-regular fa-bell-slash',
        'not_followup_case_closed'        => 'fa-solid fa-circle-check',
        'not_followup_case_reopened'      => 'fa-solid fa-rotate-left',
        'not_followup_case_high_priority' => 'fa-solid fa-flag',
    ];

    return $icons[$description_key] ?? 'fa-regular fa-bell';
}

/**
 * Extracts the Case id from this module's own notification 'link' field
 * (always 'follow_up_management/view/{id}' - see notify_case_event()), so
 * the Notification Center / Dashboard can resolve the Related Lead/Related
 * Case columns without a stored case_id column on tblnotifications itself.
 *
 * @param string $link
 * @return int|null
 */
function get_followup_notification_case_id($link)
{
    if (preg_match('/follow_up_management\/view\/(\d+)/', (string) $link, $matches)) {
        return (int) $matches[1];
    }

    return null;
}

/**
 * Single-Case ownership gate - "can the current staff member act on THIS
 * case" (distinct from get_followup_case_visibility_where(), which scopes
 * a LIST of cases). Extracted from Follow_up_management::case_access_denied()
 * (kept there as a one-line delegate to this function for backward
 * compatibility) so the Telecaller Workspace, embedded in the Task modal
 * rather than routed through that controller, can apply the exact same
 * rule instead of re-deriving it: admin, the case's assigned staff, or
 * the case's creator may act; everyone else may only view.
 *
 * @param object $case a tblfollowups row
 * @return bool true if access is denied
 */
function follow_up_case_access_denied($case)
{
    return ! is_admin()
        && $case->assigned_staff_id != get_staff_user_id()
        && $case->created_by != get_staff_user_id();
}

/**
 * Step 4 - Telecaller Lead page access gate. A Lead is "mine" here only
 * through an open Case I can act on (follow_up_case_access_denied()),
 * never through tblleads.addedfrom/assigned directly - unlike
 * field_portal's own is_owned_lead(), a Telecaller has no standing
 * relationship to a Lead outside of the Case that was created when it
 * was assigned to them.
 *
 * @param  int $lead_id
 * @return bool
 */
function is_assigned_telecaller_lead($lead_id)
{
    if (is_admin()) {
        return true;
    }

    $CI = &get_instance();
    $CI->load->model('follow_up_management/follow_ups_model');

    foreach ($CI->follow_ups_model->get_for_entity($lead_id, 'lead') as $case) {
        if ($case->case_status === 'open' && !follow_up_case_access_denied($case)) {
            return true;
        }
    }

    return false;
}

/**
 * Click-to-call/WhatsApp sanitizer - strips everything except digits and a
 * single leading "+" so "98765-43210" / "+91 98765 43210" become valid
 * tel:/wa.me values. Extracted from the closure already used by the Leads
 * DataTable's phone column (application/views/admin/tables/leads.php) so
 * the Telecaller Workspace's Call/WhatsApp buttons use identical logic
 * instead of a second copy of the same regex.
 *
 * @param string $number
 * @return string
 */
function follow_up_sanitize_phone_for_dialing($number)
{
    $digitsAndPlus = preg_replace('/[^0-9+]/', '', (string) $number);

    return (strpos($digitsAndPlus, '+') === 0 ? '+' : '') . str_replace('+', '', $digitsAndPlus);
}
