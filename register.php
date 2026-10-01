<?php
/**
 * AcademicHub Nepal - Student Registration Page
 * File: register.php
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth_check.php';

$register_error = '';
$register_old = [
    'name' => '',
    'email' => '',
    'phone' => '',
    'campus' => 'Other Campuses'
];

// If already logged in, redirect to appropriate portal
if (is_logged_in()) {
    if (is_admin()) {
        header('Location: ' . BASE_URL . '/admin/dashboard.php');
    } else {
        header('Location: ' . BASE_URL . '/dashboard.php');
    }
    exit;
}

// Process POST requests (Registration)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        $register_error = 'Security session validation expired. Please try submitting again.';
    } else {
        $name = trim(auth_text('name'));
        $email = trim(auth_text('email'));
        $password = auth_text('password');
        $confirm_password = auth_text('confirm_password');
        $phone = trim(auth_text('phone'));
        $campus = trim(auth_text('campus', 100) ?: 'Other Campuses');
        $agree = isset($_POST['agree']);

        $register_old = [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'campus' => $campus
        ];

        if (empty($name) || empty($email) || empty($password)) {
            $register_error = 'Please fill in all mandatory fields (Name, Email, and Password).';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $register_error = 'Please provide a valid email address.';
        } elseif (strlen($password) < 12 || strlen($password) > 72) {
            $register_error = 'Use a password between 12 and 72 bytes long.';
        } elseif ($password !== $confirm_password) {
            $register_error = 'Password and confirmation password do not match.';
        } elseif (!$agree) {
            $register_error = 'You must agree to the academic integrity terms.';
        } else {
            try {
                // Check if email is already registered
                $check_stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
                $check_stmt->execute([':email' => $email]);
                if ($check_stmt->fetch()) {
                    $register_error = 'An account with this email address already exists. Please log in instead.';
                } else {
                    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                    $insert_stmt = $pdo->prepare("
                        INSERT INTO users (name, email, password, phone, campus, role, is_active, created_at)
                        VALUES (:name, :email, :password, :phone, :campus, 'student', 1, NOW())
                    ");
                    $insert_stmt->execute([
                        ':name' => $name,
                        ':email' => $email,
                        ':password' => $hashed_password,
                        ':phone' => $phone,
                        ':campus' => $campus
                    ]);

                    $createdId = (int)$pdo->lastInsertId();
                    if (account_mail_ready($pdo)) {
                        try { account_send_link($pdo, ['id'=>$createdId, 'email'=>$email, 'password'=>$hashed_password], 'verify'); }
                        catch (Throwable $e) { security_audit('VERIFICATION_MAIL_FAILED'); }
                    }
                    set_flash_message('success', "Account created successfully. Please login.");

                    header('Location: ' . BASE_URL . '/login.php');
                    exit;
                }
            } catch (Exception $e) {
                error_log("Registration Error: " . $e->getMessage());
                $register_error = 'Registration could not be completed right now. Please try again.';
            }
        }
    }
}

$page_title = 'Create Student Account | AcademicHub Nepal';
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
                        Join Thousands of Students
                    </h2>
                    <p class="text-light opacity-75 small mb-4" style="line-height: 1.6; font-size: 0.9rem;">
                        Create your account to access verified thesis models, research questionnaires, and APA 7th edition formatting templates.
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

            <!-- Right Pane: Registration Form -->
            <div class="auth-form-side col-lg-7 p-4 p-md-5 d-flex flex-column justify-content-center bg-white">

                <!-- Registration Form -->
                <div class="mb-3 text-center text-md-start">
                    <h3 class="fw-bold text-dark mb-1" style="font-family: 'Outfit', sans-serif;">Create Student Account</h3>
                    <p class="text-muted small">Join thousands of university students downloading reference academic blueprints.</p>
                </div>

                <?php if (!empty($register_error)): ?>
                    <div class="alert alert-danger py-3 px-3 rounded-3 mb-4 d-flex align-items-center border-0 shadow-sm" style="background-color: #FEF2F2; border-left: 4px solid #DC2626 !important;">
                        <i class="bi bi-exclamation-triangle-fill text-danger fs-5 me-2 flex-shrink-0"></i>
                        <div class="small text-danger fw-semibold"><?= htmlspecialchars($register_error) ?></div>
                    </div>
                <?php endif; ?>

                <form action="<?= BASE_URL ?>/register.php" method="POST">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark">Full Name <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border"><i class="bi bi-person text-muted"></i></span>
                            <input type="text" name="name" class="form-control" placeholder="e.g. Aayush Sharma" value="<?= htmlspecialchars($register_old['name']) ?>" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-dark">Email Address <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border"><i class="bi bi-envelope text-muted"></i></span>
                                <input type="email" name="email" class="form-control" placeholder="student@campus.edu.np" value="<?= htmlspecialchars($register_old['email']) ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-dark">Mobile Number (Nepal)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border"><i class="bi bi-telephone text-muted"></i></span>
                                <input type="text" name="phone" class="form-control" placeholder="98XXXXXXXX" value="<?= htmlspecialchars($register_old['phone']) ?>">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark">Affiliated Campus / College</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border"><i class="bi bi-building text-muted"></i></span>
                            <select name="campus" class="form-select">
                                <option value="Mark International College" <?= $register_old['campus'] === 'Mark International College' ? 'selected' : '' ?>>Mark International College</option>
                                <option value="Mahendra Multiple Campus" <?= $register_old['campus'] === 'Mahendra Multiple Campus' ? 'selected' : '' ?>>Mahendra Multiple Campus</option>
                                <option value="Deukhuri Bahumukhi Campus" <?= $register_old['campus'] === 'Deukhuri Bahumukhi Campus' ? 'selected' : '' ?>>Deukhuri Bahumukhi Campus</option>
                                <option value="Babai Campus" <?= $register_old['campus'] === 'Babai Campus' ? 'selected' : '' ?>>Babai Campus</option>
                                <option value="Gorkha Campus" <?= $register_old['campus'] === 'Gorkha Campus' ? 'selected' : '' ?>>Gorkha Campus</option>
                                <option value="Other Campuses" <?= $register_old['campus'] === 'Other Campuses' ? 'selected' : '' ?>>Other Campus (Tribhuvan University)</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-dark">Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border"><i class="bi bi-lock text-muted"></i></span>
                                <input type="password" name="password" id="regPassword" class="form-control" placeholder="Min. 6 chars" required>
                                <button type="button" class="btn btn-outline-secondary password-toggle-btn border" onclick="togglePasswordVisibility('regPassword', this)" title="Show / Hide Password" aria-label="Toggle password visibility">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-dark">Confirm Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border"><i class="bi bi-lock-fill text-muted"></i></span>
                                <input type="password" name="confirm_password" id="regConfirmPassword" class="form-control" placeholder="Re-enter password" required>
                                <button type="button" class="btn btn-outline-secondary password-toggle-btn border" onclick="togglePasswordVisibility('regConfirmPassword', this)" title="Show / Hide Password" aria-label="Toggle password visibility">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" name="agree" id="agreeTerms" required checked>
                        <label class="form-check-label small text-muted" for="agreeTerms">
                            I agree to the <a href="<?= BASE_URL ?>/about.php#terms" target="_blank" class="text-primary text-decoration-none fw-semibold">Terms of Use</a> and acknowledge that materials are for academic reference and study guidance.
                        </label>
                    </div>

                    <div class="d-grid mb-3">
                        <button type="submit" class="btn btn-hub-primary py-3 fw-bold shadow-sm" style="font-size: 0.95rem;">
                            <i class="bi bi-shield-check me-2"></i> Complete Student Registration
                        </button>
                    </div>

                    <div class="text-center small text-muted pt-3 border-top">
                        Already have an account?
                        <a href="<?= BASE_URL ?>/login.php" class="fw-bold text-primary text-decoration-none ms-1">Sign In</a>
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
