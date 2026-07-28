<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_369 extends CI_Migration
{
    public function up(): void
    {
        // Field Executive Portal - Meeting Management (Step 3). Per the
        // approved architecture: a Meeting is a Perfex Task
        // (tbltasks.rel_type='lead'), never a new table. Only 2 new Task
        // custom fields are added - deliberately NOT one field per meeting
        // attribute (Discussion Summary/Products Discussed/Requirements/
        // Budget/Competitor Info/Next Action all live in the Task's own
        // `description` as a structured text template instead, so the
        // native Task form everywhere else in the CRM isn't cluttered with
        // Lead-meeting-only fields).
        $fields = [
            [
                'name'    => 'Meeting Time',
                'slug'    => 'tasks_meeting_time',
                'type'    => 'text',
                'options' => '',
                'order'   => 1,
            ],
            [
                'name'    => 'Meeting Type',
                'slug'    => 'tasks_meeting_type',
                'type'    => 'select',
                'options' => 'Site Visit,Follow-up Visit,Demo,Negotiation Meeting,Phone Call,Other',
                'order'   => 2,
            ],
        ];

        foreach ($fields as $field) {
            $exists = $this->db->where('fieldto', 'tasks')->where('slug', $field['slug'])->get(db_prefix() . 'customfields')->row();

            if ($exists) {
                continue;
            }

            $this->db->insert(db_prefix() . 'customfields', [
                'fieldto'                => 'tasks',
                'name'                   => $field['name'],
                'slug'                   => $field['slug'],
                'required'               => 0,
                'type'                   => $field['type'],
                'options'                => $field['options'],
                'display_inline'         => 0,
                'field_order'            => $field['order'],
                'active'                 => 1,
                'show_on_pdf'            => 0,
                'show_on_ticket_form'    => 0,
                'only_admin'             => 0,
                'show_on_table'          => 0,
                'show_on_client_portal'  => 0,
                'disalow_client_to_edit' => 0,
                'bs_column'              => 12,
                'default_value'          => '',
            ]);
        }

        // Lead Status expansion for "business progress" - New/Follow-up/
        // Interested/Lost already exist; Customer Confirmed (isdefault=1)
        // is deliberately KEPT under its current name, not renamed to
        // "Converted" - modules/follow_up_management's own status-change
        // dispatcher (follow_up_management.php) does a hardcoded
        // strtolower($new_status_name) === 'customer confirmed' string
        // match to trigger auto-conversion/case-closing; renaming this
        // status again would silently break that already-shipped
        // Telecaller workflow. "Converted" in the requested list refers to
        // this same isdefault status under its existing name.
        //
        // New order: New -> Visited -> Follow-up -> Interested ->
        // Proposal Given -> Negotiation -> Customer Confirmed -> Lost.
        $reordered = [
            'New'                => 1,
            'Follow-up'          => 3,
            'Interested'         => 4,
            'Customer Confirmed' => 7,
            'Lost'               => 8,
        ];

        foreach ($reordered as $name => $order) {
            $this->db->where('name', $name)->update(db_prefix() . 'leads_status', ['statusorder' => $order]);
        }

        $new_statuses = [
            ['name' => 'Visited',        'statusorder' => 2, 'color' => '#009688'],
            ['name' => 'Proposal Given',  'statusorder' => 5, 'color' => '#3f51b5'],
            ['name' => 'Negotiation',     'statusorder' => 6, 'color' => '#f57c00'],
        ];

        foreach ($new_statuses as $status) {
            $exists = $this->db->where('name', $status['name'])->get(db_prefix() . 'leads_status')->row();

            if ($exists) {
                continue;
            }

            $this->db->insert(db_prefix() . 'leads_status', [
                'name'        => $status['name'],
                'statusorder' => $status['statusorder'],
                'color'       => $status['color'],
                'isdefault'   => 0,
            ]);
        }
    }
}
