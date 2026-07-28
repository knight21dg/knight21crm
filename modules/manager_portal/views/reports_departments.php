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
                                <?php foreach ($departments as $department) { ?>
                                <option value="<?= (int) $department['id']; ?>" <?= ((string) $filters['department_id'] === (string) $department['id']) ? 'selected' : ''; ?>><?= e($department['name']); ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="col-md-2 form-group tw-flex tw-items-end">
                            <button type="submit" class="btn btn-primary tw-w-full"><?= _l('manager_portal_filter_apply'); ?></button>
                        </div>
                        <div class="col-md-2 form-group tw-flex tw-items-end">
                            <a href="<?= admin_url('manager_portal/reports_departments'); ?>" class="btn btn-default tw-w-full"><?= _l('manager_portal_filter_clear'); ?></a>
                        </div>
                    </div>
                </form>

                <?php if (empty($rows)) { ?>
                <span class="text-muted"><?= _l('manager_portal_reports_empty'); ?></span>
                <?php } else { ?>
                <div class="table-responsive">
                <table id="mp-report-departments" class="table dt-table">
                    <thead>
                        <tr>
                            <th><?= _l('manager_portal_dwu_column_department'); ?></th>
                            <th><?= _l('manager_portal_reports_employees'); ?></th>
                            <th><?= _l('manager_portal_kpi_active_projects'); ?></th>
                            <th><?= _l('manager_portal_reports_completed_projects'); ?></th>
                            <th><?= _l('manager_portal_kpi_pending_tasks'); ?></th>
                            <th><?= _l('manager_portal_reports_completed_tasks'); ?></th>
                            <th><?= _l('manager_portal_attendance_kpi_percent'); ?></th>
                            <th><?= _l('manager_portal_reports_dwu_percent'); ?></th>
                            <th><?= _l('manager_portal_kpi_productivity'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $row) { ?>
                        <tr>
                            <td><?= e($row['department_name']); ?></td>
                            <td><?= (int) $row['employees']; ?></td>
                            <td><?= (int) $row['active_projects']; ?></td>
                            <td><?= (int) $row['completed_projects']; ?></td>
                            <td><?= (int) $row['pending_tasks']; ?></td>
                            <td><?= (int) $row['completed_tasks']; ?></td>
                            <td><?= (int) $row['attendance_percent']; ?>%</td>
                            <td><?= (int) $row['dwu_percent']; ?>%</td>
                            <td><?= (int) $row['productivity_percent']; ?>%</td>
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
        var table = $('#mp-report-departments');
        table.DataTable({
            dom: "<'row'><'row'<'col-md-7'lB><'col-md-5'f>>rt<'row'<'col-md-4'i><'col-md-8 dataTables_paging'<'#colvis'><'.dt-page-jump'>p>>",
            buttons: get_datatable_buttons(table),
        });
        <?php } ?>
    });
</script>
</body>
</html>
