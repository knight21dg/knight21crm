<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<?php
    $row        = $detail['row'];
    $attendance = $detail['attendance'];
?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-8">
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="tw-flex tw-items-center tw-justify-between tw-flex-wrap tw-gap-2">
                            <h4 class="no-margin"><?= e(_d($row->work_date)); ?></h4>
                            <a href="<?= admin_url('manager_portal/daily_work_updates'); ?>" class="btn btn-default"><i class="fa-solid fa-arrow-left"></i> <?= _l('manager_portal_dwu_back'); ?></a>
                        </div>
                        <hr class="hr-panel-separator" />

                        <h5><?= _l('manager_portal_dwu_employee_information'); ?></h5>
                        <div class="row tw-mb-4">
                            <div class="col-md-4">
                                <strong><?= _l('manager_portal_dwu_column_employee'); ?></strong>
                                <p><?= e(trim($row->firstname . ' ' . $row->lastname)); ?></p>
                            </div>
                            <div class="col-md-4">
                                <strong><?= _l('manager_portal_dwu_column_department'); ?></strong>
                                <p><?= $row->departments ? e($row->departments) : '-'; ?></p>
                            </div>
                            <div class="col-md-4">
                                <strong><?= _l('manager_portal_dwu_column_submitted_time'); ?></strong>
                                <p><?= $row->created_at ? e(_dt($row->created_at)) : '-'; ?></p>
                            </div>
                        </div>

                        <h5><?= _l('manager_portal_dwu_working_hours'); ?></h5>
                        <div class="row tw-mb-4">
                            <div class="col-md-4">
                                <strong><?= _l('manager_portal_dwu_column_checkin'); ?></strong>
                                <p><?= ($attendance && $attendance->login_time) ? e(_dt($attendance->login_time)) : '-'; ?></p>
                            </div>
                            <div class="col-md-4">
                                <strong><?= _l('manager_portal_dwu_column_checkout'); ?></strong>
                                <p><?= ($attendance && $attendance->logout_time) ? e(_dt($attendance->logout_time)) : '-'; ?></p>
                            </div>
                        </div>

                        <?php if (!empty($row->project_name) || !empty($row->task_name)) { ?>
                        <p class="text-muted">
                            <?= $row->project_name ? e($row->project_name) : ''; ?>
                            <?= $row->task_name ? ' - ' . e($row->task_name) : ''; ?>
                        </p>
                        <?php } ?>

                        <h5><?= _l('manager_portal_dwu_completed_tasks'); ?></h5>
                        <div class="tw-mb-4"><?= nl2br(e($row->today_work)); ?></div>

                        <h5><?= _l('manager_portal_dwu_tomorrow_plan'); ?></h5>
                        <div class="tw-mb-4"><?= nl2br(e($row->tomorrow_plan)); ?></div>

                        <h5><?= _l('manager_portal_dwu_issues'); ?></h5>
                        <div class="tw-mb-4"><?= !empty($row->issues) ? nl2br(e($row->issues)) : '<span class="text-muted">' . _l('manager_portal_dwu_no_issues') . '</span>'; ?></div>

                        <h5><?= _l('manager_portal_dwu_attachments'); ?> (<?= count($detail['attachments']); ?>)</h5>
                        <?php if (empty($detail['attachments'])) { ?>
                        <p class="text-muted"><?= _l('manager_portal_dwu_no_attachments'); ?></p>
                        <?php } else { ?>
                        <ul class="list-unstyled">
                            <?php foreach ($detail['attachments'] as $attachment) { ?>
                            <li><i class="fa-regular fa-paperclip"></i> <?= e($attachment['file_name']); ?></li>
                            <?php } ?>
                        </ul>
                        <?php } ?>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="panel_s dwu-row" data-dwu-id="<?= (int) $row->id; ?>">
                    <div class="panel-body">
                        <h5 class="no-margin"><?= _l('manager_portal_dwu_review'); ?></h5>
                        <div class="tw-mt-2 tw-mb-3">
                            <span class="dwu-status-badge"><?= manager_portal_dwu_status_badge($row->review_status); ?></span>
                        </div>

                        <?php if ($row->reviewed_by) { ?>
                        <p class="text-muted tw-text-sm">
                            <?= _l('manager_portal_dwu_column_reviewed_by'); ?>: <?= e(trim(($row->reviewer_firstname ?? '') . ' ' . ($row->reviewer_lastname ?? ''))); ?>
                            <?php if ($row->reviewed_at) { ?>
                            - <?= e(_dt($row->reviewed_at)); ?>
                            <?php } ?>
                        </p>
                        <?php } else { ?>
                        <p class="text-muted tw-text-sm"><?= _l('manager_portal_dwu_not_reviewed_yet'); ?></p>
                        <?php } ?>

                        <div class="form-group">
                            <label><?= _l('manager_portal_dwu_review_comment_label'); ?></label>
                            <textarea class="form-control dwu-comment-input" rows="3" placeholder="<?= e(_l('manager_portal_dwu_review_comment_placeholder')); ?>"><?= e($row->review_comment ?? ''); ?></textarea>
                        </div>
                        <button type="button" class="btn btn-success btn-sm dwu-approve-btn"><i class="fa-solid fa-check"></i> <?= _l('manager_portal_dwu_approve'); ?></button>
                        <button type="button" class="btn btn-warning btn-sm dwu-revision-btn"><i class="fa-solid fa-rotate-left"></i> <?= _l('manager_portal_dwu_needs_revision_action'); ?></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script>
    function dwuSubmitReview($row, status) {
        var id = $row.data('dwu-id');
        var comment = $row.find('.dwu-comment-input').val();

        $.post(admin_url + 'manager_portal/dwu_review', {
            id: id,
            status: status,
            comment: comment
        }, function(response) {
            if (response.success) {
                alert_float('success', response.message);
                $row.find('.dwu-status-badge').html(
                    status === 'approved' ?
                    '<?= addslashes(manager_portal_dwu_status_badge('approved')); ?>' :
                    '<?= addslashes(manager_portal_dwu_status_badge('needs_revision')); ?>'
                );
            } else {
                alert_float('danger', response.message);
            }
        }, 'json');
    }

    $(function() {
        $(document).on('click', '.dwu-approve-btn', function() {
            dwuSubmitReview($(this).closest('.dwu-row'), 'approved');
        });

        $(document).on('click', '.dwu-revision-btn', function() {
            dwuSubmitReview($(this).closest('.dwu-row'), 'needs_revision');
        });
    });
</script>
</body>
</html>
