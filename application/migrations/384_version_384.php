<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Project Notes: migrate legacy Status Description values into the shared
 * project note store.
 *
 * tblproject_notes is Perfex's existing project note table (id, project_id,
 * title, content, staff_id, dateadded) - already used by the native admin
 * Projects -> Notes tab. It becomes the one shared note source for both the
 * Admin side and the Staff Development Portal. The additions themselves
 * (guard clauses, latest-note cache sync in Projects_model) are code-only;
 * this migration only seeds the existing single-value status_description
 * text into the notes table so no existing information disappears.
 *
 * tblprojects.status_description is kept as a display cache: every note
 * write refreshes it with the newest note (Projects_model::save_note()/
 * update_note()/delete_note() -> _refresh_latest_note_cache()), so the
 * Admin Projects list and Customer Portal keep working without query
 * changes, while the full history with author + timestamp lives in
 * tblproject_notes.
 */
class Migration_Version_384 extends CI_Migration
{
    public function up(): void
    {
        $prefix = db_prefix();

        $this->db->query(
            'INSERT INTO `' . $prefix . 'project_notes` (`project_id`, `title`, `content`, `staff_id`, `dateadded`) ' .
            'SELECT p.id, NULL, p.status_description, ' .
            'CASE WHEN p.addedfrom > 0 THEN p.addedfrom ELSE 1 END, ' .
            'COALESCE(p.last_updated, p.project_created, NOW()) ' .
            'FROM `' . $prefix . 'projects` p ' .
            'WHERE p.status_description IS NOT NULL ' .
            'AND NULLIF(TRIM(p.status_description), \'\') IS NOT NULL ' .
            'AND NOT EXISTS (SELECT 1 FROM `' . $prefix . 'project_notes` n WHERE n.project_id = p.id)'
        );
    }
}