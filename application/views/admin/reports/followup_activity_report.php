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
                    <table id="followup-activity-report-table" class="table dt-table">
                        <thead>
                            <tr>
                                <th></th>
                                <th><?= _l('notification_column_title'); ?></th>
                                <th><?= _l('notification_column_description'); ?></th>
                                <th><?= _l('notification_column_related_case'); ?></th>
                                <th><?= _l('case_field_assigned_staff'); ?></th>
                                <th><?= _l('case_field_created_date'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $row) {
                                $is_history = $row->source === 'history';
                                $icon  = $is_history ? get_followup_history_icon((object) ['activity_type' => $row->type, 'follow_up_type' => $row->type]) : get_followup_activity_icon((object) ['action_type' => $row->type]);
                                $label = $is_history ? get_followup_history_label((object) ['activity_type' => $row->type, 'follow_up_type' => $row->type]) : get_followup_activity_label((object) ['action_type' => $row->type]);
                                ?>
                            <tr>
                                <td><i class="<?= e($icon); ?>"></i></td>
                                <td><?= e($label); ?></td>
                                <td><?= $row->description ? e(strip_tags($row->description)) : '-'; ?></td>
                                <td><a href="<?= admin_url('follow_up_management/view/' . $row->followup_id); ?>">#<?= (int) $row->followup_id; ?></a></td>
                                <td><?= e(get_staff_full_name($row->created_by)); ?></td>
                                <td><?= e(_dt($row->event_time)); ?></td>
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

        var table = $('#followup-activity-report-table');
        table.DataTable({
            dom: "<'row'><'row'<'col-md-7'lB><'col-md-5'f>>rt<'row'<'col-md-4'i><'col-md-8 dataTables_paging'<'#colvis'><'.dt-page-jump'>p>>",
            buttons: get_datatable_buttons(table),
            order: [],
        });
    });
</script>
</body>
</html>
