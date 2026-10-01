<?php
/**
 * AcademicHub Nepal - Certified Payment Receipt & Tax Invoice
 * File: receipt.php
 * 
 * Generates an official, verifiable student tax invoice and payment voucher.
 * Accessible by: Order Owner (Student) and Authorized Administrators (Super Admin / Admin).
 * Features:
 * - 1-Click Print & Save as PDF
 * - Official PAN/VAT Header & Unique Invoice Number
 * - eSewa Transaction Reference & Audit Trail
 * - Security Verified Stamp & Direct Unlocked Note Access
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth_check.php';

// Authentication Check: Must be logged in
if (!is_logged_in()) {
    set_flash_message('danger', 'Please log in to view and download your payment receipts.');
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0);
$uuid = isset($_GET['uuid']) ? trim($_GET['uuid']) : '';

if ($order_id <= 0 && empty($uuid)) {
    set_flash_message('warning', 'Please provide a valid Order ID or Transaction UUID.');
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}

try {
    // Fetch comprehensive order information
    if ($order_id > 0) {
        $stmt = $pdo->prepare("
            SELECT o.*, 
                   u.name AS student_name, u.email AS student_email, u.phone AS student_phone, u.campus AS student_campus,
                   r.title AS resource_title, r.subject, r.campus AS resource_campus, r.program, 
                   r.document_type, r.pages, r.file_format, r.price AS original_price, r.file_path
            FROM orders o
            JOIN users u ON o.user_id = u.id
            JOIN resources r ON o.resource_id = r.id
            WHERE o.id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $order_id]);
    } else {
        $stmt = $pdo->prepare("
            SELECT o.*, 
                   u.name AS student_name, u.email AS student_email, u.phone AS student_phone, u.campus AS student_campus,
                   r.title AS resource_title, r.subject, r.campus AS resource_campus, r.program, 
                   r.document_type, r.pages, r.file_format, r.price AS original_price, r.file_path
            FROM orders o
            JOIN users u ON o.user_id = u.id
            JOIN resources r ON o.resource_id = r.id
            WHERE o.transaction_uuid = :uuid
            LIMIT 1
        ");
        $stmt->execute([':uuid' => $uuid]);
    }

    $order = $stmt->fetch();

    if (!$order) {
        set_flash_message('danger', 'Order record not found or resource has been permanently archived.');
        header('Location: ' . BASE_URL . '/dashboard.php');
        exit;
    }

    // Authorization Verification: User must own this order, or must have admin privileges
    if (!is_admin() && (int)$order['user_id'] !== $user_id) {
        set_flash_message('danger', 'Unauthorized access. You do not have permission to view another student’s payment receipt.');
        header('Location: ' . BASE_URL . '/dashboard.php');
        exit;
    }

} catch (PDOException $e) {
    error_log("Receipt DB error: " . $e->getMessage());
    set_flash_message('danger', 'A database error occurred while generating your payment receipt.');
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}

// Convert amount to English words for official receipt
function npr_to_words_chunk($num, $words) {
    if ($num <= 20) return $words[$num];
    if ($num < 100) {
        $tens = (int)($num / 10) * 10;
        $units = $num % 10;
        return $words[$tens] . ($units ? ' ' . $words[$units] : '');
    }
    return '';
}

function npr_amount_to_words($number) {
    $words = array(
        0 => 'Zero', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four',
        5 => 'Five', 6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine',
        10 => 'Ten', 11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen',
        14 => 'Fourteen', 15 => 'Fifteen', 16 => 'Sixteen', 17 => 'Seventeen',
        18 => 'Eighteen', 19 => 'Nineteen', 20 => 'Twenty', 30 => 'Thirty',
        40 => 'Forty', 50 => 'Fifty', 60 => 'Sixty', 70 => 'Seventy',
        80 => 'Eighty', 90 => 'Ninety'
    );
    $num = (int)$number;
    if ($num === 0) return 'Zero Rupees Only';
    
    $result = '';
    if ($num >= 10000000) {
        $crore = (int)($num / 10000000);
        $result .= npr_to_words_chunk($crore, $words) . ' Crore ';
        $num %= 10000000;
    }
    if ($num >= 100000) {
        $lakh = (int)($num / 100000);
        $result .= npr_to_words_chunk($lakh, $words) . ' Lakh ';
        $num %= 100000;
    }
    if ($num >= 1000) {
        $thousand = (int)($num / 1000);
        $result .= npr_to_words_chunk($thousand, $words) . ' Thousand ';
        $num %= 1000;
    }
    if ($num >= 100) {
        $hundred = (int)($num / 100);
        $result .= $words[$hundred] . ' Hundred ';
        $num %= 100;
    }
    if ($num > 0) {
        if ($result !== '') $result .= 'and ';
        $result .= npr_to_words_chunk($num, $words) . ' ';
    }
    return trim($result) . ' Rupees Only';
}

$invoice_number = 'AH-INV-' . date('Y', strtotime($order['created_at'])) . '-' . str_pad($order['id'], 5, '0', STR_PAD_LEFT);
$receipt_date = !empty($order['verified_at']) ? date('F d, Y - h:i A', strtotime($order['verified_at'])) : date('F d, Y - h:i A', strtotime($order['created_at']));
$is_paid = ($order['payment_status'] === 'PAID');
$page_title = "Payment Receipt " . $invoice_number . " - AcademicHub Nepal";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?></title>
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🎓</text></svg>">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-hub: #0f3d64;
            --primary-dark: #09253d;
            --accent-green: #10b981;
            --esewa-green: #60bb46;
            --border-color: #e2e8f0;
            --text-main: #1e293b;
            --text-muted: #64748b;
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f1f5f9;
            color: var(--text-main);
            padding: 30px 15px;
            font-size: 0.95rem;
        }

        .receipt-card {
            max-width: 860px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 15px 35px -5px rgba(0, 0, 0, 0.08), 0 5px 15px rgba(0, 0, 0, 0.03);
            border: 1px solid var(--border-color);
            overflow: hidden;
            position: relative;
        }

        .receipt-header-banner {
            background: linear-gradient(135deg, var(--primary-hub) 0%, var(--primary-dark) 100%);
            color: #ffffff;
            padding: 32px 40px;
            position: relative;
        }

        .receipt-body {
            padding: 40px;
        }

        .font-mono {
            font-family: 'JetBrains Mono', monospace;
        }

        .info-label {
            font-size: 0.72rem;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 0.08em;
            color: var(--text-muted);
            margin-bottom: 4px;
        }

        .info-value {
            font-size: 0.96rem;
            font-weight: 600;
            color: var(--text-main);
        }

        .badge-verified-seal {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background-color: #ecfdf5;
            color: #065f46;
            border: 1.5px solid #10b981;
            padding: 6px 14px;
            border-radius: 9999px;
            font-weight: 700;
            font-size: 0.82rem;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .badge-pending-seal {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background-color: #fffbeb;
            color: #92400e;
            border: 1.5px solid #f59e0b;
            padding: 6px 14px;
            border-radius: 9999px;
            font-weight: 700;
            font-size: 0.82rem;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .receipt-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin: 24px 0;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid var(--border-color);
        }

        .receipt-table th {
            background-color: #f8fafc;
            color: #475569;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            padding: 14px 18px;
            border-bottom: 1px solid var(--border-color);
        }

        .receipt-table td {
            padding: 16px 18px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }

        .receipt-table tr:last-child td {
            border-bottom: none;
        }

        .receipt-total-box {
            background: #f8fafc;
            border: 1px solid var(--border-color);
            border-radius: 14px;
            padding: 20px 24px;
        }

        .stamp-container {
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .stamp-badge {
            width: 150px;
            height: 150px;
            border: 3px dashed #10b981;
            border-radius: 50%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: #059669;
            padding: 10px;
            transform: rotate(-10deg);
            opacity: 0.88;
            user-select: none;
        }

        .action-toolbar {
            max-width: 860px;
            margin: 0 auto 20px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }

        /* Guaranteed 1-Page A4 Print & PDF Styles */
        @media print {
            @page {
                size: A4 portrait;
                margin: 6mm 8mm;
            }

            html, body {
                background: #ffffff !important;
                padding: 0 !important;
                margin: 0 !important;
                font-size: 8.5pt !important;
                line-height: 1.25 !important;
                height: auto !important;
            }

            .action-toolbar, .no-print {
                display: none !important;
            }

            .receipt-card {
                box-shadow: none !important;
                border: 1.5px solid #0f3d64 !important;
                border-radius: 8px !important;
                max-width: 100% !important;
                width: 100% !important;
                margin: 0 auto !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
                page-break-after: avoid !important;
                break-after: avoid !important;
            }

            .receipt-header-banner {
                background: #0f3d64 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                color: #ffffff !important;
                padding: 12px 18px !important;
            }

            .receipt-header-banner h4 {
                font-size: 1.15rem !important;
                margin-bottom: 2px !important;
            }

            .receipt-header-banner .badge {
                font-size: 0.72rem !important;
                padding: 4px 10px !important;
                margin-bottom: 3px !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .receipt-header-banner .fs-5 {
                font-size: 1.05rem !important;
            }

            .receipt-header-banner .text-white-50 {
                font-size: 0.7rem !important;
                line-height: 1.2 !important;
            }

            .receipt-body {
                padding: 12px 18px !important;
            }

            .receipt-body .row.g-4 {
                --bs-gutter-x: 0.75rem !important;
                --bs-gutter-y: 0.75rem !important;
            }

            .receipt-body .mb-4 {
                margin-bottom: 8px !important;
            }

            .receipt-body .p-3 {
                padding: 8px 12px !important;
            }

            .receipt-body .fs-5 {
                font-size: 0.95rem !important;
            }

            .info-label {
                font-size: 0.65rem !important;
                margin-bottom: 2px !important;
            }

            .info-value {
                font-size: 0.88rem !important;
            }

            .receipt-table {
                margin: 8px 0 !important;
            }

            .receipt-table th {
                padding: 6px 10px !important;
                font-size: 0.68rem !important;
                background-color: #f1f5f9 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .receipt-table td {
                padding: 6px 10px !important;
                font-size: 0.82rem !important;
            }

            .receipt-total-box {
                padding: 8px 12px !important;
                border-radius: 8px !important;
                background-color: #f8fafc !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .receipt-total-box .fs-5 {
                font-size: 1.05rem !important;
            }

            .stamp-badge {
                width: 90px !important;
                height: 90px !important;
                padding: 4px !important;
                border-width: 2px !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .stamp-badge i {
                font-size: 1.15rem !important;
                margin-bottom: 0 !important;
            }

            .stamp-badge strong {
                font-size: 0.55rem !important;
                line-height: 1.05 !important;
            }

            .stamp-badge span {
                font-size: 0.52rem !important;
                margin-top: 1px !important;
            }

            .border-top {
                border-top-width: 1px !important;
            }

            .pt-3 {
                padding-top: 6px !important;
            }

            .mt-4 {
                margin-top: 8px !important;
            }

            .badge-verified-seal, .badge-pending-seal {
                font-size: 0.7rem !important;
                padding: 3px 8px !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .disclaimer-text {
                font-size: 0.65rem !important;
                margin-top: 6px !important;
                padding-top: 4px !important;
            }
        }
    </style>
<link href="<?= BASE_URL ?>/assets/css/design-system.css?v=3" rel="stylesheet">
</head>
<body>

    <!-- Action Toolbar (Hidden during Print/Save as PDF) -->
    <div class="action-toolbar no-print">
        <div class="d-flex align-items-center gap-2">
            <a href="<?= BASE_URL ?>/dashboard.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3 shadow-sm bg-white">
                <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
            </a>
            <?php if (is_admin()): ?>
                <a href="<?= BASE_URL ?>/admin/manage-orders.php" class="btn btn-light btn-sm border rounded-pill px-3 shadow-sm">
                    <i class="bi bi-shield-lock me-1"></i> Admin Orders
                </a>
            <?php endif; ?>
        </div>

        <div class="d-flex align-items-center gap-2">
            <?php if ($is_paid): ?>
                <a href="<?= BASE_URL ?>/download.php?order_id=<?= $order['id'] ?>" class="btn btn-success btn-sm rounded-pill px-3 shadow-sm fw-semibold">
                    <i class="bi bi-download me-1"></i> Download Unlocked Notes
                </a>
            <?php endif; ?>

            <button type="button" class="btn btn-primary btn-sm rounded-pill px-4 shadow-sm fw-semibold" onclick="window.print()">
                <i class="bi bi-printer-fill me-1"></i> Print / Save as PDF
            </button>
        </div>
    </div>

    <!-- Official Payment Receipt Card -->
    <div class="receipt-card">
        <!-- Header Banner -->
        <div class="receipt-header-banner d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <div class="bg-white rounded-3 p-1 d-inline-flex align-items-center justify-content-center shadow-sm" style="width: 38px; height: 38px;">
                        <span style="font-size: 1.4rem;">🎓</span>
                    </div>
                    <div>
                        <h4 class="fw-bold mb-0 text-white" style="letter-spacing: -0.02em;">ACADEMIC HUB NEPAL</h4>
                        <div class="text-white-50 small" style="font-size: 0.72rem; letter-spacing: 0.05em;">ACADEMIC RESEARCH &amp; NOTES REPOSITORY</div>
                    </div>
                </div>
                <div class="text-white-50 small mt-2" style="font-size: 0.8rem; line-height: 1.4;">
                    Kathmandu, Bagmati Province, Nepal<br>
                    <strong>PAN Reg No:</strong> 609823412 &bull; <strong>Support:</strong> support@academichub.com.np
                </div>
            </div>

            <div class="text-md-end">
                <div class="badge bg-white text-dark font-mono px-3 py-2 rounded-pill fw-bold mb-2 shadow-sm" style="font-size: 0.85rem;">
                    OFFICIAL TAX INVOICE
                </div>
                <div class="text-white fw-bold font-mono fs-5 mb-0"><?= htmlspecialchars($invoice_number) ?></div>
                <div class="text-white-50 small font-mono" style="font-size: 0.75rem;">Generated: <?= date('Y-m-d H:i:s') ?></div>
            </div>
        </div>

        <!-- Receipt Body -->
        <div class="receipt-body">

            <?php if (!$is_paid): ?>
                <!-- Pending Notice if not yet verified -->
                <div class="alert alert-warning border-warning rounded-4 p-3 mb-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-3">
                        <i class="bi bi-hourglass-split fs-3 text-warning"></i>
                        <div>
                            <strong class="d-block text-dark">Payment Verification In Progress</strong>
                            <span class="small text-muted">This transaction is currently under review by our administration. Once verified, your notes will unlock and this receipt will receive certified status.</span>
                        </div>
                    </div>
                    <span class="badge-pending-seal">
                        <i class="bi bi-clock-history"></i> Verification Pending
                    </span>
                </div>
            <?php endif; ?>

            <!-- Row 1: Student Details & Invoice Meta -->
            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded-4 border h-100">
                        <div class="info-label text-primary"><i class="bi bi-person-check-fill me-1"></i> Billed To (Student Information)</div>
                        <div class="info-value fs-5 text-dark"><?= htmlspecialchars($order['student_name']) ?></div>
                        <div class="text-muted small mt-1 font-mono">
                            <div><i class="bi bi-envelope me-1"></i><?= htmlspecialchars($order['student_email']) ?></div>
                            <?php if (!empty($order['student_phone'])): ?>
                                <div><i class="bi bi-telephone me-1"></i><?= htmlspecialchars($order['student_phone']) ?></div>
                            <?php endif; ?>
                            <?php if (!empty($order['student_campus'])): ?>
                                <div class="text-truncate"><i class="bi bi-building me-1"></i><?= htmlspecialchars($order['student_campus']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="p-3 bg-light rounded-4 border h-100">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div class="info-label text-primary"><i class="bi bi-receipt me-1"></i> Payment Transaction Audit</div>
                            <?php if ($is_paid): ?>
                                <span class="badge-verified-seal">
                                    <i class="bi bi-patch-check-fill"></i> Paid &amp; Unlocked
                                </span>
                            <?php else: ?>
                                <span class="badge-pending-seal">
                                    <i class="bi bi-clock"></i> <?= htmlspecialchars($order['payment_status']) ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="row g-2 small font-mono">
                            <div class="col-5 text-muted">Payment Method:</div>
                            <div class="col-7 fw-bold text-dark">
                                <span class="text-success"><i class="bi bi-qr-code me-1"></i><?= htmlspecialchars($order['payment_method']) ?></span>
                            </div>

                            <div class="col-5 text-muted">Txn Reference:</div>
                            <div class="col-7 fw-bold text-dark font-monospace">
                                <?= htmlspecialchars($order['transaction_code'] ?? 'N/A') ?>
                            </div>

                            <div class="col-5 text-muted">Order UUID:</div>
                            <div class="col-7 text-truncate text-muted" title="<?= htmlspecialchars($order['transaction_uuid']) ?>">
                                <?= htmlspecialchars(substr($order['transaction_uuid'], 0, 16)) ?>...
                            </div>

                            <div class="col-5 text-muted">Invoice Date:</div>
                            <div class="col-7 text-dark"><?= $receipt_date ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Itemized Table of Unlocked Resources -->
            <table class="receipt-table">
                <thead>
                    <tr>
                        <th style="width: 8%;">S.N.</th>
                        <th style="width: 52%;">Description of Academic Resource</th>
                        <th style="width: 15%;" class="text-center">Format / Pgs</th>
                        <th style="width: 25%;" class="text-end">Total Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="font-mono text-muted">01</td>
                        <td>
                            <div class="fw-bold text-dark mb-1">
                                <?= htmlspecialchars($order['resource_title']) ?>
                            </div>
                            <div class="small text-muted d-flex flex-wrap gap-2">
                                <span class="badge bg-light text-dark border"><?= htmlspecialchars($order['program']) ?></span>
                                <span class="badge bg-light text-muted border"><?= htmlspecialchars($order['subject']) ?></span>
                                <span class="badge bg-light text-muted border"><?= htmlspecialchars($order['document_type']) ?></span>
                            </div>
                            <div class="small text-muted mt-1 fst-italic">
                                Campus: <?= htmlspecialchars($order['resource_campus']) ?> &bull; Single Student Perpetual Academic License
                            </div>
                        </td>
                        <td class="text-center font-mono small">
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle"><?= htmlspecialchars($order['file_format']) ?></span>
                            <div class="text-muted mt-1" style="font-size:0.75rem;"><?= (int)$order['pages'] ?> Pages</div>
                        </td>
                        <td class="text-end font-mono fw-bold fs-6 text-dark">
                            <?= format_npr($order['amount']) ?>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Financial Calculation & Words Breakdown -->
            <div class="row g-4 align-items-center mb-4">
                <div class="col-md-7">
                    <div class="p-3 border rounded-4 bg-light">
                        <div class="info-label text-muted">Amount in Words</div>
                        <div class="fw-bold text-dark font-italic" style="font-size: 0.95rem;">
                            <?= npr_amount_to_words($order['amount']) ?>
                        </div>
                        <hr class="my-2 border-secondary-subtle">
                        <div class="small text-muted">
                            <i class="bi bi-info-circle me-1 text-primary"></i>
                            Non-refundable academic digital asset. Digital VAT/Tax exempt under Section 11 of Nepal Education &amp; Academic Digital Resource Provisions.
                        </div>
                    </div>
                </div>

                <div class="col-md-5">
                    <div class="receipt-total-box">
                        <div class="d-flex justify-content-between mb-2 small text-muted font-mono">
                            <span>Subtotal:</span>
                            <span class="text-dark fw-bold"><?= format_npr($order['amount']) ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2 small text-muted font-mono">
                            <span>Educational Tax (0%):</span>
                            <span class="text-dark">NPR 0.00</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2 small text-muted font-mono">
                            <span>Service Fee:</span>
                            <span class="text-dark">NPR 0.00</span>
                        </div>
                        <hr class="my-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-bold text-dark fs-6">Grand Total:</span>
                            <span class="fw-bold text-success fs-5 font-mono"><?= format_npr($order['amount']) ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Verification Stamp & Signatures -->
            <div class="row align-items-center pt-3 border-top mt-4">
                <div class="col-sm-5 text-center text-sm-start mb-3 mb-sm-0">
                    <?php if ($is_paid): ?>
                        <div class="stamp-badge mx-auto mx-sm-0">
                            <i class="bi bi-patch-check-fill fs-3 mb-1"></i>
                            <strong style="font-size: 0.72rem; letter-spacing: 0.05em; line-height: 1.1;">ACADEMIC HUB<br>VERIFIED PAYMENT</strong>
                            <span style="font-size: 0.65rem;" class="mt-1 font-mono"><?= date('Y-m-d', strtotime($order['verified_at'] ?? $order['created_at'])) ?></span>
                        </div>
                    <?php else: ?>
                        <div class="p-3 border border-warning rounded-4 bg-warning bg-opacity-10 text-warning text-center">
                            <i class="bi bi-shield-exclamation fs-3"></i>
                            <div class="small fw-bold mt-1">Pending Approval</div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="col-sm-7 text-center text-sm-end">
                    <div class="d-inline-block text-center" style="min-width: 200px;">
                        <div class="font-mono text-primary fw-bold signature-script" style="font-family: 'Brush Script MT', cursive, sans-serif; font-size: 1.5rem; transform: rotate(-3deg);">
                            AcademicHub Audit
                        </div>
                        <div style="border-top: 1.5px solid #0f3d64; margin-top: 2px; padding-top: 3px;">
                            <div class="fw-bold small text-dark">Authorized Signatory</div>
                            <div class="text-muted" style="font-size: 0.72rem;">Finance &amp; Verification Department</div>
                            <div class="text-muted font-mono" style="font-size: 0.68rem;">AcademicHub Nepal Pvt. Ltd.</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Disclaimer -->
            <div class="mt-4 pt-3 border-top text-center text-muted small disclaimer-text" style="font-size: 0.75rem;">
                This document is a certified digital tax invoice and proof of payment generated by AcademicHub Nepal. 
                Keep this document for your personal academic expense records.
            </div>

        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
