<?php
/**
 * AcademicHub Nepal - Manage Orders & eSewa QR Verification
 * File: admin/manage-orders.php
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth_check.php';
require_once __DIR__ . '/../config/notifications.php';

require_admin();
require_admin_permission('manage_orders');

// Handle Order Verification Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verify_csrf_token()) {
        set_flash_message('danger', 'Security validation expired.');
    } else {
        $order_id = (int)($_POST['order_id'] ?? 0);
        $action = $_POST['action'];

        if ($order_id > 0) {
            try {
                if ($action === 'approve_qr' || $action === 'mark_paid') {
                    $pdo->beginTransaction();
                    // Serialize approval and counter updates.
                    $stmt_o = $pdo->prepare("SELECT o.*, r.title as resource_title, u.name as student_name FROM orders o JOIN resources r ON o.resource_id = r.id JOIN users u ON o.user_id = u.id WHERE o.id = :id FOR UPDATE");
                    $stmt_o->execute([':id' => $order_id]);
                    $ord = $stmt_o->fetch();

                    if ($ord && $ord['payment_status'] !== 'PAID') {
                        $txn_code = !empty($ord['transaction_code']) ? $ord['transaction_code'] : ('VERIFIED-ADMIN-' . time());
                        $stmt_update = $pdo->prepare("
                            UPDATE orders 
                            SET payment_status = 'PAID', transaction_code = :txn, verified_at = NOW() 
                            WHERE id = :id
                        ");
                        $stmt_update->execute([':txn' => $txn_code, ':id' => $order_id]);

                        // Increment downloads counter if not already counted
                        if ($ord['payment_status'] !== 'PAID') {
                            $pdo->prepare("UPDATE resources SET downloads_count = downloads_count + 1 WHERE id = :rid")
                                ->execute([':rid' => $ord['resource_id']]);
                        }

                        // Notify student about payment approval
                        create_notification(
                            $pdo,
                            $ord['user_id'],
                            'Payment Approved',
                            "Your payment for '{$ord['resource_title']}' has been approved. You can now download the file.",
                            'success',
                            BASE_URL . '/resource-details.php?id=' . $ord['resource_id']
                        );

                        log_admin_activity($pdo, 'APPROVE_ORDER_PAYMENT', "Order #{$order_id} verified & approved. Resource unlocked.", 'order', $order_id);
                        set_flash_message('success', "Order #{$order_id} verified and approved successfully! Download unlocked for student.");
                    }
                    $pdo->commit();
                } elseif ($action === 'reject_order' || $action === 'mark_failed') {
                    // Fetch order to get user ID and resource title
                    $stmt_o = $pdo->prepare("SELECT o.*, r.title as resource_title FROM orders o JOIN resources r ON o.resource_id = r.id WHERE o.id = :id");
                    $stmt_o->execute([':id' => $order_id]);
                    $ord = $stmt_o->fetch();

                    $stmt_update = $pdo->prepare("UPDATE orders SET payment_status = 'FAILED' WHERE id = :id");
                    $stmt_update->execute([':id' => $order_id]);

                    // Notify student about payment rejection
                    if ($ord) {
                        create_notification(
                            $pdo,
                            $ord['user_id'],
                            'Payment Rejected',
                            "Your payment for '{$ord['resource_title']}' could not be verified. Please contact support or submit a new payment proof.",
                            'error',
                            BASE_URL . '/dashboard.php#orders'
                        );
                    }

                    log_admin_activity($pdo, 'REJECT_ORDER_PAYMENT', "Order #{$order_id} marked as FAILED / REJECTED.", 'order', $order_id);
                    set_flash_message('warning', "Order #{$order_id} has been marked as rejected.");
                } elseif ($action === 'mark_pending') {
                    $stmt_update = $pdo->prepare("UPDATE orders SET payment_status = 'PENDING' WHERE id = :id");
                    $stmt_update->execute([':id' => $order_id]);

                    log_admin_activity($pdo, 'RESET_ORDER_STATUS', "Order #{$order_id} reset to PENDING.", 'order', $order_id);
                    set_flash_message('info', "Order #{$order_id} reset to PENDING.");
                }
            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                error_log('Payment management error: ' . $e->getMessage());
                set_flash_message('danger', 'Unable to update this order. Please try again.');
            }
        }
    }
    header('Location: ' . BASE_URL . '/admin/manage-orders.php' . (!empty($_GET['status']) ? '?status=' . urlencode($_GET['status']) : ''));
    exit;
}

// Compute Financial & Operational Summary Metrics
$total_orders_count = 0;
$total_paid_revenue = 0;
$total_paid_count = 0;
$pending_qr_count = 0;

try {
    $stat_row = $pdo->query("
        SELECT 
            COUNT(*) as total_orders,
            SUM(CASE WHEN payment_status = 'PAID' THEN amount ELSE 0 END) as total_revenue,
            SUM(CASE WHEN payment_status = 'PAID' THEN 1 ELSE 0 END) as paid_orders,
            SUM(CASE WHEN payment_status = 'PENDING_VERIFICATION' THEN 1 ELSE 0 END) as pending_qr
        FROM orders
    ")->fetch();
    if ($stat_row) {
        $total_orders_count = (int)($stat_row['total_orders'] ?? 0);
        $total_paid_revenue = (float)($stat_row['total_revenue'] ?? 0);
        $total_paid_count = (int)($stat_row['paid_orders'] ?? 0);
        $pending_qr_count = (int)($stat_row['pending_qr'] ?? 0);
    }
} catch (Exception $e) {}

// Filter by Status
$filter_status = $_GET['status'] ?? '';
$whereClause = "";
$params = [];

if (!empty($filter_status) && in_array($filter_status, ['PAID', 'PENDING', 'PENDING_VERIFICATION', 'FAILED'])) {
    $whereClause = "WHERE o.payment_status = :status";
    $params[':status'] = $filter_status;
}

try {
    $stmt = $pdo->prepare("
        SELECT o.*, u.name as student_name, u.email as student_email, u.phone as student_phone,
               r.title as resource_title, r.campus, r.program, r.file_format, r.pages
        FROM orders o
        JOIN users u ON o.user_id = u.id
        JOIN resources r ON o.resource_id = r.id
        {$whereClause}
        ORDER BY 
            CASE WHEN o.payment_status = 'PENDING_VERIFICATION' THEN 1 ELSE 2 END,
            o.id DESC
    ");
    $stmt->execute($params);
    $orders = $stmt->fetchAll();
} catch (Exception $e) {
    die("Error fetching orders: " . safe_error_message($e));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders &amp; QR Payments | Admin AcademicHub</title>
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
        <?php $active_page = 'manage-orders'; require_once __DIR__ . '/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="col-md-9 col-lg-10 p-3 p-md-4 p-xl-5">
            <?php display_flash_message(); ?>

            <!-- Page Title Bar -->
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-3 border-bottom gap-3">
                <div>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-1 small text-muted">
                            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/dashboard.php">Dashboard</a></li>
                            <li class="breadcrumb-item active">Transactions &amp; QR Payments</li>
                        </ol>
                    </nav>
                    <h1 class="h3 fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-wallet2 text-success"></i>
                        <span>Orders &amp; Payment Verification</span>
                    </h1>
                </div>

                <!-- Export & Utility Actions -->
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3 shadow-sm" onclick="exportTableToCSV('ordersTable', 'orders_export.csv')">
                        <i class="bi bi-file-earmark-spreadsheet me-1 text-success"></i> Export CSV
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3 shadow-sm" onclick="window.print()">
                        <i class="bi bi-printer me-1"></i> Print
                    </button>
                    <a href="<?= BASE_URL ?>/admin/manage-orders.php<?= !empty($filter_status) ? '?status=' . urlencode($filter_status) : '' ?>" class="btn btn-light btn-sm rounded-pill px-3 border shadow-sm" title="Refresh list">
                        <i class="bi bi-arrow-clockwise"></i>
                    </a>
                </div>
            </div>

            <!-- Financial & Operational Summary KPI Cards -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-lg-3">
                    <div class="metric-kpi-card h-100">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.05em;">Total Orders</div>
                                <div class="fs-4 fw-bold text-dark mt-1"><?= number_format($total_orders_count) ?></div>
                                <span class="small text-muted">All-time transactions</span>
                            </div>
                            <div class="metric-kpi-icon bg-primary bg-opacity-10 text-primary">
                                <i class="bi bi-cart4"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-lg-3">
                    <div class="metric-kpi-card h-100">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.05em;">Verified Revenue</div>
                                <div class="fs-4 fw-bold text-success mt-1"><?= format_npr($total_paid_revenue) ?></div>
                                <span class="small text-muted">Successfully collected</span>
                            </div>
                            <div class="metric-kpi-icon bg-success bg-opacity-10 text-success">
                                <i class="bi bi-cash-stack"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-lg-3">
                    <div class="metric-kpi-card h-100 <?= $pending_qr_count > 0 ? 'border-warning' : '' ?>">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-muted small fw-semibold text-uppercase d-flex align-items-center gap-2" style="font-size: 0.72rem; letter-spacing: 0.05em;">
                                    <span>Pending QR Verify</span>
                                    <?php if ($pending_qr_count > 0): ?>
                                        <span class="pulse-dot pulse-warning"></span>
                                    <?php endif; ?>
                                </div>
                                <div class="fs-4 fw-bold <?= $pending_qr_count > 0 ? 'text-warning' : 'text-dark' ?> mt-1">
                                    <?= number_format($pending_qr_count) ?>
                                </div>
                                <span class="small text-muted">Needs admin review</span>
                            </div>
                            <div class="metric-kpi-icon bg-warning bg-opacity-10 text-warning">
                                <i class="bi bi-hourglass-split"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-lg-3">
                    <div class="metric-kpi-card h-100">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.05em;">Unlocked Resources</div>
                                <div class="fs-4 fw-bold text-info mt-1"><?= number_format($total_paid_count) ?></div>
                                <span class="small text-muted">Active student downloads</span>
                            </div>
                            <div class="metric-kpi-icon bg-info bg-opacity-10 text-info">
                                <i class="bi bi-file-earmark-lock2-fill"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Banner if there are pending QR verifications -->
            <?php if ($pending_qr_count > 0 && $filter_status !== 'PENDING_VERIFICATION'): ?>
                <div class="alert alert-warning border-warning d-flex flex-wrap align-items-center justify-content-between p-3 rounded-4 shadow-sm mb-4 gap-2">
                    <div class="d-flex align-items-center gap-3">
                        <span class="pulse-dot pulse-warning" style="width: 14px; height: 14px;"></span>
                        <div>
                            <strong class="d-block text-dark">You have <?= $pending_qr_count ?> pending eSewa QR payment(s) waiting for verification!</strong>
                            <span class="small text-muted">Students have scanned your QR code and submitted transaction codes / receipts.</span>
                        </div>
                    </div>
                    <a href="<?= BASE_URL ?>/admin/manage-orders.php?status=PENDING_VERIFICATION" class="btn btn-warning btn-sm fw-bold px-3 shadow-sm rounded-pill">
                        Review Pending Payments <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
            <?php endif; ?>

            <!-- Table Filter Toolbar & Live Search -->
            <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
                <div class="card-body p-3">
                    <div class="row g-3 align-items-center justify-content-between">
                        <!-- Live Search Input -->
                        <div class="col-md-5 col-lg-4">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                                <input type="text" id="tableLiveSearch" data-table-search="ordersTable" class="form-control bg-light border-start-0 ps-0" placeholder="Type student, email, ID, or txn code...">
                                <button class="btn btn-light border border-start-0 text-muted" type="button" onclick="document.getElementById('tableLiveSearch').value=''; document.getElementById('tableLiveSearch').dispatchEvent(new Event('input'));" title="Clear">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Status Filter Pills -->
                        <div class="col-md-7 col-lg-8 text-md-end">
                            <div class="btn-group btn-group-sm shadow-sm flex-wrap">
                                <a href="<?= BASE_URL ?>/admin/manage-orders.php" class="btn <?= empty($filter_status) ? 'btn-primary' : 'btn-outline-secondary' ?>">
                                    All Orders
                                </a>
                                <a href="<?= BASE_URL ?>/admin/manage-orders.php?status=PENDING_VERIFICATION" class="btn <?= $filter_status === 'PENDING_VERIFICATION' ? 'btn-warning fw-bold' : 'btn-outline-warning text-dark' ?>">
                                    <i class="bi bi-clock-history me-1"></i>QR Pending <?php if ($pending_qr_count > 0): ?><span class="badge bg-danger text-white ms-1"><?= $pending_qr_count ?></span><?php endif; ?>
                                </a>
                                <a href="<?= BASE_URL ?>/admin/manage-orders.php?status=PAID" class="btn <?= $filter_status === 'PAID' ? 'btn-success fw-bold' : 'btn-outline-secondary' ?>">
                                    <i class="bi bi-check-circle me-1"></i>Paid
                                </a>
                                <a href="<?= BASE_URL ?>/admin/manage-orders.php?status=PENDING" class="btn <?= $filter_status === 'PENDING' ? 'btn-secondary fw-bold' : 'btn-outline-secondary' ?>">
                                    Pending Gateway
                                </a>
                                <a href="<?= BASE_URL ?>/admin/manage-orders.php?status=FAILED" class="btn <?= $filter_status === 'FAILED' ? 'btn-danger fw-bold' : 'btn-outline-secondary' ?>">
                                    Failed / Rejected
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Orders Enterprise Data Card -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
                <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
                    <span class="fw-bold text-dark small">
                        Showing <span id="ordersTable-count" class="badge bg-primary rounded-pill"><?= count($orders) ?></span> record(s)
                    </span>
                    <span class="text-muted small">
                        <i class="bi bi-info-circle me-1"></i> Click transaction codes or phones to copy
                    </span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table-enterprise align-middle mb-0" id="ordersTable">
                            <thead>
                                <tr>
                                    <th class="ps-4">Order ID</th>
                                    <th>Student Details</th>
                                    <th>Resource Purchased</th>
                                    <th>Amount</th>
                                    <th>Payment Method</th>
                                    <th>Status</th>
                                    <th>Transaction Code</th>
                                    <th>Receipt</th>
                                    <th class="text-end pe-4">Verification Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($orders)): ?>
                                    <tr class="no-search">
                                        <td colspan="9" class="text-center py-5 text-muted">
                                            <i class="bi bi-inbox fs-1 d-block mb-2 opacity-50"></i>
                                            No orders match this filter criteria.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($orders as $o): ?>
                                        <?php 
                                            $student_initial = strtoupper(substr($o['student_name'] ?? 'S', 0, 1));
                                            $is_pending_v = $o['payment_status'] === 'PENDING_VERIFICATION';
                                        ?>
                                        <tr class="<?= $is_pending_v ? 'bg-warning bg-opacity-10' : '' ?>">
                                            <td class="ps-4 font-monospace fw-bold text-primary small">
                                                <div class="d-flex align-items-center gap-1">
                                                    <span>#<?= str_pad($o['id'], 5, '0', STR_PAD_LEFT) ?></span>
                                                    <button class="btn btn-link btn-xs p-0 text-muted" onclick="copyToClipboard('#<?= str_pad($o['id'], 5, '0', STR_PAD_LEFT) ?>', 'Order ID')" title="Copy Order ID">
                                                        <i class="bi bi-copy" style="font-size: 0.75rem;"></i>
                                                    </button>
                                                </div>
                                                <div class="text-muted" style="font-size:0.72rem;">
                                                    <i class="bi bi-calendar3 me-1"></i><?= date('M d, Y', strtotime($o['created_at'])) ?>
                                                    <span class="d-block text-secondary" style="font-size:0.68rem;"><?= date('H:i:s', strtotime($o['created_at'])) ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="rounded-circle bg-light border text-primary fw-bold d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; font-size: 0.85rem; flex-shrink: 0;">
                                                        <?= $student_initial ?>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold text-dark small"><?= htmlspecialchars($o['student_name']) ?></div>
                                                        <div class="small text-muted text-truncate" style="max-width: 180px;">
                                                            <?= htmlspecialchars($o['student_email']) ?>
                                                            <button class="btn btn-link btn-xs p-0 text-muted ms-1" onclick="copyToClipboard('<?= htmlspecialchars($o['student_email']) ?>', 'Email')" title="Copy Email">
                                                                <i class="bi bi-copy" style="font-size: 0.7rem;"></i>
                                                            </button>
                                                        </div>
                                                        <?php if (!empty($o['student_phone'])): ?>
                                                            <div class="small text-muted font-monospace" style="font-size:0.75rem;">
                                                                <i class="bi bi-telephone me-1"></i><?= htmlspecialchars($o['student_phone']) ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="text-dark small fw-semibold text-truncate" style="max-width: 220px;" title="<?= htmlspecialchars($o['resource_title']) ?>">
                                                    <a href="<?= BASE_URL ?>/resource-details.php?id=<?= $o['resource_id'] ?>" target="_blank" class="text-dark text-decoration-none">
                                                        <?= htmlspecialchars($o['resource_title']) ?>
                                                    </a>
                                                </div>
                                                <div class="d-flex align-items-center gap-1 mt-1">
                                                    <span class="badge bg-light text-dark border small" style="font-size:0.68rem;"><?= htmlspecialchars($o['campus']) ?></span>
                                                    <span class="badge bg-info-subtle text-info border border-info-subtle small" style="font-size:0.68rem;"><?= htmlspecialchars($o['program']) ?></span>
                                                </div>
                                            </td>
                                            <td class="fw-bold small text-success font-monospace">
                                                <?= format_npr($o['amount']) ?>
                                            </td>
                                            <td>
                                                <?php if (($o['payment_method'] ?? 'ESEWA_QR') === 'ESEWA_QR'): ?>
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                                                        <i class="bi bi-qr-code me-1"></i> eSewa QR
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-info-subtle text-info border border-info-subtle">
                                                        <i class="bi bi-wallet2 me-1"></i> ePay Gateway
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($o['payment_status'] === 'PAID'): ?>
                                                    <span class="status-chip chip-paid">
                                                        <span class="pulse-dot pulse-success"></span> PAID
                                                    </span>
                                                <?php elseif ($o['payment_status'] === 'PENDING_VERIFICATION'): ?>
                                                    <span class="status-chip chip-verify">
                                                        <span class="pulse-dot pulse-warning"></span> VERIFY QR
                                                    </span>
                                                <?php elseif ($o['payment_status'] === 'PENDING'): ?>
                                                    <span class="status-chip chip-pending">
                                                        <i class="bi bi-clock me-1"></i> PENDING
                                                    </span>
                                                <?php else: ?>
                                                    <span class="status-chip chip-failed">
                                                        <i class="bi bi-x-circle me-1"></i> FAILED
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="small font-monospace">
                                                <?php if (!empty($o['transaction_code'])): ?>
                                                    <div class="d-flex align-items-center gap-1">
                                                        <strong class="text-dark"><?= htmlspecialchars($o['transaction_code']) ?></strong>
                                                        <button class="btn btn-link btn-xs p-0 text-muted" onclick="copyToClipboard('<?= htmlspecialchars($o['transaction_code']) ?>', 'Transaction Code')" title="Copy Transaction Code">
                                                            <i class="bi bi-copy" style="font-size: 0.72rem;"></i>
                                                        </button>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted fst-italic">Not submitted</span>
                                                <?php endif; ?>
                                                <span style="font-size:0.68rem;" class="text-muted d-block"><?= htmlspecialchars($o['transaction_uuid'] ?? '') ?></span>
                                            </td>
                                            <td>
                                                <?php if (!empty($o['payment_proof'])): ?>
                                                    <button type="button" class="btn btn-sm btn-outline-info rounded-pill py-1 px-2 d-inline-flex align-items-center gap-1 shadow-sm" 
                                                            onclick="openReceiptModal('<?= BASE_URL ?>/payment-proof.php?id=<?= (int)$o['id'] ?>', '#<?= str_pad($o['id'], 5, '0', STR_PAD_LEFT) ?>', '<?= htmlspecialchars(addslashes($o['student_name'])) ?>', '<?= htmlspecialchars(addslashes($o['transaction_code'] ?? '')) ?>', '<?= format_npr($o['amount']) ?>', <?= $o['id'] ?>, '<?= $o['payment_status'] ?>')">
                                                        <i class="bi bi-image-fill"></i> <span class="small fw-semibold">Receipt</span>
                                                    </button>
                                                <?php else: ?>
                                                    <span class="text-muted small fst-italic">No Proof</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end pe-4">
                                                <?php if ($o['payment_status'] === 'PENDING_VERIFICATION'): ?>
                                                    <!-- Quick 1-click Approve or Reject for QR -->
                                                    <div class="d-flex justify-content-end gap-1">
                                                        <form method="POST" action="" class="d-inline" onsubmit="return confirm('Confirm you have checked the transaction reference and received the full order amount before unlocking this resource.');">
                                                            <?= csrf_field() ?>
                                                            <input type="hidden" name="action" value="approve_qr">
                                                            <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                                            <button type="submit" class="btn btn-sm btn-success fw-bold py-1 px-3 shadow-sm rounded-pill d-inline-flex align-items-center gap-1">
                                                                <i class="bi bi-check-lg"></i> Approve
                                                            </button>
                                                        </form>
                                                        <form method="POST" action="" class="d-inline" onsubmit="return confirm('Reject order #<?= $o['id'] ?>?');">
                                                            <?= csrf_field() ?>
                                                            <input type="hidden" name="action" value="reject_order">
                                                            <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                                            <button type="submit" class="btn btn-sm btn-outline-danger py-1 px-2 rounded-pill" title="Reject Order">
                                                                <i class="bi bi-x-lg"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                <?php elseif ($o['payment_status'] !== 'PAID'): ?>
                                                    <form method="POST" action="" class="d-inline" onsubmit="return confirm('Manually mark order #<?= $o['id'] ?> as PAID?');">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="action" value="mark_paid">
                                                        <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-success py-1 px-2 small rounded-pill">
                                                            <i class="bi bi-check2-circle me-1"></i> Unlock
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <div class="d-flex align-items-center justify-content-end gap-1">
                                                        <span class="badge bg-success-subtle text-success py-1 px-2 small">
                                                            <i class="bi bi-unlock-fill me-1"></i> Unlocked
                                                        </span>
                                                        <a href="<?= BASE_URL ?>/receipt.php?id=<?= $o['id'] ?>" class="btn btn-sm btn-light border py-1 px-2 rounded-circle shadow-sm" target="_blank" title="View Official Receipt / Tax Invoice">
                                                            <i class="bi bi-receipt text-success"></i>
                                                        </a>
                                                        <a href="<?= BASE_URL ?>/download.php?order_id=<?= $o['id'] ?>" class="btn btn-sm btn-light border py-1 px-2 rounded-circle shadow-sm" target="_blank" title="Test Download File">
                                                            <i class="bi bi-download text-primary"></i>
                                                        </a>
                                                    </div>
                                                <?php endif; ?>
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

<!-- High-Resolution Receipt Viewer Modal with 1-Click Verification -->
<div class="modal fade" id="receiptModal" tabindex="-1" aria-labelledby="receiptModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header bg-dark text-white border-0 py-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-receipt text-warning fs-5"></i>
                    <h5 class="modal-title fw-bold text-white mb-0" id="receiptModalLabel">eSewa Payment Proof Receipt</h5>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                <div class="row g-4 align-items-center">
                    <div class="col-md-7 text-center">
                        <div class="p-2 bg-white rounded-3 border shadow-sm position-relative overflow-hidden" style="max-height: 480px;">
                            <img id="receiptModalImg" src="" alt="Payment Proof" class="img-fluid rounded" style="max-height: 440px; object-fit: contain;">
                        </div>
                        <a id="receiptDownloadLink" href="" target="_blank" class="btn btn-sm btn-outline-secondary mt-2">
                            <i class="bi bi-box-arrow-up-right me-1"></i> Open Raw Image in New Tab
                        </a>
                    </div>
                    <div class="col-md-5">
                        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white mb-3">
                            <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">Order Verification Details</h6>
                            <div class="mb-2">
                                <span class="text-muted small d-block">Order Reference:</span>
                                <strong id="receiptModalOrder" class="font-monospace text-primary"></strong>
                            </div>
                            <div class="mb-2">
                                <span class="text-muted small d-block">Student Name:</span>
                                <strong id="receiptModalStudent" class="text-dark"></strong>
                            </div>
                            <div class="mb-2">
                                <span class="text-muted small d-block">Claimed Amount:</span>
                                <strong id="receiptModalAmount" class="text-success font-monospace fs-5"></strong>
                            </div>
                            <div class="mb-2">
                                <span class="text-muted small d-block">eSewa Transaction ID:</span>
                                <div class="d-flex align-items-center gap-2">
                                    <strong id="receiptModalTxn" class="font-monospace bg-light p-1 rounded border"></strong>
                                </div>
                            </div>
                        </div>

                        <!-- Modal Actions -->
                        <div id="receiptModalActions" class="d-grid gap-2">
                            <form method="POST" action="" id="modalApproveForm">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="approve_qr">
                                <input type="hidden" name="order_id" id="modalApproveOrderId" value="">
                                <button type="submit" class="btn btn-success w-100 fw-bold py-2 shadow-sm rounded-3">
                                    <i class="bi bi-check-circle-fill me-1"></i> Approve &amp; Unlock Download
                                </button>
                            </form>
                            <form method="POST" action="" id="modalRejectForm" onsubmit="return confirm('Are you sure you want to reject this order payment?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="reject_order">
                                <input type="hidden" name="order_id" id="modalRejectOrderId" value="">
                                <button type="submit" class="btn btn-outline-danger w-100 fw-semibold py-2 rounded-3">
                                    <i class="bi bi-x-circle me-1"></i> Reject Payment Proof
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-white border-0 py-2">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
<script>
function openReceiptModal(imgUrl, orderCode, studentName, txnCode, amount, orderId, paymentStatus) {
    document.getElementById('receiptModalImg').src = imgUrl;
    document.getElementById('receiptDownloadLink').href = imgUrl;
    document.getElementById('receiptModalOrder').textContent = orderCode;
    document.getElementById('receiptModalStudent').textContent = studentName;
    document.getElementById('receiptModalAmount').textContent = amount;
    document.getElementById('receiptModalTxn').textContent = txnCode || 'Not provided';
    document.getElementById('modalApproveOrderId').value = orderId;
    document.getElementById('modalRejectOrderId').value = orderId;

    const actionsContainer = document.getElementById('receiptModalActions');
    if (paymentStatus === 'PAID') {
        actionsContainer.innerHTML = '<div class="alert alert-success py-2 mb-2 small text-center"><i class="bi bi-check-circle-fill me-1"></i> This order is verified & unlocked.</div>' +
            '<a href="<?= BASE_URL ?>/receipt.php?id=' + orderId + '" target="_blank" class="btn btn-outline-success w-100 fw-bold py-2 rounded-3 shadow-sm">' +
            '<i class="bi bi-file-earmark-text me-1"></i> View Official Tax Invoice / Receipt</a>';
    }

    const modal = new bootstrap.Modal(document.getElementById('receiptModal'));
    modal.show();
}
</script>
<script src="<?= BASE_URL ?>/assets/js/experience.js?v=5"></script>
</body>
</html>
