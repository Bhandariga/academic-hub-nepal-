<?php
/**
 * AcademicHub Nepal - Unified Authentication Portal (Login & Registration)
 * File: login.php
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth_check.php';

$redirect_target = safe_redirect_target($_GET['redirect'] ?? '');

$login_error = '';

// If already logged in, redirect to appropriate portal
if (is_logged_in()) {
    if (is_admin()) {
        header('Location: ' . BASE_URL . '/admin/dashboard.php');
    } else {
        header('Location: ' . (!empty($redirect_target) ? $redirect_target : BASE_URL . '/dashboard.php'));
    }
    exit;
}

// Brute-force Login Rate Limiting
$max_attempts = 5;
$lockout_seconds = 300; // 5 minutes lockout

if (isset($_SESSION['login_lockout']) && time() < $_SESSION['login_lockout']) {
    $remaining = ceil(($_SESSION['login_lockout'] - time()) / 60);
    $login_error = "Too many failed login attempts. Account temporarily locked. Please wait {$remaining} minute(s) before trying again.";
} elseif (isset($_SESSION['login_lockout']) && time() >= $_SESSION['login_lockout']) {
    unset($_SESSION['login_lockout'], $_SESSION['login_attempts']);
}

// Process POST requests (Login only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($login_error)) {
    if (!verify_csrf_token()) {
        $login_error = 'Security session validation expired. Please try submitting again.';
    } else {
        $email = trim(auth_text('email', 150));
        $password = auth_text('password', 1024);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
            $login_error = 'Please provide both your registered email and password.';
        } else {
            try {
                $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
                $stmt->execute([':email' => $email]);
                $user = $stmt->fetch();

                if (!$user) password_verify($password, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.');
                if ($user) {
                    if (isset($user['is_active']) && (int)$user['is_active'] === 0) {
                        $login_error = 'Invalid email address or password.';
                    } elseif (password_verify($password, $user['password'])) {
                        // Prevent session fixation
                        session_regenerate_id(true);
                        auth_login_succeeded($user);

                        // Reset failed attempts upon successful authentication
                        unset($_SESSION['login_attempts'], $_SESSION['login_lockout']);

                        // Set session variables
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_name'] = $user['name'];
                        $_SESSION['user_email'] = $user['email'];
                        $_SESSION['user_role'] = $user['role'];
                        $_SESSION['user_admin_role'] = $user['admin_role'] ?? ($user['role'] === 'superadmin' ? 'Super Administrator' : ($user['role'] === 'admin' ? 'Administrator' : null));
                        $_SESSION['user_permissions'] = $user['permissions'] ?? '[]';
                        $_SESSION['user_campus'] = $user['campus'] ?? 'General';
                        $_SESSION['user_avatar'] = $user['profile_image'] ?? null;
                        $_SESSION['last_activity'] = time();
                        if (!empty($_POST['remember'])) remember_issue($pdo, $user);

                        try {
                            $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = :id")->execute([':id' => $user['id']]);
                        } catch (Exception $e) {}

                        set_flash_message('success', "Welcome back, {$user['name']}!");

                        if (in_array($user['role'], ['admin', 'superadmin'], true)) {
                            header('Location: ' . BASE_URL . '/admin/dashboard.php');
                        } elseif (!empty($redirect_target)) {
                            header('Location: ' . $redirect_target);
                        } else {
                            header('Location: ' . BASE_URL . '/dashboard.php');
                        }
                        exit;
                    } else {
                        // Increment failed attempts
                        $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
                        if ($_SESSION['login_attempts'] >= $max_attempts) {
                            $_SESSION['login_lockout'] = time() + $lockout_seconds;
                            $login_error = 'Too many failed login attempts. Account temporarily locked for 5 minutes.';
                        } else {
                            $remainingAttempts = $max_attempts - $_SESSION['login_attempts'];
                            $login_error = "Invalid email address or password. ({$remainingAttempts} attempt(s) remaining)";
                        }
                    }
                } else {
                    $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
                    if ($_SESSION['login_attempts'] >= $max_attempts) {
                        $_SESSION['login_lockout'] = time() + $lockout_seconds;
                        $login_error = 'Too many failed login attempts. Account temporarily locked for 5 minutes.';
                    } else {
                        $login_error = 'Invalid email address or password.';
                    }
                }
            } catch (Exception $e) {
                error_log("Authentication Error: " . $e->getMessage());
                $login_error = 'Authentication service temporarily busy. Please try again shortly.';
            }
        }
    }
}

$page_title = 'Student & Admin Login | AcademicHub Nepal';
require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-split-wrapper py-4 py-md-5" style="background-color: #F8FAFC; min-height: calc(100vh - 160px);">
    <div class="container py-2">
        <div class="auth-shell row g-0 rounded-4 overflow-hidden shadow-lg border" style="background: #ffffff;">
            
            <!-- Left Pane: Academic Trust & Campus Branding (Desktop / Tablet) -->
            <div class="auth-story col-lg-5 d-none d-lg-flex flex-column justify-content-between p-5 text-white position-relative overflow-hidden" style="background: linear-gradient(145deg, #0F172A 0%, #1E293B 60%, #2563EB 100%);">
                <div class="position-absolute w-100 h-100 top-0 start-0 opacity-10" style="background-image: radial-gradient(#38bdf8 1px, transparent 1px); background-size: 24px 24px; pointer-events: none;"></div>
                
                <!-- Top Brand Identity -->
                <div class="position-relative">
                    <div class="d-inline-flex align-items-center gap-2 mb-4">
                        <div class="hub-icon rounded-3 p-2 text-white shadow-sm d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; background: linear-gradient(135deg, #2563EB, #38BDF8);">
                            <i class="bi bi-mortarboard-fill fs-4"></i>
                        </div>
                        <span class="fs-4 fw-bold text-white" style="font-family: 'Outfit', sans-serif; letter-spacing: -0.02em;">AcademicHub Nepal</span>
                    </div>

                    <span class="badge bg-warning text-dark px-3 py-1 rounded-pill fw-bold mb-3 shadow-xs" style="font-size: 0.72rem; letter-spacing: 0.05em;">
                        <i class="bi bi-patch-check-fill me-1"></i> TRIBHUVAN UNIVERSITY PORTAL
                    </span>

                    <h2 class="fw-bold text-white mb-3" style="font-family: 'Outfit', sans-serif; line-height: 1.25;">
                        Empowering Nepal's Academic Excellence
                    </h2>
                    <p class="text-light opacity-75 small mb-4" style="line-height: 1.6; font-size: 0.9rem;">
                        Instant verified thesis models, research questionnaires, and APA 7th edition formatting built specifically for BBS, BBA, Ma In Societies, MBS, and MBA students.
                    </p>

                    <!-- Feature Trust List -->
                    <div class="d-flex flex-column gap-3 mb-4">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-circle bg-white bg-opacity-10 d-flex align-items-center justify-content-center text-warning" style="width: 34px; height: 34px; flex-shrink: 0;">
                                <i class="bi bi-lightning-charge-fill"></i>
                            </div>
                            <div class="small">
                                <strong class="text-white d-block">Instant eSewa Access</strong>
                                <span class="text-white-50" style="font-size: 0.78rem;">Automatic unlock and download after ePay verification</span>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-circle bg-white bg-opacity-10 d-flex align-items-center justify-content-center text-info" style="width: 34px; height: 34px; flex-shrink: 0;">
                                <i class="bi bi-journal-check"></i>
                            </div>
                            <div class="small">
                                <strong class="text-white d-block">Campus-Standard Blueprints</strong>
                                <span class="text-white-50" style="font-size: 0.78rem;">Structured chapters, TOC, SPSS models, and viva defense guides</span>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-circle bg-white bg-opacity-10 d-flex align-items-center justify-content-center text-success" style="width: 34px; height: 34px; flex-shrink: 0;">
                                <i class="bi bi-shield-check"></i>
                            </div>
                            <div class="small">
                                <strong class="text-white d-block">100% Academic Integrity</strong>
                                <span class="text-white-50" style="font-size: 0.78rem;">Faculty-reviewed reference drafts with verified originality</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bottom Testimonial & Campus Badges -->
                <div class="position-relative pt-4 border-top border-white border-opacity-10">
                    <div class="p-3 rounded-3 bg-white bg-opacity-10 backdrop-blur mb-3">
                        <div class="d-flex text-warning mb-1" style="font-size: 0.75rem;">
                            <i class="bi bi-star-fill me-1"></i><i class="bi bi-star-fill me-1"></i><i class="bi bi-star-fill me-1"></i><i class="bi bi-star-fill me-1"></i><i class="bi bi-star-fill"></i>
                        </div>
                        <p class="small text-white-90 fst-italic mb-2" style="font-size: 0.8rem; line-height: 1.45;">
                            "Saved weeks of formatting confusion for my BBS 4th Year financial performance report. The eSewa integration made download immediate!"
                        </p>
                        <div class="small text-white-50" style="font-size: 0.72rem;">
                            &mdash; Verified Student &bull; Tribhuvan University Affiliated
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-1 flex-wrap">
                        <span class="badge bg-white bg-opacity-10 text-white-50 rounded-pill px-2 py-1" style="font-size: 0.65rem;">Gyan Jyoti Campus</span>
                        <span class="badge bg-white bg-opacity-10 text-white-50 rounded-pill px-2 py-1" style="font-size: 0.65rem;">Babai Campus</span>
                        <span class="badge bg-white bg-opacity-10 text-white-50 rounded-pill px-2 py-1" style="font-size: 0.65rem;">Gorkha Campus</span>
                        <span class="badge bg-white bg-opacity-10 text-white-50 rounded-pill px-2 py-1" style="font-size: 0.65rem;">Mark International</span>
                        <span class="badge bg-white bg-opacity-10 text-white-50 rounded-pill px-2 py-1" style="font-size: 0.65rem;">Mahendra Multiple</span>
                    </div>
                </div>

            </div>

            <!-- Right Pane: Auth Forms -->
            <div class="auth-form-side col-lg-7 p-4 p-md-5 d-flex flex-column justify-content-center bg-white">

                <!-- Login Form -->
                <div class="mb-3 text-center text-md-start">
                    <h3 class="fw-bold text-dark mb-1" style="font-family: 'Outfit', sans-serif;">Welcome Back</h3>
                    <p class="text-muted small">Access your academic orders, unlocked materials, and support tickets.</p>
                </div>

                <?php if (!empty($login_error)): ?>
                    <div class="alert alert-danger py-3 px-3 rounded-3 mb-4 d-flex align-items-center justify-content-between border-0 shadow-sm" style="background-color: #FEF2F2; border-left: 4px solid #DC2626 !important;">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-exclamation-triangle-fill text-danger fs-5 me-2 flex-shrink-0"></i>
                            <div class="small text-danger fw-semibold"><?= htmlspecialchars($login_error) ?></div>
                        </div>
                        <?php if (strpos($login_error, 'Account not found') !== false): ?>
                            <a href="<?= BASE_URL ?>/register.php" class="btn btn-primary btn-sm rounded-pill px-3 ms-2">
                                <i class="bi bi-person-plus me-1"></i> Create Account
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <form action="<?= BASE_URL ?>/login.php<?= !empty($redirect_target) ? '?redirect=' . urlencode($redirect_target) : '' ?>" method="POST">
                    <?= csrf_field() ?>
                    <?php if ($isHttps && security_storage_ready($pdo)): ?><label class="form-check mb-3"><input class="form-check-input" type="checkbox" name="remember" value="1"> <span class="form-check-label">Remember me on this device for 30 days (students)</span></label><?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border"><i class="bi bi-envelope text-muted"></i></span>
                            <input type="email" name="email" id="loginEmail" class="form-control" placeholder="student@demo.com" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label small fw-semibold text-dark mb-0">Password</label>
                            <a class="small text-primary text-decoration-none fw-semibold" href="<?= BASE_URL ?>/account-recovery.php">Forgot Password?</a>
                        </div>
                        <div class="input-group">
                            <span class="input-group-text bg-light border"><i class="bi bi-lock text-muted"></i></span>
                            <input autocomplete="current-password" type="password" name="password" id="loginPassword" class="form-control" placeholder="••••••••" required>
                            <button type="button" class="btn btn-outline-secondary password-toggle-btn border" onclick="togglePasswordVisibility('loginPassword', this)" title="Show / Hide Password" aria-label="Toggle password visibility">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="d-grid mb-3">
                        <button type="submit" class="btn btn-hub-primary py-3 fw-bold shadow-sm" style="font-size: 0.95rem;">
                            <i class="bi bi-box-arrow-in-right me-2"></i> Log In to AcademicHub
                        </button>
                    </div>

                    <div class="text-center small text-muted pt-3 border-top">
                        Don't have an account?
                        <a href="<?= BASE_URL ?>/register.php" class="fw-bold text-primary text-decoration-none ms-1">Create Account</a>
                    </div>
                </form>

            </div>

        </div>
    </div>
</div>

<script>
function togglePasswordVisibility(inputId, button) {
    var input = document.getElementById(inputId);
    var icon = button.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
