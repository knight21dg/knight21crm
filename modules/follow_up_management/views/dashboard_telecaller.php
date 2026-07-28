<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<?php
/**
 * Standard Staff Dashboard template (application/helpers/staff_dashboard_helper.php)
 * - identical layout/design system used by dm_portal and dev_portal; only
 * the data fed into the shared renderers differs. $case_counts/$lead_counts
 * come from the exact same Follow_up_management::dashboard() controller/
 * model calls as before this redesign (get_case_counts()/
 * get_lead_status_counts()) - this file only changed how they're rendered.
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
                    _l('followup_dashboard_my_assigned_leads'),
                    (int) $lead_counts['my_assigned_leads'],
                    'fa-solid fa-users',
                    'projects',
                    '',
                    null,
                    admin_url('follow_up_management/my_follow_ups')
                ); ?>
                <?php staff_dashboard_kpi_card(
                    _l('todays_followups'),
                    (int) $case_counts['todays_followups'],
                    'fa-solid fa-phone-volume',
                    'pending',
                    '',
                    null,
                    admin_url('follow_up_management/followups_filtered/today')
                ); ?>
                <?php staff_dashboard_kpi_card(
                    _l('card_overdue_cases'),
                    (int) $case_counts['overdue_cases'],
                    'fa-solid fa-triangle-exclamation',
                    'deadlines',
                    '',
                    null,
                    admin_url('follow_up_management/followups_filtered/overdue')
                ); ?>
                <?php staff_dashboard_kpi_card(
                    _l('followup_dashboard_customer_confirmed'),
                    (int) $lead_counts['customer_confirmed'],
                    'fa-solid fa-circle-check',
                    'completed',
                    '',
                    null,
                    admin_url('follow_up_management/my_leads')
                ); ?>
                <?php staff_dashboard_kpi_card(
                    _l('followup_dashboard_lost_leads'),
                    (int) $lead_counts['lost'],
                    'fa-solid fa-circle-xmark',
                    'performance',
                    '',
                    null,
                    admin_url('follow_up_management/my_leads')
                ); ?>
                <?php staff_dashboard_kpi_card(
                    _l('telecaller_dashboard_todays_calls'),
                    (int) $todays_calls_count,
                    'fa-solid fa-phone',
                    'notifications',
                    '',
                    null,
                    admin_url('follow_up_management/followups_filtered/today')
                ); ?>
                <?php staff_dashboard_kpi_card(
                    _l('telecaller_dashboard_ready_for_conversion'),
                    (int) $ready_for_conversion_count,
                    'fa-solid fa-star',
                    'deadlines',
                    '',
                    null,
                    admin_url('follow_up_management/my_leads')
                ); ?>
                <?php staff_dashboard_kpi_card(
                    _l('telecaller_dashboard_converted_customers'),
                    (int) $converted_customers_count,
                    'fa-solid fa-user-check',
                    'completed',
                    '',
                    null,
                    admin_url('follow_up_management/customers')
                ); ?>
                <?php staff_dashboard_kpi_grid_close(); ?>

                <div class="row">
                    <div class="col-md-6">
                        <?php staff_dashboard_panel_open(_l('telecaller_dashboard_recent_calls'), 'fa-solid fa-phone'); ?>
                        <?php if (empty($recent_calls)) { ?>
                        <span class="text-muted"><?= _l('telecaller_no_calls_logged'); ?></span>
                        <?php } else { ?>
                        <ul class="list-unstyled">
                            <?php foreach ($recent_calls as $call) { ?>
                            <li class="tw-mb-2">
                                <i class="fa-solid fa-phone tw-text-gray-400"></i>
                                <a href="<?= admin_url('follow_up_management/lead_details/' . $call['lead_id']); ?>"><?= e($call['lead_name']); ?></a>
                                <?php if (!empty($call['outcome'])) { ?>
                                <span class="label label-default"><?= e(get_followup_outcome_label($call['outcome'])); ?></span>
                                <?php } ?>
                                <span class="text-muted"> - <?= e(_dt($call['event_datetime'])); ?></span>
                            </li>
                            <?php } ?>
                        </ul>
                        <?php } ?>
                        <?php staff_dashboard_panel_close(); ?>
                    </div>
                    <div class="col-md-6">
                        <?php staff_dashboard_panel_open(_l('followup_todays_work_update'), 'fa-solid fa-clipboard-list'); ?>
                        <?php staff_dashboard_kpi_grid_open(); ?>
                        <?php staff_dashboard_kpi_card(
                            _l('followup_calls_made_today'),
                            (int) $todays_calls_count,
                            'fa-solid fa-phone',
                            'notifications'
                        ); ?>
                        <?php staff_dashboard_kpi_card(
                            _l('followup_followups_done_today'),
                            (int) ($todays_calls_count ?? 0),
                            'fa-solid fa-check-double',
                            'completed'
                        ); ?>
                        <?php staff_dashboard_kpi_grid_close(); ?>
                        <?php staff_dashboard_panel_close(); ?>

                        <?php staff_dashboard_panel_open(_l('followup_attendance'), 'fa-solid fa-calendar-check'); ?>
                        <?php staff_dashboard_kpi_grid_open(); ?>
                        <?php staff_dashboard_kpi_card(
                            _l('followup_present_today'),
                            '1',
                            'fa-solid fa-user-check',
                            'completed'
                        ); ?>
                        <?php staff_dashboard_kpi_card(
                            _l('followup_login_time'),
                            date('h:i A'),
                            'fa-solid fa-clock',
                            'notifications'
                        ); ?>
                        <?php staff_dashboard_kpi_grid_close(); ?>
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
