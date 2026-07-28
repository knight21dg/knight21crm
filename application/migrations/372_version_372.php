<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_372 extends CI_Migration
{
    public function up(): void
    {
        if (!$this->db->table_exists(db_prefix() . 'field_expense_attachments')) {
            $this->db->query('CREATE TABLE ' . db_prefix() . "field_expense_attachments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                staff_id INT NOT NULL,
                expense_date DATE NOT NULL,
                file_name VARCHAR(255) NOT NULL,
                original_name VARCHAR(255) NOT NULL,
                title VARCHAR(255) DEFAULT '',
                description TEXT DEFAULT NULL,
                created_at DATETIME DEFAULT NULL,
                updated_at DATETIME DEFAULT NULL,
                INDEX idx_staff_id (staff_id),
                INDEX idx_expense_date (expense_date)
            ) ENGINE=InnoDB DEFAULT CHARSET=" . $this->db->char_set . ';');
        }
    }
}
