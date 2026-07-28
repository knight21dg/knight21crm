<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-8">
                                <h4 class="no-margin">
                                    <?= e($title); ?>
                                    <span class="label label-<?= $case->case_status == 'open' ? 'info' : 'default'; ?>">
                                        <?= e(get_followup_case_status_label($case->case_status)); ?>
                                    </span>
                                    <span class="label label-<?= get_followup_priority_class($case->priority); ?>">
                                        <?= e(get_followup_priority_label($case->priority)); ?>
                                    </span>
                                </h4>
                                <p>
                                    <?= _l('followup_related'); ?>:
                                    <a href="<?= e($rel_link); ?>"><?= e($rel_name); ?></a>
                                </p>
                            </div>
                            <div class="col-md-4 text-right">
                                <?php if ($can_act_on_case) { ?>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                                        <?= _l('options'); ?> <span class="caret"></span>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-right">
                                        <li><a href="#" data-toggle="modal" data-target="#modal-log-call"><?= _l('case_action_log_call'); ?></a></li>
                                        <li><a href="#" data-toggle="modal" data-target="#modal-schedule-meeting"><?= _l('case_action_schedule_meeting'); ?></a></li>
                                        <li><a href="#" data-toggle="modal" data-target="#modal-schedule-reminder"><?= _l('case_action_schedule_reminder'); ?></a></li>
                                        <li><a href="#" data-toggle="modal" data-target="#modal-add-note"><?= _l('case_action_add_note'); ?></a></li>
                                        <li role="separator" class="divider"></li>
                                        <li><a href="#" data-toggle="modal" data-target="#modal-change-status"><?= _l('case_action_change_status'); ?></a></li>
                                        <li><a href="#" data-toggle="modal" data-target="#modal-change-priority"><?= _l('case_action_change_priority'); ?></a></li>
                                        <li><a href="#" data-toggle="modal" data-target="#modal-reassign-staff"><?= _l('case_action_reassign_staff'); ?></a></li>
                                        <li role="separator" class="divider"></li>
                                        <?php if ($case->case_status == 'open') { ?>
                                        <li><a href="#" id="btn-close-case"><?= _l('case_action_close_case'); ?></a></li>
                                        <?php } else { ?>
                                        <li><a href="#" id="btn-reopen-case"><?= _l('case_action_reopen_case'); ?></a></li>
                                        <?php } ?>
                                    </ul>
                                </div>
                                <?php } ?>
                            </div>
                        </div>
                        <hr class="hr-panel-separator" />

                        <div class="row">
                            <div class="col-md-6">
                                <h5 class="tw-font-semibold"><?= _l('case_lead_information'); ?></h5>
                                <table class="table">
                                    <tbody>
                                        <tr>
                                            <td class="text-muted"><?= _l('case_field_company'); ?></td>
                                            <td><?= $lead && $lead->company ? e($lead->company) : '<span class="text-muted">' . _l('case_not_set') . '</span>'; ?></td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted"><?= _l('case_field_contact_person'); ?></td>
                                            <td><a href="<?= e($rel_link); ?>"><?= e($rel_name); ?></a></td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted"><?= _l('case_field_phone'); ?></td>
                                            <td>
                                                <?php if ($lead && $lead->phonenumber) { ?>
                                                <a href="tel:<?= e($lead->phonenumber); ?>"><?= e($lead->phonenumber); ?></a>
                                                <a href="https://wa.me/<?= e(preg_replace('/[^0-9]/', '', $lead->phonenumber)); ?>" target="_blank" class="tw-ml-2" title="<?= _l('followup_type_whatsapp'); ?>"><i class="fa-brands fa-whatsapp"></i></a>
                                                <?php } else { ?>
                                                <span class="text-muted"><?= _l('case_not_set'); ?></span>
                                                <?php } ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted"><?= _l('case_field_email'); ?></td>
                                            <td><?= $lead && $lead->email ? '<a href="mailto:' . e($lead->email) . '">' . e($lead->email) . '</a>' : '<span class="text-muted">' . _l('case_not_set') . '</span>'; ?></td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted"><?= _l('case_field_department'); ?></td>
                                            <td><?= $department_name ? e($department_name) : '<span class="text-muted">' . _l('case_not_set') . '</span>'; ?></td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted"><?= _l('case_field_assigned_staff'); ?></td>
                                            <td><?= e(get_staff_full_name($case->assigned_staff_id)); ?></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h5 class="tw-font-semibold"><?= _l('case_case_information'); ?></h5>
                                <table class="table">
                                    <tbody>
                                        <tr>
                                            <td class="text-muted"><?= _l('case_field_priority'); ?></td>
                                            <td><span class="label label-<?= get_followup_priority_class($case->priority); ?>"><?= e(get_followup_priority_label($case->priority)); ?></span></td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted"><?= _l('case_field_case_status'); ?></td>
                                            <td><span class="label label-<?= $case->case_status == 'open' ? 'info' : 'default'; ?>"><?= e(get_followup_case_status_label($case->case_status)); ?></span></td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted"><?= _l('case_field_created_date'); ?></td>
                                            <td><?= e(_dt($case->opened_at ?: $case->date_created)); ?></td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted"><?= _l('case_field_last_activity'); ?></td>
                                            <td><?= $case->last_activity_at ? e(_dt($case->last_activity_at)) : '<span class="text-muted">' . _l('case_not_set') . '</span>'; ?></td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted"><?= _l('case_field_next_follow_up'); ?></td>
                                            <td><?= $case->next_follow_up_date ? e(_dt($case->next_follow_up_date)) : '<span class="text-muted">' . _l('case_not_set') . '</span>'; ?></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-7">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin"><?= _l('case_timeline'); ?></h4>
                        <hr class="hr-panel-separator" />
                        <?php if (empty($history)) { ?>
                        <p class="text-muted"><?= _l('case_no_history'); ?></p>
                        <?php } else { ?>
                        <ul class="list-unstyled followup-timeline">
                            <?php foreach (array_reverse($history) as $entry) { ?>
                            <li class="tw-flex tw-items-start tw-mb-4">
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
            <div class="col-md-5">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin"><?= _l('case_activity_log'); ?></h4>
                        <hr class="hr-panel-separator" />
                        <?php if (empty($activity_log)) { ?>
                        <p class="text-muted"><?= _l('case_no_activity'); ?></p>
                        <?php } else { ?>
                        <ul class="list-unstyled followup-activity-log">
                            <?php foreach (array_reverse($activity_log) as $entry) { ?>
                            <li class="tw-flex tw-items-start tw-mb-3">
                                <span class="tw-mr-3"><i class="<?= e(get_followup_activity_icon($entry)); ?>"></i></span>
                                <span>
                                    <strong><?= e(get_followup_activity_label($entry)); ?></strong>
                                    <br />
                                    <small class="text-muted">
                                        <?= e(_dt($entry->created_at)); ?><?php if ($entry->created_by) { ?> &middot; <?= e(get_staff_full_name($entry->created_by)); ?><?php } ?>
                                    </small>
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

<?php if ($can_act_on_case) { ?>
<!-- Log Call -->
<div id="modal-log-call" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form class="case-action-form" data-url="<?= admin_url('follow_up_management/log_activity/' . $case->id); ?>" data-activity-type="call">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title"><?= _l('case_action_log_call'); ?></h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="activity_type" value="call" />
                    <?php $outcome_options = []; foreach (get_followup_outcomes() as $outcome_key => $outcome_label) { $outcome_options[] = ['id' => $outcome_key, 'name' => $outcome_label]; } ?>
                    <?= render_select('outcome', $outcome_options, ['id', 'name'], 'case_field_outcome', ''); ?>
                    <?= render_textarea('notes', 'case_field_notes', ''); ?>
                    <?= render_datetime_input('due_date', 'case_field_next_follow_up_date', ''); ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?= _l('close'); ?></button>
                    <button type="submit" class="btn btn-primary"><?= _l('submit'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Schedule Meeting -->
<div id="modal-schedule-meeting" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form class="case-action-form" data-url="<?= admin_url('follow_up_management/log_activity/' . $case->id); ?>" data-activity-type="meeting">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title"><?= _l('case_action_schedule_meeting'); ?></h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="activity_type" value="meeting" />
                    <?= render_datetime_input('due_date', 'case_field_meeting_datetime', '', ['required' => 'required']); ?>
                    <?= render_textarea('notes', 'case_field_notes', ''); ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?= _l('close'); ?></button>
                    <button type="submit" class="btn btn-primary"><?= _l('submit'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Schedule Reminder -->
<div id="modal-schedule-reminder" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form class="case-action-form" data-url="<?= admin_url('follow_up_management/log_activity/' . $case->id); ?>" data-activity-type="reminder">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title"><?= _l('case_action_schedule_reminder'); ?></h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="activity_type" value="reminder" />
                    <?= render_datetime_input('due_date', 'case_field_reminder_datetime', '', ['required' => 'required']); ?>
                    <?= render_textarea('notes', 'case_field_notes', ''); ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?= _l('close'); ?></button>
                    <button type="submit" class="btn btn-primary"><?= _l('submit'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Note -->
<div id="modal-add-note" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form class="case-action-form" data-url="<?= admin_url('follow_up_management/log_activity/' . $case->id); ?>" data-activity-type="note">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title"><?= _l('case_action_add_note'); ?></h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="activity_type" value="note" />
                    <?= render_textarea('notes', 'case_field_notes', '', ['required' => 'required']); ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?= _l('close'); ?></button>
                    <button type="submit" class="btn btn-primary"><?= _l('submit'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Change Status -->
<div id="modal-change-status" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form class="case-action-form" data-url="<?= admin_url('follow_up_management/change_status/' . $case->id); ?>">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title"><?= _l('case_action_change_status'); ?></h4>
                </div>
                <div class="modal-body">
                    <?php $status_options = []; foreach (get_followup_statuses() as $status_key => $status_label) { $status_options[] = ['id' => $status_key, 'name' => $status_label]; } ?>
                    <?= render_select('status', $status_options, ['id', 'name'], 'case_field_new_status', $case->status, ['required' => 'required']); ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?= _l('close'); ?></button>
                    <button type="submit" class="btn btn-primary"><?= _l('submit'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Change Priority -->
<div id="modal-change-priority" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form class="case-action-form" data-url="<?= admin_url('follow_up_management/change_priority/' . $case->id); ?>">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title"><?= _l('case_action_change_priority'); ?></h4>
                </div>
                <div class="modal-body">
                    <?php $priority_options = []; foreach (get_followup_priorities() as $priority_key => $priority_label) { $priority_options[] = ['id' => $priority_key, 'name' => $priority_label]; } ?>
                    <?= render_select('priority', $priority_options, ['id', 'name'], 'case_field_new_priority', $case->priority, ['required' => 'required']); ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?= _l('close'); ?></button>
                    <button type="submit" class="btn btn-primary"><?= _l('submit'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reassign Staff -->
<div id="modal-reassign-staff" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form class="case-action-form" data-url="<?= admin_url('follow_up_management/reassign/' . $case->id); ?>">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title"><?= _l('case_action_reassign_staff'); ?></h4>
                </div>
                <div class="modal-body">
                    <?= render_select('staff_id', $members, ['staffid', ['firstname', 'lastname']], 'case_field_new_staff', $case->assigned_staff_id, ['required' => 'required']); ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?= _l('close'); ?></button>
                    <button type="submit" class="btn btn-primary"><?= _l('submit'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php } ?>

<?php init_tail(); ?>
<script>
    $(function() {
        // Issue 4 fix - root cause: these forms are plain hand-written
        // <form> tags (not CI's form_open(), which auto-injects a hidden
        // CSRF field), and this install has csrf_protection enabled
        // (application/config/app-config.php). jQuery's global
        // $.ajaxSetup({data: csrfData.formatted}) (application/helpers/
        // general_helper.php, csrf_jquery_token(), fired via init_head()
        // on every admin page) only auto-attaches the token when a call's
        // own `data` doesn't override it - a call passing a pre-serialized
        // STRING (like $form.serialize()) replaces the default outright
        // instead of merging into it, so the token silently never reached
        // the request. CI's CSRF filter then rejected the POST (typically
        // a 403 response), which produced no visible error because none of
        // these calls had a .fail() handler - matching exactly "clicking
        // submit does nothing." Perfex's own projects/view.php works around
        // this identical limitation by manually appending csrfData to its
        // request; withCsrf() below does the same for every action here.
        // No endpoint, no route, and no model logic changes - only the
        // request payload and adding visible error feedback.
        function withCsrf(serializedData) {
            if (typeof csrfData === 'undefined') {
                return serializedData;
            }
            var token = encodeURIComponent(csrfData.token_name) + '=' + encodeURIComponent(csrfData.hash);
            return serializedData ? serializedData + '&' + token : token;
        }

        function handleActionResponse(promise) {
            promise.done(function(response) {
                response = (typeof response === 'string') ? JSON.parse(response) : response;
                if (response.message) {
                    alert_float(response.alert_type, response.message);
                }
                setTimeout(function() {
                    window.location.reload();
                }, 800);
            }).fail(function() {
                alert_float('danger', '<?= _l('case_action_failed'); ?>');
            });
        }

        $('.case-action-form').on('submit', function(e) {
            e.preventDefault();
            var $form = $(this);
            handleActionResponse($.post($form.data('url'), withCsrf($form.serialize())));
        });

        // Row actions from My Cases / Today's Follow-ups link here with
        // ?action=... instead of duplicating these modals in the grid -
        // this is the only place that reads it.
        var queuedAction = new URLSearchParams(window.location.search).get('action');
        var actionModalMap = {
            log_call: '#modal-log-call',
            schedule_meeting: '#modal-schedule-meeting',
            schedule_reminder: '#modal-schedule-reminder',
            add_note: '#modal-add-note'
        };
        if (queuedAction && actionModalMap[queuedAction] && $(actionModalMap[queuedAction]).length) {
            $(actionModalMap[queuedAction]).modal('show');
        } else if (queuedAction === 'close_case' && $('#btn-close-case').length) {
            $('#btn-close-case').trigger('click');
        }

        $('#btn-close-case').on('click', function(e) {
            e.preventDefault();
            if (!confirm('<?= _l('case_confirm_close_case'); ?>')) {
                return;
            }
            handleActionResponse($.post('<?= admin_url('follow_up_management/close_case/' . $case->id); ?>', withCsrf('')));
        });

        $('#btn-reopen-case').on('click', function(e) {
            e.preventDefault();
            if (!confirm('<?= _l('case_confirm_reopen_case'); ?>')) {
                return;
            }
            handleActionResponse($.post('<?= admin_url('follow_up_management/reopen_case/' . $case->id); ?>', withCsrf('')));
        });
    });
</script>
</body>
</html>
