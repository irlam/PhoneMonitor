-- Fix collation mismatches between tables
-- This resolves "Illegal mix of collations" errors when joining tables
-- Run this if you encounter collation errors

-- Alert rules table - ensure device_id uses utf8mb4_unicode_ci to match connection default
ALTER TABLE alert_rules 
  MODIFY COLUMN device_id VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL;

-- Alert triggers table - ensure device_id uses utf8mb4_unicode_ci
ALTER TABLE alert_rule_triggers 
  MODIFY COLUMN device_id VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL;

-- Devices table - ensure device_uuid uses utf8mb4_unicode_ci
ALTER TABLE devices 
  MODIFY COLUMN device_uuid VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL;

-- Email notifications table - ensure device_id uses utf8mb4_unicode_ci
ALTER TABLE email_notifications 
  MODIFY COLUMN device_id INT NULL;

-- Note: If you still see collation errors, check all VARCHAR columns are using utf8mb4_unicode_ci:
-- SELECT TABLE_NAME, COLUMN_NAME, COLLATION_NAME 
-- FROM information_schema.COLUMNS 
-- WHERE TABLE_SCHEMA = 'phone_monitor' 
-- AND COLLATION_NAME IS NOT NULL 
-- AND COLLATION_NAME != 'utf8mb4_unicode_ci';
