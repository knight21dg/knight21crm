<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Bank Details
Description: Centralized Bank Details management, shown on customer Invoices, Estimates, Proposals and Credit Notes
Version: 1.0.0
Requires at least: 3.3.*
*/

require_once __DIR__ . '/helpers/bank_details_helper.php';

hooks()->add_action('admin_init', 'bank_details_module_init');

function bank_details_module_init(): void
{
    $CI = &get_instance();

    $CI->app->add_settings_section_child(
        'finance',
        'bank_details',
        [
            'name'     => _l('settings_bank_details'),
            'view'     => 'bank_details/settings',
            'position' => 36,
            'icon'     => 'fa-regular fa-building-columns',
        ]
    );
}

/**
 * Fires on every Settings save (application/controllers/admin/Settings.php)
 * regardless of which tab was submitted. Only acts when the Bank Details
 * QR code file input was actually present in the request.
 */
hooks()->add_action('before_update_system_options', 'handle_bank_qr_code_upload');
