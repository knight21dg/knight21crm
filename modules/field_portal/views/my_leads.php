<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <?php field_portal_page_header(
                            _l('field_portal_my_leads'),
                            [
                                ['label' => _l('field_portal_breadcrumb_dashboard'), 'href' => admin_url('field_portal')],
                                ['label' => _l('field_portal_my_leads')],
                            ],
                            'table-field-portal-leads',
                            admin_url('field_portal/create_lead'),
                            _l('field_portal_create_lead')
                        ); ?>
                        <p class="text-muted"><?= _l('field_portal_my_leads_hint'); ?></p>

                        <div class="row">
                            <div class="col-md-3">
                                <select id="field-portal-filter-status" class="selectpicker" data-width="100%"
                                    data-none-selected-text="<?= _l('field_portal_filter_all_statuses'); ?>">
                                    <option value=""><?= _l('field_portal_filter_all_statuses'); ?></option>
                                    <?php foreach ($statuses as $status) { ?>
                                    <option value="<?= (int) $status['id']; ?>" <?= $default_status === (int) $status['id'] ? 'selected' : ''; ?>><?= e($status['name']); ?></option>
                                    <?php } ?>
                                    <option value="converted" <?= $default_status === 'converted' ? 'selected' : ''; ?>><?= _l('field_portal_filter_converted'); ?></option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <div class="input-group date">
                                    <input type="text" id="field-portal-filter-date" class="form-control datepicker" autocomplete="off" placeholder="<?= _l('field_portal_filter_select_date'); ?>" value="<?= $default_date ? e(_d($default_date)) : ''; ?>" />
                                    <span class="input-group-addon"><i class="fa-regular fa-calendar"></i></span>
                                </div>
                            </div>
                        </div>
                        <div class="clearfix mtop15"></div>

                        <?php render_datatable([
                            _l('field_portal_column_lead'),
                            _l('field_portal_column_company'),
                            _l('field_portal_column_phone'),
                            _l('field_portal_column_status'),
                            _l('field_portal_column_priority'),
                            _l('field_portal_column_meeting_date'),
                            _l('field_portal_column_next_followup'),
                            _l('field_portal_column_assigned_telecaller'),
                            _l('field_portal_column_created_time'),
                            _l('field_portal_column_latest_note'),
                            _l('field_portal_column_actions'),
                        ], 'field-portal-leads'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script>
    $(function() {
        initDataTable('.table-field-portal-leads', admin_url + 'field_portal/my_leads_table', [], [], {
            status: '#field-portal-filter-status',
            date: '#field-portal-filter-date',
        }, [0, 'asc']);

        // Persist Status/Date in the URL so a manual refresh or a copied
        // link restores the same filters.
        function field_portal_sync_leads_url() {
            var status = $('#field-portal-filter-status').val() || '';
            var dateDisplay = $('#field-portal-filter-date').val() || '';
            var params = new URLSearchParams();
            if (status) {
                params.set('status', status);
            }
            if (dateDisplay) {
                params.set('date', dateDisplay);
            }
            var qs = params.toString();
            window.history.replaceState(null, '', window.location.pathname + (qs ? '?' + qs : ''));
        }

        $('#field-portal-filter-status').on('changed.bs.select', function() {
            field_portal_sync_leads_url();
            $('.table-field-portal-leads').DataTable().ajax.reload();
        });

        $('#field-portal-filter-date').on('change', function() {
            field_portal_sync_leads_url();
            $('.table-field-portal-leads').DataTable().ajax.reload();
        });
    });
</script>
</body>
</html>
