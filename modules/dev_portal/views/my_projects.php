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
                            _l('dev_portal_column_project_name'),
                            _l('dev_portal_column_client'),
                            _l('dev_portal_column_project_type'),
                            _l('dev_portal_column_assigned_date'),
                            _l('dev_portal_column_due_date'),
                            _l('dev_portal_column_status'),
                            _l('dev_portal_column_progress'),
                            _l('dev_portal_column_priority'),
                            _l('dev_portal_column_actions'),
                        ], 'dev-portal-my-projects'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script>
    $(function() {
        // Priority (7) and Actions/Open (8) are both derived, no 1:1
        // SQL column - see Dev_portal::my_projects_table()'s own
        // file-level note.
        initDataTable('.table-dev-portal-my-projects', admin_url + 'dev_portal/my_projects_table', [7, 8], [7, 8], {}, [4, 'asc']);
    });
</script>
</body>
</html>
