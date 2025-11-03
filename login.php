<?php
/**
 * Login Page
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/csrf.php';

Auth::startSession();

// If already logged in, redirect to dashboard
if (Auth::check()) {
    header('Location: /dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CSRF::validateToken()) {
        $error = 'Invalid request. Please try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            $error = 'Please enter both username and password.';
        } elseif (Auth::attempt($username, $password)) {
            Auth::logAction('user_login');
            header('Location: /dashboard.php');
            exit;
        } else {
            $error = 'Invalid username or password.';
            sleep(1); // Simple rate limiting
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PhoneMonitor</title>
    <link rel="icon" type="image/svg+xml" href="/assets/icons/favicon.svg">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/icons/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/assets/icons/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/icons/apple-touch-icon.png">
    <link rel="mask-icon" href="/assets/icons/favicon.svg" color="#22bb66">
    <link rel="manifest" href="/assets/icons/site.webmanifest">
    <link rel="stylesheet" href="assets/css/site.css?v=<?php echo urlencode(ASSET_VERSION); ?>">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-card">
            <h1>PhoneMonitor</h1>
            <p class="subtitle">Family Device Helper</p>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="/login.php">
                <?php CSRF::field(); ?>
                
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required autofocus 
                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Login</button>
            </form>
            
            <div class="login-help">
                <p><small>Default credentials: admin / changeme123</small></p>
                <p><small>Change password immediately after first login!</small></p>
                <p style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #dee2e6;"><small><strong>Privacy First:</strong> No access to personal data, messages, calls, or media. No keylogging, screenshots, or surveillance capabilities.</small></p>
            </div>
        </div>
    </div>
</body>
</html>
