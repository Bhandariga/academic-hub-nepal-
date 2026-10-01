<?php
/**
 * AcademicHub Nepal - CMS System Migration Runner
 * Execute the CMS migration to add all required tables and fields
 */

require_once __DIR__ . '/../config/db.php';

echo "Starting CMS System Migration...\n";

try {
    $sqlFile = __DIR__ . '/20260930_cms_system.sql';
    if (!file_exists($sqlFile)) {
        die("Error: Migration file not found: {$sqlFile}\n");
    }

    $sqlContent = file_get_contents($sqlFile);
    
    // Split and execute SQL statements (handle multi-line statements better)
    $statements = [];
    $currentStmt = '';
    $lines = explode("\n", $sqlContent);
    
    foreach ($lines as $line) {
        $line = trim($line);
        // Skip comments and empty lines
        if (empty($line) || strpos($line, '--') === 0) {
            continue;
        }
        $currentStmt .= $line . ' ';
        // If line ends with semicolon, it's a complete statement
        if (substr($line, -1) === ';') {
            $statements[] = trim($currentStmt);
            $currentStmt = '';
        }
    }
    
    // Add any remaining statement
    if (!empty(trim($currentStmt))) {
        $statements[] = trim($currentStmt);
    }
    
    $executed = 0;
    $errors = [];

    foreach ($statements as $stmt) {
        if (!empty($stmt)) {
            try {
                $pdo->exec($stmt);
                $executed++;
                echo "✓ Executed statement {$executed}: " . substr($stmt, 0, 60) . "...\n";
            } catch (PDOException $e) {
                // Ignore duplicate key errors and column exists errors
                if (strpos($e->getMessage(), 'Duplicate entry') !== false || 
                    strpos($e->getMessage(), 'Duplicate column') !== false ||
                    strpos($e->getMessage(), 'already exists') !== false) {
                    echo "⊘ Skipped (already exists): " . substr($stmt, 0, 60) . "...\n";
                } else {
                    $errors[] = $e->getMessage();
                    echo "✗ Error: " . $e->getMessage() . "\n";
                    echo "   Statement: " . substr($stmt, 0, 100) . "...\n";
                }
            }
        }
    }

    echo "\n========================================\n";
    echo "Migration Complete!\n";
    echo "Statements executed: {$executed}\n";
    if (!empty($errors)) {
        echo "Errors encountered: " . count($errors) . "\n";
    }
    echo "========================================\n";

} catch (Exception $e) {
    die("Migration failed: " . $e->getMessage() . "\n");
}
