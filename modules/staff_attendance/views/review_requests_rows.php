<?php
defined('BASEPATH') or exit('No direct script access allowed');
/**
 * Shared review-queue table (Leave/Late Arrival/Early Exit) for the Admin
 * Portal Attendance Module - rendered both on the initial page load
 * (embedded in manage.php's 3 review tabs) and returned as an AJAX
 * partial by Staff_attendance::review_requests_filter() on every filter
 * change, so both paths render identical markup from one file.
 *
 * Expects: $type ('leave'|'late_arrival'|'early_exit'), $records (array).
 */
?>
<?php if (empty($records)) { ?>
<span class="text-muted"><?= _l('staff_attendance_review_empty'); ?></span>
<?php } else { ?>
<div class="table-responsive">
<table class="table">
    <thead>
        <tr>
            <th><?= _l('staff_attendance_review_column_employee'); ?></th>
            <?php if ($type === 'leave') { ?>
            <th><?= _l('staff_attendance_leave_column_type'); ?></th>
            <th><?= _l('staff_attendance_leave_column_from'); ?></th>
            <th><?= _l('staff_attendance_leave_column_to'); ?></th>
            <th><?= _l('staff_attendance_leave_column_days'); ?></th>
            <th><?= _l('staff_attendance_leave_reason'); ?></th>
            <?php } elseif ($type === 'late_arrival') { ?>
            <th><?= _l('staff_attendance_column_date'); ?></th>
            <th><?= _l('staff_attendance_column_login'); ?></th>
            <th><?= _l('staff_attendance_late_delay'); ?></th>
            <th><?= _l('staff_attendance_late_reason'); ?></th>
            <?php } else { ?>
            <th><?= _l('staff_attendance_column_date'); ?></th>
            <th><?= _l('staff_attendance_column_logout'); ?></th>
            <th><?= _l('staff_attendance_early_duration'); ?></th>
            <th><?= _l('staff_attendance_early_reason'); ?></th>
            <?php } ?>
            <th><?= _l('staff_attendance_review_column_status'); ?></th>
            <th><?= _l('staff_attendance_review_column_reviewed_by'); ?></th>
            <th><?= _l('staff_attendance_review_column_actions'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($records as $record) { ?>
        <tr>
            <td><?= e(trim($record['firstname'] . ' ' . $record['lastname'])); ?></td>
            <?php if ($type === 'leave') { ?>
            <td><?= e($record['leave_type']); ?></td>
            <td><?= _d($record['start_date']); ?></td>
            <td><?= _d($record['end_date']); ?></td>
            <td><?= rtrim(rtrim(number_format((float) $record['days'], 1), '0'), '.'); ?></td>
            <td><?= e($record['reason']); ?></td>
            <?php } elseif ($type === 'late_arrival') { ?>
            <td><?= _d($record['attendance_date']); ?></td>
            <td><?= _dt($record['login_time']); ?></td>
            <td><?= (int) staff_attendance_late_minutes($record['login_time']); ?> <?= _l('staff_attendance_minutes'); ?></td>
            <td><?= e($record['reason']); ?></td>
            <?php } else { ?>
            <td><?= _d($record['attendance_date']); ?></td>
            <td><?= _dt($record['logout_time']); ?></td>
            <td><?= (int) staff_attendance_early_minutes($record['logout_time']); ?> <?= _l('staff_attendance_minutes'); ?></td>
            <td><?= e($record['reason']); ?></td>
            <?php } ?>
            <td><span class="label" style="color:<?= staff_attendance_request_status_color($record['status']); ?>;border:1px solid <?= adjust_hex_brightness(staff_attendance_request_status_color($record['status']), 0.4); ?>;background:<?= adjust_hex_brightness(staff_attendance_request_status_color($record['status']), 0.04); ?>;"><?= e($record['status']); ?></span></td>
            <td><?= !empty($record['reviewer_firstname']) ? e(trim($record['reviewer_firstname'] . ' ' . $record['reviewer_lastname'])) : '<span class="text-muted">-</span>'; ?></td>
            <td>
                <button type="button" class="btn btn-success btn-icon" onclick="attendance_open_review_modal('<?= $type; ?>', <?= (int) $record['id']; ?>, 'Approved');"><i class="fa-solid fa-check"></i></button>
                <button type="button" class="btn btn-danger btn-icon" onclick="attendance_open_review_modal('<?= $type; ?>', <?= (int) $record['id']; ?>, 'Rejected');"><i class="fa-solid fa-xmark"></i></button>
                <?php if ($record['status'] !== 'Pending') { ?>
                <span class="label label-default" title="<?= _l('staff_attendance_override_hint'); ?>"><?= _l('staff_attendance_override'); ?></span>
                <?php } ?>
            </td>
        </tr>
        <?php } ?>
    </tbody>
</table>
</div>
<?php } ?>
