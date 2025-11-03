<?php
// Load environment variables from .env file
function loadEnv($path = __DIR__ . '/.env') {
    if (!file_exists($path)) {
        return;
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
        if (!array_key_exists($name, $_ENV)) {
            $_ENV[$name] = $value;
            putenv("$name=$value");
        }
    }
}

loadEnv();

// Configuration constants
define('APP_ENV', getenv('APP_ENV') ?: 'production');
define('SITE_URL', getenv('SITE_URL') ?: 'http://localhost');
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'phone_monitor');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('SESSION_NAME', getenv('SESSION_NAME') ?: 'pm_session');
define('CSRF_KEY', getenv('CSRF_KEY') ?: 'change_this_key');
define('REQUIRE_CONSENT', filter_var(getenv('REQUIRE_CONSENT'), FILTER_VALIDATE_BOOLEAN) ?? true);
define('GOOGLE_MAPS_API_KEY', getenv('GOOGLE_MAPS_API_KEY') ?: '');

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
if (APP_ENV === 'production') {
    ini_set('session.cookie_secure', 1);
}
session_name(SESSION_NAME);

// Error reporting
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
}
