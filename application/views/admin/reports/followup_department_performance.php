<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <?php $this->load->view('admin/reports/followup_filters', ['filters' => $filters, 'departments' => $departments]); ?>

        <div class="panel_s">
            <div class="panel-body">
                <h4 class="no-margin"><?= e($title); ?></h4>
                <hr class="hr-panel-separator" />
                <div class="table-responsive">
                    <table id="followup-department-performance-table" class="table dt-table">
                        <thead>
                            <tr>
                                <th><?= _l('case_column_department'); ?></th>
                                <th><?= _l('card_total_cases_assigned'); ?></th>
                                <th><?= _l('card_total_cases_closed'); ?></th>
                                <th><?= _l('card_open_cases'); ?></th>
                                <th><?= _l('card_overdue_cases'); ?></th>
                                <th><?= _l('card_avg_closure_time'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $row) { ?>
                            <tr>
                                <td><?= e($row['department_name']); ?></td>
                                <td><?= (int) $row['cases_assigned']; ?></td>
                                <td><?= (int) $row['cases_closed']; ?></td>
                                <td><?= (int) $row['open_cases']; ?></td>
                                <td><?= (int) $row['overdue_cases']; ?></td>
                                <td><?= e(get_followup_format_duration($row['avg_closure_time'])); ?></td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script>
    $(function() {
        $('.selectpicker').on('changed.bs.select', function() { $(this).closest('form').submit(); });

        var table = $('#followup-department-performance-table');
        table.DataTable({
            dom: "<'row'><'row'<'col-md-7'lB><'col-md-5'f>>rt<'row'<'col-md-4'i><'col-md-8 dataTables_paging'<'#colvis'><'.dt-page-jump'>p>>",
            buttons: get_datatable_buttons(table),
        });
    });
</script>
</body>
</html>
