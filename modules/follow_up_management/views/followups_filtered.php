<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="tw-flex tw-items-center tw-justify-between tw-mb-3">
                            <h4 class="no-margin"><?= e($title); ?></h4>
                        </div>

                        <?php render_datatable([
                            _l('field_portal_column_lead'),
                            _l('field_portal_column_company'),
                            _l('field_portal_column_phone'),
                            _l('field_portal_column_status'),
                            _l('followup_next_follow_up_date'),
                            _l('field_portal_column_telecaller'),
                            _l('field_portal_column_actions'),
                        ], 'telecaller-followups-filtered'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script>
    $(function() {
        initDataTable('.table-telecaller-followups-filtered', admin_url + 'follow_up_management/followups_filtered_table', [], [], {
            filter: '<?= e($filter); ?>',
        }, [4, 'asc']);
    });
</script>
</body>
</html>
