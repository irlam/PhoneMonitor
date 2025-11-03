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

// Handle date filter
$filterDays = isset($_GET['days']) ? intval($_GET['days']) : 7;
$filterDays = max(1, min(90, $filterDays)); // Limit between 1 and 90 days

// Fetch locations based on filter
$locations = db()->fetchAll(
    "SELECT * FROM device_locations 
     WHERE device_id = ? 
     AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
     ORDER BY created_at DESC 
     LIMIT 500",
    [$deviceId, $filterDays]
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
                <button class="theme-toggle" onclick="toggleDarkMode()" title="Toggle dark mode">
                    <span id="theme-icon">🌙</span>
                </button>
                <span class="user-info">Logged in as <?php echo htmlspecialchars(Auth::name()); ?></span>
                <a href="/logout.php" class="btn btn-secondary">Logout</a>
            </div>
        </header>
        
        <nav class="nav">
            <a href="/dashboard.php">Dashboard</a>
            <a href="/devices.php">All Devices</a>
            <a href="/geofences.php">Geofences</a>
            <a href="/analytics.php">Analytics</a>
            <a href="/alert_rules.php">Alert Rules</a>
            <a href="/setup.php">Setup & Help</a>
        </nav>
        
        <main class="main-content">
            <div class="page-header">
                <h2><?php echo htmlspecialchars($device['display_name']); ?></h2>
                <div class="header-actions">
                    <a href="/export.php?type=locations_csv&device_id=<?php echo urlencode($device['device_id']); ?>&days=<?php echo $filterDays; ?>" class="btn btn-secondary" style="margin-right: 10px;">
                        📊 Export Locations CSV
                    </a>
                    <a href="/export.php?type=report_pdf&device_id=<?php echo urlencode($device['device_id']); ?>" class="btn btn-secondary">
                        📄 Generate Report
                    </a>
                </div>
            </div>
            
            <div style="margin-bottom: 20px;">
                <?php if ($device['revoked']): ?>
                    <span class="badge badge-danger">Revoked</span>
                <?php else: ?>
                    <span class="badge badge-success">Active</span>
                <?php endif; ?>
                <?php if ($device['consent_given']): ?>
                    <span class="badge badge-success">Consent Given</span>
                <?php endif; ?>
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
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                            <h3>Location History</h3>
                            <div class="filter-controls">
                                <a href="?id=<?php echo $deviceId; ?>&days=1" class="filter-btn <?php echo $filterDays == 1 ? 'active' : ''; ?>">Last 24h</a>
                                <a href="?id=<?php echo $deviceId; ?>&days=7" class="filter-btn <?php echo $filterDays == 7 ? 'active' : ''; ?>">Last Week</a>
                                <a href="?id=<?php echo $deviceId; ?>&days=30" class="filter-btn <?php echo $filterDays == 30 ? 'active' : ''; ?>">Last Month</a>
                                <a href="?id=<?php echo $deviceId; ?>&days=90" class="filter-btn <?php echo $filterDays == 90 ? 'active' : ''; ?>">Last 90 Days</a>
                            </div>
                        </div>
                        <p style="color: #6c757d; margin-bottom: 15px;">Showing <?php echo count($locations); ?> location updates from the last <?php echo $filterDays; ?> day(s)</p>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Date/Time</th>
                                        <th>Coordinates</th>
                                        <th>Accuracy</th>
                                        <th>Provider</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($locations as $loc): ?>
                                        <tr>
                                            <td><?php echo date('d/m/Y H:i:s', strtotime($loc['created_at'])); ?></td>
                                            <td><code><?php echo number_format($loc['lat'], 6); ?>, <?php echo number_format($loc['lon'], 6); ?></code></td>
                                            <td><?php echo $loc['accuracy'] ? htmlspecialchars($loc['accuracy']) . 'm' : 'N/A'; ?></td>
                                            <td><?php echo htmlspecialchars($loc['provider'] ?? 'N/A'); ?></td>
                                            <td>
                                                <a href="https://www.google.com/maps?q=<?php echo $loc['lat']; ?>,<?php echo $loc['lon']; ?>" 
                                                   target="_blank" class="btn btn-sm btn-primary">View 🗺️</a>
                                            </td>
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
    
    <script>
    // Dark Mode Toggle
    function toggleDarkMode() {
        document.body.classList.toggle('dark-mode');
        const isDark = document.body.classList.contains('dark-mode');
        localStorage.setItem('darkMode', isDark ? 'enabled' : 'disabled');
        document.getElementById('theme-icon').textContent = isDark ? '☀️' : '🌙';
    }
    
    // Load dark mode preference
    if (localStorage.getItem('darkMode') === 'enabled') {
        document.body.classList.add('dark-mode');
        document.getElementById('theme-icon').textContent = '☀️';
    }
    </script>
</body>
</html>
