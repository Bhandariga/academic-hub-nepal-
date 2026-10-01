<?php
/**
 * AcademicHub Nepal - Executive Academic Catalog Management Console
 * File: admin/manage-resource.php
 * Strictly restricted to Super Administrators & Authorized Admins
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth_check.php';

require_admin();
require_admin_permission('manage_resources');

$is_sa = is_superadmin();
$current_admin_id = $_SESSION['user_id'];
$active_page = 'manage-resource';

// ---------------------------------------------------------
// POST ACTIONS (Resource Delete & Toggle Featured)
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        set_flash_message('danger', 'Security validation expired. Action aborted.');
        header('Location: ' . BASE_URL . '/admin/manage-resource.php');
        exit;
    }

    $action = $_POST['action'] ?? '';

    // 1. DELETE RESOURCE
    if ($action === 'delete') {
        if (!admin_has_permission('manage_resources') && !$is_sa) {
            set_flash_message('danger', 'You do not have permission to delete catalog resources.');
            header('Location: ' . BASE_URL . '/admin/manage-resource.php');
            exit;
        }

        $del_id = (int)($_POST['id'] ?? 0);
        if ($del_id > 0) {
            try {
                $stmt_fetch = $pdo->prepare("SELECT title, file_path, preview_image FROM resources WHERE id = :id");
                $stmt_fetch->execute([':id' => $del_id]);
                $res = $stmt_fetch->fetch();

                if ($res) {
                    // Optionally unlink files if stored in uploads
                    if (!empty($res['preview_image'])) {
                        $prevPath = dirname(__DIR__) . '/uploads/previews/' . $res['preview_image'];
                        if (file_exists($prevPath)) @unlink($prevPath);
                    }
                    if (!empty($res['file_path'])) {
                        $docPath = dirname(__DIR__) . '/uploads/resources/' . $res['file_path'];
                        if (file_exists($docPath)) @unlink($docPath);
                    }

                    $stmt_del = $pdo->prepare("DELETE FROM resources WHERE id = :id");
                    $stmt_del->execute([':id' => $del_id]);

                    log_admin_activity($pdo, 'DELETE_RESOURCE', "Deleted resource #{$del_id} ('{$res['title']}') from catalog", 'resource', $del_id);
                    set_flash_message('success', "Resource #{$del_id} ('{$res['title']}') was permanently removed.");
                } else {
                    set_flash_message('warning', 'Resource not found or already deleted.');
                }
            } catch (Exception $e) {
                set_flash_message('danger', 'Failed to delete resource: ' . safe_error_message($e));
            }
        }
        header('Location: ' . BASE_URL . '/admin/manage-resource.php');
        exit;
    }

    // 2. TOGGLE FEATURED
    if ($action === 'toggle_featured') {
        $id = (int)($_POST['id'] ?? 0);
        $new_val = (int)($_POST['is_featured'] ?? 0);

        try {
            $stmt = $pdo->prepare("SELECT title FROM resources WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $title = $stmt->fetchColumn();

            if ($title) {
                $upd = $pdo->prepare("UPDATE resources SET is_featured = :val WHERE id = :id");
                $upd->execute([':val' => $new_val, ':id' => $id]);

                $label = $new_val === 1 ? 'featured on homepage' : 'unfeatured from homepage';
                log_admin_activity($pdo, 'TOGGLE_FEATURED_RESOURCE', "Resource #{$id} ('{$title}') {$label}", 'resource', $id);
                set_flash_message('success', "Resource '{$title}' is now {$label}.");
            }
        } catch (Exception $e) {
            set_flash_message('danger', 'Failed to update featured status: ' . safe_error_message($e));
        }
        header('Location: ' . BASE_URL . '/admin/manage-resource.php');
        exit;
    }
}

// ---------------------------------------------------------
// QUERY DATA FOR DIRECTORY
// ---------------------------------------------------------
try {
    $stmt = $pdo->query("
        SELECT r.*, 
               COUNT(DISTINCT CASE WHEN o.payment_status = 'PAID' THEN o.id END) as paid_orders_count,
               COALESCE(SUM(CASE WHEN o.payment_status = 'PAID' THEN o.amount ELSE 0 END), 0) as total_earnings
        FROM resources r
        LEFT JOIN orders o ON r.id = o.resource_id
        GROUP BY r.id
        ORDER BY r.display_order ASC, r.id DESC
    ");
    $resources = $stmt->fetchAll();

    // Summary Metric Aggregations
    $totalCount = count($resources);
    $totalDownloads = array_sum(array_column($resources, 'downloads_count'));
    $totalEarnings = array_sum(array_column($resources, 'total_earnings'));
    $featuredCount = count(array_filter($resources, fn($r) => (int)($r['is_featured'] ?? 0) === 1));

    // Get list of unique campuses and programs for dynamic filters
    $campuses = array_unique(array_filter(array_column($resources, 'campus')));
    $programs = array_unique(array_filter(array_column($resources, 'program')));
    $docTypes = array_unique(array_filter(array_column($resources, 'document_type')));
} catch (Exception $e) {
    die("Error loading resources: " . safe_error_message($e));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Academic Catalog Management | AcademicHub Nepal</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css?v=<?= time() ?>" rel="stylesheet">
    <style>
    body {
        font-family: 'Plus Jakarta Sans', sans-serif;
        background-color: #f8fafc;
    }
    .table-enterprise {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }
    .table-enterprise thead th {
        background-color: #f8fafc !important;
        color: #475569 !important;
        font-size: 0.74rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        padding: 14px 18px;
        border-bottom: 2px solid #e2e8f0 !important;
        white-space: nowrap;
    }
    .table-enterprise tbody tr {
        background: #ffffff;
        transition: background-color 0.15s ease;
    }
    .table-enterprise tbody tr:hover {
        background-color: #f8fafc !important;
    }
    .table-enterprise tbody td {
        padding: 16px 18px;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
    }
    .format-badge-pdf {
        background-color: #fee2e2;
        color: #dc2626;
        border: 1px solid #fecaca;
        font-weight: 700;
        font-size: 0.72rem;
        padding: 3px 8px;
        border-radius: 6px;
    }
    .format-badge-docx {
        background-color: #dbeafe;
        color: #2563eb;
        border: 1px solid #bfdbfe;
        font-weight: 700;
        font-size: 0.72rem;
        padding: 3px 8px;
        border-radius: 6px;
    }
    .format-badge-zip {
        background-color: #fef3c7;
        color: #d97706;
        border: 1px solid #fde68a;
        font-weight: 700;
        font-size: 0.72rem;
        padding: 3px 8px;
        border-radius: 6px;
    }
    .pulse-dot {
        display: inline-block;
        border-radius: 50%;
        animation: pulseGlow 2s infinite;
    }
    .pulse-warning {
        background-color: #f59e0b;
        box-shadow: 0 0 0 rgba(245, 158, 11, 0.4);
    }
    @keyframes pulseGlow {
        0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.7); }
        70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(245, 158, 11, 0); }
        100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(245, 158, 11, 0); }
    }
    </style>
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
        <!-- Sidebar Navigation -->
        <?php require_once __DIR__ . '/sidebar.php'; ?>

        <!-- Main Content Area -->
        <div class="col-md-9 col-lg-10 p-3 p-md-4 p-xl-5">
            
            <!-- Breadcrumb & Top Bar -->
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-3 border-bottom gap-3">
                <div>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-1 small text-muted">
                            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/dashboard.php">Dashboard</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Manage Resources</li>
                        </ol>
                    </nav>
                    <h2 class="h3 fw-bold text-dark mb-0">
                        <i class="bi bi-journals text-primary me-2"></i> Academic Catalog Management
                    </h2>
                    <p class="text-muted small mb-0">Maintain verified research reports, formatting guides, project models, and digital course resources.</p>
                </div>

                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3 shadow-sm d-inline-flex align-items-center gap-1" onclick="exportTableToCSV('resourceTable', 'academic_catalog.csv')">
                        <i class="bi bi-file-earmark-spreadsheet text-success"></i> Export CSV
                    </button>
                    <a href="<?= BASE_URL ?>/admin/add-resource.php" class="btn btn-hub-primary btn-sm rounded-pill px-4 shadow-sm d-inline-flex align-items-center gap-2 fw-semibold">
                        <i class="bi bi-plus-circle-fill"></i> Add New Resource
                    </a>
                </div>
            </div>

            <!-- Flash Alert Notice -->
            <?php display_flash_message(); ?>

            <!-- Metric KPI Summary Cards -->
            <div class="row g-3 mb-4">
                <div class="col-sm-6 col-xl-3">
                    <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-primary border-4">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted small fw-semibold d-block">Catalog Items</span>
                                <h3 class="fw-bold mb-0 text-dark"><?= number_format($totalCount) ?></h3>
                                <span class="small text-muted" style="font-size: 0.72rem;">Published resources</span>
                            </div>
                            <div class="rounded-circle bg-primary bg-opacity-10 text-primary p-3 fs-3 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                                <i class="bi bi-journal-bookmark-fill"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-xl-3">
                    <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-success border-4">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted small fw-semibold d-block">Verified Downloads</span>
                                <h3 class="fw-bold mb-0 text-success"><?= number_format($totalDownloads) ?></h3>
                                <span class="small text-success d-inline-flex align-items-center gap-1" style="font-size: 0.72rem;">
                                    <i class="bi bi-graph-up-arrow"></i> Across all campuses
                                </span>
                            </div>
                            <div class="rounded-circle bg-success bg-opacity-10 text-success p-3 fs-3 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                                <i class="bi bi-download"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-xl-3">
                    <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-warning border-4">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted small fw-semibold d-block">Catalog Sales Revenue</span>
                                <h3 class="fw-bold mb-0 text-warning"><?= format_npr($totalEarnings) ?></h3>
                                <span class="small text-muted" style="font-size: 0.72rem;">Generated from downloads</span>
                            </div>
                            <div class="rounded-circle bg-warning bg-opacity-10 text-warning p-3 fs-3 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                                <i class="bi bi-cash-stack"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-xl-3">
                    <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-info border-4">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted small fw-semibold d-block">Featured On Homepage</span>
                                <h3 class="fw-bold mb-0 text-info"><?= number_format($featuredCount) ?></h3>
                                <span class="small text-muted" style="font-size: 0.72rem;">Showcased to students</span>
                            </div>
                            <div class="rounded-circle bg-info bg-opacity-10 text-info p-3 fs-3 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                                <i class="bi bi-star-fill text-warning"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search, Filter & Quick Options Card -->
            <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
                <div class="card-body p-3">
                    <div class="row g-2 align-items-center">
                        <!-- Live Search Input -->
                        <div class="col-md-4">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                                <input type="text" id="resourceLiveSearch" data-table-search="resourceTable" class="form-control bg-light border-start-0 ps-0" placeholder="Filter by title, subject, campus...">
                            </div>
                        </div>

                        <!-- Campus Filter -->
                        <div class="col-md-2 col-sm-6">
                            <select class="form-select form-select-sm bg-light" id="filterCampus" onchange="applyResourceFilters()">
                                <option value="">All Campuses</option>
                                <?php foreach ($campuses as $camp): ?>
                                    <option value="<?= htmlspecialchars($camp) ?>"><?= htmlspecialchars($camp) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Program Filter -->
                        <div class="col-md-2 col-sm-6">
                            <select class="form-select form-select-sm bg-light" id="filterProgram" onchange="applyResourceFilters()">
                                <option value="">All Programs</option>
                                <?php foreach ($programs as $prog): ?>
                                    <option value="<?= htmlspecialchars($prog) ?>"><?= htmlspecialchars($prog) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Document Type Filter -->
                        <div class="col-md-2 col-sm-6">
                            <select class="form-select form-select-sm bg-light" id="filterDocType" onchange="applyResourceFilters()">
                                <option value="">All Types</option>
                                <?php foreach ($docTypes as $dt): ?>
                                    <option value="<?= htmlspecialchars($dt) ?>"><?= htmlspecialchars($dt) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Visibility Filter -->
                        <div class="col-md-2 col-sm-6">
                            <select class="form-select form-select-sm bg-light" id="filterVisibility" onchange="applyResourceFilters()">
                                <option value="">All Visibility</option>
                                <option value="public">Public</option>
                                <option value="private">Private</option>
                                <option value="purchase_required">Purchase Required</option>
                                <option value="approval_required">Approval Required</option>
                            </select>
                        </div>

                        <!-- Status Filter -->
                        <div class="col-md-2 col-sm-6">
                            <select class="form-select form-select-sm bg-light" id="filterStatus" onchange="applyResourceFilters()">
                                <option value="">All Status</option>
                                <option value="published">Published</option>
                                <option value="draft">Draft</option>
                                <option value="hidden">Hidden</option>
                                <option value="archived">Archived</option>
                            </select>
                        </div>

                        <!-- Counter Badge -->
                        <div class="col-md-2 col-sm-6 text-md-end">
                            <span class="badge bg-light text-dark border px-3 py-2 rounded-pill small">
                                Showing <strong class="text-primary" id="visibleResourceCount"><?= $totalCount ?></strong> of <?= $totalCount ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Enterprise Catalog Table Card -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-5">
                <div class="table-responsive">
                    <table class="table table-enterprise table-hover align-middle mb-0" id="resourceTable">
                        <thead>
                            <tr>
                                <th class="ps-4" style="width: 80px;">Item ID</th>
                                <th style="min-width: 280px;">Resource Title &amp; Document Type</th>
                                <th style="width: 170px;">Campus &amp; Program</th>
                                <th style="width: 160px;">Subject &amp; Year</th>
                                <th style="width: 130px;">Format &amp; Pages</th>
                                <th style="width: 110px;">Price</th>
                                <th style="width: 110px;">Sales Count</th>
                                <th class="text-end pe-4" style="width: 140px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($resources)): ?>
                                <tr class="no-search">
                                    <td colspan="8" class="text-center py-5 text-muted">
                                        <i class="bi bi-journal-x fs-1 d-block mb-2 text-secondary"></i>
                                        No academic resources found in the catalog.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($resources as $r): 
                                    $fmt = strtoupper($r['file_format'] ?? 'PDF');
                                    $badgeClass = 'format-badge-pdf';
                                    if (in_array($fmt, ['DOCX', 'DOC'])) $badgeClass = 'format-badge-docx';
                                    elseif (in_array($fmt, ['ZIP', 'RAR'])) $badgeClass = 'format-badge-zip';
                                    $isFeatured = (int)($r['is_featured'] ?? 0) === 1;
                                ?>
                                    <tr data-campus="<?= htmlspecialchars($r['campus']) ?>" data-program="<?= htmlspecialchars($r['program']) ?>" data-doctype="<?= htmlspecialchars($r['document_type']) ?>" data-visibility="<?= htmlspecialchars($r['visibility'] ?? 'public') ?>" data-status="<?= htmlspecialchars($r['status'] ?? 'published') ?>">
                                        <!-- ID -->
                                        <td class="ps-4">
                                            <span class="badge bg-light text-muted border font-monospace px-2 py-1">
                                                #<?= str_pad($r['id'], 3, '0', STR_PAD_LEFT) ?>
                                            </span>
                                        </td>

                                        <!-- Title & Type -->
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="rounded-3 p-2 bg-light border d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; flex-shrink: 0;">
                                                    <i class="bi <?= in_array($fmt, ['DOCX', 'DOC']) ? 'bi-file-earmark-word text-primary' : 'bi-file-earmark-pdf text-danger' ?> fs-5"></i>
                                                </div>
                                                <div style="min-width: 0;">
                                                    <div class="fw-bold text-dark text-truncate" style="max-width: 320px;">
                                                        <a href="<?= BASE_URL ?>/resource-details.php?id=<?= $r['id'] ?>" target="_blank" class="text-dark text-decoration-none hover-primary" title="<?= htmlspecialchars($r['title']) ?>">
                                                            <?= htmlspecialchars($r['title']) ?>
                                                        </a>
                                                    </div>
                                                    <div class="d-flex align-items-center gap-2 mt-1">
                                                        <span class="badge bg-light text-secondary border px-2 py-0" style="font-size: 0.72rem;">
                                                            <?= htmlspecialchars($r['document_type']) ?>
                                                        </span>
                                                        <?php if ($isFeatured): ?>
                                                            <span class="badge bg-warning bg-opacity-25 text-dark border border-warning border-opacity-50 px-2 py-0" style="font-size: 0.68rem;">
                                                                <i class="bi bi-star-fill text-warning me-1"></i>Featured
                                                            </span>
                                                        <?php endif; ?>
                                                        <?php
                                                        $vis = $r['visibility'] ?? 'public';
                                                        $visBadge = 'bg-success';
                                                        if ($vis === 'private') $visBadge = 'bg-secondary';
                                                        elseif ($vis === 'purchase_required') $visBadge = 'bg-info';
                                                        elseif ($vis === 'approval_required') $visBadge = 'bg-warning';
                                                        ?>
                                                        <span class="badge <?= $visBadge ?> bg-opacity-10 text-dark border px-2 py-0" style="font-size: 0.68rem;">
                                                            <?= strtoupper($vis) ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Campus & Program -->
                                        <td>
                                            <span class="badge bg-light text-secondary border px-2 py-1 d-inline-flex align-items-center gap-1 mb-1" style="font-size: 0.75rem;">
                                                <i class="bi bi-buildings"></i> <?= htmlspecialchars($r['campus']) ?>
                                            </span>
                                            <div class="fw-bold text-primary small d-flex align-items-center gap-1">
                                                <i class="bi bi-mortarboard"></i> <?= htmlspecialchars($r['program']) ?>
                                            </div>
                                        </td>

                                        <!-- Subject & Year -->
                                        <td>
                                            <div class="fw-semibold text-dark small text-truncate" style="max-width: 150px;">
                                                <?= htmlspecialchars($r['subject']) ?>
                                            </div>
                                            <div class="text-muted small" style="font-size: 0.75rem;">
                                                <?= htmlspecialchars($r['year']) ?>
                                            </div>
                                        </td>

                                        <!-- Format & Pages -->
                                        <td>
                                            <span class="<?= $badgeClass ?>">
                                                <?= htmlspecialchars($fmt) ?>
                                            </span>
                                            <span class="text-muted small ms-1" style="font-size: 0.78rem;">
                                                <?= (int)$r['pages'] ?> Pgs
                                            </span>
                                        </td>

                                        <!-- Price -->
                                        <td>
                                            <div class="fw-bold text-dark font-monospace" style="font-size: 0.92rem;">
                                                <?= format_npr($r['price']) ?>
                                            </div>
                                        </td>

                                        <!-- Sales Count -->
                                        <td>
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 fw-semibold px-2 py-1 rounded-pill d-inline-flex align-items-center gap-1" style="font-size: 0.78rem;">
                                                <i class="bi bi-check2-circle"></i> <?= $r['downloads_count'] ?> sales
                                            </span>
                                        </td>

                                        <!-- Actions -->
                                        <td class="text-end pe-4">
                                            <div class="btn-group btn-group-sm shadow-sm rounded-pill overflow-hidden border">
                                                <!-- View Public Page -->
                                                <a href="<?= BASE_URL ?>/resource-details.php?id=<?= $r['id'] ?>" target="_blank" class="btn btn-light px-2" title="View Public Page">
                                                    <i class="bi bi-eye text-muted"></i>
                                                </a>
                                                <!-- Edit Resource -->
                                                <a href="<?= BASE_URL ?>/admin/edit-resource.php?id=<?= $r['id'] ?>" class="btn btn-light px-2" title="Edit Resource Details">
                                                    <i class="bi bi-pencil text-primary"></i>
                                                </a>
                                                <!-- Toggle Featured -->
                                                <form action="" method="POST" class="d-inline">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="action" value="toggle_featured">
                                                    <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                                    <input type="hidden" name="is_featured" value="<?= $isFeatured ? 0 : 1 ?>">
                                                    <button type="submit" class="btn btn-light px-2 border-0" title="<?= $isFeatured ? 'Remove from Homepage Featured' : 'Feature on Homepage' ?>">
                                                        <i class="bi <?= $isFeatured ? 'bi-star-fill text-warning' : 'bi-star text-muted' ?>"></i>
                                                    </button>
                                                </form>
                                                <!-- Delete -->
                                                <button type="button" class="btn btn-light px-2" onclick="confirmDeleteResource(<?= $r['id'] ?>, '<?= htmlspecialchars(addslashes($r['title'])) ?>')" title="Delete Resource">
                                                    <i class="bi bi-trash text-danger"></i>
                                                </button>
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

<!-- ================= DELETE CONFIRMATION MODAL ================= -->
<div class="modal fade" id="deleteResourceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="p-4 text-center">
                <div class="rounded-circle bg-danger bg-opacity-10 text-danger mx-auto mb-3 d-flex align-items-center justify-content-center shadow-xs" style="width: 56px; height: 56px;">
                    <i class="bi bi-exclamation-triangle-fill fs-3"></i>
                </div>
                <h5 class="fw-bold text-dark mb-1">Delete Resource?</h5>
                <p class="small text-muted mb-3">Permanently remove <strong id="delResourceTitle"></strong>? All student downloads will be disabled.</p>
                
                <form action="" method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="delResourceId" value="">
                    <div class="d-flex justify-content-center gap-2">
                        <button type="button" class="btn btn-light btn-sm rounded-pill px-3 border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger btn-sm rounded-pill px-3 fw-semibold">Confirm Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
<script>
// Confirm Delete Modal Trigger
function confirmDeleteResource(id, title) {
    document.getElementById('delResourceId').value = id;
    document.getElementById('delResourceTitle').innerText = title;
    var modal = new bootstrap.Modal(document.getElementById('deleteResourceModal'));
    modal.show();
}

// Multi-attribute Table Filter
function applyResourceFilters() {
    var campusFilter = (document.getElementById('filterCampus').value || '').toLowerCase();
    var programFilter = (document.getElementById('filterProgram').value || '').toLowerCase();
    var docTypeFilter = (document.getElementById('filterDocType').value || '').toLowerCase();
    var visibilityFilter = (document.getElementById('filterVisibility').value || '').toLowerCase();
    var statusFilter = (document.getElementById('filterStatus').value || '').toLowerCase();

    var rows = document.querySelectorAll('#resourceTable tbody tr:not(.no-search)');
    var visibleCount = 0;

    rows.forEach(function(row) {
        var rowCampus = (row.getAttribute('data-campus') || '').toLowerCase();
        var rowProgram = (row.getAttribute('data-program') || '').toLowerCase();
        var rowDocType = (row.getAttribute('data-doctype') || '').toLowerCase();
        var rowVisibility = (row.getAttribute('data-visibility') || '').toLowerCase();
        var rowStatus = (row.getAttribute('data-status') || '').toLowerCase();

        var matchesCampus = !campusFilter || rowCampus === campusFilter;
        var matchesProgram = !programFilter || rowProgram === programFilter;
        var matchesDocType = !docTypeFilter || rowDocType === docTypeFilter;
        var matchesVisibility = !visibilityFilter || rowVisibility === visibilityFilter;
        var matchesStatus = !statusFilter || rowStatus === statusFilter;

        if (matchesCampus && matchesProgram && matchesDocType && matchesVisibility && matchesStatus) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    var countBadge = document.getElementById('visibleResourceCount');
    if (countBadge) {
        countBadge.innerText = visibleCount;
    }
}
</script>
<script src="<?= BASE_URL ?>/assets/js/experience.js?v=5"></script>
</body>
</html>
