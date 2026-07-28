<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Manager Portal Phase 3 (Project & Task Monitoring) - a single private
 * note field the Operations Manager can set per task, distinct from the
 * public tbltask_comments thread (visible to assignees/followers) - this
 * is a Manager-only annotation, not a conversation. No new table: a
 * single nullable column on the existing tbltasks row, same footprint
 * as every other addition this engagement has made to existing tables
 * rather than inventing a parallel one.
 */
class Migration_Version_377 extends CI_Migration
{
    public function up(): void
    {
        $prefix = db_prefix();

        if (!$this->db->field_exists('manager_note', $prefix . 'tasks')) {
            $this->db->query('ALTER TABLE ' . $prefix . 'tasks ADD COLUMN manager_note MEDIUMTEXT NULL DEFAULT NULL');
        }
    }
}
