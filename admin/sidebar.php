<?php
/**
 * AcademicHub Nepal - Unified Modern Executive Admin & Super Admin Sidebar
 * File: admin/sidebar.php
 */
require_once __DIR__ . '/../config/auth_check.php';

$active_page = $active_page ?? 'dashboard';
$admin_name = $_SESSION['user_name'] ?? 'Administrator';
$admin_email = $_SESSION['user_email'] ?? 'admin@academichub.com';
$admin_role = $_SESSION['user_role'] ?? 'admin';
$admin_title = $_SESSION['user_admin_role'] ?? ($admin_role === 'superadmin' ? 'Super Administrator' : 'Staff Administrator');
$admin_initial = strtoupper(substr($admin_name, 0, 1));
$is_sa = is_superadmin();
?>
<!-- Mobile Admin Header Toggle (Visible on screens < 768px) -->
<div class="d-md-none text-white p-3 d-flex align-items-center justify-content-between border-bottom w-100" style="background-color: #0F172A !important;">
    <div class="d-flex align-items-center gap-2">
        <div class="hub-icon rounded-2 shadow-xs" style="width:34px;height:34px;display:flex;align-items:center;justify-content:center;background: <?= $is_sa ? 'linear-gradient(135deg, #f59e0b, #d97706)' : 'linear-gradient(135deg, #2563eb, #38bdf8)' ?>; color: #fff;">
            <i class="bi <?= $is_sa ? 'bi-shield-shaded' : 'bi-mortarboard-fill' ?>"></i>
        </div>
        <div>
            <div class="fw-bold text-white lh-1 small" style="font-family: 'Outfit', sans-serif;">AcademicHub</div>
            <span class="badge bg-warning bg-opacity-25 text-warning" style="font-size:0.6rem; letter-spacing:0.04em;"><?= $is_sa ? 'SUPER ADMIN' : 'ADMIN' ?></span>
        </div>
    </div>
    <button class="btn btn-outline-light btn-sm rounded-pill px-3 py-1 d-flex align-items-center gap-1 shadow-xs" type="button" data-bs-toggle="collapse" data-bs-target="#adminSidebarCollapse" aria-expanded="false" aria-controls="adminSidebarCollapse" aria-label="Toggle admin navigation menu">
        <i class="bi bi-list fs-6"></i>
        <span class="small fw-semibold">Menu</span>
    </button>
</div>

<!-- Modern Executive Admin Sidebar -->
<div class="col-md-3 col-lg-2 p-0 admin-sidebar d-flex flex-column collapse d-md-flex" id="adminSidebarCollapse" style="position: sticky; top: 0; height: 100vh; overflow-y: auto;">
    <!-- Brand Header -->
    <div class="p-3 border-bottom border-secondary border-opacity-25 d-none d-md-flex align-items-center gap-3">
        <div class="hub-icon rounded-3 shadow-sm" style="width:40px;height:40px;display:flex;align-items:center;justify-content:center; background: <?= $is_sa ? 'linear-gradient(135deg, #f59e0b, #d97706)' : 'linear-gradient(135deg, #2563eb, #38bdf8)' ?>; color: #fff;">
            <i class="bi <?= $is_sa ? 'bi-shield-shaded' : 'bi-mortarboard-fill' ?> fs-5"></i>
        </div>
        <div class="overflow-hidden">
            <h6 class="fw-bold text-white mb-0 lh-1 text-truncate" style="font-family: 'Outfit', sans-serif;">AcademicHub</h6>
            <?php if ($is_sa): ?>
                <span class="badge bg-warning bg-opacity-25 text-warning border border-warning border-opacity-25 mt-1" style="font-size:0.62rem; letter-spacing: 0.06em;">SUPER ADMIN</span>
            <?php else: ?>
                <span class="badge bg-info bg-opacity-25 text-info mt-1" style="font-size:0.62rem; letter-spacing: 0.05em;">ADMIN CONSOLE</span>
            <?php endif; ?>
        </div>
    </div>

    <div class="p-3"><button type="button" class="btn btn-outline-light w-100" data-theme-toggle aria-label="Switch to dark mode" aria-pressed="false"><i class="bi bi-moon-stars"></i></button></div>
    <!-- Navigation Menu -->
    <div class="p-3 flex-grow-1">
        <div class="small text-uppercase fw-bold text-white-50 mb-2" style="font-size:0.68rem; letter-spacing:0.08em;">Core Management</div>
        
        <a href="<?= BASE_URL ?>/admin/dashboard.php" class="<?= $active_page === 'dashboard' ? 'active' : '' ?>">
            <i class="bi bi-grid-1x2-fill"></i> Overview
        </a>
        <a href="<?= BASE_URL ?>/admin/add-resource.php" class="<?= $active_page === 'add-resource' ? 'active' : '' ?>">
            <i class="bi bi-cloud-arrow-up-fill"></i> Add Resource
        </a>
        <a href="<?= BASE_URL ?>/admin/manage-resource.php" class="<?= $active_page === 'manage-resource' ? 'active' : '' ?>">
            <i class="bi bi-journals"></i> Manage Resources
        </a>
