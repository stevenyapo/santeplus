CREATE TABLE IF NOT EXISTS system_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    level VARCHAR(10) NOT NULL,
    message TEXT NOT NULL,
    user_id INT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    url TEXT,
    context JSON,
    created_at DATETIME NOT NULL,
    INDEX idx_level (level),
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci; 