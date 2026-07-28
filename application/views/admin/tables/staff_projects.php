<?php

defined('BASEPATH') or exit('No direct script access allowed');

$aColumns         = ['name', 'start_date', 'deadline', 'status'];
$sIndexColumn     = 'id';
$sTable           = db_prefix() . 'projects';
$additionalSelect = ['id'];
$join             = [
    'JOIN ' . db_prefix() . 'clients ON ' . db_prefix() . 'clients.userid = ' . db_prefix() . 'projects.clientid',
    ];

// start_date exists on both tblprojects and tblclients (the latter added
// later for an unrelated Customers work-tracking feature), so the JOIN
// below makes a bare "start_date" ambiguous to MySQL. Qualified only for
// the query - $aColumns itself stays bare because the render loop below
// indexes $aRow by these exact names, and the "as start_date" alias keeps
// that key unchanged in the result set.
$sqlColumns = $aColumns;
$sqlColumns[array_search('start_date', $sqlColumns)] = db_prefix() . 'projects.start_date as start_date';

$where    = [];
$staff_id = get_staff_user_id();
if ($this->ci->input->post('staff_id')) {
    $staff_id = $this->ci->input->post('staff_id');
}

// Request from dashboard or profile, finished and canceled not need to be shown
array_push($where, ' AND status != 4 AND status != 5');

array_push($where, ' AND ' . db_prefix() . 'projects.id IN (SELECT project_id FROM ' . db_prefix() . 'project_members WHERE staff_id=' . $this->ci->db->escape_str($staff_id) . ')');

$result = data_tables_init($sqlColumns, $sIndexColumn, $sTable, $join, $where, $additionalSelect);

$output  = $result['output'];
$rResult = $result['rResult'];

foreach ($rResult as $aRow) {
    $row = [];
    for ($i = 0 ; $i < count($aColumns) ; $i++) {
        $_data = $aRow[ $aColumns[$i] ];

        if ($aColumns[$i] == 'start_date' || $aColumns[$i] == 'deadline') {
            $_data = e(_d($_data));
        } elseif ($aColumns[$i] == 'name') {
            $_data = '<a href="' . admin_url('projects/view/' . $aRow['id']) . '">' . e($_data) . '</a>';
        } elseif ($aColumns[$i] == 'status') {
            $status = get_project_status_by_id($_data);
            $status = '<span class="label label project-status-' . $_data . '" style="color:' . $status['color'] . ';border:1px solid ' . $status['color'] . '">' . e($status['name']) . '</span>';
            $_data  = $status;
        }

        $row[] = $_data;
    }
    $output['aaData'][] = $row;
}