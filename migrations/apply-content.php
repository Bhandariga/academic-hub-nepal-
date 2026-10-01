<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
// This additive migration never invokes the legacy initializer or demo seed logic.
putenv('ALLOW_DB_SETUP=0');
require __DIR__ . '/../config/db.php';
foreach (explode(';', file_get_contents(__DIR__ . '/20260930_content.sql')) as $sql) {
    if (trim($sql) !== '') $pdo->exec($sql);
}
$columns = $pdo->query('SHOW COLUMNS FROM team_members')->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('category', $columns, true)) {
    $pdo->exec("ALTER TABLE team_members ADD COLUMN category VARCHAR(60) NOT NULL DEFAULT 'Academic Advisors'");
}
echo "Content tables and team category are ready. Existing records preserved.\n";
