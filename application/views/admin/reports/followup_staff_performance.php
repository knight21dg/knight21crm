<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <?php $this->load->view('admin/reports/followup_filters', ['filters' => $filters, 'departments' => $departments, 'members' => $members]); ?>

        <div class="panel_s">
            <div class="panel-body">
                <h4 class="no-margin"><?= e($title); ?></h4>
                <hr class="hr-panel-separator" />
                <div class="table-responsive">
                    <table id="followup-staff-performance-table" class="table dt-table">
                        <thead>
                            <tr>
                                <th><?= _l('case_field_assigned_staff'); ?></th>
                                <th><?= _l('case_column_department'); ?></th>
                                <th><?= _l('card_total_cases_assigned'); ?></th>
                                <th><?= _l('card_total_cases_closed'); ?></th>
                                <th><?= _l('card_open_cases'); ?></th>
                                <th><?= _l('card_calls_logged'); ?></th>
                                <th><?= _l('card_meetings_scheduled'); ?></th>
                                <th><?= _l('card_reminders_created'); ?></th>
                                <th><?= _l('card_cases_reopened'); ?></th>
                                <th><?= _l('card_avg_response_time'); ?></th>
                                <th><?= _l('card_avg_closure_time'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $row) { ?>
                            <tr>
                                <td><?= e(get_staff_full_name($row['staff_id'])); ?></td>
                                <td><?= $row['department_name'] ? e($row['department_name']) : '-'; ?></td>
                                <td><?= (int) $row['total_cases_assigned']; ?></td>
                                <td><?= (int) $row['total_cases_closed']; ?></td>
                                <td><?= (int) $row['open_cases']; ?></td>
                                <td><?= (int) $row['calls_logged']; ?></td>
                                <td><?= (int) $row['meetings_scheduled']; ?></td>
                                <td><?= (int) $row['reminders_created']; ?></td>
                                <td><?= (int) $row['cases_reopened']; ?></td>
                                <td><?= e(get_followup_format_duration($row['avg_response_time'])); ?></td>
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

        var table = $('#followup-staff-performance-table');
        table.DataTable({
            dom: "<'row'><'row'<'col-md-7'lB><'col-md-5'f>>rt<'row'<'col-md-4'i><'col-md-8 dataTables_paging'<'#colvis'><'.dt-page-jump'>p>>",
            buttons: get_datatable_buttons(table),
        });
    });
</script>
</body>
</html>
