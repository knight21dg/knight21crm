<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<?php
/**
 * Standard Staff Dashboard template (application/helpers/staff_dashboard_helper.php)
 * - identical layout/design system used by dm_portal and the Telecaller
 * dashboard; only the data fed into the shared renderers differs. The
 * $summary/$attendance_summary values below come from the exact same
 * Dev_portal::dashboard() controller/model calls as before this redesign -
 * this file only changed how they're rendered, not what's queried.
 */
$staff_departments = get_staff_business_departments(get_staff_user_id());
$department_label  = $staff_departments ? implode(', ', array_column($staff_departments, 'name')) : '';
?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <?php staff_dashboard_header(get_staff_full_name(get_staff_user_id()), $department_label); ?>

                <?php staff_dashboard_kpi_grid_open(); ?>
                <?php staff_dashboard_kpi_card(
                    _l('dev_portal_dashboard_assigned_projects'),
                    (int) $summary['assigned_projects'],
                    'fa-solid fa-diagram-project',
                    'projects'
                ); ?>
                <?php staff_dashboard_kpi_card(
                    _l('dev_portal_dashboard_todays_tasks'),
                    (int) $summary['todays_tasks'],
                    'fa-solid fa-list-check',
                    'notifications'
                ); ?>
                <?php staff_dashboard_kpi_card(
                    _l('dev_portal_dashboard_completed_tasks'),
                    (int) $summary['completed_tasks'],
                    'fa-solid fa-circle-check',
                    'completed'
                ); ?>
                <?php staff_dashboard_kpi_grid_close(); ?>

                <div class="row">
                    <div class="col-md-6">
                        <?php staff_dashboard_panel_open(_l('dev_portal_dashboard_upcoming_deadlines'), 'fa-solid fa-calendar-days'); ?>
                        <?php if (empty($summary['upcoming_deadlines'])) { ?>
                        <span class="text-muted"><?= _l('dev_portal_dashboard_no_deadlines'); ?></span>
                        <?php } else { ?>
                        <ul class="list-unstyled">
                            <?php foreach ($summary['upcoming_deadlines'] as $deadline) { ?>
                            <li class="tw-mb-2">
                                <i class="<?= $deadline['type'] === 'project' ? 'fa-solid fa-diagram-project' : 'fa-solid fa-list-check'; ?> tw-text-gray-400"></i>
                                <a href="<?= $deadline['url']; ?>"><?= e($deadline['name']); ?></a>
                                <span class="text-muted"> - <?= e(_d($deadline['date'])); ?></span>
                            </li>
                            <?php } ?>
                        </ul>
                        <?php } ?>
                        <?php staff_dashboard_panel_close(); ?>
                    </div>
                    <div class="col-md-6">
                        <?php staff_dashboard_panel_open(_l('dev_portal_dashboard_attendance_summary'), 'fa-solid fa-clock'); ?>
                        <?php if ($attendance_is_admin) { ?>
                            <div class="row">
                                <?php foreach (get_attendance_statuses() as $status) { ?>
                                <div class="col-xs-4 tw-mb-3">
                                    <div class="tw-flex tw-items-center tw-gap-2">
                                        <span class="tw-inline-block tw-rounded-full" style="width:8px;height:8px;background:<?= get_attendance_status_color($status); ?>;"></span>
                                        <span class="text-muted"><?= e($status); ?></span>
                                    </div>
                                    <div class="tw-text-xl tw-font-semibold"><?= (int) $attendance_summary[$status]; ?></div>
                                </div>
                                <?php } ?>
                            </div>
                        <?php } elseif ($attendance_summary) { ?>
                            <span class="label" style="color:<?= get_attendance_status_color($attendance_summary); ?>;border:1px solid <?= adjust_hex_brightness(get_attendance_status_color($attendance_summary), 0.4); ?>;background:<?= adjust_hex_brightness(get_attendance_status_color($attendance_summary), 0.04); ?>;"><?= e($attendance_summary); ?></span>
                        <?php } else { ?>
                            <span class="text-muted"><?= _l('dev_portal_dashboard_no_attendance_today'); ?></span>
                        <?php } ?>
                        <div class="tw-mt-3">
                            <a href="<?= admin_url('staff_attendance'); ?>"><?= _l('dev_portal_attendance'); ?> <i class="fa-solid fa-arrow-right"></i></a>
                        </div>
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
