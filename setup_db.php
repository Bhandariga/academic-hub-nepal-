<?php
/**
 * AcademicHub Nepal - 1-Click Database Setup & Sample Environment Seeder
 * Allows instant verification and initialization on any XAMPP / MySQL installation.
 */

require_once __DIR__ . '/config/config.php';

$message = '';
$status = 'info';
$installed = false;

// Ensure directories exist
$dirs = [
    __DIR__ . '/uploads',
    __DIR__ . '/uploads/resources',
    __DIR__ . '/uploads/previews',
    __DIR__ . '/assets/images'
];
foreach ($dirs as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }
}

// Ensure .htaccess in uploads/resources to protect files from direct HTTP access
$htaccessPath = __DIR__ . '/uploads/resources/.htaccess';
if (!file_exists($htaccessPath)) {
    file_put_contents($htaccessPath, "Order Deny,Allow\nDeny from all\n");
}

// Generate sample PDF/DOCX files if they do not exist
$sampleFiles = [
    'sample_bbs_digital_banking.pdf' => "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n3 0 obj<</Type/Page/MediaBox[0 0 612 792]/Parent 2 0 R/Resources<<>>>>endobj\nxref\n0 4\n0000000000 65535 f\n0000000009 00000 n\n0000000052 00000 n\n0000000101 00000 n\ntrailer<</Size 4/Root 1 0 R>>\nstartxref\n178\n%%EOF",
    'sample_marketing_proposal.docx' => "PK\x03\x04\x14\x00\x00\x00\x08\x00AcademicHub Sample DOCX Resource Content",
    'sample_bca_project_template.pdf' => "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n3 0 obj<</Type/Page/MediaBox[0 0 612 792]/Parent 2 0 R/Resources<<>>>>endobj\nxref\n0 4\n0000000000 65535 f\n0000000009 00000 n\n0000000052 00000 n\n0000000101 00000 n\ntrailer<</Size 4/Root 1 0 R>>\nstartxref\n178\n%%EOF",
    'sample_credit_risk_bbs.pdf' => "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n3 0 obj<</Type/Page/MediaBox[0 0 612 792]/Parent 2 0 R/Resources<<>>>>endobj\nxref\n0 4\n0000000000 65535 f\n0000000009 00000 n\n0000000052 00000 n\n0000000101 00000 n\ntrailer<</Size 4/Root 1 0 R>>\nstartxref\n178\n%%EOF",
    'sample_survey_questionnaire.docx' => "PK\x03\x04\x14\x00\x00\x00\x08\x00AcademicHub Questionnaire Instrument",
    'sample_apa7_master_guide.pdf' => "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n3 0 obj<</Type/Page/MediaBox[0 0 612 792]/Parent 2 0 R/Resources<<>>>>endobj\nxref\n0 4\n0000000000 65535 f\n0000000009 00000 n\n0000000052 00000 n\n0000000101 00000 n\ntrailer<</Size 4/Root 1 0 R>>\nstartxref\n178\n%%EOF",
    'sample_employee_motivation.pdf' => "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n3 0 obj<</Type/Page/MediaBox[0 0 612 792]/Parent 2 0 R/Resources<<>>>>endobj\nxref\n0 4\n0000000000 65535 f\n0000000009 00000 n\n0000000052 00000 n\n0000000101 00000 n\ntrailer<</Size 4/Root 1 0 R>>\nstartxref\n178\n%%EOF",
    'sample_ecommerce_mbs.pdf' => "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n3 0 obj<</Type/Page/MediaBox[0 0 612 792]/Parent 2 0 R/Resources<<>>>>endobj\nxref\n0 4\n0000000000 65535 f\n0000000009 00000 n\n0000000052 00000 n\n0000000101 00000 n\ntrailer<</Size 4/Root 1 0 R>>\nstartxref\n178\n%%EOF"
];

