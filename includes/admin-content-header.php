<!doctype html><html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= content_escape($page_title) ?> | AcademicHub</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
<link href="<?= BASE_URL ?>/assets/css/design-system.css?v=4" rel="stylesheet">
<link href="<?= BASE_URL ?>/assets/css/experience.css?v=4" rel="stylesheet">
<link href="<?= BASE_URL ?>/assets/css/studio.css?v=1" rel="stylesheet">
<link href="<?= BASE_URL ?>/assets/css/platform.css?v=2" rel="stylesheet">
<script src="<?= BASE_URL ?>/assets/js/theme.js?v=1"></script>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"><link href="<?= BASE_URL ?>/assets/css/admin-enterprise.css?v=1" rel="stylesheet"><script src="<?= BASE_URL ?>/assets/js/admin-ui.js?v=1" defer></script>
</head><body class="hub-admin">
<a href="#content-main" class="skip-link">Skip to content</a>
<div class="container-fluid"><div class="row">
<?php require __DIR__ . '/../admin/sidebar.php'; ?>
<main id="content-main" class="col-md-9 col-lg-10 p-3 p-lg-5">
<div class="d-flex justify-content-between align-items-center gap-3 mb-4"><div><div class="eyebrow">Content studio</div><h1 class="h2 mb-1"><?= content_escape($page_title) ?></h1><p class="text-muted mb-0">Thoughtful stories. A stronger academic community.</p></div><a href="<?= BASE_URL ?>/index.php" class="btn btn-outline-primary">View website <i class="bi bi-arrow-up-right"></i></a></div>
<?php display_flash_message(); ?>
