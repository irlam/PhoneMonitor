<?php
/**
 * Analytics Dashboard
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/AnalyticsService.php';

Auth::require();

// Get analytics data
$overview = AnalyticsService::getOverviewStats();
$devices = AnalyticsService::getDeviceComparison();
$geofenceStats = AnalyticsService::getGeofenceStats();
$alertRuleStats = AnalyticsService::getAlertRuleStats();

// Get battery trends for chart
$batteryTrends = AnalyticsService::getBatteryTrends(null, 7);

// Get activity timeline
$activityTimeline = AnalyticsService::getActivityTimeline(null, 7);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - PhoneMonitor</title>
    <link rel="stylesheet" href="assets/css/site.css?v=<?php echo urlencode(ASSET_VERSION); ?>">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        .analytics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .chart-container {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .chart-container h3 {
            margin-top: 0;
            margin-bottom: 15px;
            color: #2c3e50;
        }
        
        .chart-wrapper {
            position: relative;
            height: 300px;
        }
        
        body.dark-mode .chart-container {
            background: rgba(40, 40, 55, 0.8);
        }
        
        body.dark-mode .chart-container h3 {
            color: #e8e8e8;
        }
        
        .stat-card-mini {
            background: linear-gradient(135deg, rgba(52, 152, 219, 0.1), rgba(41, 128, 185, 0.1));
            border-radius: 8px;
            padding: 15px;
            text-align: center;
        }
        
        .stat-card-mini h4 {
            margin: 0;
            font-size: 14px;
            color: #7f8c8d;
            font-weight: 500;
        }
        
        .stat-card-mini .value {
            font-size: 28px;
            font-weight: 700;
            color: #2c3e50;
            margin-top: 5px;
        }
        
        body.dark-mode .stat-card-mini .value {
            color: #e8e8e8;
        }
        
        body.dark-mode .stat-card-mini h4 {
            color: #bdc3c7;
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="header">
            <h1>PhoneMonitor Analytics</h1>
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
            <a href="/analytics.php" class="active">Analytics</a>
            <a href="/alert_rules.php">Alert Rules</a>
            <a href="/setup.php">Setup & Help</a>
        </nav>
        
        <main class="main-content">
            <div class="page-header">
                <h2>📈 Analytics Dashboard</h2>
                <p class="subtitle">Visual insights and statistical analysis</p>
            </div>
            
            <!-- Overview Stats -->
            <div class="analytics-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
                <div class="stat-card-mini">
                    <h4>Total Devices</h4>
                    <div class="value"><?php echo $overview['total_devices']; ?></div>
                </div>
                <div class="stat-card-mini">
                    <h4>Online Now</h4>
                    <div class="value" style="color: #22bb66;"><?php echo $overview['online_devices']; ?></div>
                </div>
                <div class="stat-card-mini">
                    <h4>Offline</h4>
                    <div class="value" style="color: #ff6b6b;"><?php echo $overview['offline_devices']; ?></div>
                </div>
                <div class="stat-card-mini">
                    <h4>Avg Battery</h4>
                    <div class="value"><?php echo $overview['avg_battery']; ?>%</div>
                </div>
                <div class="stat-card-mini">
                    <h4>Low Battery</h4>
                    <div class="value" style="color: #f39c12;"><?php echo $overview['low_battery_count']; ?></div>
                </div>
                <div class="stat-card-mini">
                    <h4>Locations Today</h4>
                    <div class="value"><?php echo $overview['locations_today']; ?></div>
                </div>
            </div>
            
            <!-- Charts Row 1 -->
            <div class="analytics-grid">
                <!-- Battery Trends Chart -->
                <div class="chart-container">
                    <h3>📊 Battery Trends (7 Days)</h3>
                    <div class="chart-wrapper">
                        <canvas id="batteryChart"></canvas>
                    </div>
                </div>
                
                <!-- Activity Timeline Chart -->
                <div class="chart-container">
                    <h3>📍 Location Updates (7 Days)</h3>
                    <div class="chart-wrapper">
                        <canvas id="activityChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Charts Row 2 -->
            <div class="analytics-grid">
                <!-- Device Status Pie Chart -->
                <div class="chart-container">
                    <h3>📱 Device Status Distribution</h3>
                    <div class="chart-wrapper">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
                
                <!-- Geofence Activity Chart -->
                <div class="chart-container">
                    <h3>🌍 Geofence Activity (30 Days)</h3>
                    <div class="chart-wrapper">
                        <canvas id="geofenceChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Device Comparison Table -->
            <div class="card" style="margin-top: 30px;">
                <div class="card-header">
                    <h3 class="card-title">📊 Device Comparison</h3>
                </div>
                <div class="card-body" style="overflow-x: auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Device</th>
                                <th>Battery</th>
                                <th>Storage Free</th>
                                <th>Last Seen</th>
                                <th>Weekly Updates</th>
                                <th>Geofence Events</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($devices as $device): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($device['owner_name']); ?></strong>
                                    <?php if ($device['display_name']): ?>
                                        <br><small><?php echo htmlspecialchars($device['display_name']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span style="color: <?php echo $device['battery_level'] < 20 ? '#e74c3c' : '#2ecc71'; ?>">
                                        <?php echo $device['battery_level']; ?>%
                                    </span>
                                </td>
                                <td><?php echo round($device['storage_free'] / 1073741824, 2); ?> GB</td>
                                <td><?php echo date('d/m/Y H:i', strtotime($device['last_seen'])); ?></td>
                                <td><?php echo $device['weekly_updates']; ?></td>
                                <td><?php echo $device['weekly_events']; ?></td>
                                <td>
                                    <?php if (!$device['consent_given']): ?>
                                        <span class="badge badge-danger">Revoked</span>
                                    <?php elseif (strtotime($device['last_seen']) > time() - 1800): ?>
                                        <span class="badge badge-success">Online</span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">Offline</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Alert Rules Stats -->
            <?php if (!empty($alertRuleStats)): ?>
            <div class="card" style="margin-top: 30px;">
                <div class="card-header">
                    <h3 class="card-title">🔔 Alert Rule Statistics (30 Days)</h3>
                </div>
                <div class="card-body" style="overflow-x: auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Rule Name</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Triggers</th>
                                <th>Last Triggered</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($alertRuleStats as $rule): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($rule['name']); ?></strong></td>
                                <td><?php echo ucfirst($rule['rule_type']); ?></td>
                                <td>
                                    <?php if ($rule['enabled']): ?>
                                        <span class="badge badge-success">Enabled</span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">Disabled</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $rule['trigger_count']; ?></td>
                                <td><?php echo $rule['last_triggered'] ? date('d/m/Y H:i', strtotime($rule['last_triggered'])) : 'Never'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </main>
        
        <footer class="footer">
            <p>PhoneMonitor - Analytics Dashboard</p>
            <p><small>Data refreshes every 5-15 minutes via cache</small></p>
        </footer>
    </div>
    
    <script>
    // Dark Mode Toggle
    function toggleDarkMode() {
        document.body.classList.toggle('dark-mode');
        const isDark = document.body.classList.contains('dark-mode');
        localStorage.setItem('darkMode', isDark ? 'enabled' : 'disabled');
        document.getElementById('theme-icon').textContent = isDark ? '☀️' : '🌙';
        
        // Update chart colors for dark mode
        updateChartColors(isDark);
    }
    
    // Load dark mode preference
    if (localStorage.getItem('darkMode') === 'enabled') {
        document.body.classList.add('dark-mode');
        document.getElementById('theme-icon').textContent = '☀️';
    }
    
    // Chart.js default colors
    const isDarkMode = document.body.classList.contains('dark-mode');
    const textColor = isDarkMode ? '#e8e8e8' : '#2c3e50';
    const gridColor = isDarkMode ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)';
    
    // Battery Trends Chart
    const batteryData = <?php echo json_encode($batteryTrends); ?>;
    const batteryDates = [...new Set(batteryData.map(d => d.date))];
    const batteryDevices = [...new Set(batteryData.map(d => d.device_id))];
    
    const batteryDatasets = batteryDevices.map((deviceId, index) => {
        const colors = ['#3498db', '#e74c3c', '#2ecc71', '#f39c12', '#9b59b6', '#1abc9c'];
        const deviceData = batteryData.filter(d => d.device_id === deviceId);
        
        return {
            label: deviceId,
            data: batteryDates.map(date => {
                const item = deviceData.find(d => d.date === date);
                return item ? parseFloat(item.avg_battery) : null;
            }),
            borderColor: colors[index % colors.length],
            backgroundColor: colors[index % colors.length] + '33',
            tension: 0.4
        };
    });
    
    new Chart(document.getElementById('batteryChart'), {
        type: 'line',
        data: {
            labels: batteryDates,
            datasets: batteryDatasets
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    labels: { color: textColor }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    ticks: { color: textColor },
                    grid: { color: gridColor }
                },
                x: {
                    ticks: { color: textColor },
                    grid: { color: gridColor }
                }
            }
        }
    });
    
    // Activity Timeline Chart
    const activityData = <?php echo json_encode($activityTimeline); ?>;
    const activityDates = [...new Set(activityData.map(d => d.date + ' ' + String(d.hour).padStart(2, '0') + ':00'))];
    
    new Chart(document.getElementById('activityChart'), {
        type: 'bar',
        data: {
            labels: activityDates.slice(-24), // Last 24 hours
            datasets: [{
                label: 'Location Updates',
                data: activityData.slice(-24).map(d => d.location_count),
                backgroundColor: '#3498db88',
                borderColor: '#3498db',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    labels: { color: textColor }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { color: textColor },
                    grid: { color: gridColor }
                },
                x: {
                    ticks: { 
                        color: textColor,
                        maxRotation: 45,
                        minRotation: 45
                    },
                    grid: { color: gridColor }
                }
            }
        }
    });
    
    // Device Status Pie Chart
    new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: {
            labels: ['Online', 'Offline', 'Revoked'],
            datasets: [{
                data: [
                    <?php echo $overview['online_devices']; ?>,
                    <?php echo $overview['offline_devices']; ?>,
                    <?php echo $overview['revoked_devices']; ?>
                ],
                backgroundColor: ['#2ecc71', '#95a5a6', '#e74c3c'],
                borderWidth: 2,
                borderColor: isDarkMode ? '#282837' : '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    labels: { color: textColor }
                }
            }
        }
    });
    
    // Geofence Activity Chart
    const geofenceData = <?php echo json_encode($geofenceStats); ?>;
    new Chart(document.getElementById('geofenceChart'), {
        type: 'bar',
        data: {
            labels: geofenceData.map(g => g.name),
            datasets: [
                {
                    label: 'Entries',
                    data: geofenceData.map(g => g.enter_count),
                    backgroundColor: '#2ecc7188',
                    borderColor: '#2ecc71',
                    borderWidth: 1
                },
                {
                    label: 'Exits',
                    data: geofenceData.map(g => g.exit_count),
                    backgroundColor: '#e74c3c88',
                    borderColor: '#e74c3c',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    labels: { color: textColor }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { color: textColor },
                    grid: { color: gridColor }
                },
                x: {
                    ticks: { color: textColor },
                    grid: { color: gridColor }
                }
            }
        }
    });
    
    function updateChartColors(isDark) {
        // Reload page to update charts with new colors
        location.reload();
    }
    
    // Auto-refresh every 5 minutes
    setTimeout(() => location.reload(), 5 * 60 * 1000);
    </script>
</body>
</html>
