<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Task Notes: the same shared-notes pattern as Project Notes
 * (tblproject_notes, application/migrations/109_version_109.php +
 * 340_version_340.php's later title/dateadded additions) - Admin and the
 * Development Portal both read/write this ONE table, so a task's notes
 * are never duplicated or out of sync between the two sides, exactly
 * mirroring how tblproject_notes already works. Schema copied field-for-
 * field from the current tblproject_notes (id, title, content, staff_id,
 * dateadded), only the FK column renamed project_id -> task_id.
 *
 * tbltasks already has a `manager_note` column, but it is NOT a spare
 * field available for this - it is a single-value column already owned
 * exclusively by the Manager Portal module
 * (Manager_portal_model::set_task_manager_note()/get_tasks()), a
 * different, unrelated concept (one manager-only annotation, no author/
 * history) from the shared, multi-author Admin<->Staff Task Notes this
 * migration introduces. No existing task data is touched.
 */
class Migration_Version_385 extends CI_Migration
{
    public function up(): void
    {
        $prefix = db_prefix();

        if (! $this->db->table_exists($prefix . 'task_notes')) {
            $this->db->query(
                'CREATE TABLE `' . $prefix . 'task_notes` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `task_id` int(11) NOT NULL,
                    `title` varchar(255) DEFAULT NULL,
                    `content` mediumtext NOT NULL,
                    `staff_id` int(11) NOT NULL,
                    `dateadded` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;'
            );
        }
    }
}
