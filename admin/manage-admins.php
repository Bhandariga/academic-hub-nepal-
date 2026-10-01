<?php
/**
 * AcademicHub Nepal - Super Admin Governance & Role Assignment Console
 * File: admin/manage-admins.php
 * Strictly restricted to Super Administrators (require_superadmin())
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth_check.php';

// Strict Super Admin Access Guard
require_superadmin();

$current_sa_id = $_SESSION['user_id'];
$available_permissions = [
    'manage_students_success' => 'Manage Successful Student Stories',
    'manage_colleges' => 'Manage College Profiles',
    'manage_gallery' => 'Manage Society Gallery',
    'manage_resources' => 'Manage Academic Resources (Upload, Edit, Delete)',
    'manage_orders'    => 'Manage Orders & Payment Verification',
    'manage_students'  => 'Manage Student Profiles & Directory',
    'manage_team'      => 'Manage Team Members & Faculty (Add, Edit, Photos, Delete)',
    'view_reports'     => 'Access Sales & Analytics Reports',
    'system_settings'  => 'Configure System & Operational Settings',
    'manage_admins'    => 'Super Admin Governance & Role Assignments'
];

$role_presets = [
    'Super Administrator' => ['all', 'manage_resources', 'manage_orders', 'manage_students', 'manage_team', 'view_reports', 'system_settings', 'manage_admins'],
    'General Administrator' => ['manage_resources', 'manage_orders', 'manage_students', 'view_reports'],
    'Content & Resource Manager' => ['manage_resources'],
    'Finance & Orders Admin' => ['manage_orders', 'view_reports'],
    'Student Support Specialist' => ['manage_students']
];

// Handle Actions (POST only with CSRF verification)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        set_flash_message('danger', 'Security validation expired. Action aborted.');
        header('Location: ' . BASE_URL . '/admin/manage-admins.php');
        exit;
    }

    $action = $_POST['action'] ?? '';

    // 1. Create New Administrator
    if ($action === 'create_admin') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $phone = trim($_POST['phone'] ?? '');
        $campus = trim($_POST['campus'] ?? 'Administration HQ');
        $role_type = $_POST['role_type'] ?? 'admin'; // 'admin' or 'superadmin'
        $admin_role = trim($_POST['admin_role'] ?? 'General Administrator');
        $selected_perms = is_array($_POST['permissions'] ?? null) ? array_values(array_intersect($_POST['permissions'], array_keys($available_permissions))) : [];

        if (empty($name) || empty($email) || empty($password)) {
            set_flash_message('danger', 'Please provide Administrator Name, Email ID, and Password.');
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            set_flash_message('danger', 'Please provide a valid email format.');
        } elseif (strlen($password) < 6) {
            set_flash_message('danger', 'Password must be at least 6 characters long.');
        } else {
            try {
                // Check if email already registered
                $chk = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
                $chk->execute([':email' => $email]);
                if ($chk->fetch()) {
                    set_flash_message('danger', "An account with email '{$email}' already exists.");
                } else {
                    $hashed = password_hash($password, PASSWORD_BCRYPT);
                    $permsJson = json_encode(array_values($selected_perms));

                    $ins = $pdo->prepare("
                        INSERT INTO users (name, email, password, phone, campus, role, admin_role, permissions, is_active, created_at)
                        VALUES (:name, :email, :pwd, :phone, :campus, :role, :admin_role, :perms, 1, NOW())
                    ");
                    $ins->execute([
                        ':name' => $name,
                        ':email' => $email,
                        ':pwd' => $hashed,
                        ':phone' => $phone,
                        ':campus' => $campus,
                        ':role' => in_array($role_type, ['admin', 'superadmin']) ? $role_type : 'admin',
                        ':admin_role' => $admin_role,
                        ':perms' => $permsJson
                    ]);

                    $new_id = $pdo->lastInsertId();
                    log_admin_activity($pdo, 'CREATE_ADMIN', "Created new admin {$email} with role {$admin_role}", 'user', $new_id);
                    set_flash_message('success', "New administrator account '{$email}' successfully created with role '{$admin_role}'.");
                }
            } catch (Exception $e) {
                set_flash_message('danger', 'Error creating administrator: ' . safe_error_message($e));
            }
        }
        header('Location: ' . BASE_URL . '/admin/manage-admins.php');
        exit;
    }

    // 2. Update Admin Role & Permissions
    if ($action === 'update_admin_role') {
        $target_id = (int)($_POST['target_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $role_type = $_POST['role_type'] ?? 'admin';
        $admin_role = trim($_POST['admin_role'] ?? 'General Administrator');
        $selected_perms = is_array($_POST['permissions'] ?? null) ? array_values(array_intersect($_POST['permissions'], array_keys($available_permissions))) : [];

        // Check target
        $st = $pdo->prepare("SELECT * FROM users WHERE id = :id AND role IN ('admin', 'superadmin') LIMIT 1");
        $st->execute([':id' => $target_id]);
        $target_admin = $st->fetch();

        if (!$target_admin) {
            set_flash_message('danger', 'Target administrator account not found.');
        } elseif ($target_admin['email'] === 'superadmin@gmail.com' && $role_type !== 'superadmin') {
            set_flash_message('danger', 'The primary Root Super Administrator cannot be demoted.');
        } else {
            try {
                $permsJson = json_encode(array_values($selected_perms));
                $upd = $pdo->prepare("
                    UPDATE users SET 
                        name = :name,
                        role = :role,
                        admin_role = :admin_role,
                        permissions = :perms
                    WHERE id = :id
                ");
                $upd->execute([
                    ':name' => !empty($name) ? $name : $target_admin['name'],
                    ':role' => in_array($role_type, ['admin', 'superadmin']) ? $role_type : 'admin',
                    ':admin_role' => $admin_role,
                    ':perms' => $permsJson,
                    ':id' => $target_id
                ]);

                log_admin_activity($pdo, 'UPDATE_ADMIN_ROLE', "Updated roles and permissions for {$target_admin['email']} to {$admin_role}", 'user', $target_id);
                set_flash_message('success', "Administrative role and permissions updated for {$target_admin['email']}.");
            } catch (Exception $e) {
                set_flash_message('danger', 'Update error: ' . safe_error_message($e));
            }
        }
        header('Location: ' . BASE_URL . '/admin/manage-admins.php');
        exit;
    }

    // 3. Toggle Active / Suspended Status
    if ($action === 'toggle_status') {
        $target_id = (int)($_POST['target_id'] ?? 0);
        $st = $pdo->prepare("SELECT * FROM users WHERE id = :id AND role IN ('admin', 'superadmin') LIMIT 1");
        $st->execute([':id' => $target_id]);
        $target_admin = $st->fetch();

        if (!$target_admin) {
            set_flash_message('danger', 'Administrator account not found.');
        } elseif ($target_admin['email'] === 'superadmin@gmail.com' || $target_id === $current_sa_id) {
            set_flash_message('danger', 'You cannot deactivate the primary Super Administrator or your active session.');
        } else {
            $new_status = ($target_admin['is_active'] == 1) ? 0 : 1;
            $pdo->prepare("UPDATE users SET is_active = :st WHERE id = :id")->execute([':st' => $new_status, ':id' => $target_id]);
            $status_word = $new_status ? 'activated' : 'suspended';
            log_admin_activity($pdo, 'TOGGLE_ADMIN_STATUS', "Admin {$target_admin['email']} {$status_word}", 'user', $target_id);
            set_flash_message('success', "Administrator account {$target_admin['email']} has been {$status_word}.");
        }
        header('Location: ' . BASE_URL . '/admin/manage-admins.php');
        exit;
    }

    // 4. Reset Admin Password
    if ($action === 'reset_password') {
        $target_id = (int)($_POST['target_id'] ?? 0);
        $new_pass = $_POST['new_password'] ?? '';

        if (strlen($new_pass) < 6) {
            set_flash_message('danger', 'New password must be at least 6 characters long.');
        } else {
            $st = $pdo->prepare("SELECT email FROM users WHERE id = :id AND role IN ('admin', 'superadmin') LIMIT 1");
            $st->execute([':id' => $target_id]);
            $target = $st->fetch();

            if ($target) {
                $hashed = password_hash($new_pass, PASSWORD_BCRYPT);
                $pdo->prepare("UPDATE users SET password = :p WHERE id = :id")->execute([':p' => $hashed, ':id' => $target_id]);
                log_admin_activity($pdo, 'RESET_ADMIN_PWD', "Reset password for {$target['email']}", 'user', $target_id);
                set_flash_message('success', "Password successfully reset for administrator '{$target['email']}'.");
            }
        }
        header('Location: ' . BASE_URL . '/admin/manage-admins.php');
        exit;
    }

    // 5. Delete Admin Account (Super Admin Only)
    if ($action === 'delete_admin') {
        $target_id = (int)($_POST['target_id'] ?? 0);
        $st = $pdo->prepare("SELECT * FROM users WHERE id = :id AND role IN ('admin', 'superadmin') LIMIT 1");
        $st->execute([':id' => $target_id]);
        $target_admin = $st->fetch();

        if (!$target_admin) {
            set_flash_message('danger', 'Administrator account not found.');
        } elseif ($target_admin['email'] === 'superadmin@gmail.com') {
            set_flash_message('danger', 'The primary Root Super Administrator cannot be deleted.');
        } elseif ($target_id === $current_sa_id) {
            set_flash_message('danger', 'You cannot delete your own active session account.');
        } else {
            try {
                $target_email = $target_admin['email'];
                $target_name = $target_admin['name'];
                
                // Delete the admin account
                $pdo->prepare("DELETE FROM users WHERE id = :id")->execute([':id' => $target_id]);
                
                // Log the deletion
                log_admin_activity($pdo, 'DELETE_ADMIN', "Deleted administrator account: {$target_email} ({$target_name})", 'user', $target_id);
                
                set_flash_message('success', "Administrator account '{$target_email}' has been permanently deleted.");
            } catch (Exception $e) {
                set_flash_message('danger', 'Error deleting administrator: ' . safe_error_message($e));
            }
        }
        header('Location: ' . BASE_URL . '/admin/manage-admins.php');
        exit;
    }
}

// Fetch all administrators
try {
    $stmt_admins = $pdo->query("
        SELECT * FROM users 
        WHERE role IN ('admin', 'superadmin')
        ORDER BY CASE WHEN role = 'superadmin' THEN 0 ELSE 1 END, id ASC
    ");
    $admins = $stmt_admins->fetchAll();

    // Fetch recent activity audit logs
    $stmt_logs = $pdo->query("
        SELECT l.*, u.name as admin_name, u.email as admin_email, u.role as admin_role
        FROM admin_activity_logs l
        LEFT JOIN users u ON l.admin_id = u.id
        ORDER BY l.id DESC LIMIT 20
    ");
    $activity_logs = $stmt_logs->fetchAll();
} catch (Exception $e) {
    die("Database query error: " . safe_error_message($e));
}

$total_sa = count(array_filter($admins, fn($a) => $a['role'] === 'superadmin'));
$total_staff_admins = count(array_filter($admins, fn($a) => $a['role'] === 'admin'));
$total_active = count(array_filter($admins, fn($a) => (int)($a['is_active'] ?? 1) === 1));
$total_suspended = count(array_filter($admins, fn($a) => (int)($a['is_active'] ?? 1) === 0));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Role Governance | AcademicHub Nepal</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <style>
        .super-badge-glow {
            background: linear-gradient(135deg, #d97706, #f59e0b);
            color: #fff;
            box-shadow: 0 4px 14px rgba(245, 158, 11, 0.4);
        }
        .perm-tag {
            font-size: 0.72rem;
            background: #f1f5f9;
            color: #334155;
            border: 1px solid #e2e8f0;
            padding: 2px 8px;
            border-radius: 6px;
            display: inline-block;
            margin: 2px;
        }
        .stat-badge-card {
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 16px 20px;
            background: #ffffff;
            transition: all 0.2s ease;
        }
        .stat-badge-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(15, 41, 66, 0.05);
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
        <?php $active_page = 'manage-admins'; require_once __DIR__ . '/sidebar.php'; ?>

        <!-- Main Executive Content Area -->
        <div class="col-md-9 col-lg-10 p-4 p-md-5">
            <?php display_flash_message(); ?>

            <!-- Page Header -->
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-3 border-bottom">
                <div>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-1 small text-muted">
                            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/dashboard.php">Dashboard</a></li>
                            <li class="breadcrumb-item active text-warning fw-semibold"><i class="bi bi-shield-shaded me-1"></i>Super Admin Governance</li>
                        </ol>
                    </nav>
                    <div class="d-flex align-items-center gap-3">
                        <h2 class="h3 fw-bold text-dark mb-0">Admin Role &amp; Access Control</h2>
                        <span class="badge super-badge-glow px-3 py-1 rounded-pill">
                            <i class="bi bi-shield-fill-check me-1"></i>ROOT AUTHORITY
                        </span>
                    </div>
                    <p class="text-muted small mb-0 mt-1">Assign, customize, and audit administrative roles, permissions, and status across AcademicHub.</p>
                </div>

                <div class="mt-3 mt-md-0">
                    <button type="button" class="btn btn-primary rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#createAdminModal">
                        <i class="bi bi-person-plus-fill me-2"></i>Create New Administrator
                    </button>
                </div>
            </div>

            <!-- KPI Metric Summary Row -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="stat-badge-card">
                        <div class="text-muted small fw-semibold">Super Admins</div>
                        <div class="h3 fw-bold text-warning mb-0 mt-1"><?= $total_sa ?></div>
                        <div class="small text-muted mt-1" style="font-size:0.75rem;">Full system sovereignty</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-badge-card">
                        <div class="text-muted small fw-semibold">Staff Admins</div>
                        <div class="h3 fw-bold text-primary mb-0 mt-1"><?= $total_staff_admins ?></div>
                        <div class="small text-muted mt-1" style="font-size:0.75rem;">Role-scoped operators</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-badge-card">
                        <div class="text-muted small fw-semibold">Active Accounts</div>
                        <div class="h3 fw-bold text-success mb-0 mt-1"><?= $total_active ?></div>
                        <div class="small text-muted mt-1" style="font-size:0.75rem;">Enabled login access</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-badge-card">
                        <div class="text-muted small fw-semibold">Suspended Accounts</div>
                        <div class="h3 fw-bold text-danger mb-0 mt-1"><?= $total_suspended ?></div>
                        <div class="small text-muted mt-1" style="font-size:0.75rem;">Blocked access</div>
                    </div>
                </div>
            </div>

            <!-- Tabs Navigation -->
            <ul class="nav nav-pills mb-4" id="governanceTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active rounded-pill px-4 fw-semibold" id="admins-tab" data-bs-toggle="pill" data-bs-target="#tab-admins" type="button" role="tab">
                        <i class="bi bi-people-fill me-2"></i>Administrators Directory (<?= count($admins) ?>)
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link rounded-pill px-4 fw-semibold" id="audit-tab" data-bs-toggle="pill" data-bs-target="#tab-audit" type="button" role="tab">
                        <i class="bi bi-journal-text me-2"></i>Activity Audit Trail
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="governanceTabsContent">
                <!-- TAB 1: Administrators Directory -->
                <div class="tab-pane fade show active" id="tab-admins" role="tabpanel">
                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light small">
                                    <tr>
                                        <th class="ps-4">Administrator</th>
                                        <th>Role Classification</th>
                                        <th>Granted Permissions</th>
                                        <th>Account Status</th>
                                        <th>Last Login</th>
                                        <th class="text-end pe-4">Governance Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($admins as $adm): 
                                        $is_root_sa = ($adm['email'] === 'superadmin@gmail.com');
                                        $perms_arr = json_decode($adm['permissions'] ?? '[]', true) ?: [];
                                        $has_all = in_array('all', $perms_arr) || $adm['role'] === 'superadmin';
                                    ?>
                                        <tr>
                                            <td class="ps-4">
                                                <div class="d-flex align-items-center gap-3">
                                                    <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold shadow-sm" style="width: 42px; height: 42px; background: <?= $adm['role'] === 'superadmin' ? 'linear-gradient(135deg, #d97706, #f59e0b)' : 'linear-gradient(135deg, #0284c7, #0f2942)' ?>;">
                                                        <?= strtoupper(substr($adm['name'], 0, 1)) ?>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold text-dark d-flex align-items-center gap-2">
                                                            <?= htmlspecialchars($adm['name']) ?>
                                                            <?php if ($is_root_sa): ?>
                                                                <span class="badge bg-warning bg-opacity-25 text-warning border border-warning border-opacity-50" style="font-size:0.65rem;">ROOT</span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="text-muted small"><?= htmlspecialchars($adm['email']) ?></div>
                                                        <div class="text-muted" style="font-size: 0.72rem;"><i class="bi bi-telephone me-1"></i><?= htmlspecialchars($adm['phone'] ?: 'No phone') ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if ($adm['role'] === 'superadmin'): ?>
                                                    <span class="badge super-badge-glow px-2 py-1 rounded-pill">
                                                        <i class="bi bi-shield-shaded me-1"></i><?= htmlspecialchars($adm['admin_role'] ?: 'Super Administrator') ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2 py-1 rounded-pill">
                                                        <i class="bi bi-person-badge-fill me-1"></i><?= htmlspecialchars($adm['admin_role'] ?: 'Staff Admin') ?>
                                                    </span>
                                                <?php endif; ?>
                                                <div class="text-muted mt-1" style="font-size:0.75rem;">Campus: <?= htmlspecialchars($adm['campus'] ?: 'HQ') ?></div>
                                            </td>
                                            <td style="max-width: 280px;">
                                                <?php if ($has_all): ?>
                                                    <span class="perm-tag text-warning border-warning bg-warning bg-opacity-10 fw-semibold">
                                                        <i class="bi bi-star-fill me-1"></i>All System Capabilities Granted
                                                    </span>
                                                <?php elseif (empty($perms_arr)): ?>
                                                    <span class="text-muted small">No permissions assigned</span>
                                                <?php else: ?>
                                                    <?php foreach ($perms_arr as $p): ?>
                                                        <span class="perm-tag">
                                                            <?= htmlspecialchars(str_replace('_', ' ', $p)) ?>
                                                        </span>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ((int)($adm['is_active'] ?? 1) === 1): ?>
                                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1 rounded-pill">
                                                        <i class="bi bi-check-circle-fill me-1"></i>Active
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2 py-1 rounded-pill">
                                                        <i class="bi bi-slash-circle-fill me-1"></i>Suspended
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="small text-dark fw-medium">
                                                    <?= !empty($adm['last_login']) ? date('M j, Y H:i', strtotime($adm['last_login'])) : '<span class="text-muted">Never</span>' ?>
                                                </div>
                                                <div class="text-muted" style="font-size:0.72rem;">Joined: <?= date('M j, Y', strtotime($adm['created_at'])) ?></div>
                                            </td>
                                            <td class="text-end pe-4">
                                                <div class="btn-group btn-group-sm">
                                                    <!-- Edit Role Trigger -->
                                                    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal<?= $adm['id'] ?>" title="Edit Role & Permissions">
                                                        <i class="bi bi-sliders"></i> Role
                                                    </button>

                                                    <!-- Reset Password Trigger -->
                                                    <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#resetModal<?= $adm['id'] ?>" title="Reset Password">
                                                        <i class="bi bi-key-fill"></i>
                                                    </button>

                                                    <!-- Status Toggle (Protected for Root) -->
                                                    <?php if (!$is_root_sa && $adm['id'] != $current_sa_id): ?>
                                                        <form method="POST" action="" class="d-inline" onsubmit="return confirm('Change active status for this administrator?');">
                                                            <?= csrf_field() ?>
                                                            <input type="hidden" name="action" value="toggle_status">
                                                            <input type="hidden" name="target_id" value="<?= $adm['id'] ?>">
                                                            <?php if ((int)($adm['is_active'] ?? 1) === 1): ?>
                                                                <button type="submit" class="btn btn-outline-danger" title="Suspend Admin Access">
                                                                    <i class="bi bi-lock-fill"></i>
                                                                </button>
                                                            <?php else: ?>
                                                                <button type="submit" class="btn btn-outline-success" title="Activate Admin Access">
                                                                    <i class="bi bi-unlock-fill"></i>
                                                                </button>
                                                            <?php endif; ?>
                                                        </form>
                                                    <?php endif; ?>

                                                    <!-- Delete Admin (Protected for Root and Self) -->
                                                    <?php if (!$is_root_sa && $adm['id'] != $current_sa_id): ?>
                                                        <form method="POST" action="" class="d-inline" onsubmit="return confirm('Are you sure you want to permanently remove this administrator? This action cannot be undone.');">
                                                            <?= csrf_field() ?>
                                                            <input type="hidden" name="action" value="delete_admin">
                                                            <input type="hidden" name="target_id" value="<?= $adm['id'] ?>">
                                                            <button type="submit" class="btn btn-danger" title="Delete Administrator">
                                                                <i class="bi bi-trash-fill"></i>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>

                                                <!-- MODAL: Edit Role & Permissions -->
                                                <div class="modal fade text-start" id="editModal<?= $adm['id'] ?>" tabindex="-1" aria-hidden="true">
                                                    <div class="modal-dialog modal-dialog-centered">
                                                        <div class="modal-content rounded-4 border-0 shadow">
                                                            <form method="POST" action="">
                                                                <?= csrf_field() ?>
                                                                <input type="hidden" name="action" value="update_admin_role">
                                                                <input type="hidden" name="target_id" value="<?= $adm['id'] ?>">

                                                                <div class="modal-header border-0 bg-light p-4">
                                                                    <div>
                                                                        <h5 class="modal-title fw-bold text-dark">Assign Role &amp; Permissions</h5>
                                                                        <div class="text-muted small"><?= htmlspecialchars($adm['email']) ?></div>
                                                                    </div>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>

                                                                <div class="modal-body p-4">
                                                                    <div class="mb-3">
                                                                        <label class="form-label small fw-bold text-muted">Administrator Display Name</label>
                                                                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($adm['name']) ?>" required>
                                                                    </div>

                                                                    <div class="mb-3">
                                                                        <label class="form-label small fw-bold text-muted">Account Authority Tier</label>
                                                                        <select name="role_type" class="form-select" <?= $is_root_sa ? 'disabled' : '' ?>>
                                                                            <option value="admin" <?= $adm['role'] === 'admin' ? 'selected' : '' ?>>Staff Administrator</option>
                                                                            <option value="superadmin" <?= $adm['role'] === 'superadmin' ? 'selected' : '' ?>>Super Administrator (Full Sovereignty)</option>
                                                                        </select>
                                                                        <?php if ($is_root_sa): ?>
                                                                            <input type="hidden" name="role_type" value="superadmin">
                                                                            <div class="form-text text-warning small"><i class="bi bi-info-circle me-1"></i>Root Super Administrator tier is permanently locked.</div>
                                                                        <?php endif; ?>
                                                                    </div>

                                                                    <div class="mb-3">
                                                                        <label class="form-label small fw-bold text-muted">Administrative Title / Role</label>
                                                                        <input type="text" name="admin_role" class="form-control" list="rolePresetList<?= $adm['id'] ?>" value="<?= htmlspecialchars($adm['admin_role'] ?: 'General Administrator') ?>" required>
                                                                        <datalist id="rolePresetList<?= $adm['id'] ?>">
                                                                            <?php foreach (array_keys($role_presets) as $presetName): ?>
                                                                                <option value="<?= htmlspecialchars($presetName) ?>"></option>
                                                                            <?php endforeach; ?>
                                                                        </datalist>
                                                                    </div>

                                                                    <div class="mb-2">
                                                                        <label class="form-label small fw-bold text-muted d-block mb-2">Granular Module Permissions</label>
                                                                        <?php foreach ($available_permissions as $permKey => $permLabel): 
                                                                            $checked = $has_all || in_array($permKey, $perms_arr);
                                                                        ?>
                                                                            <div class="form-check mb-2">
                                                                                <input class="form-check-input" type="checkbox" name="permissions[]" value="<?= $permKey ?>" id="p_<?= $adm['id'] ?>_<?= $permKey ?>" <?= $checked ? 'checked' : '' ?>>
                                                                                <label class="form-check-label small" for="p_<?= $adm['id'] ?>_<?= $permKey ?>">
                                                                                    <?= htmlspecialchars($permLabel) ?>
                                                                                </label>
                                                                            </div>
                                                                        <?php endforeach; ?>
                                                                    </div>
                                                                </div>

                                                                <div class="modal-footer border-0 bg-light p-3">
                                                                    <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                                                                    <button type="submit" class="btn btn-primary btn-sm rounded-pill px-4">
                                                                        <i class="bi bi-check2-circle me-1"></i>Save Role Assignments
                                                                    </button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- MODAL: Reset Password -->
                                                <div class="modal fade text-start" id="resetModal<?= $adm['id'] ?>" tabindex="-1" aria-hidden="true">
                                                    <div class="modal-dialog modal-dialog-centered modal-sm">
                                                        <div class="modal-content rounded-4 border-0 shadow">
                                                            <form method="POST" action="">
                                                                <?= csrf_field() ?>
                                                                <input type="hidden" name="action" value="reset_password">
                                                                <input type="hidden" name="target_id" value="<?= $adm['id'] ?>">

                                                                <div class="modal-header border-0 bg-light p-3">
                                                                    <h6 class="modal-title fw-bold text-dark"><i class="bi bi-key-fill text-warning me-2"></i>Reset Password</h6>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>

                                                                <div class="modal-body p-4">
                                                                    <p class="small text-muted mb-3">Set a new secure password for <strong><?= htmlspecialchars($adm['email']) ?></strong>.</p>
                                                                    <div class="mb-3">
                                                                        <label class="form-label small fw-bold text-muted">New Password</label>
                                                                        <input type="password" name="new_password" class="form-control form-control-sm" placeholder="At least 6 characters" minlength="6" required>
                                                                    </div>
                                                                </div>

                                                                <div class="modal-footer border-0 bg-light p-3">
                                                                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                                                                    <button type="submit" class="btn btn-warning btn-sm fw-bold">Update Password</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- TAB 2: Activity Audit Trail -->
                <div class="tab-pane fade" id="tab-audit" role="tabpanel">
                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
                        <div class="p-4 border-bottom bg-light d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-shield-check text-primary me-2"></i>Administrator Operations Audit Log</h6>
                                <p class="small text-muted mb-0">Immutable tracking of critical administrative actions, role updates, and system events.</p>
                            </div>
                            <span class="badge bg-white border text-muted px-3 py-2 rounded-pill shadow-sm">
                                Total Logged: <?= count($activity_logs) ?> Operations
                            </span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light small">
                                    <tr>
                                        <th class="ps-4">Timestamp</th>
                                        <th>Operating Administrator</th>
                                        <th>Action Identifier</th>
                                        <th>Operation Details</th>
                                        <th class="pe-4 text-end">IP Address</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($activity_logs)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-5 text-muted">No administrative operations recorded yet.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($activity_logs as $log): ?>
                                            <tr>
                                                <td class="ps-4 small text-muted">
                                                    <?= date('M j, Y H:i:s', strtotime($log['created_at'])) ?>
                                                </td>
                                                <td>
                                                    <div class="fw-semibold text-dark small"><?= htmlspecialchars($log['admin_name'] ?: 'System / Root') ?></div>
                                                    <div class="text-muted" style="font-size:0.72rem;"><?= htmlspecialchars($log['admin_email'] ?: 'HQ') ?></div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border px-2 py-1">
                                                        <?= htmlspecialchars($log['action']) ?>
                                                    </span>
                                                </td>
                                                <td class="small text-dark">
                                                    <?= htmlspecialchars($log['details']) ?>
                                                </td>
                                                <td class="pe-4 text-end small text-muted font-monospace">
                                                    <?= htmlspecialchars($log['ip_address'] ?: '127.0.0.1') ?>
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

<!-- MODAL: Create New Administrator -->
<div class="modal fade" id="createAdminModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
            <form method="POST" action="">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="create_admin">

                <div class="modal-header border-0 bg-light p-4">
                    <div>
                        <h5 class="modal-title fw-bold text-dark"><i class="bi bi-person-plus-fill text-primary me-2"></i>Create New Administrator</h5>
                        <p class="text-muted small mb-0">Configure credentials, assign operational role, and declare capability permissions.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Full Name</label>
                            <input type="text" name="name" class="form-control" placeholder="e.g. Ramesh Karki" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Administrator Email ID</label>
                            <input type="email" name="email" class="form-control" placeholder="staff@academichub.com" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Initial Password</label>
                            <input type="password" name="password" class="form-control" placeholder="Minimum 6 characters" minlength="6" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Phone Number</label>
                            <input type="text" name="phone" class="form-control" placeholder="98XXXXXXXX">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Campus / Branch Department</label>
                            <input type="text" name="campus" class="form-control" value="Tribhuvan University HQ">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Account Authority Tier</label>
                            <select name="role_type" class="form-select" id="newAdminTier" onchange="onTierChange(this.value)">
                                <option value="admin" selected>Staff Administrator</option>
                                <option value="superadmin">Super Administrator</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Role Preset / Title</label>
                        <select class="form-select mb-2" onchange="applyRolePreset(this.value)">
                            <option value="">-- Choose a standard role preset or customize below --</option>
                            <?php foreach ($role_presets as $presetTitle => $presetPerms): ?>
                                <option value="<?= htmlspecialchars($presetTitle) ?>"><?= htmlspecialchars($presetTitle) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="text" name="admin_role" id="newAdminRoleInput" class="form-control" placeholder="e.g. Content & Resource Manager" value="General Administrator" required>
                    </div>

                    <div>
                        <label class="form-label small fw-bold text-muted d-block mb-2">Module Permissions</label>
                        <div class="row g-2">
                            <?php foreach ($available_permissions as $permKey => $permLabel): ?>
                                <div class="col-md-6">
                                    <div class="form-check p-2 border rounded-3 bg-light">
                                        <input class="form-check-input new-perm-check" type="checkbox" name="permissions[]" value="<?= $permKey ?>" id="new_<?= $permKey ?>" checked>
                                        <label class="form-check-label small fw-semibold text-dark" for="new_<?= $permKey ?>">
                                            <?= htmlspecialchars($permLabel) ?>
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 bg-light p-3">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-5 shadow-sm">
                        <i class="bi bi-shield-check me-2"></i>Provision Administrator
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
const rolePresets = <?= json_encode($role_presets) ?>;

function applyRolePreset(presetName) {
    if (!presetName || !rolePresets[presetName]) return;
    document.getElementById('newAdminRoleInput').value = presetName;
    const targetPerms = rolePresets[presetName];
    const isSuper = (presetName === 'Super Administrator');

    if (isSuper) {
        document.getElementById('newAdminTier').value = 'superadmin';
    } else {
        document.getElementById('newAdminTier').value = 'admin';
    }

    document.querySelectorAll('.new-perm-check').forEach(chk => {
        if (targetPerms.includes('all') || targetPerms.includes(chk.value)) {
            chk.checked = true;
        } else {
            chk.checked = false;
        }
    });
}

function onTierChange(val) {
    if (val === 'superadmin') {
        document.getElementById('newAdminRoleInput').value = 'Super Administrator';
        document.querySelectorAll('.new-perm-check').forEach(chk => chk.checked = true);
    }
}
</script>
<script src="<?= BASE_URL ?>/assets/js/experience.js?v=5"></script>
</body>
</html>
