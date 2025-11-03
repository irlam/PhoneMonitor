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

// Helper: compute current speed (mph) from last two locations within recent window
function computeCurrentSpeedMph($deviceInternalId) {
    try {
        $rows = db()->fetchAll(
            "SELECT lat, lon, created_at FROM device_locations WHERE device_id = ? ORDER BY created_at DESC LIMIT 2",
            [$deviceInternalId]
        );
        if (!$rows || count($rows) < 2) return null;
        $a = $rows[0];
        $b = $rows[1];
        if (!$a['created_at'] || !$b['created_at']) return null;
        $t1 = strtotime($a['created_at']);
        $t2 = strtotime($b['created_at']);
        if (!$t1 || !$t2 || $t1 === $t2) return null;
        // Only consider if updates are within the last 15 minutes to avoid stale speeds
        if (abs(time() - $t1) > 3600) return null; // last point older than 1 hour
        if (abs($t1 - $t2) > 900) return null; // interval greater than 15 minutes
        $lat1 = deg2rad((float)$a['lat']);
        $lon1 = deg2rad((float)$a['lon']);
        $lat2 = deg2rad((float)$b['lat']);
        $lon2 = deg2rad((float)$b['lon']);
        $dlat = $lat2 - $lat1;
        $dlon = $lon2 - $lon1;
        $hav = sin($dlat/2)**2 + cos($lat1)*cos($lat2)*sin($dlon/2)**2;
        $earthRadiusKm = 6371.0;
        $distanceKm = 2 * $earthRadiusKm * asin(min(1, sqrt($hav)));
        $hours = abs($t1 - $t2) / 3600.0;
        if ($hours <= 0) return null;
        $speedKmh = $distanceKm / $hours;
        $speedMph = $speedKmh * 0.621371;
        return round($speedMph, 1);
    } catch (Exception $e) {
        return null;
    }
}

// Attach computed speed to each device (best-effort; N is typically small)
foreach ($devices as $idx => $dev) {
    $devices[$idx]['speed_mph'] = computeCurrentSpeedMph((int)$dev['id']);
}

// Helper: compute average speed (mph) over recent hours using consecutive segments
function computeAvgSpeedMph($deviceInternalId, $hoursWindow = 24) {
    try {
        $rows = db()->fetchAll(
            "SELECT lat, lon, created_at FROM device_locations WHERE device_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL ? HOUR) ORDER BY created_at DESC LIMIT 500",
            [$deviceInternalId, (int)$hoursWindow]
        );
        if (!$rows || count($rows) < 2) return [null, 0];
        $sum = 0.0; $cnt = 0;
        for ($i = 0; $i < count($rows) - 1; $i++) {
            $a = $rows[$i];
            $b = $rows[$i+1];
            if (!$a['created_at'] || !$b['created_at']) continue;
            $t1 = strtotime($a['created_at']);
            $t2 = strtotime($b['created_at']);
            if (!$t1 || !$t2 || $t1 === $t2) continue;
            $delta = abs($t1 - $t2);
            if ($delta > 900) continue; // only segments <= 15 minutes apart
            $lat1 = deg2rad((float)$a['lat']);
            $lon1 = deg2rad((float)$a['lon']);
            $lat2 = deg2rad((float)$b['lat']);
            $lon2 = deg2rad((float)$b['lon']);
            $dlat = $lat2 - $lat1;
            $dlon = $lon2 - $lon1;
            $hav = sin($dlat/2)**2 + cos($lat1)*cos($lat2)*sin($dlon/2)**2;
            $earthRadiusKm = 6371.0;
            $distanceKm = 2 * $earthRadiusKm * asin(min(1, sqrt($hav)));
            $hours = $delta / 3600.0;
            if ($hours <= 0) continue;
            $speedKmh = $distanceKm / $hours;
            $sum += ($speedKmh * 0.621371);
            $cnt++;
        }
        if ($cnt === 0) return [null, 0];
        return [round($sum / $cnt, 1), $cnt];
    } catch (Exception $e) {
        return [null, 0];
    }
}

