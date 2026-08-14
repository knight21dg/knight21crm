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
                            _l('dev_portal_column_note'),
                            _l('dev_portal_column_priority'),
                            _l('dev_portal_column_assigned_team'),
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
        // Priority (8), Assigned Team (9) and Actions/Open (10) are all
        // derived, no 1:1 SQL column - see Dev_portal::my_projects_table()'s
        // own file-level note.
        initDataTable('.table-dev-portal-my-projects', admin_url + 'dev_portal/my_projects_table', [8, 9, 10], [8, 9, 10], {}, [4, 'asc']);
    });
</script>
</body>
</html>
