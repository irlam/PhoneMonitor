-- ============================================================
-- PhoneMonitor - FRESH INSTALL SQL
-- ============================================================
-- HOW TO USE:
--   1. Open phpMyAdmin and click the SQL tab (at the top)
--   2. Paste this entire file into the SQL box
--   3. Click "Go"
--
-- ⚠️  THIS DROPS AND RECREATES THE DATABASE.
--     ALL EXISTING DATA WILL BE PERMANENTLY DELETED.
--
-- The database name below must match DB_NAME in your .env file.
-- Default in .env: DB_NAME=phone_monitor
-- Your current production database appears to be: k87747_phone_monitor
--
-- Change the name on the three lines below if needed.
-- ============================================================

DROP DATABASE IF EXISTS `k87747_phone_monitor`;
CREATE DATABASE `k87747_phone_monitor`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE `k87747_phone_monitor`;

-- ============================================================
-- TABLE: users
-- ============================================================
CREATE TABLE `users` (
  `id`            INT          AUTO_INCREMENT PRIMARY KEY,
  `username`      VARCHAR(50)  NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `name`          VARCHAR(100) NOT NULL,
  `created_at`    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_username`  (`username`),
  INDEX          `idx_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: devices
--
-- device_uuid : canonical UUID sent by the Android app (used by all API
--               endpoints – register, ping, unregister)
-- device_id   : VARCHAR copy of device_uuid kept for ExportService
--               compatibility; auto-populated by the triggers below
-- device_model, os_version : used by ExportService CSV/PDF output
-- last_speed  : last calculated speed in km/h (AlertRuleService)
-- ============================================================
CREATE TABLE `devices` (
  `id`              INT          AUTO_INCREMENT PRIMARY KEY,
  `device_uuid`     VARCHAR(255) NOT NULL,
  `device_id`       VARCHAR(255) NULL     COMMENT 'Mirror of device_uuid for ExportService',
  `display_name`    VARCHAR(100) NOT NULL,
  `owner_name`      VARCHAR(100) NOT NULL,
  `device_model`    VARCHAR(100) NULL,
  `os_version`      VARCHAR(50)  NULL,
  `registered_at`   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  `created_at`      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  `last_seen`       TIMESTAMP    NULL,
  `last_payload`    JSON         NULL,
  `battery_level`   INT          NULL     COMMENT 'Last reported battery % (0-100)',
  `storage_free`    BIGINT       NULL     COMMENT 'Last reported free storage in bytes',
  `last_speed`      FLOAT        NULL     DEFAULT 0 COMMENT 'Last calculated speed km/h',
  `consent_given`   TINYINT(1)   DEFAULT 0,
  `revoked`         TINYINT(1)   DEFAULT 0,
  `pending_refresh` TINYINT(1)   DEFAULT 0 COMMENT '1 = ask device for immediate update',
  `updated_at`      TIMESTAMP    NULL     COMMENT 'Timestamp of last ping',
  UNIQUE KEY `uq_device_uuid` (`device_uuid`),
  INDEX `idx_device_uuid`   (`device_uuid`),
  INDEX `idx_device_id`     (`device_id`),
  INDEX `idx_last_seen`     (`last_seen`),
  INDEX `idx_revoked`       (`revoked`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: device_locations
--
-- lat / lon         : written by the ping API; used by the dashboard,
--                     map, geofences, and analytics
-- latitude/longitude: GENERATED columns that alias lat/lon so that
--                     ExportService (which reads `latitude`/`longitude`)
--                     always gets the correct values automatically
-- altitude          : optional extra field; NULL for standard pings
-- speed             : km/h calculated from consecutive location pairs
-- loc_ts            : device-side timestamp sent by the Android app
-- timestamp         : DATETIME copy of loc_ts (or created_at when
--                     loc_ts is NULL); used by ExportService for display
-- ============================================================
CREATE TABLE `device_locations` (
  `id`         INT           AUTO_INCREMENT PRIMARY KEY,
  `device_id`  INT           NOT NULL,
  `lat`        DECIMAL(9,6)  NOT NULL,
  `lon`        DECIMAL(9,6)  NOT NULL,
  `accuracy`   FLOAT         NULL,
  `provider`   VARCHAR(32)   NULL,
  `altitude`   FLOAT         NULL,
  `speed`      DECIMAL(10,2) NULL    DEFAULT 0.0 COMMENT 'Speed in km/h',
  `loc_ts`     TIMESTAMP     NULL    COMMENT 'Timestamp received from device',
  `created_at` TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  `latitude`   DECIMAL(10,8) GENERATED ALWAYS AS (`lat`) STORED
               COMMENT 'ExportService alias for lat',
  `longitude`  DECIMAL(11,8) GENERATED ALWAYS AS (`lon`) STORED
               COMMENT 'ExportService alias for lon',
  `timestamp`  DATETIME      NULL    DEFAULT NULL
               COMMENT 'Populated by trigger from loc_ts / created_at; used by ExportService',
  INDEX `idx_device_created` (`device_id`, `created_at`),
  INDEX `idx_lat_lon`        (`lat`, `lon`),
  FOREIGN KEY `fk_dl_device` (`device_id`)
    REFERENCES `devices`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: audit_log
-- ============================================================
CREATE TABLE `audit_log` (
  `id`         INT          AUTO_INCREMENT PRIMARY KEY,
  `device_id`  INT          NULL,
  `user_id`    INT          NULL,
  `action`     VARCHAR(100) NOT NULL,
  `meta`       JSON         NULL,
  `created_at` TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_device_id`  (`device_id`),
  INDEX `idx_user_id`    (`user_id`),
  INDEX `idx_created_at` (`created_at`),
  FOREIGN KEY `fk_al_device` (`device_id`) REFERENCES `devices`(`id`) ON DELETE SET NULL,
  FOREIGN KEY `fk_al_user`   (`user_id`)   REFERENCES `users`(`id`)   ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: geofences
-- ============================================================
CREATE TABLE `geofences` (
  `id`             INT           AUTO_INCREMENT PRIMARY KEY,
  `name`           VARCHAR(100)  NOT NULL,
  `device_id`      INT           NULL COMMENT 'NULL = applies to all devices',
  `latitude`       DECIMAL(10,8) NOT NULL,
  `longitude`      DECIMAL(11,8) NOT NULL,
  `radius_meters`  INT           NOT NULL DEFAULT 100,
  `alert_on_enter` TINYINT(1)    DEFAULT 1,
  `alert_on_exit`  TINYINT(1)    DEFAULT 0,
  `active`         TINYINT(1)    DEFAULT 1,
  `created_at`     TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_active` (`active`),
  INDEX `idx_device` (`device_id`),
  FOREIGN KEY `fk_gf_device` (`device_id`)
    REFERENCES `devices`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: geofence_events
-- timestamp mirrors created_at for ExportService PDF/CSV output
-- ============================================================
CREATE TABLE `geofence_events` (
  `id`              INT           AUTO_INCREMENT PRIMARY KEY,
  `geofence_id`     INT           NOT NULL,
  `device_id`       INT           NOT NULL,
  `event_type`      ENUM('enter','exit') NOT NULL,
  `latitude`        DECIMAL(10,8) NOT NULL,
  `longitude`       DECIMAL(11,8) NOT NULL,
  `distance_meters` INT           NOT NULL,
  `created_at`      TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  `timestamp`       DATETIME      NULL DEFAULT NULL
                    COMMENT 'Populated by trigger; used by ExportService',
  INDEX `idx_device_time`   (`device_id`,   `created_at`),
  INDEX `idx_geofence_time` (`geofence_id`, `created_at`),
  FOREIGN KEY `fk_ge_geofence` (`geofence_id`) REFERENCES `geofences`(`id`) ON DELETE CASCADE,
  FOREIGN KEY `fk_ge_device`   (`device_id`)   REFERENCES `devices`(`id`)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: email_notifications
-- 'custom_alert' is added to the enum so that AlertRuleService can
-- insert rows without causing a MySQL error
-- ============================================================
CREATE TABLE `email_notifications` (
  `id`                INT          AUTO_INCREMENT PRIMARY KEY,
  `email_to`          VARCHAR(255) NOT NULL,
  `subject`           VARCHAR(255) NOT NULL,
  `body`              TEXT         NOT NULL,
  `notification_type` ENUM(
    'geofence',
    'low_battery',
    'offline',
    'weekly_report',
    'custom_alert'
  ) NOT NULL,
  `device_id`         INT          NULL,
  `sent_at`           TIMESTAMP    NULL,
  `failed_at`         TIMESTAMP    NULL,
  `error_message`     TEXT         NULL,
  `created_at`        TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_sent`   (`sent_at`),
  INDEX `idx_type`   (`notification_type`),
  INDEX `idx_device` (`device_id`),
  FOREIGN KEY `fk_en_device` (`device_id`)
    REFERENCES `devices`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: alert_rules
-- device_id here is VARCHAR (stores the device_uuid string, not an
-- integer) so the AlertRuleService lookup by UUID string works.
-- ============================================================
CREATE TABLE `alert_rules` (
  `id`                INT          AUTO_INCREMENT PRIMARY KEY,
  `name`              VARCHAR(255) NOT NULL,
  `device_id`         VARCHAR(255) NULL    COLLATE utf8mb4_unicode_ci
                      COMMENT 'NULL = all devices; stores device_uuid string',
  `rule_type`         ENUM('battery','location','offline','speed','storage','custom') NOT NULL,
  `conditions`        JSON         NOT NULL,
  `actions`           JSON         NOT NULL,
  `enabled`           TINYINT(1)   DEFAULT 1,
  `cooldown_minutes`  INT          DEFAULT 60,
  `last_triggered_at` TIMESTAMP    NULL,
  `created_at`        TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_device`  (`device_id`),
  INDEX `idx_enabled` (`enabled`),
  INDEX `idx_type`    (`rule_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: alert_rule_triggers
-- ============================================================
CREATE TABLE `alert_rule_triggers` (
  `id`             INT          AUTO_INCREMENT PRIMARY KEY,
  `alert_rule_id`  INT          NOT NULL,
  `device_id`      VARCHAR(255) NOT NULL COLLATE utf8mb4_unicode_ci
                   COMMENT 'Stores the device_uuid string',
  `trigger_reason` TEXT         NULL,
  `actions_taken`  JSON         NULL,
  `triggered_at`   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_rule`   (`alert_rule_id`),
  INDEX `idx_device` (`device_id`),
  INDEX `idx_date`   (`triggered_at`),
  FOREIGN KEY `fk_art_rule` (`alert_rule_id`)
    REFERENCES `alert_rules`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: bot_config
-- ============================================================
CREATE TABLE `bot_config` (
  `id`         INT        AUTO_INCREMENT PRIMARY KEY,
  `bot_type`   ENUM('telegram','discord') NOT NULL,
  `config`     JSON       NOT NULL,
  `enabled`    TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP  DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP  DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_bot_type` (`bot_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: export_history
-- ============================================================
CREATE TABLE `export_history` (
  `id`          INT          AUTO_INCREMENT PRIMARY KEY,
  `export_type` ENUM('devices_csv','locations_csv','battery_csv','report_pdf') NOT NULL,
  `device_id`   VARCHAR(255) NULL COLLATE utf8mb4_unicode_ci,
  `date_from`   DATE         NULL,
  `date_to`     DATE         NULL,
  `file_size`   INT          NULL,
  `row_count`   INT          NULL,
  `exported_by` VARCHAR(255) NULL,
  `exported_at` TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_type` (`export_type`),
  INDEX `idx_date` (`exported_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: analytics_cache
-- ============================================================
CREATE TABLE `analytics_cache` (
  `id`         INT          AUTO_INCREMENT PRIMARY KEY,
  `cache_key`  VARCHAR(255) NOT NULL,
  `cache_data` JSON         NOT NULL,
  `expires_at` TIMESTAMP    NOT NULL,
  `created_at` TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_cache_key` (`cache_key`),
  INDEX `idx_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TRIGGERS
--
-- These automatically keep derived / alias columns up to date.
-- If your hosting provider does not allow CREATE TRIGGER (rare),
-- you can skip this block – the app will still work but ExportService
-- will show NULL timestamps in reports/CSV.
-- ============================================================

DELIMITER $$

-- devices: auto-populate device_id from device_uuid on INSERT
CREATE TRIGGER `trg_devices_before_insert`
BEFORE INSERT ON `devices`
FOR EACH ROW
BEGIN
  IF NEW.device_id IS NULL THEN
    SET NEW.device_id = NEW.device_uuid;
  END IF;
END$$

-- devices: keep device_id in sync when device_uuid is changed
CREATE TRIGGER `trg_devices_before_update`
BEFORE UPDATE ON `devices`
FOR EACH ROW
BEGIN
  IF NEW.device_uuid <> OLD.device_uuid THEN
    SET NEW.device_id = NEW.device_uuid;
  END IF;
END$$

-- device_locations: populate timestamp from loc_ts (or NOW())
CREATE TRIGGER `trg_device_locations_before_insert`
BEFORE INSERT ON `device_locations`
FOR EACH ROW
BEGIN
  IF NEW.timestamp IS NULL THEN
    SET NEW.timestamp = COALESCE(NEW.loc_ts, NOW());
  END IF;
END$$

-- geofence_events: populate timestamp from NOW()
CREATE TRIGGER `trg_geofence_events_before_insert`
BEFORE INSERT ON `geofence_events`
FOR EACH ROW
BEGIN
  IF NEW.timestamp IS NULL THEN
    SET NEW.timestamp = NOW();
  END IF;
END$$

DELIMITER ;

-- ============================================================
-- SEED DATA
-- ============================================================

-- Default admin user
-- Password: changeme123  ← ⚠️ CHANGE THIS IMMEDIATELY AFTER FIRST LOGIN
-- To generate a new hash:  php -r "echo password_hash('yourpassword', PASSWORD_DEFAULT);"
INSERT INTO `users` (`username`, `password_hash`, `name`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator');

-- System initialisation marker
INSERT INTO `audit_log` (`device_id`, `user_id`, `action`, `meta`) VALUES
(NULL, 1, 'system_init', JSON_OBJECT('message', 'Database initialised'));

-- Bot config placeholders (both disabled)
INSERT INTO `bot_config` (`bot_type`, `config`, `enabled`) VALUES
('telegram', '{"token":"","chat_id":""}', 0),
('discord',  '{"webhook_url":""}',        0);

-- Default alert rules
INSERT INTO `alert_rules`
  (`name`, `device_id`, `rule_type`, `conditions`, `actions`, `enabled`, `cooldown_minutes`)
VALUES
(
  'Low Battery Alert', NULL, 'battery',
  '{"operator":"and","rules":[{"field":"battery_level","operator":"<","value":15}]}',
  '{"email":true,"telegram":true,"discord":true}',
  1, 120
),
(
  'Critical Battery', NULL, 'battery',
  '{"operator":"and","rules":[{"field":"battery_level","operator":"<","value":5}]}',
  '{"email":true,"telegram":true,"discord":true}',
  1, 60
),
(
  'Device Offline 24h', NULL, 'offline',
  '{"operator":"and","rules":[{"field":"offline_hours","operator":">","value":24}]}',
  '{"email":true,"telegram":true,"discord":false}',
  1, 1440
),
(
  'High Speed Alert', NULL, 'speed',
  '{"operator":"and","rules":[{"field":"speed_mph","operator":">","value":75}]}',
  '{"email":false,"telegram":true,"discord":false}',
  0, 30
);
