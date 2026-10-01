<?php
require_once __DIR__.'/config/db.php';require_once __DIR__.'/includes/community-cards.php';
$page_title='Associated Colleges & Academic Partners | AcademicHub Nepal';$page_description='Explore our college community, programs and academic connections.';
$rows=content_rows($pdo,'colleges');require __DIR__.'/includes/header.php'; ?>
<section class="community-hero"><div class="container"><span class="eyebrow">Connected through education</span><h1>A stronger academic<br>community, together.</h1><p>Discover college profiles, university affiliations and business programs across our growing network.</p><a href="<?= BASE_URL ?>/resources.php" class="btn btn-primary">Find campus resources</a></div></section>
<section class="container py-5"><div class="section-heading"><div><span class="eyebrow">Our network</span><h2>Associated Colleges / Academic Partners</h2></div></div><?php render_community_cards($pdo,'colleges',$rows); ?></section>
<?php require __DIR__.'/includes/footer.php'; ?>
