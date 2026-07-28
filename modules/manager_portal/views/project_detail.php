<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<?php
    $project = $detail['project'];
?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="tw-flex tw-items-center tw-justify-between tw-flex-wrap tw-gap-2">
                            <h4 class="no-margin"><?= e($project->name); ?></h4>
                            <a href="<?= admin_url('manager_portal/projects'); ?>" class="btn btn-default"><i class="fa-solid fa-arrow-left"></i> <?= _l('manager_portal_back_to_projects'); ?></a>
                        </div>
                        <p class="text-muted tw-mb-0">
                            <?= $project->client_data ? e($project->client_data->company) : '-'; ?>
                        </p>
                        <hr class="hr-panel-separator" />

                        <div class="row tw-mb-4">
                            <div class="col-md-3">
                                <strong><?= _l('manager_portal_projects_column_progress'); ?></strong>
                                <div class="progress" style="height:8px;margin-top:6px;">
                                    <div class="progress-bar no-percent-text not-dynamic" role="progressbar" aria-valuenow="<?= (int) $project->progress; ?>" aria-valuemin="0" aria-valuemax="100" data-percent="<?= (int) $project->progress; ?>" style="width:<?= (int) $project->progress; ?>%"></div>
                                </div>
                                <span class="text-muted"><?= (int) $project->progress; ?>%</span>
                            </div>
                            <div class="col-md-3">
                                <strong><?= _l('manager_portal_projects_column_start_date'); ?></strong>
                                <p><?= $project->start_date ? e(_d($project->start_date)) : '-'; ?></p>
                            </div>
                            <div class="col-md-3">
                                <strong><?= _l('manager_portal_projects_column_deadline'); ?></strong>
                                <p><?= $project->deadline ? e(_d($project->deadline)) : '-'; ?></p>
                            </div>
                            <div class="col-md-3">
                                <strong><?= _l('manager_portal_projects_column_status'); ?></strong>
                                <p><?= e(get_project_status_by_id($project->status)['name'] ?? '-'); ?></p>
                            </div>
                        </div>

                        <ul class="nav nav-tabs nav-tabs-horizontal" role="tablist">
                            <li class="active"><a href="#tab_overview" data-toggle="tab"><?= _l('manager_portal_tab_overview'); ?></a></li>
                            <li><a href="#tab_members" data-toggle="tab"><?= _l('manager_portal_tab_members'); ?> (<?= count($detail['members']); ?>)</a></li>
                            <li><a href="#tab_milestones" data-toggle="tab"><?= _l('manager_portal_tab_milestones'); ?> (<?= count($detail['milestones']); ?>)</a></li>
                            <li><a href="#tab_tasks" data-toggle="tab"><?= _l('manager_portal_tab_tasks'); ?> (<?= count($detail['tasks']); ?>)</a></li>
                            <li><a href="#tab_files" data-toggle="tab"><?= _l('manager_portal_tab_files'); ?> (<?= count($detail['files']); ?>)</a></li>
                            <li><a href="#tab_discussions" data-toggle="tab"><?= _l('manager_portal_tab_discussions'); ?> (<?= count($detail['discussions']); ?>)</a></li>
                            <li><a href="#tab_activity" data-toggle="tab"><?= _l('manager_portal_tab_activity'); ?></a></li>
                        </ul>
                        <div class="tab-content tw-pt-4">
                            <div class="tab-pane active" id="tab_overview">
                                <?= $project->description ? $project->description : '<span class="text-muted">' . _l('manager_portal_no_description') . '</span>'; ?>
                            </div>

                            <div class="tab-pane" id="tab_members">
                                <?php if (empty($detail['members'])) { ?>
                                <span class="text-muted"><?= _l('manager_portal_no_members'); ?></span>
                                <?php } else { ?>
                                <ul class="list-unstyled">
                                    <?php foreach ($detail['members'] as $member) { ?>
                                    <li class="tw-mb-2 tw-flex tw-items-center tw-gap-2">
                                        <?= staff_profile_image($member['staffid'], ['tw-rounded-full'], 'tiny'); ?>
                                        <?= e(trim($member['firstname'] . ' ' . $member['lastname'])); ?>
                                    </li>
                                    <?php } ?>
                                </ul>
                                <?php } ?>
                            </div>

                            <div class="tab-pane" id="tab_milestones">
                                <?php if (empty($detail['milestones'])) { ?>
                                <span class="text-muted"><?= _l('manager_portal_no_milestones'); ?></span>
                                <?php } else { ?>
                                <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th><?= _l('manager_portal_milestone_name'); ?></th>
                                            <th><?= _l('manager_portal_milestone_due_date'); ?></th>
                                            <th><?= _l('manager_portal_milestone_tasks'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($detail['milestones'] as $milestone) { ?>
                                        <tr>
                                            <td><?= e($milestone['name']); ?></td>
                                            <td><?= $milestone['due_date'] ? e(_d($milestone['due_date'])) : '-'; ?></td>
                                            <td><?= (int) $milestone['total_finished_tasks']; ?> / <?= (int) $milestone['total_tasks']; ?></td>
                                        </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                                </div>
                                <?php } ?>
                            </div>

                            <div class="tab-pane" id="tab_tasks">
                                <?php if (empty($detail['tasks'])) { ?>
                                <span class="text-muted"><?= _l('manager_portal_no_tasks'); ?></span>
                                <?php } else { ?>
                                <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th><?= _l('manager_portal_tasks_column_task'); ?></th>
                                            <th><?= _l('manager_portal_tasks_column_status'); ?></th>
                                            <th><?= _l('manager_portal_tasks_column_priority'); ?></th>
                                            <th><?= _l('manager_portal_tasks_column_due_date'); ?></th>
                                            <th><?= _l('manager_portal_projects_column_actions'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($detail['tasks'] as $task) { ?>
                                        <tr>
                                            <td><?= e($task['name']); ?></td>
                                            <td><?= format_task_status($task['status'], true); ?></td>
                                            <td><span style="color:<?= task_priority_color($task['priority']); ?>"><?= e(task_priority($task['priority'])); ?></span></td>
                                            <td><?= $task['duedate'] ? e(_d($task['duedate'])) : '-'; ?></td>
                                            <td><a href="<?= admin_url('manager_portal/task/' . $task['id']); ?>" class="btn btn-default btn-icon"><i class="fa-regular fa-eye"></i></a></td>
                                        </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                                </div>
                                <?php } ?>
                            </div>

                            <div class="tab-pane" id="tab_files">
                                <?php if (empty($detail['files'])) { ?>
                                <span class="text-muted"><?= _l('manager_portal_no_files'); ?></span>
                                <?php } else { ?>
                                <ul class="list-unstyled">
                                    <?php foreach ($detail['files'] as $file) { ?>
                                    <li class="tw-mb-2">
                                        <i class="fa-regular fa-file"></i>
                                        <?= e($file['subject'] ? $file['subject'] : $file['file_name']); ?>
                                        <span class="text-muted tw-text-sm">- <?= e(_dt($file['dateadded'])); ?></span>
                                    </li>
                                    <?php } ?>
                                </ul>
                                <?php } ?>
                            </div>

                            <div class="tab-pane" id="tab_discussions">
                                <?php if (empty($detail['discussions'])) { ?>
                                <span class="text-muted"><?= _l('manager_portal_no_discussions'); ?></span>
                                <?php } else { ?>
                                <ul class="list-unstyled">
                                    <?php foreach ($detail['discussions'] as $discussion) { ?>
                                    <li class="tw-mb-3">
                                        <div class="tw-font-medium"><?= e($discussion['subject']); ?></div>
                                        <div class="text-muted tw-text-sm"><?= (int) $discussion['total_comments']; ?> <?= _l('manager_portal_comments'); ?></div>
                                    </li>
                                    <?php } ?>
                                </ul>
                                <?php } ?>
                            </div>

                            <div class="tab-pane" id="tab_activity">
                                <?php if (empty($detail['activity'])) { ?>
                                <span class="text-muted"><?= _l('manager_portal_no_activity'); ?></span>
                                <?php } else { ?>
                                <div class="activity-feed">
                                    <?php foreach ($detail['activity'] as $activity) { ?>
                                    <div class="feed-item">
                                        <div class="date"><span class="text-has-action" data-toggle="tooltip" data-title="<?= e(_dt($activity['dateadded'])); ?>"><?= e(time_ago($activity['dateadded'])); ?></span></div>
                                        <div class="text">
                                            <p class="tw-mb-0 tw-mt-2"><?= e($activity['fullname']); ?> - <b><?= e($activity['description']); ?></b></p>
                                            <p class="tw-mb-0 text-muted mleft30 tw-mt-1"><?= $activity['additional_data']; ?></p>
                                        </div>
                                    </div>
                                    <?php } ?>
                                </div>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
</body>
</html>
