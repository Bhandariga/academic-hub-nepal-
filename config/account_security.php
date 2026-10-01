<?php
/** Tokens are random, hashed at rest, purpose-bound, single-use and expiring. */
function security_storage_ready(PDO $pdo): bool {
    static $ready;
    if ($ready !== null) return $ready;
    try { $pdo->query('SELECT id FROM auth_tokens LIMIT 0'); return $ready = true; }
    catch (PDOException $e) { return $ready = false; }
}
function account_mail_ready(PDO $pdo): bool {
    return security_storage_ready($pdo) && getenv('AUTH_MAIL_ENABLED') === '1'
        && filter_var(getenv('MAIL_FROM'), FILTER_VALIDATE_EMAIL)
        && parse_url(getenv('APP_URL') ?: '', PHP_URL_SCHEME) === 'https';
}
function account_create_token(PDO $pdo, array $user, string $purpose, int $ttl): string {
    if (!in_array($purpose, ['reset','verify','remember'], true)) throw new InvalidArgumentException('Invalid token purpose.');
    $token = bin2hex(random_bytes(32));
    $stmt = $pdo->prepare('INSERT INTO auth_tokens (user_id,purpose,token_hash,password_version,email,expires_at) VALUES (?,?,?,?,?,?)');
    $stmt->execute([$user['id'], $purpose, hash('sha256', $token), hash('sha256', $user['password']), $user['email'], gmdate('Y-m-d H:i:s', time() + $ttl)]);
    return $token;
}
function account_send_link(PDO $pdo, array $user, string $purpose): bool {
    if (!account_mail_ready($pdo) || !in_array($purpose, ['reset','verify'], true)) return false;
    $token = account_create_token($pdo, $user, $purpose, $purpose === 'reset' ? 1800 : 86400);
    // Configured canonical URL only: never derive recovery links from an incoming Host header.
    $url = rtrim(getenv('APP_URL'), '/') . '/account-recovery.php?purpose=' . $purpose . '&token=' . $token;
    $subject = $purpose === 'reset' ? 'Reset your AcademicHub password' : 'Verify your AcademicHub email';
    $body = $subject . "\n\nOpen this link and confirm the action:\n" . $url
        . "\n\nThis link works once and expires " . ($purpose === 'reset' ? 'in 30 minutes.' : 'in 24 hours.')
        . "\nIf you did not request this, you can ignore this email.\n";
    $sent = mail($user['email'], $subject, $body, ['From' => getenv('MAIL_FROM'), 'Content-Type' => 'text/plain; charset=UTF-8']);
    if (!$sent) {
        $pdo->prepare('DELETE FROM auth_tokens WHERE token_hash = ?')->execute([hash('sha256', $token)]);
        security_audit('AUTH_MAIL_FAILED');
    }
    return $sent;
}
/** Must be called inside a transaction. Lock protects consumption against replay races. */
function account_lock_token(PDO $pdo, string $token, string $purpose): ?array {
    if (!preg_match('/^[a-f0-9]{64}$/D', $token)) return null;
    $stmt = $pdo->prepare('SELECT * FROM auth_tokens WHERE token_hash = ? AND purpose = ? AND expires_at > ? FOR UPDATE');
    $stmt->execute([hash('sha256', $token), $purpose, gmdate('Y-m-d H:i:s')]);
    $row = $stmt->fetch();
    if (!$row) return null;
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? AND is_active = 1 FOR UPDATE');
    $stmt->execute([$row['user_id']]);
    $user = $stmt->fetch();
    if (!$user || !hash_equals($row['password_version'], hash('sha256', $user['password'])) || $row['email'] !== $user['email']) return null;
    return ['token' => $row, 'user' => $user];
}
function account_consume_link(PDO $pdo, string $token, string $purpose, string $password = ''): bool {
    if (!in_array($purpose, ['reset','verify'], true)) return false;
    if ($purpose === 'reset' && (strlen($password) < 12 || strlen($password) > 72)) return false;
    $pdo->beginTransaction();
    try {
        $locked = account_lock_token($pdo, $token, $purpose);
        if (!$locked) { $pdo->rollBack(); return false; }
        $user = $locked['user'];
        if ($purpose === 'reset') {
            $pdo->prepare('UPDATE users SET password = ? WHERE id = ?')->execute([password_hash($password, PASSWORD_DEFAULT), $user['id']]);
            // Revoke all remembered devices, recovery links and existing versioned sessions.
            $pdo->prepare('DELETE FROM auth_tokens WHERE user_id = ?')->execute([$user['id']]);
        } else {
            $pdo->prepare('INSERT INTO verified_emails (user_id,email) VALUES (?,?) ON DUPLICATE KEY UPDATE email = VALUES(email), verified_at = CURRENT_TIMESTAMP')->execute([$user['id'], $user['email']]);
            $pdo->prepare("DELETE FROM auth_tokens WHERE user_id = ? AND purpose = 'verify'")->execute([$user['id']]);
        }
        $pdo->commit();
        security_audit($purpose === 'reset' ? 'PASSWORD_RECOVERED' : 'EMAIL_VERIFIED', ['account' => $user['id']]);
        return true;
    } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
}
function remember_cookie(string $value, int $expires): void {
    setcookie('ah_remember', $value, ['expires' => $expires, 'path' => (parse_url(BASE_URL, PHP_URL_PATH) ?: '') . '/',
        'secure' => true, 'httponly' => true, 'samesite' => 'Lax']);
}
function remember_issue(PDO $pdo, array $user): void {
    // Persistent login is deliberately limited to students and HTTPS.
    if ($user['role'] !== 'student' || empty($GLOBALS['isHttps']) || !security_storage_ready($pdo)) return;
    $token = account_create_token($pdo, $user, 'remember', 2592000);
    remember_cookie($token, time() + 2592000);
}
function remember_restore(PDO $pdo): void {
    $token = $_COOKIE['ah_remember'] ?? '';
    if (!is_string($token) || $token === '' || isset($_SESSION['user_id']) || empty($GLOBALS['isHttps']) || !security_storage_ready($pdo)) return;
    $pdo->beginTransaction();
    try {
        $locked = account_lock_token($pdo, $token, 'remember');
        if (!$locked || $locked['user']['role'] !== 'student') { $pdo->rollBack(); remember_cookie('', time() - 3600); return; }
        $user = $locked['user'];
        $pdo->prepare('DELETE FROM auth_tokens WHERE id = ?')->execute([$locked['token']['id']]);
        $replacement = account_create_token($pdo, $user, 'remember', 2592000);
        $pdo->commit();
        session_regenerate_id(true);
        $_SESSION = ['user_id' => $user['id'], 'user_name' => $user['name'], 'user_email' => $user['email'],
            'user_role' => $user['role'], 'user_permissions' => $user['permissions'], 'user_campus' => $user['campus'],
            'user_avatar' => $user['profile_image'], 'last_activity' => time()];
        auth_login_succeeded($user);
        remember_cookie($replacement, time() + 2592000);
    } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); remember_cookie('', time() - 3600); security_audit('REMEMBER_REJECTED'); }
}
function remember_revoke(PDO $pdo): void {
    $token = $_COOKIE['ah_remember'] ?? '';
    if (is_string($token) && security_storage_ready($pdo)) $pdo->prepare("DELETE FROM auth_tokens WHERE token_hash = ? AND purpose = 'remember'")->execute([hash('sha256', $token)]);
    remember_cookie('', time() - 3600);
}
