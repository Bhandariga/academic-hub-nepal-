<?php
/**
 * AcademicHub Nepal - Automated Dynamic XML Sitemap Generator
 * File: sitemap.php
 * 
 * Generates an up-to-the-minute, compliant XML Sitemap with Google Image extensions,
 * covering all static pages, academic categories, and dynamic study materials.
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/config.php';

// Set headers for XML output and browser/search bot caching (6 hours)
header('Content-Type: application/xml; charset=utf-8');
header('Cache-Control: public, max-age=21600');

// Fetch all public resources from database
try {
    $stmt = $pdo->prepare("SELECT id, title, preview_image, created_at FROM resources WHERE status = 'published' AND visibility = 'public' ORDER BY id DESC");
    $stmt->execute();
    $resources = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $resources = [];
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">

    <!-- 1. Homepage -->
    <url>
        <loc><?= htmlspecialchars(BASE_URL, ENT_XML1 | ENT_QUOTES, 'UTF-8') ?>/</loc>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
        <image:image>
            <image:loc><?= htmlspecialchars(BASE_URL, ENT_XML1 | ENT_QUOTES, 'UTF-8') ?>/assets/images/og_banner.svg</image:loc>
            <image:title>AcademicHub Nepal - Tribhuvan University Notes &amp; Projects</image:title>
        </image:image>
    </url>

    <!-- 2. Academic Resources Catalog -->
    <url>
        <loc><?= htmlspecialchars(BASE_URL, ENT_XML1 | ENT_QUOTES, 'UTF-8') ?>/resources.php</loc>
        <changefreq>daily</changefreq>
        <priority>0.9</priority>
    </url>

    <!-- 3. Academic Programs Categories -->
    <?php
    $programs = ['BBS', 'BBA', 'BCA', 'MBS', 'MBA'];
    foreach ($programs as $prog):
    ?>
    <url>
        <loc><?= htmlspecialchars(BASE_URL, ENT_XML1 | ENT_QUOTES, 'UTF-8') ?>/resources.php?program=<?= urlencode($prog) ?></loc>
        <changefreq>weekly</changefreq>
        <priority>0.85</priority>
    </url>
    <?php endforeach; ?>

    <!-- 4. Campus Specific Collections -->
    <?php
    $campuses = ['Mark International College', 'Mahendra Multiple Campus', 'Deukhuri Bahumukhi Campus', 'Babai Campus', 'Gorkha Campus'];
    foreach ($campuses as $camp):
    ?>
    <url>
        <loc><?= htmlspecialchars(BASE_URL, ENT_XML1 | ENT_QUOTES, 'UTF-8') ?>/resources.php?campus=<?= urlencode($camp) ?></loc>
        <changefreq>weekly</changefreq>
        <priority>0.80</priority>
    </url>
    <?php endforeach; ?>

    <!-- 5. Academic Support Services -->
    <url>
        <loc><?= htmlspecialchars(BASE_URL, ENT_XML1 | ENT_QUOTES, 'UTF-8') ?>/services.php</loc>
        <changefreq>weekly</changefreq>
        <priority>0.80</priority>
    </url>

    <!-- 6. Our Faculty & Team -->
    <url>
        <loc><?= htmlspecialchars(BASE_URL, ENT_XML1 | ENT_QUOTES, 'UTF-8') ?>/team.php</loc>
        <changefreq>monthly</changefreq>
        <priority>0.75</priority>
    </url>

    <!-- 7. About & Academic Standards -->
    <url>
        <loc><?= htmlspecialchars(BASE_URL, ENT_XML1 | ENT_QUOTES, 'UTF-8') ?>/about.php</loc>
        <changefreq>monthly</changefreq>
        <priority>0.75</priority>
    </url>

    <!-- 8. Dynamic Individual Study Materials & Projects -->
    <?php foreach ($resources as $res): ?>
    <url>
        <loc><?= htmlspecialchars(BASE_URL, ENT_XML1 | ENT_QUOTES, 'UTF-8') ?>/resource-details.php?id=<?= (int)$res['id'] ?></loc>
        <lastmod><?= !empty($res['created_at']) ? date('Y-m-d', strtotime($res['created_at'])) : date('Y-m-d') ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.80</priority>
        <?php if (!empty($res['preview_image'])): 
            $imgUrl = (strpos($res['preview_image'], 'http') === 0) ? $res['preview_image'] : BASE_URL . '/uploads/previews/' . rawurlencode(basename($res['preview_image']));
        ?>
        <image:image>
            <image:loc><?= htmlspecialchars($imgUrl) ?></image:loc>
            <image:title><?= htmlspecialchars($res['title']) ?></image:title>
        </image:image>
        <?php endif; ?>
    </url>
    <?php endforeach; ?>

</urlset>
