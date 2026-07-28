<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <?= form_open($this->uri->uri_string()); ?>
        <div class="tw-max-w-3xl tw-mx-auto">
            <h4 class="tw-mt-0 tw-font-bold tw-text-lg tw-text-neutral-700">
                <?= e($title); ?>
            </h4>
            <div class="panel_s">
                <div class="panel-body">
                    <?php $value = (isset($department) ? $department->name : ''); ?>
                    <?= render_input('name', 'business_department_name', $value, 'text', ['required' => 'required']); ?>

                    <?php $value = (isset($department) ? $department->description : ''); ?>
                    <?= render_textarea('description', 'business_department_description', $value, ['rows' => 3]); ?>

                    <div class="form-group">
                        <div class="checkbox checkbox-primary">
                            <input type="checkbox" name="active" id="active" <?php if (!isset($department) || $department->active == 1) {
                                echo 'checked';
                            } ?>>
                            <label for="active"><?= _l('business_department_active'); ?></label>
                        </div>
                    </div>

                    <hr />

                    <?php
                    // Real tblstaff records only - the mapping this Department
                    // exposes to the Assigned Employee dropdown everywhere else.
                    echo render_select(
                        'staff_ids[]',
                        $staff,
                        ['staffid', ['firstname', 'lastname']],
                        'business_department_staff',
                        isset($selected_staff_ids) ? $selected_staff_ids : [],
                        ['multiple' => true, 'data-actions-box' => true],
                        [],
                        '',
                        '',
                        false
                    );
                    ?>

                    <hr />
                    <button type="submit" class="btn btn-primary"><?= _l('submit'); ?></button>
                    <a href="<?= admin_url('business_departments'); ?>" class="btn btn-default"><?= _l('close'); ?></a>
                </div>
            </div>
        </div>
        <?= form_close(); ?>
    </div>
</div>
<?php init_tail(); ?>
<script>
    $(function() {
        appValidateForm($('form'), {
            name: 'required',
        });
    });
</script>
