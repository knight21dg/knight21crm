<?php

defined('BASEPATH') or exit('No direct script access allowed');

// "All Follow-ups" page feed, mirroring admin/tables/all_reminders.php's
// CASE-based rel_type resolution. Only 'lead' is wired today
// (get_followup_supported_rel_types()) - extending to Customers/Projects/
// Students later means adding one LEFT JOIN + one CASE branch here, the same
// way all_reminders.php would need to for a new rel_type.
//
// Telecaller simplification ("Follow-up History"): a plain Telecaller gets
// a reduced, more legible column set (Lead Name/Company/Contact Method/
// Outcome/Notes/Follow-up Date/Total Interactions/Last Interaction) instead
// of the full Admin/Manager set (Related To/Type/Outcome/Notes/Date/Next
// Date/Staff/Status) - same table id, same route, same base query shape
// ($sTable/$sIndexColumn/visibility WHERE all identical), branched only on
// $aColumns/$join/$additionalSelect/render below. The existing non-admin
// visibility restriction (staff_id = self OR created_by = self) already
// satisfies "Telecallers only see their own history" verbatim - reused
// unchanged for every role, not re-implemented.
$is_telecaller = is_staff_member() && !is_admin() && !staff_can('view_department', 'follow_up_management');

$sIndexColumn = 'id';
$sTable       = db_prefix() . 'followups';
$where        = [];
if (!is_admin()) {
    $where[] = 'AND (' . db_prefix() . 'followups.staff_id = ' . get_staff_user_id() . ' OR ' . db_prefix() . 'followups.created_by = ' . get_staff_user_id() . ')';
}

// Filter toolbar (Status / Assigned Staff / Department / Outcome) - values
// come through as extra POST params via initDataTable()'s fnserverparams
// mechanism (assets/js/main.js:3454-3456), same convention used app-wide for
// simple datatable filters, no new AJAX endpoint needed. Staff/Department
// are hidden from the Telecaller UI (views/all_follow_ups.php), so these
// two params simply never arrive for that role - no extra guard needed
// here.
$CI = &get_instance();
if ($CI->input->post('status') !== null && $CI->input->post('status') !== '') {
    $where[] = 'AND ' . db_prefix() . 'followups.status="' . $CI->db->escape_str($CI->input->post('status')) . '"';
}
if ($CI->input->post('staff_id') !== null && $CI->input->post('staff_id') !== '') {
    $where[] = 'AND ' . db_prefix() . 'followups.staff_id=' . (int) $CI->input->post('staff_id');
}
if ($CI->input->post('department_id') !== null && $CI->input->post('department_id') !== '') {
    $where[] = 'AND ' . db_prefix() . 'followups.department_id=' . (int) $CI->input->post('department_id');
}
if ($CI->input->post('outcome') !== null && $CI->input->post('outcome') !== '') {
    $where[] = 'AND ' . db_prefix() . 'followups.outcome="' . $CI->db->escape_str($CI->input->post('outcome')) . '"';
}

if ($is_telecaller) {
    // Simplified "Follow-up History" columns - Lead Name/Company reuse the
    // exact same LEFT JOIN leads + get_relation_data()/get_relation_values()
    // resolution the Admin/Manager branch already uses below (not a second
    // way of resolving the lead link). Contact Method reuses the existing
    // follow_up_type column/get_followup_type_label() (call/email/meeting/
    // whatsapp/other) - no new channel-tracking field. Last Interaction
    // reuses the existing last_activity_at column (already maintained by
    // add_history_entry() on every logged interaction) - not a new query.
    $aColumns = [
        'CASE ' . db_prefix() . 'followups.rel_type
            WHEN \'lead\' THEN ' . db_prefix() . 'leads.name
            ELSE ' . db_prefix() . 'followups.rel_type END as rel_type_name',
        db_prefix() . 'leads.company',
        db_prefix() . 'followups.follow_up_type',
        db_prefix() . 'followups.outcome',
        db_prefix() . 'followups.notes',
        db_prefix() . 'followups.follow_up_date',
        db_prefix() . 'followups.last_activity_at',
    ];

    $join = [
        'LEFT JOIN ' . db_prefix() . 'leads ON ' . db_prefix() . 'leads.id = ' . db_prefix() . 'followups.rel_id AND ' . db_prefix() . 'followups.rel_type="lead"',
    ];

    $result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, [
        db_prefix() . 'followups.id',
        db_prefix() . 'followups.created_by',
        db_prefix() . 'followups.rel_type',
        db_prefix() . 'followups.rel_id',
        // Total Interactions - a single correlated COUNT against the
        // existing tblfollowup_history table (the same log every Log
        // Call/Schedule Reminder/Schedule Meeting/Add Note action already
        // writes to via add_history_entry()) - reused as-is, not a new
        // tracking mechanism, and computed in SQL (not per-row PHP calls)
        // so this stays one query, not N+1.
        '(SELECT COUNT(*) FROM ' . db_prefix() . 'followup_history WHERE followup_id = ' . db_prefix() . 'followups.id) as interaction_count',
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

            if ($i == 0) {
                $rel_data   = get_relation_data($aRow['rel_type'], $aRow['rel_id']);
                $rel_values = get_relation_values($rel_data, $aRow['rel_type']);
                $_data      = '<a href="' . $rel_values['link'] . '">' . e($rel_values['name']) . '</a>';
            } elseif ($aColumns[$i] == db_prefix() . 'followups.follow_up_type') {
                $_data = $_data ? get_followup_type_label($_data) : '-';
            } elseif ($aColumns[$i] == db_prefix() . 'followups.outcome') {
                $outcome_label = get_followup_outcome_label($_data);
                $_data         = $outcome_label !== '' ? e($outcome_label) : '-';
            } elseif ($aColumns[$i] == db_prefix() . 'followups.notes') {
                $_data = $_data ? process_text_content_for_display($_data) : '-';
            } elseif ($aColumns[$i] == db_prefix() . 'followups.follow_up_date' || $aColumns[$i] == db_prefix() . 'followups.last_activity_at') {
                $_data = $_data ? e(time_ago($_data)) : '-';
            } elseif ($_data === null || $_data === '') {
                $_data = '-';
            } else {
                $_data = e($_data);
            }

            $row[] = $_data;
        }

        // Total Interactions - derived (additionalSelect, not $aColumns),
        // appended after the loop for the same column-index-alignment
        // reason documented in application/views/admin/tables/
        // my_follow_ups.php: interleaving it would shift every later
        // $aColumns entry's visible position out of sync with its SQL
        // index and silently break sort/search on this DataTable.
        $row[] = (int) $aRow['interaction_count'] . ' ' . _l('followup_column_total_interactions');

        // Options - View only for the simplified history view (no Delete
        // link here, matching "a clean CRM interaction history" - Delete
        // remains available from the full Admin/Manager All Follow-ups
        // view and from the Case Detail page itself).
        $row[] = '<a href="' . admin_url('follow_up_management/view/' . $aRow['id']) . '">' . _l('view') . '</a>';

        $row['DT_RowClass'] = 'has-row-options';
        $output['aaData'][] = $row;
    }

    return;
}

