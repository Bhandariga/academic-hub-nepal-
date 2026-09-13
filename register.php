<?php
/**
 * AcademicHub Nepal - Student Registration
 * File: register.php
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth_check.php';

$error = '';
$redirect_target = $_GET['redirect'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $campus = trim($_POST['campus'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $agree = isset($_POST['agree']);

    if (empty($name) || empty($email) || empty($password)) {
        $error = 'Please fill in all mandatory fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please provide a valid student email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirm_password) {
        $error = 'Password and confirmation do not match.';
    } elseif (!$agree) {
        $error = 'You must agree to the academic integrity terms.';
    } else {
        try {
            // Check if email already registered
            $check_stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
            $check_stmt->execute([':email' => $email]);
            if ($check_stmt->fetch()) {
                $error = 'An account with this email address already exists. Please login.';
            } else {
                // Hash password securely
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                
                $insert_stmt = $pdo->prepare("
                    INSERT INTO users (name, email, password, phone, campus, role) 
                    VALUES (:name, :email, :password, :phone, :campus, 'student')
                ");
                $insert_stmt->execute([
                    ':name' => $name,
                    ':email' => $email,
                    ':password' => $hashed_password,
                    ':phone' => $phone,
                    ':campus' => $campus
                ]);

                $new_id = $pdo->lastInsertId();

                // Auto login student
                $_SESSION['user_id'] = $new_id;
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_role'] = 'student';
                $_SESSION['user_campus'] = $campus;

                set_flash_message('success', 'Registration successful! Welcome to AcademicHub Nepal.');
                
                if (!empty($redirect_target)) {
                    header('Location: ' . urldecode($redirect_target));
                } else {
                    header('Location: ' . BASE_URL . '/dashboard.php');
                }
                exit;
            }
        } catch (Exception $e) {
            $error = 'Registration failed: ' . $e->getMessage();
        }
    }
}

$page_title = "Register Student Account | AcademicHub Nepal";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-6">
            <div class="card border-0 shadow rounded-4 overflow-hidden">
                <div class="p-4 bg-primary text-white text-center" style="background: linear-gradient(135deg, #0f2942 0%, #398de7ff 100%) !important;">
                    <div class="hub-icon mx-auto mb-2 text-info fs-3">
                        <i class="bi bi-person-plus-fill"></i>
                    </div>
                    <h3 class="fw-bold mb-1 text-white">Create Student Account</h3>
                    <p class="small text-light opacity-75 mb-0">Join thousands of Nepalese college students</p>
                </div>

                <div class="card-body p-4 p-md-5 bg-white">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger py-2 px-3 small rounded-3 mb-4">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>

                    <form action="" method="POST">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-muted">Full Name *</label>
                            <input type="text" name="name" class="form-control" placeholder="e.g. Aayush Sharma" required>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-muted">Email Address *</label>
                                <input type="email" name="email" class="form-control" placeholder="student@campus.edu.np" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-muted">Mobile Number (Nepal)</label>
                                <input type="text" name="phone" class="form-control" placeholder="98XXXXXXXX">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-muted">Your Campus / College</label>
                            <select name="campus" class="form-select">
                                <option value="Gyan Jyoti Campus">Gyan Jyoti Campus</option>
                                <option value="Babai Campus">Babai Campus</option>
                                <option value="Gorkha Campus">Gorkha Campus</option>
                                <option value="Other Campuses" selected>Other Campus (Tribhuvan University)</option>
                            </select>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-muted">Password *</label>
                                <input type="password" name="password" class="form-control" placeholder="Min. 6 chars" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-muted">Confirm Password *</label>
                                <input type="password" name="confirm_password" class="form-control" placeholder="Re-enter password" required>
                            </div>
                        </div>

                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" name="agree" id="agreeTerms" required checked>
                            <label class="form-check-label small text-muted" for="agreeTerms">
                                I agree to the <a href="<?= BASE_URL ?>/about.php#terms" target="_blank">Terms of Use</a> and acknowledge that resources are for reference and structural guidance only.
                            </label>
                        </div>

                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-hub-primary py-2 fw-semibold">
                                <i class="bi bi-shield-check me-2"></i> Complete Registration
                            </button>
                        </div>

                        <div class="text-center small text-muted">
                            Already have an account? 
                            <a href="<?= BASE_URL ?>/login.php" class="fw-bold text-primary">Login Here</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
