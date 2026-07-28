<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_361 extends CI_Migration
{
    public function up(): void
    {
        // =====================================================================
        // Lead form: dependent State -> District dropdowns. Reuses the two
        // EXISTING "State"/"District" select-type Custom Fields (fieldto
        // 'leads') - no new columns, no new fields. Two changes only:
        //   1. State's options are narrowed to exactly the two states in
        //      scope (already exactly this before this migration - kept
        //      here so the migration is the single source of truth/is
        //      re-runnable safely).
        //   2. District's options become the full union of every AP +
        //      Telangana district (was a small unscoped mixed sample
        //      before). The state->district GROUPING (which districts
        //      belong to which state) is not stored in the DB - there is
        //      no column for it without a schema change, which this task
        //      explicitly ruled out - so that grouping lives in the
        //      client-side script that narrows the rendered options
        //      (see follow_up_management_lead_state_district_cascade_script()
        //      in modules/follow_up_management/follow_up_management.php).
        //      This flat union list is what keeps render_custom_fields()'s
        //      existing generic renderer working unchanged, and keeps
        //      every already-saved lead's district value found/pre-selected
        //      exactly as before (backward compatible).
        // Both fields are also marked required, reusing Perfex's own
        // existing required-custom-field validation (no new validation
        // code) - matches "Both State and District remain required."
        // =====================================================================

        $state_field = $this->db->where('fieldto', 'leads')->where('name', 'State')->get(db_prefix() . 'customfields')->row();
        $district_field = $this->db->where('fieldto', 'leads')->where('name', 'District')->get(db_prefix() . 'customfields')->row();

        if ($state_field) {
            $this->db->where('id', $state_field->id)->update(db_prefix() . 'customfields', [
                'options'  => "Andhra Pradesh,\nTelangana",
                'required' => 1,
            ]);
        }

        if ($district_field) {
            $districts = [
                // Andhra Pradesh
                'Alluri Sitharama Raju', 'Anakapalli', 'Anantapur', 'Annamayya', 'Bapatla',
                'Chittoor', 'Dr. B.R. Ambedkar Konaseema', 'East Godavari', 'Eluru', 'Guntur',
                'Kakinada', 'Krishna', 'Kurnool', 'Nandyal', 'NTR', 'Palnadu',
                'Parvathipuram Manyam', 'Prakasam', 'SPSR Nellore', 'Srikakulam', 'Tirupati',
                'Visakhapatnam', 'Vizianagaram', 'West Godavari', 'YSR Kadapa',
                // Telangana
                'Adilabad', 'Bhadradri Kothagudem', 'Hanamkonda', 'Hyderabad', 'Jagtial',
                'Jangaon', 'Jayashankar Bhupalpally', 'Jogulamba Gadwal', 'Kamareddy', 'Karimnagar',
                'Khammam', 'Komaram Bheem Asifabad', 'Mahabubabad', 'Mahabubnagar', 'Mancherial',
                'Medak', 'Medchal–Malkajgiri', 'Mulugu', 'Nagarkurnool', 'Nalgonda', 'Narayanpet',
                'Nirmal', 'Nizamabad', 'Peddapalli', 'Rajanna Sircilla', 'Ranga Reddy', 'Sangareddy',
                'Siddipet', 'Suryapet', 'Vikarabad', 'Wanaparthy', 'Warangal', 'Yadadri Bhuvanagiri',
            ];

            $this->db->where('id', $district_field->id)->update(db_prefix() . 'customfields', [
                'options'  => implode(",\n", $districts),
                'required' => 1,
            ]);
        }
    }
}