$aColumns = [
    'CASE ' . db_prefix() . 'followups.rel_type
        WHEN \'lead\' THEN ' . db_prefix() . 'leads.name
        ELSE ' . db_prefix() . 'followups.rel_type END as rel_type_name',
    db_prefix() . 'followups.follow_up_type',
    db_prefix() . 'followups.outcome',
    db_prefix() . 'followups.notes',
    db_prefix() . 'followups.follow_up_date',
    db_prefix() . 'followups.next_follow_up_date',
    'CONCAT(firstname, " ", lastname) as full_name',
    db_prefix() . 'followups.status',
];

$join = [
    'JOIN ' . db_prefix() . 'staff ON ' . db_prefix() . 'staff.staffid = ' . db_prefix() . 'followups.staff_id',
    'LEFT JOIN ' . db_prefix() . 'leads ON ' . db_prefix() . 'leads.id = ' . db_prefix() . 'followups.rel_id AND ' . db_prefix() . 'followups.rel_type="lead"',
];

$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, [
    db_prefix() . 'followups.id',
    db_prefix() . 'followups.created_by',
    db_prefix() . 'followups.rel_type',
    db_prefix() . 'followups.rel_id',
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

        if ($i == 0) {
            // related-entity name, linked
            $rel_data   = get_relation_data($aRow['rel_type'], $aRow['rel_id']);
            $rel_values = get_relation_values($rel_data, $aRow['rel_type']);
            $_data      = '<a href="' . $rel_values['link'] . '">' . e($rel_values['name']) . '</a>';
        } elseif ($aColumns[$i] == db_prefix() . 'followups.follow_up_type') {
            $_data = get_followup_type_label($_data);
        } elseif ($aColumns[$i] == db_prefix() . 'followups.outcome') {
            $_data = get_followup_outcome_label($_data);
        } elseif ($aColumns[$i] == db_prefix() . 'followups.notes') {
            $_data = process_text_content_for_display($_data);
        } elseif ($aColumns[$i] == db_prefix() . 'followups.follow_up_date' || $aColumns[$i] == db_prefix() . 'followups.next_follow_up_date') {
            $_data = $_data ? e(_dt($_data)) : '-';
        } elseif ($aColumns[$i] == db_prefix() . 'followups.status') {
            $_data = get_followup_status_label($_data);
        } else {
            $_data = e($_data);
        }

        $row[] = $_data;
    }

    // Dedicated Options column (not part of $aColumns/SQL - matches the
    // native Perfex list-page convention of a final "Options" column).
    // Links to the standalone Case Detail page (case.php), not a modal -
    // this module has no injected UI anywhere else anymore.
    $options = '<a href="' . admin_url('follow_up_management/view/' . $aRow['id']) . '">' . _l('view') . '</a>';
    if ($aRow['created_by'] == get_staff_user_id() || is_admin()) {
        $options .= ' | <a href="' . admin_url('follow_up_management/delete/' . $aRow['id']) . '" class="text-danger delete-followup">' . _l('delete') . '</a>';
    }
    $row[] = $options;

    $row['DT_RowClass'] = 'has-row-options';
    $output['aaData'][] = $row;
}
