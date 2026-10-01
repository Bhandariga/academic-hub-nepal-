<?php
/**
 * AcademicHub Nepal - Production SEO & Structured Data Engine
 * File: includes/seo.php
 * 
 * Provides automated, standards-compliant SEO meta tags, Open Graph / Twitter Card protocols,
 * and JSON-LD Schema.org rich snippets (Organization, WebSite with SearchAction, BreadcrumbList,
 * LearningResource, Product, FAQPage, Service).
 */

require_once __DIR__ . '/../config/config.php';

// 1. Clean & truncate plain-text description for search snippets (155-160 chars)
if (!function_exists('clean_seo_description')) {
    function clean_seo_description($text, $limit = 160) {
        $clean = strip_tags((string)$text);
        $clean = preg_replace('/\s+/', ' ', $clean);
        $clean = trim($clean);
        if (mb_strlen($clean) <= $limit) {
            return $clean;
        }
        return mb_substr($clean, 0, $limit - 3) . '...';
    }
}

// 2. Render Complete SEO Head Meta Tags
if (!function_exists('render_seo_tags')) {
    function render_seo_tags($params = []) {
    // 1. Resolve Primary SEO Fields
    $default_title = "Academic Resources Nepal | TU, BBS & BCA Notes | AcademicHub Nepal";
    $default_desc = "Academic resources for Nepal: explore TU notes, BBS and BCA resources, project reports, research templates and academic research support from AcademicHub Nepal.";
    $default_keywords = "AcademicHub Nepal, TU Notes, BBS 4th Year Project Report, BBA Internship Report Nepal, Ma In Societies Project Documentation TU, MBS Thesis Guidelines, Tribhuvan University notes, APA 7 thesis template Nepal, eSewa payment academic notes";
    $default_image = BASE_URL . '/assets/images/og_banner.svg';

    $title = !empty($params['title']) ? $params['title'] : $default_title;
    $description = !empty($params['description']) ? clean_seo_description($params['description']) : $default_desc;
    $keywords = !empty($params['keywords']) ? $params['keywords'] : $default_keywords;
    $image = !empty($params['image']) ? $params['image'] : $default_image;
    $og_type = !empty($params['og_type']) ? $params['og_type'] : 'website';

    // 2. Canonical URL Resolution
    if (!empty($params['canonical'])) {
        $canonical = $params['canonical'];
    } else {
        $page = basename($_SERVER['SCRIPT_NAME'] ?? 'index.php');
        $canonical = BASE_URL . ($page === 'index.php' ? '/' : '/' . $page);
        if ($page === 'resources.php') {
            $filters = [];
            foreach (['program', 'campus'] as $key) {
                if (isset($_GET[$key]) && is_string($_GET[$key]) && strlen($_GET[$key]) <= 150) $filters[$key] = $_GET[$key];
            }
            if ($filters) $canonical .= '?' . http_build_query($filters);
        }
    }

    // 3. Robots Directive (Auto-detect sensitive/private pages)
    $currentPage = basename($_SERVER['PHP_SELF'] ?? '');
    $isPrivatePage = in_array($currentPage, [
        'login.php', 'register.php', 'dashboard.php', 'download.php', 
        'receipt.php', 'account-recovery.php', 'payment-proof.php', 'logout.php', 'payment.php', 'payment-success.php', 'payment-failed.php'
    ]) || (strpos($_SERVER['REQUEST_URI'] ?? '', '/admin/') !== false);

    if (isset($params['robots'])) {
        $robots = $params['robots'];
    } elseif ($isPrivatePage) {
        $robots = 'noindex, nofollow';
    } else {
        $robots = 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1';
    }

    // Output Sanitized Tags
    ?>
    <title><?= htmlspecialchars($title) ?></title>
    <meta name="description" content="<?= htmlspecialchars($description) ?>">
    <meta name="keywords" content="<?= htmlspecialchars($keywords) ?>">
    <meta name="robots" content="<?= htmlspecialchars($robots) ?>">
    <meta name="author" content="AcademicHub Nepal">
    <link rel="canonical" href="<?= htmlspecialchars($canonical) ?>">

    <!-- Geographic & Regional Targeting (Nepal) -->
    <meta name="geo.region" content="NP">
    <meta name="geo.placename" content="Kathmandu, Nepal">
    <meta name="coverage" content="Nepal">
    <meta name="distribution" content="Global">

    <!-- Open Graph Protocol (Facebook, LinkedIn, WhatsApp, Viber) -->
    <meta property="og:locale" content="en_US">
    <meta property="og:type" content="<?= htmlspecialchars($og_type) ?>">
    <meta property="og:title" content="<?= htmlspecialchars($title) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($description) ?>">
    <meta property="og:url" content="<?= htmlspecialchars($canonical) ?>">
    <meta property="og:site_name" content="AcademicHub Nepal">
    <meta property="og:image" content="<?= htmlspecialchars($image) ?>">
    <meta property="og:image:alt" content="<?= htmlspecialchars($title) ?>">

    <!-- Twitter Card Protocol -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= htmlspecialchars($title) ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($description) ?>">
    <meta name="twitter:image" content="<?= htmlspecialchars($image) ?>">
    <meta name="twitter:site" content="@academichub_np">
    <?php
    }
}

