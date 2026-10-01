<?php
/**
 * AcademicHub Nepal - Search Suggestions API
 * Returns live search suggestions as user types
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

$query = trim($_GET['q'] ?? '');
$limit = max(1, min((int)($_GET['limit'] ?? 8), 10));

if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

try {
    // Search across title, description, subject, program, campus, keywords
    $stmt = $pdo->prepare("
        SELECT 
            id,
            title,
            program,
            campus,
            subject,
            price,
            preview_image
        FROM resources
        WHERE visibility = 'public' AND status = 'published' AND 
            (title LIKE :q 
            OR description LIKE :q_desc 
            OR subject LIKE :q_subject 
            OR program LIKE :q_program 
            OR campus LIKE :q_campus 
            OR keywords LIKE :q_keywords)
        ORDER BY 
            CASE 
                WHEN title LIKE :q_exact THEN 1
                WHEN program LIKE :q_exact_program THEN 2
                WHEN subject LIKE :q_exact_subject THEN 3
                ELSE 4
            END,
            downloads_count DESC
        LIMIT :limit
    ");
    
    $searchPattern = "%{$query}%";
    $exactPattern = "{$query}%";
    
    foreach (['q','q_desc','q_subject','q_program','q_campus','q_keywords'] as $key) $stmt->bindValue(':' . $key, $searchPattern, PDO::PARAM_STR);
    foreach (['q_exact','q_exact_program','q_exact_subject'] as $key) $stmt->bindValue(':' . $key, $exactPattern, PDO::PARAM_STR);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    
    $results = $stmt->fetchAll();
    
    // Format suggestions
    $suggestions = [];
    foreach ($results as $row) {
        $suggestions[] = [
            'id' => $row['id'],
            'title' => $row['title'],
            'program' => $row['program'],
            'campus' => $row['campus'],
            'subject' => $row['subject'],
            'price' => number_format((float)$row['price'], 2),
            'url' => BASE_URL . '/resource-details.php?id=' . $row['id']
        ];
    }
    
    echo json_encode($suggestions);
    
} catch (Exception $e) {
    echo json_encode(['error' => 'Search temporarily unavailable']);
}
