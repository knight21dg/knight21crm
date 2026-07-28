<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="tw-flex tw-items-center tw-justify-between tw-mb-4">
                            <h4 class="no-margin"><?= _l('followup_admin_sales_dashboard'); ?></h4>
                            <button type="button" class="btn btn-default btn-sm" onclick="refreshKpis()"><i class="fa-solid fa-rotate"></i> <?= _l('followup_refresh'); ?></button>
                        </div>

                        <div class="row" id="kpi-grid">
                            <div class="col-md-3 col-sm-6 tw-mb-3">
                                <a href="<?= admin_url('follow_up_management/lead_reports'); ?>" class="tw-text-inherit">
                                    <div class="panel_s tw-mb-0">
                                        <div class="panel-body text-center">
                                            <h3 class="no-margin tw-text-sky-600"><?= (int) $kpis['total_leads']; ?></h3>
                                            <span class="text-muted"><?= _l('followup_total_leads'); ?></span>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <div class="col-md-3 col-sm-6 tw-mb-3">
                                <a href="<?= admin_url('follow_up_management/lead_reports?date_from=' . date('Y-m-d') . '&date_to=' . date('Y-m-d')); ?>" class="tw-text-inherit">
                                    <div class="panel_s tw-mb-0">
                                        <div class="panel-body text-center">
                                            <h3 class="no-margin tw-text-emerald-600"><?= (int) $kpis['new_leads_today']; ?></h3>
                                            <span class="text-muted"><?= _l('followup_new_leads_today'); ?></span>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <div class="col-md-3 col-sm-6 tw-mb-3">
                                <a href="<?= admin_url('follow_up_management/lead_reports?role=fe'); ?>" class="tw-text-inherit">
                                    <div class="panel_s tw-mb-0">
                                        <div class="panel-body text-center">
                                            <h3 class="no-margin tw-text-blue-600"><?= (int) $kpis['leads_with_fe']; ?></h3>
                                            <span class="text-muted"><?= _l('followup_leads_with_fe'); ?></span>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <div class="col-md-3 col-sm-6 tw-mb-3">
                                <a href="<?= admin_url('follow_up_management/lead_reports?role=tc'); ?>" class="tw-text-inherit">
                                    <div class="panel_s tw-mb-0">
                                        <div class="panel-body text-center">
                                            <h3 class="no-margin tw-text-purple-600"><?= (int) $kpis['leads_with_tc']; ?></h3>
                                            <span class="text-muted"><?= _l('followup_leads_with_tc'); ?></span>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <div class="col-md-3 col-sm-6 tw-mb-3">
                                <a href="<?= admin_url('follow_up_management/followups_filtered/today'); ?>" class="tw-text-inherit">
                                    <div class="panel_s tw-mb-0">
                                        <div class="panel-body text-center">
                                            <h3 class="no-margin tw-text-orange-600"><?= (int) $kpis['todays_followups']; ?></h3>
                                            <span class="text-muted"><?= _l('followup_todays_followups'); ?></span>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <div class="col-md-3 col-sm-6 tw-mb-3">
                                <a href="<?= admin_url('follow_up_management/lead_reports?is_converted=1'); ?>" class="tw-text-inherit">
                                    <div class="panel_s tw-mb-0">
                                        <div class="panel-body text-center">
                                            <h3 class="no-margin tw-text-green-600"><?= (int) $kpis['converted']; ?></h3>
                                            <span class="text-muted"><?= _l('followup_converted_customers'); ?></span>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <div class="col-md-3 col-sm-6 tw-mb-3">
                                <a href="<?= admin_url('follow_up_management/lead_reports?is_lost=1'); ?>" class="tw-text-inherit">
                                    <div class="panel_s tw-mb-0">
                                        <div class="panel-body text-center">
                                            <h3 class="no-margin tw-text-red-600"><?= (int) $kpis['lost']; ?></h3>
                                            <span class="text-muted"><?= _l('followup_lost_leads'); ?></span>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <div class="col-md-3 col-sm-6 tw-mb-3">
                                <div class="panel_s tw-mb-0">
                                    <div class="panel-body text-center">
                                        <h3 class="no-margin tw-text-amber-600"><?= e($kpis['conversion_rate']); ?>%</h3>
                                        <span class="text-muted"><?= _l('followup_conversion_rate'); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <?php staff_dashboard_panel_open(_l('todays_meetings'), 'fa-solid fa-calendar-check'); ?>
                                <h3 class="tw-text-indigo-600 tw-text-center"><?= (int) ($kpis['todays_meetings'] ?? 0); ?></h3>
                                <span class="text-muted"><?= _l('followup_meetings_scheduled_today'); ?></span>
                                <?php staff_dashboard_panel_close(); ?>
                            </div>
                            <div class="col-md-6">
                                <?php staff_dashboard_panel_open(_l('followup_active_customers'), 'fa-solid fa-building'); ?>
                                <h3 class="tw-text-teal-600 tw-text-center"><?= (int) ($kpis['active_customers'] ?? 0); ?></h3>
                                <span class="text-muted"><?= _l('followup_customers_from_leads'); ?></span>
                                <?php staff_dashboard_panel_close(); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script>
    function refreshKpis() {
        $.get(admin_url + 'follow_up_management/admin_kpi_data', function(data) {
            var kpis = typeof data === 'string' ? JSON.parse(data) : data;
            var grid = $('#kpi-grid');
            grid.find('.col-md-3').eq(0).find('h3').text(kpis.total_leads);
            grid.find('.col-md-3').eq(1).find('h3').text(kpis.new_leads_today);
            grid.find('.col-md-3').eq(2).find('h3').text(kpis.leads_with_fe);
            grid.find('.col-md-3').eq(3).find('h3').text(kpis.leads_with_tc);
            grid.find('.col-md-3').eq(4).find('h3').text(kpis.todays_followups);
            grid.find('.col-md-3').eq(5).find('h3').text(kpis.converted);
            grid.find('.col-md-3').eq(6).find('h3').text(kpis.lost);
            grid.find('.col-md-3').eq(7).find('h3').text(kpis.conversion_rate + '%');
        });
    }
</script>
</body>
</html>
