<?php

defined('BASEPATH') or exit('No direct script access allowed');

return App_table::find('projects')
    ->outputUsing(function ($params) {
        extract($params);

        $hasPermissionEdit   = staff_can('edit',  'projects');
        $hasPermissionDelete = staff_can('delete',  'projects');
        $hasPermissionCreate = staff_can('create',  'projects');

        $aColumns = [
            db_prefix() . 'projects.id as id',
            'name',
            // Raw company value only - the old get_sql_select_client_company()
            // CASE expression returned a single value (contact name when the
            // company was ' '), which is why the list could never show both
            // company AND the primary contact. The renderer combines this
            // with the primary contact below (two lines, 'Unknown' fallbacks).
            db_prefix() . 'clients.company as company',
            '(SELECT CONCAT(firstname, \' \', lastname) FROM ' . db_prefix() . 'contacts WHERE userid=' . db_prefix() . 'projects.clientid AND is_primary=1 LIMIT 1) as customer_contact',
            '(SELECT GROUP_CONCAT(name SEPARATOR ",") FROM ' . db_prefix() . 'taggables JOIN ' . db_prefix() . 'tags ON ' . db_prefix() . 'taggables.tag_id = ' . db_prefix() . 'tags.id WHERE rel_id = ' . db_prefix() . 'projects.id and rel_type="project" ORDER by tag_order ASC) as tags',
            db_prefix() . 'projects.start_date as start_date',
            'deadline',
            '(SELECT GROUP_CONCAT(CONCAT(firstname, \' \', lastname) SEPARATOR ",") FROM ' . db_prefix() . 'project_members JOIN ' . db_prefix() . 'staff on ' . db_prefix() . 'staff.staffid = ' . db_prefix() . 'project_members.staff_id WHERE project_id=' . db_prefix() . 'projects.id ORDER BY staff_id) as members',
            'status',
            db_prefix() . 'projects.department as department',
            db_prefix() . 'projects.assigned_employee as assigned_employee',
            db_prefix() . 'projects.assigned_work as assigned_work',
            db_prefix() . 'projects.work_status as work_status',
            db_prefix() . 'projects.progress as progress',
            db_prefix() . 'projects.progress_from_tasks as progress_from_tasks',
            db_prefix() . 'projects.status_description as status_description',
            db_prefix() . 'projects.last_updated as last_updated',
        ];


        $sIndexColumn = 'id';
        $sTable       = db_prefix() . 'projects';

        $join = [
            // LEFT JOIN (not INNER): a project whose clientid points at a
            // customer that no longer exists, or at no customer at all
            // (clientid 0), must still appear in the list - its Customer
            // column then renders 'Unknown' via
            // customer_company_name_display().
            'LEFT JOIN ' . db_prefix() . 'clients ON ' . db_prefix() . 'clients.userid = ' . db_prefix() . 'projects.clientid',
        ];

        $where  = [];

        if ($filtersWhere = $this->getWhereFromRules()) {
            $where[] = $filtersWhere;
        }

        if ($clientid != '') {
            array_push($where, ' AND clientid=' . $this->ci->db->escape_str($clientid));
        }

        if (staff_cant('view', 'projects')) {
            array_push($where, ' AND ' . db_prefix() . 'projects.id IN (SELECT project_id FROM ' . db_prefix() . 'project_members WHERE staff_id=' . get_staff_user_id() . ')');
        }

        $custom_fields = get_table_custom_fields('projects');

        foreach ($custom_fields as $key => $field) {
            $selectAs = (is_cf_date($field) ? 'date_picker_cvalue_' . $key : 'cvalue_' . $key);
            array_push($customFieldsColumns, $selectAs);
            array_push($aColumns, 'ctable_' . $key . '.value as ' . $selectAs);
            array_push($join, 'LEFT JOIN ' . db_prefix() . 'customfieldsvalues as ctable_' . $key . ' ON ' . db_prefix() . 'projects.id = ctable_' . $key . '.relid AND ctable_' . $key . '.fieldto="' . $field['fieldto'] . '" AND ctable_' . $key . '.fieldid=' . $field['id']);
        }

        $join = hooks()->apply_filters('projects_table_sql_join', $join);
        $aColumns = hooks()->apply_filters('projects_table_sql_columns', $aColumns);

        // Fix for big queries. Some hosting have max_join_limit
        if (count($custom_fields) > 4) {
            @$this->ci->db->query('SET SQL_BIG_SELECTS=1');
        }

        $result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, [
            'clientid',
            '(SELECT GROUP_CONCAT(staff_id SEPARATOR ",") FROM ' . db_prefix() . 'project_members WHERE project_id=' . db_prefix() . 'projects.id ORDER BY staff_id) as members_ids',
        ]);

        $output  = $result['output'];
        $rResult = $result['rResult'];

        $CI = &get_instance();
        $CI->load->model('projects_model');

        // Reusable inline-edit dropdown data sources - loaded once outside
        // the row loop, not per-row. Same helpers the Customers module uses
        // for its Department/Employee/Work Status inline edits.
        $departments   = get_business_departments();
        $work_statuses = get_work_statuses();

        foreach ($rResult as $aRow) {
            $row = [];

            $link = admin_url('projects/view/' . $aRow['id']);

            $row[] = '<a href="' . $link . '" class="tw-font-medium">' . $aRow['id'] . '</a>';

            $name = '<a href="' . $link . '" class="tw-font-medium">' . e($aRow['name']) . '</a>';

            $name .= '<div class="row-options">';

            $name .= '<a href="' . $link . '">' . _l('view') . '</a>';

            if ($hasPermissionCreate && !$clientid) {
                $name .= ' | <a href="#" data-name="' . e($aRow['name']) . '" onclick="copy_project(' . $aRow['id'] . ', this);return false;">' . _l('copy_project') . '</a>';
            }

            if ($hasPermissionEdit) {
                $name .= ' | <a href="' . admin_url('projects/project/' . $aRow['id']) . '">' . _l('edit') . '</a>';
            }

            if ($hasPermissionDelete) {
                $name .= ' | <a href="' . admin_url('projects/delete/' . $aRow['id']) . '" class="_delete">' . _l('delete') . '</a>';
            }

            $name .= '</div>';

            $row[] = $name;

            // Customer column - two lines: Company (first) and the primary
            // contact name (second). Each line falls back to a muted
            // 'Unknown' independently, so every combination renders correctly
            // (company only / contact only / both / neither). 'Unknown' is
            // display-only - never persisted. The hidden span carries the
            // combined value for DataTables export, like the members column.
            $companyDisplay  = customer_company_name_display($aRow['company']);
            $contactDisplay  = trim((string) ($aRow['customer_contact'] ?? ''));
            $contactDisplay  = $contactDisplay !== '' ? $contactDisplay : 'Unknown';

            $companyCell = $companyDisplay === 'Unknown'
                ? '<span class="text-muted">Unknown</span>'
                : '<a href="' . admin_url('clients/client/' . $aRow['clientid']) . '">' . e($companyDisplay) . '</a>';

            $contactCell = $contactDisplay === 'Unknown'
                ? '<span class="text-muted">Unknown</span>'
                : e($contactDisplay);

            $row[] = '<div class="tw-leading-snug">' . $companyCell
                . '<div class="text-muted tw-text-xs">' . $contactCell . '</div></div>'
                . '<span class="hide">' . e($companyDisplay . ' - ' . $contactDisplay) . '</span>';

            $row[] = render_tags($aRow['tags']);

            $row[] = e(_d($aRow['start_date']));

            // Deadline (admin-only inline date edit; read-only _d() display
            // otherwise). Saves through the same update_assignment_field()
            // endpoint as every other inline edit - the server converts to
            // SQL date exactly like the edit form's Deadline field, and
            // there is no second deadline stored anywhere.
            if ($hasPermissionEdit) {
                $row[] = '<span class="project-editable-date" data-id="' . $aRow['id'] . '" data-value="' . e(_d($aRow['deadline'])) . '" onclick="project_edit_deadline(this);" style="cursor:pointer;" data-toggle="tooltip" data-title="' . e(_l('project_click_to_edit')) . '">'
                    . ($aRow['deadline'] ? e(_d($aRow['deadline'])) : '<span class="text-muted">-</span>')
                    . '</span>';
            } else {
                $row[] = e(_d($aRow['deadline']));
            }

            $membersOutput = '<div class="tw-flex -tw-space-x-1">';
            $members       = explode(',', $aRow['members']);
            $exportMembers = '';
            foreach ($members as $key => $member) {
                if ($member != '') {
                    $members_ids = explode(',', $aRow['members_ids']);
                    $member_id   = $members_ids[$key];
                    $membersOutput .= '<a href="' . admin_url('profile/' . $member_id) . '">' .
                        staff_profile_image($member_id, [
                            'tw-inline-block tw-h-7 tw-w-7 tw-rounded-full tw-ring-2 tw-ring-white',
                        ], 'small', [
                            'data-toggle' => 'tooltip',
                            'data-title'  => $member,
                        ]) . '</a>';
                    // For exporting
                    $exportMembers .= $member . ', ';
                }
            }

            $membersOutput .= '<span class="hide">' . trim($exportMembers, ', ') . '</span>';
            $membersOutput .= '</div>';
            $row[] = $membersOutput;

            // Department (admin-only inline-editable dropdown; read-only display otherwise)
            // Now a real Business Department id (get_business_departments()).
            $currentDepartmentId   = $aRow['department'];
            $currentDepartmentName = '';
            foreach ($departments as $departmentOption) {
                if ($departmentOption['id'] == $currentDepartmentId) {
                    $currentDepartmentName = $departmentOption['name'];
                    break;
                }
            }
            if ($hasPermissionEdit) {
                $deptToggleLabel  = $currentDepartmentName != '' ? e($currentDepartmentName) : _l('dropdown_non_selected_tex');
                $outputDepartment = '<div class="dropdown inline-block">';
                $outputDepartment .= '<a href="#" class="dropdown-toggle label' . ($currentDepartmentName != '' ? '' : ' label-default') . '" id="tableProjectDepartment-' . $aRow['id'] . '" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">' . $deptToggleLabel . '<i class="chevron"></i></a>';
                $outputDepartment .= '<ul class="dropdown-menu dropdown-menu-right" aria-labelledby="tableProjectDepartment-' . $aRow['id'] . '">';
                foreach ($departments as $departmentOption) {
                    if ($currentDepartmentId != $departmentOption['id']) {
                        $outputDepartment .= '<li><a href="#" onclick="project_mark_as(\'department\',\'' . e($departmentOption['id']) . '\',' . $aRow['id'] . '); return false;">' . e($departmentOption['name']) . '</a></li>';
                    }
                }
                $outputDepartment .= '</ul></div>';
                $row[] = $outputDepartment;
            } else {
                $row[] = $currentDepartmentName != '' ? e($currentDepartmentName) : '-';
            }

            // Assigned Employee - dependent on Department, exact same pattern (and same
            // get_business_department_staff() data source, real tblstaff records) as
            // the Customers list's Employee column. Cleared automatically whenever
            // Department changes - see Projects_model::update_assignment_field().
            $currentAssignedEmployeeId = $aRow['assigned_employee'];
            if ($hasPermissionEdit) {
                if ($currentDepartmentId == '') {
                    $row[] = '<span class="text-muted">-</span>';
                } else {
                    $employeeOptions     = get_business_department_staff($currentDepartmentId);
                    $currentEmployeeName = $currentAssignedEmployeeId != '' ? get_staff_full_name($currentAssignedEmployeeId) : '';
                    $empToggleLabel      = $currentEmployeeName != '' ? e($currentEmployeeName) : _l('dropdown_non_selected_tex');
                    $outputEmployee      = '<div class="dropdown inline-block">';
                    $outputEmployee .= '<a href="#" class="dropdown-toggle label' . ($currentEmployeeName != '' ? '' : ' label-default') . '" id="tableProjectEmployee-' . $aRow['id'] . '" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">' . $empToggleLabel . '<i class="chevron"></i></a>';
                    $outputEmployee .= '<ul class="dropdown-menu dropdown-menu-right" aria-labelledby="tableProjectEmployee-' . $aRow['id'] . '">';
                    foreach ($employeeOptions as $employeeOption) {
                        if ($currentAssignedEmployeeId != $employeeOption['staffid']) {
                            $outputEmployee .= '<li><a href="#" onclick="project_mark_as(\'assigned_employee\',\'' . e($employeeOption['staffid']) . '\',' . $aRow['id'] . '); return false;">' . e($employeeOption['name']) . '</a></li>';
                        }
                    }
                    $outputEmployee .= '</ul></div>';
                    $row[] = $outputEmployee;
                }
            } else {
                $row[] = $currentAssignedEmployeeId != '' ? e(get_staff_full_name($currentAssignedEmployeeId)) : '-';
            }

            // Assigned Work (admin-only inline-editable text; read-only truncated + tooltip otherwise)
            if ($hasPermissionEdit) {
                $row[] = '<span class="project-editable-text" data-id="' . $aRow['id'] . '" data-value="' . e($aRow['assigned_work']) . '" onclick="project_edit_assigned_work(this);" style="cursor:pointer;" data-toggle="tooltip" data-title="' . e(_l('project_click_to_edit')) . '">'
                    . ($aRow['assigned_work'] != '' ? e(mb_strimwidth($aRow['assigned_work'], 0, 50, '...')) : '<span class="text-muted">-</span>')
                    . '</span>';
            } else {
                $row[] = $aRow['assigned_work'] != '' ? '<span data-toggle="tooltip" data-title="' . e($aRow['assigned_work']) . '">' . e(mb_strimwidth($aRow['assigned_work'], 0, 50, '...')) . '</span>' : '-';
            }

            // Work Status (admin-only inline-editable colored dropdown). This is the only
            // status column shown in this list - the core Perfex Project status (`status`
            // column, still used internally for Kanban/task-completion/billing/notifications)
            // is intentionally not displayed here to avoid two confusing status indicators;
            // see Projects_model::update_assignment_field() and resolve_progress_value().
            $currentWorkStatus = $aRow['work_status'];
            $workStatusStyle   = '';
            if ($currentWorkStatus != '') {
                $workStatusColor = get_work_status_color($currentWorkStatus);
                $workStatusStyle = ' style="color:' . $workStatusColor . ';border:1px solid ' . adjust_hex_brightness($workStatusColor, 0.4) . ';background: ' . adjust_hex_brightness($workStatusColor, 0.04) . ';"';
            }
            if ($hasPermissionEdit) {
                $workStatusToggleLabel = $currentWorkStatus != '' ? e($currentWorkStatus) : _l('dropdown_non_selected_tex');
                $outputWorkStatus      = '<div class="dropdown inline-block">';
                $outputWorkStatus .= '<a href="#" class="dropdown-toggle label' . ($currentWorkStatus != '' ? '' : ' label-default') . '" id="tableProjectWorkStatus-' . $aRow['id'] . '" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"' . $workStatusStyle . '>' . $workStatusToggleLabel . '<i class="chevron"></i></a>';
                $outputWorkStatus .= '<ul class="dropdown-menu dropdown-menu-right" aria-labelledby="tableProjectWorkStatus-' . $aRow['id'] . '">';
                foreach ($work_statuses as $statusOption) {
                    if ($currentWorkStatus != $statusOption) {
                        $outputWorkStatus .= '<li><a href="#" onclick="project_mark_as(\'work_status\',\'' . e($statusOption) . '\',' . $aRow['id'] . '); return false;">' . e($statusOption) . '</a></li>';
                    }
                }
                $outputWorkStatus .= '</ul></div>';
                $row[] = $outputWorkStatus;
            } else {
                $row[] = $currentWorkStatus != '' ? '<span class="label"' . $workStatusStyle . '>' . e($currentWorkStatus) . '</span>' : '-';
            }

            // Progress - single shared resolver (Projects_model::resolve_progress_value()),
            // also reused by the Customer Portal Projects list so both always match.
            // Admin-only inline dropdown (same get_progress_options() data as the
            // project edit form), otherwise read-only - Progress and Work Status
            // are co-synced on write (reconcile_single_status()), so editing either
            // keeps them agreeing.
            $progressValue = $CI->projects_model->resolve_progress_value($aRow);
            $progressColor = get_progress_bar_color($progressValue);
            $progressBar   = '<div class="progress progress-bar-mini" style="min-width:100px;"><div class="progress-bar" role="progressbar" aria-valuenow="' . $progressValue . '" aria-valuemin="0" aria-valuemax="100" style="width: ' . $progressValue . '%;background-color:' . $progressColor . ';">' . $progressValue . '%</div></div>';

            if ($hasPermissionEdit) {
                $outputProgress  = '<div class="dropdown inline-block">';
                $outputProgress .= '<a href="#" class="dropdown-toggle" id="tableProjectProgress-' . $aRow['id'] . '" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">' . $progressBar . '</a>';
                $outputProgress .= '<ul class="dropdown-menu dropdown-menu-right" aria-labelledby="tableProjectProgress-' . $aRow['id'] . '">';
                foreach (get_progress_options() as $progressOption) {
                    if ((int) $progressOption != $progressValue) {
                        $outputProgress .= '<li><a href="#" onclick="project_mark_as(\'progress\',\'' . (int) $progressOption . '\',' . $aRow['id'] . '); return false;">' . (int) $progressOption . '%</a></li>';
                    }
                }
                $outputProgress .= '</ul></div>';
                $row[] = $outputProgress;
            } else {
                $row[] = $progressBar;
            }

            // Status Description
            $row[] = $aRow['status_description'] != '' ? '<span data-toggle="tooltip" data-title="' . e($aRow['status_description']) . '">' . e(mb_strimwidth($aRow['status_description'], 0, 50, '...')) . '</span>' : '-';

            // Last Updated
            $row[] = $aRow['last_updated'] != '' ? '<span class="text-has-action" data-toggle="tooltip" data-title="' . e(_dt($aRow['last_updated'])) . '">' . e(time_ago($aRow['last_updated'])) . '</span>' : '-';

            // Custom fields add values
            foreach ($customFieldsColumns as $customFieldColumn) {
                $row[] = (strpos($customFieldColumn, 'date_picker_') !== false ? _d($aRow[$customFieldColumn]) : $aRow[$customFieldColumn]);
            }

            $row['DT_RowClass'] = 'has-row-options';

            $row = hooks()->apply_filters('projects_table_row_data', $row, $aRow);

            $output['aaData'][] = $row;
        }
        return $output;
    })->setRules([
        App_table_filter::new('name','TextRule')->label(_l('project_name')),
        App_table_filter::new('start_date','DateRule')->label(_l('project_start_date')),
        App_table_filter::new('deadline','DateRule')->label(_l('project_deadline')),
        App_table_filter::new('billing_type','SelectRule')->label(_l('project_billing_type'))->options(function($ci) {
            return [
                ['value'=>1,'label'=>_l('project_billing_type_fixed_cost')],
                ['value'=>2,'label'=>_l('project_billing_type_project_hours')],
                ['value'=>3,'label'=>_l('project_billing_type_project_task_hours_hourly_rate')],
            ];
        }),
        App_table_filter::new('status','MultiSelectRule')->label(_l('project_status'))->options(function($ci){
                return collect($ci->projects_model->get_project_statuses())->map(fn ($data) => [
                    'value' => $data['id'],
                    'label' => $data['name'],
                ])->all();
        }),

        App_table_filter::new('members', 'MultiSelectRule')->label(_l('project_team_members'))
            ->isVisible(fn () => staff_can('view', 'projects'))
            ->options(function ($ci) {
                return collect($ci->projects_model->get_distinct_projects_members())->map(function ($staff) {
                    return [
                        'value' => $staff['staff_id'],
                        'label' => get_staff_full_name($staff['staff_id'])
                    ];
                })->all();
            })->raw(function ($value, $operator, $sqlOperator) {
                $dbPrefix = db_prefix();
                $sqlOperator = $sqlOperator['operator'];
                return "({$dbPrefix}projects.id IN (SELECT project_id FROM {$dbPrefix}project_members WHERE staff_id $sqlOperator ('" . implode("','", $value) . "')))";
            })
    ]);
