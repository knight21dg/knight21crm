<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin"><?= e($title); ?></h4>
                        <p class="text-muted"><?= _l('my_follow_ups_hint'); ?></p>
                        <hr class="hr-panel-separator" />

                        <?php render_datatable([
                            _l('followup_column_lead_name'),
                            _l('case_column_company'),
                            _l('followup_column_email'),
                            _l('followup_column_phone'),
                            _l('followup_column_lead_status'),
                            _l('followup_workspace_next_follow_up'),
                            _l('followup_column_source'),
                            _l('followup_column_last_contact'),
                            _l('followup_column_last_activity'),
                            _l('followup_column_assigned_date'),
                            _l('followup_column_whatsapp'),
                            _l('followup_column_priority'),
                            _l('followup_column_actions'),
                            _l('options'),
                            _l('followup_column_latest_note'),
                        ], 'my-follow-ups'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script>
    $(function() {
        // Same initDataTable() convention core Leads uses
        // (application/views/admin/leads/manage_leads.php) - pagination,
        // native search box, and column sorting all come from this one
        // call, unchanged. Columns 10-14 (WhatsApp/Priority/Actions/
        // Options/Latest Note) are derived, not real SQL columns - see
        // application/views/admin/tables/my_follow_ups.php's file-level
        // note for why they're excluded from sort/search here.
        initDataTable('.table-my-follow-ups', admin_url + 'follow_up_management/my_follow_ups_table', [10, 11, 12, 13, 14], [10, 11, 12, 13, 14], {}, [5, 'asc']);
    });
</script>
</body>
</html>
