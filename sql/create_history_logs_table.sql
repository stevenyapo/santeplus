CREATE TABLE IF NOT EXISTS history_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    table_name VARCHAR(50) NOT NULL,
    record_id INT NOT NULL,
    action VARCHAR(20) NOT NULL,
    old_data JSON,
    new_data JSON,
    user_id INT,
    created_at DATETIME NOT NULL,
    INDEX idx_table_record (table_name, record_id),
    INDEX idx_created_at (created_at),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci; 