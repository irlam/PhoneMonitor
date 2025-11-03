-- PhoneMonitor Database Schema
-- MySQL 8.0+ compatible

CREATE DATABASE IF NOT EXISTS phone_monitor CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE phone_monitor;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Devices table
CREATE TABLE IF NOT EXISTS devices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    device_uuid CHAR(36) UNIQUE NOT NULL,
    display_name VARCHAR(100) NOT NULL,
    owner_name VARCHAR(100) NOT NULL,
    registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_seen TIMESTAMP NULL,
    last_payload JSON NULL,
    consent_given TINYINT(1) DEFAULT 0,
    revoked TINYINT(1) DEFAULT 0,
    INDEX idx_device_uuid (device_uuid),
    INDEX idx_last_seen (last_seen),
    INDEX idx_revoked (revoked)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Device locations table (append-only)
CREATE TABLE IF NOT EXISTS device_locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    device_id INT NOT NULL,
    lat DECIMAL(9,6) NOT NULL,
    lon DECIMAL(9,6) NOT NULL,
    accuracy FLOAT NULL,
    provider VARCHAR(32) NULL,
    loc_ts TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_device_created (device_id, created_at),
    FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Audit log table
CREATE TABLE IF NOT EXISTS audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    device_id INT NULL,
    user_id INT NULL,
    action VARCHAR(100) NOT NULL,
    meta JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_device_id (device_id),
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed default admin user
-- Default password is 'changeme123' - MUST be changed after first login
-- To generate a new password hash, run: php -r "echo password_hash('your_password', PASSWORD_DEFAULT);"
INSERT INTO users (username, password_hash, name) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator')
ON DUPLICATE KEY UPDATE username=username;

-- Sample data marker
INSERT INTO audit_log (device_id, user_id, action, meta) VALUES 
(NULL, 1, 'system_init', JSON_OBJECT('message', 'Database initialized'));
