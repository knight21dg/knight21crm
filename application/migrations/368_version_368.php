<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_368 extends CI_Migration
{
    public function up(): void
    {
        // Field Executive Portal - Create Lead form fields with no existing
        // representation anywhere in core Leads (confirmed via a live audit
        // of tblcustomfields fieldto='leads': ids 1-4 are State/District/
        // Service Required/Budget, ids 5-6 are Next Follow-up Date/Follow-up
        // Status - none of Alternate Phone/Google Maps Location/Priority/
        // Remarks exist). Added the sanctioned Perfex way (tblcustomfields,
        // same mechanism the existing 6 lead custom fields already use) so
        // they render/save through the exact same render_custom_fields()/
        // handle_custom_fields_post() core machinery everywhere a lead is
        // edited - not a portal-only bolt-on field.
        //
        // "Business Category" (from the original spec) and "Expected
        // Budget" are deliberately NOT added here - they already exist as
        // the "Service Required" (id 3) and "Budget" (id 4) custom fields
        // respectively, reused as-is by the portal's Create Lead form.
        $fields = [
            [
                'name'     => 'Alternate Phone',
                'slug'     => 'leads_alternate_phone',
                'type'     => 'text',
                'options'  => '',
                'order'    => 7,
            ],
            [
                'name'     => 'Google Maps Location',
                'slug'     => 'leads_google_maps_location',
                'type'     => 'text',
                'options'  => '',
                'order'    => 8,
            ],
            [
                'name'     => 'Priority',
                'slug'     => 'leads_priority',
                'type'     => 'select',
                'options'  => 'Low,Medium,High',
                'order'    => 9,
            ],
            [
                'name'     => 'Remarks',
                'slug'     => 'leads_remarks',
                'type'     => 'textarea',
                'options'  => '',
                'order'    => 10,
            ],
        ];

        foreach ($fields as $field) {
            $exists = $this->db->where('fieldto', 'leads')->where('slug', $field['slug'])->get(db_prefix() . 'customfields')->row();

            if ($exists) {
                continue;
            }

            $this->db->insert(db_prefix() . 'customfields', [
                'fieldto'                => 'leads',
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
    }
}
