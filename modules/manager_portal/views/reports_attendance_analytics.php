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
                                <h5><?= _l('manager_portal_reports_present_vs_absent'); ?></h5>
                                <canvas height="180" id="mp-chart-present-absent"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 tw-mb-4">
                        <div class="panel_s tw-h-full">
                            <div class="panel-body">
                                <h5><?= _l('manager_portal_reports_late_arrivals'); ?></h5>
                                <canvas height="180" id="mp-chart-late-arrivals"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-8 tw-mb-4">
                        <div class="panel_s tw-h-full">
                            <div class="panel-body">
                                <h5><?= _l('manager_portal_reports_attendance_trend'); ?></h5>
                                <canvas height="180" id="mp-chart-attendance-trend"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 tw-mb-4">
                        <div class="panel_s tw-h-full">
                            <div class="panel-body">
                                <h5><?= _l('manager_portal_reports_monthly_attendance_percent'); ?></h5>
                                <canvas height="180" id="mp-chart-monthly-percent"></canvas>
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
        var presentAbsent = new Chart($('#mp-chart-present-absent'), {
            type: 'doughnut',
            data: {
                labels: [<?= "'" . _l('manager_portal_reports_present') . "', '" . _l('manager_portal_reports_absent') . "', '" . _l('manager_portal_reports_late') . "', '" . _l('manager_portal_reports_half_day') . "', '" . _l('manager_portal_reports_leave') . "'"; ?>],
                datasets: [{
                    data: [<?= (int) $analytics['today_summary']['Present']; ?>, <?= (int) $analytics['today_summary']['Absent']; ?>, <?= (int) $analytics['today_summary']['Late']; ?>, <?= (int) $analytics['today_summary']['Half Day']; ?>, <?= (int) $analytics['today_summary']['Leave']; ?>],
                    backgroundColor: ['#4acccd', '#e65252', '#f1c40f', '#9b59b6', '#95a5a6'],
                }],
            },
            options: { maintainAspectRatio: true },
        });

        var lateArrivals = new Chart($('#mp-chart-late-arrivals'), {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($analytics['late_by_day'], 'date')); ?>,
                datasets: [{
                    label: '<?= _l('manager_portal_reports_late_arrivals'); ?>',
                    data: <?= json_encode(array_column($analytics['late_by_day'], 'count')); ?>,
                    backgroundColor: '#f1c40f',
                }],
            },
            options: { maintainAspectRatio: true, scales: { yAxes: [{ ticks: { beginAtZero: true } }] } },
        });

        var attendanceTrend = new Chart($('#mp-chart-attendance-trend'), {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column($analytics['trend'], 'date')); ?>,
                datasets: [{
                    label: '<?= _l('manager_portal_reports_present'); ?>',
                    data: <?= json_encode(array_column($analytics['trend'], 'count')); ?>,
                    borderColor: '#4acccd',
                    backgroundColor: 'rgba(74,204,205,0.15)',
                    fill: true,
                }],
            },
            options: { maintainAspectRatio: true, scales: { yAxes: [{ ticks: { beginAtZero: true } }] } },
        });

        var monthlyPercent = new Chart($('#mp-chart-monthly-percent'), {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($analytics['department_rates'], 'name')); ?>,
                datasets: [{
                    label: '<?= _l('manager_portal_reports_monthly_attendance_percent'); ?>',
                    data: <?= json_encode(array_column($analytics['department_rates'], 'rate')); ?>,
                    backgroundColor: '#2ecc71',
                }],
            },
            options: { maintainAspectRatio: true, scales: { yAxes: [{ ticks: { beginAtZero: true, max: 100 } }] } },
        });
    });
</script>
</body>
</html>
