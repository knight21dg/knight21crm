<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php if (empty($projects)) { ?>
<p class="text-muted"><?= _l('dm_portal_no_projects'); ?></p>
<?php } else { ?>
<div class="dm-project-list">
    <?php foreach ($projects as $project) {
        $status = get_project_status_by_id($project['status']);
        $pid    = (int) $project['id'];
    ?>
    <div class="panel_s tw-mb-2">
        <div class="panel-body dm-clickable-row" onclick="dm_toggle_project_detail(<?= $pid; ?>)">
            <div class="row">
                <div class="col-sm-3">
                    <strong><?= e($project['name']); ?></strong>
                    <div class="text-muted tw-text-xs"><?= $project['company'] ? e($project['company']) : '-'; ?></div>
                </div>
                <div class="col-sm-2">
                    <div class="text-muted tw-text-xs"><?= _l('dm_portal_col_assigned_date'); ?></div>
                    <?= $project['start_date'] ? e(_d($project['start_date'])) : '-'; ?>
                </div>
                <div class="col-sm-2">
                    <div class="text-muted tw-text-xs"><?= _l('dm_portal_column_deadline'); ?></div>
                    <?= $project['deadline'] ? e(_d($project['deadline'])) : '-'; ?>
                </div>
                <div class="col-sm-2">
                    <span class="label" style="color:<?= $status['color']; ?>;border:1px solid <?= $status['color']; ?>;"><?= e($status['name']); ?></span>
                </div>
                <div class="col-sm-2">
                    <div class="progress" style="margin-bottom:0;"><div class="progress-bar" role="progressbar" style="width:<?= (int) $project['progress']; ?>%;"><?= (int) $project['progress']; ?>%</div></div>
                </div>
                <div class="col-sm-1 text-right">
                    <i class="fa-solid fa-chevron-down" id="dm-project-chevron-<?= $pid; ?>"></i>
                </div>
            </div>
            <?php if ($project['assigned_work']) { ?>
            <div class="tw-mt-2 tw-text-sm text-muted"><?= e($project['assigned_work']); ?></div>
            <?php } ?>
        </div>
        <div id="dm-project-detail-<?= $pid; ?>" class="dm-project-detail" style="display:none;padding:15px;border-top:1px solid #eee;">
            <div class="text-center tw-py-4"><i class="fa-solid fa-spinner fa-spin"></i></div>
        </div>
    </div>
    <?php } ?>
</div>
<?php } ?>
