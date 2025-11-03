<?php
/**
 * Analytics Service - Device statistics and insights
 */

require_once __DIR__ . '/db.php';

class AnalyticsService {
    
    /**
     * Get cached analytics data
     */
    private static function getCache($key) {
        $cached = db()->fetchOne("
            SELECT * FROM analytics_cache 
            WHERE cache_key = ? AND expires_at > NOW()
        ", [$key]);
        
        if ($cached) {
            return json_decode($cached['cache_data'], true);
        }
        
        return null;
    }
    
    /**
     * Set cache
     */
    private static function setCache($key, $data, $expiresMinutes = 15) {
        // Delete old cache
        db()->query("DELETE FROM analytics_cache WHERE cache_key = ?", [$key]);
        
        // Insert new cache
        db()->query("
            INSERT INTO analytics_cache (cache_key, cache_data, expires_at)
            VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ? MINUTE))
        ", [$key, json_encode($data), $expiresMinutes]);
    }
    
    /**
     * Get overview statistics
     */
    public static function getOverviewStats() {
        $cacheKey = 'overview_stats';
        $cached = self::getCache($cacheKey);
        if ($cached) return $cached;
        
        $stats = [
            'total_devices' => 0,
            'online_devices' => 0,
            'offline_devices' => 0,
            'revoked_devices' => 0,
            'avg_battery' => 0,
            'low_battery_count' => 0,
            'total_locations' => 0,
            'locations_today' => 0,
        ];
        
        $devices = db()->fetchAll("SELECT * FROM devices");
        $stats['total_devices'] = count($devices);
        
        $batteryLevels = [];
        foreach ($devices as $device) {
            if (!$device['consent_given']) {
                $stats['revoked_devices']++;
            } elseif (strtotime($device['last_seen']) > time() - 1800) {
                $stats['online_devices']++;
            } else {
                $stats['offline_devices']++;
            }
            
            $batteryLevels[] = $device['battery_level'];
            if ($device['battery_level'] < 20) {
                $stats['low_battery_count']++;
            }
        }
        
        $stats['avg_battery'] = !empty($batteryLevels) ? round(array_sum($batteryLevels) / count($batteryLevels), 1) : 0;
        
        $locationCount = db()->fetchOne("SELECT COUNT(*) as count FROM device_locations");
        $stats['total_locations'] = $locationCount['count'];
        
        $todayCount = db()->fetchOne("
            SELECT COUNT(*) as count FROM device_locations 
            WHERE DATE(timestamp) = CURDATE()
        ");
        $stats['locations_today'] = $todayCount['count'];
        
        self::setCache($cacheKey, $stats, 5);
        return $stats;
    }
    
    /**
     * Get battery trends for charts
     */
    public static function getBatteryTrends($deviceId = null, $days = 7) {
        $cacheKey = 'battery_trends_' . ($deviceId ?? 'all') . '_' . $days;
        $cached = self::getCache($cacheKey);
        if ($cached) return $cached;
        
        // Get daily averages
        $sql = "
            SELECT 
                DATE(last_seen) as date,
                device_id,
                AVG(battery_level) as avg_battery,
                MIN(battery_level) as min_battery,
                MAX(battery_level) as max_battery
            FROM devices
            WHERE last_seen >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
        ";
        
        $params = [$days];
        
        if ($deviceId) {
            $sql .= " AND device_id = ?";
            $params[] = $deviceId;
        }
        
        $sql .= " GROUP BY DATE(last_seen), device_id ORDER BY date ASC";
        
        $data = db()->fetchAll($sql, $params);
        
        self::setCache($cacheKey, $data, 30);
        return $data;
    }
    
    /**
     * Get activity timeline (locations per hour)
     */
    public static function getActivityTimeline($deviceId = null, $days = 7) {
        $cacheKey = 'activity_timeline_' . ($deviceId ?? 'all') . '_' . $days;
        $cached = self::getCache($cacheKey);
        if ($cached) return $cached;
        
        $sql = "
            SELECT 
                DATE(timestamp) as date,
                HOUR(timestamp) as hour,
                COUNT(*) as location_count
            FROM device_locations
            WHERE timestamp >= DATE_SUB(NOW(), INTERVAL ? DAY)
        ";
        
        $params = [$days];
        
        if ($deviceId) {
            $sql .= " AND device_id = ?";
            $params[] = $deviceId;
        }
        
        $sql .= " GROUP BY DATE(timestamp), HOUR(timestamp) ORDER BY date, hour";
        
        $data = db()->fetchAll($sql, $params);
        
        self::setCache($cacheKey, $data, 30);
        return $data;
    }
    
    /**
     * Get location heatmap data
     */
    public static function getLocationHeatmap($deviceId, $days = 30) {
        $cacheKey = 'location_heatmap_' . $deviceId . '_' . $days;
        $cached = self::getCache($cacheKey);
        if ($cached) return $cached;
        
        $data = db()->fetchAll("
            SELECT latitude, longitude, accuracy
            FROM device_locations
            WHERE device_id = ?
            AND timestamp >= DATE_SUB(NOW(), INTERVAL ? DAY)
            AND accuracy < 100
            ORDER BY timestamp DESC
            LIMIT 1000
        ", [$deviceId, $days]);
        
        self::setCache($cacheKey, $data, 60);
        return $data;
    }
    
    /**
     * Get device comparison stats
     */
    public static function getDeviceComparison() {
        $cacheKey = 'device_comparison';
        $cached = self::getCache($cacheKey);
        if ($cached) return $cached;
        
        $devices = db()->fetchAll("
            SELECT 
                d.device_id,
                d.owner_name,
                d.display_name,
                d.battery_level,
                d.storage_free,
                d.last_seen,
                d.consent_given,
                (SELECT COUNT(*) FROM device_locations WHERE device_id = d.device_id AND timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY)) as weekly_updates,
                (SELECT COUNT(*) FROM geofence_events WHERE device_id = d.device_id AND timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY)) as weekly_events
            FROM devices d
            ORDER BY d.owner_name
        ");
        
        self::setCache($cacheKey, $devices, 10);
        return $devices;
    }
    
    /**
     * Get geofence statistics
     */
    public static function getGeofenceStats() {
        $cacheKey = 'geofence_stats';
        $cached = self::getCache($cacheKey);
        if ($cached) return $cached;
        
        $stats = db()->fetchAll("
            SELECT 
                g.id,
                g.name,
                COUNT(ge.id) as total_events,
                SUM(CASE WHEN ge.event_type = 'enter' THEN 1 ELSE 0 END) as enter_count,
                SUM(CASE WHEN ge.event_type = 'exit' THEN 1 ELSE 0 END) as exit_count,
                MAX(ge.timestamp) as last_event
            FROM geofences g
            LEFT JOIN geofence_events ge ON g.id = ge.geofence_id
            AND ge.timestamp >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY g.id, g.name
            ORDER BY total_events DESC
        ");
        
        self::setCache($cacheKey, $stats, 30);
        return $stats;
    }
    
    /**
     * Get alert rule statistics
     */
    public static function getAlertRuleStats() {
        $cacheKey = 'alert_rule_stats';
        $cached = self::getCache($cacheKey);
        if ($cached) return $cached;
        
        $stats = db()->fetchAll("
            SELECT 
                ar.id,
                ar.name,
                ar.rule_type,
                ar.enabled,
                COUNT(art.id) as trigger_count,
                MAX(art.triggered_at) as last_triggered
            FROM alert_rules ar
            LEFT JOIN alert_rule_triggers art ON ar.id = art.alert_rule_id
            AND art.triggered_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY ar.id, ar.name, ar.rule_type, ar.enabled
            ORDER BY trigger_count DESC
        ");
        
        self::setCache($cacheKey, $stats, 15);
        return $stats;
    }
    
    /**
     * Get storage usage trends
     */
    public static function getStorageTrends($days = 30) {
        $cacheKey = 'storage_trends_' . $days;
        $cached = self::getCache($cacheKey);
        if ($cached) return $cached;
        
        $data = db()->fetchAll("
            SELECT 
                device_id,
                owner_name,
                storage_free,
                last_seen
            FROM devices
            WHERE last_seen >= DATE_SUB(NOW(), INTERVAL ? DAY)
            ORDER BY device_id, last_seen
        ", [$days]);
        
        self::setCache($cacheKey, $data, 30);
        return $data;
    }
    
    /**
     * Clear all analytics cache
     */
    public static function clearCache() {
        db()->query("DELETE FROM analytics_cache WHERE expires_at < NOW()");
    }
}
