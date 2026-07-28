<?php

defined('BASEPATH') or exit('No direct script access allowed');

// Telecaller Work Queue - looks/behaves like the Admin Leads table
// (application/views/admin/tables/leads.php - its status-dropdown and
// phone/WhatsApp cell markup is mirrored verbatim below, not
// redesigned) but rooted on tblleads with a Lead-centric visibility
// rule instead of that page's own, per "the telecaller should feel like
// they're using a Sales Calling Queue". Same data_tables_init()
// convention as my_cases.php/all_follow_ups.php.
//
// Every piece of actual business logic is reused, not re-implemented:
// follow_up_sanitize_phone_for_dialing(), get_followup_priority_label()/
// _class(), get_followup_lead_visibility_where() (new, but the Case-
// centric get_followup_case_visibility_where() is untouched and still
// used everywhere it already was). Status changes made from here call
// the exact same lead_mark_as()/admin/leads/update_lead_status() path
// core Leads already uses (assets/js/main.js) - unchanged - so they
// fire the exact same lead_status_changed hook and therefore the exact
// same Follow-up Case/Task/Reminder/History sync this module already
// built, with zero new synchronization code.
//
// Column/index note: all 10 real SQL columns are rendered strictly in
// $aColumns order (indices 0-9, all sortable/searchable, correctly
// aligned with data_tables_init()'s ORDER BY/search-column indexing).
// WhatsApp/Priority/Actions/Options are derived, not real columns, and
// are appended after the loop rather than interleaved - interleaving them
// (e.g. WhatsApp directly after Phone) would shift every subsequent
// $aColumns entry's VISIBLE position out of sync with its SQL index,
// silently breaking sort/search on every column after the interleave
// point. This is a deliberate, small deviation from the spec's exact
// left-to-right column order in exchange for correct sort/search.
$CI = &get_instance();
$CI->load->model('leads_model');
$CI->load->model('follow_up_management/follow_ups_model');
$statuses = $CI->leads_model->get_status();

$next_followup_field    = get_custom_fields('leads', ['name' => 'Next Follow-up Date']);
$next_followup_field_id = !empty($next_followup_field) ? (int) $next_followup_field[0]['id'] : 0;

$aColumns = [
    db_prefix() . 'leads.name as lead_name',
    db_prefix() . 'leads.company',
    db_prefix() . 'leads.email',
    db_prefix() . 'leads.phonenumber',
    db_prefix() . 'leads_status.name as status_name',
    ($next_followup_field_id ? 'ctable_next_followup.value' : 'NULL') . ' as next_follow_up_date_value',
    db_prefix() . 'leads_sources.name as source_name',
    db_prefix() . 'leads.lastcontact',
    db_prefix() . 'followups.last_activity_at',
    db_prefix() . 'leads.dateassigned',
];

$sIndexColumn = 'id';
$sTable       = db_prefix() . 'leads';

$where = [
    // Content scope: only leads currently "in Follow-up Management" -
    // either the Lead's own status is Follow-up, or an open Follow-up
    // Case already exists (covers the same brief-window edge case
    // sync_case_from_lead() already self-heals elsewhere).
    'AND (' . db_prefix() . 'leads_status.name = "Follow-up" OR ' . db_prefix() . 'followups.id IS NOT NULL)',
];

// Admin/Manager/Telecaller visibility - Lead-centric (tblleads.assigned),
// a separate function from the Case-centric get_followup_case_visibility_where()
// so every existing caller of that one keeps its exact current behavior.
$visibility = get_followup_lead_visibility_where();
if ($visibility) {
    $where[] = $visibility;
}

$join = [
    'LEFT JOIN ' . db_prefix() . 'leads_status ON ' . db_prefix() . 'leads_status.id = ' . db_prefix() . 'leads.status',
    'LEFT JOIN ' . db_prefix() . 'leads_sources ON ' . db_prefix() . 'leads_sources.id = ' . db_prefix() . 'leads.source',
    'LEFT JOIN ' . db_prefix() . 'followups ON ' . db_prefix() . 'followups.rel_id = ' . db_prefix() . 'leads.id AND ' . db_prefix() . 'followups.rel_type="lead" AND ' . db_prefix() . 'followups.case_status="open"',
];

