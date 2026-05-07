<?php
/**
 * API: Request Immediate Update from Device
 * POST /api/request_update.php
 * Requires admin session. Sets pending_refresh flag on the device so the next
 * ping response instructs the Android app to send a fresh update immediately.
 * Also clears the analytics cache so the analytics dashboard reflects the new data.
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../AnalyticsService.php';

Auth::require();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed. Use POST.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$deviceId = intval($input['device_id'] ?? 0);

if (!$deviceId) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required field: device_id']);
    exit;
}

try {
    $device = db()->fetchOne(
        "SELECT id, display_name FROM devices WHERE id = ? LIMIT 1",
        [$deviceId]
    );

    if (!$device) {
        http_response_code(404);
        echo json_encode(['error' => 'Device not found.']);
        exit;
    }

    db()->query(
        "UPDATE devices SET pending_refresh = 1 WHERE id = ?",
        [$deviceId]
    );

    // Clear analytics cache so the next page load shows fresh data
    AnalyticsService::clearCache();

    echo json_encode([
        'success' => true,
        'message' => 'Update request queued. The device will send fresh data when it next contacts the server (within the heartbeat interval).',
    ]);
} catch (Exception $e) {
    error_log("Request update API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
