<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="tw-flex tw-items-center tw-justify-between tw-mb-3">
                            <h4 class="no-margin"><?= _l('followup_customers'); ?></h4>
                        </div>
                        <p class="text-muted"><?= _l('followup_customers_hint'); ?></p>

                        <?php render_datatable([
                            _l('field_portal_column_customer'),
                            _l('field_portal_column_phone'),
                            _l('field_portal_column_created_from_lead'),
                            _l('field_portal_column_converted_date'),
                            _l('field_portal_column_current_status'),
                            _l('field_portal_column_actions'),
                        ], 'telecaller-customers'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script>
    $(function() {
        initDataTable('.table-telecaller-customers', admin_url + 'follow_up_management/customers_table', [], [], [], [3, 'desc']);
    });
</script>
</body>
</html>
