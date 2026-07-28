<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<?php
/**
 * Operations Manager Dashboard, built on the same shared
 * staff_dashboard_helper.php components as Dev/DM Portal and the
 * Telecaller dashboard - only the data differs
 * (Manager_portal_model::get_dashboard_summary()/get_dashboard_operational_section()).
 * Company-wide, no department scoping.
 *
 * Clickability note (Phase 3): Team Members links to Team; Active
 * Projects/Active Tasks/Completed Today/Pending Tasks/Overdue Tasks now
 * link to the Projects/Tasks pages built this phase, each pre-filtered to
 * match this card's own count exactly (see Manager_portal_model::
 * get_tasks()'s active/pending/overdue/completed_today filter keys and
 * get_projects()'s active filter - same definitions get_dashboard_summary()
 * itself counts by). Attendance Today/DWU Submitted/DWU Pending/
 * Productivity stay unlinked until their target pages land in Phase
 * 4/5/6 - each href is added the same commit that builds its target,
 * never left pointing at a page that doesn't exist yet.
 */
?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <?php staff_dashboard_header(get_staff_full_name(get_staff_user_id()), _l('manager_portal_scope_label')); ?>

                <?php staff_dashboard_kpi_grid_open(); ?>
                <?php staff_dashboard_kpi_card(
                    _l('manager_portal_kpi_team'),
                    (int) $summary['team_count'],
                    'fa-solid fa-people-group',
                    'projects',
                    '',
                    null,
                    admin_url('manager_portal/team')
                ); ?>
                <?php staff_dashboard_kpi_card(
                    _l('manager_portal_kpi_active_projects'),
                    (int) $summary['active_projects'],
                    'fa-solid fa-diagram-project',
                    'projects',
                    '',
                    null,
                    admin_url('manager_portal/projects?active=active')
                ); ?>
                <?php staff_dashboard_kpi_card(
                    _l('manager_portal_kpi_active_tasks'),
                    (int) $summary['active_tasks'],
                    'fa-solid fa-list-check',
                    'pending',
                    '',
                    null,
                    admin_url('manager_portal/tasks?active=active')
                ); ?>
                <?php staff_dashboard_kpi_card(
                    _l('manager_portal_kpi_completed_today'),
                    (int) $summary['completed_today'],
                    'fa-solid fa-circle-check',
                    'completed',
                    '',
                    null,
                    admin_url('manager_portal/tasks?completed_today=1')
                ); ?>
                <?php staff_dashboard_kpi_card(
                    _l('manager_portal_kpi_pending_tasks'),
                    (int) $summary['pending_tasks'],
                    'fa-solid fa-hourglass-half',
                    'pending',
                    '',
                    null,
                    admin_url('manager_portal/tasks?pending=1')
                ); ?>
                <?php staff_dashboard_kpi_card(
                    _l('manager_portal_kpi_overdue_tasks'),
                    (int) $summary['overdue_tasks'],
                    'fa-solid fa-triangle-exclamation',
                    'deadlines',
                    '',
                    null,
                    admin_url('manager_portal/tasks?overdue=1')
                ); ?>
                <?php staff_dashboard_kpi_card(
                    _l('manager_portal_kpi_attendance_today'),
                    (int) $summary['attendance_today']['Present'],
                    'fa-solid fa-clock',
                    'attendance',
                    _l('manager_portal_kpi_attendance_today_subtitle'),
                    null,
                    admin_url('manager_portal/attendance?date_preset=today')
                ); ?>
                <?php staff_dashboard_kpi_card(
                    _l('manager_portal_attendance_kpi_absent_today'),
                    (int) $attendance_review['absent_today'],
                    'fa-solid fa-user-slash',
                    'deadlines',
                    '',
                    null,
                    admin_url('manager_portal/attendance?date_preset=today&status=Absent')
                ); ?>
                <?php staff_dashboard_kpi_card(
                    _l('manager_portal_attendance_kpi_late_today'),
                    (int) $attendance_review['late_today'],
                    'fa-solid fa-hourglass-end',
                    'work_update',
                    '',
                    null,
                    admin_url('manager_portal/attendance?date_preset=today&status=Late')
                ); ?>
                <?php staff_dashboard_kpi_card(
                    _l('manager_portal_attendance_kpi_half_day'),
                    (int) $attendance_review['half_day_today'],
                    'fa-solid fa-business-time',
                    'work_update',
                    '',
                    null,
                    admin_url('manager_portal/attendance?date_preset=today&status=' . rawurlencode('Half Day'))
                ); ?>
                <?php staff_dashboard_kpi_card(
                    _l('manager_portal_attendance_kpi_percent'),
                    (int) $attendance_review['attendance_percent'] . '%',
                    'fa-solid fa-chart-pie',
                    'attendance',
                    '',
                    null,
                    admin_url('manager_portal/attendance?date_preset=today')
                ); ?>
                <?php staff_dashboard_kpi_card(
                    _l('manager_portal_attendance_kpi_average_hours'),
                    $attendance_review['average_hours_label'],
                    'fa-solid fa-stopwatch',
                    'attendance',
                    '',
                    null,
                    admin_url('manager_portal/attendance?date_preset=today')
                ); ?>
                <?php staff_dashboard_kpi_card(
                    _l('manager_portal_kpi_dwu_submitted'),
                    (int) $summary['daily_work_update']['submitted'],
                    'fa-solid fa-file-circle-check',
                    'work_update',
                    '',
                    null,
                    admin_url('manager_portal/daily_work_updates?date_preset=today')
                ); ?>
                <?php staff_dashboard_kpi_card(
                    _l('manager_portal_kpi_dwu_pending'),
                    (int) $summary['daily_work_update']['pending'],
                    'fa-solid fa-file-circle-exclamation',
                    'work_update',
                    '',
                    null,
                    admin_url('manager_portal/daily_work_updates?missing=1')
                ); ?>
                <?php staff_dashboard_kpi_card(
                    _l('manager_portal_dwu_kpi_pending_reviews'),
                    (int) $dwu_review['pending_reviews'],
                    'fa-solid fa-magnifying-glass',
                    'work_update',
                    '',
                    null,
                    admin_url('manager_portal/daily_work_updates?review_status=pending')
                ); ?>
                <?php staff_dashboard_kpi_card(
                    _l('manager_portal_dwu_kpi_approved_today'),
                    (int) $dwu_review['approved_today'],
                    'fa-solid fa-check-double',
                    'completed',
                    '',
                    null,
                    admin_url('manager_portal/daily_work_updates?review_status=approved&reviewed_today=1')
                ); ?>
                <?php staff_dashboard_kpi_card(
                    _l('manager_portal_dwu_kpi_needs_revision'),
                    (int) $dwu_review['needs_revision'],
                    'fa-solid fa-rotate-left',
                    'deadlines',
                    '',
                    null,
                    admin_url('manager_portal/daily_work_updates?review_status=needs_revision')
                ); ?>
                <?php staff_dashboard_kpi_card(
                    _l('manager_portal_kpi_productivity'),
                    (int) $summary['productivity'] . '%',
                    'fa-solid fa-gauge-high',
                    'performance',
                    '',
                    null,
                    admin_url('manager_portal/reports')
                ); ?>
                <?php staff_dashboard_kpi_card(
                    _l('manager_portal_kpi_pending_leave_requests'),
                    (int) $attendance_requests['pending_leave'],
                    'fa-solid fa-calendar-days',
                    'pending',
                    '',
                    null,
                    admin_url('manager_portal/attendance?review_status=Pending#tab_leave_review')
                ); ?>
                <?php staff_dashboard_kpi_card(
                    _l('manager_portal_kpi_pending_late_arrival_requests'),
                    (int) $attendance_requests['pending_late_arrival'],
                    'fa-solid fa-user-clock',
                    'pending',
                    '',
                    null,
                    admin_url('manager_portal/attendance?review_status=Pending#tab_late_review')
                ); ?>
                <?php staff_dashboard_kpi_card(
                    _l('manager_portal_kpi_pending_early_exit_requests'),
                    (int) $attendance_requests['pending_early_exit'],
                    'fa-solid fa-person-walking-arrow-right',
                    'pending',
                    '',
                    null,
                    admin_url('manager_portal/attendance?review_status=Pending#tab_early_review')
                ); ?>
                <?php staff_dashboard_kpi_card(
                    _l('manager_portal_kpi_employees_on_leave_today'),
                    (int) $attendance_requests['on_leave_today'],
                    'fa-solid fa-umbrella-beach',
                    'attendance',
                    '',
                    null,
                    admin_url('manager_portal/attendance?review_status=Approved#tab_leave_review')
                ); ?>
                <?php staff_dashboard_kpi_card(
                    _l('manager_portal_kpi_upcoming_holidays'),
                    (int) $attendance_requests['upcoming_holidays'],
                    'fa-solid fa-champagne-glasses',
                    'deadlines',
                    '',
                    null,
                    admin_url('manager_portal/attendance')
                ); ?>
                <?php staff_dashboard_kpi_grid_close(); ?>

                <div class="row">
                    <div class="col-md-12">
                        <?php staff_dashboard_panel_open(_l('manager_portal_reports_menu'), 'fa-solid fa-chart-column'); ?>
                        <div class="tw-flex tw-flex-wrap tw-gap-2">
                            <a href="<?= admin_url('manager_portal/reports'); ?>" class="btn btn-default"><i class="fa-solid fa-gauge-high tw-mr-1"></i> <?= _l('manager_portal_reports_productivity'); ?></a>
                            <a href="<?= admin_url('manager_portal/reports_departments'); ?>" class="btn btn-default"><i class="fa-solid fa-building tw-mr-1"></i> <?= _l('manager_portal_reports_departments'); ?></a>
                            <a href="<?= admin_url('manager_portal/reports_projects'); ?>" class="btn btn-default"><i class="fa-solid fa-diagram-project tw-mr-1"></i> <?= _l('manager_portal_reports_projects'); ?></a>
                            <a href="<?= admin_url('manager_portal/reports_attendance_analytics'); ?>" class="btn btn-default"><i class="fa-solid fa-clock tw-mr-1"></i> <?= _l('manager_portal_reports_attendance_analytics'); ?></a>
                            <a href="<?= admin_url('manager_portal/reports_dwu_analytics'); ?>" class="btn btn-default"><i class="fa-solid fa-file-circle-check tw-mr-1"></i> <?= _l('manager_portal_reports_dwu_analytics'); ?></a>
                            <a href="<?= admin_url('manager_portal/reports_task_analytics'); ?>" class="btn btn-default"><i class="fa-solid fa-list-check tw-mr-1"></i> <?= _l('manager_portal_reports_task_analytics'); ?></a>
                            <a href="<?= admin_url('manager_portal/reports_project_analytics'); ?>" class="btn btn-default"><i class="fa-solid fa-chart-pie tw-mr-1"></i> <?= _l('manager_portal_reports_project_analytics'); ?></a>
                        </div>
                        <?php staff_dashboard_panel_close(); ?>
                    </div>
                </div>

                <h4 class="tw-mt-6 tw-mb-3 tw-text-neutral-500" style="font-size:14px;text-transform:uppercase;letter-spacing:.05em;"><?= _l('manager_portal_operational_section'); ?></h4>

                <div class="row">
                    <div class="col-md-6">
                        <?php staff_dashboard_panel_open(_l('manager_portal_dwu_awaiting_review'), 'fa-solid fa-file-circle-question'); ?>
                        <?php if (empty($operational['dwu_awaiting_review'])) { ?>
                        <span class="text-muted"><?= _l('manager_portal_nothing_to_show'); ?></span>
                        <?php } else { ?>
                        <ul class="list-unstyled">
                            <?php foreach ($operational['dwu_awaiting_review'] as $row) { ?>
                            <li class="tw-mb-2">
                                <a href="<?= admin_url('manager_portal/daily_work_update_detail/' . $row['id']); ?>" class="tw-font-medium"><?= e($row['name']); ?></a>
                                <span class="text-muted"> - <?= e(_d($row['work_date'])); ?></span>
                            </li>
                            <?php } ?>
                        </ul>
                        <?php } ?>
                        <?php staff_dashboard_panel_close(); ?>
                    </div>
                    <div class="col-md-6">
                        <?php staff_dashboard_panel_open(_l('manager_portal_absent_today'), 'fa-solid fa-user-slash'); ?>
                        <?php if (empty($operational['absent_today'])) { ?>
                        <span class="text-muted"><?= _l('manager_portal_nothing_to_show'); ?></span>
                        <?php } else { ?>
                        <ul class="list-unstyled">
                            <?php foreach ($operational['absent_today'] as $row) { ?>
                            <li class="tw-mb-2"><?= e($row['name']); ?></li>
                            <?php } ?>
                        </ul>
                        <?php } ?>
                        <?php staff_dashboard_panel_close(); ?>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <?php staff_dashboard_panel_open(_l('manager_portal_attendance_missing_panel'), 'fa-solid fa-user-clock'); ?>
                        <?php if (empty($attendance_missing)) { ?>
                        <span class="text-muted"><?= _l('manager_portal_nothing_to_show'); ?></span>
                        <?php } else { ?>
                        <ul class="list-unstyled">
                            <?php foreach (array_slice($attendance_missing, 0, 8) as $row) { ?>
                            <li class="tw-mb-2">
                                <span class="tw-font-medium"><?= e($row['employee_name']); ?></span>
                                <span class="text-muted"> - <?= $row['department_name'] ? e($row['department_name']) : '-'; ?></span>
                                <span class="label label-default"><?= _l('manager_portal_dwu_status_not_submitted'); ?></span>
                            </li>
                            <?php } ?>
                        </ul>
                        <a href="<?= admin_url('manager_portal/attendance?missing=1'); ?>" class="tw-text-sm"><?= _l('manager_portal_view_all'); ?></a>
                        <?php } ?>
                        <?php staff_dashboard_panel_close(); ?>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <?php staff_dashboard_panel_open(_l('manager_portal_not_submitted_today'), 'fa-solid fa-file-circle-exclamation'); ?>
                        <?php if (empty($operational['not_submitted_today'])) { ?>
                        <span class="text-muted"><?= _l('manager_portal_nothing_to_show'); ?></span>
                        <?php } else { ?>
                        <ul class="list-unstyled">
                            <?php foreach ($operational['not_submitted_today'] as $row) { ?>
                            <li class="tw-mb-2"><?= e($row['name']); ?></li>
                            <?php } ?>
                        </ul>
                        <?php } ?>
                        <?php staff_dashboard_panel_close(); ?>
                    </div>
                    <div class="col-md-6">
                        <?php staff_dashboard_panel_open(_l('manager_portal_overdue_tasks'), 'fa-solid fa-triangle-exclamation'); ?>
                        <?php if (empty($operational['overdue_tasks'])) { ?>
                        <span class="text-muted"><?= _l('manager_portal_nothing_to_show'); ?></span>
                        <?php } else { ?>
                        <ul class="list-unstyled">
                            <?php foreach ($operational['overdue_tasks'] as $row) { ?>
                            <li class="tw-mb-2">
                                <a href="<?= admin_url('manager_portal/task/' . $row['id']); ?>"><?= e($row['name']); ?></a>
                                <span class="text-muted"> - <?= e(_d($row['duedate'])); ?></span>
                            </li>
                            <?php } ?>
                        </ul>
                        <?php } ?>
                        <?php staff_dashboard_panel_close(); ?>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <?php staff_dashboard_panel_open(_l('manager_portal_overdue_projects'), 'fa-solid fa-diagram-project'); ?>
                        <?php if (empty($operational['overdue_projects'])) { ?>
                        <span class="text-muted"><?= _l('manager_portal_nothing_to_show'); ?></span>
                        <?php } else { ?>
                        <ul class="list-unstyled">
                            <?php foreach ($operational['overdue_projects'] as $row) { ?>
                            <li class="tw-mb-2">
                                <a href="<?= admin_url('manager_portal/project/' . $row['id']); ?>"><?= e($row['name']); ?></a>
                                <span class="text-muted"> - <?= e(_d($row['deadline'])); ?></span>
                            </li>
                            <?php } ?>
                        </ul>
                        <?php } ?>
                        <?php staff_dashboard_panel_close(); ?>
                    </div>
                    <div class="col-md-6">
                        <?php staff_dashboard_panel_open(_l('manager_portal_upcoming_deadlines'), 'fa-solid fa-calendar-days'); ?>
                        <?php if (empty($operational['upcoming_deadlines'])) { ?>
                        <span class="text-muted"><?= _l('manager_portal_no_deadlines'); ?></span>
                        <?php } else { ?>
                        <ul class="list-unstyled">
                            <?php foreach ($operational['upcoming_deadlines'] as $deadline) { ?>
                            <li class="tw-mb-2">
                                <i class="<?= $deadline['type'] === 'project' ? 'fa-solid fa-diagram-project' : 'fa-solid fa-list-check'; ?> tw-text-gray-400"></i>
                                <a href="<?= admin_url('manager_portal/' . ($deadline['type'] === 'project' ? 'project' : 'task') . '/' . $deadline['id']); ?>"><?= e($deadline['name']); ?></a>
                                <span class="text-muted"> - <?= e(_d($deadline['date'])); ?></span>
                            </li>
                            <?php } ?>
                        </ul>
                        <?php } ?>
                        <?php staff_dashboard_panel_close(); ?>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <?php staff_dashboard_panel_open(_l('manager_portal_recent_activity'), 'fa-solid fa-timeline'); ?>
                        <?php if (empty($summary['recent_activity'])) { ?>
                        <span class="text-muted"><?= _l('manager_portal_no_recent_activity'); ?></span>
                        <?php } else { ?>
                        <ul class="list-unstyled">
                            <?php foreach ($summary['recent_activity'] as $activity) { ?>
                            <li class="tw-mb-2">
                                <span class="tw-font-medium"><?= e(trim($activity['firstname'] . ' ' . $activity['lastname'])); ?></span>
                                <span class="text-muted"> - <?= e($activity['project_name']); ?></span>
                                <div class="text-muted" style="font-size:12px;"><?= e(_dt($activity['date'])); ?></div>
                            </li>
                            <?php } ?>
                        </ul>
                        <?php } ?>
                        <?php staff_dashboard_panel_close(); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
</body>
</html>
