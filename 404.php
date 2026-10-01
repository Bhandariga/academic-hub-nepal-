<?php
/**
 * AcademicHub Nepal - 404 Page Not Found
 * File: 404.php
 */
http_response_code(404);
$page_title = "404 - Page Not Found | AcademicHub Nepal";
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5 my-md-4">
    <div class="row justify-content-center text-center">
        <div class="col-lg-6 col-md-8">
            <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white">
                <div class="display-1 fw-bold text-primary mb-2" style="font-size: 5rem; letter-spacing: -0.05em;">
                    404
                </div>
                <div class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-3 py-2 rounded-pill fw-bold mx-auto mb-3" style="width: fit-content;">
                    <i class="bi bi-exclamation-octagon-fill me-1"></i> Page or Document Not Found
                </div>
                <h1 class="fw-bold text-dark mb-3">A small detour in your learning journey.</h1>
                <p class="text-muted small mb-4">
                    The academic resource, campus page, or link you requested may have been moved, archived, or is temporarily unavailable.
                </p>

                <!-- Search Recovery Form -->
                <form action="<?= BASE_URL ?>/resources.php" method="GET" class="mb-4">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control bg-light border-start-0 ps-0" aria-label="Search academic resources" placeholder="Search BBS, Finance, Thesis..." required>
                        <button class="btn btn-hub-primary px-3" type="submit">Search</button>
                    </div>
                </form>

                <div class="d-flex flex-wrap justify-content-center gap-2">
                    <a href="<?= BASE_URL ?>/index.php" class="btn btn-outline-secondary rounded-pill px-4 btn-sm">
                        <i class="bi bi-house-door me-1"></i> Home Page
                    </a>
                    <a href="<?= BASE_URL ?>/resources.php" class="btn btn-hub-primary rounded-pill px-4 btn-sm">
                        <i class="bi bi-journal-text me-1"></i> Browse Catalog
                    </a>
                    <a href="<?= BASE_URL ?>/dashboard.php" class="btn btn-light border rounded-pill px-4 btn-sm">
                        <i class="bi bi-grid-fill me-1"></i> My Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
