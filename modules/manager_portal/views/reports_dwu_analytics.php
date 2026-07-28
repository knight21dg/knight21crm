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
                    <div class="col-md-7 tw-mb-4">
                        <div class="panel_s tw-h-full">
                            <div class="panel-body">
                                <h5><?= _l('manager_portal_reports_dwu_status_breakdown'); ?></h5>
                                <canvas height="180" id="mp-chart-dwu-status"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-5 tw-mb-4">
                        <div class="panel_s tw-h-full">
                            <div class="panel-body">
                                <h5><?= _l('manager_portal_reports_dwu_department_summary'); ?></h5>
                                <canvas height="180" id="mp-chart-dwu-department"></canvas>
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
        var dwuStatus = new Chart($('#mp-chart-dwu-status'), {
            type: 'bar',
            data: {
                labels: [<?= "'" . _l('manager_portal_reports_dwu_submitted') . "', '" . _l('manager_portal_dwu_status_pending') . "', '" . _l('manager_portal_dwu_status_approved') . "', '" . _l('manager_portal_dwu_status_needs_revision') . "'"; ?>],
                datasets: [{
                    label: '<?= _l('manager_portal_reports_dwu_analytics'); ?>',
                    data: [<?= (int) $analytics['total_submitted']; ?>, <?= (int) $analytics['pending']; ?>, <?= (int) $analytics['approved']; ?>, <?= (int) $analytics['needs_revision']; ?>],
                    backgroundColor: ['#4acccd', '#f1c40f', '#2ecc71', '#e65252'],
                }],
            },
            options: { maintainAspectRatio: true, scales: { yAxes: [{ ticks: { beginAtZero: true } }] } },
        });

        var dwuDepartment = new Chart($('#mp-chart-dwu-department'), {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_column($analytics['department_summary'], 'name')); ?>,
                datasets: [{
                    data: <?= json_encode(array_column($analytics['department_summary'], 'count')); ?>,
                    backgroundColor: ['#4acccd', '#f1c40f', '#2ecc71', '#e65252', '#9b59b6', '#3498db', '#e67e22', '#1abc9c', '#95a5a6'],
                }],
            },
            options: { maintainAspectRatio: true },
        });
    });
</script>
</body>
</html>
