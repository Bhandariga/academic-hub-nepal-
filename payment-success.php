<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth_check.php';
require_once __DIR__ . '/config/payment_verification.php';
require_login();
$order = null;
$error_msg = 'Payment could not be verified. Check your orders or contact support before paying again.';
$encoded = $_GET['data'] ?? '';
$decoded = is_string($encoded) && strlen($encoded) <= 16384 ? base64_decode($encoded, true) : false;
$response = $decoded !== false ? json_decode($decoded, true) : null;
if (is_array($response) && esewa_valid_signature($response)) {
    try {
        $stmt = $pdo->prepare('SELECT o.*, r.title AS resource_title, r.pages, r.file_format, r.campus, r.program FROM orders o JOIN resources r ON r.id = o.resource_id WHERE o.transaction_uuid = :uuid AND o.user_id = :uid LIMIT 1');
        $stmt->execute([':uuid' => $response['transaction_uuid'], ':uid' => $_SESSION['user_id']]);
        $candidate = $stmt->fetch();
        if ($candidate && esewa_verify_order($response, $candidate)) {
            $pdo->beginTransaction();
            $update = $pdo->prepare("UPDATE orders SET payment_status = 'PAID', transaction_code = :code WHERE id = :id AND payment_status IN ('PENDING', 'PENDING_VERIFICATION')");
            $update->execute([':code' => $response['transaction_code'], ':id' => $candidate['id']]);
            if ($update->rowCount() === 1) {
                $pdo->prepare('UPDATE resources SET downloads_count = downloads_count + 1 WHERE id = :id')->execute([':id' => $candidate['resource_id']]);
                security_audit('PAYMENT_VERIFIED', ['order_id' => $candidate['id']]);
                $candidate['payment_status'] = 'PAID';
                $candidate['transaction_code'] = $response['transaction_code'];
            }
            $pdo->commit();
            if ($candidate['payment_status'] === 'PAID' && $candidate['transaction_code'] === $response['transaction_code']) $order = $candidate;
        } else {
            security_audit('PAYMENT_REJECTED');
        }
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('Payment verification: ' . $e->getMessage());
    }
} else {
    security_audit('PAYMENT_INVALID_CALLBACK');
}
$page_title = "Payment Successful | AcademicHub Nepal";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <?php if ($order && $order['payment_status'] === 'PAID'): ?>
                <!-- Success Card -->
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden text-center">
                    <div class="p-5 text-white" style="background: linear-gradient(135deg, #166534 0%, #15803d 100%);">
                        <div class="display-1 mb-2"><i class="bi bi-check-circle-fill text-white"></i></div>
                        <h2 class="fw-bold text-white mb-1">Payment Successful!</h2>
                        <p class="text-white-50 mb-0">Your academic reference resource is unlocked and ready for download.</p>
                    </div>

                    <div class="card-body p-4 p-md-5 bg-white text-start">
                        <!-- Order Receipt Summary Box -->
                        <div class="bg-light p-4 rounded-3 border mb-4">
                            <div class="row g-3">
                                <div class="col-sm-6">
                                    <span class="small text-muted d-block">Resource Title</span>
                                    <strong class="text-primary fs-6"><?= htmlspecialchars($order['resource_title']) ?></strong>
                                    <div class="small text-muted"><?= htmlspecialchars($order['campus']) ?> &bull; <?= htmlspecialchars($order['program']) ?></div>
                                </div>
                                <div class="col-sm-6 text-sm-end">
                                    <span class="small text-muted d-block">Amount Paid</span>
                                    <strong class="text-dark fs-5"><?= format_npr($order['amount']) ?></strong>
                                    <div class="badge bg-success mt-1"><i class="bi bi-shield-check me-1"></i> Verified eSewa</div>
                                </div>
                                <div class="col-sm-6 pt-2 border-top">
                                    <span class="small text-muted d-block">Transaction Code</span>
                                    <code class="text-dark fw-bold"><?= htmlspecialchars($order['transaction_code']) ?></code>
                                </div>
                                <div class="col-sm-6 pt-2 border-top text-sm-end">
                                    <span class="small text-muted d-block">Order Reference</span>
                                    <code class="text-secondary"><?= htmlspecialchars($order['transaction_uuid']) ?></code>
                                </div>
                            </div>
                        </div>

                        <!-- Big Download Now Action -->
                        <div class="d-grid gap-3 mb-4">
                            <a href="<?= BASE_URL ?>/download.php?order_id=<?= $order['id'] ?>" class="btn btn-hub-primary btn-lg fw-bold shadow-sm py-3">
                                <i class="bi bi-cloud-arrow-down-fill fs-5 me-2"></i> Download Resource Now (<?= htmlspecialchars($order['file_format']) ?>)
                            </a>
                            <a href="<?= BASE_URL ?>/dashboard.php" class="btn btn-outline-secondary py-2">
                                <i class="bi bi-grid-fill me-1"></i> Go to Student Dashboard
                            </a>
                        </div>

                        <div class="alert alert-info py-2 px-3 small border-0 mb-0 text-center">
                            <i class="bi bi-info-circle-fill me-1"></i> You can download this file anytime from your <a href="<?= BASE_URL ?>/dashboard.php#downloads" class="fw-bold">My Downloads</a> tab.
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Verification Issue Card -->
                <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white">
                    <div class="text-danger display-3 mb-3"><i class="bi bi-exclamation-octagon-fill"></i></div>
                    <h3 class="fw-bold text-dark mb-2">Payment Verification Issue</h3>
                    <p class="text-muted"><?= htmlspecialchars($error_msg ?: 'We could not automatically confirm your payment.') ?></p>
                    <div class="d-flex justify-content-center gap-3 mt-3">
                        <a href="<?= BASE_URL ?>/resources.php" class="btn btn-outline-primary">Return to Catalog</a>
                        <a href="<?= BASE_URL ?>/dashboard.php" class="btn btn-primary">Check Orders in Dashboard</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
