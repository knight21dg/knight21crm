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
                            <label><?= _l('manager_portal_filter_employee'); ?></label>
                            <select name="employee_id" class="selectpicker" data-width="100%" data-live-search="true">
                                <option value=""><?= _l('manager_portal_filter_all'); ?></option>
                                <?php foreach ($options['employees'] as $employee) { ?>
                                <option value="<?= (int) $employee['staffid']; ?>" <?= ((string) $filters['employee_id'] === (string) $employee['staffid']) ? 'selected' : ''; ?>><?= e(trim($employee['firstname'] . ' ' . $employee['lastname'])); ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="col-md-3 form-group">
                            <label><?= _l('manager_portal_reports_month'); ?></label>
                            <input type="text" name="date_from" class="form-control datepicker" autocomplete="off" value="<?= e((string) $filters['date_from']); ?>" placeholder="<?= e(_l('manager_portal_reports_month_hint')); ?>" />
                        </div>
                        <div class="col-md-2 form-group tw-flex tw-items-end">
                            <button type="submit" class="btn btn-primary tw-w-full"><?= _l('manager_portal_filter_apply'); ?></button>
                        </div>
                        <div class="col-md-1 form-group tw-flex tw-items-end">
                            <a href="<?= admin_url('manager_portal/reports'); ?>" class="btn btn-default tw-w-full"><?= _l('manager_portal_filter_clear'); ?></a>
                        </div>
                    </div>
                </form>

                <?php if (empty($rows)) { ?>
                <span class="text-muted"><?= _l('manager_portal_reports_empty'); ?></span>
                <?php } else { ?>
                <div class="table-responsive">
                <table id="mp-report-productivity" class="table dt-table">
                    <thead>
                        <tr>
                            <th><?= _l('manager_portal_attendance_column_employee'); ?></th>
                            <th><?= _l('manager_portal_dwu_column_department'); ?></th>
                            <th><?= _l('manager_portal_attendance_kpi_percent'); ?></th>
                            <th><?= _l('manager_portal_reports_dwu_submitted'); ?></th>
                            <th><?= _l('manager_portal_reports_tasks_assigned'); ?></th>
                            <th><?= _l('manager_portal_reports_tasks_completed'); ?></th>
                            <th><?= _l('manager_portal_kpi_active_projects'); ?></th>
                            <th><?= _l('manager_portal_reports_completion_rate'); ?></th>
                            <th><?= _l('manager_portal_reports_productivity_score'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $row) { ?>
                        <tr>
                            <td><?= e($row['employee_name']); ?></td>
                            <td><?= $row['department_name'] ? e($row['department_name']) : '-'; ?></td>
                            <td><?= (int) $row['attendance_percent']; ?>%</td>
                            <td><?= (int) $row['dwu_submitted']; ?></td>
                            <td><?= (int) $row['tasks_assigned']; ?></td>
                            <td><?= (int) $row['tasks_completed']; ?></td>
                            <td><?= (int) $row['active_projects']; ?></td>
                            <td><?= (int) $row['completion_rate']; ?>%</td>
                            <td><?= (int) $row['productivity_score']; ?>%</td>
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
        var table = $('#mp-report-productivity');
        table.DataTable({
            dom: "<'row'><'row'<'col-md-7'lB><'col-md-5'f>>rt<'row'<'col-md-4'i><'col-md-8 dataTables_paging'<'#colvis'><'.dt-page-jump'>p>>",
            buttons: get_datatable_buttons(table),
        });
        <?php } ?>
    });
</script>
</body>
</html>
