<?php
/**
 * Cron Job - Send notifications and check device status
 * Run this every 5-15 minutes via cron
 * Example crontab: */15 * * * * php /path/to/cron_notifications.php
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/NotificationService.php';

echo "[" . date('Y-m-d H:i:s') . "] Starting notification cron job...\n";

// Send pending email notifications
try {
    NotificationService::sendPending();
    echo "✓ Sent pending email notifications\n";
} catch (Exception $e) {
    echo "✗ Error sending emails: " . $e->getMessage() . "\n";
}

// Check for devices that have been offline for more than 24 hours
try {
    $offlineDevices = db()->fetchAll(
        "SELECT id, display_name, last_seen 
         FROM devices 
         WHERE revoked = 0 
         AND last_seen < DATE_SUB(NOW(), INTERVAL 24 HOUR)
         AND last_seen > DATE_SUB(NOW(), INTERVAL 25 HOUR)"
    );
    
    foreach ($offlineDevices as $device) {
        $hoursOffline = round((time() - strtotime($device['last_seen'])) / 3600);
        NotificationService::sendOfflineAlert($device['id'], $hoursOffline);
        echo "✓ Queued offline alert for device: {$device['display_name']} ({$hoursOffline}h offline)\n";
    }
    
    if (empty($offlineDevices)) {
        echo "✓ No offline devices detected\n";
    }
} catch (Exception $e) {
    echo "✗ Error checking offline devices: " . $e->getMessage() . "\n";
}

echo "[" . date('Y-m-d H:i:s') . "] Cron job completed\n";
