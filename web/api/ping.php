<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

// Rate limiting (simple in-memory)
function checkRateLimit($identifier, $maxRequests = 60, $window = 60) {
    static $requests = [];
    $now = time();
    
    if (!isset($requests[$identifier])) {
        $requests[$identifier] = [];
    }
    
    // Clean old entries
    $requests[$identifier] = array_filter($requests[$identifier], function($time) use ($now, $window) {
        return ($now - $time) < $window;
    });
    
    if (count($requests[$identifier]) >= $maxRequests) {
        return false;
    }
    
    $requests[$identifier][] = $now;
    return true;
}

// Parse JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

// Validate required fields
if (!isset($input['device_uuid']) || trim($input['device_uuid']) === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Missing device_uuid']);
    exit;
}

$deviceUuid = trim($input['device_uuid']);

// Rate limiting
if (!checkRateLimit($deviceUuid, 30, 60)) {
    http_response_code(429);
    echo json_encode(['error' => 'Rate limit exceeded']);
    exit;
}

try {
    // Get device
    $device = fetchOne("SELECT id, revoked, consent_given FROM devices WHERE device_uuid = ?", [$deviceUuid]);
    
    if (!$device) {
        http_response_code(404);
        echo json_encode(['error' => 'Device not found']);
        exit;
    }
    
    if ($device['revoked']) {
        http_response_code(403);
        echo json_encode(['error' => 'revoked', 'message' => 'Device has been revoked']);
        exit;
    }
    
    if (REQUIRE_CONSENT && !$device['consent_given']) {
        http_response_code(403);
        echo json_encode(['error' => 'Consent required']);
        exit;
    }
    
    // Build payload
    $payload = [];
    
    if (isset($input['battery'])) {
        $payload['battery'] = (int)$input['battery'];
    }
    
    if (isset($input['free_storage'])) {
        $payload['free_storage'] = trim($input['free_storage']);
    }
    
    if (isset($input['note'])) {
        $payload['note'] = trim($input['note']);
    }
    
    // Handle location data
    $hasLocation = isset($input['lat']) && isset($input['lon']);
    
    if ($hasLocation) {
        $lat = (float)$input['lat'];
        $lon = (float)$input['lon'];
        $accuracy = isset($input['accuracy']) ? (float)$input['accuracy'] : null;
        $provider = isset($input['provider']) ? trim($input['provider']) : null;
        $locTs = isset($input['loc_ts']) ? (int)$input['loc_ts'] : null;
        
        // Validate coordinates
        if ($lat < -90 || $lat > 90 || $lon < -180 || $lon > 180) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid coordinates']);
            exit;
        }
        
        $payload['location'] = [
            'lat' => $lat,
            'lon' => $lon,
            'accuracy' => $accuracy,
            'provider' => $provider,
            'loc_ts' => $locTs
        ];
        
        // Insert location record
        $locTsFormatted = $locTs ? date('Y-m-d H:i:s', $locTs / 1000) : null;
        executeQuery(
            "INSERT INTO device_locations (device_id, lat, lon, accuracy, provider, loc_ts) VALUES (?, ?, ?, ?, ?, ?)",
            [$device['id'], $lat, $lon, $accuracy, $provider, $locTsFormatted]
        );
    }
    
    // Update device
    executeQuery(
        "UPDATE devices SET last_seen = NOW(), last_payload = ? WHERE id = ?",
        [json_encode($payload), $device['id']]
    );
    
    echo json_encode([
        'success' => true,
        'message' => 'Ping received'
    ]);
    
} catch (Exception $e) {
    error_log("Ping API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
