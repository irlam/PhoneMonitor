<?php
/**
 * Export Service - CSV and PDF generation
 */

require_once __DIR__ . '/db.php';

class ExportService {
    
    /**
     * Export all devices to CSV
     */
    public static function exportDevicesCSV() {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="devices_' . date('Y-m-d') . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        $output = fopen('php://output', 'w');
        
        // UTF-8 BOM for Excel
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Headers
        fputcsv($output, [
            'Device ID',
            'Owner Name',
            'Display Name',
            'Device Model',
            'OS Version',
            'Status',
            'Battery Level',
            'Storage Free (MB)',
            'Last Location',
            'Last Seen',
            'Registered At'
        ]);
        
        // Data
        $devices = db()->fetchAll("
            SELECT d.*, 
                   (SELECT CONCAT(latitude, ',', longitude) 
                    FROM device_locations 
                    WHERE device_id = d.device_id 
                    ORDER BY timestamp DESC LIMIT 1) as last_location
            FROM devices d
            ORDER BY d.last_seen DESC
        ");
        
        $count = 0;
        foreach ($devices as $device) {
            $status = 'Unknown';
            if ($device['consent_given'] == 0) {
                $status = 'Revoked';
            } elseif (strtotime($device['last_seen']) > time() - 1800) {
                $status = 'Online';
            } else {
                $status = 'Offline';
            }
            
            fputcsv($output, [
                $device['device_id'],
                $device['owner_name'],
                $device['display_name'] ?? '',
                $device['device_model'] ?? '',
                $device['os_version'] ?? '',
                $status,
                $device['battery_level'] . '%',
                round($device['storage_free'] / 1048576, 2),
                $device['last_location'] ?? 'No location',
                $device['last_seen'],
                $device['created_at']
            ]);
            $count++;
        }
        
        fclose($output);
        
        // Log export
        self::logExport('devices_csv', null, null, null, $count);
        exit;
    }
    
    /**
     * Export device location history to CSV
     */
    public static function exportLocationsCSV($deviceId, $dateFrom = null, $dateTo = null) {
        $device = db()->fetchOne("SELECT * FROM devices WHERE device_id = ?", [$deviceId]);
        if (!$device) {
            die('Device not found');
        }
        
        $filename = 'locations_' . $deviceId . '_' . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Headers
        fputcsv($output, [
            'Timestamp',
            'Latitude',
            'Longitude',
            'Accuracy (m)',
            'Altitude (m)',
            'Speed (mph)',
            'Provider',
            'Google Maps Link'
        ]);
        
        // Build query
        $sql = "SELECT * FROM device_locations WHERE device_id = ?";
        $params = [$deviceId];
        
        if ($dateFrom) {
            $sql .= " AND timestamp >= ?";
            $params[] = $dateFrom;
        }
        
        if ($dateTo) {
            $sql .= " AND timestamp <= ?";
            $params[] = $dateTo . ' 23:59:59';
        }
        
        $sql .= " ORDER BY timestamp DESC LIMIT 10000";
        
        $locations = db()->fetchAll($sql, $params);
        
        $count = 0;
        foreach ($locations as $loc) {
            $mapsLink = "https://www.google.com/maps?q=" . $loc['latitude'] . "," . $loc['longitude'];
            
            $speed_mph = isset($loc['speed']) ? round(((float)$loc['speed']) * 0.621371, 2) : 0;
            fputcsv($output, [
                $loc['timestamp'],
                $loc['latitude'],
                $loc['longitude'],
                $loc['accuracy'],
                $loc['altitude'] ?? 0,
                $speed_mph,
                $loc['provider'] ?? 'gps',
                $mapsLink
            ]);
            $count++;
        }
        
        fclose($output);
        
        // Log export
        self::logExport('locations_csv', $deviceId, $dateFrom, $dateTo, $count);
        exit;
    }
    
    /**
     * Export battery history to CSV
     */
    public static function exportBatteryCSV($deviceId = null, $dateFrom = null, $dateTo = null) {
        $filename = 'battery_history_' . ($deviceId ?? 'all') . '_' . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Headers
        fputcsv($output, [
            'Device ID',
            'Owner Name',
            'Timestamp',
            'Battery Level (%)',
            'Storage Free (MB)'
        ]);
        
        // Build query - get battery snapshots from devices table history
        // Since we don't have a battery_history table, we'll get latest from devices
        // and aggregate location pings which also update battery
        
        $sql = "
            SELECT 
                d.device_id,
                d.owner_name,
                d.last_seen as timestamp,
                d.battery_level,
                d.storage_free
            FROM devices d
            WHERE 1=1
        ";
        
        $params = [];
        
        if ($deviceId) {
            $sql .= " AND d.device_id = ?";
            $params[] = $deviceId;
        }
        
        if ($dateFrom) {
            $sql .= " AND d.last_seen >= ?";
            $params[] = $dateFrom;
        }
        
        if ($dateTo) {
            $sql .= " AND d.last_seen <= ?";
            $params[] = $dateTo . ' 23:59:59';
        }
        
        $sql .= " ORDER BY d.last_seen DESC LIMIT 10000";
        
        $records = db()->fetchAll($sql, $params);
        
        $count = 0;
        foreach ($records as $record) {
            fputcsv($output, [
                $record['device_id'],
                $record['owner_name'],
                $record['timestamp'],
                $record['battery_level'],
                round($record['storage_free'] / 1048576, 2)
            ]);
            $count++;
        }
        
        fclose($output);
        
        // Log export
        self::logExport('battery_csv', $deviceId, $dateFrom, $dateTo, $count);
        exit;
    }
    
    /**
     * Generate PDF report for a device
     */
    public static function generatePDFReport($deviceId) {
        $device = db()->fetchOne("SELECT * FROM devices WHERE device_id = ?", [$deviceId]);
        if (!$device) {
            die('Device not found');
        }
        
        // Simple text-based PDF (basic implementation)
        // For production, use a library like TCPDF or FPDF
        
        $filename = 'report_' . $deviceId . '_' . date('Y-m-d') . '.txt';
        
        header('Content-Type: text/plain; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        echo "═══════════════════════════════════════════════════════════\n";
        echo "              PHONEMONITOR DEVICE REPORT\n";
        echo "═══════════════════════════════════════════════════════════\n\n";
        
        echo "Generated: " . date('Y-m-d H:i:s') . "\n";
        echo "Report Period: Last 30 days\n\n";
        
        echo "───────────────────────────────────────────────────────────\n";
        echo " DEVICE INFORMATION\n";
        echo "───────────────────────────────────────────────────────────\n\n";
        
        echo "Device ID:      " . $device['device_id'] . "\n";
        echo "Owner:          " . $device['owner_name'] . "\n";
        echo "Display Name:   " . ($device['display_name'] ?? 'N/A') . "\n";
        echo "Model:          " . ($device['device_model'] ?? 'N/A') . "\n";
        echo "OS Version:     " . ($device['os_version'] ?? 'N/A') . "\n";
        echo "Status:         " . ($device['consent_given'] ? 'Active' : 'Revoked') . "\n";
        echo "Last Seen:      " . $device['last_seen'] . "\n";
        echo "Registered:     " . $device['created_at'] . "\n\n";
        
        echo "───────────────────────────────────────────────────────────\n";
        echo " CURRENT STATUS\n";
        echo "───────────────────────────────────────────────────────────\n\n";
        
        echo "Battery Level:  " . $device['battery_level'] . "%\n";
        echo "Storage Free:   " . round($device['storage_free'] / 1073741824, 2) . " GB\n";
    $lastSpeedMph = round(($device['last_speed'] ?? 0) * 0.621371, 2);
    echo "Last Speed:     " . $lastSpeedMph . " mph\n\n";
        
        // Location stats
        $locationStats = db()->fetchOne("
            SELECT 
                COUNT(*) as total_locations,
                MIN(timestamp) as first_location,
                MAX(timestamp) as last_location,
                AVG(accuracy) as avg_accuracy
            FROM device_locations
            WHERE device_id = ?
            AND timestamp >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ", [$deviceId]);
        
        echo "───────────────────────────────────────────────────────────\n";
        echo " LOCATION TRACKING (Last 30 Days)\n";
        echo "───────────────────────────────────────────────────────────\n\n";
        
        echo "Total Updates:  " . $locationStats['total_locations'] . "\n";
        echo "First Update:   " . ($locationStats['first_location'] ?? 'N/A') . "\n";
        echo "Last Update:    " . ($locationStats['last_location'] ?? 'N/A') . "\n";
        echo "Avg Accuracy:   " . round($locationStats['avg_accuracy'] ?? 0, 1) . " meters\n\n";
        
        // Recent locations
        $recentLocations = db()->fetchAll("
            SELECT * FROM device_locations
            WHERE device_id = ?
            ORDER BY timestamp DESC
            LIMIT 10
        ", [$deviceId]);
        
        echo "───────────────────────────────────────────────────────────\n";
        echo " RECENT LOCATIONS (Last 10)\n";
        echo "───────────────────────────────────────────────────────────\n\n";
        
        foreach ($recentLocations as $loc) {
            echo date('Y-m-d H:i:s', strtotime($loc['timestamp'])) . " | ";
            echo $loc['latitude'] . ", " . $loc['longitude'] . " | ";
            echo "±" . round($loc['accuracy']) . "m\n";
        }
        
        echo "\n";
        
        // Geofence events
        $geofenceEvents = db()->fetchAll("
            SELECT ge.*, g.name as geofence_name
            FROM geofence_events ge
            JOIN geofences g ON ge.geofence_id = g.id
            WHERE ge.device_id = ?
            AND ge.timestamp >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ORDER BY ge.timestamp DESC
            LIMIT 20
        ", [$deviceId]);
        
        if (!empty($geofenceEvents)) {
            echo "───────────────────────────────────────────────────────────\n";
            echo " GEOFENCE EVENTS (Last 30 Days)\n";
            echo "───────────────────────────────────────────────────────────\n\n";
            
            foreach ($geofenceEvents as $event) {
                echo date('Y-m-d H:i:s', strtotime($event['timestamp'])) . " | ";
                echo strtoupper($event['event_type']) . " | ";
                echo $event['geofence_name'] . "\n";
            }
            echo "\n";
        }
        
        echo "═══════════════════════════════════════════════════════════\n";
        echo "                   END OF REPORT\n";
        echo "═══════════════════════════════════════════════════════════\n";
        
        // Log export
        self::logExport('report_pdf', $deviceId, null, null, 1);
        exit;
    }
    
    /**
     * Log export activity
     */
    private static function logExport($type, $deviceId, $dateFrom, $dateTo, $rowCount) {
        try {
            db()->query("
                INSERT INTO export_history 
                (export_type, device_id, date_from, date_to, row_count, exported_by)
                VALUES (?, ?, ?, ?, ?, ?)
            ", [
                $type,
                $deviceId,
                $dateFrom,
                $dateTo,
                $rowCount,
                $_SESSION['user_name'] ?? 'system'
            ]);
        } catch (Exception $e) {
            // Ignore logging errors
        }
    }
}
