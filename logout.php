<?php
/**
 * AcademicHub Nepal - Logout
 * File: logout.php
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth_check.php';
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    $page_title = 'Sign out';
    require __DIR__ . '/includes/header.php';
    echo '<div class="container py-5"><div class="card p-4 mx-auto" style="max-width:480px"><h1 class="h3">Sign out of AcademicHub?</h1><p class="text-muted">You can sign in again to access your resources.</p><form method="post">' . csrf_field() . '<button class="btn btn-primary" type="submit">Sign out</button></form></div></div>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

security_audit('LOGOUT');
if (is_admin()) log_admin_activity($pdo, 'ADMIN_LOGOUT', 'Signed out');
remember_revoke($pdo);
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
