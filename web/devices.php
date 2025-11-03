<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';

requireAuth();

// Get all devices
$devices = fetchAll("
    SELECT 
        id,
        device_uuid,
        display_name,
        owner_name,
        registered_at,
        last_seen,
        consent_given,
        revoked,
        CASE 
            WHEN last_seen > DATE_SUB(NOW(), INTERVAL 1 HOUR) THEN 'online'
            WHEN last_seen > DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 'away'
            ELSE 'offline'
        END as status
    FROM devices
    ORDER BY display_name ASC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Devices - PhoneMonitor</title>
    <link rel="stylesheet" href="/assets/css/site.css">
</head>
<body>
    <?php include __DIR__ . '/includes/header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1>All Devices</h1>
        </div>
        
        <div class="card">
            <div class="card-body">
                <?php if (empty($devices)): ?>
                    <p class="text-muted">No devices registered yet.</p>
                <?php else: ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Status</th>
                                <th>Device Name</th>
                                <th>Owner</th>
                                <th>Registered</th>
                                <th>Last Seen</th>
                                <th>Consent</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($devices as $device): ?>
                                <tr class="<?php echo $device['revoked'] ? 'device-revoked' : ''; ?>">
                                    <td>
                                        <span class="badge badge-<?php echo htmlspecialchars($device['status'], ENT_QUOTES, 'UTF-8'); ?>">
                                            <?php echo htmlspecialchars(ucfirst($device['status']), ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                        <?php if ($device['revoked']): ?>
                                            <span class="badge badge-revoked">Revoked</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($device['display_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($device['owner_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars(date('Y-m-d', strtotime($device['registered_at'])), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <?php 
                                        if ($device['last_seen']) {
                                            echo htmlspecialchars(date('Y-m-d H:i', strtotime($device['last_seen'])), ENT_QUOTES, 'UTF-8');
                                        } else {
                                            echo '<span class="text-muted">Never</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php if ($device['consent_given']): ?>
                                            <span class="badge badge-success">✓</span>
                                        <?php else: ?>
                                            <span class="badge badge-warning">!</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="/device_view.php?id=<?php echo (int)$device['id']; ?>" class="btn btn-sm btn-primary">View</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="/assets/js/site.js"></script>
</body>
</html>
