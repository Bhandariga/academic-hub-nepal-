<?php
/**
 * AcademicHub Nepal - Executive Team Members Management Console (CRUD)
 * File: admin/manage-team.php
 * Strictly restricted to Super Administrators & Authorized Admins
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth_check.php';
require_once __DIR__ . '/../config/team_media.php';

// Strict Admin Access Guard
require_admin();

if (!admin_has_permission('manage_team')) {
    set_flash_message('danger', 'Access to Team Management is restricted to Super Administrators and authorized administrators.');
    header('Location: ' . BASE_URL . '/admin/dashboard.php');
    exit;
}

$is_sa = is_superadmin();
$current_admin_id = $_SESSION['user_id'];
$active_page = 'manage-team';

$uploadDir = dirname(__DIR__) . '/uploads/team/';
if (!file_exists($uploadDir)) {
    @mkdir($uploadDir, 0777, true);
}

// ---------------------------------------------------------
// POST ACTION HANDLER (CRUD)
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        set_flash_message('danger', 'Security validation token expired. Please try again.');
        header('Location: ' . BASE_URL . '/admin/manage-team.php');
        exit;
    }

    $action = $_POST['action'] ?? '';

    // ================= 1. CREATE TEAM MEMBER =================
    if ($action === 'create_member') {
        $name = trim($_POST['name'] ?? '');
        $role_title = trim($_POST['role_title'] ?? '');
        $department = trim($_POST['department'] ?? '');
        $category = in_array($_POST['category'] ?? '', team_categories(), true) ? $_POST['category'] : 'Academic Advisors';
        foreach (['linkedin_url','facebook_url','twitter_url'] as $linkField) {
            if (!empty($_POST[$linkField]) && content_url($_POST[$linkField]) === '') { set_flash_message('danger','Social links must use http or https.'); header('Location: '.BASE_URL.'/admin/manage-team.php'); exit; }
        }
        $bio = trim($_POST['bio'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $linkedin_url = trim($_POST['linkedin_url'] ?? '');
        $facebook_url = trim($_POST['facebook_url'] ?? '');
        $twitter_url = trim($_POST['twitter_url'] ?? '');
        $display_order = (int)($_POST['display_order'] ?? 0);
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if (empty($name) || empty($role_title)) {
            set_flash_message('danger', 'Full Name and Role / Designation are required fields.');
            header('Location: ' . BASE_URL . '/admin/manage-team.php');
            exit;
        }

        $photoFilename = null;

        try { $photoFilename = selected_team_photo($pdo, $name, null); }
        catch (Throwable $e) { error_log($e->getMessage()); set_flash_message('danger','Unable to save photo. Check format and size.'); header('Location: '.BASE_URL.'/admin/manage-team.php'); exit; }

        try {
            $stmt = $pdo->prepare("
                INSERT INTO team_members 
                (name, role_title, department, category, bio, photo, email, phone, linkedin_url, facebook_url, twitter_url, display_order, is_active, created_at)
                VALUES 
                (:name, :role, :dept, :category, :bio, :photo, :email, :phone, :linkedin, :facebook, :twitter, :order, :active, NOW())
            ");
            $stmt->execute([
                ':name' => $name,
                ':role' => $role_title,
                ':dept' => $department,
                ':category' => $category,
                ':bio' => $bio,
                ':photo' => $photoFilename,
                ':email' => $email,
                ':phone' => $phone,
                ':linkedin' => $linkedin_url,
                ':facebook' => $facebook_url,
                ':twitter' => $twitter_url,
                ':order' => $display_order,
                ':active' => $is_active
            ]);

            $newId = $pdo->lastInsertId();

            // Log activity
            try {
                $pdo->prepare("
                    INSERT INTO admin_activity_logs (admin_id, action, details, target_type, target_id, ip_address, created_at)
                    VALUES (:aid, 'create_team_member', :details, 'team_members', :tid, :ip, NOW())
                ")->execute([
                    ':aid' => $current_admin_id,
                    ':details' => "Added team member '{$name}' as {$role_title}",
                    ':tid' => $newId,
                    ':ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
                ]);
            } catch (Exception $e) {}

            set_flash_message('success', "Team member '{$name}' has been added successfully!");
        } catch (Exception $e) {
            set_flash_message('danger', 'Database error adding team member: ' . safe_error_message($e));
        }

        header('Location: ' . BASE_URL . '/admin/manage-team.php');
        exit;
    }

    // ================= 2. UPDATE TEAM MEMBER =================
    if ($action === 'update_member') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $role_title = trim($_POST['role_title'] ?? '');
        $department = trim($_POST['department'] ?? '');
        $category = in_array($_POST['category'] ?? '', team_categories(), true) ? $_POST['category'] : 'Academic Advisors';
        foreach (['linkedin_url','facebook_url','twitter_url'] as $linkField) {
            if (!empty($_POST[$linkField]) && content_url($_POST[$linkField]) === '') { set_flash_message('danger','Social links must use http or https.'); header('Location: '.BASE_URL.'/admin/manage-team.php'); exit; }
        }
        $bio = trim($_POST['bio'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $linkedin_url = trim($_POST['linkedin_url'] ?? '');
        $facebook_url = trim($_POST['facebook_url'] ?? '');
        $twitter_url = trim($_POST['twitter_url'] ?? '');
        $display_order = (int)($_POST['display_order'] ?? 0);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $remove_photo = isset($_POST['remove_photo']);

        if ($id <= 0 || empty($name) || empty($role_title)) {
            set_flash_message('danger', 'Valid Member ID, Full Name, and Role Title are required.');
            header('Location: ' . BASE_URL . '/admin/manage-team.php');
            exit;
        }

        try {
            $stmt = $pdo->prepare("SELECT photo FROM team_members WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $currentPhoto = $stmt->fetchColumn();

            $photoFilename = $currentPhoto;

            // Remove existing photo if requested
            if ($remove_photo) {
                if (!empty($currentPhoto) && file_exists($uploadDir . $currentPhoto)) {
                    @unlink($uploadDir . $currentPhoto);
                }
                $photoFilename = null;
            }

            $photoFilename = selected_team_photo($pdo, $name, $photoFilename);

            $update = $pdo->prepare('UPDATE team_members SET name=:name, role_title=:role, department=:department, category=:category, bio=:bio, photo=:photo, email=:email, phone=:phone, linkedin_url=:linkedin, facebook_url=:facebook, twitter_url=:twitter, display_order=:position, is_active=:active WHERE id=:id');
            $update->execute([':name'=>$name, ':role'=>$role_title, ':department'=>$department,
                ':category'=>$category, ':bio'=>$bio, ':photo'=>$photoFilename, ':email'=>$email,
                ':phone'=>$phone, ':linkedin'=>$linkedin_url, ':facebook'=>$facebook_url,
                ':twitter'=>$twitter_url, ':position'=>$display_order, ':active'=>$is_active, ':id'=>$id]);
            log_admin_activity($pdo,'update_team_member','Updated team profile','team_members',$id);
            set_flash_message('success','Team member updated successfully.');
        } catch (Exception $e) {
            error_log($e->getMessage());
            set_flash_message('danger','Unable to update this profile. Check the form and photo.');
        }
        header('Location: '.BASE_URL.'/admin/manage-team.php'); exit;
    }

    // Existing deletion workflow remains separate from profile updates.
    if ($action === 'delete_member') {
        $id = (int)($_POST['id'] ?? 0);
        try {

            $stmt = $pdo->prepare("SELECT name, photo FROM team_members WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $member = $stmt->fetch();

            if ($member) {
                // Delete photo file
                if (!empty($member['photo']) && file_exists($uploadDir . $member['photo'])) {
                    @unlink($uploadDir . $member['photo']);
                }

                $delStmt = $pdo->prepare("DELETE FROM team_members WHERE id = :id");
                $delStmt->execute([':id' => $id]);

                // Log activity
                try {
                    $pdo->prepare("
                        INSERT INTO admin_activity_logs (admin_id, action, details, target_type, target_id, ip_address, created_at)
                        VALUES (:aid, 'delete_team_member', :details, 'team_members', :tid, :ip, NOW())
                    ")->execute([
                        ':aid' => $current_admin_id,
                        ':details' => "Deleted team member '{$member['name']}' (ID: {$id})",
                        ':tid' => $id,
                        ':ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
                    ]);
                } catch (Exception $e) {}

                set_flash_message('success', "Team member '{$member['name']}' was removed successfully.");
            } else {
                set_flash_message('warning', 'Member not found or already deleted.');
            }
        } catch (Exception $e) {
            set_flash_message('danger', 'Error deleting team member: ' . safe_error_message($e));
        }

        header('Location: ' . BASE_URL . '/admin/manage-team.php');
        exit;
    }

    // ================= 4. TOGGLE STATUS =================
    if ($action === 'toggle_status') {
        $id = (int)($_POST['id'] ?? 0);
        $new_status = (int)($_POST['status'] ?? 0);

        try {
            $upd = $pdo->prepare("UPDATE team_members SET is_active = :status WHERE id = :id");
            $upd->execute([':status' => $new_status, ':id' => $id]);
            set_flash_message('success', 'Member visibility updated.');
        } catch (Exception $e) {
            set_flash_message('danger', 'Failed to update visibility.');
        }

        header('Location: ' . BASE_URL . '/admin/manage-team.php');
        exit;
    }
}

// ---------------------------------------------------------
// QUERY DATA FOR VIEW
// ---------------------------------------------------------
try {
    $membersStmt = $pdo->query("SELECT * FROM team_members ORDER BY display_order ASC, id ASC");
    $teamMembers = $membersStmt->fetchAll();

    $totalCount = count($teamMembers);
    $activeCount = count(array_filter($teamMembers, fn($m) => (int)$m['is_active'] === 1));
    $advisorsCount = count(array_filter($teamMembers, fn($m) => stripos($m['role_title'], 'advisor') !== false || stripos($m['department'], 'university') !== false || stripos($m['role_title'], 'lecturer') !== false));
    $techCount = count(array_filter($teamMembers, fn($m) => stripos($m['role_title'], 'architect') !== false || stripos($m['role_title'], 'lead') !== false || stripos($m['role_title'], 'founder') !== false));
} catch (Exception $e) {
    $teamMembers = [];
    $totalCount = 0;
    $activeCount = 0;
    $advisorsCount = 0;
    $techCount = 0;
}

$page_title = "Manage Team Members | Admin Console";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar Inclusion -->
        <?php require_once __DIR__ . '/sidebar.php'; ?>

        <!-- Main Content Area -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            
            <!-- Breadcrumb & Top Bar -->
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
                <div>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-1 small">
                            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/dashboard.php">Dashboard</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Team Members</li>
                        </ol>
                    </nav>
                    <h1 class="h3 fw-bold text-dark mb-0">
                        <i class="bi bi-people-fill text-primary me-2"></i> Our Team &amp; Faculty Management
                    </h1>
                    <p class="text-muted small mb-0">Manage leadership, academic advisors, and mentors featured on the public "Our Team" page</p>
                </div>
                <div class="btn-toolbar mb-2 mb-md-0 gap-2">
                    <a href="<?= BASE_URL ?>/team.php" target="_blank" class="btn btn-outline-secondary btn-sm rounded-pill px-3 shadow-sm">
                        <i class="bi bi-eye me-1"></i> View Public Team Page
                    </a>
                    <button type="button" class="btn btn-hub-primary btn-sm rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#addMemberModal">
                        <i class="bi bi-person-plus-fill me-1"></i> Add New Team Member
                    </button>
                </div>
            </div>

            <!-- Flash Alert Messages -->
            <?php display_flash_message(); ?>

            <!-- Metric KPI Summary Cards -->
            <div class="row g-3 mb-4">
                <div class="col-sm-6 col-xl-3">
                    <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100 border-start border-primary border-4">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted small fw-semibold d-block">Total Members</span>
                                <h3 class="fw-bold mb-0 text-dark"><?= $totalCount ?></h3>
                            </div>
                            <div class="rounded-circle bg-primary bg-opacity-10 text-primary p-3 fs-4">
                                <i class="bi bi-people"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100 border-start border-success border-4">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted small fw-semibold d-block">Active On Public Site</span>
                                <h3 class="fw-bold mb-0 text-success"><?= $activeCount ?></h3>
                            </div>
                            <div class="rounded-circle bg-success bg-opacity-10 text-success p-3 fs-4">
                                <i class="bi bi-check-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100 border-start border-warning border-4">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted small fw-semibold d-block">Academic Advisors</span>
                                <h3 class="fw-bold mb-0 text-warning"><?= $advisorsCount ?></h3>
                            </div>
                            <div class="rounded-circle bg-warning bg-opacity-10 text-warning p-3 fs-4">
                                <i class="bi bi-mortarboard"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100 border-start border-info border-4">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted small fw-semibold d-block">Tech &amp; Operations</span>
                                <h3 class="fw-bold mb-0 text-info"><?= $techCount ?></h3>
                            </div>
                            <div class="rounded-circle bg-info bg-opacity-10 text-info p-3 fs-4">
                                <i class="bi bi-cpu"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Team Members Enterprise Table Card -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-5">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <h5 class="fw-bold mb-0 text-dark">Faculty &amp; Team Directory</h5>
                        <span class="badge bg-light text-dark border"><?= $totalCount ?> records</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <div class="input-group input-group-sm" style="width: 250px;">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-search"></i></span>
                            <input type="text" class="form-control bg-light border-start-0" placeholder="Search team member..." data-table-search="#teamTable">
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-enterprise table-hover align-middle mb-0" id="teamTable">
                        <thead class="bg-light">
                            <tr>
                                <th style="width: 60px;">Order</th>
                                <th style="width: 70px;">Photo</th>
                                <th>Name &amp; Role</th>
                                <th>Department / College</th>
                                <th>Contact &amp; Social</th>
                                <th>Status</th>
                                <th class="text-end" style="width: 140px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($teamMembers)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="bi bi-people fs-1 d-block mb-2 text-secondary"></i>
                                        No team members found. Click <strong>"Add New Team Member"</strong> to create one.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($teamMembers as $m): 
                                    $hasPhoto = !empty($m['photo']) && file_exists($uploadDir . $m['photo']);
                                    $initial = strtoupper(substr($m['name'], 0, 1));
                                ?>
                                    <tr>
                                        <!-- Display Order -->
                                        <td>
                                            <span class="badge bg-light text-dark border fw-bold px-2 py-1">
                                                #<?= (int)$m['display_order'] ?>
                                            </span>
                                        </td>

                                        <!-- Photo Thumbnail -->
                                        <td>
                                            <?php if ($hasPhoto): ?>
                                                <img src="<?= BASE_URL ?>/uploads/team/<?= htmlspecialchars($m['photo']) ?>" alt="<?= htmlspecialchars($m['name']) ?>" class="rounded-circle object-fit-cover shadow-sm border" style="width: 44px; height: 44px;">
                                            <?php else: ?>
                                                <div class="rounded-circle text-white d-flex align-items-center justify-content-center fw-bold shadow-sm" style="width: 44px; height: 44px; background: linear-gradient(135deg, #0f2942, #2563eb); font-size: 1rem;">
                                                    <?= $initial ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Name & Role -->
                                        <td>
                                            <div class="fw-bold text-dark mb-0"><?= htmlspecialchars($m['name']) ?></div>
                                            <div class="text-primary small fw-semibold"><?= htmlspecialchars($m['role_title']) ?></div>
                                            <?php if (!empty($m['bio'])): ?>
                                                <div class="text-muted small text-truncate" style="max-width: 320px; font-size: 0.78rem;">
                                                    <?= htmlspecialchars($m['bio']) ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Department / Affiliation -->
                                        <td>
                                            <?php if (!empty($m['department'])): ?>
                                                <span class="badge bg-light text-secondary border px-2 py-1" style="font-size:0.75rem;">
                                                    <i class="bi bi-building me-1"></i><?= htmlspecialchars($m['department']) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted small">—</span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Contact & Social -->
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <?php if (!empty($m['email'])): ?>
                                                    <a href="mailto:<?= htmlspecialchars($m['email']) ?>" class="btn btn-sm btn-light border p-1 rounded-circle text-muted" title="<?= htmlspecialchars($m['email']) ?>">
                                                        <i class="bi bi-envelope"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <?php if (!empty($m['phone'])): ?>
                                                    <a href="tel:<?= htmlspecialchars($m['phone']) ?>" class="btn btn-sm btn-light border p-1 rounded-circle text-muted" title="<?= htmlspecialchars($m['phone']) ?>">
                                                        <i class="bi bi-telephone"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <?php if (!empty($m['linkedin_url'])): ?>
                                                    <a href="<?= htmlspecialchars($m['linkedin_url']) ?>" target="_blank" class="btn btn-sm btn-light border p-1 rounded-circle text-primary" title="LinkedIn">
                                                        <i class="bi bi-linkedin"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <?php if (!empty($m['facebook_url'])): ?>
                                                    <a href="<?= htmlspecialchars($m['facebook_url']) ?>" target="_blank" class="btn btn-sm btn-light border p-1 rounded-circle text-primary" title="Facebook">
                                                        <i class="bi bi-facebook"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <?php if (!empty($m['twitter_url'])): ?>
                                                    <a href="<?= htmlspecialchars($m['twitter_url']) ?>" target="_blank" class="btn btn-sm btn-light border p-1 rounded-circle text-info" title="Twitter / X">
                                                        <i class="bi bi-twitter-x"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>

                                        <!-- Status with quick toggle -->
                                        <td>
                                            <form action="" method="POST" class="d-inline">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="id" value="<?= $m['id'] ?>">
                                                <input type="hidden" name="status" value="<?= (int)$m['is_active'] === 1 ? 0 : 1 ?>">
                                                <button type="submit" class="btn btn-sm border-0 p-0" title="Click to toggle visibility">
                                                    <?php if ((int)$m['is_active'] === 1): ?>
                                                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1 rounded-pill">
                                                            <i class="bi bi-check-circle-fill me-1"></i> Active
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary bg-opacity-10 text-secondary border px-2 py-1 rounded-pill">
                                                            <i class="bi bi-eye-slash-fill me-1"></i> Hidden
                                                        </span>
                                                    <?php endif; ?>
                                                </button>
                                            </form>
                                        </td>

                                        <!-- Actions -->
                                        <td class="text-end">
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-outline-primary" onclick="openEditModal(<?= htmlspecialchars(json_encode($m)) ?>)" title="Edit Member">
                                                    <i class="bi bi-pencil-square"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-danger" onclick="confirmDelete(<?= $m['id'] ?>, '<?= htmlspecialchars(addslashes($m['name'])) ?>')" title="Delete Member">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>
</div>

<!-- ================= MODAL: ADD TEAM MEMBER ================= -->
<div class="modal fade" id="addMemberModal" tabindex="-1" aria-labelledby="addMemberModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-primary text-white" style="background: linear-gradient(135deg, #0f2942 0%, #1e4976 100%) !important;">
                <h5 class="modal-title fw-bold" id="addMemberModalLabel">
                    <i class="bi bi-person-plus-fill me-2"></i> Add New Team Member
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="POST" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="create_member">

                <div class="modal-body p-4 bg-light">
                    <div class="row g-3">
<div class="col-md-6"><label class="form-label small fw-semibold">Team category<select name="category" class="form-select" ><?php foreach(team_categories() as $option): ?><option><?= htmlspecialchars($option) ?></option><?php endforeach; ?></select></label></div><?php team_media_fields($pdo); ?>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted">Full Name *</label>
                            <input type="text" name="name" class="form-control" placeholder="e.g. Prof. Dr. Ram Chandra Pokharel" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted">Role / Designation *</label>
                            <input type="text" name="role_title" class="form-control" placeholder="e.g. Chief Academic Advisor" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted">Department / College Affiliation</label>
                            <input type="text" name="department" class="form-control" placeholder="e.g. Faculty of Management, Tribhuvan University">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted">Display Priority Order</label>
                            <input type="number" name="display_order" class="form-control" value="<?= $totalCount + 1 ?>" min="0">
                            <div class="form-text small">Lower numbers appear first on the public website.</div>
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-semibold text-muted">Academic Bio &amp; Profile Summary</label>
                            <textarea name="bio" rows="3" class="form-control" placeholder="Brief summary of academic achievements, teaching background, and responsibilities..."></textarea>
                        </div>

                        <!-- Photo Upload with Preview -->
                        <div class="col-12">
                            <label class="form-label small fw-semibold text-muted">Profile Photo (Max 5MB, JPG/PNG/WEBP)</label>
                            <div class="d-flex align-items-center gap-3">
                                <div id="addPhotoPreviewContainer" class="rounded-circle border bg-white d-flex align-items-center justify-content-center shadow-sm" style="width: 60px; height: 60px; overflow: hidden; flex-shrink: 0;">
                                    <i class="bi bi-person text-secondary fs-3" id="addPhotoDefaultIcon"></i>
                                    <img id="addPhotoPreview" src="#" alt="Preview" class="w-100 h-100 object-fit-cover d-none">
                                </div>
                                <div class="flex-grow-1">
                                    <input data-optimize-image type="file" name="photo" id="addPhotoInput" class="form-control" accept="image/jpeg,image/png,image/webp" onchange="previewAddPhoto(this)">
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted">Official Email Address</label>
                            <input type="email" name="email" class="form-control" placeholder="advisor@academichub.edu.np">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted">Phone Number</label>
                            <input type="text" name="phone" class="form-control" placeholder="+977-98XXXXXXXX">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted">LinkedIn URL</label>
                            <input type="url" name="linkedin_url" class="form-control" placeholder="https://linkedin.com/in/...">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted">Facebook URL</label>
                            <input type="url" name="facebook_url" class="form-control" placeholder="https://facebook.com/...">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted">Twitter / X URL</label>
                            <input type="url" name="twitter_url" class="form-control" placeholder="https://twitter.com/...">
                        </div>

                        <div class="col-12">
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="is_active" id="addIsActive" value="1" checked>
                                <label class="form-check-label small fw-semibold text-dark" for="addIsActive">
                                    Publish directly on public "Our Team" website
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-white border-top">
                    <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-hub-primary btn-sm rounded-pill px-4 fw-semibold shadow-sm">
                        <i class="bi bi-check-lg me-1"></i> Save Team Member
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ================= MODAL: EDIT TEAM MEMBER ================= -->
<div class="modal fade" id="editMemberModal" tabindex="-1" aria-labelledby="editMemberModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold" id="editMemberModalLabel">
                    <i class="bi bi-pencil-square me-2 text-warning"></i> Edit Team Member
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="POST" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update_member">
                <input type="hidden" name="id" id="edit_id" value="">

                <div class="modal-body p-4 bg-light">
                    <div class="row g-3">
<div class="col-md-6"><label class="form-label small fw-semibold">Team category<select name="category" class="form-select" id="edit_category"><?php foreach(team_categories() as $option): ?><option><?= htmlspecialchars($option) ?></option><?php endforeach; ?></select></label></div><?php team_media_fields($pdo); ?>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted">Full Name *</label>
                            <input type="text" name="name" id="edit_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted">Role / Designation *</label>
                            <input type="text" name="role_title" id="edit_role_title" class="form-control" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted">Department / College Affiliation</label>
                            <input type="text" name="department" id="edit_department" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted">Display Priority Order</label>
                            <input type="number" name="display_order" id="edit_display_order" class="form-control" min="0">
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-semibold text-muted">Academic Bio &amp; Profile Summary</label>
                            <textarea name="bio" id="edit_bio" rows="3" class="form-control"></textarea>
                        </div>

                        <!-- Photo Edit / Replace -->
                        <div class="col-12">
                            <label class="form-label small fw-semibold text-muted">Current &amp; Replace Photo</label>
                            <div class="d-flex align-items-center gap-3">
                                <div id="editPhotoThumbContainer" class="rounded-circle border bg-white d-flex align-items-center justify-content-center shadow-sm" style="width: 60px; height: 60px; overflow: hidden; flex-shrink: 0;">
                                    <i class="bi bi-person text-secondary fs-3" id="editPhotoDefaultIcon"></i>
                                    <img id="editPhotoPreview" src="#" alt="Preview" class="w-100 h-100 object-fit-cover d-none">
                                </div>
                                <div class="flex-grow-1">
                                    <input data-optimize-image type="file" name="photo" id="editPhotoInput" class="form-control" accept="image/jpeg,image/png,image/webp" onchange="previewEditPhoto(this)">
                                    <div class="form-check mt-1" id="removePhotoWrapper">
                                        <input class="form-check-input" type="checkbox" name="remove_photo" id="edit_remove_photo" value="1">
                                        <label class="form-check-label small text-danger" for="edit_remove_photo">
                                            Remove current photo
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted">Email Address</label>
                            <input type="email" name="email" id="edit_email" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted">Phone Number</label>
                            <input type="text" name="phone" id="edit_phone" class="form-control">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted">LinkedIn URL</label>
                            <input type="url" name="linkedin_url" id="edit_linkedin_url" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted">Facebook URL</label>
                            <input type="url" name="facebook_url" id="edit_facebook_url" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted">Twitter / X URL</label>
                            <input type="url" name="twitter_url" id="edit_twitter_url" class="form-control">
                        </div>

                        <div class="col-12">
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="is_active" id="edit_is_active" value="1">
                                <label class="form-check-label small fw-semibold text-dark" for="edit_is_active">
                                    Publish on public "Our Team" website
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-white border-top">
                    <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm rounded-pill px-4 fw-semibold shadow-sm">
                        <i class="bi bi-save me-1"></i> Update Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ================= MODAL: DELETE CONFIRMATION ================= -->
<div class="modal fade" id="deleteMemberModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow rounded-4 overflow-hidden">
            <div class="p-4 text-center">
                <div class="rounded-circle bg-danger bg-opacity-10 text-danger mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 56px; height: 56px;">
                    <i class="bi bi-exclamation-triangle-fill fs-3"></i>
                </div>
                <h5 class="fw-bold text-dark mb-1">Delete Member?</h5>
                <p class="small text-muted mb-3">Are you sure you want to remove <strong id="deleteMemberName"></strong>? This will permanently delete their profile and photo.</p>
                
                <form action="" method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete_member">
                    <input type="hidden" name="id" id="deleteMemberId" value="">
                    <div class="d-flex justify-content-center gap-2">
                        <button type="button" class="btn btn-light btn-sm rounded-pill px-3 border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger btn-sm rounded-pill px-3 fw-semibold">Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Photo Preview for Add Modal
function previewAddPhoto(input) {
    var preview = document.getElementById('addPhotoPreview');
    var icon = document.getElementById('addPhotoDefaultIcon');
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.classList.remove('d-none');
            icon.classList.add('d-none');
        };
        reader.readAsDataURL(input.files[0]);
    } else {
        preview.src = '#';
        preview.classList.add('d-none');
        icon.classList.remove('d-none');
    }
}

// Photo Preview for Edit Modal
function previewEditPhoto(input) {
    var preview = document.getElementById('editPhotoPreview');
    var icon = document.getElementById('editPhotoDefaultIcon');
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.classList.remove('d-none');
            icon.classList.add('d-none');
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Open Edit Modal with Pre-populated data
function openEditModal(data) {
    document.getElementById('edit_id').value = data.id || '';
    document.getElementById('edit_name').value = data.name || '';
    document.getElementById('edit_role_title').value = data.role_title || '';
    document.getElementById('edit_department').value = data.department || '';
    if(document.getElementById('edit_category'))document.getElementById('edit_category').value=data.category || 'Academic Advisors';
    document.getElementById('edit_display_order').value = data.display_order || '0';
    document.getElementById('edit_bio').value = data.bio || '';
    document.getElementById('edit_email').value = data.email || '';
    document.getElementById('edit_phone').value = data.phone || '';
    document.getElementById('edit_linkedin_url').value = data.linkedin_url || '';
    document.getElementById('edit_facebook_url').value = data.facebook_url || '';
    document.getElementById('edit_twitter_url').value = data.twitter_url || '';
    document.getElementById('edit_is_active').checked = parseInt(data.is_active) === 1;

    var preview = document.getElementById('editPhotoPreview');
    var icon = document.getElementById('editPhotoDefaultIcon');
    var removeWrap = document.getElementById('removePhotoWrapper');
    document.getElementById('edit_remove_photo').checked = false;

    if (data.photo && data.photo.trim() !== '') {
        preview.src = '<?= BASE_URL ?>/uploads/team/' + encodeURIComponent(data.photo);
        preview.classList.remove('d-none');
        icon.classList.add('d-none');
        removeWrap.style.display = 'block';
    } else {
        preview.src = '#';
        preview.classList.add('d-none');
        icon.classList.remove('d-none');
        removeWrap.style.display = 'none';
    }

    var editModal = new bootstrap.Modal(document.getElementById('editMemberModal'));
    editModal.show();
}

// Confirm Delete Modal
function confirmDelete(id, name) {
    document.getElementById('deleteMemberId').value = id;
    document.getElementById('deleteMemberName').innerText = name;
    var delModal = new bootstrap.Modal(document.getElementById('deleteMemberModal'));
    delModal.show();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