if ($next_followup_field_id) {
    $join[] = 'LEFT JOIN ' . db_prefix() . 'customfieldsvalues as ctable_next_followup ON ctable_next_followup.relid = ' . db_prefix() . 'leads.id AND ctable_next_followup.fieldto="leads" AND ctable_next_followup.fieldid=' . $next_followup_field_id;
}

$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, [
    db_prefix() . 'leads.id',
    db_prefix() . 'leads.status',
    db_prefix() . 'leads_status.color',
    db_prefix() . 'followups.id as case_id',
    db_prefix() . 'followups.task_id',
    db_prefix() . 'followups.priority',
    // For the Options dropdown's can_act gate below - the exact same
    // three-part check my_cases.php's own row renderer already uses,
    // not a new access rule.
    db_prefix() . 'followups.assigned_staff_id',
    db_prefix() . 'followups.created_by',
    // Latest Note - a correlated subquery against the existing Timeline
    // table (tblfollowup_history), the single history log already
    // written by Log Call/Add Note/Schedule Reminder/Schedule Meeting
    // (Follow_ups_model::log_case_interaction() only ever inserts
    // activity_type in ('call','meeting','reminder','note') - see that
    // method's own guard). The activity_type filter is required: this
    // same table also carries system-generated timeline rows
    // (status_change/case_created/auto_forwarded/etc.) which are not
    // "notes" and must not surface here. Ordered by event_datetime (its
    // own indexed column) then id as a tiebreaker, LIMIT 1 - the DB does
    // the "latest per row" work in one pass per outer row, not a
    // separate PHP query per lead (no N+1). No new table, no duplicated
    // note storage - this reads the same notes column the Case Detail
    // Timeline already renders.
    '(SELECT h.notes FROM ' . db_prefix() . 'followup_history h WHERE h.followup_id = ' . db_prefix() . "followups.id AND h.activity_type IN ('call','meeting','reminder','note') ORDER BY h.event_datetime DESC, h.id DESC LIMIT 1) as latest_note",
]);

$output  = $result['output'];
$rResult = $result['rResult'];

