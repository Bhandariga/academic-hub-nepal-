<?php
/**
 * AcademicHub Nepal - Database Connection
 * PDO connection with standard XAMPP configuration (localhost, root, empty password)
 */

require_once __DIR__ . '/config.php';

$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'academic_hub';

try {
    $pdo = new PDO("mysql:host={$db_host};dbname={$db_name};charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
} catch (PDOException $e) {
    // Check if database simply does not exist yet; offer friendly guidance
    if ($e->getCode() === 1049) {
        header('Location: ' . BASE_URL . '/setup_db.php');
        exit;
    }
    die("<div style='font-family:sans-serif;padding:30px;max-width:600px;margin:50px auto;background:#fee2e2;border:1px solid #ef4444;border-radius:10px;color:#991b1b;'>
            <h3 style='margin-top:0;'>⚠️ Database Connection Error</h3>
            <p>Could not connect to MySQL server. Please ensure XAMPP MySQL service is running.</p>
            <p><strong>Details:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
            <a href='" . BASE_URL . "/setup_db.php' style='display:inline-block;background:#0f2942;color:#fff;padding:10px 18px;border-radius:6px;text-decoration:none;'>Run 1-Click Database Setup</a>
         </div>");
}
