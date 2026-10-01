<?php
/**
 * AcademicHub Nepal - Authentication Helpers & Guards
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/login_protection.php';
require_once __DIR__ . '/account_security.php';
if (isset($pdo)) remember_restore($pdo);

// Refresh authorization so suspended accounts and revoked permissions take effect immediately.
if (isset($pdo, $_SESSION['user_id'])) {
    $guard = $pdo->prepare('SELECT role, permissions, is_active, password FROM users WHERE id = :id LIMIT 1');
    $guard->execute([':id' => $_SESSION['user_id']]);
    $identity = $guard->fetch();
    if (!$identity || !(int)$identity['is_active'] || (!isset($_SESSION['password_version']) || !hash_equals($_SESSION['password_version'], hash('sha256', $identity['password'])))) {
        $_SESSION = [];
        session_regenerate_id(true);
    } else {
        $_SESSION['password_version'] = hash('sha256', $identity['password']);
        $_SESSION['user_role'] = $identity['role'];
        $_SESSION['user_permissions'] = $identity['permissions'];
    }
}

// Local POST endpoints always validate CSRF before their handlers run.
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && !verify_csrf_token()) {
    http_response_code(403);
    exit('Your form session expired. Reload the page and try again.');
}

require_once __DIR__ . '/login_protection.php';
// Reserve attempts atomically across concurrent requests and sessions.
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'
    && in_array(basename($_SERVER['SCRIPT_FILENAME'] ?? ''), ['login.php', 'admin-login.php', 'register.php', 'account-recovery.php'], true)) {
    try {
        $emailKey = strtolower(trim(auth_text('email', 150)));
        $allowed = auth_rate_limit('auth-ip', $_SERVER['REMOTE_ADDR'] ?? 'unknown', 30);
        if ($emailKey !== '') $allowed = auth_rate_limit('auth-account', $emailKey, 8) && $allowed;
        if (!$allowed) {
            security_audit('AUTH_RATE_LIMIT');
            header('Retry-After: 900'); http_response_code(429);
            exit('Too many attempts. Please try again in 15 minutes.');
        }
        register_shutdown_function(function () {
            if (empty($GLOBALS['auth_success'])) security_audit('AUTH_UNSUCCESSFUL');
        });
    } catch (Throwable $e) {
        http_response_code(503); exit('Authentication is temporarily unavailable. Please try later.');
    }
}

/** Only allow application-local return URLs, including legacy absolute links. */
function safe_redirect_target($target) {
    if (!is_string($target) || preg_match('/[\\\\\x00-\x20]/', $target)) return '';
    if (str_starts_with($target, BASE_URL . '/')) return $target;
    $path = parse_url(BASE_URL, PHP_URL_PATH) ?: '';
    if (str_starts_with($target, $path . '/') && !str_starts_with($target, '//')) return $target;
    return '';
}

function require_admin_permission($permission) {
    require_admin();
    if (!admin_has_permission($permission)) {
        http_response_code(403);
        exit('You do not have permission to access this section.');
    }
}

/** Structured audit log without recording passwords, tokens or payment payloads. */
function security_audit($action, array $details = []) {
    $entry = ['time' => gmdate('c'), 'user' => $_SESSION['user_id'] ?? null,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? '', 'action' => $action, 'details' => $details];
    error_log(json_encode($entry, JSON_UNESCAPED_SLASHES) . PHP_EOL, 3, __DIR__ . '/../logs/security.log');
}

/**
 * Check if a student user is logged in
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if the logged-in user is any administrator (admin or superadmin)
 */
function is_admin() {
    return is_logged_in() && isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['admin', 'superadmin'], true);
}

/**
 * Check if the logged-in user is specifically a Super Administrator
 */
function is_superadmin() {
    return is_logged_in() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'superadmin';
}

/**
 * Check if the current administrator has a specific permission
 */
function admin_has_permission($permission) {
    if (!is_admin()) {
        return false;
    }
    if (is_superadmin()) {
        return true; // Super admin has absolute access to all modules
    }
    if (in_array($permission, ['system_settings', 'manage_admins'], true)) return false;
    $perms = $_SESSION['user_permissions'] ?? [];
    if (is_string($perms)) {
        $perms = json_decode($perms, true) ?: [];
    }
    if (!is_array($perms)) return false;
    if (in_array('all', $perms, true)) {
        return true;
    }
    return in_array($permission, $perms, true);
}

/**
 * Check if the current administrator has a specific permission
 * Alias for admin_has_permission
 */
