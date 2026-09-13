<?php
/**
 * AcademicHub Nepal - Home Page
 * File: index.php
 */
$page_title = "AcademicHub Nepal | Academic Resources & Research Support Platform";
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/header.php';

// Fetch Featured Resources from Database
try {
    $stmt = $pdo->prepare("SELECT * FROM resources WHERE featured = 1 ORDER BY id ASC LIMIT 6");
    $stmt->execute();
    $featured_resources = $stmt->fetchAll();
    
    // If no featured explicitly tagged, grab latest 6
    if (empty($featured_resources)) {
        $stmt = $pdo->query("SELECT * FROM resources ORDER BY id DESC LIMIT 6");
        $featured_resources = $stmt->fetchAll();
    }
} catch (Exception $e) {
    $featured_resources = [];
}
?>

<!-- 1. HERO SECTION -->
<section class="hero-section">
    <div class="container text-center text-lg-start">
        <div class="row align-items-center py-4">
            <div class="col-lg-8 mx-auto text-center">
                <span class="badge bg-info text-dark px-3 py-2 rounded-pill fw-bold mb-3 shadow-sm">
                    <i class="bi bi-patch-check-fill me-1"></i> Tribhuvan University & Nepal Campus Specialized
                </span>
                <h1 class="hero-title">
                    Find Academic Resources For Your Campus &amp; Program
                </h1>
                <p class="hero-subtitle mx-auto">
                    Project Reports, Research Materials, Templates and Academic Support for Nepalese Students
                </p>

                <!-- Prominent Search Bar -->
                <form action="<?= BASE_URL ?>/resources.php" method="GET" class="hero-search-box mx-auto text-start">
                    <i class="bi bi-search text-muted fs-4 ms-2"></i>
                    <input type="text" name="search" class="form-control" placeholder="Search BBS Project Report, Banking, Finance..." aria-label="Search Resources">
                    <button type="submit" class="btn btn-hub-primary rounded-3 px-4">
                        Search Resources
                    </button>
                </form>

                <!-- Quick Trending Pills -->
                <div class="d-flex flex-wrap justify-content-center gap-2 mt-4 text-light small opacity-90">
                    <span class="text-white-50">Popular:</span>
                    <a href="<?= BASE_URL ?>/resources.php?program=BBS&year=Fourth+Year" class="badge bg-white bg-opacity-10 text-white text-decoration-none py-2 px-3 rounded-pill">BBS 4th Year Finance</a>
                    <a href="<?= BASE_URL ?>/resources.php?campus=Gyan+Jyoti+Campus" class="badge bg-white bg-opacity-10 text-white text-decoration-none py-2 px-3 rounded-pill">Gyan Jyoti Campus</a>
                    <a href="<?= BASE_URL ?>/resources.php?document_type=Questionnaire" class="badge bg-white bg-opacity-10 text-white text-decoration-none py-2 px-3 rounded-pill">Likert Questionnaires</a>
                    <a href="<?= BASE_URL ?>/resources.php?program=BCA" class="badge bg-white bg-opacity-10 text-white text-decoration-none py-2 px-3 rounded-pill">BCA Documentation</a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 2. STATISTICS SECTION -->
<div class="container stats-bar">
    <div class="row g-3">
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-number">5,000+</div>
                <div class="stat-label">Active Students</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-number">1,000+</div>
                <div class="stat-label">Academic Resources</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-number">50+</div>
                <div class="stat-label">Affiliated Campuses</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-number">10+</div>
                <div class="stat-label">Degree Programs</div>
            </div>
        </div>
    </div>
</div>

