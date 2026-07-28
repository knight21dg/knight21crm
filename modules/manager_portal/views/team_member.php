<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="tw-mb-4">
            <a href="<?= admin_url('manager_portal/team'); ?>" class="tw-text-neutral-500 tw-text-sm">
                <i class="fa fa-angle-left"></i> <?= _l('manager_portal_team'); ?>
            </a>
        </div>

        <!-- Basic Information -->
        <div class="panel_s">
            <div class="panel-body">
                <div class="tw-flex tw-items-center tw-gap-4 tw-mb-4">
                    <?= staff_profile_image($staff->staffid, ['tw-rounded-full'], 'thumb'); ?>
                    <div>
                        <h4 class="no-margin"><?= e($staff->firstname . ' ' . $staff->lastname); ?></h4>
                        <span class="text-muted"><?= e($staff->email); ?></span>
                        <?php if ($staff->active) { ?>
                        <span class="label label-success"><?= _l('active'); ?></span>
                        <?php } else { ?>
                        <span class="label label-danger"><?= _l('inactive'); ?></span>
                        <?php } ?>
                    </div>
                </div>
                <hr class="hr-panel-separator" />
                <div class="row">
                    <div class="col-md-3 col-sm-6">
                        <label class="text-muted"><?= _l('manager_portal_team_member_phone'); ?></label>
                        <div><?= !empty($staff->phonenumber) ? e($staff->phonenumber) : '-'; ?></div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <label class="text-muted"><?= _l('manager_portal_team_member_position'); ?></label>
                        <div><?= !empty($bundle['designation']) ? e($bundle['designation']) : '-'; ?></div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <label class="text-muted"><?= _l('manager_portal_team_column_department'); ?></label>
                        <div>
                            <?php if (empty($bundle['departments'])) { ?>
                            -
                            <?php } else { ?>
                            <?= e(implode(', ', $bundle['departments'])); ?>
                            <?php } ?>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <label class="text-muted"><?= _l('manager_portal_team_member_joined'); ?></label>
                        <div><?= !empty($staff->datecreated) ? _d($staff->datecreated) : '-'; ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <!-- Attendance Summary -->
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin"><?= _l('manager_portal_profile_attendance_summary'); ?></h4>
                        <hr class="hr-panel-separator" />
                        <?php $today_status = $bundle['attendance_today'] ? $bundle['attendance_today']->attendance_status : 'Absent'; ?>
                        <p>
                            <?= _l('manager_portal_profile_today'); ?>:
                            <span class="label" style="color:<?= get_attendance_status_color($today_status); ?>;border:1px solid <?= adjust_hex_brightness(get_attendance_status_color($today_status), 0.4); ?>;background:<?= adjust_hex_brightness(get_attendance_status_color($today_status), 0.04); ?>;">
                                <?= e($today_status); ?>
                            </span>
                        </p>
                        <div class="row">
                            <div class="col-xs-4">
                                <div class="tw-text-xl tw-font-semibold"><?= (int) $bundle['attendance_month']['attendance_rate']; ?>%</div>
                                <div class="text-muted"><?= _l('manager_portal_profile_attendance_rate'); ?></div>
                            </div>
                            <div class="col-xs-4">
                                <div class="tw-text-xl tw-font-semibold"><?= (int) $bundle['attendance_month']['counts']['Present']; ?></div>
                                <div class="text-muted"><?= _l('manager_portal_profile_present_days'); ?></div>
                            </div>
                            <div class="col-xs-4">
                                <div class="tw-text-xl tw-font-semibold"><?= (int) $bundle['attendance_month']['counts']['Absent']; ?></div>
                                <div class="text-muted"><?= _l('manager_portal_profile_absent_days'); ?></div>
                            </div>
                        </div>
                        <div class="tw-mt-3">
                            <a href="<?= admin_url('staff_attendance/manage?employee=' . $staff->staffid); ?>"><?= _l('manager_portal_profile_view_full_attendance'); ?> <i class="fa-solid fa-arrow-right"></i></a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <!-- Performance Summary -->
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin"><?= _l('manager_portal_profile_performance_summary'); ?></h4>
                        <hr class="hr-panel-separator" />
                        <p><?= manager_portal_performance_badge($bundle['performance_label'], $bundle['productivity']); ?></p>
                        <div class="row">
                            <div class="col-xs-6">
                                <div class="tw-text-xl tw-font-semibold"><?= count($bundle['assigned_tasks']); ?></div>
                                <div class="text-muted"><?= _l('manager_portal_profile_total_tasks'); ?></div>
                            </div>
                            <div class="col-xs-6">
                                <div class="tw-text-xl tw-font-semibold"><?= count($bundle['assigned_projects']); ?></div>
                                <div class="text-muted"><?= _l('manager_portal_profile_total_projects'); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <!-- Assigned Projects -->
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin"><?= _l('manager_portal_profile_assigned_projects'); ?></h4>
                        <hr class="hr-panel-separator" />
                        <?php if (empty($bundle['assigned_projects'])) { ?>
                        <span class="text-muted"><?= _l('manager_portal_nothing_to_show'); ?></span>
                        <?php } else { ?>
                        <ul class="list-unstyled">
                            <?php foreach ($bundle['assigned_projects'] as $project) { ?>
                            <li class="tw-mb-2">
                                <?php // Not a link yet - native admin/projects/* is out of this
                                // role's reach until Phase 3 (Project & Task Monitoring) adds
                                // this module's own Project Workspace, per the approved roadmap. ?>
                                <?= e($project['name']); ?>
                                <div class="text-muted" style="font-size:12px;">
                                    <?= $project['deadline'] ? e(_d($project['deadline'])) : '-'; ?> &middot; <?= (int) $project['progress']; ?>%
                                </div>
                            </li>
                            <?php } ?>
                        </ul>
                        <?php } ?>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <!-- Assigned Tasks -->
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin"><?= _l('manager_portal_profile_assigned_tasks'); ?></h4>
                        <hr class="hr-panel-separator" />
                        <?php if (empty($bundle['assigned_tasks'])) { ?>
                        <span class="text-muted"><?= _l('manager_portal_nothing_to_show'); ?></span>
                        <?php } else { ?>
                        <ul class="list-unstyled">
                            <?php foreach ($bundle['assigned_tasks'] as $task) { ?>
                            <li class="tw-mb-2">
                                <?= e($task['name']); ?>
                                <?= format_task_status($task['status']); ?>
                                <div class="text-muted" style="font-size:12px;"><?= $task['duedate'] ? e(_d($task['duedate'])) : '-'; ?></div>
                            </li>
                            <?php } ?>
                        </ul>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Daily Work Updates -->
        <div class="panel_s">
            <div class="panel-body">
                <h4 class="no-margin"><?= _l('manager_portal_profile_daily_work_updates'); ?></h4>
                <hr class="hr-panel-separator" />
                <?php if (empty($bundle['daily_work_updates'])) { ?>
                <span class="text-muted"><?= _l('manager_portal_nothing_to_show'); ?></span>
                <?php } else { ?>
                <?php foreach ($bundle['daily_work_updates'] as $update) { ?>
                <div class="dwu-row tw-mb-3" style="border:1px solid #eceff1;border-radius:6px;padding:14px;" data-dwu-id="<?= (int) $update['id']; ?>">
                    <div class="tw-flex tw-items-center tw-justify-between">
                        <strong><?= e(_d($update['work_date'])); ?></strong>
                        <span class="dwu-status-badge"><?= manager_portal_dwu_status_badge($update['review_status']); ?></span>
                    </div>
                    <?php if (!empty($update['project_name'])) { ?>
                    <div class="text-muted" style="font-size:12px;"><?= e($update['project_name']); ?></div>
                    <?php } ?>
                    <p class="tw-mb-1"><strong><?= _l('manager_portal_profile_dwu_today_work'); ?>:</strong> <?= nl2br(e($update['today_work'])); ?></p>
                    <p class="tw-mb-1"><strong><?= _l('manager_portal_profile_dwu_tomorrow_plan'); ?>:</strong> <?= nl2br(e($update['tomorrow_plan'])); ?></p>
                    <?php if (!empty($update['issues'])) { ?>
                    <p class="tw-mb-1"><strong><?= _l('manager_portal_profile_dwu_issues'); ?>:</strong> <?= nl2br(e($update['issues'])); ?></p>
                    <?php } ?>
                    <?php if (!empty($update['review_comment'])) { ?>
                    <p class="tw-mb-1 text-muted"><strong><?= _l('manager_portal_profile_dwu_review_comment'); ?>:</strong> <?= nl2br(e($update['review_comment'])); ?></p>
                    <?php } ?>

                    <div class="dwu-review-actions tw-mt-2">
                        <textarea class="form-control dwu-comment-input" rows="2" placeholder="<?= e(_l('manager_portal_profile_dwu_comment_placeholder')); ?>"><?= e($update['review_comment'] ?? ''); ?></textarea>
                        <div class="tw-mt-2">
                            <button type="button" class="btn btn-success btn-sm dwu-approve-btn"><i class="fa-solid fa-check"></i> <?= _l('manager_portal_profile_dwu_approve'); ?></button>
                            <button type="button" class="btn btn-warning btn-sm dwu-revision-btn"><i class="fa-solid fa-rotate-left"></i> <?= _l('manager_portal_profile_dwu_needs_revision'); ?></button>
                        </div>
                    </div>
                </div>
                <?php } ?>
                <?php } ?>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="panel_s">
            <div class="panel-body">
                <h4 class="no-margin"><?= _l('manager_portal_recent_activity'); ?></h4>
                <hr class="hr-panel-separator" />
                <?php if (empty($bundle['recent_activity'])) { ?>
                <span class="text-muted"><?= _l('manager_portal_no_recent_activity'); ?></span>
                <?php } else { ?>
                <ul class="list-unstyled">
                    <?php foreach ($bundle['recent_activity'] as $activity) { ?>
                    <li class="tw-mb-2">
                        <?= e($activity['project_name']); ?>
                        <div class="text-muted" style="font-size:12px;"><?= e(_dt($activity['date'])); ?></div>
                    </li>
                    <?php } ?>
                </ul>
                <?php } ?>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script>
$(function() {
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
