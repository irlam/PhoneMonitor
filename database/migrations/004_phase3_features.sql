-- Phase 3 Advanced Features Migration
-- Run this after 003_geofences.sql
-- mysql -u user -p phone_monitor < database/migrations/004_phase3_features.sql

-- Alert Rules Table
CREATE TABLE IF NOT EXISTS alert_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    device_id VARCHAR(255) NULL, -- NULL = all devices
    rule_type ENUM('battery', 'location', 'offline', 'speed', 'storage', 'custom') NOT NULL,
    conditions JSON NOT NULL, -- {"operator": "and", "rules": [{"field": "battery", "operator": "<", "value": 20}]}
    actions JSON NOT NULL, -- {"email": true, "telegram": true, "discord": false}
    enabled BOOLEAN DEFAULT TRUE,
    cooldown_minutes INT DEFAULT 60, -- Prevent spam
    last_triggered_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_device (device_id),
    INDEX idx_enabled (enabled),
    INDEX idx_type (rule_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Bot Configuration Table
CREATE TABLE IF NOT EXISTS bot_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bot_type ENUM('telegram', 'discord') NOT NULL,
    config JSON NOT NULL, -- {"token": "...", "chat_id": "..."} or {"webhook_url": "..."}
    enabled BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_bot_type (bot_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Export History Table
CREATE TABLE IF NOT EXISTS export_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    export_type ENUM('devices_csv', 'locations_csv', 'battery_csv', 'report_pdf') NOT NULL,
    device_id VARCHAR(255) NULL, -- NULL = all devices
    date_from DATE NULL,
    date_to DATE NULL,
    file_size INT NULL,
    row_count INT NULL,
    exported_by VARCHAR(255) NULL,
    exported_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_type (export_type),
    INDEX idx_date (exported_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Analytics Cache Table (for performance)
CREATE TABLE IF NOT EXISTS analytics_cache (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cache_key VARCHAR(255) NOT NULL,
    cache_data JSON NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_cache_key (cache_key),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Alert Rule Triggers Log
CREATE TABLE IF NOT EXISTS alert_rule_triggers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    alert_rule_id INT NOT NULL,
    device_id VARCHAR(255) NOT NULL,
    trigger_reason TEXT,
    actions_taken JSON, -- {"email": true, "telegram": true, "discord": false}
    triggered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_rule (alert_rule_id),
    INDEX idx_device (device_id),
    INDEX idx_date (triggered_at),
    FOREIGN KEY (alert_rule_id) REFERENCES alert_rules(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default alert rules (examples)
INSERT INTO alert_rules (name, device_id, rule_type, conditions, actions, enabled, cooldown_minutes) VALUES
('Low Battery Alert', NULL, 'battery', '{"operator": "and", "rules": [{"field": "battery_level", "operator": "<", "value": 15}]}', '{"email": true, "telegram": true, "discord": true}', TRUE, 120),
('Critical Battery Alert', NULL, 'battery', '{"operator": "and", "rules": [{"field": "battery_level", "operator": "<", "value": 5}]}', '{"email": true, "telegram": true, "discord": true}', TRUE, 60),
('Device Offline 24h', NULL, 'offline', '{"operator": "and", "rules": [{"field": "offline_hours", "operator": ">", "value": 24}]}', '{"email": true, "telegram": true, "discord": false}', TRUE, 1440),
('High Speed Alert', NULL, 'speed', '{"operator": "and", "rules": [{"field": "speed_kmh", "operator": ">", "value": 120}]}', '{"email": false, "telegram": true, "discord": false}', FALSE, 30);

-- Add column to devices table for speed tracking (if not exists)
ALTER TABLE devices 
ADD COLUMN IF NOT EXISTS last_speed DECIMAL(10,2) DEFAULT 0.0 COMMENT 'Last calculated speed in km/h';

-- Add column to device_locations for calculated speed (if not exists)
ALTER TABLE device_locations
ADD COLUMN IF NOT EXISTS speed DECIMAL(10,2) DEFAULT 0.0 COMMENT 'Speed in km/h calculated from previous location';

COMMIT;
