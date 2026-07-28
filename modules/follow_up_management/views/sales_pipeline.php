<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin tw-mb-4"><?= _l('followup_sales_pipeline'); ?></h4>
                        <p class="text-muted"><?= _l('followup_pipeline_hint'); ?></p>

                        <?php $stages = $pipeline['stages'] ?? []; ?>
                        <?php if (empty($stages)) { ?>
                        <div class="alert alert-info"><?= _l('followup_pipeline_no_data'); ?></div>
                        <?php } else { ?>
                        <div class="tw-flex tw-flex-wrap tw-gap-0" style="overflow-x:auto;">
                            <?php
                            $colors = ['#3b82f6','#8b5cf6','#f59e0b','#10b981','#06b6d4','#f97316','#ef4444','#22c55e','#6b7280'];
                            $i = 0;
                            foreach ($stages as $stage) {
                                $pct = $pipeline['total'] > 0 ? round(($stage['lead_count'] / $pipeline['total']) * 100, 1) : 0;
                                $color = $colors[$i % count($colors)];
                            ?>
                            <div class="tw-flex tw-flex-col tw-items-center" style="min-width:140px;flex:1;">
                                <div class="panel_s tw-mb-2 tw-w-full" style="border-left:4px solid <?= $color; ?>;">
                                    <div class="panel-body tw-text-center">
                                        <h3 class="no-margin" style="color:<?= $color; ?>;"><?= (int) $stage['lead_count']; ?></h3>
                                        <span class="text-muted tw-text-xs"><?= e($stage['name'] ?: '-'); ?></span>
                                        <div class="tw-mt-1 tw-text-xs tw-text-gray-400"><?= $pct; ?>%</div>
                                    </div>
                                </div>
                                <a href="<?= admin_url('follow_up_management/lead_reports?status_id=' . urlencode($stage['name'])); ?>" class="btn btn-default btn-xs"><?= _l('followup_view_leads'); ?></a>
                            </div>
                            <?php if ($i < count($stages) - 1) { ?>
                            <div class="tw-flex tw-items-center tw-px-2" style="min-width:20px;">
                                <i class="fa-solid fa-chevron-right tw-text-gray-300"></i>
                            </div>
                            <?php } ?>
                            <?php $i++; } ?>
                        </div>
                        <div class="tw-mt-4 tw-text-muted">
                            <strong><?= _l('followup_total'); ?>:</strong> <?= (int) $pipeline['total']; ?> <?= _l('followup_pipeline_leads'); ?>
                        </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
</body>
</html>
