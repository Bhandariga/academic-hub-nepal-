<?php
/**
 * AcademicHub Nepal - eSewa Payment Success & Order Finalization
 * File: payment-success.php
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth_check.php';

require_login();

$user_id = $_SESSION['user_id'];
$is_verified = false;
$error_msg = '';
$order = null;
$resource = null;

// eSewa ePay V2 returns encoded Base64 string in ?data=...
$encoded_data = $_GET['data'] ?? '';
$demo_simulate = isset($_GET['demo_simulate']) && $_GET['demo_simulate'] == 1;

$transaction_uuid = '';
$transaction_code = '';
$total_amount = '';
$status = '';

if (!empty($encoded_data)) {
    // Decode base64 payload from eSewa
    $decoded_json = base64_decode($encoded_data);
    $response = json_decode($decoded_json, true);

    if ($response && is_array($response)) {
        $transaction_uuid = $response['transaction_uuid'] ?? '';
        $transaction_code = $response['transaction_code'] ?? ('ESEWA-' . strtoupper(substr(md5(time()), 0, 8)));
        $total_amount = $response['total_amount'] ?? 0;
        $status = $response['status'] ?? '';

        if ($status === 'COMPLETE') {
            $is_verified = true;
        }
    }
} elseif ($demo_simulate) {
    // Fast-track demo simulation mode for testing
    $transaction_uuid = $_GET['transaction_uuid'] ?? '';
    $total_amount = $_GET['total_amount'] ?? 0;
    $transaction_code = 'ESEWA-UAT-' . strtoupper(substr(uniqid(), -8));
    $is_verified = true;
}

if (!empty($transaction_uuid)) {
    try {
        // Fetch order from database
        $stmt = $pdo->prepare("
            SELECT o.*, r.title as resource_title, r.pages, r.file_format, r.campus, r.program, r.file_path 
            FROM orders o
            JOIN resources r ON o.resource_id = r.id
            WHERE o.transaction_uuid = :uuid AND o.user_id = :uid
            LIMIT 1
        ");
        $stmt->execute([':uuid' => $transaction_uuid, ':uid' => $user_id]);
        $order = $stmt->fetch();

        if ($order) {
            if ($is_verified) {
                // Update Order Status to PAID and store transaction code
                $update_stmt = $pdo->prepare("
                    UPDATE orders 
                    SET payment_status = 'PAID', transaction_code = :txn_code 
                    WHERE id = :order_id
                ");
                $update_stmt->execute([
                    ':txn_code' => $transaction_code,
                    ':order_id' => $order['id']
                ]);

                // Increment resource download/popularity counter
                $pdo->prepare("UPDATE resources SET downloads_count = downloads_count + 1 WHERE id = :rid")
                    ->execute([':rid' => $order['resource_id']]);

                // Update local array
                $order['payment_status'] = 'PAID';
                $order['transaction_code'] = $transaction_code;
            } else {
                $error_msg = "eSewa returned a status other than COMPLETE ({$status}).";
            }
        } else {
            $error_msg = "Order with reference {$transaction_uuid} could not be located.";
        }
    } catch (Exception $e) {
        $error_msg = "Database verification error: " . $e->getMessage();
    }
} else {
    $error_msg = "No transaction verification data received.";
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
