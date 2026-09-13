<?php
/**
 * AcademicHub Nepal - eSewa Payment Failed / Cancelled
 * File: payment-failed.php
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth_check.php';

$page_title = "Payment Failed | AcademicHub Nepal";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 text-center p-5 bg-white">
                <div class="text-danger display-1 mb-3">
                    <i class="bi bi-x-circle-fill"></i>
                </div>
                <h2 class="fw-bold text-dark mb-2">Payment Failed or Cancelled</h2>
                <p class="text-muted mb-4">
                    Your eSewa transaction could not be completed or was cancelled. No amount was charged to your wallet.
                </p>

                <div class="bg-light p-3 rounded-3 border text-start small text-muted mb-4">
                    <strong class="text-dark d-block mb-1"><i class="bi bi-lightbulb-fill text-warning me-1"></i> Common Reasons:</strong>
                    <ul class="mb-0 ps-3">
                        <li>You cancelled the checkout on the eSewa portal.</li>
                        <li>Temporary connection timeout with the payment gateway.</li>
                        <li>Insufficient balance in test wallet.</li>
                    </ul>
                </div>

                <div class="d-flex flex-wrap justify-content-center gap-3">
                    <a href="<?= BASE_URL ?>/resources.php" class="btn btn-hub-primary px-4 py-2">
                        <i class="bi bi-arrow-repeat me-1"></i> Try Again / Browse Resources
                    </a>
                    <a href="<?= BASE_URL ?>/dashboard.php" class="btn btn-outline-secondary px-4 py-2">
                        <i class="bi bi-grid-fill me-1"></i> Student Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
