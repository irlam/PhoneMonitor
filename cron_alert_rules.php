<?php
/**
 * Cron job: Evaluate alert rules
 * Run every 5-15 minutes
 * 
 * Crontab example:
 * */5 * * * * php /path/to/PhoneMonitor/cron_alert_rules.php
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/AlertRuleService.php';

echo "[" . date('Y-m-d H:i:s') . "] Starting alert rule evaluation...\n";

try {
    AlertRuleService::evaluateAllRules();
    echo "[" . date('Y-m-d H:i:s') . "] Alert rules evaluated successfully\n";
} catch (Exception $e) {
    echo "[" . date('Y-m-d H:i:s') . "] ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

echo "[" . date('Y-m-d H:i:s') . "] Done\n";
