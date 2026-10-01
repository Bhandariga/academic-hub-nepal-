<?php
/**
 * AcademicHub Nepal - Executive Modern Admin Dashboard
 * File: admin/dashboard.php
 * Features: Modern SaaS UI, KPIs, Analytics, and Self-Service Admin ID / Password Management
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth_check.php';

require_admin();

$admin_id = $_SESSION['user_id'];
$profile_msg = '';
$profile_status = '';
$pwd_msg = '';
$pwd_status = '';
$active_tab = 'overview';

// 1. Handle Admin Profile Update (Name, Email / Admin ID, Phone)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $active_tab = 'settings';
    if (!verify_csrf_token()) {
        $profile_msg = 'Security token expired. Please try submitting again.';
        $profile_status = 'danger';
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');

        if (empty($name) || empty($email)) {
            $profile_msg = 'Name and Admin Email ID cannot be empty.';
            $profile_status = 'danger';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $profile_msg = 'Please provide a valid email format.';
            $profile_status = 'danger';
        } else {
            try {
                // Check if email taken by another account
                $chk = $pdo->prepare("SELECT id FROM users WHERE email = :email AND id != :id LIMIT 1");
                $chk->execute([':email' => $email, ':id' => $admin_id]);
                if ($chk->fetch()) {
                    $profile_msg = 'This email address is already assigned to another user account.';
                    $profile_status = 'danger';
                } else {
                    $upd = $pdo->prepare("UPDATE users SET name = :name, email = :email, phone = :phone WHERE id = :id");
                    $upd->execute([':name' => $name, ':email' => $email, ':phone' => $phone, ':id' => $admin_id]);
                    
                    $_SESSION['user_name'] = $name;
                    $_SESSION['user_email'] = $email;
                    log_admin_activity($pdo, 'UPDATE_SELF_PROFILE', "Updated personal credentials to {$email}");
                    $profile_msg = 'Admin profile & ID credentials updated successfully!';
                    $profile_status = 'success';
                }
            } catch (Exception $e) {
                $profile_msg = 'Update failed: ' . safe_error_message($e);
                $profile_status = 'danger';
            }
        }
    }
}

// 2. Handle Admin Password Change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $active_tab = 'settings';
    if (!verify_csrf_token()) {
        $pwd_msg = 'Security token expired. Please try submitting again.';
        $pwd_status = 'danger';
    } else {
        $current_pwd = $_POST['current_password'] ?? '';
        $new_pwd = $_POST['new_password'] ?? '';
        $confirm_pwd = $_POST['confirm_password'] ?? '';

        if (empty($current_pwd) || empty($new_pwd) || empty($confirm_pwd)) {
            $pwd_msg = 'Please fill out all password fields.';
            $pwd_status = 'danger';
        } elseif (strlen($new_pwd) < 6) {
            $pwd_msg = 'New password must be at least 6 characters long.';
            $pwd_status = 'danger';
        } elseif ($new_pwd !== $confirm_pwd) {
            $pwd_msg = 'New password and confirmation do not match.';
            $pwd_status = 'danger';
        } else {
            try {
                // Verify current password
                $st = $pdo->prepare("SELECT password FROM users WHERE id = :id LIMIT 1");
                $st->execute([':id' => $admin_id]);
                $admin_record = $st->fetch();

                $is_current_valid = false;
                if ($admin_record) {
                    if (password_verify($current_pwd, $admin_record['password'])) {
                        $is_current_valid = true;
                    }
                }

                if (!$is_current_valid) {
                    $pwd_msg = 'The current password you entered is incorrect.';
                    $pwd_status = 'danger';
                } else {
                    $new_hash = password_hash($new_pwd, PASSWORD_BCRYPT);
                    $upd_pwd = $pdo->prepare("UPDATE users SET password = :p WHERE id = :id");
                    $upd_pwd->execute([':p' => $new_hash, ':id' => $admin_id]);

                    log_admin_activity($pdo, 'CHANGE_SELF_PWD', 'Administrator updated personal login password');
                    $pwd_msg = 'Your administrator password has been updated successfully!';
                    $pwd_status = 'success';
                }
            } catch (Exception $e) {
                $pwd_msg = 'Password change failed: ' . safe_error_message($e);
                $pwd_status = 'danger';
            }
        }
    }
}

// Fetch current admin info
$stmt_admin = $pdo->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
$stmt_admin->execute([':id' => $admin_id]);
$current_admin = $stmt_admin->fetch() ?: [
    'name' => $_SESSION['user_name'] ?? 'Admin',
    'email' => $_SESSION['user_email'] ?? 'admin@academichub.com',
    'phone' => '9800000001'
];

// Fetch KPI statistics
try {
    $total_students = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
    $total_resources = $pdo->query("SELECT COUNT(*) FROM resources")->fetchColumn();
    $total_orders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    $total_sales = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM orders WHERE payment_status = 'PAID'")->fetchColumn();
    $total_admins = $pdo->query("SELECT COUNT(*) FROM users WHERE role IN ('admin', 'superadmin')")->fetchColumn();
    $pending_qr = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE payment_status = 'PENDING_VERIFICATION'")->fetchColumn();


    $stmt_recent_orders = $pdo->query("
        SELECT o.*, u.name as student_name, u.email as student_email, r.title as resource_title, r.campus, r.program
        FROM orders o
        JOIN users u ON o.user_id = u.id
        JOIN resources r ON o.resource_id = r.id
        ORDER BY o.id DESC LIMIT 8
    ");
    $recent_orders = $stmt_recent_orders->fetchAll();

    $service_requests = $pdo->query("SELECT * FROM service_requests ORDER BY id DESC LIMIT 5")->fetchAll();
    $pending_services = $pdo->query("SELECT COUNT(*) FROM service_requests WHERE status = 'Pending'")->fetchColumn();
} catch (Exception $e) {
    die("Database query error: " . safe_error_message($e));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Executive Admin Dashboard | AcademicHub Nepal</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Outfit:wght@400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 & Icons -->
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
        <!-- 1. Left Sidebar Navigation -->
        <?php $active_page = 'dashboard'; require_once __DIR__ . '/sidebar.php'; ?>


        <!-- 2. Main Executive Content Area -->
        <div class="col-md-9 col-lg-10 p-4 p-md-5">
            <!-- Flash Message Alerts -->
            <?php display_flash_message(); ?>

            <!-- Modern Topbar / Breadcrumb Header -->
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-3 border-bottom">
                <div>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-1 small text-muted">
                            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php">AcademicHub</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Admin Console</li>
                        </ol>
                    </nav>
                    <h2 class="h3 fw-bold text-dark mb-0">Dashboard overview</h2>
                </div>

                <div class="d-flex align-items-center gap-3 mt-3 mt-md-0">
                    <a href="<?= BASE_URL ?>/admin/add-resource.php" class="btn btn-hub-primary rounded-pill px-4 shadow-sm">
                        <i class="bi bi-plus-circle-fill me-1"></i> Upload Resource
                    </a>
                </div>
            </div>

            <!-- Tab Switcher Nav -->
            <ul class="nav admin-tab-nav mb-4 shadow-sm" id="adminTabs" role="tablist">
                <li class="nav-item">
                    <button class="nav-link <?= $active_tab === 'overview' ? 'active' : '' ?>" id="overview-tab" data-bs-toggle="pill" data-bs-target="#tab-overview" type="button">
                        <i class="bi bi-speedometer2 me-1"></i> Dashboard Overview
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link <?= $active_tab === 'settings' ? 'active' : '' ?>" id="settings-tab" data-bs-toggle="pill" data-bs-target="#tab-settings" type="button">
                        <i class="bi bi-shield-lock-fill me-1"></i> Change Admin ID &amp; Password
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="inquiries-tab" data-bs-toggle="pill" data-bs-target="#tab-inquiries" type="button">
                        <i class="bi bi-headset me-1"></i> Service Inquiries (<?= $pending_services ?>)
                    </button>
                </li>
            </ul>

            <!-- Tab Content -->
            <div class="tab-content" id="adminTabContent">
                
                <!-- TAB 1: OVERVIEW & KPIS -->
                <div class="tab-pane fade <?= $active_tab === 'overview' ? 'show active' : '' ?>" id="tab-overview" role="tabpanel">
                    <?php require __DIR__ . '/../includes/admin-analytics.php'; ?>
                    
                    <?php if (is_superadmin()): ?>
                        <!-- Super Admin Sovereignty Command Center -->
                        <div class="card border-0 rounded-4 p-4 mb-4 text-white shadow-sm" style="background: linear-gradient(135deg, #0f1f33 0%, #172a45 100%); border-left: 5px solid #f59e0b !important;">
                            <div class="row align-items-center">
                                <div class="col-lg-8">
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <span class="badge bg-warning bg-opacity-25 text-warning border border-warning border-opacity-50 px-3 py-1 rounded-pill">
                                            <i class="bi bi-shield-fill-check me-1"></i>SUPER ADMIN WORKSPACE
                                        </span>
                                        <span class="badge bg-white bg-opacity-10 text-white-50 px-2 py-1 rounded-pill">
                                            <?= $total_admins ?> Total Operators
                                        </span>
                                    </div>
                                    <h4 class="fw-bold text-white mb-1">Administrative Role Governance Active</h4>
                                    <p class="text-white-50 small mb-0">
                                        As Chief Super Administrator, you possess absolute authority to provision staff accounts, assign granular role capabilities, suspend rogue admins, and review the immutable security audit trail.
                                    </p>
                                </div>
                                <div class="col-lg-4 text-lg-end mt-3 mt-lg-0 d-flex flex-wrap justify-content-lg-end gap-2">
                                    <a href="<?= BASE_URL ?>/admin/manage-admins.php" class="btn btn-warning rounded-pill px-3 py-2 fw-bold shadow-sm d-inline-flex align-items-center gap-2 text-dark">
                                        <i class="bi bi-person-gear"></i> Admins &amp; Roles
                                    </a>
                                    <a href="<?= BASE_URL ?>/admin/manage-team.php" class="btn btn-outline-light rounded-pill px-3 py-2 fw-semibold shadow-sm d-inline-flex align-items-center gap-2">
                                        <i class="bi bi-people-fill text-warning"></i> Team Members
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($pending_qr > 0): ?>
                        <!-- Pending QR Verifications Alert Banner -->
                        <div class="alert alert-warning border-0 rounded-4 shadow-sm p-4 mb-4 d-flex flex-wrap align-items-center justify-content-between gap-3 bg-warning-subtle text-dark">
                            <div class="d-flex align-items-center gap-3">
                                <div class="bg-warning text-dark p-3 rounded-circle fs-3 shadow-sm">
                                    <i class="bi bi-qr-code-scan"></i>
                                </div>
                                <div>
                                    <h5 class="fw-bold mb-1">Attention: <?= $pending_qr ?> eSewa QR Payment(s) Awaiting Verification</h5>
                                    <p class="mb-0 small text-muted">Students have scanned your eSewa QR code and submitted their Transaction IDs. Verify to unlock their downloads.</p>
                                </div>
                            </div>
                            <a href="<?= BASE_URL ?>/admin/manage-orders.php?status=PENDING_VERIFICATION" class="btn btn-warning text-dark fw-bold rounded-pill px-4 shadow-sm">
                                Verify Orders Now <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </div>
                    <?php endif; ?>

                    <!-- KPI Cards Grid -->
                    <div class="row g-4 mb-4">

                        <!-- Revenue -->
                        <div class="col-sm-6 col-xl-3">
                            <div class="admin-kpi-card kpi-revenue">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="text-muted small fw-bold text-uppercase">Realized Revenue</span>
                                    <div class="admin-kpi-icon"><i class="bi bi-cash-stack"></i></div>
                                </div>
                                <div class="h3 fw-bold text-success mb-1"><?= format_npr($total_sales) ?></div>
                                <div class="small text-muted d-flex align-items-center gap-1">
                                    <i class="bi bi-shield-check text-success"></i> Verified via eSewa ePay
                                </div>
                            </div>
                        </div>

                        <!-- Total Orders -->
                        <div class="col-sm-6 col-xl-3">
                            <div class="admin-kpi-card kpi-orders">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="text-muted small fw-bold text-uppercase">Orders Placed</span>
                                    <div class="admin-kpi-icon"><i class="bi bi-bag-check-fill"></i></div>
                                </div>
                                <div class="h3 fw-bold text-dark mb-1"><?= number_format($total_orders) ?></div>
                                <div class="small text-muted">
                                    <a href="<?= BASE_URL ?>/admin/manage-orders.php" class="text-decoration-none">Review transactions &rarr;</a>
                                </div>
                            </div>
                        </div>

                        <!-- Total Students -->
                        <div class="col-sm-6 col-xl-3">
                            <div class="admin-kpi-card kpi-students">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="text-muted small fw-bold text-uppercase">Active Students</span>
                                    <div class="admin-kpi-icon"><i class="bi bi-people-fill"></i></div>
                                </div>
                                <div class="h3 fw-bold text-dark mb-1"><?= number_format($total_students) ?></div>
                                <div class="small text-muted">
                                    <a href="<?= BASE_URL ?>/admin/manage-students.php" class="text-decoration-none">Registered accounts &rarr;</a>
                                </div>
                            </div>
                        </div>

                        <!-- Resources Catalog -->
                        <div class="col-sm-6 col-xl-3">
                            <div class="admin-kpi-card kpi-resources">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="text-muted small fw-bold text-uppercase">Catalog Items</span>
                                    <div class="admin-kpi-icon"><i class="bi bi-journal-bookmark-fill"></i></div>
                                </div>
                                <div class="h3 fw-bold text-dark mb-1"><?= number_format($total_resources) ?></div>
                                <div class="small text-muted">
                                    <a href="<?= BASE_URL ?>/admin/manage-resource.php" class="text-decoration-none">Manage catalog &rarr;</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6"><div class="admin-kpi-card"><div class="d-flex justify-content-between"><span class="small text-muted">Pending service requests</span><i class="bi bi-headset text-primary"></i></div><div class="h3 mt-3"><?= number_format($pending_services) ?></div><a class="small" href="#tab-inquiries">Review service requests →</a></div></div>
                        <div class="col-md-6"><div class="admin-kpi-card"><div class="d-flex justify-content-between"><span class="small text-muted">Payments awaiting verification</span><i class="bi bi-clock-history text-warning"></i></div><div class="h3 mt-3"><?= number_format($pending_qr) ?></div><a class="small" href="<?= BASE_URL ?>/admin/manage-orders.php">Review payment activity →</a></div></div>
                    </div>
                    <!-- Recent Transactions & Quick Actions -->
                    <div class="row g-4 mb-4">
                        <div class="col-xl-8">
                            <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white h-100">
                                <div class="card-header bg-white p-4 border-bottom d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="fw-bold mb-0 text-dark">Recent Student Orders</h5>
                                        <span class="small text-muted">Real-time payment verification from eSewa sandbox</span>
                                    </div>
                                    <a href="<?= BASE_URL ?>/admin/manage-orders.php" class="btn btn-outline-primary btn-sm rounded-pill px-3 fw-semibold">
                                        View All Orders &rarr;
                                    </a>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-0">
                                            <thead class="table-light small">
                                                <tr>
                                                    <th class="ps-4">Student</th>
                                                    <th>Purchased Resource</th>
                                                    <th>Amount</th>
                                                    <th>Status</th>
                                                    <th class="text-end pe-4">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($recent_orders)): ?>
                                                    <tr><td colspan="5" class="text-center py-4 text-muted">No orders recorded in database yet.</td></tr>
                                                <?php else: ?>
                                                    <?php foreach ($recent_orders as $ord): ?>
                                                        <tr>
                                                            <td class="ps-4">
                                                                <div class="fw-bold text-dark small"><?= htmlspecialchars($ord['student_name']) ?></div>
                                                                <div class="small text-muted" style="font-size:0.75rem;"><?= htmlspecialchars($ord['student_email']) ?></div>
                                                            </td>
                                                            <td>
                                                                <div class="text-dark small fw-semibold text-truncate" style="max-width: 220px;">
                                                                    <?= htmlspecialchars($ord['resource_title']) ?>
                                                                </div>
                                                                <span class="badge bg-light text-dark border small"><?= htmlspecialchars($ord['campus']) ?></span>
                                                            </td>
                                                            <td class="fw-bold small"><?= format_npr($ord['amount']) ?></td>
                                                            <td>
                                                                <?php if ($ord['payment_status'] === 'PAID'): ?>
                                                                    <span class="badge bg-success-subtle text-success border border-success px-2 py-1">
                                                                        <i class="bi bi-check-circle-fill me-1"></i> PAID
                                                                    </span>
                                                                <?php elseif ($ord['payment_status'] === 'PENDING_VERIFICATION'): ?>
                                                                    <span class="badge bg-warning-subtle text-warning border border-warning px-2 py-1">
                                                                        <i class="bi bi-hourglass-split me-1"></i> QR VERIFY
                                                                    </span>
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
                                                                    <a href="<?= BASE_URL ?>/download.php?order_id=<?= $ord['id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill py-1 px-3" target="_blank">
                                                                        <i class="bi bi-download"></i> Test
                                                                    </a>
                                                                <?php else: ?>
                                                                    <a href="<?= BASE_URL ?>/admin/manage-orders.php?action=mark_paid&id=<?= $ord['id'] ?>" class="btn btn-sm btn-outline-success rounded-pill py-1 px-3">
                                                                        <i class="bi bi-check-lg"></i> Unlock
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

                        <!-- System Architecture & Shortcuts -->
                        <div class="col-xl-4">
                            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
                                <h6 class="fw-bold text-dark mb-3"><i class="bi bi-sliders text-primary me-2"></i>Quick Actions</h6>
                                <div class="d-grid gap-2">
                                    <a href="<?= BASE_URL ?>/admin/add-resource.php" class="btn btn-outline-primary text-start p-2 rounded-3 d-flex align-items-center gap-2">
                                        <i class="bi bi-file-earmark-plus fs-5 text-primary"></i>
                                        <div>
                                            <div class="fw-bold small">Publish New Resource</div>
                                            <div class="text-muted" style="font-size: 0.72rem;">Upload PDF/DOCX with preview</div>
                                        </div>
                                    </a>
                                    <a href="<?= BASE_URL ?>/admin/sales-reports.php" class="btn btn-outline-secondary text-start p-2 rounded-3 d-flex align-items-center gap-2">
                                        <i class="bi bi-graph-up-arrow fs-5 text-secondary"></i>
                                        <div>
                                            <div class="fw-bold small">Campus Analytics</div>
                                            <div class="text-muted" style="font-size: 0.72rem;">Review Mark, Mahendra, Deukhuri, Babai sales</div>
                                        </div>
                                    </a>
                                    <button type="button" class="btn btn-outline-dark text-start p-2 rounded-3 d-flex align-items-center gap-2" onclick="document.getElementById('settings-tab').click();">
                                        <i class="bi bi-key-fill fs-5 text-warning"></i>
                                        <div>
                                            <div class="fw-bold small">Change Admin ID / Password</div>
                                            <div class="text-muted" style="font-size: 0.72rem;">Update backoffice security credentials</div>
                                        </div>
                                    </button>
                                </div>
                            </div>

                            <div class="card border-0 shadow-sm rounded-4 p-4 text-white" style="background: linear-gradient(135deg, #091a2c 0%, #173d63 100%);">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <i class="bi bi-shield-lock-fill text-info fs-4"></i>
                                    <h6 class="fw-bold text-white mb-0">System Security Active</h6>
                                </div>
                                <p class="small text-white-50 mb-3" style="line-height: 1.5;">
                                    Protected storage rules (`.htaccess` Deny from all) prevent unauthorized downloads. eSewa transactions are validated via HMAC-SHA256 signatures.
                                </p>
                                <span class="badge bg-success bg-opacity-25 text-success border border-success py-2">
                                    <i class="bi bi-check-circle me-1"></i> Core PHP &bull; MySQL &bull; XAMPP
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 2: CHANGE ADMIN ID & PASSWORD SETTINGS -->
                <div class="tab-pane fade <?= $active_tab === 'settings' ? 'show active' : '' ?>" id="tab-settings" role="tabpanel">
                    <div class="row g-4">
                        <!-- Left Column: Update Admin ID & Profile -->
                        <div class="col-lg-6">
                            <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white h-100">
                                <div class="d-flex align-items-center gap-3 mb-4">
                                    <div class="p-3 bg-primary bg-opacity-10 text-primary rounded-3 fs-4">
                                        <i class="bi bi-person-badge-fill"></i>
                                    </div>
                                    <div>
                                        <h5 class="fw-bold text-dark mb-0">Admin Identity &amp; Login ID</h5>
                                        <span class="small text-muted">Change your administrator email and contact profile</span>
                                    </div>
                                </div>

                                <?php if (!empty($profile_msg)): ?>
                                    <div class="alert alert-<?= $profile_status ?> py-2 px-3 small rounded-3 mb-4">
                                        <i class="bi <?= $profile_status === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill' ?> me-2"></i>
                                        <?= htmlspecialchars($profile_msg) ?>
                                    </div>
                                <?php endif; ?>

                                <form action="" method="POST">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="update_profile">

                                    <div class="mb-3">
                                        <label class="form-label small fw-bold text-muted">Administrator Full Name *</label>
                                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($current_admin['name']) ?>" required>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label small fw-bold text-muted">Admin Login ID / Email *</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light"><i class="bi bi-envelope-at-fill"></i></span>
                                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($current_admin['email']) ?>" required>
                                        </div>
                                        <span class="form-text small text-muted">You will use this email address to log in to the admin console.</span>
                                    </div>

                                    <div class="mb-4">
                                        <label class="form-label small fw-bold text-muted">Contact Phone (Nepal)</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light"><i class="bi bi-telephone-fill"></i></span>
                                            <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($current_admin['phone'] ?? '') ?>" placeholder="98XXXXXXXX">
                                        </div>
                                    </div>

                                    <button type="submit" class="btn btn-hub-primary px-4 py-2 fw-semibold">
                                        <i class="bi bi-check2-circle me-1"></i> Save Profile &amp; Login ID
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Right Column: Change Password -->
                        <div class="col-lg-6">
                            <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white h-100">
                                <div class="d-flex align-items-center gap-3 mb-4">
                                    <div class="p-3 bg-danger bg-opacity-10 text-danger rounded-3 fs-4">
                                        <i class="bi bi-key-fill"></i>
                                    </div>
                                    <div>
                                        <h5 class="fw-bold text-dark mb-0">Change Admin Password</h5>
                                        <span class="small text-muted">Update your secure login password (BCRYPT encrypted)</span>
                                    </div>
                                </div>

                                <?php if (!empty($pwd_msg)): ?>
                                    <div class="alert alert-<?= $pwd_status ?> py-2 px-3 small rounded-3 mb-4">
                                        <i class="bi <?= $pwd_status === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill' ?> me-2"></i>
                                        <?= htmlspecialchars($pwd_msg) ?>
                                    </div>
                                <?php endif; ?>

                                <form action="" method="POST">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="change_password">


                                    <div class="mb-3">
                                        <label class="form-label small fw-bold text-muted">Current Password *</label>
                                        <input type="password" name="current_password" class="form-control" placeholder="Enter current password (or admin123)" required>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label small fw-bold text-muted">New Password *</label>
                                        <input type="password" name="new_password" class="form-control" placeholder="Min. 6 characters" required>
                                    </div>

                                    <div class="mb-4">
                                        <label class="form-label small fw-bold text-muted">Confirm New Password *</label>
                                        <input type="password" name="confirm_password" class="form-control" placeholder="Re-enter new password" required>
                                    </div>

                                    <div class="p-3 bg-light rounded-3 border small text-muted mb-4">
                                        <i class="bi bi-info-circle-fill text-primary me-1"></i>
                                        Once updated, you can log in directly using your new password. Passwords are saved with standard 60-character BCRYPT salt hashes.
                                    </div>

                                    <button type="submit" class="btn btn-danger px-4 py-2 fw-semibold">
                                        <i class="bi bi-lock-fill me-1"></i> Update Admin Password
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 3: SERVICE INQUIRIES -->
                <div class="tab-pane fade" id="tab-inquiries" role="tabpanel">
                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
                        <div class="card-header bg-white p-4 border-bottom">
                            <h5 class="fw-bold mb-0 text-dark">Research Support &amp; Formatting Inquiries</h5>
                            <span class="small text-muted">Submissions from students requesting custom thesis review, APA 7, or data analysis</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light small">
                                        <tr>
                                            <th class="ps-4">Student Name</th>
                                            <th>Contact Phone</th>
                                            <th>Campus &amp; Program</th>
                                            <th>Requested Service</th>
                                            <th>Deadline</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($service_requests)): ?>
                                            <tr><td colspan="6" class="text-center py-5 text-muted">No student inquiries received yet.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($service_requests as $sr): ?>
                                                <tr>
                                                    <td class="ps-4">
                                                        <div class="fw-bold text-dark small"><?= htmlspecialchars($sr['name']) ?></div>
                                                        <div class="small text-muted"><?= htmlspecialchars($sr['email']) ?></div>
                                                    </td>
                                                    <td>
                                                        <a href="https://wa.me/977<?= preg_replace('/[^0-9]/', '', $sr['phone']) ?>" target="_blank" class="btn btn-sm btn-outline-success py-0 px-2 rounded small">
                                                            <i class="bi bi-whatsapp"></i> <?= htmlspecialchars($sr['phone']) ?>
                                                        </a>
                                                    </td>
                                                    <td class="small">
                                                        <div><?= htmlspecialchars($sr['campus'] ?: 'General') ?></div>
                                                        <span class="text-muted"><?= htmlspecialchars($sr['program'] ?: '-') ?></span>
                                                    </td>
                                                    <td class="small fw-semibold text-primary">
                                                        <?= htmlspecialchars($sr['service_type']) ?>
                                                    </td>
                                                    <td class="small text-muted">
                                                        <?= $sr['deadline'] ? date('M d, Y', strtotime($sr['deadline'])) : 'Flexible' ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-warning-subtle text-warning border border-warning px-2 py-1">
                                                            <?= htmlspecialchars($sr['status']) ?>
                                                        </span>
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
    </div>
</div>

<!-- Bootstrap 5 Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Deep-linking helper for tabs (e.g. #settings or #inquiries)
document.addEventListener('DOMContentLoaded', () => {
    if (window.location.hash === '#settings') {
        const settingsTab = document.getElementById('settings-tab');
        if (settingsTab) {
            const tabTrigger = new bootstrap.Tab(settingsTab);
            tabTrigger.show();
        }
    }
    
    // Sidebar link listener
    const sidebarSettingsLink = document.getElementById('sidebarSettingsLink');
    if (sidebarSettingsLink) {
        sidebarSettingsLink.addEventListener('click', (e) => {
            e.preventDefault();
            const settingsTab = document.getElementById('settings-tab');
            if (settingsTab) {
                const tabTrigger = new bootstrap.Tab(settingsTab);
                tabTrigger.show();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        });
    }
});
</script>

<script src="<?= BASE_URL ?>/assets/js/experience.js?v=5"></script>
</body>
</html>
