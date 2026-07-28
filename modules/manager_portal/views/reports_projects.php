<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <?php $this->load->view('reports_subnav'); ?>

        <div class="panel_s">
            <div class="panel-body">
                <h4 class="no-margin"><?= e($title); ?></h4>
                <hr class="hr-panel-separator" />

                <form method="get" class="tw-mb-4">
                    <div class="row">
                        <div class="col-md-3 form-group">
                            <label><?= _l('manager_portal_filter_department'); ?></label>
                            <select name="department_id" class="selectpicker" data-width="100%" data-live-search="true">
                                <option value=""><?= _l('manager_portal_filter_all'); ?></option>
                                <?php foreach ($options['departments'] as $department) { ?>
                                <option value="<?= (int) $department['id']; ?>" <?= ((string) $filters['department_id'] === (string) $department['id']) ? 'selected' : ''; ?>><?= e($department['name']); ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="col-md-3 form-group">
                            <label><?= _l('manager_portal_filter_status'); ?></label>
                            <select name="status" class="selectpicker" data-width="100%">
                                <option value=""><?= _l('manager_portal_filter_all'); ?></option>
                                <?php foreach ($options['statuses'] as $status) { ?>
                                <option value="<?= (int) $status['id']; ?>" <?= ((string) $filters['status'] === (string) $status['id']) ? 'selected' : ''; ?>><?= e($status['name']); ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="col-md-3 form-group">
                            <label><?= _l('manager_portal_filter_active_completed'); ?></label>
                            <select name="active" class="selectpicker" data-width="100%">
                                <option value=""><?= _l('manager_portal_filter_all'); ?></option>
                                <option value="active" <?= ($filters['active'] === 'active') ? 'selected' : ''; ?>><?= _l('manager_portal_filter_active'); ?></option>
                                <option value="completed" <?= ($filters['active'] === 'completed') ? 'selected' : ''; ?>><?= _l('manager_portal_filter_completed'); ?></option>
                            </select>
                        </div>
                        <div class="col-md-2 form-group tw-flex tw-items-end">
                            <button type="submit" class="btn btn-primary tw-w-full"><?= _l('manager_portal_filter_apply'); ?></button>
                        </div>
                        <div class="col-md-1 form-group tw-flex tw-items-end">
                            <a href="<?= admin_url('manager_portal/reports_projects'); ?>" class="btn btn-default tw-w-full"><?= _l('manager_portal_filter_clear'); ?></a>
                        </div>
                    </div>
                </form>

                <?php if (empty($rows)) { ?>
                <span class="text-muted"><?= _l('manager_portal_reports_empty'); ?></span>
                <?php } else { ?>
                <div class="table-responsive">
                <table id="mp-report-projects" class="table dt-table">
                    <thead>
                        <tr>
                            <th><?= _l('manager_portal_projects_column_name'); ?></th>
                            <th><?= _l('manager_portal_projects_column_customer'); ?></th>
                            <th><?= _l('manager_portal_projects_column_department'); ?></th>
                            <th><?= _l('manager_portal_projects_column_progress'); ?></th>
                            <th><?= _l('manager_portal_projects_column_status'); ?></th>
                            <th><?= _l('manager_portal_projects_column_deadline'); ?></th>
                            <th><?= _l('manager_portal_projects_column_assigned'); ?></th>
                            <th><?= _l('manager_portal_reports_completed_tasks'); ?></th>
                            <th><?= _l('manager_portal_kpi_pending_tasks'); ?></th>
                            <th><?= _l('manager_portal_kpi_overdue_tasks'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $row) { ?>
                        <?php
                            $status_meta = null;
                            foreach ($options['statuses'] as $status) {
                                if ((int) $status['id'] === (int) $row['status']) {
                                    $status_meta = $status;
                                    break;
                                }
                            }
                        ?>
                        <tr>
                            <td><?= e($row['name']); ?></td>
                            <td><?= $row['company'] ? e($row['company']) : '-'; ?></td>
                            <td><?= $row['department_name'] ? e($row['department_name']) : '-'; ?></td>
                            <td><?= (int) $row['progress']; ?>%</td>
                            <td><?php if ($status_meta) { ?><span class="label" style="color:<?= $status_meta['color']; ?>;border:1px solid <?= adjust_hex_brightness($status_meta['color'], 0.4); ?>;background:<?= adjust_hex_brightness($status_meta['color'], 0.04); ?>;"><?= e($status_meta['name']); ?></span><?php } else { ?>-<?php } ?></td>
                            <td><?= $row['deadline'] ? e(_d($row['deadline'])) : '-'; ?></td>
                            <td><?= !empty($row['member_names']) ? e(implode(', ', $row['member_names'])) : '-'; ?></td>
                            <td><?= (int) $row['completed_tasks']; ?></td>
                            <td><?= (int) $row['pending_tasks']; ?></td>
                            <td><?= (int) $row['overdue_task_count']; ?></td>
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
<?php init_tail(); ?>
<script>
    $(function() {
        <?php if (!empty($rows)) { ?>
        var table = $('#mp-report-projects');
        table.DataTable({
            dom: "<'row'><'row'<'col-md-7'lB><'col-md-5'f>>rt<'row'<'col-md-4'i><'col-md-8 dataTables_paging'<'#colvis'><'.dt-page-jump'>p>>",
            buttons: get_datatable_buttons(table),
        });
        <?php } ?>
    });
</script>
</body>
</html>
