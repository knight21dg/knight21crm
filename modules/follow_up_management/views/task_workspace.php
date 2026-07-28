<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
// Telecaller Sales Workspace - simplified to exactly 7 items per the
// "simplify and finalize the Telecaller workflow" spec: Lead Details,
// Quick Actions, Call Outcome, Notes, Next Follow-up Date, Lead Status,
// Save. Everything mutating submits together to one unified endpoint
// (Follow_up_management::save_workspace()) rather than the 4 separate
// per-section forms this view used to have - see that method's docblock
// for why the required-field validation lives there, not here.
//
// Removed from the previous 8-section layout (per the same spec's REMOVE
// list): the Lead Pipeline stepper, Appointment/meeting scheduling, the
// Case-status quick-action buttons (Complete/Mark Pending/Cancel - Case
// closing is now entirely status-driven, see auto_convert_confirmed_lead()
// and the 'lost' branch in follow_up_management_on_lead_status_changed()),
// and the "Request Customer Conversion" button (superseded - selecting
// "Customer Confirmed" below IS the conversion trigger).
//
// Injected via before_task_description_section (see
// follow_up_management.php) into the Task modal's own body, above the
// Description heading. Everything reads live from the Lead/Case/history -
// nothing here is a duplicate copy of that data.
$outcome_options = [];
foreach (get_followup_outcomes() as $outcome_key => $outcome_label) {
    $outcome_options[] = ['id' => $outcome_key, 'name' => $outcome_label];
}
$dialable = $lead && $lead->phonenumber ? follow_up_sanitize_phone_for_dialing($lead->phonenumber) : '';
?>
<div class="followup-task-workspace" data-task-id="<?= e($task->id); ?>">
    <div class="panel_s">
        <div class="panel-body">
            <h4 class="no-margin">
                <i class="fa-solid fa-headset tw-mr-2"></i><?= _l('followup_workspace_title'); ?>
                <?php if ($overdue) { ?>
                <span class="label label-danger"><?= _l('followup_workspace_overdue'); ?></span>
                <?php } ?>
            </h4>
            <hr class="hr-panel-separator" />

            <div class="row">
                <!-- 1. Lead Details -->
                <div class="col-md-6">
                    <h5 class="tw-font-semibold"><?= _l('case_lead_information'); ?></h5>
                    <table class="table">
                        <tbody>
                            <tr>
                                <td class="text-muted"><?= _l('lead_add_edit_name'); ?></td>
                                <td><a href="<?= e($rel_link); ?>"><?= e($rel_name); ?></a></td>
                            </tr>
                            <tr>
                                <td class="text-muted"><?= _l('case_field_company'); ?></td>
                                <td><?= $lead && $lead->company ? e($lead->company) : '<span class="text-muted">' . _l('case_not_set') . '</span>'; ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted"><?= _l('case_field_phone'); ?></td>
                                <td><?= $lead && $lead->phonenumber ? e($lead->phonenumber) : '<span class="text-muted">' . _l('case_not_set') . '</span>'; ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted"><?= _l('followup_column_whatsapp'); ?></td>
                                <td><?= $dialable !== '' ? e($lead->phonenumber) : '<span class="text-muted">' . _l('case_not_set') . '</span>'; ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted"><?= _l('case_field_email'); ?></td>
                                <td><?= $lead && $lead->email ? e($lead->email) : '<span class="text-muted">' . _l('case_not_set') . '</span>'; ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted"><?= _l('lead_source'); ?></td>
                                <td><?= $lead && !empty($lead->source_name) ? e($lead->source_name) : '<span class="text-muted">' . _l('case_not_set') . '</span>'; ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- 2. Quick Actions -->
                <div class="col-md-6">
                    <h5 class="tw-font-semibold"><?= _l('followup_workspace_quick_actions'); ?></h5>
                    <?php if ($dialable !== '') { ?>
                    <div class="btn-group flex-wrap" role="group">
                        <a href="tel:<?= e($dialable); ?>" class="btn btn-default btn-sm"><i class="fa-solid fa-phone"></i> <?= _l('followup_workspace_action_call'); ?></a>
                        <a href="https://wa.me/<?= e($dialable); ?>" target="_blank" rel="noopener" class="btn btn-default btn-sm"><i class="fa-brands fa-whatsapp"></i> <?= _l('followup_type_whatsapp'); ?></a>
                        <a href="#" class="btn btn-default btn-sm lead-phone-copy" data-phone="<?= e($dialable); ?>"><i class="fa-regular fa-copy"></i> <?= _l('copy'); ?></a>
                    </div>
                    <?php } else { ?>
                    <span class="text-muted"><?= _l('case_not_set'); ?></span>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>

    <?php if ($can_act) { ?>
    <div class="panel_s">
        <div class="panel-body">
            <form class="followup-workspace-action-form" data-url="<?= admin_url('follow_up_management/save_workspace/' . $case->id); ?>">
                <div class="row">
                    <!-- 3. Call Outcome -->
                    <div class="col-md-6">
                        <?= render_select('outcome', $outcome_options, ['id', 'name'], 'followup_workspace_call_outcome', ''); ?>
                    </div>
                    <!-- 6. Lead Status Dropdown -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="workspace_lead_status_<?= e($task->id); ?>"><?= _l('lead_status'); ?></label>
                            <select id="workspace_lead_status_<?= e($task->id); ?>" name="lead_status" class="form-control workspace-lead-status-select">
                                <?php foreach ($statuses as $status) { ?>
                                <option value="<?= e($status['id']); ?>" data-name="<?= e(strtolower($status['name'])); ?>" <?= ($lead && (int) $lead->status === (int) $status['id']) ? 'selected' : ''; ?>><?= e($status['name']); ?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- 4. Notes -->
                <?= render_textarea('notes', 'case_field_notes', ''); ?>

                <div class="row">
                    <!-- 5. Next Follow-up Date - shown/required only for Follow-up/Interested -->
                    <div class="col-md-6 workspace-next-date-wrap">
                        <?= render_date_input('next_follow_up_date', 'followup_next_follow_up_date_label', $case->next_follow_up_date ? _d($case->next_follow_up_date) : '', ['data-date-min-date' => _d(date('Y-m-d'))]); ?>
                    </div>
                    <!-- Lost Reason - shown/required only for Lost -->
                    <div class="col-md-6 workspace-lost-reason-wrap">
                        <?= render_input('lost_reason', 'followup_workspace_lost_reason', $case->lost_reason ?? ''); ?>
                    </div>
                </div>

                <!-- 7. Save -->
                <button type="submit" class="btn btn-primary"><?= _l('followup_workspace_action_save'); ?></button>
            </form>
        </div>
    </div>
    <?php } ?>

    <!-- History -->
    <div class="row">
        <div class="col-md-12">
            <div class="panel_s">
                <div class="panel-body">
                    <h4 class="no-margin"><?= _l('case_timeline'); ?></h4>
                    <hr class="hr-panel-separator" />
                    <?php if (empty($history)) { ?>
                    <p class="text-muted"><?= _l('case_no_history'); ?></p>
                    <?php } else { ?>
                    <ul class="list-unstyled followup-timeline">
                        <?php foreach (array_reverse($history) as $entry) { ?>
                        <li class="tw-flex tw-items-start tw-mb-3">
                            <span class="tw-mr-3"><i class="<?= e(get_followup_history_icon($entry)); ?>"></i></span>
                            <span>
                                <strong><?= e(get_followup_history_label($entry)); ?></strong>
                                <?php if (!empty($entry->outcome)) { ?>
                                - <span class="label label-default"><?= e(get_followup_outcome_label($entry->outcome)); ?></span>
                                <?php } ?>
                                <br />
                                <small class="text-muted">
                                    <?= e(_dt($entry->event_datetime)); ?> &middot; <?= e(get_staff_full_name($entry->created_by)); ?>
                                </small>
                                <?php if (!empty($entry->notes)) { ?>
                                <div class="tw-mt-1"><?= process_text_content_for_display($entry->notes); ?></div>
                                <?php } ?>
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
