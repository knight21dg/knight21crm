<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <?php $this->load->view('admin/reports/followup_filters', ['filters' => $filters, 'departments' => $departments, 'members' => $members]); ?>

        <div class="tw-grid tw-grid-cols-2 sm:tw-grid-cols-4 tw-gap-3">
            <div class="panel_s tw-mb-0"><div class="panel-body text-center">
                <h3 class="no-margin"><?= (int) $summary['total_cases_assigned']; ?></h3>
                <small class="text-muted"><?= _l('card_total_cases_assigned'); ?></small>
            </div></div>
            <div class="panel_s tw-mb-0"><div class="panel-body text-center">
                <h3 class="no-margin"><?= (int) $summary['open_cases']; ?></h3>
                <small class="text-muted"><?= _l('card_open_cases'); ?></small>
            </div></div>
            <div class="panel_s tw-mb-0"><div class="panel-body text-center">
                <h3 class="no-margin tw-text-green-600"><?= (int) $summary['total_cases_closed']; ?></h3>
                <small class="text-muted"><?= _l('card_total_cases_closed'); ?></small>
            </div></div>
            <div class="panel_s tw-mb-0"><div class="panel-body text-center">
                <h3 class="no-margin tw-text-red-600"><?= (int) $summary['overdue_cases']; ?></h3>
                <small class="text-muted"><?= _l('card_overdue_cases'); ?></small>
            </div></div>
            <div class="panel_s tw-mb-0"><div class="panel-body text-center">
                <h3 class="no-margin"><?= (int) $summary['cases_reopened']; ?></h3>
                <small class="text-muted"><?= _l('card_cases_reopened'); ?></small>
            </div></div>
            <div class="panel_s tw-mb-0"><div class="panel-body text-center">
                <h3 class="no-margin"><?= (int) $summary['high_priority_cases']; ?></h3>
                <small class="text-muted"><?= _l('card_high_priority'); ?></small>
            </div></div>
            <div class="panel_s tw-mb-0"><div class="panel-body text-center">
                <h3 class="no-margin"><?= e(get_followup_format_duration($summary['avg_response_time'])); ?></h3>
                <small class="text-muted"><?= _l('card_avg_response_time'); ?></small>
            </div></div>
            <div class="panel_s tw-mb-0"><div class="panel-body text-center">
                <h3 class="no-margin"><?= e(get_followup_format_duration($summary['avg_closure_time'])); ?></h3>
                <small class="text-muted"><?= _l('card_avg_closure_time'); ?></small>
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
