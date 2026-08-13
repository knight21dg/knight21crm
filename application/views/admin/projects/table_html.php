<?php

defined('BASEPATH') or exit('No direct script access allowed');

$table_data = [
   _l('the_number_sign'),
   _l('project_name'),
    [
         'name'     => _l('project_customer'),
         'th_attrs' => ['class' => isset($client) ? 'not_visible' : ''],
    ],
   _l('tags'),
   _l('project_start_date'),
   _l('project_deadline'),
   _l('project_members'),
    [
        'name'     => _l('project_department'),
        'th_attrs' => ['class' => 'toggleable', 'id' => 'th-project-department'],
    ],
    [
        'name'     => _l('project_assigned_employee'),
        'th_attrs' => ['class' => 'toggleable', 'id' => 'th-project-assigned-employee'],
    ],
    [
        'name'     => _l('project_assigned_work'),
        'th_attrs' => ['class' => 'toggleable', 'id' => 'th-project-assigned-work'],
    ],
    [
        'name'     => _l('project_work_status'),
        'th_attrs' => ['class' => 'toggleable', 'id' => 'th-project-work-status'],
    ],
    [
        'name'     => _l('project_progress'),
        'th_attrs' => ['class' => 'toggleable', 'id' => 'th-project-progress'],
    ],
    [
        'name'     => _l('project_status_description'),
        'th_attrs' => ['class' => 'toggleable', 'id' => 'th-project-status-description'],
    ],
    [
        'name'     => _l('project_last_updated'),
        'th_attrs' => ['class' => 'toggleable', 'id' => 'th-project-last-updated'],
    ],
];

$custom_fields = get_custom_fields('projects', ['show_on_table' => 1]);
foreach ($custom_fields as $field) {
    array_push($table_data, [
     'name'     => $field['name'],
     'th_attrs' => ['data-type' => $field['type'], 'data-custom-field' => 1],
 ]);
}

$table_data = hooks()->apply_filters('projects_table_columns', $table_data);

render_datatable($table_data, isset($class) ?  $class : 'projects', ['number-index-1'], [
  'data-last-order-identifier' => 'projects',
  'data-default-order'         => get_table_last_order('projects'),
  'id'=>$table_id ?? 'projects',
]);
?>
<?php if (staff_can('edit', 'projects')) { ?>
<script>
    /**
     * Admin-only inline quick-edit for the Projects list (Department,
     * Assigned Employee, Assigned Work, Work Status). Mirrors the Customers
     * list's update_customer_field()/customer_mark_as() pattern exactly -
     * same endpoint-call shape, same "reload the table in place" behavior,
     * using the table's own real CSS class (no dt-table class exists in
     * this codebase - render_datatable() only ever outputs "table-{class}").
     * This file is shared by both the main Projects list (.table-projects)
     * and the Customer Profile "Projects" tab (.table-projects-single-client),
     * so both are checked here instead of hardcoding just one, like
     * Customers does (which only ever appears in a single context).
     */
    function reload_projects_table() {
        if ($.fn.DataTable.isDataTable('.table-projects')) {
            $('.table-projects').DataTable().ajax.reload(null, false);
        }
        if ($.fn.DataTable.isDataTable('.table-projects-single-client')) {
            $('.table-projects-single-client').DataTable().ajax.reload(null, false);
        }
    }

    function project_mark_as(field, value, id) {
        $.post(admin_url + 'projects/update_assignment_field/' + id, {
            field: field,
            value: value
        }).done(function() {
            reload_projects_table();
        });
    }

    function project_edit_assigned_work(el) {
        var $el = $(el);
        if ($el.find('input').length > 0) {
            return;
        }
        var id = $el.data('id');
        var currentValue = $el.data('value') || '';
        var $input = $('<input type="text" class="form-control input-sm">').val(currentValue);
        $el.empty().append($input);
        $input.trigger('focus');

        var save = function() {
            var newValue = $input.val();
            $.post(admin_url + 'projects/update_assignment_field/' + id, {
                field: 'assigned_work',
                value: newValue
            }).done(function() {
                reload_projects_table();
            });
        };

        $input.on('blur', save);
        $input.on('keydown', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                $input.trigger('blur');
            }
        });
    }

    function project_edit_deadline(el) {
        var $el = $(el);
        if ($el.find('input').length > 0) {
            return;
        }
        var id = $el.data('id');
        var currentValue = $el.data('value') || '';
        var $input = $('<input type="text" class="form-control input-sm datepicker">').val(currentValue);
        $el.empty().append($input);
        $input.trigger('focus');

        var save = function() {
            var newValue = $input.val();
            if (newValue === '') {
                reload_projects_table();
                return;
            }
            $.post(admin_url + 'projects/update_assignment_field/' + id, {
                field: 'deadline',
                value: newValue
            }).done(function() {
                reload_projects_table();
            });
        };

        $input.on('blur', save);
        $input.on('keydown', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                $input.trigger('blur');
            }
        });

        if (typeof init_datepicker === 'function') {
            init_datepicker();
        }
    }

    function project_edit_progress(el) {
        var $el = $(el);
        if ($el.find('select').length > 0) {
            return;
        }
        var id = $el.data('id');
        var currentValue = parseInt($el.data('value') || '0', 10);
        var $select = $('<select class="form-control input-sm">');
        var options = $el.data('options') ? $el.data('options').split(',') : [];
        for (var i = 0; i < options.length; i++) {
            var v = parseInt(options[i], 10);
            $select.append($('<option>').val(v).text(v + '%').prop('selected', v === currentValue));
        }
        $el.empty().append($select);
        $select.trigger('focus');

        var save = function() {
            var newValue = $select.val();
            $select.off('change change', save);
            $.post(admin_url + 'projects/update_assignment_field/' + id, {
                field: 'progress',
                value: newValue
            }).done(function() {
                reload_projects_table();
            });
        };

        $select.on('change', save);
        $select.on('blur', save);
    }
</script>
<?php } ?>
