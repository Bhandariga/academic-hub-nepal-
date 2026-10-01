<?php
/**
 * AcademicHub Nepal - CMS Content Management
 * Complete control over client-facing website content
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth_check.php';

require_admin();
require_admin_permission('manage_resources');

$active_page = 'cms-content';
$msg = '';
$status = '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        set_flash_message('danger', 'Security token expired. Please try again.');
        header('Location: ' . BASE_URL . '/admin/cms-content.php');
        exit;
    }

    $action = $_POST['action'] ?? '';

    // Update content section
    if ($action === 'update_section') {
        try {
            $id = (int)($_POST['id'] ?? 0);
            $title = trim($_POST['title'] ?? '');
            $subtitle = trim($_POST['subtitle'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $button_text = trim($_POST['button_text'] ?? '');
            $button_link = trim($_POST['button_link'] ?? '');
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            $display_order = (int)($_POST['display_order'] ?? 0);

            $stmt = $pdo->prepare("UPDATE content_sections SET title = :title, subtitle = :subtitle, description = :description, button_text = :btn_text, button_link = :btn_link, is_active = :is_active, display_order = :display_order WHERE id = :id");
            $stmt->execute([
                ':title' => $title,
                ':subtitle' => $subtitle,
                ':description' => $description,
                ':btn_text' => $button_text,
                ':btn_link' => $button_link,
                ':is_active' => $is_active,
                ':display_order' => $display_order,
                ':id' => $id
            ]);

            log_admin_activity($pdo, 'UPDATE_CMS_SECTION', "Updated content section ID {$id}");
            set_flash_message('success', 'Content section updated successfully!');
        } catch (Exception $e) {
            set_flash_message('danger', 'Failed to update section: ' . safe_error_message($e));
        }
        header('Location: ' . BASE_URL . '/admin/cms-content.php');
        exit;
    }

    // Update campus section
    if ($action === 'update_campus') {
        try {
            $id = (int)($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $website_url = trim($_POST['website_url'] ?? '');
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            $display_order = (int)($_POST['display_order'] ?? 0);

            $stmt = $pdo->prepare("UPDATE campus_sections SET name = :name, description = :description, website_url = :website_url, is_active = :is_active, display_order = :display_order WHERE id = :id");
            $stmt->execute([
                ':name' => $name,
                ':description' => $description,
                ':website_url' => $website_url,
                ':is_active' => $is_active,
                ':display_order' => $display_order,
                ':id' => $id
            ]);

            log_admin_activity($pdo, 'UPDATE_CAMPUS_SECTION', "Updated campus section ID {$id}");
            set_flash_message('success', 'Campus section updated successfully!');
        } catch (Exception $e) {
            set_flash_message('danger', 'Failed to update campus: ' . safe_error_message($e));
        }
        header('Location: ' . BASE_URL . '/admin/cms-content.php');
        exit;
    }

    // Add new campus
    if ($action === 'add_campus') {
        try {
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $website_url = trim($_POST['website_url'] ?? '');

            $stmt = $pdo->prepare("INSERT INTO campus_sections (name, description, website_url, display_order, is_active) VALUES (:name, :description, :website_url, 0, 1)");
            $stmt->execute([
                ':name' => $name,
                ':description' => $description,
                ':website_url' => $website_url
            ]);

            log_admin_activity($pdo, 'ADD_CAMPUS_SECTION', "Added new campus: {$name}");
            set_flash_message('success', 'Campus added successfully!');
        } catch (Exception $e) {
            set_flash_message('danger', 'Failed to add campus: ' . safe_error_message($e));
        }
        header('Location: ' . BASE_URL . '/admin/cms-content.php');
        exit;
    }

    // Delete campus
    if ($action === 'delete_campus') {
        try {
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $pdo->prepare("DELETE FROM campus_sections WHERE id = :id");
            $stmt->execute([':id' => $id]);

            log_admin_activity($pdo, 'DELETE_CAMPUS_SECTION', "Deleted campus section ID {$id}");
            set_flash_message('success', 'Campus deleted successfully!');
        } catch (Exception $e) {
            set_flash_message('danger', 'Failed to delete campus: ' . safe_error_message($e));
        }
        header('Location: ' . BASE_URL . '/admin/cms-content.php');
        exit;
    }

    // Update program section
    if ($action === 'update_program') {
        try {
            $id = (int)($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $icon = trim($_POST['icon'] ?? '');
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            $display_order = (int)($_POST['display_order'] ?? 0);

            $stmt = $pdo->prepare("UPDATE program_sections SET name = :name, description = :description, icon = :icon, is_active = :is_active, display_order = :display_order WHERE id = :id");
            $stmt->execute([
                ':name' => $name,
                ':description' => $description,
                ':icon' => $icon,
                ':is_active' => $is_active,
                ':display_order' => $display_order,
                ':id' => $id
            ]);

            log_admin_activity($pdo, 'UPDATE_PROGRAM_SECTION', "Updated program section ID {$id}");
            set_flash_message('success', 'Program section updated successfully!');
        } catch (Exception $e) {
            set_flash_message('danger', 'Failed to update program: ' . safe_error_message($e));
        }
        header('Location: ' . BASE_URL . '/admin/cms-content.php');
        exit;
    }

    // Add new program
    if ($action === 'add_program') {
        try {
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $icon = trim($_POST['icon'] ?? '');

            $stmt = $pdo->prepare("INSERT INTO program_sections (name, description, icon, display_order, is_active) VALUES (:name, :description, :icon, 0, 1)");
            $stmt->execute([
                ':name' => $name,
                ':description' => $description,
                ':icon' => $icon
            ]);

            log_admin_activity($pdo, 'ADD_PROGRAM_SECTION', "Added new program: {$name}");
            set_flash_message('success', 'Program added successfully!');
        } catch (Exception $e) {
            set_flash_message('danger', 'Failed to add program: ' . safe_error_message($e));
        }
        header('Location: ' . BASE_URL . '/admin/cms-content.php');
        exit;
    }

    // Delete program
    if ($action === 'delete_program') {
        try {
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $pdo->prepare("DELETE FROM program_sections WHERE id = :id");
            $stmt->execute([':id' => $id]);

            log_admin_activity($pdo, 'DELETE_PROGRAM_SECTION', "Deleted program section ID {$id}");
            set_flash_message('success', 'Program deleted successfully!');
        } catch (Exception $e) {
            set_flash_message('danger', 'Failed to delete program: ' . safe_error_message($e));
        }
        header('Location: ' . BASE_URL . '/admin/cms-content.php');
        exit;
    }
}

// Fetch all CMS data
try {
    $content_sections = $pdo->query("SELECT * FROM content_sections ORDER BY display_order ASC")->fetchAll();
    $campus_sections = $pdo->query("SELECT * FROM campus_sections ORDER BY display_order ASC")->fetchAll();
    $program_sections = $pdo->query("SELECT * FROM program_sections ORDER BY display_order ASC")->fetchAll();
    $testimonials = $pdo->query("SELECT * FROM testimonials ORDER BY display_order ASC")->fetchAll();
    $faq_items = $pdo->query("SELECT * FROM faq_items ORDER BY display_order ASC")->fetchAll();
    $notices = $pdo->query("SELECT * FROM notices ORDER BY created_at DESC")->fetchAll();
    $services = $pdo->query("SELECT * FROM services ORDER BY display_order ASC")->fetchAll();
    $pricing_plans = $pdo->query("SELECT * FROM pricing_plans ORDER BY display_order ASC")->fetchAll();
} catch (Exception $e) {
    die("Error loading CMS data: " . safe_error_message($e));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CMS Content Management | AcademicHub Nepal</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css?v=<?= time() ?>" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/design-system.css?v=3" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/experience.css?v=4" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/studio.css?v=1" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/platform.css?v=2" rel="stylesheet">
    <script src="<?= BASE_URL ?>/assets/js/theme.js?v=1"></script>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"><link href="<?= BASE_URL ?>/assets/css/admin-enterprise.css?v=1" rel="stylesheet"><script src="<?= BASE_URL ?>/assets/js/admin-ui.js?v=1" defer></script>
</head>
<body class="hub-admin bg-light">

<div class="container-fluid p-0">
    <div class="row g-0">
        <!-- Sidebar Navigation -->
        <?php $active_page = 'cms-content'; require_once __DIR__ . '/sidebar.php'; ?>

        <!-- Main Content Area -->
        <div class="col-md-9 col-lg-10 p-3 p-md-4 p-xl-5">
            
            <!-- Breadcrumb & Top Bar -->
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-3 border-bottom gap-3">
                <div>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-1 small text-muted">
                            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/dashboard.php">Dashboard</a></li>
                            <li class="breadcrumb-item active" aria-current="page">CMS Content Management</li>
                        </ol>
                    </nav>
                    <h2 class="h3 fw-bold text-dark mb-0">
                        <i class="bi bi-gear-fill text-primary me-2"></i> Complete CMS Control
                    </h2>
                    <p class="text-muted small mb-0">Manage all client-facing website content without editing code</p>
                </div>
            </div>

            <!-- Flash Alert Notice -->
            <?php display_flash_message(); ?>

            <!-- CMS Navigation Tabs -->
            <ul class="nav nav-tabs mb-4" id="cmsTabs" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" id="hero-tab" data-bs-toggle="tab" data-bs-target="#tab-hero" type="button">
                        <i class="bi bi-house-door me-1"></i> Hero & Sections
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="campus-tab" data-bs-toggle="tab" data-bs-target="#tab-campus" type="button">
                        <i class="bi bi-buildings me-1"></i> Campuses
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="program-tab" data-bs-toggle="tab" data-bs-target="#tab-program" type="button">
                        <i class="bi bi-mortarboard me-1"></i> Programs
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="testimonials-tab" data-bs-toggle="tab" data-bs-target="#tab-testimonials" type="button">
                        <i class="bi bi-chat-quote me-1"></i> Testimonials
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="faq-tab" data-bs-toggle="tab" data-bs-target="#tab-faq" type="button">
                        <i class="bi bi-question-circle me-1"></i> FAQ
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="notices-tab" data-bs-toggle="tab" data-bs-target="#tab-notices" type="button">
                        <i class="bi bi-megaphone me-1"></i> Notices
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="services-tab" data-bs-toggle="tab" data-bs-target="#tab-services" type="button">
                        <i class="bi bi-gear me-1"></i> Services
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="pricing-tab" data-bs-toggle="tab" data-bs-target="#tab-pricing" type="button">
                        <i class="bi bi-cash-stack me-1"></i> Pricing
                    </button>
                </li>
            </ul>

            <!-- Tab Content -->
            <div class="tab-content" id="cmsTabContent">
                
                <!-- HERO & SECTIONS TAB -->
                <div class="tab-pane fade show active" id="tab-hero" role="tabpanel">
                    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                        <h5 class="fw-bold text-dark mb-4"><i class="bi bi-house-door-fill text-primary me-2"></i>Homepage Sections</h5>
                        
                        <?php foreach ($content_sections as $section): ?>
                            <div class="border rounded-3 p-4 mb-3 <?= $section['is_active'] ? 'border-success' : 'border-secondary' ?>">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <h6 class="fw-bold text-dark mb-1"><?= htmlspecialchars($section['section_name']) ?></h6>
                                        <span class="badge bg-light text-muted border small"><?= htmlspecialchars($section['section_key']) ?></span>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="active_<?= $section['id'] ?>" <?= $section['is_active'] ? 'checked' : '' ?> disabled>
                                        <label class="form-check-label small" for="active_<?= $section['id'] ?>">
                                            <?= $section['is_active'] ? 'Active' : 'Hidden' ?>
                                        </label>
                                    </div>
                                </div>
                                
                                <form action="" method="POST">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="update_section">
                                    <input type="hidden" name="id" value="<?= $section['id'] ?>">
                                    
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label small fw-bold text-muted">Title</label>
                                            <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($section['title'] ?? '') ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small fw-bold text-muted">Subtitle</label>
                                            <input type="text" name="subtitle" class="form-control" value="<?= htmlspecialchars($section['subtitle'] ?? '') ?>">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label small fw-bold text-muted">Description</label>
                                            <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($section['description'] ?? '') ?></textarea>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small fw-bold text-muted">Button Text</label>
                                            <input type="text" name="button_text" class="form-control" value="<?= htmlspecialchars($section['button_text'] ?? '') ?>">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small fw-bold text-muted">Button Link</label>
                                            <input type="text" name="button_link" class="form-control" value="<?= htmlspecialchars($section['button_link'] ?? '') ?>">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label small fw-bold text-muted">Display Order</label>
                                            <input type="number" name="display_order" class="form-control" value="<?= $section['display_order'] ?>">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label small fw-bold text-muted">Status</label>
                                            <select name="is_active" class="form-select">
                                                <option value="1" <?= $section['is_active'] ? 'selected' : '' ?>>Active</option>
                                                <option value="0" <?= !$section['is_active'] ? 'selected' : '' ?>>Hidden</option>
                                            </select>
                                        </div>
                                        <div class="col-12">
                                            <button type="submit" class="btn btn-primary btn-sm">
                                                <i class="bi bi-check-circle me-1"></i> Update Section
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- CAMPUSES TAB -->
                <div class="tab-pane fade" id="tab-campus" role="tabpanel">
                    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="fw-bold text-dark mb-0"><i class="bi bi-buildings text-primary me-2"></i>Manage Campuses</h5>
                            <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addCampusModal">
                                <i class="bi bi-plus-circle me-1"></i> Add Campus
                            </button>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Order</th>
                                        <th>Campus Name</th>
                                        <th>Description</th>
                                        <th>Website</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($campus_sections as $campus): ?>
                                        <tr>
                                            <td><?= $campus['display_order'] ?></td>
                                            <td><strong><?= htmlspecialchars($campus['name']) ?></strong></td>
                                            <td><?= htmlspecialchars(substr($campus['description'] ?? '', 0, 50)) ?>...</td>
                                            <td><?= htmlspecialchars($campus['website_url'] ?? '-') ?></td>
                                            <td>
                                                <span class="badge <?= $campus['is_active'] ? 'bg-success' : 'bg-secondary' ?>">
                                                    <?= $campus['is_active'] ? 'Active' : 'Hidden' ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="editCampus(<?= htmlspecialchars(json_encode($campus)) ?>)">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <form action="" method="POST" class="d-inline" onsubmit="return confirm('Delete this campus?');">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="action" value="delete_campus">
                                                    <input type="hidden" name="id" value="<?= $campus['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- PROGRAMS TAB -->
                <div class="tab-pane fade" id="tab-program" role="tabpanel">
                    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="fw-bold text-dark mb-0"><i class="bi bi-mortarboard text-primary me-2"></i>Manage Programs</h5>
                            <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addProgramModal">
                                <i class="bi bi-plus-circle me-1"></i> Add Program
                            </button>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Order</th>
                                        <th>Program Name</th>
                                        <th>Icon</th>
                                        <th>Description</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($program_sections as $program): ?>
                                        <tr>
                                            <td><?= $program['display_order'] ?></td>
                                            <td><strong><?= htmlspecialchars($program['name']) ?></strong></td>
                                            <td><i class="bi <?= htmlspecialchars($program['icon'] ?? 'bi-book') ?>"></i></td>
                                            <td><?= htmlspecialchars(substr($program['description'] ?? '', 0, 50)) ?>...</td>
                                            <td>
                                                <span class="badge <?= $program['is_active'] ? 'bg-success' : 'bg-secondary' ?>">
                                                    <?= $program['is_active'] ? 'Active' : 'Hidden' ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="editProgram(<?= htmlspecialchars(json_encode($program)) ?>)">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <form action="" method="POST" class="d-inline" onsubmit="return confirm('Delete this program?');">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="action" value="delete_program">
                                                    <input type="hidden" name="id" value="<?= $program['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- TESTIMONIALS TAB -->
                <div class="tab-pane fade" id="tab-testimonials" role="tabpanel">
                    <div class="card border-0 shadow-sm rounded-4 p-4">
                        <h5 class="fw-bold text-dark mb-4"><i class="bi bi-chat-quote text-primary me-2"></i>Student Testimonials</h5>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            Testimonials are managed through the Reviews system. Go to <a href="<?= BASE_URL ?>/admin/manage-students.php">Manage Students</a> to approve and manage student reviews.
                        </div>
                    </div>
                </div>

                <!-- FAQ TAB -->
                <div class="tab-pane fade" id="tab-faq" role="tabpanel">
                    <div class="card border-0 shadow-sm rounded-4 p-4">
                        <h5 class="fw-bold text-dark mb-4"><i class="bi bi-question-circle text-primary me-2"></i>FAQ Management</h5>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            FAQ items are displayed on the website. Add, edit, or delete frequently asked questions here.
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Question</th>
                                        <th>Category</th>
                                        <th>Order</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($faq_items as $faq): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($faq['question']) ?></td>
                                            <td><?= htmlspecialchars($faq['category'] ?? 'General') ?></td>
                                            <td><?= $faq['display_order'] ?></td>
                                            <td>
                                                <span class="badge <?= $faq['is_active'] ? 'bg-success' : 'bg-secondary' ?>">
                                                    <?= $faq['is_active'] ? 'Active' : 'Hidden' ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-pencil"></i> Edit
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- NOTICES TAB -->
                <div class="tab-pane fade" id="tab-notices" role="tabpanel">
                    <div class="card border-0 shadow-sm rounded-4 p-4">
                        <h5 class="fw-bold text-dark mb-4"><i class="bi bi-megaphone text-primary me-2"></i>Notices & Announcements</h5>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            Manage notices and announcements displayed to students. Create urgent alerts, general notices, or important announcements.
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Title</th>
                                        <th>Type</th>
                                        <th>Priority</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($notices as $notice): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($notice['title']) ?></td>
                                            <td>
                                                <span class="badge bg-light text-dark border"><?= htmlspecialchars($notice['type']) ?></span>
                                            </td>
                                            <td>
                                                <span class="badge 
                                                    <?= $notice['priority'] === 'urgent' ? 'bg-danger' : 
                                                       ($notice['priority'] === 'high' ? 'bg-warning' : 
                                                       ($notice['priority'] === 'medium' ? 'bg-info' : 'bg-secondary')) ?>">
                                                    <?= htmlspecialchars($notice['priority']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge <?= $notice['is_active'] ? 'bg-success' : 'bg-secondary' ?>">
                                                    <?= $notice['is_active'] ? 'Active' : 'Hidden' ?>
                                                </span>
                                            </td>
                                            <td><?= date('M d, Y', strtotime($notice['created_at'])) ?></td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-pencil"></i> Edit
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- SERVICES TAB -->
                <div class="tab-pane fade" id="tab-services" role="tabpanel">
                    <div class="card border-0 shadow-sm rounded-4 p-4">
                        <h5 class="fw-bold text-dark mb-4"><i class="bi bi-gear text-primary me-2"></i>Services Management</h5>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            Manage services offered to students (custom writing, formatting, consultation, etc.).
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Service Title</th>
                                        <th>Icon</th>
                                        <th>Price</th>
                                        <th>Order</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($services as $service): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($service['title']) ?></td>
                                            <td><i class="bi <?= htmlspecialchars($service['icon'] ?? 'bi-gear') ?>"></i></td>
                                            <td><?= $service['price'] ? format_npr($service['price']) : 'N/A' ?></td>
                                            <td><?= $service['display_order'] ?></td>
                                            <td>
                                                <span class="badge <?= $service['is_active'] ? 'bg-success' : 'bg-secondary' ?>">
                                                    <?= $service['is_active'] ? 'Active' : 'Hidden' ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-pencil"></i> Edit
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- PRICING TAB -->
                <div class="tab-pane fade" id="tab-pricing" role="tabpanel">
                    <div class="card border-0 shadow-sm rounded-4 p-4">
                        <h5 class="fw-bold text-dark mb-4"><i class="bi bi-cash-stack text-primary me-2"></i>Pricing Plans</h5>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            Manage pricing plans and subscription options for students.
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Plan Name</th>
                                        <th>Price</th>
                                        <th>Duration</th>
                                        <th>Popular</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pricing_plans as $plan): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($plan['plan_name']) ?></td>
                                            <td><?= $plan['price'] ? format_npr($plan['price']) : 'Variable' ?></td>
                                            <td><?= htmlspecialchars($plan['duration'] ?? 'N/A') ?></td>
                                            <td>
                                                <?php if ($plan['is_popular']): ?>
                                                    <span class="badge bg-warning text-dark"><i class="bi bi-star-fill me-1"></i>Popular</span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge <?= $plan['is_active'] ? 'bg-success' : 'bg-secondary' ?>">
                                                    <?= $plan['is_active'] ? 'Active' : 'Hidden' ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-pencil"></i> Edit
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- Add Campus Modal -->
<div class="modal fade" id="addCampusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Campus</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="add_campus">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Campus Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Website URL</label>
                        <input type="url" name="website_url" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Add Campus</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Program Modal -->
<div class="modal fade" id="addProgramModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Program</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="add_program">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Program Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Icon (Bootstrap Icons class)</label>
                        <input type="text" name="icon" class="form-control" placeholder="bi-mortarboard">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Add Program</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function editCampus(campus) {
    // Implement edit campus functionality
    console.log('Edit campus:', campus);
}

function editProgram(program) {
    // Implement edit program functionality
    console.log('Edit program:', program);
}
</script>
<script src="<?= BASE_URL ?>/assets/js/experience.js?v=5"></script>
</body>
</html>
