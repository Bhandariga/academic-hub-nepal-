<?php
/**
 * AcademicHub Nepal - Resources Catalog & Search Module
 * File: resources.php
 */
require_once __DIR__ . '/config/db.php';

// Capture Filter Parameters
$search = trim($_GET['search'] ?? '');
$campus = trim($_GET['campus'] ?? '');
$program = trim($_GET['program'] ?? '');
$year = trim($_GET['year'] ?? '');
$subject = trim($_GET['subject'] ?? '');
$document_type = trim($_GET['document_type'] ?? '');
$sort = trim($_GET['sort'] ?? 'newest');

// Build Dynamic SQL Query
$sql = "SELECT * FROM resources WHERE visibility = 'public' AND status = 'published'";
$params = [];

if (!empty($search)) {
    $sql .= " AND (LOWER(title) LIKE LOWER(:search) OR LOWER(description) LIKE LOWER(:search_desc) OR LOWER(subject) LIKE LOWER(:search_subj) OR LOWER(program) LIKE LOWER(:search_prog) OR LOWER(campus) LIKE LOWER(:search_campus) OR LOWER(keywords) LIKE LOWER(:search_keys))";
    $searchPattern = "%{$search}%";
    $params[':search'] = $searchPattern;
    $params[':search_desc'] = $searchPattern;
    $params[':search_subj'] = $searchPattern;
    $params[':search_prog'] = $searchPattern;
    $params[':search_campus'] = $searchPattern;
    $params[':search_keys'] = $searchPattern;
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

$max_price = filter_input(INPUT_GET, 'max_price', FILTER_VALIDATE_FLOAT);
if ($max_price !== false && $max_price !== null && $max_price >= 0) {
    $sql .= ' AND price <= :max_price';
    $params[':max_price'] = $max_price;
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
$campuses = ['Mark International College', 'Mahendra Multiple Campus', 'Deukhuri Bahumukhi Campus', 'Babai Campus', 'Gorkha Campus', 'Other Campuses'];
$programs = ['BBS', 'BBA', 'MBA', 'MBS'];
$years = ['First Year', 'Second Year', 'Third Year', 'Fourth Year'];
$subjects = ['Finance', 'Marketing', 'Accounting', 'Management', 'Strategic Management & Finance', 'Computer Science'];
$doc_types = ['Project Report', 'Research Proposal', 'Questionnaire', 'Presentation', 'Template'];

// Dynamic SEO Resolution for Catalog
if (!empty($search)) {
    $page_title = "Search: " . htmlspecialchars($search) . " - Academic Resources | AcademicHub Nepal";
    $page_description = "Explore search results for '" . htmlspecialchars($search) . "' across Tribhuvan University MBA, BBS, BBA, MBS project reports and research templates.";
} elseif (!empty($program) && !empty($campus)) {
    $page_title = htmlspecialchars($program) . " Resources & Project Reports - " . htmlspecialchars($campus) . " | AcademicHub Nepal";
    $page_description = "Browse verified " . htmlspecialchars($program) . " academic resources, project reports, and syllabus notes for " . htmlspecialchars($campus) . ".";
} elseif (!empty($program)) {
    $page_title = htmlspecialchars($program) . " Project Reports, Dissertations & Templates | AcademicHub Nepal";
    $page_description = "Download verified " . htmlspecialchars($program) . " research projects, graduate dissertations, internship reports, and syllabus templates aligned with Tribhuvan University curriculum.";
} elseif (!empty($document_type)) {
    $page_title = htmlspecialchars($document_type) . " Samples & Templates | AcademicHub Nepal";
    $page_description = "Find verified " . htmlspecialchars($document_type) . " formats, guidelines, and samples for MBA, BBS, BBA, and MBS degrees in Nepal.";
} else {
    $page_title = "Browse Academic Resources, MBA Dissertations & Project Reports | AcademicHub Nepal";
    $page_description = "Access Tribhuvan University MBA, BBS, BBA, MBS project reports, research templates, APA 7 guides, and syllabus notes in Nepal.";
}

$canonical_url = BASE_URL . '/resources.php' . (!empty($program) ? '?program=' . urlencode($program) : '');
$page_keywords = "TU academic resources, MBA dissertation Nepal, BBS project report, BBA internship report Nepal, Ma In Societies system documentation, MBS thesis, Tribhuvan University notes, Mark International College, Mahendra Multiple Campus, Deukhuri Bahumukhi Campus, Babai Campus";

$page_breadcrumbs = [
    ['name' => 'Home', 'url' => BASE_URL . '/index.php'],
    ['name' => 'Resources Catalog', 'url' => BASE_URL . '/resources.php']
];
if (!empty($program)) {
    $page_breadcrumbs[] = ['name' => $program, 'url' => $canonical_url];
}

require_once __DIR__ . '/includes/header.php';
?>

<section class="studio-catalog-header">
 <div class="container">
  <span class="studio-kicker">THE ACADEMIC LIBRARY</span>
  <h1>A stronger starting point.</h1>
  <p>Explore research references, project reports and practical guides for your next academic milestone.</p>
  <form action="<?= BASE_URL ?>/resources.php" method="get" class="studio-search" role="search" id="searchForm">
   <div class="studio-search-line position-relative">
    <i class="bi bi-search" aria-hidden="true"></i>
    <input type="search" name="search" id="searchInput" value="<?= htmlspecialchars($search) ?>" placeholder="Search topics, titles, programs, or subjects" aria-label="Search resources" autocomplete="off">
    <button class="btn btn-primary" type="submit">Search <i class="bi bi-arrow-right"></i></button>
    <div id="searchSuggestions" class="search-suggestions-dropdown"></div>
   </div>
   <?php foreach (['campus','program','year','subject','document_type','max_price','sort'] as $filter_key): if (isset($_GET[$filter_key]) && is_string($_GET[$filter_key])): ?><input type="hidden" name="<?= $filter_key ?>" value="<?= htmlspecialchars($_GET[$filter_key]) ?>"><?php endif; endforeach; ?>
  </form>
  <div class="studio-category-chips" aria-label="Filter by program"><a class="<?= empty($program) ? 'active' : '' ?>" href="<?= BASE_URL ?>/resources.php">All programs</a><?php foreach (['BBS','BBA','Ma In Societies','MBS','MBA'] as $program_chip): ?><a class="<?= $program === $program_chip ? 'active' : '' ?>" href="<?= BASE_URL ?>/resources.php?program=<?= $program_chip ?>"><?= $program_chip ?></a><?php endforeach; ?></div>
 </div>
</section>
<div class="container py-2">
    <!-- Breadcrumb & Page Heading -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php" class="text-decoration-none">Home</a></li>
            <li class="breadcrumb-item active" aria-current="page">Resources Catalog</li>
        </ol>
    </nav>

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <h2 class="h4 fw-bold mb-1 text-dark" style="font-family: 'Outfit', sans-serif;">Academic Resource Catalog</h2>
            <p class="text-muted small mb-0">Showing <?= count($resources) ?> verified reference materials and templates</p>
        </div>
        
        <!-- Search reset if filters applied -->
        <?php if (!empty($search) || !empty($campus) || !empty($program) || !empty($year) || !empty($subject) || !empty($document_type)): ?>
            <a href="<?= BASE_URL ?>/resources.php" class="btn btn-sm btn-outline-danger rounded-pill px-3 shadow-xs">
                <i class="bi bi-x-circle me-1"></i> Clear All Filters
            </a>
        <?php endif; ?>
    </div>

    <div class="row g-4">
        <!-- Filters Sidebar -->
        <div class="col-lg-3">
            <button class="btn btn-primary d-lg-none w-100 mb-3" type="button" data-bs-toggle="offcanvas" data-bs-target="#catalogFilters" aria-controls="catalogFilters"><i class="bi bi-funnel"></i> Search &amp; filters</button>
            <div class="filter-sidebar shadow-sm offcanvas-lg offcanvas-bottom" id="catalogFilters" tabindex="-1" aria-labelledby="catalogFiltersTitle">
                <div class="d-lg-none d-flex justify-content-between mb-3"><strong id="catalogFiltersTitle">Refine your search</strong><button class="btn-close" type="button" data-bs-dismiss="offcanvas" data-bs-target="#catalogFilters" aria-label="Close filters"></button></div>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0 text-primary" style="font-family: 'Outfit', sans-serif;"><i class="bi bi-funnel-fill me-1"></i> Filter Catalog</h5>
                    <a href="<?= BASE_URL ?>/resources.php" class="small text-muted text-decoration-none">Reset</a>
                </div>

                <form action="<?= BASE_URL ?>/resources.php" method="GET" id="filterForm">
                    <!-- Keyword Search -->
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.05em;">Keyword Search</label>
                        <div class="input-group input-group-sm">
                            <input type="text" name="search" class="form-control" placeholder="E.g. Banking, Risk, SPSS..." value="<?= htmlspecialchars($search) ?>">
                            <button class="btn btn-hub-secondary" type="submit"><i class="bi bi-search"></i></button>
                        </div>
                    </div>

                    <!-- Campus Filter -->
                    <div class="mb-4">
                        <label class="filter-group-title d-block">Affiliated Campus</label>
                        <select name="campus" class="form-select form-select-sm">
                            <option value="">All Campuses</option>
                            <option value="Gyan Jyoti Campus" <?= $campus === 'Gyan Jyoti Campus' ? 'selected' : '' ?>>Gyan Jyoti Campus</option>
                            <?php foreach ($campuses as $c): ?>
                                <option value="<?= htmlspecialchars($c) ?>" <?= $campus === $c ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Program Filter -->
                    <div class="mb-4">
                        <label class="filter-group-title d-block">Academic Program</label>
                        <select name="program" class="form-select form-select-sm">
                            <option value="">All Programs</option>
                            <option value="Ma In Societies" <?= $program === 'Ma In Societies' ? 'selected' : '' ?>>Ma In Societies</option>
                            <?php foreach ($programs as $p): ?>
                                <option value="<?= htmlspecialchars($p) ?>" <?= $program === $p ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($p) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Year/Semester Filter -->
                    <div class="mb-4">
                        <label class="filter-group-title d-block">Year / Semester</label>
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

                    <div class="mb-4"><label for="max-price" class="filter-group-title">Maximum price (NPR)</label><input id="max-price" class="form-control" type="number" name="max_price" min="0" step="1" placeholder="Any price" value="<?= htmlspecialchars((string)($max_price === false ? '' : $max_price)) ?>"></div>
                    <!-- Preserve Sort Option -->
                    <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">

                    <button type="submit" class="btn btn-hub-secondary btn-sm w-100 mt-2 rounded-pill fw-semibold py-2 shadow-xs">
                        <i class="bi bi-funnel me-1"></i> Apply Filters
                    </button>
                </form>
            </div>
        </div>

        <!-- Resource Listing Grid -->
        <div class="col-lg-9">
            <!-- Active Filters & Sorting Bar -->
            <div class="d-flex flex-wrap justify-content-between align-items-center bg-white p-3 rounded-3 border mb-4 shadow-xs">
                <div class="small text-muted">
                    Showing <strong class="text-dark"><?= count($resources) ?></strong> verified academic resources
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
                <div class="text-center py-5 bg-white rounded-4 border shadow-sm p-5">
                    <i class="bi bi-search display-3 text-muted opacity-50 mb-3 d-block"></i>
                    <h4 class="fw-bold text-dark" style="font-family: 'Outfit', sans-serif;">No resources matched your criteria</h4>
                    <p class="text-muted small mb-3">Try removing some filters or searching with different keywords.</p>
                    <a href="<?= BASE_URL ?>/resources.php" class="btn btn-hub-primary rounded-pill px-4 shadow-sm">
                        View All Resources
                    </a>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($resources as $res): ?>
                        <div class="col-md-6">
                            <div class="resource-card">
                                <div class="resource-cover">
                                    <?php if (!empty($res['preview_image']) && is_file(PREVIEW_IMAGE_DIR.basename($res['preview_image']))): ?><img class="catalog-cover-image" src="<?= BASE_URL ?>/uploads/previews/<?= rawurlencode(basename($res['preview_image'])) ?>" alt="Cover of <?= htmlspecialchars($res['title']) ?>" loading="lazy"><?php endif; ?>
                                    <span class="resource-badge-format"><?= htmlspecialchars($res['file_format']) ?> &bull; <?= $res['pages'] ?> Pgs</span>
                                    <span class="resource-badge-campus"><i class="bi bi-geo-alt-fill me-1"></i><?= htmlspecialchars($res['campus']) ?></span>
                                    <div class="catalog-document p-3">
                                        <i class="bi <?= $res['file_format'] === 'PDF' ? 'bi-file-earmark-pdf-fill text-danger' : 'bi-file-earmark-word-fill text-info' ?> display-4 opacity-75"></i>
                                        <strong><?= htmlspecialchars($res['program']) ?></strong><small>ACADEMIC REFERENCE</small>
                                    </div>
                                </div>

                                <div class="resource-body">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <div class="resource-category">
                                            <?= htmlspecialchars($res['program']) ?> &bull; <?= htmlspecialchars($res['subject']) ?>
                                        </div>
                                        <div class="d-flex align-items-center gap-1 text-warning small" style="font-size: 0.75rem;">
                                            <i class="bi bi-star-fill"></i>
                                            <span class="fw-bold text-dark">4.9</span>
                                        </div>
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

                                    <p class="small text-muted mb-3" style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden; line-height: 1.55;">
                                        <?= htmlspecialchars($res['description']) ?>
                                    </p>

                                    <div class="resource-footer">
                                        <div>
                                            <span class="text-muted small d-block" style="font-size: 0.75rem;">Student Price</span>
                                            <span class="resource-price"><?= format_npr($res['price']) ?></span>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <!-- Details / Preview Trigger Button -->
                                            <a href="<?= BASE_URL ?>/resource-details.php?id=<?= $res['id'] ?>" class="btn btn-sm btn-outline-secondary rounded-pill px-3 shadow-xs" title="View Details">
                                                Details
                                            </a>
                                            <button type="button" class="btn btn-sm btn-light border rounded-pill px-2" data-bs-toggle="modal" data-bs-target="#previewModal<?= $res['id'] ?>" title="Quick Preview">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <!-- Buy Now Button -->
                                            <a href="<?= BASE_URL ?>/payment.php?resource_id=<?= $res['id'] ?>" class="btn btn-sm btn-hub-secondary rounded-pill px-3 shadow-xs">
                                                <i class="bi bi-cart-fill me-1"></i> Buy
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Sample Watermarked Preview Modal -->
                        <div class="modal fade" id="previewModal<?= $res['id'] ?>" tabindex="-1" aria-labelledby="modalLabel<?= $res['id'] ?>" aria-hidden="true">
                            <div class="modal-dialog modal-lg modal-dialog-centered">
                                <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                                    <div class="modal-header text-white p-4" style="background: linear-gradient(135deg, #0F172A 0%, #1E293B 100%);">
                                        <h5 class="modal-title fs-6 fw-bold text-white d-flex align-items-center" id="modalLabel<?= $res['id'] ?>">
                                            <i class="bi bi-file-earmark-lock-fill text-warning me-2 fs-5"></i>
                                            <span>Watermarked Preview: <?= htmlspecialchars($res['title']) ?></span>
                                        </h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body p-4 bg-light">
                                        <div class="preview-watermark-container border bg-white shadow-sm p-4 rounded-3">
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

                                            <div class="alert alert-warning py-2 px-3 small border-0 d-flex align-items-center gap-2 rounded-3">
                                                <i class="bi bi-info-circle-fill fs-5 text-warning"></i>
                                                <span>This is a protected sample preview (Pages 1–5). The complete <?= $res['pages'] ?>-page editable file will be delivered to your account upon verified payment.</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer bg-white d-flex justify-content-between p-3 border-top">
                                        <div class="fs-5 fw-bold text-primary font-heading">
                                            Price: <?= format_npr($res['price']) ?>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">Close Preview</button>
                                            <a href="<?= BASE_URL ?>/payment.php?resource_id=<?= $res['id'] ?>" class="btn btn-esewa btn-sm rounded-pill px-4">
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

<style>
.search-suggestions-dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    margin-top: 8px;
    max-height: 400px;
    overflow-y: auto;
    z-index: 1000;
    display: none;
}

.search-suggestions-dropdown.show {
    display: block;
}

.search-suggestion-item {
    padding: 12px 16px;
    border-bottom: 1px solid #f1f5f9;
    cursor: pointer;
    transition: background 0.2s;
}

.search-suggestion-item:hover {
    background: #f8fafc;
}

.search-suggestion-item:last-child {
    border-bottom: none;
}

.suggestion-title {
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 4px;
}

.suggestion-meta {
    font-size: 0.85rem;
    color: #64748b;
    display: flex;
    gap: 8px;
    align-items: center;
}

.suggestion-price {
    font-weight: 600;
    color: #0f172a;
    margin-left: auto;
}

.search-loading {
    padding: 20px;
    text-align: center;
    color: #64748b;
}

.search-no-results {
    padding: 20px;
    text-align: center;
    color: #64748b;
}
</style>

<script>
let searchTimeout;
const searchInput = document.getElementById('searchInput');
const suggestionsDropdown = document.getElementById('searchSuggestions');

if (searchInput && suggestionsDropdown) {
    searchInput.addEventListener('input', function() {
        const query = this.value.trim();
        
        clearTimeout(searchTimeout);
        
        if (query.length < 2) {
            suggestionsDropdown.classList.remove('show');
            return;
        }
        
        suggestionsDropdown.innerHTML = '<div class="search-loading"><i class="bi bi-hourglass-split"></i> Searching...</div>';
        suggestionsDropdown.classList.add('show');
        
        searchTimeout = setTimeout(function() {
            fetch('<?= BASE_URL ?>/api/search_suggestions.php?q=' + encodeURIComponent(query))
                .then(response => response.json())
                .then(data => {
                    if (data.length === 0) {
                        suggestionsDropdown.innerHTML = '<div class="search-no-results">No resources found</div>';
                    } else {
                        let html = '';
                        data.forEach(function(item) {
                            html += '<div class="search-suggestion-item" onclick="selectSuggestion(' + item.id + ')">';
                            html += '<div class="suggestion-title">' + escapeHtml(item.title) + '</div>';
                            html += '<div class="suggestion-meta">';
                            html += '<span class="badge bg-light text-dark">' + escapeHtml(item.program) + '</span>';
                            html += '<span>' + escapeHtml(item.campus) + '</span>';
                            html += '<span>' + escapeHtml(item.subject) + '</span>';
                            html += '<span class="suggestion-price">Rs. ' + item.price + '</span>';
                            html += '</div>';
                            html += '</div>';
                        });
                        suggestionsDropdown.innerHTML = html;
                    }
                })
                .catch(function() {
                    suggestionsDropdown.innerHTML = '<div class="search-no-results">Search temporarily unavailable</div>';
                });
        }, 300);
    });
    
    searchInput.addEventListener('blur', function() {
        setTimeout(function() {
            suggestionsDropdown.classList.remove('show');
        }, 200);
    });
    
    searchInput.addEventListener('focus', function() {
        if (this.value.trim().length >= 2) {
            suggestionsDropdown.classList.add('show');
        }
    });
}

function selectSuggestion(id) {
    window.location.href = '<?= BASE_URL ?>/resource-details.php?id=' + id;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
