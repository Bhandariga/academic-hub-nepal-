<?php
/**
 * AcademicHub Nepal - Manage Students
 * File: admin/manage-students.php
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth_check.php';

require_admin();

try {
    $stmt = $pdo->query("
        SELECT u.*, 
               COUNT(o.id) as total_orders,
               COALESCE(SUM(CASE WHEN o.payment_status = 'PAID' THEN o.amount ELSE 0 END), 0) as total_spent
        FROM users u
        LEFT JOIN orders o ON u.id = o.user_id
        WHERE u.role = 'student'
        GROUP BY u.id
        ORDER BY u.id DESC
    ");
    $students = $stmt->fetchAll();
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students | Admin AcademicHub</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container-fluid p-0">
    <div class="row g-0">
        <?php $active_page = 'manage-students'; require_once __DIR__ . '/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="col-md-9 col-lg-10 p-4 p-md-5">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-3 border-bottom">
                <div>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-1 small text-muted">
                            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/dashboard.php">Dashboard</a></li>
                            <li class="breadcrumb-item active">Students Directory</li>
                        </ol>
                    </nav>
                    <h2 class="h3 fw-bold text-dark mb-0">Registered Students Directory</h2>
                </div>
                <div class="badge bg-white border text-muted px-3 py-2 rounded-pill shadow-sm">
                    Total: <strong class="text-primary"><?= count($students) ?> Students</strong>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light small">
                                <tr>
                                    <th class="ps-4">Student ID</th>
                                    <th>Name &amp; Contact</th>
                                    <th>Affiliated Campus</th>
                                    <th>Total Orders</th>
                                    <th>Total Spent</th>
                                    <th>Joined Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($students)): ?>
                                    <tr><td colspan="6" class="text-center py-4 text-muted">No student accounts registered yet.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($students as $s): ?>
                                        <tr>
                                            <td class="ps-4 font-monospace text-muted small">#STU-<?= $s['id'] ?></td>
                                            <td>
                                                <div class="fw-bold text-dark"><?= htmlspecialchars($s['name']) ?></div>
                                                <div class="small text-muted"><?= htmlspecialchars($s['email']) ?></div>
                                                <div class="small text-muted font-monospace"><?= htmlspecialchars($s['phone'] ?: 'No phone') ?></div>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark border"><?= htmlspecialchars($s['campus'] ?: 'General') ?></span>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary rounded-pill"><?= $s['total_orders'] ?> orders</span>
                                            </td>
                                            <td class="fw-bold text-success">
                                                <?= format_npr($s['total_spent']) ?>
                                            </td>
                                            <td class="small text-muted">
                                                <?= date('M d, Y', strtotime($s['created_at'])) ?>
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