<!-- 3. CAMPUS SECTION -->
<section id="campuses" class="py-5 mt-4">
    <div class="container">
        <div class="text-center mb-5">
            <span class="text-secondary fw-bold text-uppercase small letter-spacing-1">Campus-Specific Structure</span>
            <h2 class="fw-bold mt-1">Explore Resources by Campus</h2>
            <p class="text-muted mx-auto" style="max-width: 600px;">
                Find project guidelines, approved formats, and benchmark reports aligned specifically with your college syllabus.
            </p>
        </div>

        <div class="row g-4">
            <div class="col-6 col-lg-3">
                <a href="<?= BASE_URL ?>/resources.php?campus=Gyan+Jyoti+Campus" class="campus-card">
                    <div class="campus-icon">
                        <i class="bi bi-buildings-fill"></i>
                    </div>
                    <h5 class="fw-bold mb-1">Gyan Jyoti Campus</h5>
                    <p class="small text-muted mb-0">BBS, BBA &amp; MBS Guidelines</p>
                    <span class="badge bg-light text-primary mt-3 border">View Resources &rarr;</span>
                </a>
            </div>
            <div class="col-6 col-lg-3">
                <a href="<?= BASE_URL ?>/resources.php?campus=Babai+Campus" class="campus-card">
                    <div class="campus-icon">
                        <i class="bi bi-bank2"></i>
                    </div>
                    <h5 class="fw-bold mb-1">Babai Campus</h5>
                    <p class="small text-muted mb-0">Management &amp; Finance Reports</p>
                    <span class="badge bg-light text-primary mt-3 border">View Resources &rarr;</span>
                </a>
            </div>
            <div class="col-6 col-lg-3">
                <a href="<?= BASE_URL ?>/resources.php?campus=Gorkha+Campus" class="campus-card">
                    <div class="campus-icon">
                        <i class="bi bi-mortarboard-fill"></i>
                    </div>
                    <h5 class="fw-bold mb-1">Gorkha Campus</h5>
                    <p class="small text-muted mb-0">BBS &amp; BCA Technical Reports</p>
                    <span class="badge bg-light text-primary mt-3 border">View Resources &rarr;</span>
                </a>
            </div>
            <div class="col-6 col-lg-3">
                <a href="<?= BASE_URL ?>/resources.php?campus=Other+Campuses" class="campus-card">
                    <div class="campus-icon">
                        <i class="bi bi-globe2"></i>
                    </div>
                    <h5 class="fw-bold mb-1">Other Campuses</h5>
                    <p class="small text-muted mb-0">TU Affiliated Colleges Nationwide</p>
                    <span class="badge bg-light text-primary mt-3 border">View Resources &rarr;</span>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- 4. PROGRAM SECTION -->
<section id="programs" class="py-5 bg-white border-top border-bottom">
    <div class="container">
        <div class="text-center mb-5">
            <span class="text-secondary fw-bold text-uppercase small">Undergraduate &amp; Graduate</span>
            <h2 class="fw-bold mt-1">Select Your Academic Program</h2>
            <p class="text-muted">Targeted reference materials categorized by faculty and semester.</p>
        </div>

        <div class="row g-4">
            <div class="col-md col-6">
                <a href="<?= BASE_URL ?>/resources.php?program=BBS" class="program-card">
                    <div class="program-icon"><i class="bi bi-graph-up-arrow"></i></div>
                    <h5 class="fw-bold mb-0">BBS</h5>
                    <span class="small text-muted">Bachelor of Business Studies</span>
                </a>
            </div>
            <div class="col-md col-6">
                <a href="<?= BASE_URL ?>/resources.php?program=BBA" class="program-card">
                    <div class="program-icon"><i class="bi bi-briefcase-fill"></i></div>
                    <h5 class="fw-bold mb-0">BBA</h5>
                    <span class="small text-muted">Business Administration</span>
                </a>
            </div>
            <div class="col-md col-6">
                <a href="<?= BASE_URL ?>/resources.php?program=BCA" class="program-card">
                    <div class="program-icon"><i class="bi bi-code-slash"></i></div>
                    <h5 class="fw-bold mb-0">BCA</h5>
                    <span class="small text-muted">Computer Application</span>
                </a>
            </div>
            <div class="col-md col-6">
                <a href="<?= BASE_URL ?>/resources.php?program=MBS" class="program-card">
                    <div class="program-icon"><i class="bi bi-award-fill"></i></div>
                    <h5 class="fw-bold mb-0">MBS</h5>
                    <span class="small text-muted">Master of Business Studies</span>
                </a>
            </div>
            <div class="col-md col-6">
                <a href="<?= BASE_URL ?>/resources.php?program=MBA" class="program-card">
                    <div class="program-icon"><i class="bi bi-compass-fill"></i></div>
                    <h5 class="fw-bold mb-0">MBA</h5>
                    <span class="small text-muted">Graduate Business</span>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- 5. FEATURED RESOURCE SECTION -->
