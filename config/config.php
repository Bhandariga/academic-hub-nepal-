<?php
/**
 * AcademicHub Nepal - Production System Configuration
 * Defines enterprise security headers, session parameters, environment variables, and eSewa parameters
 */

// 2. Load Local .env Configuration if present
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile) && is_readable($envFile)) {
    $envLines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($envLines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($envKey, $envVal) = explode('=', $line, 2);
            $envKey = trim($envKey);
            $envVal = trim($envVal, " \t\n\r\0\x0B\"'");
            if (!empty($envKey) && getenv($envKey) === false) {
                putenv("{$envKey}={$envVal}");
                $_ENV[$envKey] = $envVal;
                $_SERVER[$envKey] = $envVal;
            }
        }
    }
}

// 1. Detect HTTPS & Protocol (Handles reverse proxies, Cloudflare, ngrok tunnels, and load balancers)
$isHttps = false;
if (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') {
    $isHttps = true;
} elseif (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443) {
    $isHttps = true;
} elseif (getenv('TRUST_PROXY') === '1' && !empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') {
    $isHttps = true;
} elseif (getenv('TRUST_PROXY') === '1' && !empty($_SERVER['HTTP_X_FORWARDED_SSL']) && strtolower($_SERVER['HTTP_X_FORWARDED_SSL']) === 'on') {
    $isHttps = true;
} elseif (getenv('TRUST_PROXY') === '1' && !empty($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) === 'on') {
    $isHttps = true;
} elseif (getenv('TRUST_PROXY') === '1' && !empty($_SERVER['HTTP_CF_VISITOR']) && strpos($_SERVER['HTTP_CF_VISITOR'], 'https') !== false) {
    $isHttps = true;
}
$protocol = $isHttps ? "https://" : "http://";

// 3. Environment Mode ('production' or 'development')
// Automatically default to 'development' on localhost/XAMPP unless explicitly specified in .env
$serverHost = $_SERVER['HTTP_HOST'] ?? 'localhost';
$isLocalhost = (
    in_array(strtolower($serverHost), ['localhost', 'localhost:80', 'localhost:8080'], true) ||
    preg_match('/^127\.0\.0\.1(?::[0-9]+)?$/D', $serverHost) ||
    $serverHost === '[::1]' ||
    php_sapi_name() === 'cli'
);
$defaultEnv = $isLocalhost ? 'development' : 'production';
$appEnv = getenv('APP_ENV') ?: $defaultEnv;
define('APP_ENV', $appEnv);

// 3. Error Reporting & Secure Logging
$logDir = __DIR__ . '/../logs/';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0755, true);
}

if (APP_ENV === 'production') {
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', $logDir . 'app_errors.log');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
}

// Reject unexpected arrays instead of letting PHP coerce IDs or crash on scalar fields.
foreach ([$_GET, $_POST] as $input) {
    foreach ($input as $key => $value) {
        if (is_array($value) && ($key !== 'permissions' || count(array_filter($value, 'is_string')) !== count($value))) {
            http_response_code(400); exit('Invalid request parameters.');
        }
    }
}
if (APP_ENV === 'production') {
    set_exception_handler(function (Throwable $e) {
        error_log('Unhandled ' . get_class($e) . ': ' . $e->getMessage());
        if (!headers_sent()) require __DIR__ . '/../500.php';
        exit;
    });
}

// 4. Enterprise Session Hardening (Must be set before session_start)
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_trans_sid', '0');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// 5. Session Inactivity Timeout (Auto-logout idle sessions after 2 hours)
$session_timeout = 7200; // 120 minutes
if ((isset($_SESSION['last_activity']) && time() - $_SESSION['last_activity'] > $session_timeout) || (isset($_SESSION['authenticated_at']) && time() - $_SESSION['authenticated_at'] > 43200)) {
    session_unset();
    session_regenerate_id(true);
    $_SESSION['flash_message'] = [
        'type' => 'warning',
        'message' => 'Your session expired due to inactivity. Please log in again.'
    ];
}
$_SESSION['last_activity'] = time();

// 6. Global HTTP Security Headers
if (!headers_sent()) {
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('X-XSS-Protection: 0');
    header("Content-Security-Policy: base-uri 'self'; object-src 'none'; frame-ancestors 'self'");
    if ($isHttps && APP_ENV === 'production') header('Strict-Transport-Security: max-age=31536000');
    $privatePage = basename($_SERVER['SCRIPT_NAME'] ?? '');
    if (strpos($_SERVER['SCRIPT_NAME'] ?? '', '/admin/') !== false || in_array($privatePage, ['login.php','register.php','dashboard.php','payment.php','payment-success.php','payment-failed.php','payment-proof.php','download.php','receipt.php','logout.php','account-recovery.php'], true)) {
        header('Cache-Control: no-store, private');
        header('X-Robots-Tag: noindex, nofollow');
        header('Referrer-Policy: no-referrer');
    }
    if (!isset($privatePage) || !in_array($privatePage, ['account-recovery.php','login.php','register.php'], true)) header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
}

// 7. Application Information
define('APP_NAME', 'AcademicHub Nepal');
define('APP_TAGLINE', 'Academic Resource & Research Support Platform');
define('APP_VERSION', '2.0 Production');

