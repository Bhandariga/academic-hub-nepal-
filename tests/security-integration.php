<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../config/auth_check.php';
function assertion(bool $ok, string $label): void {
    if (!$ok) throw new RuntimeException($label);
    echo "PASS: $label\n";
}
$id = null; $rateKey = bin2hex(random_bytes(16));
try {
    $email = 'security-test-' . $rateKey . '@example.invalid';
    $hash = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
    $pdo->prepare("INSERT INTO users (name,email,password,role,is_active) VALUES ('Security test fixture',?,?,'student',1)")->execute([$email,$hash]);
    $id = (int)$pdo->lastInsertId();
    $user = ['id'=>$id,'email'=>$email,'password'=>$hash];
    $token = account_create_token($pdo,$user,'verify',60);
    $stored = $pdo->prepare('SELECT token_hash FROM auth_tokens WHERE user_id = ?'); $stored->execute([$id]);
    assertion($stored->fetchColumn() === hash('sha256',$token), 'Token stored as hash only');
    assertion(!account_consume_link($pdo,$token,'reset','a-long-test-password'), 'Purpose mismatch rejected');
    assertion(account_consume_link($pdo,$token,'verify'), 'Verification consumes valid token');
    assertion(!account_consume_link($pdo,$token,'verify'), 'Token replay rejected');
    $expired = account_create_token($pdo,$user,'reset',-1);
    assertion(!account_consume_link($pdo,$expired,'reset','a-long-test-password'), 'Expired token rejected');
    $remember = account_create_token($pdo,$user,'remember',60);
    $reset = account_create_token($pdo,$user,'reset',60);
    assertion(!account_consume_link($pdo,$reset,'reset','short'), 'Weak reset password rejected');
    assertion(account_consume_link($pdo,$reset,'reset','a-long-test-password'), 'Password reset succeeds');
    $read = $pdo->prepare('SELECT password FROM users WHERE id = ?'); $read->execute([$id]);
    assertion(password_verify('a-long-test-password',$read->fetchColumn()), 'Reset password is hashed');
    $read = $pdo->prepare('SELECT COUNT(*) FROM auth_tokens WHERE user_id = ?'); $read->execute([$id]);
    assertion((int)$read->fetchColumn() === 0, 'Reset revokes every device and recovery token');
    $stale = account_create_token($pdo,$user,'reset',60);
    assertion(!account_consume_link($pdo,$stale,'reset','another-long-password'), 'Credential-bound stale token rejected');
    assertion(auth_rate_limit('test',$rateKey,2), 'First attempt allowed');
    assertion(auth_rate_limit('test',$rateKey,2), 'Second attempt allowed');
    assertion(!auth_rate_limit('test',$rateKey,2), 'Persistent attempt limit enforced');
    ob_start(); render_seo_schema(['schema'=>['name'=>'</script><script>alert(1)</script>']]); $output=ob_get_clean();
    assertion(!str_contains($output,'<script>alert(1)'), 'JSON-LD cannot break out of script tag');
    assertion(str_contains(render_user_avatar('../config.php','<script>'), '&lt;'), 'Avatar initial escaped and traversal ignored');
    $_SESSION['user_id']=$id; $_SESSION['user_role']='admin'; $_SESSION['user_permissions']='["all"]';
    assertion(!admin_has_permission('system_settings'), 'System settings restricted to superadmin');
    assertion(!admin_has_permission('manage_admins'), 'Admin management restricted to superadmin');
    echo "Integration checks passed. Disposable fixture will be removed.\n";
} finally {
    if ($pdo->inTransaction()) $pdo->rollBack();
    if ($id) $pdo->prepare('DELETE FROM users WHERE id = ? AND email = ?')->execute([$id,$email]);
    $rateFile=__DIR__.'/../logs/rate-'.hash('sha256','test:'.$rateKey).'.json';
    if (is_file($rateFile)) unlink($rateFile);
}
