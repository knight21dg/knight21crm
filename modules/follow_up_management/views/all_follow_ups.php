<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
// "Follow-up History" (Telecaller) is the exact same "All Follow-ups"
// page/route/controller/table id - only the title, filter toolbar, and
// column set are simplified for a plain Telecaller, same guard used
// throughout this module. Admin/Manager keep the original page byte-
// for-byte (below, in the else branch).
//
// Root-cause fix: this used to be "is_staff_member() && !is_admin() &&
// !staff_can('view_department', 'follow_up_management')" - true for ANY
// non-admin/non-manager staff who can reach this page at all (the
// controller only gates on the broad, commonly-granted native
// staff_can('view','leads') capability, not a department check), so any
// such staff member - regardless of actual department - was shown the
// simplified Telecaller UI here even if they've never been added to the
// Telecalling Business Department. Every other Telecaller check in this
// module already requires real Telecalling-department membership (see
// follow_up_management_is_telecalling_department_member()'s own
// docblock for the identical leak, fixed there); this view had drifted
// out of sync with that fix.
$is_telecaller = follow_up_management_is_telecalling_department_member() && !is_admin() && !staff_can('view_department', 'follow_up_management');

// Browser tab <title> comes from application/views/admin/includes/
// head.php's own $title, read from CI's cached view vars
// (Loader::_ci_cached_vars) - already populated by the controller's
// $this->load->view('all_follow_ups', $data) call with $data['title']
// = _l('all_followups') (unchanged, controller not touched). Simply
// reassigning the local $title variable here would NOT reach that cache
// (each nested load->view() extracts its own fresh scope from the
// cache, not from this file's local variables) - $this->load->vars()
// is CI's own documented mechanism for updating the cache from anywhere,
// including from inside a view, which is why it's used here instead of
// a second $title assignment.
if ($is_telecaller) {
    $CI = &get_instance();
    $CI->load->vars(['title' => _l('followup_history')]);
}
?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <?php if ($is_telecaller) { ?>
                        <h4 class="no-margin"><?= _l('followup_history'); ?></h4>
                        <p class="text-muted"><?= _l('followup_history_hint'); ?></p>
                        <hr class="hr-panel-separator" />

                        <div class="row followups-filters">
                            <div class="col-md-4">
                                <select id="followups-filter-outcome" class="selectpicker" data-width="100%"
                                    data-none-selected-text="<?= _l('followup_filter_all_outcomes'); ?>">
                                    <option value=""><?= _l('followup_filter_all_outcomes'); ?></option>
                                    <?php foreach (get_followup_outcomes() as $key => $label) { ?>
                                    <option value="<?= e($key); ?>"><?= e($label); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                        <div class="clearfix mtop15"></div>

                        <?php render_datatable([
                            _l('followup_column_lead_name'),
                            _l('case_column_company'),
                            _l('followup_column_contact_method'),
                            _l('followup_outcome_label'),
                            _l('followup_notes_label'),
                            _l('followup_date_label'),
                            _l('followup_column_last_interaction'),
                            _l('followup_column_total_interactions'),
                            _l('options'),
                        ], 'all-follow-ups'); ?>
                        <?php } else { ?>
                        <h4 class="no-margin">
                            <?= e($title); ?>
                            <?php if (!is_admin()) { ?><br />
                            <small><?= _l('followups_view_none_admin'); ?></small>
                            <?php } ?>
                        </h4>
                        <hr class="hr-panel-separator" />

                        <div class="row followups-filters">
                            <div class="col-md-3">
                                <select id="followups-filter-status" class="selectpicker" data-width="100%"
                                    data-none-selected-text="<?= _l('followup_filter_all_statuses'); ?>">
                                    <option value=""><?= _l('followup_filter_all_statuses'); ?></option>
                                    <?php foreach (get_followup_statuses() as $key => $label) { ?>
                                    <option value="<?= e($key); ?>"><?= e($label); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select id="followups-filter-staff" class="selectpicker" data-live-search="true"
                                    data-width="100%" data-none-selected-text="<?= _l('followup_filter_all_staff'); ?>">
                                    <option value=""><?= _l('followup_filter_all_staff'); ?></option>
                                    <?php foreach ($members as $member) { ?>
                                    <option value="<?= e($member['staffid']); ?>">
                                        <?= e($member['firstname'] . ' ' . $member['lastname']); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select id="followups-filter-department" class="selectpicker" data-width="100%"
                                    data-none-selected-text="<?= _l('followup_filter_all_departments'); ?>">
                                    <option value=""><?= _l('followup_filter_all_departments'); ?></option>
                                    <?php foreach ($departments as $department) { ?>
                                    <option value="<?= e($department['id']); ?>"><?= e($department['name']); ?>
                                    </option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select id="followups-filter-outcome" class="selectpicker" data-width="100%"
                                    data-none-selected-text="<?= _l('followup_filter_all_outcomes'); ?>">
                                    <option value=""><?= _l('followup_filter_all_outcomes'); ?></option>
                                    <?php foreach (get_followup_outcomes() as $key => $label) { ?>
                                    <option value="<?= e($key); ?>"><?= e($label); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                        <div class="clearfix mtop15"></div>

                        <?php render_datatable([
                            _l('followup_related'),
                            _l('followup_type_label'),
                            _l('followup_outcome_label'),
                            _l('followup_notes_label'),
                            _l('followup_date_label'),
                            _l('followup_next_follow_up_date_label'),
                            _l('followup_staff_label'),
                            _l('followup_status_label'),
                            _l('options'),
                        ], 'all-follow-ups'); ?>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script>
    $(function() {
        <?php if ($is_telecaller) { ?>
        // Simplified column set - Lead Name(0)/Company(1)/Contact Method(2)/
        // Outcome(3)/Notes(4)/Follow-up Date(5)/Last Interaction(6) are all
        // real $aColumns entries (sortable/searchable, native search box
        // covers Lead Name/Company/Contact Method/Outcome/Notes/Follow-up
        // Date for free). Total Interactions(7, a COUNT subquery) and
        // Options(8) are derived, appended after the loop - same reasoning
        // as every other derived column in this module (see
        // application/views/admin/tables/my_follow_ups.php's file-level
        // note), so only those two are excluded from sort/search here.
        initDataTable('.table-all-follow-ups', admin_url + 'follow_up_management/all_follow_ups_table', [7, 8], [7, 8], {
            outcome: '#followups-filter-outcome'
        }, [6, 'desc']);
        <?php } else { ?>
        initDataTable('.table-all-follow-ups', admin_url + 'follow_up_management/all_follow_ups_table', [8], [8], {
            status: '#followups-filter-status',
            staff_id: '#followups-filter-staff',
            department_id: '#followups-filter-department',
            outcome: '#followups-filter-outcome'
        }, [4, 'desc']);
        <?php } ?>

        $('.followups-filters select').on('changed.bs.select', function() {
            $('.table-all-follow-ups').DataTable().ajax.reload();
        });
    });
</script>
</body>
</html>
