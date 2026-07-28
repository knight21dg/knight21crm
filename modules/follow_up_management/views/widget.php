<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
// "My Today's Follow-ups" dashboard widget - reuses the same
// get_dashboard_widgets() filter architecture every other dashboard widget
// uses (registered once in follow_up_management.php); this file is that
// widget's own render callback, nothing new invented.
//
// Always self-scoped to the current staff member (same reasoning as the
// Dashboard's "My Assigned Cases" KPI) - a personal widget shows the
// viewer's own queue regardless of Admin/Manager/Telecaller visibility.
// Combines Today's Follow-ups + Overdue into one compact list (soonest/most
// overdue first), each row showing its Priority badge and a Quick Open
// button, per the widget spec.
$due_cases = [];
if (is_staff_member()) {
    $CI = &get_instance();
    $CI->db->where('assigned_staff_id', get_staff_user_id());
    $CI->db->where('case_status', 'open');
    $CI->db->where('next_follow_up_date IS NOT NULL');
    $CI->db->where('next_follow_up_date <=', date('Y-m-d 23:59:59'));
    $CI->db->order_by('next_follow_up_date', 'asc');
    $CI->db->limit(5);
    $due_cases = $CI->db->get(db_prefix() . 'followups')->result();
}
$today = date('Y-m-d');
?>
<div class="widget<?php if (count($due_cases) == 0 || !is_staff_member()) {
    echo ' hide';
} ?>" id="widget-<?= create_widget_id('follow_up_management'); ?>">
    <?php if (is_staff_member()) { ?>
    <div class="row">
        <div class="col-md-12">
            <div class="panel_s">
                <div class="panel-body padding-10">
                    <div class="widget-dragger"></div>

                    <p class="tw-font-semibold tw-flex tw-items-center tw-mb-0 tw-space-x-1.5 rtl:tw-space-x-reverse tw-p-1.5">
                        <i class="fa-regular fa-clock tw-text-neutral-500"></i>
                        <span class="tw-text-neutral-700">
                            <?= _l('followup_widget_title'); ?>
                        </span>
                    </p>

                    <hr class="-tw-mx-3 tw-mt-3 tw-mb-6">

                    <?php foreach ($due_cases as $case) {
                        $rel_data   = get_relation_data($case->rel_type, $case->rel_id);
                        $rel_values = get_relation_values($rel_data, $case->rel_type);
                        $company    = '';
                        if ($case->rel_type === 'lead') {
                            $CI      = &get_instance();
                            $lead    = $CI->db->select('company')->where('id', $case->rel_id)->get(db_prefix() . 'leads')->row();
                            $company = $lead ? $lead->company : '';
                        }
                        $is_overdue = substr($case->next_follow_up_date, 0, 10) < $today;
                        ?>
                    <div class="followup-due tw-px-1 tw-pb-2 tw-flex tw-items-center tw-justify-between">
                        <span>
                            <a href="<?= e($rel_values['link']); ?>"><?= e($rel_values['name']); ?></a>
                            <?php if ($company) { ?><br /><small class="text-muted"><?= e($company); ?></small><?php } ?>
                            <br />
                            <small class="<?= $is_overdue ? 'text-danger' : 'text-muted'; ?>">
                                <?= $is_overdue ? _l('section_overdue') . ' - ' : ''; ?><?= e(_dt($case->next_follow_up_date)); ?>
                            </small>
                            <span class="label label-<?= get_followup_priority_class($case->priority); ?>"><?= e(get_followup_priority_label($case->priority)); ?></span>
                        </span>
                        <a href="<?= admin_url('follow_up_management/view/' . $case->id); ?>" class="btn btn-default btn-icon">
                            <?= _l('case_quick_open'); ?>
                        </a>
                    </div>
                    <?php } ?>
                    <a href="<?= admin_url('follow_up_management/todays_followups'); ?>" class="tw-text-xs">
                        <?= _l('followup_view_all'); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php } ?>
</div>
