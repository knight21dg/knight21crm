<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">

        <!-- Other Follow-up reports -->
        <div class="panel_s">
            <div class="panel-body">
                <div class="tw-flex tw-flex-wrap tw-gap-2">
                    <a href="<?= admin_url('reports/followup_case_summary'); ?>" class="btn btn-default"><?= _l('followup_report_case_summary'); ?></a>
                    <a href="<?= admin_url('reports/followup_staff_performance'); ?>" class="btn btn-default"><?= _l('followup_report_staff_performance'); ?></a>
                    <a href="<?= admin_url('reports/followup_department_performance'); ?>" class="btn btn-default"><?= _l('followup_report_department_performance'); ?></a>
                    <a href="<?= admin_url('reports/followup_activity_report'); ?>" class="btn btn-default"><?= _l('followup_report_activity'); ?></a>
                    <a href="<?= admin_url('reports/followup_sla_report'); ?>" class="btn btn-default"><?= _l('followup_report_sla'); ?></a>
                    <a href="<?= admin_url('reports/followup_productivity_report'); ?>" class="btn btn-default"><?= _l('followup_report_productivity'); ?></a>
                    <a href="<?= admin_url('reports/followup_executive_dashboard'); ?>" class="btn btn-default"><?= _l('followup_report_executive_dashboard'); ?></a>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 animated fadeIn">
                <div class="panel_s">
                    <div class="panel-heading">
                        <h3 class="panel-title"><?= _l('report_followups_by_outcome'); ?></h3>
                    </div>
                    <div class="panel-body">
                        <canvas class="followups-by-outcome" height="150" id="followups-by-outcome"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6 animated fadeIn">
                <div class="panel_s">
                    <div class="panel-heading">
                        <h3 class="panel-title"><?= _l('report_followups_by_staff'); ?></h3>
                    </div>
                    <div class="panel-body">
                        <canvas class="followups-by-staff" height="150" id="followups-by-staff"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8 animated fadeIn">
                <div class="panel_s">
                    <div class="panel-heading">
                        <h3 class="panel-title"><?= _l('followup_outcome_trend'); ?></h3>
                    </div>
                    <div class="panel-body">
                        <canvas height="90" id="followups-outcome-trend"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="panel_s">
                    <div class="panel-body text-center">
                        <h3 class="no-margin"><?= e($call_success_rate['rate']); ?>%</h3>
                        <small class="text-muted"><?= _l('followup_call_success_rate'); ?></small>
                        <p class="text-muted tw-text-xs tw-mt-1"><?= (int) $call_success_rate['success']; ?> / <?= (int) $call_success_rate['total']; ?></p>
                    </div>
                </div>
                <div class="panel_s">
                    <div class="panel-body text-center">
                        <h3 class="no-margin"><?= e($meeting_conversion_rate['rate']); ?>%</h3>
                        <small class="text-muted"><?= _l('followup_meeting_conversion_rate'); ?></small>
                        <p class="text-muted tw-text-xs tw-mt-1"><?= (int) $meeting_conversion_rate['converted']; ?> / <?= (int) $meeting_conversion_rate['total']; ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script>
$(function() {
    new Chart($('#followups-by-outcome'), {
        type: 'pie',
        data: <?= $followups_by_outcome_report; ?>,
        options: {
            responsive: true
        }
    });

    new Chart($('#followups-by-staff'), {
        type: 'bar',
        data: <?= $followups_by_staff_report; ?>,
        options: {
            responsive: true,
            legend: {
                display: false,
            },
            scales: {
                yAxes: [{
                    ticks: {
                        beginAtZero: true,
                    }
                }]
            },
        },
    });

    new Chart($('#followups-outcome-trend'), {
        type: 'bar',
        data: <?= $followups_outcome_trend_report; ?>,
        options: {
            responsive: true,
            scales: {
                xAxes: [{ stacked: true }],
                yAxes: [{ stacked: true, ticks: { beginAtZero: true } }]
            },
        },
    });
});
</script>
</body>
</html>
