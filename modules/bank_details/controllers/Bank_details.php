<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Bank_details extends AdminController
{
    public function remove_qr_code()
    {
        if (staff_cant('delete', 'settings')) {
            access_denied('settings');
        }

        $filename = get_option('bank_qr_code');

        if ($filename != '') {
            $path = get_upload_path_by_type('company') . $filename;
            if (file_exists($path)) {
                unlink($path);
            }
        }

        update_option('bank_qr_code', '');

        redirect(admin_url('settings?group=bank_details'));
    }
}
