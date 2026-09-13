<?php
/**
 * AcademicHub Nepal - Manage Resources
 * File: admin/manage-resource.php
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth_check.php';

require_admin();

// Handle Delete Action
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $del_id = (int)$_GET['id'];
    try {
        $stmt_del = $pdo->prepare("DELETE FROM resources WHERE id = :id");
        $stmt_del->execute([':id' => $del_id]);
        set_flash_message('success', 'Resource #' . $del_id . ' was permanently deleted from catalog.');
    } catch (Exception $e) {
        set_flash_message('danger', 'Failed to delete resource: ' . $e->getMessage());
    }
    header('Location: ' . BASE_URL . '/admin/manage-resource.php');
    exit;
}

// Fetch all resources with order/download stats
try {
    $stmt = $pdo->query("
        SELECT r.*, COUNT(DISTINCT o.id) as total_orders
        FROM resources r
        LEFT JOIN orders o ON r.id = o.resource_id AND o.payment_status = 'PAID'
        GROUP BY r.id
        ORDER BY r.id DESC
    ");
    $resources = $stmt->fetchAll();
} catch (Exception $e) {
    die("Error loading resources: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Academic Resources | Admin AcademicHub</title>
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
            <?php display_flash_message(); ?>

            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-3 border-bottom">
                <div>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-1 small text-muted">
                            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/dashboard.php">Dashboard</a></li>
                            <li class="breadcrumb-item active">Manage Resources</li>
                        </ol>
                    </nav>
                    <h2 class="h3 fw-bold text-dark mb-0">Academic Catalog Management</h2>
                </div>
                <a href="<?= BASE_URL ?>/admin/add-resource.php" class="btn btn-hub-primary rounded-pill px-4 shadow-sm">
                    <i class="bi bi-plus-circle-fill me-1"></i> Add New Resource
                </a>
            </div>

            <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light small">
                                <tr>
                                    <th class="ps-4">ID</th>
                                    <th>Resource Title</th>
                                    <th>Campus &amp; Program</th>
                                    <th>Subject &amp; Year</th>
                                    <th>Format &amp; Pages</th>
                                    <th>Price</th>
                                    <th>Sales</th>
                                    <th class="text-end pe-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($resources)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-5 text-muted">No academic resources found in catalog.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($resources as $r): ?>
                                        <tr>
                                            <td class="ps-4 font-monospace text-muted small">#<?= $r['id'] ?></td>
                                            <td>
                                                <div class="fw-bold text-dark text-truncate" style="max-width: 250px;">
                                                    <a href="<?= BASE_URL ?>/resource-details.php?id=<?= $r['id'] ?>" target="_blank" class="text-dark text-decoration-none">
                                                        <?= htmlspecialchars($r['title']) ?>
                                                    </a>
                                                </div>
                                                <div class="small text-muted"><?= htmlspecialchars($r['document_type']) ?></div>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark border small"><?= htmlspecialchars($r['campus']) ?></span>
                                                <div class="small text-primary fw-semibold"><?= htmlspecialchars($r['program']) ?></div>
                                            </td>
                                            <td class="small">
                                                <div><?= htmlspecialchars($r['subject']) ?></div>
                                                <span class="text-muted"><?= htmlspecialchars($r['year']) ?></span>
                                            </td>
                                            <td class="small">
                                                <span class="badge bg-secondary"><?= htmlspecialchars($r['file_format']) ?></span>
                                                <span class="text-muted ms-1"><?= $r['pages'] ?> Pgs</span>
                                            </td>
                                            <td class="fw-bold text-dark">
                                                <?= format_npr($r['price']) ?>
                                            </td>
                                            <td class="small">
                                                <span class="badge bg-success bg-opacity-10 text-success fw-bold px-2 py-1">
                                                    <?= $r['downloads_count'] ?> sales
                                                </span>
                                            </td>
                                            <td class="text-end pe-4">
                                                <div class="btn-group btn-group-sm">
                                                    <a href="<?= BASE_URL ?>/resource-details.php?id=<?= $r['id'] ?>" target="_blank" class="btn btn-outline-secondary" title="View Public Page">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <a href="<?= BASE_URL ?>/admin/edit-resource.php?id=<?= $r['id'] ?>" class="btn btn-outline-primary" title="Edit Resource">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <a href="<?= BASE_URL ?>/admin/manage-resource.php?action=delete&id=<?= $r['id'] ?>" class="btn btn-outline-danger" onclick="return confirm('Are you sure you want to permanently delete this resource?');" title="Delete Resource">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
