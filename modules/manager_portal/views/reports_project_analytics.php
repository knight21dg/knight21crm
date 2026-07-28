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
                                <h5><?= _l('manager_portal_reports_project_status'); ?></h5>
                                <canvas height="180" id="mp-chart-project-status"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 tw-mb-4">
                        <div class="panel_s tw-h-full">
                            <div class="panel-body">
                                <h5><?= _l('manager_portal_reports_department_distribution'); ?></h5>
                                <canvas height="180" id="mp-chart-project-department"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12 tw-mb-4">
                        <div class="panel_s tw-h-full">
                            <div class="panel-body">
                                <h5><?= _l('manager_portal_reports_completion_trend'); ?></h5>
                                <canvas height="120" id="mp-chart-project-trend"></canvas>
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
        var projectStatus = new Chart($('#mp-chart-project-status'), {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_column($analytics['status_distribution'], 'name')); ?>,
                datasets: [{
                    data: <?= json_encode(array_column($analytics['status_distribution'], 'count')); ?>,
                    backgroundColor: <?= json_encode(array_column($analytics['status_distribution'], 'color')); ?>,
                }],
            },
            options: { maintainAspectRatio: true },
        });

        var projectDepartment = new Chart($('#mp-chart-project-department'), {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($analytics['department_distribution'], 'name')); ?>,
                datasets: [{
                    label: '<?= _l('manager_portal_reports_department_distribution'); ?>',
                    data: <?= json_encode(array_column($analytics['department_distribution'], 'count')); ?>,
                    backgroundColor: '#4acccd',
                }],
            },
            options: { maintainAspectRatio: true, scales: { yAxes: [{ ticks: { beginAtZero: true } }] } },
        });

        var projectTrend = new Chart($('#mp-chart-project-trend'), {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column($analytics['completion_trend'], 'month')); ?>,
                datasets: [{
                    label: '<?= _l('manager_portal_reports_completion_trend'); ?>',
                    data: <?= json_encode(array_column($analytics['completion_trend'], 'count')); ?>,
                    borderColor: '#2ecc71',
                    backgroundColor: 'rgba(46,204,113,0.15)',
                    fill: true,
                }],
            },
            options: { maintainAspectRatio: true, scales: { yAxes: [{ ticks: { beginAtZero: true } }] } },
        });
    });
</script>
</body>
</html>
