<?php

defined('BASEPATH') or exit('No direct script access allowed');

return App_table::find('clients')
    ->outputUsing(function ($params) {
        extract($params);

        $hasPermissionDelete = staff_can('delete', 'customers');

        $custom_fields = get_table_custom_fields('customers');
        $this->ci->db->query("SET sql_mode = ''");

        $departments = get_business_departments();

        $aColumns = [
            '1',
            db_prefix() . 'clients.userid as userid',
            'company',
            'CONCAT(' . db_prefix() . 'contacts.firstname, " ", ' . db_prefix() . 'contacts.lastname) as fullname',
            db_prefix() . 'contacts.email as email',
            db_prefix() . 'clients.phonenumber as phonenumber',
            db_prefix() . 'clients.active',
            '(SELECT GROUP_CONCAT(name SEPARATOR ",") FROM ' . db_prefix() . 'customer_groups JOIN ' . db_prefix() . 'customers_groups ON ' . db_prefix() . 'customer_groups.groupid = ' . db_prefix() . 'customers_groups.id WHERE customer_id = ' . db_prefix() . 'clients.userid ORDER by name ASC) as customerGroups',
            // Work-tracking columns (Department, Employee, Assigned Work, Work
            // Status, Due Date, Progress, Last Updated) - the Customers list
            // reads them from the customer's latest related project record
            // (single source of truth: the project owns these values; the
            // Admin Projects list, employee panels and the Customer Portal
            // all read the same project record), falling back to the
            // customer row's own columns only when the customer has no
            // project yet. COALESCE(...) expressions with explicit aliases
            // pass through data_tables_init() unchanged (confirmed in
            // application/helpers/datatables_helper.php), so sorting and
            // search operate on the effective values.
            'COALESCE((SELECT ' . db_prefix() . 'projects.department FROM ' . db_prefix() . 'projects WHERE clientid = ' . db_prefix() . 'clients.userid ORDER BY id DESC LIMIT 1), ' . db_prefix() . 'clients.department) as effective_department',
            'COALESCE((SELECT ' . db_prefix() . 'projects.assigned_employee FROM ' . db_prefix() . 'projects WHERE clientid = ' . db_prefix() . 'clients.userid ORDER BY id DESC LIMIT 1), ' . db_prefix() . 'clients.employee_name) as effective_employee',
            lead_converted_from_sql_case() . ' as converted_from',
            lead_converted_by_name_expr() . ' as converted_by_name',
            'COALESCE((SELECT ' . db_prefix() . 'projects.assigned_work FROM ' . db_prefix() . 'projects WHERE clientid = ' . db_prefix() . 'clients.userid ORDER BY id DESC LIMIT 1), ' . db_prefix() . 'clients.assigned_work) as effective_assigned_work',
            'COALESCE((SELECT ' . db_prefix() . 'projects.work_status FROM ' . db_prefix() . 'projects WHERE clientid = ' . db_prefix() . 'clients.userid ORDER BY id DESC LIMIT 1), ' . db_prefix() . 'clients.work_status) as effective_work_status',
            'COALESCE((SELECT ' . db_prefix() . 'projects.deadline FROM ' . db_prefix() . 'projects WHERE clientid = ' . db_prefix() . 'clients.userid ORDER BY id DESC LIMIT 1), ' . db_prefix() . 'clients.due_date) as effective_due_date',
            'COALESCE((SELECT ' . db_prefix() . 'projects.progress FROM ' . db_prefix() . 'projects WHERE clientid = ' . db_prefix() . 'clients.userid ORDER BY id DESC LIMIT 1), ' . db_prefix() . 'clients.progress) as effective_progress',
            'COALESCE((SELECT ' . db_prefix() . 'projects.last_updated FROM ' . db_prefix() . 'projects WHERE clientid = ' . db_prefix() . 'clients.userid ORDER BY id DESC LIMIT 1), ' . db_prefix() . 'clients.last_updated) as effective_last_updated',
            db_prefix() . 'clients.datecreated as datecreated',
        ];

        $sIndexColumn = 'userid';
        $sTable       = db_prefix() . 'clients';
        $where        = [];

        if ($filtersWhere = $this->getWhereFromRules()) {
            $where[] = $filtersWhere;
        }

        // Converted From - a plain dropdown above the table (not the
        // Filters/New Filter/Add Rule panel), same pattern as every other
        // simple single-value filter on this codebase's portal pages
        // (e.g. modules/field_portal's My Leads Status filter): the
        // dropdown's value is posted directly as 'converted_from' via
        // initDataTable()'s server-params map and read here.
        $converted_from_filter = $this->ci->input->post('converted_from');
        if (in_array($converted_from_filter, ['field_executive', 'telecaller', 'admin'], true)) {
            $where[] = 'AND ' . lead_converted_from_sql_case() . " = '" . $converted_from_filter . "'";
        }

        // Conversion Report date filter - same plain-POST-param pattern as
        // converted_from above, resolved via the ONE shared date-range
        // resolver (application/helpers/lead_workspace_helper.php) also
        // used by Clients::conversion_summary(), so the table and the KPI
        // cards above it always agree on what "This Month" etc. means.
        $converted_date_where = customer_conversion_date_where(
            $this->ci->input->post('converted_date_range'),
            $this->ci->input->post('converted_date_from'),
            $this->ci->input->post('converted_date_to')
        );
        if ($converted_date_where) {
            $where[] = 'AND ' . $converted_date_where;
        }

        $join = [
            'LEFT JOIN ' . db_prefix() . 'contacts ON ' . db_prefix() . 'contacts.userid=' . db_prefix() . 'clients.userid AND ' . db_prefix() . 'contacts.is_primary=1',
            // "Converted From" and "Converted By" are both correlated
            // subqueries (lead_converted_from_sql_case() /
            // lead_converted_by_name_expr(), application/helpers/
            // lead_workspace_helper.php) built from the SAME
            // lead_conversion_branches() array, so they always agree on
            // which workflow branch fired - no JOIN needed here, and
            // deliberately no JOIN to tblstaff by a stable alias, since
            // that JOIN previously collided with tblcontacts'
            // firstname/lastname/email columns (ambiguous-column bug).
        ];

        foreach ($custom_fields as $key => $field) {
            $selectAs = (is_cf_date($field) ? 'date_picker_cvalue_' . $key : 'cvalue_' . $key);
            array_push($customFieldsColumns, $selectAs);
            array_push($aColumns, 'ctable_' . $key . '.value as ' . $selectAs);
            array_push($join, 'LEFT JOIN ' . db_prefix() . 'customfieldsvalues as ctable_' . $key . ' ON ' . db_prefix() . 'clients.userid = ctable_' . $key . '.relid AND ctable_' . $key . '.fieldto="' . $field['fieldto'] . '" AND ctable_' . $key . '.fieldid=' . $field['id']);
        }

        $join = hooks()->apply_filters('customers_table_sql_join', $join);

        if (staff_cant('view', 'customers')) {
            array_push($where, 'AND ' . db_prefix() . 'clients.userid IN (SELECT customer_id FROM ' . db_prefix() . 'customer_admins WHERE staff_id=' . get_staff_user_id() . ')');
        }

        $aColumns = hooks()->apply_filters('customers_table_sql_columns', $aColumns);

        // Fix for big queries. Some hosting have max_join_limit
        if (count($custom_fields) > 4) {
            @$this->ci->db->query('SET SQL_BIG_SELECTS=1');
        }

        $result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, [
            db_prefix() . 'contacts.id as contact_id',
            db_prefix() . 'contacts.lastname as lastname',
            db_prefix() . 'clients.zip as zip',
            'registration_confirmed',
            'vat',
        ]);

        $output  = $result['output'];
        $rResult = $result['rResult'];

        foreach ($rResult as $aRow) {
            $row = [];

            // Bulk actions
            $row[] = '<div class="checkbox"><input type="checkbox" value="' . $aRow['userid'] . '"><label></label></div>';
            // User id
            $row[] = $aRow['userid'];

            // Company - the actual customer/company name only. A customer created
            // without a company name renders the fixed literal 'Unknown'
            // (single shared rule: customer_company_name_display(),
            // application/helpers/clients_helper.php) - NEVER the primary
            // contact's profile. The old fallback used
            // _l('no_company_view_profile'), which the language file
            // defines as 'Person - View Profile' - that string made the
            // Company column act like a contact column and is no longer
            // used anywhere in this table.
            $isPerson = false;

            if (trim((string) $aRow['company']) === '') {
                $isPerson = true;
            }

            $company = e(customer_company_name_display($aRow['company']));

            $url = admin_url('clients/client/' . $aRow['userid']);

            if ($isPerson && $aRow['contact_id']) {
                $url .= '?contactid=' . $aRow['contact_id'];
            }

            $company = '<a href="' . $url . '" class="tw-font-medium">' . $company . '</a>';

            $company .= '<div class="row-options">';
            $company .= '<a href="' . admin_url('clients/client/' . $aRow['userid'] . ($isPerson && $aRow['contact_id'] ? '?group=contacts' : '')) . '">' . _l('view') . '</a>';

            if (staff_can('edit', 'customers')) {
                $company .= ' | <a href="' . admin_url('clients/client/' . $aRow['userid']) . '">' . _l('edit') . '</a>';
            }

            if ($aRow['registration_confirmed'] == 0 && is_admin()) {
                $company .= ' | <a href="' . admin_url('clients/confirm_registration/' . $aRow['userid']) . '" class="text-success bold">' . _l('confirm_registration') . '</a>';
            }

            if (! $isPerson) {
                $company .= ' | <a href="' . admin_url('clients/client/' . $aRow['userid'] . '?group=contacts') . '">' . _l('customer_contacts') . '</a>';
            }

            if ($hasPermissionDelete) {
                $company .= ' | <a href="' . admin_url('clients/delete/' . $aRow['userid']) . '" class="_delete">' . _l('delete') . '</a>';
            }

            $company .= '</div>';

            $row[] = $company;

            // Primary contact
            $row[] = ($aRow['contact_id'] ? '<a href="' . admin_url('clients/client/' . $aRow['userid'] . '?contactid=' . $aRow['contact_id']) . '" target="_blank" class="tw-font-medium">' . e(trim($aRow['fullname'])) . '</a>' : '');

            // Primary contact email
            $row[] = ($aRow['email'] ? '<a href="mailto:' . e($aRow['email']) . '">' . e($aRow['email']) . '</a>' : '');

            // Primary contact phone
            $row[] = ($aRow['phonenumber'] ? '<a href="tel:' . e($aRow['phonenumber']) . '">' . e($aRow['phonenumber']) . '</a>' : '');

            // Toggle active/inactive customer
            $toggleActive = '<div class="onoffswitch" data-toggle="tooltip" data-title="' . _l('customer_active_inactive_help') . '">
    <input type="checkbox"' . ($aRow['registration_confirmed'] == 0 ? ' disabled' : '') . ' data-switch-url="' . admin_url() . 'clients/change_client_status" name="onoffswitch" class="onoffswitch-checkbox" id="' . $aRow['userid'] . '" data-id="' . $aRow['userid'] . '" ' . ($aRow[db_prefix() . 'clients.active'] == 1 ? 'checked' : '') . '>
    <label class="onoffswitch-label" for="' . $aRow['userid'] . '"></label>
    </div>';

            // For exporting
            $toggleActive .= '<span class="hide">' . ($aRow[db_prefix() . 'clients.active'] == 1 ? _l('is_active_export') : _l('is_not_active_export')) . '</span>';

            $row[] = $toggleActive;

            // Customer groups parsing
            $groupsRow = '';
            if ($aRow['customerGroups']) {
                $groups = explode(',', $aRow['customerGroups']);

                foreach ($groups as $group) {
                    $groupsRow .= '<span class="label label-default mleft5 customer-group-list pointer">' . e($group) . '</span>';
                }
            }

            $row[] = $groupsRow;

            // Department (display only - project assignment is edited from
            // the Project page, never from the Customer page)
            $currentDepartmentId = $aRow['effective_department'];
            $currentDepartmentName = '';
            foreach ($departments as $departmentOption) {
                if ($departmentOption['id'] == $currentDepartmentId) {
                    $currentDepartmentName = $departmentOption['name'];
                    break;
                }
            }
            $row[] = $currentDepartmentName != '' ? '<span class="label label-default">' . e($currentDepartmentName) . '</span>' : '<span class="text-muted">-</span>';

            // Employee (display only - project assignment is edited from
            // the Project page, never from the Customer page)
            $currentEmployeeId = $aRow['effective_employee'];
            $currentEmployeeName = $currentEmployeeId != '' ? get_staff_full_name($currentEmployeeId) : '';
            $row[] = $currentEmployeeName != '' ? '<span class="label label-info">' . e($currentEmployeeName) . '</span>' : '<span class="text-muted">-</span>';

            // Converted From - which DEPARTMENT/workflow generated this
            // customer (no Lead -> Admin; Lead ever handed to a confirmed
            // Telecalling-department Case -> Telecaller; otherwise ->
            // Field Executive), via lead_converted_from_sql_case()
            // (application/helpers/lead_workspace_helper.php), joined
            // through tblclients.leadid. Every customer resolves to a real
            // bucket - '-' is only a defensive fallback that should never
            // actually fire. Read-only - this reflects history, it isn't
            // something to reassign here.
            $row[] = $aRow['converted_from']
                ? '<span class="label label-default">' . e(lead_converted_from_role_label($aRow['converted_from'])) . '</span>'
                : '<span class="text-muted">-</span>';

            // Converted By - the actual staff member who performed the
            // conversion (a fact, independent of the Converted From
            // bucket above): tblleads.converted_by when recorded, else the
            // bucket-appropriate staff (lead_converted_by_name_expr()).
            // Resolved to a name directly in SQL rather than a staff id +
            // PHP lookup, so the generic search box and column sort can
            // match/order by the name itself.
            $row[] = $aRow['converted_by_name'] ? e($aRow['converted_by_name']) : '-';

            // Assigned Work (read-only, truncated + full text on tooltip)
            $row[] = $aRow['effective_assigned_work'] ? '<span data-toggle="tooltip" data-title="' . e($aRow['effective_assigned_work']) . '">' . e(mb_strimwidth($aRow['effective_assigned_work'], 0, 50, '...')) . '</span>' : '';

            // Work Status (display only - project assignment is edited from
            // the Project page, never from the Customer page). Keeps the
            // color coding, but renders as a plain label, not a dropdown.
            $currentWorkStatus = $aRow['effective_work_status'];
            $statusLabelText   = $currentWorkStatus ? e($currentWorkStatus) : _l('client_work_status_not_started');
            $statusLabelStyle  = '';
            if ($currentWorkStatus) {
                $statusColor      = get_work_status_color($currentWorkStatus);
                $statusLabelStyle = ' style="color:' . $statusColor . ';border:1px solid ' . adjust_hex_brightness($statusColor, 0.4) . ';background: ' . adjust_hex_brightness($statusColor, 0.04) . ';"';
            }
            $row[] = '<span class="label' . ($currentWorkStatus ? '' : ' label-default') . '"' . $statusLabelStyle . '>' . $statusLabelText . '</span>';

            // Due Date (d-M-Y, color-coded)
            $dueDateClass = get_due_date_class($aRow['effective_due_date'], $aRow['effective_work_status']);
            $row[]        = $aRow['effective_due_date'] ? '<span class="' . $dueDateClass . '">' . e(date('d-M-Y', strtotime($aRow['effective_due_date']))) . '</span>' : '';

            // Progress (read-only Bootstrap progress bar - display only, edited from the Customer Edit page).
            // Resolved through the same Work Status -> Progress rule used on
            // write (resolve_client_progress_for_status(), shared with
            // Clients_model) rather than trusting the stored column
            // directly, so a row whose progress drifted out of sync with
            // its status (e.g. legacy data from before this rule existed)
            // always renders correctly without needing a data migration.
            $progressValue  = resolve_client_progress_for_status($currentWorkStatus, (int) $aRow['effective_progress']);
            $progressColor  = get_progress_bar_color($progressValue);
            $row[]          = '<div class="progress progress-bar-mini" style="min-width:100px;"><div class="progress-bar" role="progressbar" aria-valuenow="' . $progressValue . '" aria-valuemin="0" aria-valuemax="100" data-percent="' . $progressValue . '" style="width: ' . $progressValue . '%;background-color:' . $progressColor . ';">' . $progressValue . '%</div></div>';

            // Last Updated
            $row[] = $aRow['effective_last_updated'] ? '<span class="text-has-action" data-toggle="tooltip" data-title="' . e(_dt($aRow['effective_last_updated'])) . '">' . e(time_ago($aRow['effective_last_updated'])) . '</span>' : '-';

            $row[] = e(_dt($aRow['datecreated']));

            // Custom fields add values
            foreach ($customFieldsColumns as $customFieldColumn) {
                $row[] = (strpos($customFieldColumn, 'date_picker_') !== false ? _d($aRow[$customFieldColumn]) : $aRow[$customFieldColumn]);
            }

            $row['DT_RowClass'] = 'has-row-options has-border-left';

            if ($aRow['registration_confirmed'] == 0) {
                $row['DT_RowClass'] .= ' row-border-warning requires-confirmation';
                $row['Data_Title']  = _l('customer_requires_registration_confirmation');
                $row['Data_Toggle'] = 'tooltip';
            }

            if ($aRow[db_prefix() . 'clients.active'] == 0) {
                $row['DT_RowClass'] .= ' secondary';
            }

            $row = hooks()->apply_filters('customers_table_row_data', $row, $aRow);

            $output['aaData'][] = $row;
        }

        return $output;
    })->setRules([
        App_table_filter::new('phonenumber', 'TextRule')->label(_l('clients_phone')),
        App_table_filter::new('active', 'BooleanRule')->label(_l('customer_active')),
        App_table_filter::new('invoice_statuses', 'MultiSelectRule')->label(_l('invoices'))
            ->options(function ($ci) {
                $ci->load->model('invoices_model');

                return collect($ci->invoices_model->get_statuses())->map(fn ($status) => [
                    'value' => $status,
                    'label' => _l('customer_have_invoices_by', format_invoice_status($status, '', false)),
                ]);
            })
            ->raw(function ($value, $operator, $sqlOperator) {
                return db_prefix() . 'clients.userid IN (SELECT clientid FROM ' . db_prefix() . 'invoices WHERE status ' . $sqlOperator['operator'] . ' (' . implode(', ', $value) . '))';
            }),

        App_table_filter::new('estimate_statuses', 'MultiSelectRule')->label(_l('estimates'))
            ->options(function ($ci) {
                $ci->load->model('estimates_model');

                return collect($ci->estimates_model->get_statuses())->map(fn ($status) => [
                    'value' => $status,
                    'label' => _l('customer_have_estimates_by', format_estimate_status($status, '', false)),
                ]);
            })
            ->raw(function ($value, $operator, $sqlOperator) {
                return db_prefix() . 'clients.userid IN (SELECT clientid FROM ' . db_prefix() . 'estimates WHERE status ' . $sqlOperator['operator'] . ' (' . implode(', ', $value) . '))';
            }),

        App_table_filter::new('proposal_statuses', 'MultiSelectRule')->label(_l('proposals'))
            ->options(function ($ci) {
                $ci->load->model('proposals_model');

                return collect($ci->proposals_model->get_statuses())->map(fn ($status) => [
                    'value' => $status,
                    'label' => _l('customer_have_proposals_by', format_proposal_status($status, '', false)),
                ]);
            })
            ->raw(function ($value, $operator, $sqlOperator) {
                return db_prefix() . 'clients.userid IN (SELECT rel_id FROM ' . db_prefix() . 'proposals WHERE status ' . $sqlOperator['operator'] . ' (' . implode(', ', $value) . ') AND rel_type="customer")';
            }),

        App_table_filter::new('project_statuses', 'MultiSelectRule')->label(_l('projects'))
            ->options(function ($ci) {
                $ci->load->model('projects_model');

                return collect($ci->projects_model->get_project_statuses())->map(fn ($data) => [
                    'value' => $data['id'],
                    'label' => _l('customer_have_projects_by', $data['name']),
                ]);
            })->raw(function ($value, $operator, $sqlOperator) {
                return db_prefix() . 'clients.userid IN (SELECT clientid FROM ' . db_prefix() . 'projects WHERE status ' . $sqlOperator['operator'] . ' (' . implode(', ', $value) . '))';
            }),

        App_table_filter::new('contracts_types', 'MultiSelectRule')->label(_l('contract_types'))
            ->options(function ($ci) {
                $ci->load->model('contracts_model');

                return collect($ci->contracts_model->get_contract_types())->map(fn ($data) => [
                    'value' => $data['id'],
                    'label' => _l('customer_have_contracts_by_type', $data['name']),
                ]);
            })
            ->raw(function ($value, $operator, $sqlOperator) {
                return db_prefix() . 'clients.userid IN (SELECT client FROM ' . db_prefix() . 'contracts WHERE contract_type ' . $sqlOperator['operator'] . ' (' . implode(', ', $value) . '))';
            }),
        App_table_filter::new('city', 'TextRule')->label(_l('clients_city')),
        App_table_filter::new('zip', 'TextRule')->label(_l('clients_zip')),
        App_table_filter::new('state', 'TextRule')->label(_l('clients_state')),
        App_table_filter::new('country', 'SelectRule')->label(_l('clients_country'))
            ->options(function ($ci) {
                return collect($ci->clients_model->get_clients_distinct_countries())->map(fn ($data) => [
                    'value' => $data['country_id'],
                    'label' => $data['short_name'],
                ]);
            }),
        App_table_filter::new('customer_admins', 'MultiSelectRule')->label(_l('responsible_admin'))
            ->isVisible(fn () => staff_can('create', 'customers') || staff_can('edit', 'customers'))
            ->options(function ($ci) {
                return collect($ci->clients_model->get_customers_admin_unique_ids())->map(fn ($data) => [
                    'value' => $data['staff_id'],
                    'label' => get_staff_full_name($data['staff_id']),
                ]);
            })
            ->raw(function ($value, $operator, $sqlOperator) {
                return db_prefix() . 'clients.userid IN (SELECT customer_id FROM ' . db_prefix() . 'customer_admins WHERE staff_id ' . $sqlOperator['operator'] . ' (' . implode(', ', $value) . '))';
            }),
        App_table_filter::new('groups', 'MultiSelectRule')->label(_l('customer_groups'))
            ->options(function ($ci) {
                return collect($ci->clients_model->get_groups())->map(fn ($group) => [
                    'value' => $group['id'],
                    'label' => $group['name'],
                ]);
            })->raw(function ($value, $operator, $sqlOperator) {
                return db_prefix() . 'clients.userid IN (SELECT customer_id FROM ' . db_prefix() . 'customer_groups WHERE groupid ' . $sqlOperator['operator'] . ' (' . implode(', ', $value) . '))';
            }),
        App_table_filter::new('my_customers', 'BooleanRule')->label(_l('customers_assigned_to_me'))
            ->raw(function ($value) {
                return db_prefix() . 'clients.userid ' . ($value == '1' ? 'IN' : 'NOT IN') . ' (SELECT customer_id FROM ' . db_prefix() . 'customer_admins WHERE staff_id=' . get_staff_user_id() . ')';
            }),
        App_table_filter::new('requires_confirmation', 'BooleanRule')
            ->label(_l('customer_requires_registration_confirmation'))
            ->raw(function ($value) {
                return db_prefix() . 'clients.registration_confirmed=' . ($value == '1' ? '0' : '1');
            }),
    ]);
