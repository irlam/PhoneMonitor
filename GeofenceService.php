<?php
/**
 * Geofencing Service
 * Manages location-based zones and alerts
 */

class GeofenceService {
    
    /**
     * Calculate distance between two coordinates using Haversine formula
     * @param float $lat1 Latitude 1
     * @param float $lon1 Longitude 1
     * @param float $lat2 Latitude 2
     * @param float $lon2 Longitude 2
     * @return float Distance in meters
     */
    public static function calculateDistance($lat1, $lon1, $lat2, $lon2) {
        $earthRadius = 6371000; // meters
        
        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lon1);
        $latTo = deg2rad($lat2);
        $lonTo = deg2rad($lon2);
        
        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;
        
        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
        
        return $earthRadius * $angle;
    }
    
    /**
     * Check if coordinates are within a geofence
     * @param float $lat Latitude to check
     * @param float $lon Longitude to check
     * @param array $geofence Geofence data
     * @return bool True if inside geofence
     */
    public static function isInside($lat, $lon, $geofence) {
        $distance = self::calculateDistance(
            $lat, $lon,
            $geofence['latitude'], $geofence['longitude']
        );
        
        return $distance <= $geofence['radius_meters'];
    }
    
    /**
     * Get all active geofences for a device
     * @param int $deviceId Device ID
     * @return array Array of geofences
     */
    public static function getActiveGeofences($deviceId) {
        return db()->fetchAll(
            "SELECT * FROM geofences 
             WHERE active = 1 
             AND (device_id IS NULL OR device_id = ?)
             ORDER BY name",
            [$deviceId]
        );
    }
    
    /**
     * Check geofences for a location update
     * @param int $deviceId Device ID
     * @param float $lat Latitude
     * @param float $lon Longitude
     */
    public static function checkGeofences($deviceId, $lat, $lon) {
        $geofences = self::getActiveGeofences($deviceId);
        
        foreach ($geofences as $geofence) {
            $isInside = self::isInside($lat, $lon, $geofence);
            $wasInside = self::wasInside($deviceId, $geofence['id']);
            
            // Entering geofence
            if ($isInside && !$wasInside && $geofence['alert_on_enter']) {
                self::recordEvent($geofence['id'], $deviceId, 'enter', $lat, $lon, $geofence);
                self::sendAlert($deviceId, $geofence, 'enter');
            }
            
            // Exiting geofence
            if (!$isInside && $wasInside && $geofence['alert_on_exit']) {
                self::recordEvent($geofence['id'], $deviceId, 'exit', $lat, $lon, $geofence);
                self::sendAlert($deviceId, $geofence, 'exit');
            }
        }
    }
    
    /**
     * Check if device was previously inside geofence
     * @param int $deviceId Device ID
     * @param int $geofenceId Geofence ID
     * @return bool True if was inside in last event
     */
    private static function wasInside($deviceId, $geofenceId) {
        $lastEvent = db()->fetchOne(
            "SELECT event_type FROM geofence_events 
             WHERE device_id = ? AND geofence_id = ? 
             ORDER BY created_at DESC LIMIT 1",
            [$deviceId, $geofenceId]
        );
        
        return $lastEvent && $lastEvent['event_type'] === 'enter';
    }
    
    /**
     * Record a geofence event
     */
    private static function recordEvent($geofenceId, $deviceId, $eventType, $lat, $lon, $geofence) {
        $distance = self::calculateDistance(
            $lat, $lon,
            $geofence['latitude'], $geofence['longitude']
        );
        
        db()->query(
            "INSERT INTO geofence_events 
             (geofence_id, device_id, event_type, latitude, longitude, distance_meters) 
             VALUES (?, ?, ?, ?, ?, ?)",
            [$geofenceId, $deviceId, $eventType, $lat, $lon, round($distance)]
        );
    }
    
    /**
     * Send geofence alert
     */
    private static function sendAlert($deviceId, $geofence, $eventType) {
        $device = db()->fetchOne("SELECT * FROM devices WHERE id = ?", [$deviceId]);
        if (!$device) return;
        
        $action = $eventType === 'enter' ? 'entered' : 'left';
        $subject = "{$device['display_name']} {$action} {$geofence['name']}";
        $body = "Device {$device['display_name']} has {$action} the geofence '{$geofence['name']}' at " . date('Y-m-d H:i:s');
        
        NotificationService::queueEmail(
            getenv('ADMIN_EMAIL') ?: 'admin@localhost',
            $subject,
            $body,
            'geofence',
            $deviceId
        );
    }
    
    /**
     * Create a new geofence
     */
    public static function create($name, $lat, $lon, $radiusMeters, $deviceId = null, $alertOnEnter = true, $alertOnExit = false) {
        return db()->query(
            "INSERT INTO geofences 
             (name, latitude, longitude, radius_meters, device_id, alert_on_enter, alert_on_exit) 
             VALUES (?, ?, ?, ?, ?, ?, ?)",
            [$name, $lat, $lon, $radiusMeters, $deviceId, $alertOnEnter, $alertOnExit]
        );
    }
    
    /**
     * Get all geofences
     */
    public static function getAll() {
        return db()->fetchAll(
            "SELECT g.*, d.display_name as device_name 
             FROM geofences g 
             LEFT JOIN devices d ON g.device_id = d.id 
             ORDER BY g.created_at DESC"
        );
    }
    
    /**
     * Delete a geofence
     */
    public static function delete($id) {
        return db()->query("DELETE FROM geofences WHERE id = ?", [$id]);
    }
}
