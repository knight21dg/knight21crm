<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Follow_ups_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * The sole entry point Leads triggers (via the lead_status_changed hook
     * in follow_up_management.php) when a lead's status becomes "Follow-up".
     * Creates a Case with sensible defaults - no interaction has actually
     * happened yet, so follow_up_type/outcome/notes are placeholders the
     * assigned staff member fills in from the Case Detail page.
     *
     * Guards against duplicates: if this lead already has an open
     * (status='pending') case, returns that case's id instead of creating
     * a second one.
     *
     * @param mixed $lead_id
     * @return int|false
     */
    public function create_case_for_lead($lead_id)
    {
        $this->db->where('rel_id', $lead_id);
        $this->db->where('rel_type', 'lead');
        $this->db->where('case_status', 'open');
        $existing = $this->db->get(db_prefix() . 'followups')->row();

        if ($existing) {
            return $existing->id;
        }

        $this->db->where('id', $lead_id);
        $lead = $this->db->get(db_prefix() . 'leads')->row();

        if (!$lead) {
            return false;
        }

        $now       = date('Y-m-d H:i:s');
        $staff_id  = $lead->assigned ? $lead->assigned : get_staff_user_id();
        $auto_note = nl2br(_l('followup_case_auto_created_note'));

        // Issue 2 fix: copy the Lead's own "Next Follow-up Date" value into
        // the Case instead of leaving it null and asking staff to enter it
        // again. That field already exists as a Lead custom field (see
        // get_lead_next_follow_up_date()'s docblock) - nothing new is
        // created here, this only reads what's already on the Lead.
        $next_follow_up_date = $this->get_lead_next_follow_up_date($lead_id);

        $data = [
            'rel_id'              => $lead_id,
            'rel_type'            => 'lead',
            'department_id'       => $lead->department,
            'staff_id'            => $staff_id,
            'assigned_staff_id'   => $staff_id,
            'follow_up_type'      => 'other',
            'outcome'             => null,
            'notes'               => $auto_note,
            'follow_up_date'      => $now,
            'next_follow_up_date' => $next_follow_up_date,
            'status'              => 'pending',
            'case_status'         => 'open',
            'priority'            => 'medium',
            'opened_at'           => $now,
            'last_activity_at'    => $now,
            'created_by'          => get_staff_user_id(),
            'date_created'        => $now,
        ];

        $this->db->insert(db_prefix() . 'followups', $data);
        $insert_id = $this->db->insert_id();

        if ($insert_id) {
            // A next_follow_up_date now exists at creation time (it never
            // did before) - sync it into tblreminders the exact same way
            // every other next_follow_up_date write in this model already
            // does (sync_reminder(), unchanged), so the copied date gets
            // the same Calendar/Cron/notification pipeline as a manually
            // scheduled one, rather than silently having a due date with
            // no reminder behind it.
            if ($next_follow_up_date) {
                $reminder_id = $this->sync_reminder($insert_id, $lead_id, 'lead', $next_follow_up_date, $staff_id);

                if ($reminder_id) {
                    $this->db->where('id', $insert_id);
                    $this->db->update(db_prefix() . 'followups', ['reminder_id' => $reminder_id]);
                }
            }

            $this->add_history_entry($insert_id, [
                'activity_type'  => 'case_created',
                'follow_up_type' => 'other',
                'outcome'        => null,
                'notes'          => $auto_note,
                'event_datetime' => $now,
                'created_by'     => $data['created_by'],
            ]);

            $this->log_case_activity($insert_id, 'case_created', 'Case automatically created for Lead ID: ' . $lead_id, null, null, $data['created_by']);

            $this->notify_case_event(
                $insert_id,
                $staff_id,
                'not_followup_case_assigned',
                [$lead->name],
                true,
                _l('followup_email_case_assigned_title'),
                _l('followup_email_case_assigned_body', [$lead->name])
            );

            // Lead -> Case -> Task synchronization. A standard Perfex Task
            // (tbltasks, via the existing generic Tasks_model::add() - the
            // same API every other module uses, not a parallel task
            // system) is created alongside the Case so the assigned staff
            // member's work actually shows up where Perfex already shows
            // staff their work: Tasks list, "My Tasks", and the staff
            // dashboard's task widgets - none of which this module
            // reimplements. task_id links back to it so later syncs
            // (sync_case_from_lead()) know which Task to update instead of
            // creating another one.
            $task_id = $this->create_or_get_case_task($insert_id, $lead, $staff_id, $data['priority'], $next_follow_up_date);

            if ($task_id) {
                $this->db->where('id', $insert_id);
                $this->db->update(db_prefix() . 'followups', ['task_id' => $task_id]);
            }

            log_activity('Follow-up Case auto-created for Lead ID: ' . $lead_id);
            hooks()->do_action('follow_up_case_created', ['id' => $insert_id, 'lead_id' => $lead_id]);
        }

        return $insert_id;
    }

    /**
     * Rule 6. When a Lead becomes Converted, its open Follow-up Case (if
     * any) and linked Task are marked COMPLETE - never deleted, so the
     * full Timeline/Activity Log history stays intact and visible on the
     * Case Detail page. Reuses change_case_status() (the exact path the
     * Case Detail page's own "Mark Completed" action already uses - sets
     * status='completed' and, since 'completed' is one of the two
     * case_status-closing statuses that method already recognises, also
     * closes the Case) and Tasks_model::mark_as() (the Tasks module's own
     * completion API - sets datefinished, stops open timers, sends the
     * standard task-completed notification). No duplicated status logic.
     *
     * Does nothing if the lead never had an open Case (a Lead can convert
     * without ever passing through Follow-up status) - not an error, just
     * nothing to synchronize.
     *
     * @param mixed $lead_id
     * @return void
     */
    public function complete_case_for_lead($lead_id)
    {
        $this->db->where('rel_id', $lead_id);
        $this->db->where('rel_type', 'lead');
        $this->db->where('case_status', 'open');
        $case = $this->db->get(db_prefix() . 'followups')->row();

        if (!$case) {
            return;
        }

        // change_case_status() itself completes the linked Task and
        // cancels the pending reminder on any closing transition (see its
        // docblock) - this stays a thin wrapper so Lead-converted,
        // Case-Detail's manual Change Status, and the Telecaller
        // Workspace's Complete/Cancel quick actions all go through the
        // exact same completion logic instead of three copies of it.
        $this->change_case_status($case->id, 'completed');

        log_activity('Follow-up Case auto-completed for Lead ID: ' . $lead_id . ' (Lead Converted)');
        hooks()->do_action('follow_up_case_completed', ['id' => $case->id, 'lead_id' => $lead_id]);
    }

    /**
     * Telecaller status change to "Customer Confirmed" -> automatic Lead ->
     * Customer conversion, reusing the exact canonical logic Admin's manual
     * "Convert to Customer" button uses (Leads_model::convert_to_customer(),
     * extracted from Leads::convert_to_customer() specifically so this
     * method has something correct to call - see that method's docblock).
     * This is NOT a second conversion implementation: it only builds the
     * $data array the human-facing modal's own JS would otherwise have
     * pre-filled from the same Lead fields, then hands off to the one
     * shared implementation.
     *
     * Idempotent: no-ops if the lead is already converted. tblleads.client_id
     * is `int(11) NOT NULL DEFAULT 0`, never actually NULL, so this checks
     * > 0 - the same idiom already correctly used by
     * check_and_trigger_automatic_conversion() elsewhere in Leads_model
     * (a `!== null` check here would always be true and this method would
     * never convert anything).
     *
     * @param mixed $lead_id
     * @return int|false New customer id, false if not converted (already
     *                    converted, or lead not found)
     */
    public function auto_convert_confirmed_lead($lead_id)
    {
        $this->load->model('leads_model');
        $lead = $this->leads_model->get($lead_id);

        if (!$lead) {
            return false;
        }

        if ($lead->date_converted || (int) $lead->client_id > 0) {
            return false;
        }

        $default_country = get_option('customer_default_country');
        $country          = $lead->country ?: $default_country;

        $name_parts = explode(' ', trim($lead->name), 2);

        // Every value coerced to a string (never null): app_hash_password()
        // (called on 'password' inside Clients_model::add_contact() via
        // isset() - which is true even for `false`) requires a string
        // argument - a bare `false` here throws an uncaught TypeError on
        // PHP 8.1+. The other fields get the same `?? ''` coercion
        // field_portal's own lead_convert() already established for this
        // exact reason (a null here would otherwise leak into the
        // tblclients/tblcontacts INSERT).
        $data = [
            'leadid'          => $lead_id,
            'firstname'       => $name_parts[0],
            'lastname'        => $name_parts[1] ?? '',
            'email'           => $lead->email ?? '',
            'title'           => $lead->title ?? '',
            'company'         => $lead->company ?? '',
            'phonenumber'     => $lead->phonenumber ?? '',
            'website'         => $lead->website ?? '',
            'address'         => $lead->address ?? '',
            'city'            => $lead->city ?? '',
            'state'           => $lead->state ?? '',
            'zip'             => $lead->zip ?? '',
            'country'         => $country,
            'billing_street'  => $lead->address ?? '',
            'billing_city'    => $lead->city ?? '',
            'billing_state'   => $lead->state ?? '',
            'billing_zip'     => $lead->zip ?? '',
            'billing_country' => $country,
            'is_primary'      => 1,
            'password'        => '',
        ];

        $this->load->model('misc_model');
        $notes = $this->misc_model->get_notes($lead_id, 'lead');

        return $this->leads_model->convert_to_customer($data, $lead->email, $notes ?: null);
    }

    /**
     * Marks a Case's linked Task complete (Tasks_model::mark_as()), if it
     * has one and it isn't already complete. Extracted so every path that
     * closes a Case (Lead converted, Case Detail's manual close/complete,
     * Telecaller Workspace's Complete/Cancel) shares one implementation -
     * see change_case_status() and close_case().
     *
     * @param object $case
     * @return void
     */
    private function complete_linked_task($case)
    {
        if (!$case->task_id) {
            return;
        }

        $this->load->model('tasks_model');
        $task = $this->tasks_model->get($case->task_id);

        if ($task && $task->status != Tasks_model::STATUS_COMPLETE) {
            $this->tasks_model->mark_as(Tasks_model::STATUS_COMPLETE, $case->task_id);
        }
    }

    /**
     * Creates the standard Perfex Task for a Case (see create_case_for_lead()
     * docblock), or returns the existing linked task's id unchanged if one
     * is already there - callers decide whether to sync fields on it
     * separately (sync_case_from_lead()), this method only ever creates.
     *
     * @param mixed       $case_id
     * @param object      $lead
     * @param int         $staff_id             Lead.assigned - never the
     *                                           logged-in user, see
     *                                           create_case_for_lead()
     * @param string      $case_priority        this module's low/medium/high/urgent
     * @param string|null $next_follow_up_date  SQL datetime or null
     * @return int|false
     */
    private function create_or_get_case_task($case_id, $lead, $staff_id, $case_priority, $next_follow_up_date)
    {
        $this->db->where('id', $case_id);
        $existing_task_id = $this->db->get(db_prefix() . 'followups')->row()->task_id ?? null;

        if ($existing_task_id) {
            return $existing_task_id;
        }

        $this->load->model('leads_model');
        $this->load->model('tasks_model');
        $source = $lead->source ? $this->leads_model->get_source($lead->source) : null;

        $today    = date('Y-m-d');
        $due_date = $next_follow_up_date ? substr($next_follow_up_date, 0, 10) : $today;

        $description  = _l('lead_add_edit_name') . ': ' . $lead->name . "\n";
        $description .= _l('lead_company') . ': ' . ($lead->company ?: '-') . "\n";
        $description .= _l('leads_dt_phonenumber') . ': ' . ($lead->phonenumber ?: '-') . "\n";
        $description .= _l('leads_dt_email') . ': ' . ($lead->email ?: '-') . "\n";
        $description .= _l('lead_source') . ': ' . ($source ? $source->name : '-') . "\n\n";
        $description .= _l('followup_next_follow_up_date_label') . ': ' . ($next_follow_up_date ? _dt($next_follow_up_date) : '-') . "\n\n";
        $description .= _l('followup_task_auto_created_note');

        $task_id = $this->tasks_model->add([
            'name'        => _l('followup_task_title', [$lead->name]),
            'description' => $description,
            'priority'    => follow_up_case_priority_to_task_priority($case_priority),
            'startdate'   => $today,
            'duedate'     => $due_date,
            'rel_id'      => $lead->id,
            'rel_type'    => 'lead',
            'assignees'   => [$staff_id],
        ]);

        return $task_id ?: false;
    }

    /**
     * Reassigns the linked Task's assignee to follow a Case's current
     * assigned_staff_id. Uses Tasks_model::remove_assignee() +
     * add_task_assignees() - the same APIs the Tasks module itself uses
     * to reassign a task, not a direct tbltask_assigned write - so
     * add_task_assignees()'s built-in "you've been assigned" notification
     * + email (see Tasks_model::add_task_assignees()) fires automatically
     * and satisfies the Notifications requirement without any custom
     * notification code here.
     *
     * @param int      $task_id
     * @param int      $new_staff_id
     * @param int|null $old_staff_id
     * @return void
     */
    private function sync_task_assignee($task_id, $new_staff_id, $old_staff_id = null)
    {
        $this->load->model('tasks_model');

        if ($old_staff_id && $old_staff_id != $new_staff_id) {
            $this->db->where('taskid', $task_id);
            $this->db->where('staffid', $old_staff_id);
            $assignment = $this->db->get(db_prefix() . 'task_assigned')->row();

            if ($assignment) {
                $this->tasks_model->remove_assignee($assignment->id, $task_id);
            }
        }

        $this->db->where('taskid', $task_id);
        $this->db->where('staffid', $new_staff_id);
        $already_assigned = $this->db->get(db_prefix() . 'task_assigned')->row();

        if (!$already_assigned) {
            $this->tasks_model->add_task_assignees([
                'taskid'   => $task_id,
                'assignee' => $new_staff_id,
            ]);
        }
    }

    /**
     * Rule 4 (Task half). tbltasks.duedate is a DATE column while a
     * Case/Lead next_follow_up_date is a full SQL datetime - only the
     * date portion applies. A direct update is used rather than
     * Tasks_model::update() because that method expects a full task
     * edit-form payload (name, description, checklist, tags, etc.) and
     * would either require re-supplying every existing field or risk
     * clearing ones we don't pass - a targeted single-column update is
     * both simpler and safer here, matching how Tasks_model itself does
     * single-field changes elsewhere (e.g. mark_as()).
     *
     * @param int         $task_id
     * @param string|null $next_follow_up_date SQL datetime or null
     * @return void
     */
    private function sync_task_duedate($task_id, $next_follow_up_date)
    {
        $this->db->where('id', $task_id);
        $this->db->update(db_prefix() . 'tasks', [
            'duedate' => $next_follow_up_date ? substr($next_follow_up_date, 0, 10) : null,
        ]);
    }

    /**
     * Rule 5 (Task half) - see sync_case_from_lead()'s docblock for why
     * Case priority (not Lead priority) is the source here.
     *
     * @param int    $task_id
     * @param string $case_priority low|medium|high|urgent
     * @return void
     */
    private function sync_task_priority($task_id, $case_priority)
    {
        $this->db->where('id', $task_id);
        $this->db->update(db_prefix() . 'tasks', [
            'priority' => follow_up_case_priority_to_task_priority($case_priority),
        ]);
    }

    /**
     * Issue 2 fix - reads the Lead's own "Next Follow-up Date" value, an
     * EXISTING Lead custom field (confirmed present in this install:
     * tblcustomfields, fieldto='leads', type='date_picker' - not created by
     * this module, not a new field). Looked up by name via the core
     * get_custom_fields() helper rather than a hardcoded field id, since
     * custom field ids are install-specific and not a stable constant.
     *
     * The field is date-only (no time component), so a fixed 09:00
     * business-hours time is appended to produce a complete SQL datetime
     * for tblfollowups.next_follow_up_date (a DATETIME column). The
     * to_sql_date() result is validated against a strict Y-m-d pattern
     * before use - to_sql_date() returns its input unmodified if parsing
     * fails, so this guard is required to avoid writing a non-date string
     * into a DATETIME column.
     *
     * Public (was private) so the Telecaller Work Queue's DataTable
     * source (application/views/admin/tables/telecaller_leads.php) can
     * reuse this exact lookup for its Next Follow-up Date column instead
     * of re-deriving the custom-field lookup a second time.
     *
     * @param mixed $lead_id
     * @return string|null SQL datetime, or null if the field doesn't exist
     *                      or has no value for this lead
     */
    public function get_lead_next_follow_up_date($lead_id)
    {
        $field = get_custom_fields('leads', ['name' => 'Next Follow-up Date']);

        if (empty($field)) {
            return null;
        }

        $value = get_custom_field_value($lead_id, $field[0]['id'], 'leads', false);

        if (empty($value)) {
            return null;
        }

        $sql_date = to_sql_date($value, false);

        if (!$sql_date || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $sql_date)) {
            return null;
        }

        return $sql_date . ' 09:00:00';
    }

    /**
     * Dashboard mini-widget for a plain Telecaller - a small, capped
     * version of the same Lead-centric query the Telecaller Work Queue
     * page uses (application/views/admin/tables/my_follow_ups.php).
     * Reuses get_followup_lead_visibility_where() and
     * get_lead_next_follow_up_date() rather than a third copy of
     * either - the only genuinely new part is this capped SELECT
     * itself, since the DataTable source's pagination/search machinery
     * isn't reusable for a plain capped list.
     *
     * @param int $limit
     * @return array
     */
    public function get_my_follow_up_leads_for_widget($limit = 5)
    {
        // Dashboard widget scope: only leads whose Next Follow-up Date is
        // TODAY - "the Dashboard should only show today's work," distinct
        // from the My Follow-ups page itself (application/views/admin/
        // tables/my_follow_ups.php), which is untouched and still shows
        // every date bucket (Overdue/Today/Tomorrow/Future/Not Set).
        // Reuses the exact same custom-field lookup convention already
        // established there (get_custom_fields('leads', ['name' =>
        // 'Next Follow-up Date']) + a LEFT JOIN on customfieldsvalues),
        // not a new field or a second way of resolving the date - just
        // filtered at the SQL level instead of in PHP, so the limit
        // below only ever caps an already-correct result set instead of
        // truncating before the date filter could apply.
        $next_followup_field    = get_custom_fields('leads', ['name' => 'Next Follow-up Date']);
        $next_followup_field_id = !empty($next_followup_field) ? (int) $next_followup_field[0]['id'] : 0;

        if (!$next_followup_field_id) {
            return [];
        }

        $this->db->select(db_prefix() . 'leads.id, ' . db_prefix() . 'leads.name, ' . db_prefix() . 'leads.company, ' . db_prefix() . 'leads.phonenumber, ' . db_prefix() . 'leads_status.name as status_name, ' . db_prefix() . 'followups.task_id, ' . db_prefix() . 'followups.id as case_id', false);
        $this->db->from(db_prefix() . 'leads');
        $this->db->join(db_prefix() . 'leads_status', db_prefix() . 'leads_status.id = ' . db_prefix() . 'leads.status', 'left');
        $this->db->join(db_prefix() . 'followups', db_prefix() . 'followups.rel_id = ' . db_prefix() . 'leads.id AND ' . db_prefix() . 'followups.rel_type="lead" AND ' . db_prefix() . 'followups.case_status="open"', 'left');
        $this->db->join(db_prefix() . 'customfieldsvalues as ctable_next_followup', 'ctable_next_followup.relid = ' . db_prefix() . 'leads.id AND ctable_next_followup.fieldto="leads" AND ctable_next_followup.fieldid=' . $next_followup_field_id, 'left');
        $this->db->where('(' . db_prefix() . 'leads_status.name = "Follow-up" OR ' . db_prefix() . 'followups.id IS NOT NULL)', null, false);
        $this->db->where('DATE(ctable_next_followup.value) = CURDATE()', null, false);

        $visibility = get_followup_lead_visibility_where();
        if ($visibility) {
            // get_followup_lead_visibility_where() returns a raw "AND ..."
            // fragment (matching the data_tables_init() convention its
            // Case-centric sibling was built for) - CI's query builder
            // where() doesn't want the leading "AND ", so it's stripped
            // here rather than changing the shared helper's return shape.
            $this->_apply_visibility();
        }

        $this->db->order_by(db_prefix() . 'followups.last_activity_at', 'desc');
        $this->db->limit($limit);

        $leads = $this->db->get()->result();

        foreach ($leads as $lead) {
            $lead->next_follow_up_date = $this->get_lead_next_follow_up_date($lead->id);
        }

        return $leads;
    }

    /**
     * Lead -> Case -> Task synchronization (Rules 2-4). Lead is the single
     * source of truth: create_case_for_lead() only ever copies Lead data
     * onto a Case once, at the moment the Case is first created. This is
     * called from the after_lead_updated hook instead, which fires on
     * every Lead save, so every later Lead edit (status still "Follow-up")
     * also gets reflected onto the existing open Case and its linked Task.
     *
     * What this does NOT need to sync: Lead name and Source. Neither is
     * duplicated onto tblfollowups - the Case only stores rel_id/rel_type
     * and reads both live via get_relation_data()/get_relation_values()
     * (same helper reassign_case() already uses), so they can never go
     * stale and there is nothing to write. Rule 2's "update ... source,
     * lead name" is therefore satisfied by the existing read path, not by
     * new sync code.
     *
     * Lead Priority (Rule 5) has no source here either: tblleads has no
     * native `priority` column and no "Priority" custom field exists in
     * this install (confirmed against tblcustomfields). The Case's own
     * priority - set by staff via change_case_priority() - is this
     * module's actual priority source of truth, so Task priority sync for
     * Rule 5 is wired there instead (see change_case_priority() below).
     *
     * Reuses get_lead_next_follow_up_date() and sync_reminder() (same
     * reminder sync every other next_follow_up_date write in this model
     * already uses) and create_or_get_case_task() (same Task-creation
     * path create_case_for_lead() uses, so a pre-migration Case with no
     * task_id yet gets its Task created here too - backward compatible).
     * Only ever UPDATEs an existing open Case, never inserts, so it
     * cannot create a duplicate Case; if none exists yet,
     * create_case_for_lead() (via lead_status_changed) still owns that.
     *
     * @param mixed $lead_id
     * @return void
     */
    public function sync_case_from_lead($lead_id)
    {
        $this->db->where('rel_id', $lead_id);
        $this->db->where('rel_type', 'lead');
        $this->db->where('case_status', 'open');
        $case = $this->db->get(db_prefix() . 'followups')->row();

        if (!$case) {
            return;
        }

        $this->db->where('id', $lead_id);
        $lead = $this->db->get(db_prefix() . 'leads')->row();

        if (!$lead) {
            return;
        }

        $now    = date('Y-m-d H:i:s');
        $update = ['last_activity_at' => $now];

        // Rule 4: Next Follow-up Date.
        $next_follow_up_date = $this->get_lead_next_follow_up_date($lead_id);
        $date_changed        = $next_follow_up_date != $case->next_follow_up_date;
        if ($date_changed) {
            $update['next_follow_up_date'] = $next_follow_up_date;
        }

        // Rule 3: Assigned Staff. $lead->assigned is NOT NULL/default 0 on
        // this schema, so 0 means "unassigned" - never overwrite an
        // already-assigned Case with that.
        $new_staff_id  = $lead->assigned ?: null;
        $staff_changed = $new_staff_id && $new_staff_id != $case->assigned_staff_id;
        if ($staff_changed) {
            $update['assigned_staff_id'] = $new_staff_id;
            $update['staff_id']          = $new_staff_id;
        }

        // Rule 2: Department.
        if ((int) $lead->department !== (int) $case->department_id) {
            $update['department_id'] = $lead->department;
        }

        if (count($update) > 1) {
            $this->db->where('id', $case->id);
            $this->db->update(db_prefix() . 'followups', $update);
        }

        if ($date_changed && $next_follow_up_date) {
            $reminder_staff_id    = $new_staff_id ?: ($case->assigned_staff_id ?: $case->staff_id);
            $reminder_id          = $this->sync_reminder($case->id, $lead_id, 'lead', $next_follow_up_date, $reminder_staff_id, $case->reminder_id);
            $this->db->where('id', $case->id);
            $this->db->update(db_prefix() . 'followups', ['reminder_id' => $reminder_id]);
        }

        if ($staff_changed) {
            $this->log_case_activity(
                $case->id,
                'reassigned',
                'Case auto-reassigned to match Lead Assigned Staff',
                $case->assigned_staff_id ? get_staff_full_name($case->assigned_staff_id) : null,
                get_staff_full_name($new_staff_id)
            );

            $rel_data   = get_relation_data('lead', $lead_id);
            $rel_values = get_relation_values($rel_data, 'lead');
            $this->notify_case_event(
                $case->id,
                $new_staff_id,
                'not_followup_case_reassigned',
                [$rel_values['name']],
                true,
                _l('followup_email_case_reassigned_title'),
                _l('followup_email_case_reassigned_body', [$rel_values['name']])
            );
        }

        // Task sync. A Case created before migration 358 (or whose Task
        // creation failed) has task_id = NULL - create_or_get_case_task()
        // creates the missing Task right now instead of silently leaving
        // the Case un-linked forever.
        if (!$case->task_id) {
            $task_staff_id = $new_staff_id ?: ($case->assigned_staff_id ?: $case->staff_id);
            $task_id       = $this->create_or_get_case_task($case->id, $lead, $task_staff_id, $case->priority, $date_changed ? $next_follow_up_date : $case->next_follow_up_date);

            if ($task_id) {
                $this->db->where('id', $case->id);
                $this->db->update(db_prefix() . 'followups', ['task_id' => $task_id]);
            }
        } else {
            if ($staff_changed) {
                $this->sync_task_assignee($case->task_id, $new_staff_id, $case->assigned_staff_id);
            }

            if ($date_changed) {
                $this->sync_task_duedate($case->task_id, $next_follow_up_date);
            }
        }
    }

    /**
     * Reschedule Follow-up (Telecaller Workspace quick action). The
     * Lead's own "Next Follow-up Date" custom field is the source of
     * truth (same field get_lead_next_follow_up_date() reads), so this
     * writes it first via the same generic handle_custom_fields_post()
     * the Lead edit form itself uses, then reuses sync_case_from_lead() -
     * the exact Lead->Case->Task->Reminder propagation Rule 4 already
     * built - instead of a second sync path. sync_case_from_lead()
     * deliberately never logs a Timeline entry (it would be noisy on
     * every unrelated Lead save), so one 'reschedule' entry is added
     * here, since this action is a distinct, deliberate user choice.
     *
     * @param mixed       $case_id
     * @param string      $new_date      Y-m-d
     * @param string|null $reminder_time H:i - defaults to the same 09:00
     *                                   business-hours convention
     *                                   get_lead_next_follow_up_date()
     *                                   uses when the Lead's date-only
     *                                   custom field is synced normally
     * @return bool
     */
    public function reschedule_case($case_id, $new_date, $reminder_time = null)
    {
        $case = $this->get($case_id);

        if (!$case || follow_up_case_access_denied($case)) {
            return false;
        }

        $field = get_custom_fields('leads', ['name' => 'Next Follow-up Date']);

        if (empty($field)) {
            return false;
        }

        handle_custom_fields_post($case->rel_id, ['leads' => [$field[0]['id'] => $new_date]]);

        $this->sync_case_from_lead($case->rel_id);

        $new_datetime = $new_date . ' 09:00:00';

        // sync_case_from_lead() always applies the fixed 09:00 default -
        // the Lead's own field is date-only, there is no persistent slot
        // for a specific time on it. A non-default reminder time is
        // therefore applied as one small correction on top of the sync
        // that already ran, using the same sync_task_duedate()/
        // sync_reminder() this model already uses everywhere else for
        // exactly this kind of write - the Lead's date-only field itself
        // is untouched either way.
        if ($reminder_time && $reminder_time !== '09:00') {
            $new_datetime = $new_date . ' ' . $reminder_time . ':00';
            $case         = $this->get($case_id);

            $this->db->where('id', $case_id);
            $this->db->update(db_prefix() . 'followups', ['next_follow_up_date' => $new_datetime]);

            if ($case->task_id) {
                $this->sync_task_duedate($case->task_id, $new_datetime);
            }

            if ($case->reminder_id) {
                $reminder_staff_id = $case->assigned_staff_id ?: $case->staff_id;
                $reminder_id       = $this->sync_reminder($case_id, $case->rel_id, $case->rel_type, $new_datetime, $reminder_staff_id, $case->reminder_id);

                $this->db->where('id', $case_id);
                $this->db->update(db_prefix() . 'followups', ['reminder_id' => $reminder_id]);
            }
        }

        $this->add_history_entry($case_id, [
            'activity_type'  => 'reschedule',
            'notes'          => 'Next follow-up rescheduled to ' . _dt($new_datetime),
            'event_datetime' => date('Y-m-d H:i:s'),
            'created_by'     => get_staff_user_id(),
        ]);

        return true;
    }

    /**
     * @param mixed $id
     * @return object|array
     */
    public function get($id = '')
    {
        if (is_numeric($id)) {
            $this->db->where('id', $id);

            return $this->db->get(db_prefix() . 'followups')->row();
        }

        return $this->db->get(db_prefix() . 'followups')->result();
    }

    public function get_for_entity($rel_id, $rel_type)
    {
        $this->db->where('rel_id', $rel_id);
        $this->db->where('rel_type', $rel_type);
        $this->db->order_by('follow_up_date', 'desc');

        return $this->db->get(db_prefix() . 'followups')->result();
    }

    /**
     * Telecaller Workspace. The single lookup the get_task filter hook
     * (follow_up_management.php) runs on EVERY task load to decide
     * whether a task is a Follow-up Task - task_id is indexed (migration
     * 359) specifically because of this. Returns null for any ordinary,
     * non-Follow-up task, which is the overwhelmingly common case.
     *
     * @param mixed $task_id
     * @return object|null
     */
    public function get_case_by_task_id($task_id)
    {
        $this->db->where('task_id', $task_id);

        return $this->db->get(db_prefix() . 'followups')->row();
    }

    /**
     * @param array  $data
     * @param mixed  $rel_id
     * @param string $rel_type
     * @return int|false
     */
    public function add($data, $rel_id, $rel_type)
    {
        $now = date('Y-m-d H:i:s');

        $data['rel_id']              = $rel_id;
        $data['rel_type']            = $rel_type;
        $data['follow_up_date']      = to_sql_date($data['follow_up_date'], true);
        $data['next_follow_up_date'] = (!empty($data['next_follow_up_date'])) ? to_sql_date($data['next_follow_up_date'], true) : null;
        $data['notes']               = isset($data['notes']) && $data['notes'] != '' ? nl2br($data['notes']) : null;
        $data['status']              = !empty($data['next_follow_up_date']) ? 'pending' : 'completed';
        $data['created_by']          = get_staff_user_id();
        $data['date_created']        = $now;

        if (empty($data['staff_id'])) {
            $data['staff_id'] = get_staff_user_id();
        }

        if (empty($data['department_id'])) {
            $data['department_id'] = $this->get_entity_department($rel_id, $rel_type);
        }

        // Case-level columns - this method starts a brand new Case (as
        // opposed to edit(), which logs an interaction against an existing
        // one), so it always opens the case regardless of the legacy
        // `status` value above.
        $data['assigned_staff_id'] = $data['staff_id'];
        $data['case_status']       = 'open';
        $data['priority']          = !empty($data['priority']) ? $data['priority'] : 'medium';
        $data['opened_at']         = $now;
        $data['last_activity_at']  = $now;

        $this->db->insert(db_prefix() . 'followups', $data);
        $insert_id = $this->db->insert_id();

        if ($insert_id) {
            if (!empty($data['next_follow_up_date'])) {
                $reminder_id = $this->sync_reminder($insert_id, $rel_id, $rel_type, $data['next_follow_up_date'], $data['staff_id']);

                if ($reminder_id) {
                    $this->db->where('id', $insert_id);
                    $this->db->update(db_prefix() . 'followups', ['reminder_id' => $reminder_id]);
                }
            }

            $this->add_history_entry($insert_id, [
                'activity_type'  => 'follow_up',
                'follow_up_type' => $data['follow_up_type'],
                'outcome'        => $data['outcome'] ?? null,
                'notes'          => $data['notes'],
                'event_datetime' => $data['follow_up_date'],
                'created_by'     => $data['created_by'],
                'reminder_id'    => $data['reminder_id'] ?? null,
            ]);

            $this->log_case_activity($insert_id, 'case_created', 'Case created', null, null, $data['created_by']);

            if ($rel_type == 'lead') {
                $this->load->model('leads_model');
                $this->leads_model->log_lead_activity($rel_id, 'not_activity_new_followup_logged', false, serialize([
                    get_staff_full_name($data['staff_id']),
                ]));
            }

            log_activity('New Follow-up Logged [' . ucfirst($rel_type) . 'ID: ' . $rel_id . ']');

            hooks()->do_action('follow_up_added', ['id' => $insert_id, 'data' => $data]);

            return $insert_id;
        }

        return false;
    }

    /**
     * @param array $data
     * @param mixed $id
     * @return bool
     */
    public function edit($data, $id)
    {
        $existing = $this->get($id);

        if (!$existing) {
            return false;
        }

        $now = date('Y-m-d H:i:s');

        $data['follow_up_date']      = to_sql_date($data['follow_up_date'], true);
        $data['next_follow_up_date'] = (!empty($data['next_follow_up_date'])) ? to_sql_date($data['next_follow_up_date'], true) : null;
        $data['notes']               = isset($data['notes']) && $data['notes'] != '' ? nl2br($data['notes']) : null;
        $data['date_updated']        = $now;
        $data['last_activity_at']    = $now;

        if (empty($data['staff_id'])) {
            $data['staff_id'] = $existing->staff_id;
        }

        // Keep the new case_status/closed_at columns in sync with the
        // legacy `status` field this form has always posted, so anything
        // reading the new columns (Phase 2+) stays correct even though the
        // Case Detail form itself is unchanged in this phase. Only ever
        // transitions open -> closed here; reopening is an explicit action
        // added in Phase 2, not an implicit side effect of editing.
        $case_status_changed = false;
        if (isset($data['status']) && in_array($data['status'], ['completed', 'no_further_action']) && $existing->case_status !== 'closed') {
            $data['case_status'] = 'closed';
            $data['closed_at']   = $now;
            $case_status_changed = true;
        }

        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'followups', $data);
        // Captured immediately - several queries run after this (reminder
        // sync, history/activity inserts) and would otherwise overwrite
        // affected_rows() by the time this method returns.
        $success = $this->db->affected_rows() > 0;

        if (!empty($data['next_follow_up_date'])) {
            $reminder_id = $this->sync_reminder($id, $existing->rel_id, $existing->rel_type, $data['next_follow_up_date'], $data['staff_id'], $existing->reminder_id);

            if ($reminder_id != $existing->reminder_id) {
                $this->db->where('id', $id);
                $this->db->update(db_prefix() . 'followups', ['reminder_id' => $reminder_id]);
            }
        } elseif ($existing->reminder_id) {
            // next follow-up date was cleared - cancel the linked reminder if it hasn't fired yet
            $this->cancel_reminder_if_pending($existing->reminder_id, $id);
            $this->db->where('id', $id);
            $this->db->update(db_prefix() . 'followups', ['reminder_id' => null]);
        }

        $this->add_history_entry($id, [
            'activity_type'  => 'follow_up',
            'follow_up_type' => $data['follow_up_type'] ?? $existing->follow_up_type,
            'outcome'        => $data['outcome'] ?? $existing->outcome,
            'notes'          => $data['notes'],
            'event_datetime' => $data['follow_up_date'],
            'created_by'     => get_staff_user_id(),
            'reminder_id'    => $data['reminder_id'] ?? $existing->reminder_id,
        ]);

        if ($case_status_changed) {
            $this->log_case_activity($id, 'status_changed', 'Case closed', $existing->case_status, 'closed', get_staff_user_id());
        }

        hooks()->do_action('follow_up_updated', ['id' => $id, 'data' => $data]);

        return $success;
    }

    /**
     * Ownership-gated the same way Misc_model::delete_reminder() gates
     * reminder deletion - creator or admin only.
     *
     * @param mixed $id
     * @return bool
     */
    public function delete($id)
    {
        $followup = $this->get($id);

        if (!$followup) {
            return false;
        }

        if ($followup->created_by != get_staff_user_id() && !is_admin()) {
            return false;
        }

        if ($followup->reminder_id) {
            $this->cancel_reminder_if_pending($followup->reminder_id);
        }

        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'followups');
        $deleted = $this->db->affected_rows() > 0;

        if ($deleted) {
            // Clean up child rows so history/activity never orphan a
            // deleted case.
            $this->db->where('followup_id', $id);
            $this->db->delete(db_prefix() . 'followup_history');
            $this->db->where('followup_id', $id);
            $this->db->delete(db_prefix() . 'followup_activity');

            hooks()->do_action('follow_up_deleted', $id);

            return true;
        }

        return false;
    }

    /**
     * Creates or updates the tblreminders row linked to a follow-up's
     * next_follow_up_date, by calling into Misc_model - this is the reuse
     * point that gives Follow-up Management the entire existing due-date
     * cron, in-app notification, email, SMS, and calendar pipeline for free
     * (see Cron_model::staff_reminders() and Utilities_model::get_calendar_data()),
     * with no new delivery code in this module.
     *
     * Phase 6 fix for the Phase 5 duplicate calendar marker: the stored
     * reminder's own rel_type is 'follow_up_case' (rel_id = the Case, not
     * the Lead) - NOT $rel_type/$rel_id, which are only used below to build
     * the reminder's description text. Utilities_model::get_calendar_data()'s
     * reminders-on-calendar loop iterates exclusively over
     * App::get_available_reminders_keys(), a hardcoded, non-filterable list
     * of 9 core types that will never include 'follow_up_case' - so these
     * rows are structurally invisible to that loop and never produce a
     * second marker. This module's own before_fetch_events hook
     * (follow_up_management_calendar_events()) remains the single source of
     * the calendar marker. get_relation_data()/get_relation_values() still
     * resolve 'follow_up_case' correctly via the get_relation_data/
     * relation_values filters registered in follow_up_management.php, so
     * Cron_model::staff_reminders() (rel_type-agnostic - it processes every
     * due row in tblreminders) keeps building a working notification
     * link/name and sending the core 'staff_reminder' email, unmodified.
     *
     * Known, accepted trade-off: Misc_model::add_reminder() only logs a
     * "reminder created" entry onto the LEAD's own activity feed when
     * rel_type == 'lead'; since these rows are now 'follow_up_case', that
     * one line of Lead-side activity visibility is lost. This module logs
     * the same event to its own Activity Log instead (see the
     * add_notification()/notify_case_event() call sites), so nothing is
     * silently dropped, but it is worth knowing this is the direct,
     * necessary cost of the fix.
     *
     * @param mixed  $case_id
     * @param mixed  $rel_id   the entity this follow-up is about (e.g. the lead)
     * @param string $rel_type
     * @param string $next_date SQL datetime
     * @param mixed  $staff_id
     * @param mixed  $existing_reminder_id
     * @param string $extra_description Appointment section (Section 7) Location/
     *                                  Meeting Type text, appended onto the
     *                                  auto-generated description - tblreminders.
     *                                  description is already free text, no
     *                                  schema change.
     * @return int|false
     */
    private function sync_reminder($case_id, $rel_id, $rel_type, $next_date, $staff_id, $existing_reminder_id = null, $extra_description = '')
    {
        $this->load->model('misc_model');

        $rel_data    = get_relation_data($rel_type, $rel_id);
        $rel_values  = get_relation_values($rel_data, $rel_type);
        $description = _l('followup_reminder_description', [$rel_values['name']]);

        if ($extra_description !== '') {
            $description .= "\n" . $extra_description;
        }

        $reminder_data = [
            'rel_id'          => $case_id,
            'rel_type'        => 'follow_up_case',
            'date'            => $next_date,
            'staff'           => $staff_id,
            'description'     => $description,
            'notify_by_email' => 1,
        ];

        if ($existing_reminder_id) {
            $reminder = $this->misc_model->get_reminders($existing_reminder_id);

            if ($reminder && $reminder->isnotified == 0) {
                $this->misc_model->edit_reminder($reminder_data, $existing_reminder_id);

                return $existing_reminder_id;
            }
        }

        // Bug fix: Misc_model::add_reminder() itself calls log_activity()
        // (a second, unrelated INSERT into tblactivity_log) right after
        // its own tblreminders insert but before returning - calling
        // $this->db->insert_id() out here after it returns therefore
        // picked up THAT insert's id, not the reminder's, silently
        // corrupting tblfollowups.reminder_id with an id from the wrong
        // table on every new reminder (confirmed live: reminder_id values
        // were landing in tblactivity_log's id range). add_reminder() now
        // returns the actual reminder id it just inserted (see its own
        // fix) - use that directly instead of re-deriving it here.
        return $this->misc_model->add_reminder($reminder_data, $case_id);
    }

    /**
     * Inherits the Department from the related entity's own Department field
     * (already a real column on tblleads as of migration 352) so staff aren't
     * asked to pick the same department twice. Returns null for any rel_type
     * that doesn't have a Department field yet.
     *
     * @param mixed  $rel_id
     * @param string $rel_type
     * @return int|null
     */
    private function get_entity_department($rel_id, $rel_type)
    {
        if ($rel_type == 'lead') {
            $lead = $this->db->select('department')->where('id', $rel_id)->get(db_prefix() . 'leads')->row();

            return $lead ? $lead->department : null;
        }

        if ($rel_type == 'customer') {
            $client = $this->db->select('department')->where('userid', $rel_id)->get(db_prefix() . 'clients')->row();

            return $client ? $client->department : null;
        }

        return null;
    }

    /**
     * Chronological interaction log for a Case (the Case Detail page's
     * Timeline section, built in Phase 2). Oldest first, matching the
     * "display every follow-up in chronological order" requirement.
     *
     * @param mixed $followup_id
     * @return array
     */
    public function get_history($followup_id)
    {
        $this->db->where('followup_id', $followup_id);
        $this->db->order_by('event_datetime', 'asc');

        return $this->db->get(db_prefix() . 'followup_history')->result();
    }

    /**
     * System/audit trail for a Case (the Case Detail page's Activity Log
     * section, built in Phase 2). Oldest first.
     *
     * @param mixed $followup_id
     * @return array
     */
    public function get_activity_log($followup_id)
    {
        $this->db->where('followup_id', $followup_id);
        $this->db->order_by('created_at', 'asc');

        return $this->db->get(db_prefix() . 'followup_activity')->result();
    }

    /**
     * Telecaller Workspace bundle - everything Sections 1/2/6 (Lead Info,
     * Follow-up Info, Lead Snapshot) need beyond the Case row itself,
     * assembled in one pass so the render callback doesn't re-derive any
     * of it. Sections 3/4 (History/Notes) deliberately stay out of this
     * bundle - they're a single already-indexed get_history() call the
     * caller makes separately and filters client-side (both sections read
     * the same result set, so there is nothing to gain by folding it in
     * here).
     *
     * Every value here is read live from the Lead/reminder/customer
     * tables - nothing is cached or duplicated onto tblfollowups/tbltasks,
     * keeping the Lead the single source of truth.
     *
     * @param object $case a tblfollowups row (from get_case_by_task_id() or get())
     * @return array
     */
    public function get_task_workspace_data($case)
    {
        $this->load->model('leads_model');
        $lead = $this->leads_model->get($case->rel_id);

        $tags = get_tags_in($case->rel_id, 'lead');

        $custom_fields = [];
        foreach (get_custom_fields('leads') as $field) {
            $value = get_custom_field_value($case->rel_id, $field['id'], 'leads');

            if ($value !== '' && $value !== null) {
                $custom_fields[] = ['name' => $field['name'], 'value' => $value];
            }
        }

        $this->db->select('userid, company');
        $this->db->where('leadid', $case->rel_id);
        $customer = $this->db->get(db_prefix() . 'clients')->row();

        $reminder = null;
        if ($case->reminder_id) {
            $this->load->model('misc_model');
            $reminder = $this->misc_model->get_reminders($case->reminder_id);
        }

        $overdue = $case->case_status === 'open'
            && $case->next_follow_up_date
            && $case->next_follow_up_date < date('Y-m-d H:i:s');

        return [
            'lead'            => $lead,
            'tags'            => $tags,
            'custom_fields'   => $custom_fields,
            'customer'        => $customer,
            'reminder'        => $reminder,
            'department_name' => get_business_department_name($case->department_id),
            'overdue'         => $overdue,
            'can_act'         => !follow_up_case_access_denied($case),
        ];
    }

    /**
     * Telecaller Workspace Section 6 (Lead Pipeline) - derives the
     * current stage from signals that already exist, never storing a
     * stage anywhere. Deliberately takes $lead and $history as separate
     * params (rather than re-fetching them, or folding this into
     * get_task_workspace_data()) since the render callback already has
     * both from calls it makes anyway (the Lead from the workspace
     * bundle, History from the existing get_history() call for Section
     * 8) - avoids a third, duplicate query pass.
     *
     * Order matters: checked most-advanced-first, since a Case can carry
     * signals for multiple earlier stages at once (e.g. an already-won
     * case still has call/meeting history) and only the furthest one
     * should be reported as "current".
     *
     * @param object      $case    a tblfollowups row
     * @param object|null $lead    Leads_model::get() result
     * @param array       $history get_history($case->id) result
     * @return string one of get_followup_pipeline_stages()'s keys
     */
    public function get_pipeline_stage($case, $lead, $history)
    {
        if ($lead && !empty($lead->status_name) && strtolower($lead->status_name) === 'converted') {
            return 'converted_customer';
        }

        if ($case->outcome === 'converted') {
            return 'won_customer';
        }

        if ($case->outcome === 'negotiation') {
            return 'negotiation';
        }

        if ($case->outcome === 'proposal_sent') {
            return 'proposal_sent';
        }

        if ($case->outcome === 'sales_discussion') {
            return 'sales_discussion';
        }

        $has_meeting = false;
        $has_call    = false;
        foreach ($history as $entry) {
            if ($entry->activity_type === 'meeting') {
                $has_meeting = true;
            }
            if ($entry->activity_type === 'call') {
                $has_call = true;
            }
        }

        if ($has_meeting) {
            return 'appointment_scheduled';
        }

        if ($case->next_follow_up_date) {
            return 'follow_up_scheduled';
        }

        if ($case->outcome === 'interested') {
            return 'interested';
        }

        if ($has_call) {
            return 'call_attempted';
        }

        return 'lead_assigned';
    }

    /**
     * KPI card counts for the My Cases page header, all respecting the
     * Admin/Manager/Telecaller visibility scope from
     * get_followup_case_visibility_where() (helpers/follow_up_management_helper.php).
     *
     * @return array
     */
    public function get_case_counts()
    {
        $today = date('Y-m-d');

        return [
            'my_open_cases'    => $this->count_cases(['case_status' => 'open']),
            'todays_followups' => $this->count_cases(['case_status' => 'open'], 'next_follow_up_date IS NOT NULL AND DATE(next_follow_up_date) = "' . $today . '"'),
            'overdue_cases'    => $this->count_cases(['case_status' => 'open'], 'next_follow_up_date IS NOT NULL AND DATE(next_follow_up_date) < "' . $today . '"'),
            'completed_today'  => $this->count_cases(['case_status' => 'closed'], 'closed_at IS NOT NULL AND DATE(closed_at) = "' . $today . '"'),
            'high_priority'    => $this->count_cases(['case_status' => 'open'], 'priority IN ("high","urgent")'),
        ];
    }

    /**
     * Lead-status counts for the simplified Telecaller dashboard - unlike
     * get_case_counts() above (Case-status based), "Customer Confirmed"/
     * "Lost" are Lead-status values, not Case-lifecycle values, so this
     * counts tblleads directly. Scoped through the same
     * get_followup_lead_visibility_where() the Telecaller queue itself
     * uses (application/views/admin/tables/my_follow_ups.php), not a new
     * visibility rule.
     *
     * @return array ['my_assigned_leads', 'customer_confirmed', 'lost']
     */
    public function get_lead_status_counts()
    {
        $visibility = get_followup_lead_visibility_where();

        $this->db->select('COUNT(*) as count', false);
        $this->db->from(db_prefix() . 'leads');
        if ($visibility) {
            $this->db->where(substr($visibility, 4), null, false);
        }
        $my_assigned_leads = (int) $this->db->get()->row()->count;

        $this->db->select(db_prefix() . 'leads_status.name as status_name, COUNT(*) as count', false);
        $this->db->from(db_prefix() . 'leads');
        $this->db->join(db_prefix() . 'leads_status', db_prefix() . 'leads_status.id = ' . db_prefix() . 'leads.status', 'left');
        $this->db->where_in(db_prefix() . 'leads_status.name', ['Customer Confirmed', 'Lost']);
        if ($visibility) {
            $this->db->where(substr($visibility, 4), null, false);
        }
        $this->db->group_by(db_prefix() . 'leads_status.name');
        $by_status = $this->db->get()->result();

        $counts = [
            'my_assigned_leads'  => $my_assigned_leads,
            'customer_confirmed' => 0,
            'lost'               => 0,
        ];

        foreach ($by_status as $row) {
            if ($row->status_name === 'Customer Confirmed') {
                $counts['customer_confirmed'] = (int) $row->count;
            } elseif ($row->status_name === 'Lost') {
                $counts['lost'] = (int) $row->count;
            }
        }

        return $counts;
    }

    /**
     * Today's Follow-ups page data - three visibility-scoped groups.
     * "Completed Today" uses the authoritative case_status/closed_at
     * lifecycle columns (same definition as the Dashboard's "Closed Today"
     * KPI, get_dashboard_kpis() below) - this replaces an earlier version
     * of this method that checked the legacy `status` field, which missed
     * cases closed via the Close Case action (that action never touches
     * `status`, only `case_status`/`closed_at`).
     *
     * @return array ['overdue' => [...], 'due_today' => [...], 'completed_today' => [...]]
     */
    public function get_todays_followups_grouped()
    {
        $today = date('Y-m-d');

        return [
            'overdue'         => $this->get_cases_where(['case_status' => 'open'], 'next_follow_up_date IS NOT NULL AND DATE(next_follow_up_date) < "' . $today . '"', 'next_follow_up_date', 'asc'),
            'due_today'       => $this->get_cases_where(['case_status' => 'open'], 'next_follow_up_date IS NOT NULL AND DATE(next_follow_up_date) = "' . $today . '"', 'next_follow_up_date', 'asc'),
            'completed_today' => $this->get_cases_where(['case_status' => 'closed'], 'closed_at IS NOT NULL AND DATE(closed_at) = "' . $today . '"', 'closed_at', 'desc'),
        ];
    }

    /**
     * Dashboard KPI cards. All visibility-scoped except my_assigned_cases,
     * which is always self-scoped by definition (same reasoning as the "My
     * Today's Follow-ups" widget - an Admin's own dashboard still shows
     * THEIR assignment count, not everyone's).
     *
     * @return array
     */
    public function get_dashboard_kpis()
    {
        $today      = date('Y-m-d');
        $week_start = date('Y-m-d', strtotime('monday this week'));
        $week_end   = date('Y-m-d', strtotime('sunday this week'));

        return [
            'total_open_cases'    => $this->count_cases(['case_status' => 'open']),
            'my_assigned_cases'   => $this->count_my_assigned_cases(),
            'todays_followups'    => $this->count_cases(['case_status' => 'open'], 'next_follow_up_date IS NOT NULL AND DATE(next_follow_up_date) = "' . $today . '"'),
            'overdue_cases'       => $this->count_cases(['case_status' => 'open'], 'next_follow_up_date IS NOT NULL AND DATE(next_follow_up_date) < "' . $today . '"'),
            'high_priority_cases' => $this->count_cases(['case_status' => 'open'], 'priority IN ("high","urgent")'),
            'closed_today'        => $this->count_cases(['case_status' => 'closed'], 'closed_at IS NOT NULL AND DATE(closed_at) = "' . $today . '"'),
            'completed_this_week' => $this->count_cases(['case_status' => 'closed'], 'closed_at IS NOT NULL AND DATE(closed_at) BETWEEN "' . $week_start . '" AND "' . $week_end . '"'),
            'avg_response_time'   => $this->get_average_response_time_seconds(),
        ];
    }

    private function count_my_assigned_cases()
    {
        $this->db->where('case_status', 'open');
        $this->db->where('assigned_staff_id', get_staff_user_id());

        return (int) $this->db->count_all_results(db_prefix() . 'followups');
    }

    /**
     * Average seconds between a case being opened and its first real
     * interaction (any Timeline entry other than the automatic
     * "case_created" one). Computed in PHP over a small grouped result set
     * rather than a single fragile aggregate query. Returns null (no data
     * yet) or an integer second count - formatting is the view's job via
     * get_followup_format_duration() in the helper.
     *
     * @return int|null
     */
    private function get_average_response_time_seconds($staff_id = null, $date_from = null, $date_to = null)
    {
        $this->db->select(db_prefix() . 'followups.opened_at, MIN(' . db_prefix() . 'followup_history.created_at) as first_response_at', false);
        $this->db->from(db_prefix() . 'followups');
        $this->db->join(db_prefix() . 'followup_history', db_prefix() . 'followup_history.followup_id = ' . db_prefix() . 'followups.id AND ' . db_prefix() . 'followup_history.activity_type != "case_created"');
        $this->db->where(db_prefix() . 'followups.opened_at IS NOT NULL', null, false);

        if ($staff_id) {
            $this->db->where(db_prefix() . 'followups.assigned_staff_id', $staff_id);
        }

        if ($date_from) {
            $this->db->where(db_prefix() . 'followups.opened_at >=', $date_from . ' 00:00:00');
        }

        if ($date_to) {
            $this->db->where(db_prefix() . 'followups.opened_at <=', $date_to . ' 23:59:59');
        }

        $visibility = get_followup_case_visibility_where();
        if ($visibility) {
            $this->_apply_visibility();
        }

        $this->db->group_by(db_prefix() . 'followups.id');
        $rows = $this->db->get()->result();

        $total_seconds = 0;
        $count         = 0;
        foreach ($rows as $row) {
            if (!$row->first_response_at) {
                continue;
            }
            $total_seconds += strtotime($row->first_response_at) - strtotime($row->opened_at);
            $count++;
        }

        return $count > 0 ? (int) round($total_seconds / $count) : null;
    }

    /**
     * Cases by Status (Pie). Same shape/convention as
     * Reports_model::followups_by_outcome_report() - reusing
     * get_system_favourite_colors()/adjust_color_brightness(), the exact
     * colors already used by the existing Follow-ups report chart.
     *
     * @return array Chart.js data
     */
    public function get_chart_cases_by_status()
    {
        return $this->build_pie_chart(get_followup_statuses(), function ($key) {
            return $this->count_cases(['status' => $key]);
        });
    }

    /**
     * Cases by Priority (Doughnut) - open cases only (the actionable
     * workload), same chart shape.
     *
     * @return array Chart.js data
     */
    public function get_chart_cases_by_priority()
    {
        return $this->build_pie_chart(get_followup_priorities(), function ($key) {
            return $this->count_cases(['case_status' => 'open', 'priority' => $key]);
        });
    }

    private function build_pie_chart($labeled_keys, $count_callback)
    {
        $colors = get_system_favourite_colors();
        $chart  = [
            'labels'   => array_values($labeled_keys),
            'datasets' => [[
                'data'                 => [],
                'backgroundColor'      => [],
                'hoverBackgroundColor' => [],
            ]],
        ];

        $i = 0;
        foreach (array_keys($labeled_keys) as $key) {
            $chart['datasets'][0]['data'][] = $count_callback($key);
            $color                          = $colors[$i % count($colors)];
            $chart['datasets'][0]['backgroundColor'][]      = $color;
            $chart['datasets'][0]['hoverBackgroundColor'][] = adjust_color_brightness($color, -20);
            $i++;
        }

        return $chart;
    }

    /**
     * Cases Created vs Closed (Line) - last $days days, two series.
     *
     * @param int $days
     * @return array Chart.js data
     */
    public function get_chart_cases_created_vs_closed($days = 14)
    {
        [$labels, $dates] = $this->build_day_buckets($days);

        return [
            'labels'   => $labels,
            'datasets' => [
                [
                    'label'           => _l('chart_created'),
                    'data'            => $this->count_cases_grouped_by_day('opened_at', $dates),
                    'borderColor'     => '#28B8DA',
                    'backgroundColor' => 'rgba(40,184,218,0.15)',
                    'fill'            => true,
                ],
                [
                    'label'           => _l('chart_closed'),
                    'data'            => $this->count_cases_grouped_by_day('closed_at', $dates, ['case_status' => 'closed']),
                    'borderColor'     => '#84C529',
                    'backgroundColor' => 'rgba(132,197,41,0.15)',
                    'fill'            => true,
                ],
            ],
        ];
    }

    /**
     * Daily Follow-up Activity (Bar) - count of Timeline entries per day,
     * last $days days, visibility-scoped via the join back to tblfollowups.
     *
     * @param int $days
     * @return array Chart.js data
     */
    public function get_chart_daily_activity($days = 7, $staff_id = null)
    {
        [$labels, $dates] = $this->build_day_buckets($days, 'D');

        $this->db->select('DATE(' . db_prefix() . 'followup_history.event_datetime) as day, COUNT(*) as total', false);
        $this->db->from(db_prefix() . 'followup_history');
        $this->db->join(db_prefix() . 'followups', db_prefix() . 'followups.id = ' . db_prefix() . 'followup_history.followup_id');
        $this->db->where(db_prefix() . 'followup_history.event_datetime >=', $dates[0] . ' 00:00:00');

        if ($staff_id) {
            $this->db->where(db_prefix() . 'followup_history.created_by', $staff_id);
        }

        $visibility = get_followup_case_visibility_where();
        if ($visibility) {
            $this->_apply_visibility();
        }

        $this->db->group_by('DATE(' . db_prefix() . 'followup_history.event_datetime)');
        $rows = $this->db->get()->result();

        $by_day = [];
        foreach ($rows as $row) {
            $by_day[$row->day] = (int) $row->total;
        }

        $data = [];
        foreach ($dates as $date) {
            $data[] = $by_day[$date] ?? 0;
        }

        return [
            'labels'   => $labels,
            'datasets' => [[
                'label'           => _l('case_timeline'),
                'data'            => $data,
                'backgroundColor' => '#03a9f4',
            ]],
        ];
    }

    /**
     * Department Performance (Bar) - cases closed per department, last 30
     * days. Uses the existing get_business_departments() helper, not a
     * separate department listing.
     *
     * @return array Chart.js data
     */
    public function get_chart_department_performance()
    {
        $departments = get_business_departments();
        $colors      = get_system_favourite_colors();
        $since       = date('Y-m-d', strtotime('-30 days'));

        $chart = [
            'labels'   => [],
            'datasets' => [[
                'label'           => _l('chart_department_performance'),
                'data'            => [],
                'backgroundColor' => [],
            ]],
        ];

        $i = 0;
        foreach ($departments as $department) {
            $chart['labels'][]              = $department['name'];
            $chart['datasets'][0]['data'][] = $this->count_cases(
                ['department_id' => $department['id'], 'case_status' => 'closed'],
                'closed_at IS NOT NULL AND closed_at >= "' . $since . ' 00:00:00"'
            );
            $chart['datasets'][0]['backgroundColor'][] = $colors[$i % count($colors)];
            $i++;
        }

        return $chart;
    }

    /**
     * Monthly Closure Trend (Line) - cases closed per month, last $months
     * months.
     *
     * @param int $months
     * @return array Chart.js data
     */
    public function get_chart_monthly_closure_trend($months = 6)
    {
        $labels     = [];
        $month_keys = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $ts           = strtotime('-' . $i . ' months');
            $month_keys[] = date('Y-m', $ts);
            $labels[]     = date('M Y', $ts);
        }

        $this->db->select('DATE_FORMAT(closed_at, "%Y-%m") as month, COUNT(*) as total', false);
        $this->db->from(db_prefix() . 'followups');
        $this->db->where('case_status', 'closed');
        $this->db->where('closed_at IS NOT NULL', null, false);
        $this->db->where('closed_at >=', date('Y-m-01', strtotime('-' . ($months - 1) . ' months')));

        $visibility = get_followup_case_visibility_where();
        if ($visibility) {
            $this->_apply_visibility();
        }

        $this->db->group_by('DATE_FORMAT(closed_at, "%Y-%m")');
        $rows = $this->db->get()->result();

        $by_month = [];
        foreach ($rows as $row) {
            $by_month[$row->month] = (int) $row->total;
        }

        $data = [];
        foreach ($month_keys as $key) {
            $data[] = $by_month[$key] ?? 0;
        }

        return [
            'labels'   => $labels,
            'datasets' => [[
                'label'           => _l('chart_monthly_closure_trend'),
                'data'            => $data,
                'borderColor'     => '#8e24aa',
                'backgroundColor' => 'rgba(142,36,170,0.15)',
                'fill'            => true,
            ]],
        ];
    }

    /**
     * @param int    $days
     * @param string $label_format PHP date() format for the x-axis labels
     * @return array [labels[], dates[] (Y-m-d, oldest first)]
     */
    private function build_day_buckets($days, $label_format = 'M j')
    {
        $labels = [];
        $dates  = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $ts       = strtotime('-' . $i . ' days');
            $dates[]  = date('Y-m-d', $ts);
            $labels[] = date($label_format, $ts);
        }

        return [$labels, $dates];
    }

    /**
     * @param string $date_column  opened_at|closed_at
     * @param array  $dates        Y-m-d, oldest first
     * @param array  $where_equals
     * @return array counts aligned to $dates
     */
    private function count_cases_grouped_by_day($date_column, $dates, $where_equals = [])
    {
        $this->db->select('DATE(' . $date_column . ') as day, COUNT(*) as total', false);
        $this->db->from(db_prefix() . 'followups');
        $this->db->where($date_column . ' IS NOT NULL', null, false);
        $this->db->where($date_column . ' >=', $dates[0] . ' 00:00:00');

        if (!empty($where_equals)) {
            $this->db->where($where_equals);
        }

        $visibility = get_followup_case_visibility_where();
        if ($visibility) {
            $this->_apply_visibility();
        }

        $this->db->group_by('DATE(' . $date_column . ')');
        $rows = $this->db->get()->result();

        $by_day = [];
        foreach ($rows as $row) {
            $by_day[$row->day] = (int) $row->total;
        }

        $result = [];
        foreach ($dates as $date) {
            $result[] = $by_day[$date] ?? 0;
        }

        return $result;
    }

    /**
     * Upcoming Follow-ups list - visibility-scoped, soonest first.
     *
     * @param int $limit
     * @return array
     */
    /**
     * The single source of truth for "open cases with a scheduled
     * next_follow_up_date in this date range" - visibility-scoped. This
     * backs BOTH the Calendar integration (follow_up_management_calendar_events()
     * in follow_up_management.php, hooked into Utilities_model's
     * before_fetch_events filter) and the Dashboard's Upcoming Follow-ups
     * (get_upcoming_followups() below), per the explicit Phase 5
     * requirement that Dashboard upcoming events read directly from
     * Calendar data rather than running a second, near-identical query.
     *
     * @param string $start Y-m-d
     * @param string $end   Y-m-d
     * @return array
     */
    public function get_calendar_events($start, $end)
    {
        $this->db->where('case_status', 'open');
        $this->db->where('next_follow_up_date IS NOT NULL', null, false);
        $this->db->where('next_follow_up_date BETWEEN "' . $start . ' 00:00:00" AND "' . $end . ' 23:59:59"', null, false);

        $visibility = get_followup_case_visibility_where();
        if ($visibility) {
            $this->_apply_visibility();
        }

        $this->db->order_by('next_follow_up_date', 'asc');

        return $this->db->get(db_prefix() . 'followups')->result();
    }

    /**
     * Dashboard's Upcoming Follow-ups - filters/limits get_calendar_events()'s
     * result in PHP rather than querying separately (see that method's
     * docblock).
     *
     * @param int $limit
     * @return array
     */
    public function get_upcoming_followups($limit = 10)
    {
        $events = $this->get_calendar_events(date('Y-m-d'), date('Y-m-d', strtotime('+90 days')));

        $now    = date('Y-m-d H:i:s');
        $events = array_values(array_filter($events, function ($event) use ($now) {
            return $event->next_follow_up_date >= $now;
        }));

        return array_slice($events, 0, $limit);
    }

    /**
     * Recent Activities feed (tblfollowup_activity) - visibility-scoped via
     * the join back to tblfollowups, newest first.
     *
     * @param int $limit
     * @return array
     */
    public function get_recent_activities($limit = 10)
    {
        $this->db->select(db_prefix() . 'followup_activity.*');
        $this->db->from(db_prefix() . 'followup_activity');
        $this->db->join(db_prefix() . 'followups', db_prefix() . 'followups.id = ' . db_prefix() . 'followup_activity.followup_id');

        $visibility = get_followup_case_visibility_where();
        if ($visibility) {
            $this->_apply_visibility();
        }

        $this->db->order_by(db_prefix() . 'followup_activity.created_at', 'desc');
        $this->db->limit($limit);

        return $this->db->get()->result();
    }

    /**
     * Today's Performance strip.
     *
     * @return array
     */
    public function get_todays_performance()
    {
        $today = date('Y-m-d');

        return [
            'calls_logged_today'       => $this->count_history_today('call', $today),
            'meetings_scheduled_today' => $this->count_history_today('meeting', $today),
            'reminders_created_today'  => $this->count_history_today('reminder', $today),
            'cases_closed_today'       => $this->count_cases(['case_status' => 'closed'], 'closed_at IS NOT NULL AND DATE(closed_at) = "' . $today . '"'),
            'cases_reopened_today'     => $this->count_activity_today('case_reopened', $today),
        ];
    }

    /**
     * Notification Center feed - reads the CORE tblnotifications table
     * (never a separate table of its own), isolated to this module's own
     * rows via description IN (get_followup_notification_types() keys),
     * joined back to tblfollowups so Admin/Manager/Telecaller visibility
     * reuses get_followup_case_visibility_where() exactly like every other
     * query in this module, per the explicit Phase 6 instruction. This
     * means an Admin sees every Follow-up notification system-wide (not
     * just ones addressed to them), a Manager sees their department's, and
     * a Telecaller sees only notifications tied to their own assigned
     * cases - "own notifications" and "notifications about my cases"
     * coincide for a Telecaller since every Follow-up notification is only
     * ever sent to the case's assigned staff member in the first place.
     *
     * @param int $limit
     * @return array
     */
    public function get_case_notifications($limit = 50)
    {
        $this->db->select(db_prefix() . 'notifications.*');
        $this->db->from(db_prefix() . 'notifications');
        $this->db->join(db_prefix() . 'followups', db_prefix() . 'notifications.link = CONCAT("follow_up_management/view/", ' . db_prefix() . 'followups.id)');
        $this->db->where_in(db_prefix() . 'notifications.description', array_keys(get_followup_notification_types()));

        $visibility = get_followup_case_visibility_where();
        if ($visibility) {
            $this->_apply_visibility();
        }

        $this->db->order_by(db_prefix() . 'notifications.date', 'desc');
        $this->db->limit($limit);

        return $this->db->get()->result();
    }

    private function count_history_today($activity_type, $today)
    {
        $this->db->from(db_prefix() . 'followup_history');
        $this->db->join(db_prefix() . 'followups', db_prefix() . 'followups.id = ' . db_prefix() . 'followup_history.followup_id');
        $this->db->where(db_prefix() . 'followup_history.activity_type', $activity_type);
        $this->db->where('DATE(' . db_prefix() . 'followup_history.created_at) = "' . $today . '"', null, false);

        $visibility = get_followup_case_visibility_where();
        if ($visibility) {
            $this->_apply_visibility();
        }

        return (int) $this->db->count_all_results();
    }

    /**
     * Row-returning sibling of count_history_today() above - identical
     * from/join/where, just SELECTing the Case instead of counting rows,
     * so a lead called twice today still appears (and counts) twice, the
     * exact same way count_history_today() itself counts that case twice.
     * Keeps the "Completed Calls" detail list's row count matching its
     * own summary number rather than silently deduplicating.
     */
    private function get_history_cases_today($activity_type, $today)
    {
        $this->db->select(db_prefix() . 'followups.*', false);
        $this->db->from(db_prefix() . 'followup_history');
        $this->db->join(db_prefix() . 'followups', db_prefix() . 'followups.id = ' . db_prefix() . 'followup_history.followup_id');
        $this->db->where(db_prefix() . 'followup_history.activity_type', $activity_type);
        $this->db->where('DATE(' . db_prefix() . 'followup_history.created_at) = "' . $today . '"', null, false);

        $visibility = get_followup_case_visibility_where();
        if ($visibility) {
            $this->_apply_visibility();
        }

        $this->db->order_by(db_prefix() . 'followup_history.created_at', 'desc');

        return $this->db->get()->result();
    }

    private function count_activity_today($action_type, $today)
    {
        $this->db->from(db_prefix() . 'followup_activity');
        $this->db->join(db_prefix() . 'followups', db_prefix() . 'followups.id = ' . db_prefix() . 'followup_activity.followup_id');
        $this->db->where(db_prefix() . 'followup_activity.action_type', $action_type);
        $this->db->where('DATE(' . db_prefix() . 'followup_activity.created_at) = "' . $today . '"', null, false);

        $visibility = get_followup_case_visibility_where();
        if ($visibility) {
            $this->_apply_visibility();
        }

        return (int) $this->db->count_all_results();
    }

    /**
     * =========================================================================
     * PERFORMANCE DASHBOARD (Phase 7)
     * =========================================================================
     *
     * Access-control note that applies to every method below: each staff-
     * scoped query combines an explicit `assigned_staff_id = $staff_id` (or
     * `created_by = $staff_id`) filter WITH the existing
     * get_followup_case_visibility_where() scope, not staff_id alone. This
     * is deliberate, not redundant: for a Telecaller (whose visibility scope
     * already collapses to "assigned_staff_id = viewer"), passing a
     * DIFFERENT staff's id here yields zero rows automatically, because the
     * two conditions can never both match. That is what stops a Telecaller
     * from viewing a colleague's performance by guessing their staff id -
     * no separate permission check is needed, the same visibility rule
     * already used everywhere else in this module does it for free.
     */

    /**
     * Which staff ids the current viewer's Leaderboard/Staff Comparison
     * chart should include - Admin: everyone active; Manager: their
     * department(s)' staff (get_business_department_staff(), already
     * built); Telecaller: just themselves.
     *
     * @return array staff ids
     */
    /**
     * @param mixed $department_filter when given, additionally narrows the
     *                                 result to this one department - used
     *                                 by the Performance Dashboard's own
     *                                 Department filter (on top of, not
     *                                 instead of, the visibility scope: a
     *                                 Manager selecting a department outside
     *                                 their own still only sees their own).
     * @return array staff ids
     */
    private function get_performance_staff_ids($department_filter = null)
    {
        if (is_admin()) {
            $this->load->model('staff_model');
            $staff_ids = array_column($this->staff_model->get('', ['active' => 1]), 'staffid');

            if ($department_filter) {
                $staff_ids = array_intersect($staff_ids, array_column(get_business_department_staff($department_filter), 'staffid'));
            }

            return array_values($staff_ids);
        }

        if (staff_can('view_department', 'follow_up_management')) {
            $department_ids = array_column(get_staff_business_departments(get_staff_user_id()), 'id');

            if ($department_filter) {
                $department_ids = array_intersect($department_ids, [(int) $department_filter]);
            }

            $staff_ids = [];
            foreach ($department_ids as $department_id) {
                foreach (get_business_department_staff($department_id) as $member) {
                    $staff_ids[$member['staffid']] = true;
                }
            }

            return array_keys($staff_ids);
        }

        return [get_staff_user_id()];
    }

    /**
     * First department a staff belongs to, for Leaderboard display only.
     *
     * @param mixed $staff_id
     * @return string
     */
    private function get_staff_primary_department_name($staff_id)
    {
        $departments = get_staff_business_departments($staff_id);

        return $departments ? $departments[0]['name'] : '';
    }

    /**
     * The 12 Performance KPIs for one staff member, all respecting
     * get_followup_case_visibility_where() (see class-level note above).
     * "Total/Open/Overdue/High Priority Cases" and "Average Response/
     * Closure Time" are scoped by case assignment (assigned_staff_id);
     * "Calls Logged/Meetings Scheduled/Reminders Created/Follow-ups
     * Completed/Cases Reopened" are scoped by who actually performed the
     * action (created_by) - these can differ (e.g. an Admin logging a call
     * on a case assigned to someone else), which is the correct meaning of
     * "performance" (what did THIS person do), not just case ownership.
     *
     * @param mixed       $staff_id
     * @param string|null $date_from Y-m-d
     * @param string|null $date_to   Y-m-d
     * @return array
     */
    public function get_staff_performance($staff_id, $date_from = null, $date_to = null, $priority = null, $status = null)
    {
        // The Priority/Case Status filters only narrow "Total Cases
        // Assigned" - the one KPI here without an intrinsic status/priority
        // meaning of its own. Every other KPI already has a fixed meaning
        // (Open Cases IS case_status=open, High Priority Cases IS priority
        // IN (high,urgent)) that an external Priority/Case Status filter
        // would either contradict or duplicate, so they are deliberately
        // left as-is rather than double-filtered.
        $assigned_where = [];
        if ($priority) {
            $assigned_where['priority'] = $priority;
        }
        if ($status) {
            $assigned_where['status'] = $status;
        }

        return [
            'staff_id'             => $staff_id,
            'total_cases_assigned' => $this->count_staff_cases($staff_id, $date_from, $date_to, $assigned_where),
            'total_cases_closed'   => $this->count_staff_cases($staff_id, $date_from, $date_to, ['case_status' => 'closed']),
            'open_cases'           => $this->count_staff_cases($staff_id, $date_from, $date_to, ['case_status' => 'open']),
            'overdue_cases'        => $this->count_staff_cases($staff_id, $date_from, $date_to, ['case_status' => 'open'], 'next_follow_up_date IS NOT NULL AND next_follow_up_date < "' . date('Y-m-d H:i:s') . '"'),
            'high_priority_cases'  => $this->count_staff_cases($staff_id, $date_from, $date_to, ['case_status' => 'open'], 'priority IN ("high","urgent")'),
            'avg_response_time'    => $this->get_average_response_time_seconds($staff_id, $date_from, $date_to),
            'avg_closure_time'     => $this->get_average_closure_time_seconds($staff_id, $date_from, $date_to),
            'calls_logged'         => $this->count_staff_activity($staff_id, 'call', $date_from, $date_to),
            'meetings_scheduled'   => $this->count_staff_activity($staff_id, 'meeting', $date_from, $date_to),
            'reminders_created'    => $this->count_staff_activity($staff_id, 'reminder', $date_from, $date_to),
            'followups_completed'  => $this->count_staff_case_activity($staff_id, 'case_closed', $date_from, $date_to),
            'cases_reopened'       => $this->count_staff_case_activity($staff_id, 'case_reopened', $date_from, $date_to),
        ];
    }

    /**
     * Top Performers - one get_staff_performance() call per staff id in
     * get_performance_staff_ids(), sorted by Cases Closed. No separate
     * "leaderboard query" duplicating the per-staff KPI logic.
     *
     * @param string|null $date_from
     * @param string|null $date_to
     * @param int         $limit
     * @return array
     */
    /**
     * Single entry point for the Performance Dashboard's summary KPI cards
     * + Leaderboard - both are derived from ONE underlying fetch of every
     * in-scope staff member's performance (get_all_staff_performance_rows()),
     * not two separate query passes, per the explicit "do not duplicate
     * queries" instruction.
     *
     * @param string|null $date_from
     * @param string|null $date_to
     * @param mixed       $department_filter
     * @param string|null $priority
     * @param string|null $status
     * @param int         $limit
     * @return array ['summary' => [...], 'leaderboard' => [...]]
     */
    public function get_performance_overview($date_from = null, $date_to = null, $department_filter = null, $priority = null, $status = null, $limit = 10)
    {
        $rows = $this->get_all_staff_performance_rows($date_from, $date_to, $department_filter, $priority, $status);

        return [
            'summary'     => $this->summarize_performance_rows($rows),
            'leaderboard' => array_slice($rows, 0, $limit),
        ];
    }

    private function summarize_performance_rows($rows)
    {
        $sum_keys = [
            'total_cases_assigned', 'total_cases_closed', 'open_cases', 'overdue_cases',
            'high_priority_cases', 'calls_logged', 'meetings_scheduled', 'reminders_created',
            'followups_completed', 'cases_reopened',
        ];
        $summary = array_fill_keys($sum_keys, 0);

        $response_times = [];
        $closure_times  = [];

        foreach ($rows as $row) {
            foreach ($sum_keys as $key) {
                $summary[$key] += $row[$key];
            }
            if ($row['avg_response_time'] !== null) {
                $response_times[] = $row['avg_response_time'];
            }
            if ($row['avg_closure_time'] !== null) {
                $closure_times[] = $row['avg_closure_time'];
            }
        }

        $summary['avg_response_time'] = $response_times ? (int) round(array_sum($response_times) / count($response_times)) : null;
        $summary['avg_closure_time']  = $closure_times ? (int) round(array_sum($closure_times) / count($closure_times)) : null;

        return $summary;
    }

    /**
     * One get_staff_performance() call per in-scope staff id - the single
     * shared data source for get_leaderboard() and get_performance_summary(),
     * so selecting a page of the leaderboard and computing the page-level
     * totals never runs the underlying per-staff queries twice.
     */
    private function get_all_staff_performance_rows($date_from, $date_to, $department_filter, $priority, $status)
    {
        $rows = [];

        foreach ($this->get_performance_staff_ids($department_filter) as $staff_id) {
            $perf                    = $this->get_staff_performance($staff_id, $date_from, $date_to, $priority, $status);
            $perf['department_name'] = $this->get_staff_primary_department_name($staff_id);
            $rows[]                  = $perf;
        }

        usort($rows, function ($a, $b) {
            return $b['total_cases_closed'] <=> $a['total_cases_closed'];
        });

        return $rows;
    }

    /**
     * Staff Detail drill-down: the KPI summary plus Daily/Weekly/Monthly
     * Activity charts (staff-scoped variants of the Dashboard's own
     * charts), the three case buckets, and a Timeline Summary (this
     * staff's own logged interactions, reusing get_history()-style
     * ordering - not a separate timeline implementation).
     *
     * @param mixed $staff_id
     * @return array|null null if this viewer cannot see this staff (see
     *                     class-level access-control note above)
     */
    public function get_staff_detail($staff_id)
    {
        $performance = $this->get_staff_performance($staff_id);

        // A Telecaller querying someone else's id gets every count at 0 -
        // that alone isn't a reliable "not allowed" signal (a real 0 looks
        // identical). This confirms the staff is genuinely in scope before
        // returning anything, by re-running the same visibility-scoped
        // case lookup the KPIs already use.
        $in_scope = $this->count_staff_cases($staff_id, null, null) > 0
            || in_array($staff_id, $this->get_performance_staff_ids(), false);

        if (!$in_scope) {
            return null;
        }

        return [
            'performance'      => $performance,
            'daily_activity'   => $this->get_chart_daily_activity(7, $staff_id),
            'weekly_activity'  => $this->get_chart_weekly_productivity(8, $staff_id),
            'monthly_activity' => $this->get_chart_monthly_productivity(6, $staff_id),
            'open_cases'       => $this->get_staff_cases($staff_id, 'open'),
            'closed_cases'     => $this->get_staff_cases($staff_id, 'closed'),
            'overdue_cases'    => $this->get_staff_cases($staff_id, 'open', true),
            'timeline_summary' => $this->get_staff_timeline_summary($staff_id),
        ];
    }

    /**
     * @param mixed  $staff_id
     * @param string $case_status
     * @param bool   $overdue_only
     * @return array
     */
    private function get_staff_cases($staff_id, $case_status, $overdue_only = false)
    {
        $this->db->where('assigned_staff_id', $staff_id);
        $this->db->where('case_status', $case_status);

        if ($overdue_only) {
            $this->db->where('next_follow_up_date IS NOT NULL', null, false);
            $this->db->where('next_follow_up_date <', date('Y-m-d H:i:s'));
        }

        $visibility = get_followup_case_visibility_where();
        if ($visibility) {
            $this->_apply_visibility();
        }

        $this->db->order_by('last_activity_at', 'desc');
        $this->db->limit(20);

        return $this->db->get(db_prefix() . 'followups')->result();
    }

    /**
     * This staff's own recent Timeline entries across every case they
     * touched, reusing tblfollowup_history (the same table get_history()
     * reads) rather than a separate log.
     *
     * @param mixed $staff_id
     * @param int   $limit
     * @return array
     */
    private function get_staff_timeline_summary($staff_id, $limit = 15)
    {
        $this->db->select(db_prefix() . 'followup_history.*');
        $this->db->from(db_prefix() . 'followup_history');
        $this->db->join(db_prefix() . 'followups', db_prefix() . 'followups.id = ' . db_prefix() . 'followup_history.followup_id');
        $this->db->where(db_prefix() . 'followup_history.created_by', $staff_id);

        $visibility = get_followup_case_visibility_where();
        if ($visibility) {
            $this->_apply_visibility();
        }

        $this->db->order_by(db_prefix() . 'followup_history.event_datetime', 'desc');
        $this->db->limit($limit);

        return $this->db->get()->result();
    }

    /**
     * @param mixed       $staff_id
     * @param string|null $date_from
     * @param string|null $date_to
     * @param array       $where_equals
     * @param string      $extra_raw
     * @return int
     */
    private function count_staff_cases($staff_id, $date_from, $date_to, $where_equals = [], $extra_raw = '')
    {
        $this->db->where('assigned_staff_id', $staff_id);

        if ($date_from) {
            $this->db->where('date_created >=', $date_from . ' 00:00:00');
        }

        if ($date_to) {
            $this->db->where('date_created <=', $date_to . ' 23:59:59');
        }

        if (!empty($where_equals)) {
            $this->db->where($where_equals);
        }

        if ($extra_raw) {
            $this->db->where($extra_raw, null, false);
        }

        $visibility = get_followup_case_visibility_where();
        if ($visibility) {
            $this->_apply_visibility();
        }

        return (int) $this->db->count_all_results(db_prefix() . 'followups');
    }

    /**
     * Count of Timeline entries of one type PERFORMED BY $staff_id
     * (created_by), not just belonging to their cases - see the
     * class-level note on the assigned_staff_id vs created_by distinction.
     *
     * @param mixed       $staff_id
     * @param string      $activity_type
     * @param string|null $date_from
     * @param string|null $date_to
     * @return int
     */
    private function count_staff_activity($staff_id, $activity_type, $date_from, $date_to)
    {
        $this->db->from(db_prefix() . 'followup_history');
        $this->db->join(db_prefix() . 'followups', db_prefix() . 'followups.id = ' . db_prefix() . 'followup_history.followup_id');
        $this->db->where(db_prefix() . 'followup_history.activity_type', $activity_type);
        $this->db->where(db_prefix() . 'followup_history.created_by', $staff_id);

        if ($date_from) {
            $this->db->where(db_prefix() . 'followup_history.created_at >=', $date_from . ' 00:00:00');
        }

        if ($date_to) {
            $this->db->where(db_prefix() . 'followup_history.created_at <=', $date_to . ' 23:59:59');
        }

        $visibility = get_followup_case_visibility_where();
        if ($visibility) {
            $this->_apply_visibility();
        }

        return (int) $this->db->count_all_results();
    }

    /**
     * Same as count_staff_activity() but against tblfollowup_activity (the
     * Activity Log) - used for "Follow-ups Completed" (case_closed actions
     * performed by this staff) and "Cases Reopened" (case_reopened).
     *
     * @param mixed       $staff_id
     * @param string      $action_type
     * @param string|null $date_from
     * @param string|null $date_to
     * @return int
     */
    private function count_staff_case_activity($staff_id, $action_type, $date_from, $date_to)
    {
        $this->db->from(db_prefix() . 'followup_activity');
        $this->db->join(db_prefix() . 'followups', db_prefix() . 'followups.id = ' . db_prefix() . 'followup_activity.followup_id');
        $this->db->where(db_prefix() . 'followup_activity.action_type', $action_type);
        $this->db->where(db_prefix() . 'followup_activity.created_by', $staff_id);

        if ($date_from) {
            $this->db->where(db_prefix() . 'followup_activity.created_at >=', $date_from . ' 00:00:00');
        }

        if ($date_to) {
            $this->db->where(db_prefix() . 'followup_activity.created_at <=', $date_to . ' 23:59:59');
        }

        $visibility = get_followup_case_visibility_where();
        if ($visibility) {
            $this->_apply_visibility();
        }

        return (int) $this->db->count_all_results();
    }

    /**
     * Average seconds between opened_at and closed_at for closed cases,
     * optionally staff/date scoped - the Average Closure Time KPI.
     *
     * @param mixed       $staff_id
     * @param string|null $date_from
     * @param string|null $date_to
     * @return int|null
     */
    private function get_average_closure_time_seconds($staff_id, $date_from = null, $date_to = null)
    {
        $this->db->select('opened_at, closed_at');
        $this->db->where('case_status', 'closed');
        $this->db->where('closed_at IS NOT NULL', null, false);
        $this->db->where('opened_at IS NOT NULL', null, false);

        if ($staff_id) {
            $this->db->where('assigned_staff_id', $staff_id);
        }

        if ($date_from) {
            $this->db->where('closed_at >=', $date_from . ' 00:00:00');
        }

        if ($date_to) {
            $this->db->where('closed_at <=', $date_to . ' 23:59:59');
        }

        $visibility = get_followup_case_visibility_where();
        if ($visibility) {
            $this->_apply_visibility();
        }

        $rows = $this->db->get(db_prefix() . 'followups')->result();

        if (empty($rows)) {
            return null;
        }

        $total = 0;
        foreach ($rows as $row) {
            $total += strtotime($row->closed_at) - strtotime($row->opened_at);
        }

        return (int) round($total / count($rows));
    }

    /**
     * Weekly Productivity (Line/Bar) - Timeline entry count per week, last
     * $weeks weeks, optionally staff-scoped. Same bucketing idea as
     * build_day_buckets()/get_chart_monthly_closure_trend()'s month
     * buckets, at week granularity.
     *
     * @param int   $weeks
     * @param mixed $staff_id
     * @return array Chart.js data
     */
    public function get_chart_weekly_productivity($weeks = 8, $staff_id = null)
    {
        $labels     = [];
        $week_starts = [];

        for ($i = $weeks - 1; $i >= 0; $i--) {
            $start         = date('Y-m-d', strtotime('monday this week -' . $i . ' weeks'));
            $week_starts[] = $start;
            $labels[]      = _l('week_of') . ' ' . date('M j', strtotime($start));
        }

        $this->db->select('YEARWEEK(' . db_prefix() . 'followup_history.event_datetime, 3) as yweek, COUNT(*) as total', false);
        $this->db->from(db_prefix() . 'followup_history');
        $this->db->join(db_prefix() . 'followups', db_prefix() . 'followups.id = ' . db_prefix() . 'followup_history.followup_id');
        $this->db->where(db_prefix() . 'followup_history.event_datetime >=', $week_starts[0] . ' 00:00:00');

        if ($staff_id) {
            $this->db->where(db_prefix() . 'followup_history.created_by', $staff_id);
        }

        $visibility = get_followup_case_visibility_where();
        if ($visibility) {
            $this->_apply_visibility();
        }

        $this->db->group_by('YEARWEEK(' . db_prefix() . 'followup_history.event_datetime, 3)');
        $rows = $this->db->get()->result();

        $by_week = [];
        foreach ($rows as $row) {
            $by_week[$row->yweek] = (int) $row->total;
        }

        $data = [];
        foreach ($week_starts as $start) {
            // 'o' (ISO-8601 week-numbering year), not 'Y' (calendar year) -
            // they diverge for the last/first few days of December/January,
            // and only 'o' matches MySQL's YEARWEEK(date, 3) mode used in
            // the query above for every week, not just mid-year ones.
            $yweek  = date('o', strtotime($start)) . str_pad((string) (int) date('W', strtotime($start)), 2, '0', STR_PAD_LEFT);
            $data[] = $by_week[$yweek] ?? 0;
        }

        return [
            'labels'   => $labels,
            'datasets' => [[
                'label'           => _l('chart_weekly_productivity'),
                'data'            => $data,
                'backgroundColor' => '#7cb342',
            ]],
        ];
    }

    /**
     * Monthly Productivity (Line/Bar) - Timeline entry count per month,
     * last $months months, optionally staff-scoped. Same month-bucketing
     * pattern as get_chart_monthly_closure_trend(), counting all activity
     * instead of just closures.
     *
     * @param int   $months
     * @param mixed $staff_id
     * @return array Chart.js data
     */
    public function get_chart_monthly_productivity($months = 6, $staff_id = null)
    {
        $labels     = [];
        $month_keys = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $ts           = strtotime('-' . $i . ' months');
            $month_keys[] = date('Y-m', $ts);
            $labels[]     = date('M Y', $ts);
        }

        $this->db->select('DATE_FORMAT(' . db_prefix() . 'followup_history.event_datetime, "%Y-%m") as month, COUNT(*) as total', false);
        $this->db->from(db_prefix() . 'followup_history');
        $this->db->join(db_prefix() . 'followups', db_prefix() . 'followups.id = ' . db_prefix() . 'followup_history.followup_id');
        $this->db->where(db_prefix() . 'followup_history.event_datetime >=', date('Y-m-01', strtotime('-' . ($months - 1) . ' months')));

        if ($staff_id) {
            $this->db->where(db_prefix() . 'followup_history.created_by', $staff_id);
        }

        $visibility = get_followup_case_visibility_where();
        if ($visibility) {
            $this->_apply_visibility();
        }

        $this->db->group_by('DATE_FORMAT(' . db_prefix() . 'followup_history.event_datetime, "%Y-%m")');
        $rows = $this->db->get()->result();

        $by_month = [];
        foreach ($rows as $row) {
            $by_month[$row->month] = (int) $row->total;
        }

        $data = [];
        foreach ($month_keys as $key) {
            $data[] = $by_month[$key] ?? 0;
        }

        return [
            'labels'   => $labels,
            'datasets' => [[
                'label'           => _l('chart_monthly_productivity'),
                'data'            => $data,
                'borderColor'     => '#0288d1',
                'backgroundColor' => 'rgba(2,136,209,0.15)',
                'fill'            => true,
            ]],
        ];
    }

    /**
     * Calls vs Meetings (Bar/Pie) - simple two-value comparison, optionally
     * staff-scoped, last 30 days.
     *
     * @param mixed $staff_id
     * @return array Chart.js data
     */
    public function get_chart_calls_vs_meetings($staff_id = null)
    {
        $since = date('Y-m-d', strtotime('-30 days'));

        $calls    = $this->count_history_since('call', $since, $staff_id);
        $meetings = $this->count_history_since('meeting', $since, $staff_id);

        return [
            'labels'   => [_l('followup_type_call'), _l('followup_type_meeting')],
            'datasets' => [[
                'data'                 => [$calls, $meetings],
                'backgroundColor'      => ['#03a9f4', '#8e24aa'],
                'hoverBackgroundColor' => ['#0288d1', '#6a1b7a'],
            ]],
        ];
    }

    private function count_history_since($activity_type, $since, $staff_id = null)
    {
        $this->db->from(db_prefix() . 'followup_history');
        $this->db->join(db_prefix() . 'followups', db_prefix() . 'followups.id = ' . db_prefix() . 'followup_history.followup_id');
        $this->db->where(db_prefix() . 'followup_history.activity_type', $activity_type);
        $this->db->where(db_prefix() . 'followup_history.event_datetime >=', $since . ' 00:00:00');

        if ($staff_id) {
            $this->db->where(db_prefix() . 'followup_history.created_by', $staff_id);
        }

        $visibility = get_followup_case_visibility_where();
        if ($visibility) {
            $this->_apply_visibility();
        }

        return (int) $this->db->count_all_results();
    }

    /**
     * Case Status Trend (Stacked Bar/Line) - cases CREATED per week over
     * the last $weeks weeks, broken down by their CURRENT status. Honest
     * simplification, documented rather than silently assumed: this module
     * has no historical status-snapshot table, so a true "status as of
     * each week" trend isn't derivable - this shows creation volume by
     * today's status instead, which is still a useful trend view.
     *
     * @param int $weeks
     * @return array Chart.js data
     */
    public function get_chart_case_status_trend($weeks = 8)
    {
        $labels      = [];
        $week_starts = [];

        for ($i = $weeks - 1; $i >= 0; $i--) {
            $start         = date('Y-m-d', strtotime('monday this week -' . $i . ' weeks'));
            $week_starts[] = $start;
            $labels[]      = date('M j', strtotime($start));
        }

        $statuses = get_followup_statuses();
        $colors   = get_system_favourite_colors();
        $datasets = [];
        $i        = 0;

        foreach ($statuses as $key => $status_label) {
            $data = [];

            foreach ($week_starts as $idx => $start) {
                $end    = date('Y-m-d', strtotime($start . ' +6 days'));
                $data[] = $this->count_cases_created_between($start, $end, $key);
            }

            $datasets[] = [
                'label'           => $status_label,
                'data'            => $data,
                'backgroundColor' => $colors[$i % count($colors)],
            ];
            $i++;
        }

        return [
            'labels'   => $labels,
            'datasets' => $datasets,
        ];
    }

    private function count_cases_created_between($start, $end, $status)
    {
        $this->db->where('status', $status);
        $this->db->where('date_created >=', $start . ' 00:00:00');
        $this->db->where('date_created <=', $end . ' 23:59:59');

        $visibility = get_followup_case_visibility_where();
        if ($visibility) {
            $this->_apply_visibility();
        }

        return (int) $this->db->count_all_results(db_prefix() . 'followups');
    }

    /**
     * Staff Performance Comparison (Bar) - one bar per staff in
     * get_performance_staff_ids(), reusing get_staff_performance() rather
     * than a separate comparison query. Value = Total Cases Closed.
     *
     * @param string|null $date_from
     * @param string|null $date_to
     * @return array Chart.js data
     */
    public function get_chart_staff_comparison($date_from = null, $date_to = null)
    {
        $colors = get_system_favourite_colors();
        $chart  = [
            'labels'   => [],
            'datasets' => [[
                'label'           => _l('card_total_cases_closed'),
                'data'            => [],
                'backgroundColor' => [],
            ]],
        ];

        $i = 0;
        foreach ($this->get_performance_staff_ids() as $staff_id) {
            $perf                            = $this->get_staff_performance($staff_id, $date_from, $date_to);
            $chart['labels'][]               = get_staff_full_name($staff_id);
            $chart['datasets'][0]['data'][]  = $perf['total_cases_closed'];
            $chart['datasets'][0]['backgroundColor'][] = $colors[$i % count($colors)];
            $i++;
        }

        return $chart;
    }

    /**
     * Average Response Time Trend (Line) - average response time computed
     * separately per week, last $weeks weeks, reusing
     * get_average_response_time_seconds()'s date-range parameters rather
     * than a new aggregate implementation.
     *
     * @param int $weeks
     * @return array Chart.js data
     */
    public function get_chart_avg_response_time_trend($weeks = 8)
    {
        $labels = [];
        $data   = [];

        for ($i = $weeks - 1; $i >= 0; $i--) {
            $start    = date('Y-m-d', strtotime('monday this week -' . $i . ' weeks'));
            $end      = date('Y-m-d', strtotime($start . ' +6 days'));
            $labels[] = date('M j', strtotime($start));
            $seconds  = $this->get_average_response_time_seconds(null, $start, $end);
            $data[]   = $seconds !== null ? round($seconds / 3600, 2) : 0;
        }

        return [
            'labels'   => $labels,
            'datasets' => [[
                'label'           => _l('chart_avg_response_time_trend') . ' (' . _l('hours') . ')',
                'data'            => $data,
                'borderColor'     => '#fb8c00',
                'backgroundColor' => 'rgba(251,140,0,0.15)',
                'fill'            => true,
            ]],
        ];
    }

    /**
     * =========================================================================
     * REPORTS (Phase 8) - only three genuinely new query methods; every
     * other report reuses a Phase 3/4/7 method as-is (Case Summary reuses
     * get_performance_overview()'s summary, Staff Performance reuses its
     * leaderboard, Productivity reuses the existing daily/weekly/monthly
     * chart methods, Executive Dashboard composes several existing methods
     * from the controller). See Reports.php/Reports_model.php for how each
     * report action calls into these.
     * =========================================================================
     */

    /**
     * Department Performance Report - Cases Assigned/Closed/Open/Overdue
     * and Average Closure Time per department, visibility-scoped like every
     * other report in this module.
     *
     * @param string|null $date_from
     * @param string|null $date_to
     * @return array
     */
    public function get_department_performance_report($date_from = null, $date_to = null)
    {
        $rows = [];

        foreach (get_business_departments() as $department) {
            $department_id = $department['id'];

            $rows[] = [
                'department_id'     => $department_id,
                'department_name'   => $department['name'],
                'cases_assigned'    => $this->count_department_cases($department_id, $date_from, $date_to),
                'cases_closed'      => $this->count_department_cases($department_id, $date_from, $date_to, ['case_status' => 'closed']),
                'open_cases'        => $this->count_department_cases($department_id, $date_from, $date_to, ['case_status' => 'open']),
                'overdue_cases'     => $this->count_department_cases($department_id, $date_from, $date_to, ['case_status' => 'open'], 'next_follow_up_date IS NOT NULL AND next_follow_up_date < "' . date('Y-m-d H:i:s') . '"'),
                'avg_closure_time'  => $this->get_department_avg_closure_time($department_id, $date_from, $date_to),
            ];
        }

        return $rows;
    }

    private function count_department_cases($department_id, $date_from, $date_to, $where_equals = [], $extra_raw = '')
    {
        $this->db->where('department_id', $department_id);

        if ($date_from) {
            $this->db->where('date_created >=', $date_from . ' 00:00:00');
        }

        if ($date_to) {
            $this->db->where('date_created <=', $date_to . ' 23:59:59');
        }

        if (!empty($where_equals)) {
            $this->db->where($where_equals);
        }

        if ($extra_raw) {
            $this->db->where($extra_raw, null, false);
        }

        $visibility = get_followup_case_visibility_where();
        if ($visibility) {
            $this->_apply_visibility();
        }

        return (int) $this->db->count_all_results(db_prefix() . 'followups');
    }

    private function get_department_avg_closure_time($department_id, $date_from = null, $date_to = null)
    {
        $this->db->select('opened_at, closed_at');
        $this->db->where('department_id', $department_id);
        $this->db->where('case_status', 'closed');
        $this->db->where('closed_at IS NOT NULL', null, false);
        $this->db->where('opened_at IS NOT NULL', null, false);

        if ($date_from) {
            $this->db->where('closed_at >=', $date_from . ' 00:00:00');
        }

        if ($date_to) {
            $this->db->where('closed_at <=', $date_to . ' 23:59:59');
        }

        $visibility = get_followup_case_visibility_where();
        if ($visibility) {
            $this->_apply_visibility();
        }

        $rows = $this->db->get(db_prefix() . 'followups')->result();

        if (empty($rows)) {
            return null;
        }

        $total = 0;
        foreach ($rows as $row) {
            $total += strtotime($row->closed_at) - strtotime($row->opened_at);
        }

        return (int) round($total / count($rows));
    }

    /**
     * Activity Report - a single chronological feed merging
     * tblfollowup_history (Calls/Meetings/Reminders/Notes) and
     * tblfollowup_activity (Status Changes/Priority Changes/Case Transfers/
     * Closures/Reopens) via one SQL UNION, rather than querying each table
     * separately and merge-sorting in PHP. Visibility-scoped through the
     * join back to tblfollowups on both sides of the union.
     *
     * @param string|null $date_from
     * @param string|null $date_to
     * @param mixed       $department_filter
     * @param mixed       $staff_filter
     * @param int         $limit
     * @return array
     */
    public function get_activity_report($date_from = null, $date_to = null, $department_filter = null, $staff_filter = null, $limit = 500)
    {
        $visibility = get_followup_case_visibility_where('f');
        $visibility_sql = $visibility ? ' ' . $visibility : '';

        $extra = '';
        if ($department_filter) {
            $extra .= ' AND f.department_id = ' . (int) $department_filter;
        }
        if ($staff_filter) {
            $extra .= ' AND f.assigned_staff_id = ' . (int) $staff_filter;
        }

        $date_sql_history  = '';
        $date_sql_activity = '';
        if ($date_from) {
            $date_sql_history  .= ' AND h.event_datetime >= "' . $this->db->escape_str($date_from) . ' 00:00:00"';
            $date_sql_activity .= ' AND a.created_at >= "' . $this->db->escape_str($date_from) . ' 00:00:00"';
        }
        if ($date_to) {
            $date_sql_history  .= ' AND h.event_datetime <= "' . $this->db->escape_str($date_to) . ' 23:59:59"';
            $date_sql_activity .= ' AND a.created_at <= "' . $this->db->escape_str($date_to) . ' 23:59:59"';
        }

        $limit = (int) $limit;

        $sql = '
            (SELECT h.followup_id, "history" as source, h.activity_type as type, h.notes as description, h.event_datetime as event_time, h.created_by
             FROM ' . db_prefix() . 'followup_history h
             JOIN ' . db_prefix() . 'followups f ON f.id = h.followup_id
             WHERE 1=1' . $extra . $visibility_sql . $date_sql_history . ')
            UNION ALL
            (SELECT a.followup_id, "activity" as source, a.action_type as type, a.description as description, a.created_at as event_time, a.created_by
             FROM ' . db_prefix() . 'followup_activity a
             JOIN ' . db_prefix() . 'followups f ON f.id = a.followup_id
             WHERE 1=1' . $extra . $visibility_sql . $date_sql_activity . ')
            ORDER BY event_time DESC
            LIMIT ' . $limit;

        return $this->db->query($sql)->result();
    }

    /**
     * SLA Report - fixed default thresholds (Response: 24h, Closure: 72h),
     * since this module has no existing configurable SLA setting to reuse
     * and building one would be new workflow, which Phase 8 explicitly
     * rules out. Documented here rather than silently assumed; a real SLA
     * configuration UI would be a reasonable future addition, not built now.
     *
     * @param string|null $date_from
     * @param string|null $date_to
     * @return array
     */
    public function get_sla_report($date_from = null, $date_to = null)
    {
        $response_sla_seconds = 24 * 3600;
        $closure_sla_seconds  = 72 * 3600;

        $this->db->select('opened_at, closed_at');
        $this->db->where('case_status', 'closed');
        $this->db->where('closed_at IS NOT NULL', null, false);
        $this->db->where('opened_at IS NOT NULL', null, false);

        if ($date_from) {
            $this->db->where('closed_at >=', $date_from . ' 00:00:00');
        }
        if ($date_to) {
            $this->db->where('closed_at <=', $date_to . ' 23:59:59');
        }

        $visibility = get_followup_case_visibility_where();
        if ($visibility) {
            $this->_apply_visibility();
        }

        $closed_rows = $this->db->get(db_prefix() . 'followups')->result();

        $within_sla = 0;
        $outside_sla = 0;
        foreach ($closed_rows as $row) {
            $closure_seconds = strtotime($row->closed_at) - strtotime($row->opened_at);
            if ($closure_seconds <= $closure_sla_seconds) {
                $within_sla++;
            } else {
                $outside_sla++;
            }
        }

        $open_count    = $this->count_cases(['case_status' => 'open'], $date_from ? 'date_created >= "' . $date_from . ' 00:00:00"' : '');
        $overdue_count = $this->count_cases(['case_status' => 'open'], 'next_follow_up_date IS NOT NULL AND next_follow_up_date < "' . date('Y-m-d H:i:s') . '"');

        return [
            'avg_response_time'    => $this->get_average_response_time_seconds(null, $date_from, $date_to),
            'avg_closure_time'     => $this->get_average_closure_time_seconds(null, $date_from, $date_to),
            'response_sla_hours'   => 24,
            'closure_sla_hours'    => 72,
            'overdue_percent'      => $open_count > 0 ? round(($overdue_count / $open_count) * 100, 1) : 0.0,
            'within_sla'           => $within_sla,
            'outside_sla'          => $outside_sla,
            'total_closed'         => count($closed_rows),
        ];
    }

    /**
     * Shared query-filter application for count_cases()/get_cases_where() -
     * one place that combines a simple equality filter, an optional raw SQL
     * condition, and the Admin/Manager/Telecaller visibility scope, so
     * neither caller re-implements the visibility rule.
     *
     * @param array  $where_equals
     * @param string $extra_raw
     */
    private function apply_case_query_filters($where_equals = [], $extra_raw = '')
    {
        if (!empty($where_equals)) {
            $this->db->where($where_equals);
        }

        if ($extra_raw) {
            $this->db->where($extra_raw, null, false);
        }

        $visibility = get_followup_case_visibility_where();

        if ($visibility) {
            // get_followup_case_visibility_where() returns a data_tables_init()
            // -style "AND ..." fragment; the query builder's where() wants the
            // condition without that leading "AND ".
            $this->_apply_visibility();
        }
    }

    private function count_cases($where_equals = [], $extra_raw = '')
    {
        $this->apply_case_query_filters($where_equals, $extra_raw);

        return (int) $this->db->count_all_results(db_prefix() . 'followups');
    }

    private function get_cases_where($where_equals, $extra_raw, $order_by, $order_dir)
    {
        $this->apply_case_query_filters($where_equals, $extra_raw);
        $this->db->order_by($order_by, $order_dir);

        return $this->db->get(db_prefix() . 'followups')->result();
    }

    /**
     * Log Call / Schedule Meeting (incl. the Telecaller Workspace's
     * Appointment section, which is a 'meeting' with Location/Meeting
     * Type folded in - see below) / Schedule Reminder / Add Note - the
     * four Case Detail actions that append a Timeline entry. One shared
     * write path since all four only differ in which fields are required
     * and what gets stored (per the "avoid duplicate logic" instruction),
     * rather than four near-identical methods.
     *
     * Meeting/Reminder always sync a tblreminders row via the existing
     * sync_reminder() (same pipeline create_case_for_lead/add/edit already
     * use) - no separate calendar/notification code here.
     *
     * Every interaction also mirrors onto the Lead's own native activity
     * feed via Leads_model::log_lead_activity() (the Workspace's "Notes ->
     * Lead Activity -> Follow-up History" sync rule) - reusing the exact
     * mechanism already used for the Completion flow, just applied to
     * every activity type here instead of only closing transitions.
     *
     * @param mixed  $id
     * @param string $activity_type call|meeting|reminder|note
     * @param array  $data          outcome, notes, due_date, and (meeting
     *                              only) location, meeting_type - both
     *                              folded into the stored notes/reminder
     *                              description text, no schema change
     * @return bool
     */
    public function log_case_interaction($id, $activity_type, $data)
    {
        if (!in_array($activity_type, ['call', 'meeting', 'reminder', 'note'], true)) {
            return false;
        }

        $case = $this->get($id);

        if (!$case) {
            return false;
        }

        $now            = date('Y-m-d H:i:s');
        $notes          = isset($data['notes']) && $data['notes'] != '' ? nl2br($data['notes']) : null;
        $outcome        = !empty($data['outcome']) ? $data['outcome'] : null;
        $follow_up_type = $activity_type === 'call' ? 'call' : ($activity_type === 'meeting' ? 'meeting' : 'other');
        $due_date       = !empty($data['due_date']) ? to_sql_date($data['due_date'], true) : null;

        // Meeting/Reminder exist to schedule something - without a due date
        // there is nothing to sync into tblreminders/Calendar.
        if (in_array($activity_type, ['meeting', 'reminder'], true) && empty($due_date)) {
            return false;
        }

        // Appointment section (Section 7) fields - tblreminders/
        // tblfollowup_history have no location/meeting_type column and
        // don't need one; both are free-text already, so the detail is
        // folded directly into the notes shown here AND into the
        // reminder's own description (via sync_reminder()'s optional
        // extra-description param) so it's visible on the Calendar/
        // notification side too, not just in History/Notes.
        $meeting_detail = '';
        if ($activity_type === 'meeting') {
            if (!empty($data['meeting_type'])) {
                $meeting_types = get_followup_meeting_types();
                $meeting_detail .= _l('followup_appointment_meeting_type') . ': ' . ($meeting_types[$data['meeting_type']] ?? $data['meeting_type']) . "\n";
            }
            if (!empty($data['location'])) {
                $meeting_detail .= _l('followup_appointment_location') . ': ' . $data['location'] . "\n";
            }
            if ($meeting_detail !== '') {
                $notes = $notes ? (nl2br($meeting_detail) . $notes) : nl2br($meeting_detail);
            }
        }

        $reminder_id          = $case->reminder_id;
        $reminder_was_created = false;
        $update               = ['last_activity_at' => $now];

        if ($due_date) {
            $reminder_id          = $this->sync_reminder($id, $case->rel_id, $case->rel_type, $due_date, $case->assigned_staff_id ?: $case->staff_id, $case->reminder_id, $meeting_detail);
            $reminder_was_created = ($reminder_id != $case->reminder_id);
            $update['reminder_id']         = $reminder_id;
            $update['next_follow_up_date'] = $due_date;
        }

        if ($activity_type === 'call') {
            $update['follow_up_date'] = $now;

            if ($outcome) {
                $update['outcome'] = $outcome;
            }
        }

        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'followups', $update);

        $this->add_history_entry($id, [
            'activity_type'  => $activity_type,
            'follow_up_type' => $follow_up_type,
            'outcome'        => $outcome,
            'notes'          => $notes,
            'event_datetime' => $now,
            'created_by'     => get_staff_user_id(),
            'reminder_id'    => $due_date ? $reminder_id : null,
        ]);

        if ($activity_type === 'meeting') {
            $this->log_case_activity($id, 'meeting_created', 'Meeting scheduled for ' . _dt($due_date));

            $rel_data   = get_relation_data($case->rel_type, $case->rel_id);
            $rel_values = get_relation_values($rel_data, $case->rel_type);

            if ($reminder_was_created) {
                $this->notify_case_event($id, $case->assigned_staff_id, 'not_followup_meeting_scheduled', [$rel_values['name']], true, _l('followup_email_meeting_scheduled_title'), _l('followup_email_meeting_scheduled_body', [$rel_values['name'], _dt($due_date)]));
            } else {
                $this->notify_case_event($id, $case->assigned_staff_id, 'not_followup_meeting_updated', [$rel_values['name']], true, _l('followup_email_meeting_updated_title'), _l('followup_email_meeting_updated_body', [$rel_values['name'], _dt($due_date)]));
            }
        } elseif ($activity_type === 'reminder') {
            $this->log_case_activity($id, 'reminder_created', 'Reminder scheduled for ' . _dt($due_date));

            if ($reminder_was_created) {
                $rel_data   = get_relation_data($case->rel_type, $case->rel_id);
                $rel_values = get_relation_values($rel_data, $case->rel_type);
                $this->notify_case_event($id, $case->assigned_staff_id, 'not_followup_reminder_created', [$rel_values['name']]);
            }
        }

        if ($case->rel_type === 'lead') {
            $this->load->model('leads_model');
            $this->leads_model->log_lead_activity($case->rel_id, 'followup_lead_activity_interaction_logged', false, serialize([get_followup_history_types()[$activity_type] ?? $activity_type]));
        }

        hooks()->do_action('follow_up_history_added', ['id' => $id, 'activity_type' => $activity_type]);

        return true;
    }

    /**
     * Change Status action - updates the legacy per-attempt `status`
     * column and keeps case_status/closed_at in sync using the exact same
     * rule edit() already applies (open -> closed only; reopening is
     * always the explicit reopen_case() action, never implicit).
     *
     * Telecaller Workspace / Task Completion flow: this is the single
     * place every path that closes a Case funnels through - the Case
     * Detail page's own "Change Status" action, complete_case_for_lead()
     * (Lead converted), and the Workspace's Complete/Mark Pending/Cancel
     * quick actions. On a closing transition (completed|no_further_action)
     * it therefore also: completes the linked Task
     * (complete_linked_task()), cancels the now-pointless pending reminder
     * (cancel_reminder_if_pending(), same helper close_case() already
     * uses), and best-effort logs a note on the Lead's own activity feed
     * ("update Lead if required" - deliberately NOT a Lead status change;
     * the Lead's own status lifecycle stays an explicit, separate action
     * so it remains the single source of truth for itself).
     *
     * @param mixed  $id
     * @param string $new_status pending|completed|no_further_action
     * @return bool
     */
    public function change_case_status($id, $new_status)
    {
        if (!array_key_exists($new_status, get_followup_statuses())) {
            return false;
        }

        $case = $this->get($id);

        if (!$case) {
            return false;
        }

        $now         = date('Y-m-d H:i:s');
        $is_closing  = in_array($new_status, ['completed', 'no_further_action'], true) && $case->case_status !== 'closed';
        $update      = [
            'status'           => $new_status,
            'last_activity_at' => $now,
            'date_updated'     => $now,
        ];

        if ($is_closing) {
            $update['case_status'] = 'closed';
            $update['closed_at']   = $now;
        }

        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'followups', $update);

        $this->add_history_entry($id, [
            'activity_type'  => 'status_change',
            'notes'          => 'Status changed from ' . get_followup_status_label($case->status) . ' to ' . get_followup_status_label($new_status),
            'event_datetime' => $now,
            'created_by'     => get_staff_user_id(),
        ]);

        $this->log_case_activity($id, 'status_changed', 'Status changed', $case->status, $new_status);

        if ($is_closing) {
            // Precomputed BEFORE complete_linked_task() deliberately - see
            // the note below on why.
            $new_status_label = get_followup_status_label($new_status);

            $this->complete_linked_task($case);

            if ($case->reminder_id) {
                $this->cancel_reminder_if_pending($case->reminder_id, $id);
            }

            // Bug fix, confirmed live: Tasks_model::mark_as() (inside
            // complete_linked_task()) and notify_case_event() (inside
            // cancel_reminder_if_pending()) both notify staff, and Perfex's
            // notification path calls core's load_admin_language()
            // (application/helpers/admin_helper.php) to render that
            // notification in the recipient's own language - which
            // unconditionally does `$CI->lang->is_loaded = [];
            // $CI->lang->language = [];` and reloads ONLY the base
            // english_lang.php, silently dropping every custom module
            // language file (this one included) for the REST of the
            // request. Confirmed by reproducing: _l() calls made after
            // these two lines returned raw untranslated keys instead of
            // their English text, while identical calls made earlier in
            // the same request (or when this closing branch doesn't run,
            // e.g. status=pending) resolved correctly. Reloading here
            // fixes both this method's own remaining _l() calls below AND
            // the calling controller's _l('case_action_success') in its
            // JSON response, since both happen later in the same request.
            follow_up_management_load_language();

            if ($case->rel_type === 'lead') {
                $this->load->model('leads_model');
                $this->leads_model->log_lead_activity($case->rel_id, 'followup_lead_activity_completed', false, serialize([$new_status_label]));
            }
        }

        hooks()->do_action('follow_up_status_changed', ['id' => $id, 'old' => $case->status, 'new' => $new_status]);

        return true;
    }

    /**
     * Change Priority action - Activity Log only, no Timeline entry (not
     * one of the Timeline's supported types).
     *
     * @param mixed  $id
     * @param string $new_priority low|medium|high|urgent
     * @return bool
     */
    public function change_case_priority($id, $new_priority)
    {
        if (!array_key_exists($new_priority, get_followup_priorities())) {
            return false;
        }

        $case = $this->get($id);

        if (!$case) {
            return false;
        }

        if ($case->priority === $new_priority) {
            return true;
        }

        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'followups', [
            'priority'         => $new_priority,
            'last_activity_at' => date('Y-m-d H:i:s'),
        ]);

        $this->log_case_activity($id, 'priority_changed', 'Priority changed', $case->priority, $new_priority);

        // Rule 5 (Task half) - Case priority is this module's actual
        // priority source of truth (see sync_case_from_lead()'s docblock).
        if ($case->task_id) {
            $this->sync_task_priority($case->task_id, $new_priority);
        }

        if (in_array($new_priority, ['high', 'urgent'], true) && !in_array($case->priority, ['high', 'urgent'], true)) {
            $rel_data   = get_relation_data($case->rel_type, $case->rel_id);
            $rel_values = get_relation_values($rel_data, $case->rel_type);
            $this->notify_case_event(
                $id,
                $case->assigned_staff_id,
                'not_followup_case_high_priority',
                [$rel_values['name']],
                true,
                _l('followup_email_high_priority_title'),
                _l('followup_email_high_priority_body', [$rel_values['name']])
            );
        }

        return true;
    }

    /**
     * Reassign Staff action - Activity Log only.
     *
     * @param mixed $id
     * @param mixed $new_staff_id
     * @return bool
     */
    public function reassign_case($id, $new_staff_id)
    {
        $this->load->model('staff_model');
        $staff = $this->staff_model->get($new_staff_id);

        if (!$staff) {
            return false;
        }

        $case = $this->get($id);

        if (!$case) {
            return false;
        }

        if ($case->assigned_staff_id == $new_staff_id) {
            return true;
        }

        $old_staff_id = $case->assigned_staff_id;

        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'followups', [
            'assigned_staff_id' => $new_staff_id,
            'last_activity_at'  => date('Y-m-d H:i:s'),
        ]);

        $this->log_case_activity(
            $id,
            'reassigned',
            'Case reassigned to ' . get_staff_full_name($new_staff_id),
            $old_staff_id ? get_staff_full_name($old_staff_id) : null,
            get_staff_full_name($new_staff_id)
        );

        // Rule 3 (Task half) - keep the linked Task's assignee in step
        // with a manual Case reassignment too, not only a Lead-driven one
        // (sync_case_from_lead() handles that path).
        if ($case->task_id) {
            $this->sync_task_assignee($case->task_id, $new_staff_id, $old_staff_id);
        }

        $rel_data   = get_relation_data($case->rel_type, $case->rel_id);
        $rel_values = get_relation_values($rel_data, $case->rel_type);
        $this->notify_case_event(
            $id,
            $new_staff_id,
            'not_followup_case_reassigned',
            [$rel_values['name']],
            true,
            _l('followup_email_case_reassigned_title'),
            _l('followup_email_case_reassigned_body', [$rel_values['name']])
        );

        return true;
    }

    /**
     * Close Case action.
     *
     * @param mixed $id
     * @return bool
     */
    public function close_case($id)
    {
        $case = $this->get($id);

        if (!$case || $case->case_status === 'closed') {
            return false;
        }

        $now = date('Y-m-d H:i:s');
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'followups', [
            'case_status'      => 'closed',
            'closed_at'        => $now,
            'last_activity_at' => $now,
        ]);

        $this->log_case_activity($id, 'case_closed', 'Case closed', 'open', 'closed');

        // A closed case has nothing left to be reminded about - cancel any
        // pending reminder the same way edit() already does when a
        // follow-up date is cleared (reused, not duplicated). This also
        // fires the "Meeting Cancelled" notification if the pending
        // reminder was for a meeting.
        if ($case->reminder_id) {
            $this->cancel_reminder_if_pending($case->reminder_id, $id);
        }

        // Same rule change_case_status() applies on its own closing
        // transition - a closed Case's linked Task has no more work
        // behind it either.
        $this->complete_linked_task($case);

        $rel_data   = get_relation_data($case->rel_type, $case->rel_id);
        $rel_values = get_relation_values($rel_data, $case->rel_type);
        $this->notify_case_event($id, $case->assigned_staff_id, 'not_followup_case_closed', [$rel_values['name']]);

        // See change_case_status()'s docblock - notify_case_event()/
        // complete_linked_task() can silently wipe this module's loaded
        // language keys (core load_admin_language() behavior), affecting
        // the calling controller's own _l() calls later in this request.
        follow_up_management_load_language();

        return true;
    }

    /**
     * Reopen Case action.
     *
     * @param mixed $id
     * @return bool
     */
    public function reopen_case($id)
    {
        $case = $this->get($id);

        if (!$case || $case->case_status !== 'closed') {
            return false;
        }

        $now = date('Y-m-d H:i:s');
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'followups', [
            'case_status'      => 'open',
            'closed_at'        => null,
            'last_activity_at' => $now,
        ]);

        $this->log_case_activity($id, 'case_reopened', 'Case reopened', 'closed', 'open');

        $rel_data   = get_relation_data($case->rel_type, $case->rel_id);
        $rel_values = get_relation_values($rel_data, $case->rel_type);
        $this->notify_case_event(
            $id,
            $case->assigned_staff_id,
            'not_followup_case_reopened',
            [$rel_values['name']],
            true,
            _l('followup_email_case_reopened_title'),
            _l('followup_email_case_reopened_body', [$rel_values['name']])
        );

        return true;
    }

    /**
     * Appends one Timeline entry. Every write path that logs an interaction
     * (create_case_for_lead, add, edit) funnels through here so
     * tblfollowup_history stays complete from this phase forward, ahead of
     * the Case Detail Timeline UI landing in Phase 2.
     *
     * @param mixed $followup_id
     * @param array $data
     * @return int|false
     */
    private function add_history_entry($followup_id, $data)
    {
        $now = date('Y-m-d H:i:s');

        $insert = [
            'followup_id'    => $followup_id,
            'activity_type'  => $data['activity_type'] ?? 'follow_up',
            'follow_up_type' => $data['follow_up_type'] ?? null,
            'outcome'        => $data['outcome'] ?? null,
            'notes'          => $data['notes'] ?? null,
            'event_datetime' => $data['event_datetime'] ?? $now,
            'created_by'     => $data['created_by'] ?? get_staff_user_id(),
            'reminder_id'    => $data['reminder_id'] ?? null,
            'created_at'     => $now,
        ];

        $this->db->insert(db_prefix() . 'followup_history', $insert);

        return $this->db->insert_id();
    }

    /**
     * Appends one Activity Log (audit) entry - Case Created, Assigned,
     * Reassigned, Status Changed, Closed, Reopened, Reminder Created,
     * Reminder Completed, etc. Read by the Case Detail Activity Log section
     * built in Phase 2.
     *
     * @param mixed  $followup_id
     * @param string $action_type
     * @param string $description
     * @param mixed  $old_value
     * @param mixed  $new_value
     * @param mixed  $created_by
     * @return int|false
     */
    private function log_case_activity($followup_id, $action_type, $description, $old_value = null, $new_value = null, $created_by = null)
    {
        $insert = [
            'followup_id' => $followup_id,
            'action_type' => $action_type,
            'description' => $description,
            'old_value'   => $old_value,
            'new_value'   => $new_value,
            'created_by'  => $created_by ?? get_staff_user_id(),
            'created_at'  => date('Y-m-d H:i:s'),
        ];

        $this->db->insert(db_prefix() . 'followup_activity', $insert);

        return $this->db->insert_id();
    }

    /**
     * @param mixed $reminder_id
     * @param mixed $case_id     when given, fires the "Meeting Cancelled" /
     *                           "Reminder Cancelled" notification - omitted
     *                           by delete() (the whole case is gone, so a
     *                           cancellation notice would be meaningless).
     */
    private function cancel_reminder_if_pending($reminder_id, $case_id = null)
    {
        $this->load->model('misc_model');
        $reminder = $this->misc_model->get_reminders($reminder_id);

        if ($reminder && $reminder->isnotified == 0) {
            $this->misc_model->delete_reminder($reminder_id);

            if ($case_id) {
                $case = $this->get($case_id);

                if ($case) {
                    // Distinguish "Meeting Cancelled" from a plain
                    // "Reminder Cancelled" by checking whether the most
                    // recent Timeline entry linked to this reminder was a
                    // meeting - reuses tblfollowup_history rather than
                    // guessing from context.
                    $this->db->where('followup_id', $case_id);
                    $this->db->where('reminder_id', $reminder_id);
                    $this->db->order_by('id', 'desc');
                    $this->db->limit(1);
                    $history_entry = $this->db->get(db_prefix() . 'followup_history')->row();
                    $was_meeting   = $history_entry && $history_entry->activity_type === 'meeting';

                    $action_type      = $was_meeting ? 'meeting_cancelled' : 'reminder_cancelled';
                    $description_key  = $was_meeting ? 'not_followup_meeting_cancelled' : 'not_followup_reminder_cancelled';

                    $this->log_case_activity($case_id, $action_type, $was_meeting ? 'Meeting cancelled' : 'Reminder cancelled');

                    $rel_data   = get_relation_data($case->rel_type, $case->rel_id);
                    $rel_values = get_relation_values($rel_data, $case->rel_type);
                    $this->notify_case_event($case_id, $case->assigned_staff_id, $description_key, [$rel_values['name']]);
                }
            }
        }
    }

    /**
     * Central notification helper for every Case-related trigger in this
     * module - one place that calls the core add_notification()/
     * pusher_trigger_notification() (reused, not duplicated) and optionally
     * this module's own Follow_up_case_notification email, instead of
     * repeating this at each of the ~10 trigger call sites.
     *
     * The acting staff member never gets notified about their own action
     * (matches the convention core already follows for e.g. lead
     * reassignment).
     *
     * @param mixed  $case_id
     * @param mixed  $staff_id         recipient - skipped if empty or the acting staff
     * @param string $description_key  language key for the in-app notification (%s placeholder)
     * @param array  $additional_data  vsprintf params for that language key
     * @param bool   $send_email
     * @param string $email_title
     * @param string $email_description
     */
    /**
     * "Reminder Overdue" - called by follow_up_management_check_overdue_cases()
     * (registered via register_cron_task(), see the module entry file) once
     * per overdue case per day, guarded there against re-notifying the same
     * case twice via a tblfollowup_activity lookup. Public because that
     * function lives outside this model.
     *
     * @param object $case
     */
    public function notify_overdue_case($case)
    {
        $this->log_case_activity($case->id, 'reminder_overdue_notified', 'Reminder overdue');

        $rel_data   = get_relation_data($case->rel_type, $case->rel_id);
        $rel_values = get_relation_values($rel_data, $case->rel_type);

        $this->notify_case_event(
            $case->id,
            $case->assigned_staff_id,
            'not_followup_reminder_overdue',
            [$rel_values['name']],
            true,
            _l('followup_email_reminder_overdue_title'),
            _l('followup_email_reminder_overdue_body', [$rel_values['name'], _dt($case->next_follow_up_date)])
        );
    }

    /**
     * "Missed follow-up" auto-forward - called by
     * follow_up_management_forward_missed_followups() (registered via
     * register_cron_task(), see the module entry file) once per case whose
     * Next Follow-up Date was today and never actioned today. Moves the
     * date to tomorrow only - nothing else on the Case/Lead changes.
     *
     * Deliberately does NOT call reschedule_case() (the Telecaller's own
     * manual "Reschedule" action): that method gates on
     * follow_up_case_access_denied($case), a staff-session check that's
     * meaningless with no logged-in staff in a cron context, and it logs
     * its history entry as a user-initiated reschedule, which would
     * misattribute this automated action. Instead this reuses the exact
     * same underlying propagation reschedule_case() itself reuses - the
     * Lead's own "Next Follow-up Date" custom field is the single source
     * of truth (confirmed via get_lead_next_follow_up_date()/
     * sync_case_from_lead()), so it's written via the same generic
     * handle_custom_fields_post() the Lead edit form and reschedule_case()
     * already use, then sync_case_from_lead() (existing Lead->Case->Task->
     * Reminder propagation) pulls it back onto the Case - not a second
     * sync path. A distinct 'auto_forwarded' history entry (created_by=0,
     * a safe sentinel - get_staff_full_name(0) resolves to '' with no
     * error) keeps this honestly labeled as automated rather than reusing
     * the 'reschedule' entry type.
     *
     * @param object $case a tblfollowups row (rel_type must be 'lead')
     * @return bool
     */
    public function forward_case_to_tomorrow($case)
    {
        if ($case->rel_type !== 'lead') {
            return false;
        }

        $field = get_custom_fields('leads', ['name' => 'Next Follow-up Date']);

        if (empty($field)) {
            return false;
        }

        $tomorrow = date('Y-m-d', strtotime('+1 day'));

        handle_custom_fields_post($case->rel_id, ['leads' => [$field[0]['id'] => $tomorrow]]);

        $this->sync_case_from_lead($case->rel_id);

        // Hardcoded rather than _l(): this runs from the unauthenticated
        // /cron request (no staff session), where module language files
        // were found empirically (live-tested) to not resolve reliably -
        // notify_overdue_case()'s own log_case_activity() call above uses
        // the same hardcoded-string convention for exactly that reason.
        $this->add_history_entry($case->id, [
            'activity_type'  => 'auto_forwarded',
            'notes'          => 'Not actioned today - Next Follow-up Date automatically moved to tomorrow.',
            'event_datetime' => date('Y-m-d H:i:s'),
            'created_by'     => 0,
        ]);

        return true;
    }

    private function notify_case_event($case_id, $staff_id, $description_key, $additional_data = [], $send_email = false, $email_title = '', $email_description = '')
    {
        if (empty($staff_id) || $staff_id == get_staff_user_id()) {
            return;
        }

        $notified = add_notification([
            'fromcompany'     => 1,
            'touserid'        => $staff_id,
            'description'     => $description_key,
            'link'            => 'follow_up_management/view/' . $case_id,
            'additional_data' => serialize($additional_data),
        ]);

        if ($notified) {
            pusher_trigger_notification([$staff_id]);
        }

        if ($send_email && is_email_template_active('follow_up_case_notification')) {
            $this->load->model('staff_model');
            $staff = $this->staff_model->get($staff_id);

            if ($staff && !empty($staff->email)) {
                send_mail_template('follow_up_case_notification', 'follow_up_management', $case_id, $staff->email, $email_title, $email_description);
            }
        }
    }

    /**
     * Step 4 - Telecaller Dashboard "Assigned Leads" card. Identical
     * scoping to get_case_counts()'s own 'my_open_cases' (count_cases()
     * already applies get_followup_case_visibility_where()) - a separate
     * named method only because the Telecaller Dashboard spec calls this
     * card "Assigned Leads", not "Open Cases".
     *
     * @return int
     */
    public function get_assigned_leads_count()
    {
        return $this->count_cases(['case_status' => 'open']);
    }

    /**
     * Calls logged today across this Telecaller's own (visibility-scoped)
     * cases - tblfollowup_history rows, not a new call-log table.
     *
     * @return int
     */
    public function get_todays_calls_count()
    {
        $this->db->from(db_prefix() . 'followup_history fh');
        $this->db->join(db_prefix() . 'followups f', 'f.id = fh.followup_id');
        $this->db->where('fh.activity_type', 'call');
        $this->db->where('DATE(fh.event_datetime)', date('Y-m-d'));

        $this->_apply_visibility('f');

        return (int) $this->db->count_all_results();
    }

    /**
     * Open cases whose Lead is already in one of the "hot" pre-close
     * statuses (Interested/Proposal Given/Negotiation) - the existing
     * Lead status taxonomy (Step 3) is the single source of truth for
     * "ready to convert", not a second pipeline-stage flag.
     *
     * @return int
     */
    public function get_ready_for_conversion_count()
    {
        $this->db->from(db_prefix() . 'followups f');
        $this->db->join(db_prefix() . 'leads l', 'l.id = f.rel_id');
        $this->db->join(db_prefix() . 'leads_status ls', 'ls.id = l.status');
        $this->db->where('f.rel_type', 'lead');
        $this->db->where('f.case_status', 'open');
        $this->db->where_in('ls.name', ['Interested', 'Proposal Given', 'Negotiation']);

        $this->_apply_visibility('f');

        return (int) $this->db->count_all_results();
    }

    /**
     * Customers this Telecaller personally converted (tblleads.converted_by,
     * migration 370) - not scoped by Case visibility, since a converted
     * Lead's Case is already closed and its assigned_staff_id reflects
     * who held it at close time, not necessarily who converted it;
     * converted_by is the permanent record of who actually performed the
     * conversion, set via the lead_converted_to_customer hook regardless
     * of which portal triggered it.
     *
     * @return int
     */
    public function get_converted_customers_count()
    {
        $this->db->where('converted_by', get_staff_user_id());
        $this->db->where('client_id >', 0);

        return (int) $this->db->count_all_results(db_prefix() . 'leads');
    }

    /**
     * Most recent calls across this Telecaller's own cases, for the
     * dashboard's "Recent Calls" panel.
     *
     * @param  int $limit
     * @return array
     */
    public function get_recent_calls($limit = 5)
    {
        $this->db->select('fh.notes, fh.event_datetime, fh.outcome, f.rel_id as lead_id, l.name as lead_name');
        $this->db->from(db_prefix() . 'followup_history fh');
        $this->db->join(db_prefix() . 'followups f', 'f.id = fh.followup_id');
        $this->db->join(db_prefix() . 'leads l', 'l.id = f.rel_id', 'left');
        $this->db->where('fh.activity_type', 'call');

        $this->_apply_visibility('f');

        $this->db->order_by('fh.event_datetime', 'desc');
        $this->db->limit($limit);

        return $this->db->get()->result_array();
    }

    /**
     * Call History for one Lead's Telecaller Lead page - every
     * tblfollowup_history row (across every Case this Lead has ever had)
     * whose activity_type is 'call'. No ownership check - the calling
     * controller action already verified access before reaching this.
     * Returns objects (not result_array()) since the view renders them
     * through get_followup_history_icon()/label(), which read ->activity_type/
     * ->follow_up_type as object properties - the same shape those helpers
     * already expect from case.php/task_workspace.php's own history loops.
     *
     * @param  int $lead_id
     * @return array
     */
    public function get_lead_call_history($lead_id)
    {
        $this->db->select('fh.*');
        $this->db->from(db_prefix() . 'followup_history fh');
        $this->db->join(db_prefix() . 'followups f', 'f.id = fh.followup_id');
        $this->db->where('f.rel_id', $lead_id);
        $this->db->where('f.rel_type', 'lead');
        $this->db->where('fh.activity_type', 'call');
        $this->db->order_by('fh.event_datetime', 'desc');

        return $this->db->get()->result();
    }

    /**
     * Follow-up History for one Lead's Telecaller Lead page - every
     * non-call tblfollowup_history row (case creation, reschedules,
     * scheduled reminders/meetings).
     *
     * @param  int $lead_id
     * @return array
     */
    public function get_lead_followup_history($lead_id)
    {
        $this->db->select('fh.*');
        $this->db->from(db_prefix() . 'followup_history fh');
        $this->db->join(db_prefix() . 'followups f', 'f.id = fh.followup_id');
        $this->db->where('f.rel_id', $lead_id);
        $this->db->where('f.rel_type', 'lead');
        $this->db->where_in('fh.activity_type', ['reschedule', 'reminder', 'meeting', 'case_created']);
        $this->db->order_by('fh.event_datetime', 'desc');

        return $this->db->get()->result();
    }

    /**
     * Return to Field Executive - "Needs Another Visit" outcome. Current
     * Handler moves back to the Original Field Executive
     * (tblleads.addedfrom, never itself changed), the Telecaller's open
     * Case+linked Task are closed via the existing complete_case_for_lead()
     * (never a second close path), and the Lead's status moves to
     * "Visited" (already exists, Step 3) - the same status a fresh
     * meeting outcome uses, since "needs another visit" means the same
     * thing whichever side sent it back.
     *
     * @param  int $lead_id
     * @return bool
     */
    public function return_lead_to_field_executive($lead_id)
    {
        $this->db->select('addedfrom');
        $this->db->where('id', $lead_id);
        $lead = $this->db->get(db_prefix() . 'leads')->row();

        if (!$lead) {
            return false;
        }

        $this->db->where('id', $lead_id);
        $this->db->update(db_prefix() . 'leads', ['assigned' => $lead->addedfrom]);

        $this->complete_case_for_lead($lead_id);

        $this->load->model('field_portal/field_portal_model');
        $this->load->model('leads_model');

        $visited_status_id = $this->field_portal_model->get_status_id_by_name('Visited');

        if ($visited_status_id) {
            $this->leads_model->update_lead_status(['leadid' => $lead_id, 'status' => $visited_status_id]);
        }

        $this->leads_model->log_lead_activity($lead_id, 'not_lead_returned_to_field_executive', false, serialize([get_staff_full_name()]));

        if ($lead->addedfrom != get_staff_user_id()) {
            add_notification([
                'fromcompany'     => 1,
                'touserid'        => $lead->addedfrom,
                'description'     => 'not_lead_returned_to_field_executive',
                'link'            => 'field_portal/lead_details/' . $lead_id,
                'additional_data' => serialize([get_staff_full_name()]),
            ]);
            pusher_trigger_notification([$lead->addedfrom]);
        }

        return true;
    }

    /**
     * Applies the case visibility WHERE clause consistently across all
     * queries. Wraps get_followup_case_visibility_where() which returns
     * SQL starting with 'AND ' - strips that prefix for use with Query
     * Builder's where() method.
     */
    private function _apply_visibility($table_alias = null)
    {
        $visibility = get_followup_case_visibility_where($table_alias);
        if ($visibility) {
            $this->db->where(substr($visibility, 4), null, false);
        }
    }

    // ----------------------------------------------------------------------
    // Admin Sales CRM - model methods (Phase 6)
    // ----------------------------------------------------------------------

    /**
     * Aggregate KPI data for the Admin Sales Dashboard. Every count
     * comes from core tables (tblleads, tblclients, tblfollowups) with
     * zero new schema.
     */
    public function get_admin_sales_kpis()
    {
        $prefix = db_prefix();
        $staff_id = get_staff_user_id();

        // Total Leads (non-junk)
        $total_leads = total_rows($prefix . 'leads', ['junk' => 0]);

        // New Leads Today
        $new_leads_today = total_rows($prefix . 'leads', [
            'junk'       => 0,
            'DATE(dateadded)' => date('Y-m-d'),
        ]);

        // Leads with a Field Executive assigned (assigned > 0, status != Lost)
        $this->db->where('junk', 0);
        $this->db->where('assigned >', 0);
        $this->db->where('lost', 0);
        $leads_with_fe = $this->db->count_all_results($prefix . 'leads');

        // Leads with a Telecaller (has an open follow-up case)
        $this->db->where($prefix . 'leads.junk', 0);
        $this->db->where($prefix . 'followups.rel_type', 'lead');
        $this->db->where($prefix . 'followups.case_status', 'open');
        $this->db->join($prefix . 'followups', $prefix . 'followups.rel_id = ' . $prefix . 'leads.id');
        $leads_with_tc = $this->db->count_all_results($prefix . 'leads');

        // Today's Meetings (from tblfollowup_history)
        $this->db->where('activity_type', 'meeting');
        $this->db->where('DATE(event_datetime)', date('Y-m-d'));
        $todays_meetings = $this->db->count_all_results($prefix . 'followup_history');

        // Today's Follow-ups (non-meeting history entries today)
        $this->db->where('activity_type !=', 'meeting');
        $this->db->where('DATE(event_datetime)', date('Y-m-d'));
        $todays_followups = $this->db->count_all_results($prefix . 'followup_history');

        // Converted Customers (leads with client_id > 0)
        $converted = total_rows($prefix . 'leads', ['client_id >' => 0]);

        // Lost Leads
        $lost = total_rows($prefix . 'leads', ['lost' => 1]);

        // Active Customers (clients with at least one lead that converted)
        $this->db->select('client_id');
        $this->db->distinct();
        $this->db->where('client_id >', 0);
        $active_customers = $this->db->count_all_results($prefix . 'leads');

        // Conversion Rate (leads converted / (total non-lost non-junk) * 100)
        $non_lost = total_rows($prefix . 'leads', ['lost' => 0, 'junk' => 0]);
        $conversion_rate = $non_lost > 0 ? round(($converted / $non_lost) * 100, 1) : 0;

        return [
            'total_leads'         => (int) $total_leads,
            'new_leads_today'     => (int) $new_leads_today,
            'leads_with_fe'       => (int) $leads_with_fe,
            'leads_with_tc'       => (int) $leads_with_tc,
            'todays_meetings'     => (int) $todays_meetings,
            'todays_followups'    => (int) $todays_followups,
            'converted'           => (int) $converted,
            'lost'                => (int) $lost,
            'conversion_rate'     => $conversion_rate,
            'active_customers'    => (int) $active_customers,
        ];
    }

    /**
     * Returns lead counts grouped by lead status name (Sales Pipeline
     * stages). Uses tblleads_status for the canonical stage names.
     */
    public function get_sales_pipeline_data()
    {
        $prefix = db_prefix();
        $this->db->select($prefix . 'leads_status.name, COUNT(' . $prefix . 'leads.id) as lead_count');
        $this->db->from($prefix . 'leads_status');
        $this->db->join($prefix . 'leads', $prefix . 'leads.status = ' . $prefix . 'leads_status.id AND ' . $prefix . 'leads.junk = 0', 'left');
        $this->db->group_by($prefix . 'leads_status.id');
        $this->db->order_by($prefix . 'leads_status.sort_order', 'asc');
        $query = $this->db->get();

        $stages = $query->result_array();
        $total  = array_sum(array_column($stages, 'lead_count'));

        return ['stages' => $stages, 'total' => (int) $total];
    }

    /**
     * Employee Performance data for Field Executives and Telecallers.
     *
     * @param string $role 'fe' or 'tc'
     * @return array
     */
    public function get_employee_performance_data($role = 'fe')
    {
        $prefix = db_prefix();

        if ($role === 'fe') {
            // Field Executive: staff who created leads
            $this->db->select($prefix . 'staff.staffid, CONCAT(' . $prefix . "staff.firstname, ' ', " . $prefix . "staff.lastname) as name");
            $this->db->from($prefix . 'staff');
            $this->db->where($prefix . 'staff.active', 1);
            $this->db->where('EXISTS (SELECT 1 FROM ' . $prefix . "leads WHERE " . $prefix . "leads.addedfrom = " . $prefix . "staff.staffid)", null, false);
            $staff = $this->db->get()->result();

            $result = [];
            foreach ($staff as $s) {
                $sid = (int) $s->staffid;

                // Leads created
                $created = total_rows($prefix . 'leads', ['addedfrom' => $sid, 'junk' => 0]);

                // Meetings from leads they created
                $this->db->from($prefix . 'followup_history fh');
                $this->db->join($prefix . 'followups f', 'f.id = fh.followup_id');
                $this->db->where('f.rel_type', 'lead');
                $this->db->where('fh.activity_type', 'meeting');
                $this->db->where('fh.created_by', $sid);
                $meetings = $this->db->count_all_results();

                // Assigned to Telecaller (leads where assigned != addedfrom)
                $assigned_tc = total_rows($prefix . 'leads', ['addedfrom' => $sid, 'junk' => 0, 'assigned !=' => $sid]);

                // Converted
                $converted = total_rows($prefix . 'leads', ['addedfrom' => $sid, 'client_id >' => 0]);

                // Lost
                $fe_lost = total_rows($prefix . 'leads', ['addedfrom' => $sid, 'lost' => 1]);

                // Conversion %
                $total_leads = $created;
                $conv_pct = $total_leads > 0 ? round(($converted / $total_leads) * 100, 1) : 0;

                $result[] = [
                    'staff_id'       => $sid,
                    'name'           => $s->name,
                    'leads_created'  => (int) $created,
                    'meetings'       => (int) $meetings,
                    'assigned_tc'    => (int) $assigned_tc,
                    'converted'      => (int) $converted,
                    'lost'           => (int) $fe_lost,
                    'conversion_pct' => $conv_pct,
                ];
            }

            return $result;
        }

        // Telecaller: staff who have been assigned as telecaller on follow-ups
        $this->db->select($prefix . 'staff.staffid, CONCAT(' . $prefix . "staff.firstname, ' ', " . $prefix . "staff.lastname) as name");
        $this->db->from($prefix . 'staff');
        $this->db->where($prefix . 'staff.active', 1);
        $this->db->where('EXISTS (SELECT 1 FROM ' . $prefix . 'followups f WHERE f.assigned_staff_id = ' . $prefix . "staff.staffid AND f.rel_type = 'lead')", null, false);
        $staff = $this->db->get()->result();

        $result = [];
        foreach ($staff as $s) {
            $sid = (int) $s->staffid;

            // Assigned Leads
            $assigned_leads = total_rows($prefix . 'followups', ['assigned_staff_id' => $sid, 'rel_type' => 'lead']);

            // Calls Made
            $this->db->from($prefix . 'followup_history fh');
            $this->db->join($prefix . 'followups f', 'f.id = fh.followup_id');
            $this->db->where('f.rel_type', 'lead');
            $this->db->where('f.assigned_staff_id', $sid);
            $this->db->where('fh.activity_type', 'call');
            $this->db->where('fh.created_by', $sid);
            $calls = $this->db->count_all_results();

            // Follow-ups (non-meeting interactions)
            $this->db->from($prefix . 'followup_history fh');
            $this->db->join($prefix . 'followups f', 'f.id = fh.followup_id');
            $this->db->where('f.rel_type', 'lead');
            $this->db->where('f.assigned_staff_id', $sid);
            $this->db->where('fh.created_by', $sid);
            $this->db->where_in('fh.activity_type', ['call', 'reminder', 'note', 'status_change']);
            $followups = $this->db->count_all_results();

            // Converted Customers from their assigned leads
            $this->db->from($prefix . 'leads');
            $this->db->join($prefix . 'followups', $prefix . 'followups.rel_id = ' . $prefix . 'leads.id AND ' . $prefix . "followups.rel_type = 'lead'");
            $this->db->where($prefix . 'followups.assigned_staff_id', $sid);
            $this->db->where($prefix . 'leads.client_id >', 0);
            $converted = $this->db->count_all_results();

            // Lost Leads from their assigned leads
            $this->db->from($prefix . 'leads');
            $this->db->join($prefix . 'followups', $prefix . 'followups.rel_id = ' . $prefix . 'leads.id AND ' . $prefix . "followups.rel_type = 'lead'");
            $this->db->where($prefix . 'followups.assigned_staff_id', $sid);
            $this->db->where($prefix . 'leads.lost', 1);
            $lost = $this->db->count_all_results();

            // Average Follow-up Time (hours between first and last interaction)
            $this->db->select_min('event_datetime', 'first_event');
            $this->db->select_max('event_datetime', 'last_event');
            $this->db->from($prefix . 'followup_history fh');
            $this->db->join($prefix . 'followups f', 'f.id = fh.followup_id');
            $this->db->where('f.rel_type', 'lead');
            $this->db->where('f.assigned_staff_id', $sid);
            $this->db->where('fh.created_by', $sid);
            $range = $this->db->get()->row();

            $avg_hours = 0;
            if ($range && $range->first_event && $range->last_event) {
                $diff = strtotime($range->last_event) - strtotime($range->first_event);
                $avg_hours = $diff > 0 ? round($diff / 3600, 1) : 0;
            }

            $result[] = [
                'staff_id'          => $sid,
                'name'              => $s->name,
                'assigned_leads'    => (int) $assigned_leads,
                'calls_made'        => (int) $calls,
                'followups'         => (int) $followups,
                'converted'         => (int) $converted,
                'lost'              => (int) $lost,
                'avg_followup_time' => $avg_hours,
            ];
        }

        return $result;
    }

    /**
     * Returns the origin metadata for a converted lead (customer).
     *
     * @param  int  $client_id
     * @return array|null
     */
    public function get_customer_origin_data($client_id)
    {
        $prefix = db_prefix();

        $this->db->select($prefix . 'leads.*, ' . $prefix . 'leads_status.name as status_name, ' . $prefix . 'leads_sources.name as source_name, fe_staff.firstname as fe_firstname, fe_staff.lastname as fe_lastname, tc_staff.firstname as tc_firstname, tc_staff.lastname as tc_lastname, ' . $prefix . 'followups.assigned_staff_id as telecaller_id');
        $this->db->from($prefix . 'leads');
        $this->db->join($prefix . 'leads_status', $prefix . 'leads_status.id = ' . $prefix . 'leads.status', 'left');
        $this->db->join($prefix . 'leads_sources', $prefix . 'leads_sources.id = ' . $prefix . 'leads.source', 'left');
        $this->db->join($prefix . 'staff fe_staff', 'fe_staff.staffid = ' . $prefix . 'leads.addedfrom', 'left');
        $this->db->join($prefix . 'followups', $prefix . 'followups.rel_id = ' . $prefix . 'leads.id AND ' . $prefix . "followups.rel_type = 'lead'", 'left');
        $this->db->join($prefix . 'staff tc_staff', 'tc_staff.staffid = ' . $prefix . 'followups.assigned_staff_id', 'left');
        $this->db->where($prefix . 'leads.client_id', $client_id);
        $this->db->order_by($prefix . 'followups.id', 'asc');
        $this->db->limit(1);

        return $this->db->get()->row();
    }

    /**
     * Lead Reports - filtered lead data with all metadata.
     *
     * @param  array  $filters  ['date_from', 'date_to', 'staff_id', 'status_id', 'source_id', 'role']
     * @return array
     */
    public function get_lead_report_data($filters = [])
    {
        $prefix = db_prefix();

        $this->db->select($prefix . 'leads.id, ' . $prefix . 'leads.name, ' . $prefix . 'leads.company, ' . $prefix . 'leads.phonenumber, ' . $prefix . 'leads.email, ' . $prefix . 'leads.dateadded, ' . $prefix . 'leads.date_converted, ' . $prefix . 'leads_status.name as status_name, ' . $prefix . 'leads_sources.name as source_name, fe_staff.firstname as fe_firstname, fe_staff.lastname as fe_lastname, tc_staff.firstname as tc_firstname, tc_staff.lastname as tc_lastname, ' . $prefix . 'leads.lost as is_lost, ' . $prefix . 'leads.lost_reason, ' . $prefix . 'leads.client_id');
        $this->db->from($prefix . 'leads');
        $this->db->join($prefix . 'leads_status', $prefix . 'leads_status.id = ' . $prefix . 'leads.status', 'left');
        $this->db->join($prefix . 'leads_sources', $prefix . 'leads_sources.id = ' . $prefix . 'leads.source', 'left');
        $this->db->join($prefix . 'staff fe_staff', 'fe_staff.staffid = ' . $prefix . 'leads.addedfrom', 'left');
        $this->db->join($prefix . 'followups', $prefix . 'followups.rel_id = ' . $prefix . 'leads.id AND ' . $prefix . "followups.rel_type = 'lead'", 'left');
        $this->db->join($prefix . 'staff tc_staff', 'tc_staff.staffid = ' . $prefix . 'followups.assigned_staff_id', 'left');
        $this->db->where($prefix . 'leads.junk', 0);

        if (!empty($filters['date_from'])) {
            $this->db->where($prefix . 'leads.dateadded >=', $filters['date_from'] . ' 00:00:00');
        }
        if (!empty($filters['date_to'])) {
            $this->db->where($prefix . 'leads.dateadded <=', $filters['date_to'] . ' 23:59:59');
        }
        if (!empty($filters['staff_id'])) {
            $this->db->where($prefix . 'leads.addedfrom', (int) $filters['staff_id']);
        }
        if (!empty($filters['status_id'])) {
            $this->db->where($prefix . 'leads.status', (int) $filters['status_id']);
        }
        if (!empty($filters['source_id'])) {
            $this->db->where($prefix . 'leads.source', (int) $filters['source_id']);
        }
        if (!empty($filters['role'])) {
            if ($filters['role'] === 'fe') {
                $this->db->where($prefix . 'leads.addedfrom >', 0);
            } elseif ($filters['role'] === 'tc') {
                $this->db->where($prefix . 'followups.assigned_staff_id >', 0);
            }
        }
        if (!empty($filters['is_lost'])) {
            $this->db->where($prefix . 'leads.lost', 1);
        }
        if (!empty($filters['is_converted'])) {
            $this->db->where($prefix . 'leads.client_id >', 0);
        }

        $this->db->order_by($prefix . 'leads.dateadded', 'desc');

        return $this->db->get()->result_array();
    }
}
