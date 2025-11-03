<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['username']);
}

function requireAuth() {
    if (!isLoggedIn()) {
        header('Location: /login.php');
        exit;
    }
}

function login($username, $password) {
    $user = fetchOne(
        "SELECT id, username, password_hash, name FROM users WHERE username = ?",
        [$username]
    );
    
    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['name'] = $user['name'];
        
        // Regenerate session ID to prevent session fixation
        session_regenerate_id(true);
        
        return true;
    }
    
    return false;
}

function logout() {
    $_SESSION = [];
    session_destroy();
    session_start();
}

function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'name' => $_SESSION['name'] ?? 'Admin'
    ];
}
