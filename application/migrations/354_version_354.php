<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_354 extends CI_Migration
{
    public function up(): void
    {
        // =====================================================================
        // Auto-activate the Business Departments and Bank Details modules on
        // deployment. Modules only run (their init file is required, hooks
        // registered, helpers loaded) once they have an `active = 1` row in
        // tblmodules - normally set by a staff member manually visiting
        // Setup -> Modules and clicking Activate. Without this, every page
        // that unconditionally calls the Business Departments helper
        // functions (Customers, Projects, Leads) fatal-errors with
        // "Call to undefined function" until someone performs that manual
        // step, and the Bank Details settings tab/card silently never appear.
        //
        // This uses the exact same official activation path App_modules uses
        // for its own bundled modules (see 230_version_230.php) rather than a
        // hand-rolled INSERT: it inserts the tblmodules row, includes the
        // module's init file for the current request, and fires the standard
        // activate hooks. is_active() guards against re-firing those hooks on
        // an install where an admin already activated (or deliberately
        // deactivated) either module by hand.
        // =====================================================================
        $this->app_modules->initialize();

        foreach (['business_departments', 'bank_details'] as $module_name) {
            if (!$this->app_modules->is_active($module_name)) {
                $this->app_modules->activate($module_name);
            }
        }
    }
}
