<?php
/**
 * AcademicHub Nepal - Resource Details Page
 * File: resource-details.php
 */
require_once __DIR__ . '/config/db.php';

$resource_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

try {
    $stmt = $pdo->prepare("SELECT * FROM resources WHERE id = :id");
    $stmt->execute([':id' => $resource_id]);
    $resource = $stmt->fetch();

    if (!$resource) {
        set_flash_message('danger', 'The requested academic resource was not found.');
        header('Location: ' . BASE_URL . '/resources.php');
        exit;
    }

    // Fetch related resources (same program or campus)
    $stmt_related = $pdo->prepare("SELECT * FROM resources WHERE (program = :program OR campus = :campus) AND id != :current_id LIMIT 3");
    $stmt_related->execute([
        ':program' => $resource['program'],
        ':campus' => $resource['campus'],
        ':current_id' => $resource['id']
    ]);
    $related_resources = $stmt_related->fetchAll();

} catch (Exception $e) {
    die("Error loading resource: " . $e->getMessage());
}

$page_title = htmlspecialchars($resource['title']) . " | AcademicHub Nepal";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php">Home</a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/resources.php">Resources</a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/resources.php?program=<?= urlencode($resource['program']) ?>"><?= htmlspecialchars($resource['program']) ?></a></li>
            <li class="breadcrumb-item active text-truncate" style="max-width: 300px;" aria-current="page"><?= htmlspecialchars($resource['title']) ?></li>
        </ol>
    </nav>

    <div class="row g-4">
        <!-- Main Content Column -->
        <div class="col-lg-8">
            <div class="bg-white p-4 p-md-5 rounded-3 border shadow-sm mb-4">
                <!-- Badges -->
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <span class="badge bg-primary px-3 py-2 rounded-pill"><i class="bi bi-buildings-fill me-1"></i> <?= htmlspecialchars($resource['campus']) ?></span>
                    <span class="badge bg-info text-dark px-3 py-2 rounded-pill"><i class="bi bi-mortarboard-fill me-1"></i> <?= htmlspecialchars($resource['program']) ?></span>
                    <span class="badge bg-secondary px-3 py-2 rounded-pill"><?= htmlspecialchars($resource['year']) ?></span>
                    <span class="badge bg-dark px-3 py-2 rounded-pill"><?= htmlspecialchars($resource['document_type']) ?></span>
                </div>

                <!-- Resource Title -->
                <h1 class="h2 fw-bold text-primary mb-3">
                    <?= htmlspecialchars($resource['title']) ?>
                </h1>

                <!-- Subject & Meta Bar -->
                <div class="d-flex flex-wrap align-items-center gap-4 text-muted small pb-3 mb-4 border-bottom">
                    <div><i class="bi bi-book-half text-secondary me-1"></i> <strong>Subject:</strong> <?= htmlspecialchars($resource['subject']) ?></div>
                    <div><i class="bi bi-file-earmark-text text-secondary me-1"></i> <strong>Length:</strong> <?= $resource['pages'] ?> Pages</div>
                    <div><i class="bi bi-filetype-pdf text-secondary me-1"></i> <strong>Format:</strong> <?= htmlspecialchars($resource['file_format']) ?></div>
                    <div><i class="bi bi-arrow-down-circle text-secondary me-1"></i> <strong>Downloads:</strong> <?= $resource['downloads_count'] ?>+ times</div>
                </div>

                <!-- Comprehensive Description -->
                <h4 class="fw-bold text-dark mb-3">About This Reference Resource</h4>
                <div class="text-secondary leading-relaxed mb-4" style="font-size: 1.05rem;">
                    <?= nl2br(htmlspecialchars($resource['description'])) ?>
                </div>

                <!-- Chapters Breakdown -->
                <div class="card bg-light border-0 rounded-3 p-4 mb-4">
                    <h5 class="fw-bold text-primary mb-3"><i class="bi bi-list-check me-2"></i> Structured Contents Included:</h5>
                    <ul class="list-unstyled mb-0">
                        <li class="py-2 border-bottom d-flex align-items-center"><i class="bi bi-check-circle-fill text-success me-2"></i> <strong>Preliminaries:</strong> Letter of Approval, Viva Sheet, Declaration, Acknowledgements, Table of Contents</li>
                        <li class="py-2 border-bottom d-flex align-items-center"><i class="bi bi-check-circle-fill text-success me-2"></i> <strong>Chapter I:</strong> Introduction, Statement of Problem, Objectives, Significance &amp; Limitations</li>
                        <li class="py-2 border-bottom d-flex align-items-center"><i class="bi bi-check-circle-fill text-success me-2"></i> <strong>Chapter II:</strong> Literature Review, Theoretical Framework &amp; Review of Recent Nepalese Studies</li>
                        <li class="py-2 border-bottom d-flex align-items-center"><i class="bi bi-check-circle-fill text-success me-2"></i> <strong>Chapter III:</strong> Research Methodology, Design, Sampling Plan &amp; Reliability Tests</li>
                        <li class="py-2 border-bottom d-flex align-items-center"><i class="bi bi-check-circle-fill text-success me-2"></i> <strong>Chapter IV:</strong> Data Presentation, SPSS / Statistical Analysis &amp; Empirical Findings</li>
                        <li class="py-2 d-flex align-items-center"><i class="bi bi-check-circle-fill text-success me-2"></i> <strong>Chapter V:</strong> Summary, Conclusions, Actionable Recommendations &amp; APA 7 Bibliography</li>
                    </ul>
                </div>

                <!-- Embedded Watermarked Sample Preview Box -->
                <h4 class="fw-bold text-dark mb-3"><i class="bi bi-eye-fill text-secondary me-2"></i> Sample Preview (Pages 1–5)</h4>
                <div class="preview-watermark-container border bg-white shadow-sm p-4 mb-4">
                    <div class="preview-watermark-overlay">SAMPLE REFERENCE COPY</div>
                    <div class="text-center pb-3 border-bottom mb-3">
                        <span class="badge bg-light text-dark border mb-2"><?= htmlspecialchars($resource['campus']) ?></span>
                        <h4 class="fw-bold text-primary"><?= htmlspecialchars($resource['title']) ?></h4>
                        <div class="text-muted small">Faculty of Management &bull; <?= htmlspecialchars($resource['program']) ?></div>
                    </div>
                    
                    <div class="p-3 bg-light rounded text-muted small mb-3">
                        <strong>Preview Note:</strong> This preview displays verified layout, typography hierarchy, and chapter structuring. The full editable file is immediately unlocked upon checkout.
                    </div>

                    <div class="border p-3 rounded bg-white font-monospace small text-secondary">
                        <p class="mb-1"><strong>1.1 Background of the Study:</strong></p>
                        <p class="mb-2">In recent decades, digital banking services have revolutionized the financial ecosystem across developing economies. In Nepal, the transition from traditional branch banking to mobile and digital wallet platforms has accelerated customer expectations regarding transaction speed, data security, and convenience...</p>
                        <p class="mb-1"><strong>1.2 Statement of the Problem:</strong></p>
                        <p class="mb-0">While banks continue to invest heavily in financial technology infrastructure, empirical inquiry into whether these advancements directly correlate with customer retention across private commercial banks remains crucial...</p>
                    </div>
                </div>

                <!-- Academic Integrity Warning Notice -->
                <div class="academic-trust-box">
                    <h6 class="fw-bold mb-1"><i class="bi bi-shield-exclamation text-primary me-2"></i>Academic Integrity &amp; Ethical Notice</h6>
                    <p class="mb-0 small text-dark">
                        This material is offered strictly for learning, reference benchmarking, and thesis structural guidance. Students are required to independently conduct their own fieldwork, surveys, and write original academic submissions in full accordance with their campus and university policies.
                    </p>
                </div>
            </div>
        </div>

        <!-- Sidebar Purchase Card -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-3 p-4 sticky-top" style="top: 90px;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="badge bg-success bg-opacity-10 text-success fw-bold px-3 py-2 rounded-pill">
                        <i class="bi bi-shield-check me-1"></i> Instant Delivery
                    </span>
                    <span class="text-muted small">ID: #RES-<?= str_pad($resource['id'], 4, '0', STR_PAD_LEFT) ?></span>
                </div>

                <div class="mb-4">
                    <span class="text-muted small text-uppercase fw-semibold d-block">Instant Access Price</span>
                    <div class="d-flex align-items-baseline gap-2">
                        <span class="display-5 fw-bold text-primary"><?= format_npr($resource['price']) ?></span>
                        <span class="text-decoration-line-through text-muted small">NPR <?= number_format($resource['price'] + 300, 0) ?></span>
                    </div>
                </div>

                <!-- Purchase Action Button -->
                <div class="d-grid gap-2 mb-3">
                    <a href="<?= BASE_URL ?>/payment.php?resource_id=<?= $resource['id'] ?>" class="btn btn-esewa btn-lg fw-bold shadow-sm">
                        <i class="bi bi-wallet2 fs-5 me-1"></i> Buy Now with eSewa
                    </a>
                </div>

                <p class="text-center text-muted small mb-4">
                    <i class="bi bi-lock-fill text-success me-1"></i> Secure 256-bit eSewa UAT Sandbox Demo
                </p>

                <!-- What You Get Guarantee List -->
                <div class="border-top pt-3">
                    <h6 class="fw-bold text-dark mb-3">Package Includes:</h6>
                    <ul class="list-unstyled small text-muted mb-0">
                        <li class="mb-2 d-flex align-items-center"><i class="bi bi-check-lg text-success fw-bold me-2"></i> Complete <?= $resource['pages'] ?> pages clean <?= htmlspecialchars($resource['file_format']) ?> document</li>
                        <li class="mb-2 d-flex align-items-center"><i class="bi bi-check-lg text-success fw-bold me-2"></i> Standard TU margin &amp; font pre-formatted</li>
                        <li class="mb-2 d-flex align-items-center"><i class="bi bi-check-lg text-success fw-bold me-2"></i> Pre-tested questionnaire &amp; methodology guidelines</li>
                        <li class="mb-2 d-flex align-items-center"><i class="bi bi-check-lg text-success fw-bold me-2"></i> Lifetime access under your Student Account</li>
                        <li class="d-flex align-items-center"><i class="bi bi-check-lg text-success fw-bold me-2"></i> 100% money-back guarantee for technical defects</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Related Resources Section -->
    <?php if (!empty($related_resources)): ?>
        <div class="mt-5 pt-4 border-top">
            <h3 class="fw-bold text-primary mb-4">Related Academic Resources</h3>
            <div class="row g-4">
                <?php foreach ($related_resources as $rel): ?>
                    <div class="col-md-4">
                        <div class="resource-card">
                            <div class="resource-cover" style="height: 140px;">
                                <span class="resource-badge-campus"><?= htmlspecialchars($rel['campus']) ?></span>
                                <i class="bi bi-file-earmark-text-fill display-5 opacity-75"></i>
                            </div>
                            <div class="resource-body">
                                <div class="resource-category"><?= htmlspecialchars($rel['program']) ?> &bull; <?= htmlspecialchars($rel['subject']) ?></div>
                                <h4 class="resource-title fs-6">
                                    <a href="<?= BASE_URL ?>/resource-details.php?id=<?= $rel['id'] ?>"><?= htmlspecialchars($rel['title']) ?></a>
                                </h4>
                                <div class="resource-footer">
                                    <span class="resource-price"><?= format_npr($rel['price']) ?></span>
                                    <a href="<?= BASE_URL ?>/resource-details.php?id=<?= $rel['id'] ?>" class="btn btn-sm btn-outline-primary">View</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
