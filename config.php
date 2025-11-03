<?php
/**
 * Configuration loader
 * Loads environment variables from .env file
 */

if (!defined('BASE_PATH')) {
    define('BASE_PATH', __DIR__);
}

// Load .env file
function loadEnv($path = BASE_PATH . '/.env') {
    if (!file_exists($path)) {
        $samplePath = BASE_PATH . '/.env.sample';
        if (file_exists($samplePath)) {
            throw new Exception('.env file not found. Copy .env.sample to .env and configure it.');
        }
        throw new Exception('.env file not found.');
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        
        // Skip comments
        if (strpos($line, '#') === 0) {
            continue;
        }
        
        // Parse KEY=VALUE
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Set environment variable if not already set
            if (!getenv($key)) {
                putenv("$key=$value");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
    }
}

// Load environment
try {
    loadEnv();
} catch (Exception $e) {
    die('Configuration Error: ' . $e->getMessage());
}

// Configuration constants
define('APP_ENV', getenv('APP_ENV') ?: 'production');
define('SITE_URL', getenv('SITE_URL') ?: 'http://localhost');
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'phone_monitor');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('SESSION_NAME', getenv('SESSION_NAME') ?: 'pm_session');
// Static asset cache-busting version (bump when CSS/JS changes)
define('ASSET_VERSION', getenv('ASSET_VERSION') ?: '2');
define('CSRF_KEY', getenv('CSRF_KEY') ?: 'change_this_key');
define('REQUIRE_CONSENT', filter_var(getenv('REQUIRE_CONSENT') ?: 'true', FILTER_VALIDATE_BOOLEAN));
define('GOOGLE_MAPS_API_KEY', getenv('GOOGLE_MAPS_API_KEY') ?: '');

// Error reporting based on environment
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}

// Session configuration
ini_set('session.name', SESSION_NAME);
ini_set('session.cookie_httponly', '1');
ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_samesite', 'Strict');

if (stripos(SITE_URL, 'https://') === 0) {
    ini_set('session.cookie_secure', '1');
}