foreach ($sampleFiles as $filename => $content) {
    $filePath = __DIR__ . '/uploads/resources/' . $filename;
    if (!file_exists($filePath)) {
        file_put_contents($filePath, $content);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'install') {
    $db_host = sanitize($_POST['db_host'] ?? 'localhost');
    $db_user = sanitize($_POST['db_user'] ?? 'root');
    $db_pass = $_POST['db_pass'] ?? '';
    $db_name = sanitize($_POST['db_name'] ?? 'academic_hub');

    try {
        // Connect to MySQL server without DB first
        $pdo = new PDO("mysql:host={$db_host};charset=utf8mb4", $db_user, $db_pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        // Read SQL schema file
        $sqlFile = __DIR__ . '/database.sql';
        if (!file_exists($sqlFile)) {
            throw new Exception("database.sql file was not found in directory.");
        }

        $sqlContent = file_get_contents($sqlFile);

        // Execute queries
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db_name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
        $pdo->exec("USE `{$db_name}`;");

        // Split and execute SQL statements
        $statements = array_filter(array_map('trim', explode(';', $sqlContent)));
        foreach ($statements as $stmt) {
            if (!empty($stmt)) {
                $pdo->exec($stmt);
            }
        }

        $message = "Database '{$db_name}' and all seed tables successfully created! AcademicHub Nepal is ready.";
        $status = 'success';
        $installed = true;
    } catch (Exception $e) {
        $message = "Installation failed: " . $e->getMessage();
        $status = 'danger';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>1-Click Database Setup | AcademicHub Nepal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f0f4f8; font-family: 'Segoe UI', system-ui, sans-serif; color: #1e293b; }
        .setup-card { max-width: 680px; margin: 50px auto; border-radius: 16px; border: none; box-shadow: 0 10px 30px rgba(15, 41, 66, 0.08); overflow: hidden; }
        .setup-header { background: linear-gradient(135deg, #0f2942 0%, #1e4976 100%); color: #fff; padding: 32px; text-align: center; }
        .setup-body { padding: 36px; background: #fff; }
        .btn-primary-hub { background: #0f2942; border-color: #0f2942; color: #fff; padding: 12px 24px; font-weight: 600; border-radius: 8px; transition: all 0.2s; }
        .btn-primary-hub:hover { background: #0284c7; border-color: #0284c7; color: #fff; }
        .credential-box { background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 10px; padding: 16px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card setup-card">
            <div class="setup-header">
                <i class="bi bi-mortarboard-fill display-4 text-info mb-2 d-block"></i>
                <h2 class="fw-bold mb-1">AcademicHub Nepal</h2>
                <p class="text-light mb-0 opacity-75">1-Click Database Installer & Environment Seeder</p>
            </div>
            <div class="setup-body">
                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?= $status ?> alert-dismissible fade show" role="alert">
                        <i class="bi <?= $status === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill' ?> me-2"></i>
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <?php if ($installed): ?>
                    <div class="text-center py-4">
                        <div class="text-success display-1 mb-3"><i class="bi bi-check-circle-fill"></i></div>
                        <h4 class="fw-bold text-success">Setup Completed Successfully!</h4>
                        <p class="text-muted">The database schema has been executed, sample BBS, BBA, and BCA resources for Gyan Jyoti, Babai, and Gorkha campuses are loaded, and demo accounts are ready.</p>
                        
                        <div class="credential-box text-start my-4">
                            <h6 class="fw-bold text-dark mb-2"><i class="bi bi-key-fill me-2 text-primary"></i>Demo Access Credentials:</h6>
                            <div class="row">
                                <div class="col-sm-6 mb-2">
                                    <span class="badge bg-primary mb-1">Student Account</span>
                                    <div class="small"><strong>Email:</strong> student@demo.com</div>
                                    <div class="small"><strong>Password:</strong> password123</div>
                                </div>
                                <div class="col-sm-6 mb-2">
                                    <span class="badge bg-dark mb-1">Admin Panel</span>
                                    <div class="small"><strong>Email:</strong> admin@academichub.com</div>
                                    <div class="small"><strong>Password:</strong> admin123</div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-3 justify-content-center mt-4">
                            <a href="<?= BASE_URL ?>/index.php" class="btn btn-primary-hub">
                                <i class="bi bi-house-door-fill me-2"></i>Go to Home Page
                            </a>
                            <a href="<?= BASE_URL ?>/admin/admin-login.php" class="btn btn-outline-dark px-4 py-2 rounded-3 fw-semibold">
                                <i class="bi bi-shield-lock-fill me-2"></i>Admin Login
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <p class="text-secondary">Click the button below to automatically initialize the <code>academic_hub</code> database in your XAMPP MySQL server. It will create all necessary tables and seed realistic sample project reports, proposals, and demo user accounts.</p>

                    <form method="POST" action="">
                        <input type="hidden" name="action" value="install">
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-muted">Database Host</label>
                                <input type="text" name="db_host" class="form-control" value="localhost" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-muted">Database Name</label>
                                <input type="text" name="db_name" class="form-control" value="academic_hub" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-muted">MySQL Username</label>
                                <input type="text" name="db_user" class="form-control" value="root" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-muted">MySQL Password</label>
                                <input type="password" name="db_pass" class="form-control" placeholder="(Leave blank for standard XAMPP)">
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary-hub btn-lg shadow-sm">
                                <i class="bi bi-database-fill-gear me-2"></i>Initialize Database & Load Demo Data
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
