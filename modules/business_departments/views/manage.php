<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="tw-mb-2">
                    <?php if (staff_can('create', 'business_departments')) { ?>
                    <a href="<?= admin_url('business_departments/department'); ?>" class="btn btn-primary">
                        <i class="fa-regular fa-plus tw-mr-1"></i>
                        <?= _l('new_business_department'); ?>
                    </a>
                    <?php } ?>
                </div>
                <div class="panel_s">
                    <div class="panel-body">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th><?= _l('business_department_name'); ?></th>
                                    <th><?= _l('business_department_staff_count'); ?></th>
                                    <th><?= _l('business_department_active'); ?></th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($departments)) { ?>
                                <tr>
                                    <td colspan="4" class="text-center"><?= _l('nothing_found'); ?></td>
                                </tr>
                                <?php }
                                foreach ($departments as $department) { ?>
                                <tr>
                                    <td>
                                        <a href="<?= admin_url('business_departments/department/' . $department['id']); ?>" class="tw-font-medium">
                                            <?= e($department['name']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?= (int) ($staff_counts[$department['id']] ?? 0); ?>
                                    </td>
                                    <td>
                                        <?php if ($department['active'] == 1) { ?>
                                        <span class="label label-success"><?= _l('active'); ?></span>
                                        <?php } else { ?>
                                        <span class="label label-default"><?= _l('inactive'); ?></span>
                                        <?php } ?>
                                    </td>
                                    <td>
                                        <div class="tw-flex tw-items-center tw-space-x-2">
                                            <?php if (staff_can('edit', 'business_departments')) { ?>
                                            <a href="<?= admin_url('business_departments/department/' . $department['id']); ?>" class="tw-text-neutral-500 hover:tw-text-neutral-700">
                                                <i class="fa-regular fa-pen-to-square fa-lg"></i>
                                            </a>
                                            <?php } ?>
                                            <?php if (staff_can('delete', 'business_departments')) { ?>
                                            <a href="<?= admin_url('business_departments/delete/' . $department['id']); ?>" class="tw-text-neutral-500 hover:tw-text-neutral-700 _delete">
                                                <i class="fa-regular fa-trash-can fa-lg"></i>
                                            </a>
                                            <?php } ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
