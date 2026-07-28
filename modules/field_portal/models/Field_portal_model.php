<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Field_portal_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Dashboard KPI counts for one Field Executive. Every count here
     * reads the existing tblleads table directly (addedfrom/assigned/
     * status/client_id are all stock Perfex Lead columns, unchanged) -
     * no new Lead-related table exists. Every KPI is scoped by
     * 'addedfrom' only (Original Owner, set once at creation and never
     * reassigned) rather than 'assigned' (Current Handler, which moves to
     * a Telecaller and back) - each of these cards represents "what this
     * Field Executive brought in", not "what's currently routed to them".
     *
     * @param  int $staff_id
     * @return array
     */
    public function get_dashboard_summary($staff_id)
    {
        $summary = [];

        // Today's Leads - reuses get_todays_new_leads() (also backs the
        // "Today's New Leads" list panel below), limit=0 per that
        // method's own "give me every row" contract.
        $summary['todays_leads'] = count($this->get_todays_new_leads($staff_id, 0));

        // Total Leads - this Field Executive's complete Lead portfolio.
        $this->db->where('addedfrom', $staff_id);
        $summary['total_leads'] = (int) $this->db->count_all_results(db_prefix() . 'leads');

        // Converted Customers - date_converted/client_id are set by the
        // existing Leads_model::convert_to_customer() regardless of who
        // performs the conversion (Admin, this Field Executive directly,
        // or a Telecaller via the Customer Ready action), so this already
        // counts correctly no matter which portal/button triggered it.
        $this->db->where('addedfrom', $staff_id);
        $this->db->where('client_id >', 0);
        $summary['converted_customers'] = (int) $this->db->count_all_results(db_prefix() . 'leads');

        // Today's Meetings - Meetings are Tasks, rel_type='lead'. limit=0
        // fetches the full row set, only ever used here for count().
        $summary['todays_meetings'] = count($this->get_todays_meetings($staff_id, 0));

        // Pending Follow-ups - reuses the exact same definition as the
        // "Pending" tab on the All Follow-ups page (see
        // get_pending_followups_count()'s own docblock).
        $summary['pending_followups'] = $this->get_pending_followups_count($staff_id);

        // Assigned to Telecaller - Leads this Field Executive originally
        // created that are currently routed (assigned) to a Telecaller -
        // reuses field_portal_get_telecallers() (the same department
        // staff lookup the Assign-to-Telecaller modal itself validates
        // against). array_map('intval', ...): get_business_department_staff()
        // returns staffid as whatever type the DB driver hands back (a
        // numeric string, not necessarily an int) - where_in() compares
        // loosely so this isn't strictly required here, but keeps the
        // id list in the same normalized shape used everywhere else this
        // helper's result is consumed.
        $telecaller_ids = array_map('intval', array_column(field_portal_get_telecallers(), 'staffid'));

        if ($telecaller_ids) {
            $this->db->where('addedfrom', $staff_id);
            $this->db->where_in('assigned', $telecaller_ids);
            $summary['assigned_to_telecaller'] = (int) $this->db->count_all_results(db_prefix() . 'leads');
        } else {
            $summary['assigned_to_telecaller'] = 0;
        }

        return $summary;
    }

    /**
     * Recent leads created-by/assigned-to this Field Executive that have
     * already gone through the existing Lead->Customer conversion -
     * joined to tblclients (via tblleads.client_id, set by that same
     * conversion) purely for display, so the row can show the customer's
     * current company name rather than the lead's original one.
     *
     * @param  int $staff_id
     * @param  int $limit
     * @return array
     */
    public function get_recent_conversions($staff_id, $limit = 5)
    {
        $this->db->select(db_prefix() . 'leads.id, ' . db_prefix() . 'leads.name as lead_name, ' . db_prefix() . 'leads.date_converted, ' . db_prefix() . 'clients.userid as client_id, ' . db_prefix() . 'clients.company');
        $this->db->from(db_prefix() . 'leads');
        $this->db->join(db_prefix() . 'clients', db_prefix() . 'clients.userid = ' . db_prefix() . 'leads.client_id', 'left');
        // Scoped by 'addedfrom' only (Original Owner) - see the matching
        // note on get_dashboard_summary()'s 'converted_customers' count,
        // which this panel must stay consistent with.
        $this->db->where(db_prefix() . 'leads.addedfrom', $staff_id);
        $this->db->where(db_prefix() . 'leads.client_id >', 0);
        $this->db->order_by(db_prefix() . 'leads.date_converted', 'desc');
        $this->db->limit($limit);

        return $this->db->get()->result_array();
    }

    /**
     * Live id of the "New" lead status - looked up by name rather than
     * hardcoded, same reasoning as field_portal_department_id(). Falls
     * back to 1 (this status's id on every environment audited so far)
     * only if the row is somehow missing, so a dashboard count never
     * hard-fails.
     *
     * @return int
     */
    public function get_new_lead_status_id()
    {
        static $id = null;

        if ($id !== null) {
            return $id;
        }

        $row = $this->db->where('name', 'New')->get(db_prefix() . 'leads_status')->row();

        $id = $row ? (int) $row->id : 1;

        return $id;
    }

    /**
     * Live Lead status id by name - Step 4's "Assign to Telecaller" (sets
     * 'Follow-up') and "Mark Lost" (sets 'Lost') actions both need this;
     * same lookup-by-name reasoning as get_new_lead_status_id(), just
     * generalized to any status name instead of only "New".
     *
     * @param  string $name
     * @return int 0 if no such status exists
     */
    public function get_status_id_by_name($name)
    {
        $row = $this->db->where('name', $name)->get(db_prefix() . 'leads_status')->row();

        return $row ? (int) $row->id : 0;
    }

    /**
     * Leads created today by this Field Executive - real data
     * (tblleads.dateadded), backing both the Dashboard's "Today's New
     * Leads" panel and its "Today's Leads" KPI card (count($this->get_todays_new_leads($staff_id, 0))).
     * Scoped by 'addedfrom' only (Original Owner) - never 'assigned' -
     * so a lead merely routed to this Field Executive today (not created
     * by them) doesn't count, matching 'total_leads'/'converted_customers'
     * in get_dashboard_summary().
     *
     * @param  int $staff_id
     * @param  int $limit
     * @return array
     */
    public function get_todays_new_leads($staff_id, $limit = 5)
    {
        $this->db->select('id, name, company, status');
        $this->db->where('DATE(dateadded)', date('Y-m-d'));
        $this->db->where('addedfrom', $staff_id);
        $this->db->order_by('dateadded', 'desc');

        // limit(0) compiles to a literal "LIMIT 0" (zero rows), not
        // "unlimited" - skipped entirely so callers can pass 0 to mean
        // "give me every row" (used for the dashboard KPI count).
        if ($limit) {
            $this->db->limit($limit);
        }

        return $this->db->get(db_prefix() . 'leads')->result_array();
    }

    /**
     * Upcoming follow-ups for this Field Executive's own leads - backs
     * both the Dashboard's "Upcoming Follow-ups" panel and (via the exact
     * same "> tomorrow" definition) the "Upcoming" tab on the All
     * Follow-ups page (Field_portal::followups_table()). Rooted at
     * tblfollowups (one row per Case) rather than the Lead's own "Next
     * Follow-up Date" custom field, the same correction made to
     * followups_table() itself: a Case's own next_follow_up_date column
     * (kept in sync by Follow_ups_model::reschedule_case() etc.) is the
     * complete, authoritative source - the Lead custom field can lag
     * behind or never get written at all for a lead whose follow-up date
     * was only ever set via the Telecaller side.
     *
     * @param  int $staff_id
     * @param  int $limit
     * @return array
     */
    public function get_upcoming_followups($staff_id, $limit = 5)
    {
        $this->db->select(db_prefix() . 'leads.id, ' . db_prefix() . 'leads.name, ' . db_prefix() . 'leads.company, ' . db_prefix() . 'followups.next_follow_up_date');
        $this->db->from(db_prefix() . 'followups');
        $this->db->join(db_prefix() . 'leads', db_prefix() . 'leads.id = ' . db_prefix() . 'followups.rel_id');
        $this->db->where(db_prefix() . 'followups.rel_type', 'lead');
        $this->db->where('DATE(' . db_prefix() . 'followups.next_follow_up_date) >', date('Y-m-d', strtotime('+1 day')));
        $this->db->group_start();
        $this->db->where(db_prefix() . 'leads.addedfrom', $staff_id);
        $this->db->or_where(db_prefix() . 'leads.assigned', $staff_id);
        $this->db->group_end();
        $this->db->order_by(db_prefix() . 'followups.next_follow_up_date', 'asc');

        // limit(0) compiles to a literal "LIMIT 0" (zero rows), not
        // "unlimited" - skipped entirely so callers can pass 0 to mean
        // "give me every row" (used for dashboard counts).
        if ($limit) {
            $this->db->limit($limit);
        }

        return $this->db->get()->result_array();
    }

    /**
     * Pending follow-ups count for the Dashboard's "Pending Follow-ups"
     * KPI card - reuses the EXACT same definition as the "Pending" tab on
     * the All Follow-ups page (Field_portal::followups_table()'s
     * 'pending' branch): due today or later, on a Lead that isn't already
     * Converted or Lost. Kept as its own small query (not a call into
     * get_upcoming_followups()) precisely because "Pending" and
     * "Upcoming" are two distinct, non-overlapping tabs on that page as
     * of this Follow-ups redesign - reusing one for the other would make
     * this KPI's number disagree with what the destination tab shows.
     *
     * @param  int $staff_id
     * @return int
     */
    public function get_pending_followups_count($staff_id)
    {
        $lost_status_id = $this->get_status_id_by_name('Lost');

        $this->db->from(db_prefix() . 'followups');
        $this->db->join(db_prefix() . 'leads', db_prefix() . 'leads.id = ' . db_prefix() . 'followups.rel_id');
        $this->db->where(db_prefix() . 'followups.rel_type', 'lead');
        $this->db->where(db_prefix() . 'followups.next_follow_up_date >=', date('Y-m-d'));
        $this->db->where(db_prefix() . 'leads.client_id', 0);
        $this->db->where(db_prefix() . 'leads.status !=', $lost_status_id);
        $this->db->group_start();
        $this->db->where(db_prefix() . 'leads.addedfrom', $staff_id);
        $this->db->or_where(db_prefix() . 'leads.assigned', $staff_id);
        $this->db->group_end();

        return (int) $this->db->count_all_results();
    }

    /**
     * Recent lead-activity entries across every lead this Field Executive
     * created/is assigned - reuses tbllead_activity_log
     * (Leads_model::log_lead_activity(), already written on every add/
     * status-change/attachment/conversion event), not a new activity
     * mechanism.
     *
     * @param  int $staff_id
     * @param  int $limit
     * @return array
     */
    public function get_recent_lead_activity($staff_id, $limit = 8)
    {
        $this->db->select(db_prefix() . 'lead_activity_log.*, ' . db_prefix() . 'leads.name as lead_name');
        $this->db->from(db_prefix() . 'lead_activity_log');
        $this->db->join(db_prefix() . 'leads', db_prefix() . 'leads.id = ' . db_prefix() . 'lead_activity_log.leadid');
        $this->db->group_start();
        $this->db->where(db_prefix() . 'leads.addedfrom', $staff_id);
        $this->db->or_where(db_prefix() . 'leads.assigned', $staff_id);
        $this->db->group_end();
        $this->db->order_by(db_prefix() . 'lead_activity_log.date', 'desc');
        $this->db->limit($limit);

        return $this->db->get()->result_array();
    }

    /**
     * Full converted-customer list for this Field Executive - the same
     * query get_recent_conversions() runs for the Dashboard's 5-row
     * panel, just without the row limit and with a couple more display
     * columns, for the dedicated Customers page. converted_by_name (Step 4's
     * tblleads.converted_by, migration 370) and projects_count are
     * additive SELECT-only columns - no change to the WHERE scope shared
     * with the KPI/panel this page's rows must stay consistent with.
     *
     * @param  int $staff_id
     * @return array
     */
    public function get_my_customers($staff_id)
    {
        $prefix = db_prefix();

        $this->db->select(
            $prefix . 'leads.id, ' .
            $prefix . 'leads.name as lead_name, ' .
            $prefix . 'leads.date_converted, ' .
            $prefix . 'clients.userid as client_id, ' .
            $prefix . 'clients.company, ' .
            $prefix . 'clients.phonenumber, ' .
            $prefix . 'clients.address, ' .
            $prefix . 'clients.active, ' .
            "CONCAT(converted_staff.firstname, ' ', converted_staff.lastname) as converted_by_name, " .
            '(SELECT COUNT(*) FROM ' . $prefix . 'projects WHERE ' . $prefix . 'projects.clientid = ' . $prefix . 'clients.userid) as projects_count'
        );
        $this->db->from($prefix . 'leads');
        $this->db->join($prefix . 'clients', $prefix . 'clients.userid = ' . $prefix . 'leads.client_id', 'left');
        $this->db->join($prefix . 'staff converted_staff', 'converted_staff.staffid = ' . $prefix . 'leads.converted_by', 'left');
        // Scoped by 'addedfrom' only (Original Owner) - see the matching
        // note on get_dashboard_summary()'s 'converted_customers' count,
        // which this page must stay consistent with.
        $this->db->where($prefix . 'leads.addedfrom', $staff_id);
        $this->db->where($prefix . 'leads.client_id >', 0);
        $this->db->order_by($prefix . 'leads.date_converted', 'desc');

        return $this->db->get()->result_array();
    }

    /**
     * Fixed-window KPI counts for the Customers page (Total/Today/This
     * Month/This Year) - reads ONLY tblclients.converted_at, scoped to
     * customers converted from a Lead this Field Executive owns
     * (addedfrom), joined via tblclients.leadid (the reliable direction -
     * see Field_portal::customers_table()'s own doc comment). These are
     * deliberately independent of whatever date-range filter is currently
     * applied to the table below them: they always describe the complete
     * picture for this Field Executive, refreshed via AJAX
     * (Field_portal::customers_summary()) after every table redraw so
     * they never go stale, rather than being narrowed by the very date
     * filter they're meant to summarize around.
     *
     * @param  int $staff_id
     * @return array{total:int, today:int, this_month:int, this_year:int}
     */
    public function get_customer_conversion_summary($staff_id)
    {
        $prefix = db_prefix();

        $count = function ($extra_where = null) use ($prefix, $staff_id) {
            $this->db->from($prefix . 'clients');
            $this->db->join($prefix . 'leads', $prefix . 'leads.id = ' . $prefix . 'clients.leadid');
            $this->db->where($prefix . 'leads.addedfrom', $staff_id);
            if ($extra_where) {
                $this->db->where($extra_where, null, false);
            }

            return $this->db->count_all_results();
        };

        return [
            'total'      => $count(),
            'today'      => $count($prefix . "clients.converted_at BETWEEN '" . date('Y-m-d 00:00:00') . "' AND '" . date('Y-m-d 23:59:59') . "'"),
            'this_month' => $count($prefix . "clients.converted_at BETWEEN '" . date('Y-m-01 00:00:00') . "' AND '" . date('Y-m-t 23:59:59') . "'"),
            'this_year'  => $count($prefix . "clients.converted_at BETWEEN '" . date('Y-01-01 00:00:00') . "' AND '" . date('Y-12-31 23:59:59') . "'"),
        ];
    }

    /**
     * Shared SQL fragment - restricts a tbltasks query (rel_type='lead')
     * to only the leads this Field Executive created/is assigned to,
     * reusing the exact same ownership rule field_portal_my_leads_where()
     * already applies directly to tblleads - a Meeting (Task) is only
     * ever "mine" through the Lead it's linked to, never its own
     * independent ownership.
     *
     * @param  int $staff_id
     * @return string
     */
    private function my_lead_ids_subquery($staff_id)
    {
        return '(SELECT id FROM ' . db_prefix() . 'leads WHERE addedfrom = ' . (int) $staff_id . ' OR assigned = ' . (int) $staff_id . ')';
    }

    /**
     * Base query for "meetings" (tbltasks rows with rel_type='lead')
     * scoped to this Field Executive's own leads, joined to the Lead name
     * and the 2 Meeting custom fields (Meeting Time/Meeting Type, looked
     * up by name rather than hardcoded id, migration 369). Shared by
     * get_todays_meetings()/get_tomorrows_meetings()/get_overdue_meetings()
     * so the JOINs are defined exactly once.
     *
     * @param  int $staff_id
     * @return void
     */
    private function meetings_base_query($staff_id)
    {
        $time_field = get_custom_fields('tasks', ['name' => 'Meeting Time']);
        $time_field_id = !empty($time_field) ? (int) $time_field[0]['id'] : 0;

        $type_field = get_custom_fields('tasks', ['name' => 'Meeting Type']);
        $type_field_id = !empty($type_field) ? (int) $type_field[0]['id'] : 0;

        $this->db->select(db_prefix() . 'tasks.id, ' . db_prefix() . 'tasks.name, ' . db_prefix() . 'tasks.duedate, ' . db_prefix() . 'tasks.status, ' . db_prefix() . 'tasks.rel_id as lead_id, ' . db_prefix() . 'leads.name as lead_name, cfv_time.value as meeting_time, cfv_type.value as meeting_type');
        $this->db->from(db_prefix() . 'tasks');
        $this->db->join(db_prefix() . 'leads', db_prefix() . 'leads.id = ' . db_prefix() . 'tasks.rel_id', 'left');
        $this->db->join(db_prefix() . 'customfieldsvalues cfv_time', 'cfv_time.relid = ' . db_prefix() . 'tasks.id AND cfv_time.fieldid = ' . $time_field_id . " AND cfv_time.fieldto = 'tasks'", 'left');
        $this->db->join(db_prefix() . 'customfieldsvalues cfv_type', 'cfv_type.relid = ' . db_prefix() . 'tasks.id AND cfv_type.fieldid = ' . $type_field_id . " AND cfv_type.fieldto = 'tasks'", 'left');
        $this->db->where(db_prefix() . 'tasks.rel_type', 'lead');
        $this->db->where(db_prefix() . 'tasks.rel_id IN ' . $this->my_lead_ids_subquery($staff_id), null, false);
    }

    /**
     * Meetings due today, for the Dashboard's "Today's Meetings" card/panel.
     *
     * @param  int $staff_id
     * @param  int $limit 0 = every row (used for the dashboard count)
     * @return array
     */
    public function get_todays_meetings($staff_id, $limit = 5)
    {
        $this->meetings_base_query($staff_id);
        $this->db->where('DATE(' . db_prefix() . 'tasks.duedate)', date('Y-m-d'));
        $this->db->order_by(db_prefix() . 'tasks.duedate', 'asc');

        if ($limit) {
            $this->db->limit($limit);
        }

        return $this->db->get()->result_array();
    }

    /**
     * Meetings due tomorrow.
     *
     * @param  int $staff_id
     * @param  int $limit
     * @return array
     */
    public function get_tomorrows_meetings($staff_id, $limit = 5)
    {
        $this->meetings_base_query($staff_id);
        $this->db->where('DATE(' . db_prefix() . 'tasks.duedate)', date('Y-m-d', strtotime('+1 day')));
        $this->db->order_by(db_prefix() . 'tasks.duedate', 'asc');

        if ($limit) {
            $this->db->limit($limit);
        }

        return $this->db->get()->result_array();
    }

    /**
     * Meetings whose due date is in the past and are not yet marked
     * Complete - reuses Tasks_model's own STATUS_COMPLETE constant value
     * (5) rather than a second status concept.
     *
     * @param  int $staff_id
     * @param  int $limit
     * @return array
     */
    public function get_overdue_meetings($staff_id, $limit = 5)
    {
        $this->meetings_base_query($staff_id);
        $this->db->where('DATE(' . db_prefix() . 'tasks.duedate) <', date('Y-m-d'));
        $this->db->where(db_prefix() . 'tasks.status !=', Tasks_model::STATUS_COMPLETE);
        $this->db->order_by(db_prefix() . 'tasks.duedate', 'asc');

        if ($limit) {
            $this->db->limit($limit);
        }

        return $this->db->get()->result_array();
    }

    // get_lead_meetings()/get_unified_timeline() moved to the shared
    // lead_workspace_get_meetings()/lead_workspace_get_unified_timeline()
    // (application/helpers/lead_workspace_helper.php, Step 4) so
    // modules/follow_up_management's own Telecaller Lead page reads the
    // exact same data instead of a second implementation.
}
