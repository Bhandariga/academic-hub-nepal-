<?php
/**
 * AcademicHub Nepal - Resources Catalog & Search Module
 * File: resources.php
 */
$page_title = "Browse Academic Resources | AcademicHub Nepal";
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/header.php';

// Capture Filter Parameters
$search = trim($_GET['search'] ?? '');
$campus = trim($_GET['campus'] ?? '');
$program = trim($_GET['program'] ?? '');
$year = trim($_GET['year'] ?? '');
$subject = trim($_GET['subject'] ?? '');
$document_type = trim($_GET['document_type'] ?? '');
$sort = trim($_GET['sort'] ?? 'newest');

// Build Dynamic SQL Query
$sql = "SELECT * FROM resources WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (title LIKE :search OR description LIKE :search_desc OR subject LIKE :search_subj)";
    $params[':search'] = "%{$search}%";
    $params[':search_desc'] = "%{$search}%";
    $params[':search_subj'] = "%{$search}%";
}

if (!empty($campus)) {
    $sql .= " AND campus = :campus";
    $params[':campus'] = $campus;
}

if (!empty($program)) {
    $sql .= " AND program = :program";
    $params[':program'] = $program;
}

if (!empty($year)) {
    $sql .= " AND year = :year";
    $params[':year'] = $year;
}

if (!empty($subject)) {
    $sql .= " AND subject = :subject";
    $params[':subject'] = $subject;
}

if (!empty($document_type)) {
    $sql .= " AND document_type = :document_type";
    $params[':document_type'] = $document_type;
}

// Sorting logic
switch ($sort) {
    case 'price_low':
        $sql .= " ORDER BY price ASC";
        break;
    case 'price_high':
        $sql .= " ORDER BY price DESC";
        break;
    case 'popular':
        $sql .= " ORDER BY downloads_count DESC";
        break;
    case 'newest':
    default:
        $sql .= " ORDER BY id DESC";
        break;
}

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $resources = $stmt->fetchAll();
} catch (Exception $e) {
    $resources = [];
}

// Available Filter Options
$campuses = ['Gyan Jyoti Campus', 'Babai Campus', 'Gorkha Campus', 'Other Campuses'];
$programs = ['BBS', 'BBA', 'BCA', 'MBS', 'MBA'];
$years = ['First Year', 'Second Year', 'Third Year', 'Fourth Year'];
$subjects = ['Finance', 'Marketing', 'Accounting', 'Management', 'Computer Science'];
$doc_types = ['Project Report', 'Research Proposal', 'Questionnaire', 'Presentation', 'Template'];
?>

