<?php
/**
 * AcademicHub Nepal - Add Academic Resource
 * File: admin/add-resource.php
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth_check.php';

require_admin();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $campus = trim($_POST['campus'] ?? '');
    $program = trim($_POST['program'] ?? '');
    $year = trim($_POST['year'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $document_type = trim($_POST['document_type'] ?? '');
    $pages = (int)($_POST['pages'] ?? 1);
    $file_format = trim($_POST['file_format'] ?? 'PDF');
    $price = (float)($_POST['price'] ?? 0);
    $featured = isset($_POST['featured']) ? 1 : 0;

    if (empty($title) || empty($description) || empty($campus) || empty($program) || empty($subject)) {
        $error = 'Please fill in all required text and classification fields.';
    } elseif ($price < 0) {
        $error = 'Price cannot be negative.';
    } else {
        // Handle File Uploads
        $file_name = 'sample_bbs_digital_banking.pdf'; // default fallback
        $preview_name = 'preview_digital_banking.jpg'; // default fallback

        // 1. Process Protected Master Document File
        if (isset($_FILES['resource_file']) && $_FILES['resource_file']['error'] === UPLOAD_ERR_OK) {
            $allowedExtensions = ['pdf', 'docx', 'doc', 'zip', 'pptx'];
            $fileExt = strtolower(pathinfo($_FILES['resource_file']['name'], PATHINFO_EXTENSION));

            if (!in_array($fileExt, $allowedExtensions)) {
                $error = 'Invalid document format. Allowed types: ' . implode(', ', $allowedExtensions);
            } else {
                $cleanTitle = preg_replace('/[^a-zA-Z0-9_-]/', '_', strtolower(substr($title, 0, 30)));
                $file_name = time() . '_' . $cleanTitle . '.' . $fileExt;
                $targetPath = RESOURCE_FILE_DIR . $file_name;
                if (!move_uploaded_file($_FILES['resource_file']['tmp_name'], $targetPath)) {
                    $error = 'Failed to save master file to protected storage.';
                }
            }
        }

        // 2. Process Preview Image
        if (empty($error) && isset($_FILES['preview_image']) && $_FILES['preview_image']['error'] === UPLOAD_ERR_OK) {
            $allowedImgExt = ['jpg', 'jpeg', 'png', 'webp'];
            $imgExt = strtolower(pathinfo($_FILES['preview_image']['name'], PATHINFO_EXTENSION));

            if (!in_array($imgExt, $allowedImgExt)) {
                $error = 'Invalid preview image format. Allowed: JPG, PNG, WEBP.';
            } else {
                $preview_name = 'prev_' . time() . '.' . $imgExt;
                $targetImg = PREVIEW_IMAGE_DIR . $preview_name;
                move_uploaded_file($_FILES['preview_image']['tmp_name'], $targetImg);
            }
        }

        if (empty($error)) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO resources 
                    (title, description, campus, program, year, subject, document_type, pages, file_format, price, file_path, preview_image, featured, downloads_count, created_at)
                    VALUES 
                    (:title, :description, :campus, :program, :year, :subject, :document_type, :pages, :file_format, :price, :file_path, :preview_image, :featured, 0, NOW())
                ");
                $stmt->execute([
                    ':title' => $title,
                    ':description' => $description,
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
                    ':featured' => $featured
                ]);

                set_flash_message('success', 'Academic resource "' . htmlspecialchars($title) . '" has been published successfully!');
                header('Location: ' . BASE_URL . '/admin/manage-resource.php');
                exit;

            } catch (Exception $e) {
                $error = 'Database insertion failed: ' . $e->getMessage();
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
</head>
<body class="bg-light">

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
                    <div class="row g-3 mb-3">
                        <div class="col-12">
                            <label class="form-label small fw-bold text-muted">Resource Title *</label>
                            <input type="text" name="title" class="form-control" placeholder="e.g. Impact of Working Capital Management on Profitability of Commercial Banks" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Campus / College *</label>
                            <select name="campus" class="form-select" required>
                                <option value="Gyan Jyoti Campus">Gyan Jyoti Campus</option>
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
                                <option value="BCA">BCA (Bachelor in Computer Application)</option>
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
                                <input type="file" name="preview_image" class="form-control">
                                <span class="form-text small">Visible on catalog card and preview window.</span>
                            </div>
                        </div>
                    </div>

                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" name="featured" id="featuredCheck" checked>
                        <label class="form-check-label small fw-semibold text-dark" for="featuredCheck">
                            Feature this resource on the Home Page
                        </label>
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
</body>
</html>