<?php
$side_pending_qr = 0;
if (isset($pdo)) {
    try {
        $side_pending_qr = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE payment_status = 'PENDING_VERIFICATION'")->fetchColumn();
    } catch (Exception $e) {}
}
?>
        <a href="<?= BASE_URL ?>/admin/manage-orders.php" class="<?= $active_page === 'manage-orders' ? 'active' : '' ?> d-flex align-items-center justify-content-between">
            <span><i class="bi bi-cart-check-fill"></i> Manage Orders</span>
            <?php if ($side_pending_qr > 0): ?>
                <span class="badge bg-warning text-dark px-2 rounded-pill shadow-sm d-flex align-items-center gap-1" style="font-size:0.68rem;">
                    <span class="pulse-dot pulse-warning" style="width:6px;height:6px;"></span> <?= $side_pending_qr ?>
                </span>
            <?php endif; ?>
        </a>
        <a href="<?= BASE_URL ?>/admin/manage-students.php" class="<?= $active_page === 'manage-students' ? 'active' : '' ?>">
            <i class="bi bi-people-fill"></i> Manage Students
        </a>
        <a href="<?= BASE_URL ?>/admin/manage-team.php" class="<?= $active_page === 'manage-team' ? 'active' : '' ?>">
            <i class="bi bi-person-workspace"></i> Team Members
        </a>
        <a href="<?= BASE_URL ?>/admin/sales-reports.php" class="<?= $active_page === 'sales-reports' ? 'active' : '' ?>">
            <i class="bi bi-bar-chart-fill"></i> Sales Reports
        </a>

        <div class="small text-uppercase fw-bold text-white-50 mt-4 mb-2">Content Management</div>
        <a href="<?= BASE_URL ?>/admin/cms-content.php" class="<?= $active_page === 'cms-content' ? 'active' : '' ?>">
            <i class="bi bi-gear-fill"></i> CMS Control
        </a>
        <?php foreach (['students'=>['Successful Students','manage_students_success','bi-mortarboard'], 'colleges'=>['Colleges','manage_colleges','bi-buildings'], 'gallery'=>['Society Gallery','manage_gallery','bi-images']] as $key=>$item): if(admin_has_permission($item[1])): ?>
        <a href="<?= BASE_URL ?>/admin/content.php?module=<?= $key ?>" class="<?= $active_page === 'content-'.$key ? 'active' : '' ?>"><i class="bi <?= $item[2] ?>"></i> <?= $item[0] ?></a>
        <?php endif; endforeach; ?>
        <?php if(admin_has_permission('manage_team')): ?><a href="<?= BASE_URL ?>/admin/manage-team.php"><i class="bi bi-people"></i> Our Team</a><?php endif; ?>
        <?php if(is_superadmin() || admin_has_permission('manage_students_success') || admin_has_permission('manage_colleges') || admin_has_permission('manage_team') || admin_has_permission('manage_gallery') || admin_has_permission('manage_resources')): ?><a href="<?= BASE_URL ?>/admin/media.php" class="<?= $active_page==='media'?'active':'' ?>"><i class="bi bi-image"></i> Media Library</a><?php endif; ?>
        <!-- Super Admin Governance Exclusive Module -->
        <?php if ($is_sa): ?>
            <div class="small text-uppercase fw-bold text-warning mb-2 mt-4" style="font-size:0.68rem; letter-spacing:0.08em;">
                <i class="bi bi-shield-shaded me-1"></i>Role Governance
            </div>
            <a href="<?= BASE_URL ?>/admin/manage-admins.php" class="<?= $active_page === 'manage-admins' ? 'active' : '' ?> text-warning">
                <i class="bi bi-person-gear"></i> Admins &amp; Roles
            </a>
        <?php endif; ?>

        <div class="small text-uppercase fw-bold text-white-50 mt-4 mb-2" style="font-size:0.68rem; letter-spacing:0.08em;">Account &amp; Security</div>
        <a href="<?= BASE_URL ?>/admin/dashboard.php#tab-settings" class="<?= $active_page === 'settings' ? 'active' : '' ?>">
            <i class="bi bi-shield-lock-fill"></i> ID &amp; Password Settings
        </a>

        <hr class="border-secondary border-opacity-25 my-3">
        <a href="<?= BASE_URL ?>/index.php" target="_blank" class="text-white-50 small">
            <i class="bi bi-box-arrow-up-right"></i> View Public Site
        </a>
    </div>

    <!-- Logged Admin Bottom Profile -->
    <div class="p-3 border-top border-secondary border-opacity-25 bg-black bg-opacity-20">
        <div class="d-flex align-items-center gap-2 mb-2">
            <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white shadow-sm" style="width: 36px; height: 36px; font-size: 0.85rem; background: <?= $is_sa ? 'linear-gradient(135deg, #d97706, #f59e0b)' : 'linear-gradient(135deg, #0284c7, #0f2942)' ?>;">
                <?= $admin_initial ?>
            </div>
            <div class="overflow-hidden">
                <div class="fw-bold text-light small text-truncate"><?= htmlspecialchars($admin_name) ?></div>
                <div class="text-white-50 text-truncate" style="font-size: 0.72rem;"><?= htmlspecialchars($admin_title) ?></div>
            </div>
        </div>
        <a href="<?= BASE_URL ?>/admin/logout.php" class="btn btn-outline-danger btn-sm w-100 py-1 d-flex align-items-center justify-content-center gap-2" style="font-size:0.8rem;">
            <i class="bi bi-box-arrow-right"></i> Log Out
        </a>
    </div>
<?php require __DIR__ . '/../includes/admin-topbar.php'; ?>
</div>

