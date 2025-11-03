<?php
/**
 * Setup & Configuration Page
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/db.php';

Auth::require();

$message = '';

// Handle configuration updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CSRF::validateToken()) {
        $message = ['type' => 'error', 'text' => 'Invalid request'];
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'update_env') {
            $envFile = __DIR__ . '/.env';
            $envContent = [];
            
            // Read current .env
            if (file_exists($envFile)) {
                $envContent = parse_ini_file($envFile);
            }
            
            // Update values
            $updates = [
                'SITE_URL' => trim($_POST['site_url'] ?? ''),
                'GOOGLE_MAPS_API_KEY' => trim($_POST['google_maps_key'] ?? ''),
                'ADMIN_EMAIL' => trim($_POST['admin_email'] ?? ''),
                'ASSET_VERSION' => trim($_POST['asset_version'] ?? '2'),
            ];
            
            foreach ($updates as $key => $value) {
                if (!empty($value)) {
                    $envContent[$key] = $value;
                }
            }
            
            // Write back to .env
            $newEnvContent = "# PhoneMonitor Configuration\n";
            $newEnvContent .= "# Updated: " . date('Y-m-d H:i:s') . "\n\n";
            
            foreach ($envContent as $key => $value) {
                $newEnvContent .= "$key=$value\n";
            }
            
            if (file_put_contents($envFile, $newEnvContent)) {
                $message = ['type' => 'success', 'text' => 'Configuration updated! Please reload the page to see changes.'];
            } else {
                $message = ['type' => 'error', 'text' => 'Failed to write .env file. Check file permissions.'];
            }
        }
    }
}

// Check system status
$status = [
    'database' => false,
    'env_file' => file_exists(__DIR__ . '/.env'),
    'env_writable' => is_writable(__DIR__ . '/.env'),
    'google_maps' => !empty(GOOGLE_MAPS_API_KEY),
    'admin_email' => !empty(getenv('ADMIN_EMAIL')),
    'geofences_table' => false,
    'notifications_table' => false,
];

try {
    db()->fetchOne("SELECT 1");
    $status['database'] = true;
    
    // Check if geofences table exists
    $tables = db()->fetchAll("SHOW TABLES LIKE 'geofences'");
    $status['geofences_table'] = !empty($tables);
    
    $tables = db()->fetchAll("SHOW TABLES LIKE 'email_notifications'");
    $status['notifications_table'] = !empty($tables);
} catch (Exception $e) {
    $status['database'] = false;
}

$setupComplete = $status['database'] && $status['geofences_table'] && $status['notifications_table'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup & Configuration - PhoneMonitor</title>
    <link rel="stylesheet" href="assets/css/site.css?v=<?php echo urlencode(ASSET_VERSION); ?>">
    <style>
        .setup-step {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 20px;
            border-left: 4px solid #6c757d;
        }
        
        .setup-step.complete {
            border-left-color: #22bb66;
            background: linear-gradient(135deg, rgba(34, 187, 102, 0.05), rgba(26, 153, 80, 0.05));
        }
        
        .setup-step.incomplete {
            border-left-color: #ffc107;
            background: linear-gradient(135deg, rgba(255, 193, 7, 0.05), rgba(224, 168, 0, 0.05));
        }
        
        .setup-step h3 {
            margin-top: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-ok { background: #22bb66; color: white; }
        .status-warning { background: #ffc107; color: #333; }
        .status-error { background: #dc3545; color: white; }
        
        .code-block {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 15px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            overflow-x: auto;
            margin: 10px 0;
        }
        
        body.dark-mode .setup-step {
            background: rgba(40, 40, 55, 0.8);
        }
        
        body.dark-mode .code-block {
            background: rgba(30, 30, 45, 0.9);
            border-color: rgba(255, 255, 255, 0.1);
            color: #e8e8e8;
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="header">
            <h1>PhoneMonitor Setup</h1>
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
            <a href="/setup.php" class="active">Setup & Help</a>
        </nav>
        
        <main class="main-content">
            <div class="page-header">
                <h2>🚀 Setup & Configuration</h2>
                <p class="subtitle">Get PhoneMonitor up and running in just a few steps</p>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message['type']; ?>">
                    <?php echo htmlspecialchars($message['text']); ?>
                </div>
            <?php endif; ?>
            
            <!-- System Status Overview -->
            <div class="card card-primary" style="margin-bottom: 30px;">
                <div class="card-header">
                    <h3 class="card-title">📊 System Status</h3>
                </div>
                <div class="card-body">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                        <div>
                            <strong>Database:</strong> 
                            <span class="status-badge <?php echo $status['database'] ? 'status-ok' : 'status-error'; ?>">
                                <?php echo $status['database'] ? '✓ Connected' : '✗ Not Connected'; ?>
                            </span>
                        </div>
                        <div>
                            <strong>Configuration File:</strong> 
                            <span class="status-badge <?php echo $status['env_file'] ? 'status-ok' : 'status-warning'; ?>">
                                <?php echo $status['env_file'] ? '✓ Exists' : '⚠ Missing'; ?>
                            </span>
                        </div>
                        <div>
                            <strong>Google Maps:</strong> 
                            <span class="status-badge <?php echo $status['google_maps'] ? 'status-ok' : 'status-warning'; ?>">
                                <?php echo $status['google_maps'] ? '✓ Configured' : '⚠ Not Set'; ?>
                            </span>
                        </div>
                        <div>
                            <strong>Email Alerts:</strong> 
                            <span class="status-badge <?php echo $status['admin_email'] ? 'status-ok' : 'status-warning'; ?>">
                                <?php echo $status['admin_email'] ? '✓ Configured' : '⚠ Not Set'; ?>
                            </span>
                        </div>
                        <div>
                            <strong>Geofences:</strong> 
                            <span class="status-badge <?php echo $status['geofences_table'] ? 'status-ok' : 'status-warning'; ?>">
                                <?php echo $status['geofences_table'] ? '✓ Ready' : '⚠ Not Installed'; ?>
                            </span>
                        </div>
                        <div>
                            <strong>Notifications:</strong> 
                            <span class="status-badge <?php echo $status['notifications_table'] ? 'status-ok' : 'status-warning'; ?>">
                                <?php echo $status['notifications_table'] ? '✓ Ready' : '⚠ Not Installed'; ?>
                            </span>
                        </div>
                    </div>
                    
                    <?php if ($setupComplete): ?>
                        <div style="margin-top: 20px; padding: 15px; background: rgba(34, 187, 102, 0.1); border-radius: 8px; border-left: 4px solid #22bb66;">
                            <strong>🎉 Setup Complete!</strong> All core features are ready to use.
                        </div>
                    <?php else: ?>
                        <div style="margin-top: 20px; padding: 15px; background: rgba(255, 193, 7, 0.1); border-radius: 8px; border-left: 4px solid #ffc107;">
                            <strong>⚠ Setup Incomplete:</strong> Follow the steps below to complete setup.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Setup Steps -->
            <div class="section-header">
                <h3>Setup Steps</h3>
                <p>Follow these steps in order to configure PhoneMonitor</p>
            </div>
            
            <!-- Step 1: Database -->
            <div class="setup-step <?php echo $status['database'] ? 'complete' : 'incomplete'; ?>">
                <h3>
                    <span><?php echo $status['database'] ? '✅' : '1️⃣'; ?></span>
                    Database Connection
                </h3>
                
                <?php if ($status['database']): ?>
                    <p style="color: #22bb66;">✓ Database is connected and working properly!</p>
                <?php else: ?>
                    <p>Your database is not connected. Please check your <code>.env</code> file:</p>
                    <div class="code-block">
DB_HOST=localhost<br>
DB_NAME=phone_monitor<br>
DB_USER=your_username<br>
DB_PASS=your_password
                    </div>
                    <p><strong>Steps:</strong></p>
                    <ol>
                        <li>Create a MySQL database named <code>phone_monitor</code></li>
                        <li>Create a database user with full privileges</li>
                        <li>Update the <code>.env</code> file with your credentials</li>
                        <li>Run the database migrations (see database/migrations/ folder)</li>
                    </ol>
                <?php endif; ?>
            </div>
            
            <!-- Step 2: Install Database Tables -->
            <div class="setup-step <?php echo ($status['geofences_table'] && $status['notifications_table']) ? 'complete' : 'incomplete'; ?>">
                <h3>
                    <span><?php echo ($status['geofences_table'] && $status['notifications_table']) ? '✅' : '2️⃣'; ?></span>
                    Install Database Tables
                </h3>
                
                <?php if ($status['geofences_table'] && $status['notifications_table']): ?>
                    <p style="color: #22bb66;">✓ All required database tables are installed!</p>
                <?php else: ?>
                    <p>Run the latest database migration to install geofencing and notification features:</p>
                    <div class="code-block">
mysql -u your_user -p phone_monitor < database/migrations/003_geofences.sql
                    </div>
                    <p><strong>Or via phpMyAdmin/Plesk:</strong></p>
                    <ol>
                        <li>Open phpMyAdmin or your database tool</li>
                        <li>Select the <code>phone_monitor</code> database</li>
                        <li>Go to SQL tab</li>
                        <li>Copy and paste the contents of <code>database/migrations/003_geofences.sql</code></li>
                        <li>Click "Execute"</li>
                    </ol>
                    <p>This will create:</p>
                    <ul>
                        <li><code>geofences</code> - Location-based alert zones</li>
                        <li><code>geofence_events</code> - Entry/exit event tracking</li>
                        <li><code>email_notifications</code> - Email notification queue</li>
                    </ul>
                <?php endif; ?>
            </div>
            
            <!-- Step 3: Configuration -->
            <div class="setup-step">
                <h3>
                    <span>3️⃣</span>
                    Configuration Settings
                </h3>
                
                <p>Configure your PhoneMonitor installation:</p>
                
                <form method="POST" style="margin-top: 20px;">
                    <?php CSRF::field(); ?>
                    <input type="hidden" name="action" value="update_env">
                    
                    <div class="form-group">
                        <label for="site_url">Site URL</label>
                        <input type="url" id="site_url" name="site_url" 
                               value="<?php echo htmlspecialchars(SITE_URL); ?>" 
                               placeholder="https://phone-monitor.defecttracker.uk">
                        <small>Your website's full URL (used for email links and CORS)</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="google_maps_key">Google Maps API Key <?php echo $status['google_maps'] ? '✓' : '(Optional)'; ?></label>
                        <input type="text" id="google_maps_key" name="google_maps_key" 
                               value="<?php echo htmlspecialchars(GOOGLE_MAPS_API_KEY); ?>" 
                               placeholder="AIza...">
                        <small>Required for interactive maps. <a href="https://console.cloud.google.com/apis/credentials" target="_blank">Get your API key here →</a></small>
                    </div>
                    
                    <div class="form-group">
                        <label for="admin_email">Admin Email <?php echo $status['admin_email'] ? '✓' : '(Optional)'; ?></label>
                        <input type="email" id="admin_email" name="admin_email" 
                               value="<?php echo htmlspecialchars(getenv('ADMIN_EMAIL') ?: ''); ?>" 
                               placeholder="admin@example.com">
                        <small>Email address for receiving alerts and reports</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="asset_version">Asset Version (Cache Busting)</label>
                        <input type="text" id="asset_version" name="asset_version" 
                               value="<?php echo htmlspecialchars(ASSET_VERSION); ?>" 
                               placeholder="2">
                        <small>Increment this number when you update CSS/JS files to force browser refresh</small>
                    </div>
                    
                    <?php if ($status['env_writable']): ?>
                        <button type="submit" class="btn btn-primary">Save Configuration</button>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <strong>Warning:</strong> The .env file is not writable. Please update it manually or change file permissions.
                        </div>
                    <?php endif; ?>
                </form>
            </div>
            
            <!-- Step 4: Cron Jobs -->
            <div class="setup-step">
                <h3>
                    <span>4️⃣</span>
                    Setup Cron Jobs (Optional but Recommended)
                </h3>
                
                <p>Enable automated notifications and reports by adding these cron jobs:</p>
                
                <h4 style="margin-top: 20px;">🔔 Send Notifications (Every 15 minutes)</h4>
                <div class="code-block">
*/15 * * * * php /path/to/PhoneMonitor/cron_notifications.php
                </div>
                <p>Sends queued emails for: Low battery alerts, offline device alerts, geofence notifications</p>
                
                <h4 style="margin-top: 20px;">📊 Weekly Reports (Every Monday at 9am)</h4>
                <div class="code-block">
