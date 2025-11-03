<?php
/**
 * Logout Handler
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

Auth::startSession();

if (Auth::check()) {
    Auth::logAction('user_logout');
}

Auth::logout();

header('Location: /login.php');
exit;
