<?php
/**
 * Telegram Bot Service - Send alerts via Telegram
 */

require_once __DIR__ . '/db.php';

class TelegramBot {
    
    private static function getConfig() {
        $config = db()->fetchOne("SELECT * FROM bot_config WHERE bot_type = 'telegram' AND enabled = 1");
        if (!$config) {
            return null;
        }
        return json_decode($config['config'], true);
    }
    
    /**
     * Send a message via Telegram
     */
    public static function sendMessage($message) {
        $config = self::getConfig();
        if (!$config || empty($config['bot_token']) || empty($config['chat_id'])) {
            return false;
        }
        
        $url = "https://api.telegram.org/bot" . $config['bot_token'] . "/sendMessage";
        
        $data = [
            'chat_id' => $config['chat_id'],
            'text' => $message,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true
        ];
        
        return self::sendRequest($url, $data);
    }
    
    /**
     * Send low battery alert
     */
    public static function sendLowBatteryAlert($device, $batteryLevel) {
        $message = "🔋 <b>Low Battery Alert</b>\n\n";
        $message .= "Device: <b>" . htmlspecialchars($device['owner_name']) . "</b>\n";
        if (!empty($device['display_name'])) {
            $message .= "Name: " . htmlspecialchars($device['display_name']) . "\n";
        }
        $message .= "Battery: <b>" . $batteryLevel . "%</b>\n";
        $message .= "Status: " . (strtotime($device['last_seen']) > time() - 1800 ? "🟢 Online" : "🔴 Offline") . "\n";
        $message .= "Time: " . date('Y-m-d H:i:s');
        
        return self::sendMessage($message);
    }
    
    /**
     * Send device offline alert
     */
    public static function sendOfflineAlert($device, $hoursOffline) {
        $message = "📵 <b>Device Offline Alert</b>\n\n";
        $message .= "Device: <b>" . htmlspecialchars($device['owner_name']) . "</b>\n";
        if (!empty($device['display_name'])) {
            $message .= "Name: " . htmlspecialchars($device['display_name']) . "\n";
        }
        $message .= "Offline for: <b>" . round($hoursOffline, 1) . " hours</b>\n";
        $message .= "Last seen: " . $device['last_seen'] . "\n";
        $message .= "Battery: " . $device['battery_level'] . "%";
        
        return self::sendMessage($message);
    }
    
    /**
     * Send geofence alert
     */
    public static function sendGeofenceAlert($device, $geofence, $eventType) {
        $emoji = $eventType === 'enter' ? '✅' : '🚪';
        $action = $eventType === 'enter' ? 'ENTERED' : 'LEFT';
        
        $message = "$emoji <b>Geofence Alert</b>\n\n";
        $message .= "Device: <b>" . htmlspecialchars($device['owner_name']) . "</b>\n";
        $message .= "Action: <b>" . $action . "</b>\n";
        $message .= "Zone: <b>" . htmlspecialchars($geofence['name']) . "</b>\n";
        $message .= "Time: " . date('Y-m-d H:i:s');
        
        return self::sendMessage($message);
    }
    
    /**
     * Send custom alert from alert rule
     */
    public static function sendCustomAlert($device, $ruleName, $reason) {
        $message = "⚠️ <b>Custom Alert</b>\n\n";
        $message .= "Rule: <b>" . htmlspecialchars($ruleName) . "</b>\n";
        $message .= "Device: <b>" . htmlspecialchars($device['owner_name']) . "</b>\n";
        if (!empty($device['display_name'])) {
            $message .= "Name: " . htmlspecialchars($device['display_name']) . "\n";
        }
        $message .= "Reason: " . htmlspecialchars($reason) . "\n";
        $message .= "Time: " . date('Y-m-d H:i:s');
        
        return self::sendMessage($message);
    }
    
    /**
     * Send test message
     */
    public static function sendTestMessage() {
        $message = "✅ <b>PhoneMonitor Test</b>\n\n";
        $message .= "Your Telegram bot is configured correctly!\n";
        $message .= "Time: " . date('Y-m-d H:i:s');
        
        return self::sendMessage($message);
    }
    
    /**
     * HTTP request helper
     */
    private static function sendRequest($url, $data) {
        $options = [
            'http' => [
                'header'  => "Content-type: application/json\r\n",
                'method'  => 'POST',
                'content' => json_encode($data),
                'timeout' => 10
            ]
        ];
        
        $context  = stream_context_create($options);
        $result = @file_get_contents($url, false, $context);
        
        if ($result === false) {
            error_log("Telegram API error: " . error_get_last()['message']);
            return false;
        }
        
        $response = json_decode($result, true);
        return isset($response['ok']) && $response['ok'] === true;
    }
    
    /**
     * Validate bot token and get bot info
     */
    public static function validateToken($botToken) {
        $url = "https://api.telegram.org/bot" . $botToken . "/getMe";
        
        $result = @file_get_contents($url);
        if ($result === false) {
            return ['success' => false, 'error' => 'Failed to connect to Telegram API'];
        }
        
        $response = json_decode($result, true);
        if (!isset($response['ok']) || !$response['ok']) {
            return ['success' => false, 'error' => 'Invalid bot token'];
        }
        
        return [
            'success' => true,
            'bot_username' => $response['result']['username'] ?? 'Unknown',
            'bot_name' => $response['result']['first_name'] ?? 'Unknown'
        ];
    }
}
