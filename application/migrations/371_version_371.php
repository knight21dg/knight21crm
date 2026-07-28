<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_371 extends CI_Migration
{
    public function up(): void
    {
        if (!$this->db->table_exists(db_prefix() . 'field_expenses')) {
            $this->db->query('CREATE TABLE ' . db_prefix() . "field_expenses (
                id INT AUTO_INCREMENT PRIMARY KEY,
                staff_id INT NOT NULL,
                lead_id INT NULL DEFAULT NULL,
                expense_date DATE NOT NULL,
                category VARCHAR(100) NOT NULL,
                amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                payment_method VARCHAR(100) DEFAULT '',
                description TEXT DEFAULT NULL,
                remarks TEXT DEFAULT NULL,
                receipt VARCHAR(255) DEFAULT '',
                created_at DATETIME DEFAULT NULL,
                updated_at DATETIME DEFAULT NULL,
                INDEX idx_staff_id (staff_id),
                INDEX idx_expense_date (expense_date),
                INDEX idx_category (category)
            ) ENGINE=InnoDB DEFAULT CHARSET=" . $this->db->char_set . ';');
        }
    }
}