<section class="py-5">
    <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-end mb-4">
            <div>
                <span class="text-secondary fw-bold text-uppercase small">Curated Quality</span>
                <h2 class="fw-bold mb-0">Featured Academic Resources</h2>
            </div>
            <a href="<?= BASE_URL ?>/resources.php" class="btn btn-outline-primary fw-semibold rounded-pill px-4 mt-2 mt-sm-0">
                View All Catalog (<?= count($featured_resources) ?>+) &rarr;
            </a>
        </div>

        <div class="row g-4">
            <?php foreach ($featured_resources as $res): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="resource-card">
                        <div class="resource-cover">
                            <span class="resource-badge-format"><?= htmlspecialchars($res['file_format']) ?> &bull; <?= $res['pages'] ?> Pgs</span>
                            <span class="resource-badge-campus"><i class="bi bi-geo-alt-fill me-1"></i><?= htmlspecialchars($res['campus']) ?></span>
                            <div class="p-3">
                                <i class="bi <?= $res['file_format'] === 'PDF' ? 'bi-file-earmark-pdf-fill text-danger' : 'bi-file-earmark-word-fill text-info' ?> display-4 opacity-75"></i>
                            </div>
                        </div>

                        <div class="resource-body">
                            <div class="resource-category">
                                <?= htmlspecialchars($res['program']) ?> &bull; <?= htmlspecialchars($res['subject']) ?>
                            </div>
                            <h3 class="resource-title">
                                <a href="<?= BASE_URL ?>/resource-details.php?id=<?= $res['id'] ?>">
                                    <?= htmlspecialchars($res['title']) ?>
                                </a>
                            </h3>
                            
                            <div class="resource-meta">
                                <span><i class="bi bi-calendar3 me-1"></i><?= htmlspecialchars($res['year']) ?></span>
                                <span><i class="bi bi-tag-fill me-1"></i><?= htmlspecialchars($res['document_type']) ?></span>
                            </div>

                            <p class="small text-muted mb-3" style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                                <?= htmlspecialchars($res['description']) ?>
                            </p>

                            <div class="resource-footer">
                                <div>
                                    <span class="text-muted small d-block">Price</span>
                                    <span class="resource-price"><?= format_npr($res['price']) ?></span>
                                </div>
                                <div class="d-flex gap-2">
                                    <a href="<?= BASE_URL ?>/resource-details.php?id=<?= $res['id'] ?>" class="btn btn-sm btn-outline-secondary rounded-2">
                                        Details
                                    </a>
                                    <a href="<?= BASE_URL ?>/payment.php?resource_id=<?= $res['id'] ?>" class="btn btn-sm btn-hub-primary rounded-2">
                                        <i class="bi bi-cart-fill me-1"></i> Buy Now
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- HOW IT WORKS SECTION -->
<section class="py-5 bg-white border-top">
    <div class="container">
        <div class="text-center mb-5">
            <span class="text-secondary fw-bold text-uppercase small">Simple &amp; Secure</span>
            <h2 class="fw-bold mt-1">How AcademicHub Works</h2>
            <p class="text-muted">Four transparent steps from discovery to verified download.</p>
        </div>

        <div class="row g-4 text-center">
            <div class="col-md-3">
                <div class="p-3">
                    <div class="hub-icon bg-primary text-white mx-auto mb-3" style="width:54px;height:54px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.5rem;">
                        1
                    </div>
                    <h5 class="fw-bold">Search &amp; Filter</h5>
                    <p class="small text-muted">Locate benchmark reports by your specific campus, program, and year.</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-3">
                    <div class="hub-icon bg-secondary text-white mx-auto mb-3" style="width:54px;height:54px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.5rem;">
                        2
                    </div>
                    <h5 class="fw-bold">Preview Sample</h5>
                    <p class="small text-muted">Review table of contents, structure, and sample pages before purchasing.</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-3">
                    <div class="hub-icon bg-success text-white mx-auto mb-3" style="width:54px;height:54px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.5rem;">
                        3
                    </div>
                    <h5 class="fw-bold">eSewa ePay Demo</h5>
                    <p class="small text-muted">Fast, secure sandbox payment with instant HMAC-SHA256 signature verification.</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-3">
                    <div class="hub-icon bg-dark text-white mx-auto mb-3" style="width:54px;height:54px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.5rem;">
                        4
                    </div>
                    <h5 class="fw-bold">Instant Download</h5>
                    <p class="small text-muted">Access protected full files immediately in your Student Dashboard.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 6. SERVICES SECTION -->
