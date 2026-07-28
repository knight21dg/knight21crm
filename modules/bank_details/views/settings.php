<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="row">
    <div class="col-md-6">
        <?= render_input('settings[bank_name]', 'settings_bank_name', get_option('bank_name'), 'text', ['required' => 'required']); ?>
        <?= render_input('settings[bank_account_name]', 'settings_bank_account_name', get_option('bank_account_name'), 'text', ['required' => 'required']); ?>
        <?= render_input('settings[bank_account_number]', 'settings_bank_account_number', get_option('bank_account_number'), 'text', ['required' => 'required']); ?>
        <?= render_input('settings[bank_ifsc_code]', 'settings_bank_ifsc_code', get_option('bank_ifsc_code'), 'text', ['required' => 'required']); ?>
        <?= render_input('settings[bank_branch_name]', 'settings_bank_branch_name', get_option('bank_branch_name'), 'text', ['required' => 'required']); ?>
    </div>
    <div class="col-md-6">
        <?= render_input('settings[bank_swift_code]', 'settings_bank_swift_code', get_option('bank_swift_code')); ?>
        <?= render_input('settings[bank_account_type]', 'settings_bank_account_type', get_option('bank_account_type')); ?>
        <?= render_input('settings[bank_upi_id]', 'settings_bank_upi_id', get_option('bank_upi_id')); ?>

        <?php $bank_qr_code = get_option('bank_qr_code'); ?>
        <div class="form-group">
            <label class="control-label"><?= _l('settings_bank_qr_code'); ?></label>
            <?php if ($bank_qr_code != '') { ?>
            <div class="row">
                <div class="col-md-9">
                    <img src="<?= base_url('uploads/company/' . $bank_qr_code); ?>" class="img img-responsive" style="max-width:180px;">
                </div>
                <?php if (staff_can('delete', 'settings')) { ?>
                <div class="col-md-3 text-right">
                    <a href="<?= admin_url('bank_details/remove_qr_code'); ?>" class="_delete text-danger">
                        <i class="fa fa-remove"></i>
                    </a>
                </div>
                <?php } ?>
            </div>
            <?php } else { ?>
            <input type="file" name="bank_qr_code" class="form-control">
            <?php } ?>
        </div>
    </div>
    <div class="col-md-12">
        <?= render_textarea('settings[bank_payment_instructions]', 'settings_bank_payment_instructions', get_option('bank_payment_instructions'), ['rows' => 4]); ?>
    </div>
</div>