// Compute and attach average speeds for UI chips
$avgThreshold = defined('SPEED_AVG_THRESHOLD_MPH') ? (float)SPEED_AVG_THRESHOLD_MPH : 75;
foreach ($devices as $idx => $dev) {
    [$avgMph, $segCnt] = computeAvgSpeedMph((int)$dev['id'], 24);
    $devices[$idx]['avg_speed_mph'] = $avgMph;
    $devices[$idx]['avg_speed_segments'] = $segCnt;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - PhoneMonitor</title>
    <link rel="icon" type="image/svg+xml" href="/assets/icons/favicon.svg">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/icons/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/assets/icons/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/icons/apple-touch-icon.png">
    <link rel="mask-icon" href="/assets/icons/favicon.svg" color="#22bb66">
    <link rel="manifest" href="/assets/icons/site.webmanifest">
    <link rel="stylesheet" href="assets/css/site.css?v=<?php echo urlencode(ASSET_VERSION); ?>">
</head>
<body>
    <div class="container">
        <header class="header">
            <h1>PhoneMonitor Dashboard</h1>
            <div class="header-actions">
                <button class="theme-toggle" onclick="toggleDarkMode()" title="Toggle dark mode">
                    <span id="theme-icon">🌙</span>
                </button>
                <span class="user-info">Logged in as <?php echo htmlspecialchars(Auth::name()); ?></span>
                <a href="/logout.php" class="btn btn-secondary">Logout</a>
            </div>
        </header>
        
        <nav class="nav">
            <a href="/dashboard.php" class="active">Dashboard</a>
            <a href="/devices.php">All Devices</a>
            <a href="/geofences.php">Geofences</a>
            <a href="/analytics.php">Analytics</a>
            <a href="/alert_rules.php">Alert Rules</a>
            <a href="/setup.php">Setup & Help</a>
        </nav>
        
        <main class="main-content">
                        <div class="page-header">
                <h2>📊 Dashboard</h2>
                <div class="header-actions">
                    <a href="/export.php?type=devices_csv" class="btn btn-secondary" style="margin-right: 10px;">
                        📊 Export Devices CSV
                    </a>
                    <a href="/analytics.php" class="btn btn-primary">
                        📈 View Analytics
                    </a>
                </div>
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
            
            <!-- Device Filters -->
            <div class="filter-controls">
                <button class="filter-btn active" onclick="filterDevices('all')">All Devices</button>
                <button class="filter-btn" onclick="filterDevices('online')">🟢 Online</button>
                <button class="filter-btn" onclick="filterDevices('offline')">⚫ Offline</button>
                <button class="filter-btn" onclick="filterDevices('revoked')">🚫 Revoked</button>
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
                        <div class="device-card <?php echo $device['revoked'] ? 'revoked' : ''; ?>" 
                             data-status="<?php echo $device['revoked'] ? 'revoked' : ($device['is_online'] ? 'online' : 'offline'); ?>">
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
                                <?php if (!empty($device['speed_mph'])): ?>
                                    <p><strong>Speed:</strong> <?php echo number_format($device['speed_mph'], 1); ?> mph 🚗</p>
                                <?php endif; ?>
                                <?php 
                                    $avgClass = 'badge-secondary';
                                    $avgTitle = 'Average of recent consecutive segment speeds (<= 15 min apart) over last 24h.';
                                    if (!is_null($device['avg_speed_mph'])) {
                                        $avgClass = ($device['avg_speed_mph'] > $avgThreshold) ? 'badge-danger' : 'badge-info';
                                        $avgTitle .= ' Segments: ' . intval($device['avg_speed_segments']) . '. Threshold: ' . number_format($avgThreshold, 0) . ' mph';
                                    }
                                ?>
                                <div class="mt-20">
                                    <span class="badge <?php echo $avgClass; ?>" title="<?php echo htmlspecialchars($avgTitle); ?>">
                                        Avg 24h: <?php echo !is_null($device['avg_speed_mph']) ? number_format($device['avg_speed_mph'], 1) . ' mph' : '-'; ?>
                                    </span>
                                </div>
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
    
    // Device Filtering
    function filterDevices(status) {
        const cards = document.querySelectorAll('.device-card');
        const buttons = document.querySelectorAll('.filter-btn');
        
        // Update button states
        buttons.forEach(btn => btn.classList.remove('active'));
        event.target.classList.add('active');
        
        // Filter cards
        cards.forEach(card => {
            const cardStatus = card.getAttribute('data-status');
            
            if (status === 'all') {
                card.classList.remove('hidden');
            } else if (status === cardStatus) {
                card.classList.remove('hidden');
            } else {
                card.classList.add('hidden');
            }
        });
    }
    
    // Auto-refresh dashboard every 30 seconds
    let refreshInterval;
    
    function startAutoRefresh() {
        refreshInterval = setInterval(() => {
            // Refresh stats silently
            fetch(window.location.href)
                .then(response => response.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    
                    // Update stats cards
                    const statsGrid = doc.querySelector('.stats-grid');
                    if (statsGrid) {
                        document.querySelector('.stats-grid').innerHTML = statsGrid.innerHTML;
                    }
                    
                    // Update device cards
                    const deviceGrid = doc.querySelector('.device-grid');
                    if (deviceGrid) {
                        const currentFilter = document.querySelector('.filter-btn.active');
                        const currentStatus = currentFilter ? currentFilter.textContent.toLowerCase() : 'all';
                        
                        document.querySelector('.device-grid').innerHTML = deviceGrid.innerHTML;
                        
                        // Reapply filter if not 'all'
                        if (!currentStatus.includes('all')) {
                            setTimeout(() => {
                                if (currentStatus.includes('online')) filterDevices('online');
                                else if (currentStatus.includes('offline')) filterDevices('offline');
                                else if (currentStatus.includes('revoked')) filterDevices('revoked');
                            }, 100);
                        }
                    }
                    
                    console.log('Dashboard refreshed at ' + new Date().toLocaleTimeString());
                })
                .catch(err => console.error('Auto-refresh failed:', err));
        }, 30000); // 30 seconds
    }
    
    // Start auto-refresh on page load
    startAutoRefresh();
    
    // Pause refresh when page is hidden, resume when visible
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            clearInterval(refreshInterval);
            console.log('Auto-refresh paused');
        } else {
            startAutoRefresh();
            console.log('Auto-refresh resumed');
        }
    });
    </script>
</body>
</html>
