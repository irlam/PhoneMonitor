<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/csrf.php';

// If already logged in, redirect to dashboard
if (isLoggedIn()) {
    header('Location: /dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (login($username, $password)) {
        header('Location: /dashboard.php');
        exit;
    } else {
        $error = 'Invalid username or password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PhoneMonitor</title>
    <link rel="stylesheet" href="/assets/css/site.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-card">
            <h1>PhoneMonitor</h1>
            <p class="subtitle">Family Device Helper</p>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="/login.php">
                <?php echo csrfField(); ?>
                
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required autofocus>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Login</button>
            </form>
            
            <div class="login-footer">
                <p><small>Default credentials: admin / admin123</small></p>
                <p><small><strong>Change the password immediately after first login!</strong></small></p>
            </div>
        </div>
    </div>
</body>
</html>
