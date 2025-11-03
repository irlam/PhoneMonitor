<?php
/**
 * API: Ping (Heartbeat)
 * POST /api/ping.php
 * Payload: {device_uuid, battery, free_storage, note, lat?, lon?, accuracy?, provider?, loc_ts?}
 */

// CORS headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../GeofenceService.php';
require_once __DIR__ . '/../NotificationService.php';

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed. Use POST.']);
    exit;
}

// Rate limiting - simple IP-based (allow more frequent pings than registration)
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$cacheFile = sys_get_temp_dir() . '/pm_ping_' . md5($ip);
if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 10) {
    http_response_code(429);
    echo json_encode(['error' => 'Too many requests. Please wait.']);
    exit;
}
touch($cacheFile);

// Parse JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

// Validate required fields
$deviceUuid = trim($input['device_uuid'] ?? '');

if (empty($deviceUuid)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required field: device_uuid']);
    exit;
}

try {
    // Find device
    $device = db()->fetchOne(
        "SELECT id, revoked FROM devices WHERE device_uuid = ? LIMIT 1",
        [$deviceUuid]
    );
    
    if (!$device) {
        http_response_code(404);
        echo json_encode(['error' => 'Device not found. Please register first.']);
        exit;
    }
    
    if ($device['revoked']) {
        http_response_code(403);
        echo json_encode(['error' => 'Device has been revoked']);
        exit;
    }
    
    $deviceId = $device['id'];
    
    // Build payload
    $payload = [];
    if (isset($input['battery'])) {
        $payload['battery'] = intval($input['battery']);
    }
    if (isset($input['free_storage'])) {
        $payload['free_storage'] = floatval($input['free_storage']);
    }
    if (isset($input['note'])) {
        $payload['note'] = substr(trim($input['note']), 0, 500);
    }
    
    // Handle location data if provided
    $hasLocation = false;
    $lat = null;
    $lon = null;
    $accuracy = null;
    $provider = null;
    $locTs = null;
    
    if (isset($input['lat']) && isset($input['lon'])) {
        $lat = floatval($input['lat']);
        $lon = floatval($input['lon']);
        
        // Validate coordinates
        if ($lat >= -90 && $lat <= 90 && $lon >= -180 && $lon <= 180) {
            $hasLocation = true;
            $payload['lat'] = $lat;
            $payload['lon'] = $lon;
            
            if (isset($input['accuracy'])) {
                $accuracy = floatval($input['accuracy']);
                $payload['accuracy'] = $accuracy;
            }
            
            if (isset($input['provider'])) {
                $provider = substr(trim($input['provider']), 0, 32);
                $payload['provider'] = $provider;
            }
            
            if (isset($input['loc_ts'])) {
                // Convert milliseconds to timestamp
                $locTsMs = intval($input['loc_ts']);
                if ($locTsMs > 0) {
                    $locTs = date('Y-m-d H:i:s', $locTsMs / 1000);
                    $payload['loc_ts'] = $locTs;
                }
            }
        }
    }
    
    $payloadJson = !empty($payload) ? json_encode($payload) : null;
    
    // Update device
    db()->query(
        "UPDATE devices SET last_seen = NOW(), last_payload = ? WHERE id = ?",
        [$payloadJson, $deviceId]
    );
    
    // Store location in separate table if provided
    if ($hasLocation) {
        db()->query(
            "INSERT INTO device_locations (device_id, lat, lon, accuracy, provider, loc_ts) VALUES (?, ?, ?, ?, ?, ?)",
            [$deviceId, $lat, $lon, $accuracy, $provider, $locTs]
        );
        
        // Check geofences for this location
        GeofenceService::checkGeofences($deviceId, $lat, $lon);
    }
    
    // Check for low battery alert (below 15%)
    if (isset($payload['battery']) && $payload['battery'] < 15) {
        NotificationService::sendLowBatteryAlert($deviceId, $payload['battery']);
    }
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Ping received',
        'timestamp' => date('c')
    ]);
} catch (Exception $e) {
    error_log("Ping API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
