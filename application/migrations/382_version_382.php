<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Single project status - one-time backfill. The fork's custom
 * `work_status` column and the core Perfex `status` column were two
 * independent write paths before Projects_model::reconcile_single_status()
 * unified them, so existing rows could disagree - e.g. a project
 * displayed 'In Progress' in the Admin Projects list while its core
 * status column said 3 (On Hold), which made the employee Development
 * Portal show a different status than the Admin list. This migration
 * makes the stored columns agree everywhere, with the Work Status
 * label (the fork's primary status UI) winning where both were set.
 * It also applies the same Work Status -> Progress rule used by
 * resolve_client_progress_for_status() to rows whose stored progress
 * drifted from their status, and backfills the tblproject_members row
 * for every assigned_employee that was never mirrored there (the
 * root-cause fix for "assigned staff can't see their own project").
 */
class Migration_Version_382 extends CI_Migration
{
    public function up(): void
    {
        $prefix = db_prefix();

        $workStatusToStatus = [
            'Pending'     => 1,
            'In Progress' => 2,
            'On Hold'     => 3,
            'Completed'   => 4,
            'Cancelled'   => 5,
        ];
        $statusToWorkStatus = array_flip($workStatusToStatus);

        $projects = $this->db->select('id, status, work_status, progress, assigned_employee')
            ->get($prefix . 'projects')
            ->result();

        foreach ($projects as $project) {
            $workStatus = trim((string) $project->work_status);
            $update     = [];

            if (isset($workStatusToStatus[$workStatus])) {
                if ((int) $project->status !== $workStatusToStatus[$workStatus]) {
                    $update['status'] = $workStatusToStatus[$workStatus];
                }

                switch ($workStatus) {
                    case 'Completed':
                        if ((int) $project->progress !== 100) {
                            $update['progress'] = 100;
                        }
                        break;
                    case 'Pending':
                    case 'Cancelled':
                        if ((int) $project->progress !== 0) {
                            $update['progress'] = 0;
                        }
                        break;
                    case 'In Progress':
                        if ((int) $project->progress === 0) {
                            $update['progress'] = 25;
                        }
                        break;
                }
            } elseif (isset($statusToWorkStatus[(int) $project->status])) {
                $update['work_status'] = $statusToWorkStatus[(int) $project->status];
            }

            if ($update) {
                $this->db->where('id', $project->id);
                $this->db->update($prefix . 'projects', $update);
            }
        }

        // Backfill tblproject_members for every assigned_employee that
        // was never mirrored there - see
        // Projects_model::update_assignment_field() (single source of
        // truth: assigned_employee is "who owns this project", and the
        // membership row is what lets them see/open it).
        $assigned = $this->db->where('assigned_employee IS NOT NULL', null, false)
            ->where('assigned_employee !=', '')
            ->get($prefix . 'projects')
            ->result();

        foreach ($assigned as $project) {
            $exists = $this->db->where('project_id', $project->id)
                ->where('staff_id', $project->assigned_employee)
                ->get($prefix . 'project_members')
                ->row();

            if (!$exists) {
                $this->db->insert($prefix . 'project_members', [
                    'project_id' => $project->id,
                    'staff_id'   => $project->assigned_employee,
                ]);
            }
        }
    }
}
