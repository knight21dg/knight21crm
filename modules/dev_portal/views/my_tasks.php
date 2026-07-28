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

                        <?php render_datatable([
                            _l('dev_portal_column_task'),
                            _l('dev_portal_column_project'),
                            _l('dev_portal_column_priority'),
                            _l('dev_portal_column_status'),
                            _l('dev_portal_column_due_date'),
                            _l('dev_portal_column_estimated_hours'),
                            _l('dev_portal_column_hours_worked'),
                        ], 'dev-portal-my-tasks'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script>
    $(function() {
        // Only Hours Worked (index 6, a SUM aggregate) is derived -
        // Project (index 1) is a real joined column and stays fully
        // sortable/searchable, see Dev_portal::my_tasks_table()'s own
        // file-level note.
        initDataTable('.table-dev-portal-my-tasks', admin_url + 'dev_portal/my_tasks_table', [6], [6], {}, [4, 'asc']);
    });
</script>
</body>
</html>
