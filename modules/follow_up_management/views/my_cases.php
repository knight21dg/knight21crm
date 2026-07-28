<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="tw-grid tw-grid-cols-2 sm:tw-grid-cols-3 lg:tw-grid-cols-5 tw-gap-3 tw-mb-4">
                    <div class="panel_s tw-mb-0">
                        <div class="panel-body text-center">
                            <h3 class="no-margin"><?= (int) $counts['my_open_cases']; ?></h3>
                            <small class="text-muted"><?= _l('card_my_open_cases'); ?></small>
                        </div>
                    </div>
                    <div class="panel_s tw-mb-0">
                        <div class="panel-body text-center">
                            <h3 class="no-margin"><?= (int) $counts['todays_followups']; ?></h3>
                            <small class="text-muted"><?= _l('card_todays_followups'); ?></small>
                        </div>
                    </div>
                    <div class="panel_s tw-mb-0">
                        <div class="panel-body text-center">
                            <h3 class="no-margin tw-text-red-600"><?= (int) $counts['overdue_cases']; ?></h3>
                            <small class="text-muted"><?= _l('card_overdue_cases'); ?></small>
                        </div>
                    </div>
                    <div class="panel_s tw-mb-0">
                        <div class="panel-body text-center">
                            <h3 class="no-margin tw-text-green-600"><?= (int) $counts['completed_today']; ?></h3>
                            <small class="text-muted"><?= _l('card_completed_today'); ?></small>
                        </div>
                    </div>
                    <div class="panel_s tw-mb-0">
                        <div class="panel-body text-center">
                            <h3 class="no-margin"><?= (int) $counts['high_priority']; ?></h3>
                            <small class="text-muted"><?= _l('card_high_priority'); ?></small>
                        </div>
                    </div>
                </div>

                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin"><?= e($title); ?></h4>
                        <hr class="hr-panel-separator" />

                        <div class="row my-cases-filters">
                            <div class="col-md-2">
                                <select id="my-cases-filter-priority" class="selectpicker" data-width="100%"
                                    data-none-selected-text="<?= _l('followup_filter_all_priorities'); ?>">
                                    <option value=""><?= _l('followup_filter_all_priorities'); ?></option>
                                    <?php foreach (get_followup_priorities() as $key => $label) { ?>
                                    <option value="<?= e($key); ?>" <?= $prefill['priority'] === $key ? 'selected' : ''; ?>><?= e($label); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select id="my-cases-filter-department" class="selectpicker" data-width="100%"
                                    data-none-selected-text="<?= _l('followup_filter_all_departments'); ?>">
                                    <option value=""><?= _l('followup_filter_all_departments'); ?></option>
                                    <?php foreach ($departments as $department) { ?>
                                    <option value="<?= e($department['id']); ?>"><?= e($department['name']); ?>
                                    </option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select id="my-cases-filter-staff" class="selectpicker" data-live-search="true"
                                    data-width="100%" data-none-selected-text="<?= _l('followup_filter_all_staff'); ?>">
                                    <option value=""><?= _l('followup_filter_all_staff'); ?></option>
                                    <?php foreach ($members as $member) { ?>
                                    <option value="<?= e($member['staffid']); ?>" <?= $prefill['staff_id'] == $member['staffid'] ? 'selected' : ''; ?>>
                                        <?= e($member['firstname'] . ' ' . $member['lastname']); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select id="my-cases-filter-case-status" class="selectpicker" data-width="100%"
                                    data-none-selected-text="<?= _l('followup_filter_all_case_statuses'); ?>">
                                    <option value=""><?= _l('followup_filter_all_case_statuses'); ?></option>
                                    <?php foreach (get_followup_case_statuses() as $key => $label) { ?>
                                    <option value="<?= e($key); ?>" <?= $prefill['case_status'] === $key ? 'selected' : ''; ?>><?= e($label); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <?= render_date_input('my_cases_date_from', 'followup_filter_date_from', ''); ?>
                            </div>
                            <div class="col-md-2">
                                <?= render_date_input('my_cases_date_to', 'followup_filter_date_to', ''); ?>
                            </div>
                        </div>
                        <div class="clearfix mtop15"></div>

                        <?php render_datatable([
                            _l('case_column_case_id'),
                            _l('case_column_lead_name'),
                            _l('case_column_company'),
                            _l('case_column_department'),
                            _l('case_column_priority'),
                            _l('case_column_case_status'),
                            _l('case_column_next_follow_up'),
                            _l('case_column_last_activity'),
                            _l('case_column_assigned_staff'),
                            _l('case_column_created_date'),
                            _l('options'),
                        ], 'my-cases'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script>
    $(function() {
        initDataTable('.table-my-cases', admin_url + 'follow_up_management/my_cases_table', [10], [10], {
            priority: '#my-cases-filter-priority',
            department_id: '#my-cases-filter-department',
            staff_id: '#my-cases-filter-staff',
            case_status: '#my-cases-filter-case-status',
            date_from: '#my_cases_date_from',
            date_to: '#my_cases_date_to'
        }, [0, 'desc']);

        $('.my-cases-filters select').on('changed.bs.select', function() {
            $('.table-my-cases').DataTable().ajax.reload();
        });

        $('#my_cases_date_from, #my_cases_date_to').on('changeDate', function() {
            $('.table-my-cases').DataTable().ajax.reload();
        });
    });
</script>
</body>
</html>
