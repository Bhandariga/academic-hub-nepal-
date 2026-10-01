<?php
/**
 * AcademicHub Nepal - Header Include
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/auth_check.php';
require_once __DIR__ . '/seo.php';

$current_page = basename($_SERVER['PHP_SELF']);

// Consolidate SEO params passed from calling page
$seo_params = [
    'title' => $page_title ?? null,
    'description' => $page_description ?? null,
    'keywords' => $page_keywords ?? null,
    'canonical' => $canonical_url ?? null,
    'image' => $og_image ?? null,
    'og_type' => $og_type ?? 'website',
    'robots' => $page_robots ?? null,
    'breadcrumbs' => $page_breadcrumbs ?? null,
    'schema' => $page_schema ?? null
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0f2942">

    <!-- Production SEO Meta & Open Graph Tags -->
    <?php render_seo_tags($seo_params); ?>

    <!-- Schema.org JSON-LD Structured Data -->
    <?php render_seo_schema($seo_params); ?>

    <!-- Google Fonts: Inter & Outfit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Outfit:wght@400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom AcademicHub Stylesheet -->
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
<link href="<?= BASE_URL ?>/assets/css/design-system.css?v=3" rel="stylesheet">
<link href="<?= BASE_URL ?>/assets/css/experience.css?v=4" rel="stylesheet">
<link href="<?= BASE_URL ?>/assets/css/studio.css?v=1" rel="stylesheet">
<link href="<?= BASE_URL ?>/assets/css/platform.css?v=2" rel="stylesheet">
<script src="<?= BASE_URL ?>/assets/js/theme.js?v=1"></script>
<?php if ($current_page === 'manage-team.php'): ?><link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"><link href="<?= BASE_URL ?>/assets/css/admin-enterprise.css?v=1" rel="stylesheet"><script src="<?= BASE_URL ?>/assets/js/admin-ui.js?v=1" defer></script><?php endif; ?>
</head>
<body class="hub-public page-<?= htmlspecialchars(pathinfo($current_page, PATHINFO_FILENAME)) ?>">
<a class="skip-link" href="#main-content">Skip to main content</a>

<!-- Top Announcement Trust Bar -->
<!-- <div class="topbar-announcement d-none d-md-block">
    <div class="container d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-3">
            <span><i class="bi bi-patch-check-fill text-info me-1"></i> <strong>TU Campus Specialized:</strong> BBS &bull; BBA &bull; Ma In Societies &bull; MBS &bull; MBA Guidelines</span>
            <span class="text-white-50">|</span>
            <span class="text-white-50"><i class="bi bi-shield-lock me-1"></i> Verified Academic Reference Standards</span>
        </div>
        <div class="d-flex align-items-center gap-3">
            <span class="small text-white-50"><i class="bi bi-clock me-1"></i> Support: 8:00 AM - 9:00 PM</span>
            <a href="https://wa.me/9805149182" target="_blank" class="small"><i class="bi bi-whatsapp text-success me-1"></i> WhatsApp: +977 9805149182</a>
        </div>
    </div>
</div> -->

<?php require_once __DIR__ . '/navbar.php'; ?>

<main id="main-content" tabindex="-1">
    <div class="container mt-3 flash-region">
        <?php display_flash_message(); ?>
    </div>
