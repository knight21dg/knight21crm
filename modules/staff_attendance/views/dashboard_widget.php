<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Admin-only Today's Attendance summary widget. Self-loading, same
 * convention as follow_up_management's dashboard widgets - fetches its
 * own data via $CI = &get_instance() rather than being passed context,
 * since get_dashboard_widgets only registers a view path/container.
 */
$CI = &get_instance();

if (!is_staff_member()) {
    return;
}

$CI->load->model('staff_attendance/staff_attendance_model');
$CI->lang->load('staff_attendance/staff_attendance', 'english');

if (!is_admin()) {
    // Attendance Module Enhancement - a plain staff member landing on the
    // native Admin Dashboard (i.e. not funneled into one of the
    // department portals, which have their own dashboards) gets the
    // Employee Dashboard widgets here instead of the company-wide
    // Present/Absent/Late/... summary below, which is Admin-only by
    // design (per-employee figures, not a personal one).
    staff_attendance_dashboard_widgets(get_staff_user_id());

    return;
}

$summary          = $CI->staff_attendance_model->get_today_summary();
$avg_minutes      = $CI->staff_attendance_model->get_today_avg_working_minutes();
$pending_leave    = $CI->staff_attendance_model->get_pending_leave_requests_count();
$pending_late     = $CI->staff_attendance_model->get_pending_late_arrival_requests_count();
$pending_early    = $CI->staff_attendance_model->get_pending_early_exit_requests_count();
?>
<div class="panel_s">
    <div class="panel-body">
        <h4 class="no-margin tw-flex tw-items-center tw-gap-2">
            <i class="fa-regular fa-clock"></i> <?= _l('staff_attendance_widget_title'); ?>
        </h4>
        <hr class="hr-panel-separator" />
        <div class="row">
            <?php foreach (get_attendance_statuses() as $status) { ?>
            <div class="col-md-4 col-sm-4 col-xs-6 tw-mb-3">
                <div class="tw-flex tw-items-center tw-gap-2">
                    <span class="tw-inline-block tw-rounded-full" style="width:10px;height:10px;background:<?= get_attendance_status_color($status); ?>;"></span>
                    <span class="text-muted"><?= e($status); ?></span>
                </div>
                <div class="tw-text-2xl tw-font-semibold"><?= (int) $summary[$status]; ?></div>
            </div>
            <?php } ?>
            <div class="col-md-4 col-sm-4 col-xs-6 tw-mb-3">
                <div class="text-muted"><?= _l('staff_attendance_widget_avg_working_hours'); ?></div>
                <div class="tw-text-2xl tw-font-semibold"><?= format_working_minutes($avg_minutes); ?></div>
            </div>
        </div>
        <hr class="hr-panel-separator" />
        <div class="row">
            <div class="col-md-4 col-sm-4 col-xs-6 tw-mb-3">
                <a href="<?= admin_url('staff_attendance#tab_admin_leave_review'); ?>" class="text-muted"><?= _l('staff_attendance_widget_pending_leave'); ?></a>
                <div class="tw-text-2xl tw-font-semibold"><?= (int) $pending_leave; ?></div>
            </div>
            <div class="col-md-4 col-sm-4 col-xs-6 tw-mb-3">
                <a href="<?= admin_url('staff_attendance#tab_admin_late_review'); ?>" class="text-muted"><?= _l('staff_attendance_widget_pending_late'); ?></a>
                <div class="tw-text-2xl tw-font-semibold"><?= (int) $pending_late; ?></div>
            </div>
            <div class="col-md-4 col-sm-4 col-xs-6 tw-mb-3">
                <a href="<?= admin_url('staff_attendance#tab_admin_early_review'); ?>" class="text-muted"><?= _l('staff_attendance_widget_pending_early'); ?></a>
                <div class="tw-text-2xl tw-font-semibold"><?= (int) $pending_early; ?></div>
            </div>
        </div>
        <a href="<?= admin_url('staff_attendance'); ?>" class="tw-text-sm"><?= _l('staff_attendance_view_all'); ?> <i class="fa-solid fa-arrow-right"></i></a>
    </div>
</div>
