<?php
/**
 * AcademicHub Nepal - Logout
 * File: logout.php
 */
require_once __DIR__ . '/config/config.php';

$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

// Start clean session for flash message
session_start();
set_flash_message('info', 'You have been logged out successfully.');
header('Location: ' . BASE_URL . '/login.php');
exit;
