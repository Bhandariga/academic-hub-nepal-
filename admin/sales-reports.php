<?php
/**
 * AcademicHub Nepal - Sales & Revenue Reports
 * File: admin/sales-reports.php
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth_check.php';

require_admin();
require_admin_permission('view_reports');

try {
    // 1. Sales by Campus
    $stmt_campus = $pdo->query("
        SELECT r.campus, COUNT(o.id) as orders_count, COALESCE(SUM(o.amount), 0) as total_revenue
        FROM resources r
        LEFT JOIN orders o ON r.id = o.resource_id AND o.payment_status = 'PAID'
        GROUP BY r.campus
        ORDER BY total_revenue DESC
    ");
    $campus_sales = $stmt_campus->fetchAll();

    // 2. Sales by Program
    $stmt_program = $pdo->query("
        SELECT r.program, COUNT(o.id) as orders_count, COALESCE(SUM(o.amount), 0) as total_revenue
        FROM resources r
        LEFT JOIN orders o ON r.id = o.resource_id AND o.payment_status = 'PAID'
        GROUP BY r.program
        ORDER BY total_revenue DESC
    ");
    $program_sales = $stmt_program->fetchAll();

    // 3. Top Selling Resources
    $stmt_top = $pdo->query("
        SELECT r.title, r.campus, r.program, r.price, r.downloads_count,
               COUNT(o.id) as confirmed_orders,
               COALESCE(SUM(o.amount), 0) as total_revenue
        FROM resources r
        LEFT JOIN orders o ON r.id = o.resource_id AND o.payment_status = 'PAID'
        GROUP BY r.id
        ORDER BY confirmed_orders DESC, r.downloads_count DESC
        LIMIT 10
    ");
    $top_resources = $stmt_top->fetchAll();

    // 4. Overall Totals
    $total_revenue = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM orders WHERE payment_status = 'PAID'")->fetchColumn();
    $total_orders = $pdo->query("SELECT COUNT(*) FROM orders WHERE payment_status = 'PAID'")->fetchColumn();

} catch (Exception $e) {
    die("Error generating reports: " . safe_error_message($e));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales &amp; Analytics Reports | Admin AcademicHub</title>
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
        <?php $active_page = 'sales-reports'; require_once __DIR__ . '/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="col-md-9 col-lg-10 p-4 p-md-5">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-3 border-bottom">
                <div>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-1 small text-muted">
                            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/dashboard.php">Dashboard</a></li>
                            <li class="breadcrumb-item active">Revenue &amp; Analytics</li>
                        </ol>
                    </nav>
                    <h2 class="h3 fw-bold text-dark mb-0">Sales &amp; Financial Analytics</h2>
                </div>
                <div class="badge bg-white border text-muted px-3 py-2 rounded-pill shadow-sm">
                    Total Revenue: <strong class="text-success"><?= format_npr($total_revenue) ?></strong>
                </div>
            </div>

            <!-- Aggregate Revenue Cards -->
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="admin-kpi-card bg-white">
                        <span class="text-muted small fw-bold text-uppercase d-block mb-1">Gross Realized Revenue</span>
                        <div class="display-5 fw-bold text-success mb-1"><?= format_npr($total_revenue) ?></div>
                        <span class="small text-muted"><i class="bi bi-shield-check text-success"></i> Fully reconciled via eSewa merchant gateway</span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="admin-kpi-card bg-white">
                        <span class="text-muted small fw-bold text-uppercase d-block mb-1">Total Completed Orders</span>
                        <div class="display-5 fw-bold text-primary mb-1"><?= number_format($total_orders) ?></div>
                        <span class="small text-muted"><i class="bi bi-check-all text-primary"></i> 100% digital fulfillment to student accounts</span>
                    </div>
                </div>
            </div>

            <!-- Campus & Program Breakdowns -->
            <div class="row g-4 mb-4">
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                        <h5 class="fw-bold text-dark mb-3"><i class="bi bi-buildings-fill text-primary me-2"></i>Sales by Campus</h5>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="table-light small">
                                    <tr>
                                        <th>Campus</th>
                                        <th class="text-center">Orders</th>
                                        <th class="text-end">Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($campus_sales as $cs): ?>
                                        <tr>
                                            <td class="fw-bold text-dark small"><?= htmlspecialchars($cs['campus']) ?></td>
                                            <td class="text-center"><span class="badge bg-light text-dark border"><?= $cs['orders_count'] ?></span></td>
                                            <td class="text-end fw-bold text-success"><?= format_npr($cs['total_revenue']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                        <h5 class="fw-bold text-dark mb-3"><i class="bi bi-mortarboard-fill text-info me-2"></i>Sales by Academic Program</h5>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="table-light small">
                                    <tr>
                                        <th>Program</th>
                                        <th class="text-center">Orders</th>
                                        <th class="text-end">Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($program_sales as $ps): ?>
                                        <tr>
                                            <td class="fw-bold text-dark small"><?= htmlspecialchars($ps['program']) ?></td>
                                            <td class="text-center"><span class="badge bg-light text-dark border"><?= $ps['orders_count'] ?></span></td>
                                            <td class="text-end fw-bold text-success"><?= format_npr($ps['total_revenue']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Selling Resources Table -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
                <div class="card-header bg-white p-3 border-bottom">
                    <h5 class="fw-bold text-dark mb-0"><i class="bi bi-trophy-fill text-warning me-2"></i>Top Selling Academic Reference Products</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light small">
                                <tr>
                                    <th class="ps-4">Resource Title</th>
                                    <th>Campus &amp; Program</th>
                                    <th>Unit Price</th>
                                    <th>Confirmed Orders</th>
                                    <th class="text-end pe-4">Total Realized</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($top_resources as $tr): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="fw-bold text-dark small text-truncate" style="max-width: 320px;">
                                                <?= htmlspecialchars($tr['title']) ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border small"><?= htmlspecialchars($tr['campus']) ?></span>
                                            <span class="small text-muted ms-1"><?= htmlspecialchars($tr['program']) ?></span>
                                        </td>
                                        <td class="small"><?= format_npr($tr['price']) ?></td>
                                        <td><span class="badge bg-primary rounded-pill"><?= $tr['confirmed_orders'] ?> orders</span></td>
                                        <td class="text-end pe-4 fw-bold text-success"><?= format_npr($tr['total_revenue']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/experience.js?v=5"></script>
</body>
</html>
