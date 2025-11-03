<?php
/**
 * Add Admin User Script
 * Run this once to add the specified user, then delete this file for security.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

$username = 'irlam';
$password = 'Subaru5554346';
$name = 'Admin User';

try {
    // Hash the password
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    
    // Check if user already exists
    $existing = db()->fetchOne("SELECT id FROM users WHERE username = ?", [$username]);
    
    if ($existing) {
        echo "User '$username' already exists.\n";
    } else {
        // Insert new user
        db()->query(
            "INSERT INTO users (username, password_hash, name) VALUES (?, ?, ?)",
            [$username, $passwordHash, $name]
        );
        
        echo "User '$username' added successfully.\n";
        echo "You can now log in with username: $username and the provided password.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>