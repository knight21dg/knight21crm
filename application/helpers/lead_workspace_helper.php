<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Shared Lead Workspace components - Field Executive Portal Step 4.
 *
 * Both modules/field_portal and modules/follow_up_management now render
 * the same Lead data (Meetings, unified Timeline, ownership/reporting
 * overview) on their own Lead Details pages, with different actions
 * layered on top per role. Rather than duplicate the timeline-merge
 * query, the meeting+comments+attachments enrichment, or the panel
 * markup in both modules, both the data functions and the render
 * functions live here once - same precedent as
 * application/helpers/staff_dashboard_helper.php for the 3 portal
 * dashboards.
 */

/**
 * Notify every active Admin of a Lead-lifecycle event (Converted, Lost) -
 * shared by modules/field_portal (lead_converted_to_customer hook) and
 * modules/follow_up_management (the Lead status dispatcher's 'lost'
 * branch) so both call the same "get every active admin" lookup
 * (application/helpers/clients_helper.php's own get_admins()-adjacent
 * precedent: staff_model->get('', ['active'=>1,'admin'=>1])) rather than
 * two copies of it.
 *
 * @param  string $description_key
 * @param  string $link
 * @param  array  $additional_data
 * @return void
 */
function lead_workspace_notify_admins($description_key, $link, $additional_data = [])
{
    $CI = &get_instance();
    $CI->load->model('staff_model');
    $admins = $CI->staff_model->get('', ['active' => 1, 'admin' => 1]);

    foreach ($admins as $admin) {
        if ($admin['staffid'] == get_staff_user_id()) {
            continue;
        }

        add_notification([
            'fromcompany'     => 1,
            'touserid'        => $admin['staffid'],
            'description'     => $description_key,
            'link'            => $link,
            'additional_data' => serialize($additional_data),
        ]);
    }

    if (!empty($admins)) {
        pusher_trigger_notification(array_column($admins, 'staffid'));
    }
}

/**
 * Every meeting (tbltasks, rel_type='lead') logged against one Lead,
 * enriched with its comments and non-comment attachments. Also fetches
 * the Lead's Google Maps Location once and attaches it to every meeting
 * row for display.
 *
 * @param  int $lead_id
 * @return array
 */
function lead_workspace_get_meetings($lead_id)
{
    $CI = &get_instance();
    $CI->load->model('tasks_model');

    $time_field    = get_custom_fields('tasks', ['name' => 'Meeting Time']);
    $time_field_id = !empty($time_field) ? (int) $time_field[0]['id'] : 0;

    $type_field    = get_custom_fields('tasks', ['name' => 'Meeting Type']);
    $type_field_id = !empty($type_field) ? (int) $type_field[0]['id'] : 0;

    $CI->db->select(db_prefix() . 'tasks.*, cfv_time.value as meeting_time, cfv_type.value as meeting_type');
    $CI->db->from(db_prefix() . 'tasks');
    $CI->db->join(db_prefix() . 'customfieldsvalues cfv_time', 'cfv_time.relid = ' . db_prefix() . 'tasks.id AND cfv_time.fieldid = ' . $time_field_id . " AND cfv_time.fieldto = 'tasks'", 'left');
    $CI->db->join(db_prefix() . 'customfieldsvalues cfv_type', 'cfv_type.relid = ' . db_prefix() . 'tasks.id AND cfv_type.fieldid = ' . $type_field_id . " AND cfv_type.fieldto = 'tasks'", 'left');
    $CI->db->where(db_prefix() . 'tasks.rel_type', 'lead');
    $CI->db->where(db_prefix() . 'tasks.rel_id', $lead_id);
    $CI->db->order_by(db_prefix() . 'tasks.duedate', 'desc');
    $meetings = $CI->db->get()->result_array();

    foreach ($meetings as &$meeting) {
        $meeting['comments'] = $CI->tasks_model->get_task_comments($meeting['id']);

        $attachments = $CI->tasks_model->get_task_attachments($meeting['id']);
        $meeting['attachments'] = array_filter($attachments, function ($attachment) {
            return empty($attachment['task_comment_id']);
        });
    }
    unset($meeting);

    return $meetings;
}

/**
 * The unified chronological activity stream for a Lead - merges
 * tbllead_activity_log (system events) with tblnotes (free-text notes)
 * and tblfollowup_history (telecaller interactions). Moved here from
 * Field_portal_model::get_unified_timeline() so both portals share it.
 *
 * @param  int $lead_id
 * @return array each entry: ['sort_date' => ..., 'type' => 'activity'|'note'|'followup', ...row fields]
 */
