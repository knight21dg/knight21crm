<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <?php $this->load->view('reports_subnav'); ?>

        <div class="panel_s">
            <div class="panel-body">
                <h4 class="no-margin"><?= e($title); ?></h4>
                <hr class="hr-panel-separator" />

                <div class="row">
                    <div class="col-md-6 tw-mb-4">
                        <div class="panel_s tw-h-full">
                            <div class="panel-body">
                                <h5><?= _l('manager_portal_reports_task_status_breakdown'); ?></h5>
                                <canvas height="180" id="mp-chart-task-status"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 tw-mb-4">
                        <div class="panel_s tw-h-full">
                            <div class="panel-body">
                                <h5><?= _l('manager_portal_reports_priority_distribution'); ?></h5>
                                <canvas height="180" id="mp-chart-task-priority"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script>
    $(function() {
        var taskStatus = new Chart($('#mp-chart-task-status'), {
            type: 'doughnut',
            data: {
                labels: [<?= "'" . _l('manager_portal_reports_completed_tasks') . "', '" . _l('manager_portal_kpi_pending_tasks') . "', '" . _l('manager_portal_kpi_overdue_tasks') . "'"; ?>],
                datasets: [{
                    data: [<?= (int) $analytics['completed']; ?>, <?= (int) $analytics['pending']; ?>, <?= (int) $analytics['overdue']; ?>],
                    backgroundColor: ['#2ecc71', '#f1c40f', '#e65252'],
                }],
            },
            options: { maintainAspectRatio: true },
        });

        var taskPriority = new Chart($('#mp-chart-task-priority'), {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($analytics['priority_distribution'], 'name')); ?>,
                datasets: [{
                    label: '<?= _l('manager_portal_reports_priority_distribution'); ?>',
                    data: <?= json_encode(array_column($analytics['priority_distribution'], 'count')); ?>,
                    backgroundColor: <?= json_encode(array_column($analytics['priority_distribution'], 'color')); ?>,
                }],
            },
            options: { maintainAspectRatio: true, scales: { yAxes: [{ ticks: { beginAtZero: true } }] } },
        });
    });
</script>
</body>
</html>
