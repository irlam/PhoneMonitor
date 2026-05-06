-- Migration 006: Add device status columns for real-time battery/storage tracking
-- Safe to run on existing installs - checks information_schema before altering.
-- mysql -u user -p phone_monitor < database/migrations/006_device_status_columns.sql

-- Add battery_level column to devices
SET @col_battery := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'devices'
        AND COLUMN_NAME = 'battery_level'
);
SET @sql_battery := IF(@col_battery = 0,
    'ALTER TABLE `devices` ADD COLUMN `battery_level` INT NULL COMMENT ''Last reported battery percentage (0-100)'';',
    'SELECT "devices.battery_level already exists";'
);
PREPARE stmt_battery FROM @sql_battery; EXECUTE stmt_battery; DEALLOCATE PREPARE stmt_battery;

-- Add storage_free column to devices (stored in bytes)
SET @col_storage := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'devices'
        AND COLUMN_NAME = 'storage_free'
);
SET @sql_storage := IF(@col_storage = 0,
    'ALTER TABLE `devices` ADD COLUMN `storage_free` BIGINT NULL COMMENT ''Last reported free storage in bytes'';',
    'SELECT "devices.storage_free already exists";'
);
PREPARE stmt_storage FROM @sql_storage; EXECUTE stmt_storage; DEALLOCATE PREPARE stmt_storage;

-- Add updated_at column to devices
SET @col_updated := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'devices'
        AND COLUMN_NAME = 'updated_at'
);
SET @sql_updated := IF(@col_updated = 0,
    'ALTER TABLE `devices` ADD COLUMN `updated_at` TIMESTAMP NULL COMMENT ''Timestamp of last ping update'';',
    'SELECT "devices.updated_at already exists";'
);
PREPARE stmt_updated FROM @sql_updated; EXECUTE stmt_updated; DEALLOCATE PREPARE stmt_updated;