function lead_workspace_get_unified_timeline($lead_id)
{
    $CI = &get_instance();
    $CI->load->model('leads_model');
    $CI->load->model('misc_model');

    $activity = $CI->leads_model->get_lead_activity_log($lead_id);
    $notes    = $CI->misc_model->get_notes($lead_id, 'lead');

    $timeline = [];

    foreach ($activity as $entry) {
        $entry['type'] = 'activity';
        $entry['sort_date'] = $entry['date'];
        $timeline[] = $entry;
    }

    foreach ($notes as $note) {
        $note['type'] = 'note';
        $note['sort_date'] = $note['dateadded'];
        $timeline[] = $note;
    }

    foreach (lead_workspace_get_followup_history($lead_id) as $fh) {
        $fh['type'] = 'followup';
        $fh['sort_date'] = $fh['event_datetime'];
        $timeline[] = $fh;
    }

    usort($timeline, function ($a, $b) {
        return strtotime($b['sort_date']) <=> strtotime($a['sort_date']);
    });

    return $timeline;
}

/**
 * All follow-up history entries across every Case this Lead has ever had,
 * enriched with staff names and parsed notes. Reuses the existing
 * tblfollowup_history table - never creates a second history store.
 *
 * @param  int $lead_id
 * @return array
 */
function lead_workspace_get_followup_history($lead_id)
{
    $CI = &get_instance();
    $prefix = db_prefix();

    $CI->db->select(
        $prefix . 'followup_history.*, ' .
        "CONCAT(telecaller.firstname, ' ', telecaller.lastname) as telecaller_name, " .
        $prefix . 'followups.next_follow_up_date as case_next_follow_up, ' .
        $prefix . 'followups.outcome as case_outcome'
    );
    $CI->db->from($prefix . 'followup_history');
    $CI->db->join($prefix . 'followups', $prefix . 'followups.id = ' . $prefix . 'followup_history.followup_id');
    $CI->db->join($prefix . 'staff telecaller', 'telecaller.staffid = ' . $prefix . 'followup_history.created_by', 'left');
    $CI->db->where($prefix . 'followups.rel_id', $lead_id);
    $CI->db->where($prefix . 'followups.rel_type', 'lead');
    $CI->db->order_by($prefix . 'followup_history.event_datetime', 'desc');

    $result = $CI->db->get()->result_array();

    foreach ($result as &$entry) {
        $entry['parsed_notes'] = lead_workspace_parse_followup_notes($entry['notes'] ?? '');
    }
    unset($entry);

    return $result;
}

/**
 * Parse the merged notes string from tblfollowup_history into structured fields.
 * The notes are stored as a multiline "Label: Value" format.
 *
 * @param  string $notes
 * @return array
 */
function lead_workspace_parse_followup_notes($notes)
{
    $parsed = [
        'call_date'         => '',
        'call_time'         => '',
        'customer_response' => '',
        'discussion_summary' => '',
        'remarks'            => '',
    ];

    if (empty(trim($notes))) {
        return $parsed;
    }

    $sections = explode("\n\n", $notes);
    foreach ($sections as $section) {
        $section = trim($section);
        if (empty($section)) {
            continue;
        }
        $colon_pos = strpos($section, ':');
        if ($colon_pos === false) {
            continue;
        }
        $label = trim(substr($section, 0, $colon_pos));
        $value = trim(substr($section, $colon_pos + 1));

        switch ($label) {
            case 'Call Date/Time':
                $parts = array_map('trim', explode(' ', $value, 2));
                $parsed['call_date'] = $parts[0] ?? '';
                $parsed['call_time'] = $parts[1] ?? '';
                break;
            case 'Customer Response':
                $parsed['customer_response'] = $value;
                break;
            case 'Discussion Summary':
                $parsed['discussion_summary'] = $value;
                break;
            case 'Remarks':
                $parsed['remarks'] = $value;
                break;
        }
    }

    return $parsed;
}

/**
 * Cross-portal reporting overview for one Lead - Original Field
 * Executive, Current Handler, Total Meetings, Total Calls, Total Notes,
 * Total Attachments, Lead Age, and (once applicable) Lost Reason /
 * Converted By/Date. Also includes upcoming follow-up date and last
 * activity timestamp.
 *
 * @param  int $lead_id
 * @return array
 */
