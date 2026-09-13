<?php
/**
 * AcademicHub Nepal - Student & User Login
 * File: login.php
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth_check.php';

$redirect_target = $_GET['redirect'] ?? '';
$error = '';

// If already logged in, redirect to appropriate portal
if (is_logged_in()) {
    if (is_admin()) {
        header('Location: ' . BASE_URL . '/admin/dashboard.php');
    } else {
        header('Location: ' . (!empty($redirect_target) ? urldecode($redirect_target) : BASE_URL . '/dashboard.php'));
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please provide both your registered email and password.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();

            // Auto-heal demo student account if needed
            if ((!$user || !password_verify($password, $user['password'])) && $email === 'student@demo.com' && $password === 'password123') {
                $hashed = password_hash('password123', PASSWORD_BCRYPT);
                $pdo->prepare("
                    INSERT INTO users (id, name, email, password, phone, campus, role)
                    VALUES (2, 'Aayush Sharma', 'student@demo.com', :pwd, '9841234567', 'Gyan Jyoti Campus', 'student')
                    ON DUPLICATE KEY UPDATE password = :pwd2
                ")->execute([':pwd' => $hashed, ':pwd2' => $hashed]);

                $stmt->execute([':email' => $email]);
                $user = $stmt->fetch();
            }

            // Auto-heal demo admin account if logging in from main login
            if ((!$user || !password_verify($password, $user['password'])) && $email === 'admin@academichub.com' && $password === 'admin123') {
                $hashed = password_hash('admin123', PASSWORD_BCRYPT);
                $pdo->prepare("
                    INSERT INTO users (id, name, email, password, phone, campus, role)
                    VALUES (1, 'Admin AcademicHub', 'admin@academichub.com', :pwd, '9800000001', 'HQ Administration', 'admin')
                    ON DUPLICATE KEY UPDATE password = :pwd2, role = 'admin'
                ")->execute([':pwd' => $hashed, ':pwd2' => $hashed]);

                $stmt->execute([':email' => $email]);
                $user = $stmt->fetch();
            }

            if ($user && (password_verify($password, $user['password']) || ($password === 'password123' && $email === 'student@demo.com') || ($password === 'admin123' && $email === 'admin@academichub.com'))) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_campus'] = $user['campus'] ?? 'General';

                set_flash_message('success', "Welcome back, {$user['name']}!");

                if ($user['role'] === 'admin') {
                    header('Location: ' . BASE_URL . '/admin/dashboard.php');
                } elseif (!empty($redirect_target)) {
                    header('Location: ' . urldecode($redirect_target));
                } else {
                    header('Location: ' . BASE_URL . '/dashboard.php');
                }
                exit;
            } else {
                $error = 'Invalid email address or password. Please try again.';
            }
        } catch (Exception $e) {
            $error = 'Authentication system error: ' . $e->getMessage();
        }
    }
}

$page_title = "Login | AcademicHub Nepal";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card border-0 shadow rounded-4 overflow-hidden">
                <div class="p-4 bg-primary text-white text-center" style="background: linear-gradient(135deg, #0f2942 0%, #1e4976 100%) !important;">
                    <div class="hub-icon mx-auto mb-2 text-info fs-3">
                        <i class="bi bi-mortarboard-fill"></i>
                    </div>
                    <h3 class="fw-bold mb-1 text-white">Student Login</h3>
                    <p class="small text-light opacity-75 mb-0">Access your purchased reference materials &amp; downloads</p>
                </div>

                <div class="card-body p-4 p-md-5 bg-white">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger py-2 px-3 small rounded-3 mb-4">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>

                    <!-- Demo 1-Click Quick Fill Helper -->
                    <div class="bg-light p-3 rounded-3 border mb-4 text-center">
                        <span class="small fw-bold text-muted d-block mb-2">⚡ Demo Quick Login:</span>
                        <div class="d-flex justify-content-center gap-2">
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="fillStudent()">
                                <i class="bi bi-person-fill"></i> Fill Student
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-dark" onclick="fillAdmin()">
                                <i class="bi bi-shield-lock-fill"></i> Fill Admin
                            </button>
                        </div>
                    </div>

                    <form action="" method="POST">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-muted">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                                <input type="email" name="email" id="loginEmail" class="form-control" placeholder="student@demo.com" required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label small fw-semibold text-muted mb-0">Password</label>
                                <a href="#" class="small text-muted" onclick="alert('Demo password for student@demo.com is: password123'); return false;">Forgot?</a>
                            </div>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-lock"></i></span>
                                <input type="password" name="password" id="loginPassword" class="form-control" placeholder="••••••••" required>
                            </div>
                        </div>

                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-hub-primary py-2 fw-semibold">
                                <i class="bi bi-box-arrow-in-right me-2"></i> Log In to AcademicHub
                            </button>
                        </div>

                        <div class="text-center small text-muted">
                            Don't have an account yet? 
                            <a href="<?= BASE_URL ?>/register.php" class="fw-bold text-primary">Register Here</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function fillStudent() {
    document.getElementById('loginEmail').value = 'student@demo.com';
    document.getElementById('loginPassword').value = 'password123';
}
function fillAdmin() {
    document.getElementById('loginEmail').value = 'admin@academichub.com';
    document.getElementById('loginPassword').value = 'admin123';
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
