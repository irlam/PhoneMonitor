<?php
/**
 * Discord Bot Service - Send alerts via Discord webhooks
 */

require_once __DIR__ . '/db.php';

class DiscordBot {
    
    private static function getConfig() {
        $config = db()->fetchOne("SELECT * FROM bot_config WHERE bot_type = 'discord' AND enabled = 1");
        if (!$config) {
            return null;
        }
        return json_decode($config['config'], true);
    }
    
    /**
     * Send a message via Discord webhook
     */
    public static function sendMessage($message, $color = 0x3498db) {
        $config = self::getConfig();
        if (!$config || empty($config['webhook_url'])) {
            return false;
        }
        
        $data = [
            'embeds' => [
                [
                    'description' => $message,
                    'color' => $color,
                    'timestamp' => date('c'),
                    'footer' => [
                        'text' => 'PhoneMonitor'
                    ]
                ]
            ]
        ];
        
        return self::sendWebhook($config['webhook_url'], $data);
    }
    
    /**
     * Send low battery alert
     */
    public static function sendLowBatteryAlert($device, $batteryLevel) {
        $description = "**Low Battery Alert**\n\n";
        $description .= "**Device:** " . $device['owner_name'] . "\n";
        if (!empty($device['display_name'])) {
            $description .= "**Name:** " . $device['display_name'] . "\n";
        }
        $description .= "**Battery:** " . $batteryLevel . "%\n";
        $description .= "**Status:** " . (strtotime($device['last_seen']) > time() - 1800 ? "🟢 Online" : "🔴 Offline");
        
        return self::sendMessage($description, 0xe74c3c); // Red
    }
    
    /**
     * Send device offline alert
     */
    public static function sendOfflineAlert($device, $hoursOffline) {
        $description = "**Device Offline Alert**\n\n";
        $description .= "**Device:** " . $device['owner_name'] . "\n";
        if (!empty($device['display_name'])) {
            $description .= "**Name:** " . $device['display_name'] . "\n";
        }
        $description .= "**Offline for:** " . round($hoursOffline, 1) . " hours\n";
        $description .= "**Last seen:** " . $device['last_seen'] . "\n";
        $description .= "**Battery:** " . $device['battery_level'] . "%";
        
        return self::sendMessage($description, 0x95a5a6); // Gray
    }
    
    /**
     * Send geofence alert
     */
    public static function sendGeofenceAlert($device, $geofence, $eventType) {
        $emoji = $eventType === 'enter' ? '✅' : '🚪';
        $action = $eventType === 'enter' ? 'ENTERED' : 'LEFT';
        $color = $eventType === 'enter' ? 0x2ecc71 : 0xf39c12; // Green or Orange
        
        $description = "$emoji **Geofence Alert**\n\n";
        $description .= "**Device:** " . $device['owner_name'] . "\n";
        $description .= "**Action:** " . $action . "\n";
        $description .= "**Zone:** " . $geofence['name'];
        
        return self::sendMessage($description, $color);
    }
    
    /**
     * Send custom alert from alert rule
     */
    public static function sendCustomAlert($device, $ruleName, $reason) {
        $description = "⚠️ **Custom Alert**\n\n";
        $description .= "**Rule:** " . $ruleName . "\n";
        $description .= "**Device:** " . $device['owner_name'] . "\n";
        if (!empty($device['display_name'])) {
            $description .= "**Name:** " . $device['display_name'] . "\n";
        }
        $description .= "**Reason:** " . $reason;
        
        return self::sendMessage($description, 0xe67e22); // Orange
    }
    
    /**
     * Send test message
     */
    public static function sendTestMessage() {
        $description = "✅ **PhoneMonitor Test**\n\n";
        $description .= "Your Discord webhook is configured correctly!";
        
        return self::sendMessage($description, 0x2ecc71); // Green
    }
    
    /**
     * HTTP webhook request helper
     */
    private static function sendWebhook($webhookUrl, $data) {
        $options = [
            'http' => [
                'header'  => "Content-Type: application/json\r\n",
                'method'  => 'POST',
                'content' => json_encode($data),
                'timeout' => 10
            ]
        ];
        
        $context  = stream_context_create($options);
        $result = @file_get_contents($webhookUrl, false, $context);
        
        if ($result === false) {
            error_log("Discord webhook error: " . error_get_last()['message']);
            return false;
        }
        
        // Discord returns 204 No Content on success
        return true;
    }
    
    /**
     * Validate webhook URL
     */
    public static function validateWebhook($webhookUrl) {
        // Basic validation
        if (!filter_var($webhookUrl, FILTER_VALIDATE_URL)) {
            return ['success' => false, 'error' => 'Invalid URL format'];
        }
        
        if (strpos($webhookUrl, 'discord.com/api/webhooks/') === false) {
            return ['success' => false, 'error' => 'Not a Discord webhook URL'];
        }
        
        // Test the webhook
        $data = [
            'content' => 'PhoneMonitor webhook validation test'
        ];
        
        $options = [
            'http' => [
                'header'  => "Content-Type: application/json\r\n",
                'method'  => 'POST',
                'content' => json_encode($data),
                'timeout' => 10
            ]
        ];
        
        $context  = stream_context_create($options);
        $result = @file_get_contents($webhookUrl, false, $context);
        
        if ($result === false) {
            return ['success' => false, 'error' => 'Failed to send test message'];
        }
        
        return ['success' => true];
    }
}
