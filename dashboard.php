<?php
/**
 * AcademicHub Nepal - Student Dashboard
 * File: dashboard.php
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth_check.php';
require_once __DIR__ . '/config/notifications.php';

require_login();

$user_id = $_SESSION['user_id'];

// Handle POST actions: Avatar upload, Avatar removal, and Review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'upload_avatar') {
        if (!verify_csrf_token()) {
            header('Location: ' . BASE_URL . '/dashboard.php#profile-pane');
            exit;
        }

        if (!isset($_FILES['profile_picture']) || $_FILES['profile_picture']['error'] !== UPLOAD_ERR_OK) {
            set_flash_message('danger', 'Please select a valid image file to upload.');
            header('Location: ' . BASE_URL . '/dashboard.php#profile-pane');
            exit;
        }

        $file = $_FILES['profile_picture'];
        $maxSize = 5 * 1024 * 1024; // 5 MB

        if ($file['size'] > $maxSize) {
            set_flash_message('danger', 'Image size exceeds maximum limit of 5MB.');
            header('Location: ' . BASE_URL . '/dashboard.php#profile-pane');
            exit;
        }

        $targetDir = __DIR__ . '/uploads/avatars/';
        if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);
        try {
            require_once __DIR__ . '/config/uploads.php';
            $filename = store_validated_upload($file, $targetDir, true);
            // Unlink old avatar if exists
            $stmt_old = $pdo->prepare("SELECT profile_image FROM users WHERE id = :id");
            $stmt_old->execute([':id' => $user_id]);
            $oldImg = basename((string)$stmt_old->fetchColumn());
            if (!empty($oldImg) && file_exists($targetDir . $oldImg)) {
                @unlink($targetDir . $oldImg);
            }

            // Update user table
            $upd = $pdo->prepare("UPDATE users SET profile_image = :img WHERE id = :id");
            $upd->execute([':img' => $filename, ':id' => $user_id]);

            $_SESSION['user_avatar'] = $filename;
            set_flash_message('success', 'Profile picture updated successfully!');
        } catch (RuntimeException $e) {
            set_flash_message('danger', safe_error_message($e));
        }

        header('Location: ' . BASE_URL . '/dashboard.php#profile-pane');
        exit;
    }

    if ($action === 'remove_avatar') {
        if (!verify_csrf_token()) {
            header('Location: ' . BASE_URL . '/dashboard.php#profile-pane');
            exit;
        }

        $targetDir = __DIR__ . '/uploads/avatars/';
        $stmt_old = $pdo->prepare("SELECT profile_image FROM users WHERE id = :id");
        $stmt_old->execute([':id' => $user_id]);
        $oldImg = basename((string)$stmt_old->fetchColumn());
        if (!empty($oldImg) && file_exists($targetDir . $oldImg)) {
            @unlink($targetDir . $oldImg);
        }

        $upd = $pdo->prepare("UPDATE users SET profile_image = NULL WHERE id = :id");
        $upd->execute([':id' => $user_id]);

        $_SESSION['user_avatar'] = null;
        set_flash_message('info', 'Profile picture removed.');
        header('Location: ' . BASE_URL . '/dashboard.php#profile-pane');
        exit;
    }

    if ($action === 'save_review') {
        if (!verify_csrf_token()) {
            header('Location: ' . BASE_URL . '/dashboard.php#review-pane');
            exit;
        }

        $rating = max(1, min(5, (int)($_POST['rating'] ?? 5)));
        $program = trim(sanitize($_POST['program'] ?? ''));
        $review_text = trim(sanitize($_POST['review_text'] ?? ''));

        if (strlen($review_text) < 15) {
            set_flash_message('danger', 'Please provide a meaningful review of at least 15 characters.');
            header('Location: ' . BASE_URL . '/dashboard.php#review-pane');
            exit;
        }

        // Check if student already has a review
        $stmt_check = $pdo->prepare("SELECT id FROM reviews WHERE user_id = :uid LIMIT 1");
        $stmt_check->execute([':uid' => $user_id]);
        $existing = $stmt_check->fetch();

        if ($existing) {
            $stmt_upd = $pdo->prepare("
                UPDATE reviews 
                SET rating = :rating, program = :prog, review_text = :text, status = 'approved', created_at = NOW() 
                WHERE user_id = :uid
            ");
            $stmt_upd->execute([
                ':rating' => $rating,
                ':prog'   => $program,
                ':text'   => $review_text,
                ':uid'    => $user_id
            ]);
            set_flash_message('success', 'Your review has been updated and is live on the AcademicHub website!');
        } else {
            $stmt_ins = $pdo->prepare("
                INSERT INTO reviews (user_id, rating, program, review_text, status, created_at)
                VALUES (:uid, :rating, :prog, :text, 'approved', NOW())
            ");
            $stmt_ins->execute([
                ':uid'    => $user_id,
                ':rating' => $rating,
                ':prog'   => $program,
                ':text'   => $review_text
            ]);
            set_flash_message('success', 'Thank you! Your verified student review is now featured on the homepage.');
        }

        header('Location: ' . BASE_URL . '/dashboard.php#review-pane');
        exit;
    }
}

try {
    // 1. Fetch Student Profile
    $stmt_user = $pdo->prepare("SELECT * FROM users WHERE id = :id");
    $stmt_user->execute([':id' => $user_id]);
    $student = $stmt_user->fetch();

    // Sync session avatar
    if (isset($student['profile_image'])) {
        $_SESSION['user_avatar'] = $student['profile_image'];
    }

    // 2. Fetch Student Orders with Resource info
    $stmt_orders = $pdo->prepare("
        SELECT o.*, r.title as resource_title, r.campus, r.program, r.file_format, r.pages, r.id as res_id
        FROM orders o
        JOIN resources r ON o.resource_id = r.id
        WHERE o.user_id = :uid
        ORDER BY o.id DESC
    ");
    $stmt_orders->execute([':uid' => $user_id]);
    $orders = $stmt_orders->fetchAll();

    // 3. Filter strictly Paid Resources
    $paid_resources = array_filter($orders, function($o) {
        return $o['payment_status'] === 'PAID';
    });

    // 4. Fetch Download Log
    $stmt_downloads = $pdo->prepare("
        SELECT d.*, r.title as resource_title, r.file_format
        FROM downloads d
        JOIN resources r ON d.resource_id = r.id
        WHERE d.user_id = :uid
        ORDER BY d.id DESC LIMIT 10
    ");
    $stmt_downloads->execute([':uid' => $user_id]);
    $download_logs = $stmt_downloads->fetchAll();

    // 5. Fetch User Notifications
    $notifications = get_user_notifications($pdo, $user_id, 10);
    $unread_count = get_unread_notification_count($pdo, $user_id);

    // 5. Fetch Existing Review by This Student
    $stmt_rev = $pdo->prepare("SELECT * FROM reviews WHERE user_id = :uid LIMIT 1");
    $stmt_rev->execute([':uid' => $user_id]);
    $my_review = $stmt_rev->fetch();

    // 6. Fetch Student's Service Inquiries (if any)
    $my_services = [];
    if (!empty($student['email'])) {
        try {
            $stmt_serv = $pdo->prepare("SELECT * FROM service_requests WHERE email = :email ORDER BY id DESC LIMIT 10");
            $stmt_serv->execute([':email' => $student['email']]);
            $my_services = $stmt_serv->fetchAll();
        } catch (Exception $e) {
            $my_services = [];
        }
    }

    // 7. Calculate Student Total Realized Investment / Spent
    $total_spent = array_reduce($paid_resources, function($carry, $item) {
        return $carry + (float)($item['amount'] ?? 0);
    }, 0);

} catch (Exception $e) {
    die("Error loading dashboard: " . safe_error_message($e));
}

$page_title = "Student LMS Dashboard | AcademicHub Nepal";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <!-- Modern LMS Header Banner -->
    <div class="student-welcome card border-0 shadow-sm rounded-4 p-4 p-md-5 mb-4 text-white position-relative overflow-hidden" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #2563eb 100%);">
        <div class="position-absolute w-100 h-100 top-0 start-0 opacity-10" style="background-image: radial-gradient(#38bdf8 1px, transparent 1px); background-size: 24px 24px; pointer-events: none;"></div>
        <div class="row align-items-center position-relative">
            <div class="col-lg-8 d-flex align-items-center gap-3">
                <div class="position-relative d-inline-block">
                    <?= render_user_avatar($student['profile_image'] ?? null, $student['name'] ?? 'Student', 76, 'border border-3 border-white shadow') ?>
                    <button type="button" onclick="document.getElementById('profile-tab').click();" class="btn btn-sm btn-primary rounded-circle position-absolute bottom-0 end-0 p-0 d-flex align-items-center justify-content-center shadow" style="width: 28px; height: 28px; font-size: 0.75rem;" title="Upload or Change Photo" aria-label="Change photo">
                        <i class="bi bi-camera-fill"></i>
                    </button>
                </div>
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                        <span class="badge bg-warning text-dark fw-bold px-2 py-1" style="font-size: 0.7rem; letter-spacing: 0.04em;">
                            <i class="bi bi-mortarboard-fill me-1"></i> STUDENT LMS PORTAL
                        </span>
                        <span class="badge bg-white bg-opacity-20 text-white rounded-pill px-2 py-0" style="font-size: 0.72rem;">
                            ID: #AH-<?= str_pad($student['id'], 4, '0', STR_PAD_LEFT) ?>
                        </span>
                    </div>
                    <h1 class="h2 fw-bold text-white mb-1" style="font-family: 'Outfit', sans-serif;">Welcome back, <?= htmlspecialchars($student['name'] ?? 'Student') ?>!</h1>
                    <p class="text-white-50 small mb-0 d-flex align-items-center gap-2 flex-wrap">
                        <span><i class="bi bi-buildings text-warning me-1"></i> <?= htmlspecialchars($student['campus'] ?? 'Tribhuvan University') ?></span>
                        <span>&bull;</span>
                        <span><i class="bi bi-envelope text-info me-1"></i> <?= htmlspecialchars($student['email']) ?></span>
                    </p>
                </div>
            </div>
            <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                <div class="d-flex align-items-center justify-content-lg-end gap-2 flex-wrap">
                    <a href="<?= BASE_URL ?>/resources.php" class="btn btn-light rounded-pill px-3 py-2 fw-semibold shadow-sm">
                        <i class="bi bi-search me-1"></i> Browse Catalog
                    </a>
                    <button type="button" onclick="document.getElementById('review-tab').click();" class="btn btn-warning rounded-pill px-3 py-2 fw-bold text-dark shadow-sm">
                        <i class="bi bi-star-fill me-1"></i> <?= $my_review ? 'My Review' : 'Write Review' ?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Stats KPI Cards Grid: Purchased, Downloads, Spent, Requests -->
    <div class="row g-3 mb-4">
        <!-- 1. Purchased -->
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 transition-lift" style="border-top: 3px solid #059669 !important;">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="small text-muted fw-bold text-uppercase" style="font-size: 0.72rem;">Purchased</span>
                    <div class="hub-icon bg-success bg-opacity-10 text-success rounded-3 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                        <i class="bi bi-file-earmark-check-fill fs-5"></i>
                    </div>
                </div>
                <div class="h3 fw-bold text-success mb-0" style="font-family: 'Outfit', sans-serif;"><?= count($paid_resources) ?></div>
                <span class="small text-muted" style="font-size: 0.75rem;">Unlocked Resources</span>
            </div>
        </div>
        <!-- 2. Downloads -->
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 transition-lift" style="border-top: 3px solid #2563eb !important;">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="small text-muted fw-bold text-uppercase" style="font-size: 0.72rem;">Downloads</span>
                    <div class="hub-icon bg-primary bg-opacity-10 text-primary rounded-3 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                        <i class="bi bi-cloud-arrow-down-fill fs-5"></i>
                    </div>
                </div>
                <div class="h3 fw-bold text-dark mb-0" style="font-family: 'Outfit', sans-serif;"><?= count($download_logs) ?></div>
                <span class="small text-muted" style="font-size: 0.75rem;">Completed Downloads</span>
            </div>
        </div>
        <!-- 3. Spent -->
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 transition-lift" style="border-top: 3px solid #4f46e5 !important;">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="small text-muted fw-bold text-uppercase" style="font-size: 0.72rem;">Spent</span>
                    <div class="hub-icon bg-primary bg-opacity-10 text-primary rounded-3 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                        <i class="bi bi-cash-stack fs-5"></i>
                    </div>
                </div>
                <div class="h3 fw-bold text-dark mb-0" style="font-family: 'Outfit', sans-serif;"><?= format_npr($total_spent) ?></div>
                <span class="small text-muted" style="font-size: 0.75rem;">eSewa Investments</span>
            </div>
        </div>
        <!-- 4. Requests -->
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 transition-lift" style="border-top: 3px solid #f59e0b !important;">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="small text-muted fw-bold text-uppercase" style="font-size: 0.72rem;">Requests</span>
                    <div class="hub-icon bg-warning bg-opacity-10 text-warning rounded-3 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                        <i class="bi bi-headset fs-5"></i>
                    </div>
                </div>
                <div class="h3 fw-bold text-dark mb-0" style="font-family: 'Outfit', sans-serif;"><?= count($my_services) ?></div>
                <span class="small text-muted" style="font-size: 0.75rem;">Service Inquiries</span>
            </div>
        </div>
    </div>

    <!-- LMS Dashboard Main Layout with Left Navigation & Right Content -->
    <div class="row g-4">
        
        <!-- LMS Sidebar Navigation (Desktop & Mobile) -->
        <div class="col-lg-3">
            <div class="student-sidebar card border-0 shadow-sm rounded-4 p-3 bg-white sticky-lg-top" style="top: 85px; z-index: 10;">
                <div class="small fw-bold text-uppercase text-muted px-3 py-2 mb-1" style="font-size: 0.68rem; letter-spacing: 0.08em;">
                    STUDENT LMS NAVIGATION
                </div>

                <div class="nav flex-column nav-pills gap-1" id="dashboardTabs" role="tablist">
                    <button class="nav-link text-start rounded-3 fw-semibold py-2 px-3 d-flex align-items-center justify-content-between" id="orders-tab" data-bs-toggle="pill" data-bs-target="#orders-pane" type="button" role="tab">
                        <span><i class="bi bi-grid-1x2-fill me-2 text-primary"></i> Dashboard</span>
                        <span class="badge bg-light text-muted border small"><?= count($orders) ?></span>
                    </button>

                    <button class="nav-link text-start rounded-3 fw-semibold py-2 px-3 d-flex align-items-center justify-content-between" id="notifications-tab" data-bs-toggle="pill" data-bs-target="#notifications-pane" type="button" role="tab">
                        <span><i class="bi bi-bell-fill me-2 text-warning"></i> Notifications</span>
                        <?php if ($unread_count > 0): ?>
                            <span class="badge bg-danger text-white small"><?= $unread_count ?></span>
                        <?php endif; ?>
                    </button>

                    <button class="nav-link text-start rounded-3 fw-semibold py-2 px-3 d-flex align-items-center justify-content-between" id="downloads-tab" data-bs-toggle="pill" data-bs-target="#downloads-pane" type="button" role="tab">
                        <span><i class="bi bi-journal-bookmark-fill me-2 text-success"></i> My Purchases</span>
                        <span class="badge bg-success-subtle text-success border border-success small"><?= count($paid_resources) ?></span>
                    </button>

                    <button class="nav-link text-start rounded-3 fw-semibold py-2 px-3" id="receipts-tab" data-bs-toggle="pill" data-bs-target="#orders-pane-content" type="button" role="tab">
                        <span><i class="bi bi-receipt me-2 text-secondary"></i> Receipts</span>
                    </button>

                    <button class="nav-link text-start rounded-3 fw-semibold py-2 px-3 d-flex align-items-center justify-content-between" id="services-tab" data-bs-toggle="pill" data-bs-target="#services-pane" type="button" role="tab">
                        <span><i class="bi bi-headset me-2 text-info"></i> Services</span>
                        <?php if (count($my_services) > 0): ?>
                            <span class="badge bg-info-subtle text-info border border-info small"><?= count($my_services) ?></span>
                        <?php endif; ?>
                    </button>

                    <button class="nav-link text-start rounded-3 fw-semibold py-2 px-3 d-flex align-items-center justify-content-between" id="profile-tab" data-bs-toggle="pill" data-bs-target="#profile-pane" type="button" role="tab">
                        <span><i class="bi bi-person-lines-fill me-2 text-secondary"></i> Profile</span>
                        <?php if (!empty($student['profile_image'])): ?>
                            <i class="bi bi-check-circle-fill text-success" style="font-size: 0.8rem;"></i>
                        <?php endif; ?>
                    </button>

                    <button class="nav-link text-start rounded-3 fw-semibold py-2 px-3 d-flex align-items-center justify-content-between" id="review-tab" data-bs-toggle="pill" data-bs-target="#review-pane" type="button" role="tab">
                        <span><i class="bi bi-star-fill me-2 text-warning"></i> Reviews</span>
                        <?php if ($my_review): ?>
                            <span class="badge bg-success small" style="font-size: 0.65rem;">Active</span>
                        <?php endif; ?>
                    </button>

                    <button class="nav-link text-start rounded-3 fw-semibold py-2 px-3 d-flex align-items-center justify-content-between" id="support-tab" data-bs-toggle="pill" data-bs-target="#support-pane" type="button" role="tab">
                        <span><i class="bi bi-life-preserver me-2 text-danger"></i> Help</span>
                    </button>
                </div>

                <hr class="my-3">

                <!-- Quick Help / Support Contact Card -->
                <div class="p-3 bg-light rounded-3 text-center">
                    <span class="small fw-bold text-dark d-block mb-1">Need Urgent Help?</span>
                    <p class="text-muted small mb-2" style="font-size: 0.75rem;">Direct WhatsApp support for payment unlocks and downloading.</p>
                    <a href="https://wa.me/9779800000000" target="_blank" class="btn btn-outline-success btn-sm rounded-pill w-100 fw-semibold">
                        <i class="bi bi-whatsapp me-1"></i> WhatsApp Support
                    </a>
                </div>
            </div>
        </div>

        <!-- Main LMS Content Panels -->
        <div class="col-lg-9">
            <div class="tab-content" id="dashboardTabContent">
                
                <!-- ================= 1. MY ORDERS TAB ================= -->
                <div class="tab-pane fade show active" id="orders-pane" role="tabpanel">
                    <?php require __DIR__.'/includes/student-insights.php'; ?>
                </div>

                <!-- ================= 2. NOTIFICATIONS TAB ================= -->
                <div class="tab-pane fade" id="notifications-pane" role="tabpanel">
                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-4">
                        <div class="card-header bg-white p-4 border-bottom d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="fw-bold mb-0 text-dark" style="font-family: 'Outfit', sans-serif;">
                                    <i class="bi bi-bell-fill text-warning me-2"></i>Notifications
                                </h5>
                                <span class="small text-muted">Stay updated with your purchases, downloads, and account activity</span>
                            </div>
                            <?php if ($unread_count > 0): ?>
                                <button type="button" onclick="markAllRead()" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                    Mark All Read
                                </button>
                            <?php endif; ?>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($notifications)): ?>
                                <div class="text-center py-5 p-4">
                                    <div class="rounded-circle bg-light d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 72px; height: 72px;">
                                        <i class="bi bi-bell-slash fs-1 text-muted"></i>
                                    </div>
                                    <h5 class="fw-bold text-dark mb-1">No notifications</h5>
                                    <p class="text-muted small mb-3">You're all caught up! Check back later for updates.</p>
                                </div>
                            <?php else: ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($notifications as $notif): ?>
                                        <div class="list-group-item <?= $notif['is_read'] ? 'bg-light' : 'bg-white' ?> border-start border-4 <?= $notif['is_read'] ? 'border-secondary' : 'border-primary' ?> py-3 px-4">
                                            <div class="d-flex align-items-start gap-3">
                                                <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 40px; height: 40px; <?= $notif['type'] === 'success' ? 'background: #dcfce7; color: #16a34a;' : ($notif['type'] === 'error' ? 'background: #fee2e2; color: #dc2626;' : ($notif['type'] === 'warning' ? 'background: #fef3c7; color: #d97706;' : 'background: #dbeafe; color: #2563eb;')) ?>">
                                                    <i class="bi <?= $notif['type'] === 'success' ? 'bi-check-circle-fill' : ($notif['type'] === 'error' ? 'bi-x-circle-fill' : ($notif['type'] === 'warning' ? 'bi-exclamation-triangle-fill' : 'bi-info-circle-fill')) ?>"></i>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <h6 class="fw-bold text-dark mb-1 <?= $notif['is_read'] ? 'text-muted' : '' ?>"><?= htmlspecialchars($notif['title']) ?></h6>
                                                        <span class="small text-muted" style="font-size: 0.72rem;"><?= date('M d, H:i', strtotime($notif['created_at'])) ?></span>
                                                    </div>
                                                    <p class="small text-muted mb-2"><?= htmlspecialchars($notif['message']) ?></p>
                                                    <?php if (!empty($notif['link_url'])): ?>
                                                        <a href="<?= htmlspecialchars($notif['link_url']) ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1">
                                                            View Details
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if (!$notif['is_read']): ?>
                                                    <button type="button" onclick="markRead(<?= $notif['id'] ?>)" class="btn btn-sm btn-light rounded-circle flex-shrink-0" title="Mark as read" style="width: 32px; height: 32px;">
                                                        <i class="bi bi-check"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- ================= 3. MY ORDERS TAB ================= -->
                <div class="tab-pane fade" id="orders-pane-content" role="tabpanel">
                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-4">
                        <div class="card-header bg-white p-4 border-bottom d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="fw-bold mb-0 text-dark" style="font-family: 'Outfit', sans-serif;">
                                    <i class="bi bi-receipt text-primary me-2"></i>My Orders &amp; Receipts
                                </h5>
                                <span class="small text-muted">Complete payment history and official download licenses</span>
                            </div>
                            <span class="badge bg-light text-muted border px-3 py-1 rounded-pill"><?= count($orders) ?> Total Orders</span>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($orders)): ?>
                                <div class="text-center py-5 p-4">
                                    <div class="rounded-circle bg-light d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 72px; height: 72px;">
                                        <i class="bi bi-cart-x fs-1 text-muted"></i>
                                    </div>
                                    <h5 class="fw-bold text-dark mb-1">No orders placed yet</h5>
                                    <p class="text-muted small mb-3">Browse our campus-wise catalog to get started with reference thesis models and reports.</p>
                                    <a href="<?= BASE_URL ?>/resources.php" class="btn btn-hub-primary rounded-pill px-4 shadow-sm">
                                        <i class="bi bi-journal-text me-1"></i> Browse Resources
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="table-light small">
                                            <tr>
                                                <th class="ps-4">Order ID &amp; Date</th>
                                                <th>Academic Resource</th>
                                                <th>Campus &amp; Program</th>
                                                <th>Amount</th>
                                                <th>Payment Status</th>
                                                <th class="text-end pe-4">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($orders as $ord): ?>
                                                <tr>
                                                    <td class="ps-4">
                                                        <div class="fw-bold text-dark font-monospace small">#<?= str_pad($ord['id'], 5, '0', STR_PAD_LEFT) ?></div>
                                                        <div class="text-muted" style="font-size: 0.75rem;"><?= date('M d, Y', strtotime($ord['created_at'])) ?></div>
                                                    </td>
                                                    <td>
                                                        <a href="<?= BASE_URL ?>/resource-details.php?id=<?= $ord['res_id'] ?>" class="fw-bold text-primary text-decoration-none d-block text-truncate" style="max-width: 260px;">
                                                            <?= htmlspecialchars($ord['resource_title']) ?>
                                                        </a>
                                                        <span class="small text-muted"><?= $ord['pages'] ?> Pgs &bull; <?= htmlspecialchars($ord['file_format']) ?></span>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-light text-dark border small"><?= htmlspecialchars($ord['campus']) ?></span>
                                                        <span class="small text-muted d-block" style="font-size: 0.75rem;"><?= htmlspecialchars($ord['program']) ?></span>
                                                    </td>
                                                    <td class="fw-bold" style="font-family: 'Outfit', sans-serif;">
                                                        <?= format_npr($ord['amount']) ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($ord['payment_status'] === 'PAID'): ?>
                                                            <span class="badge bg-success-subtle text-success border border-success px-2 py-1">
                                                                <i class="bi bi-check-circle-fill me-1"></i> PAID
                                                            </span>
                                                            <div class="small text-muted font-monospace" style="font-size:0.7rem;"><?= htmlspecialchars($ord['transaction_code'] ?? 'eSewa') ?></div>
                                                        <?php elseif ($ord['payment_status'] === 'PENDING_VERIFICATION'): ?>
                                                            <span class="badge bg-warning-subtle text-warning border border-warning px-2 py-1 shadow-xs">
                                                                <i class="bi bi-hourglass-split me-1"></i> QR Verifying
                                                            </span>
                                                            <div class="small text-muted font-monospace" style="font-size:0.7rem;">Txn: <?= htmlspecialchars($ord['transaction_code'] ?? '') ?></div>
                                                        <?php elseif ($ord['payment_status'] === 'PENDING'): ?>
                                                            <span class="badge bg-warning-subtle text-warning border border-warning px-2 py-1">
                                                                <i class="bi bi-clock-history me-1"></i> PENDING
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="badge bg-danger-subtle text-danger border border-danger px-2 py-1">
                                                                <i class="bi bi-x-circle me-1"></i> FAILED
                                                            </span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="text-end pe-4">
                                                        <?php if ($ord['payment_status'] === 'PAID'): ?>
                                                            <div class="d-flex align-items-center justify-content-end gap-1">
                                                                <a href="<?= BASE_URL ?>/receipt.php?id=<?= $ord['id'] ?>" target="_blank" class="btn btn-sm btn-outline-secondary rounded-pill px-3 shadow-xs" title="Official Payment Receipt">
                                                                    <i class="bi bi-file-earmark-text text-danger me-1"></i> Receipt
                                                                </a>
                                                                <a href="<?= BASE_URL ?>/download.php?order_id=<?= $ord['id'] ?>" class="btn btn-sm btn-hub-primary rounded-pill px-3 shadow-xs">
                                                                    <i class="bi bi-download me-1"></i> Download
                                                                </a>
                                                            </div>
                                                        <?php elseif ($ord['payment_status'] === 'PENDING_VERIFICATION'): ?>
                                                            <span class="badge bg-light text-muted border px-2 py-2 small">
                                                                <i class="bi bi-clock text-warning me-1"></i> Under Review
                                                            </span>
                                                        <?php else: ?>
                                                            <a href="<?= BASE_URL ?>/payment.php?resource_id=<?= $ord['res_id'] ?>" class="btn btn-sm btn-outline-success rounded-pill px-3 shadow-xs">
                                                                <i class="bi bi-qr-code me-1"></i> Pay with QR
                                                            </a>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- ================= 2. DOWNLOADS TAB ================= -->
                <div class="tab-pane fade" id="downloads-pane" role="tabpanel">
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h5 class="fw-bold text-dark mb-0" style="font-family: 'Outfit', sans-serif;">
                                    <i class="bi bi-cloud-arrow-down-fill text-success me-2"></i>Unlocked Academic Materials
                                </h5>
                                <span class="small text-muted">Unlimited lifetime access to your purchased resources</span>
                            </div>
                            <span class="badge bg-success-subtle text-success border border-success rounded-pill px-3 py-1">
                                <?= count($paid_resources) ?> Verified Unlocked
                            </span>
                        </div>
                        
                        <?php if (empty($paid_resources)): ?>
                            <div class="text-center py-5">
                                <div class="rounded-circle bg-light d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 72px; height: 72px;">
                                    <i class="bi bi-folder2-open fs-1 text-muted"></i>
                                </div>
                                <h5 class="fw-bold text-dark mb-1">No downloaded resources yet</h5>
                                <p class="text-muted small mb-3">Once an eSewa payment is verified, your purchased documents appear here for unlimited access.</p>
                                <a href="<?= BASE_URL ?>/resources.php" class="btn btn-hub-primary rounded-pill px-4 shadow-sm">
                                    <i class="bi bi-journal-text me-1"></i> Explore Academic Catalog
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="row g-3">
                                <?php foreach ($paid_resources as $paid): ?>
                                    <div class="col-md-6">
                                        <div class="p-3 border rounded-4 bg-light d-flex flex-column justify-content-between h-100 transition-lift" style="border-left: 4px solid #059669 !important;">
                                            <div>
                                                <div class="d-flex align-items-center justify-content-between mb-2">
                                                    <span class="badge bg-primary rounded-pill px-2 py-1"><?= htmlspecialchars($paid['file_format']) ?></span>
                                                    <span class="badge bg-success-subtle text-success border border-success rounded-pill" style="font-size: 0.7rem;">
                                                        <i class="bi bi-patch-check-fill me-1"></i> Lifetime License
                                                    </span>
                                                </div>
                                                <h6 class="fw-bold mb-1 text-dark"><?= htmlspecialchars($paid['resource_title']) ?></h6>
                                                <span class="small text-muted d-block mb-3"><?= htmlspecialchars($paid['campus']) ?> &bull; <?= $paid['pages'] ?> Pages</span>
                                            </div>
                                            <div class="d-flex align-items-center gap-2 pt-2 border-top">
                                                <a href="<?= BASE_URL ?>/receipt.php?id=<?= $paid['id'] ?>" target="_blank" class="btn btn-outline-secondary btn-sm rounded-pill px-3 shadow-xs" title="Official Payment Receipt">
                                                    <i class="bi bi-file-earmark-text text-danger me-1"></i> Receipt
                                                </a>
                                                <a href="<?= BASE_URL ?>/download.php?order_id=<?= $paid['id'] ?>" class="btn btn-hub-primary btn-sm rounded-pill px-3 shadow-xs flex-grow-1 text-center">
                                                    <i class="bi bi-download me-1"></i> Download File
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Download Activity Log -->
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                        <h6 class="fw-bold text-dark mb-3"><i class="bi bi-clock-history text-secondary me-2"></i>Recent Download Access Log</h6>
                        <?php if (empty($download_logs)): ?>
                            <p class="small text-muted mb-0">No download logs recorded yet.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm text-muted small mb-0 align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Resource Title</th>
                                            <th>Format</th>
                                            <th>IP Address</th>
                                            <th>Timestamp</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($download_logs as $dl): ?>
                                            <tr>
                                                <td class="fw-semibold text-dark"><?= htmlspecialchars($dl['resource_title']) ?></td>
                                                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($dl['file_format']) ?></span></td>
                                                <td class="font-monospace small"><?= htmlspecialchars($dl['ip_address']) ?></td>
                                                <td><?= date('Y-m-d H:i', strtotime($dl['download_date'])) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- ================= 3. SERVICE REQUESTS TAB ================= -->
                <div class="tab-pane fade" id="services-pane" role="tabpanel">
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h5 class="fw-bold text-dark mb-0" style="font-family: 'Outfit', sans-serif;">
                                    <i class="bi bi-headset text-info me-2"></i>My Service Requests
                                </h5>
                                <span class="small text-muted">Status of requested thesis formatting, APA 7, or data analysis</span>
                            </div>
                            <a href="<?= BASE_URL ?>/services.php#intakeForm" class="btn btn-hub-primary btn-sm rounded-pill px-3 shadow-sm">
                                <i class="bi bi-plus-circle me-1"></i> New Request
                            </a>
                        </div>

                        <?php if (empty($my_services)): ?>
                            <div class="text-center py-5">
                                <div class="rounded-circle bg-light d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 72px; height: 72px;">
                                    <i class="bi bi-file-earmark-text fs-1 text-muted"></i>
                                </div>
                                <h5 class="fw-bold text-dark mb-1">No service inquiries yet</h5>
                                <p class="text-muted small mb-3">Need APA 7th edition formatting, questionnaire design, or SPSS analysis support?</p>
                                <a href="<?= BASE_URL ?>/services.php" class="btn btn-hub-primary rounded-pill px-4 shadow-sm">
                                    <i class="bi bi-arrow-right-circle me-1"></i> Request Mentorship Service
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light small">
                                        <tr>
                                            <th class="ps-3">Service Type</th>
                                            <th>Campus / Program</th>
                                            <th>Desired Deadline</th>
                                            <th>Submitted Date</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($my_services as $srv): ?>
                                            <tr>
                                                <td class="ps-3">
                                                    <span class="fw-bold text-dark d-block"><?= htmlspecialchars($srv['service_type']) ?></span>
                                                    <span class="small text-muted text-truncate d-inline-block" style="max-width: 250px;"><?= htmlspecialchars($srv['message']) ?></span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-light text-dark border small"><?= htmlspecialchars($srv['campus'] ?: 'General') ?></span>
                                                    <span class="small text-muted d-block"><?= htmlspecialchars($srv['program'] ?: '-') ?></span>
                                                </td>
                                                <td class="small">
                                                    <?= $srv['deadline'] ? date('M d, Y', strtotime($srv['deadline'])) : 'Flexible' ?>
                                                </td>
                                                <td class="small text-muted">
                                                    <?= date('M d, Y', strtotime($srv['created_at'])) ?>
                                                </td>
                                                <td>
                                                    <?php if (strtolower($srv['status']) === 'completed'): ?>
                                                        <span class="badge bg-success-subtle text-success border border-success px-2 py-1">
                                                            <i class="bi bi-check-circle-fill me-1"></i> Completed
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning-subtle text-warning border border-warning px-2 py-1">
                                                            <i class="bi bi-hourglass-split me-1"></i> <?= htmlspecialchars($srv['status']) ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- ================= 4. PROFILE & PICTURE TAB ================= -->
                <div class="tab-pane fade" id="profile-pane" role="tabpanel">
                    <div class="row g-4">
                        <!-- Avatar Upload Card -->
                        <div class="col-lg-4">
                            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100 text-center">
                                <h6 class="fw-bold text-dark mb-3"><i class="bi bi-person-bounding-box me-1 text-primary"></i> Profile Picture</h6>
                                
                                <div class="position-relative d-inline-block mx-auto mb-3">
                                    <div id="currentAvatarDisplay">
                                        <?= render_user_avatar($student['profile_image'] ?? null, $student['name'] ?? 'Student', 120, 'avatar-preview-box') ?>
                                    </div>
                                    <!-- Instant Client Preview -->
                                    <div id="avatarClientPreview" class="d-none mt-2">
                                        <span class="badge bg-warning text-dark mb-1 small">Previewing New Photo</span>
                                        <div>
                                            <img id="avatarClientPreviewImg" src="" alt="New Photo Preview" class="avatar-preview-box">
                                        </div>
                                    </div>
                                </div>

                                <div class="small text-muted mb-3">
                                    <?php if (!empty($student['profile_image'])): ?>
                                        <span class="badge bg-success-subtle text-success border border-success px-2 py-1">
                                            <i class="bi bi-check-circle-fill me-1"></i> Custom Photo Active
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted border px-2 py-1">
                                            <i class="bi bi-info-circle me-1"></i> Using Default Initials
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <!-- Upload Photo Form -->
                                <form action="<?= BASE_URL ?>/dashboard.php" method="POST" enctype="multipart/form-data" class="mb-3">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="upload_avatar">
                                    <div class="avatar-upload-card mb-3 text-start">
                                        <label for="profilePhotoInput" class="form-label small fw-semibold text-dark mb-1">
                                            <i class="bi bi-image me-1 text-secondary"></i> Select New Image
                                        </label>
                                        <input type="file" name="profile_picture" id="profilePhotoInput" accept="image/jpeg,image/png,image/webp" class="form-control form-control-sm" required onchange="previewAvatarImage(this)">
                                        <div class="form-text text-muted" style="font-size: 0.75rem;">
                                            Max 5 MB. Formats: JPG, PNG, WEBP.
                                        </div>
                                    </div>

                                    <button type="submit" class="btn btn-hub-primary btn-sm rounded-pill w-100 fw-semibold shadow-sm py-2">
                                        <i class="bi bi-cloud-arrow-up-fill me-1"></i> Save Profile Picture
                                    </button>
                                </form>

                                <!-- Remove Photo Form (if uploaded) -->
                                <?php if (!empty($student['profile_image'])): ?>
                                    <form action="<?= BASE_URL ?>/dashboard.php" method="POST" onsubmit="return confirm('Remove your profile picture and revert to initial avatar?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="remove_avatar">
                                        <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill w-100 py-1">
                                            <i class="bi bi-trash3 me-1"></i> Remove Picture
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Profile Information Card -->
                        <div class="col-lg-8">
                            <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white h-100">
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <div>
                                        <h5 class="fw-bold text-dark mb-0" style="font-family: 'Outfit', sans-serif;">
                                            <i class="bi bi-person-lines-fill text-primary me-2"></i>Student Profile Details
                                        </h5>
                                        <span class="small text-muted">Your registered university student credentials</span>
                                    </div>
                                    <span class="badge bg-primary text-uppercase px-3 py-1"><?= htmlspecialchars($student['role']) ?></span>
                                </div>
                                
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="p-3 bg-light rounded-3 border">
                                            <label class="small text-muted fw-semibold d-block">Full Name</label>
                                            <span class="fs-6 fw-bold text-dark"><?= htmlspecialchars($student['name']) ?></span>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="p-3 bg-light rounded-3 border">
                                            <label class="small text-muted fw-semibold d-block">Email Address</label>
                                            <span class="fs-6 fw-bold text-dark"><?= htmlspecialchars($student['email']) ?></span>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="p-3 bg-light rounded-3 border">
                                            <label class="small text-muted fw-semibold d-block">Affiliated Campus</label>
                                            <span class="fs-6 fw-bold text-dark"><?= htmlspecialchars($student['campus'] ?? 'Not specified') ?></span>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="p-3 bg-light rounded-3 border">
                                            <label class="small text-muted fw-semibold d-block">Mobile Number</label>
                                            <span class="fs-6 fw-bold text-dark"><?= htmlspecialchars($student['phone'] ?: 'None provided') ?></span>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="p-3 bg-light rounded-3 border">
                                            <label class="small text-muted fw-semibold d-block">Member Since</label>
                                            <span class="fs-6 fw-bold text-dark"><?= date('F d, Y', strtotime($student['created_at'])) ?></span>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="p-3 bg-light rounded-3 border">
                                            <label class="small text-muted fw-semibold d-block">Verification Status</label>
                                            <span class="badge bg-success-subtle text-success border border-success px-2 py-1">
                                                <i class="bi bi-patch-check-fill me-1"></i> Verified Student
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div class="alert alert-info border-0 rounded-3 mt-4 mb-0 small d-flex align-items-center gap-2">
                                    <i class="bi bi-shield-lock-fill fs-5 text-primary"></i>
                                    <div>Your student profile picture is displayed on your portal, dropdown header, and on your verified review testimonials.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ================= 5. WRITE REVIEW TAB ================= -->
                <div class="tab-pane fade" id="review-pane" role="tabpanel">
                    <div class="row g-4">
                        <!-- Form Column -->
                        <div class="col-lg-7">
                            <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white h-100">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <h5 class="fw-bold text-dark mb-1" style="font-family: 'Outfit', sans-serif;">
                                            <i class="bi bi-star-fill text-warning me-2"></i>Student Review &amp; Feedback
                                        </h5>
                                        <p class="text-muted small mb-0">Share your learning experience on AcademicHub Nepal. Your feedback will be published on the homepage!</p>
                                    </div>
                                    <?php if ($my_review): ?>
                                        <span class="badge bg-success-subtle text-success border border-success px-3 py-1">
                                            <i class="bi bi-check2-circle me-1"></i> Live on Website
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <?php if ($my_review): ?>
                                    <div class="alert alert-success border-0 rounded-3 mb-4 small d-flex align-items-center justify-content-between" style="background-color: #ECFDF5; border-left: 4px solid #059669 !important;">
                                        <div>
                                            <i class="bi bi-patch-check-fill me-1 text-success"></i> You currently have an active review published on the homepage. You can update it anytime below.
                                        </div>
                                        <span class="badge bg-light text-dark"><?= date('M d, Y', strtotime($my_review['created_at'])) ?></span>
                                    </div>
                                <?php endif; ?>

                                <form action="<?= BASE_URL ?>/dashboard.php" method="POST">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="save_review">

                                    <!-- Rating Selector -->
                                    <div class="mb-4">
                                        <label class="form-label small fw-bold text-dark d-block mb-1">
                                            Overall Rating <span class="text-danger">*</span>
                                        </label>
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="star-rating-widget">
                                                <?php $curRating = (int)($my_review['rating'] ?? 5); ?>
                                                <input type="radio" id="star5" name="rating" value="5" <?= $curRating === 5 ? 'checked' : '' ?> onchange="updateStarRatingText(5)">
                                                <label for="star5" title="5 Stars - Outstanding"><i class="bi bi-star-fill"></i></label>

                                                <input type="radio" id="star4" name="rating" value="4" <?= $curRating === 4 ? 'checked' : '' ?> onchange="updateStarRatingText(4)">
                                                <label for="star4" title="4 Stars - Very Good"><i class="bi bi-star-fill"></i></label>

                                                <input type="radio" id="star3" name="rating" value="3" <?= $curRating === 3 ? 'checked' : '' ?> onchange="updateStarRatingText(3)">
                                                <label for="star3" title="3 Stars - Good"><i class="bi bi-star-fill"></i></label>

                                                <input type="radio" id="star2" name="rating" value="2" <?= $curRating === 2 ? 'checked' : '' ?> onchange="updateStarRatingText(2)">
                                                <label for="star2" title="2 Stars - Fair"><i class="bi bi-star-fill"></i></label>

                                                <input type="radio" id="star1" name="rating" value="1" <?= $curRating === 1 ? 'checked' : '' ?> onchange="updateStarRatingText(1)">
                                                <label for="star1" title="1 Star - Poor"><i class="bi bi-star-fill"></i></label>
                                            </div>
                                            <span id="starRatingLabel" class="small fw-semibold text-warning">
                                                <?= $curRating === 5 ? '5 Stars - Outstanding' : ($curRating === 4 ? '4 Stars - Very Good' : ($curRating === 3 ? '3 Stars - Good' : ($curRating === 2 ? '2 Stars - Fair' : '1 Star - Needs Improvement'))) ?>
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Degree / Program -->
                                    <div class="mb-3">
                                        <label for="reviewProgramInput" class="form-label small fw-bold text-dark">
                                            Your Academic Program &amp; Year <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" name="program" id="reviewProgramInput" class="form-control" 
                                            placeholder="e.g. BBS 4th Year, BBA 8th Semester, Ma In Societies 4th Year, MBS 2nd Year" 
                                            value="<?= htmlspecialchars($my_review['program'] ?? 'BBS 4th Year') ?>" 
                                            required oninput="updateLiveReviewPreview()">
                                        <div class="form-text text-muted" style="font-size: 0.75rem;">
                                            Helps students in your discipline identify relevant project recommendations.
                                        </div>
                                    </div>

                                    <!-- Review Text -->
                                    <div class="mb-4">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <label for="reviewTextInput" class="form-label small fw-bold text-dark mb-0">
                                                Your Review / Experience <span class="text-danger">*</span>
                                            </label>
                                            <span class="small text-muted" style="font-size: 0.75rem;">
                                                <span id="charCount"><?= strlen($my_review['review_text'] ?? '') ?></span> / 1000 characters
                                            </span>
                                        </div>
                                        <textarea name="review_text" id="reviewTextInput" rows="5" class="form-control" 
                                            minlength="15" maxlength="1000" required 
                                            placeholder="Describe how AcademicHub Nepal helped with your project proposal, thesis formatting, questionnaires, or internal defense..." 
                                            oninput="updateLiveReviewPreview()"><?= htmlspecialchars($my_review['review_text'] ?? '') ?></textarea>
                                    </div>

                                    <button type="submit" class="btn btn-warning rounded-pill px-4 py-2 fw-bold text-dark shadow-sm">
                                        <i class="bi bi-send-check-fill me-1"></i> <?= $my_review ? 'Update My Review' : 'Publish My Review' ?>
                                    </button>
                                    <a href="<?= BASE_URL ?>/index.php#testimonials" target="_blank" class="btn btn-link text-muted btn-sm ms-2 text-decoration-none">
                                        <i class="bi bi-box-arrow-up-right me-1"></i> View Live Testimonials
                                    </a>
                                </form>
                            </div>
                        </div>

                        <!-- Live Homepage Card Simulation -->
                        <div class="col-lg-5">
                            <div class="card border-0 shadow-sm rounded-4 p-4 bg-light h-100">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <span class="badge bg-secondary text-uppercase small"><i class="bi bi-eye-fill me-1"></i> Live Homepage Preview</span>
                                    <span class="small text-muted">What visitors see</span>
                                </div>

                                <div class="testimonial-card review-preview-card shadow-sm bg-white p-4 rounded-3">
                                    <div class="testimonial-stars mb-2" id="previewStars">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="bi <?= $i <= ($my_review['rating'] ?? 5) ? 'bi-star-fill text-warning' : 'bi-star text-muted' ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <p class="text-dark small mb-3 fst-italic" id="previewCardText" style="line-height: 1.6; min-height: 60px;">
                                        <?= !empty($my_review['review_text']) ? '"' . htmlspecialchars($my_review['review_text']) . '"' : '"The BBS 4th Year Finance project sample saved me weeks of confusion. The chapter organization, financial ratios, and statistical tables were exactly aligned with our college guidelines."' ?>
                                    </p>
                                    <div class="d-flex align-items-center gap-3 pt-3 border-top">
                                        <?= render_user_avatar($student['profile_image'] ?? null, $student['name'] ?? 'Student', 42, 'shadow-xs') ?>
                                        <div style="min-width: 0;">
                                            <strong class="d-block text-dark small text-truncate">
                                                <?= htmlspecialchars($student['name'] ?? 'Student Name') ?>
                                                <span class="badge bg-success-subtle text-success border border-success ms-1" style="font-size: 0.65rem;">
                                                    <i class="bi bi-patch-check-fill me-1"></i>Verified
                                                </span>
                                            </strong>
                                            <span class="text-muted text-truncate d-block" style="font-size: 0.75rem;">
                                                <span id="previewCardProg"><?= htmlspecialchars($my_review['program'] ?? 'BBS 4th Year') ?></span> &bull; 
                                                <span><?= htmlspecialchars($student['campus'] ?? 'Tribhuvan University') ?></span>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div class="card border-0 bg-white rounded-3 p-3 mt-3 shadow-none">
                                    <h6 class="small fw-bold text-dark mb-1"><i class="bi bi-lightbulb-fill text-warning me-1"></i> Review Tips:</h6>
                                    <ul class="text-muted small ps-3 mb-0" style="font-size: 0.8rem; line-height: 1.6;">
                                        <li>Mention your specific campus or college program.</li>
                                        <li>Highlight which report or template assisted you most.</li>
                                        <li>Note how smooth the eSewa QR payment or download was.</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ================= 6. SUPPORT TAB ================= -->
                <div class="tab-pane fade" id="support-pane" role="tabpanel">
                    <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white">
                        <div class="mb-4">
                            <h5 class="fw-bold text-dark mb-1" style="font-family: 'Outfit', sans-serif;">
                                <i class="bi bi-life-preserver text-danger me-2"></i>AcademicHub Student Support
                            </h5>
                            <p class="text-muted small mb-0">Experiencing issues with an eSewa payment or opening a downloaded document? Submit a ticket below.</p>
                        </div>

                        <form action="" method="POST" onsubmit="alert('Support ticket submitted successfully! A support executive will contact you via your registered email.'); return false;">
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold text-dark">Related Order Number (Optional)</label>
                                    <input type="text" class="form-control" placeholder="e.g. #ORD-2026-001">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold text-dark">Issue Category</label>
                                    <select class="form-select">
                                        <option>Payment Deducted but Not Unlocked</option>
                                        <option>Corrupted / Unable to Open File</option>
                                        <option>Formatting Question</option>
                                        <option>General Inquiry</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label small fw-semibold text-dark">Detailed Message / Description</label>
                                <textarea class="form-control" rows="4" placeholder="Please describe the issue in detail..." required></textarea>
                            </div>

                            <button type="submit" class="btn btn-hub-primary px-4 py-2 fw-semibold">
                                <i class="bi bi-send-fill me-1"></i> Submit Support Request
                            </button>
                        </form>
                    </div>
                </div>

            </div>
        </div>

    </div>
</div>

<script>
// Mark notification as read
function markRead(notificationId) {
    fetch('<?= BASE_URL ?>/api/mark_notification_read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'notification_id=' + notificationId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    })
    .catch(error => console.error('Error:', error));
}

// Mark all notifications as read
function markAllRead() {
    if (confirm('Mark all notifications as read?')) {
        fetch('<?= BASE_URL ?>/api/mark_all_notifications_read.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            }
        })
        .catch(error => console.error('Error:', error));
    }
}

// Live avatar preview before submitting
function previewAvatarImage(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        if (file.size > 5 * 1024 * 1024) {
            alert('File exceeds 5MB size limit. Please choose a smaller image.');
            input.value = '';
            return;
        }
        const reader = new FileReader();
        reader.onload = function(e) {
            const previewContainer = document.getElementById('avatarClientPreview');
            const previewImg = document.getElementById('avatarClientPreviewImg');
            if (previewContainer && previewImg) {
                previewImg.src = e.target.result;
                previewContainer.classList.remove('d-none');
            }
        };
        reader.readAsDataURL(file);
    }
}

// Update star rating text and simulation
function updateStarRatingText(val) {
    const labels = {
        5: '5 Stars - Outstanding Experience',
        4: '4 Stars - Very Good',
        3: '3 Stars - Good / Helpful',
        2: '2 Stars - Fair',
        1: '1 Star - Needs Improvement'
    };
    const lbl = document.getElementById('starRatingLabel');
    if (lbl) lbl.textContent = labels[val] || (val + ' Stars');

    const previewStars = document.getElementById('previewStars');
    if (previewStars) {
        let html = '';
        for (let i = 1; i <= 5; i++) {
            if (i <= val) {
                html += '<i class="bi bi-star-fill text-warning me-1"></i>';
            } else {
                html += '<i class="bi bi-star text-muted me-1"></i>';
            }
        }
        previewStars.innerHTML = html;
    }
}

// Live review text and program preview
function updateLiveReviewPreview() {
    const text = document.getElementById('reviewTextInput').value;
    const previewText = document.getElementById('previewCardText');
    const charCount = document.getElementById('charCount');
    if (charCount) charCount.textContent = text.length;
    if (previewText) {
        previewText.textContent = text.trim() ? '"' + text + '"' : '"Your review text will appear here on the homepage..."';
    }
    const prog = document.getElementById('reviewProgramInput').value;
    const previewProg = document.getElementById('previewCardProg');
    if (previewProg) {
        previewProg.textContent = prog.trim() ? prog : 'Academic Program';
    }
}

// URL Hash Tab switcher
document.addEventListener('DOMContentLoaded', function() {
    if (window.location.hash) {
        const hash = window.location.hash;
        const targetBtn = document.querySelector(`button[data-bs-target="${hash}"]`);
        if (targetBtn) {
            const tab = new bootstrap.Tab(targetBtn);
            tab.show();
        }
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
