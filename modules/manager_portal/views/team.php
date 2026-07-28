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

                        <?php if (empty($members)) { ?>
                        <span class="text-muted"><?= _l('manager_portal_team_empty'); ?></span>
                        <?php } else { ?>
                        <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th><?= _l('manager_portal_team_column_profile'); ?></th>
                                    <th><?= _l('manager_portal_team_column_name'); ?></th>
                                    <th><?= _l('manager_portal_team_column_id'); ?></th>
                                    <th><?= _l('manager_portal_team_column_department'); ?></th>
                                    <th><?= _l('manager_portal_team_column_designation'); ?></th>
                                    <th><?= _l('manager_portal_team_column_attendance'); ?></th>
                                    <th><?= _l('manager_portal_team_column_todays_tasks'); ?></th>
                                    <th><?= _l('manager_portal_team_column_pending_tasks'); ?></th>
                                    <th><?= _l('manager_portal_team_column_active_projects'); ?></th>
                                    <th><?= _l('manager_portal_team_column_dwu_status'); ?></th>
                                    <th><?= _l('manager_portal_team_column_performance'); ?></th>
                                    <th><?= _l('manager_portal_team_column_actions'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($members as $member) { ?>
                                <tr>
                                    <td><?= staff_profile_image($member['staffid'], ['tw-rounded-full'], 'tiny'); ?></td>
                                    <td><?= e(trim($member['firstname'] . ' ' . $member['lastname'])); ?></td>
                                    <td>#<?= (int) $member['staffid']; ?></td>
                                    <td>
                                        <?php if (empty($member['departments'])) { ?>
                                        <span class="text-muted">-</span>
                                        <?php } else { ?>
                                        <?php foreach ($member['departments'] as $department_name) { ?>
                                        <span class="label label-default"><?= e($department_name); ?></span>
                                        <?php } ?>
                                        <?php } ?>
                                    </td>
                                    <td><?= !empty($member['designation']) ? e($member['designation']) : '-'; ?></td>
                                    <td>
                                        <span class="label" style="color:<?= get_attendance_status_color($member['attendance_status']); ?>;border:1px solid <?= adjust_hex_brightness(get_attendance_status_color($member['attendance_status']), 0.4); ?>;background:<?= adjust_hex_brightness(get_attendance_status_color($member['attendance_status']), 0.04); ?>;">
                                            <?= e($member['attendance_status']); ?>
                                        </span>
                                    </td>
                                    <td><?= (int) $member['todays_tasks']; ?></td>
                                    <td><?= (int) $member['pending_tasks']; ?></td>
                                    <td><?= (int) $member['active_projects']; ?></td>
                                    <td><?= manager_portal_dwu_status_badge($member['dwu_status']); ?></td>
                                    <td><?= manager_portal_performance_badge($member['performance_label'], $member['productivity']); ?></td>
                                    <td>
                                        <a href="<?= admin_url('manager_portal/team_member/' . $member['staffid']); ?>" class="btn btn-default btn-icon"><i class="fa-regular fa-eye"></i></a>
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
