<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <?php $this->load->view('admin/reports/followup_filters', ['filters' => $filters, 'departments' => $departments]); ?>

        <div class="panel_s">
            <div class="panel-body">
                <p class="text-muted"><?= _l('followup_sla_thresholds_note', [$sla['response_sla_hours'], $sla['closure_sla_hours']]); ?></p>
            </div>
        </div>

        <div class="tw-grid tw-grid-cols-2 sm:tw-grid-cols-3 tw-gap-3">
            <div class="panel_s tw-mb-0"><div class="panel-body text-center">
                <h3 class="no-margin"><?= e(get_followup_format_duration($sla['avg_response_time'])); ?></h3>
                <small class="text-muted"><?= _l('card_avg_response_time'); ?></small>
            </div></div>
            <div class="panel_s tw-mb-0"><div class="panel-body text-center">
                <h3 class="no-margin"><?= e(get_followup_format_duration($sla['avg_closure_time'])); ?></h3>
                <small class="text-muted"><?= _l('card_avg_closure_time'); ?></small>
            </div></div>
            <div class="panel_s tw-mb-0"><div class="panel-body text-center">
                <h3 class="no-margin tw-text-red-600"><?= e($sla['overdue_percent']); ?>%</h3>
                <small class="text-muted"><?= _l('followup_overdue_percent'); ?></small>
            </div></div>
            <div class="panel_s tw-mb-0"><div class="panel-body text-center">
                <h3 class="no-margin tw-text-green-600"><?= (int) $sla['within_sla']; ?></h3>
                <small class="text-muted"><?= _l('followup_within_sla'); ?></small>
            </div></div>
            <div class="panel_s tw-mb-0"><div class="panel-body text-center">
                <h3 class="no-margin tw-text-red-600"><?= (int) $sla['outside_sla']; ?></h3>
                <small class="text-muted"><?= _l('followup_outside_sla'); ?></small>
            </div></div>
            <div class="panel_s tw-mb-0"><div class="panel-body text-center">
                <h3 class="no-margin"><?= (int) $sla['total_closed']; ?></h3>
                <small class="text-muted"><?= _l('card_total_cases_closed'); ?></small>
            </div></div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script>
    $(function() {
        $('.selectpicker').on('changed.bs.select', function() { $(this).closest('form').submit(); });
    });
</script>
</body>
</html>