<div class="container py-4">
    <!-- Breadcrumb & Page Heading -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php">Home</a></li>
            <li class="breadcrumb-item active" aria-current="page">Resources Catalog</li>
        </ol>
    </nav>

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <h1 class="h3 fw-bold mb-1">Academic Resource Catalog</h1>
            <p class="text-muted small mb-0">Showing <?= count($resources) ?> verified reference materials and templates</p>
        </div>
        
        <!-- Search reset if filters applied -->
        <?php if (!empty($search) || !empty($campus) || !empty($program) || !empty($year) || !empty($subject) || !empty($document_type)): ?>
            <a href="<?= BASE_URL ?>/resources.php" class="btn btn-sm btn-outline-danger rounded-pill">
                <i class="bi bi-x-circle me-1"></i> Clear All Filters
            </a>
        <?php endif; ?>
    </div>

    <div class="row g-4">
        <!-- Filters Sidebar -->
        <div class="col-lg-3">
            <div class="filter-sidebar">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0 text-primary"><i class="bi bi-funnel-fill me-1"></i> Filters</h5>
                    <a href="<?= BASE_URL ?>/resources.php" class="small text-muted text-decoration-none">Reset</a>
                </div>

                <form action="<?= BASE_URL ?>/resources.php" method="GET" id="filterForm">
                    <!-- Keyword Search -->
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted text-uppercase">Keyword Search</label>
                        <div class="input-group input-group-sm">
                            <input type="text" name="search" class="form-control" placeholder="E.g. Banking, Risk..." value="<?= htmlspecialchars($search) ?>">
                            <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i></button>
                        </div>
                    </div>

                    <!-- Campus Filter -->
                    <div class="mb-4">
                        <label class="filter-group-title d-block">Campus</label>
                        <select name="campus" class="form-select form-select-sm">
                            <option value="">All Campuses</option>
                            <?php foreach ($campuses as $c): ?>
                                <option value="<?= htmlspecialchars($c) ?>" <?= $campus === $c ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Program Filter -->
                    <div class="mb-4">
                        <label class="filter-group-title d-block">Program</label>
                        <select name="program" class="form-select form-select-sm">
                            <option value="">All Programs</option>
                            <?php foreach ($programs as $p): ?>
                                <option value="<?= htmlspecialchars($p) ?>" <?= $program === $p ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($p) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Year/Semester Filter -->
                    <div class="mb-4">
                        <label class="filter-group-title d-block">Academic Year</label>
                        <select name="year" class="form-select form-select-sm">
                            <option value="">All Years</option>
                            <?php foreach ($years as $y): ?>
                                <option value="<?= htmlspecialchars($y) ?>" <?= $year === $y ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($y) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Subject Filter -->
                    <div class="mb-4">
                        <label class="filter-group-title d-block">Subject</label>
                        <select name="subject" class="form-select form-select-sm">
                            <option value="">All Subjects</option>
                            <?php foreach ($subjects as $s): ?>
                                <option value="<?= htmlspecialchars($s) ?>" <?= $subject === $s ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($s) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Document Type Filter -->
                    <div class="mb-4">
                        <label class="filter-group-title d-block">Document Type</label>
                        <select name="document_type" class="form-select form-select-sm">
                            <option value="">All Document Types</option>
                            <?php foreach ($doc_types as $d): ?>
                                <option value="<?= htmlspecialchars($d) ?>" <?= $document_type === $d ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($d) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Preserve Sort Option -->
                    <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">

                    <button type="submit" class="btn btn-hub-primary btn-sm w-100 mt-2">
                        <i class="bi bi-check2-circle me-1"></i> Apply Filters
                    </button>
                </form>
            </div>
        </div>

        <!-- Resource Listing Grid -->
        <div class="col-lg-9">
            <!-- Active Filters & Sorting Bar -->
            <div class="d-flex flex-wrap justify-content-between align-items-center bg-white p-3 rounded-3 border mb-4">
                <div class="small text-muted">
                    Showing <strong class="text-dark"><?= count($resources) ?></strong> items found
                </div>
                
                <div class="d-flex align-items-center gap-2">
                    <label class="small text-muted fw-semibold text-nowrap">Sort By:</label>
                    <select class="form-select form-select-sm" style="width: auto;" onchange="location = this.value;">
                        <?php
                        $baseQuery = $_GET;
                        $makeSortUrl = function($sortVal) use ($baseQuery) {
                            $baseQuery['sort'] = $sortVal;
                            return BASE_URL . '/resources.php?' . http_build_query($baseQuery);
                        };
                        ?>
                        <option value="<?= $makeSortUrl('newest') ?>" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest Uploads</option>
                        <option value="<?= $makeSortUrl('popular') ?>" <?= $sort === 'popular' ? 'selected' : '' ?>>Most Popular</option>
                        <option value="<?= $makeSortUrl('price_low') ?>" <?= $sort === 'price_low' ? 'selected' : '' ?>>Price: Low to High</option>
                        <option value="<?= $makeSortUrl('price_high') ?>" <?= $sort === 'price_high' ? 'selected' : '' ?>>Price: High to Low</option>
                    </select>
                </div>
            </div>

            <?php if (empty($resources)): ?>
                <div class="text-center py-5 bg-white rounded-3 border">
                    <i class="bi bi-search display-3 text-muted opacity-50 mb-3 d-block"></i>
                    <h4 class="fw-bold">No resources matched your criteria</h4>
                    <p class="text-muted">Try removing some filters or searching with different keywords.</p>
                    <a href="<?= BASE_URL ?>/resources.php" class="btn btn-primary rounded-pill px-4 mt-2">
                        View All Resources
                    </a>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($resources as $res): ?>
                        <div class="col-md-6">
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
                                            <!-- Preview Trigger Button -->
                                            <button type="button" class="btn btn-sm btn-outline-secondary rounded-2" data-bs-toggle="modal" data-bs-target="#previewModal<?= $res['id'] ?>">
                                                <i class="bi bi-eye me-1"></i> Preview
                                            </button>
                                            <!-- Buy Now Button -->
                                            <a href="<?= BASE_URL ?>/payment.php?resource_id=<?= $res['id'] ?>" class="btn btn-sm btn-hub-primary rounded-2">
                                                <i class="bi bi-cart-fill me-1"></i> Buy Now
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Sample Watermarked Preview Modal -->
                        <div class="modal fade" id="previewModal<?= $res['id'] ?>" tabindex="-1" aria-labelledby="modalLabel<?= $res['id'] ?>" aria-hidden="true">
                            <div class="modal-dialog modal-lg modal-dialog-centered">
                                <div class="modal-content border-0 shadow">
                                    <div class="modal-header text-white" style="background: linear-gradient(135deg, #0f2942 0%, #1e4976 100%);">
                                        <h5 class="modal-title fs-6 fw-bold text-white d-flex align-items-center" id="modalLabel<?= $res['id'] ?>">
                                            <i class="bi bi-file-earmark-lock-fill text-warning me-2 fs-5"></i>
                                            <span>Watermarked Preview: <?= htmlspecialchars($res['title']) ?></span>
                                        </h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body p-4 bg-light">
                                        <div class="preview-watermark-container border bg-white shadow-sm p-4">
                                            <div class="preview-watermark-overlay">SAMPLE REFERENCE COPY</div>
                                            
                                            <div class="text-center pb-3 border-bottom mb-4">
                                                <div class="text-uppercase fw-bold text-muted small letter-spacing-1"><?= htmlspecialchars($res['campus']) ?></div>
                                                <h4 class="fw-bold mt-1 text-primary"><?= htmlspecialchars($res['title']) ?></h4>
                                                <div class="badge bg-secondary"><?= htmlspecialchars($res['program']) ?> &bull; <?= htmlspecialchars($res['year']) ?> &bull; <?= htmlspecialchars($res['subject']) ?></div>
                                            </div>

                                            <div class="row g-3 mb-4">
                                                <div class="col-sm-6">
                                                    <div class="p-3 bg-light rounded border">
                                                        <h6 class="fw-bold text-dark mb-1">Document Abstract</h6>
                                                        <p class="small text-muted mb-0"><?= htmlspecialchars($res['description']) ?></p>
                                                    </div>
                                                </div>
                                                <div class="col-sm-6">
                                                    <div class="p-3 bg-light rounded border">
                                                        <h6 class="fw-bold text-dark mb-1">Standard Table of Contents</h6>
                                                        <ul class="small text-muted mb-0 ps-3">
                                                            <li>Chapter I: Introduction, Problem &amp; Objectives</li>
                                                            <li>Chapter II: Related Literature Review &amp; Framework</li>
                                                            <li>Chapter III: Research Methodology &amp; Tools</li>
                                                            <li>Chapter IV: Data Analysis &amp; Empirical Findings</li>
                                                            <li>Chapter V: Conclusion &amp; Managerial Implications</li>
                                                            <li>Appendices: Questionnaires &amp; Raw Output</li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="alert alert-warning py-2 px-3 small border-0 d-flex align-items-center gap-2">
                                                <i class="bi bi-info-circle-fill fs-5 text-warning"></i>
                                                <span>This is a protected sample preview (Pages 1–5). The complete <?= $res['pages'] ?>-page editable file will be delivered to your account upon verified payment.</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer bg-white d-flex justify-content-between">
                                        <div class="fs-5 fw-bold text-primary">
                                            Price: <?= format_npr($res['price']) ?>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close Preview</button>
                                            <a href="<?= BASE_URL ?>/payment.php?resource_id=<?= $res['id'] ?>" class="btn btn-esewa btn-sm">
                                                <i class="bi bi-cart-check-fill"></i> Buy Now with eSewa
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
