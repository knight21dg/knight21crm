<?php

defined('BASEPATH') or exit('No direct script access allowed');

// "My Cases" work queue feed - same data_tables_init() convention as
// all_follow_ups.php, extended with the Case-level columns added in
// migration 356 and the Admin/Manager/Telecaller visibility scope from
// get_followup_case_visibility_where() (reused as-is, not duplicated).
$aColumns = [
    db_prefix() . 'followups.id',
    'CASE ' . db_prefix() . 'followups.rel_type
        WHEN \'lead\' THEN ' . db_prefix() . 'leads.name
        ELSE ' . db_prefix() . 'followups.rel_type END as rel_type_name',
    'CASE ' . db_prefix() . 'followups.rel_type
        WHEN \'lead\' THEN ' . db_prefix() . 'leads.company
        ELSE "" END as company_name',
    db_prefix() . 'business_departments.name as department_name',
    db_prefix() . 'followups.priority',
    db_prefix() . 'followups.case_status',
    db_prefix() . 'followups.next_follow_up_date',
    db_prefix() . 'followups.last_activity_at',
    'CONCAT(assigned_staff.firstname, " ", assigned_staff.lastname) as assigned_staff_name',
    db_prefix() . 'followups.date_created',
];

$sIndexColumn = 'id';
$sTable       = db_prefix() . 'followups';
$where        = [];

// Admin/Manager/Telecaller visibility - the single shared rule, not
// re-implemented here.
$visibility = get_followup_case_visibility_where();
if ($visibility) {
    $where[] = $visibility;
}

// Filter toolbar (Priority / Department / Assigned Staff / Case Status /
// Date Range) - same fnserverparams convention as all_follow_ups.php.
// Native DataTables search box covers the "Search" filter already.
$CI = &get_instance();
if ($CI->input->post('priority') !== null && $CI->input->post('priority') !== '') {
    $where[] = 'AND ' . db_prefix() . 'followups.priority="' . $CI->db->escape_str($CI->input->post('priority')) . '"';
}
if ($CI->input->post('department_id') !== null && $CI->input->post('department_id') !== '') {
    $where[] = 'AND ' . db_prefix() . 'followups.department_id=' . (int) $CI->input->post('department_id');
}
if ($CI->input->post('staff_id') !== null && $CI->input->post('staff_id') !== '') {
    $where[] = 'AND ' . db_prefix() . 'followups.assigned_staff_id=' . (int) $CI->input->post('staff_id');
}
if ($CI->input->post('case_status') !== null && $CI->input->post('case_status') !== '') {
    $where[] = 'AND ' . db_prefix() . 'followups.case_status="' . $CI->db->escape_str($CI->input->post('case_status')) . '"';
}
if ($CI->input->post('date_from') !== null && $CI->input->post('date_from') !== '') {
    $where[] = 'AND ' . db_prefix() . 'followups.date_created >= "' . $CI->db->escape_str(to_sql_date($CI->input->post('date_from'))) . ' 00:00:00"';
}
if ($CI->input->post('date_to') !== null && $CI->input->post('date_to') !== '') {
    $where[] = 'AND ' . db_prefix() . 'followups.date_created <= "' . $CI->db->escape_str(to_sql_date($CI->input->post('date_to'))) . ' 23:59:59"';
}

$join = [
    'LEFT JOIN ' . db_prefix() . 'leads ON ' . db_prefix() . 'leads.id = ' . db_prefix() . 'followups.rel_id AND ' . db_prefix() . 'followups.rel_type="lead"',
    'LEFT JOIN ' . db_prefix() . 'business_departments ON ' . db_prefix() . 'business_departments.id = ' . db_prefix() . 'followups.department_id',
    'LEFT JOIN ' . db_prefix() . 'staff assigned_staff ON assigned_staff.staffid = ' . db_prefix() . 'followups.assigned_staff_id',
];

$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, [
    db_prefix() . 'followups.id',
    db_prefix() . 'followups.rel_type',
    db_prefix() . 'followups.rel_id',
    db_prefix() . 'followups.assigned_staff_id',
    db_prefix() . 'followups.created_by',
    db_prefix() . 'followups.case_status',
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
            $_data = '<a href="' . admin_url('follow_up_management/view/' . $_data) . '">#' . e($_data) . '</a>';
        } elseif ($aColumns[$i] == db_prefix() . 'followups.priority') {
            $_data = '<span class="label label-' . get_followup_priority_class($_data) . '">' . e(get_followup_priority_label($_data)) . '</span>';
        } elseif ($aColumns[$i] == db_prefix() . 'followups.case_status') {
            $_data = '<span class="label label-' . ($_data == 'open' ? 'info' : 'default') . '">' . e(get_followup_case_status_label($_data)) . '</span>';
        } elseif ($aColumns[$i] == db_prefix() . 'followups.next_follow_up_date' || $aColumns[$i] == db_prefix() . 'followups.last_activity_at' || $aColumns[$i] == db_prefix() . 'followups.date_created') {
            $_data = $_data ? e(_dt($_data)) : '-';
        } elseif (strpos($aColumns[$i], 'assigned_staff_name') !== false) {
            $_data = trim((string) $_data) !== '' ? e(trim($_data)) : '-';
        } elseif (strpos($aColumns[$i], 'company_name') !== false || strpos($aColumns[$i], 'department_name') !== false) {
            $_data = $_data ? e($_data) : '-';
        } else {
            $_data = e($_data);
        }

        $row[] = $_data;
    }

    // Options - Open Case always available (page-level gate already
    // required 'view leads'); the mutating actions only for whoever can
    // actually act on this specific case (assigned staff, creator, admin),
    // mirroring case_access_denied() in the controller.
    $view_url = admin_url('follow_up_management/view/' . $aRow['id']);
    $can_act  = is_admin() || $aRow['assigned_staff_id'] == get_staff_user_id() || $aRow['created_by'] == get_staff_user_id();

    $options = '<div class="btn-group">';
    $options .= '<a href="' . $view_url . '" class="btn btn-default btn-icon" title="' . _l('view') . '"><i class="fa-regular fa-eye"></i></a>';

    if ($can_act) {
        $options .= '<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown"><span class="caret"></span></button>';
        $options .= '<ul class="dropdown-menu dropdown-menu-right">';
        $options .= '<li><a href="' . $view_url . '?action=log_call">' . _l('case_action_log_call') . '</a></li>';
        $options .= '<li><a href="' . $view_url . '?action=schedule_reminder">' . _l('case_action_schedule_reminder') . '</a></li>';
        $options .= '<li><a href="' . $view_url . '?action=schedule_meeting">' . _l('case_action_schedule_meeting') . '</a></li>';
        $options .= '<li><a href="' . $view_url . '?action=add_note">' . _l('case_action_add_note') . '</a></li>';
        if ($aRow['case_status'] == 'open') {
            $options .= '<li><a href="' . $view_url . '?action=close_case">' . _l('case_action_close_case') . '</a></li>';
        }
        $options .= '</ul>';
    }
    $options .= '</div>';

    $row[] = $options;

    $row['DT_RowClass'] = 'has-row-options';
    $output['aaData'][] = $row;
}
