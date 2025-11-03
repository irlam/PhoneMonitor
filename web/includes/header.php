<?php
$current_page = basename($_SERVER['PHP_SELF']);
$user = getCurrentUser();
?>
<header class="site-header">
    <div class="container">
        <div class="header-content">
            <div class="logo">
                <a href="/dashboard.php">📱 PhoneMonitor</a>
            </div>
            <nav class="nav">
                <a href="/dashboard.php" class="<?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">Dashboard</a>
                <a href="/devices.php" class="<?php echo $current_page === 'devices.php' ? 'active' : ''; ?>">Devices</a>
                <span class="user-info">
                    <?php echo htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?>
                </span>
                <a href="/logout.php" class="btn btn-sm btn-secondary">Logout</a>
            </nav>
        </div>
    </div>
</header>
