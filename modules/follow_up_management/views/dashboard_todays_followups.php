<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
/**
 * "Today's Follow-ups" widget - a prominent daily-work card that renders
 * directly ABOVE the core FullCalendar on the native Dashboard's main/left
 * (left-8) column, for a plain Telecaller only. Registered by
 * follow_up_management_add_todays_followups_widget() in
 * modules/follow_up_management/follow_up_management.php.
 *
 * The rows are NOT a second follow-up data source: they come from the exact
 * same Follow_ups_model::get_todays_followups_grouped() method (and
 * therefore the same get_followup_case_visibility_where() Admin/Manager/
 * Telecaller scope) the full "Today's Follow-ups" page already uses for its
 * "Due Today" section - a plain Telecaller only ever sees their own
 * assigned follow-ups. Overdue + Due Today are merged into one queue (each
 * group already sorted by Next Follow-up Date ascending, so the most
 * overdue rows lead), with the existing per-row badge logic marking rows
 * whose Next Follow-up Date has already passed as "Overdue" - a past-dated
 * follow-up is therefore visible on the Dashboard immediately instead of
 * only on the full page's Overdue section. Row-level display reuses the
 * module's existing label helpers (priority/status/type) and the existing
 * "Due Today"/"Overdue" badge styling (format_followup_next_date_badge()'s
 * own label classes).
 */
$CI = &get_instance();
$CI->load->model('follow_up_management/follow_ups_model');
$groups = $CI->follow_ups_model->get_todays_followups_grouped();
$cases  = array_merge($groups['overdue'], $groups['due_today']);
$now    = date('Y-m-d H:i:s');
?>
<div class="widget" id="widget-dashboard_todays_followups" data-name="<?= _l('todays_followups'); ?>">
    <div class="clearfix"></div>
    <div class="panel_s">
        <div class="panel-body">
            <div class="widget-dragger"></div>

            <div class="tw-flex tw-items-center">
                <i class="fa-solid fa-phone-volume tw-text-neutral-500 tw-mr-2"></i>
                <div>
                    <h4 class="no-margin tw-font-semibold"><?= _l('todays_followups'); ?></h4>
                    <small class="text-muted"><?= _l('followup_dashboard_todays_subtitle'); ?></small>
                </div>
            </div>

            <hr class="hr-panel-separator" />

            <?php if (empty($cases)) { ?>
            <p class="text-muted no-margin"><?= _l('followup_dashboard_no_followups_today'); ?></p>
            <?php } else { ?>
            <ul class="list-unstyled no-margin">
                <?php foreach ($cases as $case) {
                    $rel_data   = get_relation_data($case->rel_type, $case->rel_id);
                    $rel_values = get_relation_values($rel_data, $case->rel_type);
                    $is_overdue = ($case->next_follow_up_date < $now);
                    ?>
                <li class="tw-flex tw-items-center tw-justify-between tw-py-3" style="border-bottom:1px solid #eee;">
                    <div class="tw-pr-2">
                        <a href="<?= e($rel_values['link']); ?>"><strong><?= e($rel_values['name']); ?></strong></a>
                        <br />
                        <small class="text-muted">
                            <i class="fa-regular fa-comment-dots tw-mr-1"></i><?= e(get_followup_type_label($case->follow_up_type)); ?>
                        </small>
                    </div>
                    <div class="tw-text-right tw-shrink-0">
                        <div class="tw-mb-1">
                            <?php if ($is_overdue) { ?>
                            <span class="label label-danger"><?= _l('section_overdue'); ?></span>
                            <?php } else { ?>
                            <span class="label label-warning"><?= _l('followup_due_today'); ?></span>
                            <?php } ?>
                            <a href="<?= admin_url('follow_up_management/view/' . $case->id); ?>" class="btn btn-default btn-icon btn-sm" title="<?= _l('case_quick_open'); ?>">
                                <i class="fa-regular fa-eye"></i>
                            </a>
                        </div>
                        <div>
                            <small class="text-muted">
                                <i class="fa-regular fa-clock tw-mr-1"></i><?= e(_dt($case->next_follow_up_date)); ?>
                            </small>
                            <span class="label label-<?= get_followup_priority_class($case->priority); ?>"><?= e(get_followup_priority_label($case->priority)); ?></span>
                            <span class="label label-<?= $case->status === 'pending' ? 'info' : ($case->status === 'completed' ? 'success' : 'default'); ?>"><?= e(get_followup_status_label($case->status)); ?></span>
                        </div>
                    </div>
                </li>
                <?php } ?>
            </ul>
            <?php } ?>

            <hr class="hr-panel-separator" />

            <a href="<?= admin_url('follow_up_management/my_follow_ups'); ?>" class="btn btn-default btn-sm">
                <i class="fa-solid fa-list-check tw-mr-1"></i><?= _l('followup_view_all'); ?>
            </a>
        </div>
    </div>
</div>