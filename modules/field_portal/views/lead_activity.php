<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <?php field_portal_page_header(
                            _l('field_portal_lead_activity'),
                            [
                                ['label' => _l('field_portal_breadcrumb_dashboard'), 'href' => admin_url('field_portal')],
                                ['label' => _l('field_portal_lead_activity')],
                            ]
                        ); ?>
                        <p class="text-muted"><?= _l('field_portal_lead_activity_hint'); ?></p>

                        <?php if (empty($activity)) { ?>
                        <span class="text-muted"><?= _l('field_portal_no_recent_activity'); ?></span>
                        <?php } else { ?>
                        <ul class="list-unstyled">
                            <?php foreach ($activity as $entry) {
                                // description is a raw language key, not display
                                // text - same translation convention as the
                                // native Lead activity tab / this same panel on
                                // the Dashboard.
                                $additional_data = !empty($entry['additional_data']) ? unserialize($entry['additional_data']) : '';
                                $activity_text   = $additional_data !== '' ? _l($entry['description'], $additional_data) : e(_l($entry['description']));
                            ?>
                            <li class="tw-mb-3">
                                <i class="fa-solid fa-clock-rotate-left tw-text-gray-400"></i>
                                <a href="<?= admin_url('field_portal/lead_details/' . $entry['leadid']); ?>"><?= e($entry['lead_name']); ?></a>
                                <span class="text-muted"> - <?= $activity_text; ?></span>
                                <span class="text-muted tw-block" style="font-size:11.5px;"><?= e(_dt($entry['date'])); ?></span>
                            </li>
                            <?php } ?>
                        </ul>
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
