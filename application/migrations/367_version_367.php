<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_367 extends CI_Migration
{
    public function up(): void
    {
        // Field Executive Portal - auto-activate on deployment, same
        // pattern as migrations 354/355 (business_departments/
        // follow_up_management): a module's init file only runs once
        // tblmodules has an active=1 row for it, normally set by hand via
        // Setup -> Modules. is_active() guards against re-firing the
        // activate hooks if an admin already (de)activated it manually.
        $this->app_modules->initialize();

        if (!$this->app_modules->is_active('field_portal')) {
            $this->app_modules->activate('field_portal');
        }
    }
}
