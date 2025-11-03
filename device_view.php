<?php
/**
 * Device Detail View with Location Map
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';

Auth::require();

$deviceId = intval($_GET['id'] ?? 0);

if (!$deviceId) {
    header('Location: /dashboard.php');
    exit;
}

// Fetch device details
$device = db()->fetchOne(
    "SELECT * FROM devices WHERE id = ? LIMIT 1",
    [$deviceId]
);

if (!$device) {
    header('Location: /dashboard.php');
    exit;
}

// Fetch recent locations (last 10)
$locations = db()->fetchAll(
    "SELECT * FROM device_locations 
     WHERE device_id = ? 
     ORDER BY created_at DESC 
     LIMIT 10",
    [$deviceId]
);

// Get the latest location for the map
$latestLocation = !empty($locations) ? $locations[0] : null;

$payload = $device['last_payload'] ? json_decode($device['last_payload'], true) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($device['display_name']); ?> - PhoneMonitor</title>
    <link rel="stylesheet" href="assets/css/site.css?v=<?php echo urlencode(ASSET_VERSION); ?>">
    <?php if (GOOGLE_MAPS_API_KEY && $latestLocation): ?>
        <script src="https://maps.googleapis.com/maps/api/js?key=<?php echo htmlspecialchars(GOOGLE_MAPS_API_KEY); ?>"></script>
    <?php endif; ?>
</head>
<body>
    <div class="container">
        <header class="header">
            <h1>PhoneMonitor</h1>
            <div class="header-actions">
                <span class="user-info">Logged in as <?php echo htmlspecialchars(Auth::name()); ?></span>
                <a href="/logout.php" class="btn btn-secondary">Logout</a>
            </div>
        </header>
        
        <nav class="nav">
            <a href="/dashboard.php">Dashboard</a>
            <a href="/devices.php">All Devices</a>
        </nav>
        
        <main class="main-content">
            <div class="page-header">
                <h2><?php echo htmlspecialchars($device['display_name']); ?></h2>
                <div>
                    <?php if ($device['revoked']): ?>
                        <span class="badge badge-danger">Revoked</span>
                    <?php else: ?>
                        <span class="badge badge-success">Active</span>
                    <?php endif; ?>
                    <?php if ($device['consent_given']): ?>
                        <span class="badge badge-success">Consent Given</span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="device-details">
                <div class="info-section">
                    <h3>Device Information</h3>
                    <table class="info-table">
                        <tr>
                            <th>Owner:</th>
                            <td><?php echo htmlspecialchars($device['owner_name']); ?></td>
                        </tr>
                        <tr>
                            <th>Device UUID:</th>
                            <td><code><?php echo htmlspecialchars($device['device_uuid']); ?></code></td>
                        </tr>
                        <tr>
                            <th>Registered:</th>
                            <td><?php echo date('d/m/Y H:i:s', strtotime($device['registered_at'])); ?></td>
                        </tr>
                        <tr>
                            <th>Last Seen:</th>
                            <td>
                                <?php 
                                if ($device['last_seen']) {
                                    echo date('d/m/Y H:i:s', strtotime($device['last_seen']));
                                    $diff = time() - strtotime($device['last_seen']);
                                    if ($diff < 3600) {
                                        echo ' (' . floor($diff / 60) . ' minutes ago)';
                                    }
                                } else {
                                    echo 'Never';
                                }
                                ?>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <?php if (!empty($payload)): ?>
                    <div class="info-section">
                        <h3>Current Status</h3>
                        <table class="info-table">
                            <?php if (isset($payload['battery'])): ?>
                                <tr>
                                    <th>Battery:</th>
                                    <td><?php echo htmlspecialchars($payload['battery']); ?>%</td>
                                </tr>
                            <?php endif; ?>
                            <?php if (isset($payload['free_storage'])): ?>
                                <tr>
                                    <th>Free Storage:</th>
                                    <td><?php echo htmlspecialchars($payload['free_storage']); ?> GB</td>
                                </tr>
                            <?php endif; ?>
                            <?php if (isset($payload['note'])): ?>
                                <tr>
                                    <th>Note:</th>
                                    <td><?php echo htmlspecialchars($payload['note']); ?></td>
                                </tr>
                            <?php endif; ?>
                        </table>
                    </div>
                <?php endif; ?>
                
                <?php if ($latestLocation): ?>
                    <div class="info-section">
                        <h3>Location</h3>
                        
                        <?php if (GOOGLE_MAPS_API_KEY): ?>
                            <div id="map" style="width: 100%; height: 400px; margin-bottom: 20px;"></div>
                            <p>
                                <a href="https://www.google.com/maps?q=<?php echo $latestLocation['lat']; ?>,<?php echo $latestLocation['lon']; ?>" 
                                   target="_blank" class="btn btn-primary">View on Google Maps</a>
                            </p>
                            
                            <script>
                                function initMap() {
                                    const location = {
                                        lat: <?php echo $latestLocation['lat']; ?>,
                                        lng: <?php echo $latestLocation['lon']; ?>
                                    };
                                    
                                    const map = new google.maps.Map(document.getElementById('map'), {
                                        zoom: 15,
                                        center: location
                                    });
                                    
                                    const marker = new google.maps.Marker({
                                        position: location,
                                        map: map,
                                        title: '<?php echo htmlspecialchars($device['display_name']); ?>'
                                    });
                                    
                                    const infoWindow = new google.maps.InfoWindow({
                                        content: '<div><strong><?php echo htmlspecialchars($device['display_name']); ?></strong><br>' +
                                                'Last updated: <?php echo date('d/m/Y H:i:s', strtotime($latestLocation['created_at'])); ?><br>' +
                                                'Accuracy: <?php echo $latestLocation['accuracy'] ?? 'N/A'; ?>m</div>'
                                    });
                                    
                                    marker.addListener('click', function() {
                                        infoWindow.open(map, marker);
                                    });
                                }
                                
                                window.onload = initMap;
                            </script>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <strong>Map unavailable</strong>
                                <p>Add GOOGLE_MAPS_API_KEY to your .env file to display the map.</p>
                                <p>Location: <?php echo $latestLocation['lat']; ?>, <?php echo $latestLocation['lon']; ?></p>
                                <p>
                                    <a href="https://www.google.com/maps?q=<?php echo $latestLocation['lat']; ?>,<?php echo $latestLocation['lon']; ?>" 
                                       target="_blank">View on Google Maps</a>
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($locations)): ?>
                    <div class="info-section">
                        <h3>Recent Locations (Last 10)</h3>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Date/Time</th>
                                        <th>Latitude</th>
                                        <th>Longitude</th>
                                        <th>Accuracy</th>
                                        <th>Provider</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($locations as $loc): ?>
                                        <tr>
                                            <td><?php echo date('d/m/Y H:i:s', strtotime($loc['created_at'])); ?></td>
                                            <td><?php echo htmlspecialchars($loc['lat']); ?></td>
                                            <td><?php echo htmlspecialchars($loc['lon']); ?></td>
                                            <td><?php echo $loc['accuracy'] ? htmlspecialchars($loc['accuracy']) . 'm' : 'N/A'; ?></td>
                                            <td><?php echo htmlspecialchars($loc['provider'] ?? 'N/A'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <p>No location data available for this device.</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="actions">
                <a href="/dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
            </div>
        </main>
        
        <footer class="footer">
            <p>PhoneMonitor - Consent-based Family Device Helper</p>
            <p><small>No access to personal data, messages, calls, or media · No keylogging, screenshots, or surveillance capabilities</small></p>
        </footer>
    </div>
</body>
</html>
