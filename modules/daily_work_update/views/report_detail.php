<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="row tw-mb-3">
    <div class="col-sm-4"><span class="text-muted"><?= _l('daily_work_update_report_col_employee'); ?>:</span> <?= e(trim($row->firstname . ' ' . $row->lastname)); ?></div>
    <div class="col-sm-4"><span class="text-muted"><?= _l('daily_work_update_report_col_department'); ?>:</span> <?= $row->departments ? e($row->departments) : '-'; ?></div>
    <div class="col-sm-4"><span class="text-muted"><?= _l('daily_work_update_report_col_date'); ?>:</span> <?= e(_d($row->work_date)); ?></div>
</div>

<div class="row tw-mb-3">
    <div class="col-sm-6"><span class="text-muted"><?= _l('daily_work_update_report_col_project'); ?>:</span> <?= $row->project_name ? e($row->project_name) : '-'; ?></div>
    <div class="col-sm-6"><span class="text-muted"><?= _l('daily_work_update_report_col_task'); ?>:</span> <?= $row->task_name ? e($row->task_name) : '-'; ?></div>
</div>

<hr class="hr-panel-separator" />

<div class="tw-mb-3">
    <h5 class="no-margin tw-mb-2"><?= _l('daily_work_update_report_col_today_work'); ?></h5>
    <div><?= nl2br(e($row->today_work)); ?></div>
</div>

<div class="tw-mb-3">
    <h5 class="no-margin tw-mb-2"><?= _l('daily_work_update_report_col_tomorrow_plan'); ?></h5>
    <div><?= nl2br(e($row->tomorrow_plan)); ?></div>
</div>

<div class="tw-mb-3">
    <h5 class="no-margin tw-mb-2"><?= _l('daily_work_update_report_col_issues'); ?></h5>
    <?php if ($row->issues) { ?>
    <div><?= nl2br(e($row->issues)); ?></div>
    <?php } else { ?>
    <div class="text-muted"><?= _l('daily_work_update_report_no_issues'); ?></div>
    <?php } ?>
</div>

<hr class="hr-panel-separator" />

<div class="tw-mb-3">
    <h5 class="no-margin tw-mb-2"><?= _l('daily_work_update_report_col_attachments'); ?></h5>
    <?php if (empty($attachments)) { ?>
    <p class="text-muted"><?= _l('daily_work_update_no_files'); ?></p>
    <?php } else { ?>
    <ul class="list-unstyled">
        <?php foreach ($attachments as $file) { ?>
        <li class="tw-mb-1"><i class="fa-regular fa-file tw-text-gray-400"></i> <?= e($file['file_name']); ?></li>
        <?php } ?>
    </ul>
    <?php } ?>
</div>

<hr class="hr-panel-separator" />

<div class="row">
    <div class="col-sm-6"><span class="text-muted"><?= _l('daily_work_update_report_col_submitted_time'); ?>:</span> <?= e(_dt($row->created_at)); ?></div>
    <div class="col-sm-6"><span class="text-muted"><?= _l('daily_work_update_report_col_updated_time'); ?>:</span> <?= $row->updated_at ? e(_dt($row->updated_at)) : '-'; ?></div>
</div>