// 8. Smart Base URL & Host Resolution
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
if (strpos($host, ',') !== false) {
    $host = trim(explode(',', $host)[0]);
}

$appDir = str_replace('\\', '/', realpath(__DIR__ . '/..'));
$docRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? '');
$scriptFile = str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME'] ?? '');
$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');

$basePath = '';
if (!empty($scriptFile) && !empty($scriptName) && stripos($scriptFile, $appDir) === 0) {
    $subScript = substr($scriptFile, strlen($appDir));
    if (!empty($subScript) && substr($scriptName, -strlen($subScript)) === $subScript) {
        $basePath = substr($scriptName, 0, -strlen($subScript));
    }
}
if ($basePath === '' && !empty($docRoot) && stripos($appDir, $docRoot) === 0) {
    $basePath = substr($appDir, strlen($docRoot));
}
if ($basePath === '') {
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    if (stripos($requestUri, '/Academic Hub') !== false || stripos($requestUri, '/Academic%20Hub') !== false) {
        $basePath = '/Academic Hub';
    }
}
$basePath = rtrim($basePath, '/');
$urlBasePath = implode('/', array_map('rawurlencode', explode('/', $basePath)));

define('BASE_PATH', $basePath);
if (!preg_match('/^[a-zA-Z0-9.\-\[\]:]+$/D', $host)) { http_response_code(400); exit('Invalid host.'); }
$configuredUrl = getenv('APP_URL') ?: '';
if (APP_ENV === 'production' && (!filter_var($configuredUrl, FILTER_VALIDATE_URL) || parse_url($configuredUrl, PHP_URL_SCHEME) !== 'https')) {
    http_response_code(503); exit('Application configuration is incomplete. Please contact support.');
}
if ($configuredUrl && PHP_SAPI !== 'cli' && strcasecmp(explode(':', $host)[0], parse_url($configuredUrl, PHP_URL_HOST) ?: '') !== 0) {
    http_response_code(400); exit('Invalid application host.');
}
define('BASE_URL', rtrim($configuredUrl ?: ($protocol . $host . $urlBasePath), '/'));

// 9. Storage & Upload Directories
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('RESOURCE_FILE_DIR', UPLOAD_DIR . 'resources/');
define('PREVIEW_IMAGE_DIR', UPLOAD_DIR . 'previews/');
define('RECEIPTS_DIR', UPLOAD_DIR . 'receipts/');

// Ensure critical storage directories exist
foreach ([UPLOAD_DIR, RESOURCE_FILE_DIR, PREVIEW_IMAGE_DIR, RECEIPTS_DIR, $logDir] as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
}

// 10. eSewa ePay V2 Configuration (Supports environment variables or secure defaults)
define('ESEWA_URL', getenv('ESEWA_URL') ?: 'https://rc-epay.esewa.com.np/api/epay/main/v2/form');
define('ESEWA_STATUS_URL', getenv('ESEWA_STATUS_URL') ?: 'https://rc-epay.esewa.com.np/api/epay/transaction/status/');
define('ESEWA_PRODUCT_CODE', getenv('ESEWA_PRODUCT_CODE') ?: 'EPAYTEST');
define('ESEWA_SECRET_KEY', getenv('ESEWA_SECRET_KEY') ?: '8gBm/:&EnhH.1/q');
define('ESEWA_SUCCESS_URL', BASE_URL . '/payment-success.php');
define('ESEWA_FAILURE_URL', BASE_URL . '/payment-failed.php');

/**
 * Generate HMAC SHA256 Signature for eSewa ePay V2
 */
function generate_esewa_signature($total_amount, $transaction_uuid, $product_code = ESEWA_PRODUCT_CODE, $secret_key = ESEWA_SECRET_KEY) {
    $message = "total_amount={$total_amount},transaction_uuid={$transaction_uuid},product_code={$product_code}";
    $hash = hash_hmac('sha256', $message, $secret_key, true);
    return base64_encode($hash);
}

/**
 * Sanitize User Inputs
 */
function sanitize($data) {
    return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
}

/**
 * Currency Formatter for Nepali Rupees
 */
function format_npr($amount) {
    return 'NPR ' . number_format((float)$amount, 0);
}

/**
 * Flash Message Helpers
 */
function set_flash_message($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type, // success, danger, warning, info
        'message' => $message
    ];
}

function display_flash_message() {
    if (isset($_SESSION['flash_message'])) {
        $msg = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        $icon = ($msg['type'] === 'success') ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill';
        echo '<div class="alert alert-' . htmlspecialchars($msg['type']) . ' alert-dismissible fade show shadow-sm" role="alert">
                <i class="bi ' . $icon . ' me-2"></i>' . htmlspecialchars($msg['message']) . '
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>';
    }
}

if (!function_exists('display_flash_messages')) {
    function display_flash_messages() {
        display_flash_message();
    }
}

// Global SEO & Schema.org Helpers
require_once __DIR__ . '/../includes/seo.php';



/** Keep SQL errors and filesystem details in private logs, not rendered pages. */
function safe_error_message(Throwable $e): string {
    error_log(get_class($e) . ': ' . $e->getMessage());
    if ($e instanceof PDOException) return 'The request could not be completed. Please try again later.';
    if ($e instanceof InvalidArgumentException || $e instanceof RuntimeException) return $e->getMessage();
    return 'The request could not be completed. Please try again later.';
}
