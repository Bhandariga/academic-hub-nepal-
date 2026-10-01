<?php
/**
 * AcademicHub Nepal - Executive Backoffice & Super Admin Login
 * File: admin/admin-login.php
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth_check.php';

// If already admin or superadmin, redirect to admin dashboard
if (is_admin()) {
    header('Location: ' . BASE_URL . '/admin/dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        $error = 'Security session validation expired. Please submit the form again.';
    } else {
        $email = trim(auth_text('email', 150));
        $password = auth_text('password', 1024);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
            $error = 'Please enter both administrator ID (email) and password.';
        } else {
            try {
                // Query for either admin or superadmin
                $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email AND role IN ('admin', 'superadmin') LIMIT 1");
                $stmt->execute([':email' => $email]);
                $admin = $stmt->fetch();

                if (!$admin) password_verify($password, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.');
                if ($admin) {
                    // Check if account is suspended
                    if (isset($admin['is_active']) && (int)$admin['is_active'] === 0) {
                        $error = 'Invalid email address or password.';
                    } elseif (password_verify($password, $admin['password'])) {
                        // Prevent session fixation
                        session_regenerate_id(true);
                        auth_login_succeeded($admin);

                        $_SESSION['user_id'] = $admin['id'];
                        $_SESSION['user_name'] = $admin['name'];
                        $_SESSION['user_email'] = $admin['email'];
                        $_SESSION['user_role'] = $admin['role']; // 'admin' or 'superadmin'
                        $_SESSION['user_admin_role'] = $admin['admin_role'] ?? ($admin['role'] === 'superadmin' ? 'Super Administrator' : 'Administrator');
                        $_SESSION['user_permissions'] = $admin['permissions'] ?? '[]';
                        $_SESSION['user_campus'] = $admin['campus'] ?? 'Administration';

                        // Update last login
                        try {
                            $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = :id")->execute([':id' => $admin['id']]);
                            log_admin_activity($pdo, 'ADMIN_LOGIN', 'Logged into administration console as ' . $admin['role']);
                        } catch (Exception $e) {}

                        $welcomeRole = ($admin['role'] === 'superadmin') ? 'Super Administrator' : 'Administrator';
                        set_flash_message('success', "Authenticated successfully as {$welcomeRole}. Welcome back, {$admin['name']}!");
                        header('Location: ' . BASE_URL . '/admin/dashboard.php');
                        exit;
                    } else {
                        $error = 'Invalid administrative credentials. Access denied.';
                    }
                } else {
                    $error = 'Invalid email address or password.';
                }
            } catch (Exception $e) {
                error_log($e->getMessage());
                $error = 'Authentication is temporarily unavailable.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Executive &amp; Super Admin Console | AcademicHub Nepal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --bg-dark: #07111e;
            --card-dark: #0f1f33;
            --primary-accent: #0284c7;
            --gold-accent: #f59e0b;
        }
        body {
            background: radial-gradient(circle at top right, #132a47 0%, #07111e 100%);
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #f1f5f9;
            padding: 24px 12px;
        }
        .admin-box {
            width: 100%;
            max-width: 480px;
        }
        .card-admin {
            background: rgba(15, 31, 51, 0.85);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(255, 255, 255, 0.05);
            overflow: hidden;
        }
        .admin-header {
            padding: 36px 32px 24px;
            text-align: center;
            position: relative;
        }
        .admin-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 10%;
            width: 80%;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(56, 189, 248, 0.3), transparent);
        }
        .hub-logo-badge {
            width: 60px;
            height: 60px;
            margin: 0 auto 16px;
            border-radius: 18px;
            background: linear-gradient(135deg, #0284c7, #0f2942);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: #38bdf8;
            box-shadow: 0 10px 25px -5px rgba(2, 132, 199, 0.4);
            border: 1px solid rgba(56, 189, 248, 0.3);
        }
        .form-control {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: #ffffff;
            border-radius: 12px;
            padding: 12px 16px;
            font-size: 0.95rem;
            transition: all 0.2s ease;
        }
        .form-control:focus {
            background: rgba(255, 255, 255, 0.08);
            border-color: #38bdf8;
            color: #ffffff;
            box-shadow: 0 0 0 4px rgba(56, 189, 248, 0.15);
        }
        .form-control::placeholder {
            color: #64748b;
        }
        .input-group-text {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: #94a3b8;
            border-radius: 12px;
        }
        .btn-admin-submit {
            background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
            border: none;
            color: #ffffff;
            font-weight: 700;
            padding: 13px;
            border-radius: 12px;
            transition: all 0.25s ease;
            box-shadow: 0 8px 20px -4px rgba(2, 132, 199, 0.4);
        }
        .btn-admin-submit:hover {
            background: linear-gradient(135deg, #0369a1 0%, #0284c7 100%);
            color: #ffffff;
            transform: translateY(-2px);
            box-shadow: 0 12px 24px -4px rgba(2, 132, 199, 0.5);
        }
        .quick-role-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.07);
            border-radius: 14px;
            padding: 12px 16px;
            margin-bottom: 20px;
        }
    </style>
<link href="<?= BASE_URL ?>/assets/css/design-system.css?v=3" rel="stylesheet">
<link href="<?= BASE_URL ?>/assets/css/experience.css?v=4" rel="stylesheet">
<link href="<?= BASE_URL ?>/assets/css/studio.css?v=1" rel="stylesheet">
<link href="<?= BASE_URL ?>/assets/css/platform.css?v=2" rel="stylesheet">
<script src="<?= BASE_URL ?>/assets/js/theme.js?v=1"></script>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"><link href="<?= BASE_URL ?>/assets/css/admin-enterprise.css?v=1" rel="stylesheet"><script src="<?= BASE_URL ?>/assets/js/admin-ui.js?v=1" defer></script>
</head>
<body>
    <div class="container admin-box">
        <div class="card card-admin">
            <div class="admin-header">
                <div class="hub-logo-badge">
                    <i class="bi bi-shield-lock-fill"></i>
                </div>
                <h3 class="fw-bold mb-1 text-white">AcademicHub Nepal</h3>
                <div class="d-flex justify-content-center align-items-center gap-2 mt-2">
                    <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 px-2 py-1 rounded-pill" style="font-size:0.75rem;">
                        <i class="bi bi-shield-check me-1"></i>SUPER ADMIN &amp; EXECUTIVE PORTAL
                    </span>
                </div>
            </div>

            <div class="card-body p-4 p-md-5 pt-4">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger border-0 py-2 px-3 small rounded-3 mb-4 d-flex align-items-center gap-2" style="background: rgba(239, 68, 68, 0.15); color: #fca5a5;">
                        <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                        <div><?= htmlspecialchars($error) ?></div>
                    </div>
                <?php endif; ?>

                <form action="" method="POST">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-white-50">Administrator Email ID</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope-at"></i></span>
                            <input type="email" name="email" id="adminEmail" class="form-control" placeholder="superadmin@gmail.com" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label small fw-semibold text-white-50 mb-0">Password</label>
                            <a href="javascript:void(0)" onclick="togglePasswordVisibility()" class="text-white-50 text-decoration-none small" style="font-size:0.75rem;">
                                <span id="toggleText"><i class="bi bi-eye"></i> Show</span>
                            </a>
                        </div>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-key-fill"></i></span>
                            <input type="password" name="password" id="adminPass" class="form-control" placeholder="••••••••" required>
                        </div>
                    </div>

                    <div class="d-grid mb-3">
                        <button type="submit" class="btn btn-admin-submit">
                            <i class="bi bi-box-arrow-in-right me-2"></i> Authenticate &amp; Launch Console
                        </button>
                    </div>

                    <div class="text-center pt-2">
                        <a href="<?= BASE_URL ?>/index.php" class="small text-white-50 text-decoration-none hover-white">
                            <i class="bi bi-arrow-left me-1"></i> Return to AcademicHub Nepal
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    function fillCreds(email, pass) {
        document.getElementById('adminEmail').value = email;
        document.getElementById('adminPass').value = pass;
    }

    function togglePasswordVisibility() {
        const pass = document.getElementById('adminPass');
        const text = document.getElementById('toggleText');
        if (pass.type === 'password') {
            pass.type = 'text';
            text.innerHTML = '<i class="bi bi-eye-slash"></i> Hide';
        } else {
            pass.type = 'password';
            text.innerHTML = '<i class="bi bi-eye"></i> Show';
        }
    }
    </script>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
<script src="<?= BASE_URL ?>/assets/js/experience.js?v=5"></script>
</body>
</html>

