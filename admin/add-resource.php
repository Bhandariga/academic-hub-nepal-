<?php
/**
 * AcademicHub Nepal - Add Academic Resource
 * File: admin/add-resource.php
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth_check.php';

require_once __DIR__ . '/../config/uploads.php';
require_once __DIR__ . '/../config/resource_media.php';
require_admin();
require_admin_permission('manage_resources');

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        $error = 'Security session validation expired. Please re-submit.';
    } else {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $keywords = trim($_POST['keywords'] ?? '');
        $campus = trim($_POST['campus'] ?? '');
        $program = trim($_POST['program'] ?? '');
        $year = trim($_POST['year'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $document_type = trim($_POST['document_type'] ?? '');
        $pages = (int)($_POST['pages'] ?? 1);
        $file_format = trim($_POST['file_format'] ?? 'PDF');
        $price = (float)($_POST['price'] ?? 0);
        $featured = isset($_POST['featured']) ? 1 : 0;
        $visibility = trim($_POST['visibility'] ?? 'public');
        $status = trim($_POST['status'] ?? 'published');
        $display_order = (int)($_POST['display_order'] ?? 0);


    if (empty($title) || empty($description) || empty($campus) || empty($program) || empty($subject)) {
        $error = 'Please fill in all required text and classification fields.';
    } elseif ($price < 0) {
        $error = 'Price cannot be negative.';
    } else {
        $file_name = null;
        $preview_name = null;
        try {
            $file_name = store_validated_upload($_FILES['resource_file'] ?? [], RESOURCE_FILE_DIR);
            $preview_name = resource_cover($pdo,$title,$preview_name);
        } catch (RuntimeException $e) { $error = safe_error_message($e); }

        if (empty($error)) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO resources 
                    (title, description, keywords, campus, program, year, subject, document_type, pages, file_format, price, file_path, preview_image, featured, visibility, status, display_order, downloads_count, created_at)
                    VALUES 
                    (:title, :description, :keywords, :campus, :program, :year, :subject, :document_type, :pages, :file_format, :price, :file_path, :preview_image, :featured, :visibility, :status, :display_order, 0, NOW())
                ");
                $stmt->execute([
                    ':title' => $title,
                    ':description' => $description,
                    ':keywords' => $keywords,
                    ':campus' => $campus,
                    ':program' => $program,
                    ':year' => $year,
                    ':subject' => $subject,
                    ':document_type' => $document_type,
                    ':pages' => $pages,
                    ':file_format' => $file_format,
                    ':price' => $price,
                    ':file_path' => $file_name,
                    ':preview_image' => $preview_name,
                    ':featured' => $featured,
                    ':visibility' => $visibility,
                    ':status' => $status,
                    ':display_order' => $display_order
                ]);

                $resId = $pdo->lastInsertId();
                log_admin_activity($pdo, 'ADD_RESOURCE', "Uploaded resource '{$title}'", 'resource', $resId);

                set_flash_message('success', 'Academic resource "' . htmlspecialchars($title) . '" has been published successfully!');
                header('Location: ' . BASE_URL . '/admin/manage-resource.php');
                exit;

            } catch (Exception $e) {
                $error = 'Database insertion failed: ' . safe_error_message($e);
            }
        }
    }
}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Academic Resource | Admin AcademicHub</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
<link href="<?= BASE_URL ?>/assets/css/design-system.css?v=3" rel="stylesheet">
<link href="<?= BASE_URL ?>/assets/css/experience.css?v=4" rel="stylesheet">
<link href="<?= BASE_URL ?>/assets/css/studio.css?v=1" rel="stylesheet">
<link href="<?= BASE_URL ?>/assets/css/platform.css?v=2" rel="stylesheet">
<script src="<?= BASE_URL ?>/assets/js/theme.js?v=1"></script>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"><link href="<?= BASE_URL ?>/assets/css/admin-enterprise.css?v=1" rel="stylesheet"><script src="<?= BASE_URL ?>/assets/js/admin-ui.js?v=1" defer></script>
</head>
<body class="hub-admin bg-light">

<div class="container-fluid p-0">
    <div class="row g-0">
        <?php $active_page = 'add-resource'; require_once __DIR__ . '/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="col-md-9 col-lg-10 p-4 p-md-5">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-3 border-bottom">
                <div>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-1 small text-muted">
                            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/dashboard.php">Dashboard</a></li>
                            <li class="breadcrumb-item active">Add Resource</li>
                        </ol>
                    </nav>
                    <h2 class="h3 fw-bold text-dark mb-0">Publish New Academic Resource</h2>
                </div>
                <a href="<?= BASE_URL ?>/admin/manage-resource.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                    &larr; Back to Catalog
                </a>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white">
                <form action="" method="POST" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <div class="row g-3 mb-3">

                        <div class="col-12">
                            <label class="form-label small fw-bold text-muted">Resource Title *</label>
                            <input type="text" name="title" class="form-control" placeholder="e.g. Impact of Working Capital Management on Profitability of Commercial Banks" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Description *</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Provide a detailed description of the resource content, chapters, and learning outcomes" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Keywords / Tags (Optional)</label>
                        <input type="text" name="keywords" class="form-control" placeholder="e.g. finance, banking, ratio analysis, SPSS (comma-separated for better search)">
                        <div class="form-text small text-muted">Add relevant keywords to improve search visibility</div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Campus / College *</label>
                            <select name="campus" class="form-select" required>
                                <option value="Mark International College">Mark International College</option>
                                <option value="Mahendra Multiple Campus">Mahendra Multiple Campus</option>
                                <option value="Deukhuri Bahumukhi Campus">Deukhuri Bahumukhi Campus</option>
                                <option value="Babai Campus">Babai Campus</option>
                                <option value="Gorkha Campus">Gorkha Campus</option>
                                <option value="Other Campuses">Other Campus (Tribhuvan University)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Academic Program *</label>
                            <select name="program" class="form-select" required>
                                <option value="BBS">BBS (Bachelor of Business Studies)</option>
                                <option value="BBA">BBA (Bachelor of Business Administration)</option>
                                <option value="Ma In Societies">Ma In Societies (Bachelor in Computer Application)</option>
                                <option value="MBS">MBS (Master of Business Studies)</option>
                                <option value="MBA">MBA (Master of Business Administration)</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">Year / Semester *</label>
                            <select name="year" class="form-select" required>
                                <option value="First Year">First Year</option>
                                <option value="Second Year">Second Year</option>
                                <option value="Third Year">Third Year</option>
                                <option value="Fourth Year" selected>Fourth Year</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">Subject / Specialization *</label>
                            <select name="subject" class="form-select" required>
                                <option value="Finance">Finance</option>
                                <option value="Marketing">Marketing</option>
                                <option value="Accounting">Accounting</option>
                                <option value="Management">Management</option>
                                <option value="Computer Science">Computer Science</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">Document Type *</label>
                            <select name="document_type" class="form-select" required>
                                <option value="Project Report">Project Report</option>
                                <option value="Research Proposal">Research Proposal</option>
                                <option value="Questionnaire">Questionnaire</option>
                                <option value="Presentation">Presentation (PPT)</option>
                                <option value="Template">Formatting / Structural Template</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">Price in NPR (e.g. 399) *</label>
                            <div class="input-group">
                                <span class="input-group-text">NPR</span>
                                <input type="number" step="1" name="price" class="form-control" value="399" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">Total Pages *</label>
                            <input type="number" name="pages" class="form-control" value="60" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">File Format *</label>
                            <select name="file_format" class="form-select">
                                <option value="PDF">PDF</option>
                                <option value="DOCX">DOCX (Editable Word)</option>
                                <option value="ZIP">ZIP (Multi-file bundle)</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Detailed Description &amp; Abstract *</label>
                        <textarea name="description" class="form-control" rows="4" placeholder="Provide background of report, methodology, questionnaires, SPSS outputs, and chapters included..." required></textarea>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded-3 border">
                                <label class="form-label small fw-bold text-dark d-block">
                                    <i class="bi bi-file-earmark-lock-fill text-danger me-1"></i> Upload Master Paid File (PDF/DOCX)
                                </label>
                                <input type="file" name="resource_file" class="form-control">
                                <span class="form-text small">Stored securely in protected storage directory (never public).</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded-3 border">
                                <label class="form-label small fw-bold text-dark d-block">
                                    <i class="bi bi-image text-primary me-1"></i> Preview Image / Cover (Optional)
                                </label>
                                <input type="file" name="preview_image" class="form-control" accept="image/jpeg,image/png,image/webp" data-optimize-image>
                                <?php resource_media_picker($pdo); ?>
                                <span class="form-text small">Visible on catalog card and preview window.</span>
                            </div>
                        </div>
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="featured" id="featuredCheck" checked>
                        <label class="form-check-label small fw-semibold text-dark" for="featuredCheck">
                            Feature this resource on the Home Page
                        </label>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">Visibility</label>
                            <select name="visibility" class="form-select">
                                <option value="public" selected>Public (Visible to all)</option>
                                <option value="private">Private (Admin only)</option>
                                <option value="purchase_required">Purchase Required</option>
                                <option value="approval_required">Approval Required</option>
                            </select>
                            <span class="form-text small">Control who can see this resource</span>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">Status</label>
                            <select name="status" class="form-select">
                                <option value="published" selected>Published</option>
                                <option value="draft">Draft</option>
                                <option value="hidden">Hidden</option>
                                <option value="archived">Archived</option>
                            </select>
                            <span class="form-text small">Resource publication status</span>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">Display Order</label>
                            <input type="number" name="display_order" class="form-control" value="0">
                            <span class="form-text small">Lower numbers appear first</span>
                        </div>
                    </div>

                    <div class="d-flex gap-3">
                        <button type="submit" class="btn btn-hub-primary px-4 py-2 fw-semibold">
                            <i class="bi bi-cloud-arrow-up-fill me-1"></i> Publish Academic Resource
                        </button>
                        <a href="<?= BASE_URL ?>/admin/manage-resource.php" class="btn btn-light px-4 py-2 border">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/experience.js?v=5"></script>
</body>
</html>
