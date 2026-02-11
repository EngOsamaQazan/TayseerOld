CREATE TABLE IF NOT EXISTS os_promissory_notes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    contract_id INT NOT NULL,
    sequence_number TINYINT UNSIGNED NOT NULL DEFAULT 1,
    amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    due_date VARCHAR(30) DEFAULT NULL,
    status ENUM('active','executed','cancelled') NOT NULL DEFAULT 'active',
    created_at INT UNSIGNED DEFAULT NULL,
    created_by INT UNSIGNED DEFAULT NULL,
    UNIQUE KEY uq_contract_seq (contract_id, sequence_number),
    KEY idx_contract (contract_id),
    KEY idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
