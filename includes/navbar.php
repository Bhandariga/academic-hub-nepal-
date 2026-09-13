<?php
/**
 * AcademicHub Nepal - Navbar Component
 */
$isLoggedIn = is_logged_in();
$user = get_current_user_data();
?>
<nav class="navbar navbar-expand-lg navbar-hub sticky-top">
    <div class="container">
        <!-- Brand Logo -->
        <a class="navbar-brand navbar-brand-hub" href="<?= BASE_URL ?>/index.php">
            <div class="hub-icon">
                <i class="bi bi-mortarboard-fill"></i>
            </div>
            <span>AcademicHub <span class="text-primary" style="color: var(--secondary-color) !important;">Nepal</span></span>
        </a>

        <!-- Mobile Toggle Button -->
        <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navbar Links -->
        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-3">
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'index.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/index.php">
                        <i class="bi bi-house-door me-1"></i> Home
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'resources.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/resources.php">
                        <i class="bi bi-journal-text me-1"></i> Resources
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>/index.php#campuses">
                        <i class="bi bi-buildings me-1"></i> Campuses
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>/index.php#programs">
                        <i class="bi bi-mortarboard me-1"></i> Programs
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'services.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/services.php">
                        <i class="bi bi-layers me-1"></i> Services
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'about.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/about.php">
                        <i class="bi bi-info-circle me-1"></i> About
                    </a>
                </li>
            </ul>

            <!-- Search Quick Link & Auth Actions -->
            <div class="d-flex align-items-center gap-2">
                <?php if ($isLoggedIn): ?>
                    <?php if (is_admin()): ?>
                        <a href="<?= BASE_URL ?>/admin/dashboard.php" class="btn btn-dark btn-sm rounded-pill px-3 fw-semibold">
                            <i class="bi bi-speedometer2 me-1"></i> Admin Panel
                        </a>
                    <?php else: ?>
                        <a href="<?= BASE_URL ?>/dashboard.php" class="btn btn-outline-primary btn-sm rounded-pill px-3 fw-semibold">
                            <i class="bi bi-grid-fill me-1"></i> My Dashboard
                        </a>
                    <?php endif; ?>

                    <div class="dropdown">
                        <button class="btn btn-light rounded-pill dropdown-toggle d-flex align-items-center gap-2 border" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle text-primary fs-5"></i>
                            <span class="small fw-semibold text-dark d-none d-md-inline"><?= htmlspecialchars($user['name']) ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2">
                            <li><h6 class="dropdown-header text-truncate"><?= htmlspecialchars($user['email']) ?></h6></li>
                            <?php if (!is_admin()): ?>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>/dashboard.php"><i class="bi bi-box-seam me-2 text-muted"></i> My Orders</a></li>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>/dashboard.php#downloads"><i class="bi bi-download me-2 text-muted"></i> My Downloads</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>/logout.php"><i class="bi bi-box-arrow-right me-2"></i> Logout</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>/login.php" class="btn btn-outline-secondary btn-sm px-3 rounded-pill fw-semibold">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Login
                    </a>
                    <a href="<?= BASE_URL ?>/register.php" class="btn btn-hub-primary btn-sm rounded-pill px-3">
                        <i class="bi bi-person-plus-fill me-1"></i> Register
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