0 9 * * 1 php /path/to/PhoneMonitor/cron_weekly_report.php
                </div>
                <p>Sends a weekly summary of all device activity</p>
                
                <h4 style="margin-top: 20px;">📝 Via Plesk Control Panel:</h4>
                <ol>
                    <li>Go to <strong>Scheduled Tasks</strong> in Plesk</li>
                    <li>Click <strong>Add Task</strong></li>
                    <li>Enter the command above</li>
                    <li>Set the schedule (e.g., "Every 15 minutes")</li>
                    <li>Click <strong>OK</strong></li>
                </ol>
            </div>
            
            <!-- User Guide -->
            <div class="section-header" style="margin-top: 50px;">
                <h3>📖 User Guide</h3>
                <p>How to use PhoneMonitor features</p>
            </div>
            
            <!-- Feature: Dashboard -->
            <div class="card" style="margin-bottom: 20px;">
                <div class="card-header">
                    <h3 class="card-title">📱 Dashboard</h3>
                </div>
                <div class="card-body">
                    <h4>Features:</h4>
                    <ul>
                        <li><strong>Stats Cards:</strong> View total devices, online status, consent status, and revoked devices</li>
                        <li><strong>Device List:</strong> See all registered devices with real-time status</li>
                        <li><strong>Auto-Refresh:</strong> Dashboard updates every 30 seconds automatically</li>
                        <li><strong>Filter Devices:</strong> Click filter buttons to show only Online/Offline/Revoked devices</li>
                        <li><strong>Dark Mode:</strong> Click the 🌙 button in the header to toggle dark theme</li>
                    </ul>
                    
                    <h4>How to Use:</h4>
                    <ol>
                        <li>The dashboard shows an overview of all registered devices</li>
                        <li>Click on any device card to view detailed information</li>
                        <li>Use filter buttons to quickly find specific device types</li>
                        <li>Watch the browser console to see auto-refresh activity</li>
                    </ol>
                </div>
            </div>
            
            <!-- Feature: Geofences -->
            <div class="card" style="margin-bottom: 20px;">
                <div class="card-header">
                    <h3 class="card-title">📍 Geofences (Location Alerts)</h3>
                </div>
                <div class="card-body">
                    <h4>What are Geofences?</h4>
                    <p>Geofences are virtual boundaries around physical locations. You can get alerts when a device enters or leaves these zones.</p>
                    
                    <h4>Common Use Cases:</h4>
                    <ul>
                        <li><strong>Home Zone:</strong> Know when family members arrive home safely</li>
                        <li><strong>School Zone:</strong> Confirm kids arrived at school</li>
                        <li><strong>Work Zone:</strong> Track work arrivals/departures</li>
                        <li><strong>Restricted Areas:</strong> Alert when device leaves safe zone</li>
                    </ul>
                    
                    <h4>How to Create a Geofence:</h4>
                    <ol>
                        <li>Go to <strong>Geofences</strong> page</li>
                        <li>Enter a name (e.g., "Home", "School")</li>
                        <li>Get coordinates: 
                            <ul>
                                <li>Open Google Maps</li>
                                <li>Right-click on the location</li>
                                <li>Click "What's here?"</li>
                                <li>Copy the latitude and longitude</li>
                            </ul>
                        </li>
                        <li>Set radius (default 100 meters, adjust as needed)</li>
                        <li>Choose device (or "All Devices")</li>
                        <li>Select alert type: Enter, Exit, or Both</li>
                        <li>Click <strong>Create Geofence</strong></li>
                    </ol>
                    
                    <h4>Tips:</h4>
                    <ul>
                        <li>Start with a 100m radius and adjust based on results</li>
                        <li>Use "Enter" alerts for arrivals, "Exit" for departures</li>
                        <li>Enable email notifications (set ADMIN_EMAIL in config)</li>
                    </ul>
                </div>
            </div>
            
            <!-- Feature: Location History -->
            <div class="card" style="margin-bottom: 20px;">
                <div class="card-header">
                    <h3 class="card-title">🗺️ Location History</h3>
                </div>
                <div class="card-body">
                    <h4>View Device Location Timeline:</h4>
                    <ol>
                        <li>Click on any device from the Dashboard</li>
                        <li>Scroll to "Location History" section</li>
                        <li>Use date filters:
                            <ul>
                                <li><strong>Last 24h:</strong> Today's movements</li>
                                <li><strong>Last Week:</strong> 7 days of history</li>
                                <li><strong>Last Month:</strong> 30 days of tracking</li>
                                <li><strong>Last 90 Days:</strong> Full quarterly view</li>
                            </ul>
                        </li>
                        <li>Click "View 🗺️" to see exact location on Google Maps</li>
                    </ol>
                    
                    <h4>Understanding Location Data:</h4>
                    <ul>
                        <li><strong>Coordinates:</strong> Precise latitude/longitude</li>
                        <li><strong>Accuracy:</strong> GPS accuracy in meters (lower is better)</li>
                        <li><strong>Provider:</strong> How location was obtained (GPS, Network, etc.)</li>
                        <li><strong>Timestamp:</strong> When the location was recorded</li>
                    </ul>
                </div>
            </div>
            
            <!-- Feature: Email Notifications -->
            <div class="card" style="margin-bottom: 20px;">
                <div class="card-header">
                    <h3 class="card-title">📧 Email Notifications</h3>
                </div>
                <div class="card-body">
                    <h4>Automatic Alerts:</h4>
                    <ul>
                        <li><strong>Low Battery:</strong> When battery drops below 15%</li>
                        <li><strong>Device Offline:</strong> When device hasn't been seen for 24+ hours</li>
                        <li><strong>Geofence Events:</strong> When device enters/exits zones</li>
                        <li><strong>Weekly Reports:</strong> Sunday summary of all activity</li>
                    </ul>
                    
                    <h4>Setup Requirements:</h4>
                    <ol>
                        <li>Set <code>ADMIN_EMAIL</code> in configuration (Step 3 above)</li>
                        <li>Setup cron jobs to send queued emails (Step 4 above)</li>
                        <li>Ensure your server can send emails (most hosting supports this)</li>
                    </ol>
                    
                    <h4>Testing:</h4>
                    <p>Run the notification cron manually to test:</p>
                    <div class="code-block">
