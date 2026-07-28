<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="tw-flex tw-items-center tw-justify-between">
                            <h4 class="no-margin"><?= e($title); ?></h4>
                            <a href="#" class="text-muted" onclick="event.preventDefault(); mark_all_notifications_as_read_inline(this);">
                                <?= _l('mark_all_as_read'); ?>
                            </a>
                        </div>
                        <hr class="hr-panel-separator" />

                        <?php if (empty($notifications)) { ?>
                        <p class="text-muted"><?= _l('notification_no_notifications'); ?></p>
                        <?php } else { ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th></th>
                                        <th><?= _l('notification_column_title'); ?></th>
                                        <th><?= _l('notification_column_description'); ?></th>
                                        <th><?= _l('notification_column_related_lead'); ?></th>
                                        <th><?= _l('notification_column_related_case'); ?></th>
                                        <th><?= _l('case_field_assigned_staff'); ?></th>
                                        <th><?= _l('case_field_created_date'); ?></th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($notifications as $notification) {
                                        $case_id = get_followup_notification_case_id($notification->link);
                                        $case    = $case_id ? $this->follow_ups_model->get($case_id) : null;
                                        $rel_name = '';
                                        if ($case) {
                                            $rel_data     = get_relation_data($case->rel_type, $case->rel_id);
                                            $rel_values   = get_relation_values($rel_data, $case->rel_type);
                                            $rel_name     = $rel_values['name'];
                                        }
                                        $additional_data = $notification->additional_data ? unserialize($notification->additional_data) : [];
                                        $description      = _l($notification->description, $additional_data);
                                        $is_unread        = $notification->isread_inline == 0;
                                        ?>
                                    <tr data-notification-id="<?= e($notification->id); ?>" class="<?= $is_unread ? 'tw-font-semibold' : ''; ?>">
                                        <td><i class="<?= e(get_followup_notification_icon($notification->description)); ?>"></i></td>
                                        <td>
                                            <a href="<?= admin_url($notification->link); ?>"><?= e(get_followup_notification_title($notification->description)); ?></a>
                                        </td>
                                        <td><?= $description; ?></td>
                                        <td><?= $rel_name ? e($rel_name) : '-'; ?></td>
                                        <td><?php if ($case_id) { ?><a href="<?= admin_url('follow_up_management/view/' . $case_id); ?>">#<?= (int) $case_id; ?></a><?php } else { ?>-<?php } ?></td>
                                        <td><?= $notification->touserid ? e(get_staff_full_name($notification->touserid)) : '-'; ?></td>
                                        <td><small class="text-muted"><?= e(_dt($notification->date)); ?></small></td>
                                        <td>
                                            <?php if ($is_unread) { ?>
                                            <a href="#" class="text-muted" title="<?= _l('mark_as_read'); ?>"
                                                onclick="event.preventDefault(); set_notification_read_inline(<?= e($notification->id); ?>); $(this).closest('tr').removeClass('tw-font-semibold'); $(this).remove();">
                                                <i class="fa-regular fa-circle"></i>
                                            </a>
                                            <?php } else { ?>
                                            <i class="fa-solid fa-check text-muted" title="<?= _l('mark_as_read'); ?>"></i>
                                            <?php } ?>
                                        </td>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
</body>
</html>
