<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Field Executive Portal - Step 2 (Create Lead + Lead Details, the
 * central workspace). Every action here is a thin, ownership-gated
 * wrapper (is_owned_lead()) around the existing core Leads_model/
 * Misc_model methods - notes, attachments, activity log and conversion
 * are never re-implemented, only reused directly. Native admin/leads/*
 * routes are deliberately never linked to: those enforce the native
 * 'leads' permission-capability system, which this portal doesn't use
 * (access here is Business Department membership + lead ownership, same
 * philosophy as every other portal in this codebase) - a fresh Field
 * Executive account with zero native Leads permissions granted can still
 * fully use every action below.
 */
class Field_portal extends AdminController
{
    public function __construct()
    {
        parent::__construct();

        if (!is_field_portal_member()) {
            access_denied('Field Executive Portal');
        }

        $this->load->model('field_portal/field_portal_model');
        $this->load->model('daily_work_update/daily_work_update_model');
        $this->load->model('staff_attendance/staff_attendance_model');
        $this->load->model('leads_model');
        $this->load->model('misc_model');
        $this->load->model('tasks_model');
        $this->load->model('follow_up_management/follow_ups_model');
        $this->load->helper('follow_up_management/follow_up_management');
        $this->load->model('field_portal/field_portal_expenses_model');
        $this->lang->load('field_portal/field_portal', 'english');
        // get_followup_priorities()'s own labels (followup_priority_low/
        // medium/high/urgent), reused as-is for the Assign to Telecaller
        // modal's Priority dropdown rather than a duplicate set of labels.
        $this->lang->load('follow_up_management/follow_up_management', 'english');
    }

    public function index()
    {
        $staff_id = get_staff_user_id();
        $is_admin = is_admin();

        $data['title']              = _l('field_portal_dashboard');
        $data['summary']            = $this->field_portal_model->get_dashboard_summary($staff_id);
        $data['recent_conversions'] = $this->field_portal_model->get_recent_conversions($staff_id, 5);
        $data['todays_new_leads']   = $this->field_portal_model->get_todays_new_leads($staff_id, 5);
        $data['upcoming_followups'] = $this->field_portal_model->get_upcoming_followups($staff_id, 5);
        $data['recent_activity']    = $this->field_portal_model->get_recent_lead_activity($staff_id, 5);
        $data['todays_meetings']    = $this->field_portal_model->get_todays_meetings($staff_id, 5);
        $data['tomorrows_meetings'] = $this->field_portal_model->get_tomorrows_meetings($staff_id, 5);
        $data['overdue_meetings']   = $this->field_portal_model->get_overdue_meetings($staff_id, 5);

        // Today's Work Update - the shared daily_work_update module works
        // for any staff_id regardless of department, reused as-is.
        $data['today_work_update'] = $this->daily_work_update_model->get_today($staff_id);

        // Attendance Summary - same admin-vs-staff branch dev_portal's own
        // dashboard already uses.
        if ($is_admin) {
            $data['attendance_summary']  = $this->staff_attendance_model->get_today_summary();
            $data['attendance_is_admin'] = true;
        } else {
            $today_record = $this->staff_attendance_model->get_staff_date_record($staff_id);
            $data['attendance_summary']  = $today_record ? $today_record->attendance_status : null;
            $data['attendance_is_admin'] = false;
        }

        $this->load->view('dashboard', $data);
    }

    /**
     * My Leads - list page, filter dropdown data only (the table itself
     * loads via my_leads_table() below), matching the exact "filter
     * dropdowns here, DataTable source is its own action" split
     * dm_portal/dev_portal already use.
     */
    public function my_leads()
    {
        $data['title']    = _l('field_portal_my_leads');
        $data['statuses'] = $this->leads_model->get_status();

        $requested_date = to_sql_date($this->input->get('date'));

        if ($requested_date && preg_match('/^\d{4}-\d{2}-\d{2}$/', $requested_date)) {
            $data['default_date'] = $requested_date;
        } else {
            $data['default_date'] = '';
        }

        $requested_status = $this->input->get('status');
        if ($requested_status === 'converted') {
            $data['default_status'] = 'converted';
        } else {
            $data['default_status'] = (int) $requested_status;
        }

        $this->load->view('my_leads', $data);
    }

    /**
     * My Leads DataTable source - Lead-rooted, scoped to leads this Field
     * Executive created or is assigned to (field_portal_my_leads_where()),
     * LEFT JOINed to the "Priority" and "Next Follow-up Date" Lead custom
     * fields (both looked up by name, never hardcoded ids, same technique
     * the core admin Leads table itself already uses for "Next Follow-up
     * Date"). Meeting Date and Assigned Telecaller have no backing data
     * yet (Steps 3-4) - shown as "-" placeholders, not fabricated.
     */
    public function my_leads_table()
    {
        if (!$this->input->is_ajax_request()) {
            return;
        }

        $staff_id = get_staff_user_id();

        $priority_field    = get_custom_fields('leads', ['name' => 'Priority']);
        $priority_field_id = !empty($priority_field) ? (int) $priority_field[0]['id'] : 0;

        $followup_field    = get_custom_fields('leads', ['name' => 'Next Follow-up Date']);
        $followup_field_id = !empty($followup_field) ? (int) $followup_field[0]['id'] : 0;

        $aColumns = [
            db_prefix() . 'leads.name',
            db_prefix() . 'leads.company',
            db_prefix() . 'leads.phonenumber',
            db_prefix() . 'leads_status.name as status_name',
            'cfv_priority.value as priority_value',
            'cfv_followup.value as next_followup_value',
            db_prefix() . 'leads.dateadded',
        ];

        $sIndexColumn = 'id';
        $sTable       = db_prefix() . 'leads';

        $join = [
            'LEFT JOIN ' . db_prefix() . 'leads_status ON ' . db_prefix() . 'leads_status.id = ' . db_prefix() . 'leads.status',
            'LEFT JOIN ' . db_prefix() . 'customfieldsvalues cfv_priority ON cfv_priority.relid = ' . db_prefix() . 'leads.id AND cfv_priority.fieldid = ' . $priority_field_id . " AND cfv_priority.fieldto = 'leads'",
            'LEFT JOIN ' . db_prefix() . 'customfieldsvalues cfv_followup ON cfv_followup.relid = ' . db_prefix() . 'leads.id AND cfv_followup.fieldid = ' . $followup_field_id . " AND cfv_followup.fieldto = 'leads'",
            'LEFT JOIN ' . db_prefix() . 'staff telecaller ON telecaller.staffid = ' . db_prefix() . 'leads.assigned',
        ];

        $where = [
            field_portal_my_leads_where($staff_id),
        ];

        $status = $this->input->post('status');

        if ($status === 'converted') {
            $where[] = 'AND ' . db_prefix() . 'leads.client_id > 0';
        } elseif ($status) {
            $where[] = 'AND ' . db_prefix() . 'leads.status = ' . (int) $status;
        }

        // Smart date filter - the date column changes dynamically based on
        // the selected Status filter, so the same datepicker can search
        // lead-created dates, follow-up dates, status-change dates, or
        // conversion dates without adding separate filter controls.
        $date = to_sql_date($this->input->post('date'));

        if ($date && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            if ($status === 'converted') {
                $where[] = "AND DATE(" . db_prefix() . "leads.date_converted) = '" . $date . "'";
            } elseif ((string) $status === '2') {
                // Follow-up: filter by next_follow_up_date (custom field)
                $where[] = "AND DATE(cfv_followup.value) = '" . $date . "'";
            } elseif ((string) $status === '3') {
                // Customer Confirmed: filter by last_status_change
                $where[] = "AND DATE(" . db_prefix() . "leads.last_status_change) = '" . $date . "'";
            } else {
                // All other statuses / no status: filter by created date
                $where[] = "AND DATE(" . db_prefix() . "leads.dateadded) = '" . $date . "'";
                $where[] = 'AND ' . db_prefix() . 'leads.addedfrom = ' . (int) $staff_id;
            }
        }

        $result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, [
            db_prefix() . 'leads.id',
            db_prefix() . 'leads.assigned',
            db_prefix() . 'leads.addedfrom',
            "CONCAT(telecaller.firstname, ' ', telecaller.lastname) as telecaller_name",
            "(SELECT description FROM " . db_prefix() . "notes WHERE rel_id = " . db_prefix() . "leads.id AND rel_type = 'lead' ORDER BY dateadded DESC LIMIT 1) as latest_note",
        ]);

        $output  = $result['output'];
        $rResult = $result['rResult'];

        foreach ($rResult as $aRow) {
            $lead_id = (int) $aRow['id'];
            $row     = [];

            $row[] = '<a href="' . admin_url('field_portal/lead_details/' . $lead_id) . '" class="tw-font-medium">' . e($aRow[db_prefix() . 'leads.name']) . '</a>';
            $row[] = $aRow[db_prefix() . 'leads.company'] ? e($aRow[db_prefix() . 'leads.company']) : '-';
            $row[] = $aRow[db_prefix() . 'leads.phonenumber'] ? e($aRow[db_prefix() . 'leads.phonenumber']) : '-';
            $row[] = $aRow['status_name'] ? e($aRow['status_name']) : '-';
            $row[] = $aRow['priority_value'] ? e($aRow['priority_value']) : '-';
            $row[] = '-'; // Meeting Date - Step 3
            $row[] = $aRow['next_followup_value'] ? e(_d($aRow['next_followup_value'])) : '-';
            $row[] = $aRow['telecaller_name'] ? e($aRow['telecaller_name']) : '-';
            $row[] = $aRow[db_prefix() . 'leads.dateadded'] ? e(_dt($aRow[db_prefix() . 'leads.dateadded'])) : '-';
            $latest_note = $aRow['latest_note'] ?? '';
            $row[] = $latest_note ? e(mb_substr($latest_note, 0, 100)) . (mb_strlen($latest_note) > 100 ? '...' : '') : '-';
            $actions = '<a href="' . admin_url('field_portal/lead_details/' . $lead_id) . '" class="btn btn-default btn-sm" title="' . _l('field_portal_view_details') . '"><i class="fa-solid fa-eye"></i></a>';
            if ((int) $aRow['addedfrom'] === (int) $staff_id) {
                $actions .= ' <a href="' . admin_url('field_portal/edit_lead/' . $lead_id) . '" class="btn btn-default btn-sm" title="' . _l('field_portal_edit') . '"><i class="fa-solid fa-pencil"></i></a>';
            }
            $row[] = $actions;

            $row['DT_RowId'] = 'field_portal_lead_' . $lead_id;
            $output['aaData'][] = $row;
        }

