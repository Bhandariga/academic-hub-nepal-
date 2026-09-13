<?php
/**
 * AcademicHub Nepal - Authentication Helpers & Guards
 */

require_once __DIR__ . '/config.php';

/**
 * Check if a student user is logged in
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if the logged-in user is an administrator
 */
function is_admin() {
    return is_logged_in() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Require Student Login Guard
 */
function require_login($redirect_url = null) {
    if (!is_logged_in()) {
        $target = $redirect_url ? urlencode($redirect_url) : urlencode($_SERVER['REQUEST_URI'] ?? '');
        set_flash_message('warning', 'Please login to access this resource or dashboard.');
        header('Location: ' . BASE_URL . '/login.php?redirect=' . $target);
        exit;
    }
}

/**
 * Require Admin Login Guard
 */
function require_admin() {
    if (!is_admin()) {
        set_flash_message('danger', 'Administrator privileges required to access this area.');
        header('Location: ' . BASE_URL . '/admin/admin-login.php');
        exit;
    }
}

/**
 * Get Current Logged In User Info
 */
function get_current_user_data() {
    if (!is_logged_in()) {
        return null;
    }
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'name' => $_SESSION['user_name'] ?? 'Student',
        'email' => $_SESSION['user_email'] ?? '',
        'role' => $_SESSION['user_role'] ?? 'student',
        'campus' => $_SESSION['user_campus'] ?? 'Not specified'
    ];
}
