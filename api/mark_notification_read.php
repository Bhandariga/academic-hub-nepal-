<?php
/**
 * API: Mark notification as read
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth_check.php';
require_once __DIR__ . '/../config/notifications.php';

header('Content-Type: application/json');

require_login();
header('Cache-Control: no-store');
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { header('Allow: POST'); http_response_code(405); exit; }

$user_id = $_SESSION['user_id'];
$notification_id = (int)($_POST['notification_id'] ?? 0);

if ($notification_id > 0) {
    $success = mark_notification_read($pdo, $notification_id, $user_id);
    echo json_encode(['success' => $success]);
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid notification ID']);
}
