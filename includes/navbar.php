<?php
/**
 * AcademicHub Nepal - Navbar Component
 */
$isLoggedIn = is_logged_in();
$user = get_current_user_data();
$user_initial = $user ? strtoupper(substr($user['name'] ?? 'U', 0, 1)) : 'U';
?>
<nav class="navbar navbar-expand-xl navbar-hub sticky-top">
    <div class="container">
        <!-- Brand Logo -->
        <a class="navbar-brand navbar-brand-hub" href="<?= BASE_URL ?>/index.php" style="font-size: 23px;">
            <div class="hub-icon">
                <i class="bi bi-mortarboard-fill"></i>
            </div>
            <span>AcademicHub <span style="color: var(--secondary-color) !important;">Nepal</span></span>
        </a>

        <!-- Mobile Toggle Button -->
        <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-expanded="false" aria-controls="navbarMain" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navbar Links -->
        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-5" style="gap: 20px;">
                <li class="nav-item"><a class="nav-link <?= $current_page === 'index.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/index.php" style="font-size: 18px; font-weight: 600;">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/index.php#campuses" style="font-size: 18px; font-weight: 600;">Campuses</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/index.php#programs" style="font-size: 18px; font-weight: 600;">Programs</a></li>
                <li class="nav-item"><a class="nav-link <?= in_array($current_page, ['resources.php','resource-details.php']) ? 'active' : '' ?>" href="<?= BASE_URL ?>/resources.php" style="font-size: 18px; font-weight: 600;">Resources</a></li>
                <li class="nav-item"><a class="nav-link <?= $current_page === 'services.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/services.php" style="font-size: 18px; font-weight: 600;">Services</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/index.php#how-it-works" style="font-size: 18px; font-weight: 600;">How it works</a></li>
                <li class="nav-item dropdown"><button class="nav-link dropdown-toggle border-0 bg-transparent" data-bs-toggle="dropdown" aria-expanded="false" style="font-size: 18px; font-weight: 600;">About</button><ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="<?= BASE_URL ?>/index.php#programs">Programs</a></li>
                    <li><a class="dropdown-item" href="<?= BASE_URL ?>/index.php#campuses">Campus hubs</a></li>
                    <li><a class="dropdown-item" href="<?= BASE_URL ?>/mba.php">MBA Research Desk</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="<?= BASE_URL ?>/about.php">About AcademicHub</a></li>
                    <li><a class="dropdown-item" href="<?= BASE_URL ?>/team.php">Our academic team</a></li>
                    <li><a class="dropdown-item" href="<?= BASE_URL ?>/successful-students.php">Successful Students</a></li>
                    <li><a class="dropdown-item" href="<?= BASE_URL ?>/colleges.php">Associated Colleges</a></li>
                    <li><a class="dropdown-item" href="<?= BASE_URL ?>/society.php">AcademicHub Society</a></li>
                </ul></li>
            </ul>
            <?php require __DIR__ . '/ui-tools.php'; ?>
            <!-- Auth Actions -->
            <div class="d-flex align-items-center gap-2">
                <?php if ($isLoggedIn): ?>
                    <?php if (is_superadmin()): ?>
                        <a href="<?= BASE_URL ?>/admin/dashboard.php" class="btn btn-warning btn-sm rounded-pill px-3 fw-bold text-dark shadow-sm">
                            <i class="bi bi-shield-shaded me-1"></i> Super Admin
                        </a>
                    <?php elseif (is_admin()): ?>
                        <a href="<?= BASE_URL ?>/admin/dashboard.php" class="btn btn-dark btn-sm rounded-pill px-3 fw-semibold">
                            <i class="bi bi-speedometer2 me-1"></i> Admin Panel
                        </a>
                    <?php else: ?>
                        <a href="<?= BASE_URL ?>/dashboard.php" class="btn btn-outline-primary btn-sm rounded-pill px-3 fw-semibold">
                            <i class="bi bi-grid-fill me-1"></i> My Dashboard
                        </a>
                    <?php endif; ?>

                    <div class="dropdown">
                        <button class="btn btn-light rounded-pill dropdown-toggle d-flex align-items-center gap-2 border shadow-sm px-2 py-1" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <?= render_user_avatar($user['profile_image'] ?? null, $user['name'] ?? 'User', 28) ?>
                            <span class="small fw-semibold text-dark d-none d-md-inline pe-1"><?= htmlspecialchars($user['name']) ?></span>
                            <?php if (is_superadmin()): ?>
                                <span class="badge bg-warning text-dark px-2 py-0" style="font-size:0.65rem;">SUPER</span>
                            <?php elseif (is_admin()): ?>
                                <span class="badge bg-info text-dark px-2 py-0" style="font-size:0.65rem;">ADMIN</span>
                            <?php endif; ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2 p-2 rounded-3" style="min-width: 230px;">
                            <li class="px-2 py-2 d-flex align-items-center gap-2 border-bottom mb-2">
                                <?= render_user_avatar($user['profile_image'] ?? null, $user['name'] ?? 'User', 38) ?>
                                <div style="min-width: 0; flex-grow: 1;">
                                    <div class="fw-bold text-dark small text-truncate"><?= htmlspecialchars($user['name']) ?></div>
                                    <div class="text-muted small text-truncate" style="font-size: 0.72rem;"><?= htmlspecialchars($user['email']) ?></div>
                                </div>
                            </li>
                            <?php if (is_superadmin()): ?>
                                <li><a class="dropdown-item fw-semibold text-warning" href="<?= BASE_URL ?>/admin/manage-admins.php"><i class="bi bi-person-gear me-2"></i> Role Governance</a></li>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>/admin/dashboard.php"><i class="bi bi-speedometer2 me-2 text-muted"></i> Admin Console</a></li>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>/admin/manage-orders.php"><i class="bi bi-cart-check me-2 text-muted"></i> Orders &amp; QR Verify</a></li>
                            <?php elseif (is_admin()): ?>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>/admin/dashboard.php"><i class="bi bi-speedometer2 me-2 text-muted"></i> Admin Console</a></li>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>/admin/manage-orders.php"><i class="bi bi-cart-check me-2 text-muted"></i> Orders &amp; QR Verify</a></li>
                            <?php else: ?>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>/dashboard.php"><i class="bi bi-grid-fill me-2 text-muted"></i> Student Portal</a></li>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>/dashboard.php#profile-pane"><i class="bi bi-person-bounding-box me-2 text-muted"></i> Profile &amp; Picture</a></li>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>/dashboard.php#review-pane"><i class="bi bi-star-fill me-2 text-warning"></i> Write Review</a></li>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>/dashboard.php#downloads-pane"><i class="bi bi-download me-2 text-muted"></i> My Downloads</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider my-2"></li>
                            <li><a class="dropdown-item text-danger fw-semibold" href="<?= BASE_URL ?>/logout.php"><i class="bi bi-box-arrow-right me-2"></i> Sign Out</a></li>
                        </ul>
                    </div>

                <?php else: ?>
                    <a href="<?= BASE_URL ?>/login.php" class="btn btn-hub-primary btn-sm rounded-pill px-4 fw-semibold shadow-sm" style="font-size: 16px;">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Login
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
