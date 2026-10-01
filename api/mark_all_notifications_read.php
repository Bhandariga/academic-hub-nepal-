<?php
/**
 * API: Mark all notifications as read
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth_check.php';
require_once __DIR__ . '/../config/notifications.php';

header('Content-Type: application/json');

require_login();
header('Cache-Control: no-store');
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { header('Allow: POST'); http_response_code(405); exit; }

$user_id = $_SESSION['user_id'];
$success = mark_all_notifications_read($pdo, $user_id);

echo json_encode(['success' => $success]);
