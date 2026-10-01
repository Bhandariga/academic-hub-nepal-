<?php
require __DIR__ . '/config/config.php';
header('Content-Type: text/plain; charset=UTF-8');
$base = rtrim(parse_url(BASE_URL, PHP_URL_PATH) ?: '', '/');
echo "User-agent: *\n";
foreach (['admin/', 'config/', 'logs/', 'tests/', 'migrations/', 'uploads/resources/', 'uploads/receipts/', 'dashboard.php', 'payment.php', 'receipt.php', 'download.php', 'account-recovery.php'] as $path) {
    echo 'Disallow: ' . $base . '/' . $path . "\n";
}
echo 'Sitemap: ' . BASE_URL . "/sitemap.xml\n";