function lead_workspace_get_overview_stats($lead_id)
{
    $CI = &get_instance();
    $prefix = db_prefix();

    $CI->db->select('addedfrom, assigned, dateadded, date_converted, converted_by, client_id, status');
    $CI->db->where('id', $lead_id);
    $lead = $CI->db->get($prefix . 'leads')->row();

    if (!$lead) {
        return [];
    }

    $CI->db->where('rel_type', 'lead');
    $CI->db->where('rel_id', $lead_id);
    $total_meetings = (int) $CI->db->count_all_results($prefix . 'tasks');

    $case_ids_subquery = '(SELECT id FROM ' . $prefix . "followups WHERE rel_id = " . (int) $lead_id . " AND rel_type = 'lead')";

    $CI->db->where('followup_id IN ' . $case_ids_subquery, null, false);
    $CI->db->where('activity_type', 'call');
    $total_calls = (int) $CI->db->count_all_results($prefix . 'followup_history');

    $CI->db->where('rel_id', $lead_id);
    $CI->db->where('rel_type', 'lead');
    $total_notes = (int) $CI->db->count_all_results($prefix . 'notes');

    $CI->db->where('rel_id', $lead_id);
    $CI->db->where('rel_type', 'lead');
    $total_attachments = (int) $CI->db->count_all_results($prefix . 'files');

    $CI->db->select('next_follow_up_date');
    $CI->db->where('rel_id', $lead_id);
    $CI->db->where('rel_type', 'lead');
    $CI->db->where('case_status', 'open');
    $CI->db->order_by('next_follow_up_date', 'asc');
    $CI->db->limit(1);
    $open_case = $CI->db->get($prefix . 'followups')->row();

    $CI->db->select_max('date', 'last_activity_date');
    $CI->db->where('leadid', $lead_id);
    $last_activity_row = $CI->db->get($prefix . 'lead_activity_log')->row();

    $CI->db->select('lost_reason');
    $CI->db->where('rel_id', $lead_id);
    $CI->db->where('rel_type', 'lead');
    $CI->db->where('lost_reason IS NOT NULL', null, false);
    $CI->db->order_by('id', 'desc');
    $CI->db->limit(1);
    $lost_case = $CI->db->get($prefix . 'followups')->row();

    $current_status_name = '';
    $CI->db->select('name');
    $CI->db->where('id', $lead->status);
    $status_row = $CI->db->get($prefix . 'leads_status')->row();
    $current_status_name = $status_row ? $status_row->name : '';

    return [
        'original_owner_id'     => (int) $lead->addedfrom,
        'original_owner_name'   => get_staff_full_name($lead->addedfrom),
        'current_handler_id'    => (int) $lead->assigned,
        'current_handler_name'  => $lead->assigned ? get_staff_full_name($lead->assigned) : '',
        'current_status_name'   => $current_status_name,
        'total_meetings'        => $total_meetings,
        'total_calls'           => $total_calls,
        'total_notes'           => $total_notes,
        'total_attachments'     => $total_attachments,
        'lead_age_days'         => max(0, (int) floor((time() - strtotime($lead->dateadded)) / 86400)),
        'upcoming_followup'     => $open_case ? $open_case->next_follow_up_date : '',
        'last_activity'         => $last_activity_row->last_activity_date ?? '',
        'lost_reason'           => $lost_case ? $lost_case->lost_reason : '',
        'is_converted'          => (int) $lead->client_id > 0,
        'date_converted'        => $lead->date_converted,
        'converted_by_name'     => $lead->converted_by ? get_staff_full_name($lead->converted_by) : '',
    ];
}

/**
 * Lightweight customer data for the Customer Summary card.
 *
 * @param  int $lead_id
 * @return array|null
 */
function lead_workspace_get_customer_data($lead_id)
{
    $CI = &get_instance();
    $prefix = db_prefix();

    $CI->db->select(
        $prefix . 'clients.userid as client_id, ' .
        $prefix . 'clients.company, ' .
        $prefix . 'clients.phonenumber, ' .
        $prefix . 'clients.address, ' .
        $prefix . 'clients.active, ' .
        $prefix . 'leads.date_converted, ' .
        $prefix . 'leads.name as lead_name, ' .
        "CONCAT(admin_staff.firstname, ' ', admin_staff.lastname) as admin_name"
    );
    $CI->db->from($prefix . 'leads');
    $CI->db->join($prefix . 'clients', $prefix . 'clients.userid = ' . $prefix . 'leads.client_id', 'left');
    $CI->db->join($prefix . 'staff admin_staff', 'admin_staff.staffid = ' . $prefix . 'clients.addedfrom', 'left');
    $CI->db->where($prefix . 'leads.id', $lead_id);
    $CI->db->where($prefix . 'leads.client_id >', 0);
    $CI->db->limit(1);

    $result = $CI->db->get()->row_array();

    return !empty($result) && $result['client_id'] ? $result : null;
}

/**
 * Canonical lead progress stages mapped to status IDs.
 * Order matters - defines the visual progression.
 */
function lead_workspace_progress_stages()
{
    $CI = &get_instance();
    $CI->db->select('id, name');
    $statuses = $CI->db->get(db_prefix() . 'leads_status')->result_array();

    $ordered = [];
    $desired = ['New', 'Visited', 'Follow-up', 'Interested', 'Proposal Given', 'Negotiation', 'Customer Confirmed'];

    $name_to_id = [];
    foreach ($statuses as $s) {
        $name_to_id[$s['name']] = (int) $s['id'];
    }

    foreach ($desired as $name) {
        if (isset($name_to_id[$name])) {
            $ordered[] = ['id' => $name_to_id[$name], 'name' => $name];
        }
    }

    return $ordered;
}

