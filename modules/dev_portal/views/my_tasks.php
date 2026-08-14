<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin"><?= e($title); ?></h4>

                        <?php
                        // Dashboard-card deep-link filter badge - $filter/$status
                        // come straight from Dev_portal::my_tasks()'s own GET
                        // read, same two params my_tasks_table() re-reads for
                        // the actual query (see that method's own note).
                        $active_filter_label = null;
                        if ($filter === 'today') {
                            $active_filter_label = _l('dev_portal_filter_today');
                        } elseif ($status === 'completed') {
                            $active_filter_label = _l('dev_portal_filter_completed');
                        }
                        ?>
                        <?php if ($active_filter_label) { ?>
                        <div class="tw-mt-2 tw-mb-3">
                            <span class="label label-default tw-inline-flex tw-items-center tw-gap-2">
                                <?= e($active_filter_label); ?>
                                <a href="<?= admin_url('dev_portal/my_tasks'); ?>" class="tw-text-white" style="text-decoration:underline;">
                                    <?= _l('dev_portal_filter_clear'); ?>
                                </a>
                            </span>
                        </div>
                        <?php } ?>

                        <hr class="hr-panel-separator" />

                        <?php render_datatable([
                            _l('dev_portal_column_task'),
                            _l('dev_portal_column_project'),
                            _l('dev_portal_column_priority'),
                            _l('dev_portal_column_status'),
                            _l('dev_portal_column_due_date'),
                            _l('dev_portal_column_estimated_hours'),
                            _l('dev_portal_column_hours_worked'),
                            _l('dev_portal_column_note'),
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
        //
        // location.search (?filter=today / ?status=completed) is appended
        // to the AJAX endpoint itself - a separate HTTP request from this
        // page load, so Dev_portal::my_tasks_table() re-reads the same GET
        // params from its own request rather than trusting anything passed
        // from PHP into this script.
        initDataTable('.table-dev-portal-my-tasks', admin_url + 'dev_portal/my_tasks_table' + window.location.search, [6], [6], {}, [4, 'asc']);
    });
</script>
</body>
</html>
