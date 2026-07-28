<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_373 extends CI_Migration
{
    public function up(): void
    {
        // Performance: the native Customers list's new "Converted From"
        // column/filter (application/helpers/lead_workspace_helper.php's
        // lead_converted_from_lookup_expr()) runs a correlated subquery
        // against tblleads.client_id for every customer row. Index it so
        // that stays a single-row index seek instead of a full table scan
        // as the Leads table grows - no schema/data change beyond the index.
        $indexes = $this->db->query("SHOW INDEX FROM " . db_prefix() . "leads WHERE Key_name = 'client_id'")->result_array();

        if (empty($indexes)) {
            $this->db->query('ALTER TABLE ' . db_prefix() . 'leads ADD INDEX client_id (client_id)');
        }
    }
}
