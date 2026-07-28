<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin"><?= e($title); ?></h4>
                        <hr class="hr-panel-separator" />

                        <form method="get" class="tw-mb-4">
                            <div class="row">
                                <div class="col-md-2 form-group">
                                    <label><?= _l('manager_portal_filter_department'); ?></label>
                                    <select name="department_id" class="selectpicker" data-width="100%" data-live-search="true">
                                        <option value=""><?= _l('manager_portal_filter_all'); ?></option>
                                        <?php foreach ($options['departments'] as $department) { ?>
                                        <option value="<?= (int) $department['id']; ?>" <?= ((string) $filters['department_id'] === (string) $department['id']) ? 'selected' : ''; ?>><?= e($department['name']); ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <div class="col-md-2 form-group">
                                    <label><?= _l('manager_portal_filter_employee'); ?></label>
                                    <select name="employee_id" class="selectpicker" data-width="100%" data-live-search="true">
                                        <option value=""><?= _l('manager_portal_filter_all'); ?></option>
                                        <?php foreach ($options['employees'] as $employee) { ?>
                                        <option value="<?= (int) $employee['staffid']; ?>" <?= ((string) $filters['employee_id'] === (string) $employee['staffid']) ? 'selected' : ''; ?>><?= e(trim($employee['firstname'] . ' ' . $employee['lastname'])); ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <div class="col-md-2 form-group">
                                    <label><?= _l('manager_portal_filter_status'); ?></label>
                                    <select name="status" class="selectpicker" data-width="100%">
                                        <option value=""><?= _l('manager_portal_filter_all'); ?></option>
                                        <?php foreach ($options['statuses'] as $status) { ?>
                                        <option value="<?= (int) $status['id']; ?>" <?= ((string) $filters['status'] === (string) $status['id']) ? 'selected' : ''; ?>><?= e($status['name']); ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <div class="col-md-2 form-group">
                                    <label><?= _l('manager_portal_filter_priority'); ?></label>
                                    <select name="priority" class="selectpicker" data-width="100%">
                                        <option value=""><?= _l('manager_portal_filter_all'); ?></option>
                                        <?php foreach ($options['priorities'] as $priority) { ?>
                                        <option value="<?= (int) $priority['id']; ?>" <?= ((string) $filters['priority'] === (string) $priority['id']) ? 'selected' : ''; ?>><?= e($priority['name']); ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <div class="col-md-2 form-group">
                                    <label><?= _l('manager_portal_filter_project'); ?></label>
                                    <select name="project_id" class="selectpicker" data-width="100%" data-live-search="true">
                                        <option value=""><?= _l('manager_portal_filter_all'); ?></option>
                                        <?php foreach ($options['projects'] as $project) { ?>
                                        <option value="<?= (int) $project['id']; ?>" <?= ((string) $filters['project_id'] === (string) $project['id']) ? 'selected' : ''; ?>><?= e($project['name']); ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <div class="col-md-2 form-group">
                                    <label><?= _l('manager_portal_filter_date_from'); ?></label>
                                    <input type="text" name="date_from" class="form-control datepicker" autocomplete="off" value="<?= e((string) $filters['date_from']); ?>" />
                                </div>
                                <div class="col-md-2 form-group">
                                    <label><?= _l('manager_portal_filter_date_to'); ?></label>
                                    <input type="text" name="date_to" class="form-control datepicker" autocomplete="off" value="<?= e((string) $filters['date_to']); ?>" />
                                </div>
                                <div class="col-md-2 form-group tw-flex tw-items-end">
                                    <button type="submit" class="btn btn-primary tw-w-full"><?= _l('manager_portal_filter_apply'); ?></button>
                                </div>
                                <div class="col-md-2 form-group tw-flex tw-items-end">
                                    <a href="<?= admin_url('manager_portal/tasks'); ?>" class="btn btn-default tw-w-full"><?= _l('manager_portal_filter_clear'); ?></a>
                                </div>
                            </div>
                        </form>

                        <?php if (empty($tasks)) { ?>
                        <span class="text-muted"><?= _l('manager_portal_tasks_empty'); ?></span>
                        <?php } else { ?>
                        <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th><?= _l('manager_portal_tasks_column_task'); ?></th>
                                    <th><?= _l('manager_portal_tasks_column_project'); ?></th>
                                    <th><?= _l('manager_portal_tasks_column_assigned'); ?></th>
                                    <th><?= _l('manager_portal_tasks_column_priority'); ?></th>
                                    <th><?= _l('manager_portal_tasks_column_status'); ?></th>
                                    <th><?= _l('manager_portal_projects_column_progress'); ?></th>
                                    <th><?= _l('manager_portal_tasks_column_due_date'); ?></th>
                                    <th><?= _l('manager_portal_projects_column_overdue'); ?></th>
                                    <th><?= _l('manager_portal_tasks_column_last_updated'); ?></th>
                                    <th><?= _l('manager_portal_projects_column_actions'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tasks as $task) { ?>
                                <tr>
                                    <td><?= e($task['name']); ?></td>
                                    <td><?= $task['project_name'] ? e($task['project_name']) : '-'; ?></td>
                                    <td>
                                        <?php if (empty($task['assignee_names'])) { ?>
                                        <span class="text-muted">-</span>
                                        <?php } else { ?>
                                        <?= e(implode(', ', $task['assignee_names'])); ?>
                                        <?php } ?>
                                    </td>
                                    <td><span style="color:<?= task_priority_color($task['priority']); ?>"><?= e(task_priority($task['priority'])); ?></span></td>
                                    <td><?= format_task_status($task['status'], true); ?></td>
                                    <td>
                                        <?php
                                            $progress = 0;
                                            if ((int) $task['status'] === Tasks_model::STATUS_COMPLETE) {
                                                $progress = 100;
                                            } elseif ((int) $task['status'] === Tasks_model::STATUS_IN_PROGRESS) {
                                                $progress = 50;
                                            }
                                        ?>
                                        <?= (int) $progress; ?>%
                                    </td>
                                    <td><?= $task['duedate'] ? e(_d($task['duedate'])) : '-'; ?></td>
                                    <td>
                                        <?php if ($task['overdue']) { ?>
                                        <span class="label label-danger"><?= _l('manager_portal_overdue'); ?></span>
                                        <?php } else { ?>
                                        <span class="text-muted">-</span>
                                        <?php } ?>
                                    </td>
                                    <td><?= $task['last_updated'] ? e(time_ago($task['last_updated'])) : '-'; ?></td>
                                    <td>
                                        <a href="<?= admin_url('manager_portal/task/' . $task['id']); ?>" class="btn btn-default btn-icon"><i class="fa-regular fa-eye"></i></a>
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
