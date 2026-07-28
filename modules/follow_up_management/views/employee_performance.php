<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin tw-mb-4"><?= _l('followup_employee_performance'); ?></h4>

                        <ul class="nav nav-tabs" role="tablist">
                            <li role="presentation" class="active">
                                <a href="#tab-fe" aria-controls="tab-fe" role="tab" data-toggle="tab"><?= _l('followup_field_executives'); ?></a>
                            </li>
                            <li role="presentation">
                                <a href="#tab-tc" aria-controls="tab-tc" role="tab" data-toggle="tab"><?= _l('followup_telecallers'); ?></a>
                            </li>
                        </ul>

                        <div class="tab-content tw-mt-3">
                            <div role="tabpanel" class="tab-pane active" id="tab-fe">
                                <?php if (empty($fe_performance)) { ?>
                                <div class="alert alert-info"><?= _l('followup_no_data'); ?></div>
                                <?php } else { ?>
                                <div class="table-responsive">
                                    <table class="table table-hover table-bordered">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th><?= _l('followup_staff_name'); ?></th>
                                                <th><?= _l('followup_leads_created'); ?></th>
                                                <th><?= _l('followup_meetings'); ?></th>
                                                <th><?= _l('followup_assigned_to_tc'); ?></th>
                                                <th><?= _l('followup_converted'); ?></th>
                                                <th><?= _l('followup_lost'); ?></th>
                                                <th><?= _l('followup_conversion_pct'); ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $idx = 1; foreach ($fe_performance as $row) { ?>
                                            <tr>
                                                <td><?= $idx++; ?></td>
                                                <td><?= e($row['name']); ?></td>
                                                <td><?= (int) $row['leads_created']; ?></td>
                                                <td><?= (int) $row['meetings']; ?></td>
                                                <td><?= (int) $row['assigned_tc']; ?></td>
                                                <td><?= (int) $row['converted']; ?></td>
                                                <td><?= (int) $row['lost']; ?></td>
                                                <td><?= e($row['conversion_pct']); ?>%</td>
                                            </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php } ?>
                            </div>

                            <div role="tabpanel" class="tab-pane" id="tab-tc">
                                <?php if (empty($tc_performance)) { ?>
                                <div class="alert alert-info"><?= _l('followup_no_data'); ?></div>
                                <?php } else { ?>
                                <div class="table-responsive">
                                    <table class="table table-hover table-bordered">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th><?= _l('followup_staff_name'); ?></th>
                                                <th><?= _l('followup_assigned_leads'); ?></th>
                                                <th><?= _l('followup_calls_made'); ?></th>
                                                <th><?= _l('followup_followups_done'); ?></th>
                                                <th><?= _l('followup_converted'); ?></th>
                                                <th><?= _l('followup_lost'); ?></th>
                                                <th><?= _l('followup_avg_followup_time_hours'); ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $idx = 1; foreach ($tc_performance as $row) { ?>
                                            <tr>
                                                <td><?= $idx++; ?></td>
                                                <td><?= e($row['name']); ?></td>
                                                <td><?= (int) $row['assigned_leads']; ?></td>
                                                <td><?= (int) $row['calls_made']; ?></td>
                                                <td><?= (int) $row['followups']; ?></td>
                                                <td><?= (int) $row['converted']; ?></td>
                                                <td><?= (int) $row['lost']; ?></td>
                                                <td><?= e($row['avg_followup_time']); ?> hrs</td>
                                            </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
</body>
</html>
