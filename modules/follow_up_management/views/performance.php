<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">

                <!-- Filters -->
                <div class="panel_s">
                    <div class="panel-body">
                        <form method="get" class="row">
                            <div class="col-md-2">
                                <?= render_date_input('date_from', 'followup_filter_date_from', $filters['date_from']); ?>
                            </div>
                            <div class="col-md-2">
                                <?= render_date_input('date_to', 'followup_filter_date_to', $filters['date_to']); ?>
                            </div>
                            <div class="col-md-2">
                                <select name="department_id" class="selectpicker" data-width="100%"
                                    data-none-selected-text="<?= _l('followup_filter_all_departments'); ?>">
                                    <option value=""><?= _l('followup_filter_all_departments'); ?></option>
                                    <?php foreach ($departments as $department) { ?>
                                    <option value="<?= e($department['id']); ?>" <?= $filters['department_id'] == $department['id'] ? 'selected' : ''; ?>><?= e($department['name']); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select name="priority" class="selectpicker" data-width="100%"
                                    data-none-selected-text="<?= _l('followup_filter_all_priorities'); ?>">
                                    <option value=""><?= _l('followup_filter_all_priorities'); ?></option>
                                    <?php foreach (get_followup_priorities() as $key => $label) { ?>
                                    <option value="<?= e($key); ?>" <?= $filters['priority'] === $key ? 'selected' : ''; ?>><?= e($label); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select name="case_status" class="selectpicker" data-width="100%"
                                    data-none-selected-text="<?= _l('followup_filter_all_statuses'); ?>">
                                    <option value=""><?= _l('followup_filter_all_statuses'); ?></option>
                                    <?php foreach (get_followup_statuses() as $key => $label) { ?>
                                    <option value="<?= e($key); ?>" <?= $filters['case_status'] === $key ? 'selected' : ''; ?>><?= e($label); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary tw-w-full"><?= _l('apply'); ?></button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- KPI Cards -->
                <div class="tw-grid tw-grid-cols-2 sm:tw-grid-cols-4 lg:tw-grid-cols-6 tw-gap-3 tw-mb-4">
                    <div class="panel_s tw-mb-0"><div class="panel-body text-center">
                        <h3 class="no-margin"><?= (int) $summary['total_cases_assigned']; ?></h3>
                        <small class="text-muted"><?= _l('card_total_cases_assigned'); ?></small>
                    </div></div>
                    <div class="panel_s tw-mb-0"><div class="panel-body text-center">
                        <h3 class="no-margin"><?= (int) $summary['total_cases_closed']; ?></h3>
                        <small class="text-muted"><?= _l('card_total_cases_closed'); ?></small>
                    </div></div>
                    <div class="panel_s tw-mb-0"><div class="panel-body text-center">
                        <h3 class="no-margin"><?= (int) $summary['open_cases']; ?></h3>
                        <small class="text-muted"><?= _l('card_open_cases'); ?></small>
                    </div></div>
                    <div class="panel_s tw-mb-0"><div class="panel-body text-center">
                        <h3 class="no-margin tw-text-red-600"><?= (int) $summary['overdue_cases']; ?></h3>
                        <small class="text-muted"><?= _l('card_overdue_cases'); ?></small>
                    </div></div>
                    <div class="panel_s tw-mb-0"><div class="panel-body text-center">
                        <h3 class="no-margin"><?= e(get_followup_format_duration($summary['avg_response_time'])); ?></h3>
                        <small class="text-muted"><?= _l('card_avg_response_time'); ?></small>
                    </div></div>
                    <div class="panel_s tw-mb-0"><div class="panel-body text-center">
                        <h3 class="no-margin"><?= e(get_followup_format_duration($summary['avg_closure_time'])); ?></h3>
                        <small class="text-muted"><?= _l('card_avg_closure_time'); ?></small>
                    </div></div>
                    <div class="panel_s tw-mb-0"><div class="panel-body text-center">
                        <h3 class="no-margin"><?= (int) $summary['calls_logged']; ?></h3>
                        <small class="text-muted"><?= _l('card_calls_logged'); ?></small>
                    </div></div>
                    <div class="panel_s tw-mb-0"><div class="panel-body text-center">
                        <h3 class="no-margin"><?= (int) $summary['meetings_scheduled']; ?></h3>
                        <small class="text-muted"><?= _l('card_meetings_scheduled'); ?></small>
                    </div></div>
                    <div class="panel_s tw-mb-0"><div class="panel-body text-center">
                        <h3 class="no-margin"><?= (int) $summary['reminders_created']; ?></h3>
                        <small class="text-muted"><?= _l('card_reminders_created'); ?></small>
                    </div></div>
                    <div class="panel_s tw-mb-0"><div class="panel-body text-center">
                        <h3 class="no-margin tw-text-green-600"><?= (int) $summary['followups_completed']; ?></h3>
                        <small class="text-muted"><?= _l('card_followups_completed'); ?></small>
                    </div></div>
                    <div class="panel_s tw-mb-0"><div class="panel-body text-center">
                        <h3 class="no-margin"><?= (int) $summary['cases_reopened']; ?></h3>
                        <small class="text-muted"><?= _l('card_cases_reopened'); ?></small>
                    </div></div>
                    <div class="panel_s tw-mb-0"><div class="panel-body text-center">
                        <h3 class="no-margin"><?= (int) $summary['high_priority_cases']; ?></h3>
                        <small class="text-muted"><?= _l('card_high_priority'); ?></small>
                    </div></div>
                </div>

                <!-- Charts -->
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
                <div class="row">
                    <div class="col-md-4">
                        <div class="panel_s">
                            <div class="panel-heading"><h3 class="panel-title"><?= _l('chart_calls_vs_meetings'); ?></h3></div>
                            <div class="panel-body"><canvas height="180" id="chart-calls-meetings"></canvas></div>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="panel_s">
                            <div class="panel-heading"><h3 class="panel-title"><?= _l('chart_case_status_trend'); ?></h3></div>
                            <div class="panel-body"><canvas height="90" id="chart-status-trend"></canvas></div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="panel_s">
                            <div class="panel-heading"><h3 class="panel-title"><?= _l('chart_staff_comparison'); ?></h3></div>
                            <div class="panel-body"><canvas height="150" id="chart-staff-comparison"></canvas></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="panel_s">
                            <div class="panel-heading"><h3 class="panel-title"><?= _l('chart_department_performance'); ?></h3></div>
                            <div class="panel-body"><canvas height="150" id="chart-department"></canvas></div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="panel_s">
                            <div class="panel-heading"><h3 class="panel-title"><?= _l('chart_avg_response_time_trend'); ?></h3></div>
                            <div class="panel-body"><canvas height="90" id="chart-response-trend"></canvas></div>
                        </div>
                    </div>
                </div>

                <!-- Leaderboard -->
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin"><?= _l('top_performers'); ?></h4>
                        <hr class="hr-panel-separator" />
                        <?php if (empty($leaderboard)) { ?>
                        <p class="text-muted"><?= _l('notification_no_notifications'); ?></p>
                        <?php } else { ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th><?= _l('leaderboard_rank'); ?></th>
                                        <th><?= _l('case_field_assigned_staff'); ?></th>
                                        <th><?= _l('case_column_department'); ?></th>
                                        <th><?= _l('card_total_cases_closed'); ?></th>
                                        <th><?= _l('card_calls_logged'); ?></th>
                                        <th><?= _l('card_meetings_scheduled'); ?></th>
                                        <th><?= _l('card_avg_response_time'); ?></th>
                                        <th><?= _l('card_avg_closure_time'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($leaderboard as $index => $row) { ?>
                                    <tr>
                                        <td>#<?= $index + 1; ?></td>
                                        <td><a href="<?= admin_url('follow_up_management/performance_staff_detail/' . $row['staff_id']); ?>"><?= e(get_staff_full_name($row['staff_id'])); ?></a></td>
                                        <td><?= $row['department_name'] ? e($row['department_name']) : '-'; ?></td>
                                        <td><?= (int) $row['total_cases_closed']; ?></td>
                                        <td><?= (int) $row['calls_logged']; ?></td>
                                        <td><?= (int) $row['meetings_scheduled']; ?></td>
                                        <td><?= e(get_followup_format_duration($row['avg_response_time'])); ?></td>
                                        <td><?= e(get_followup_format_duration($row['avg_closure_time'])); ?></td>
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
<?php init_tail(); ?>
<script>
    $(function() {
        new Chart($('#chart-daily'), { type: 'bar', data: <?= $chart_daily; ?>, options: { responsive: true, legend: { display: false }, scales: { yAxes: [{ ticks: { beginAtZero: true } }] } } });
        new Chart($('#chart-weekly'), { type: 'bar', data: <?= $chart_weekly; ?>, options: { responsive: true, legend: { display: false }, scales: { yAxes: [{ ticks: { beginAtZero: true } }] } } });
        new Chart($('#chart-monthly'), { type: 'line', data: <?= $chart_monthly; ?>, options: { responsive: true, legend: { display: false }, scales: { yAxes: [{ ticks: { beginAtZero: true } }] } } });
        new Chart($('#chart-calls-meetings'), { type: 'doughnut', data: <?= $chart_calls_meetings; ?>, options: { responsive: true } });
        new Chart($('#chart-status-trend'), { type: 'bar', data: <?= $chart_status_trend; ?>, options: { responsive: true, scales: { xAxes: [{ stacked: true }], yAxes: [{ stacked: true, ticks: { beginAtZero: true } }] } } });
        new Chart($('#chart-staff-comparison'), { type: 'bar', data: <?= $chart_staff_comparison; ?>, options: { responsive: true, legend: { display: false }, scales: { yAxes: [{ ticks: { beginAtZero: true } }] } } });
        new Chart($('#chart-department'), { type: 'bar', data: <?= $chart_department; ?>, options: { responsive: true, legend: { display: false }, scales: { yAxes: [{ ticks: { beginAtZero: true } }] } } });
        new Chart($('#chart-response-trend'), { type: 'line', data: <?= $chart_response_trend; ?>, options: { responsive: true, legend: { display: false }, scales: { yAxes: [{ ticks: { beginAtZero: true } }] } } });

        $('.selectpicker').on('changed.bs.select', function() {
            $(this).closest('form').submit();
        });
    });
</script>
</body>
</html>