// 3. Render Schema.org JSON-LD Structured Data
if (!function_exists('render_seo_schema')) {
    function render_seo_schema($params = []) {
        $schemas = [];

    // 1. Organization Schema
    $schemas[] = [
        "@context" => "https://schema.org",
        "@type" => "EducationalOrganization",
        "name" => "AcademicHub Nepal",
        "alternateName" => "Academic Hub Nepal",
        "url" => BASE_URL,
        "logo" => BASE_URL . '/assets/images/og_banner.svg',
        "description" => "Nepal's trusted academic reference platform for Tribhuvan University research reports, project templates, and thesis support.",
        "address" => [
            "@type" => "PostalAddress",
            "streetAddress" => "Kirtipur / New Baneshwor",
            "addressLocality" => "Kathmandu",
            "addressRegion" => "Bagmati Province",
            "postalCode" => "44600",
            "addressCountry" => "NP"
        ],
        "contactPoint" => [
            "@type" => "ContactPoint",
            "telephone" => "+977-9805149182",
            "contactType" => "customer support",
            "areaServed" => "NP",
            "availableLanguage" => ["Nepali", "English"]
        ],
        "sameAs" => [
            "https://www.facebook.com/academichubnepal",
            "https://www.linkedin.com/company/academic-hub-nepal"
        ]
    ];

    // 2. WebSite Schema with Sitelinks SearchBox
    $schemas[] = [
        "@context" => "https://schema.org",
        "@type" => "WebSite",
        "name" => "AcademicHub Nepal",
        "url" => BASE_URL,
        "potentialAction" => [
            "@type" => "SearchAction",
            "target" => [
                "@type" => "EntryPoint",
                "urlTemplate" => BASE_URL . "/resources.php?search={search_term_string}"
            ],
            "query-input" => "required name=search_term_string"
        ]
    ];

    // 3. BreadcrumbList Schema (if supplied)
    if (!empty($params['breadcrumbs']) && is_array($params['breadcrumbs'])) {
        $itemList = [];
        $pos = 1;
        foreach ($params['breadcrumbs'] as $crumb) {
            $itemList[] = [
                "@type" => "ListItem",
                "position" => $pos++,
                "name" => $crumb['name'],
                "item" => $crumb['url']
            ];
        }
        $schemas[] = [
            "@context" => "https://schema.org",
            "@type" => "BreadcrumbList",
            "itemListElement" => $itemList
        ];
    }

    // 4. Custom Page Schema (e.g. LearningResource, Product, FAQPage, Service)
    if (!empty($params['schema']) && is_array($params['schema'])) {
        $schemas[] = $params['schema'];
    }

    // Output JSON-LD Block
    foreach ($schemas as $schema) {
        echo "\n    <script type=\"application/ld+json\">\n" . 
             json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE) . 
             "\n    </script>\n";
    }
}
}
