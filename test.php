<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
error_reporting(E_ALL);
ini_set('display_errors', 1);
echo "<h1>AcademicHub Diagnostics</h1>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Loaded Extensions: " . implode(', ', get_loaded_extensions()) . "</p>";
try {
    require_once __DIR__ . '/config/config.php';
    echo "<p style='color:green'>config.php loaded successfully! BASE_URL = " . BASE_URL . "</p>";
} catch (Throwable $e) {
    echo "<p style='color:red'>config.php failed: " . safe_error_message($e) . "</p>";
}

try {
    require_once __DIR__ . '/config/db.php';
    echo "<p style='color:green'>db.php connected successfully!</p>";
} catch (Throwable $e) {
    echo "<p style='color:red'>db.php failed: " . safe_error_message($e) . "</p>";
}
