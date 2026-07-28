<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="calling-summary-widget">
    <ul class="list-unstyled no-margin">
        <li class="tw-flex tw-items-center tw-justify-between tw-py-1">
            <span class="text-muted"><?= _l('followup_calling_summary_completed'); ?></span>
            <span class="tw-font-medium tw-text-green-600"><?= (int) $summary['completed_today']; ?></span>
        </li>
        <li class="tw-flex tw-items-center tw-justify-between tw-py-1">
            <span class="text-muted"><?= _l('followup_calling_summary_pending'); ?></span>
            <span class="tw-font-medium tw-text-red-600"><?= (int) $summary['pending_today']; ?></span>
        </li>
        <li class="tw-flex tw-items-center tw-justify-between tw-py-1">
            <span class="text-muted"><?= _l('followup_calling_summary_tomorrow'); ?></span>
            <span class="tw-font-medium"><?= (int) $summary['tomorrow_calls']; ?></span>
        </li>
        <li class="tw-flex tw-items-center tw-justify-between tw-py-1">
            <span class="text-muted"><?= _l('followup_calling_summary_overdue'); ?></span>
            <span class="tw-font-medium tw-text-orange-600"><?= (int) $summary['overdue_calls']; ?></span>
        </li>
        <li class="tw-flex tw-items-center tw-justify-between tw-py-1">
            <span class="text-muted"><?= _l('followup_calling_summary_completion'); ?></span>
            <span class="tw-font-medium"><?= (int) $summary['completion_percentage']; ?>%</span>
        </li>
    </ul>

    <button type="button" class="btn btn-default btn-sm btn-block calling-summary-toggle" data-expanded="0">
        <span class="calling-summary-toggle-label"><?= _l('followup_calling_summary_view_details'); ?></span>
        <i class="fa-solid fa-chevron-down"></i>
    </button>

    <div class="calling-summary-details" style="display:none;">
        <hr class="hr-panel-separator" />
        <?php
        $sections = [
            'completed_today' => ['label' => _l('followup_calling_summary_completed'), 'icon' => 'fa-solid fa-check tw-text-green-600'],
            'pending_today'   => ['label' => _l('followup_calling_summary_pending'), 'icon' => 'fa-solid fa-circle tw-text-red-600'],
            'tomorrow_calls'  => ['label' => _l('followup_calling_summary_tomorrow'), 'icon' => 'fa-solid fa-circle'],
            'overdue_calls'   => ['label' => _l('followup_calling_summary_overdue'), 'icon' => 'fa-solid fa-triangle-exclamation tw-text-orange-600'],
        ];
        foreach ($sections as $key => $section) {
            $names = $details[$key] ?? [];
            ?>
        <div class="calling-summary-detail-section tw-mb-3">
            <strong><?= e($section['label']); ?> (<?= count($names); ?>)</strong>
            <?php if (empty($names)) { ?>
            <div class="text-muted small"><?= _l('followup_calling_summary_no_records'); ?></div>
            <?php } else { ?>
            <ul class="list-unstyled tw-mt-1 no-margin">
                <?php foreach ($names as $name) { ?>
                <li><i class="<?= e($section['icon']); ?> tw-mr-1"></i><?= e($name); ?></li>
                <?php } ?>
            </ul>
            <?php } ?>
        </div>
        <?php } ?>
    </div>
</div>

<script>
(function () {
    var labelViewDetails = <?= json_encode(_l('followup_calling_summary_view_details')); ?>;
    var labelShowLess    = <?= json_encode(_l('followup_calling_summary_show_less')); ?>;

    $(document).off('click.callingSummaryToggle').on('click.callingSummaryToggle', '.calling-summary-toggle', function () {
        var $btn      = $(this);
        var $widget   = $btn.closest('.calling-summary-widget');
        var $details  = $widget.find('.calling-summary-details');
        var expanded  = $btn.attr('data-expanded') === '1';

        $details.slideToggle(150);
        $btn.attr('data-expanded', expanded ? '0' : '1');
        $btn.find('i').toggleClass('fa-chevron-down fa-chevron-up');
        $btn.find('.calling-summary-toggle-label').text(expanded ? labelViewDetails : labelShowLess);
    });
})();
</script>
