<?php
/**
 * AcademicHub Nepal - System Configuration
 * Defines application constants, paths, and eSewa ePay V2 UAT parameters
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Application Information
define('APP_NAME', 'AcademicHub Nepal');
define('APP_TAGLINE', 'Academic Resource & Research Support Platform');
define('APP_VERSION', '1.0 Demo');

// Base URL Auto-Detection (Works on localhost, XAMPP, and live domains)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
// Normalize base path for both root, /Academic Hub/, /academic-hub/, or subdirectories
$docRoot = str_replace('\\', '/', realpath(__DIR__ . '/..'));
$serverDocRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? '');
$relPath = str_replace($serverDocRoot, '', $docRoot);
$relPath = '/' . ltrim($relPath, '/');
if ($relPath === '/') {
    $basePath = '';
} else {
    $basePath = rtrim($relPath, '/');
}
define('BASE_URL', $protocol . $host . $basePath);

// Storage & Upload Paths
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('RESOURCE_FILE_DIR', UPLOAD_DIR . 'resources/');
define('PREVIEW_IMAGE_DIR', UPLOAD_DIR . 'previews/');

// eSewa ePay V2 UAT (Sandbox) Configuration
// Official eSewa sandbox endpoint & credentials
define('ESEWA_URL', 'https://rc-epay.esewa.com.np/api/epay/main/v2/form');
define('ESEWA_STATUS_URL', 'https://rc-epay.esewa.com.np/api/epay/transaction/status/');
define('ESEWA_PRODUCT_CODE', 'EPAYTEST');
// Official public test secret key for EPAYTEST merchant
define('ESEWA_SECRET_KEY', '8gBm/:&EnhH.1/q');
define('ESEWA_SUCCESS_URL', BASE_URL . '/payment-success.php');
define('ESEWA_FAILURE_URL', BASE_URL . '/payment-failed.php');

/**
 * Generate HMAC SHA256 Signature for eSewa ePay V2
 * Signed message format: "total_amount={val},transaction_uuid={val},product_code={val}"
 *
 * @param string $total_amount
 * @param string $transaction_uuid
 * @param string $product_code
 * @param string $secret_key
 * @return string Base64 encoded signature
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
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
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
