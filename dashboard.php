<?php
/**
 * Dashboard - Device List
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';

Auth::require();

// Fetch device stats
$stats = db()->fetchOne(
    "SELECT
        COUNT(*) as total_devices,
        SUM(CASE WHEN TIMESTAMPDIFF(MINUTE, last_seen, NOW()) < 60 THEN 1 ELSE 0 END) as online_devices,
        SUM(CASE WHEN consent_given = 1 THEN 1 ELSE 0 END) as consented_devices,
        SUM(CASE WHEN revoked = 1 THEN 1 ELSE 0 END) as revoked_devices
    FROM devices"
);

// Fetch all devices
$devices = db()->fetchAll(
    "SELECT d.*,
        TIMESTAMPDIFF(MINUTE, d.last_seen, NOW()) < 60 as is_online,
        (SELECT COUNT(*) FROM device_locations WHERE device_id = d.id) as location_count
    FROM devices d
    ORDER BY d.last_seen DESC"
);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - PhoneMonitor</title>
    <link rel="stylesheet" href="assets/css/site.css?v=2">
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
                <h2>Device Overview</h2>
                <p class="subtitle">Monitor your family's devices with consent and transparency</p>
            </div>
            
            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card-enhanced card-primary">
                    <div class="stat-card-icon-wrapper">
                        <div class="stat-icon-box">📱</div>
                        <div class="stat-category-badge">DEVICES</div>
                    </div>
                    <div class="stat-label">Total Devices</div>
                    <div class="stat-number"><?php echo $stats['total_devices']; ?></div>
                    <div class="stat-description">All registered devices across your monitoring network.</div>
                    <a href="/devices.php" class="stat-action-btn">Manage Devices</a>
                </div>
                
                <div class="stat-card-enhanced card-success">
                    <div class="stat-card-icon-wrapper">
                        <div class="stat-icon-box">🟢</div>
                        <div class="stat-category-badge">STATUS</div>
                    </div>
                    <div class="stat-label">Online Now</div>
                    <div class="stat-number"><?php echo $stats['online_devices']; ?></div>
                    <div class="stat-description">Active devices seen within the last 60 minutes.</div>
                    <a href="/devices.php" class="stat-action-btn">View Active</a>
                </div>
                
                <div class="stat-card-enhanced card-info">
                    <div class="stat-card-icon-wrapper">
                        <div class="stat-icon-box">✅</div>
                        <div class="stat-category-badge">CONSENT</div>
                    </div>
                    <div class="stat-label">With Consent</div>
                    <div class="stat-number"><?php echo $stats['consented_devices']; ?></div>
                    <div class="stat-description">Devices with explicit monitoring consent given.</div>
                    <a href="/devices.php" class="stat-action-btn">Review Consent</a>
                </div>
                
                <div class="stat-card-enhanced card-danger">
                    <div class="stat-card-icon-wrapper">
                        <div class="stat-icon-box">🚫</div>
                        <div class="stat-category-badge">REVOKED</div>
                    </div>
                    <div class="stat-label">Revoked Access</div>
                    <div class="stat-number"><?php echo $stats['revoked_devices']; ?></div>
                    <div class="stat-description">Devices with revoked monitoring permissions.</div>
                    <a href="/devices.php" class="stat-action-btn">View Revoked</a>
                </div>
            </div>
            
            <div class="section-header">
                <h3>Registered Devices</h3>
                <p>Click on any device to view detailed information and location history</p>
            </div>
            
            <?php if (empty($devices)): ?>
                <div class="empty-state">
                    <div class="empty-icon">📱</div>
                    <h3>No devices registered yet</h3>
                    <p>Install the Android app on a device and register it to see it here.</p>
                    <p class="empty-subtitle">All monitoring requires explicit consent from device owners.</p>
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
                                <p><strong>Registered:</strong> <?php echo date('d/m/Y', strtotime($device['registered_at'])); ?></p>
                                <p><strong>Last Seen:</strong> 
                                    <?php 
                                    if ($device['last_seen']) {
                                        $diff = time() - strtotime($device['last_seen']);
                                        if ($diff < 60) {
                                            echo '<span style="color: var(--success-color); font-weight: 600;">Just now</span>';
                                        } elseif ($diff < 3600) {
                                            echo floor($diff / 60) . ' minutes ago';
                                        } elseif ($diff < 86400) {
                                            echo floor($diff / 3600) . ' hours ago';
                                        } else {
                                            echo date('d/m/Y H:i', strtotime($device['last_seen']));
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
                                            <p><strong>Battery:</strong> <?php echo htmlspecialchars($payload['battery']); ?>% 
                                                <?php if ($payload['battery'] > 80): ?>🔋<?php elseif ($payload['battery'] > 20): ?>🪫<?php else: ?>🔌<?php endif; ?>
                                            </p>
                                        <?php endif; ?>
                                        <?php if (isset($payload['free_storage'])): ?>
                                            <p><strong>Storage:</strong> <?php echo htmlspecialchars($payload['free_storage']); ?> GB free 💾</p>
                                        <?php endif; ?>
                                        <?php if (isset($payload['note'])): ?>
                                            <p><strong>Note:</strong> <?php echo htmlspecialchars($payload['note']); ?> 📝</p>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($device['consent_given']): ?>
                                    <p class="consent-badge"><span class="badge badge-success">✓ Consent Given</span></p>
                                <?php endif; ?>
                                
                                <?php if ($device['location_count'] > 0): ?>
                                    <p><strong>Locations:</strong> <?php echo $device['location_count']; ?> recorded 📍</p>
                                <?php endif; ?>
                            </div>
                            
                            <div class="device-actions">
                                <a href="/device_view.php?id=<?php echo $device['id']; ?>" class="btn btn-primary">View Details 👁️</a>
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
