<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

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

try {
    // Get device
    $device = fetchOne("SELECT id FROM devices WHERE device_uuid = ?", [$deviceUuid]);
    
    if (!$device) {
        http_response_code(404);
        echo json_encode(['error' => 'Device not found']);
        exit;
    }
    
    // Mark device as revoked
    executeQuery("UPDATE devices SET revoked = 1 WHERE id = ?", [$device['id']]);
    
    // Log unregistration
    executeQuery(
        "INSERT INTO audit_log (device_id, action, meta) VALUES (?, 'unregister', ?)",
        [$device['id'], json_encode(['source' => 'device'])]
    );
    
    echo json_encode([
        'success' => true,
        'message' => 'Device unregistered successfully'
    ]);
    
} catch (Exception $e) {
    error_log("Unregister API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
