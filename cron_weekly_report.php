<?php
/**
 * Cron Job - Send weekly reports
 * Run this once per week via cron
 * Example crontab: 0 9 * * 1 php cron_weekly_report.php (Every Monday at 9am)
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/NotificationService.php';

echo "[" . date('Y-m-d H:i:s') . "] Generating weekly report...\n";

try {
    NotificationService::sendWeeklyReport();
    echo "✓ Weekly report queued successfully\n";
} catch (Exception $e) {
    echo "✗ Error generating weekly report: " . $e->getMessage() . "\n";
}

echo "[" . date('Y-m-d H:i:s') . "] Weekly report cron completed\n";
