<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="tw-flex tw-items-center tw-justify-between tw-mb-3">
                            <h4 class="no-margin"><?= _l('my_leads'); ?></h4>
                        </div>

                        <div class="row tw-mb-3">
                            <div class="col-md-3">
                                <select id="filter-status" class="selectpicker" data-width="100%"
                                    data-none-selected-text="<?= _l('field_portal_filter_all_statuses'); ?>">
                                    <option value=""><?= _l('field_portal_filter_all_statuses'); ?></option>
                                    <?php foreach ($statuses as $status) { ?>
                                    <option value="<?= (int) $status['id']; ?>"><?= e($status['name']); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <div class="input-group date">
                                    <input type="text" id="filter-date" class="form-control datepicker" autocomplete="off" placeholder="<?= _l('field_portal_filter_select_date'); ?>" />
                                    <span class="input-group-addon"><i class="fa-regular fa-calendar"></i></span>
                                </div>
                            </div>
                        </div>

                        <?php render_datatable([
                            _l('field_portal_column_lead'),
                            _l('field_portal_column_company'),
                            _l('field_portal_column_phone'),
                            _l('field_portal_column_status'),
                            _l('field_portal_column_priority'),
                            _l('followup_last_call'),
                            _l('field_portal_column_next_followup'),
                            _l('field_portal_column_field_executive'),
                            _l('field_portal_column_latest_note'),
                            _l('field_portal_column_actions'),
                        ], 'telecaller-my-leads'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script>
    $(function() {
        initDataTable('.table-telecaller-my-leads', admin_url + 'follow_up_management/my_leads_table', [], [], {
            status: '#filter-status',
            date: '#filter-date',
        }, [0, 'asc']);

        $('#filter-status').on('changed.bs.select', function() {
            $('.table-telecaller-my-leads').DataTable().ajax.reload();
        });

        $('#filter-date').on('change', function() {
            $('.table-telecaller-my-leads').DataTable().ajax.reload();
        });
    });
</script>
</body>
</html>
