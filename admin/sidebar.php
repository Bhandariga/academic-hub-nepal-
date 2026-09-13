<?php
/**
 * AcademicHub Nepal - Unified Modern Admin Sidebar
 * File: admin/sidebar.php
 */
$active_page = $active_page ?? 'dashboard';
$admin_name = $_SESSION['user_name'] ?? 'Admin';
$admin_email = $_SESSION['user_email'] ?? 'admin@academichub.com';
$admin_initial = strtoupper(substr($admin_name, 0, 1));
?>
<!-- Modern Executive Admin Sidebar -->
<div class="col-md-3 col-lg-2 p-0 admin-sidebar d-flex flex-column" style="position: sticky; top: 0; height: 100vh; overflow-y: auto;">
    <!-- Brand Header -->
    <div class="p-3 border-bottom border-secondary border-opacity-25 d-flex align-items-center gap-3">
        <div class="hub-icon bg-info text-dark rounded-3 shadow-sm" style="width:38px;height:38px;display:flex;align-items:center;justify-content:center;">
            <i class="bi bi-mortarboard-fill fs-5"></i>
        </div>
        <div>
            <h6 class="fw-bold text-white mb-0 lh-1">AcademicHub</h6>
            <span class="badge bg-info bg-opacity-25 text-info mt-1" style="font-size:0.65rem; letter-spacing: 0.05em;">ADMIN CONSOLE</span>
        </div>
    </div>

    <!-- Navigation Menu -->
    <div class="p-3 flex-grow-1">
        <div class="small text-uppercase fw-bold text-white-50 mb-2" style="font-size:0.7rem; letter-spacing:0.08em;">Core Management</div>
        
        <a href="<?= BASE_URL ?>/admin/dashboard.php" class="<?= $active_page === 'dashboard' ? 'active' : '' ?>">
            <i class="bi bi-grid-1x2-fill"></i> Overview
        </a>
        <a href="<?= BASE_URL ?>/admin/add-resource.php" class="<?= $active_page === 'add-resource' ? 'active' : '' ?>">
            <i class="bi bi-cloud-arrow-up-fill"></i> Add Resource
        </a>
        <a href="<?= BASE_URL ?>/admin/manage-resource.php" class="<?= $active_page === 'manage-resource' ? 'active' : '' ?>">
            <i class="bi bi-journals"></i> Manage Resources
        </a>
        <a href="<?= BASE_URL ?>/admin/manage-orders.php" class="<?= $active_page === 'manage-orders' ? 'active' : '' ?>">
            <i class="bi bi-cart-check-fill"></i> Manage Orders
        </a>
        <a href="<?= BASE_URL ?>/admin/manage-students.php" class="<?= $active_page === 'manage-students' ? 'active' : '' ?>">
            <i class="bi bi-people-fill"></i> Manage Students
        </a>
        <a href="<?= BASE_URL ?>/admin/sales-reports.php" class="<?= $active_page === 'sales-reports' ? 'active' : '' ?>">
            <i class="bi bi-bar-chart-fill"></i> Sales Reports
        </a>

        <div class="small text-uppercase fw-bold text-white-50 mt-4 mb-2" style="font-size:0.7rem; letter-spacing:0.08em;">Account &amp; Security</div>
        <a href="<?= BASE_URL ?>/admin/dashboard.php#settings" class="<?= $active_page === 'settings' ? 'active' : '' ?>">
            <i class="bi bi-shield-lock-fill"></i> ID &amp; Password Settings
        </a>

        <hr class="border-secondary border-opacity-25 my-3">
        <a href="<?= BASE_URL ?>/index.php" target="_blank" class="text-white-50">
            <i class="bi bi-box-arrow-up-right"></i> View Public Site
        </a>
    </div>

    <!-- Logged Admin Bottom Profile -->
    <div class="p-3 border-top border-secondary border-opacity-25 bg-black bg-opacity-20">
        <div class="d-flex align-items-center gap-2 mb-2">
            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 34px; height: 34px; font-size: 0.85rem;">
                <?= $admin_initial ?>
            </div>
            <div class="overflow-hidden">
                <div class="fw-bold text-light small text-truncate"><?= htmlspecialchars($admin_name) ?></div>
                <div class="text-white-50 text-truncate" style="font-size: 0.72rem;"><?= htmlspecialchars($admin_email) ?></div>
            </div>
        </div>
        <a href="<?= BASE_URL ?>/admin/logout.php" class="btn btn-outline-danger btn-sm w-100 py-1 d-flex align-items-center justify-content-center gap-2">
            <i class="bi bi-box-arrow-right"></i> Log Out
        </a>
    </div>
</div>
