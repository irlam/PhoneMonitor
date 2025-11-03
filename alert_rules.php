<?php
/**
 * Alert Rules Management
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/AlertRuleService.php';

Auth::require();

$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::require();
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create' || $action === 'update') {
        $ruleId = $_POST['rule_id'] ?? null;
        $name = trim($_POST['name'] ?? '');
        // Use string device_id (not numeric id) to match devices.device_id
        $deviceId = ($_POST['device_id'] === 'all') ? null : trim($_POST['device_id']);
        $ruleType = $_POST['rule_type'] ?? 'custom';
        $enabled = isset($_POST['enabled']);
        $cooldownMinutes = (int)($_POST['cooldown_minutes'] ?? 60);
        
        // Build conditions
        $conditions = [
            // normalize to lowercase as used by service ('and'|'or')
            'operator' => strtolower($_POST['condition_operator'] ?? 'and'),
            'rules' => []
        ];
        
        if (!empty($_POST['condition_field'])) {
            foreach ($_POST['condition_field'] as $idx => $field) {
                if (!empty($field) && isset($_POST['condition_operator_type'][$idx]) && isset($_POST['condition_value'][$idx])) {
                    $conditions['rules'][] = [
                        'field' => $field,
                        'operator' => $_POST['condition_operator_type'][$idx],
                        'value' => $_POST['condition_value'][$idx]
                    ];
                }
            }
        }
        
        // Build actions
        $actions = [
            'email' => isset($_POST['action_email']),
            'telegram' => isset($_POST['action_telegram']),
            'discord' => isset($_POST['action_discord'])
        ];
        
        if (empty($name)) {
            $error = 'Rule name is required';
        } elseif (empty($conditions['rules'])) {
            $error = 'At least one condition is required';
        } elseif (!$actions['email'] && !$actions['telegram'] && !$actions['discord']) {
            $error = 'At least one action (email, telegram, or discord) is required';
        } else {
            try {
                if ($action === 'create') {
                    AlertRuleService::createRule($name, $deviceId, $ruleType, $conditions, $actions, $enabled, $cooldownMinutes);
                    $success = 'Alert rule created successfully';
                } else {
                    AlertRuleService::updateRule($ruleId, $name, $deviceId, $ruleType, $conditions, $actions, $enabled, $cooldownMinutes);
                    $success = 'Alert rule updated successfully';
                }
            } catch (Exception $e) {
                $error = 'Error saving rule: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'delete') {
        $ruleId = (int)($_POST['rule_id'] ?? 0);
        if ($ruleId > 0) {
            try {
                AlertRuleService::deleteRule($ruleId);
                $success = 'Alert rule deleted successfully';
            } catch (Exception $e) {
                $error = 'Error deleting rule: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'toggle') {
        $ruleId = (int)($_POST['rule_id'] ?? 0);
        $enabled = (int)($_POST['enabled'] ?? 0);
        if ($ruleId > 0) {
            try {
                $rule = AlertRuleService::getRule($ruleId);
                if ($rule) {
                    AlertRuleService::updateRule(
                        $ruleId,
                        $rule['name'],
                        $rule['device_id'],
                        $rule['rule_type'],
                        json_decode($rule['conditions'], true),
                        json_decode($rule['actions'], true),
                        $enabled === 1,
                        $rule['cooldown_minutes']
                    );
                    $success = 'Alert rule ' . ($enabled ? 'enabled' : 'disabled');
                }
            } catch (Exception $e) {
                $error = 'Error toggling rule: ' . $e->getMessage();
            }
        }
    }
}

// Get all rules
$rules = AlertRuleService::getAllRules();

// Get recent triggers
$recentTriggers = AlertRuleService::getRecentTriggers(20);

// Get devices for dropdown
$devices = db()->query("SELECT device_id, owner_name, display_name FROM devices WHERE consent_given = TRUE ORDER BY owner_name")->fetchAll();

// Get rule for editing
$editRule = null;
if (isset($_GET['edit'])) {
    $editRule = AlertRuleService::getRule((int)$_GET['edit']);
    if ($editRule) {
        $editRule['conditions_array'] = json_decode($editRule['conditions'], true);
        $editRule['actions_array'] = json_decode($editRule['actions'], true);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alert Rules - PhoneMonitor</title>
    <link rel="stylesheet" href="assets/css/site.css?v=<?php echo urlencode(ASSET_VERSION); ?>">
    <style>
        .rule-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .rule-table th,
        .rule-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        
        body.dark-mode .rule-table th,
        body.dark-mode .rule-table td {
            border-bottom-color: rgba(255, 255, 255, 0.1);
        }
        
        .rule-table th {
            background: #f8f9fa;
            font-weight: 600;
        }
        
        body.dark-mode .rule-table th {
            background: rgba(40, 40, 55, 0.6);
        }
        
        .condition-row {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr auto;
            gap: 10px;
            margin-bottom: 10px;
            align-items: center;
        }
        
        .btn-icon {
            padding: 5px 10px;
            font-size: 16px;
            cursor: pointer;
        }
        
        .trigger-history {
            font-size: 14px;
            color: #7f8c8d;
        }
        
        .trigger-history strong {
            color: #2c3e50;
        }
        
        body.dark-mode .trigger-history strong {
            color: #e8e8e8;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .form-section {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        body.dark-mode .form-section {
            background: rgba(40, 40, 55, 0.8);
        }
        
        .checkbox-group {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }
        
        .checkbox-group label {
            display: flex;
            align-items: center;
            gap: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="header">
            <h1>Alert Rules</h1>
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
            <a href="/alert_rules.php" class="active">Alert Rules</a>
            <a href="/setup.php">Setup & Help</a>
        </nav>
        
        <main class="main-content">
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <!-- Create/Edit Rule Form -->
            <div class="form-section">
                <h2><?php echo $editRule ? 'Edit Alert Rule' : 'Create New Alert Rule'; ?></h2>
                
                <form method="POST" id="ruleForm">
                    <?php CSRF::field(); ?>
                    <input type="hidden" name="action" value="<?php echo $editRule ? 'update' : 'create'; ?>">
                    <?php if ($editRule): ?>
                        <input type="hidden" name="rule_id" value="<?php echo $editRule['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="name">Rule Name *</label>
                        <input type="text" id="name" name="name" class="form-control" required
                               value="<?php echo $editRule ? htmlspecialchars($editRule['name']) : ''; ?>"
                               placeholder="e.g., Low Battery Alert">
                    </div>
                    
                    <div class="form-group">
                        <label for="device_id">Apply To</label>
                        <select id="device_id" name="device_id" class="form-control">
                            <option value="all" <?php echo (!$editRule || $editRule['device_id'] === null) ? 'selected' : ''; ?>>
                                All Devices
                            </option>
                            <?php foreach ($devices as $device): ?>
                                <option value="<?php echo htmlspecialchars($device['device_id']); ?>"
                                        <?php echo ($editRule && $editRule['device_id'] === $device['device_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($device['owner_name']); ?>
                                    <?php if ($device['display_name']): ?>
                                        (<?php echo htmlspecialchars($device['display_name']); ?>)
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="rule_type">Rule Type</label>
                        <select id="rule_type" name="rule_type" class="form-control">
                            <option value="battery" <?php echo ($editRule && $editRule['rule_type'] === 'battery') ? 'selected' : ''; ?>>Battery</option>
                            <option value="location" <?php echo ($editRule && $editRule['rule_type'] === 'location') ? 'selected' : ''; ?>>Location</option>
                            <option value="storage" <?php echo ($editRule && $editRule['rule_type'] === 'storage') ? 'selected' : ''; ?>>Storage</option>
                            <option value="custom" <?php echo (!$editRule || $editRule['rule_type'] === 'custom') ? 'selected' : ''; ?>>Custom</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Conditions *</label>
                        <select name="condition_operator" class="form-control" style="margin-bottom: 10px;">
                            <?php $op = $editRule ? strtolower($editRule['conditions_array']['operator'] ?? 'and') : 'and'; ?>
                            <option value="and" <?php echo ($op === 'and') ? 'selected' : ''; ?>>
                                Match ALL conditions (AND)
                            </option>
                            <option value="or" <?php echo ($op === 'or') ? 'selected' : ''; ?>>
                                Match ANY condition (OR)
                            </option>
                        </select>
                        
                        <div id="conditions">
                            <?php if ($editRule && !empty($editRule['conditions_array']['rules'])): ?>
                                <?php foreach ($editRule['conditions_array']['rules'] as $idx => $cond): ?>
                                    <div class="condition-row">
                                        <select name="condition_field[]" class="form-control" required>
                                            <option value="">Select Field</option>
                                            <option value="battery_level" <?php echo $cond['field'] === 'battery_level' ? 'selected' : ''; ?>>Battery Level (%)</option>
                                            <option value="storage_free_gb" <?php echo $cond['field'] === 'storage_free_gb' ? 'selected' : ''; ?>>Free Storage (GB)</option>
                                            <option value="offline_hours" <?php echo $cond['field'] === 'offline_hours' ? 'selected' : ''; ?>>Offline Hours</option>
                                            <option value="offline_minutes" <?php echo $cond['field'] === 'offline_minutes' ? 'selected' : ''; ?>>Offline Minutes</option>
                                            <option value="speed_kmh" <?php echo $cond['field'] === 'speed_kmh' ? 'selected' : ''; ?>>Speed (km/h)</option>
                                            <option value="is_charging" <?php echo $cond['field'] === 'is_charging' ? 'selected' : ''; ?>>Is Charging (0 or 1)</option>
                                            <option value="hour_of_day" <?php echo $cond['field'] === 'hour_of_day' ? 'selected' : ''; ?>>Hour of Day (0-23)</option>
                                            <option value="day_of_week" <?php echo $cond['field'] === 'day_of_week' ? 'selected' : ''; ?>>Day of Week (1-7)</option>
                                        </select>
                                        
                                        <select name="condition_operator_type[]" class="form-control" required>
                                            <option value="<" <?php echo $cond['operator'] === '<' ? 'selected' : ''; ?>>&lt; Less than</option>
                                            <option value="<=" <?php echo $cond['operator'] === '<=' ? 'selected' : ''; ?>>&lt;= Less or equal</option>
                                            <option value=">" <?php echo $cond['operator'] === '>' ? 'selected' : ''; ?>>&gt; Greater than</option>
                                            <option value=">=" <?php echo $cond['operator'] === '>=' ? 'selected' : ''; ?>>&gt;= Greater or equal</option>
                                            <option value="==" <?php echo $cond['operator'] === '==' ? 'selected' : ''; ?>>== Equal to</option>
                                            <option value="!=" <?php echo $cond['operator'] === '!=' ? 'selected' : ''; ?>>!= Not equal to</option>
                                        </select>
                                        
                                        <input type="text" name="condition_value[]" class="form-control" required
                                               value="<?php echo htmlspecialchars($cond['value']); ?>"
                                               placeholder="Value">
                                        
                                        <button type="button" class="btn btn-danger btn-icon" onclick="removeCondition(this)">✕</button>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="condition-row">
                                    <select name="condition_field[]" class="form-control" required>
                                        <option value="">Select Field</option>
                                        <option value="battery_level">Battery Level (%)</option>
                                        <option value="storage_free_gb">Free Storage (GB)</option>
                                        <option value="offline_hours">Offline Hours</option>
                                        <option value="offline_minutes">Offline Minutes</option>
                                        <option value="speed_kmh">Speed (km/h)</option>
                                        <option value="is_charging">Is Charging (0 or 1)</option>
                                        <option value="hour_of_day">Hour of Day (0-23)</option>
                                        <option value="day_of_week">Day of Week (1-7)</option>
                                    </select>
                                    
                                    <select name="condition_operator_type[]" class="form-control" required>
                                        <option value="<">&lt; Less than</option>
                                        <option value="<=">&lt;= Less or equal</option>
                                        <option value=">">&gt; Greater than</option>
                                        <option value=">=">&gt;= Greater or equal</option>
                                        <option value="==">== Equal to</option>
                                        <option value="!=">!= Not equal to</option>
                                    </select>
                                    
                                    <input type="text" name="condition_value[]" class="form-control" required placeholder="Value">
                                    
                                    <button type="button" class="btn btn-danger btn-icon" onclick="removeCondition(this)">✕</button>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <button type="button" class="btn btn-secondary" onclick="addCondition()" style="margin-top: 10px;">
                            + Add Condition
                        </button>
                    </div>
                    
                    <div class="form-group">
                        <label>Actions * (Select at least one)</label>
                        <div class="checkbox-group">
                            <label>
                                <input type="checkbox" name="action_email" value="1"
                                       <?php echo ($editRule ? (!empty($editRule['actions_array']['email'])) : 'checked'); ?>>
                                📧 Email
                            </label>
                            <label>
                                <input type="checkbox" name="action_telegram" value="1"
                                       <?php echo ($editRule && !empty($editRule['actions_array']['telegram'])) ? 'checked' : ''; ?>>
                                💬 Telegram
                            </label>
                            <label>
                                <input type="checkbox" name="action_discord" value="1"
                                       <?php echo ($editRule && !empty($editRule['actions_array']['discord'])) ? 'checked' : ''; ?>>
                                💬 Discord
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="cooldown_minutes">Cooldown (Minutes)</label>
                        <input type="number" id="cooldown_minutes" name="cooldown_minutes" class="form-control"
                               min="1" max="10080"
                               value="<?php echo $editRule ? $editRule['cooldown_minutes'] : '60'; ?>"
                               placeholder="60">
                        <small>Prevent repeated alerts for the same rule within this time period</small>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="enabled" value="1"
                                   <?php echo (!$editRule || $editRule['enabled']) ? 'checked' : ''; ?>>
                            Enable this rule
                        </label>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">
                            <?php echo $editRule ? 'Update Rule' : 'Create Rule'; ?>
                        </button>
                        <?php if ($editRule): ?>
                            <a href="/alert_rules.php" class="btn btn-secondary">Cancel</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            
            <!-- Rules List -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">🔔 All Alert Rules</h3>
                </div>
                <div class="card-body" style="overflow-x: auto;">
                    <table class="rule-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Device</th>
                                <th>Actions</th>
                                <th>Status</th>
                                <th>Last Triggered</th>
                                <th>Operations</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($rules)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 30px; color: #7f8c8d;">
                                        No alert rules created yet. Create your first rule above!
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($rules as $rule): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($rule['name']); ?></strong></td>
                                    <td><?php echo ucfirst($rule['rule_type']); ?></td>
                                    <td><?php echo $rule['device_id'] ? htmlspecialchars($rule['owner_name']) : 'All Devices'; ?></td>
                                    <td>
                                        <?php
                                        $actions = json_decode($rule['actions'], true);
                                        $actionLabels = [];
                                        if (!empty($actions['email'])) $actionLabels[] = '📧';
                                        if (!empty($actions['telegram'])) $actionLabels[] = '💬T';
                                        if (!empty($actions['discord'])) $actionLabels[] = '💬D';
                                        echo implode(' ', $actionLabels);
                                        ?>
                                    </td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                        <?php CSRF::field(); ?>
                                            <input type="hidden" name="action" value="toggle">
                                            <input type="hidden" name="rule_id" value="<?php echo $rule['id']; ?>">
                                            <input type="hidden" name="enabled" value="<?php echo $rule['enabled'] ? 0 : 1; ?>">
                                            <button type="submit" class="badge <?php echo $rule['enabled'] ? 'badge-success' : 'badge-secondary'; ?>"
                                                    style="border: none; cursor: pointer;">
                                                <?php echo $rule['enabled'] ? 'Enabled' : 'Disabled'; ?>
                                            </button>
                                        </form>
                                    </td>
                                    <td>
                                        <?php if ($rule['last_triggered_at']): ?>
                                            <?php echo date('M d, H:i', strtotime($rule['last_triggered_at'])); ?>
                                        <?php else: ?>
                                            <span style="color: #95a5a6;">Never</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="?edit=<?php echo $rule['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this rule?');">
                                        <?php CSRF::field(); ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="rule_id" value="<?php echo $rule['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Recent Triggers -->
            <?php if (!empty($recentTriggers)): ?>
            <div class="card" style="margin-top: 30px;">
                <div class="card-header">
                    <h3 class="card-title">📋 Recent Triggers (Last 20)</h3>
                </div>
                <div class="card-body" style="overflow-x: auto;">
                    <table class="rule-table">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Rule</th>
                                <th>Device</th>
                                <th>Reason</th>
                                <th>Actions Taken</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentTriggers as $trigger): ?>
                            <tr>
                                <td><?php echo date('M d, H:i:s', strtotime($trigger['triggered_at'])); ?></td>
                                <td><?php echo htmlspecialchars($trigger['rule_name']); ?></td>
                                <td><?php echo htmlspecialchars($trigger['owner_name']); ?></td>
                                <td class="trigger-history"><?php echo htmlspecialchars($trigger['trigger_reason']); ?></td>
                                <td>
                                    <?php
                                    $actions = json_decode($trigger['actions_taken'], true);
                                    $labels = [];
                                    if (in_array('email', $actions)) $labels[] = 'Email';
                                    if (in_array('telegram', $actions)) $labels[] = 'Telegram';
                                    if (in_array('discord', $actions)) $labels[] = 'Discord';
                                    echo implode(', ', $labels);
                                    ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </main>
        
        <footer class="footer">
            <p>PhoneMonitor - Alert Rules Management</p>
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
    
    // Add new condition row
    function addCondition() {
        const conditionsDiv = document.getElementById('conditions');
        const newRow = document.createElement('div');
        newRow.className = 'condition-row';
        newRow.innerHTML = `
            <select name="condition_field[]" class="form-control" required>
                <option value="">Select Field</option>
                <option value="battery_level">Battery Level (%)</option>
                <option value="storage_free_gb">Free Storage (GB)</option>
                <option value="offline_hours">Offline Hours</option>
                <option value="offline_minutes">Offline Minutes</option>
                <option value="speed_kmh">Speed (km/h)</option>
                <option value="is_charging">Is Charging (0 or 1)</option>
                <option value="hour_of_day">Hour of Day (0-23)</option>
                <option value="day_of_week">Day of Week (1-7)</option>
            </select>
            
            <select name="condition_operator_type[]" class="form-control" required>
                <option value="<">&lt; Less than</option>
                <option value="<=">&lt;= Less or equal</option>
                <option value=">">&gt; Greater than</option>
                <option value=">=">&gt;= Greater or equal</option>
                <option value="==">\== Equal to</option>
                <option value="!=">!= Not equal to</option>
            </select>
            
            <input type="text" name="condition_value[]" class="form-control" required placeholder="Value">
            
            <button type="button" class="btn btn-danger btn-icon" onclick="removeCondition(this)">✕</button>
        `;
        conditionsDiv.appendChild(newRow);
    }
    
    // Remove condition row
    function removeCondition(btn) {
        const conditionsDiv = document.getElementById('conditions');
        if (conditionsDiv.children.length > 1) {
            btn.parentElement.remove();
        } else {
            alert('At least one condition is required');
        }
    }
    </script>
</body>
</html>
