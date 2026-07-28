<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">

                <!-- Quick Actions -->
                <div class="tw-mb-4 tw-flex tw-flex-wrap tw-gap-2">
                    <a href="<?= admin_url('follow_up_management/my_cases'); ?>" class="btn btn-default"><i class="fa-regular fa-list-alt tw-mr-1"></i> <?= _l('my_cases'); ?></a>
                    <a href="<?= admin_url('follow_up_management/todays_followups'); ?>" class="btn btn-default"><i class="fa-regular fa-clock tw-mr-1"></i> <?= _l('todays_followups'); ?></a>
                    <a href="<?= admin_url('utilities/calendar'); ?>" class="btn btn-default"><i class="fa-regular fa-calendar tw-mr-1"></i> <?= _l('quick_action_calendar'); ?></a>
                    <a href="<?= admin_url('reports/followups'); ?>" class="btn btn-default"><i class="fa-solid fa-chart-column tw-mr-1"></i> <?= _l('quick_action_reports'); ?></a>
                    <a href="<?= admin_url('follow_up_management/all_follow_ups'); ?>" class="btn btn-default"><i class="fa-regular fa-folder-open tw-mr-1"></i> <?= _l('quick_action_open_case'); ?></a>
                    <a href="<?= admin_url('follow_up_management/my_cases'); ?>" class="btn btn-default"><i class="fa-regular fa-bell tw-mr-1"></i> <?= _l('quick_action_create_reminder'); ?></a>
                </div>

                <!-- KPI Cards -->
                <div class="tw-grid tw-grid-cols-2 sm:tw-grid-cols-4 lg:tw-grid-cols-4 tw-gap-3 tw-mb-4">
                    <a href="<?= admin_url('follow_up_management/my_cases?case_status=open'); ?>" class="panel_s tw-mb-0 tw-block hover:tw-shadow-md">
                        <div class="panel-body text-center">
                            <h3 class="no-margin"><?= (int) $kpis['total_open_cases']; ?></h3>
                            <small class="text-muted"><?= _l('card_total_open_cases'); ?></small>
                        </div>
                    </a>
                    <a href="<?= admin_url('follow_up_management/my_cases?staff_id=' . get_staff_user_id()); ?>" class="panel_s tw-mb-0 tw-block hover:tw-shadow-md">
                        <div class="panel-body text-center">
                            <h3 class="no-margin"><?= (int) $kpis['my_assigned_cases']; ?></h3>
                            <small class="text-muted"><?= _l('card_my_assigned_cases'); ?></small>
                        </div>
                    </a>
                    <a href="<?= admin_url('follow_up_management/todays_followups'); ?>" class="panel_s tw-mb-0 tw-block hover:tw-shadow-md">
                        <div class="panel-body text-center">
                            <h3 class="no-margin"><?= (int) $kpis['todays_followups']; ?></h3>
                            <small class="text-muted"><?= _l('card_todays_followups'); ?></small>
                        </div>
                    </a>
                    <a href="<?= admin_url('follow_up_management/todays_followups'); ?>" class="panel_s tw-mb-0 tw-block hover:tw-shadow-md">
                        <div class="panel-body text-center">
                            <h3 class="no-margin tw-text-red-600"><?= (int) $kpis['overdue_cases']; ?></h3>
                            <small class="text-muted"><?= _l('card_overdue_cases'); ?></small>
                        </div>
                    </a>
                    <a href="<?= admin_url('follow_up_management/my_cases?priority=high'); ?>" class="panel_s tw-mb-0 tw-block hover:tw-shadow-md">
                        <div class="panel-body text-center">
                            <h3 class="no-margin"><?= (int) $kpis['high_priority_cases']; ?></h3>
                            <small class="text-muted"><?= _l('card_high_priority'); ?></small>
                        </div>
                    </a>
                    <div class="panel_s tw-mb-0">
                        <div class="panel-body text-center">
                            <h3 class="no-margin tw-text-green-600"><?= (int) $kpis['closed_today']; ?></h3>
                            <small class="text-muted"><?= _l('card_closed_today'); ?></small>
                        </div>
                    </div>
                    <div class="panel_s tw-mb-0">
                        <div class="panel-body text-center">
                            <h3 class="no-margin tw-text-green-600"><?= (int) $kpis['completed_this_week']; ?></h3>
                            <small class="text-muted"><?= _l('card_completed_this_week'); ?></small>
                        </div>
                    </div>
                    <div class="panel_s tw-mb-0">
                        <div class="panel-body text-center">
                            <h3 class="no-margin"><?= e(get_followup_format_duration($kpis['avg_response_time'])); ?></h3>
                            <small class="text-muted"><?= _l('card_avg_response_time'); ?></small>
                        </div>
                    </div>
                </div>

                <!-- Charts -->
                <div class="row">
                    <div class="col-md-4">
                        <div class="panel_s">
                            <div class="panel-heading"><h3 class="panel-title"><?= _l('chart_cases_by_status'); ?></h3></div>
                            <div class="panel-body"><canvas height="180" id="chart-cases-by-status"></canvas></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="panel_s">
                            <div class="panel-heading"><h3 class="panel-title"><?= _l('chart_cases_by_priority'); ?></h3></div>
                            <div class="panel-body"><canvas height="180" id="chart-cases-by-priority"></canvas></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="panel_s">
                            <div class="panel-heading"><h3 class="panel-title"><?= _l('chart_department_performance'); ?></h3></div>
                            <div class="panel-body"><canvas height="180" id="chart-department-performance"></canvas></div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="panel_s">
                            <div class="panel-heading"><h3 class="panel-title"><?= _l('chart_cases_created_vs_closed'); ?></h3></div>
                            <div class="panel-body"><canvas height="150" id="chart-created-closed"></canvas></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="panel_s">
                            <div class="panel-heading"><h3 class="panel-title"><?= _l('chart_daily_followup_activity'); ?></h3></div>
                            <div class="panel-body"><canvas height="150" id="chart-daily-activity"></canvas></div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="panel_s">
                            <div class="panel-heading"><h3 class="panel-title"><?= _l('chart_monthly_closure_trend'); ?></h3></div>
                            <div class="panel-body"><canvas height="90" id="chart-monthly-trend"></canvas></div>
                        </div>
                    </div>
                </div>

                <!-- Today's Performance -->
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin"><?= _l('todays_performance'); ?></h4>
                        <hr class="hr-panel-separator" />
                        <div class="tw-grid tw-grid-cols-2 sm:tw-grid-cols-5 tw-gap-3 text-center">
                            <div>
                                <h4 class="no-margin"><?= (int) $performance['calls_logged_today']; ?></h4>
                                <small class="text-muted"><?= _l('perf_calls_logged_today'); ?></small>
                            </div>
                            <div>
                                <h4 class="no-margin"><?= (int) $performance['meetings_scheduled_today']; ?></h4>
                                <small class="text-muted"><?= _l('perf_meetings_scheduled_today'); ?></small>
                            </div>
                            <div>
                                <h4 class="no-margin"><?= (int) $performance['reminders_created_today']; ?></h4>
                                <small class="text-muted"><?= _l('perf_reminders_created_today'); ?></small>
                            </div>
                            <div>
                                <h4 class="no-margin"><?= (int) $performance['cases_closed_today']; ?></h4>
                                <small class="text-muted"><?= _l('perf_cases_closed_today'); ?></small>
                            </div>
                            <div>
                                <h4 class="no-margin"><?= (int) $performance['cases_reopened_today']; ?></h4>
                                <small class="text-muted"><?= _l('perf_cases_reopened_today'); ?></small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Upcoming Follow-ups -->
                    <div class="col-md-7">
                        <div class="panel_s">
                            <div class="panel-body">
                                <h4 class="no-margin"><?= _l('upcoming_followups'); ?></h4>
                                <hr class="hr-panel-separator" />
                                <?php if (empty($upcoming)) { ?>
                                <p class="text-muted"><?= _l('case_no_upcoming_followups'); ?></p>
                                <?php } else { ?>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th><?= _l('case_column_lead_name'); ?></th>
                                                <th><?= _l('case_column_company'); ?></th>
                                                <th><?= _l('case_column_department'); ?></th>
                                                <th><?= _l('case_column_assigned_staff'); ?></th>
                                                <th><?= _l('case_field_priority'); ?></th>
                                                <th><?= _l('case_column_next_follow_up'); ?></th>
                                                <th><?= _l('options'); ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($upcoming as $case) {
                                                $rel_data   = get_relation_data($case->rel_type, $case->rel_id);
                                                $rel_values = get_relation_values($rel_data, $case->rel_type);
                                                $company    = '';
                                                if ($case->rel_type === 'lead') {
                                                    $CI   = &get_instance();
                                                    $lead = $CI->db->select('company')->where('id', $case->rel_id)->get(db_prefix() . 'leads')->row();
                                                    $company = $lead ? $lead->company : '';
                                                }
                                                ?>
                                            <tr>
                                                <td><a href="<?= e($rel_values['link']); ?>"><?= e($rel_values['name']); ?></a></td>
                                                <td><?= $company ? e($company) : '-'; ?></td>
                                                <td><?= e(get_business_department_name($case->department_id)) ?: '-'; ?></td>
                                                <td><?= e(get_staff_full_name($case->assigned_staff_id)); ?></td>
                                                <td><span class="label label-<?= get_followup_priority_class($case->priority); ?>"><?= e(get_followup_priority_label($case->priority)); ?></span></td>
                                                <td><?= e(_dt($case->next_follow_up_date)); ?></td>
                                                <td><a href="<?= admin_url('follow_up_management/view/' . $case->id); ?>" class="btn btn-default btn-icon"><?= _l('case_quick_open'); ?></a></td>
                                            </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php } ?>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activities -->
                    <div class="col-md-5">
                        <div class="panel_s">
                            <div class="panel-body">
                                <h4 class="no-margin"><?= _l('recent_activities'); ?></h4>
                                <hr class="hr-panel-separator" />
                                <?php if (empty($recent_activities)) { ?>
                                <p class="text-muted"><?= _l('case_no_recent_activity'); ?></p>
                                <?php } else { ?>
                                <ul class="list-unstyled">
                                    <?php foreach ($recent_activities as $entry) { ?>
                                    <li class="tw-flex tw-items-start tw-mb-3">
                                        <span class="tw-mr-3"><i class="<?= e(get_followup_activity_icon($entry)); ?>"></i></span>
                                        <span>
                                            <a href="<?= admin_url('follow_up_management/view/' . $entry->followup_id); ?>"><?= e(get_followup_activity_label($entry)); ?></a>
                                            <span class="text-muted">#<?= (int) $entry->followup_id; ?></span>
                                            <br />
                                            <small class="text-muted"><?= e(_dt($entry->created_at)); ?></small>
                                        </span>
                                    </li>
                                    <?php } ?>
                                </ul>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Notifications -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="panel_s">
                            <div class="panel-body">
                                <div class="tw-flex tw-items-center tw-justify-between">
                                    <h4 class="no-margin"><?= _l('recent_notifications'); ?></h4>
                                    <a href="<?= admin_url('follow_up_management/notifications'); ?>" class="tw-text-xs"><?= _l('followup_view_all'); ?></a>
                                </div>
                                <hr class="hr-panel-separator" />
                                <?php if (empty($recent_notifications)) { ?>
                                <p class="text-muted"><?= _l('notification_no_notifications'); ?></p>
                                <?php } else { ?>
                                <ul class="list-unstyled">
                                    <?php foreach ($recent_notifications as $notification) {
                                        $additional_data = $notification->additional_data ? unserialize($notification->additional_data) : [];
                                        ?>
                                    <li class="tw-flex tw-items-start tw-mb-3 <?= $notification->isread_inline == 0 ? 'tw-font-semibold' : ''; ?>">
                                        <span class="tw-mr-3"><i class="<?= e(get_followup_notification_icon($notification->description)); ?>"></i></span>
                                        <span>
                                            <a href="<?= admin_url($notification->link); ?>"><?= _l($notification->description, $additional_data); ?></a>
                                            <br />
                                            <small class="text-muted"><?= e(_dt($notification->date)); ?></small>
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
    </div>
</div>
<?php init_tail(); ?>
<script>
    $(function() {
        var chartFont = { display: true };

        new Chart($('#chart-cases-by-status'), {
            type: 'pie',
            data: <?= $chart_by_status; ?>,
            options: { responsive: true }
        });

        new Chart($('#chart-cases-by-priority'), {
            type: 'doughnut',
            data: <?= $chart_by_priority; ?>,
            options: { responsive: true }
        });

        new Chart($('#chart-department-performance'), {
            type: 'bar',
            data: <?= $chart_department; ?>,
            options: {
                responsive: true,
                legend: { display: false },
                scales: {
                    yAxes: [{ ticks: { beginAtZero: true } }]
                }
            }
        });

        new Chart($('#chart-created-closed'), {
            type: 'line',
            data: <?= $chart_created_closed; ?>,
            options: {
                responsive: true,
                scales: {
                    yAxes: [{ ticks: { beginAtZero: true } }]
                }
            }
        });

        new Chart($('#chart-daily-activity'), {
            type: 'bar',
            data: <?= $chart_daily_activity; ?>,
            options: {
                responsive: true,
                legend: { display: false },
                scales: {
                    yAxes: [{ ticks: { beginAtZero: true } }]
                }
            }
        });

        new Chart($('#chart-monthly-trend'), {
            type: 'line',
            data: <?= $chart_monthly_trend; ?>,
            options: {
                responsive: true,
                legend: { display: false },
                scales: {
                    yAxes: [{ ticks: { beginAtZero: true } }]
                }
            }
        });
    });
</script>
</body>
</html>