/**
 * Renders the "Timeline" panel - now includes activity events, notes,
 * and follow-up history entries in chronological order.
 *
 * @param  array $timeline
 * @return void
 */
function render_lead_workspace_timeline($timeline)
{
    ?>
    <div class="panel_s">
        <div class="panel-body">
            <h5 class="no-margin tw-mb-3"><i class="fa-solid fa-clock-rotate-left text-muted"></i> <?= _l('field_portal_timeline'); ?></h5>
            <?php if (empty($timeline)) { ?>
            <span class="text-muted"><?= _l('field_portal_no_activity'); ?></span>
            <?php } else { ?>
            <ul class="list-unstyled">
                <?php foreach ($timeline as $entry) {
                    if ($entry['type'] === 'note') {
                        $icon = 'fa-note-sticky';
                        $who  = trim(($entry['firstname'] ?? '') . ' ' . ($entry['lastname'] ?? ''));
                        $text = ($who !== '' ? e($who) . ' - ' : '') . _l('field_portal_timeline_note_added') . ': ' . nl2br(e($entry['description']));
                    } elseif ($entry['type'] === 'followup') {
                        $icon = 'fa-phone-volume';
                        $outcome_label = $entry['outcome'] ? get_followup_outcome_label($entry['outcome']) : '';
                        $tc_name = $entry['telecaller_name'] ?? '-';
                        $text = _l('field_portal_followup_history_entry', [$tc_name, $outcome_label]);
                    } else {
                        $icon = 'fa-clock-rotate-left';
                        $additional_data = !empty($entry['additional_data']) ? unserialize($entry['additional_data']) : '';
                        $activity_text   = $additional_data !== '' ? _l($entry['description'], $additional_data) : e(_l($entry['description']));
                        $text = (!empty($entry['full_name']) ? e($entry['full_name']) . ' - ' : '') . $activity_text;
                    }
                ?>
                <li class="tw-mb-2 tw-pb-2" style="border-bottom:1px solid #eee;">
                    <div><i class="fa-solid <?= $icon; ?> text-muted tw-text-xs"></i> <?= $text; ?></div>
                    <span class="text-muted tw-text-xs"><?= e(_dt($entry['sort_date'])); ?></span>
                </li>
                <?php } ?>
            </ul>
            <?php } ?>
        </div>
    </div>
    <?php
}

/**
 * Renders the "Meetings" panel with enhanced display: Meeting Type,
 * Meeting Date, Meeting Time, Status, Location (from lead's Google Maps),
 * Notes count, Attachments count, Meeting Details.
 *
 * @param  array  $meetings
 * @param  int    $lead_id
 * @param  array  $task_statuses
 * @param  bool   $readonly
 * @param  string $maps_location  Lead's Google Maps Location value
 * @return void
 */
