<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin"><?= e($title); ?></h4>
                        <p class="text-muted"><?= _l('daily_work_update_report_hint'); ?></p>
                        <hr class="hr-panel-separator" />

                        <div class="row dwu-report-filters">
                            <div class="col-md-2 tw-mb-3">
                                <select id="dwu-report-filter-department" class="selectpicker" data-width="100%"
                                    data-none-selected-text="<?= _l('daily_work_update_report_all_departments'); ?>">
                                    <option value=""><?= _l('daily_work_update_report_all_departments'); ?></option>
                                    <?php foreach ($departments as $department) { ?>
                                    <option value="<?= (int) $department['id']; ?>"><?= e($department['name']); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="col-md-2 tw-mb-3">
                                <select id="dwu-report-filter-employee" class="selectpicker" data-width="100%" data-live-search="true"
                                    data-none-selected-text="<?= _l('daily_work_update_report_all_employees'); ?>">
                                    <option value=""><?= _l('daily_work_update_report_all_employees'); ?></option>
                                    <?php foreach ($employees as $employee) { ?>
                                    <option value="<?= (int) $employee['staffid']; ?>"><?= e(trim($employee['firstname'] . ' ' . $employee['lastname'])); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="col-md-2 tw-mb-3">
                                <select id="dwu-report-filter-project" class="selectpicker" data-width="100%" data-live-search="true"
                                    data-none-selected-text="<?= _l('daily_work_update_report_all_projects'); ?>">
                                    <option value=""><?= _l('daily_work_update_report_all_projects'); ?></option>
                                    <?php foreach ($projects as $project) { ?>
                                    <option value="<?= (int) $project['id']; ?>"><?= e($project['name']); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="col-md-2 tw-mb-3">
                                <select id="dwu-report-filter-task" class="selectpicker" data-width="100%" data-live-search="true"
                                    data-none-selected-text="<?= _l('daily_work_update_report_all_tasks'); ?>">
                                    <option value=""><?= _l('daily_work_update_report_all_tasks'); ?></option>
                                    <?php foreach ($tasks as $task) { ?>
                                    <option value="<?= (int) $task['id']; ?>"><?= e($task['name']); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="col-md-2 tw-mb-3">
                                <?= render_date_input('dwu_report_date_from', 'daily_work_update_report_date_from', ''); ?>
                            </div>
                            <div class="col-md-2 tw-mb-3">
                                <?= render_date_input('dwu_report_date_to', 'daily_work_update_report_date_to', ''); ?>
                            </div>
                            <div class="col-md-3 tw-mb-3">
                                <select id="dwu-report-filter-status" class="selectpicker" data-width="100%">
                                    <option value=""><?= _l('daily_work_update_report_status_all'); ?></option>
                                    <option value="submitted_today"><?= _l('daily_work_update_report_status_submitted_today'); ?></option>
                                    <option value="not_submitted_today"><?= _l('daily_work_update_report_status_not_submitted_today'); ?></option>
                                </select>
                            </div>
                            <div class="col-md-3 tw-mb-3">
                                <select id="dwu-report-filter-has-attachments" class="selectpicker" data-width="100%">
                                    <option value=""><?= _l('daily_work_update_report_attachments_any'); ?></option>
                                    <option value="1"><?= _l('daily_work_update_report_attachments_yes'); ?></option>
                                </select>
                            </div>
                        </div>
                        <div class="clearfix mtop15"></div>

                        <?php render_datatable([
                            _l('daily_work_update_report_col_date'),
                            _l('daily_work_update_report_col_employee'),
                            _l('daily_work_update_report_col_department'),
                            _l('daily_work_update_report_col_project'),
                            _l('daily_work_update_report_col_task'),
                            _l('daily_work_update_report_col_today_work'),
                            _l('daily_work_update_report_col_tomorrow_plan'),
                            _l('daily_work_update_report_col_issues'),
                            _l('daily_work_update_report_col_attachments'),
                            _l('daily_work_update_report_col_submitted_time'),
                            _l('daily_work_update_report_col_updated_time'),
                        ], 'daily-work-update-report'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="dwu-report-modal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><?= _l('daily_work_update_report_detail_title'); ?></h4>
            </div>
            <div class="modal-body" id="dwu-report-modal-body">
                <div class="text-center tw-py-4"><i class="fa-solid fa-spinner fa-spin"></i></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?= _l('close'); ?></button>
            </div>
        </div>
    </div>
</div>

<style>
    .table-daily-work-update-report tbody tr[id^="daily_work_update_report_"] { cursor: pointer; }
    .table-daily-work-update-report tbody tr[id^="daily_work_update_report_"]:hover { background: #f8f9fb; }
</style>

<?php init_tail(); ?>
<script>
    $(function() {
        // Attachments (index 8) has no real backing column - same
        // "derived column, not sortable/searchable" convention every
        // other module table source in this fork already uses.
        initDataTable('.table-daily-work-update-report', admin_url + 'daily_work_update/report_table', [8], [8], {
            department_id: '#dwu-report-filter-department',
            employee_id: '#dwu-report-filter-employee',
            project_id: '#dwu-report-filter-project',
            task_id: '#dwu-report-filter-task',
            date_from: '#dwu_report_date_from',
            date_to: '#dwu_report_date_to',
            status: '#dwu-report-filter-status',
            has_attachments: '#dwu-report-filter-has-attachments',
        }, [0, 'desc']);

        $('.dwu-report-filters select').on('changed.bs.select', function() {
            $('.table-daily-work-update-report').DataTable().ajax.reload();
        });

        $('#dwu_report_date_from, #dwu_report_date_to').on('changeDate', function() {
            $('.table-daily-work-update-report').DataTable().ajax.reload();
        });

        // Whole-row click, per spec - guarded against "Not Submitted
        // Today" rows, which have nothing to show (no DT_RowId is set
        // for those server-side, see Daily_work_update::report_table()).
        $('.table-daily-work-update-report tbody').on('click', 'tr', function() {
            var id = $(this).attr('id');
            if (!id || id.indexOf('daily_work_update_report_') !== 0) {
                return;
            }

            var workUpdateId = id.replace('daily_work_update_report_', '');

            $('#dwu-report-modal-body').html('<div class="text-center tw-py-4"><i class="fa-solid fa-spinner fa-spin"></i></div>');
            $('#dwu-report-modal').modal('show');

            $.get(admin_url + 'daily_work_update/report_detail/' + workUpdateId).done(function(response) {
                $('#dwu-report-modal-body').html(response);
            });
        });
    });
</script>
