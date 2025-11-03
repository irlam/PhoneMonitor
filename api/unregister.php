<?php
/**
 * API: Unregister Device
 * POST /api/unregister.php
 * Payload: {device_uuid}
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
        "SELECT id FROM devices WHERE device_uuid = ? LIMIT 1",
        [$deviceUuid]
    );
    
    if (!$device) {
        http_response_code(404);
        echo json_encode(['error' => 'Device not found']);
        exit;
    }
    
    $deviceId = $device['id'];
    
    // Mark as revoked instead of deleting (audit trail)
    db()->query(
        "UPDATE devices SET revoked = 1 WHERE id = ?",
        [$deviceId]
    );
    
    Auth::logAction('device_unregistered', $deviceId, [
        'device_uuid' => $deviceUuid,
        'source' => 'api'
    ]);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Device unregistered'
    ]);
} catch (Exception $e) {
    error_log("Unregister API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
