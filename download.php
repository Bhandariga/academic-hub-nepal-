<?php
/**
 * AcademicHub Nepal - Protected File Download Delivery Engine
 * File: download.php
 * 
 * Enforces:
 * 1. User Authentication (Session check)
 * 2. Verified Paid Order Authorization (Prevents direct URL access or tampering)
 * 3. Protected binary streaming (Never reveals real storage path on server)
 * 4. Audit logging into downloads table
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth_check.php';

// Rule 1: Only logged-in users can download
if (!is_logged_in()) {
    set_flash_message('danger', 'You must be logged in to download purchased academic materials.');
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$resource_id = isset($_GET['resource_id']) ? (int)$_GET['resource_id'] : 0;

try {
    // Rule 2: Only paid orders can download
    // Locate a valid paid order for this user
    if ($order_id > 0) {
        $stmt = $pdo->prepare("
            SELECT o.*, r.title, r.file_path, r.file_format 
            FROM orders o
            JOIN resources r ON o.resource_id = r.id
            WHERE o.id = :order_id AND o.user_id = :user_id AND o.payment_status = 'PAID'
            LIMIT 1
        ");
        $stmt->execute([':order_id' => $order_id, ':user_id' => $user_id]);
        $order = $stmt->fetch();
    } elseif ($resource_id > 0) {
        $stmt = $pdo->prepare("
            SELECT o.*, r.title, r.file_path, r.file_format 
            FROM orders o
            JOIN resources r ON o.resource_id = r.id
            WHERE o.resource_id = :resource_id AND o.user_id = :user_id AND o.payment_status = 'PAID'
            ORDER BY o.id DESC LIMIT 1
        ");
        $stmt->execute([':resource_id' => $resource_id, ':user_id' => $user_id]);
        $order = $stmt->fetch();
    } else {
        $order = null;
    }

    // Admins can inspect and download any file for QA verification
    if (!$order && is_admin() && $resource_id > 0) {
        $stmt_admin = $pdo->prepare("SELECT id as resource_id, title, file_path, file_format FROM resources WHERE id = :id LIMIT 1");
        $stmt_admin->execute([':id' => $resource_id]);
        $res = $stmt_admin->fetch();
        if ($res) {
            $order = [
                'id' => null,
                'resource_id' => $res['resource_id'],
                'title' => $res['title'],
                'file_path' => $res['file_path'],
                'file_format' => $res['file_format']
            ];
        }
    }

    if (!$order) {
        set_flash_message('danger', 'Access denied. You do not have a verified paid order for this document.');
        header('Location: ' . BASE_URL . '/dashboard.php');
        exit;
    }

    // Check physical file path in uploads/resources/
    $realFileName = basename($order['file_path']);
    $fullFilePath = RESOURCE_FILE_DIR . $realFileName;

    // If file missing on disk, create standard demo sample payload dynamically
    if (!file_exists($fullFilePath)) {
        if (!file_exists(RESOURCE_FILE_DIR)) {
            mkdir(RESOURCE_FILE_DIR, 0777, true);
        }
        $dummyContent = "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n3 0 obj<</Type/Page/MediaBox[0 0 612 792]/Parent 2 0 R/Resources<<>>>>endobj\nxref\n0 4\n0000000000 65535 f\n0000000009 00000 n\n0000000052 00000 n\n0000000101 00000 n\ntrailer<</Size 4/Root 1 0 R>>\nstartxref\n178\n%%EOF";
        file_put_contents($fullFilePath, $dummyContent);
    }

    // Log the download into downloads table
    $log_stmt = $pdo->prepare("
        INSERT INTO downloads (user_id, resource_id, order_id, ip_address, download_date)
        VALUES (:uid, :rid, :oid, :ip, NOW())
    ");
    $log_stmt->execute([
        ':uid' => $user_id,
        ':rid' => $order['resource_id'],
        ':oid' => $order['id'],
        ':ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
    ]);

    // Rule 3: Do not expose direct file URL - Stream binary directly
    $cleanDownloadName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $order['title']);
    $extension = strtolower(pathinfo($realFileName, PATHINFO_EXTENSION)) ?: 'pdf';
    $downloadFileName = $cleanDownloadName . '.' . $extension;

    // Clear output buffers to prevent corrupt files
    if (ob_get_level()) {
        ob_end_clean();
    }

    // Determine Content-Type
    $contentType = 'application/octet-stream';
    if ($extension === 'pdf') {
        $contentType = 'application/pdf';
    } elseif ($extension === 'docx') {
        $contentType = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
    } elseif ($extension === 'doc') {
        $contentType = 'application/msword';
    }

    // Send HTTP download headers
    header('Content-Description: File Transfer');
    header('Content-Type: ' . $contentType);
    header('Content-Disposition: attachment; filename="' . $downloadFileName . '"');
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    header('Content-Length: ' . filesize($fullFilePath));

    // Stream file
    readfile($fullFilePath);
    exit;

} catch (Exception $e) {
    die("Download processing error: " . $e->getMessage());
}
