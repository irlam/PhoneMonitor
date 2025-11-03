<?php
/**
 * Notification Service
 * Handles email notifications and alerts
 */

class NotificationService {
    
    /**
     * Queue an email notification
     */
    public static function queueEmail($emailTo, $subject, $body, $type, $deviceId = null) {
        return db()->query(
            "INSERT INTO email_notifications 
             (email_to, subject, body, notification_type, device_id) 
             VALUES (?, ?, ?, ?, ?)",
            [$emailTo, $subject, $body, $type, $deviceId]
        );
    }
    
    /**
     * Send pending email notifications
     * Call this from a cron job
     */
    public static function sendPending() {
        $pending = db()->fetchAll(
            "SELECT * FROM email_notifications 
             WHERE sent_at IS NULL AND failed_at IS NULL 
             ORDER BY created_at ASC 
             LIMIT 10"
        );
        
        foreach ($pending as $notification) {
            try {
                self::sendEmail(
                    $notification['email_to'],
                    $notification['subject'],
                    $notification['body']
                );
                
                db()->query(
                    "UPDATE email_notifications SET sent_at = NOW() WHERE id = ?",
                    [$notification['id']]
                );
            } catch (Exception $e) {
                db()->query(
                    "UPDATE email_notifications SET failed_at = NOW(), error_message = ? WHERE id = ?",
                    [$e->getMessage(), $notification['id']]
                );
            }
        }
    }
    
    /**
     * Send email using PHP mail()
     * For production, use SMTP library like PHPMailer or SwiftMailer
     */
    private static function sendEmail($to, $subject, $body) {
        $headers = [
            'From: PhoneMonitor <' . (getenv('ADMIN_EMAIL') ?: 'noreply@localhost') . '>',
            'Reply-To: ' . (getenv('ADMIN_EMAIL') ?: 'noreply@localhost'),
            'X-Mailer: PHP/' . phpversion(),
            'Content-Type: text/html; charset=UTF-8'
        ];
        
        $htmlBody = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #22bb66, #1a9950); color: white; padding: 20px; text-align: center; }
                .content { background: #f8f9fa; padding: 20px; border-radius: 8px; margin-top: 20px; }
                .footer { text-align: center; margin-top: 20px; color: #6c757d; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>PhoneMonitor Alert</h1>
                </div>
                <div class='content'>
                    " . nl2br(htmlspecialchars($body)) . "
                </div>
                <div class='footer'>
                    <p>PhoneMonitor - Consent-based Family Device Helper</p>
                    <p><small>This is an automated notification. Do not reply to this email.</small></p>
                </div>
            </div>
        </body>
        </html>";
        
        return mail($to, $subject, $htmlBody, implode("\r\n", $headers));
    }
    
    /**
     * Send low battery alert
     */
    public static function sendLowBatteryAlert($deviceId, $batteryLevel) {
        $device = db()->fetchOne("SELECT * FROM devices WHERE id = ?", [$deviceId]);
        if (!$device) return;
        
        $subject = "Low Battery Alert: {$device['display_name']} ({$batteryLevel}%)";
        $body = "Device {$device['display_name']} has a low battery level of {$batteryLevel}%.\n\n";
        $body .= "Device Owner: {$device['owner_name']}\n";
        $body .= "Last seen: " . ($device['last_seen'] ?: 'Never') . "\n";
        
        self::queueEmail(
            getenv('ADMIN_EMAIL') ?: 'admin@localhost',
            $subject,
            $body,
            'low_battery',
            $deviceId
        );
    }
    
    /**
     * Send device offline alert
     */
    public static function sendOfflineAlert($deviceId, $hoursOffline) {
        $device = db()->fetchOne("SELECT * FROM devices WHERE id = ?", [$deviceId]);
        if (!$device) return;
        
        $subject = "Device Offline: {$device['display_name']} ({$hoursOffline} hours)";
        $body = "Device {$device['display_name']} has been offline for {$hoursOffline} hours.\n\n";
        $body .= "Device Owner: {$device['owner_name']}\n";
        $body .= "Last seen: " . ($device['last_seen'] ?: 'Never') . "\n";
        
        self::queueEmail(
            getenv('ADMIN_EMAIL') ?: 'admin@localhost',
            $subject,
            $body,
            'offline',
            $deviceId
        );
    }
    
    /**
     * Generate and send weekly report
     */
    public static function sendWeeklyReport() {
        $devices = db()->fetchAll("SELECT * FROM devices ORDER BY display_name");
        
        $report = "Weekly Device Report - " . date('Y-m-d') . "\n\n";
        $report .= "Summary of device activity for the past week:\n\n";
        
        foreach ($devices as $device) {
            $stats = db()->fetchOne(
                "SELECT 
                    COUNT(*) as pings,
                    MIN(created_at) as first_seen,
                    MAX(created_at) as last_seen
                 FROM device_locations
                 WHERE device_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)",
                [$device['id']]
            );
            
            $report .= "---\n";
            $report .= "Device: {$device['display_name']}\n";
            $report .= "Owner: {$device['owner_name']}\n";
            $report .= "Status: " . ($device['revoked'] ? 'Revoked' : 'Active') . "\n";
            $report .= "Location updates: {$stats['pings']}\n";
            $report .= "Last seen: " . ($device['last_seen'] ?: 'Never') . "\n\n";
        }
        
        self::queueEmail(
            getenv('ADMIN_EMAIL') ?: 'admin@localhost',
            "Weekly PhoneMonitor Report - " . date('Y-m-d'),
            $report,
            'weekly_report'
        );
    }
}
