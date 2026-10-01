<?php
/** Persistent throttling shared by student/admin login and recovery endpoints. */
function auth_text(string $key, int $max = 255): string {
    $value = $_POST[$key] ?? '';
    return is_string($value) && strlen($value) <= $max ? $value : '';
}
function auth_rate_limit(string $scope, string $identity, int $limit, int $seconds = 900): bool {
    $path = __DIR__ . '/../logs/rate-' . hash('sha256', $scope . ':' . $identity) . '.json';
    $handle = @fopen($path, 'c+');
    if (!$handle || !flock($handle, LOCK_EX)) {
        if ($handle) fclose($handle);
        throw new RuntimeException('Authentication protection is unavailable.');
    }
    try {
        $state = json_decode(stream_get_contents($handle), true);
        if (!is_array($state) || ($state['until'] ?? 0) <= time()) $state = ['until' => time() + $seconds, 'count' => 0];
        $allowed = $state['count'] < $limit;
        if ($allowed) $state['count']++;
        rewind($handle);
        if (!ftruncate($handle, 0) || fwrite($handle, json_encode($state)) === false || !fflush($handle)) {
            throw new RuntimeException('Authentication protection is unavailable.');
        }
        return $allowed;
    } finally { flock($handle, LOCK_UN); fclose($handle); }
}
function auth_login_succeeded(array $user): void {
    $_SESSION['password_version'] = hash('sha256', $user['password']);
    $_SESSION['authenticated_at'] = time();
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $GLOBALS['auth_success'] = true;
    security_audit('LOGIN_SUCCESS', ['account' => $user['id'], 'role' => $user['role']]);
}
