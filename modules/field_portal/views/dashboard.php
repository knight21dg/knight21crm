<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<?php
/**
 * Standard Staff Dashboard template (application/helpers/staff_dashboard_helper.php)
 * - identical layout/design system used by dm_portal/dev_portal/the
 * Telecaller dashboard; only the data fed into the shared renderers
 * differs. Every KPI card is real, live data - see
 * Field_portal_model::get_dashboard_summary()'s own docblock for how
 * each is scoped.
 */
$staff_id           = get_staff_user_id();
$staff_departments  = get_staff_business_departments($staff_id);
$department_label   = $staff_departments ? implode(', ', array_column($staff_departments, 'name')) : '';

$attendance_value = '-';
if ($attendance_is_admin) {
    $attendance_value = (int) array_sum((array) $attendance_summary);
} elseif ($attendance_summary) {
    $attendance_value = $attendance_summary;
} else {
    $attendance_value = _l('field_portal_not_marked');
}

$work_update_value = $today_work_update ? _l('field_portal_submitted') : _l('field_portal_pending');
?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <?php staff_dashboard_header(get_staff_full_name($staff_id), $department_label); ?>

                <?php staff_dashboard_kpi_grid_open(); ?>
                <?php staff_dashboard_kpi_card(
                    _l('field_portal_card_todays_leads'),
                    (int) $summary['todays_leads'],
                    'fa-solid fa-user-plus',
                    'projects',
                    '',
                    null,
                    admin_url('field_portal/my_leads?date=' . date('Y-m-d'))
                ); ?>
                <?php staff_dashboard_kpi_card(
                    _l('field_portal_card_total_leads'),
                    (int) $summary['total_leads'],
                    'fa-solid fa-users',
                    'projects',
                    '',
                    null,
                    admin_url('field_portal/my_leads')
                ); ?>
                <?php staff_dashboard_kpi_card(
                    _l('field_portal_card_todays_meetings'),
                    (int) $summary['todays_meetings'],
                    'fa-solid fa-calendar-check',
                    'deadlines',
                    '',
                    null,
                    admin_url('field_portal/meetings?filter=today')
                ); ?>
                <?php staff_dashboard_kpi_card(
                    _l('field_portal_card_pending_followups'),
                    (int) $summary['pending_followups'],
                    'fa-solid fa-phone-volume',
                    'pending',
                    '',
                    null,
                    admin_url('field_portal/my_leads?status=2')
                ); ?>
                <?php staff_dashboard_kpi_card(
                    _l('field_portal_card_assigned_to_telecaller'),
                    (int) $summary['assigned_to_telecaller'],
                    'fa-solid fa-headset',
                    'notifications',
                    '',
                    null,
                    admin_url('field_portal/assigned_to_telecaller')
                ); ?>
                <?php staff_dashboard_kpi_card(
                    _l('field_portal_card_converted_customers'),
                    (int) $summary['converted_customers'],
                    'fa-solid fa-circle-check',
                    'completed',
                    '',
                    null,
                    admin_url('field_portal/customers')
                ); ?>
                <?php staff_dashboard_kpi_card(
                    _l('field_portal_card_attendance'),
                    $attendance_value,
                    'fa-solid fa-clock',
                    'attendance',
                    '',
                    null,
                    admin_url('staff_attendance')
                ); ?>
                <?php staff_dashboard_kpi_card(
                    _l('field_portal_card_work_update'),
                    $work_update_value,
                    'fa-solid fa-note-sticky',
                    'work_update',
                    '',
                    null,
                    admin_url('daily_work_update')
                ); ?>
                <?php staff_dashboard_kpi_grid_close(); ?>

                <?php
                /**
                 * Shared renderer for the 3 meeting-list dashboard panels
                 * (Today's/Tomorrow's/Overdue) - each is just
                 * Field_portal_model::get_todays_meetings()/etc. rendered
                 * identically, only the source array and empty-state
                 * string differ. A closure (not a plain function) so
                 * re-including this view within the same request - e.g. an
                 * error page fallback - can never trigger a "cannot
                 * redeclare" fatal.
                 */
                $field_portal_render_meeting_list = function ($list, $empty_label) {
                    if (empty($list)) {
                        echo '<span class="text-muted">' . _l($empty_label) . '</span>';

                        return;
                    }
                    echo '<ul class="list-unstyled">';
                    foreach ($list as $meeting) {
                        echo '<li class="tw-mb-2">';
                        echo '<i class="fa-solid fa-calendar-check tw-text-gray-400"></i> ';
                        echo '<a href="' . admin_url('field_portal/lead_details/' . $meeting['lead_id']) . '#meeting-' . $meeting['id'] . '">' . e($meeting['lead_name']) . '</a>';
                        echo '<span class="text-muted"> - ' . ($meeting['duedate'] ? e(_d($meeting['duedate'])) : '-');
                        if (!empty($meeting['meeting_time'])) {
                            echo ' &middot; ' . e($meeting['meeting_time']);
                        }
                        if (!empty($meeting['meeting_type'])) {
                            echo ' &middot; ' . e($meeting['meeting_type']);
                        }
                        echo '</span></li>';
                    }
                    echo '</ul>';
                };
                ?>
                <div class="row">
                    <div class="col-md-6">
                        <?php staff_dashboard_panel_open(_l('field_portal_card_todays_meetings'), 'fa-solid fa-calendar-check', admin_url('field_portal/meetings?filter=today')); ?>
                        <?php $field_portal_render_meeting_list($todays_meetings, 'field_portal_no_todays_meetings'); ?>
                        <?php staff_dashboard_panel_close(); ?>

                        <?php staff_dashboard_panel_open(_l('field_portal_card_overdue_meetings'), 'fa-solid fa-triangle-exclamation', admin_url('field_portal/meetings?filter=overdue')); ?>
                        <?php $field_portal_render_meeting_list($overdue_meetings, 'field_portal_no_overdue_meetings'); ?>
                        <?php staff_dashboard_panel_close(); ?>

                        <?php staff_dashboard_panel_open(_l('field_portal_todays_new_leads'), 'fa-solid fa-user-plus', admin_url('field_portal/my_leads?date=' . date('Y-m-d'))); ?>
                        <?php if (empty($todays_new_leads)) { ?>
                        <span class="text-muted"><?= _l('field_portal_no_todays_new_leads'); ?></span>
                        <?php } else { ?>
                        <ul class="list-unstyled">
                            <?php foreach ($todays_new_leads as $lead) { ?>
                            <li class="tw-mb-2">
                                <i class="fa-solid fa-user-plus tw-text-gray-400"></i>
                                <a href="<?= admin_url('field_portal/lead_details/' . $lead['id']); ?>"><?= e($lead['name']); ?></a>
                                <span class="text-muted"> - <?= $lead['company'] ? e($lead['company']) : '-'; ?></span>
                            </li>
                            <?php } ?>
                        </ul>
                        <?php } ?>
                        <?php staff_dashboard_panel_close(); ?>

                        <?php staff_dashboard_panel_open(_l('field_portal_upcoming_followups'), 'fa-solid fa-phone-volume', admin_url('field_portal/my_leads?status=2')); ?>
                        <?php if (empty($upcoming_followups)) { ?>
                        <span class="text-muted"><?= _l('field_portal_no_upcoming_followups'); ?></span>
                        <?php } else { ?>
                        <ul class="list-unstyled">
                            <?php foreach ($upcoming_followups as $followup) { ?>
                            <li class="tw-mb-2">
                                <i class="fa-solid fa-phone-volume tw-text-gray-400"></i>
                                <a href="<?= admin_url('field_portal/lead_details/' . $followup['id']); ?>"><?= e($followup['name']); ?></a>
                                <span class="text-muted"> - <?= e(_d($followup['next_follow_up_date'])); ?></span>
                            </li>
                            <?php } ?>
                        </ul>
                        <?php } ?>
                        <?php staff_dashboard_panel_close(); ?>
                    </div>
                    <div class="col-md-6">
                        <?php staff_dashboard_panel_open(_l('field_portal_card_tomorrows_meetings'), 'fa-solid fa-calendar-days', admin_url('field_portal/meetings?filter=tomorrow')); ?>
                        <?php $field_portal_render_meeting_list($tomorrows_meetings, 'field_portal_no_tomorrows_meetings'); ?>
                        <?php staff_dashboard_panel_close(); ?>

                        <?php staff_dashboard_panel_open(_l('field_portal_recent_conversions'), 'fa-solid fa-circle-check', admin_url('field_portal/customers')); ?>
                        <?php if (empty($recent_conversions)) { ?>
                        <span class="text-muted"><?= _l('field_portal_no_recent_conversions'); ?></span>
                        <?php } else { ?>
                        <ul class="list-unstyled">
                            <?php foreach ($recent_conversions as $conversion) { ?>
                            <li class="tw-mb-2">
                                <i class="fa-solid fa-circle-check tw-text-gray-400"></i>
                                <?php if ($conversion['client_id']) { ?>
                                <a href="<?= admin_url('field_portal/customers'); ?>"><?= e($conversion['company'] ? $conversion['company'] : $conversion['lead_name']); ?></a>
                                <?php } else { ?>
                                <span><?= e($conversion['lead_name']); ?></span>
                                <?php } ?>
                                <span class="text-muted"> - <?= $conversion['date_converted'] ? e(_dt($conversion['date_converted'])) : '-'; ?></span>
                            </li>
                            <?php } ?>
                        </ul>
                        <?php } ?>
                        <?php staff_dashboard_panel_close(); ?>

                        <?php staff_dashboard_panel_open(_l('field_portal_recent_lead_activity'), 'fa-solid fa-clock-rotate-left', admin_url('field_portal/lead_activity')); ?>
                        <?php if (empty($recent_activity)) { ?>
                        <span class="text-muted"><?= _l('field_portal_no_recent_activity'); ?></span>
                        <?php } else { ?>
                        <ul class="list-unstyled">
                            <?php foreach ($recent_activity as $entry) {
                                // description is a raw language key, not display
                                // text - same translation convention as the
                                // native Lead activity tab (see lead_details.php).
                                $additional_data = !empty($entry['additional_data']) ? unserialize($entry['additional_data']) : '';
                                $activity_text   = $additional_data !== '' ? _l($entry['description'], $additional_data) : e(_l($entry['description']));
                            ?>
                            <li class="tw-mb-2">
                                <i class="fa-solid fa-clock-rotate-left tw-text-gray-400"></i>
                                <a href="<?= admin_url('field_portal/lead_details/' . $entry['leadid']); ?>"><?= e($entry['lead_name']); ?></a>
                                <span class="text-muted"> - <?= $activity_text; ?></span>
                            </li>
                            <?php } ?>
                        </ul>
                        <?php } ?>
                        <?php staff_dashboard_panel_close(); ?>

                        <?php staff_dashboard_panel_open(_l('field_portal_quick_actions'), 'fa-solid fa-bolt'); ?>
                        <div class="tw-flex tw-flex-wrap tw-gap-2">
                            <a href="<?= admin_url('field_portal/create_lead'); ?>" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus"></i> <?= _l('field_portal_create_lead'); ?></a>
                            <a href="<?= admin_url('field_portal/my_leads'); ?>" class="btn btn-default btn-sm"><i class="fa-solid fa-list"></i> <?= _l('field_portal_my_leads'); ?></a>
                            <a href="<?= admin_url('field_portal/customers'); ?>" class="btn btn-default btn-sm"><i class="fa-solid fa-user-check"></i> <?= _l('field_portal_customers'); ?></a>
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