        echo json_encode($output);
    }

    /**
     * Create Lead - a fast, mobile-friendly form. Every native Lead field
     * used here (name/company/phonenumber/email/address/source/
     * description) maps straight onto the real tblleads columns; the
     * fields with no native/existing representation (Alternate Phone,
     * Google Maps Location, Priority, Remarks) are the 4 custom fields
     * added by migration 368; "Business Category" and "Expected Budget"
     * reuse the existing "Service Required"/"Budget" custom fields
     * as-is, not a duplicate concept.
     */
    public function create_lead()
    {
        $data['title']               = _l('field_portal_create_lead');
        $data['sources']             = $this->leads_model->get_source();
        $data['business_category_field'] = $this->_get_field_meta('Service Required');
        $data['priority_field']          = $this->_get_field_meta('Priority');
        $data['budget_field']            = $this->_get_field_meta('Budget');
        $data['alt_phone_field']         = $this->_get_field_meta('Alternate Phone');
        $data['maps_field']              = $this->_get_field_meta('Google Maps Location');
        $data['remarks_field']           = $this->_get_field_meta('Remarks');

        $this->load->view('create_lead', $data);
    }

    /**
     * Create Lead submit - calls Leads_model::add($data) directly, the
     * exact same model method the native Add Lead form uses
     * (application/controllers/admin/Leads.php's own lead() action just
     * passes $this->input->post() through unchanged) - identical side
     * effects (activity log entry, department/addedfrom auto-set,
     * automatic-conversion check), just reached from this portal's own
     * lightweight form instead of the full native Lead edit page.
     */
    public function create_lead_submit()
    {
        if (!$this->input->is_ajax_request()) {
            return;
        }

        $post = $this->input->post();

        if (empty($post['name'])) {
            echo json_encode(['success' => false, 'message' => _l('field_portal_lead_name_required')]);

            return;
        }

        $staff_id = get_staff_user_id();

        // Auto: Created By (addedfrom, set inside Leads_model::add() itself),
        // Department, assigned - a lead created through this portal always
        // starts assigned to its own creator so it immediately shows in
        // "My Leads" (field_portal_my_leads_where() matches on addedfrom OR
        // assigned), and lead_assigned_member_notification() already
        // no-ops when assigned === the creator, so no stray self-notification.
        $post['department'] = field_portal_department_id();
        $post['assigned']   = $staff_id;

        // Leads_model::add() has no implicit default for 'status' - it
        // inserts whatever is (or isn't) in $data as-is, and tblleads.status
        // is NOT NULL, so an unset status silently lands as 0 (matching no
        // real tblleads_status row). The native Add Lead form always posts
        // a real status id from its own dropdown; this portal's own
        // lightweight form doesn't show one, so it's set explicitly here to
        // the same "New" status every other lead starts at.
        $post['status'] = $this->field_portal_model->get_new_lead_status_id();

        $lead_id = $this->leads_model->add($post);

        if (!$lead_id) {
            echo json_encode(['success' => false, 'message' => _l('field_portal_lead_create_failed')]);

            return;
        }

        echo json_encode([
            'success'  => true,
            'id'       => $lead_id,
            'redirect' => admin_url('field_portal/lead_details/' . $lead_id),
        ]);
    }

    /**
     * Lead Details - the central workspace: Lead info, Notes, Attachments,
     * Activity Log timeline and a Convert action all on one page, per the
     * requirement that these not be "separate disconnected workflows".
     * Meetings/Follow-ups sections show honest placeholders until Steps
     * 3-4 land. Leads_model::get($id) already bundles attachments and
     * status_name/source_name - reused as-is, not re-queried.
     *
     * @param int $id
     */
    public function lead_details($id)
    {
        if (!is_owned_lead($id)) {
            access_denied('Field Executive Portal');
        }

        $lead = $this->leads_model->get($id);

        if (!$lead) {
            show_404();
        }

        $data['lead']          = $lead;
        $data['title']         = $lead->name;
        $data['notes']         = $this->misc_model->get_notes($id, 'lead');
        $data['custom_fields'] = get_custom_fields('leads');
        $data['is_converted']  = (int) $lead->client_id > 0;

        // Meetings - Tasks with rel_type='lead' (see field_portal.php's
        // own module docblock for the full "Meeting IS a Task" rationale).
        // Timeline merges lead_activity_log + notes chronologically -
        // Meetings already appear in it for free (Tasks_model::add()
        // itself logs a lead activity entry whenever rel_type='lead').
        // Both functions now live in the shared lead_workspace_helper
        // (application/helpers/) so modules/follow_up_management's own
        // Telecaller Lead page reads the exact same data, never a second
        // implementation - see that helper's own docblock.
        $data['meetings']      = lead_workspace_get_meetings($id);
        $data['timeline']      = lead_workspace_get_unified_timeline($id);
        $data['overview']      = lead_workspace_get_overview_stats($id);
        $data['task_statuses'] = $this->tasks_model->get_statuses();

        // Step 4 - once a Telecaller has an open Case for this Lead, the
        // Field Executive's own Assign/Convert/Mark Lost quick actions are
        // hidden in favour of a "Currently with Telecaller" banner, so the
        // same lead is never independently assigned/converted/lost from
        // both sides at once.
        $data['active_case']      = field_portal_get_active_case($id);
        $data['telecallers']      = field_portal_get_telecallers();
        $data['followup_history'] = lead_workspace_get_followup_history($id);
        $data['customer_data']    = $is_converted ? lead_workspace_get_customer_data($id) : null;

        $this->load->view('lead_details', $data);
    }

    /**
     * Edit Lead - reuses the same create_lead view (the single form)
     * but pre-fills every field with the existing lead's data.
     * Only leads created by THIS Field Executive are editable.
     *
     * @param int $id
     */
    public function edit_lead($id)
    {
        $lead = $this->leads_model->get($id);

        if (!$lead) {
            show_404();
        }

        // Ownership gate: can only edit leads YOU created.
        if ((int) $lead->addedfrom !== (int) get_staff_user_id()) {
            access_denied('Field Executive Portal - Edit Lead');
        }

        $staff_id = get_staff_user_id();

        $data['lead']             = $lead;
        $telecallers              = field_portal_get_telecallers();
        $telecaller_ids           = array_map('intval', array_column($telecallers, 'staffid'));
        $data['is_assigned']      = !empty($lead->assigned) && in_array((int) $lead->assigned, $telecaller_ids);
        $data['is_converted']     = !empty($lead->client_id) && (int) $lead->client_id > 0;
        $data['title']            = _l('field_portal_edit_lead');
        $data['sources']          = $this->leads_model->get_source();
        $data['statuses']         = $this->leads_model->get_status();
        $data['business_category_field'] = $this->_get_field_meta('Service Required');
        $data['priority_field']          = $this->_get_field_meta('Priority');
        $data['budget_field']            = $this->_get_field_meta('Budget');
        $data['alt_phone_field']         = $this->_get_field_meta('Alternate Phone');
        $data['maps_field']              = $this->_get_field_meta('Google Maps Location');
        $data['remarks_field']           = $this->_get_field_meta('Remarks');

        // Current custom field values for pre-filling.
        $data['custom_fields_values'] = [];
        foreach (['business_category_field', 'priority_field', 'budget_field', 'alt_phone_field', 'maps_field', 'remarks_field'] as $cf_key) {
            $field = $data[$cf_key];
            if (!empty($field)) {
                $val = get_custom_field_value($id, (int) $field['id'], 'leads', false);
                $data['custom_fields_values'][(int) $field['id']] = $val;
            }
        }

        $this->load->view('create_lead', $data);
    }

    /**
     * Edit Lead submit - validates, updates via Leads_model::update(), and
     * logs individual field changes as activity entries. Reuses the exact
     * same Leads_model::update() the native Leads controller calls.
     *
     * @param int $id
     */
    public function edit_lead_submit($id)
    {
        if (!$this->input->is_ajax_request()) {
            return;
        }

        $lead = $this->leads_model->get((int) $id);

        if (!$lead || (int) $lead->addedfrom !== (int) get_staff_user_id()) {
            echo json_encode(['success' => false, 'message' => _l('field_portal_lead_edit_failed')]);
            return;
        }

        $staff_id     = get_staff_user_id();
        $telecallers  = field_portal_get_telecallers();
        $telecaller_ids = array_map('intval', array_column($telecallers, 'staffid'));
        $is_assigned  = !empty($lead->assigned) && in_array((int) $lead->assigned, $telecaller_ids);
        $is_converted = !empty($lead->client_id) && (int) $lead->client_id > 0;

        $post = $this->input->post();

        if (empty($post['name'])) {
            echo json_encode(['success' => false, 'message' => _l('field_portal_lead_name_required')]);
            return;
        }

        // Build update data - only fields that were actually posted.
        $data = [];
        $changes = [];
        $field_map = [
            'name'        => _l('field_portal_field_contact_person'),
            'company'     => _l('field_portal_field_company'),
            'phonenumber' => _l('field_portal_field_phone'),
            'email'       => _l('field_portal_field_email'),
            'address'     => _l('field_portal_field_address'),
            'description' => _l('field_portal_field_requirements'),
            'source'      => _l('field_portal_field_source'),
            'status'      => _l('field_portal_column_status'),
        ];

        foreach ($field_map as $key => $label) {
            $posted_val = $this->input->post($key);
            if ($posted_val === null) {
                continue;
            }
            $data[$key] = $posted_val;
            $old = (string) ($lead->$key ?? '');
            $new = (string) $posted_val;
            if ($old !== $new) {
                $changes[] = $label . ' updated from "' . $old . '" to "' . $new . '"';
            }
        }

        // Custom fields.
        $custom_fields = [];

        $cf_fields = [
            'alt_phone_field'    => 'Alternate Phone',
            'maps_field'         => 'Google Maps Location',
            'business_category_field' => 'Service Required',
            'budget_field'       => 'Budget',
            'priority_field'     => 'Priority',
            'remarks_field'      => 'Remarks',
        ];

        foreach ($cf_fields as $meta_key => $cf_label) {
            $field = $this->_get_field_meta($cf_label);
            if (empty($field)) {
                continue;
            }
            $fid = (int) $field['id'];

            // Lock custom fields if lead is assigned or converted (except Remarks).
            if ($meta_key !== 'remarks_field' && ($is_assigned || $is_converted)) {
                continue;
            }

            $cf_post = $this->input->post('custom_fields');
            $new_val = ($cf_post['leads'][$fid] ?? '');
            $custom_fields[$fid] = $new_val;

            $old_val = get_custom_field_value($id, $fid, 'leads', false);
            if ((string) $old_val !== (string) $new_val) {
                $changes[] = $cf_label . ' updated from "' . $old_val . '" to "' . $new_val . '"';
            }
        }

        $data['custom_fields'] = $custom_fields;

        $updated = $this->leads_model->update($data, $id);

        if ($updated) {
            // Log each change as a custom activity entry.
            foreach ($changes as $change) {
                $aid = $this->leads_model->log_lead_activity($id, $change);
                if ($aid) {
                    $this->db->where('id', $aid);
                    $this->db->update(db_prefix() . 'lead_activity_log', ['custom_activity' => 1]);
                }
            }

            echo json_encode([
                'success'  => true,
                'message'  => _l('field_portal_lead_updated'),
                'redirect' => admin_url('field_portal/lead_details/' . $id),
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => _l('field_portal_lead_edit_failed')]);
        }
    }

    /**
     * Add Note - reuses Misc_model::add_note() directly (the exact same
     * model method Leads::add_note() calls), just gated by lead ownership
     * instead of the native 'leads' permission system.
     *
     * @param int $lead_id
     */
    public function lead_add_note($lead_id)
    {
        if (!is_owned_lead($lead_id)) {
            ajax_access_denied();
        }

        $description = trim($this->input->post('description'));

        if ($description === '') {
            echo json_encode(['success' => false, 'message' => _l('field_portal_note_required')]);

            return;
        }

        $this->misc_model->add_note(['description' => $description], 'lead', $lead_id);

        echo json_encode(['success' => true]);
    }

    /**
     * Delete Note - Misc_model::delete_note() already enforces ownership
     * (addedfrom == staff OR is_admin()) internally, but this portal's own
     * lead-ownership gate is checked first too, consistent with every
     * other action here.
     *
     * @param int $id
     * @param int $lead_id
     */
    public function lead_delete_note($id, $lead_id)
    {
        if (!is_owned_lead($lead_id)) {
            ajax_access_denied();
        }

        $this->misc_model->delete_note($id);

        echo json_encode(['success' => true]);
    }

    /**
     * Add Attachment(s) - reuses the global handle_lead_attachments()
     * helper directly (the exact function Leads::add_lead_attachment()
     * calls), which itself validates/moves the files and inserts into
     * tblfiles via Leads_model::add_attachment_to_database() - no
     * duplicate upload handling here.
     *
     * @param int $lead_id
     */
    public function lead_add_attachment($lead_id)
    {
        if (!is_owned_lead($lead_id)) {
            ajax_access_denied();
        }

        handle_lead_attachments($lead_id);

        echo json_encode(['success' => true]);
    }

    /**
     * Delete Attachment - reuses Leads_model::delete_lead_attachment()
     * directly (unlinks the physical file, deletes the tblfiles row,
     * cleans up the now-empty folder - identical to the native delete
     * action).
     *
     * @param int $id
     * @param int $lead_id
     */
    public function lead_delete_attachment($id, $lead_id)
    {
        if (!is_owned_lead($lead_id)) {
            ajax_access_denied();
        }

        $this->leads_model->delete_lead_attachment($id);

        echo json_encode(['success' => true]);
    }

    /**
     * Convert to Customer - a quick-convert action for Case 1 ("Customer
     * Ready") and the Telecaller-side Case 1 equivalent, built the exact
     * same way modules/follow_up_management's own
     * Follow_ups_model::auto_convert_confirmed_lead() already does it:
     * shape the minimal $data Leads_model::convert_to_customer() requires
     * (billing_* mirrored from address/city/state/zip/country, is_primary=1,
     * default country fallback - identical to Leads::convert_to_customer()'s
     * own shaping, application/controllers/admin/Leads.php:413-423) and
     * call the model directly - reusing the ONE existing conversion
     * implementation, never a second one. Notes are always transferred
     * (retaining Lead history on the new customer, per the requirement);
     * GDPR consent transfer and custom-field merge are skipped for this
     * quick flow, same simplification already accepted for the
     * Telecaller's own auto-convert path.
     *
     * @param int $id
     */
    public function lead_convert($id)
    {
        if (!is_owned_lead($id)) {
            ajax_access_denied();
        }

        $lead = $this->leads_model->get($id);

        if (!$lead || (int) $lead->client_id > 0) {
            echo json_encode(['success' => false, 'message' => _l('field_portal_already_converted')]);

            return;
        }

        if (mb_strpos($lead->name, ' ') !== false) {
            $parts     = explode(' ', $lead->name);
            $firstname = $parts[0];
            $lastname  = isset($parts[2]) ? ($parts[1] . ' ' . $parts[2]) : $parts[1];
        } else {
            $firstname = $lead->name;
            $lastname  = '';
        }

        $default_country = get_option('customer_default_country');

        // Every value coerced to a string (never null): Clients_model::add()
        // decides which fields belong to the contact vs the client row via
        // `if (!isset($data[$field])) continue;` per field - isset() on a
        // null value returns false, so a null here (e.g. $lead->title,
        // routinely null for a lead nobody set a title on) would silently
        // skip that field's removal from $data and leak straight into the
        // tblclients INSERT, which has no matching column ("Unknown column
        // 'title'"). A real HTML form never hits this because submitted
        // fields are always strings, even when empty - matched here with
        // `?? ''` instead of leaving the raw (possibly null) property.
        $data = [
            'leadid'           => $id,
            'firstname'        => $firstname,
            'lastname'         => $lastname,
            'title'            => $lead->title ?? '',
            'email'            => $lead->email ?? '',
            'company'          => $lead->company ?: $lead->name,
            'phonenumber'      => $lead->phonenumber ?? '',
            'website'          => $lead->website ?? '',
            'address'          => $lead->address ?? '',
            'city'             => $lead->city ?? '',
            'state'            => $lead->state ?? '',
            'country'          => $lead->country ?: $default_country,
            'zip'              => $lead->zip ?? '',
            'default_language' => $lead->default_language ?? '',
            'password'         => '',
        ];

        $data['billing_street']  = $data['address'];
        $data['billing_city']    = $data['city'];
        $data['billing_state']   = $data['state'];
        $data['billing_zip']     = $data['zip'];
        $data['billing_country'] = $data['country'];
        $data['is_primary']      = 1;

        $notes = $this->misc_model->get_notes($id, 'lead');

        $customer_id = $this->leads_model->convert_to_customer($data, $lead->email, $notes, null, null, null, null);

        if (!$customer_id) {
            echo json_encode(['success' => false, 'message' => _l('field_portal_convert_failed')]);

            return;
        }

        echo json_encode([
            'success'  => true,
            'customer_id' => $customer_id,
            'redirect' => admin_url('field_portal/customers'),
        ]);
    }

    /**
     * Assign to Telecaller submit - the one new mechanism this step adds
     * is a single raw update to tblleads.assigned (Current Handler moves
     * to the Telecaller; tblleads.addedfrom/Original Field Executive is
     * never touched). Everything after that is existing, unduplicated
     * follow_up_management machinery:
     * Follow_ups_model::create_case_for_lead() (dedup-guarded - creates
     * the Case, its linked Task, syncs a Reminder, and sends the
     * Telecaller their "new case assigned" notification+email, all
     * already-existing code) is called directly rather than only hoping
     * the lead_status_changed hook fires it, since MySQL reports 0
     * affected rows (and therefore never fires the hook) if the Lead's
     * status happened to already be "Follow-up". The subsequent
     * Leads_model::update_lead_status() call keeps the Lead's own status
     * column in sync for every other consumer (Admin Leads page, kanban,
     * reports) - if it DOES fire the hook, create_case_for_lead() simply
     * returns the same already-created case id a second time. Priority/
     * Follow-up Date/Reason/Remarks are then applied onto that one Case
     * via change_case_priority()/reschedule_case()/log_case_interaction()
     * - reusing the Reminder system (reschedule_case()) rather than a
     * second one, and folding Reason/Remarks into a Case History note
     * (which log_case_interaction() itself already mirrors onto the
     * Lead's own activity log - Timeline for free, no new logging code).
     *
     * @param int $lead_id
     */
    public function lead_assign_to_telecaller_submit($lead_id)
    {
        if (!$this->input->is_ajax_request()) {
            return;
        }

        if (!is_owned_lead($lead_id)) {
            ajax_access_denied();
        }

        $lead = $this->leads_model->get($lead_id);

        if (!$lead) {
            echo json_encode(['success' => false, 'message' => _l('field_portal_lead_create_failed')]);

            return;
        }

        if (field_portal_get_active_case($lead_id)) {
            echo json_encode(['success' => false, 'message' => _l('field_portal_already_with_telecaller')]);

            return;
        }

        $telecaller_id = (int) $this->input->post('telecaller_id');
        // array_map('intval', ...): get_business_department_staff() returns
        // staffid as whatever type the DB driver hands back (a numeric
        // string, not necessarily an int) - a strict in_array() comparison
        // against the (int)-cast POST value would otherwise always fail.
        $telecallers = array_map('intval', array_column(field_portal_get_telecallers(), 'staffid'));

        if (!$telecaller_id || !in_array($telecaller_id, $telecallers, true)) {
            echo json_encode(['success' => false, 'message' => _l('field_portal_telecaller_required')]);

            return;
        }

        $priority      = $this->input->post('priority');
        $reason        = trim($this->input->post('reason'));
        $follow_up_date = $this->input->post('follow_up_date');
        $remarks       = trim($this->input->post('remarks'));

        $this->db->where('id', $lead_id);
        $this->db->update(db_prefix() . 'leads', ['assigned' => $telecaller_id]);

        $this->follow_ups_model->create_case_for_lead($lead_id);

        $followup_status_id = $this->field_portal_model->get_status_id_by_name('Follow-up');
        if ($followup_status_id) {
            $this->leads_model->update_lead_status(['leadid' => $lead_id, 'status' => $followup_status_id]);
        }

        $case = field_portal_get_active_case($lead_id);

        if ($case) {
            if ($priority && array_key_exists($priority, get_followup_priorities())) {
                $this->follow_ups_model->change_case_priority($case->id, $priority);
            }

            if ($follow_up_date) {
                $this->follow_ups_model->reschedule_case($case->id, to_sql_date($follow_up_date));
            }

            $notes_parts = [];
            if ($reason !== '') {
                $notes_parts[] = _l('field_portal_assign_reason') . ': ' . $reason;
            }
            if ($remarks !== '') {
                $notes_parts[] = _l('field_portal_field_remarks') . ': ' . $remarks;
            }

            if (!empty($notes_parts)) {
                $this->follow_ups_model->log_case_interaction($case->id, 'note', ['notes' => implode("\n\n", $notes_parts)]);
            }
        }

        $this->leads_model->log_lead_activity($lead_id, 'not_lead_assigned_to_telecaller', false, serialize([get_staff_full_name($telecaller_id)]));

        echo json_encode(['success' => true]);
    }

    /**
     * Mark Lost - one of the 4 meeting outcomes. Reuses the exact same
     * Leads_model::update_lead_status() path the Telecaller's own "Not
     * Interested" outcome (Follow_up_management) uses, which is already
     * wired (follow_up_management_on_lead_status_changed()) to close any
     * open Case + linked Task via the existing complete_case_for_lead() -
     * a lead marked Lost directly by the Field Executive before ever
     * being assigned simply has no open Case to close, so that call is a
     * harmless no-op in that path.
     *
     * @param int $lead_id
     */
    public function lead_mark_lost_submit($lead_id)
    {
        if (!$this->input->is_ajax_request()) {
            return;
        }

        if (!is_owned_lead($lead_id)) {
            ajax_access_denied();
        }

        if (field_portal_get_active_case($lead_id)) {
            echo json_encode(['success' => false, 'message' => _l('field_portal_already_with_telecaller')]);

            return;
        }

        $reason = trim($this->input->post('reason'));

        if ($reason === '') {
            echo json_encode(['success' => false, 'message' => _l('field_portal_lost_reason_required')]);

            return;
        }

        $lost_status_id = $this->field_portal_model->get_status_id_by_name('Lost');

        if (!$lost_status_id) {
            echo json_encode(['success' => false, 'message' => _l('field_portal_lead_create_failed')]);

            return;
        }

        $this->leads_model->update_lead_status(['leadid' => $lead_id, 'status' => $lost_status_id]);
        $this->leads_model->log_lead_activity($lead_id, 'not_lead_marked_lost', false, serialize([$reason]));

        echo json_encode(['success' => true]);
    }

    /**
     * Meetings - portal-wide list across every one of this Field
     * Executive's own leads. A Meeting IS a Task (rel_type='lead') - see
     * this file's own module docblock in field_portal.php - so this page
     * is really just "my leads' Tasks", not a new entity list.
     */
    public function meetings()
    {
        $data['title']         = _l('field_portal_meetings');
        $data['task_statuses'] = $this->tasks_model->get_statuses();
        // Dashboard drill-down (Today's/Tomorrow's/Overdue Meetings KPI
        // card + panels all link here with ?filter=today|tomorrow|overdue)
        // - read once here so the view can pre-select the "When" filter
        // dropdown; the actual filtering happens in meetings_table() below.
        $allowed_filters        = ['today', 'tomorrow', 'overdue'];
        $requested_filter       = $this->input->get('filter');
        $data['default_filter'] = in_array($requested_filter, $allowed_filters, true) ? $requested_filter : '';

        $this->load->view('meetings', $data);
    }

    /**
     * Meetings DataTable source - reuses Field_portal_model's own meeting
     * query builder (meetings_base_query(), private - so this action just
     * calls the public today/tomorrow/overdue methods isn't right for an
     * unfiltered "all meetings" list; instead this queries tbltasks
     * directly with the same ownership scoping, matching my_leads_table()'s
     * own structure). Company/Location columns reuse Lead-level data
     * (leads.company, and the Lead's own "Google Maps Location" custom
     * field - a Meeting has no location field of its own) via the same
     * relid JOIN technique already used for Meeting Time.
     */
    public function meetings_table()
    {
        if (!$this->input->is_ajax_request()) {
            return;
        }

        $staff_id = get_staff_user_id();

        $time_field    = get_custom_fields('tasks', ['name' => 'Meeting Time']);
        $time_field_id = !empty($time_field) ? (int) $time_field[0]['id'] : 0;

        $location_field    = get_custom_fields('leads', ['name' => 'Google Maps Location']);
        $location_field_id = !empty($location_field) ? (int) $location_field[0]['id'] : 0;

        $aColumns = [
            db_prefix() . 'leads.name as lead_name',
            db_prefix() . 'leads.company',
            db_prefix() . 'tasks.duedate',
            'cfv_time.value as meeting_time',
            'cfv_location.value as meeting_location',
            db_prefix() . 'tasks.status',
        ];

        $sIndexColumn = 'id';
        $sTable       = db_prefix() . 'tasks';

        $join = [
            'LEFT JOIN ' . db_prefix() . 'leads ON ' . db_prefix() . 'leads.id = ' . db_prefix() . 'tasks.rel_id',
            'LEFT JOIN ' . db_prefix() . 'customfieldsvalues cfv_time ON cfv_time.relid = ' . db_prefix() . 'tasks.id AND cfv_time.fieldid = ' . $time_field_id . " AND cfv_time.fieldto = 'tasks'",
            'LEFT JOIN ' . db_prefix() . 'customfieldsvalues cfv_location ON cfv_location.relid = ' . db_prefix() . 'tasks.rel_id AND cfv_location.fieldid = ' . $location_field_id . " AND cfv_location.fieldto = 'leads'",
        ];

        $where = [
            'AND ' . db_prefix() . "tasks.rel_type = 'lead'",
        ];

        if (!is_admin()) {
            $where[] = 'AND ' . db_prefix() . 'tasks.rel_id IN ' . '(SELECT id FROM ' . db_prefix() . 'leads WHERE addedfrom = ' . (int) $staff_id . ' OR assigned = ' . (int) $staff_id . ')';
        }

        // Dashboard drill-down - mirrors the exact same DATE(duedate)
        // comparisons get_todays_meetings()/get_tomorrows_meetings()/
        // get_overdue_meetings() already use for the dashboard panels.
        $when_filter = $this->input->post('when_filter');
        if ($when_filter === 'today') {
            $where[] = 'AND DATE(' . db_prefix() . 'tasks.duedate) = CURDATE()';
        } elseif ($when_filter === 'tomorrow') {
            $where[] = 'AND DATE(' . db_prefix() . 'tasks.duedate) = DATE_ADD(CURDATE(), INTERVAL 1 DAY)';
        } elseif ($when_filter === 'overdue') {
            $where[] = 'AND DATE(' . db_prefix() . 'tasks.duedate) < CURDATE()';
            $where[] = 'AND ' . db_prefix() . 'tasks.status != ' . Tasks_model::STATUS_COMPLETE;
        }

        $result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, [
            db_prefix() . 'tasks.id',
            db_prefix() . 'tasks.rel_id',
        ]);

        $output  = $result['output'];
        $rResult = $result['rResult'];

        foreach ($rResult as $aRow) {
            $task_id = (int) $aRow['id'];
            // 'rel_id', not the qualified 'tbltasks.rel_id' - an unaliased
            // $additionalSelect column comes back from the driver under
            // its own bare column name, unlike aColumns entries (which
            // data_tables_init() auto-aliases to their qualified name).
            $lead_id = (int) $aRow['rel_id'];
            $row     = [];

            $row[] = '<a href="' . admin_url('field_portal/lead_details/' . $lead_id) . '">' . e($aRow['lead_name']) . '</a>';
            $row[] = $aRow[db_prefix() . 'leads.company'] ? e($aRow[db_prefix() . 'leads.company']) : '-';
            $row[] = $aRow[db_prefix() . 'tasks.duedate'] ? e(_d($aRow[db_prefix() . 'tasks.duedate'])) : '-';
            $row[] = $aRow['meeting_time'] ? e($aRow['meeting_time']) : '-';
            $row[] = $aRow['meeting_location'] ? e($aRow['meeting_location']) : '-';
            $row[] = format_task_status((int) $aRow[db_prefix() . 'tasks.status']);
            $row[] = '<a href="' . admin_url('field_portal/lead_details/' . $lead_id) . '#meeting-' . $task_id . '" class="btn btn-default btn-sm"><i class="fa-solid fa-eye"></i></a>';

            $row['DT_RowId'] = 'field_portal_meeting_' . $task_id;
            $output['aaData'][] = $row;
        }

        echo json_encode($output);
    }

    /**
     * Log Meeting - a fast form (mirrors Create Lead's own UX), reached
     * from a Lead's own Details page.
     *
     * @param int $lead_id
     */
    public function lead_add_meeting($lead_id)
    {
        if (!is_owned_lead($lead_id)) {
            access_denied('Field Executive Portal');
        }

        $lead = $this->leads_model->get($lead_id);

        if (!$lead) {
            show_404();
        }

        $data['lead']              = $lead;
        $data['title']             = _l('field_portal_log_meeting');
        $data['meeting_type_field'] = $this->_get_task_field_meta('Meeting Type');

        $this->load->view('lead_add_meeting', $data);
    }

    /**
     * Log Meeting submit - creates ONE Task (rel_type='lead',
     * rel_id=$lead_id), exactly the same core method
     * (Tasks_model::add()) and rel_type value modules/dev_portal and this
     * portal's own Create Lead flow already rely on elsewhere. The
     * structured discussion fields (Discussion Summary/Products
     * Discussed/Customer Requirements/Budget/Competitor Info/Next Action)
     * are composed into the Task's own `description` via
     * field_portal_meeting_template() rather than becoming one Task
     * custom field each - see this module's docblock for why (CRM-wide
     * Task form clutter). Only Meeting Time/Meeting Type are real custom
     * fields (migration 369).
     *
     * @param int $lead_id
     */
    public function lead_add_meeting_submit($lead_id)
    {
        if (!$this->input->is_ajax_request()) {
            return;
        }

        if (!is_owned_lead($lead_id)) {
            ajax_access_denied();
        }

        $lead = $this->leads_model->get($lead_id);

        if (!$lead) {
            echo json_encode(['success' => false, 'message' => _l('field_portal_lead_create_failed')]);

            return;
        }

        $post = $this->input->post();
        $staff_id = get_staff_user_id();

        $meeting_date = $post['meeting_date'] ?? '';

        if ($meeting_date === '') {
            echo json_encode(['success' => false, 'message' => _l('field_portal_meeting_date_required')]);

            return;
        }

        $description = field_portal_meeting_template([
            _l('field_portal_field_discussion_summary')  => $post['discussion_summary'] ?? '',
            _l('field_portal_field_products_discussed')  => $post['products_discussed'] ?? '',
            _l('field_portal_field_requirements')        => $post['customer_requirements'] ?? '',
            _l('field_portal_field_meeting_budget')      => $post['budget'] ?? '',
            _l('field_portal_field_competitor_info')     => $post['competitor_info'] ?? '',
            _l('field_portal_field_next_action')         => $post['next_action'] ?? '',
        ]);

        $type_field = $this->_get_task_field_meta('Meeting Type');
        $time_field = $this->_get_task_field_meta('Meeting Time');

        $data = [
            'name'        => _l('field_portal_meeting_task_name') . ' - ' . $lead->name,
            'description' => $description,
            'startdate'   => to_sql_date($meeting_date),
            'duedate'     => to_sql_date($meeting_date),
            'priority'    => 2,
            'rel_id'      => $lead_id,
            'rel_type'    => 'lead',
            'assignees'   => [$staff_id],
        ];

        if ($type_field && !empty($post['meeting_type'])) {
            $data['custom_fields']['tasks'][$type_field['id']] = $post['meeting_type'];
        }

        if ($time_field && !empty($post['meeting_time'])) {
            $data['custom_fields']['tasks'][$time_field['id']] = $post['meeting_time'];
        }

        $task_id = $this->tasks_model->add($data);

        if (!$task_id) {
            echo json_encode(['success' => false, 'message' => _l('field_portal_meeting_create_failed')]);

            return;
        }

        echo json_encode([
            'success'  => true,
            'id'       => $task_id,
            'redirect' => admin_url('field_portal/lead_details/' . $lead_id . '#meeting-' . $task_id),
        ]);
    }

    /**
     * Meeting note - reuses Tasks_model::add_task_comment() directly, the
     * exact method the native Task view's own Comments tab uses - a note
     * added here shows there too. Precedent: modules/dm_portal's own
     * task_add_note().
     *
     * @param int $task_id
     */
    public function meeting_add_note($task_id)
    {
        if (!is_owned_meeting_task($task_id)) {
            ajax_access_denied();
        }

        $content = trim($this->input->post('content'));

        if ($content === '') {
            echo json_encode(['success' => false, 'message' => _l('field_portal_note_required')]);

            return;
        }

        $this->tasks_model->add_task_comment(['taskid' => $task_id, 'content' => $content]);

        echo json_encode(['success' => true]);
    }

    /**
     * Meeting attachment(s) - reuses handle_task_attachments_array() +
     * Misc_model::add_attachment_to_database($id, 'task', ...) directly,
     * the exact pair modules/dm_portal's own task_upload_file() already
     * uses, so files land in tblfiles identically to a native task upload.
     *
     * @param int $task_id
     */
    public function meeting_add_attachment($task_id)
    {
        if (!is_owned_meeting_task($task_id)) {
            ajax_access_denied();
        }

        $files = handle_task_attachments_array($task_id, 'file');

        if ($files && is_array($files)) {
            foreach ($files as $file) {
                $this->misc_model->add_attachment_to_database($task_id, 'task', [$file]);
            }
        }

        if (!$files || !is_array($files) || count($files) === 0) {
            echo json_encode(['success' => false, 'message' => _l('validation_extension_not_allowed')]);

            return;
        }

        echo json_encode(['success' => true]);
    }

    /**
     * Delete meeting attachment - reuses Tasks_model::remove_task_attachment()
     * directly (the exact method the native Task view's own attachment
     * delete button calls) rather than re-implementing its unlink/
     * thumbnail-cleanup/orphaned-comment logic a second time.
     *
     * @param int $id
     * @param int $task_id
     */
    public function meeting_delete_attachment($id, $task_id)
    {
        if (!is_owned_meeting_task($task_id)) {
            ajax_access_denied();
        }

        $this->tasks_model->remove_task_attachment($id);

        echo json_encode(['success' => true]);
    }

    /**
     * Meeting progress - reuses Tasks_model::mark_as() directly (native
     * Task status), per the explicit "reuse existing Task Status for
     * meeting progress" instruction - no separate meeting-status concept.
     * Additionally logs a Lead activity entry, since mark_as() itself
     * only auto-logs to the Lead activity log for rel_type='project'
     * tasks, not 'lead' ones - without this, the unified timeline would
     * never show a meeting's status changing.
     *
     * @param int $task_id
     */
    public function meeting_update_status($task_id)
    {
        if (!is_owned_meeting_task($task_id)) {
            ajax_access_denied();
        }

        $status = (int) $this->input->post('status');

        $this->db->select('rel_id, name');
        $this->db->where('id', $task_id);
        $task = $this->db->get(db_prefix() . 'tasks')->row();

        $this->tasks_model->mark_as($status, $task_id);

        if ($task) {
            $this->leads_model->log_lead_activity($task->rel_id, 'not_task_status_changed', false, serialize([
                $task->name,
                format_task_status($status, false, true),
            ]));
        }

        echo json_encode(['success' => true]);
    }

    /**
     * Pending Follow-ups - the full-list page the Dashboard's "Pending
     * Follow-ups" KPI card and "Upcoming Follow-ups" panel both link to.
     * Same "Next Follow-up Date >= today" definition
     * Field_portal_model::get_upcoming_followups() already uses for the
     * dashboard panel - this page is that same data source, unlimited and
     * DataTable-backed instead of a 5-row preview.
     */
    public function followups()
    {
        $allowed_filters  = ['all', 'pending', 'today', 'tomorrow', 'upcoming', 'completed', 'overdue'];
        $requested_filter = $this->input->get('filter');

        $data['title']          = _l('field_portal_all_followups');
        $data['default_filter'] = in_array($requested_filter, $allowed_filters, true) ? $requested_filter : 'all';

        $this->load->view('followups', $data);
    }

    /**
     * All Follow-ups - the ONE Follow-ups DataTable source, reused for
     * every filter tab (All/Pending/Today/Tomorrow/Upcoming/Completed/
     * Overdue) - only the WHERE clause changes per $this->input->post('filter'),
     * never a second controller action/view/query. Rooted at tblfollowups
     * (one row per Case, i.e. per follow-up engagement - not per Lead, so
     * a lead's full follow-up history, not just its current state, is
     * representable) LEFT JOINed to its Lead for
     * Company/Phone/Current Status, and to tblstaff for the Telecaller
     * this specific case was assigned to (followups.assigned_staff_id,
     * captured at Case-creation time - stays correct for a since-returned/
     * closed case, unlike leads.assigned which reflects only the Lead's
     * CURRENT handler). Priority/next_follow_up_date/case_status/
     * last_activity_at are all existing tblfollowups columns already kept
     * in sync by Follow_ups_model's own action methods - nothing new is
     * written here, only read.
     */
    public function followups_table()
    {
        if (!$this->input->is_ajax_request()) {
            return;
        }

        $staff_id = get_staff_user_id();

        $aColumns = [
            db_prefix() . 'leads.name',
            db_prefix() . 'leads.company',
            db_prefix() . 'leads.phonenumber',
            db_prefix() . 'leads_status.name as status_name',
            "CONCAT(telecaller.firstname, ' ', telecaller.lastname) as telecaller_name",
            db_prefix() . 'followups.next_follow_up_date',
            db_prefix() . 'followups.priority',
            db_prefix() . 'followups.last_activity_at',
        ];

        $sIndexColumn = 'id';
        $sTable       = db_prefix() . 'followups';

        $join = [
            'LEFT JOIN ' . db_prefix() . 'leads ON ' . db_prefix() . 'leads.id = ' . db_prefix() . 'followups.rel_id',
            'LEFT JOIN ' . db_prefix() . 'leads_status ON ' . db_prefix() . 'leads_status.id = ' . db_prefix() . 'leads.status',
            'LEFT JOIN ' . db_prefix() . 'staff telecaller ON telecaller.staffid = ' . db_prefix() . 'followups.assigned_staff_id',
        ];

        $where = [
            "AND " . db_prefix() . "followups.rel_type = 'lead'",
            field_portal_my_leads_where($staff_id),
        ];

        // "Completed" = Converted Customer OR a closed Follow-up Case -
        // reused verbatim by both the 'completed' and 'overdue' branches
        // below (Overdue is explicitly "not yet completed").
        $completed_expr = '(' . db_prefix() . 'leads.client_id > 0 OR ' . db_prefix() . "followups.case_status = 'closed')";

        $filter = $this->input->post('filter');

        if ($filter === 'pending') {
            // Not converted, not Lost, and still due today or later -
            // reuses the same "Lost" status lookup lead_mark_lost_submit()
            // already does, rather than string-matching the status name.
            $lost_status_id = $this->field_portal_model->get_status_id_by_name('Lost');
            $where[]        = 'AND ' . db_prefix() . 'followups.next_follow_up_date >= CURDATE()';
            $where[]        = 'AND ' . db_prefix() . 'leads.client_id = 0';
            $where[]        = 'AND ' . db_prefix() . 'leads.status != ' . (int) $lost_status_id;
        } elseif ($filter === 'today') {
            $where[] = 'AND DATE(' . db_prefix() . 'followups.next_follow_up_date) = CURDATE()';
        } elseif ($filter === 'tomorrow') {
            $where[] = 'AND DATE(' . db_prefix() . 'followups.next_follow_up_date) = DATE_ADD(CURDATE(), INTERVAL 1 DAY)';
        } elseif ($filter === 'upcoming') {
            $where[] = 'AND DATE(' . db_prefix() . 'followups.next_follow_up_date) > DATE_ADD(CURDATE(), INTERVAL 1 DAY)';
        } elseif ($filter === 'completed') {
            $where[] = 'AND ' . $completed_expr;
        } elseif ($filter === 'overdue') {
            $where[] = 'AND ' . db_prefix() . 'followups.next_follow_up_date < CURDATE()';
            $where[] = 'AND NOT ' . $completed_expr;
        }
        // 'all' (or anything unrecognized): no extra condition.

        $result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, [
            db_prefix() . 'followups.id',
            db_prefix() . 'followups.rel_id',
        ]);

        $output  = $result['output'];
        $rResult = $result['rResult'];

        foreach ($rResult as $aRow) {
            // 'rel_id' (not the qualified 'tblfollowups.rel_id') - unlike
            // aColumns entries (auto-aliased to their qualified name by
            // data_tables_init() when ambiguous), an unaliased
            // $additionalSelect column comes back from the driver under
            // its own bare column name.
            $lead_id = (int) $aRow['rel_id'];
            $row     = [];

            $row[] = '<a href="' . admin_url('field_portal/lead_details/' . $lead_id) . '" class="tw-font-medium">' . e($aRow[db_prefix() . 'leads.name']) . '</a>';
            $row[] = $aRow[db_prefix() . 'leads.company'] ? e($aRow[db_prefix() . 'leads.company']) : '-';
            $row[] = $aRow[db_prefix() . 'leads.phonenumber'] ? e($aRow[db_prefix() . 'leads.phonenumber']) : '-';
            $row[] = $aRow['status_name'] ? e($aRow['status_name']) : '-';
            $row[] = $aRow['telecaller_name'] ? e($aRow['telecaller_name']) : '-';
            $row[] = $aRow[db_prefix() . 'followups.next_follow_up_date'] ? e(_d($aRow[db_prefix() . 'followups.next_follow_up_date'])) : '-';
            $row[] = $aRow[db_prefix() . 'followups.priority'] ? e(ucfirst($aRow[db_prefix() . 'followups.priority'])) : '-';
            $row[] = $aRow[db_prefix() . 'followups.last_activity_at'] ? e(_dt($aRow[db_prefix() . 'followups.last_activity_at'])) : '-';
            $row[] = '<a href="' . admin_url('field_portal/lead_details/' . $lead_id) . '" class="btn btn-default btn-sm" title="' . _l('field_portal_view_details') . '"><i class="fa-solid fa-eye"></i></a>';

            $row['DT_RowId'] = 'field_portal_followup_' . $lead_id;
            $output['aaData'][] = $row;
        }

        echo json_encode($output);
    }

    /**
     * Assigned to Telecaller - the full-list page the Dashboard's KPI card
     * of the same name links to. Same Lead-level definition
     * Field_portal_model::get_dashboard_summary()'s 'assigned_to_telecaller'
     * count uses (addedfrom = this Field Executive AND assigned is
     * currently a Telecalling-department staff member), LEFT JOINed to the
     * open Follow-up Case (if any) for Telecaller name/priority/next
     * follow-up rather than a second per-row lookup.
     */
    public function assigned_to_telecaller()
    {
        $data['title'] = _l('field_portal_assigned_to_telecaller');

        $this->load->view('assigned_to_telecaller', $data);
    }

    /**
     * Assigned to Telecaller DataTable source.
     */
    public function assigned_to_telecaller_table()
    {
        if (!$this->input->is_ajax_request()) {
            return;
        }

        $staff_id = get_staff_user_id();

        $telecaller_ids = array_map('intval', array_column(field_portal_get_telecallers(), 'staffid'));

        if (!$telecaller_ids) {
            echo json_encode(['draw' => (int) $this->input->post('draw'), 'iTotalRecords' => 0, 'iTotalDisplayRecords' => 0, 'aaData' => []]);

            return;
        }

        $aColumns = [
            db_prefix() . 'leads.name',
            "CONCAT(telecaller.firstname, ' ', telecaller.lastname) as telecaller_name",
            db_prefix() . 'followups.date_created',
            db_prefix() . 'followups.priority',
            db_prefix() . 'leads_status.name as status_name',
            db_prefix() . 'followups.next_follow_up_date',
        ];

        $sIndexColumn = 'id';
        $sTable       = db_prefix() . 'leads';

        $join = [
            'LEFT JOIN ' . db_prefix() . 'staff telecaller ON telecaller.staffid = ' . db_prefix() . 'leads.assigned',
            'LEFT JOIN ' . db_prefix() . 'leads_status ON ' . db_prefix() . 'leads_status.id = ' . db_prefix() . 'leads.status',
            'LEFT JOIN ' . db_prefix() . 'followups ON ' . db_prefix() . 'followups.rel_id = ' . db_prefix() . "leads.id AND " . db_prefix() . "followups.rel_type = 'lead' AND " . db_prefix() . 'followups.case_status = ' . "'open'",
        ];

        $where = [
            // is_admin() bypass matches field_portal_my_leads_where()'s own
            // convention (every other table in this portal shows Admin
            // everything, unscoped, rather than "this staff id's own
            // leads" - which for an Admin visiting directly would
            // otherwise render an artificially empty table).
            is_admin() ? '' : 'AND ' . db_prefix() . 'leads.addedfrom = ' . (int) $staff_id,
            'AND ' . db_prefix() . 'leads.assigned IN (' . implode(',', $telecaller_ids) . ')',
        ];

        $result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, [
            db_prefix() . 'leads.id',
        ]);

        $output  = $result['output'];
        $rResult = $result['rResult'];

        foreach ($rResult as $aRow) {
            $lead_id = (int) $aRow['id'];
            $row     = [];

            $row[] = '<a href="' . admin_url('field_portal/lead_details/' . $lead_id) . '" class="tw-font-medium">' . e($aRow[db_prefix() . 'leads.name']) . '</a>';
            $row[] = $aRow['telecaller_name'] ? e($aRow['telecaller_name']) : '-';
            $row[] = $aRow[db_prefix() . 'followups.date_created'] ? e(_dt($aRow[db_prefix() . 'followups.date_created'])) : '-';
            $row[] = $aRow[db_prefix() . 'followups.priority'] ? e(ucfirst($aRow[db_prefix() . 'followups.priority'])) : '-';
            $row[] = $aRow['status_name'] ? e($aRow['status_name']) : '-';
            $row[] = $aRow[db_prefix() . 'followups.next_follow_up_date'] ? e(_d($aRow[db_prefix() . 'followups.next_follow_up_date'])) : '-';
            $row[] = '<a href="' . admin_url('field_portal/lead_details/' . $lead_id) . '" class="btn btn-default btn-sm" title="' . _l('field_portal_view_details') . '"><i class="fa-solid fa-eye"></i></a>';

            $row['DT_RowId'] = 'field_portal_assigned_' . $lead_id;
            $output['aaData'][] = $row;
        }

        echo json_encode($output);
    }

    /**
     * Lead Activity - the full timeline page the Dashboard's "Recent Lead
     * Activity" panel links to. Reuses
     * Field_portal_model::get_recent_lead_activity() directly (the exact
     * same query the dashboard panel's own 5-row preview already runs),
     * just with a much higher cap instead of a second implementation.
     */
    public function lead_activity()
    {
        $staff_id = get_staff_user_id();

        $data['title']    = _l('field_portal_lead_activity');
        $data['activity'] = $this->field_portal_model->get_recent_lead_activity($staff_id, 200);

        $this->load->view('lead_activity', $data);
    }

    /**
     * Customers - every customer converted from one of this Field
     * Executive's own leads. The KPI cards (Total/Today/This Month/This
     * Year) are rendered once here with their initial, always-unfiltered
     * values (Field_portal_model::get_customer_conversion_summary()) so
     * they aren't blank before JS runs - customers_summary() below keeps
     * them fresh after that.
     */
    public function customers()
    {
        $staff_id = get_staff_user_id();

        $data['title'] = _l('field_portal_customers');

        $this->load->model('field_portal_model');
        $data['conversion_summary'] = $this->field_portal_model->get_customer_conversion_summary($staff_id);

        $this->load->view('customers', $data);
    }

    /**
     * KPI cards' AJAX refresh - Total/Today/This Month/This Year, always
     * describing this Field Executive's WHOLE converted-customer set
     * (never narrowed by whatever date-range filter is currently applied
     * to the table below them - see get_customer_conversion_summary()'s
     * own doc comment for why). Called after every table redraw so the
     * numbers never go stale.
     */
    public function customers_summary()
    {
        if (!$this->input->is_ajax_request()) {
            return;
        }

        $staff_id = get_staff_user_id();

        $this->load->model('field_portal_model');
        echo json_encode($this->field_portal_model->get_customer_conversion_summary($staff_id));
    }

    /**
     * Customers DataTable source - every customer converted from one of
     * this Field Executive's own leads. Rooted at tblclients, joined to
     * tblleads via tblclients.leadid (the reliably-populated direction -
     * tblleads.client_id has real historical gaps, see
     * application/migrations/374_version_374.php), scoped by 'addedfrom'
     * only (Original Owner) - matching the Dashboard's
     * 'converted_customers' KPI and the "Recent Customer Conversions"
     * panel. Date filtering reads ONLY tblclients.converted_at (never
     * dateadded/tblleads), via the SAME shared resolver the native Admin
     * Customers page uses (application/helpers/lead_workspace_helper.php)
     * - this portal's UI always submits concrete From/To dates (its quick-
     * filter buttons just pre-fill them client-side), so it's always
     * treated as a 'custom' range server-side.
     */
    public function customers_table()
    {
        if (!$this->input->is_ajax_request()) {
            return;
        }

        $staff_id = get_staff_user_id();
        $prefix   = db_prefix();

        $this->load->helper('lead_workspace');

        $aColumns = [
            $prefix . 'clients.company',
            $prefix . 'clients.phonenumber',
            $prefix . 'clients.address',
            $prefix . 'clients.active',
            $prefix . 'leads.name as lead_name',
            $prefix . 'clients.converted_at',
        ];

        $sIndexColumn = 'userid';
        $sTable       = $prefix . 'clients';

        $join = [
            'INNER JOIN ' . $prefix . 'leads ON ' . $prefix . 'leads.id = ' . $prefix . 'clients.leadid',
        ];

        $where = [
            'AND ' . $prefix . 'leads.addedfrom = ' . (int) $staff_id,
        ];

        $converted_date_from = $this->input->post('converted_date_from');
        $converted_date_to   = $this->input->post('converted_date_to');
        $date_where          = customer_conversion_date_where(
            $converted_date_from && $converted_date_to ? 'custom' : '',
            $converted_date_from,
            $converted_date_to
        );
        if ($date_where) {
            $where[] = 'AND ' . $date_where;
        }

        $result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, [
            $prefix . 'leads.id as lead_id',
        ]);

        $output  = $result['output'];
        $rResult = $result['rResult'];

        foreach ($rResult as $aRow) {
            $lead_id = (int) $aRow['lead_id'];
            $row     = [];

            $row[] = '<a href="' . admin_url('field_portal/lead_details/' . $lead_id) . '" class="tw-font-medium">' . e($aRow[$prefix . 'clients.company'] ?: $aRow['lead_name']) . '</a>';
            $row[] = $aRow[$prefix . 'clients.phonenumber'] ? e($aRow[$prefix . 'clients.phonenumber']) : '-';
            $row[] = $aRow[$prefix . 'clients.address'] ? e($aRow[$prefix . 'clients.address']) : '-';
            $row[] = (int) $aRow[$prefix . 'clients.active'] ? 'Active' : 'Inactive';
            $row[] = e($aRow['lead_name']);
            $row[] = $aRow[$prefix . 'clients.converted_at'] ? e(_d($aRow[$prefix . 'clients.converted_at'])) : '-';
            $row[] = '<a href="' . admin_url('field_portal/lead_details/' . $lead_id) . '" class="btn btn-default btn-sm" title="' . _l('field_portal_view_details') . '"><i class="fa-solid fa-eye"></i></a>';

            $row['DT_RowId'] = 'field_portal_customer_' . $lead_id;
            $output['aaData'][] = $row;
        }

        echo json_encode($output);
    }

    /**
     * Small helper - a single custom field's metadata (id/type/options),
     * looked up by name rather than hardcoded id, same reasoning as every
     * other custom-field lookup in this module.
     *
     * @param  string $name
     * @return array|null
     */
    private function _get_field_meta($name)
    {
        $field = get_custom_fields('leads', ['name' => $name]);

        return !empty($field) ? $field[0] : null;
    }

    /**
     * Same as _get_field_meta() but for the Task custom fields (Meeting
     * Time/Meeting Type, migration 369) instead of the Lead ones.
     *
     * @param  string $name
     * @return array|null
     */
    private function _get_task_field_meta($name)
    {
        $field = get_custom_fields('tasks', ['name' => $name]);

        return !empty($field) ? $field[0] : null;
    }

    // -------------------------------------------------------------------------
    // Daily Expenses
    // -------------------------------------------------------------------------

    /**
     * Daily Expenses - list page with summary cards, filters, and DataTable.
     */
    public function expenses()
    {
        $staff_id              = get_staff_user_id();
        $data['title']         = _l('field_portal_daily_expenses');
        $data['categories']    = $this->_expense_categories();

        // Initial summary (no filters applied yet).
        $data['summary']       = $this->field_portal_expenses_model->get_summary($staff_id);
        $data['category_totals'] = $this->field_portal_expenses_model->get_category_totals($staff_id, [], $data['categories']);

        // All leads for the lead dropdown.
        $data['leads'] = $this->leads_model->get();

        // Available years for the Year filter dropdown.
        $this->db->select('DISTINCT YEAR(expense_date) as yr');
        $this->db->where('staff_id', $staff_id);
        $this->db->order_by('yr', 'desc');
        $years = $this->db->get(db_prefix() . 'field_expenses')->result_array();
        $data['years'] = array_column($years, 'yr');

        // Filter defaults from URL params.
        $data['default_category'] = $this->input->get('category');
        $data['default_from']     = $this->input->get('from');
        $data['default_to']       = $this->input->get('to');
        $data['default_month']    = $this->input->get('month');
        $data['default_year']     = $this->input->get('year');

        $this->load->view('expenses', $data);
    }

    /**
     * Expenses DataTable source - grouped one row per daily entry
     * (staff_id, expense_date, lead_id), not one row per category record.
     * A day's amounts are combined with GROUP_CONCAT() and split back apart
     * in PHP for display; category/free-text filters are applied via
     * HAVING (post-aggregation) rather than WHERE, so a day keeps showing
     * ALL of its categories as long as ANY one of them matches - filtering
     * never truncates an entry down to only the matching category.
     *
     * data_tables_init() is NOT reused here: its GROUP BY support computes
     * both row counts via "SELECT COUNT(*) ... GROUP BY ..." and takes
     * ->row() of the result, which with a GROUP BY returns only the FIRST
     * group's count, not the number of groups - it silently breaks
     * pagination the moment there is more than one page of grouped rows.
     * This method reimplements the same draw/start/length/order/search
     * DataTables protocol by hand, with a correct COUNT(*) over a derived
     * table instead.
     */
    public function expenses_table()
    {
        if (!$this->input->is_ajax_request()) {
            return;
        }

        $staff_id = (int) get_staff_user_id();
        $prefix   = db_prefix();

        $where = ['staff_id = ' . $staff_id];

        $from_date = to_sql_date($this->input->post('from'));
        if ($from_date && preg_match('/^\d{4}-\d{2}-\d{2}$/', $from_date)) {
            $where[] = "expense_date >= '" . $from_date . "'";
        }

        $to_date = to_sql_date($this->input->post('to'));
        if ($to_date && preg_match('/^\d{4}-\d{2}-\d{2}$/', $to_date)) {
            $where[] = "expense_date <= '" . $to_date . "'";
        }

        $month = $this->input->post('month');
        if ($month) {
            $where[] = 'MONTH(expense_date) = ' . (int) $month;
        }

        $year = $this->input->post('year');
        if ($year) {
            $where[] = 'YEAR(expense_date) = ' . (int) $year;
        }

        $where_sql = 'WHERE ' . implode(' AND ', $where);

        $category = $this->input->post('category');
        $category_having = $category
            ? "SUM(CASE WHEN {$prefix}field_expenses.category = '" . $this->db->escape_str($category) . "' THEN 1 ELSE 0 END) > 0"
            : '';

        $search_post  = $this->input->post('search');
        $search_value = is_array($search_post) ? trim($search_post['value'] ?? '') : '';

        $having = array_filter([$category_having]);
        if ($search_value !== '') {
            $escaped  = $this->db->escape_like_str($search_value);
            $having[] = "(category_amounts LIKE '%{$escaped}%' ESCAPE '!' OR remarks LIKE '%{$escaped}%' ESCAPE '!' OR lead_name LIKE '%{$escaped}%' ESCAPE '!')";
        }
        $having_sql = $having ? ('HAVING ' . implode(' AND ', $having)) : '';

        $base_select = "
            SELECT
                {$prefix}field_expenses.expense_date,
                {$prefix}field_expenses.lead_id,
                {$prefix}leads.name as lead_name,
                {$prefix}leads.company as lead_company,
                GROUP_CONCAT(CONCAT({$prefix}field_expenses.category, ':::', {$prefix}field_expenses.amount) SEPARATOR '|||') as category_amounts,
                SUM({$prefix}field_expenses.amount) as total_amount,
                MIN({$prefix}field_expenses.remarks) as remarks,
                MIN({$prefix}field_expenses.created_at) as created_at,
                (SELECT COUNT(*) FROM {$prefix}field_expense_attachments att WHERE att.staff_id = {$staff_id} AND att.expense_date = {$prefix}field_expenses.expense_date) as attachment_count
            FROM {$prefix}field_expenses
            LEFT JOIN {$prefix}leads ON {$prefix}leads.id = {$prefix}field_expenses.lead_id
            {$where_sql}
            GROUP BY {$prefix}field_expenses.expense_date, {$prefix}field_expenses.lead_id
        ";

        $order_columns = ['expense_date', 'lead_name', 'category_amounts', 'total_amount', 'attachment_count', 'created_at', 'expense_date'];
        $order_sql     = 'ORDER BY expense_date DESC';
        $order_post    = $this->input->post('order');
        if ($order_post && isset($order_post[0])) {
            $col_index = (int) $order_post[0]['column'];
            $dir       = strtoupper($order_post[0]['dir']) === 'ASC' ? 'ASC' : 'DESC';
            $col       = $order_columns[$col_index] ?? 'expense_date';
            $order_sql = 'ORDER BY ' . $col . ' ' . $dir;
        }

        $limit_sql = '';
        $start     = $this->input->post('start');
        $length    = $this->input->post('length');
        if (is_numeric($start) && $length != '-1') {
            $limit_sql = 'LIMIT ' . (int) $start . ', ' . (int) $length;
        }

        $rResult = $this->db->query($base_select . ' ' . $having_sql . ' ' . $order_sql . ' ' . $limit_sql)->result_array();

        // Filtered count - matches every filter above (scope + category + free-text search).
        $iFilteredTotal = (int) $this->db->query("SELECT COUNT(*) as cnt FROM ({$base_select} {$having_sql}) t")->row()->cnt;

        // Total count - scope + category filters only, ignoring the free-text
        // search box, matching data_tables_init()'s own iTotal/iFilteredTotal
        // convention used everywhere else in this codebase.
        $having_no_search_sql = $category_having ? ('HAVING ' . $category_having) : '';
        $iTotal = (int) $this->db->query("SELECT COUNT(*) as cnt FROM ({$base_select} {$having_no_search_sql}) t")->row()->cnt;

        $draw   = $this->input->post('draw');
        $output = [
            'draw'                 => $draw ? (int) $draw : 0,
            'iTotalRecords'        => $iTotal,
            'iTotalDisplayRecords' => $iFilteredTotal,
            'aaData'               => [],
        ];

        foreach ($rResult as $aRow) {
            $row = [];

            $row[] = $aRow['expense_date'] ? e(_d($aRow['expense_date'])) : '-';

            if ($aRow['lead_id']) {
                $row[] = '<a href="' . admin_url('field_portal/lead_details/' . (int) $aRow['lead_id']) . '">' . e($aRow['lead_name']) . '</a>';
            } else {
                $row[] = '-';
            }

            $cat_lines = [];
            if ($aRow['category_amounts']) {
                foreach (explode('|||', $aRow['category_amounts']) as $pair) {
                    [$cat, $amt] = array_pad(explode(':::', $pair, 2), 2, 0);
                    $cat_lines[] = e($cat) . ' ' . e(app_format_money((float) $amt, get_base_currency()->id));
                }
            }
            $row[] = $cat_lines ? implode('<br>', $cat_lines) : '-';

            $row[] = e(app_format_money((float) $aRow['total_amount'], get_base_currency()->id));

            $att_count = (int) $aRow['attachment_count'];
            $row[] = $att_count > 0
                ? '<a href="#" class="btn btn-default btn-sm expense-attachments-view" data-date="' . e($aRow['expense_date']) . '" title="' . _l('field_portal_expenses_attachments') . '"><i class="fa-solid fa-paperclip"></i> ' . _l('field_portal_expenses_attachments_count', $att_count) . '</a>'
                : '-';

            $row[] = $aRow['created_at'] ? e(_dt($aRow['created_at'])) : '-';

            $date_sql  = $aRow['expense_date'];
            $lead_attr = $aRow['lead_id'] ? (int) $aRow['lead_id'] : 0;
            $actions   = '<a href="#" class="btn btn-default btn-sm expense-view-day" data-date="' . e($date_sql) . '" data-lead="' . $lead_attr . '" title="' . _l('field_portal_view_details') . '"><i class="fa-solid fa-eye"></i></a>';
            $actions  .= ' <a href="#" class="btn btn-default btn-sm expense-edit-day" data-date="' . e($date_sql) . '" data-lead="' . $lead_attr . '" title="' . _l('field_portal_edit_day') . '"><i class="fa-solid fa-pencil"></i></a>';
            $actions  .= ' <a href="#" class="btn btn-danger btn-sm expense-delete-day" data-date="' . e($date_sql) . '" data-lead="' . $lead_attr . '" title="' . _l('field_portal_delete') . '"><i class="fa-solid fa-trash"></i></a>';
            $row[] = $actions;

            $row['DT_RowId'] = 'field_portal_expense_day_' . $date_sql . '_' . $lead_attr;
            $output['aaData'][] = $row;
        }

        echo json_encode($output);
    }

    /**
     * AJAX - summary cards (reloads when filters change).
     */
    public function expenses_get_summary()
    {
        if (!$this->input->is_ajax_request()) {
            return;
        }

        $staff_id = get_staff_user_id();
        $filters  = $this->_expense_filters_from_post();

        $summary         = $this->field_portal_expenses_model->get_summary($staff_id, $filters);
        $category_totals = $this->field_portal_expenses_model->get_category_totals($staff_id, $filters);

        echo json_encode([
            'summary'         => $summary,
            'category_totals' => $category_totals,
        ]);
    }

    /**
     * AJAX - save (insert or update) a full day's expenses.
     *
     * Accepts one amount per category.  Non-zero amounts are stored as
     * individual records; zero amounts delete any existing record for that
     * category+date. original_lead_id (mirroring original_date) identifies
     * which existing daily entry is being edited, since identity is
     * (date, lead_id) - without it, changing the Lead on an edit would
     * leave the old Lead's entry behind as an orphaned duplicate instead of
     * being moved/removed.
     */
    public function expenses_save_day()
    {
        if (!$this->input->is_ajax_request()) {
            return;
        }

        $staff_id      = get_staff_user_id();
        $expense_date  = to_sql_date($this->input->post('expense_date')) ?: date('Y-m-d');
        $original_date = $this->input->post('original_date')
            ? to_sql_date($this->input->post('original_date')) : null;
        $lead_id          = (int) $this->input->post('lead_id') ?: null;
        $original_lead_id = $this->input->post('original_lead_id');
        $original_lead_id = ($original_lead_id === '' || $original_lead_id === null) ? 'ANY' : ((int) $original_lead_id ?: null);
        $remarks       = $this->input->post('remarks');

        $categories = $this->_expense_categories();
        $amounts    = [];
        $hasNonZero = false;

        foreach ($categories as $cat) {
            $key = 'amount_' . strtolower(str_replace([' ', '/'], '_', $cat));
            $amount = (float) $this->input->post($key);
            $amounts[$cat] = $amount;
            if ($amount > 0) {
                $hasNonZero = true;
            }
        }

        if (!$hasNonZero) {
            echo json_encode(['success' => false, 'message' => _l('field_portal_expenses_validation')]);
            return;
        }

        $result = $this->field_portal_expenses_model->save_day(
            $staff_id, $expense_date, $lead_id, $remarks, $amounts, $original_date, $original_lead_id
        );

        $this->_handle_expense_attachments($staff_id, $expense_date);

        echo json_encode([
            'success' => true,
            'message' => _l('field_portal_expenses_added'),
            'result'  => $result,
        ]);
    }

    /**
     * AJAX - get one daily entry's full breakdown (date + lead_id
     * together, since that's a day's real identity) - used to both
     * populate the Edit form and render the read-only View modal, so
     * there's a single source of truth for "what does this daily entry
     * contain" rather than one query per surface.
     */
    public function expenses_edit_day($date, $lead_id = null)
    {
        if (!$this->input->is_ajax_request()) {
            return;
        }

        $staff_id = get_staff_user_id();
        // The date is passed in SQL (Y-m-d) format from the DataTable.
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            echo json_encode(['success' => false, 'message' => _l('field_portal_expenses_not_found')]);
            return;
        }
        $date_sql = $date;

        // lead_id arrives as a URL segment - '0'/'null' both mean "no Lead"
        // (a literal empty segment isn't addressable in a URL).
        $lead_filter = ($lead_id === null || $lead_id === 'null' || $lead_id === '0') ? null : (int) $lead_id;

        $records = $this->field_portal_expenses_model->get_by_staff_date($staff_id, $date_sql, $lead_filter);

        if (empty($records)) {
            echo json_encode(['success' => false, 'message' => _l('field_portal_expenses_not_found')]);
            return;
        }

        $categories    = [];
        $lead_id_found = null;
        $remarks       = '';
        $grand_total   = 0;

        foreach ($records as $r) {
            $categories[$r['category']] = (float) $r['amount'];
            $grand_total += (float) $r['amount'];
            if ($r['lead_id']) {
                $lead_id_found = (int) $r['lead_id'];
            }
            if ($r['remarks']) {
                $remarks = $r['remarks'];
            }
        }

        $lead_name = null;
        if ($lead_id_found) {
            $lead = $this->leads_model->get($lead_id_found);
            $lead_name = $lead ? $lead->name . ($lead->company ? ' (' . $lead->company . ')' : '') : null;
        }

        echo json_encode([
            'success'     => true,
            'date'        => _d($date_sql),
            'date_sql'    => $date_sql,
            'lead_id'     => $lead_id_found,
            'lead_name'   => $lead_name,
            'remarks'     => $remarks,
            'categories'  => $categories,
            'grand_total' => $grand_total,
        ]);
    }

    /**
     * AJAX - delete a whole daily entry (every category record for the
     * date+lead group, plus every attachment for that date).
     */
    public function expenses_delete_day($date, $lead_id = null)
    {
        if (!$this->input->is_ajax_request()) {
            return;
        }

        $staff_id = get_staff_user_id();

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            echo json_encode(['success' => false, 'message' => _l('field_portal_expenses_not_found')]);
            return;
        }

        $lead_filter = ($lead_id === null || $lead_id === 'null' || $lead_id === '0') ? null : (int) $lead_id;

        $this->field_portal_expenses_model->delete_day($staff_id, $date, $lead_filter);

        echo json_encode(['success' => true, 'message' => _l('field_portal_expenses_deleted')]);
    }

    /**
     * AJAX - get attachments for a date.
     */
    public function expenses_get_attachments()
    {
        if (!$this->input->is_ajax_request()) {
            return;
        }

        $staff_id = get_staff_user_id();
        $date     = $this->input->post('date');

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            echo json_encode(['success' => false, 'message' => '']);
            return;
        }

        $attachments = $this->field_portal_expenses_model->get_attachments($staff_id, $date);

        echo json_encode(['success' => true, 'attachments' => $attachments]);
    }

    /**
     * AJAX - update attachment title/description.
     */
    public function expenses_update_attachment()
    {
        if (!$this->input->is_ajax_request()) {
            return;
        }

        $id = (int) $this->input->post('id');
        $att = $this->field_portal_expenses_model->get_attachment($id);

        if (!$att || (int) $att->staff_id !== (int) get_staff_user_id()) {
            echo json_encode(['success' => false]);
            return;
        }

        $data = [
            'title'       => $this->input->post('title'),
            'description' => $this->input->post('description'),
        ];

        $this->field_portal_expenses_model->update_attachment($id, $data);

        echo json_encode(['success' => true]);
    }

    /**
     * AJAX - delete attachment.
     */
    public function expenses_delete_attachment($id)
    {
        if (!$this->input->is_ajax_request()) {
            return;
        }

        $att = $this->field_portal_expenses_model->get_attachment((int) $id);

        if (!$att || (int) $att->staff_id !== (int) get_staff_user_id()) {
            echo json_encode(['success' => false]);
            return;
        }

        $this->field_portal_expenses_model->delete_attachment((int) $id);

        echo json_encode(['success' => true]);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Available expense categories.
     */
    private function _expense_categories()
    {
        return [
            'Travel',
            'Fuel',
            'Food',
            'Tea / Snacks',
            'Parking',
            'Toll',
            'Hotel',
            'Client Meeting',
            'Miscellaneous',
        ];
    }

    /**
     * Read filter values from POST for summary recalculation.
     */
    private function _expense_filters_from_post()
    {
        return [
            'category'  => $this->input->post('category'),
            'from_date' => to_sql_date($this->input->post('from')),
            'to_date'   => to_sql_date($this->input->post('to')),
            'month'     => $this->input->post('month'),
            'year'      => $this->input->post('year'),
        ];
    }

    /**
     * Handle attachment file uploads submitted with the day form.
     * Files are named att_{timestamp}_{random}.ext for uniqueness.
     */
    private function _handle_expense_attachments($staff_id, $expense_date)
    {
        if (empty($_FILES['attachment_file'])) {
            return;
        }

        $upload_path = FCPATH . 'uploads/field_portal/expenses/';
        if (!is_dir($upload_path)) {
            mkdir($upload_path, 0755, true);
        }

        $titles       = $this->input->post('attachment_title') ?? [];
        $descriptions = $this->input->post('attachment_description') ?? [];
        $files        = $_FILES['attachment_file'];
        $allowed      = ['jpg', 'jpeg', 'png', 'pdf'];

        // Normalise single upload to array form.
        if (!is_array($files['name'])) {
            $files = [
                'name'     => [$files['name']],
                'type'     => [$files['type']],
                'tmp_name' => [$files['tmp_name']],
                'error'    => [$files['error']],
                'size'     => [$files['size']],
            ];
            $titles       = [$titles];
            $descriptions = [$descriptions];
        }

        foreach ($files['name'] as $i => $name) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                continue;
            }

            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed)) {
                continue;
            }

            $filename = 'att_' . time() . '_' . uniqid() . '.' . $ext;
            $dest     = $upload_path . $filename;

            if (move_uploaded_file($files['tmp_name'][$i], $dest)) {
                $this->field_portal_expenses_model->add_attachment([
                    'staff_id'      => $staff_id,
                    'expense_date'  => $expense_date,
                    'file_name'     => $filename,
                    'original_name' => $name,
                    'title'         => $titles[$i] ?? '',
                    'description'   => $descriptions[$i] ?? '',
                ]);
            }
        }
    }

}
