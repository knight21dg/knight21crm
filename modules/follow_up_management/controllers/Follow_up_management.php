<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Follow_up_management extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('follow_ups_model');
        // The Telecaller Lead page (Step 4) reuses field_portal's own
        // shared Lead Details labels (Phone/Email/Address/Discussion
        // Summary/Remarks/Attachments/etc.) via lead_workspace_helper.php's
        // render functions - its language keys are loaded here too rather
        // than duplicating them under a follow_up_management_* key.
        $this->lang->load('field_portal/field_portal', 'english');
    }

    /**
     * Entity-agnostic - gated per rel_type on the underlying entity's own
     * permission (only 'lead' is wired in v1). Mirrors Misc::add_reminder().
     *
     * @param string $rel_type
     * @param mixed  $rel_id
     */
    public function add($rel_type, $rel_id)
    {
        if (!array_key_exists($rel_type, get_followup_supported_rel_types())) {
            show_404();
        }

        if ($rel_type == 'lead' && staff_cant('view', 'leads')) {
            ajax_access_denied();
        }

        $message    = '';
        $alert_type = 'warning';

        if ($this->input->post()) {
            $insert_id = $this->follow_ups_model->add($this->input->post(), $rel_id, $rel_type);

            if ($insert_id) {
                $alert_type = 'success';
                $message    = _l('followup_added_successfully');
            }
        }

        echo json_encode([
            'alert_type' => $alert_type,
            'message'    => $message,
        ]);
    }

    /**
     * Ownership-gated the same way Misc::edit_reminder() is - creator or
     * admin only.
     *
     * @param mixed $id
     */
    public function edit($id)
    {
        $followup = $this->follow_ups_model->get($id);

        if (!$followup) {
            show_404();
        }

        if ($followup->created_by != get_staff_user_id() && !is_admin()) {
            ajax_access_denied();
        }

        if ($this->input->post()) {
            $success = $this->follow_ups_model->edit($this->input->post(), $id);
            echo json_encode([
                'alert_type' => 'success',
                'message'    => ($success ? _l('updated_successfully', _l('followup')) : ''),
            ]);

            return;
        }

        $followup->follow_up_date      = _dt($followup->follow_up_date);
        $followup->next_follow_up_date = $followup->next_follow_up_date ? _dt($followup->next_follow_up_date) : '';
        $followup->notes               = clear_textarea_breaks($followup->notes);
        echo json_encode($followup);
    }

    /**
     * @param mixed $id
     */
    public function delete($id)
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $success = $this->follow_ups_model->delete($id);

        echo json_encode([
            'alert_type' => $success ? 'success' : 'warning',
            'message'    => $success ? _l('followup_deleted') : _l('followup_failed_to_delete'),
        ]);
    }

    /**
     * Case Detail page - the standalone module's own detail view. Not
     * embedded anywhere in Leads; reached only from the All Follow-ups list
     * or the dashboard widget.
     *
     * @param mixed $id
     */
    public function view($id)
    {
        if (staff_cant('view', 'leads')) {
            access_denied('Follow-up Management');
        }

        $case = $this->follow_ups_model->get($id);

        if (!$case) {
            show_404();
        }

        $rel_data   = get_relation_data($case->rel_type, $case->rel_id);
        $rel_values = get_relation_values($rel_data, $case->rel_type);

        $this->load->model('staff_model');

        // Lead Information section - read-only lookup against Leads_model's
        // own get() (the same method Leads' own controller uses to render
        // its profile page), never writing to it. Only 'lead' is a
        // supported rel_type today (get_followup_supported_rel_types()).
        $lead = null;
        if ($case->rel_type === 'lead') {
            $this->load->model('leads_model');
            $lead = $this->leads_model->get($case->rel_id);
        }

        $department_name = '';
        $departments      = get_business_departments();
        foreach ($departments as $department) {
            if ($department['id'] == $case->department_id) {
                $department_name = $department['name'];
                break;
            }
        }

        $data['case']             = $case;
        $data['lead']             = $lead;
        $data['rel_name']         = $rel_values['name'];
        $data['rel_link']         = $rel_values['link'];
        $data['department_name']  = $department_name;
        $data['members']          = $this->staff_model->get('', ['active' => 1]);
        $data['departments']      = $departments;
        $data['history']          = $this->follow_ups_model->get_history($id);
        $data['activity_log']     = $this->follow_ups_model->get_activity_log($id);
        $data['can_act_on_case']  = !$this->case_access_denied($case);
        $data['title']            = _l('followup_case') . ' #' . $case->id;
        $data['bodyclass']        = 'follow-up-case';
        $this->load->view('case', $data);
    }

    /**
     * Log Call / Schedule Meeting / Schedule Reminder / Add Note - all four
     * Case Detail Timeline actions share this one AJAX endpoint,
     * distinguished by the posted `activity_type`.
     *
     * @param mixed $id
     */
    public function log_activity($id)
    {
        $case = $this->follow_ups_model->get($id);

        if (!$case) {
            show_404();
        }

        if ($this->case_access_denied($case)) {
            ajax_access_denied();
        }

        if ($this->input->post()) {
            $success = $this->follow_ups_model->log_case_interaction($id, $this->input->post('activity_type'), $this->input->post());

            echo json_encode([
                'alert_type' => $success ? 'success' : 'warning',
                'message'    => $success ? _l('case_action_success') : _l('case_action_failed'),
            ]);

            return;
        }

        show_404();
    }

    /**
     * Change Status action.
     *
     * @param mixed $id
     */
    public function change_status($id)
    {
        $case = $this->follow_ups_model->get($id);

        if (!$case) {
            show_404();
        }

        if ($this->case_access_denied($case)) {
            ajax_access_denied();
        }

        if ($this->input->post()) {
            $success = $this->follow_ups_model->change_case_status($id, $this->input->post('status'));

            echo json_encode([
                'alert_type' => $success ? 'success' : 'warning',
                'message'    => $success ? _l('case_action_success') : _l('case_action_failed'),
            ]);

            return;
        }

        show_404();
    }

    /**
     * Reschedule Follow-up action - the Telecaller Workspace's Reschedule
     * quick action (embedded in the Task modal, see
     * follow_up_management.php's before_task_description_section hook).
     * Same shape as every other case-action endpoint here; the actual
     * Lead -> Case -> Task -> Reminder propagation is
     * Follow_ups_model::reschedule_case()'s job.
     *
     * @param mixed $id
     */
    public function reschedule($id)
    {
        $case = $this->follow_ups_model->get($id);

        if (!$case) {
            show_404();
        }

        if ($this->case_access_denied($case)) {
            ajax_access_denied();
        }

        if ($this->input->post()) {
            $success = $this->follow_ups_model->reschedule_case($id, $this->input->post('next_follow_up_date'), $this->input->post('reminder_time'));

            echo json_encode([
                'alert_type' => $success ? 'success' : 'warning',
                'message'    => $success ? _l('case_action_success') : _l('case_action_failed'),
            ]);

            return;
        }

        show_404();
    }

    /**
     * Telecaller Workspace "Save" action - the single unified endpoint the
     * simplified 7-item Workspace submits to (see views/task_workspace.php).
     * Reuses every existing mutator unchanged (log_case_interaction() for
     * Call Outcome/Notes, reschedule_case() for Next Follow-up Date,
     * Leads_model::update_lead_status() - the exact same core method
     * admin/leads/update_lead_status calls - for the Lead Status change,
     * which in turn fires the real lead_status_changed hook and this
     * module's dispatcher, exactly like every other status change in the
     * CRM). Only the required-field validation below is new; no
     * Case/Task/Reminder/conversion logic lives in this method.
     *
     * @param mixed $id case id
     */
    public function save_workspace($id)
    {
        $case = $this->follow_ups_model->get($id);

        if (!$case) {
            show_404();
        }

        if ($this->case_access_denied($case)) {
            ajax_access_denied();
        }

        if (!$this->input->post()) {
            show_404();
        }

        $outcome              = $this->input->post('outcome');
        $notes                = $this->input->post('notes');
        $next_follow_up_date  = $this->input->post('next_follow_up_date');
        $lost_reason          = trim((string) $this->input->post('lost_reason'));
        $status_id            = $this->input->post('lead_status');

        $this->load->model('leads_model');
        $target_status_name = $status_id ? strtolower($this->leads_model->get_status($status_id)->name ?? '') : null;

        if (in_array($target_status_name, ['follow-up', 'interested'], true) && empty($next_follow_up_date)) {
            echo json_encode([
                'alert_type' => 'warning',
                'message'    => _l('followup_workspace_next_date_required'),
            ]);

            return;
        }

        if ($target_status_name === 'lost' && $lost_reason === '') {
            echo json_encode([
                'alert_type' => 'warning',
                'message'    => _l('followup_workspace_lost_reason_required'),
            ]);

            return;
        }

        if ($outcome || $notes) {
            $this->follow_ups_model->log_case_interaction($id, 'call', [
                'outcome' => $outcome,
                'notes'   => $notes,
            ]);
        }

        if (!empty($next_follow_up_date)) {
            $this->follow_ups_model->reschedule_case($id, $next_follow_up_date);
        }

        if ($lost_reason !== '') {
            $this->db->where('id', $id);
            $this->db->update(db_prefix() . 'followups', ['lost_reason' => $lost_reason]);
        }

        if ($status_id) {
            $this->leads_model->update_lead_status([
                'leadid' => $case->rel_id,
                'status' => $status_id,
            ]);
        }

        echo json_encode([
            'alert_type' => 'success',
            'message'    => _l('case_action_success'),
        ]);
    }

    /**
     * Change Priority action.
     *
     * @param mixed $id
     */
    public function change_priority($id)
    {
        $case = $this->follow_ups_model->get($id);

        if (!$case) {
            show_404();
        }

        if ($this->case_access_denied($case)) {
            ajax_access_denied();
        }

        if ($this->input->post()) {
            $success = $this->follow_ups_model->change_case_priority($id, $this->input->post('priority'));

            echo json_encode([
                'alert_type' => $success ? 'success' : 'warning',
                'message'    => $success ? _l('case_action_success') : _l('case_action_failed'),
            ]);

            return;
        }

        show_404();
    }

    /**
     * Reassign Staff action.
     *
     * @param mixed $id
     */
    public function reassign($id)
    {
        $case = $this->follow_ups_model->get($id);

        if (!$case) {
            show_404();
        }

        if ($this->case_access_denied($case)) {
            ajax_access_denied();
        }

        if ($this->input->post()) {
            $success = $this->follow_ups_model->reassign_case($id, $this->input->post('staff_id'));

            echo json_encode([
                'alert_type' => $success ? 'success' : 'warning',
                'message'    => $success ? _l('case_action_success') : _l('case_action_failed'),
            ]);

            return;
        }

        show_404();
    }

    /**
     * Close Case action.
     *
     * @param mixed $id
     */
    public function close_case($id)
    {
        $case = $this->follow_ups_model->get($id);

        if (!$case) {
            show_404();
        }

        if ($this->case_access_denied($case)) {
            ajax_access_denied();
        }

        if ($this->input->is_ajax_request()) {
            $success = $this->follow_ups_model->close_case($id);

            echo json_encode([
                'alert_type' => $success ? 'success' : 'warning',
                'message'    => $success ? _l('case_action_success') : _l('case_action_failed'),
            ]);

            return;
        }

        show_404();
    }

    /**
     * Reopen Case action.
     *
     * @param mixed $id
     */
    public function reopen_case($id)
    {
        $case = $this->follow_ups_model->get($id);

        if (!$case) {
            show_404();
        }

        if ($this->case_access_denied($case)) {
            ajax_access_denied();
        }

        if ($this->input->is_ajax_request()) {
            $success = $this->follow_ups_model->reopen_case($id);

            echo json_encode([
                'alert_type' => $success ? 'success' : 'warning',
                'message'    => $success ? _l('case_action_success') : _l('case_action_failed'),
            ]);

            return;
        }

        show_404();
    }

    /**
     * Ownership gate for the Case Detail action endpoints above - the
     * currently assigned staff member, the original creator (e.g. a case
     * auto-created for a lead they own), or an admin. Mirrors the existing
     * edit()/delete() ownership pattern, updated to prefer the new
     * assigned_staff_id (the CURRENT owner) added in migration 356.
     *
     * @param object $case
     * @return bool
     */
    private function case_access_denied($case)
    {
        return follow_up_case_access_denied($case);
    }

    /**
     * Dedicated "All Follow-ups" page - unlike Reminders' equivalent page,
     * this one has a proper sidebar menu entry (registered in
     * follow_up_management.php) rather than only being reachable via the
     * dashboard widget link.
     */
    public function all_follow_ups()
    {
        if (staff_cant('view', 'leads')) {
            access_denied('Follow-up Management');
        }

        $this->load->model('staff_model');
        $data['members']     = $this->staff_model->get('', ['active' => 1]);
        $data['departments'] = get_business_departments();
        $data['title']       = _l('all_followups');
        $data['bodyclass']   = 'all-follow-ups';
        $this->load->view('all_follow_ups', $data);
    }

    public function all_follow_ups_table()
    {
        if (staff_cant('view', 'leads')) {
            ajax_access_denied();
        }

        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data('all_follow_ups');
        }
    }

    /**
     * My Cases - the Telecaller work queue. Visibility (own/department/all)
     * is enforced entirely inside the DataTable source
     * (application/views/admin/tables/my_cases.php) and get_case_counts()
     * via get_followup_case_visibility_where() - this action itself only
     * gates on the same base 'view leads' capability every other page in
     * this module already uses.
     */
    public function my_cases()
    {
        if (staff_cant('view', 'leads')) {
            access_denied('Follow-up Management');
        }

        $this->load->model('staff_model');
        $data['members']     = $this->staff_model->get('', ['active' => 1]);
        $data['departments'] = get_business_departments();
        $data['counts']      = $this->follow_ups_model->get_case_counts();
        // Deep-link support for the Dashboard's clickable KPI cards - the
        // filter <select>s below pre-select these on render, and
        // initDataTable() reads their .val() on its own first load, so no
        // separate "initial filter" plumbing is needed.
        $data['prefill'] = [
            'case_status' => $this->input->get('case_status'),
            'priority'    => $this->input->get('priority'),
            'staff_id'    => $this->input->get('staff_id'),
        ];
        $data['title']     = _l('my_cases');
        $data['bodyclass'] = 'follow-up-my-cases';
        $this->load->view('my_cases', $data);
    }

    public function my_cases_table()
    {
        if (staff_cant('view', 'leads')) {
            ajax_access_denied();
        }

        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data('my_cases');
        }
    }

    /**
     * My Follow-ups - the dedicated Telecaller work queue. Lead-centric
     * (one row per assigned lead currently in an open Follow-up Case),
     * distinct from the Case-centric My Cases page above - left
     * untouched, no regression risk for its existing Admin/Manager
     * users. Same visibility rule reused (get_followup_case_visibility_where(),
     * applied inside the DataTable source, application/views/admin/
     * tables/my_follow_ups.php), same base capability gate as every
     * other page in this module.
     *
     * Exists so a Telecaller never needs the generic Tasks list, or the
     * Leads module, to reach the Telecaller Workspace - the "Open
     * Workspace" button on each row launches the exact same existing
     * Workspace (embedded in the Task modal) via init_task_modal(), the
     * same global function every other task-opening link in Perfex
     * already uses.
     */
    public function my_follow_ups()
    {
        if (staff_cant('view', 'leads') || !follow_up_management_can_access_telecaller_pages()) {
            access_denied('Follow-up Management');
        }

        $data['title']     = _l('my_follow_ups');
        $data['bodyclass'] = 'follow-up-my-follow-ups';
        $this->load->view('my_follow_ups', $data);
    }

    public function my_follow_ups_table()
    {
        if (staff_cant('view', 'leads') || !follow_up_management_can_access_telecaller_pages()) {
            ajax_access_denied();
        }

        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data('my_follow_ups');
        }
    }

    /**
     * Dashboard mini-fragment - the Telecaller Work Queue capped to a
     * handful of rows, for the native Dashboard's "My Tasks" tab swap
     * (a plain Telecaller only - see the app_admin_footer callback
     * follow_up_management_dashboard_tab_swap_script() in
     * follow_up_management.php). Reuses the same visibility rule and
     * Next Follow-up Date lookup the full page uses
     * (Follow_ups_model::get_my_follow_up_leads_for_widget()) rather
     * than a third copy of either.
     */
    public function my_follow_ups_widget()
    {
        if (staff_cant('view', 'leads') || !follow_up_management_can_access_telecaller_pages()) {
            ajax_access_denied();
        }

        $data['leads'] = $this->follow_ups_model->get_my_follow_up_leads_for_widget(5);
        $this->load->view('my_follow_ups_widget', $data);
    }

    /**
     * "Today's Calling Summary" - the native Dashboard's Todo-widget
     * replacement for a plain Telecaller (see
     * follow_up_management_replace_todo_widget_script() in
     * follow_up_management.php for the client-side hide/inject). Exact
     * same shape as my_follow_ups_widget() above - a small AJAX-fetched
     * HTML fragment, same permission gate, same reused-model-data
     * pattern (Follow_ups_model::get_todays_calling_summary(), which
     * itself reuses two already-existing private count methods, not new
     * tracking).
     */
    public function calling_summary_widget()
    {
        if (staff_cant('view', 'leads') || !follow_up_management_can_access_telecaller_pages()) {
            ajax_access_denied();
        }

        $data['summary'] = $this->follow_ups_model->get_todays_calling_summary();
        $data['details'] = $this->follow_ups_model->get_todays_calling_summary_details();
        $this->load->view('calling_summary_widget', $data);
    }

    /**
     * Today's Follow-ups - Overdue / Due Today / Completed Today, grouped
     * and counted by Follow_ups_model::get_todays_followups_grouped()
     * (same visibility scope as My Cases).
     */
    public function todays_followups()
    {
        if (staff_cant('view', 'leads')) {
            access_denied('Follow-up Management');
        }

        $data['groups']    = $this->follow_ups_model->get_todays_followups_grouped();
        $data['title']     = _l('todays_followups');
        $data['bodyclass'] = 'follow-up-todays';
        $this->load->view('todays_followups', $data);
    }

    /**
     * The Telecaller Dashboard - every number and chart on this page comes
     * from Follow_ups_model methods that already apply
     * get_followup_case_visibility_where() (Phase 3), so Admin/Manager/
     * Telecaller scoping is automatic here, not re-implemented. Chart data
     * is json_encode()'d the same way Reports::followups() already encodes
     * chart data for its view.
     */
    public function dashboard()
    {
        if (staff_cant('view', 'leads')) {
            access_denied('Follow-up Management');
        }

        // Direct-URL lockout: staff_cant('view', 'leads') alone doesn't
        // scope this to Follow-up Management specifically - it's a broad
        // native capability many staff roles hold by default, regardless
        // of department. Without this, any non-Telecalling staff member
        // (Web Development, App Development, etc.) who knew/guessed this
        // URL could still land on the simplified Telecaller dashboard or
        // the full KPI dashboard below, even with the menu link now
        // correctly hidden for them.
        if (!follow_up_management_can_access_telecaller_pages()) {
            access_denied('Follow-up Management');
        }

        // Plain Telecaller (no view_department capability, not admin) gets
        // the simplified 5-item dashboard - "My Assigned Leads/Today's
        // Follow-ups/Overdue Follow-ups/Customer Confirmed/Lost Leads" -
        // instead of the full KPI/6-chart dashboard, same three-tier guard
        // used throughout this module (e.g. the Dashboard tab-swap script).
        // Admin/Manager keep the rich dashboard below unchanged.
        if (!is_admin() && !staff_can('view_department', 'follow_up_management')) {
            $data['case_counts']  = $this->follow_ups_model->get_case_counts();
            $data['lead_counts']  = $this->follow_ups_model->get_lead_status_counts();

            // Step 4 - additional KPI cards (Today's Calls/Ready for
            // Conversion/Converted Customers) + Recent Calls panel.
            $data['todays_calls_count']         = $this->follow_ups_model->get_todays_calls_count();
            $data['ready_for_conversion_count'] = $this->follow_ups_model->get_ready_for_conversion_count();
            $data['converted_customers_count']  = $this->follow_ups_model->get_converted_customers_count();
            $data['recent_calls']               = $this->follow_ups_model->get_recent_calls(5);

            $data['title']        = _l('dashboard');
            $data['bodyclass']    = 'follow-up-dashboard-telecaller';
            $this->load->view('dashboard_telecaller', $data);

            return;
        }

        $data['kpis']                = $this->follow_ups_model->get_dashboard_kpis();
        $data['chart_by_status']     = json_encode($this->follow_ups_model->get_chart_cases_by_status());
        $data['chart_by_priority']   = json_encode($this->follow_ups_model->get_chart_cases_by_priority());
        $data['chart_created_closed'] = json_encode($this->follow_ups_model->get_chart_cases_created_vs_closed());
        $data['chart_daily_activity'] = json_encode($this->follow_ups_model->get_chart_daily_activity());
        $data['chart_department']    = json_encode($this->follow_ups_model->get_chart_department_performance());
        $data['chart_monthly_trend'] = json_encode($this->follow_ups_model->get_chart_monthly_closure_trend());
        $data['upcoming']            = $this->follow_ups_model->get_upcoming_followups();
        $data['recent_activities']   = $this->follow_ups_model->get_recent_activities();
        $data['performance']         = $this->follow_ups_model->get_todays_performance();
        $data['recent_notifications'] = $this->follow_ups_model->get_case_notifications(5);
        $data['title']               = _l('dashboard');
        $data['bodyclass']           = 'follow-up-dashboard';
        $this->load->view('dashboard', $data);
    }

    /**
     * Notification Center - reads the core tblnotifications table (see
     * Follow_ups_model::get_case_notifications()), reuses the exact same
     * mark-read AJAX endpoints/JS the bell dropdown already uses
     * (misc/set_notification_read_inline/{id}, misc/mark_all_notifications_as_read_inline)
     * rather than building a parallel read-tracking mechanism.
     */
    public function notifications()
    {
        if (staff_cant('view', 'leads')) {
            access_denied('Follow-up Management');
        }

        $data['notifications'] = $this->follow_ups_model->get_case_notifications();
        $data['title']         = _l('notification_center');
        $data['bodyclass']     = 'follow-up-notifications';
        $this->load->view('notifications', $data);
    }

    /**
     * Performance Dashboard - KPIs/charts/leaderboard all reuse
     * Follow_ups_model's Phase 7 methods, which themselves reuse
     * get_followup_case_visibility_where() (Phase 3) for Admin/Manager/
     * Telecaller scoping - not re-implemented here.
     */
    public function performance()
    {
        if (staff_cant('view', 'leads')) {
            access_denied('Follow-up Management');
        }

        $date_from = $this->input->get('date_from') ? to_sql_date($this->input->get('date_from')) : null;
        $date_to   = $this->input->get('date_to') ? to_sql_date($this->input->get('date_to')) : null;
        $department = $this->input->get('department_id') ?: null;
        $priority   = $this->input->get('priority') ?: null;
        $status     = $this->input->get('case_status') ?: null;

        $this->load->model('staff_model');
        $data['members']     = $this->staff_model->get('', ['active' => 1]);
        $data['departments'] = get_business_departments();
        $data['filters']     = [
            'date_from'     => $this->input->get('date_from'),
            'date_to'       => $this->input->get('date_to'),
            'department_id' => $department,
            'priority'      => $priority,
            'case_status'   => $status,
        ];

        $overview             = $this->follow_ups_model->get_performance_overview($date_from, $date_to, $department, $priority, $status);
        $data['summary']      = $overview['summary'];
        $data['leaderboard']  = $overview['leaderboard'];
        $data['chart_daily']         = json_encode($this->follow_ups_model->get_chart_daily_activity());
        $data['chart_weekly']        = json_encode($this->follow_ups_model->get_chart_weekly_productivity());
        $data['chart_monthly']       = json_encode($this->follow_ups_model->get_chart_monthly_productivity());
        $data['chart_calls_meetings'] = json_encode($this->follow_ups_model->get_chart_calls_vs_meetings());
        $data['chart_status_trend']  = json_encode($this->follow_ups_model->get_chart_case_status_trend());
        $data['chart_staff_comparison'] = json_encode($this->follow_ups_model->get_chart_staff_comparison($date_from, $date_to));
        $data['chart_department']    = json_encode($this->follow_ups_model->get_chart_department_performance());
        $data['chart_response_trend'] = json_encode($this->follow_ups_model->get_chart_avg_response_time_trend());

        $data['title']     = _l('performance_dashboard');
        $data['bodyclass'] = 'follow-up-performance';
        $this->load->view('performance', $data);
    }

    /**
     * Staff Detail drill-down - Follow_ups_model::get_staff_detail() itself
     * verifies the viewer is actually allowed to see this staff (reusing
     * the visibility scope, not a separate permission check) and returns
     * null if not.
     *
     * @param mixed $staff_id
     */
    public function performance_staff_detail($staff_id)
    {
        if (staff_cant('view', 'leads')) {
            access_denied('Follow-up Management');
        }

        $detail = $this->follow_ups_model->get_staff_detail($staff_id);

        if (!$detail) {
            access_denied('Follow-up Management');
        }

        $data['detail']    = $detail;
        $data['staff_id']  = $staff_id;
        $data['title']     = _l('staff_performance_detail') . ' - ' . get_staff_full_name($staff_id);
        $data['bodyclass'] = 'follow-up-staff-detail';
        $this->load->view('performance_staff_detail', $data);
    }

    /**
     * Step 4 - Telecaller Lead page. A standalone page (not the existing
     * embedded Task-modal Workspace, which stays untouched for Admin/
     * Manager) built from the exact same shared Lead data
     * modules/field_portal's own Lead Details page uses
     * (lead_workspace_get_meetings()/get_unified_timeline() -
     * application/helpers/lead_workspace_helper.php), rendered read-only
     * here, plus this module's own Call History/Follow-up History (both
     * already-existing tblfollowup_history rows, not new data) and the 4
     * outcome actions. Add Call/Continue Follow-up/Not Interested/
     * Customer Ready all submit to the EXISTING save_workspace() action
     * below (same endpoint the embedded Workspace already uses) - only
     * "Needs Another Visit" needed a new action (return_to_field_executive()),
     * since save_workspace() has no concept of moving Current Handler
     * back to the Field Executive.
     *
     * @param int $lead_id
     */
    public function lead_details($lead_id)
    {
        if (!is_assigned_telecaller_lead($lead_id)) {
            access_denied('Follow-up Management');
        }

        $this->load->model('leads_model');
        $lead = $this->leads_model->get($lead_id);

        if (!$lead) {
            show_404();
        }

        $case = null;
        foreach ($this->follow_ups_model->get_for_entity($lead_id, 'lead') as $c) {
            if ($c->case_status === 'open') {
                $case = $c;
                break;
            }
        }

        if (!$case) {
            show_404();
        }

        $this->load->model('field_portal/field_portal_model');

        $data['lead']  = $lead;
        $data['case']  = $case;
        $data['title'] = $lead->name;

        $data['is_converted']     = (int) $lead->client_id > 0;
        $data['meetings']         = lead_workspace_get_meetings($lead_id);
        $data['timeline']         = lead_workspace_get_unified_timeline($lead_id);
        $data['overview']         = lead_workspace_get_overview_stats($lead_id);
        $data['customer_data']    = $data['is_converted'] ? lead_workspace_get_customer_data($lead_id) : null;
        $data['task_statuses']    = [];
        $data['followup_history'] = lead_workspace_get_followup_history($lead_id);
        $data['outcomes']         = get_followup_outcomes();
        $data['leads_statuses']   = $this->leads_model->get_status();

        $data['customer_confirmed_status_id'] = $this->field_portal_model->get_status_id_by_name('Customer Confirmed');
        $data['lost_status_id']               = $this->field_portal_model->get_status_id_by_name('Lost');

        $this->load->view('telecaller_lead_details', $data);
    }

    /**
     * Needs Another Visit outcome - the one action this Lead page needs
     * that save_workspace() has no equivalent for. Thin wrapper around
     * Follow_ups_model::return_lead_to_field_executive() (Current Handler
     * back to the Original Field Executive, Case+Task closed via the
     * existing complete_case_for_lead(), Lead status -> "Visited").
     *
     * @param int $lead_id
     */
    public function return_to_field_executive($lead_id)
    {
        if (!$this->input->is_ajax_request()) {
            return;
        }

        if (!is_assigned_telecaller_lead($lead_id)) {
            ajax_access_denied();
        }

        $success = $this->follow_ups_model->return_lead_to_field_executive($lead_id);

        echo json_encode([
            'success' => (bool) $success,
            'message' => $success ? '' : _l('field_portal_lead_create_failed'),
        ]);
    }

    /**
     * Add Call attachment(s) - reuses handle_task_attachments_array() +
     * Misc_model::add_attachment_to_database() directly, the exact same
     * pair modules/field_portal's own meeting_add_attachment() and
     * modules/dm_portal's task_upload_file() already use, just targeting
     * the Case's own persistent linked Task (tblfollowups.task_id) rather
     * than a per-meeting one - a Case has one Task for its whole
     * lifecycle, not one per call.
     *
     * @param int $lead_id
     */
    public function lead_add_call_attachment($lead_id)
    {
        if (!is_assigned_telecaller_lead($lead_id)) {
            ajax_access_denied();
        }

        $case = null;
        foreach ($this->follow_ups_model->get_for_entity($lead_id, 'lead') as $c) {
            if ($c->case_status === 'open') {
                $case = $c;
                break;
            }
        }

        if (!$case || !$case->task_id) {
            echo json_encode([
                'alert_type' => 'warning',
                'message'    => _l('case_action_failed'),
            ]);

            return;
        }

        $this->load->helper('upload');
        $this->load->model('misc_model');

        $files = handle_task_attachments_array($case->task_id, 'file');

        if ($files && is_array($files)) {
            foreach ($files as $file) {
                $this->misc_model->add_attachment_to_database($case->task_id, 'task', [$file]);
            }
        }

        echo json_encode([
            'alert_type' => 'success',
            'message'    => _l('case_action_success'),
        ]);
    }

    /**
     * My Leads - Telecaller's assigned leads list, reusing the Lead
     * Workspace data model. Shows only leads where assigned = this staff.
     */
    public function my_leads()
    {
        if (!follow_up_management_can_access_telecaller_pages()) {
            access_denied('Follow-up Management');
        }

        $this->load->model('leads_model');

        $data['title']    = _l('my_leads');
        $data['statuses'] = $this->leads_model->get_status();

        $this->load->view('my_leads', $data);
    }

    public function my_leads_table()
    {
        if (!follow_up_management_can_access_telecaller_pages()) {
            ajax_access_denied();
        }

        if (!$this->input->is_ajax_request()) {
            return;
        }

        $staff_id = get_staff_user_id();
        $prefix   = db_prefix();
        $this->load->model('field_portal/field_portal_model');

        $priority_field    = get_custom_fields('leads', ['name' => 'Priority']);
        $priority_field_id = !empty($priority_field) ? (int) $priority_field[0]['id'] : 0;

        $followup_field    = get_custom_fields('leads', ['name' => 'Next Follow-up Date']);
        $followup_field_id = !empty($followup_field) ? (int) $followup_field[0]['id'] : 0;

        $aColumns = [
            $prefix . 'leads.name',
            $prefix . 'leads.company',
            $prefix . 'leads.phonenumber',
            $prefix . 'leads_status.name as status_name',
            'cfv_priority.value as priority_value',
            '(SELECT MAX(fh.event_datetime) FROM ' . $prefix . 'followup_history fh JOIN ' . $prefix . 'followups f ON f.id = fh.followup_id WHERE f.rel_id = ' . $prefix . 'leads.id AND f.rel_type = \'lead\') as last_call',
            'cfv_followup.value as next_followup_value',
            "CONCAT(fe_staff.firstname, ' ', fe_staff.lastname) as field_executive_name",
            "(SELECT description FROM " . $prefix . "notes WHERE rel_id = " . $prefix . "leads.id AND rel_type = 'lead' ORDER BY dateadded DESC LIMIT 1) as latest_note",
        ];

        $sIndexColumn = 'id';
        $sTable       = $prefix . 'leads';

        $join = [
            'LEFT JOIN ' . $prefix . 'leads_status ON ' . $prefix . 'leads_status.id = ' . $prefix . 'leads.status',
            'LEFT JOIN ' . $prefix . 'customfieldsvalues cfv_priority ON cfv_priority.relid = ' . $prefix . 'leads.id AND cfv_priority.fieldid = ' . $priority_field_id . " AND cfv_priority.fieldto = 'leads'",
            'LEFT JOIN ' . $prefix . 'customfieldsvalues cfv_followup ON cfv_followup.relid = ' . $prefix . 'leads.id AND cfv_followup.fieldid = ' . $followup_field_id . " AND cfv_followup.fieldto = 'leads'",
            'LEFT JOIN ' . $prefix . 'staff fe_staff ON fe_staff.staffid = ' . $prefix . 'leads.addedfrom',
        ];

        $where = [
            'AND ' . $prefix . 'leads.assigned = ' . (int) $staff_id,
        ];

        $status = $this->input->post('status');
        if ($status) {
            $where[] = 'AND ' . $prefix . 'leads.status = ' . (int) $status;
        }

        $date = to_sql_date($this->input->post('date'));
        if ($date && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $where[] = "AND DATE(" . $prefix . "leads.dateadded) = '" . $date . "'";
        }

        $result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, [
            $prefix . 'leads.id',
        ]);

        $output  = $result['output'];
        $rResult = $result['rResult'];

        foreach ($rResult as $aRow) {
            $lead_id = (int) $aRow['id'];
            $row     = [];

            $row[] = '<a href="' . admin_url('follow_up_management/lead_details/' . $lead_id) . '" class="tw-font-medium">' . e($aRow[$prefix . 'leads.name']) . '</a>';
            $row[] = $aRow[$prefix . 'leads.company'] ? e($aRow[$prefix . 'leads.company']) : '-';
            $row[] = $aRow[$prefix . 'leads.phonenumber'] ? e($aRow[$prefix . 'leads.phonenumber']) : '-';
            $row[] = $aRow['status_name'] ? e($aRow['status_name']) : '-';
            $row[] = $aRow['priority_value'] ? e($aRow['priority_value']) : '-';
            $row[] = $aRow['last_call'] ? e(_dt($aRow['last_call'])) : '-';
            $row[] = $aRow['next_followup_value'] ? e(_d($aRow['next_followup_value'])) : '-';
            $row[] = $aRow['field_executive_name'] ? e($aRow['field_executive_name']) : '-';
            $latest_note = $aRow['latest_note'] ?? '';
            $row[] = $latest_note ? e(mb_substr($latest_note, 0, 100)) . (mb_strlen($latest_note) > 100 ? '...' : '') : '-';
            $row[] = '<a href="' . admin_url('follow_up_management/lead_details/' . $lead_id) . '" class="btn btn-default btn-sm" title="' . _l('field_portal_view_details') . '"><i class="fa-solid fa-eye"></i></a>';

            $row['DT_RowId'] = 'telecaller_lead_' . $lead_id;
            $output['aaData'][] = $row;
        }

        echo json_encode($output);
    }

    /**
     * Convert to Customer - Telecaller version. Reuses the exact same
     * Leads_model::convert_to_customer() path as the Field Executive
     * portal, just redirects back to the Telecaller customers list.
     *
     * @param int $lead_id
     */
    public function convert_customer($lead_id)
    {
        if (!is_assigned_telecaller_lead($lead_id)) {
            ajax_access_denied();
        }

        $this->load->model('leads_model');
        $lead = $this->leads_model->get($lead_id);

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

        $data = [
            'leadid'           => $lead_id,
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

        $this->load->model('misc_model');
        $notes = $this->misc_model->get_notes($lead_id, 'lead');

        $customer_id = $this->leads_model->convert_to_customer($data, $lead->email, $notes, null, null, null, null);

        if (!$customer_id) {
            echo json_encode(['success' => false, 'message' => _l('field_portal_convert_failed')]);
            return;
        }

        echo json_encode([
            'success'  => true,
            'redirect' => admin_url('follow_up_management/customers'),
        ]);
    }

    /**
     * Telecaller Customers page - read-only list of converted customers
     * from leads this Telecaller was assigned to.
     */
    public function customers()
    {
        if (!follow_up_management_can_access_telecaller_pages()) {
            access_denied('Follow-up Management');
        }

        $data['title'] = _l('followup_customers');
        $this->load->view('customers', $data);
    }

    public function customers_table()
    {
        if (!follow_up_management_can_access_telecaller_pages()) {
            ajax_access_denied();
        }

        if (!$this->input->is_ajax_request()) {
            return;
        }

        $staff_id = get_staff_user_id();
        $prefix   = db_prefix();

        $aColumns = [
            $prefix . 'clients.company',
            $prefix . 'clients.phonenumber',
            $prefix . 'leads.name as lead_name',
            $prefix . 'leads.date_converted',
            $prefix . 'leads_status.name as status_name',
        ];

        $sIndexColumn = 'id';
        $sTable       = $prefix . 'leads';

        $join = [
            'LEFT JOIN ' . $prefix . 'clients ON ' . $prefix . 'clients.userid = ' . $prefix . 'leads.client_id',
            'LEFT JOIN ' . $prefix . 'leads_status ON ' . $prefix . 'leads_status.id = ' . $prefix . 'leads.status',
        ];

        $where = [
            'AND ' . $prefix . 'leads.assigned = ' . (int) $staff_id,
            'AND ' . $prefix . 'leads.client_id > 0',
        ];

        $result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, [
            $prefix . 'leads.id',
        ]);

        $output  = $result['output'];
        $rResult = $result['rResult'];

        foreach ($rResult as $aRow) {
            $lead_id = (int) $aRow['id'];
            $row     = [];

            $row[] = e($aRow[$prefix . 'clients.company'] ?: $aRow['lead_name']);
            $row[] = $aRow[$prefix . 'clients.phonenumber'] ? e($aRow[$prefix . 'clients.phonenumber']) : '-';
            $row[] = e($aRow['lead_name']);
            $row[] = $aRow[$prefix . 'leads.date_converted'] ? e(_d($aRow[$prefix . 'leads.date_converted'])) : '-';
            $row[] = e($aRow['status_name'] ?: '-');
            $row[] = '<a href="' . admin_url('follow_up_management/lead_details/' . $lead_id) . '" class="btn btn-default btn-sm" title="' . _l('field_portal_view_details') . '"><i class="fa-solid fa-eye"></i></a>';

            $row['DT_RowId'] = 'telecaller_customer_' . $lead_id;
            $output['aaData'][] = $row;
        }

        echo json_encode($output);
    }

    /**
     * Follow-ups grouped by filter - Tomorrow/Upcoming/Overdue/Completed.
     * Each returns a DataTable-driven page reusing the existing
     * follow-up data model.
     */
    public function followups_filtered($filter)
    {
        if (!follow_up_management_can_access_telecaller_pages()) {
            access_denied('Follow-up Management');
        }

        $allowed = ['today', 'tomorrow', 'upcoming', 'overdue', 'completed'];
        if (!in_array($filter, $allowed, true)) {
            show_404();
        }

        $data['title']     = _l('followup_filter_' . $filter);
        $data['filter']    = $filter;
        $this->load->view('followups_filtered', $data);
    }

    public function followups_filtered_table()
    {
        if (!follow_up_management_can_access_telecaller_pages()) {
            ajax_access_denied();
        }

        if (!$this->input->is_ajax_request()) {
            return;
        }

        $staff_id = get_staff_user_id();
        $prefix   = db_prefix();

        $filter  = $this->input->post('filter');
        $allowed = ['today', 'tomorrow', 'upcoming', 'overdue', 'completed'];
        if (!in_array($filter, $allowed, true)) {
            $filter = 'today';
        }

        $aColumns = [
            $prefix . 'leads.name',
            $prefix . 'leads.company',
            $prefix . 'leads.phonenumber',
            $prefix . 'leads_status.name as status_name',
            $prefix . 'followups.next_follow_up_date',
            "CONCAT(tc_staff.firstname, ' ', tc_staff.lastname) as telecaller_name",
        ];

        $sIndexColumn = 'id';
        $sTable       = $prefix . 'followups';

        $join = [
            'LEFT JOIN ' . $prefix . 'leads ON ' . $prefix . 'leads.id = ' . $prefix . 'followups.rel_id',
            'LEFT JOIN ' . $prefix . 'leads_status ON ' . $prefix . 'leads_status.id = ' . $prefix . 'leads.status',
            'LEFT JOIN ' . $prefix . 'staff tc_staff ON tc_staff.staffid = ' . $prefix . 'followups.assigned_staff_id',
        ];

        $where = [
            'AND ' . $prefix . "followups.rel_type = 'lead'",
            'AND ' . $prefix . 'followups.assigned_staff_id = ' . (int) $staff_id,
        ];

        if ($filter === 'today') {
            $where[] = 'AND DATE(' . $prefix . 'followups.next_follow_up_date) = CURDATE()';
        } elseif ($filter === 'tomorrow') {
            $where[] = 'AND DATE(' . $prefix . 'followups.next_follow_up_date) = DATE_ADD(CURDATE(), INTERVAL 1 DAY)';
        } elseif ($filter === 'upcoming') {
            $where[] = 'AND DATE(' . $prefix . 'followups.next_follow_up_date) > DATE_ADD(CURDATE(), INTERVAL 1 DAY)';
        } elseif ($filter === 'overdue') {
            $where[] = 'AND ' . $prefix . 'followups.next_follow_up_date < CURDATE()';
            $where[] = 'AND ' . $prefix . "followups.case_status = 'open'";
        } elseif ($filter === 'completed') {
            $where[] = 'AND (' . $prefix . "followups.case_status = 'closed' OR " . $prefix . 'leads.client_id > 0)';
        }

        $result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, [
            $prefix . 'followups.id',
            $prefix . 'followups.rel_id',
        ]);

        $output  = $result['output'];
        $rResult = $result['rResult'];

        foreach ($rResult as $aRow) {
            $lead_id = (int) $aRow['rel_id'];
            $row     = [];

            $row[] = '<a href="' . admin_url('follow_up_management/lead_details/' . $lead_id) . '" class="tw-font-medium">' . e($aRow[$prefix . 'leads.name']) . '</a>';
            $row[] = $aRow[$prefix . 'leads.company'] ? e($aRow[$prefix . 'leads.company']) : '-';
            $row[] = $aRow[$prefix . 'leads.phonenumber'] ? e($aRow[$prefix . 'leads.phonenumber']) : '-';
            $row[] = $aRow['status_name'] ? e($aRow['status_name']) : '-';
            $row[] = $aRow[$prefix . 'followups.next_follow_up_date'] ? e(_d($aRow[$prefix . 'followups.next_follow_up_date'])) : '-';
            $row[] = $aRow['telecaller_name'] ? e($aRow['telecaller_name']) : '-';
            $row[] = '<a href="' . admin_url('follow_up_management/lead_details/' . $lead_id) . '" class="btn btn-default btn-sm" title="' . _l('field_portal_view_details') . '"><i class="fa-solid fa-eye"></i></a>';

            $row['DT_RowId'] = 'followup_' . $lead_id;
            $output['aaData'][] = $row;
        }

        echo json_encode($output);
    }

    // ----------------------------------------------------------------------
    // Admin Sales CRM - controller actions (Phase 6)
    // ----------------------------------------------------------------------

    /**
     * Admin Sales Dashboard - aggregate KPIs for the full lead-to-customer
     * lifecycle. Restricted to Admin/Manager.
     */
    public function admin_sales_dashboard()
    {
        if (!is_admin() && !staff_can('view_department', 'follow_up_management')) {
            access_denied('Admin Sales CRM');
        }

        $data['kpis']  = $this->follow_ups_model->get_admin_sales_kpis();
        $data['title'] = _l('followup_admin_sales_dashboard');
        $this->load->view('admin_sales_dashboard', $data);
    }

    /**
     * AJAX endpoint returning fresh KPI data for the Sales Dashboard.
     */
    public function admin_kpi_data()
    {
        if (!is_admin() && !staff_can('view_department', 'follow_up_management')) {
            ajax_access_denied();
        }

        echo json_encode($this->follow_ups_model->get_admin_sales_kpis());
    }

    /**
     * Sales Pipeline - visual stage view with lead counts per status.
     */
    public function sales_pipeline()
    {
        if (!is_admin() && !staff_can('view_department', 'follow_up_management')) {
            access_denied('Admin Sales CRM');
        }

        $data['pipeline'] = $this->follow_ups_model->get_sales_pipeline_data();
        $data['title']    = _l('followup_sales_pipeline');
        $this->load->view('sales_pipeline', $data);
    }

    /**
     * Employee Performance - Field Executive and Telecaller reporting.
     */
    public function employee_performance()
    {
        if (!is_admin() && !staff_can('view_department', 'follow_up_management')) {
            access_denied('Admin Sales CRM');
        }

        $data['fe_performance'] = $this->follow_ups_model->get_employee_performance_data('fe');
        $data['tc_performance'] = $this->follow_ups_model->get_employee_performance_data('tc');
        $data['title']          = _l('followup_employee_performance');
        $this->load->view('employee_performance', $data);
    }

    /**
     * Lead Reports - filtered, exportable lead data.
     */
    public function lead_reports()
    {
        if (!is_admin() && !staff_can('view_department', 'follow_up_management')) {
            access_denied('Admin Sales CRM');
        }

        $this->load->model('leads_model');

        $data['statuses'] = $this->leads_model->get_status();
        $data['sources']  = $this->leads_model->get_source();
        $this->load->model('staff_model');
        $data['staff']    = $this->staff_model->get('', ['active' => 1]);
        $data['title']    = _l('followup_lead_reports');

        // Apply filters and get data
        $filters = [
            'date_from'   => $this->input->get('date_from') ? to_sql_date($this->input->get('date_from')) : '',
            'date_to'     => $this->input->get('date_to') ? to_sql_date($this->input->get('date_to')) : '',
            'staff_id'    => $this->input->get('staff_id'),
            'status_id'   => $this->input->get('status_id'),
            'source_id'   => $this->input->get('source_id'),
            'role'        => $this->input->get('role'),
            'is_lost'     => $this->input->get('is_lost'),
            'is_converted' => $this->input->get('is_converted'),
        ];

        $data['reports'] = $this->follow_ups_model->get_lead_report_data($filters);
        $this->load->view('lead_reports', $data);
    }

    /**
     * Customer Origin data - read-only metadata shown on the Customer
     * profile. Called via AJAX when the "origin" tab is clicked.
     */
    public function customer_origin($client_id)
    {
        if (!is_admin() && !staff_can('view', 'customers')) {
            ajax_access_denied();
        }

        $origin = $this->follow_ups_model->get_customer_origin_data((int) $client_id);

        if (!$origin || !$origin->client_id) {
            echo '<div class="col-md-12"><div class="alert alert-info">' . _l('followup_no_origin_data') . '</div></div>';
            return;
        }

        $data['origin'] = $origin;
        $this->load->view('customer_origin_tab', $data);
    }

    /**
     * Export Lead Reports as CSV.
     */
    public function export_lead_reports()
    {
        if (!is_admin() && !staff_can('view_department', 'follow_up_management')) {
            access_denied('Admin Sales CRM');
        }

        $filters = [
            'date_from'   => $this->input->get('date_from') ? to_sql_date($this->input->get('date_from')) : '',
            'date_to'     => $this->input->get('date_to') ? to_sql_date($this->input->get('date_to')) : '',
            'staff_id'    => $this->input->get('staff_id'),
            'status_id'   => $this->input->get('status_id'),
            'source_id'   => $this->input->get('source_id'),
            'role'        => $this->input->get('role'),
            'is_lost'     => $this->input->get('is_lost'),
            'is_converted' => $this->input->get('is_converted'),
        ];

        $reports = $this->follow_ups_model->get_lead_report_data($filters);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="lead_reports_' . date('Y-m-d') . '.csv"');

        $out = fopen('php://output', 'w');
        fputcsv($out, [
            'ID', 'Lead Name', 'Company', 'Phone', 'Email', 'Date Added',
            'Date Converted', 'Status', 'Source', 'Field Executive',
            'Telecaller', 'Lost', 'Lost Reason', 'Customer ID',
        ]);

        foreach ($reports as $row) {
            fputcsv($out, [
                $row['id'],
                $row['name'],
                $row['company'],
                $row['phonenumber'],
                $row['email'],
                $row['dateadded'],
                $row['date_converted'],
                $row['status_name'],
                $row['source_name'],
                ($row['fe_firstname'] ?? '') . ' ' . ($row['fe_lastname'] ?? ''),
                ($row['tc_firstname'] ?? '') . ' ' . ($row['tc_lastname'] ?? ''),
                $row['is_lost'] ? 'Yes' : 'No',
                $row['lost_reason'],
                $row['client_id'],
            ]);
        }

        fclose($out);
    }
}
