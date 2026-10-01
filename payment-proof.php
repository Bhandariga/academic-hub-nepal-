<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth_check.php';
require_login();
$statement = $pdo->prepare('SELECT user_id, payment_proof FROM orders WHERE id = :id');
$statement->execute([':id' => (int)($_GET['id'] ?? 0)]);
$order = $statement->fetch();
if (!$order || ((int)$order['user_id'] !== (int)$_SESSION['user_id'] && !admin_has_permission('manage_orders'))) {
    http_response_code(403); exit('Access denied.');
}
$root = realpath(RECEIPTS_DIR);
$file = !empty($order['payment_proof']) ? realpath(RECEIPTS_DIR . basename($order['payment_proof'])) : false;
if (!$root || !$file || !is_file($file) || strncmp($file, $root . DIRECTORY_SEPARATOR, strlen($root) + 1) !== 0) {
    http_response_code(404); exit('Receipt unavailable.');
}
$mime = (new finfo(FILEINFO_MIME_TYPE))->file($file);
if (!in_array($mime, ['image/jpeg','image/png','image/webp','application/pdf'], true)) { http_response_code(415); exit; }
header('Content-Type: ' . $mime);
header('Cache-Control: private, no-store');
header('Content-Length: ' . filesize($file));
session_write_close();
readfile($file);
