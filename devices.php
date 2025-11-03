<?php
/**
 * All Devices Page
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/db.php';

Auth::require();

$message = '';

// Handle device revocation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'revoke') {
    if (!CSRF::validateToken()) {
        $message = ['type' => 'error', 'text' => 'Invalid request'];
    } else {
        $deviceId = intval($_POST['device_id'] ?? 0);
        
        try {
            db()->query(
                "UPDATE devices SET revoked = 1 WHERE id = ?",
                [$deviceId]
            );
            
            Auth::logAction('device_revoked', $deviceId);
            $message = ['type' => 'success', 'text' => 'Device revoked successfully'];
        } catch (Exception $e) {
            $message = ['type' => 'error', 'text' => 'Failed to revoke device'];
        }
    }
}

// Fetch all devices
$devices = db()->fetchAll(
    "SELECT 
        d.*,
        (TIMESTAMPDIFF(MINUTE, d.last_seen, NOW()) < 60) as is_online
    FROM devices d 
    ORDER BY d.revoked ASC, d.last_seen DESC, d.registered_at DESC"
);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Devices - PhoneMonitor</title>
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
            <a href="/devices.php" class="active">All Devices</a>
            <a href="/geofences.php">Geofences</a>
            <a href="/analytics.php">Analytics</a>
            <a href="/alert_rules.php">Alert Rules</a>
            <a href="/setup.php">Setup & Help</a>
        </nav>
        
        <main class="main-content">
            <div class="page-header">
                <h2>All Registered Devices</h2>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message['type']; ?>">
                    <?php echo htmlspecialchars($message['text']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (empty($devices)): ?>
                <div class="alert alert-info">
                    <p>No devices registered yet.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Status</th>
                                <th>Device Name</th>
                                <th>Owner</th>
                                <th>UUID</th>
                                <th>Registered</th>
                                <th>Last Seen</th>
                                <th>Consent</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($devices as $device): ?>
                                <tr class="<?php echo $device['revoked'] ? 'revoked-row' : ''; ?>">
                                    <td>
                                        <?php if ($device['revoked']): ?>
                                            <span class="badge badge-danger">Revoked</span>
                                        <?php elseif ($device['is_online']): ?>
                                            <span class="badge badge-success">Online</span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">Offline</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($device['display_name']); ?></td>
                                    <td><?php echo htmlspecialchars($device['owner_name']); ?></td>
                                    <td><code><?php echo htmlspecialchars(substr($device['device_uuid'], 0, 8)); ?>...</code></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($device['registered_at'])); ?></td>
                                    <td>
                                        <?php 
                                        if ($device['last_seen']) {
                                            echo date('d/m/Y H:i', strtotime($device['last_seen']));
                                        } else {
                                            echo 'Never';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php if ($device['consent_given']): ?>
                                            <span class="badge badge-success">✓</span>
                                        <?php else: ?>
                                            <span class="badge badge-warning">✗</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="/device_view.php?id=<?php echo $device['id']; ?>" class="btn btn-sm btn-primary">View</a>
                                        <?php if (!$device['revoked']): ?>
                                            <form method="POST" style="display: inline;" 
                                                  onsubmit="return confirm('Are you sure you want to revoke this device?');">
                                                <?php CSRF::field(); ?>
                                                <input type="hidden" name="action" value="revoke">
                                                <input type="hidden" name="device_id" value="<?php echo $device['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger">Revoke</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
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