php cron_notifications.php
                    </div>
                </div>
            </div>
            
            <!-- Android App Setup -->
            <div class="card card-warning" style="margin-bottom: 20px;">
                <div class="card-header">
                    <h3 class="card-title">📲 Android App Setup</h3>
                </div>
                <div class="card-body">
                    <h4>Installing on Family Devices:</h4>
                    <ol>
                        <li>Build the Android app from <code>AndroidStudioProject/</code> folder</li>
                        <li>Install the APK on the device</li>
                        <li>Open the app and grant required permissions:
                            <ul>
                                <li>Location access (for tracking)</li>
                                <li>Background location (for continuous monitoring)</li>
                                <li>Notification access (for always-visible notification)</li>
                            </ul>
                        </li>
                        <li>Enter device owner name and optional display name</li>
                        <li>Click "Register Device"</li>
                        <li><strong>Important:</strong> Obtain consent from device owner before activating!</li>
                    </ol>
                    
                    <h4>Device will appear on dashboard within minutes!</h4>
                    
                    <h4>Privacy & Consent:</h4>
                    <ul>
                        <li>✅ Always-visible notification (can't be hidden)</li>
                        <li>✅ Users can uninstall anytime</li>
                        <li>✅ No access to messages, calls, or personal data</li>
                        <li>✅ No keylogging or screenshots</li>
                        <li>✅ Only tracks: Battery, storage, and GPS location</li>
                    </ul>
                </div>
            </div>
            
            <!-- Troubleshooting -->
            <div class="card card-danger" style="margin-bottom: 20px;">
                <div class="card-header">
                    <h3 class="card-title">🔧 Troubleshooting</h3>
                </div>
                <div class="card-body">
                    <h4>Common Issues:</h4>
                    
                    <details style="margin-bottom: 15px;">
                        <summary style="cursor: pointer; font-weight: 600; padding: 10px; background: #f8f9fa; border-radius: 6px;">
                            🔴 Database connection errors
                        </summary>
                        <div style="padding: 15px;">
                            <ul>
                                <li>Check .env file has correct credentials</li>
                                <li>Verify MySQL service is running</li>
                                <li>Ensure database user has proper permissions</li>
                                <li>Test connection: <code>mysql -u username -p database_name</code></li>
                            </ul>
                        </div>
                    </details>
                    
                    <details style="margin-bottom: 15px;">
                        <summary style="cursor: pointer; font-weight: 600; padding: 10px; background: #f8f9fa; border-radius: 6px;">
                            🔴 Devices not appearing on dashboard
                        </summary>
                        <div style="padding: 15px;">
                            <ul>
                                <li>Check Android app has internet connection</li>
                                <li>Verify API endpoint is accessible (check api/register.php)</li>
                                <li>Look for errors in browser console (F12)</li>
                                <li>Check device registered successfully (check devices table in database)</li>
                            </ul>
                        </div>
                    </details>
                    
                    <details style="margin-bottom: 15px;">
                        <summary style="cursor: pointer; font-weight: 600; padding: 10px; background: #f8f9fa; border-radius: 6px;">
                            🔴 Geofences not triggering
                        </summary>
                        <div style="padding: 15px;">
                            <ul>
                                <li>Ensure database migration 003 was run</li>
                                <li>Verify device is sending location updates</li>
                                <li>Check radius is appropriate (try 200m+)</li>
                                <li>Look in geofence_events table for entries</li>
                            </ul>
                        </div>
                    </details>
                    
                    <details style="margin-bottom: 15px;">
                        <summary style="cursor: pointer; font-weight: 600; padding: 10px; background: #f8f9fa; border-radius: 6px;">
                            🔴 Emails not sending
                        </summary>
                        <div style="padding: 15px;">
                            <ul>
                                <li>Verify ADMIN_EMAIL is set in .env</li>
                                <li>Run cron_notifications.php manually to test</li>
                                <li>Check email_notifications table for failed entries</li>
                                <li>Ensure server can send emails (check with host)</li>
                            </ul>
                        </div>
                    </details>
                    
                    <details style="margin-bottom: 15px;">
                        <summary style="cursor: pointer; font-weight: 600; padding: 10px; background: #f8f9fa; border-radius: 6px;">
                            🔴 Maps not displaying
                        </summary>
                        <div style="padding: 15px;">
                            <ul>
                                <li>Get Google Maps API key from console.cloud.google.com</li>
                                <li>Enable "Maps JavaScript API" for your project</li>
                                <li>Add API key to GOOGLE_MAPS_API_KEY in .env</li>
                                <li>Reload page and clear cache</li>
                            </ul>
                        </div>
                    </details>
                </div>
            </div>
            
            <!-- Mobile App Build Guides -->
            <div class="card card-success" style="margin-bottom: 20px;">
                <div class="card-header">
                    <h3 class="card-title">📱 Mobile App Build Guides</h3>
                </div>
                <div class="card-body">
                    <h4>Building Mobile Apps:</h4>
                    <p>PhoneMonitor supports both Android and iOS devices. The Android app is ready to build, and we provide guidance for creating an iOS app.</p>
                    
                    <div style="background: rgba(34, 187, 102, 0.1); padding: 15px; border-radius: 8px; margin: 15px 0;">
                        <h4 style="margin-top: 0;">📖 <a href="/MOBILE_APP_BUILD_GUIDE.md" target="_blank">Complete Mobile App Build Guide</a></h4>
                        <p>Comprehensive guide covering:</p>
                        <ul style="margin-bottom: 0;">
                            <li><strong>✅ Android Build:</strong> Step-by-step instructions (app is ready!)</li>
                            <li><strong>🍎 iOS Development:</strong> Native Swift guide + code examples</li>
                            <li><strong>🔄 Cross-Platform:</strong> React Native & Flutter options</li>
                            <li><strong>⚙️ Configuration:</strong> API endpoints, signing, testing</li>
                            <li><strong>📦 Distribution:</strong> APK, Play Store, App Store, TestFlight</li>
                            <li><strong>🔧 Troubleshooting:</strong> Common issues and solutions</li>
                        </ul>
                    </div>
                    
                    <h4>Platform Status:</h4>
                    <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
                        <tr style="background: #f8f9fa;">
                            <th style="padding: 10px; text-align: left; border: 1px solid #dee2e6;">Platform</th>
                            <th style="padding: 10px; text-align: left; border: 1px solid #dee2e6;">Status</th>
                            <th style="padding: 10px; text-align: left; border: 1px solid #dee2e6;">Build Time</th>
                        </tr>
                        <tr>
                            <td style="padding: 10px; border: 1px solid #dee2e6;">🤖 Android</td>
                            <td style="padding: 10px; border: 1px solid #dee2e6;"><span class="status-badge status-ok">✓ Ready Now</span></td>
                            <td style="padding: 10px; border: 1px solid #dee2e6;">5-10 minutes</td>
                        </tr>
                        <tr>
                            <td style="padding: 10px; border: 1px solid #dee2e6;">🍎 iOS</td>
                            <td style="padding: 10px; border: 1px solid #dee2e6;"><span class="status-badge status-warning">⚠ Needs Development</span></td>
                            <td style="padding: 10px; border: 1px solid #dee2e6;">1-2 weeks (or hire developer)</td>
                        </tr>
                        <tr>
                            <td style="padding: 10px; border: 1px solid #dee2e6;">🔄 Cross-Platform</td>
                            <td style="padding: 10px; border: 1px solid #dee2e6;"><span class="status-badge status-warning">⚠ Optional Alternative</span></td>
                            <td style="padding: 10px; border: 1px solid #dee2e6;">4-8 weeks</td>
                        </tr>
                    </table>
                    
                    <p style="margin-top: 15px;"><strong>Quick Start:</strong> If you have Android devices, you can build and deploy the app today! See the build guide for step-by-step instructions.</p>
                </div>
            </div>
            
            <!-- Support & Resources -->
            <div class="card card-info">
                <div class="card-header">
                    <h3 class="card-title">💡 Support & Resources</h3>
                </div>
                <div class="card-body">
                    <h4>Documentation:</h4>
                    <ul>
                        <li><strong>README.md:</strong> Project overview and features</li>
                        <li><strong>DEPLOYMENT_GUIDE.md:</strong> Web panel deployment instructions</li>
                        <li><strong>MOBILE_APP_BUILD_GUIDE.md:</strong> Android & iOS app build instructions</li>
                        <li><strong>PHASE_3_FEATURES.md:</strong> Optional advanced features (2FA, webhooks, analytics, etc.)</li>
                        <li><strong>database/migrations/:</strong> SQL schema files</li>
                    </ul>
                    
                    <h4>File Structure:</h4>
                    <ul>
                        <li><code>api/</code> - REST API endpoints for mobile apps</li>
                        <li><code>assets/</code> - CSS, JavaScript, images</li>
                        <li><code>database/</code> - SQL migration files</li>
                        <li><code>android/</code> - Android app source code (Kotlin)</li>
                    </ul>
                    
                    <h4>Key Files:</h4>
                    <ul>
                        <li><code>.env</code> - Configuration file</li>
                        <li><code>config.php</code> - PHP configuration loader</li>
                        <li><code>GeofenceService.php</code> - Geofencing logic</li>
                        <li><code>NotificationService.php</code> - Email system</li>
                    </ul>
                    
                    <h4>Want More Features?</h4>
                    <p>Check out <strong>PHASE_3_FEATURES.md</strong> for optional enhancements:</p>
                    <ul>
                        <li>📊 Advanced Analytics Dashboard</li>
                        <li>💬 Telegram/Discord Bot Alerts</li>
                        <li>📊 CSV/PDF Data Export</li>
                        <li>🔔 Custom Alert Rules</li>
                        <li>🔐 Two-Factor Authentication</li>
                        <li>🔗 API Webhooks</li>
                        <li>And 6 more features...</li>
                    </ul>
                </div>
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
