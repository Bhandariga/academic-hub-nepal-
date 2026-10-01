<?php
/**
 * AcademicHub Nepal - Our Team & Faculty Mentors
 * File: team.php
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth_check.php';

// Fetch all active team members sorted by display order
try {
    $stmt = $pdo->prepare("SELECT * FROM team_members WHERE is_active = 1 ORDER BY display_order ASC, id ASC");
    $stmt->execute();
    $teamMembers = $stmt->fetchAll();
} catch (Exception $e) {
    $teamMembers = [];
}

// Dynamic SEO Configuration for Team & Leadership
$page_title = "Our Team & Academic Faculty Mentors | AcademicHub Nepal";
$page_description = "Meet the professors, research supervisors, and curriculum specialists at AcademicHub Nepal elevating student research standards across TU, Mark International, Mahendra Multiple, Deukhuri Bahumukhi, and Babai campuses.";
$page_keywords = "AcademicHub faculty, TU professors, thesis supervisors Nepal, research mentors Tribhuvan University, Mark International College, Mahendra Multiple Campus, Deukhuri Bahumukhi Campus, Babai campus faculty";
$canonical_url = BASE_URL . "/team.php";

$page_breadcrumbs = [
    ['name' => 'Home', 'url' => BASE_URL . '/index.php'],
    ['name' => 'Our Team', 'url' => $canonical_url]
];

// Schema.org AboutPage & ItemList of Faculty Members
$teamSchemaMembers = [];
foreach ($teamMembers as $idx => $m) {
    $teamSchemaMembers[] = [
        "@type" => "Person",
        "name" => $m['name'],
        "jobTitle" => $m['role_title'],
        "worksFor" => [
            "@type" => "EducationalOrganization",
            "name" => "AcademicHub Nepal"
        ],
        "description" => clean_seo_description($m['bio'] ?? '')
    ];
}

$page_schema = [
    "@context" => "https://schema.org",
    "@type" => "AboutPage",
    "name" => "About the AcademicHub Nepal Faculty & Mentors",
    "url" => $canonical_url,
    "description" => $page_description,
    "mainEntity" => [
        "@type" => "ItemList",
        "itemListElement" => $teamSchemaMembers
    ]
];

require_once __DIR__.'/includes/header.php';
require_once __DIR__.'/includes/team-cards.php';
?>
<section class="community-hero"><div class="container"><span class="eyebrow">Our people</span><h1>Guidance grounded<br>in experience.</h1><p>Meet the advisors, researchers and specialists who support your academic journey.</p><?php if(admin_has_permission('manage_team')): ?><a class="btn btn-outline-light" href="<?= BASE_URL ?>/admin/manage-team.php">Manage team</a><?php endif; ?></div></section>
<section class="container py-5"><div class="section-heading"><div><span class="eyebrow">Different disciplines. Shared purpose.</span><h2>Meet the AcademicHub team</h2></div></div>
<nav class="team-category-nav" aria-label="Filter team"><button type="button" class="btn btn-outline-primary" data-team-filter="all" aria-pressed="true">Everyone</button><?php foreach(['Executive Team','Academic Advisors','Research Consultants','Technical Team'] as $category): ?><button type="button" class="btn btn-outline-primary" data-team-filter="<?= htmlspecialchars($category) ?>" aria-pressed="false"><?= htmlspecialchars($category) ?></button><?php endforeach; ?></nav><p id="team-filter-status" class="text-muted small" role="status"><?= count($teamMembers) ?> team members</p>
<?php render_team_cards($teamMembers); ?></section>
<section class="container pb-5"><div class="society-banner"><span class="eyebrow">Academic integrity</span><h2 class="mt-3">Support for your own original work.</h2><p>Our resources and guidance are for learning and reference. Your ideas, writing and research remain your own.</p><a class="btn btn-light" href="<?= BASE_URL ?>/services.php">Explore research support</a></div></section>
<?php require __DIR__.'/includes/footer.php'; ?>
