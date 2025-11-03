<?php
/**
 * Geofences Management Page
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/GeofenceService.php';

Auth::require();

$message = '';

// Handle geofence actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CSRF::validateToken()) {
        $message = ['type' => 'error', 'text' => 'Invalid request'];
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'create') {
            $name = trim($_POST['name'] ?? '');
            $lat = floatval($_POST['latitude'] ?? 0);
            $lon = floatval($_POST['longitude'] ?? 0);
            $radius = intval($_POST['radius'] ?? 100);
            $deviceId = !empty($_POST['device_id']) ? intval($_POST['device_id']) : null;
            $alertOnEnter = isset($_POST['alert_on_enter']);
            $alertOnExit = isset($_POST['alert_on_exit']);
            
            if (empty($name) || $lat == 0 || $lon == 0) {
                $message = ['type' => 'error', 'text' => 'Please fill all required fields'];
            } else {
                try {
                    GeofenceService::create($name, $lat, $lon, $radius, $deviceId, $alertOnEnter, $alertOnExit);
                    $message = ['type' => 'success', 'text' => 'Geofence created successfully'];
                } catch (Exception $e) {
                    $message = ['type' => 'error', 'text' => 'Failed to create geofence'];
                }
            }
        } elseif ($action === 'delete') {
            $id = intval($_POST['id'] ?? 0);
            try {
                GeofenceService::delete($id);
                $message = ['type' => 'success', 'text' => 'Geofence deleted'];
            } catch (Exception $e) {
                $message = ['type' => 'error', 'text' => 'Failed to delete geofence'];
            }
        }
    }
}

// Fetch all geofences
$geofences = GeofenceService::getAll();

// Fetch all devices for dropdown
$devices = db()->fetchAll("SELECT id, display_name FROM devices ORDER BY display_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Geofences - PhoneMonitor</title>
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
            <a href="/devices.php">All Devices</a>
            <a href="/geofences.php" class="active">Geofences</a>
            <a href="/analytics.php">Analytics</a>
            <a href="/alert_rules.php">Alert Rules</a>
            <a href="/setup.php">Setup & Help</a>
        </nav>
        
        <main class="main-content">
            <div class="page-header">
                <h2>Geofence Management</h2>
                <p class="subtitle">Create location-based alerts for home, school, work, and more</p>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message['type']; ?>">
                    <?php echo htmlspecialchars($message['text']); ?>
                </div>
            <?php endif; ?>
            
            <!-- Create Geofence Form -->
            <div class="card card-primary" style="margin-bottom: 30px;">
                <div class="card-header">
                    <h3 class="card-title">📍 Create New Geofence</h3>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <?php CSRF::field(); ?>
                        <input type="hidden" name="action" value="create">
                        
                        <div class="form-group">
                            <label for="name">Geofence Name *</label>
                            <input type="text" id="name" name="name" required placeholder="e.g., Home, School, Work">
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <div class="form-group">
                                <label for="latitude">Latitude *</label>
                                <input type="number" id="latitude" name="latitude" step="0.000001" required placeholder="e.g., 51.5074">
                            </div>
                            
                            <div class="form-group">
                                <label for="longitude">Longitude *</label>
                                <input type="number" id="longitude" name="longitude" step="0.000001" required placeholder="e.g., -0.1278">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="radius">Radius (meters)</label>
                            <input type="number" id="radius" name="radius" value="100" min="10" max="10000">
                            <small>Default: 100 meters. Range: 10-10000 meters</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="device_id">Apply to Device (optional)</label>
                            <select id="device_id" name="device_id">
                                <option value="">All Devices</option>
                                <?php foreach ($devices as $dev): ?>
                                    <option value="<?php echo $dev['id']; ?>"><?php echo htmlspecialchars($dev['display_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small>Leave as "All Devices" to apply this geofence to all current and future devices</small>
                        </div>
                        
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="alert_on_enter" checked> Alert when entering this zone
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="alert_on_exit"> Alert when leaving this zone
                            </label>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Create Geofence</button>
                    </form>
                    
                    <p style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #dee2e6; color: #6c757d; font-size: 14px;">
                        <strong>💡 Tip:</strong> You can find coordinates by searching for a location on Google Maps, right-clicking, and selecting "What's here?"
                    </p>
                </div>
            </div>
            
            <!-- Existing Geofences -->
            <div class="section-header">
                <h3>Active Geofences</h3>
                <p>Manage your location-based alert zones</p>
            </div>
            
            <?php if (empty($geofences)): ?>
                <div class="empty-state">
                    <div class="empty-icon">🗺️</div>
                    <h3>No geofences created yet</h3>
                    <p>Create your first geofence using the form above</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Coordinates</th>
                                <th>Radius</th>
                                <th>Device</th>
                                <th>Alerts</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($geofences as $geo): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($geo['name']); ?></strong></td>
                                    <td>
                                        <code><?php echo number_format($geo['latitude'], 6); ?>, <?php echo number_format($geo['longitude'], 6); ?></code>
                                    </td>
                                    <td><?php echo $geo['radius_meters']; ?>m</td>
                                    <td>
                                        <?php if ($geo['device_id']): ?>
                                            <span class="badge badge-secondary"><?php echo htmlspecialchars($geo['device_name']); ?></span>
                                        <?php else: ?>
                                            <span class="badge badge-success">All Devices</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($geo['alert_on_enter']): ?>
                                            <span class="badge badge-success">Enter</span>
                                        <?php endif; ?>
                                        <?php if ($geo['alert_on_exit']): ?>
                                            <span class="badge badge-warning">Exit</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($geo['created_at'])); ?></td>
                                    <td>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this geofence?');">
                                            <?php CSRF::field(); ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $geo['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
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
