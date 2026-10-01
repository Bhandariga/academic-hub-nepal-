<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/../config/db.php';
foreach (explode(';', file_get_contents(__DIR__ . '/20260930_security.sql')) as $sql) {
    if (trim($sql) !== '') $pdo->exec($sql);
}
echo "Security token and verification tables ready. Existing accounts unchanged.\n";
