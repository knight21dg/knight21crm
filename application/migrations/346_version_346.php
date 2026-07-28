<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_346 extends CI_Migration
{
    public function up(): void
    {
        // =====================================================================
        // CURRENCIES - India-ready Sales config (INR default, USD, EUR)
        // =====================================================================
        $this->load->model('currencies_model');

        if (!$this->currencies_model->get_by_name('INR')) {
            $this->currencies_model->add([
                'name'               => 'INR',
                'symbol'             => "\xE2\x82\xB9", // Rupee sign (U+20B9)
                'decimal_separator'  => '.',
                'thousand_separator' => ',',
                'placement'          => 'before',
            ]);
        }

        if (!$this->currencies_model->get_by_name('EUR')) {
            $this->currencies_model->add([
                'name'               => 'EUR',
                'symbol'             => "\xE2\x82\xAC", // Euro sign (U+20AC)
                'decimal_separator'  => '.',
                'thousand_separator' => ',',
                'placement'          => 'before',
            ]);
        }

        // Set INR as the default currency
        $this->db->update(db_prefix() . 'currencies', ['isdefault' => 0]);
        $this->db->where('name', 'INR');
        $this->db->update(db_prefix() . 'currencies', ['isdefault' => 1]);

        // =====================================================================
        // TAXES - Standard Indian GST rates
        // =====================================================================
        $this->load->model('taxes_model');
        $existing_taxes = $this->taxes_model->get();

        $taxes_to_add = [
            ['name' => 'GST', 'taxrate' => 18],
            ['name' => 'GST', 'taxrate' => 12],
            ['name' => 'GST', 'taxrate' => 5],
            ['name' => 'IGST', 'taxrate' => 18],
        ];

        foreach ($taxes_to_add as $tax) {
            $already_exists = false;
            foreach ($existing_taxes as $et) {
                if ($et['name'] == $tax['name'] && (float) $et['taxrate'] === (float) $tax['taxrate']) {
                    $already_exists = true;
                    break;
                }
            }
            if (!$already_exists) {
                $this->taxes_model->add($tax);
            }
        }

        // =====================================================================
        // PAYMENT MODES
        // =====================================================================
        $this->load->model('payment_modes_model');
        $this->db->select('name');
        $existing_modes = array_column($this->db->get(db_prefix() . 'payment_modes')->result_array(), 'name');

        $modes_to_add = ['UPI', 'Bank Transfer', 'Cash', 'Cheque', 'Credit Card', 'Net Banking'];

        foreach ($modes_to_add as $mode_name) {
            if (!in_array($mode_name, $existing_modes)) {
                $this->payment_modes_model->add([
                    'name'                => $mode_name,
                    'description'         => '',
                    'active'              => 1,
                    'show_on_pdf'         => 1,
                    'selected_by_default' => 0,
                ]);
            }
        }

        // =====================================================================
        // DEFAULT ITEMS - Knight21 Services
        // =====================================================================
        $this->load->model('invoice_items_model');
        $existing_items = array_column($this->invoice_items_model->get(), 'description');

        $items_to_add = [
            ['description' => 'Website Development', 'long_description' => 'Custom website design and development services.', 'unit' => 'Project'],
            ['description' => 'School ERP License', 'long_description' => 'Annual license for the School ERP platform.', 'unit' => 'License'],
            ['description' => 'ERP Customization', 'long_description' => 'Custom feature development and configuration for ERP systems.', 'unit' => 'Hour'],
            ['description' => 'Mobile App Development', 'long_description' => 'Android/iOS mobile application design and development.', 'unit' => 'Project'],
            ['description' => 'SEO Service', 'long_description' => 'Search engine optimization services.', 'unit' => 'Month'],
            ['description' => 'Digital Marketing', 'long_description' => 'Digital marketing and social media promotion services.', 'unit' => 'Month'],
            ['description' => 'Domain Registration', 'long_description' => 'Domain name registration/renewal.', 'unit' => 'Year'],
            ['description' => 'Web Hosting', 'long_description' => 'Web hosting services.', 'unit' => 'Year'],
            ['description' => 'Server Maintenance', 'long_description' => 'Ongoing server maintenance and monitoring.', 'unit' => 'Month'],
            ['description' => 'Software AMC', 'long_description' => 'Annual maintenance contract for software support.', 'unit' => 'Year'],
            ['description' => 'Technical Support', 'long_description' => 'On-demand technical support services.', 'unit' => 'Hour'],
            ['description' => 'Training & Installation', 'long_description' => 'On-site/remote training and installation services.', 'unit' => 'Session'],
        ];

        foreach ($items_to_add as $item) {
            if (!in_array($item['description'], $existing_items)) {
                $this->invoice_items_model->add([
                    'description'      => $item['description'],
                    'long_description' => $item['long_description'],
                    'rate'             => 0,
                    'unit'             => $item['unit'],
                    'group_id'         => 0,
                ]);
            }
        }
    }
}
