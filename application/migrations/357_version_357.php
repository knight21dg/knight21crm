<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_357 extends CI_Migration
{
    public function up(): void
    {
        // =====================================================================
        // NOTIFICATION CENTER (Phase 6) — registers the one shared email
        // template this module sends for Case Assigned/Reassigned/High
        // Priority/Reopened notifications (see
        // Follow_ups_model::notify_case_event() and
        // modules/follow_up_management/libraries/mails/
        // Follow_up_case_notification.php). create_email_template() is
        // idempotent (no-ops if the slug already exists), so this is safe to
        // run alongside the module's own activation-hook registration of the
        // same template for any future re-activation - purely additive, no
        // existing template touched.
        // =====================================================================
        follow_up_management_load_language();
        follow_up_management_register_email_template();
    }
}
