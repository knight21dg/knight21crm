<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_364 extends CI_Migration
{
    public function up(): void
    {
        // One-time backfill for a confirmed bug: Projects_model::update_assignment_field()
        // used to set tblprojects.assigned_employee without also inserting
        // the matching tblproject_members row that Projects_model::is_member()
        // and every "which staff can see this project" query (including the
        // Development Portal's) actually key off. The model method itself is
        // already fixed to do this going forward - this repairs every
        // project that was assigned before that fix landed. Guarded with
        // NOT EXISTS so it is safe to run more than once and never inserts
        // a duplicate row.
        $this->db->query('
            INSERT INTO `' . db_prefix() . 'project_members` (`project_id`, `staff_id`)
            SELECT p.`id`, p.`assigned_employee`
            FROM `' . db_prefix() . 'projects` p
            WHERE p.`assigned_employee` IS NOT NULL
            AND NOT EXISTS (
                SELECT 1 FROM `' . db_prefix() . 'project_members` pm
                WHERE pm.`project_id` = p.`id` AND pm.`staff_id` = p.`assigned_employee`
            );
        ');
    }
}
