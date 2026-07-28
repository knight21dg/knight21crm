<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php if (empty($attachments)) { ?>
<p class="text-muted dwu-no-files"><?= _l('daily_work_update_no_files'); ?></p>
<?php } else { ?>
<ul class="list-unstyled dwu-attachments-list">
    <?php foreach ($attachments as $file) {
        $path      = get_upload_path_by_type('dm_work_update') . $file['rel_id'] . '/' . $file['file_name'];
        $file_size = file_exists($path) ? bytesToSize($path) : '-';
        ?>
    <li class="dwu-attachment-item" data-attachment-id="<?= (int) $file['id']; ?>">
        <div class="dwu-attachment-info">
            <i class="fa-regular fa-file tw-text-gray-400"></i>
            <a href="<?= site_url('download/file/sales_attachment/' . $file['attachment_key']); ?>" class="dwu-attachment-name" target="_blank"><?= e($file['file_name']); ?></a>
            <span class="text-muted dwu-attachment-meta">(<?= e($file_size); ?>) &middot; <?= _l('daily_work_update_uploaded'); ?>: <?= e(_dt($file['dateadded'])); ?></span>
        </div>
        <button type="button" class="btn btn-link btn-xs dwu-attachment-delete" title="<?= _l('daily_work_update_delete_attachment'); ?>" onclick="dwu_delete_attachment(<?= (int) $file['id']; ?>)">
            <i class="fa-regular fa-trash-can"></i> <?= _l('daily_work_update_delete_attachment'); ?>
        </button>
    </li>
    <?php } ?>
</ul>
<?php } ?>
