<?php
/**
 * Export Controller - Handle CSV/PDF exports
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/ExportService.php';

Auth::require();

$type = $_GET['type'] ?? '';
$deviceId = $_GET['device_id'] ?? null;
$dateFrom = $_GET['date_from'] ?? null;
$dateTo = $_GET['date_to'] ?? null;

switch ($type) {
    case 'devices_csv':
        ExportService::exportDevicesCSV();
        break;
        
    case 'locations_csv':
        if (!$deviceId) {
            die('Device ID required');
        }
        ExportService::exportLocationsCSV($deviceId, $dateFrom, $dateTo);
        break;
        
    case 'battery_csv':
        ExportService::exportBatteryCSV($deviceId, $dateFrom, $dateTo);
        break;
        
    case 'report_pdf':
        if (!$deviceId) {
            die('Device ID required');
        }
        ExportService::generatePDFReport($deviceId);
        break;
        
    default:
        die('Invalid export type');
}
