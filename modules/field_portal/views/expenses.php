<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <?php field_portal_page_header(
                            _l('field_portal_daily_expenses'),
                            [
                                ['label' => _l('field_portal_breadcrumb_dashboard'), 'href' => admin_url('field_portal')],
                                ['label' => _l('field_portal_daily_expenses')],
                            ],
                            'table-field-portal-expenses',
                            null,
                            null
                        ); ?>
                        <p class="text-muted"><?= _l('field_portal_expenses_hint'); ?></p>

                        <!-- Summary Cards -->
                        <div class="row" id="expense-summary-cards">
                            <div class="col-md-3 col-sm-6">
                                <div class="staff-dash-kpi-card" style="background:linear-gradient(135deg, #60a5fa, #3b82f6);">
                                    <div class="staff-dash-kpi-value"><?= app_format_money($summary['today'], get_base_currency()->id); ?></div>
                                    <div class="staff-dash-kpi-label"><?= _l('field_portal_expenses_today'); ?></div>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <div class="staff-dash-kpi-card" style="background:linear-gradient(135deg, #34d399, #10b981);">
                                    <div class="staff-dash-kpi-value"><?= app_format_money($summary['month'], get_base_currency()->id); ?></div>
                                    <div class="staff-dash-kpi-label"><?= _l('field_portal_expenses_this_month'); ?></div>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <div class="staff-dash-kpi-card" style="background:linear-gradient(135deg, #fbbf24, #f59e0b);">
                                    <div class="staff-dash-kpi-value"><?= app_format_money($summary['year'], get_base_currency()->id); ?></div>
                                    <div class="staff-dash-kpi-label"><?= _l('field_portal_expenses_this_year'); ?></div>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <div class="staff-dash-kpi-card" style="background:linear-gradient(135deg, #a78bfa, #8b5cf6);">
                                    <div class="staff-dash-kpi-value"><?= app_format_money($summary['total'], get_base_currency()->id); ?></div>
                                    <div class="staff-dash-kpi-label"><?= _l('field_portal_expenses_total'); ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="clearfix mtop15"></div>

                        <!-- Filters -->
                        <div class="row">
                            <div class="col-md-2">
                                <select id="expense-filter-category" class="selectpicker" data-width="100%" data-none-selected-text="<?= _l('field_portal_expenses_all_categories'); ?>">
                                    <option value=""><?= _l('field_portal_expenses_all_categories'); ?></option>
                                    <?php foreach ($categories as $cat) { ?>
                                    <option value="<?= e($cat); ?>" <?= $default_category === $cat ? 'selected' : ''; ?>><?= e($cat); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <div class="input-group date">
                                    <input type="text" id="expense-filter-from" class="form-control datepicker" autocomplete="off" placeholder="<?= _l('field_portal_expenses_from_date'); ?>" value="<?= $default_from ? e(_d($default_from)) : ''; ?>" />
                                    <span class="input-group-addon"><i class="fa-regular fa-calendar"></i></span>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="input-group date">
                                    <input type="text" id="expense-filter-to" class="form-control datepicker" autocomplete="off" placeholder="<?= _l('field_portal_expenses_to_date'); ?>" value="<?= $default_to ? e(_d($default_to)) : ''; ?>" />
                                    <span class="input-group-addon"><i class="fa-regular fa-calendar"></i></span>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <select id="expense-filter-month" class="selectpicker" data-width="100%" data-none-selected-text="<?= _l('field_portal_expenses_all_months'); ?>">
                                    <option value=""><?= _l('field_portal_expenses_all_months'); ?></option>
                                    <?php for ($m = 1; $m <= 12; $m++) { ?>
                                    <option value="<?= $m; ?>" <?= $default_month == $m ? 'selected' : ''; ?>><?= date('F', mktime(0, 0, 0, $m, 1)); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select id="expense-filter-year" class="selectpicker" data-width="100%" data-none-selected-text="<?= _l('field_portal_expenses_all_years'); ?>">
                                    <option value=""><?= _l('field_portal_expenses_all_years'); ?></option>
                                    <?php foreach ($years as $yr) { ?>
                                    <option value="<?= (int) $yr; ?>" <?= $default_year == $yr ? 'selected' : ''; ?>><?= (int) $yr; ?></option>
                                    <?php } ?>
                                    <option value="<?= date('Y'); ?>" <?= $default_year == date('Y') ? 'selected' : ''; ?>><?= date('Y'); ?></option>
                                </select>
                            </div>
                            <div class="col-md-2 text-right">
                                <a href="#" class="btn btn-primary btn-sm" id="expense-add-day-btn"><i class="fa-solid fa-plus"></i> <?= _l('field_portal_expenses_add'); ?></a>
                            </div>
                        </div>
                        <div class="clearfix mtop15"></div>

                        <!-- DataTable -->
                        <?php render_datatable([
                            _l('field_portal_expenses_col_date'),
                            _l('field_portal_expenses_col_lead'),
                            _l('field_portal_expenses_col_categories'),
                            _l('field_portal_expenses_col_total_amount'),
                            _l('field_portal_expenses_attachments'),
                            _l('field_portal_column_created_time'),
                            _l('field_portal_column_actions'),
                        ], 'field-portal-expenses'); ?>

                        <!-- Bottom Summary -->
                        <div class="row mtop15" id="expense-bottom-summary">
                            <div class="col-md-12">
                                <div class="panel_s">
                                    <div class="panel-body">
                                        <h5 class="no-margin tw-mb-3"><?= _l('field_portal_expenses_summary'); ?></h5>
                                        <div class="table-responsive">
                                            <table class="table table-bordered">
                                                <thead>
                                                    <tr>
                                                        <?php foreach ($categories as $cat) { ?>
                                                        <th><?= e($cat); ?></th>
                                                        <?php } ?>
                                                        <th><?= _l('field_portal_expenses_grand_total'); ?></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <?php foreach ($categories as $cat) { ?>
                                                        <td class="expense-cat-total" data-category="<?= e($cat); ?>"><?= app_format_money($category_totals['categories'][$cat] ?? 0, get_base_currency()->id); ?></td>
                                                        <?php } ?>
                                                        <td class="expense-grand-total"><strong><?= app_format_money($category_totals['grand_total'], get_base_currency()->id); ?></strong></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Day-based Add / Edit Modal -->
<div class="modal fade" id="expense-day-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title" id="expense-day-modal-title"><?= _l('field_portal_expenses_add_day'); ?></h4>
            </div>
            <form id="expense-day-form">
                <input type="hidden" name="original_date" id="expense-original-date" value="" />
                <input type="hidden" name="original_lead_id" id="expense-original-lead-id" value="" />
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="control-label"><?= _l('field_portal_expenses_col_date'); ?></label>
                                <div class="input-group date">
                                    <input type="text" id="expense_day_date" name="expense_date" class="form-control datepicker" value="<?= e(_d(date('Y-m-d'))); ?>" autocomplete="off" />
                                    <span class="input-group-addon"><i class="fa-regular fa-calendar"></i></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="control-label"><?= _l('field_portal_expenses_col_lead'); ?></label>
                                <select name="lead_id" id="expense-day-lead-id" class="selectpicker" data-width="100%" data-none-selected-text="<?= _l('field_portal_expenses_no_lead'); ?>">
                                    <option value=""><?= _l('field_portal_expenses_no_lead'); ?></option>
                                    <?php foreach ($leads as $lead) { ?>
                                    <option value="<?= (int) $lead['id']; ?>"><?= e($lead['name']); ?> (<?= e($lead['company'] ?? '-'); ?>)</option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <hr />
                    <h5><?= _l('field_portal_expenses_day_amounts'); ?></h5>
                    <div class="row">
                        <?php $col = 0; foreach ($categories as $cat) :
                            $input_name = 'amount_' . strtolower(str_replace([' ', '/'], '_', $cat));
                        ?>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="control-label"><?= e($cat); ?></label>
                                <input type="number" name="<?= $input_name; ?>" class="form-control expense-category-input" data-category="<?= e($cat); ?>" step="0.01" min="0" value="" placeholder="0.00" />
                            </div>
                        </div>
                        <?php $col++; if ($col % 3 === 0) { echo '</div><div class="row">'; } ?>
                        <?php endforeach; ?>
                    </div>
                    <hr />
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label class="control-label"><?= _l('field_portal_field_remarks'); ?></label>
                                <textarea name="remarks" id="expense-day-remarks" class="form-control" rows="2" placeholder="<?= _l('field_portal_expenses_remarks_placeholder'); ?>"></textarea>
                            </div>
                        </div>
                    </div>

                    <hr />
                    <h5><?= _l('field_portal_expenses_attachments'); ?></h5>

                    <!-- Existing attachments list (edit mode only) -->
                    <div id="expense-existing-attachments" class="hide"></div>

                    <!-- New attachment rows -->
                    <div id="expense-attachment-rows"></div>
                    <div class="form-group">
                        <a href="#" class="btn btn-default btn-sm" id="expense-add-attachment-btn"><i class="fa-solid fa-plus"></i> <?= _l('field_portal_expenses_add_attachment'); ?></a>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?= _l('field_portal_cancel'); ?></button>
                    <button type="submit" class="btn btn-primary" id="expense-day-submit-btn"><?= _l('field_portal_expenses_save_day'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Modal -->
<div class="modal fade" id="expense-view-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title"><?= _l('field_portal_expenses_view'); ?></h4>
            </div>
            <div class="modal-body" id="expense-view-body">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?= _l('field_portal_cancel'); ?></button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="expense-delete-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title"><?= _l('field_portal_confirm_delete'); ?></h4>
            </div>
            <div class="modal-body">
                <p><?= _l('field_portal_expenses_delete_confirm'); ?></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?= _l('field_portal_cancel'); ?></button>
                <a href="#" class="btn btn-danger" id="expense-delete-confirm-btn"><?= _l('field_portal_delete'); ?></a>
            </div>
        </div>
    </div>
</div>

<!-- Attachment View Modal -->
<div class="modal fade" id="expense-attachments-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title"><?= _l('field_portal_expenses_attachments'); ?></h4>
            </div>
            <div class="modal-body" id="expense-attachments-body">
                <p class="text-muted"><?= _l('field_portal_expenses_attachments_loading'); ?></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?= _l('field_portal_close'); ?></button>
            </div>
        </div>
    </div>
</div>

<!-- Attachment Edit Modal (for title/description) -->
<div class="modal fade" id="expense-attachment-edit-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title"><?= _l('field_portal_expenses_edit_attachment'); ?></h4>
            </div>
            <form id="expense-attachment-edit-form">
                <input type="hidden" name="id" id="attachment-edit-id" value="" />
                <div class="modal-body">
                    <div class="form-group">
                        <label class="control-label"><?= _l('field_portal_expenses_attachment_title'); ?></label>
                        <input type="text" name="title" id="attachment-edit-title" class="form-control" />
                    </div>
                    <div class="form-group">
                        <label class="control-label"><?= _l('field_portal_expenses_attachment_description'); ?></label>
                        <textarea name="description" id="attachment-edit-description" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?= _l('field_portal_cancel'); ?></button>
                    <button type="submit" class="btn btn-primary"><?= _l('field_portal_expenses_save'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php init_tail(); ?>
<script>
    $(function() {
        // DataTable with server-side params matching the filter selectors.
        initDataTable('.table-field-portal-expenses', admin_url + 'field_portal/expenses_table', [], [], {
            category: '#expense-filter-category',
            from: '#expense-filter-from',
            to: '#expense-filter-to',
            month: '#expense-filter-month',
            year: '#expense-filter-year',
        }, [0, 'desc']);

        // Reload summary cards when any filter changes.
        function reloadExpenseSummary() {
            var data = {
                category: $('#expense-filter-category').val() || '',
                from: $('#expense-filter-from').val() || '',
                to: $('#expense-filter-to').val() || '',
                month: $('#expense-filter-month').val() || '',
                year: $('#expense-filter-year').val() || '',
            };
            $.post(admin_url + 'field_portal/expenses_get_summary', data, function(res) {
                if (!res || !res.summary) return;
                var currency = '<?= e(get_base_currency()->symbol ?? ''); ?>';

                function fmt(n) {
                    return currency + parseFloat(n).toFixed(2);
                }

                var $cards = $('#expense-summary-cards');
                $cards.find('.staff-dash-kpi-value').eq(0).text(fmt(res.summary.today));
                $cards.find('.staff-dash-kpi-value').eq(1).text(fmt(res.summary.month));
                $cards.find('.staff-dash-kpi-value').eq(2).text(fmt(res.summary.year));
                $cards.find('.staff-dash-kpi-value').eq(3).text(fmt(res.summary.total));

                if (res.category_totals) {
                    $('.expense-cat-total').each(function() {
                        var cat = $(this).data('category');
                        $(this).text(fmt(res.category_totals.categories[cat] || 0));
                    });
                    $('.expense-grand-total').html('<strong>' + fmt(res.category_totals.grand_total) + '</strong>');
                }
            }, 'json');
        }

        $('#expense-filter-category, #expense-filter-month, #expense-filter-year').on('changed.bs.select', function() {
            $('.table-field-portal-expenses').DataTable().ajax.reload();
            reloadExpenseSummary();
        });

        $('#expense-filter-from, #expense-filter-to').on('change', function() {
            $('.table-field-portal-expenses').DataTable().ajax.reload();
            reloadExpenseSummary();
        });

        // Shared attachments-table renderer - used by both the standalone
        // Attachments modal and the day breakdown View modal, so there is
        // one HTML shape for "a list of attachments", not two.
        function buildAttachmentsTableHtml(attachments) {
            if (!attachments || !attachments.length) {
                return '<p class="text-muted"><?= _l('field_portal_no_attachments'); ?></p>';
            }
            var html = '<div class="table-responsive"><table class="table table-bordered"><thead><tr>' +
                '<th><?= _l('field_portal_expenses_attachment_file'); ?></th>' +
                '<th><?= _l('field_portal_expenses_attachment_title'); ?></th>' +
                '<th><?= _l('field_portal_expenses_attachment_description'); ?></th>' +
                '<th><?= _l('field_portal_expenses_col_created'); ?></th>' +
                '</tr></thead><tbody>';
            $.each(attachments, function(i, a) {
                var fileUrl = '<?= site_url('uploads/field_portal/expenses/'); ?>' + a.file_name;
                var ext = a.file_name.split('.').pop().toLowerCase();
                var isImg = (ext === 'jpg' || ext === 'jpeg' || ext === 'png');
                var preview = isImg
                    ? '<a href="' + fileUrl + '" target="_blank"><img src="' + fileUrl + '" class="img img-responsive" style="max-height:80px;" /></a>'
                    : '<a href="' + fileUrl + '" target="_blank" class="btn btn-default btn-sm"><i class="fa-solid fa-file-pdf"></i> ' + a.original_name + '</a>';
                var created = a.created_at ? a.created_at : '';
                html += '<tr>';
                html += '<td>' + preview + '</td>';
                html += '<td>' + (a.title || '-') + '</td>';
                html += '<td>' + (a.description || '-') + '</td>';
                html += '<td>' + created + '</td></tr>';
            });
            html += '</tbody></table></div>';
            return html;
        }

        // Attachment counter
        var attachmentIndex = 0;

        function addAttachmentRow(file, title, desc) {
            var html = '<div class="attachment-row form-group" id="att-row-' + attachmentIndex + '">';
            html += '<div class="row">';
            html += '<div class="col-md-4">';
            html += '<input type="file" name="attachment_file[]" class="form-control" accept=".jpg,.jpeg,.png,.pdf"';
            if (file) html += ' data-existing="' + file + '"';
            html += ' /></div>';
            html += '<div class="col-md-3">';
            html += '<input type="text" name="attachment_title[]" class="form-control" placeholder="<?= _l('field_portal_expenses_attachment_title'); ?>" value="' + (title || '') + '" /></div>';
            html += '<div class="col-md-4">';
            html += '<input type="text" name="attachment_description[]" class="form-control" placeholder="<?= _l('field_portal_expenses_attachment_description'); ?>" value="' + (desc || '') + '" /></div>';
            html += '<div class="col-md-1">';
            html += '<a href="#" class="btn btn-danger btn-sm remove-attachment-row" data-row="' + attachmentIndex + '"><i class="fa-solid fa-times"></i></a>';
            html += '</div></div></div>';
            $('#expense-attachment-rows').append(html);
            attachmentIndex++;
        }

        $('#expense-add-attachment-btn').on('click', function(e) {
            e.preventDefault();
            addAttachmentRow();
        });

        $(document).on('click', '.remove-attachment-row', function(e) {
            e.preventDefault();
            $('#att-row-' + $(this).data('row')).remove();
        });

        // Load existing attachments for edit mode.
        function loadExistingAttachments(date) {
            $.post(admin_url + 'field_portal/expenses_get_attachments', {date: date}, function(res) {
                var $container = $('#expense-existing-attachments');
                $container.empty().addClass('hide');
                if (!res.success || !res.attachments || !res.attachments.length) return;
                var html = '<div class="table-responsive"><table class="table table-bordered"><thead><tr>' +
                    '<th><?= _l('field_portal_expenses_attachment_file'); ?></th>' +
                    '<th><?= _l('field_portal_expenses_attachment_title'); ?></th>' +
                    '<th><?= _l('field_portal_expenses_attachment_description'); ?></th>' +
                    '<th><?= _l('field_portal_column_actions'); ?></th>' +
                    '</tr></thead><tbody>';
                $.each(res.attachments, function(i, a) {
                    var fileUrl = '<?= site_url('uploads/field_portal/expenses/'); ?>' + a.file_name;
                    var ext = a.file_name.split('.').pop().toLowerCase();
                    var isImg = (ext === 'jpg' || ext === 'jpeg' || ext === 'png');
                    var preview = isImg
                        ? '<a href="' + fileUrl + '" target="_blank"><img src="' + fileUrl + '" class="img img-responsive" style="max-height:60px;" /></a>'
                        : '<a href="' + fileUrl + '" target="_blank" class="btn btn-default btn-sm"><i class="fa-solid fa-file-pdf"></i> ' + a.original_name + '</a>';
                    html += '<tr>';
                    html += '<td>' + preview + '</td>';
                    html += '<td>' + (a.title || '-') + '</td>';
                    html += '<td>' + (a.description || '-') + '</td>';
                    html += '<td>';
                    html += '<a href="#" class="btn btn-default btn-sm att-edit" data-id="' + a.id + '" data-title="' + $('<span>').text(a.title || '').html() + '" data-description="' + $('<span>').text(a.description || '').html() + '"><i class="fa-solid fa-pencil"></i></a> ';
                    html += '<a href="#" class="btn btn-danger btn-sm att-delete" data-id="' + a.id + '"><i class="fa-solid fa-trash"></i></a>';
                    html += '</td></tr>';
                });
                html += '</tbody></table></div>';
                $container.html(html).removeClass('hide');
            }, 'json');
        }

        // Add Daily Expenses: reset to a blank form, then show. This is a
        // dedicated click handler (not data-toggle="modal") because the
        // modal used to also reset-on-show unconditionally, via a handler
        // bound to the same 'show.bs.modal' event .modal('show') fires
        // synchronously - which silently wiped out whatever the Edit
        // handler (below) had just populated the instant before the modal
        // became visible, so "Edit Daily Expenses" never actually showed
        // the existing data. Add and Edit now each fully prepare the
        // form's state themselves before calling .modal('show') - nothing
        // implicit runs on the shared 'show.bs.modal' event anymore.
        $('#expense-add-day-btn').on('click', function(e) {
            e.preventDefault();
            $('#expense-day-form')[0].reset();
            $('#expense-original-date').val('');
            $('#expense-original-lead-id').val('');
            $('#expense-day-modal-title').text('<?= _l('field_portal_expenses_add_day'); ?>');
            $('#expense-day-submit-btn').text('<?= _l('field_portal_expenses_save_day'); ?>');
            $('#expense-attachment-rows').empty();
            $('#expense-existing-attachments').empty().addClass('hide');
            attachmentIndex = 0;
            $('.selectpicker').selectpicker('refresh');
            $('#expense-day-modal').modal('show');
        });

        $('#expense-day-modal').on('shown.bs.modal', function() {
            appDatepicker({ element_date: $('#expense_day_date') });
        });

        // Submit day-based form.
        $('#expense-day-form').on('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(this);
            if (typeof(csrfData) !== 'undefined') {
                formData.append(csrfData['token_name'], csrfData['hash']);
            }
            $.ajax({
                url: admin_url + 'field_portal/expenses_save_day',
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        alert_float('success', res.message);
                        $('#expense-day-modal').modal('hide');
                        $('.table-field-portal-expenses').DataTable().ajax.reload();
                        reloadExpenseSummary();
                    } else {
                        alert_float('danger', res.message);
                    }
                }
            });
        });

        // Edit day: load grouped data for the date.
        $('.table-field-portal-expenses').on('click', '.expense-edit-day', function(e) {
            e.preventDefault();
            var date = $(this).data('date');
            var lead = $(this).data('lead') || 0;
            $.get(admin_url + 'field_portal/expenses_edit_day/' + date + '/' + lead, function(res) {
                if (!res.success) {
                    alert_float('danger', res.message);
                    return;
                }
                $('#expense-original-date').val(res.date);
                $('#expense-original-lead-id').val(res.lead_id || '');
                $('#expense-day-modal-title').text('<?= _l('field_portal_expenses_edit_day'); ?>');
                $('#expense-day-submit-btn').text('<?= _l('field_portal_expenses_update_day'); ?>');
                $('#expense_day_date').val(res.date);
                $('#expense-day-lead-id').selectpicker('val', res.lead_id || '');
                $('#expense-day-remarks').val(res.remarks || '');

                $('.expense-category-input').each(function() {
                    var cat = $(this).data('category');
                    $(this).val(res.categories[cat] || '');
                });

                $('.selectpicker').selectpicker('refresh');
                loadExistingAttachments(date);
                $('#expense-day-modal').modal('show');
            }, 'json');
        });

        // Attachment: view list.
        $('.table-field-portal-expenses').on('click', '.expense-attachments-view', function(e) {
            e.preventDefault();
            var date = $(this).data('date');
            $.post(admin_url + 'field_portal/expenses_get_attachments', {date: date}, function(res) {
                $('#expense-attachments-body').html(buildAttachmentsTableHtml(res.success ? res.attachments : []));
                $('#expense-attachments-modal').modal('show');
            }, 'json');
        });

        // Attachment: edit title/description.
        $(document).on('click', '.att-edit', function(e) {
            e.preventDefault();
            $('#attachment-edit-id').val($(this).data('id'));
            $('#attachment-edit-title').val($(this).data('title'));
            $('#attachment-edit-description').val($(this).data('description'));
            $('#expense-attachment-edit-modal').modal('show');
        });

        $('#expense-attachment-edit-form').on('submit', function(e) {
            e.preventDefault();
            var formData = $(this).serialize();
            if (typeof(csrfData) !== 'undefined') {
                formData += '&' + csrfData['token_name'] + '=' + csrfData['hash'];
            }
            $.post(admin_url + 'field_portal/expenses_update_attachment', formData, function(res) {
                if (res.success) {
                    alert_float('success', '<?= _l('field_portal_expenses_attachment_updated'); ?>');
                    $('#expense-attachment-edit-modal').modal('hide');
                    // Reload existing attachments list.
                    var date = $('#expense-original-date').val();
                    if (date) loadExistingAttachments(date);
                }
            }, 'json');
        });

        // Attachment: delete.
        $(document).on('click', '.att-delete', function(e) {
            e.preventDefault();
            var id = $(this).data('id');
            var btn = $(this);
            $.post(admin_url + 'field_portal/expenses_delete_attachment/' + id, function(res) {
                if (res.success) {
                    alert_float('success', '<?= _l('field_portal_expenses_attachment_deleted'); ?>');
                    btn.closest('tr').remove();
                    $('.table-field-portal-expenses').DataTable().ajax.reload();
                    reloadExpenseSummary();
                }
            }, 'json');
        });

        // View a whole daily entry - breakdown of every category, grand
        // total, remarks and attachments, all for the one (date, lead)
        // group behind the clicked row.
        $('.table-field-portal-expenses').on('click', '.expense-view-day', function(e) {
            e.preventDefault();
            var date = $(this).data('date');
            var lead = $(this).data('lead') || 0;
            $.get(admin_url + 'field_portal/expenses_edit_day/' + date + '/' + lead, function(res) {
                if (!res.success) {
                    alert_float('danger', res.message);
                    return;
                }

                var html = '<table class="table table-bordered">';
                html += '<tr><th><?= _l('field_portal_expenses_col_date'); ?></th><td>' + res.date + '</td></tr>';
                html += '<tr><th><?= _l('field_portal_expenses_col_lead'); ?></th><td>' + (res.lead_name || '-') + '</td></tr>';
                html += '</table>';

                html += '<h5><?= _l('field_portal_expenses_breakdown'); ?></h5>';
                html += '<table class="table table-bordered"><thead><tr><th><?= _l('field_portal_expenses_col_category'); ?></th><th><?= _l('field_portal_expenses_col_amount'); ?></th></tr></thead><tbody>';
                $.each(res.categories, function(cat, amount) {
                    amount = parseFloat(amount);
                    if (amount > 0) {
                        html += '<tr><td>' + cat + '</td><td>' + amount.toFixed(2) + '</td></tr>';
                    }
                });
                html += '<tr><th><?= _l('field_portal_expenses_grand_total'); ?></th><th>' + parseFloat(res.grand_total).toFixed(2) + '</th></tr>';
                html += '</tbody></table>';

                html += '<h5><?= _l('field_portal_field_remarks'); ?></h5><p>' + (res.remarks || '-') + '</p>';

                html += '<h5><?= _l('field_portal_expenses_attachments'); ?></h5>';
                html += '<div id="expense-view-attachments"><p class="text-muted"><?= _l('field_portal_expenses_attachments_loading'); ?></p></div>';

                $('#expense-view-body').html(html);
                $('#expense-view-modal').modal('show');

                $.post(admin_url + 'field_portal/expenses_get_attachments', {date: date}, function(attRes) {
                    $('#expense-view-attachments').html(buildAttachmentsTableHtml(attRes.success ? attRes.attachments : []));
                }, 'json');
            }, 'json');
        });

        // Delete a whole daily entry - every category record for the
        // (date, lead) group, plus every attachment for that date.
        $('.table-field-portal-expenses').on('click', '.expense-delete-day', function(e) {
            e.preventDefault();
            $('#expense-delete-confirm-btn').data('date', $(this).data('date')).data('lead', $(this).data('lead') || 0);
            $('#expense-delete-modal').modal('show');
        });

        $('#expense-delete-confirm-btn').on('click', function(e) {
            e.preventDefault();
            var date = $(this).data('date');
            var lead = $(this).data('lead') || 0;
            $.post(admin_url + 'field_portal/expenses_delete_day/' + date + '/' + lead, function(res) {
                if (res.success) {
                    alert_float('success', res.message);
                    $('#expense-delete-modal').modal('hide');
                    $('.table-field-portal-expenses').DataTable().ajax.reload();
                    reloadExpenseSummary();
                } else {
                    alert_float('danger', res.message);
                }
            }, 'json');
        });
    });
</script>
</body>
</html>
