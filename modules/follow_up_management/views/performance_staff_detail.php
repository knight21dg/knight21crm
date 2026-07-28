<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<?php $performance = $detail['performance']; ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="tw-mb-4">
                    <a href="<?= admin_url('follow_up_management/performance'); ?>" class="btn btn-default"><i class="fa-solid fa-arrow-left tw-mr-1"></i> <?= _l('performance_dashboard'); ?></a>
                </div>

                <h4 class="tw-mb-4"><?= e($title); ?></h4>

                <!-- Performance Summary -->
                <div class="tw-grid tw-grid-cols-2 sm:tw-grid-cols-4 lg:tw-grid-cols-6 tw-gap-3 tw-mb-4">
                    <div class="panel_s tw-mb-0"><div class="panel-body text-center">
                        <h3 class="no-margin"><?= (int) $performance['total_cases_assigned']; ?></h3>
                        <small class="text-muted"><?= _l('card_total_cases_assigned'); ?></small>
                    </div></div>
                    <div class="panel_s tw-mb-0"><div class="panel-body text-center">
                        <h3 class="no-margin"><?= (int) $performance['total_cases_closed']; ?></h3>
                        <small class="text-muted"><?= _l('card_total_cases_closed'); ?></small>
                    </div></div>
                    <div class="panel_s tw-mb-0"><div class="panel-body text-center">
                        <h3 class="no-margin"><?= (int) $performance['open_cases']; ?></h3>
                        <small class="text-muted"><?= _l('card_open_cases'); ?></small>
                    </div></div>
                    <div class="panel_s tw-mb-0"><div class="panel-body text-center">
                        <h3 class="no-margin tw-text-red-600"><?= (int) $performance['overdue_cases']; ?></h3>
                        <small class="text-muted"><?= _l('card_overdue_cases'); ?></small>
                    </div></div>
                    <div class="panel_s tw-mb-0"><div class="panel-body text-center">
                        <h3 class="no-margin"><?= e(get_followup_format_duration($performance['avg_response_time'])); ?></h3>
                        <small class="text-muted"><?= _l('card_avg_response_time'); ?></small>
                    </div></div>
                    <div class="panel_s tw-mb-0"><div class="panel-body text-center">
                        <h3 class="no-margin"><?= e(get_followup_format_duration($performance['avg_closure_time'])); ?></h3>
                        <small class="text-muted"><?= _l('card_avg_closure_time'); ?></small>
                    </div></div>
                    <div class="panel_s tw-mb-0"><div class="panel-body text-center">
                        <h3 class="no-margin"><?= (int) $performance['calls_logged']; ?></h3>
                        <small class="text-muted"><?= _l('card_calls_logged'); ?></small>
                    </div></div>
                    <div class="panel_s tw-mb-0"><div class="panel-body text-center">
                        <h3 class="no-margin"><?= (int) $performance['meetings_scheduled']; ?></h3>
                        <small class="text-muted"><?= _l('card_meetings_scheduled'); ?></small>
                    </div></div>
                    <div class="panel_s tw-mb-0"><div class="panel-body text-center">
                        <h3 class="no-margin"><?= (int) $performance['reminders_created']; ?></h3>
                        <small class="text-muted"><?= _l('card_reminders_created'); ?></small>
                    </div></div>
                    <div class="panel_s tw-mb-0"><div class="panel-body text-center">
                        <h3 class="no-margin tw-text-green-600"><?= (int) $performance['followups_completed']; ?></h3>
                        <small class="text-muted"><?= _l('card_followups_completed'); ?></small>
                    </div></div>
                    <div class="panel_s tw-mb-0"><div class="panel-body text-center">
                        <h3 class="no-margin"><?= (int) $performance['cases_reopened']; ?></h3>
                        <small class="text-muted"><?= _l('card_cases_reopened'); ?></small>
                    </div></div>
                    <div class="panel_s tw-mb-0"><div class="panel-body text-center">
                        <h3 class="no-margin"><?= (int) $performance['high_priority_cases']; ?></h3>
                        <small class="text-muted"><?= _l('card_high_priority'); ?></small>
                    </div></div>
                </div>

                <!-- Daily / Weekly / Monthly Activity -->
                <div class="row">
                    <div class="col-md-4">
                        <div class="panel_s">
                            <div class="panel-heading"><h3 class="panel-title"><?= _l('daily_activity'); ?></h3></div>
                            <div class="panel-body"><canvas height="180" id="chart-daily"></canvas></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="panel_s">
                            <div class="panel-heading"><h3 class="panel-title"><?= _l('weekly_activity'); ?></h3></div>
                            <div class="panel-body"><canvas height="180" id="chart-weekly"></canvas></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="panel_s">
                            <div class="panel-heading"><h3 class="panel-title"><?= _l('monthly_activity'); ?></h3></div>
                            <div class="panel-body"><canvas height="180" id="chart-monthly"></canvas></div>
                        </div>
                    </div>
                </div>

                <!-- Case buckets -->
                <div class="row">
                    <?php
                    $buckets = [
                        'open_cases'    => ['label' => _l('card_open_cases'), 'class' => 'info'],
                        'closed_cases'  => ['label' => _l('card_total_cases_closed'), 'class' => 'success'],
                        'overdue_cases' => ['label' => _l('card_overdue_cases'), 'class' => 'danger'],
                    ];
                    foreach ($buckets as $key => $bucket) {
                        $cases = $detail[$key];
                        ?>
                    <div class="col-md-4">
                        <div class="panel_s">
                            <div class="panel-body">
                                <h4 class="no-margin">
                                    <span class="label label-<?= $bucket['class']; ?>"><?= e($bucket['label']); ?></span>
                                    (<?= count($cases); ?>)
                                </h4>
                                <hr class="hr-panel-separator" />
                                <?php if (empty($cases)) { ?>
                                <p class="text-muted"><?= _l('case_no_cases_in_section'); ?></p>
                                <?php } else { ?>
                                <ul class="list-unstyled">
                                    <?php foreach ($cases as $case) {
                                        $rel_data   = get_relation_data($case->rel_type, $case->rel_id);
                                        $rel_values = get_relation_values($rel_data, $case->rel_type);
                                        ?>
                                    <li class="tw-mb-2">
                                        <a href="<?= admin_url('follow_up_management/view/' . $case->id); ?>"><?= e($rel_values['name']); ?></a>
                                        <span class="label label-<?= get_followup_priority_class($case->priority); ?>"><?= e(get_followup_priority_label($case->priority)); ?></span>
                                    </li>
                                    <?php } ?>
                                </ul>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                    <?php } ?>
                </div>

                <!-- Timeline Summary -->
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin"><?= _l('case_timeline'); ?> - <?= _l('recent_activities'); ?></h4>
                        <hr class="hr-panel-separator" />
                        <?php if (empty($detail['timeline_summary'])) { ?>
                        <p class="text-muted"><?= _l('case_no_history'); ?></p>
                        <?php } else { ?>
                        <ul class="list-unstyled">
                            <?php foreach ($detail['timeline_summary'] as $entry) { ?>
                            <li class="tw-flex tw-items-start tw-mb-3">
                                <span class="tw-mr-3"><i class="<?= e(get_followup_history_icon($entry)); ?>"></i></span>
                                <span>
                                    <a href="<?= admin_url('follow_up_management/view/' . $entry->followup_id); ?>"><?= e(get_followup_history_label($entry)); ?></a>
                                    <span class="text-muted">#<?= (int) $entry->followup_id; ?></span>
                                    <br />
                                    <small class="text-muted"><?= e(_dt($entry->event_datetime)); ?></small>
                                </span>
                            </li>
                            <?php } ?>
                        </ul>
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
        new Chart($('#chart-daily'), { type: 'bar', data: <?= json_encode($detail['daily_activity']); ?>, options: { responsive: true, legend: { display: false }, scales: { yAxes: [{ ticks: { beginAtZero: true } }] } } });
        new Chart($('#chart-weekly'), { type: 'bar', data: <?= json_encode($detail['weekly_activity']); ?>, options: { responsive: true, legend: { display: false }, scales: { yAxes: [{ ticks: { beginAtZero: true } }] } } });
        new Chart($('#chart-monthly'), { type: 'line', data: <?= json_encode($detail['monthly_activity']); ?>, options: { responsive: true, legend: { display: false }, scales: { yAxes: [{ ticks: { beginAtZero: true } }] } } });
    });
</script>
</body>
</html>