foreach ($rResult as $aRow) {
    $row = [];

    for ($i = 0; $i < count($aColumns); $i++) {
        if (strpos($aColumns[$i], 'as') !== false && !isset($aRow[$aColumns[$i]])) {
            $_data = $aRow[strafter($aColumns[$i], 'as ')];
        } else {
            $_data = $aRow[$aColumns[$i]];
        }

        if ($aColumns[$i] == db_prefix() . 'leads.name as lead_name') {
            $_data = '<a href="' . admin_url('leads/index/' . $aRow['id']) . '" onclick="init_lead(' . $aRow['id'] . ');return false;" class="tw-font-medium">' . e($_data) . '</a>';
        } elseif ($aColumns[$i] == db_prefix() . 'leads.email') {
            $_data = $_data ? '<a href="mailto:' . e($_data) . '">' . e($_data) . '</a>' : '-';
        } elseif ($aColumns[$i] == db_prefix() . 'leads.phonenumber') {
            // Phone - identical tel:/copy/WhatsApp dropdown markup to
            // core's own Leads table (leads.php), not redesigned.
            $phone_raw = $_data;
            if ($_data) {
                $dialable = follow_up_sanitize_phone_for_dialing($phone_raw);
                $_data = '<a href="tel:' . e($_data) . '">' . e($_data) . '</a>';
                if ($dialable !== '') {
                    $_data .= ' <div class="dropdown inline-block lead-phone-actions">';
                    $_data .= '<a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Call"><i class="fa-solid fa-phone"></i></a>';
                    $_data .= '<ul class="dropdown-menu dropdown-menu-right">';
                    $_data .= '<li><a href="tel:' . e($dialable) . '"><i class="fa-solid fa-phone"></i> ' . _l('followup_workspace_action_call') . '</a></li>';
                    $_data .= '<li><a href="#" class="lead-phone-copy" data-phone="' . e($dialable) . '"><i class="fa-regular fa-copy"></i> ' . _l('copy') . '</a></li>';
                    $_data .= '<li><a href="https://wa.me/' . e($dialable) . '" target="_blank" rel="noopener"><i class="fa-brands fa-whatsapp"></i> ' . _l('followup_type_whatsapp') . '</a></li>';
                    $_data .= '</ul></div>';
                }
            } else {
                $_data = '-';
            }
        } elseif ($aColumns[$i] == db_prefix() . 'leads_status.name as status_name') {
            // Lead Status - the exact same inline dropdown markup and
            // lead_mark_as()/update_lead_status() path core Leads
            // already uses (assets/js/main.js), reused verbatim.
            $outputStatus = '<div class="dropdown inline-block">';
            $outputStatus .= '<a href="#" class="dropdown-toggle tw-flex tw-items-center tw-gap-1 tw-flex-nowrap hover:tw-opacity-80 tw-align-middle label' . (empty($aRow['color']) ? ' label-default' : '') . '" style="color:' . $aRow['color'] . ';border:1px solid ' . adjust_hex_brightness($aRow['color'], 0.4) . ';background: ' . adjust_hex_brightness($aRow['color'], 0.04) . ';" id="tableTelecallerLeadsStatus-' . $aRow['id'] . '" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
            $outputStatus .= e($_data);
            $outputStatus .= '<i class="chevron"></i></a>';
            $outputStatus .= '<ul class="dropdown-menu dropdown-menu-right" aria-labelledby="tableTelecallerLeadsStatus-' . $aRow['id'] . '">';
            foreach ($statuses as $leadChangeStatus) {
                if ($aRow['status'] != $leadChangeStatus['id']) {
                    $outputStatus .= '<li><a href="#" onclick="lead_mark_as(' . $leadChangeStatus['id'] . ',' . $aRow['id'] . '); return false;">' . e($leadChangeStatus['name']) . '</a></li>';
                }
            }
            $outputStatus .= '</ul></div>';
            $_data = $outputStatus;
        } elseif (strpos($aColumns[$i], 'next_follow_up_date_value') !== false) {
            $_data = format_followup_next_date_badge($_data ?: null);
        } elseif ($aColumns[$i] == db_prefix() . 'leads.lastcontact') {
            $_data = ($_data == '0000-00-00 00:00:00' || !is_date($_data)) ? '-' : '<span data-toggle="tooltip" data-title="' . e(_dt($_data)) . '" class="text-has-action is-date">' . e(time_ago($_data)) . '</span>';
        } elseif ($aColumns[$i] == db_prefix() . 'followups.last_activity_at') {
            $_data = $_data ? e(_dt($_data)) : '-';
        } elseif ($aColumns[$i] == db_prefix() . 'leads.dateassigned') {
            $_data = $_data ? e(_d($_data)) : '-';
        } elseif ($_data === null || $_data === '') {
            $_data = '-';
        } else {
            $_data = e($_data);
        }

        $row[] = $_data;
    }

    // WhatsApp - same phone value already selected above for the Phone
    // column (index 3), appended here rather than interleaved - see the
    // file-level note on why.
    if (!empty($phone_raw)) {
        $dialable = follow_up_sanitize_phone_for_dialing($phone_raw);
        $row[]    = '<a href="https://wa.me/' . e($dialable) . '" target="_blank" rel="noopener" class="btn btn-default btn-icon" title="' . _l('followup_type_whatsapp') . '"><i class="fa-brands fa-whatsapp"></i></a>';
    } else {
        $row[] = '-';
    }

    // Priority - the linked Case's priority (the Lead itself has no
    // priority field/custom field in this install - same reasoning
    // already established for the Workspace and this page's earlier
    // version).
    $row[] = !empty($aRow['priority']) ? '<span class="label label-' . get_followup_priority_class($aRow['priority']) . '">' . e(get_followup_priority_label($aRow['priority'])) . '</span>' : '-';

    // Actions - Open Workspace via the exact same init_task_modal()
    // every other task-opening link in Perfex already uses. No new
    // modal, no separate "view lead" popup.
    if (!empty($aRow['task_id'])) {
        $row[] = '<button type="button" class="btn btn-primary btn-sm" onclick="init_task_modal(' . (int) $aRow['task_id'] . '); return false;"><i class="fa-solid fa-headset"></i> ' . _l('followup_workspace_open') . '</button>';
    } elseif (!empty($aRow['case_id'])) {
        // Case exists but its Task hasn't synced yet - self-heals on the
        // Lead's next save. Falls back to the Case Detail page meanwhile.
        $row[] = '<a href="' . admin_url('follow_up_management/view/' . $aRow['case_id']) . '" class="btn btn-default btn-sm">' . _l('view') . '</a>';
    } else {
        // Rarer still - status is Follow-up but no Case exists yet at
        // all (a save/hook race). Falls back to the Lead itself.
        $row[] = '<a href="' . admin_url('leads/index/' . $aRow['id']) . '" onclick="init_lead(' . $aRow['id'] . ');return false;" class="btn btn-default btn-sm">' . _l('view') . '</a>';
    }

    // Step 4 - Telecaller Lead page, additive alongside the existing
    // "Open Workspace" button above (the native Task-modal Workspace
    // itself is never modified, per the Step 4 spec) - only shown when a
    // Case actually exists to view.
    if (!empty($aRow['case_id'])) {
        $row[count($row) - 1] .= ' <a href="' . admin_url('follow_up_management/lead_details/' . $aRow['id']) . '" class="btn btn-default btn-sm"><i class="fa-solid fa-address-card"></i> ' . _l('telecaller_lead_page') . '</a>';
    }

    // Options - Log Call / Schedule Reminder / Schedule Meeting / Add Note.
    // Byte-for-byte the same pattern my_cases.php's own Options dropdown
    // already uses: a plain link to the Case Detail page with ?action=...,
    // which case.php's own script (views/case.php, "Row actions from My
    // Cases / Today's Follow-ups link here with ?action=... instead of
    // duplicating these modals in the grid") auto-opens on load. No new
    // modal, no new controller action, no new model method - this is a
    // third caller of the exact same existing mechanism, not a new one.
    // can_act mirrors my_cases.php's own inline check verbatim.
    if (!empty($aRow['case_id'])) {
        $can_act  = is_admin() || $aRow['assigned_staff_id'] == get_staff_user_id() || $aRow['created_by'] == get_staff_user_id();
        $view_url = admin_url('follow_up_management/view/' . $aRow['case_id']);

        if ($can_act) {
            $options = '<div class="btn-group">';
            $options .= '<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown"><i class="fa-solid fa-ellipsis"></i> ' . _l('options') . ' <span class="caret"></span></button>';
            $options .= '<ul class="dropdown-menu dropdown-menu-right">';
            $options .= '<li><a href="' . $view_url . '?action=log_call">' . _l('case_action_log_call') . '</a></li>';
            $options .= '<li><a href="' . $view_url . '?action=schedule_reminder">' . _l('case_action_schedule_reminder') . '</a></li>';
            $options .= '<li><a href="' . $view_url . '?action=schedule_meeting">' . _l('case_action_schedule_meeting') . '</a></li>';
            $options .= '<li><a href="' . $view_url . '?action=add_note">' . _l('case_action_add_note') . '</a></li>';
            $options .= '</ul></div>';
            $row[] = $options;
        } else {
            $row[] = '-';
        }
    } else {
        $row[] = '-';
    }

    // Latest Note - derived, appended after Options for the same reason
    // WhatsApp/Priority/Actions/Options are (see file-level note above):
    // it has no 1:1 $aColumns entry to keep sort/search alignment intact.
    // notes are stored nl2br()'d (log_case_interaction()), so tags are
    // converted back to line breaks before truncating/escaping - showing
    // raw "<br />" in a 60-char preview would look broken.
    $latest_note_raw = isset($aRow['latest_note']) ? trim(strip_tags(str_replace(['<br />', '<br/>', '<br>'], "\n", (string) $aRow['latest_note']))) : '';

    if ($latest_note_raw === '') {
        $row[] = '-';
    } else {
        $latest_note_preview = mb_strlen($latest_note_raw) > 60 ? mb_substr($latest_note_raw, 0, 60) . '...' : $latest_note_raw;
        $row[] = '<span data-toggle="tooltip" data-title="' . e($latest_note_raw) . '">' . e($latest_note_preview) . '</span>';
    }

    $row['DT_RowId']    = 'telecaller_lead_' . $aRow['id'];
    $row['DT_RowClass'] = 'has-border-left';

    $output['aaData'][] = $row;
}
