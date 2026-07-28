<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<style>
/* ===== Employee Profile View — scoped styles ===== */
.ep-hero {
    background: linear-gradient(135deg, #1a237e 0%, #283593 40%, #0288d1 100%);
    border-radius: 12px;
    padding: 32px 36px;
    color: #fff;
    margin-bottom: 24px;
    position: relative;
    overflow: hidden;
}
.ep-hero::before {
    content: '';
    position: absolute;
    right: -60px;
    top: -60px;
    width: 260px;
    height: 260px;
    background: rgba(255,255,255,0.06);
    border-radius: 50%;
}
.ep-hero::after {
    content: '';
    position: absolute;
    right: 40px;
    bottom: -80px;
    width: 180px;
    height: 180px;
    background: rgba(255,255,255,0.04);
    border-radius: 50%;
}
.ep-avatar {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    border: 4px solid rgba(255,255,255,0.4);
    object-fit: cover;
    background: #fff;
    flex-shrink: 0;
}
.ep-hero .ep-name { font-size: 24px; font-weight: 700; margin: 0 0 4px; }
.ep-hero .ep-sub  { font-size: 14px; opacity: 0.82; margin: 0; }
.ep-badge {
    display: inline-flex;
    align-items: center;
    padding: 3px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    letter-spacing: 0.4px;
}
.ep-badge.active   { background: #43a047; color: #fff; }
.ep-badge.inactive { background: #e53935; color: #fff; }

.ep-section {
    background: #fff;
    border: 1px solid #e8eaf0;
    border-radius: 10px;
    margin-bottom: 20px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.06);
    overflow: hidden;
}
.ep-section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 20px;
    background: #f8f9fc;
    border-bottom: 1px solid #e8eaf0;
    cursor: pointer;
    user-select: none;
}
.ep-section-header h5 {
    margin: 0;
    font-size: 15px;
    font-weight: 700;
    color: #283593;
    display: flex;
    align-items: center;
    gap: 8px;
}
.ep-section-header .ep-toggle-icon { transition: transform 0.2s; color: #607d8b; }
.ep-section-header.collapsed .ep-toggle-icon { transform: rotate(-90deg); }
.ep-section-body { padding: 20px; }

/* Info grid */
.ep-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 14px;
}
.ep-info-item label {
    display: block;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    color: #90a4ae;
    margin-bottom: 3px;
}
.ep-info-item .ep-value {
    font-size: 14px;
    color: #37474f;
    font-weight: 500;
    word-break: break-word;
}
.ep-info-item .ep-value.muted { color: #b0bec5; font-style: italic; }

/* Stat cards */
.ep-stat-row { display: flex; flex-wrap: wrap; gap: 12px; }
.ep-stat-card {
    flex: 1 1 130px;
    background: #f8f9fc;
    border: 1px solid #e8eaf0;
    border-radius: 8px;
    padding: 14px 16px;
    text-align: center;
    transition: box-shadow 0.15s, transform 0.15s;
}
.ep-stat-card:hover { box-shadow: 0 4px 12px rgba(40,53,147,0.1); transform: translateY(-1px); }
.ep-stat-card .ep-stat-num {
    font-size: 26px;
    font-weight: 800;
    color: #283593;
    line-height: 1;
    margin-bottom: 4px;
}
.ep-stat-card .ep-stat-label {
    font-size: 11px;
    color: #78909c;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.ep-stat-card.accent-green .ep-stat-num { color: #2e7d32; }
.ep-stat-card.accent-red   .ep-stat-num { color: #c62828; }
.ep-stat-card.accent-amber .ep-stat-num { color: #e65100; }
.ep-stat-card.accent-teal  .ep-stat-num { color: #00695c; }

/* Work sub-cards */
.ep-work-card {
    border: 1px solid #e8eaf0;
    border-radius: 8px;
    overflow: hidden;
    height: 100%;
}
.ep-work-card .ep-work-header {
    padding: 10px 14px;
    font-weight: 700;
    font-size: 13px;
    display: flex;
    align-items: center;
    gap: 6px;
}
.ep-work-card.leads  .ep-work-header { background: #e3f2fd; color: #1565c0; }
.ep-work-card.tasks  .ep-work-header { background: #f3e5f5; color: #6a1b9a; }
.ep-work-card.proj   .ep-work-header { background: #e8f5e9; color: #2e7d32; }
.ep-work-card .ep-work-body { padding: 12px 14px; }
.ep-work-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 5px 0;
    border-bottom: 1px solid #f0f0f0;
    font-size: 13px;
}
.ep-work-row:last-child { border-bottom: none; }
.ep-work-row .wl { color: #607d8b; }
.ep-work-row .wr { font-weight: 700; color: #37474f; }
.ep-work-footer { padding: 10px 14px; background: #fafbfc; border-top: 1px solid #f0f0f0; }

/* Activity feed */
.ep-activity-feed { list-style: none; margin: 0; padding: 0; }
.ep-activity-feed li {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 10px 0;
    border-bottom: 1px solid #f5f5f5;
    font-size: 13px;
}
.ep-activity-feed li:last-child { border-bottom: none; }
.ep-activity-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #283593;
    flex-shrink: 0;
    margin-top: 5px;
}
.ep-activity-time { font-size: 11px; color: #90a4ae; white-space: nowrap; }

/* Quick action buttons */
.ep-action-grid { display: flex; flex-wrap: wrap; gap: 10px; }
.ep-action-grid .btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    font-weight: 600;
    padding: 8px 16px;
    border-radius: 6px;
    transition: box-shadow 0.15s, transform 0.1s;
}
.ep-action-grid .btn:hover { transform: translateY(-1px); box-shadow: 0 3px 8px rgba(0,0,0,0.12); }

/* Follow-up specific */
.ep-fu-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 12px; }
.ep-fu-box {
    border-radius: 8px;
    padding: 14px;
    text-align: center;
    border: 1px solid;
}
.ep-fu-box.today    { background: #e3f2fd; border-color: #90caf9; }
.ep-fu-box.upcoming { background: #e8f5e9; border-color: #a5d6a7; }
.ep-fu-box.overdue  { background: #ffebee; border-color: #ef9a9a; }
.ep-fu-box.last     { background: #fff3e0; border-color: #ffcc80; }
.ep-fu-box .ep-fu-num   { font-size: 28px; font-weight: 800; }
.ep-fu-box.today    .ep-fu-num { color: #1565c0; }
.ep-fu-box.upcoming .ep-fu-num { color: #2e7d32; }
.ep-fu-box.overdue  .ep-fu-num { color: #b71c1c; }
.ep-fu-box.last     .ep-fu-num { color: #e65100; }
.ep-fu-box .ep-fu-lbl { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #607d8b; margin-top: 4px; }
</style>

<div id="wrapper">
    <div class="content">

        <!-- ─── Back link ─────────────────────────────────────────────── -->
        <div class="tw-mb-4">
            <a href="<?= admin_url('staff'); ?>" class="tw-text-neutral-500 hover:tw-text-neutral-700 tw-text-sm tw-inline-flex tw-items-center tw-gap-1">
                <i class="fa fa-angle-left"></i> Back to Staff List
            </a>
            &nbsp;|&nbsp;
            <a href="<?= admin_url('staff/member/' . $member->staffid); ?>" class="tw-text-neutral-500 hover:tw-text-neutral-700 tw-text-sm tw-inline-flex tw-items-center tw-gap-1">
                <i class="fa fa-edit"></i> Edit Staff Member
            </a>
        </div>

        <!-- ═══════════════════════════════════════════════════════════════
             HERO — Basic Information
        ════════════════════════════════════════════════════════════════ -->
        <div class="ep-hero">
            <div class="tw-flex tw-items-center tw-gap-5 tw-relative" style="z-index:1">
                <?= staff_profile_image($member->staffid, ['ep-avatar'], 'thumb'); ?>
                <div class="tw-flex-1">
                    <p class="ep-name">
                        <?= e($member->firstname . ' ' . $member->lastname); ?>
                    </p>
                    <p class="ep-sub tw-mb-2">
                        <?php if (!empty($member->position)) { echo e($member->position) . ' &nbsp;·&nbsp; '; } ?>
                        Staff ID: #<?= e($member->staffid); ?>
                        <?php if ($role_name) { echo ' &nbsp;·&nbsp; ' . e($role_name); } ?>
                    </p>
                    <span class="ep-badge <?= $member->active ? 'active' : 'inactive'; ?>">
                        <i class="fa fa-circle" style="font-size:7px;margin-right:5px;"></i>
                        <?= $member->active ? 'Active' : 'Inactive'; ?>
                    </span>
                </div>
                <div class="tw-text-right" style="z-index:1">
                    <a href="<?= admin_url('staff/member/' . $member->staffid); ?>"
                       class="btn btn-sm" style="background:rgba(255,255,255,0.15);color:#fff;border:1px solid rgba(255,255,255,0.3);">
                        <i class="fa fa-edit"></i> Edit
                    </a>
                </div>
            </div>

            <!-- Quick stats in hero footer -->
            <div class="tw-flex tw-gap-6 tw-mt-6 tw-pt-4 tw-relative" style="border-top:1px solid rgba(255,255,255,0.18);z-index:1">
                <div>
                    <div style="font-size:22px;font-weight:800;"><?= $leads['total']; ?></div>
                    <div style="font-size:11px;opacity:0.75;">Total Leads</div>
                </div>
                <div>
                    <div style="font-size:22px;font-weight:800;"><?= $tasks['total']; ?></div>
                    <div style="font-size:11px;opacity:0.75;">Total Tasks</div>
                </div>
                <div>
                    <div style="font-size:22px;font-weight:800;"><?= $projects['total']; ?></div>
                    <div style="font-size:11px;opacity:0.75;">Projects</div>
                </div>
                <div>
                    <div style="font-size:22px;font-weight:800;"><?= $performance['conversion_rate']; ?>%</div>
                    <div style="font-size:11px;opacity:0.75;">Conversion</div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">

                <!-- ═══════════════════════════════════════════════════════
                     SECTION 1 — Basic Information
                ════════════════════════════════════════════════════════ -->
                <div class="ep-section">
                    <div class="ep-section-header" data-toggle="collapse" data-target="#sec-basic" aria-expanded="true">
                        <h5><i class="fa fa-user-circle" style="color:#283593;"></i> Basic Information</h5>
                        <i class="fa fa-chevron-down ep-toggle-icon"></i>
                    </div>
                    <div id="sec-basic" class="collapse in ep-section-body">
                        <div class="ep-info-grid">
                            <div class="ep-info-item">
                                <label>Full Name</label>
                                <div class="ep-value"><?= e($member->firstname . ' ' . $member->lastname); ?></div>
                            </div>
                            <div class="ep-info-item">
                                <label>Employee ID</label>
                                <div class="ep-value">#<?= e($member->staffid); ?></div>
                            </div>
                            <div class="ep-info-item">
                                <label>Email</label>
                                <div class="ep-value"><a href="mailto:<?= e($member->email); ?>"><?= e($member->email); ?></a></div>
                            </div>
                            <div class="ep-info-item">
                                <label>Phone Number</label>
                                <div class="ep-value <?= empty($member->phonenumber) ? 'muted' : ''; ?>">
                                    <?= !empty($member->phonenumber) ? e($member->phonenumber) : 'Not provided'; ?>
                                </div>
                            </div>
                            <div class="ep-info-item">
                                <label>Role</label>
                                <div class="ep-value <?= empty($role_name) ? 'muted' : ''; ?>">
                                    <?= !empty($role_name) ? e($role_name) : 'No role assigned'; ?>
                                </div>
                            </div>
                            <div class="ep-info-item">
                                <label>Designation</label>
                                <div class="ep-value <?= empty($member->position) ? 'muted' : ''; ?>">
                                    <?= !empty($member->position) ? e($member->position) : 'Not specified'; ?>
                                </div>
                            </div>
                            <div class="ep-info-item">
                                <label>Department(s)</label>
                                <div class="ep-value">
                                    <?php if (count($staff_departments) > 0) { ?>
                                        <?php foreach ($staff_departments as $sd) { ?>
                                            <span class="label label-primary" style="margin-right:3px;"><?= e($sd['name']); ?></span>
                                        <?php } ?>
                                    <?php } else { ?>
                                        <span class="muted">Not assigned</span>
                                    <?php } ?>
                                </div>
                            </div>
                            <div class="ep-info-item">
                                <label>Status</label>
                                <div class="ep-value">
                                    <span class="ep-badge <?= $member->active ? 'active' : 'inactive'; ?>">
                                        <?= $member->active ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </div>
                            </div>
                            <div class="ep-info-item">
                                <label>Joining Date</label>
                                <div class="ep-value <?= empty($member->datecreated) ? 'muted' : ''; ?>">
                                    <?= !empty($member->datecreated) ? _d($member->datecreated) : 'N/A'; ?>
                                </div>
                            </div>
                            <div class="ep-info-item">
                                <label>Last Login</label>
                                <div class="ep-value <?= empty($member->last_activity) ? 'muted' : ''; ?>">
                                    <?= !empty($member->last_activity) ? _dt($member->last_activity) : 'Never'; ?>
                                </div>
                            </div>
                            <div class="ep-info-item">
                                <label>Account Created</label>
                                <div class="ep-value"><?= !empty($member->datecreated) ? _dt($member->datecreated) : 'N/A'; ?></div>
                            </div>
                            <div class="ep-info-item">
                                <label>Administrator</label>
                                <div class="ep-value"><?= $member->admin ? '<span class="label label-danger">Yes</span>' : '<span class="label label-default">No</span>'; ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ═══════════════════════════════════════════════════════
                     SECTION 2 — Contact Information
                ════════════════════════════════════════════════════════ -->
                <div class="ep-section">
                    <div class="ep-section-header" data-toggle="collapse" data-target="#sec-contact" aria-expanded="true">
                        <h5><i class="fa fa-address-book" style="color:#283593;"></i> Contact Information</h5>
                        <i class="fa fa-chevron-down ep-toggle-icon"></i>
                    </div>
                    <div id="sec-contact" class="collapse in ep-section-body">
                        <div class="ep-info-grid" style="margin-bottom:16px;">
                            <div class="ep-info-item">
                                <label>Email Address</label>
                                <div class="ep-value"><a href="mailto:<?= e($member->email); ?>"><?= e($member->email); ?></a></div>
                            </div>
                            <div class="ep-info-item">
                                <label>Mobile / Phone</label>
                                <div class="ep-value <?= empty($member->phonenumber) ? 'muted' : ''; ?>">
                                    <?= !empty($member->phonenumber) ? e($member->phonenumber) : 'Not provided'; ?>
                                </div>
                            </div>
                            <?php 
                            $custom_fields = get_custom_fields('staff');
                            $found_emergency = false;
                            $found_address = false;
                            foreach ($custom_fields as $field) {
                                if (stripos($field['name'], 'emergency') !== false) {
                                    $found_emergency = true;
                                    $value = get_custom_field_value($member->staffid, $field['id'], 'staff');
                                    ?>
                                    <div class="ep-info-item">
                                        <label><?= e($field['name']); ?></label>
                                        <div class="ep-value <?= empty($value) ? 'muted' : ''; ?>"><?= !empty($value) ? e($value) : 'Not available'; ?></div>
                                    </div>
                                    <?php
                                }
                                if (stripos($field['name'], 'address') !== false) {
                                    $found_address = true;
                                    $value = get_custom_field_value($member->staffid, $field['id'], 'staff');
                                    ?>
                                    <div class="ep-info-item">
                                        <label><?= e($field['name']); ?></label>
                                        <div class="ep-value <?= empty($value) ? 'muted' : ''; ?>"><?= !empty($value) ? e($value) : 'Not available'; ?></div>
                                    </div>
                                    <?php
                                }
                            }
                            if (!$found_emergency) {
                                ?>
                                <div class="ep-info-item">
                                    <label>Emergency Contact</label>
                                    <div class="ep-value muted">Not available</div>
                                </div>
                                <?php
                            }
                            if (!$found_address) {
                                ?>
                                <div class="ep-info-item">
                                    <label>Address</label>
                                    <div class="ep-value muted">Not available</div>
                                </div>
                                <?php
                            }
                            ?>
                            <div class="ep-info-item">
                                <label>Skype</label>
                                <div class="ep-value <?= empty($member->skype) ? 'muted' : ''; ?>">
                                    <?= !empty($member->skype) ? e($member->skype) : 'Not provided'; ?>
                                </div>
                            </div>
                            <div class="ep-info-item">
                                <label>LinkedIn</label>
                                <div class="ep-value <?= empty($member->linkedin) ? 'muted' : ''; ?>">
                                    <?php if (!empty($member->linkedin)) { ?>
                                        <a href="<?= e($member->linkedin); ?>" target="_blank">View Profile <i class="fa fa-external-link"></i></a>
                                    <?php } else { echo 'Not provided'; } ?>
                                </div>
                            </div>
                            <div class="ep-info-item">
                                <label>Facebook</label>
                                <div class="ep-value <?= empty($member->facebook) ? 'muted' : ''; ?>">
                                    <?php if (!empty($member->facebook)) { ?>
                                        <a href="<?= e($member->facebook); ?>" target="_blank">View Profile <i class="fa fa-external-link"></i></a>
                                    <?php } else { echo 'Not provided'; } ?>
                                </div>
                            </div>
                        </div>
                        <!-- Quick contact actions -->
                        <div class="ep-action-grid">
                            <?php if (!empty($member->phonenumber)) { ?>
                            <a href="tel:<?= e($member->phonenumber); ?>" class="btn btn-success btn-sm">
                                <i class="fa fa-phone"></i> Call
                            </a>
                            <?php } ?>
                            <a href="mailto:<?= e($member->email); ?>" class="btn btn-primary btn-sm">
                                <i class="fa fa-envelope"></i> Send Email
                            </a>
                        </div>
                    </div>
                </div>

                <!-- ═══════════════════════════════════════════════════════
                     SECTION 3 — Department Information
                ════════════════════════════════════════════════════════ -->
                <div class="ep-section">
                    <div class="ep-section-header" data-toggle="collapse" data-target="#sec-dept" aria-expanded="true">
                        <h5><i class="fa fa-sitemap" style="color:#283593;"></i> Department Information</h5>
                        <i class="fa fa-chevron-down ep-toggle-icon"></i>
                    </div>
                    <div id="sec-dept" class="collapse in ep-section-body">
                        <div class="ep-info-grid">
                            <div class="ep-info-item">
                                <label>Department(s)</label>
                                <div class="ep-value">
                                    <?php if (count($staff_departments) > 0) { ?>
                                        <?php foreach ($staff_departments as $sd) { ?>
                                            <div style="margin-bottom:4px;">
                                                <span class="label label-primary"><?= e($sd['name']); ?></span>
                                                <?php if (!empty($sd['email'])) { ?>
                                                    <small class="text-muted">&nbsp;<?= e($sd['email']); ?></small>
                                                <?php } ?>
                                            </div>
                                        <?php } ?>
                                    <?php } else { ?>
                                        <span class="muted">Not assigned to any department</span>
                                    <?php } ?>
                                </div>
                            </div>
                            <div class="ep-info-item">
                                <label>Reporting Manager</label>
                                <div class="ep-value muted">Not available in core</div>
                            </div>
                            <div class="ep-info-item">
                                <label>Team Name</label>
                                <div class="ep-value muted">Not available in core</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ═══════════════════════════════════════════════════════
                     SECTION 4 — Current Work
                ════════════════════════════════════════════════════════ -->
                <div class="ep-section">
                    <div class="ep-section-header" data-toggle="collapse" data-target="#sec-work" aria-expanded="true">
                        <h5><i class="fa fa-briefcase" style="color:#283593;"></i> Current Work</h5>
                        <i class="fa fa-chevron-down ep-toggle-icon"></i>
                    </div>
                    <div id="sec-work" class="collapse in ep-section-body">
                        <div class="row">
                            <!-- Leads -->
                            <div class="col-md-4 col-sm-6" style="margin-bottom:14px;">
                                <div class="ep-work-card leads">
                                    <div class="ep-work-header"><i class="fa fa-tty"></i> Assigned Leads</div>
                                    <div class="ep-work-body">
                                        <div class="ep-work-row"><span class="wl">Total Assigned</span><span class="wr"><?= $leads['total']; ?></span></div>
                                        <div class="ep-work-row"><span class="wl">New</span><span class="wr"><?= $leads['new']; ?></span></div>
                                        <div class="ep-work-row"><span class="wl">Follow-up</span><span class="wr"><?= $leads['followup']; ?></span></div>
                                        <div class="ep-work-row"><span class="wl">Interested</span><span class="wr"><?= $leads['interested']; ?></span></div>
                                        <div class="ep-work-row"><span class="wl">Customer Confirmed</span><span class="wr"><?= $leads['customer_confirmed']; ?></span></div>
                                        <div class="ep-work-row"><span class="wl">Lost</span><span class="wr" style="color:#c62828;"><?= $leads['lost']; ?></span></div>
                                    </div>
                                    <div class="ep-work-footer">
                                        <a href="<?= admin_url('leads?assigned=' . $member->staffid); ?>" class="btn btn-xs btn-default btn-block">
                                            <i class="fa fa-tty"></i> View Assigned Leads
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <!-- Tasks -->
                            <div class="col-md-4 col-sm-6" style="margin-bottom:14px;">
                                <div class="ep-work-card tasks">
                                    <div class="ep-work-header"><i class="fa fa-tasks"></i> Assigned Tasks</div>
                                    <div class="ep-work-body">
                                        <div class="ep-work-row"><span class="wl">Total</span><span class="wr"><?= $tasks['total']; ?></span></div>
                                        <div class="ep-work-row"><span class="wl">Pending</span><span class="wr" style="color:#e65100;"><?= $tasks['pending']; ?></span></div>
                                        <div class="ep-work-row"><span class="wl">Completed</span><span class="wr" style="color:#2e7d32;"><?= $tasks['completed']; ?></span></div>
                                    </div>
                                    <div class="ep-work-footer">
                                        <a href="<?= admin_url('tasks/list_tasks?assigned=' . $member->staffid); ?>" class="btn btn-xs btn-default btn-block">
                                            <i class="fa fa-tasks"></i> View Tasks
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <!-- Projects -->
                            <div class="col-md-4 col-sm-6" style="margin-bottom:14px;">
                                <div class="ep-work-card proj">
                                    <div class="ep-work-header"><i class="fa fa-bars"></i> Assigned Projects</div>
                                    <div class="ep-work-body">
                                        <div class="ep-work-row"><span class="wl">Total Projects</span><span class="wr"><?= $projects['total']; ?></span></div>
                                        <div class="ep-work-row"><span class="wl">Active Projects</span><span class="wr" style="color:#1565c0;"><?= $projects['active']; ?></span></div>
                                        <div class="ep-work-row"><span class="wl">Completed</span><span class="wr" style="color:#2e7d32;"><?= $projects['completed']; ?></span></div>
                                    </div>
                                    <div class="ep-work-footer">
                                        <a href="<?= admin_url('projects?member=' . $member->staffid); ?>" class="btn btn-xs btn-default btn-block">
                                            <i class="fa fa-bars"></i> View Projects
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ═══════════════════════════════════════════════════════
                     SECTION 5 — Follow-up Summary
                ════════════════════════════════════════════════════════ -->
                <div class="ep-section">
                    <div class="ep-section-header" data-toggle="collapse" data-target="#sec-followup" aria-expanded="true">
                        <h5><i class="fa fa-calendar-check-o" style="color:#283593;"></i> Follow-up Summary</h5>
                        <i class="fa fa-chevron-down ep-toggle-icon"></i>
                    </div>
                    <div id="sec-followup" class="collapse in ep-section-body">
                        <div class="ep-fu-grid" style="margin-bottom:16px;">
                            <div class="ep-fu-box today">
                                <div class="ep-fu-num"><?= $followups['today']; ?></div>
                                <div class="ep-fu-lbl">Today's Follow-ups</div>
                            </div>
                            <div class="ep-fu-box upcoming">
                                <div class="ep-fu-num"><?= $followups['upcoming']; ?></div>
                                <div class="ep-fu-lbl">Upcoming</div>
                            </div>
                            <div class="ep-fu-box overdue">
                                <div class="ep-fu-num"><?= $followups['overdue']; ?></div>
                                <div class="ep-fu-lbl">Overdue</div>
                            </div>
                            <div class="ep-fu-box last">
                                <div class="ep-fu-num" style="font-size:14px;padding-top:6px;">
                                    <?= $followups['last_date'] ? _d($followups['last_date']) : '—'; ?>
                                </div>
                                <div class="ep-fu-lbl">Last Follow-up Date</div>
                            </div>
                        </div>
                        <a href="<?= admin_url('follow_up_management/my_follow_ups?staff_id=' . $member->staffid); ?>"
                           class="btn btn-default btn-sm">
                            <i class="fa fa-calendar"></i> Open Follow-ups
                        </a>
                    </div>
                </div>

                <!-- ═══════════════════════════════════════════════════════
                     SECTION 6 — Work Performance
                ════════════════════════════════════════════════════════ -->
                <div class="ep-section">
                    <div class="ep-section-header" data-toggle="collapse" data-target="#sec-perf" aria-expanded="true">
                        <h5><i class="fa fa-bar-chart" style="color:#283593;"></i> Work Performance</h5>
                        <i class="fa fa-chevron-down ep-toggle-icon"></i>
                    </div>
                    <div id="sec-perf" class="collapse in ep-section-body">
                        <div class="ep-stat-row">
                            <div class="ep-stat-card">
                                <div class="ep-stat-num"><?= $performance['total_leads']; ?></div>
                                <div class="ep-stat-label">Total Leads Assigned</div>
                            </div>
                            <div class="ep-stat-card accent-green">
                                <div class="ep-stat-num"><?= $performance['leads_converted']; ?></div>
                                <div class="ep-stat-label">Leads Converted</div>
                            </div>
                            <div class="ep-stat-card accent-teal">
                                <div class="ep-stat-num"><?= $performance['conversion_rate']; ?>%</div>
                                <div class="ep-stat-label">Conversion Rate</div>
                            </div>
                            <div class="ep-stat-card accent-amber">
                                <div class="ep-stat-num"><?= $performance['tasks_completed']; ?></div>
                                <div class="ep-stat-label">Tasks Completed</div>
                            </div>
                            <div class="ep-stat-card">
                                <div class="ep-stat-num"><?= $performance['active_projects']; ?></div>
                                <div class="ep-stat-label">Active Projects</div>
                            </div>
                            <div class="ep-stat-card accent-red">
                                <div class="ep-stat-num"><?= $performance['pending_tasks']; ?></div>
                                <div class="ep-stat-label">Pending Tasks</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ═══════════════════════════════════════════════════════
                     SECTION 7 — Recent Activity
                ════════════════════════════════════════════════════════ -->
                <div class="ep-section">
                    <div class="ep-section-header" data-toggle="collapse" data-target="#sec-activity" aria-expanded="true">
                        <h5><i class="fa fa-history" style="color:#283593;"></i> Recent Activity <small class="text-muted" style="font-size:12px;font-weight:400;">(Last 10)</small></h5>
                        <i class="fa fa-chevron-down ep-toggle-icon"></i>
                    </div>
                    <div id="sec-activity" class="collapse in ep-section-body" style="padding-top:10px;padding-bottom:10px;">
                        <?php if (count($recent_activity) > 0) { ?>
                        <ul class="ep-activity-feed">
                            <?php foreach ($recent_activity as $log) { ?>
                            <li>
                                <div class="ep-activity-dot"></div>
                                <div class="tw-flex-1">
                                    <div style="font-size:13px;color:#37474f;"><?= e($log['description']); ?></div>
                                </div>
                                <div class="ep-activity-time" data-toggle="tooltip" data-title="<?= e(_dt($log['date'])); ?>">
                                    <?= e(time_ago($log['date'])); ?>
                                </div>
                            </li>
                            <?php } ?>
                        </ul>
                        <?php } else { ?>
                        <p class="text-muted" style="margin:0;font-size:13px;">No recent activity found for this staff member.</p>
                        <?php } ?>
                    </div>
                </div>

            </div><!-- /col-md-8 -->

            <!-- ─── RIGHT COLUMN ───────────────────────────────────────── -->
            <div class="col-md-4">

                <!-- ═══════════════════════════════════════════════════════
                     SECTION 8 — Quick Actions
                ════════════════════════════════════════════════════════ -->
                <div class="ep-section">
                    <div class="ep-section-header" data-toggle="collapse" data-target="#sec-actions">
                        <h5><i class="fa fa-bolt" style="color:#283593;"></i> Quick Actions</h5>
                        <i class="fa fa-chevron-down ep-toggle-icon"></i>
                    </div>
                    <div id="sec-actions" class="collapse in ep-section-body">
                        <div class="ep-action-grid" style="flex-direction:column;">
                            <?php if (staff_can('edit', 'staff')) { ?>
                            <a href="<?= admin_url('staff/member/' . $member->staffid); ?>" class="btn btn-primary">
                                <i class="fa fa-edit"></i> Edit Staff
                            </a>
                            <?php } ?>

                            <?php if (is_admin()) { ?>
                            <a href="<?= admin_url('misc/reset_staff_password/' . $member->staffid); ?>" class="btn btn-warning"
                               onclick="return confirm('Send password reset email to this staff member?');">
                                <i class="fa fa-key"></i> Reset Password
                            </a>
                            <?php } ?>

                            <?php if (staff_can('create', 'leads')) { ?>
                            <a href="<?= admin_url('leads?assigned=' . $member->staffid); ?>" class="btn btn-default">
                                <i class="fa fa-tty"></i> Assign Lead
                            </a>
                            <?php } ?>

                            <?php if (staff_can('create', 'tasks')) { ?>
                            <a href="<?= admin_url('tasks/list_tasks'); ?>" class="btn btn-default">
                                <i class="fa fa-tasks"></i> Assign Task
                            </a>
                            <?php } ?>

                            <a href="<?= admin_url('projects'); ?>" class="btn btn-default">
                                <i class="fa fa-bars"></i> Assign Project
                            </a>

                            <?php if (staff_can('edit', 'staff') && $member->staffid != get_staff_user_id()) { ?>
                            <a href="<?= admin_url('staff/change_staff_status/' . $member->staffid . '/' . ($member->active ? 0 : 1)); ?>"
                               class="btn btn-<?= $member->active ? 'danger' : 'success'; ?>"
                               onclick="return confirm('Are you sure you want to <?= $member->active ? 'disable' : 'enable'; ?> this account?');">
                                <i class="fa fa-<?= $member->active ? 'ban' : 'check-circle'; ?>"></i>
                                <?= $member->active ? 'Disable Account' : 'Enable Account'; ?>
                            </a>
                            <?php } ?>
                        </div>
                    </div>
                </div>

                <!-- Mini social links card -->
                <?php if (!empty($member->facebook) || !empty($member->linkedin) || !empty($member->skype)) { ?>
                <div class="ep-section">
                    <div class="ep-section-header" data-toggle="collapse" data-target="#sec-social">
                        <h5><i class="fa fa-share-alt" style="color:#283593;"></i> Social / Contact</h5>
                        <i class="fa fa-chevron-down ep-toggle-icon"></i>
                    </div>
                    <div id="sec-social" class="collapse in ep-section-body">
                        <div class="ep-action-grid" style="flex-direction:column;">
                            <?php if (!empty($member->facebook)) { ?>
                            <a href="<?= e($member->facebook); ?>" target="_blank" class="btn btn-default btn-sm">
                                <i class="fa-brands fa-facebook-f" style="color:#3b5998;"></i> Facebook
                            </a>
                            <?php } ?>
                            <?php if (!empty($member->linkedin)) { ?>
                            <a href="<?= e($member->linkedin); ?>" target="_blank" class="btn btn-default btn-sm">
                                <i class="fa-brands fa-linkedin-in" style="color:#0077b5;"></i> LinkedIn
                            </a>
                            <?php } ?>
                            <?php if (!empty($member->skype)) { ?>
                            <a href="skype:<?= e($member->skype); ?>?chat" class="btn btn-default btn-sm">
                                <i class="fa-brands fa-skype" style="color:#00aff0;"></i> Skype
                            </a>
                            <?php } ?>
                        </div>
                    </div>
                </div>
                <?php } ?>

                <!-- Staff Info Summary Card -->
                <div class="ep-section">
                    <div class="ep-section-header">
                        <h5><i class="fa fa-info-circle" style="color:#283593;"></i> Staff Info</h5>
                    </div>
                    <div class="ep-section-body" style="padding:14px 16px;">
                        <table class="table table-condensed" style="margin:0;font-size:13px;">
                            <tr>
                                <td class="text-muted" style="border:none;padding:5px 0;width:46%;">Staff ID</td>
                                <td style="border:none;padding:5px 0;font-weight:600;">#<?= e($member->staffid); ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted" style="border-top:1px solid #f5f5f5;padding:5px 0;">Username</td>
                                <td style="border-top:1px solid #f5f5f5;padding:5px 0;font-weight:600;"><?= e($member->username); ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted" style="border-top:1px solid #f5f5f5;padding:5px 0;">Hourly Rate</td>
                                <td style="border-top:1px solid #f5f5f5;padding:5px 0;font-weight:600;"><?= e($member->hourly_rate); ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted" style="border-top:1px solid #f5f5f5;padding:5px 0;">Administrator</td>
                                <td style="border-top:1px solid #f5f5f5;padding:5px 0;">
                                    <?= $member->admin ? '<span class="label label-danger">Yes</span>' : '<span class="label label-default">No</span>'; ?>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted" style="border-top:1px solid #f5f5f5;padding:5px 0;">Status</td>
                                <td style="border-top:1px solid #f5f5f5;padding:5px 0;">
                                    <span class="ep-badge <?= $member->active ? 'active' : 'inactive'; ?>" style="font-size:11px;">
                                        <?= $member->active ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php if (!empty($member->datecreated)) { ?>
                            <tr>
                                <td class="text-muted" style="border-top:1px solid #f5f5f5;padding:5px 0;">Joined</td>
                                <td style="border-top:1px solid #f5f5f5;padding:5px 0;font-weight:600;"><?= _d($member->datecreated); ?></td>
                            </tr>
                            <?php } ?>
                        </table>
                    </div>
                </div>

            </div><!-- /col-md-4 -->
        </div><!-- /row -->

    </div><!-- /content -->
</div><!-- /wrapper -->

<?php init_tail(); ?>
<script>
$(function() {
    // Collapsible section toggle — rotate chevron
    $('[data-toggle="collapse"]').on('click', function() {
        $(this).find('.ep-toggle-icon').toggleClass('fa-chevron-down fa-chevron-up');
    });

    // Tooltips
    $('[data-toggle="tooltip"]').tooltip();
});
</script>
</body>
</html>