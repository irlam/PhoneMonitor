<?php
/**
 * API: Register Device
 * POST /api/register.php
 * Payload: {device_uuid, display_name, owner_name, consent}
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Rate limiting - simple IP-based
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$cacheFile = sys_get_temp_dir() . '/pm_register_' . md5($ip);
if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 60) {
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
$displayName = trim($input['display_name'] ?? '');
$ownerName = trim($input['owner_name'] ?? '');
$consent = filter_var($input['consent'] ?? false, FILTER_VALIDATE_BOOLEAN);

if (empty($deviceUuid) || empty($displayName) || empty($ownerName)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields: device_uuid, display_name, owner_name']);
    exit;
}

// Validate UUID format
if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $deviceUuid)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid device_uuid format']);
    exit;
}

// Check if consent is required
if (REQUIRE_CONSENT && !$consent) {
    http_response_code(403);
    echo json_encode(['error' => 'Consent is required']);
    exit;
}

try {
    // Check if device already exists
    $existing = db()->fetchOne(
        "SELECT id, revoked FROM devices WHERE device_uuid = ? LIMIT 1",
        [$deviceUuid]
    );
    
    if ($existing) {
        if ($existing['revoked']) {
            http_response_code(403);
            echo json_encode(['error' => 'Device has been revoked']);
            exit;
        }
        
        // Update existing device
        db()->query(
            "UPDATE devices SET display_name = ?, owner_name = ?, consent_given = ? WHERE device_uuid = ?",
            [$displayName, $ownerName, $consent ? 1 : 0, $deviceUuid]
        );
        
        Auth::logAction('device_updated', $existing['id'], [
            'device_uuid' => $deviceUuid,
            'display_name' => $displayName
        ]);
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Device updated',
            'device_id' => $existing['id']
        ]);
    } else {
        // Register new device
        db()->query(
            "INSERT INTO devices (device_uuid, display_name, owner_name, consent_given) VALUES (?, ?, ?, ?)",
            [$deviceUuid, $displayName, $ownerName, $consent ? 1 : 0]
        );
        
        $deviceId = db()->lastInsertId();
        
        Auth::logAction('device_registered', $deviceId, [
            'device_uuid' => $deviceUuid,
            'display_name' => $displayName,
            'owner_name' => $ownerName
        ]);
        
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Device registered',
            'device_id' => $deviceId
        ]);
    }
} catch (Exception $e) {
    error_log("Register API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
