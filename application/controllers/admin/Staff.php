<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @property-read Authentication_model $authentication_model
 * @property-read Staff_model $staff_model
 */
class Staff extends AdminController
{
    /* List all staff members */
    public function index()
    {
        if (staff_cant('view', 'staff')) {
            access_denied('staff');
        }
        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data('staff');
        }
        $this->load->model('business_departments/business_departments_model');
        $data['staff_members']        = $this->staff_model->get('', ['active' => 1]);
        $data['business_departments'] = $this->business_departments_model->get();
        $data['title']                = _l('staff_members');
        $this->load->view('admin/staff/manage', $data);
    }

    /* Add new staff member or edit existing */
    public function member($id = '')
    {
        if (staff_cant('view', 'staff')) {
            access_denied('staff');
        }
        hooks()->do_action('staff_member_edit_view_profile', $id);

        $this->load->model('departments_model');
        if ($this->input->post()) {
            $data = $this->input->post();
            // Don't do XSS clean here.
            $data['email_signature'] = $this->input->post('email_signature', false);
            $data['email_signature'] = html_entity_decode($data['email_signature']);

            if ($data['email_signature'] == strip_tags($data['email_signature'])) {
                // not contains HTML, add break lines
                $data['email_signature'] = nl2br_save_html($data['email_signature']);
            }

            $data['password'] = $this->input->post('password', false);

            if ($id == '') {
                if (staff_cant('create', 'staff')) {
                    access_denied('staff');
                }
                $id = $this->staff_model->add($data);
                if ($id) {
                    handle_staff_profile_image_upload($id);
                    set_alert('success', _l('added_successfully', _l('staff_member')));
                    redirect(admin_url('staff/member/' . $id));
                }
            } else {
                if (staff_cant('edit', 'staff')) {
                    access_denied('staff');
                }
                handle_staff_profile_image_upload($id);
                $response = $this->staff_model->update($data, $id);
                if (is_array($response)) {
                    if (isset($response['cant_remove_main_admin'])) {
                        set_alert('warning', _l('staff_cant_remove_main_admin'));
                    } elseif (isset($response['cant_remove_yourself_from_admin'])) {
                        set_alert('warning', _l('staff_cant_remove_yourself_from_admin'));
                    }
                } elseif ($response == true) {
                    set_alert('success', _l('updated_successfully', _l('staff_member')));
                }
                redirect(admin_url('staff/member/' . $id));
            }
        }
        if ($id == '') {
            $title = _l('add_new', _l('staff_member'));
        } else {
            $member = $this->staff_model->get($id);
            if (!$member) {
                blank_page('Staff Member Not Found', 'danger');
            }
            $data['member']            = $member;
            $title                     = $member->firstname . ' ' . $member->lastname;
            $data['staff_departments'] = $this->departments_model->get_staff_departments($member->staffid);

            $ts_filter_data = [];
            if ($this->input->get('filter')) {
                if ($this->input->get('range') != 'period') {
                    $ts_filter_data[$this->input->get('range')] = true;
                } else {
                    $ts_filter_data['period-from'] = $this->input->get('period-from');
                    $ts_filter_data['period-to']   = $this->input->get('period-to');
                }
            } else {
                $ts_filter_data['this_month'] = true;
            }

            $data['logged_time'] = $this->staff_model->get_logged_time_data($id, $ts_filter_data);
            $data['timesheets']  = $data['logged_time']['timesheets'];
        }
        $this->load->model('currencies_model');
        $data['base_currency'] = $this->currencies_model->get_base_currency();
        $data['roles']         = $this->roles_model->get();
        $data['user_notes']    = $this->misc_model->get_notes($id, 'staff');
        $data['departments']   = $this->departments_model->get();
        $data['title']         = $title;

        $this->load->view('admin/staff/member', $data);
    }

    /* Get role permission for specific role id */
    public function role_changed($id)
    {
        if (staff_cant('view', 'staff')) {
            ajax_access_denied('staff');
        }

        echo json_encode($this->roles_model->get($id)->permissions);
    }

    public function save_dashboard_widgets_order()
    {
        hooks()->do_action('before_save_dashboard_widgets_order');

        $post_data = $this->input->post();
        foreach ($post_data as $container => $widgets) {
            if ($widgets == 'empty') {
                $post_data[$container] = [];
            }
        }
        update_staff_meta(get_staff_user_id(), 'dashboard_widgets_order', serialize($post_data));
    }

    public function save_dashboard_widgets_visibility()
    {
        hooks()->do_action('before_save_dashboard_widgets_visibility');

        $post_data = $this->input->post();
        update_staff_meta(get_staff_user_id(), 'dashboard_widgets_visibility', serialize($post_data['widgets']));
    }

    public function reset_dashboard()
    {
        update_staff_meta(get_staff_user_id(), 'dashboard_widgets_visibility', null);
        update_staff_meta(get_staff_user_id(), 'dashboard_widgets_order', null);

        redirect(admin_url());
    }

    public function save_hidden_table_columns()
    {
        hooks()->do_action('before_save_hidden_table_columns');
        $data   = $this->input->post();
        $id     = $data['id'];
        $hidden = isset($data['hidden']) ? $data['hidden'] : [];
        update_staff_meta(get_staff_user_id(), 'hidden-columns-' . $id, json_encode($hidden));
    }

    public function change_language($lang = '')
    {
        hooks()->do_action('before_staff_change_language', $lang);

        $this->db->where('staffid', get_staff_user_id());
        $this->db->update(db_prefix() . 'staff', ['default_language' => $lang]);
        
        redirect(previous_url() ?: $_SERVER['HTTP_REFERER']);
    }

    public function timesheets()
    {
        $data['view_all'] = false;
        if (staff_can('view-timesheets', 'reports') && $this->input->get('view') == 'all') {
            $data['staff_members_with_timesheets'] = $this->db->query('SELECT DISTINCT staff_id FROM ' . db_prefix() . 'taskstimers WHERE staff_id !=' . get_staff_user_id())->result_array();
            $data['view_all']                      = true;
        }

        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data('staff_timesheets', ['view_all' => $data['view_all']]);
        }

        if ($data['view_all'] == false) {
            unset($data['view_all']);
        }

        $data['logged_time'] = $this->staff_model->get_logged_time_data(get_staff_user_id());
        $data['title']       = '';
        $this->load->view('admin/staff/timesheets', $data);
    }

    public function delete()
    {
        if (!is_admin() && is_admin($this->input->post('id'))) {
            die('Busted, you can\'t delete administrators');
        }

        if (staff_can('delete',  'staff')) {
            $success = $this->staff_model->delete($this->input->post('id'), $this->input->post('transfer_data_to'));
            if ($success) {
                set_alert('success', _l('deleted', _l('staff_member')));
            }
        }

        redirect(admin_url('staff'));
    }

    /* When staff edit his profile */
    public function edit_profile()
    {
        hooks()->do_action('edit_logged_in_staff_profile');

        if ($this->input->post()) {
            handle_staff_profile_image_upload();
            $data = $this->input->post();
            // Don't do XSS clean here.
            $data['email_signature'] = $this->input->post('email_signature', false);
            $data['email_signature'] = html_entity_decode($data['email_signature']);

            if ($data['email_signature'] == strip_tags($data['email_signature'])) {
                // not contains HTML, add break lines
                $data['email_signature'] = nl2br_save_html($data['email_signature']);
            }

            $success = $this->staff_model->update_profile($data, get_staff_user_id());

            if ($success) {
                set_alert('success', _l('staff_profile_updated'));
            }

            redirect(admin_url('staff/edit_profile/' . get_staff_user_id()));
        }
        $member = $this->staff_model->get(get_staff_user_id());
        $this->load->model('departments_model');
        $data['member']            = $member;
        $data['departments']       = $this->departments_model->get();
        $data['staff_departments'] = $this->departments_model->get_staff_departments($member->staffid);
        $data['title']             = $member->firstname . ' ' . $member->lastname;
        $this->load->view('admin/staff/profile', $data);
    }

    /* Remove staff profile image / ajax */
    public function remove_staff_profile_image($id = '')
    {
        $staff_id = get_staff_user_id();
        if (is_numeric($id) && (staff_can('create',  'staff') || staff_can('edit',  'staff'))) {
            $staff_id = $id;
        }
        hooks()->do_action('before_remove_staff_profile_image');
        $member = $this->staff_model->get($staff_id);
        if (file_exists(get_upload_path_by_type('staff') . $staff_id)) {
            delete_dir(get_upload_path_by_type('staff') . $staff_id);
        }
        $this->db->where('staffid', $staff_id);
        $this->db->update(db_prefix() . 'staff', [
            'profile_image' => null,
        ]);

        if (!is_numeric($id)) {
            redirect(admin_url('staff/edit_profile/' . $staff_id));
        } else {
            redirect(admin_url('staff/member/' . $staff_id));
        }
    }

    /* When staff change his password */
    public function change_password_profile()
    {
        if ($this->input->post()) {
            $response = $this->staff_model->change_password($this->input->post(null, false), get_staff_user_id());
            if (is_array($response) && isset($response[0]['passwordnotmatch'])) {
                set_alert('danger', _l('staff_old_password_incorrect'));
            } else {
                if ($response == true) {
                    set_alert('success', _l('staff_password_changed'));
                } else {
                    set_alert('warning', _l('staff_problem_changing_password'));
                }
            }
            redirect(admin_url('staff/edit_profile'));
        }
    }

    /* View public profile. If id passed view profile by staff id else current user*/
    public function profile($id = '')
    {
        if ($id == '') {
            $id = get_staff_user_id();
        } elseif ($id != get_staff_user_id() && staff_cant('view', 'staff')) {
            // Own profile (no id, or id matches the logged in staff member)
            // always allowed, same as before this fix - only viewing
            // SOMEONE ELSE's profile now requires the 'view staff'
            // permission, same gate profile_view() below already
            // enforces for the exact same reason.
            access_denied('staff');
        }

        hooks()->do_action('staff_profile_access', $id);

        $data['logged_time'] = $this->staff_model->get_logged_time_data($id);
        $data['staff_p']     = $this->staff_model->get($id);

        if (!$data['staff_p']) {
            blank_page('Staff Member Not Found', 'danger');
        }

        $this->load->model('departments_model');
        $data['staff_departments'] = $this->departments_model->get_staff_departments($data['staff_p']->staffid);
        $data['departments']       = $this->departments_model->get();
        $data['title']             = _l('staff_profile_string') . ' - ' . $data['staff_p']->firstname . ' ' . $data['staff_p']->lastname;
        // notifications
        $total_notifications = total_rows(db_prefix() . 'notifications', [
            'touserid' => get_staff_user_id(),
        ]);
        $data['total_pages'] = ceil($total_notifications / $this->misc_model->get_notifications_limit());
        $this->load->view('admin/staff/myprofile', $data);
    }

    /**
     * Enhanced read-only employee profile view (Admin → Staff → View Profile)
     * Reuses existing tables and models. No new tables, no auth/permission changes.
     */
    public function profile_view($id = '')
    {
        if (staff_cant('view', 'staff')) {
            access_denied('staff');
        }

        if (!is_numeric($id)) {
            redirect(admin_url('staff'));
        }

        $member = $this->staff_model->get($id);
        if (!$member) {
            blank_page('Staff Member Not Found', 'danger');
        }

        // -- Business Department info (source of truth: Business Departments module) --
        // Fetches the staff member's assigned Business Department(s) dynamically.
        // If department names are updated in the Business Departments module, they
        // will automatically appear correctly here without any code changes.
        $this->db->select(db_prefix() . 'business_departments.id, ' . db_prefix() . 'business_departments.name');
        $this->db->from(db_prefix() . 'business_departments');
        $this->db->join(db_prefix() . 'business_department_staff',
            db_prefix() . 'business_department_staff.department_id = ' . db_prefix() . 'business_departments.id');
        $this->db->where(db_prefix() . 'business_department_staff.staff_id', $id);
        $data['staff_departments'] = $this->db->get()->result_array();

        // -- Role name --
        $role_name = '';
        if ($member->role) {
            $role = $this->roles_model->get($member->role);
            if ($role) {
                $role_name = $role->name;
            }
        }
        $data['role_name'] = $role_name;

        // ================================================================
        // Lead stats — reuse tblleads directly (assigned = staff id)
        // ================================================================
        $leads_total = total_rows(db_prefix() . 'leads', ['assigned' => $id]);

        // Count by status name (status is a foreign key to tblleads_status)
        // We fetch all statuses then count leads per status
        $lead_statuses_raw = $this->db->get(db_prefix() . 'leads_status')->result_array();
        $lead_status_map   = [];
        foreach ($lead_statuses_raw as $ls) {
            $lead_status_map[$ls['id']] = strtolower($ls['name']);
        }

        // Count per status for this staff member
        $this->db->select('status, COUNT(*) as cnt');
        $this->db->where('assigned', $id);
        $this->db->group_by('status');
        $leads_by_status_raw = $this->db->get(db_prefix() . 'leads')->result_array();

        $leads_new               = 0;
        $leads_followup          = 0;
        $leads_interested        = 0;
        $leads_customer_confirmed = 0;
        $leads_lost              = 0;
        $leads_converted         = 0; // converted = client added

        foreach ($leads_by_status_raw as $row) {
            $sname = isset($lead_status_map[$row['status']]) ? $lead_status_map[$row['status']] : '';
            $cnt   = (int) $row['cnt'];

            if (strpos($sname, 'new') !== false) {
                $leads_new += $cnt;
            } elseif (strpos($sname, 'follow') !== false) {
                $leads_followup += $cnt;
            } elseif (strpos($sname, 'interested') !== false) {
                $leads_interested += $cnt;
            } elseif (strpos($sname, 'confirm') !== false || strpos($sname, 'customer') !== false) {
                $leads_customer_confirmed += $cnt;
            } elseif (strpos($sname, 'lost') !== false) {
                $leads_lost += $cnt;
            }
        }
        // "Converted" leads = those that have junk=0, lost=0 and client is added (client_id != 0)
        $leads_converted = total_rows(db_prefix() . 'leads', ['assigned' => $id, 'junk' => 0, 'lost' => 0, 'client_id !=' => 0]);

        $data['leads'] = [
            'total'             => $leads_total,
            'new'               => $leads_new,
            'followup'          => $leads_followup,
            'interested'        => $leads_interested,
            'customer_confirmed'=> $leads_customer_confirmed,
            'lost'              => $leads_lost,
            'converted'         => $leads_converted,
        ];

        // ================================================================
        // Task stats — reuse tbltasks + tbltask_assigned
        // ================================================================
        $tasks_total = (int) $this->db->query(
            'SELECT COUNT(DISTINCT ' . db_prefix() . 'tasks.id) as cnt FROM ' . db_prefix() . 'tasks
             JOIN ' . db_prefix() . 'task_assigned ON ' . db_prefix() . 'task_assigned.taskid = ' . db_prefix() . 'tasks.id
             WHERE ' . db_prefix() . 'task_assigned.staffid = ' . $this->db->escape_str($id)
        )->row()->cnt;

        $tasks_completed = (int) $this->db->query(
            'SELECT COUNT(DISTINCT ' . db_prefix() . 'tasks.id) as cnt FROM ' . db_prefix() . 'tasks
             JOIN ' . db_prefix() . 'task_assigned ON ' . db_prefix() . 'task_assigned.taskid = ' . db_prefix() . 'tasks.id
             WHERE ' . db_prefix() . 'task_assigned.staffid = ' . $this->db->escape_str($id) . '
             AND ' . db_prefix() . 'tasks.status = 5'
        )->row()->cnt;

        $tasks_pending = $tasks_total - $tasks_completed;

        $data['tasks'] = [
            'total'     => $tasks_total,
            'completed' => $tasks_completed,
            'pending'   => $tasks_pending,
        ];

        // ================================================================
        // Project stats — reuse tblprojects + tblproject_members
        // ================================================================
        $projects_total = (int) $this->db->query(
            'SELECT COUNT(DISTINCT ' . db_prefix() . 'projects.id) as cnt FROM ' . db_prefix() . 'projects
             JOIN ' . db_prefix() . 'project_members ON ' . db_prefix() . 'project_members.project_id = ' . db_prefix() . 'projects.id
             WHERE ' . db_prefix() . 'project_members.staff_id = ' . $this->db->escape_str($id)
        )->row()->cnt;

        $projects_active = (int) $this->db->query(
            'SELECT COUNT(DISTINCT ' . db_prefix() . 'projects.id) as cnt FROM ' . db_prefix() . 'projects
             JOIN ' . db_prefix() . 'project_members ON ' . db_prefix() . 'project_members.project_id = ' . db_prefix() . 'projects.id
             WHERE ' . db_prefix() . 'project_members.staff_id = ' . $this->db->escape_str($id) . '
             AND ' . db_prefix() . 'projects.status IN (1,2,3)'
        )->row()->cnt;

        $projects_completed = (int) $this->db->query(
            'SELECT COUNT(DISTINCT ' . db_prefix() . 'projects.id) as cnt FROM ' . db_prefix() . 'projects
             JOIN ' . db_prefix() . 'project_members ON ' . db_prefix() . 'project_members.project_id = ' . db_prefix() . 'projects.id
             WHERE ' . db_prefix() . 'project_members.staff_id = ' . $this->db->escape_str($id) . '
             AND ' . db_prefix() . 'projects.status = 4'
        )->row()->cnt;

        $data['projects'] = [
            'total'     => $projects_total,
            'active'    => $projects_active,
            'completed' => $projects_completed,
        ];

        // ================================================================
        // Follow-up summary — reuse tblfollowups (Follow-up Management module)
        // ================================================================
        $today_start = date('Y-m-d') . ' 00:00:00';
        $today_end   = date('Y-m-d') . ' 23:59:59';
        $now         = date('Y-m-d H:i:s');

        $followup_table_exists = $this->db->table_exists(db_prefix() . 'followups');
        if ($followup_table_exists) {
            $fu_today = (int) $this->db->query(
                'SELECT COUNT(*) as cnt FROM ' . db_prefix() . 'followups
                 WHERE assigned_staff_id = ' . $this->db->escape_str($id) . '
                 AND next_follow_up_date BETWEEN "' . $today_start . '" AND "' . $today_end . '"'
            )->row()->cnt;

            $fu_upcoming = (int) $this->db->query(
                'SELECT COUNT(*) as cnt FROM ' . db_prefix() . 'followups
                 WHERE assigned_staff_id = ' . $this->db->escape_str($id) . '
                 AND next_follow_up_date > "' . $today_end . '"
                 AND case_status = "open"'
            )->row()->cnt;

            $fu_overdue = (int) $this->db->query(
                'SELECT COUNT(*) as cnt FROM ' . db_prefix() . 'followups
                 WHERE assigned_staff_id = ' . $this->db->escape_str($id) . '
                 AND next_follow_up_date < "' . $today_start . '"
                 AND case_status = "open"'
            )->row()->cnt;

            $fu_last_row = $this->db->query(
                'SELECT next_follow_up_date FROM ' . db_prefix() . 'followups
                 WHERE assigned_staff_id = ' . $this->db->escape_str($id) . '
                 AND next_follow_up_date IS NOT NULL
                 ORDER BY next_follow_up_date DESC LIMIT 1'
            )->row();
            $fu_last_date = $fu_last_row ? $fu_last_row->next_follow_up_date : null;
        } else {
            $fu_today     = 0;
            $fu_upcoming  = 0;
            $fu_overdue   = 0;
            $fu_last_date = null;
        }

        $data['followups'] = [
            'today'     => $fu_today,
            'upcoming'  => $fu_upcoming,
            'overdue'   => $fu_overdue,
            'last_date' => $fu_last_date,
        ];

        // ================================================================
        // Work performance — derived from above
        // ================================================================
        $conversion_rate = $leads_total > 0 ? round(($leads_converted / $leads_total) * 100, 1) : 0;

        $data['performance'] = [
            'total_leads'     => $leads_total,
            'leads_converted' => $leads_converted,
            'conversion_rate' => $conversion_rate,
            'tasks_completed' => $tasks_completed,
            'active_projects' => $projects_active,
            'pending_tasks'   => $tasks_pending,
        ];

        // ================================================================
        // Recent activity — reuse tblactivity_log (last 10 for this staff)
        // ================================================================
        $this->db->where('staffid', $id);
        $this->db->order_by('date', 'desc');
        $this->db->limit(10);
        $data['recent_activity'] = $this->db->get(db_prefix() . 'activity_log')->result_array();

        $data['member'] = $member;
        $data['title']  = $member->firstname . ' ' . $member->lastname . ' — ' . _l('staff_profile_string');

        $this->load->view('admin/staff/profile_view', $data);
    }

    /* Change status to staff active or inactive / ajax */
    public function change_staff_status($id, $status)
    {
        if (staff_can('edit',  'staff')) {
            if ($this->input->is_ajax_request()) {
                $this->staff_model->change_staff_status($id, $status);
            }
        }
    }

    /* Logged in staff notifications*/
    public function notifications()
    {
        $this->load->model('misc_model');
        if ($this->input->post()) {
            $page   = $this->input->post('page');
            $offset = ($page * $this->misc_model->get_notifications_limit());
            $this->db->limit($this->misc_model->get_notifications_limit(), $offset);
            $this->db->where('touserid', get_staff_user_id());
            $this->db->order_by('date', 'desc');
            $notifications = $this->db->get(db_prefix() . 'notifications')->result_array();
            $i             = 0;
            foreach ($notifications as $notification) {
                if (($notification['fromcompany'] == null && $notification['fromuserid'] != 0) || ($notification['fromcompany'] == null && $notification['fromclientid'] != 0)) {
                    if ($notification['fromuserid'] != 0) {
                        $notifications[$i]['profile_image'] = '<a href="' . admin_url('staff/profile/' . $notification['fromuserid']) . '">' . staff_profile_image($notification['fromuserid'], [
                            'staff-profile-image-small',
                            'img-circle',
                            'pull-left',
                        ]) . '</a>';
                    } else {
                        $notifications[$i]['profile_image'] = '<a href="' . admin_url('clients/client/' . $notification['fromclientid']) . '">
                    <img class="client-profile-image-small img-circle pull-left" src="' . contact_profile_image_url($notification['fromclientid']) . '"></a>';
                    }
                } else {
                    $notifications[$i]['profile_image'] = '';
                    $notifications[$i]['full_name']     = '';
                }
                $additional_data = '';
                if (!empty($notification['additional_data'])) {
                    $additional_data = unserialize($notification['additional_data']);
                    $x               = 0;
                    foreach ($additional_data as $data) {
                        if (strpos($data, '<lang>') !== false) {
                            $lang = get_string_between($data, '<lang>', '</lang>');
                            $temp = _l($lang);
                            if (strpos($temp, 'project_status_') !== false) {
                                $status = get_project_status_by_id(strafter($temp, 'project_status_'));
                                $temp   = $status['name'];
                            }
                            $additional_data[$x] = $temp;
                        }
                        $x++;
                    }
                }
                $notifications[$i]['description'] = _l($notification['description'], $additional_data);
                $notifications[$i]['date']        = time_ago($notification['date']);
                $notifications[$i]['full_date']   = _dt($notification['date']);
                $i++;
            } //$notifications as $notification
            echo json_encode($notifications);
            die;
        }
    }

    public function update_two_factor()
    {
        $fail_reason = _l('set_two_factor_authentication_failed');
        if ($this->input->post()) {
            $this->load->library('form_validation');
            $this->form_validation->set_rules('two_factor_auth', _l('two_factor_auth'), 'required');

            if ($this->input->post('two_factor_auth') == 'google') {
                $this->form_validation->set_rules('google_auth_code', _l('google_authentication_code'), 'required');
            }

            if ($this->form_validation->run() !== false) {
                $two_factor_auth_mode = $this->input->post('two_factor_auth');
                $id = get_staff_user_id();
                if ($two_factor_auth_mode == 'google') {
                    $this->load->model('Authentication_model');
                    $secret = $this->input->post('secret');
                    $success = $this->authentication_model->set_google_two_factor($secret);
                    $fail_reason = _l('set_google_two_factor_authentication_failed');
                } elseif ($two_factor_auth_mode == 'email') {
                    $this->db->where('staffid', $id);
                    $success = $this->db->update(db_prefix() . 'staff', ['two_factor_auth_enabled' => 1]);
                } else {
                    $this->db->where('staffid', $id);
                    $success = $this->db->update(db_prefix() . 'staff', ['two_factor_auth_enabled' => 0]);
                }
                if ($success) {
                    set_alert('success', _l('set_two_factor_authentication_successful'));
                    redirect(admin_url('staff/edit_profile/' . get_staff_user_id()));
                }
            }
        }
        set_alert('danger', $fail_reason);
        redirect(admin_url('staff/edit_profile/' . get_staff_user_id()));
    }

    public function verify_google_two_factor()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
            die;
        }

        if ($this->input->post()) {
            $data = $this->input->post();
            $this->load->model('authentication_model');
            $is_success = $this->authentication_model->is_google_two_factor_code_valid($data['code'],$data['secret']);
            $result = [];

            header('Content-Type: application/json');
            if ($is_success) {
                $result['status'] = 'success';
                $result['message'] = _l('google_2fa_code_valid');;

                echo json_encode($result);
                die;
            }

            $result['status'] = 'failed';
            $result['message'] = _l('google_2fa_code_invalid');;

            echo json_encode($result);
            die;
        }
    }

    public function save_completed_checklist_visibility()
    {
        hooks()->do_action('before_save_completed_checklist_visibility');

        $post_data = $this->input->post();
        if (is_numeric($post_data['task_id'])) {
            update_staff_meta(get_staff_user_id(), 'task-hide-completed-items-'. $post_data['task_id'], $post_data['hideCompleted']);
        }
    }
}