function has_permission($permission) {
    return admin_has_permission($permission);
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
 * Require Admin Login Guard (admin or superadmin)
 */
function require_admin() {
    if (!is_admin()) {
        set_flash_message('danger', 'Administrator privileges required to access this area.');
        header('Location: ' . BASE_URL . '/admin/admin-login.php');
        exit;
    }
}

/**
 * Require Specific Permission Guard
 * Used for Staff role to check if they have a specific permission
 */
function require_permission($permission) {
    if (!admin_has_permission($permission)) {
        http_response_code(403);
        exit('Access Denied: You do not have permission to access this section.');
    }
}

/**
 * Require Super Admin Login Guard
 */
function require_superadmin() {
    if (!is_superadmin()) {
        set_flash_message('danger', 'Access restricted: Only the Chief Super Administrator can manage administrative roles.');
        header('Location: ' . BASE_URL . '/admin/dashboard.php');
        exit;
    }
}

/**
 * CSRF Protection Token Helpers
 */
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    $token = csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

function verify_csrf_token($token = null) {
    if ($token === null) {
        $token = $_POST['csrf_token'] ?? '';
    }
    if (!is_string($token) || empty($_SESSION['csrf_token']) || empty($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
        set_flash_message('danger', 'Security validation failed (Invalid or expired CSRF token). Please try again.');
        return false;
    }
    return true;
}

/**
 * Audit Trail Logger for Administrator Actions
 */
function log_admin_activity($pdo, $action, $details = '', $target_type = '', $target_id = null) {
    try {
        $admin_id = $_SESSION['user_id'] ?? 0;
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $stmt = $pdo->prepare("INSERT INTO admin_activity_logs (admin_id, action, details, target_type, target_id, ip_address, created_at) VALUES (:aid, :act, :det, :tt, :tid, :ip, NOW())");
        $stmt->execute([
            ':aid' => $admin_id,
            ':act' => $action,
            ':det' => is_array($details) ? json_encode($details) : (string)$details,
            ':tt' => $target_type,
            ':tid' => $target_id,
            ':ip' => $ip
        ]);
    } catch (Exception $e) {
        // Silently skip if table not yet created
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
        'admin_role' => $_SESSION['user_admin_role'] ?? ($_SESSION['user_role'] === 'superadmin' ? 'Super Administrator' : 'Administrator'),
        'campus' => $_SESSION['user_campus'] ?? 'Not specified',
        'profile_image' => $_SESSION['user_avatar'] ?? null
    ];
}

/**
 * Render Avatar Image or Stylized Initial Badge
 *
 * @param string|null $profile_image Filename in uploads/avatars
 * @param string $name User's name for initials fallback and alt tag
 * @param int $size Width & height in px
 * @param string $extraClass Additional CSS classes
 * @return string HTML string
 */
function render_user_avatar($profile_image, $name = 'User', $size = 32, $extraClass = '') {
    $cleanName = htmlspecialchars($name ?: 'User', ENT_QUOTES, 'UTF-8');
    $initial = htmlspecialchars(mb_strtoupper(mb_substr(trim($name ?: 'U'), 0, 1)), ENT_QUOTES, 'UTF-8');
    $profile_image = is_string($profile_image) && preg_match('/^[a-zA-Z0-9_-]+\.(?:jpe?g|png|webp)$/iD', $profile_image) ? $profile_image : null;
    $avatarFile = !empty($profile_image) ? dirname(__DIR__) . '/uploads/avatars/' . $profile_image : null;
    $classAttr = !empty($extraClass) ? ' ' . htmlspecialchars($extraClass, ENT_QUOTES, 'UTF-8') : '';

    if (!empty($profile_image) && file_exists($avatarFile)) {
        $src = BASE_URL . '/uploads/avatars/' . htmlspecialchars($profile_image, ENT_QUOTES, 'UTF-8');
        return '<img src="' . $src . '" alt="' . $cleanName . '" class="rounded-circle object-fit-cover' . $classAttr . '" style="width:' . (int)$size . 'px; height:' . (int)$size . 'px; flex-shrink:0;">';
    }

    $fontSize = ($size <= 32) ? '0.8rem' : (($size <= 48) ? '1.1rem' : '1.6rem');
    return '<div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold' . $classAttr . '" style="width:' . (int)$size . 'px; height:' . (int)$size . 'px; font-size:' . $fontSize . '; background:linear-gradient(135deg, #0284c7, #0f2942) !important; flex-shrink:0;">' . $initial . '</div>';
}

