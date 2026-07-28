<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Single reusable source of truth for the centralized Bank Details record.
 * Reads from the standard options system (get_option()) - values are
 * configured under Setup -> Finance -> Bank Details.
 *
 * @return array
 */
function get_bank_details()
{
    $qr = get_option('bank_qr_code');

    return [
        'bank_name'      => get_option('bank_name'),
        'account_name'   => get_option('bank_account_name'),
        'account_number' => get_option('bank_account_number'),
        'ifsc_code'      => get_option('bank_ifsc_code'),
        'branch_name'    => get_option('bank_branch_name'),
        'swift_code'     => get_option('bank_swift_code'),
        'account_type'   => get_option('bank_account_type'),
        'upi_id'         => get_option('bank_upi_id'),
        'instructions'   => get_option('bank_payment_instructions'),
        'qr_code_url'    => $qr != '' ? base_url('uploads/company/' . $qr) : '',
    ];
}

/**
 * Handles the optional QR Code image upload/removal for the Bank Details
 * settings tab. Mirrors handle_company_logo_upload() (application/helpers/upload_helper.php)
 * and reuses the existing "company" upload path - no new upload type needed.
 *
 * @return bool
 */
function handle_bank_qr_code_upload()
{
    if (!isset($_FILES['bank_qr_code']) || empty($_FILES['bank_qr_code']['name'])) {
        return false;
    }

    if (_perfex_upload_error($_FILES['bank_qr_code']['error'])) {
        set_alert('warning', _perfex_upload_error($_FILES['bank_qr_code']['error']));

        return false;
    }

    $tmpFilePath = $_FILES['bank_qr_code']['tmp_name'];
    if (empty($tmpFilePath)) {
        return false;
    }

    $extension          = strtolower(pathinfo($_FILES['bank_qr_code']['name'], PATHINFO_EXTENSION));
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];

    if (!in_array($extension, $allowed_extensions)) {
        set_alert('warning', _l('validation_extension_not_allowed'));

        return false;
    }

    $path = get_upload_path_by_type('company');
    _maybe_create_upload_path($path);

    $filename    = 'bank_qr_code_' . time() . '.' . $extension;
    $newFilePath = $path . $filename;

    if (move_uploaded_file($tmpFilePath, $newFilePath)) {
        // Remove the previous QR image, if any, to avoid orphaned files
        $existing = get_option('bank_qr_code');
        if ($existing != '' && file_exists($path . $existing)) {
            unlink($path . $existing);
        }

        update_option('bank_qr_code', $filename);

        return true;
    }

    return false;
}
