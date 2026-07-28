<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <?php field_portal_page_header(
                            _l('field_portal_assigned_to_telecaller'),
                            [
                                ['label' => _l('field_portal_breadcrumb_dashboard'), 'href' => admin_url('field_portal')],
                                ['label' => _l('field_portal_assigned_to_telecaller')],
                            ],
                            'table-field-portal-assigned'
                        ); ?>
                        <p class="text-muted"><?= _l('field_portal_assigned_to_telecaller_hint'); ?></p>

                        <?php render_datatable([
                            _l('field_portal_column_lead'),
                            _l('field_portal_column_telecaller'),
                            _l('field_portal_column_assigned_date'),
                            _l('field_portal_column_priority'),
                            _l('field_portal_column_current_status'),
                            _l('field_portal_column_next_followup'),
                            _l('field_portal_column_actions'),
                        ], 'field-portal-assigned'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script>
    $(function() {
        initDataTable('.table-field-portal-assigned', admin_url + 'field_portal/assigned_to_telecaller_table', [], [], {}, [2, 'desc']);
    });
</script>
</body>
</html>
