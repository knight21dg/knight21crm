<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">

        <div class="tw-grid tw-grid-cols-2 sm:tw-grid-cols-5 tw-gap-3 tw-mb-4">
            <div class="panel_s tw-mb-0"><div class="panel-body text-center">
                <h4 class="no-margin"><?= (int) $performance['calls_logged_today']; ?></h4>
                <small class="text-muted"><?= _l('perf_calls_logged_today'); ?></small>
            </div></div>
            <div class="panel_s tw-mb-0"><div class="panel-body text-center">
                <h4 class="no-margin"><?= (int) $performance['meetings_scheduled_today']; ?></h4>
                <small class="text-muted"><?= _l('perf_meetings_scheduled_today'); ?></small>
            </div></div>
            <div class="panel_s tw-mb-0"><div class="panel-body text-center">
                <h4 class="no-margin"><?= (int) $performance['reminders_created_today']; ?></h4>
                <small class="text-muted"><?= _l('perf_reminders_created_today'); ?></small>
            </div></div>
            <div class="panel_s tw-mb-0"><div class="panel-body text-center">
                <h4 class="no-margin"><?= (int) $performance['cases_closed_today']; ?></h4>
                <small class="text-muted"><?= _l('perf_cases_closed_today'); ?></small>
            </div></div>
            <div class="panel_s tw-mb-0"><div class="panel-body text-center">
                <h4 class="no-margin"><?= (int) $performance['cases_reopened_today']; ?></h4>
                <small class="text-muted"><?= _l('perf_cases_reopened_today'); ?></small>
            </div></div>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="panel_s">
                    <div class="panel-heading"><h3 class="panel-title"><?= _l('chart_daily_productivity'); ?></h3></div>
                    <div class="panel-body"><canvas height="180" id="chart-daily"></canvas></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="panel_s">
                    <div class="panel-heading"><h3 class="panel-title"><?= _l('chart_weekly_productivity'); ?></h3></div>
                    <div class="panel-body"><canvas height="180" id="chart-weekly"></canvas></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="panel_s">
                    <div class="panel-heading"><h3 class="panel-title"><?= _l('chart_monthly_productivity'); ?></h3></div>
                    <div class="panel-body"><canvas height="180" id="chart-monthly"></canvas></div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script>
    $(function() {
        new Chart($('#chart-daily'), { type: 'bar', data: <?= $chart_daily; ?>, options: { responsive: true, legend: { display: false }, scales: { yAxes: [{ ticks: { beginAtZero: true } }] } } });
        new Chart($('#chart-weekly'), { type: 'bar', data: <?= $chart_weekly; ?>, options: { responsive: true, legend: { display: false }, scales: { yAxes: [{ ticks: { beginAtZero: true } }] } } });
        new Chart($('#chart-monthly'), { type: 'line', data: <?= $chart_monthly; ?>, options: { responsive: true, legend: { display: false }, scales: { yAxes: [{ ticks: { beginAtZero: true } }] } } });
    });
</script>
</body>
</html>
