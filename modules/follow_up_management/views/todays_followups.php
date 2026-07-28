<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="tw-grid tw-grid-cols-1 sm:tw-grid-cols-3 tw-gap-3 tw-mb-4">
                    <div class="panel_s tw-mb-0">
                        <div class="panel-body text-center">
                            <h3 class="no-margin tw-text-red-600"><?= count($groups['overdue']); ?></h3>
                            <small class="text-muted"><?= _l('section_overdue'); ?></small>
                        </div>
                    </div>
                    <div class="panel_s tw-mb-0">
                        <div class="panel-body text-center">
                            <h3 class="no-margin"><?= count($groups['due_today']); ?></h3>
                            <small class="text-muted"><?= _l('section_due_today'); ?></small>
                        </div>
                    </div>
                    <div class="panel_s tw-mb-0">
                        <div class="panel-body text-center">
                            <h3 class="no-margin tw-text-green-600"><?= count($groups['completed_today']); ?></h3>
                            <small class="text-muted"><?= _l('section_completed_today'); ?></small>
                        </div>
                    </div>
                </div>

                <?php
                $sections = [
                    'overdue'         => ['label' => _l('section_overdue'), 'class' => 'danger', 'show_time' => true],
                    'due_today'       => ['label' => _l('section_due_today'), 'class' => 'info', 'show_time' => true],
                    'completed_today' => ['label' => _l('section_completed_today'), 'class' => 'success', 'show_time' => false],
                ];
                foreach ($sections as $key => $section) {
                    $cases = $groups[$key];
                    ?>
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin">
                            <span class="label label-<?= $section['class']; ?>"><?= e($section['label']); ?></span>
                            (<?= count($cases); ?>)
                        </h4>
                        <hr class="hr-panel-separator" />
                        <?php if (empty($cases)) { ?>
                        <p class="text-muted"><?= _l('case_no_cases_in_section'); ?></p>
                        <?php } else { ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th><?= _l('case_column_lead_name'); ?></th>
                                        <th><?= _l('case_column_company'); ?></th>
                                        <?php if ($section['show_time']) { ?>
                                        <th><?= _l('case_column_next_follow_up'); ?></th>
                                        <?php } ?>
                                        <th><?= _l('case_field_priority'); ?></th>
                                        <th><?= _l('options'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cases as $case) {
                                        $rel_data   = get_relation_data($case->rel_type, $case->rel_id);
                                        $rel_values = get_relation_values($rel_data, $case->rel_type);
                                        $company    = '';
                                        if ($case->rel_type === 'lead') {
                                            $CI = &get_instance();
                                            $lead = $CI->db->select('company')->where('id', $case->rel_id)->get(db_prefix() . 'leads')->row();
                                            $company = $lead ? $lead->company : '';
                                        }
                                        ?>
                                    <tr>
                                        <td><a href="<?= e($rel_values['link']); ?>"><?= e($rel_values['name']); ?></a></td>
                                        <td><?= $company ? e($company) : '-'; ?></td>
                                        <?php if ($section['show_time']) { ?>
                                        <td><?= $case->next_follow_up_date ? e(_dt($case->next_follow_up_date)) : '-'; ?></td>
                                        <?php } ?>
                                        <td><span class="label label-<?= get_followup_priority_class($case->priority); ?>"><?= e(get_followup_priority_label($case->priority)); ?></span></td>
                                        <td><a href="<?= admin_url('follow_up_management/view/' . $case->id); ?>" class="btn btn-default btn-icon"><?= _l('case_quick_open'); ?></a></td>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                        <?php } ?>
                    </div>
                </div>
                <?php } ?>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
</body>
</html>
