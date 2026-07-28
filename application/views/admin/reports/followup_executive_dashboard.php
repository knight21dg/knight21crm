<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="tw-flex tw-items-center tw-justify-between tw-mb-4">
            <h4 class="no-margin"><?= e($title); ?></h4>
            <button type="button" class="btn btn-default table-export-exclude" onclick="window.print();"><i class="fa-solid fa-print tw-mr-1"></i> <?= _l('print'); ?></button>
        </div>

        <!-- Overall KPIs -->
        <div class="tw-grid tw-grid-cols-2 sm:tw-grid-cols-4 tw-gap-3 tw-mb-4">
            <div class="panel_s tw-mb-0"><div class="panel-body text-center">
                <h3 class="no-margin"><?= (int) $summary['total_cases_assigned']; ?></h3>
                <small class="text-muted"><?= _l('card_total_cases_assigned'); ?></small>
            </div></div>
            <div class="panel_s tw-mb-0"><div class="panel-body text-center">
                <h3 class="no-margin tw-text-green-600"><?= (int) $summary['total_cases_closed']; ?></h3>
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
        </div>

        <div class="row">
            <!-- Top Staff -->
            <div class="col-md-6">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin"><?= _l('top_performers'); ?></h4>
                        <hr class="hr-panel-separator" />
                        <?php if (empty($top_staff)) { ?>
                        <p class="text-muted"><?= _l('notification_no_notifications'); ?></p>
                        <?php } else { ?>
                        <table class="table">
                            <thead><tr><th><?= _l('leaderboard_rank'); ?></th><th><?= _l('case_field_assigned_staff'); ?></th><th><?= _l('card_total_cases_closed'); ?></th></tr></thead>
                            <tbody>
                                <?php foreach ($top_staff as $i => $row) { ?>
                                <tr><td>#<?= $i + 1; ?></td><td><?= e(get_staff_full_name($row['staff_id'])); ?></td><td><?= (int) $row['total_cases_closed']; ?></td></tr>
                                <?php } ?>
                            </tbody>
                        </table>
                        <?php } ?>
                    </div>
                </div>
            </div>
            <!-- Top Departments -->
            <div class="col-md-6">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin"><?= _l('followup_report_department_performance'); ?></h4>
                        <hr class="hr-panel-separator" />
                        <?php if (empty($top_departments)) { ?>
                        <p class="text-muted"><?= _l('notification_no_notifications'); ?></p>
                        <?php } else { ?>
                        <table class="table">
                            <thead><tr><th><?= _l('case_column_department'); ?></th><th><?= _l('card_total_cases_closed'); ?></th><th><?= _l('card_open_cases'); ?></th></tr></thead>
                            <tbody>
                                <?php foreach ($top_departments as $row) { ?>
                                <tr><td><?= e($row['department_name']); ?></td><td><?= (int) $row['cases_closed']; ?></td><td><?= (int) $row['open_cases']; ?></td></tr>
                                <?php } ?>
                            </tbody>
                        </table>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Trend charts -->
        <div class="row">
            <div class="col-md-6">
                <div class="panel_s">
                    <div class="panel-heading"><h3 class="panel-title"><?= _l('chart_cases_created_vs_closed'); ?></h3></div>
                    <div class="panel-body"><canvas height="150" id="chart-created-closed"></canvas></div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="panel_s">
                    <div class="panel-heading"><h3 class="panel-title"><?= _l('chart_monthly_closure_trend'); ?></h3></div>
                    <div class="panel-body"><canvas height="150" id="chart-monthly-trend"></canvas></div>
                </div>
            </div>
        </div>

        <!-- Open vs Closed / Priority Distribution -->
        <div class="row">
            <div class="col-md-6">
                <div class="panel_s">
                    <div class="panel-heading"><h3 class="panel-title"><?= _l('chart_cases_by_status'); ?></h3></div>
                    <div class="panel-body"><canvas height="150" id="chart-by-status"></canvas></div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="panel_s">
                    <div class="panel-heading"><h3 class="panel-title"><?= _l('chart_cases_by_priority'); ?></h3></div>
                    <div class="panel-body"><canvas height="150" id="chart-by-priority"></canvas></div>
                </div>
            </div>
        </div>

        <!-- Upcoming Follow-ups -->
        <div class="panel_s">
            <div class="panel-body">
                <h4 class="no-margin"><?= _l('upcoming_followups'); ?></h4>
                <hr class="hr-panel-separator" />
                <?php if (empty($upcoming)) { ?>
                <p class="text-muted"><?= _l('case_no_upcoming_followups'); ?></p>
                <?php } else { ?>
                <table class="table">
                    <thead><tr><th><?= _l('case_column_lead_name'); ?></th><th><?= _l('case_field_priority'); ?></th><th><?= _l('case_column_next_follow_up'); ?></th></tr></thead>
                    <tbody>
                        <?php foreach ($upcoming as $case) {
                            $rel_data   = get_relation_data($case->rel_type, $case->rel_id);
                            $rel_values = get_relation_values($rel_data, $case->rel_type);
                            ?>
                        <tr>
                            <td><a href="<?= e($rel_values['link']); ?>"><?= e($rel_values['name']); ?></a></td>
                            <td><span class="label label-<?= get_followup_priority_class($case->priority); ?>"><?= e(get_followup_priority_label($case->priority)); ?></span></td>
                            <td><?= e(_dt($case->next_follow_up_date)); ?></td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
                <?php } ?>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script>
    $(function() {
        new Chart($('#chart-created-closed'), { type: 'line', data: <?= $chart_created_closed; ?>, options: { responsive: true, scales: { yAxes: [{ ticks: { beginAtZero: true } }] } } });
        new Chart($('#chart-monthly-trend'), { type: 'line', data: <?= $chart_monthly_trend; ?>, options: { responsive: true, legend: { display: false }, scales: { yAxes: [{ ticks: { beginAtZero: true } }] } } });
        new Chart($('#chart-by-status'), { type: 'pie', data: <?= $chart_by_status; ?>, options: { responsive: true } });
        new Chart($('#chart-by-priority'), { type: 'doughnut', data: <?= $chart_by_priority; ?>, options: { responsive: true } });
    });
</script>
</body>
</html>
