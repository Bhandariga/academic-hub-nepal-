<?php
require __DIR__ . '/config/db.php';
require __DIR__ . '/config/auth_check.php';
require_once __DIR__ . '/config/account_security.php';
header('Referrer-Policy: no-referrer');
$purpose = $_POST['purpose'] ?? $_GET['purpose'] ?? 'reset';
$purpose = in_array($purpose, ['reset', 'verify'], true) ? $purpose : 'reset';
$token = $_POST['token'] ?? $_GET['token'] ?? '';
$token = is_string($token) && preg_match('/^[a-f0-9]{64}$/D', $token) ? $token : '';
$message = ''; $error = ''; $ready = account_mail_ready($pdo);
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    try {
        if ($token !== '' && security_storage_ready($pdo)) {
            $password = auth_text('password', 72);
            if ($purpose === 'reset' && (strlen($password) < 12 || $password !== auth_text('confirm_password', 72))) {
                $error = 'Use 12–72 bytes for your password and enter the same password twice.';
            } elseif (account_consume_link($pdo, $token, $purpose, $password)) {
                $message = $purpose === 'reset' ? 'Password updated. Sign in with your new password.' : 'Your email address is now verified.';
                $token = '';
            } else $error = 'This link is invalid or expired. Request a new link below.';
        } elseif ($ready) {
            $email = trim(auth_text('email', 150));
            $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? AND is_active = 1 LIMIT 1');
            $stmt->execute([$email]); $user = $stmt->fetch();
            if ($user && filter_var($email, FILTER_VALIDATE_EMAIL)) account_send_link($pdo, $user, $purpose);
            $message = 'If this address is eligible, an email will arrive with your next steps. Check your spam folder too.';
            security_audit('RECOVERY_REQUESTED');
        } else $error = 'Email recovery is not configured yet. Please contact support.';
    } catch (Throwable $e) { security_audit('RECOVERY_SERVICE_ERROR'); $error = 'Account recovery is temporarily unavailable. Please try again later.'; }
}
$page_title = $purpose === 'verify' ? 'Verify your email | AcademicHub Nepal' : 'Account recovery | AcademicHub Nepal';
require __DIR__ . '/includes/header.php';
?>
<main class="container py-5"><section class="card p-4 p-md-5 mx-auto" style="max-width:560px">
<span class="text-primary small fw-bold">ACCOUNT SECURITY</span>
<h1 class="h3 mt-2"><?= $purpose === 'verify' ? 'Verify your email' : 'Recover your account' ?></h1>
<p class="text-muted">Secure, single-use links help keep your academic account protected.</p>
<?php if ($message): ?><div class="alert alert-success" role="status"><?= sanitize($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger" role="alert"><?= sanitize($error) ?></div><?php endif; ?>
<?php if (!$ready && !$token): ?><div class="alert alert-info">Email delivery is awaiting administrator configuration. Existing sign-in remains available.</div><?php endif; ?>
<?php if (!$message && ($ready || $token)): ?>
<form method="post" action="<?= BASE_URL ?>/account-recovery.php">
<?= csrf_field() ?><input type="hidden" name="purpose" value="<?= sanitize($purpose) ?>">
<?php if ($token): ?><input type="hidden" name="token" value="<?= sanitize($token) ?>">
<?php if ($purpose === 'reset'): ?>
<label class="form-label" for="new-password">New password</label><input id="new-password" class="form-control mb-3" type="password" name="password" minlength="12" maxlength="72" autocomplete="new-password" required>
<label class="form-label" for="confirm-password">Confirm password</label><input id="confirm-password" class="form-control mb-3" type="password" name="confirm_password" minlength="12" maxlength="72" autocomplete="new-password" required>
<?php endif; ?><button class="btn btn-primary w-100" type="submit"><?= $purpose === 'verify' ? 'Verify email address' : 'Update password' ?></button>
<?php else: ?><label class="form-label" for="recovery-email">Account email</label><input id="recovery-email" class="form-control mb-3" type="email" name="email" maxlength="150" autocomplete="email" required><button class="btn btn-primary w-100" type="submit">Send secure link</button><?php endif; ?>
</form><?php endif; ?>
<a class="mt-4" href="<?= BASE_URL ?>/login.php">Return to sign in</a>
<a class="mt-2 small" href="<?= BASE_URL ?>/account-recovery.php?purpose=<?= $purpose === 'reset' ? 'verify' : 'reset' ?>"><?= $purpose === 'reset' ? 'Verify your email address' : 'Request a password reset' ?></a>
</section></main>
<?php require __DIR__ . '/includes/footer.php'; ?>