<section id="services" class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <span class="text-secondary fw-bold text-uppercase small">Professional Academic Assistance</span>
            <h2 class="fw-bold mt-1">Custom Academic &amp; Research Support</h2>
            <p class="text-muted mx-auto" style="max-width: 650px;">
                Get hands-on mentorship and technical formatting support tailored to your original student research.
            </p>
        </div>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="service-card">
                    <div class="service-icon-box">
                        <i class="bi bi-file-text-fill"></i>
                    </div>
                    <h4 class="fw-bold mb-2">APA Formatting</h4>
                    <p class="small text-muted mb-4">
                        Standardize citations, bibliography, headings, table and figure formatting strictly conforming to the APA 7th Edition style manual.
                    </p>
                    <a href="<?= BASE_URL ?>/services.php?type=APA+Formatting" class="btn btn-sm btn-outline-primary rounded-pill">Request Service &rarr;</a>
                </div>
            </div>

            <div class="col-md-4">
                <div class="service-card">
                    <div class="service-icon-box">
                        <i class="bi bi-layout-text-window-reverse"></i>
                    </div>
                    <h4 class="fw-bold mb-2">Report Formatting</h4>
                    <p class="small text-muted mb-4">
                        Full layout alignment with campus font guidelines, preliminary page numbering (Roman), standard margins, and automated Table of Contents.
                    </p>
                    <a href="<?= BASE_URL ?>/services.php?type=Report+Formatting" class="btn btn-sm btn-outline-primary rounded-pill">Request Service &rarr;</a>
                </div>
            </div>

            <div class="col-md-4">
                <div class="service-card">
                    <div class="service-icon-box">
                        <i class="bi bi-compass-fill"></i>
                    </div>
                    <h4 class="fw-bold mb-2">Research Guidance</h4>
                    <p class="small text-muted mb-4">
                        One-on-one methodology consultation covering research question formulation, conceptual frameworks, hypothesis structuring, and scope.
                    </p>
                    <a href="<?= BASE_URL ?>/services.php?type=Research+Guidance" class="btn btn-sm btn-outline-primary rounded-pill">Request Service &rarr;</a>
                </div>
            </div>

            <div class="col-md-4">
                <div class="service-card">
                    <div class="service-icon-box">
                        <i class="bi bi-check2-circle"></i>
                    </div>
                    <h4 class="fw-bold mb-2">Proposal Review</h4>
                    <p class="small text-muted mb-4">
                        Rigorous academic review of your draft proposal with corrective feedback on literature review coherence and feasibility before supervisor submission.
                    </p>
                    <a href="<?= BASE_URL ?>/services.php?type=Proposal+Review" class="btn btn-sm btn-outline-primary rounded-pill">Request Service &rarr;</a>
                </div>
            </div>

            <div class="col-md-4">
                <div class="service-card">
                    <div class="service-icon-box">
                        <i class="bi bi-ui-checks"></i>
                    </div>
                    <h4 class="fw-bold mb-2">Questionnaire Design</h4>
                    <p class="small text-muted mb-4">
                        Formulation of validated 5-point Likert survey instruments, interview schedules, and pilot-test reliability frameworks for field data collection.
                    </p>
                    <a href="<?= BASE_URL ?>/services.php?type=Questionnaire+Design" class="btn btn-sm btn-outline-primary rounded-pill">Request Service &rarr;</a>
                </div>
            </div>

            <div class="col-md-4">
                <div class="service-card">
                    <div class="service-icon-box">
                        <i class="bi bi-pie-chart-fill"></i>
                    </div>
                    <h4 class="fw-bold mb-2">Data Analysis Support</h4>
                    <p class="small text-muted mb-4">
                        Statistical guidance on SPSS, descriptive statistics, Pearson correlation, multiple linear regression, and presentation of findings.
                    </p>
                    <a href="<?= BASE_URL ?>/services.php?type=Data+Analysis+Support" class="btn btn-sm btn-outline-primary rounded-pill">Request Service &rarr;</a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- TRUST & INTEGRITY PLEDGE SECTION -->
<div class="container my-5">
    <div class="academic-trust-box shadow-sm">
        <div class="d-flex align-items-center gap-3">
            <i class="bi bi-shield-check display-6 text-primary"></i>
            <div>
                <h5 class="fw-bold text-primary mb-1">Academic Integrity &amp; Ethical Learning Commitment</h5>
                <p class="mb-0 text-dark small">
                    All materials on AcademicHub Nepal are strictly distributed for <strong>learning, structural formatting, and reference guidance</strong>. We champion authentic scholarship: students must write and defend their own original work in accordance with university codes of conduct.
                </p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
