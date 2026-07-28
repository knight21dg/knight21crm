<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
if (!function_exists('get_bank_details')) {
    return;
}

$bank_details = get_bank_details();

if (empty($bank_details['bank_name'])) {
    return;
}
?>
<div class="col-md-12 bank-details-card-wrapper">
    <hr />
    <div class="panel_s bank-details-card">
        <div class="panel-body">
            <p class="tw-mb-2.5 tw-font-medium">
                <i class="fa-regular fa-building-columns tw-mr-1"></i>
                <?= _l('bank_details_card_heading'); ?>
            </p>
            <div class="row">
                <div class="col-sm-8 col-md-9">
                    <table class="table-bank-details" style="width:100%;">
                        <tbody>
                            <tr>
                                <td class="bold tw-pr-2.5" style="white-space:nowrap;"><?= _l('settings_bank_name'); ?>:</td>
                                <td><?= e($bank_details['bank_name']); ?></td>
                            </tr>
                            <?php if (!empty($bank_details['account_name'])) { ?>
                            <tr>
                                <td class="bold tw-pr-2.5"><?= _l('settings_bank_account_name'); ?>:</td>
                                <td><?= e($bank_details['account_name']); ?></td>
                            </tr>
                            <?php } ?>
                            <?php if (!empty($bank_details['account_number'])) { ?>
                            <tr>
                                <td class="bold tw-pr-2.5"><?= _l('settings_bank_account_number'); ?>:</td>
                                <td><?= e($bank_details['account_number']); ?></td>
                            </tr>
                            <?php } ?>
                            <?php if (!empty($bank_details['ifsc_code'])) { ?>
                            <tr>
                                <td class="bold tw-pr-2.5"><?= _l('settings_bank_ifsc_code'); ?>:</td>
                                <td><?= e($bank_details['ifsc_code']); ?></td>
                            </tr>
                            <?php } ?>
                            <?php if (!empty($bank_details['branch_name'])) { ?>
                            <tr>
                                <td class="bold tw-pr-2.5"><?= _l('settings_bank_branch_name'); ?>:</td>
                                <td><?= e($bank_details['branch_name']); ?></td>
                            </tr>
                            <?php } ?>
                            <?php if (!empty($bank_details['swift_code'])) { ?>
                            <tr>
                                <td class="bold tw-pr-2.5"><?= _l('settings_bank_swift_code'); ?>:</td>
                                <td><?= e($bank_details['swift_code']); ?></td>
                            </tr>
                            <?php } ?>
                            <?php if (!empty($bank_details['account_type'])) { ?>
                            <tr>
                                <td class="bold tw-pr-2.5"><?= _l('settings_bank_account_type'); ?>:</td>
                                <td><?= e($bank_details['account_type']); ?></td>
                            </tr>
                            <?php } ?>
                            <?php if (!empty($bank_details['upi_id'])) { ?>
                            <tr>
                                <td class="bold tw-pr-2.5"><?= _l('settings_bank_upi_id'); ?>:</td>
                                <td><?= e($bank_details['upi_id']); ?></td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                    <?php if (!empty($bank_details['instructions'])) { ?>
                    <div class="tw-text-neutral-500 tw-mt-2.5">
                        <?= process_text_content_for_display($bank_details['instructions']); ?>
                    </div>
                    <?php } ?>
                </div>
                <?php if (!empty($bank_details['qr_code_url'])) { ?>
                <div class="col-sm-4 col-md-3 text-center tw-mt-3 sm:tw-mt-0">
                    <img src="<?= e($bank_details['qr_code_url']); ?>" alt="<?= e(_l('settings_bank_qr_code')); ?>" style="max-width:180px;width:100%;height:auto;">
                </div>
                <?php } ?>
            </div>
            <p class="tw-text-neutral-500 tw-mt-2.5 tw-mb-0">
                <?= _l('bank_details_payment_verification_note'); ?>
            </p>
        </div>
    </div>
</div>
