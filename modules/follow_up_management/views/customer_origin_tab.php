<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
/**
 * Customer Origin tab content - read-only metadata about how this
 * customer originated from a lead. The native Clients::client() controller
 * renders tab views directly (no per-tab AJAX hook to inject $origin), so
 * this tab fetches its own data here rather than relying on the unused
 * Follow_up_management::customer_origin() action.
 */
$CI = &get_instance();
$CI->load->model('follow_up_management/follow_ups_model');
$o = $CI->follow_ups_model->get_customer_origin_data((int) $client->userid);

if (!$o || !$o->client_id) {
    ?>
    <div class="col-md-12"><div class="alert alert-info"><?= _l('followup_no_origin_data'); ?></div></div>
    <?php
    return;
}
?>
<div class="col-md-12">
    <div class="panel_s">
        <div class="panel-body">
            <h5 class="no-margin tw-mb-3"><i class="fa-solid fa-diagram-project text-muted"></i> <?= _l('followup_customer_origin'); ?></h5>
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-bordered no-margin">
                        <tr>
                            <th class="bold tw-w-48"><?= _l('followup_original_lead'); ?></th>
                            <td><?= e($o->name); ?></td>
                        </tr>
                        <tr>
                            <th class="bold"><?= _l('followup_lead_company'); ?></th>
                            <td><?= $o->company ? e($o->company) : '-'; ?></td>
                        </tr>
                        <tr>
                            <th class="bold"><?= _l('followup_lead_status'); ?></th>
                            <td><?= e($o->status_name ?: '-'); ?></td>
                        </tr>
                        <tr>
                            <th class="bold"><?= _l('followup_lead_source'); ?></th>
                            <td><?= e($o->source_name ?: '-'); ?></td>
                        </tr>
                        <tr>
                            <th class="bold"><?= _l('followup_conversion_date'); ?></th>
                            <td><?= $o->date_converted ? e(_d($o->date_converted)) : '-'; ?></td>
                        </tr>
                        <tr>
                            <th class="bold"><?= _l('followup_converted_by'); ?></th>
                            <td><?= $o->fe_firstname ? e($o->fe_firstname . ' ' . $o->fe_lastname) : '-'; ?></td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-bordered no-margin">
                        <tr>
                            <th class="bold tw-w-48"><?= _l('followup_original_field_executive'); ?></th>
                            <td><?= $o->fe_firstname ? e($o->fe_firstname . ' ' . $o->fe_lastname) : '-'; ?></td>
                        </tr>
                        <tr>
                            <th class="bold"><?= _l('followup_telecaller'); ?></th>
                            <td><?= ($o->tc_firstname ?? '') ? e($o->tc_firstname . ' ' . $o->tc_lastname) : '-'; ?></td>
                        </tr>
                        <tr>
                            <th class="bold"><?= _l('followup_lead_phone'); ?></th>
                            <td><?= $o->phonenumber ? e($o->phonenumber) : '-'; ?></td>
                        </tr>
                        <tr>
                            <th class="bold"><?= _l('followup_lead_email'); ?></th>
                            <td><?= $o->email ? e($o->email) : '-'; ?></td>
                        </tr>
                        <tr>
                            <th class="bold"><?= _l('followup_lead_added_date'); ?></th>
                            <td><?= $o->dateadded ? e(_dt($o->dateadded)) : '-'; ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
