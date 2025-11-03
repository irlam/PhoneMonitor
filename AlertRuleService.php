<?php
/**
 * Alert Rule Service - Custom alert rules engine
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/NotificationService.php';
require_once __DIR__ . '/TelegramBot.php';
require_once __DIR__ . '/DiscordBot.php';

class AlertRuleService {
    
    /**
     * Evaluate all enabled alert rules
     */
    public static function evaluateAllRules() {
        $rules = db()->fetchAll("
            SELECT * FROM alert_rules 
            WHERE enabled = 1
            ORDER BY id
        ");
        
        foreach ($rules as $rule) {
            self::evaluateRule($rule);
        }
    }
    
    /**
     * Evaluate a single alert rule
     */
    public static function evaluateRule($rule) {
        // Check cooldown
        if ($rule['last_triggered_at']) {
            $cooldownEnd = strtotime($rule['last_triggered_at']) + ($rule['cooldown_minutes'] * 60);
            if (time() < $cooldownEnd) {
                return; // Still in cooldown
            }
        }
        
        // Get devices to check
        $devices = [];
        if ($rule['device_id']) {
            $device = db()->fetchOne("SELECT * FROM devices WHERE device_id = ?", [$rule['device_id']]);
            if ($device) {
                $devices[] = $device;
            }
        } else {
            $devices = db()->fetchAll("SELECT * FROM devices WHERE consent_given = 1");
        }
        
        // Evaluate conditions for each device
        foreach ($devices as $device) {
            if (self::checkConditions($rule, $device)) {
                self::triggerAlert($rule, $device);
            }
        }
    }
    
    /**
     * Check if rule conditions are met
     */
    private static function checkConditions($rule, $device) {
        $conditions = json_decode($rule['conditions'], true);
        if (!$conditions || !isset($conditions['rules'])) {
            return false;
        }
        
        $operator = $conditions['operator'] ?? 'and';
        $results = [];
        
        foreach ($conditions['rules'] as $condition) {
            $results[] = self::evaluateCondition($condition, $device);
        }
        
        if ($operator === 'and') {
            return !in_array(false, $results);
        } else {
            return in_array(true, $results);
        }
    }
    
    /**
     * Evaluate a single condition
     */
    private static function evaluateCondition($condition, $device) {
        $field = $condition['field'];
        $operator = $condition['operator'];
        $value = $condition['value'];
        
        $actualValue = self::getFieldValue($field, $device);
        
        switch ($operator) {
            case '<':
                return $actualValue < $value;
            case '<=':
                return $actualValue <= $value;
            case '>':
                return $actualValue > $value;
            case '>=':
                return $actualValue >= $value;
            case '==':
            case '=':
                return $actualValue == $value;
            case '!=':
                return $actualValue != $value;
            default:
                return false;
        }
    }
    
    /**
     * Get field value from device
     */
    private static function getFieldValue($field, $device) {
        switch ($field) {
            case 'battery_level':
                return $device['battery_level'];
                
            case 'storage_free_gb':
                return round($device['storage_free'] / 1073741824, 2);
                
            case 'offline_hours':
                return (time() - strtotime($device['last_seen'])) / 3600;
                
            case 'offline_minutes':
                return (time() - strtotime($device['last_seen'])) / 60;
                
            case 'speed_kmh':
                // Stored value assumed in km/h
                return $device['last_speed'] ?? 0;
            case 'speed_mph':
                // Convert km/h to mph for UK units
                return round(($device['last_speed'] ?? 0) * 0.621371, 2);
                
            case 'is_charging':
                // Would need battery status from device
                return 0;
                
            case 'hour_of_day':
                return (int)date('H');
                
            case 'day_of_week':
                return (int)date('N'); // 1=Monday, 7=Sunday
                
            default:
                return 0;
        }
    }
    
    /**
     * Trigger alert actions
     */
    private static function triggerAlert($rule, $device) {
        $actions = json_decode($rule['actions'], true);
        $reason = self::buildReasonMessage($rule, $device);
        
        $actionsTaken = [
            'email' => false,
            'telegram' => false,
            'discord' => false
        ];
        
        // Send email
        if (!empty($actions['email'])) {
            try {
                $adminEmail = getenv('ADMIN_EMAIL') ?: 'admin@example.com';
                NotificationService::queueEmail(
                    $adminEmail,
                    'Alert: ' . $rule['name'],
                    $reason,
                    'custom_alert',
                    $device['device_id']
                );
                $actionsTaken['email'] = true;
            } catch (Exception $e) {
                error_log("Email alert failed: " . $e->getMessage());
            }
        }
        
        // Send Telegram
        if (!empty($actions['telegram'])) {
            try {
                TelegramBot::sendCustomAlert($device, $rule['name'], $reason);
                $actionsTaken['telegram'] = true;
            } catch (Exception $e) {
                error_log("Telegram alert failed: " . $e->getMessage());
            }
        }
        
        // Send Discord
        if (!empty($actions['discord'])) {
            try {
                DiscordBot::sendCustomAlert($device, $rule['name'], $reason);
                $actionsTaken['discord'] = true;
            } catch (Exception $e) {
                error_log("Discord alert failed: " . $e->getMessage());
            }
        }
        
        // Update last triggered time
        db()->query("
            UPDATE alert_rules 
            SET last_triggered_at = NOW()
            WHERE id = ?
        ", [$rule['id']]);
        
        // Log trigger
        db()->query("
            INSERT INTO alert_rule_triggers 
            (alert_rule_id, device_id, trigger_reason, actions_taken)
            VALUES (?, ?, ?, ?)
        ", [
            $rule['id'],
            $device['device_id'],
            $reason,
            json_encode($actionsTaken)
        ]);
    }
    
    /**
     * Build human-readable reason message
     */
    private static function buildReasonMessage($rule, $device) {
        $conditions = json_decode($rule['conditions'], true);
        $message = "Rule '" . $rule['name'] . "' triggered for " . $device['owner_name'];
        
        if (!empty($device['display_name'])) {
            $message .= " (" . $device['display_name'] . ")";
        }
        
        $message .= "\n\nConditions met:\n";
        
        foreach ($conditions['rules'] as $condition) {
            $field = $condition['field'];
            $operator = $condition['operator'];
            $value = $condition['value'];
            $actualValue = self::getFieldValue($field, $device);
            
            $fieldName = str_replace('_', ' ', ucfirst($field));
            $message .= "- " . $fieldName . " " . $operator . " " . $value . " (actual: " . $actualValue . ")\n";
        }
        
        $message .= "\nDevice Status:\n";
        $message .= "- Battery: " . $device['battery_level'] . "%\n";
        $message .= "- Last Seen: " . $device['last_seen'] . "\n";
        $message .= "- Storage Free: " . round($device['storage_free'] / 1073741824, 2) . " GB\n";
        
        return $message;
    }
    
    /**
     * Create a new alert rule
     */
    public static function createRule($name, $deviceId, $ruleType, $conditions, $actions, $cooldownMinutes = 60) {
        db()->query("
            INSERT INTO alert_rules 
            (name, device_id, rule_type, conditions, actions, cooldown_minutes, enabled)
            VALUES (?, ?, ?, ?, ?, ?, 1)
        ", [
            $name,
            $deviceId,
            $ruleType,
            json_encode($conditions),
            json_encode($actions),
            $cooldownMinutes
        ]);
        
        return db()->lastInsertId();
    }
    
    /**
     * Update an alert rule
     */
    public static function updateRule($id, $name, $deviceId, $ruleType, $conditions, $actions, $cooldownMinutes, $enabled) {
        db()->query("
            UPDATE alert_rules 
            SET name = ?, device_id = ?, rule_type = ?, conditions = ?, 
                actions = ?, cooldown_minutes = ?, enabled = ?
            WHERE id = ?
        ", [
            $name,
            $deviceId,
            $ruleType,
            json_encode($conditions),
            json_encode($actions),
            $cooldownMinutes,
            $enabled ? 1 : 0,
            $id
        ]);
    }
    
    /**
     * Delete an alert rule
     */
    public static function deleteRule($id) {
        db()->query("DELETE FROM alert_rules WHERE id = ?", [$id]);
    }
    
    /**
     * Get all alert rules
     */
    public static function getAllRules() {
        return db()->fetchAll("
            SELECT ar.*, d.owner_name
            FROM alert_rules ar
            LEFT JOIN devices d ON ar.device_id = d.device_id
            ORDER BY ar.enabled DESC, ar.name ASC
        ");
    }
    
    /**
     * Get alert rule by ID
     */
    public static function getRule($id) {
        return db()->fetchOne("SELECT * FROM alert_rules WHERE id = ?", [$id]);
    }
    
    /**
     * Get recent triggers
     */
    public static function getRecentTriggers($limit = 50) {
        return db()->fetchAll("
            SELECT art.*, ar.name as rule_name, d.owner_name
            FROM alert_rule_triggers art
            JOIN alert_rules ar ON art.alert_rule_id = ar.id
            JOIN devices d ON art.device_id = d.device_id
            ORDER BY art.triggered_at DESC
            LIMIT ?
        ", [$limit]);
    }
}
