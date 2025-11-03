<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

// Rate limiting (simple in-memory)
function checkRateLimit($identifier, $maxRequests = 10, $window = 60) {
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
$requiredFields = ['device_uuid', 'display_name', 'owner_name', 'consent'];
foreach ($requiredFields as $field) {
    if (!isset($input[$field]) || trim($input[$field]) === '') {
        http_response_code(400);
        echo json_encode(['error' => "Missing required field: $field"]);
        exit;
    }
}

$deviceUuid = trim($input['device_uuid']);
$displayName = trim($input['display_name']);
$ownerName = trim($input['owner_name']);
$consent = (bool)$input['consent'];

// Validate UUID format
if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $deviceUuid)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid device UUID format']);
    exit;
}

// Check consent requirement
if (REQUIRE_CONSENT && !$consent) {
    http_response_code(400);
    echo json_encode(['error' => 'Consent is required']);
    exit;
}

// Rate limiting
if (!checkRateLimit($deviceUuid, 5, 300)) {
    http_response_code(429);
    echo json_encode(['error' => 'Rate limit exceeded']);
    exit;
}

try {
    // Check if device already exists
    $existing = fetchOne("SELECT id, revoked FROM devices WHERE device_uuid = ?", [$deviceUuid]);
    
    if ($existing) {
        if ($existing['revoked']) {
            http_response_code(403);
            echo json_encode(['error' => 'Device has been revoked']);
            exit;
        }
        
        // Update existing device
        executeQuery(
            "UPDATE devices SET display_name = ?, owner_name = ?, consent_given = ?, last_seen = NOW() WHERE device_uuid = ?",
            [$displayName, $ownerName, $consent, $deviceUuid]
        );
        
        echo json_encode([
            'success' => true,
            'device_id' => $existing['id'],
            'message' => 'Device updated successfully'
        ]);
    } else {
        // Register new device
        $deviceId = insertAndGetId(
            "INSERT INTO devices (device_uuid, display_name, owner_name, consent_given, last_seen) VALUES (?, ?, ?, ?, NOW())",
            [$deviceUuid, $displayName, $ownerName, $consent]
        );
        
        // Log registration
        executeQuery(
            "INSERT INTO audit_log (device_id, action, meta) VALUES (?, 'register', ?)",
            [$deviceId, json_encode(['consent' => $consent])]
        );
        
        echo json_encode([
            'success' => true,
            'device_id' => $deviceId,
            'message' => 'Device registered successfully'
        ]);
    }
} catch (Exception $e) {
    error_log("Register API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
