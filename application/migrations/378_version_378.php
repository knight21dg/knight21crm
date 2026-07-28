<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Bug fix - Customer Work Status & Progress desync. Before this
 * engagement's Work-Status-driven progress rule existed, `tblclients`
 * rows could accumulate `work_status`/`progress` combinations that never
 * went through Clients_model::update()/update_assignment_field() (e.g.
 * QA/test data written directly), leaving rows like
 * work_status='Completed' with progress=0 stuck out of sync - the exact
 * report from the bug ("Rows display Work Status = Completed while
 * Progress = 0%"). The Customers list itself is now self-healing
 * (application/views/admin/tables/clients.php resolves the displayed
 * progress through resolve_client_progress_for_status() on every render,
 * application/helpers/clients_helper.php), but the underlying stored
 * `progress` column should also be corrected for any other consumer
 * that reads it directly (exports, reports, API). One-time data repair,
 * reusing the exact same rule as resolve_client_progress_for_status()
 * (Completed -> 100, Pending/Cancelled -> 0, In Progress with 0% -> 25,
 * On Hold and everything else -> unchanged) rather than a fixed
 * per-customer patch.
 */
class Migration_Version_378 extends CI_Migration
{
    public function up(): void
    {
        $prefix = db_prefix();

        $this->db->where('work_status', 'Completed');
        $this->db->where('progress !=', 100);
        $this->db->update($prefix . 'clients', ['progress' => 100]);

        $this->db->where_in('work_status', ['Pending', 'Cancelled']);
        $this->db->where('progress !=', 0);
        $this->db->update($prefix . 'clients', ['progress' => 0]);

        $this->db->where('work_status', 'In Progress');
        $this->db->where('progress', 0);
        $this->db->update($prefix . 'clients', ['progress' => 25]);
    }
}
