<?php
/**
 * AcademicHub Nepal - Edit Academic Resource
 * File: admin/edit-resource.php
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth_check.php';

require_admin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

try {
    $stmt = $pdo->prepare("SELECT * FROM resources WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $resource = $stmt->fetch();

    if (!$resource) {
        set_flash_message('danger', 'Resource not found.');
        header('Location: ' . BASE_URL . '/admin/manage-resource.php');
        exit;
    }
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}

$error = '';

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

    $file_name = $resource['file_path'];
    $preview_name = $resource['preview_image'];

    // Handle new master file if uploaded
    if (isset($_FILES['resource_file']) && $_FILES['resource_file']['error'] === UPLOAD_ERR_OK) {
        $allowedExtensions = ['pdf', 'docx', 'doc', 'zip', 'pptx'];
        $fileExt = strtolower(pathinfo($_FILES['resource_file']['name'], PATHINFO_EXTENSION));

        if (in_array($fileExt, $allowedExtensions)) {
            $cleanTitle = preg_replace('/[^a-zA-Z0-9_-]/', '_', strtolower(substr($title, 0, 30)));
            $file_name = time() . '_' . $cleanTitle . '.' . $fileExt;
            move_uploaded_file($_FILES['resource_file']['tmp_name'], RESOURCE_FILE_DIR . $file_name);
        }
    }

    // Handle new preview image if uploaded
    if (isset($_FILES['preview_image']) && $_FILES['preview_image']['error'] === UPLOAD_ERR_OK) {
        $allowedImgExt = ['jpg', 'jpeg', 'png', 'webp'];
        $imgExt = strtolower(pathinfo($_FILES['preview_image']['name'], PATHINFO_EXTENSION));
        if (in_array($imgExt, $allowedImgExt)) {
            $preview_name = 'prev_' . time() . '.' . $imgExt;
            move_uploaded_file($_FILES['preview_image']['tmp_name'], PREVIEW_IMAGE_DIR . $preview_name);
        }
    }

    try {
        $update_stmt = $pdo->prepare("
            UPDATE resources 
            SET title = :title, description = :description, campus = :campus, program = :program,
                year = :year, subject = :subject, document_type = :document_type, pages = :pages,
                file_format = :file_format, price = :price, file_path = :file_path, 
                preview_image = :preview_image, featured = :featured
            WHERE id = :id
        ");
        $update_stmt->execute([
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
            ':featured' => $featured,
            ':id' => $id
        ]);

        set_flash_message('success', 'Resource details updated successfully.');
        header('Location: ' . BASE_URL . '/admin/manage-resource.php');
        exit;
    } catch (Exception $e) {
        $error = 'Update failed: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Resource #<?= $id ?> | Admin AcademicHub</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container-fluid p-0">
    <div class="row g-0">
        <?php $active_page = 'manage-resource'; require_once __DIR__ . '/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="col-md-9 col-lg-10 p-4 p-md-5">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-3 border-bottom">
                <div>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-1 small text-muted">
                            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/dashboard.php">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/manage-resource.php">Catalog</a></li>
                            <li class="breadcrumb-item active">Edit #<?= $id ?></li>
                        </ol>
                    </nav>
                    <h2 class="h3 fw-bold text-dark mb-0">Edit Academic Resource #<?= $id ?></h2>
                </div>
                <a href="<?= BASE_URL ?>/admin/manage-resource.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                    &larr; Back to Resources
                </a>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white">
                <form action="" method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Resource Title *</label>
                        <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($resource['title']) ?>" required>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Campus *</label>
                            <select name="campus" class="form-select" required>
                                <option value="Gyan Jyoti Campus" <?= $resource['campus'] === 'Gyan Jyoti Campus' ? 'selected' : '' ?>>Gyan Jyoti Campus</option>
                                <option value="Babai Campus" <?= $resource['campus'] === 'Babai Campus' ? 'selected' : '' ?>>Babai Campus</option>
                                <option value="Gorkha Campus" <?= $resource['campus'] === 'Gorkha Campus' ? 'selected' : '' ?>>Gorkha Campus</option>
                                <option value="Other Campuses" <?= $resource['campus'] === 'Other Campuses' ? 'selected' : '' ?>>Other Campus (Tribhuvan University)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Program *</label>
                            <select name="program" class="form-select" required>
                                <option value="BBS" <?= $resource['program'] === 'BBS' ? 'selected' : '' ?>>BBS</option>
                                <option value="BBA" <?= $resource['program'] === 'BBA' ? 'selected' : '' ?>>BBA</option>
                                <option value="BCA" <?= $resource['program'] === 'BCA' ? 'selected' : '' ?>>BCA</option>
                                <option value="MBS" <?= $resource['program'] === 'MBS' ? 'selected' : '' ?>>MBS</option>
                                <option value="MBA" <?= $resource['program'] === 'MBA' ? 'selected' : '' ?>>MBA</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">Year / Semester</label>
                            <input type="text" name="year" class="form-control" value="<?= htmlspecialchars($resource['year']) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">Subject</label>
                            <input type="text" name="subject" class="form-control" value="<?= htmlspecialchars($resource['subject']) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">Document Type</label>
                            <input type="text" name="document_type" class="form-control" value="<?= htmlspecialchars($resource['document_type']) ?>" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">Price (NPR)</label>
                            <input type="number" step="1" name="price" class="form-control" value="<?= (int)$resource['price'] ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">Pages</label>
                            <input type="number" name="pages" class="form-control" value="<?= $resource['pages'] ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">Format</label>
                            <input type="text" name="file_format" class="form-control" value="<?= htmlspecialchars($resource['file_format']) ?>" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted">Description</label>
                        <textarea name="description" class="form-control" rows="4" required><?= htmlspecialchars($resource['description']) ?></textarea>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded border">
                                <label class="form-label small fw-bold text-dark d-block">Replace Document File</label>
                                <input type="file" name="resource_file" class="form-control mb-1">
                                <span class="small text-muted">Current file: <code><?= htmlspecialchars($resource['file_path']) ?></code></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded border">
                                <label class="form-label small fw-bold text-dark d-block">Replace Preview Image</label>
                                <input type="file" name="preview_image" class="form-control mb-1">
                                <span class="small text-muted">Current preview: <code><?= htmlspecialchars($resource['preview_image']) ?></code></span>
                            </div>
                        </div>
                    </div>

                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" name="featured" id="featuredEdit" <?= $resource['featured'] ? 'checked' : '' ?>>
                        <label class="form-check-label small fw-semibold text-dark" for="featuredEdit">
                            Show as Featured Resource on Home Page
                        </label>
                    </div>

                    <div class="d-flex gap-3">
                        <button type="submit" class="btn btn-hub-primary px-4">
                            <i class="bi bi-save-fill me-1"></i> Save Resource Changes
                        </button>
                        <a href="<?= BASE_URL ?>/admin/manage-resource.php" class="btn btn-light border px-4">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
