<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/csrf.php';

requireAuth();

$deviceId = (int)($_GET['id'] ?? 0);

if (!$deviceId) {
    header('Location: /dashboard.php');
    exit;
}

// Handle device revocation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    requireCsrf();
    
    if ($_POST['action'] === 'revoke') {
        executeQuery(
            "UPDATE devices SET revoked = 1 WHERE id = ?",
            [$deviceId]
        );
        
        // Log the action
        $user = getCurrentUser();
        executeQuery(
            "INSERT INTO audit_log (device_id, user_id, action, meta) VALUES (?, ?, 'revoke', ?)",
            [$deviceId, $user['id'], json_encode(['reason' => 'manual_revoke'])]
        );
        
        header('Location: /device_view.php?id=' . $deviceId . '&msg=revoked');
        exit;
    }
}

// Get device details
$device = fetchOne("SELECT * FROM devices WHERE id = ?", [$deviceId]);

if (!$device) {
    header('Location: /dashboard.php');
    exit;
}

// Parse last payload
$payload = $device['last_payload'] ? json_decode($device['last_payload'], true) : [];

// Get recent locations (last 10)
$locations = fetchAll(
    "SELECT * FROM device_locations WHERE device_id = ? ORDER BY created_at DESC LIMIT 10",
    [$deviceId]
);

// Get latest location for map
$latestLocation = !empty($locations) ? $locations[0] : null;

$hasLocation = $latestLocation && isset($latestLocation['lat']) && isset($latestLocation['lon']);
$hasMapKey = !empty(GOOGLE_MAPS_API_KEY);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($device['display_name'], ENT_QUOTES, 'UTF-8'); ?> - PhoneMonitor</title>
    <link rel="stylesheet" href="/assets/css/site.css">
    <?php if ($hasMapKey && $hasLocation): ?>
    <script src="https://maps.googleapis.com/maps/api/js?key=<?php echo htmlspecialchars(GOOGLE_MAPS_API_KEY, ENT_QUOTES, 'UTF-8'); ?>"></script>
    <?php endif; ?>
</head>
<body>
    <?php include __DIR__ . '/includes/header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1><?php echo htmlspecialchars($device['display_name'], ENT_QUOTES, 'UTF-8'); ?></h1>
            <div class="actions">
                <a href="/dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
            </div>
        </div>
        
        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'revoked'): ?>
            <div class="alert alert-success">Device has been revoked successfully.</div>
        <?php endif; ?>
        
        <div class="device-grid">
            <div class="card">
                <div class="card-header">
                    <h2>Device Information</h2>
                </div>
                <div class="card-body">
                    <dl class="info-list">
                        <dt>Owner:</dt>
                        <dd><?php echo htmlspecialchars($device['owner_name'], ENT_QUOTES, 'UTF-8'); ?></dd>
                        
                        <dt>Device UUID:</dt>
                        <dd><code><?php echo htmlspecialchars($device['device_uuid'], ENT_QUOTES, 'UTF-8'); ?></code></dd>
                        
                        <dt>Registered:</dt>
                        <dd><?php echo htmlspecialchars(date('Y-m-d H:i:s', strtotime($device['registered_at'])), ENT_QUOTES, 'UTF-8'); ?></dd>
                        
                        <dt>Last Seen:</dt>
                        <dd>
                            <?php 
                            if ($device['last_seen']) {
                                echo htmlspecialchars(date('Y-m-d H:i:s', strtotime($device['last_seen'])), ENT_QUOTES, 'UTF-8');
                            } else {
                                echo '<span class="text-muted">Never</span>';
                            }
                            ?>
                        </dd>
                        
                        <dt>Consent Given:</dt>
                        <dd>
                            <?php if ($device['consent_given']): ?>
                                <span class="badge badge-success">✓ Yes</span>
                            <?php else: ?>
                                <span class="badge badge-warning">! No</span>
                            <?php endif; ?>
                        </dd>
                        
                        <dt>Status:</dt>
                        <dd>
                            <?php if ($device['revoked']): ?>
                                <span class="badge badge-revoked">Revoked</span>
                            <?php else: ?>
                                <span class="badge badge-success">Active</span>
                            <?php endif; ?>
                        </dd>
                        
                        <?php if (!empty($payload['battery'])): ?>
                        <dt>Battery:</dt>
                        <dd><?php echo (int)$payload['battery']; ?>%</dd>
                        <?php endif; ?>
                        
                        <?php if (!empty($payload['free_storage'])): ?>
                        <dt>Free Storage:</dt>
                        <dd><?php echo htmlspecialchars($payload['free_storage'], ENT_QUOTES, 'UTF-8'); ?></dd>
                        <?php endif; ?>
                        
                        <?php if (!empty($payload['note'])): ?>
                        <dt>Note:</dt>
                        <dd><?php echo htmlspecialchars($payload['note'], ENT_QUOTES, 'UTF-8'); ?></dd>
                        <?php endif; ?>
                    </dl>
                    
                    <?php if (!$device['revoked']): ?>
                    <form method="POST" style="margin-top: 1rem;" onsubmit="return confirm('Are you sure you want to revoke this device? The device will no longer be able to send updates.');">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="action" value="revoke">
                        <button type="submit" class="btn btn-danger">Revoke Device</button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h2>Location</h2>
                </div>
                <div class="card-body">
                    <?php if (!$hasMapKey): ?>
                        <div class="alert alert-warning">
                            <strong>Map unavailable</strong><br>
                            Add GOOGLE_MAPS_API_KEY to your .env file to view locations on a map.
                        </div>
                    <?php elseif (!$hasLocation): ?>
                        <p class="text-muted">No location data available. The device may not have location sharing enabled.</p>
                    <?php else: ?>
                        <div id="map" style="height: 400px; width: 100%; border-radius: 4px;"></div>
                        <p style="margin-top: 0.5rem;">
                            <small>
                                Last updated: <?php echo htmlspecialchars(date('Y-m-d H:i:s', strtotime($latestLocation['created_at'])), ENT_QUOTES, 'UTF-8'); ?>
                                <?php if ($latestLocation['accuracy']): ?>
                                    (±<?php echo (int)$latestLocation['accuracy']; ?>m)
                                <?php endif; ?>
                            </small>
                        </p>
                        <a href="https://www.google.com/maps?q=<?php echo $latestLocation['lat']; ?>,<?php echo $latestLocation['lon']; ?>" 
                           target="_blank" class="btn btn-sm btn-primary">View on Google Maps →</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <?php if (!empty($locations)): ?>
        <div class="card">
            <div class="card-header">
                <h2>Recent Locations</h2>
            </div>
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <th>Latitude</th>
                            <th>Longitude</th>
                            <th>Accuracy</th>
                            <th>Provider</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($locations as $loc): ?>
                        <tr>
                            <td><?php echo htmlspecialchars(date('Y-m-d H:i:s', strtotime($loc['created_at'])), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($loc['lat'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($loc['lon'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo $loc['accuracy'] ? (int)$loc['accuracy'] . 'm' : '—'; ?></td>
                            <td><?php echo htmlspecialchars($loc['provider'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <?php if ($hasMapKey && $hasLocation): ?>
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
            
            new google.maps.Marker({
                position: location,
                map: map,
                title: '<?php echo htmlspecialchars($device['display_name'], ENT_QUOTES, 'UTF-8'); ?>'
            });
        }
        
        window.addEventListener('load', initMap);
    </script>
    <?php endif; ?>
    
    <script src="/assets/js/site.js"></script>
</body>
</html>
