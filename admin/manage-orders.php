<?php
/**
 * AcademicHub Nepal - Manage Orders & Transactions
 * File: admin/manage-orders.php
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth_check.php';

require_admin();

// Manual Status Update Action (e.g. manual verification or re-grant)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $order_id = (int)$_GET['id'];
    $new_status = $_GET['action'] === 'mark_paid' ? 'PAID' : ($_GET['action'] === 'mark_pending' ? 'PENDING' : null);

    if ($new_status) {
        try {
            $txn_code = ($new_status === 'PAID') ? 'MANUAL-ADMIN-' . time() : null;
            $stmt_update = $pdo->prepare("UPDATE orders SET payment_status = :status, transaction_code = COALESCE(transaction_code, :txn) WHERE id = :id");
            $stmt_update->execute([':status' => $new_status, ':txn' => $txn_code, ':id' => $order_id]);
            set_flash_message('success', "Order #{$order_id} status updated to {$new_status}.");
        } catch (Exception $e) {
            set_flash_message('danger', 'Update error: ' . $e->getMessage());
        }
        header('Location: ' . BASE_URL . '/admin/manage-orders.php');
        exit;
    }
}

// Filter by Status
$filter_status = $_GET['status'] ?? '';
$whereClause = "";
$params = [];

if (!empty($filter_status) && in_array($filter_status, ['PAID', 'PENDING', 'FAILED'])) {
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
        ORDER BY o.id DESC
    ");
    $stmt->execute($params);
    $orders = $stmt->fetchAll();
} catch (Exception $e) {
    die("Error fetching orders: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders &amp; Payments | Admin AcademicHub</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container-fluid p-0">
    <div class="row g-0">
        <?php $active_page = 'manage-orders'; require_once __DIR__ . '/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="col-md-9 col-lg-10 p-4 p-md-5">
            <?php display_flash_message(); ?>

            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-3 border-bottom">
                <div>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-1 small text-muted">
                            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/dashboard.php">Dashboard</a></li>
                            <li class="breadcrumb-item active">Transactions &amp; Orders</li>
                        </ol>
                    </nav>
                    <h2 class="h3 fw-bold text-dark mb-0">Order &amp; Payment Transactions</h2>
                </div>

                <!-- Status Filter Pills -->
                <div class="btn-group btn-group-sm shadow-sm mt-3 mt-md-0">
                    <a href="<?= BASE_URL ?>/admin/manage-orders.php" class="btn <?= empty($filter_status) ? 'btn-primary' : 'btn-outline-secondary' ?>">All (<?= count($orders) ?>)</a>
                    <a href="<?= BASE_URL ?>/admin/manage-orders.php?status=PAID" class="btn <?= $filter_status === 'PAID' ? 'btn-success' : 'btn-outline-secondary' ?>">Paid</a>
                    <a href="<?= BASE_URL ?>/admin/manage-orders.php?status=PENDING" class="btn <?= $filter_status === 'PENDING' ? 'btn-warning' : 'btn-outline-secondary' ?>">Pending</a>
                    <a href="<?= BASE_URL ?>/admin/manage-orders.php?status=FAILED" class="btn <?= $filter_status === 'FAILED' ? 'btn-danger' : 'btn-outline-secondary' ?>">Failed</a>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light small">
                                <tr>
                                    <th class="ps-4">Order ID</th>
                                    <th>Student Details</th>
                                    <th>Purchased Resource</th>
                                    <th>Amount</th>
                                    <th>Payment Status</th>
                                    <th>eSewa Reference</th>
                                    <th>Created At</th>
                                    <th class="text-end pe-4">Manual Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($orders)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-5 text-muted">No orders match this filter.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($orders as $o): ?>
                                        <tr>
                                            <td class="ps-4 font-monospace fw-bold text-primary small">
                                                #<?= str_pad($o['id'], 5, '0', STR_PAD_LEFT) ?>
                                            </td>
                                            <td>
                                                <div class="fw-bold text-dark small"><?= htmlspecialchars($o['student_name']) ?></div>
                                                <div class="small text-muted"><?= htmlspecialchars($o['student_email']) ?></div>
                                                <div class="small text-muted font-monospace"><?= htmlspecialchars($o['student_phone'] ?? '') ?></div>
                                            </td>
                                            <td>
                                                <div class="text-dark small fw-semibold text-truncate" style="max-width: 220px;">
                                                    <?= htmlspecialchars($o['resource_title']) ?>
                                                </div>
                                                <span class="badge bg-light text-dark border small"><?= htmlspecialchars($o['campus']) ?></span>
                                            </td>
                                            <td class="fw-bold small">
                                                <?= format_npr($o['amount']) ?>
                                            </td>
                                            <td>
                                                <?php if ($o['payment_status'] === 'PAID'): ?>
                                                    <span class="badge bg-success-subtle text-success border border-success">
                                                        <i class="bi bi-check-circle-fill me-1"></i> PAID
                                                    </span>
                                                <?php elseif ($o['payment_status'] === 'PENDING'): ?>
                                                    <span class="badge bg-warning-subtle text-warning border border-warning">
                                                        <i class="bi bi-clock-history me-1"></i> PENDING
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger-subtle text-danger border border-danger">
                                                        <i class="bi bi-x-circle me-1"></i> FAILED
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="small font-monospace text-muted">
                                                <div class="text-dark"><?= htmlspecialchars($o['transaction_code'] ?: 'N/A') ?></div>
                                                <span style="font-size:0.7rem;" class="text-secondary"><?= htmlspecialchars($o['transaction_uuid']) ?></span>
                                            </td>
                                            <td class="small text-muted">
                                                <?= date('Y-m-d H:i', strtotime($o['created_at'])) ?>
                                            </td>
                                            <td class="text-end pe-4">
                                                <?php if ($o['payment_status'] !== 'PAID'): ?>
                                                    <a href="<?= BASE_URL ?>/admin/manage-orders.php?action=mark_paid&id=<?= $o['id'] ?>" class="btn btn-xs btn-outline-success py-1 px-2 small rounded" onclick="return confirm('Manually mark this order as PAID and unlock the document?');">
                                                        <i class="bi bi-check2-circle"></i> Unlock
                                                    </a>
                                                <?php else: ?>
                                                    <a href="<?= BASE_URL ?>/download.php?order_id=<?= $o['id'] ?>" class="btn btn-xs btn-outline-primary py-1 px-2 small rounded" target="_blank">
                                                        <i class="bi bi-download"></i> Test
                                                    </a>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
