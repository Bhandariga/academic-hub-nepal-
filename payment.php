<?php
/**
 * AcademicHub Nepal - Order Checkout & Simple eSewa QR Payment
 * File: payment.php
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth_check.php';
require_once __DIR__ . '/config/notifications.php';

// Force login before payment
$resource_id = isset($_GET['resource_id']) ? (int)$_GET['resource_id'] : 0;
if ($resource_id <= 0 && isset($_POST['resource_id'])) {
    $resource_id = (int)$_POST['resource_id'];
}

if ($resource_id <= 0) {
    set_flash_message('danger', 'Please select an academic resource to purchase.');
    header('Location: ' . BASE_URL . '/resources.php');
    exit;
}

require_login(BASE_URL . '/payment.php?resource_id=' . $resource_id);

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

try {
    // Fetch resource details
    $stmt = $pdo->prepare("SELECT * FROM resources WHERE id = :id AND status = 'published' AND visibility = 'public'");
    $stmt->execute([':id' => $resource_id]);
    $resource = $stmt->fetch();

    if (!$resource) {
        set_flash_message('danger', 'The selected resource is invalid or has been archived.');
        header('Location: ' . BASE_URL . '/resources.php');
        exit;
    }

    // Check if user already owns this resource
    $stmt_check = $pdo->prepare("SELECT id FROM orders WHERE user_id = :uid AND resource_id = :rid AND payment_status = 'PAID' LIMIT 1");
    $stmt_check->execute([':uid' => $user_id, ':rid' => $resource['id']]);
    if ($stmt_check->fetch()) {
        set_flash_message('info', 'You already own this resource! You can download it directly from your dashboard.');
        header('Location: ' . BASE_URL . '/dashboard.php#downloads');
        exit;
    }

    $amount = number_format((float)$resource['price'], 2, '.', '');
    $total_amount = $amount;

    // Find or create pending order
    $stmt_find = $pdo->prepare("SELECT * FROM orders WHERE user_id = :uid AND resource_id = :rid AND payment_status IN ('PENDING', 'PENDING_VERIFICATION') ORDER BY id DESC LIMIT 1");
    $stmt_find->execute([':uid' => $user_id, ':rid' => $resource['id']]);
    $order = $stmt_find->fetch();

    if ($order) {
        $transaction_uuid = $order['transaction_uuid'];
        $order_id = $order['id'];
        $amount = $total_amount = number_format((float)$order['amount'], 2, '.', '');
    } else {
        $transaction_uuid = 'ORD-' . bin2hex(random_bytes(16));
        $insert_order = $pdo->prepare("
            INSERT INTO orders (user_id, resource_id, amount, transaction_uuid, payment_method, payment_status, created_at)
            VALUES (:user_id, :resource_id, :amount, :transaction_uuid, 'ESEWA_QR', 'PENDING', NOW())
        ");
        $insert_order->execute([
            ':user_id' => $user_id,
            ':resource_id' => $resource['id'],
            ':amount' => $total_amount,
            ':transaction_uuid' => $transaction_uuid
        ]);
        $order_id = $pdo->lastInsertId();
    }

    // Handle QR Payment Form Submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type'])) {
        if (!verify_csrf_token()) {
            $error = 'Security session validation expired. Please refresh and try again.';
        } else {
            $action_type = $_POST['action_type']; // 'submit_verification' or 'instant_demo'
            $transaction_code = trim($_POST['transaction_code'] ?? '');

            // Handle optional receipt upload with strict MIME & size validation
            $proof_path = null;
            if (isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] === UPLOAD_ERR_OK) {
                try {
                    require_once __DIR__ . '/config/uploads.php';
                    $receipt = $_FILES['payment_proof'];
                    $ext = strtolower(pathinfo($receipt['name'], PATHINFO_EXTENSION));
                    if (!in_array($ext, ['jpg','jpeg','png','webp','pdf'], true) || filesize($receipt['tmp_name']) > 5 * 1024 * 1024) throw new RuntimeException('Use an image or PDF receipt up to 5 MB.');
                    $newFileName = store_validated_upload($receipt, RECEIPTS_DIR, $ext !== 'pdf');
                    $proof_path = 'uploads/receipts/' . $newFileName;
                } catch (RuntimeException $e) { $error = safe_error_message($e); }
            }

            if (empty($error)) {
                // Standard Client Workflow: Submit for Admin Approval
                if (empty($transaction_code)) {
                    $error = 'Please enter the eSewa Transaction ID or Reference Code from your eSewa payment receipt.';
                } else {
                    $upd = $pdo->prepare("
                        UPDATE orders 
                        SET payment_method = 'ESEWA_QR', payment_status = 'PENDING_VERIFICATION', transaction_code = :code, payment_proof = COALESCE(:proof, payment_proof) 
                        WHERE id = :oid AND user_id = :uid AND payment_status IN ('PENDING', 'PENDING_VERIFICATION')
                    ");
                    $upd->execute([
                        ':code' => $transaction_code,
                        ':proof' => $proof_path,
                        ':oid' => $order_id,
                        ':uid' => $user_id
                    ]);

                    // Notify student about pending verification
                    create_notification(
                        $pdo,
                        $user_id,
                        'Payment Submitted for Verification',
                        "Your payment for '{$resource['title']}' has been submitted. Admin will verify and unlock your download shortly.",
                        'info',
                        BASE_URL . '/dashboard.php#orders'
                    );

                    // Notify admins about new payment verification request
                    create_admin_notification(
                        $pdo,
                        'New Payment Verification Required',
                        "Student has submitted payment for '{$resource['title']}' (Order: {$transaction_uuid}). Please verify the transaction.",
                        'warning',
                        BASE_URL . '/admin/manage-orders.php?status=PENDING_VERIFICATION'
                    );

                    set_flash_message('success', 'eSewa QR Payment details submitted! Transaction Ref: ' . htmlspecialchars($transaction_code) . '. Admin will verify and unlock your download shortly.');
                    header('Location: ' . BASE_URL . '/dashboard.php#orders');
                    exit;
                }
            }
        }
    }

    // Prepare eSewa online gateway signature if user prefers automated ePay
    $signature = generate_esewa_signature($total_amount, $transaction_uuid, ESEWA_PRODUCT_CODE, ESEWA_SECRET_KEY);

} catch (Exception $e) {
    error_log($e->getMessage());
    http_response_code(500); exit('Checkout is temporarily unavailable. Please try again.');
}

$page_title = "eSewa QR Checkout | AcademicHub Nepal";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5 checkout-shell">
    <div class="row justify-content-center">
        <div class="col-lg-9">

            <!-- Checkout Breadcrumb & Headline -->
            <div class="checkout-heading d-flex flex-wrap justify-content-between align-items-center mb-4">
                <div>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-1 small text-muted">
                            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/resources.php">Resources</a></li>
                            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/resource-details.php?id=<?= $resource['id'] ?>"><?= htmlspecialchars($resource['title']) ?></a></li>
                            <li class="breadcrumb-item active">Checkout</li>
                        </ol>
                    </nav>
                    <h1 class="h3 fw-bold text-dark mb-0">Your next step starts here.</h1><p class="text-muted mt-2 mb-0">Review your resource and complete your payment.</p>
                </div>
                <div class="badge bg-white border text-dark px-3 py-2 rounded-pill shadow-sm">
                    Order Ref: <strong class="text-primary font-monospace"><?= htmlspecialchars($transaction_uuid) ?></strong>
                </div>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <ol class="checkout-progress" aria-label="Checkout progress"><li><span>1</span> Choose resource</li><li aria-current="step"><span>2</span> Payment &amp; proof</li><li><span>3</span> Verification</li><li><span>4</span> Download</li></ol>
            <!-- Order Item Summary Card -->
            <div class="checkout-summary card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
                <div class="card-body p-4 bg-white">
                    <div class="row align-items-center">
                        <div class="col-md-7">
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill mb-2 px-3">Academic Material</span>
                            <h4 class="fw-bold text-dark mb-1"><?= htmlspecialchars($resource['title']) ?></h4>
                            <div class="text-muted small">
                                <i class="bi bi-mortarboard me-1"></i><?= htmlspecialchars($resource['campus']) ?> &bull; <?= htmlspecialchars($resource['program']) ?> &bull; <?= htmlspecialchars($resource['subject']) ?>
                            </div>
                        </div>
                        <div class="col-md-5 text-md-end mt-3 mt-md-0 pt-3 pt-md-0 border-top border-md-top-0">
                            <span class="small text-muted d-block text-uppercase fw-bold">Total Payable</span>
                            <div class="display-6 fw-bold text-success mb-0"><?= format_npr($total_amount) ?></div>
                            <span class="small text-muted"><i class="bi bi-shield-check text-success"></i> 100% Verified Digital Delivery</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="checkout-student mb-4"><i class="bi bi-person-check"></i><div><strong><?= htmlspecialchars($user['name'] ?? $_SESSION['user_name'] ?? 'Student account') ?></strong><span><?= htmlspecialchars($user['email'] ?? '') ?></span></div><span class="badge bg-primary-subtle text-primary ms-auto"><?= htmlspecialchars(str_replace('_', ' ', $order['payment_status'] ?? 'PENDING')) ?></span></div>
            <!-- Payment Methods Card -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                <div class="card-header bg-white border-bottom p-3 p-md-4">
                    <ul class="nav nav-pills card-header-pills gap-2" id="paymentTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active fw-bold py-2 px-4 rounded-pill" id="qr-tab" data-bs-toggle="tab" data-bs-target="#qr-pane" type="button" role="tab">
                                <i class="bi bi-qr-code-scan me-2"></i>eSewa QR Scan &amp; Pay (Recommended)
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold py-2 px-4 rounded-pill text-muted" id="epay-tab" data-bs-toggle="tab" data-bs-target="#epay-pane" type="button" role="tab">
                                <i class="bi bi-wallet2 me-2"></i>eSewa ePay V2 Online Gateway
                            </button>
                        </li>
                    </ul>
                </div>

                <div class="card-body p-4 p-md-5 bg-white">
                    <div class="tab-content" id="paymentTabsContent">
                        
                        <!-- TAB 1: eSewa QR Code Scan & Pay -->
                        <div class="tab-pane fade show active" id="qr-pane" role="tabpanel">
                            <div class="row g-4 align-items-center">
                                <!-- QR Display Column -->
                                <div class="col-md-5 text-center"><h2 class="h5 mb-3">Scan QR and pay</h2>
                                    <div class="p-3 bg-light rounded-4 border shadow-sm d-inline-block w-100" style="max-width: 320px;">
                                        <div class="position-relative overflow-hidden rounded-3 border mb-3 bg-white p-2">
                                            <img src="<?= BASE_URL ?>/assets/images/esewa_qr.jpg" alt="eSewa QR Code" class="img-fluid rounded-2 shadow-sm" style="max-height: 320px; object-fit: contain;">
                                        </div>
                                        <div class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill fw-bold w-100 mb-2">
                                            <i class="bi bi-check-circle-fill me-1"></i> Ganesh Bhandari
                                        </div>
                                        <div class="small text-muted font-monospace">eSewa ID: <strong>9765387827</strong></div>
                                        <div class="small fw-bold text-dark mt-1">Payable: <span class="text-success fs-6"><?= format_npr($total_amount) ?></span></div>
                                    </div>
                                </div>

                                <!-- Form & Instructions Column -->
                                <div class="col-md-7">
                                    <h5 class="fw-bold text-dark mb-3"><i class="bi bi-phone me-2 text-primary"></i>How to Complete Payment:</h5>
                                    
                                    <div class="d-flex flex-column gap-2 mb-4">
                                        <div class="d-flex align-items-start gap-3 p-2 rounded-3 bg-light border">
                                            <span class="badge bg-primary rounded-circle p-2 px-2 fs-6">1</span>
                                            <div>
                                                <strong class="d-block text-dark small">Scan QR with eSewa App</strong>
                                                <span class="text-muted small">Open your eSewa mobile app, tap <strong>Scan QR</strong>, and scan the QR code.</span>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-start gap-3 p-2 rounded-3 bg-light border">
                                            <span class="badge bg-primary rounded-circle p-2 px-2 fs-6">2</span>
                                            <div>
                                                <strong class="d-block text-dark small">Transfer <?= format_npr($total_amount) ?></strong>
                                                <span class="text-muted small">In remarks, write: 
                                                    <code class="bg-white px-2 py-1 border rounded text-primary fw-bold"><?= htmlspecialchars($transaction_uuid) ?></code>
                                                    <button type="button" class="btn btn-sm btn-link p-0 ms-1 text-decoration-none" onclick="navigator.clipboard.writeText('<?= htmlspecialchars($transaction_uuid) ?>'); alert('Remarks copied to clipboard!');">
                                                        <i class="bi bi-copy"></i> Copy
                                                    </button>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-start gap-3 p-2 rounded-3 bg-light border">
                                            <span class="badge bg-primary rounded-circle p-2 px-2 fs-6">3</span>
                                            <div>
                                                <strong class="d-block text-dark small">Enter eSewa Transaction ID Below</strong>
                                                <span class="text-muted small">Submit your transaction ID from the eSewa receipt to unlock your download.</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- QR Confirmation Form -->
                                    <form method="POST" action="<?= BASE_URL ?>/payment.php?resource_id=<?= $resource_id ?>" enctype="multipart/form-data">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="resource_id" value="<?= $resource_id ?>">

                                        <div class="mb-3">
                                            <label for="transaction_code" class="form-label fw-bold small text-dark">
                                                eSewa Transaction ID / Reference Code <span class="text-danger">*</span>
                                            </label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light"><i class="bi bi-hash"></i></span>
                                                <input type="text" class="form-control" id="transaction_code" name="transaction_code" placeholder="e.g. 17294829 or Ref ID from eSewa" required>
                                            </div>
                                            <div class="form-text small">Found in your eSewa payment success screen / transaction history.</div>
                                        </div>

                                        <div class="mb-4">
                                            <label for="payment_proof" class="form-label fw-bold small text-dark">
                                                Upload Payment Screenshot <span class="text-muted fw-normal">(Optional)</span>
                                            </label>
                                            <input type="file" class="form-control form-control-sm" id="payment_proof" name="payment_proof" accept="image/*,.pdf">
                                            <div class="form-text small">Attaching your screenshot helps our admin verify your order faster.</div>
                                        </div>

                                        <div class="d-grid gap-2">
                                            <button type="submit" name="action_type" value="submit_verification" class="btn btn-success btn-lg fw-bold shadow-sm py-3">
                                                <i class="bi bi-shield-check me-2"></i>Submit Payment for Verification
                                            </button>

                                            <!-- Instant Demo Button for Client Testing -->

                                        </div>
                                    </form>

                                </div>
                            </div>
                        </div>

                        <!-- TAB 2: eSewa ePay V2 Online Gateway -->
                        <div class="tab-pane fade" id="epay-pane" role="tabpanel">
                            <div class="text-center py-4" style="max-width: 500px; margin: 0 auto;">
                                <div class="bg-light p-3 rounded-circle d-inline-block mb-3 text-success fs-1">
                                    <i class="bi bi-shield-lock"></i>
                                </div>
                                <h5 class="fw-bold text-dark mb-2">eSewa ePay V2 Merchant Gateway</h5>
                                <p class="text-muted small mb-4">
                                    You will be redirected to the official eSewa sandbox environment. Login with test credentials to complete the transaction automatically.
                                </p>

                                <form action="<?= ESEWA_URL ?>" method="POST" id="esewaEpayForm">
                                    <input type="hidden" name="amount" value="<?= $amount ?>">
                                    <input type="hidden" name="tax_amount" value="0">
                                    <input type="hidden" name="total_amount" value="<?= $total_amount ?>">
                                    <input type="hidden" name="transaction_uuid" value="<?= $transaction_uuid ?>">
                                    <input type="hidden" name="product_code" value="<?= ESEWA_PRODUCT_CODE ?>">
                                    <input type="hidden" name="product_service_charge" value="0">
                                    <input type="hidden" name="product_delivery_charge" value="0">
                                    <input type="hidden" name="success_url" value="<?= ESEWA_SUCCESS_URL ?>">
                                    <input type="hidden" name="failure_url" value="<?= ESEWA_FAILURE_URL ?>">
                                    <input type="hidden" name="signed_field_names" value="total_amount,transaction_uuid,product_code">
                                    <input type="hidden" name="signature" value="<?= $signature ?>">

                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-esewa btn-lg fw-bold shadow-sm py-3">
                                            <i class="bi bi-wallet2 fs-5 me-2"></i> Proceed to eSewa Online Gateway
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Guarantee & Safety Notes -->
            <div class="row g-3 text-center">
                <div class="col-md-4">
                    <div class="p-3 bg-white rounded-3 shadow-sm border">
                        <i class="bi bi-shield-check text-success fs-4 d-block mb-1"></i>
                        <strong class="d-block text-dark small">Verified Creator</strong>
                        <span class="text-muted small">Direct transfer to registered educator</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 bg-white rounded-3 shadow-sm border">
                        <i class="bi bi-cloud-arrow-down text-primary fs-4 d-block mb-1"></i>
                        <strong class="d-block text-dark small">Instant Delivery</strong>
                        <span class="text-muted small">Download unlocked as soon as verified</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 bg-white rounded-3 shadow-sm border">
                        <i class="bi bi-headset text-warning fs-4 d-block mb-1"></i>
                        <strong class="d-block text-dark small">Student Support</strong>
                        <span class="text-muted small">Assistance available on 9805149182</span>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