function render_lead_workspace_meetings($meetings, $lead_id, $task_statuses, $readonly = false, $maps_location = '')
{
    ?>
    <div class="panel_s">
        <div class="panel-body">
            <div class="tw-flex tw-items-center tw-justify-between tw-mb-3">
                <h5 class="no-margin"><i class="fa-solid fa-calendar-check text-muted"></i> <?= _l('field_portal_meetings'); ?></h5>
                <?php if (!$readonly) { ?>
                <a href="<?= admin_url('field_portal/lead_add_meeting/' . $lead_id); ?>" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus"></i> <?= _l('field_portal_log_meeting'); ?></a>
                <?php } ?>
            </div>

            <?php if (empty($meetings)) { ?>
            <span class="text-muted"><?= _l('field_portal_no_meetings'); ?></span>
            <?php } else { foreach ($meetings as $meeting) { ?>
            <div class="tw-mb-3 tw-pb-3" style="border-bottom:1px solid #eee;" id="meeting-<?= (int) $meeting['id']; ?>">
                <div class="tw-flex tw-items-center tw-justify-between tw-flex-wrap">
                    <div>
                        <span class="tw-font-medium"><?= e($meeting['name']); ?></span>
                        <span class="text-muted tw-text-xs tw-ml-2">
                            <?php if (!empty($meeting['meeting_type'])) { ?><span class="label label-default tw-mr-1"><?= e($meeting['meeting_type']); ?></span><?php } ?>
                            <?= $meeting['duedate'] ? e(_d($meeting['duedate'])) : '-'; ?>
                            <?php if (!empty($meeting['meeting_time'])) { ?> &middot; <?= e($meeting['meeting_time']); ?><?php } ?>
                        </span>
                    </div>
                    <?php if ($readonly) { ?>
                    <?= format_task_status((int) $meeting['status']); ?>
                    <?php } else { ?>
                    <select class="form-control input-sm tw-w-auto" onchange="field_portal_meeting_status(<?= (int) $meeting['id']; ?>, this.value)">
                        <?php foreach ($task_statuses as $status) { ?>
                        <option value="<?= (int) $status['id']; ?>" <?= ((int) $status['id'] === (int) $meeting['status']) ? 'selected' : ''; ?>><?= e($status['name']); ?></option>
                        <?php } ?>
                    </select>
                    <?php } ?>
                </div>

                <?php if ($maps_location) { ?>
                <div class="tw-mt-1 tw-text-xs text-muted"><i class="fa-solid fa-location-dot"></i> <?= e($maps_location); ?></div>
                <?php } ?>

                <?php if ($meeting['description']) { ?>
                <div class="tw-mt-2" style="white-space:pre-line;"><?= nl2br(e($meeting['description'])); ?></div>
                <?php } ?>

                <div class="tw-mt-2">
                    <a href="javascript:void(0);" onclick="$('#meeting-details-<?= (int) $meeting['id']; ?>').toggle();" class="tw-text-xs">
                        <i class="fa-solid fa-comments"></i> <?= _l('field_portal_meeting_notes_attachments'); ?>
                        (<?= (int) count($meeting['comments']); ?> <?= _l('field_portal_notes'); ?>, <?= (int) count($meeting['attachments']); ?> <?= _l('field_portal_attachments'); ?>)
                    </a>
                </div>

                <div id="meeting-details-<?= (int) $meeting['id']; ?>" style="display:none;" class="tw-mt-2 tw-pl-3" style="border-left:2px solid #eee;">
                    <?php if (!$readonly) { ?>
                    <div class="tw-flex tw-gap-2 tw-mb-2">
                        <textarea id="meeting-note-input-<?= (int) $meeting['id']; ?>" class="form-control input-sm" rows="1" placeholder="<?= _l('field_portal_add_note_placeholder'); ?>"></textarea>
                        <button type="button" class="btn btn-default btn-sm" onclick="field_portal_meeting_add_note(<?= (int) $meeting['id']; ?>)"><i class="fa-solid fa-paper-plane"></i></button>
                        <label class="btn btn-default btn-sm no-margin" style="cursor:pointer;">
                            <i class="fa-solid fa-paperclip"></i>
                            <input type="file" id="meeting-attachment-input-<?= (int) $meeting['id']; ?>" multiple style="display:none;" onchange="field_portal_meeting_upload_attachment(<?= (int) $meeting['id']; ?>)">
                        </label>
                    </div>
                    <?php } ?>

                    <?php foreach ($meeting['attachments'] as $attachment) { ?>
                    <div class="tw-flex tw-items-center tw-justify-between tw-mb-1" id="meeting-attachment-<?= (int) $attachment['id']; ?>">
                        <a href="<?= base_url('uploads/tasks/' . $meeting['id'] . '/' . $attachment['file_name']); ?>" target="_blank"><i class="fa-solid fa-file tw-text-gray-400"></i> <?= e($attachment['file_name']); ?></a>
                        <?php if (!$readonly) { ?>
                        <button type="button" class="btn btn-link btn-xs text-danger" onclick="field_portal_meeting_delete_attachment(<?= (int) $attachment['id']; ?>, <?= (int) $meeting['id']; ?>)"><i class="fa-solid fa-trash"></i></button>
                        <?php } ?>
                    </div>
                    <?php } ?>

                    <?php foreach ($meeting['comments'] as $comment) { ?>
                    <div class="tw-mb-1">
                        <span class="tw-font-medium tw-text-xs"><?= e(trim($comment['firstname'] . ' ' . $comment['lastname'])); ?></span>
                        <span class="text-muted tw-text-xs"><?= e(_dt($comment['dateadded'])); ?></span>
                        <div class="tw-text-xs"><?= nl2br(e($comment['content'])); ?></div>
                    </div>
                    <?php } ?>
                </div>
            </div>
            <?php } } ?>
        </div>
    </div>
    <?php
}

/**
 * Renders the "Lead Overview" reporting panel - expanded with total
 * notes, total attachments, upcoming follow-up, and last activity.
 *
 * @param  array $stats
 * @return void
 */
