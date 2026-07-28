<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * One shared mailable for every assignment/status-type Case notification
 * (Case Assigned, Case Reassigned, High Priority Case Assigned, Case
 * Reopened - see Follow_ups_model::notify_case_event()), rather than a
 * separate mail class per event type. Mirrors application/libraries/mails/
 * Lead_assigned.php's shape exactly (same base class, same
 * set_rel_id()/set_merge_fields() calls) - the only Follow-up-specific
 * addition is three raw merge fields for the event's own title/description/
 * link, merged alongside the lead's existing merge field set rather than
 * replacing it.
 */
class Follow_up_case_notification extends App_mail_template
{
    protected $for = 'staff';

    protected $case_id;

    protected $staff_email;

    protected $event_title;

    protected $event_description;

    public $slug = 'follow_up_case_notification';

    public $rel_type = 'lead';

    public function __construct($case_id, $staff_email, $event_title, $event_description)
    {
        parent::__construct();
        $this->case_id           = $case_id;
        $this->staff_email       = $staff_email;
        $this->event_title       = $event_title;
        $this->event_description = $event_description;
    }

    public function build()
    {
        $this->ci->load->model('follow_up_management/follow_ups_model');
        $case = $this->ci->follow_ups_model->get($this->case_id);

        $this->to($this->staff_email);

        if ($case && $case->rel_type === 'lead') {
            $this->set_rel_id($case->rel_id)
                ->set_merge_fields('leads_merge_fields', $case->rel_id);
        }

        $this->set_merge_fields([
            '{case_event_title}'       => $this->event_title,
            '{case_event_description}' => $this->event_description,
            '{case_link}'              => admin_url('follow_up_management/view/' . $this->case_id),
        ]);
    }
}
