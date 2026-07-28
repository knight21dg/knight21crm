<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <?php field_portal_page_header(
                            _l('field_portal_all_followups'),
                            [
                                ['label' => _l('field_portal_breadcrumb_dashboard'), 'href' => admin_url('field_portal')],
                                ['label' => _l('field_portal_all_followups')],
                            ],
                            'table-field-portal-followups'
                        ); ?>
                        <p class="text-muted"><?= _l('field_portal_all_followups_hint'); ?></p>

                        <?php
                        // Filter tabs - ONE page/table/query, only the WHERE
                        // clause applied server-side in
                        // Field_portal::followups_table() changes per tab.
                        // Clicking a tab never navigates; it just updates the
                        // hidden input below and reloads the same DataTable
                        // via AJAX (see script at the bottom).
                        $field_portal_followup_tabs = [
                            'all'       => _l('field_portal_filter_tab_all'),
                            'pending'   => _l('field_portal_filter_tab_pending'),
                            'today'     => _l('field_portal_filter_today'),
                            'tomorrow'  => _l('field_portal_filter_tomorrow'),
                            'upcoming'  => _l('field_portal_filter_tab_upcoming'),
                            'completed' => _l('field_portal_filter_tab_completed'),
                            'overdue'   => _l('field_portal_filter_overdue'),
                        ];
                        ?>
                        <ul class="nav nav-tabs" id="field-portal-followups-tabs">
                            <?php foreach ($field_portal_followup_tabs as $tab_key => $tab_label) { ?>
                            <li class="<?= $default_filter === $tab_key ? 'active' : ''; ?>">
                                <a href="#" data-filter="<?= e($tab_key); ?>"><?= e($tab_label); ?></a>
                            </li>
                            <?php } ?>
                        </ul>
                        <input type="hidden" id="field-portal-followups-filter" value="<?= e($default_filter); ?>" />
                        <div class="clearfix mtop15"></div>

                        <?php render_datatable([
                            _l('field_portal_column_lead'),
                            _l('field_portal_column_company'),
                            _l('field_portal_column_phone'),
                            _l('field_portal_column_current_status'),
                            _l('field_portal_column_telecaller'),
                            _l('field_portal_column_next_followup'),
                            _l('field_portal_column_priority'),
                            _l('field_portal_column_last_activity'),
                            _l('field_portal_column_actions'),
                        ], 'field-portal-followups'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script>
    $(function() {
        initDataTable('.table-field-portal-followups', admin_url + 'field_portal/followups_table', [], [], {
            filter: '#field-portal-followups-filter',
        }, [5, 'asc']);

        $('#field-portal-followups-tabs a').on('click', function(e) {
            e.preventDefault();
            $('#field-portal-followups-tabs li').removeClass('active');
            $(this).parent('li').addClass('active');
            $('#field-portal-followups-filter').val($(this).data('filter'));
            $('.table-field-portal-followups').DataTable().ajax.reload();
        });
    });
</script>
</body>
</html>
