<?php
/**
 * Dashboard - Device List
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';

Auth::require();

// Fetch all devices
$devices = db()->fetchAll(
    "SELECT 
        d.*,
        (TIMESTAMPDIFF(MINUTE, d.last_seen, NOW()) < 60) as is_online,
        (SELECT COUNT(*) FROM device_locations WHERE device_id = d.id) as location_count
    FROM devices d 
    ORDER BY d.last_seen DESC, d.registered_at DESC"
);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - PhoneMonitor</title>
    <link rel="stylesheet" href="/assets/css/site.css">
</head>
<body>
    <div class="container">
        <header class="header">
            <h1>PhoneMonitor Dashboard</h1>
            <div class="header-actions">
                <span class="user-info">Logged in as <?php echo htmlspecialchars(Auth::name()); ?></span>
                <a href="/logout.php" class="btn btn-secondary">Logout</a>
            </div>
        </header>
        
        <nav class="nav">
            <a href="/dashboard.php" class="active">Devices</a>
            <a href="/devices.php">All Devices</a>
        </nav>
        
        <main class="main-content">
            <div class="page-header">
                <h2>Registered Devices</h2>
                <p class="subtitle">Consent-based family device monitoring</p>
            </div>
            
            <?php if (empty($devices)): ?>
                <div class="alert alert-info">
                    <strong>No devices registered yet</strong>
                    <p>Install the Android app on a device and register it to see it here.</p>
                </div>
            <?php else: ?>
                <div class="device-grid">
                    <?php foreach ($devices as $device): ?>
                        <div class="device-card <?php echo $device['revoked'] ? 'revoked' : ''; ?>">
                            <div class="device-header">
                                <h3><?php echo htmlspecialchars($device['display_name']); ?></h3>
                                <?php if ($device['revoked']): ?>
                                    <span class="badge badge-danger">Revoked</span>
                                <?php elseif ($device['is_online']): ?>
                                    <span class="badge badge-success">Online</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">Offline</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="device-info">
                                <p><strong>Owner:</strong> <?php echo htmlspecialchars($device['owner_name']); ?></p>
                                <p><strong>UUID:</strong> <code><?php echo htmlspecialchars($device['device_uuid']); ?></code></p>
                                <p><strong>Registered:</strong> <?php echo date('Y-m-d H:i', strtotime($device['registered_at'])); ?></p>
                                <p><strong>Last Seen:</strong> 
                                    <?php 
                                    if ($device['last_seen']) {
                                        $diff = time() - strtotime($device['last_seen']);
                                        if ($diff < 60) {
                                            echo 'Just now';
                                        } elseif ($diff < 3600) {
                                            echo floor($diff / 60) . ' minutes ago';
                                        } elseif ($diff < 86400) {
                                            echo floor($diff / 3600) . ' hours ago';
                                        } else {
                                            echo date('Y-m-d H:i', strtotime($device['last_seen']));
                                        }
                                    } else {
                                        echo 'Never';
                                    }
                                    ?>
                                </p>
                                
                                <?php if ($device['last_payload']): 
                                    $payload = json_decode($device['last_payload'], true);
                                ?>
                                    <div class="device-status">
                                        <?php if (isset($payload['battery'])): ?>
                                            <p><strong>Battery:</strong> <?php echo htmlspecialchars($payload['battery']); ?>%</p>
                                        <?php endif; ?>
                                        <?php if (isset($payload['free_storage'])): ?>
                                            <p><strong>Storage:</strong> <?php echo htmlspecialchars($payload['free_storage']); ?> GB free</p>
                                        <?php endif; ?>
                                        <?php if (isset($payload['note'])): ?>
                                            <p><strong>Note:</strong> <?php echo htmlspecialchars($payload['note']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($device['consent_given']): ?>
                                    <p class="consent-badge"><span class="badge badge-success">✓ Consent Given</span></p>
                                <?php endif; ?>
                                
                                <?php if ($device['location_count'] > 0): ?>
                                    <p><strong>Locations:</strong> <?php echo $device['location_count']; ?> recorded</p>
                                <?php endif; ?>
                            </div>
                            
                            <div class="device-actions">
                                <a href="/device_view.php?id=<?php echo $device['id']; ?>" class="btn btn-primary">View Details</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
        
        <footer class="footer">
            <p>PhoneMonitor - Consent-based Family Device Helper</p>
            <p><small>No stealth · No remote control · Always visible notification</small></p>
        </footer>
    </div>
</body>
</html>
