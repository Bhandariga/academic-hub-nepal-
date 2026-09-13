<?php
/**
 * AcademicHub Nepal - Order Creation & eSewa ePay V2 UAT Gateway Preparation
 * File: payment.php
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth_check.php';

// Force login before payment
$resource_id = isset($_GET['resource_id']) ? (int)$_GET['resource_id'] : 0;
if ($resource_id <= 0) {
    set_flash_message('danger', 'Please select an academic resource to purchase.');
    header('Location: ' . BASE_URL . '/resources.php');
    exit;
}

require_login(BASE_URL . '/payment.php?resource_id=' . $resource_id);

$user_id = $_SESSION['user_id'];

try {
    // Fetch resource details
    $stmt = $pdo->prepare("SELECT * FROM resources WHERE id = :id");
    $stmt->execute([':id' => $resource_id]);
    $resource = $stmt->fetch();

    if (!$resource) {
        set_flash_message('danger', 'The selected resource is invalid or has been archived.');
        header('Location: ' . BASE_URL . '/resources.php');
        exit;
    }

    // Check if user has already purchased this resource
    $stmt_check = $pdo->prepare("SELECT id FROM orders WHERE user_id = :uid AND resource_id = :rid AND payment_status = 'PAID' LIMIT 1");
    $stmt_check->execute([':uid' => $user_id, ':rid' => $resource['id']]);
    if ($stmt_check->fetch()) {
        set_flash_message('info', 'You already own this resource! You can download it directly from your dashboard.');
        header('Location: ' . BASE_URL . '/dashboard.php#downloads');
        exit;
    }

    // Generate unique transaction UUID
    $transaction_uuid = 'ORD-' . time() . '-' . $user_id . '-' . $resource['id'];
    $amount = number_format((float)$resource['price'], 2, '.', '');
    $tax_amount = '0.00';
    $total_amount = $amount;

    // Create Pending Order in database
    $insert_order = $pdo->prepare("
        INSERT INTO orders (user_id, resource_id, amount, transaction_uuid, payment_status, created_at)
        VALUES (:user_id, :resource_id, :amount, :transaction_uuid, 'PENDING', NOW())
    ");
    $insert_order->execute([
        ':user_id' => $user_id,
        ':resource_id' => $resource['id'],
        ':amount' => $total_amount,
        ':transaction_uuid' => $transaction_uuid
    ]);

    $order_id = $pdo->lastInsertId();

    // Prepare eSewa signature
    // Signed message: "total_amount={total_amount},transaction_uuid={transaction_uuid},product_code={product_code}"
    $signature = generate_esewa_signature($total_amount, $transaction_uuid, ESEWA_PRODUCT_CODE, ESEWA_SECRET_KEY);

} catch (Exception $e) {
    die("Payment initialization failed: " . $e->getMessage());
}

$page_title = "Checkout & eSewa Payment | AcademicHub Nepal";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                <div class="p-4 text-white" style="background: linear-gradient(135deg, #0f2942 0%, #1e4976 100%);">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="badge bg-info text-dark mb-1">eSewa ePay V2 Sandbox</span>
                            <h3 class="fw-bold mb-0 text-white">Order Checkout</h3>
                        </div>
                        <div class="text-end">
                            <span class="small text-white-50">Transaction Ref:</span>
                            <div class="fw-bold font-monospace text-warning small"><?= htmlspecialchars($transaction_uuid) ?></div>
                        </div>
                    </div>
                </div>

                <div class="card-body p-4 p-md-5 bg-white">
                    <!-- Order Items Summary Table -->
                    <h5 class="fw-bold mb-3 text-dark">Review Order Details</h5>
                    <div class="table-responsive mb-4">
                        <table class="table align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Resource</th>
                                    <th>Classification</th>
                                    <th class="text-end">Price</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-primary"><?= htmlspecialchars($resource['title']) ?></div>
                                        <div class="small text-muted"><?= $resource['pages'] ?> Pages &bull; Format: <?= htmlspecialchars($resource['file_format']) ?></div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border"><?= htmlspecialchars($resource['campus']) ?></span>
                                        <div class="small text-muted"><?= htmlspecialchars($resource['program']) ?> - <?= htmlspecialchars($resource['subject']) ?></div>
                                    </td>
                                    <td class="text-end fw-bold fs-5">
                                        <?= format_npr($total_amount) ?>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="2" class="text-end">Subtotal:</th>
                                    <td class="text-end"><?= format_npr($total_amount) ?></td>
                                </tr>
                                <tr>
                                    <th colspan="2" class="text-end">Tax / Service Charge:</th>
                                    <td class="text-end text-success">NPR 0 (Free)</td>
                                </tr>
                                <tr class="table-light">
                                    <th colspan="2" class="text-end fs-5 text-primary">Total Payable:</th>
                                    <th class="text-end fs-4 text-primary"><?= format_npr($total_amount) ?></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- Payment Gateway Selector / Confirmation -->
                    <div class="border rounded-3 p-3 bg-light mb-4">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-3">
                                <div class="bg-white p-2 rounded border" style="width: 70px; text-align: center;">
                                    <span class="fw-bold text-success" style="font-size: 1.1rem;">eSewa</span>
                                </div>
                                <div>
                                    <h6 class="fw-bold mb-0">eSewa Mobile Wallet / Internet Banking</h6>
                                    <span class="small text-muted">Official eSewa ePay V2 Sandbox Integration (UAT Demo Mode)</span>
                                </div>
                            </div>
                            <span class="badge bg-success py-2 px-3"><i class="bi bi-check2-circle me-1"></i> Active</span>
                        </div>
                    </div>

                    <!-- Official eSewa ePay V2 Form -->
                    <form action="<?= ESEWA_URL ?>" method="POST" id="esewaForm">
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

                        <div class="d-grid gap-3">
                            <button type="submit" class="btn btn-esewa btn-lg fw-bold shadow-sm">
                                <i class="bi bi-wallet2 fs-5 me-2"></i> Pay <?= format_npr($total_amount) ?> via eSewa UAT
                            </button>
                        </div>
                    </form>

                    <!-- Client Demo Simulation Option (for offline testing or quick evaluation) -->
                    <div class="mt-4 pt-3 border-top text-center">
                        <span class="small text-muted d-block mb-2">💡 Client Demo Fast-Track (Instant Simulation):</span>
                        <a href="<?= BASE_URL ?>/payment-success.php?demo_simulate=1&transaction_uuid=<?= urlencode($transaction_uuid) ?>&total_amount=<?= urlencode($total_amount) ?>" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-lightning-charge-fill text-warning me-1"></i> Simulate Instant Successful Payment (Demo Mode)
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
