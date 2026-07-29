<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">&times;</button>
    <h4 class="modal-title">
        <?= _l('staff_attendance_sessions_title'); ?> - <?= _d($record->attendance_date); ?>
    </h4>
</div>
<div class="modal-body">
    <?php if (empty($sessions)) { ?>
    <p class="text-muted"><?= _l('staff_attendance_no_sessions'); ?></p>
    <?php } else { ?>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th><?= _l('staff_attendance_session_number'); ?></th>
                <th><?= _l('staff_attendance_column_login'); ?></th>
                <th><?= _l('staff_attendance_column_logout'); ?></th>
                <th><?= _l('staff_attendance_column_working_hours'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($sessions as $session) { ?>
            <tr>
                <td><?= _l('staff_attendance_session_number'); ?> <?= (int) $session->session_no; ?></td>
                <td><?= _dt($session->login_time); ?></td>
                <td><?= $session->logout_time ? _dt($session->logout_time) : '<span class="label label-info">' . _l('staff_attendance_session_active') . '</span>'; ?></td>
                <td><?= $session->working_minutes !== null ? format_working_minutes($session->working_minutes) : '-'; ?></td>
            </tr>
            <?php } ?>
        </tbody>
    </table>

    <hr class="hr-panel-heading" />

    <h5><?= _l('staff_attendance_summary_title'); ?></h5>
    <div class="row">
        <div class="col-md-3 col-sm-6">
            <p class="text-muted tw-mb-1"><?= _l('staff_attendance_summary_first_login'); ?></p>
            <p><strong><?= $record->login_time ? _dt($record->login_time) : '-'; ?></strong></p>
        </div>
        <div class="col-md-3 col-sm-6">
            <p class="text-muted tw-mb-1"><?= _l('staff_attendance_summary_last_logout'); ?></p>
            <p><strong><?= $record->logout_time ? _dt($record->logout_time) : '-'; ?></strong></p>
        </div>
        <div class="col-md-3 col-sm-6">
            <p class="text-muted tw-mb-1"><?= _l('staff_attendance_summary_sessions'); ?></p>
            <p><strong><?= (int) $record->total_sessions; ?></strong></p>
        </div>
        <div class="col-md-3 col-sm-6">
            <p class="text-muted tw-mb-1"><?= _l('staff_attendance_summary_total_working_hours'); ?></p>
            <p><strong><?= format_working_minutes($record->working_minutes); ?></strong></p>
        </div>
    </div>
    <?php } ?>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-default" data-dismiss="modal"><?= _l('close'); ?></button>
</div>
