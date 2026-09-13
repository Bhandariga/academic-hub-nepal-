<?php
/**
 * AcademicHub Nepal - Student Dashboard
 * File: dashboard.php
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth_check.php';

require_login();

$user_id = $_SESSION['user_id'];

try {
    // 1. Fetch Student Profile
    $stmt_user = $pdo->prepare("SELECT * FROM users WHERE id = :id");
    $stmt_user->execute([':id' => $user_id]);
    $student = $stmt_user->fetch();

    // 2. Fetch Student Orders with Resource info
    $stmt_orders = $pdo->prepare("
        SELECT o.*, r.title as resource_title, r.campus, r.program, r.file_format, r.pages, r.id as res_id
        FROM orders o
        JOIN resources r ON o.resource_id = r.id
        WHERE o.user_id = :uid
        ORDER BY o.id DESC
    ");
    $stmt_orders->execute([':uid' => $user_id]);
    $orders = $stmt_orders->fetchAll();

    // 3. Filter strictly Paid Resources
    $paid_resources = array_filter($orders, function($o) {
        return $o['payment_status'] === 'PAID';
    });

    // 4. Fetch Download Log
    $stmt_downloads = $pdo->prepare("
        SELECT d.*, r.title as resource_title, r.file_format
        FROM downloads d
        JOIN resources r ON d.resource_id = r.id
        WHERE d.user_id = :uid
        ORDER BY d.id DESC LIMIT 10
    ");
    $stmt_downloads->execute([':uid' => $user_id]);
    $download_logs = $stmt_downloads->fetchAll();

} catch (Exception $e) {
    die("Error loading dashboard: " . $e->getMessage());
}

$page_title = "Student Dashboard | AcademicHub Nepal";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <!-- Welcome Header Banner -->
    <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 mb-4 text-white" style="background: linear-gradient(135deg, #0f2942 0%, #1e4976 100%);">
        <div class="row align-items-center">
            <div class="col-md-8">
                <span class="badge bg-info text-dark mb-2 fw-bold"><i class="bi bi-person-badge-fill me-1"></i> Student Portal</span>
                <h1 class="h2 fw-bold text-white mb-1">Welcome back, <?= htmlspecialchars($student['name'] ?? 'Student') ?>!</h1>
                <p class="text-white-50 mb-0">
                    <i class="bi bi-buildings me-1"></i> Campus: <strong><?= htmlspecialchars($student['campus'] ?? 'Tribhuvan University') ?></strong> &bull; 
                    <i class="bi bi-envelope me-1"></i> <?= htmlspecialchars($student['email']) ?>
                </p>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <a href="<?= BASE_URL ?>/resources.php" class="btn btn-light rounded-pill px-4 fw-semibold shadow-sm">
                    <i class="bi bi-search me-1"></i> Explore More Resources
                </a>
            </div>
        </div>
    </div>

    <!-- Quick Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 text-center bg-white h-100">
                <div class="hub-icon bg-primary text-white mx-auto mb-2" style="width: 44px; height: 44px; border-radius: 10px; display:flex;align-items:center;justify-content:center;">
                    <i class="bi bi-cart-check-fill fs-5"></i>
                </div>
                <div class="fs-4 fw-bold text-dark"><?= count($orders) ?></div>
                <span class="small text-muted fw-semibold">My Orders</span>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 text-center bg-white h-100">
                <div class="hub-icon bg-success text-white mx-auto mb-2" style="width: 44px; height: 44px; border-radius: 10px; display:flex;align-items:center;justify-content:center;">
                    <i class="bi bi-file-earmark-check-fill fs-5"></i>
                </div>
                <div class="fs-4 fw-bold text-dark"><?= count($paid_resources) ?></div>
                <span class="small text-muted fw-semibold">Unlocked Resources</span>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 text-center bg-white h-100">
                <div class="hub-icon bg-info text-dark mx-auto mb-2" style="width: 44px; height: 44px; border-radius: 10px; display:flex;align-items:center;justify-content:center;">
                    <i class="bi bi-cloud-arrow-down-fill fs-5"></i>
                </div>
                <div class="fs-4 fw-bold text-dark"><?= count($download_logs) ?></div>
                <span class="small text-muted fw-semibold">Downloads Completed</span>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 text-center bg-white h-100">
                <div class="hub-icon bg-warning text-dark mx-auto mb-2" style="width: 44px; height: 44px; border-radius: 10px; display:flex;align-items:center;justify-content:center;">
                    <i class="bi bi-shield-lock-fill fs-5"></i>
                </div>
                <div class="fs-4 fw-bold text-dark">Active</div>
                <span class="small text-muted fw-semibold">Account Status</span>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <ul class="nav nav-pills mb-4 gap-2 bg-white p-2 rounded-3 border shadow-sm" id="dashboardTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active rounded-pill fw-semibold" id="orders-tab" data-bs-toggle="pill" data-bs-target="#orders-pane" type="button" role="tab">
                <i class="bi bi-receipt me-1"></i> My Orders &amp; Receipts
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link rounded-pill fw-semibold" id="downloads-tab" data-bs-toggle="pill" data-bs-target="#downloads-pane" type="button" role="tab">
                <i class="bi bi-cloud-arrow-down me-1"></i> Purchased Downloads (<?= count($paid_resources) ?>)
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link rounded-pill fw-semibold" id="profile-tab" data-bs-toggle="pill" data-bs-target="#profile-pane" type="button" role="tab">
                <i class="bi bi-person-lines-fill me-1"></i> Profile &amp; Campus
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link rounded-pill fw-semibold" id="support-tab" data-bs-toggle="pill" data-bs-target="#support-pane" type="button" role="tab">
                <i class="bi bi-headset me-1"></i> Help &amp; Support
            </button>
        </li>
    </ul>

    <!-- Tab Content Area -->
    <div class="tab-content" id="dashboardTabContent">
        <!-- 1. MY ORDERS TAB -->
        <div class="tab-pane fade show active" id="orders-pane" role="tabpanel">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
                <div class="card-header bg-white p-3 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0 text-primary"><i class="bi bi-table me-2"></i>Order History</h5>
                    <span class="badge bg-light text-muted border"><?= count($orders) ?> Total Orders</span>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($orders)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-cart-x display-4 text-muted opacity-50 mb-3 d-block"></i>
                            <h5 class="fw-bold">No orders placed yet</h5>
                            <p class="text-muted">Browse our campus-wise catalog to get started with reference resources.</p>
                            <a href="<?= BASE_URL ?>/resources.php" class="btn btn-primary rounded-pill px-4">Browse Resources</a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4">Order ID / Ref</th>
                                        <th>Resource Name</th>
                                        <th>Campus &amp; Program</th>
                                        <th>Amount</th>
                                        <th>Payment Status</th>
                                        <th class="text-end pe-4">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $ord): ?>
                                        <tr>
                                            <td class="ps-4">
                                                <div class="fw-bold text-dark font-monospace small">#<?= str_pad($ord['id'], 5, '0', STR_PAD_LEFT) ?></div>
                                                <div class="text-muted" style="font-size: 0.75rem;"><?= date('M d, Y', strtotime($ord['created_at'])) ?></div>
                                            </td>
                                            <td>
                                                <a href="<?= BASE_URL ?>/resource-details.php?id=<?= $ord['res_id'] ?>" class="fw-bold text-primary text-decoration-none">
                                                    <?= htmlspecialchars($ord['resource_title']) ?>
                                                </a>
                                                <div class="small text-muted"><?= $ord['pages'] ?> Pgs &bull; <?= htmlspecialchars($ord['file_format']) ?></div>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark border"><?= htmlspecialchars($ord['campus']) ?></span>
                                                <span class="small text-muted d-block"><?= htmlspecialchars($ord['program']) ?></span>
                                            </td>
                                            <td class="fw-bold">
                                                <?= format_npr($ord['amount']) ?>
                                            </td>
                                            <td>
                                                <?php if ($ord['payment_status'] === 'PAID'): ?>
                                                    <span class="badge bg-success-subtle text-success border border-success px-2 py-1">
                                                        <i class="bi bi-check-circle-fill me-1"></i> PAID
                                                    </span>
                                                    <div class="small text-muted font-monospace" style="font-size:0.7rem;"><?= htmlspecialchars($ord['transaction_code'] ?? 'eSewa') ?></div>
                                                <?php elseif ($ord['payment_status'] === 'PENDING'): ?>
                                                    <span class="badge bg-warning-subtle text-warning border border-warning px-2 py-1">
                                                        <i class="bi bi-clock-history me-1"></i> PENDING
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger-subtle text-danger border border-danger px-2 py-1">
                                                        <i class="bi bi-x-circle me-1"></i> FAILED
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end pe-4">
                                                <?php if ($ord['payment_status'] === 'PAID'): ?>
                                                    <a href="<?= BASE_URL ?>/download.php?order_id=<?= $ord['id'] ?>" class="btn btn-sm btn-hub-primary rounded-pill px-3">
                                                        <i class="bi bi-download me-1"></i> Download
                                                    </a>
                                                <?php else: ?>
                                                    <a href="<?= BASE_URL ?>/payment.php?resource_id=<?= $ord['res_id'] ?>" class="btn btn-sm btn-outline-success rounded-pill px-3">
                                                        <i class="bi bi-credit-card me-1"></i> Pay Now
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- 2. DOWNLOADS TAB -->
        <div class="tab-pane fade" id="downloads-pane" role="tabpanel">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
                <h5 class="fw-bold text-primary mb-3"><i class="bi bi-cloud-arrow-down-fill me-2"></i>Unlocked Academic Materials</h5>
                
                <?php if (empty($paid_resources)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-folder2-open display-4 text-muted opacity-50 mb-3 d-block"></i>
                        <h5>No downloaded resources yet</h5>
                        <p class="text-muted">Once an eSewa payment is verified, your purchased documents appear here for unlimited access.</p>
                    </div>
                <?php else: ?>
                    <div class="row g-3">
                        <?php foreach ($paid_resources as $paid): ?>
                            <div class="col-md-6">
                                <div class="p-3 border rounded-3 bg-light d-flex justify-content-between align-items-center">
                                    <div class="me-3">
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <span class="badge bg-primary"><?= htmlspecialchars($paid['file_format']) ?></span>
                                            <span class="small text-muted"><?= htmlspecialchars($paid['campus']) ?></span>
                                        </div>
                                        <h6 class="fw-bold mb-1 text-primary"><?= htmlspecialchars($paid['resource_title']) ?></h6>
                                        <span class="small text-muted"><?= $paid['pages'] ?> Pages &bull; Lifetime Student License</span>
                                    </div>
                                    <div>
                                        <a href="<?= BASE_URL ?>/download.php?order_id=<?= $paid['id'] ?>" class="btn btn-hub-primary btn-sm text-nowrap rounded-pill px-3">
                                            <i class="bi bi-download me-1"></i> Download
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Download Activity Log -->
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                <h6 class="fw-bold text-muted mb-3"><i class="bi bi-clock-history me-1"></i> Recent Download Access Log</h6>
                <?php if (empty($download_logs)): ?>
                    <p class="small text-muted mb-0">No download logs recorded yet.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm text-muted small mb-0">
                            <thead>
                                <tr>
                                    <th>Resource</th>
                                    <th>Format</th>
                                    <th>IP Address</th>
                                    <th>Timestamp</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($download_logs as $dl): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($dl['resource_title']) ?></td>
                                        <td><?= htmlspecialchars($dl['file_format']) ?></td>
                                        <td><?= htmlspecialchars($dl['ip_address']) ?></td>
                                        <td><?= date('Y-m-d H:i', strtotime($dl['download_date'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- 3. PROFILE TAB -->
        <div class="tab-pane fade" id="profile-pane" role="tabpanel">
            <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white">
                <h5 class="fw-bold text-primary mb-4"><i class="bi bi-person-circle me-2"></i>Student Profile Details</h5>
                
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded-3 border">
                            <label class="small text-muted fw-semibold d-block">Full Name</label>
                            <span class="fs-6 fw-bold text-dark"><?= htmlspecialchars($student['name']) ?></span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded-3 border">
                            <label class="small text-muted fw-semibold d-block">Email Address</label>
                            <span class="fs-6 fw-bold text-dark"><?= htmlspecialchars($student['email']) ?></span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded-3 border">
                            <label class="small text-muted fw-semibold d-block">Affiliated Campus</label>
                            <span class="fs-6 fw-bold text-dark"><?= htmlspecialchars($student['campus'] ?? 'Not specified') ?></span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded-3 border">
                            <label class="small text-muted fw-semibold d-block">Mobile Number</label>
                            <span class="fs-6 fw-bold text-dark"><?= htmlspecialchars($student['phone'] ?: 'None provided') ?></span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded-3 border">
                            <label class="small text-muted fw-semibold d-block">Member Since</label>
                            <span class="fs-6 fw-bold text-dark"><?= date('F d, Y', strtotime($student['created_at'])) ?></span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded-3 border">
                            <label class="small text-muted fw-semibold d-block">Account Role</label>
                            <span class="badge bg-primary text-uppercase"><?= htmlspecialchars($student['role']) ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. SUPPORT TAB -->
        <div class="tab-pane fade" id="support-pane" role="tabpanel">
            <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white">
                <h5 class="fw-bold text-primary mb-2"><i class="bi bi-headset me-2"></i>AcademicHub Student Support</h5>
                <p class="text-muted small mb-4">Experiencing issues with an eSewa payment or opening a downloaded document? Submit a ticket below.</p>

                <form action="" method="POST" onsubmit="alert('Support ticket submitted successfully! A support executive will contact you via your registered email.'); return false;">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted">Related Order Number (Optional)</label>
                            <input type="text" class="form-control" placeholder="e.g. #ORD-2026-001">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted">Issue Category</label>
                            <select class="form-select">
                                <option>Payment Deducted but Not Unlocked</option>
                                <option>Corrupted / Unable to Open File</option>
                                <option>Formatting Question</option>
                                <option>General Inquiry</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Detailed Message / Description</label>
                        <textarea class="form-control" rows="4" placeholder="Please describe the issue in detail..." required></textarea>
                    </div>

                    <button type="submit" class="btn btn-hub-primary px-4">
                        <i class="bi bi-send-fill me-1"></i> Submit Support Request
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
