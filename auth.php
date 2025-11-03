<?php
/**
 * Authentication and Session Management
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

class Auth {
    /**
     * Start session if not already started
     */
    public static function startSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    /**
     * Check if user is authenticated
     */
    public static function check() {
        self::startSession();
        return isset($_SESSION['user_id']) && isset($_SESSION['username']);
    }
    
    /**
     * Get current user ID
     */
    public static function userId() {
        self::startSession();
        return $_SESSION['user_id'] ?? null;
    }
    
    /**
     * Get current username
     */
    public static function username() {
        self::startSession();
        return $_SESSION['username'] ?? null;
    }
    
    /**
     * Get current user name
     */
    public static function name() {
        self::startSession();
        return $_SESSION['name'] ?? null;
    }
    
    /**
     * Attempt to log in a user
     */
    public static function attempt($username, $password) {
        try {
            $user = db()->fetchOne(
                "SELECT id, username, password_hash, name FROM users WHERE username = ? LIMIT 1",
                [$username]
            );
            
            if (!$user || !password_verify($password, $user['password_hash'])) {
                return false;
            }
            
            self::startSession();
            
            // Regenerate session ID to prevent fixation
            session_regenerate_id(true);
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['login_time'] = time();
            
            return true;
        } catch (Exception $e) {
            error_log("Auth attempt failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Log out the current user
     */
    public static function logout() {
        self::startSession();
        
        $_SESSION = [];
        
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        session_destroy();
    }
    
    /**
     * Require authentication or redirect to login
     */
    public static function require($redirect = '/login.php') {
        if (!self::check()) {
            header('Location: ' . $redirect);
            exit;
        }
    }
    
    /**
     * Log an action to audit log
     */
    public static function logAction($action, $deviceId = null, $meta = null) {
        try {
            $userId = self::userId();
            $metaJson = $meta ? json_encode($meta) : null;
            
            db()->query(
                "INSERT INTO audit_log (device_id, user_id, action, meta) VALUES (?, ?, ?, ?)",
                [$deviceId, $userId, $action, $metaJson]
            );
        } catch (Exception $e) {
            error_log("Failed to log action: " . $e->getMessage());
        }
    }
}