function render_lead_workspace_overview($stats)
{
    if (empty($stats)) {
        return;
    }
    ?>
    <div class="panel_s">
        <div class="panel-body">
            <h5 class="no-margin tw-mb-3"><i class="fa-solid fa-chart-simple text-muted"></i> <?= _l('field_portal_lead_overview'); ?></h5>
            <div class="row">
                <div class="col-xs-6 tw-mb-3">
                    <div class="text-muted"><?= _l('field_portal_original_field_executive'); ?></div>
                    <div><?= e($stats['original_owner_name']); ?></div>
                </div>
                <div class="col-xs-6 tw-mb-3">
                    <div class="text-muted"><?= _l('field_portal_current_handler'); ?></div>
                    <div><?= $stats['current_handler_name'] ? e($stats['current_handler_name']) : '-'; ?></div>
                </div>
                <div class="col-xs-6 tw-mb-3">
                    <div class="text-muted"><?= _l('field_portal_column_current_status'); ?></div>
                    <div><?= e($stats['current_status_name']); ?></div>
                </div>
                <div class="col-xs-6 tw-mb-3">
                    <div class="text-muted"><?= _l('field_portal_lead_age'); ?></div>
                    <div><?= (int) $stats['lead_age_days']; ?> <?= _l('field_portal_days'); ?></div>
                </div>
                <div class="col-xs-6 tw-mb-3">
                    <div class="text-muted"><?= _l('field_portal_total_meetings'); ?></div>
                    <div><?= (int) $stats['total_meetings']; ?></div>
                </div>
                <div class="col-xs-6 tw-mb-3">
                    <div class="text-muted"><?= _l('field_portal_total_calls'); ?></div>
                    <div><?= (int) $stats['total_calls']; ?></div>
                </div>
                <div class="col-xs-6 tw-mb-3">
                    <div class="text-muted"><?= _l('field_portal_total_notes'); ?></div>
                    <div><?= (int) $stats['total_notes']; ?></div>
                </div>
                <div class="col-xs-6 tw-mb-3">
                    <div class="text-muted"><?= _l('field_portal_total_attachments'); ?></div>
                    <div><?= (int) $stats['total_attachments']; ?></div>
                </div>
                <div class="col-xs-6 tw-mb-3">
                    <div class="text-muted"><?= _l('field_portal_field_next_followup'); ?></div>
                    <div><?= $stats['upcoming_followup'] ? e(_d($stats['upcoming_followup'])) : '-'; ?></div>
                </div>
                <div class="col-xs-6 tw-mb-3">
                    <div class="text-muted"><?= _l('field_portal_column_last_activity'); ?></div>
                    <div><?= $stats['last_activity'] ? e(_dt($stats['last_activity'])) : '-'; ?></div>
                </div>
                <?php if ($stats['is_converted']) { ?>
                <div class="col-xs-6 tw-mb-3">
                    <div class="text-muted"><?= _l('field_portal_converted_by'); ?></div>
                    <div><?= $stats['converted_by_name'] ? e($stats['converted_by_name']) : '-'; ?> <?= $stats['date_converted'] ? '(' . e(_d($stats['date_converted'])) . ')' : ''; ?></div>
                </div>
                <?php } ?>
                <?php if ($stats['lost_reason']) { ?>
                <div class="col-xs-12 tw-mb-3">
                    <div class="text-muted"><?= _l('field_portal_lost_reason'); ?></div>
                    <div><?= nl2br(e($stats['lost_reason'])); ?></div>
                </div>
                <?php } ?>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Renders a visual progress tracker showing the lead's lifecycle:
 * New → Visited → Follow-up → Interested → Proposal Given → Negotiation → Customer Confirmed → Converted
 * The current stage is highlighted; previous stages are marked complete.
 *
 * @param  string $current_status_name
 * @param  bool   $is_converted
 * @param  string $lost_reason
 * @return void
 */
function render_lead_workspace_progress_tracker($current_status_name, $is_converted, $lost_reason = '')
{
    $stages = lead_workspace_progress_stages();
    if (empty($stages)) {
        return;
    }

    $current_idx = -1;
    foreach ($stages as $i => $stage) {
        if ($stage['name'] === $current_status_name) {
            $current_idx = $i;
            break;
        }
    }
    ?>
    <div class="panel_s">
        <div class="panel-body">
            <h5 class="no-margin tw-mb-3"><i class="fa-solid fa-road text-muted"></i> <?= _l('field_portal_lead_progress'); ?></h5>
            <div class="tw-flex tw-items-start tw-gap-0" style="overflow-x:auto;">
                <?php foreach ($stages as $i => $stage) {
                    $is_completed = $i < $current_idx;
                    $is_current   = $i === $current_idx;
                    $is_future    = $i > $current_idx;

                    if ($is_completed) {
                        $dot_class = 'tw-bg-green-500';
                        $text_class = 'tw-text-green-600';
                    } elseif ($is_current) {
                        $dot_class = 'tw-bg-blue-600';
                        $text_class = 'tw-font-medium tw-text-blue-600';
                    } else {
                        $dot_class = 'tw-bg-gray-300';
                        $text_class = 'tw-text-gray-400';
                    }
                ?>
                <div class="tw-flex tw-flex-col tw-items-center tw-text-center" style="min-width:80px;flex-shrink:0;">
                    <div class="tw-w-4 tw-h-4 tw-rounded-full <?= $dot_class; ?> tw-border-2 tw-border-white tw-shadow-sm"></div>
                    <span class="tw-text-xs tw-mt-1 <?= $text_class; ?>"><?= e($stage['name']); ?></span>
                    <?php if ($i < count($stages) - 1) { ?>
                    <div class="tw-w-full tw-h-0.5 tw-mt-2 <?= $i < $current_idx ? 'tw-bg-green-500' : 'tw-bg-gray-300'; ?>" style="margin-top:-0.5rem;position:relative;z-index:-1;"></div>
                    <?php } ?>
                </div>
                <?php } ?>
                <?php if ($is_converted) { ?>
                <div class="tw-flex tw-flex-col tw-items-center tw-text-center" style="min-width:80px;flex-shrink:0;">
                    <div class="tw-w-4 tw-h-4 tw-rounded-full tw-bg-green-500 tw-border-2 tw-border-white tw-shadow-sm"></div>
                    <span class="tw-text-xs tw-mt-1 tw-text-green-700 tw-font-medium"><?= _l('field_portal_converted'); ?></span>
                </div>
                <?php } ?>
            </div>
            <?php if ($lost_reason) { ?>
            <div class="tw-mt-2 tw-text-xs tw-text-danger">
                <i class="fa-solid fa-circle-xmark"></i> <?= _l('field_portal_lost_reason'); ?>: <?= e($lost_reason); ?>
            </div>
            <?php } ?>
        </div>
    </div>
    <?php
}

/**
 * Renders the Customer Summary card for converted leads.
 *
 * @param  array|null $customer
 * @return void
 */
function render_lead_workspace_customer_summary($customer)
{
    if (!$customer) {
        return;
    }
    ?>
    <div class="panel_s">
        <div class="panel-body">
            <h5 class="no-margin tw-mb-3"><i class="fa-solid fa-building text-muted"></i> <?= _l('field_portal_customer_summary'); ?></h5>
            <div class="row">
                <div class="col-xs-6 tw-mb-3">
                    <div class="text-muted"><?= _l('field_portal_column_customer'); ?></div>
                    <div><?= e($customer['company'] ?: $customer['lead_name']); ?></div>
                </div>
                <div class="col-xs-6 tw-mb-3">
                    <div class="text-muted"><?= _l('field_portal_field_company'); ?></div>
                    <div><?= $customer['company'] ? e($customer['company']) : '-'; ?></div>
                </div>
                <div class="col-xs-6 tw-mb-3">
                    <div class="text-muted"><?= _l('field_portal_column_converted_date'); ?></div>
                    <div><?= $customer['date_converted'] ? e(_d($customer['date_converted'])) : '-'; ?></div>
                </div>
                <div class="col-xs-6 tw-mb-3">
                    <div class="text-muted"><?= _l('field_portal_managed_by_admin'); ?></div>
                    <div><?= $customer['admin_name'] ? e($customer['admin_name']) : '-'; ?></div>
                </div>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Renders the Follow-up History table - read-only, showing every
 * interaction logged by the Telecaller for this Lead.
 *
 * @param  array $history
 * @return void
 */
function render_lead_workspace_followup_history($history)
{
    ?>
    <div class="panel_s">
        <div class="panel-body">
            <h5 class="no-margin tw-mb-3"><i class="fa-solid fa-phone-volume text-muted"></i> <?= _l('field_portal_followup_history'); ?></h5>
            <?php if (empty($history)) { ?>
            <span class="text-muted"><?= _l('field_portal_no_followup_history'); ?></span>
            <?php } else { ?>
            <div class="table-responsive">
                <table class="table table-condensed table-hover tw-text-xs">
                    <thead>
                        <tr>
                            <th><?= _l('field_portal_column_date'); ?></th>
                            <th><?= _l('field_portal_column_telecaller'); ?></th>
                            <th><?= _l('field_portal_column_outcome'); ?></th>
                            <th><?= _l('field_portal_column_customer_response'); ?></th>
                            <th><?= _l('field_portal_column_discussion_summary'); ?></th>
                            <th><?= _l('field_portal_column_remarks'); ?></th>
                            <th><?= _l('field_portal_column_next_followup'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($history as $entry) {
                            $parsed = $entry['parsed_notes'];
                            $outcome_key = $entry['outcome'] ?? '';
                            $outcome_label = $outcome_key ? get_followup_outcome_label($outcome_key) : '-';
                            $next_fu = $entry['case_next_follow_up'] ?? '';
                        ?>
                        <tr>
                            <td><?= e(_dt($entry['event_datetime'])); ?></td>
                            <td><?= e($entry['telecaller_name'] ?? '-'); ?></td>
                            <td><?= e($outcome_label); ?></td>
                            <td><?= $parsed['customer_response'] ? nl2br(e($parsed['customer_response'])) : '-'; ?></td>
                            <td><?= $parsed['discussion_summary'] ? nl2br(e($parsed['discussion_summary'])) : '-'; ?></td>
                            <td><?= $parsed['remarks'] ? nl2br(e($parsed['remarks'])) : '-'; ?></td>
                            <td><?= $next_fu ? e(_d($next_fu)) : '-'; ?></td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
            <?php } ?>
        </div>
    </div>
    <?php
}

/**
 * Human-readable label for a "Converted From" classification key.
 *
 * @param  string $role
 * @return string
 */
function lead_converted_from_role_label($role)
{
    $labels = [
        'field_executive' => _l('customer_converted_from_field_executive'),
        'telecaller'       => _l('customer_converted_from_telecaller'),
        'admin'            => _l('customer_converted_from_admin'),
        'other'            => _l('customer_converted_from_other'),
    ];

    return $labels[$role] ?? $labels['other'];
}

/**
 * "Converted From" for the Customers table - a PLAIN READ of
 * tblclients.converted_from_role, written once by
 * Leads_model::record_conversion_metadata() at the exact moment a
 * customer is created from a Lead (application/models/Leads_model.php).
 *
 * This deliberately does NOT reconstruct history from tblleads/
 * tblfollowups at read time - that reconstruction is what produced a
 * different answer every time its query changed (2 -> 0 -> 1 -> 9 Field
 * Executive customers across four rounds), even though the underlying
 * data never changed. The classification is now a recorded fact, not a
 * question re-asked on every page load. A customer with no stored value
 * (created directly, no Lead involved) reads as 'admin' via a static
 * default - not a guess, since "no Lead" unambiguously means "created
 * directly through the native Admin form."
 *
 * @return string
 */
function lead_converted_from_sql_case()
{
    return "COALESCE(" . db_prefix() . "clients.converted_from_role, 'admin')";
}

/**
 * "Converted By" for the Customers table - a PLAIN READ of the staff name
 * behind tblclients.converted_by_staff_id, written once at conversion
 * time alongside converted_from_role (same source, same moment - see
 * lead_converted_from_sql_case()'s doc comment). Falls back to
 * tblclients.addedfrom (always populated, since Clients_model::add() sets
 * it unconditionally on every insert) only for customers with no
 * conversion metadata at all - i.e. no Lead was ever involved. Resolved to
 * the name directly in SQL (not just the id) so DataTables' generic
 * search box and column sort can match/order by the name itself.
 *
 * @return string
 */
function lead_converted_by_name_expr()
{
    $prefix = db_prefix();

    return "(SELECT CONCAT(lcbn_staff.firstname, ' ', lcbn_staff.lastname) FROM {$prefix}staff lcbn_staff WHERE lcbn_staff.staffid = COALESCE({$prefix}clients.converted_by_staff_id, {$prefix}clients.addedfrom))";
}

/**
 * Resolves the Customer Conversion Report's date-range filter into
 * concrete [start, end] datetime bounds for a tblclients.converted_at
 * comparison. Resolved entirely server-side (never trusts a client-
 * computed date literal) so both callers - the Customers table's own
 * WHERE clause (application/views/admin/tables/clients.php) and the KPI
 * summary endpoint (Clients::conversion_summary()) - always agree on what
 * "This Month" etc. actually means, from one place.
 *
 * @param  string      $range_key    'today'|'yesterday'|'this_week'|'this_month'|'this_year'|'custom', or '' for no filter
 * @param  string|null $custom_from  site-display-format date, only used when $range_key === 'custom'
 * @param  string|null $custom_to    site-display-format date, only used when $range_key === 'custom'
 * @return array{0:string,1:string}|null [start 'Y-m-d 00:00:00', end 'Y-m-d 23:59:59'], or null for no filter
 */
function customer_conversion_date_bounds($range_key, $custom_from = null, $custom_to = null)
{
    switch ($range_key) {
        case 'today':
            $start = $end = date('Y-m-d');
            break;
        case 'yesterday':
            $start = $end = date('Y-m-d', strtotime('-1 day'));
            break;
        case 'this_week':
            $start = date('Y-m-d', strtotime('monday this week'));
            $end   = date('Y-m-d', strtotime('sunday this week'));
            break;
        case 'this_month':
            $start = date('Y-m-01');
            $end   = date('Y-m-t');
            break;
        case 'this_year':
            $start = date('Y-01-01');
            $end   = date('Y-12-31');
            break;
        case 'custom':
            if (!$custom_from || !$custom_to) {
                return null;
            }
            $start = to_sql_date($custom_from);
            $end   = to_sql_date($custom_to);
            break;
        default:
            return null;
    }

    return [$start . ' 00:00:00', $end . ' 23:59:59'];
}

/**
 * SQL WHERE fragment for customer_conversion_date_bounds() - a raw
 * boolean expression against tblclients.converted_at, or null when no
 * date filter is active. See that function's doc comment for why this is
 * the single shared resolver for both the table and the KPI endpoint.
 *
 * @param  string      $range_key
 * @param  string|null $custom_from
 * @param  string|null $custom_to
 * @return string|null
 */
function customer_conversion_date_where($range_key, $custom_from = null, $custom_to = null)
{
    $bounds = customer_conversion_date_bounds($range_key, $custom_from, $custom_to);

    if (!$bounds) {
        return null;
    }

    return db_prefix() . "clients.converted_at BETWEEN '" . $bounds[0] . "' AND '" . $bounds[1] . "'";
}
