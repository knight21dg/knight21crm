<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<a href="<?= admin_url('follow_up_management/my_follow_ups'); ?>" class="mbot20 inline-block full-width"><?= _l('home_widget_view_all'); ?></a>
<div class="clearfix"></div>
<?php if (empty($leads)) { ?>
<p class="text-muted"><?= _l('followup_widget_no_leads'); ?></p>
<?php } else { ?>
<ul class="list-unstyled">
    <?php foreach ($leads as $lead) { ?>
    <li class="tw-flex tw-items-center tw-justify-between tw-mb-3 tw-pb-3" style="border-bottom:1px solid #eee;">
        <div>
            <strong><?= e($lead->name); ?></strong>
            <?php if (!empty($lead->company)) { ?>
            <br /><small class="text-muted"><?= e($lead->company); ?></small>
            <?php } ?>
            <br />
            <?= format_followup_next_date_badge($lead->next_follow_up_date); ?>
        </div>
        <div class="tw-flex tw-items-center tw-gap-1">
            <?php if (!empty($lead->phonenumber)) { $dialable = follow_up_sanitize_phone_for_dialing($lead->phonenumber); ?>
            <a href="tel:<?= e($dialable); ?>" class="btn btn-default btn-icon btn-sm" title="<?= _l('followup_workspace_action_call'); ?>"><i class="fa-solid fa-phone"></i></a>
            <?php } ?>
            <?php if (!empty($lead->task_id)) { ?>
            <button type="button" class="btn btn-primary btn-sm" onclick="init_task_modal(<?= (int) $lead->task_id; ?>); return false;"><?= _l('followup_workspace_open'); ?></button>
            <?php } elseif (!empty($lead->case_id)) { ?>
            <a href="<?= admin_url('follow_up_management/view/' . $lead->case_id); ?>" class="btn btn-default btn-sm"><?= _l('view'); ?></a>
            <?php } ?>
        </div>
    </li>
    <?php } ?>
</ul>
<?php } ?>
