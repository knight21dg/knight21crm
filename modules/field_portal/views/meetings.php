<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <?php field_portal_page_header(
                            _l('field_portal_meetings'),
                            [
                                ['label' => _l('field_portal_breadcrumb_dashboard'), 'href' => admin_url('field_portal')],
                                ['label' => _l('field_portal_meetings')],
                            ],
                            'table-field-portal-meetings'
                        ); ?>
                        <p class="text-muted"><?= _l('field_portal_meetings_hint'); ?></p>

                        <div class="row">
                            <div class="col-md-3">
                                <select id="field-portal-filter-when" class="selectpicker" data-width="100%">
                                    <option value=""><?= _l('field_portal_filter_all_time'); ?></option>
                                    <option value="today" <?= $default_filter === 'today' ? 'selected' : ''; ?>><?= _l('field_portal_filter_today'); ?></option>
                                    <option value="tomorrow" <?= $default_filter === 'tomorrow' ? 'selected' : ''; ?>><?= _l('field_portal_filter_tomorrow'); ?></option>
                                    <option value="overdue" <?= $default_filter === 'overdue' ? 'selected' : ''; ?>><?= _l('field_portal_filter_overdue'); ?></option>
                                </select>
                            </div>
                        </div>
                        <div class="clearfix mtop15"></div>

                        <?php render_datatable([
                            _l('field_portal_column_lead'),
                            _l('field_portal_column_company'),
                            _l('field_portal_column_meeting_date'),
                            _l('field_portal_meeting_time'),
                            _l('field_portal_column_location'),
                            _l('field_portal_column_status'),
                            _l('field_portal_column_actions'),
                        ], 'field-portal-meetings'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script>
    $(function() {
        initDataTable('.table-field-portal-meetings', admin_url + 'field_portal/meetings_table', [], [], {
            when_filter: '#field-portal-filter-when',
        }, [2, 'asc']);

        $('#field-portal-filter-when').on('changed.bs.select', function() {
            $('.table-field-portal-meetings').DataTable().ajax.reload();
        });
    });
</script>
</body>
</html>
