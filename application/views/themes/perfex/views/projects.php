<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<h4 class="tw-mt-0 tw-font-bold tw-text-lg tw-text-neutral-700 tickets-summary-heading">
    <?= _l('projects_summary'); ?>
</h4>

<div class="tw-mb-2">
    <?php get_template_part('projects/project_summary'); ?>
</div>

<h4 class="tw-mt-0 tw-mb-3 tw-font-semibold tw-text-lg tw-text-neutral-700 section-heading section-heading-projects">
    <?= _l('clients_my_projects'); ?>
</h4>

<div class="panel_s">
    <div class="panel-body">
        <table class="table dt-table table-projects" data-order-col="6" data-order-type="desc">
            <thead>
                <tr>
                    <th class="th-project-name">
                        <?= _l('project_name'); ?>
                    </th>
                    <th class="th-project-department">
                        <?= _l('project_department'); ?>
                    </th>
                    <th class="th-project-assigned-employee">
                        <?= _l('project_assigned_employee'); ?>
                    </th>
                    <th class="th-project-work-status">
                        <?= _l('project_work_status'); ?>
                    </th>
                    <th class="th-project-progress">
                        <?= _l('project_progress'); ?>
                    </th>
                    <th class="th-project-start-date">
                        <?= _l('project_start_date'); ?>
                    </th>
                    <th class="th-project-deadline">
                        <?= _l('project_deadline'); ?>
                    </th>
                    <th class="th-project-last-updated">
                        <?= _l('project_last_updated'); ?>
                    </th>
                    <th class="th-project-billing-type">
                        <?= _l('project_billing_type'); ?>
                    </th>
                    <?php
                     $custom_fields = get_custom_fields('projects', ['show_on_client_portal' => 1]);

foreach ($custom_fields as $field) { ?>
                    <th><?= e($field['name']); ?>
                    </th>
                    <?php } ?>
                    <th class="th-project-status-description">
                        <?= _l('project_status_description'); ?>
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($projects as $project) { ?>
                <tr>
                    <td><a
                            href="<?= site_url('clients/project/' . $project['id']); ?>"><?= e($project['name']); ?></a>
                    </td>
                    <td>
                        <?= $project['department'] != '' ? e(get_business_department_name($project['department'])) : '-'; ?>
                    </td>
                    <td>
                        <?= $project['assigned_employee'] != '' ? e(get_staff_full_name($project['assigned_employee'])) : '-'; ?>
                    </td>
                    <td>
                        <?php if ($project['work_status'] != '') {
                            $workStatusColor = get_work_status_color($project['work_status']); ?>
                        <span class="label" style="color:<?= e($workStatusColor); ?>;border:1px solid <?= e(adjust_hex_brightness($workStatusColor, 0.4)); ?>;background:<?= e(adjust_hex_brightness($workStatusColor, 0.04)); ?>;">
                            <?= e($project['work_status']); ?>
                        </span>
                        <?php } else { ?>
                        -
                        <?php } ?>
                    </td>
                    <td>
                        <?php
                        // Same shared resolver as the Admin Projects list
                        // (Projects_model::resolve_progress_value()) - guarantees
                        // this always matches what Admin sees, with no duplicated
                        // branching logic. Never modified for this display fix.
                        $progressValue = $this->projects_model->resolve_progress_value($project);

                        // Display-only estimate for the Customer Portal: the resolver
                        // returns a bare 0 both when a project is genuinely 0% along
                        // AND when its progress was simply never set (progress_from_tasks
                        // off, progress column at its default). Those two cases are
                        // indistinguishable from the number alone. For "Not Started" (1)
                        // and "Cancelled" (5) a real 0% is correct as-is, and "Finished"
                        // (4) is already forced to 100 by the resolver, so only "In
                        // Progress" (2) and "On Hold" (3) get a display estimate here,
                        // and only when there's no real signal (no task-based
                        // calculation and no manually set value) to show instead.
                        $hasRealProgressSignal = $project['progress_from_tasks'] == 1 || (int) $project['progress'] > 0;
                        if (!$hasRealProgressSignal && $project['status'] == 2) {
                            // In Progress with nothing to go on - estimate from how far
                            // the project is through its own timeline (same concept
                            // already used for "project_time_left_percent" on the
                            // project detail page) rather than a single fixed number,
                            // clamped to a reasonable 30-90% band.
                            $progressValue = customer_portal_estimate_progress_from_timeline($project['start_date'], $project['deadline'], 30, 90);
                        } elseif (!$hasRealProgressSignal && $project['status'] == 3) {
                            // On Hold with nothing to go on - keep as a flat, clearly
                            // "paused partway" placeholder rather than 0%.
                            $progressValue = 50;
                        }

                        $progressColor = get_progress_bar_color($progressValue); ?>
                        <div class="progress progress-bar-mini" style="min-width:100px;">
                            <div class="progress-bar" role="progressbar" aria-valuenow="<?= $progressValue; ?>" data-percent="<?= $progressValue; ?>" aria-valuemin="0" aria-valuemax="100" style="width:<?= $progressValue; ?>%;background-color:<?= e($progressColor); ?>;">
                                <?= $progressValue; ?>%
                            </div>
                        </div>
                    </td>
                    <td
                        data-order="<?= e($project['start_date']); ?>">
                        <?= e(_d($project['start_date'])); ?>
                    </td>
                    <td
                        data-order="<?= e($project['deadline']); ?>">
                        <?= e(_d($project['deadline'])); ?>
                    </td>
                    <td>
                        <?= $project['last_updated'] != '' ? '<span data-toggle="tooltip" data-title="' . e(_dt($project['last_updated'])) . '">' . e(time_ago($project['last_updated'])) . '</span>' : '-'; ?>
                    </td>
                    <td>
                        <?php
   if ($project['billing_type'] == 1) {
       $type_name = 'project_billing_type_fixed_cost';
   } elseif ($project['billing_type'] == 2) {
       $type_name = 'project_billing_type_project_hours';
   } else {
       $type_name = 'project_billing_type_project_task_hours';
   }
                    echo _l($type_name);
                    ?>
                    </td>
                    <?php foreach ($custom_fields as $field) { ?>
                    <td><?= get_custom_field_value($project['id'], $field['id'], 'projects'); ?>
                    </td>
                    <?php } ?>
                    <td>
                        <?= $project['status_description'] != '' ? nl2br(e($project['status_description'])) : '-'; ?>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>