<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <?php field_portal_page_header(
                            _l('field_portal_customers'),
                            [
                                ['label' => _l('field_portal_breadcrumb_dashboard'), 'href' => admin_url('field_portal')],
                                ['label' => _l('field_portal_customers')],
                            ]
                        ); ?>
                        <p class="text-muted"><?= _l('field_portal_customers_hint'); ?></p>

                        <?php
                        // KPI cards - Total/Today/This Month/This Year, always
                        // describing this Field Executive's WHOLE converted-
                        // customer set (see get_customer_conversion_summary()'s
                        // doc comment for why they aren't narrowed by the date
                        // filter below them). Rendered once here with their
                        // initial values so they aren't blank before JS runs;
                        // refreshed via AJAX after every table redraw.
                        $conversion_cards = [
                            ['kpi' => 'total',      'label' => _l('field_portal_customers_kpi_total'),      'count' => $conversion_summary['total']],
                            ['kpi' => 'today',      'label' => _l('field_portal_customers_kpi_today'),      'count' => $conversion_summary['today']],
                            ['kpi' => 'this_month', 'label' => _l('field_portal_customers_kpi_this_month'), 'count' => $conversion_summary['this_month']],
                            ['kpi' => 'this_year',  'label' => _l('field_portal_customers_kpi_this_year'),  'count' => $conversion_summary['this_year']],
                        ];
                        ?>
                        <div class="tw-grid tw-grid-cols-2 md:tw-grid-cols-4 tw-gap-2 tw-mb-3" id="field-portal-customers-kpi-cards">
                            <?php foreach ($conversion_cards as $card) { ?>
                            <div class="tw-border-neutral-300/80 tw-shadow-sm tw-text-sm tw-border tw-border-solid tw-rounded-lg tw-px-4 tw-py-3 tw-flex tw-items-center tw-bg-white">
                                <span class="tw-font-semibold tw-mr-1 rtl:tw-ml-1" data-kpi="<?= $card['kpi']; ?>"><?= (int) $card['count']; ?></span>
                                <span class="text-dark tw-truncate"><?= $card['label']; ?></span>
                            </div>
                            <?php } ?>
                        </div>

                        <?php
                        // Date filter - tblclients.converted_at exclusively
                        // (never dateadded/tblleads). The quick-filter buttons
                        // just pre-fill the two always-visible From/To inputs
                        // client-side (today's date, this month's bounds,
                        // etc.) - there's only ever one code path server-side
                        // (Field_portal::customers_table()), reading whatever
                        // concrete dates end up in those two fields.
                        ?>
                        <div class="tw-flex tw-flex-wrap tw-items-end tw-gap-2 tw-mb-1">
                            <div class="btn-group" role="group" id="field-portal-customers-quick-filters">
                                <button type="button" class="btn btn-default btn-sm" data-quick="today"><?= _l('today'); ?></button>
                                <button type="button" class="btn btn-default btn-sm" data-quick="yesterday"><?= _l('yesterday'); ?></button>
                                <button type="button" class="btn btn-default btn-sm" data-quick="this_week"><?= _l('this_week'); ?></button>
                                <button type="button" class="btn btn-default btn-sm" data-quick="this_month"><?= _l('this_month'); ?></button>
                                <button type="button" class="btn btn-default btn-sm" data-quick="this_year"><?= _l('this_year'); ?></button>
                                <button type="button" class="btn btn-default btn-sm" data-quick="custom"><?= _l('field_portal_customers_custom_range'); ?></button>
                            </div>
                            <div class="tw-flex tw-items-end tw-gap-2">
                                <?= render_date_input('field-portal-customers-date-from', _l('field_portal_customers_date_from')); ?>
                                <?= render_date_input('field-portal-customers-date-to', _l('field_portal_customers_date_to')); ?>
                            </div>
                            <button type="button" class="btn btn-link btn-sm" id="field-portal-customers-clear-dates"><?= _l('clear'); ?></button>
                        </div>
                        <div class="tw-text-sm text-muted tw-mb-3" id="field-portal-customers-count"></div>

                        <?php render_datatable([
                            _l('field_portal_column_customer'),
                            _l('field_portal_column_phone'),
                            _l('field_portal_column_address'),
                            _l('field_portal_column_current_status'),
                            _l('field_portal_column_created_from_lead'),
                            _l('field_portal_column_converted_date'),
                            _l('field_portal_column_actions'),
                        ], 'field-portal-customers'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script>
    $(function() {
        initDataTable('.table-field-portal-customers', admin_url + 'field_portal/customers_table', [], [], {
                converted_date_from: '#field-portal-customers-date-from',
                converted_date_to: '#field-portal-customers-date-to'
            },
            [5, 'desc']
        );

        var $table = $('.table-field-portal-customers');

        function reload_customers_report() {
            // Page length forced to "All" so the existing native Export
            // (CSV/Excel/Print) buttons, which only ever export whatever is
            // currently rendered, automatically export the complete
            // filtered set rather than just one page - same mechanism the
            // native Admin Customers Conversion Report uses, no new export
            // endpoint needed.
            $table.DataTable().page.len(-1).ajax.reload();
        }

        function iso_date(d) {
            var mm = ('0' + (d.getMonth() + 1)).slice(-2);
            var dd = ('0' + d.getDate()).slice(-2);
            return d.getFullYear() + '-' + mm + '-' + dd;
        }

        $('#field-portal-customers-quick-filters').on('click', 'button', function() {
            $('#field-portal-customers-quick-filters button').removeClass('active');
            $(this).addClass('active');

            var quick = $(this).data('quick');

            // "Custom Range" just clears the active state above and leaves
            // the two date inputs for the user to fill in manually - they're
            // already always visible, there's nothing else to reveal.
            if (quick === 'custom') {
                return;
            }

            var today = new Date();
            var from, to;

            if (quick === 'today') {
                from = to = today;
            } else if (quick === 'yesterday') {
                from = to = new Date(today.getFullYear(), today.getMonth(), today.getDate() - 1);
            } else if (quick === 'this_week') {
                var isoDay = today.getDay() === 0 ? 7 : today.getDay();
                from = new Date(today.getFullYear(), today.getMonth(), today.getDate() - isoDay + 1);
                to = new Date(today.getFullYear(), today.getMonth(), today.getDate() - isoDay + 7);
            } else if (quick === 'this_month') {
                from = new Date(today.getFullYear(), today.getMonth(), 1);
                to = new Date(today.getFullYear(), today.getMonth() + 1, 0);
            } else if (quick === 'this_year') {
                from = new Date(today.getFullYear(), 0, 1);
                to = new Date(today.getFullYear(), 11, 31);
            }

            $('#field-portal-customers-date-from').val(iso_date(from));
            $('#field-portal-customers-date-to').val(iso_date(to));
            reload_customers_report();
        });

        $('#field-portal-customers-date-from, #field-portal-customers-date-to').on('change', function() {
            $('#field-portal-customers-quick-filters button').removeClass('active');
            reload_customers_report();
        });

        $('#field-portal-customers-clear-dates').on('click', function() {
            $('#field-portal-customers-date-from').val('');
            $('#field-portal-customers-date-to').val('');
            $('#field-portal-customers-quick-filters button').removeClass('active');
            reload_customers_report();
        });

        // "Showing X Customers" / "Showing X of Y Customers" - read
        // directly off the SAME ajax response the table already fetched
        // (recordsTotal/recordsDisplay both already include the current
        // date filter, since it's applied at the SQL WHERE level - they
        // only diverge once the DataTable's own search box also narrows
        // things further) - no separate query. KPI cards are refreshed
        // here too, via Field_portal::customers_summary() - always an
        // independent, unfiltered snapshot (see that method's doc
        // comment), just kept fresh in case new conversions land during
        // the session.
        //
        // Bound via delegation from the panel body (not directly on
        // $table) - DataTables replaces the <table> element itself during
        // initialization, which would silently detach a directly-bound
        // handler from the live element; delegation survives that.
        $('.panel-body').on('draw.dt', '.table-field-portal-customers', function() {
            var info = $(this).DataTable().page.info();
            var $count = $('#field-portal-customers-count');

            if (info.recordsDisplay < info.recordsTotal) {
                $count.text('<?= _l('field_portal_customers_showing_of'); ?>'
                    .replace('__SHOWN__', info.recordsDisplay)
                    .replace('__TOTAL__', info.recordsTotal));
            } else {
                $count.text('<?= _l('field_portal_customers_showing'); ?>'.replace('__COUNT__', info.recordsDisplay));
            }

            $.post(admin_url + 'field_portal/customers_summary', {}, function(response) {
                var data = typeof response === 'string' ? JSON.parse(response) : response;
                $('#field-portal-customers-kpi-cards [data-kpi="total"]').text(data.total);
                $('#field-portal-customers-kpi-cards [data-kpi="today"]').text(data.today);
                $('#field-portal-customers-kpi-cards [data-kpi="this_month"]').text(data.this_month);
                $('#field-portal-customers-kpi-cards [data-kpi="this_year"]').text(data.this_year);
            });
        });
    });
</script>
</body>
</html>
