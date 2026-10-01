<?php
/**
 * AcademicHub Nepal - Executive Students Directory & Governance Console
 * File: admin/manage-students.php
 * Strictly restricted to Super Administrators & Authorized Admins
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth_check.php';

require_admin();
require_admin_permission('manage_students');

$is_sa = is_superadmin();
$current_admin_id = $_SESSION['user_id'];
$active_page = 'manage-students';

// ---------------------------------------------------------
// POST ACTIONS (Super Admin / Admin Student Management)
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        set_flash_message('danger', 'Security validation expired. Action aborted.');
        header('Location: ' . BASE_URL . '/admin/manage-students.php');
        exit;
    }

    $action = $_POST['action'] ?? '';

    // 1. UPDATE STUDENT PROFILE
    if ($action === 'update_student') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $campus = trim($_POST['campus'] ?? 'General');
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if ($id <= 0 || empty($name) || empty($email)) {
            set_flash_message('danger', 'Valid Student ID, Full Name, and Email are mandatory.');
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            set_flash_message('danger', 'Please provide a valid email format.');
        } else {
            try {
                // Check email conflict with other users
                $chk = $pdo->prepare("SELECT id FROM users WHERE email = :email AND id != :id LIMIT 1");
                $chk->execute([':email' => $email, ':id' => $id]);
                if ($chk->fetch()) {
                    set_flash_message('danger', "Email '{$email}' is already in use by another account.");
                } else {
                    $upd = $pdo->prepare("
                        UPDATE users SET 
                            name = :name,
                            email = :email,
                            phone = :phone,
                            campus = :campus,
                            is_active = :active
                        WHERE id = :id AND role = 'student'
                    ");
                    $upd->execute([
                        ':name' => $name,
                        ':email' => $email,
                        ':phone' => $phone,
                        ':campus' => $campus,
                        ':active' => $is_active,
                        ':id' => $id
                    ]);

                    log_admin_activity($pdo, 'UPDATE_STUDENT', "Updated student profile '{$name}' (ID #{$id})", 'student', $id);
                    set_flash_message('success', "Student profile for '{$name}' updated successfully.");
                }
            } catch (Exception $e) {
                set_flash_message('danger', 'Error updating student: ' . safe_error_message($e));
            }
        }
        header('Location: ' . BASE_URL . '/admin/manage-students.php');
        exit;
    }

    // 2. TOGGLE STATUS (ACTIVE / SUSPENDED)
    if ($action === 'toggle_status') {
        $id = (int)($_POST['id'] ?? 0);
        $new_status = (int)($_POST['status'] ?? 0);

        try {
            $stmt = $pdo->prepare("SELECT name FROM users WHERE id = :id AND role = 'student'");
            $stmt->execute([':id' => $id]);
            $student_name = $stmt->fetchColumn();

            if ($student_name) {
                $upd = $pdo->prepare("UPDATE users SET is_active = :status WHERE id = :id");
                $upd->execute([':status' => $new_status, ':id' => $id]);

                $statusLabel = $new_status === 1 ? 'Activated' : 'Suspended';
                log_admin_activity($pdo, 'TOGGLE_STUDENT_STATUS', "Student '{$student_name}' (ID #{$id}) {$statusLabel}", 'student', $id);
                set_flash_message('success', "Student '{$student_name}' account is now " . strtolower($statusLabel) . ".");
            }
        } catch (Exception $e) {
            set_flash_message('danger', 'Status update failed: ' . safe_error_message($e));
        }
        header('Location: ' . BASE_URL . '/admin/manage-students.php');
        exit;
    }

    // 3. RESET STUDENT PASSWORD
    if ($action === 'reset_password') {
        $id = (int)($_POST['id'] ?? 0);
        $new_password = $_POST['new_password'] ?? '';

        if ($id <= 0 || strlen($new_password) < 6) {
            set_flash_message('danger', 'Password must be at least 6 characters long.');
        } else {
            try {
                $stmt = $pdo->prepare("SELECT name FROM users WHERE id = :id AND role = 'student'");
                $stmt->execute([':id' => $id]);
                $student_name = $stmt->fetchColumn();

                if ($student_name) {
                    $hashed = password_hash($new_password, PASSWORD_BCRYPT);
                    $upd = $pdo->prepare("UPDATE users SET password = :pwd WHERE id = :id");
                    $upd->execute([':pwd' => $hashed, ':id' => $id]);

                    log_admin_activity($pdo, 'RESET_STUDENT_PASSWORD', "Reset password for student '{$student_name}' (ID #{$id})", 'student', $id);
                    set_flash_message('success', "Password for '{$student_name}' has been reset successfully.");
                }
            } catch (Exception $e) {
                set_flash_message('danger', 'Failed to reset password: ' . safe_error_message($e));
            }
        }
        header('Location: ' . BASE_URL . '/admin/manage-students.php');
        exit;
    }

    // 4. DELETE STUDENT ACCOUNT (SUPER ADMIN EXCLUSIVE)
    if ($action === 'delete_student') {
        if (!$is_sa) {
            set_flash_message('danger', 'Only Super Administrators can permanently delete student accounts.');
            header('Location: ' . BASE_URL . '/admin/manage-students.php');
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            set_flash_message('danger', 'Invalid student identifier.');
        } else {
            try {
                $stmt = $pdo->prepare("SELECT name, profile_image FROM users WHERE id = :id AND role = 'student'");
                $stmt->execute([':id' => $id]);
                $target = $stmt->fetch();

                if ($target) {
                    // Unlink avatar if exists
                    if (!empty($target['profile_image'])) {
                        $avatarPath = dirname(__DIR__) . '/uploads/avatars/' . $target['profile_image'];
                        if (file_exists($avatarPath)) {
                            @unlink($avatarPath);
                        }
                    }

                    $del = $pdo->prepare("DELETE FROM users WHERE id = :id AND role = 'student'");
                    $del->execute([':id' => $id]);

                    log_admin_activity($pdo, 'DELETE_STUDENT', "Permanently deleted student '{$target['name']}' (ID #{$id})", 'student', $id);
                    set_flash_message('success', "Student '{$target['name']}' and associated records removed.");
                }
            } catch (Exception $e) {
                set_flash_message('danger', 'Error deleting student: ' . safe_error_message($e));
            }
        }
        header('Location: ' . BASE_URL . '/admin/manage-students.php');
        exit;
    }

    // 5. CREATE NEW STUDENT (ADMIN DIRECT REGISTRATION)
    if ($action === 'create_student') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $phone = trim($_POST['phone'] ?? '');
        $campus = trim($_POST['campus'] ?? 'General');

        if (empty($name) || empty($email) || empty($password)) {
            set_flash_message('danger', 'Full Name, Email, and Password are required.');
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            set_flash_message('danger', 'Please provide a valid email format.');
        } elseif (strlen($password) < 6) {
            set_flash_message('danger', 'Password must be at least 6 characters.');
        } else {
            try {
                $chk = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
                $chk->execute([':email' => $email]);
                if ($chk->fetch()) {
                    set_flash_message('danger', "An account with email '{$email}' already exists.");
                } else {
                    $hashed = password_hash($password, PASSWORD_BCRYPT);
                    $ins = $pdo->prepare("
                        INSERT INTO users (name, email, password, phone, campus, role, is_active, created_at)
                        VALUES (:name, :email, :pwd, :phone, :campus, 'student', 1, NOW())
                    ");
                    $ins->execute([
                        ':name' => $name,
                        ':email' => $email,
                        ':pwd' => $hashed,
                        ':phone' => $phone,
                        ':campus' => $campus
                    ]);
                    $newId = $pdo->lastInsertId();
                    log_admin_activity($pdo, 'CREATE_STUDENT', "Registered student '{$name}' (ID #{$newId})", 'student', $newId);
                    set_flash_message('success', "Student '{$name}' registered successfully.");
                }
            } catch (Exception $e) {
                set_flash_message('danger', 'Error adding student: ' . safe_error_message($e));
            }
        }
        header('Location: ' . BASE_URL . '/admin/manage-students.php');
        exit;
    }
}

// ---------------------------------------------------------
// QUERY DATA FOR DIRECTORY
// ---------------------------------------------------------
try {
    $stmt = $pdo->query("
        SELECT u.*, 
               COUNT(o.id) as total_orders,
               COUNT(CASE WHEN o.payment_status = 'PAID' THEN 1 END) as paid_orders,
               COALESCE(SUM(CASE WHEN o.payment_status = 'PAID' THEN o.amount ELSE 0 END), 0) as total_spent,
               MAX(o.created_at) as last_order_date
        FROM users u
        LEFT JOIN orders o ON u.id = o.user_id
        WHERE u.role = 'student'
        GROUP BY u.id
        ORDER BY u.id DESC
    ");
    $students = $stmt->fetchAll();

    // Query all orders pre-grouped by user_id for instant modal viewing
    $ordersStmt = $pdo->query("
        SELECT o.id, o.user_id, o.transaction_uuid, o.amount, o.payment_status, o.payment_method, o.transaction_code, o.created_at,
               r.title as resource_title
        FROM orders o
        JOIN resources r ON o.resource_id = r.id
        ORDER BY o.id DESC
    ");
    $allOrders = $ordersStmt->fetchAll();
    $studentOrders = [];
    foreach ($allOrders as $ord) {
        $studentOrders[$ord['user_id']][] = $ord;
    }

    // Key Performance Metrics
    $totalStudents = count($students);
    $activeStudents = count(array_filter($students, fn($s) => (int)$s['is_active'] === 1));
    $payingStudents = count(array_filter($students, fn($s) => (int)$s['paid_orders'] > 0));
    $totalRevenue = array_sum(array_column($students, 'total_spent'));
} catch (Exception $e) {
    die("Database Error: " . safe_error_message($e));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Directory &amp; Governance | AcademicHub Nepal</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css?v=<?= time() ?>" rel="stylesheet">
    <style>
    .table-enterprise {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }
    .table-enterprise thead th {
        background-color: #f8fafc !important;
        color: #64748b !important;
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        padding: 14px 18px;
        border-bottom: 1px solid #e2e8f0 !important;
        white-space: nowrap;
    }
    .table-enterprise tbody tr {
        transition: background-color 0.15s ease;
    }
    .table-enterprise tbody tr:hover {
        background-color: #f8fafc !important;
    }
    .table-enterprise tbody td {
        padding: 14px 18px;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
    }
    .pulse-dot {
        display: inline-block;
        border-radius: 50%;
        animation: pulseGlow 2s infinite;
    }
    .pulse-success {
        background-color: #10b981;
        box-shadow: 0 0 0 rgba(16, 185, 129, 0.4);
    }
    @keyframes pulseGlow {
        0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
        70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); }
        100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
    }
    .status-chip {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .chip-paid {
        background-color: rgba(16, 185, 129, 0.1);
        color: #059669;
        border: 1px solid rgba(16, 185, 129, 0.25);
    }
    .chip-failed {
        background-color: rgba(239, 68, 68, 0.1);
        color: #dc2626;
        border: 1px solid rgba(239, 68, 68, 0.25);
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
                            <li class="breadcrumb-item active" aria-current="page">Student Governance</li>
                        </ol>
                    </nav>
                    <h2 class="h3 fw-bold text-dark mb-0">
                        <i class="bi bi-people-fill text-primary me-2"></i> Student Directory &amp; Accounts
                    </h2>
                    <p class="text-muted small mb-0">Oversee student enrollment, verify campus affiliations, manage account access, and monitor purchasing history.</p>
                </div>
                
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <button type="button" class="btn btn-hub-primary btn-sm rounded-pill px-3 shadow-sm d-inline-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                        <i class="bi bi-person-plus-fill"></i> Add New Student
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3 shadow-sm d-inline-flex align-items-center gap-1" onclick="exportTableToCSV('studentsTable', 'student_directory.csv')">
                        <i class="bi bi-file-earmark-spreadsheet text-success"></i> Export CSV
                    </button>
                    <button type="button" class="btn btn-light btn-sm rounded-pill px-3 shadow-sm border d-inline-flex align-items-center gap-1" onclick="window.print()">
                        <i class="bi bi-printer text-muted"></i> Print
                    </button>
                    <?php if ($is_sa): ?>
                        <span class="badge bg-warning bg-opacity-25 text-warning border border-warning border-opacity-50 px-3 py-2 rounded-pill fw-bold" style="font-size:0.75rem;">
                            <i class="bi bi-shield-shaded me-1"></i> SUPER ADMIN ACCESS
                        </span>
                    <?php endif; ?>
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
                                <span class="text-muted small fw-semibold d-block">Total Registered</span>
                                <h3 class="fw-bold mb-0 text-dark"><?= number_format($totalStudents) ?></h3>
                                <span class="small text-muted" style="font-size: 0.72rem;">Candidate accounts</span>
                            </div>
                            <div class="rounded-circle bg-primary bg-opacity-10 text-primary p-3 fs-3 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                                <i class="bi bi-mortarboard-fill"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-xl-3">
                    <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-success border-4">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted small fw-semibold d-block">Active &amp; Verified</span>
                                <h3 class="fw-bold mb-0 text-success"><?= number_format($activeStudents) ?></h3>
                                <span class="small text-success d-inline-flex align-items-center gap-1" style="font-size: 0.72rem;">
                                    <span class="pulse-dot pulse-success" style="width: 6px; height: 6px;"></span> 
                                    <?= $totalStudents > 0 ? round(($activeStudents / $totalStudents) * 100) : 0 ?>% Active rate
                                </span>
                            </div>
                            <div class="rounded-circle bg-success bg-opacity-10 text-success p-3 fs-3 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                                <i class="bi bi-person-check-fill"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-xl-3">
                    <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-info border-4">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted small fw-semibold d-block">Enrolled Buyers</span>
                                <h3 class="fw-bold mb-0 text-info"><?= number_format($payingStudents) ?></h3>
                                <span class="small text-muted" style="font-size: 0.72rem;">Unlocked paid materials</span>
                            </div>
                            <div class="rounded-circle bg-info bg-opacity-10 text-info p-3 fs-3 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                                <i class="bi bi-cart-check-fill"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-xl-3">
                    <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 border-start border-warning border-4">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted small fw-semibold d-block">Total Student Revenue</span>
                                <h3 class="fw-bold mb-0 text-warning"><?= format_npr($totalRevenue) ?></h3>
                                <span class="small text-muted" style="font-size: 0.72rem;">Verified eSewa QR volume</span>
                            </div>
                            <div class="rounded-circle bg-warning bg-opacity-10 text-warning p-3 fs-3 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                                <i class="bi bi-cash-stack"></i>
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
                        <div class="col-md-5 col-lg-4">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                                <input type="text" id="studentsLiveSearch" data-table-search="studentsTable" class="form-control bg-light border-start-0 ps-0" placeholder="Search name, email, phone, campus, or ID...">
                            </div>
                        </div>

                        <!-- Campus Filter -->
                        <div class="col-md-3 col-lg-3">
                            <select class="form-select form-select-sm bg-light" id="filterCampus" onchange="applyTableFilters()">
                                <option value="">All Campuses</option>
                                <option value="Mark International College">Mark International College</option>
                                <option value="Mahendra Multiple Campus">Mahendra Multiple Campus</option>
                                <option value="Deukhuri Bahumukhi Campus">Deukhuri Bahumukhi Campus</option>
                                <option value="Babai Campus">Babai Campus</option>
                                <option value="Gorkha Campus">Gorkha Campus</option>
                                <option value="Other Campuses">Other Campuses (TU)</option>
                                <option value="General">General Campus</option>
                            </select>
                        </div>

                        <!-- Customer Status Filter -->
                        <div class="col-md-2 col-lg-2">
                            <select class="form-select form-select-sm bg-light" id="filterSpending" onchange="applyTableFilters()">
                                <option value="">All Students</option>
                                <option value="paying">Paying Customers</option>
                                <option value="non-paying">Free Accounts</option>
                            </select>
                        </div>

                        <!-- Status Filter -->
                        <div class="col-md-2 col-lg-3 text-md-end">
                            <div class="d-inline-flex align-items-center gap-2">
                                <span class="badge bg-light text-dark border px-3 py-2 rounded-pill">
                                    Showing <strong class="text-primary" id="visibleStudentCount"><?= $totalStudents ?></strong> of <?= $totalStudents ?> students
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Enterprise Student Table Card -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-5">
                <div class="table-responsive">
                    <table class="table table-enterprise table-hover align-middle mb-0" id="studentsTable">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4" style="width: 100px;">Student ID</th>
                                <th>Student Profile</th>
                                <th>Campus Affiliation</th>
                                <th>Purchases &amp; Orders</th>
                                <th>Lifetime Spent</th>
                                <th>Account Status</th>
                                <th class="text-end pe-4" style="width: 170px;">Governance Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($students)): ?>
                                <tr class="no-search">
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="bi bi-mortarboard fs-1 d-block mb-2 text-secondary"></i>
                                        No student accounts registered in the database yet.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($students as $s): 
                                    $hasOrders = (int)$s['total_orders'] > 0;
                                    $hasPaid = (int)$s['paid_orders'] > 0;
                                    $isActive = (int)($s['is_active'] ?? 1) === 1;
                                    $ordersList = $studentOrders[$s['id']] ?? [];
                                    $jsonStudent = htmlspecialchars(json_encode([
                                        'id' => $s['id'],
                                        'name' => $s['name'],
                                        'email' => $s['email'],
                                        'phone' => $s['phone'] ?? '',
                                        'campus' => $s['campus'] ?? 'General',
                                        'is_active' => $isActive ? 1 : 0,
                                        'created_at' => date('M d, Y h:i A', strtotime($s['created_at'])),
                                        'last_login' => !empty($s['last_login']) ? date('M d, Y h:i A', strtotime($s['last_login'])) : 'Never',
                                        'total_orders' => $s['total_orders'],
                                        'paid_orders' => $s['paid_orders'],
                                        'total_spent' => format_npr($s['total_spent']),
                                        'orders' => $ordersList
                                    ]));
                                ?>
                                    <tr data-campus="<?= htmlspecialchars($s['campus'] ?: 'General') ?>" data-spending="<?= $hasPaid ? 'paying' : 'non-paying' ?>">
                                        <!-- ID -->
                                        <td class="ps-4">
                                            <span class="badge bg-light text-muted border font-monospace px-2 py-1">
                                                #STU-<?= str_pad($s['id'], 4, '0', STR_PAD_LEFT) ?>
                                            </span>
                                        </td>

                                        <!-- Profile, Avatar, Name & Contacts -->
                                        <td>
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="position-relative">
                                                    <?= render_user_avatar($s['profile_image'] ?? null, $s['name'] ?? 'Student', 42, 'rounded-circle shadow-xs border') ?>
                                                    <?php if ($isActive): ?>
                                                        <span class="position-absolute bottom-0 end-0 bg-success border border-white border-2 rounded-circle p-1" style="width: 12px; height: 12px;" title="Active Student"></span>
                                                    <?php else: ?>
                                                        <span class="position-absolute bottom-0 end-0 bg-danger border border-white border-2 rounded-circle p-1" style="width: 12px; height: 12px;" title="Account Suspended"></span>
                                                    <?php endif; ?>
                                                </div>
                                                <div>
                                                    <div class="fw-bold text-dark d-flex align-items-center gap-1">
                                                        <span><?= htmlspecialchars($s['name']) ?></span>
                                                        <?php if ($hasPaid): ?>
                                                            <i class="bi bi-patch-check-fill text-primary" title="Verified Customer" style="font-size: 0.85rem;"></i>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="small text-muted d-flex align-items-center gap-1" style="font-size: 0.78rem;">
                                                        <span><?= htmlspecialchars($s['email']) ?></span>
                                                        <button type="button" class="btn btn-link btn-xs p-0 text-muted" onclick="copyToClipboard('<?= htmlspecialchars($s['email']) ?>', 'Student Email')" title="Copy Email">
                                                            <i class="bi bi-copy"></i>
                                                        </button>
                                                    </div>
                                                    <?php if (!empty($s['phone'])): ?>
                                                        <div class="small text-muted font-monospace d-flex align-items-center gap-1" style="font-size: 0.75rem;">
                                                            <i class="bi bi-telephone text-secondary" style="font-size: 0.7rem;"></i>
                                                            <a href="tel:<?= htmlspecialchars($s['phone']) ?>" class="text-decoration-none text-muted"><?= htmlspecialchars($s['phone']) ?></a>
                                                            <button type="button" class="btn btn-link btn-xs p-0 text-muted" onclick="copyToClipboard('<?= htmlspecialchars($s['phone']) ?>', 'Phone Number')" title="Copy Phone">
                                                                <i class="bi bi-copy"></i>
                                                            </button>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Campus Affiliation -->
                                        <td>
                                            <span class="badge bg-light text-secondary border px-2 py-1 d-inline-flex align-items-center gap-1" style="font-size: 0.78rem;">
                                                <i class="bi bi-buildings"></i> <?= htmlspecialchars($s['campus'] ?: 'General') ?>
                                            </span>
                                            <div class="text-muted mt-1" style="font-size: 0.72rem;">
                                                Joined: <?= date('M d, Y', strtotime($s['created_at'])) ?>
                                            </div>
                                        </td>

                                        <!-- Purchases & Orders -->
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <?php if ($hasPaid): ?>
                                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-3 py-1">
                                                        <i class="bi bi-cart-check me-1"></i><?= $s['paid_orders'] ?> Unlocked
                                                    </span>
                                                <?php elseif ($hasOrders): ?>
                                                    <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 rounded-pill px-2 py-1">
                                                        <i class="bi bi-hourglass-split me-1"></i><?= $s['total_orders'] ?> Pending
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-light text-muted border rounded-pill px-2 py-1">
                                                        No Orders Yet
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </td>

                                        <!-- Total Spent -->
                                        <td>
                                            <div class="fw-bold font-monospace <?= (float)$s['total_spent'] > 0 ? 'text-success' : 'text-muted' ?>">
                                                <?= format_npr($s['total_spent']) ?>
                                            </div>
                                        </td>

                                        <!-- Status Badge & 1-Click Toggle -->
                                        <td>
                                            <form action="" method="POST" class="d-inline">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                                <input type="hidden" name="status" value="<?= $isActive ? 0 : 1 ?>">
                                                <button type="submit" class="btn btn-sm border-0 p-0" title="Click to toggle active status">
                                                    <?php if ($isActive): ?>
                                                        <span class="status-chip chip-paid d-inline-flex align-items-center gap-1">
                                                            <span class="pulse-dot pulse-success"></span> Active
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="status-chip chip-failed d-inline-flex align-items-center gap-1">
                                                            <i class="bi bi-slash-circle-fill"></i> Suspended
                                                        </span>
                                                    <?php endif; ?>
                                                </button>
                                            </form>
                                        </td>

                                        <!-- Actions -->
                                        <td class="text-end pe-4">
                                            <div class="btn-group btn-group-sm shadow-sm rounded-pill overflow-hidden border">
                                                <!-- View Dossier -->
                                                <button type="button" class="btn btn-light px-2" onclick="openStudentDossier(<?= $jsonStudent ?>)" title="View Full Profile &amp; Orders">
                                                    <i class="bi bi-eye text-primary"></i>
                                                </button>
                                                <!-- Edit Details -->
                                                <button type="button" class="btn btn-light px-2" onclick="openEditStudentModal(<?= $jsonStudent ?>)" title="Edit Student">
                                                    <i class="bi bi-pencil text-secondary"></i>
                                                </button>
                                                <!-- Reset Password -->
                                                <button type="button" class="btn btn-light px-2" onclick="openResetPasswordModal(<?= $s['id'] ?>, '<?= htmlspecialchars(addslashes($s['name'])) ?>')" title="Reset Password">
                                                    <i class="bi bi-key text-warning"></i>
                                                </button>
                                                <!-- Delete (Super Admin only) -->
                                                <?php if ($is_sa): ?>
                                                    <button type="button" class="btn btn-light px-2" onclick="confirmDeleteStudent(<?= $s['id'] ?>, '<?= htmlspecialchars(addslashes($s['name'])) ?>')" title="Delete Student">
                                                        <i class="bi bi-trash text-danger"></i>
                                                    </button>
                                                <?php endif; ?>
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

<!-- ================= MODAL 0: ADD NEW STUDENT ================= -->
<div class="modal fade" id="addStudentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-primary text-white p-3" style="background: linear-gradient(135deg, #0f2942 0%, #1e4976 100%) !important;">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-person-plus-fill me-2"></i> Register New Student
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="create_student">

                <div class="modal-body p-4 bg-light">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Full Name *</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Ramesh Shrestha" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Email Address *</label>
                        <input type="email" name="email" class="form-control" placeholder="student@campus.edu.np" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Mobile Number (Nepal)</label>
                        <input type="text" name="phone" class="form-control" placeholder="98XXXXXXXX">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">College / Campus</label>
                        <select name="campus" class="form-select">
                            <option value="Mark International College">Mark International College</option>
                            <option value="Mahendra Multiple Campus">Mahendra Multiple Campus</option>
                            <option value="Deukhuri Bahumukhi Campus">Deukhuri Bahumukhi Campus</option>
                            <option value="Babai Campus">Babai Campus</option>
                            <option value="Gorkha Campus">Gorkha Campus</option>
                            <option value="Other Campuses" selected>Other Campus (TU)</option>
                            <option value="General">General Campus</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Initial Password *</label>
                        <div class="input-group">
                            <input type="password" name="password" id="addStudentPassword" class="form-control" placeholder="Min. 6 chars" required minlength="6">
                            <button type="button" class="btn btn-outline-secondary password-toggle-btn border" onclick="togglePasswordVisibility('addStudentPassword', this)">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-xs mt-2 py-1 px-2 rounded-2" onclick="generateRandomPassword('addStudentPassword')">
                            <i class="bi bi-magic me-1"></i> Auto-Generate Safe Password
                        </button>
                    </div>
                </div>

                <div class="modal-footer bg-white border-top">
                    <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-hub-primary btn-sm rounded-pill px-4 fw-semibold shadow-sm">
                        <i class="bi bi-check-lg me-1"></i> Create Student Account
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ================= MODAL 1: STUDENT FULL DOSSIER & ORDER HISTORY ================= -->
<div class="modal fade" id="studentDossierModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <!-- Modal Header -->
            <div class="modal-header bg-primary text-white p-4" style="background: linear-gradient(135deg, #0f2942 0%, #1e4976 100%) !important;">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-white text-primary d-flex align-items-center justify-content-center shadow" style="width: 56px; height: 56px; font-size: 1.5rem;" id="dosAvatarInitial">
                        <i class="bi bi-mortarboard-fill"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0 text-white" id="dosName">Student Name</h5>
                        <div class="small text-light opacity-75" id="dosEmail">student@campus.edu.np</div>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4 bg-light">
                <!-- Summary Metrics Ribbon -->
                <div class="row g-2 mb-4 text-center">
                    <div class="col-4">
                        <div class="p-3 bg-white rounded-3 shadow-xs border">
                            <span class="small text-muted d-block">Campus</span>
                            <strong class="text-dark small" id="dosCampus">Mark International</strong>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-3 bg-white rounded-3 shadow-xs border">
                            <span class="small text-muted d-block">Paid Downloads</span>
                            <strong class="text-primary fs-5" id="dosPaidOrders">0</strong>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-3 bg-white rounded-3 shadow-xs border">
                            <span class="small text-muted d-block">Total Lifetime Invested</span>
                            <strong class="text-success fs-5 font-monospace" id="dosTotalSpent">NPR 0</strong>
                        </div>
                    </div>
                </div>

                <!-- Account Metadata Details -->
                <div class="card border-0 rounded-3 shadow-sm bg-white p-3 mb-4">
                    <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">
                        <i class="bi bi-info-circle text-primary me-2"></i> Account Specifications
                    </h6>
                    <div class="row g-2 small">
                        <div class="col-sm-6">
                            <span class="text-muted">Student Identification:</span>
                            <strong class="font-monospace text-dark ms-1" id="dosId">#STU-0000</strong>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted">Mobile Number:</span>
                            <strong class="text-dark ms-1" id="dosPhone">—</strong>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted">Registration Date:</span>
                            <strong class="text-dark ms-1" id="dosCreatedAt">—</strong>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted">Last Activity / Login:</span>
                            <strong class="text-dark ms-1" id="dosLastLogin">—</strong>
                        </div>
                    </div>
                </div>

                <!-- Purchased Materials & Order History -->
                <div class="card border-0 rounded-3 shadow-sm bg-white p-3">
                    <h6 class="fw-bold text-dark border-bottom pb-2 mb-3 d-flex align-items-center justify-content-between">
                        <span><i class="bi bi-receipt text-primary me-2"></i> Purchase &amp; Order History</span>
                        <span class="badge bg-light text-muted border font-monospace" id="dosOrderCountBadge">0 Orders</span>
                    </h6>

                    <div class="table-responsive" style="max-height: 240px;">
                        <table class="table table-sm table-hover align-middle mb-0" id="dosOrdersTable">
                            <thead class="bg-light small">
                                <tr>
                                    <th>Order UUID</th>
                                    <th>Resource Title</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody id="dosOrdersBody" class="small">
                                <!-- Populated dynamically by JS -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="modal-footer bg-white border-top">
                <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- ================= MODAL 2: EDIT STUDENT PROFILE ================= -->
<div class="modal fade" id="editStudentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-dark text-white p-3">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-pencil-square me-2 text-warning"></i> Edit Student Profile
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update_student">
                <input type="hidden" name="id" id="edit_id" value="">

                <div class="modal-body p-4 bg-light">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Full Name *</label>
                        <input type="text" name="name" id="edit_name" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Email Address *</label>
                        <input type="email" name="email" id="edit_email" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Mobile Number (Nepal)</label>
                        <input type="text" name="phone" id="edit_phone" class="form-control" placeholder="98XXXXXXXX">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">College / Campus</label>
                        <select name="campus" id="edit_campus" class="form-select">
                            <option value="Mark International College">Mark International College</option>
                            <option value="Mahendra Multiple Campus">Mahendra Multiple Campus</option>
                            <option value="Deukhuri Bahumukhi Campus">Deukhuri Bahumukhi Campus</option>
                            <option value="Babai Campus">Babai Campus</option>
                            <option value="Gorkha Campus">Gorkha Campus</option>
                            <option value="Other Campuses">Other Campuses (TU)</option>
                            <option value="General">General Campus</option>
                        </select>
                    </div>

                    <div class="form-check form-switch mt-3">
                        <input class="form-check-input" type="checkbox" name="is_active" id="edit_is_active" value="1">
                        <label class="form-check-label small fw-semibold text-dark" for="edit_is_active">
                            Account Active &amp; Permitted to Log In
                        </label>
                    </div>
                </div>

                <div class="modal-footer bg-white border-top">
                    <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm rounded-pill px-4 fw-semibold shadow-sm">
                        <i class="bi bi-save me-1"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ================= MODAL 3: RESET PASSWORD ================= -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-warning text-dark p-3">
                <h6 class="modal-title fw-bold">
                    <i class="bi bi-key-fill me-1"></i> Reset Student Password
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="id" id="reset_pwd_id" value="">

                <div class="modal-body p-3 bg-light">
                    <p class="small text-muted mb-2">Set a new password for <strong id="reset_pwd_name">Student</strong>:</p>
                    
                    <div class="input-group mb-2">
                        <input type="password" name="new_password" id="studentNewPassword" class="form-control form-control-sm" placeholder="Min. 6 chars" required minlength="6">
                        <button type="button" class="btn btn-outline-secondary btn-sm password-toggle-btn" onclick="togglePasswordVisibility('studentNewPassword', this)">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>

                    <button type="button" class="btn btn-outline-primary btn-xs w-100 py-1 rounded-2" onclick="generateRandomPassword('studentNewPassword')">
                        <i class="bi bi-magic me-1"></i> Auto-Generate Safe Password
                    </button>
                </div>

                <div class="modal-footer bg-white border-top p-2">
                    <button type="button" class="btn btn-light btn-sm rounded-pill px-2" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning btn-sm rounded-pill px-3 fw-semibold">Save New Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ================= MODAL 4: DELETE CONFIRMATION ================= -->
<div class="modal fade" id="deleteStudentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow rounded-4 overflow-hidden">
            <div class="p-4 text-center">
                <div class="rounded-circle bg-danger bg-opacity-10 text-danger mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 56px; height: 56px;">
                    <i class="bi bi-exclamation-triangle-fill fs-3"></i>
                </div>
                <h5 class="fw-bold text-dark mb-1">Delete Student?</h5>
                <p class="small text-muted mb-3">Permanently remove <strong id="delStudentName"></strong>? All downloads and activity logs associated with this candidate will be purged.</p>
                
                <form action="" method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete_student">
                    <input type="hidden" name="id" id="delStudentId" value="">
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
// Open Student Dossier (Slide-over Modal)
function openStudentDossier(data) {
    document.getElementById('dosName').innerText = data.name || 'Student';
    document.getElementById('dosEmail').innerText = data.email || '';
    document.getElementById('dosId').innerText = '#STU-' + String(data.id).padStart(4, '0');
    document.getElementById('dosPhone').innerText = data.phone || 'No phone provided';
    document.getElementById('dosCampus').innerText = data.campus || 'General';
    document.getElementById('dosCreatedAt').innerText = data.created_at || '—';
    document.getElementById('dosLastLogin').innerText = data.last_login || 'Never';
    document.getElementById('dosPaidOrders').innerText = data.paid_orders || '0';
    document.getElementById('dosTotalSpent').innerText = data.total_spent || 'NPR 0';
    document.getElementById('dosAvatarInitial').innerHTML = (data.name ? data.name.charAt(0).toUpperCase() : 'S');

    // Populate Orders Table
    var tbody = document.getElementById('dosOrdersBody');
    tbody.innerHTML = '';
    var orders = data.orders || [];
    document.getElementById('dosOrderCountBadge').innerText = orders.length + ' Orders';

    if (orders.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center py-3 text-muted">No orders or transactions recorded for this student yet.</td></tr>';
    } else {
        orders.forEach(function(o) {
            var statusBadge = '';
            if (o.payment_status === 'PAID') {
                statusBadge = '<span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-0">PAID</span>';
            } else if (o.payment_status === 'PENDING_VERIFICATION') {
                statusBadge = '<span class="badge bg-warning bg-opacity-25 text-dark border border-warning px-2 py-0">VERIFY</span>';
            } else {
                statusBadge = '<span class="badge bg-secondary bg-opacity-10 text-secondary px-2 py-0">' + o.payment_status + '</span>';
            }

            var tr = document.createElement('tr');
            tr.innerHTML = 
                '<td class="font-monospace text-muted">' + (o.transaction_uuid || '#ORD-' + o.id) + '</td>' +
                '<td class="fw-semibold text-truncate" style="max-width: 180px;">' + (o.resource_title || 'Reference Material') + '</td>' +
                '<td class="font-monospace fw-bold text-success">NPR ' + Number(o.amount).toLocaleString() + '</td>' +
                '<td>' + statusBadge + '</td>' +
                '<td class="text-muted">' + (o.created_at ? o.created_at.substring(0, 10) : '—') + '</td>';
            tbody.appendChild(tr);
        });
    }

    var modal = new bootstrap.Modal(document.getElementById('studentDossierModal'));
    modal.show();
}

// Open Edit Student Modal
function openEditStudentModal(data) {
    document.getElementById('edit_id').value = data.id || '';
    document.getElementById('edit_name').value = data.name || '';
    document.getElementById('edit_email').value = data.email || '';
    document.getElementById('edit_phone').value = data.phone || '';
    document.getElementById('edit_campus').value = data.campus || 'General';
    document.getElementById('edit_is_active').checked = parseInt(data.is_active) === 1;

    var modal = new bootstrap.Modal(document.getElementById('editStudentModal'));
    modal.show();
}

// Open Reset Password Modal
function openResetPasswordModal(id, name) {
    document.getElementById('reset_pwd_id').value = id;
    document.getElementById('reset_pwd_name').innerText = name;
    document.getElementById('studentNewPassword').value = '';

    var modal = new bootstrap.Modal(document.getElementById('resetPasswordModal'));
    modal.show();
}

// Auto-generate strong random password
function generateRandomPassword(targetId) {
    var chars = "abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789!@#$%";
    var pass = "";
    for (var i = 0; i < 10; i++) {
        pass += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    var input = document.getElementById(targetId);
    input.value = pass;
    input.type = "text";
}

// Confirm Delete Modal
function confirmDeleteStudent(id, name) {
    document.getElementById('delStudentId').value = id;
    document.getElementById('delStudentName').innerText = name;

    var modal = new bootstrap.Modal(document.getElementById('deleteStudentModal'));
    modal.show();
}

// Multi-attribute Table Filter (Campus & Spending)
function applyTableFilters() {
    var campusFilter = document.getElementById('filterCampus').value.toLowerCase();
    var spendingFilter = document.getElementById('filterSpending').value;
    var rows = document.querySelectorAll('#studentsTable tbody tr:not(.no-search)');
    var visibleCount = 0;

    rows.forEach(function(row) {
        var rowCampus = (row.getAttribute('data-campus') || '').toLowerCase();
        var rowSpending = row.getAttribute('data-spending') || '';

        var matchesCampus = !campusFilter || rowCampus.indexOf(campusFilter) !== -1;
        var matchesSpending = !spendingFilter || rowSpending === spendingFilter;

        if (matchesCampus && matchesSpending) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    var countBadge = document.getElementById('visibleStudentCount');
    if (countBadge) {
        countBadge.innerText = visibleCount;
    }
}
</script>
<script src="<?= BASE_URL ?>/assets/js/experience.js?v=5"></script>
</body>
</html>
