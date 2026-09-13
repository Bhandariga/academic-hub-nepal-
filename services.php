<?php
/**
 * AcademicHub Nepal - Academic Support Services
 * File: services.php
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth_check.php';

$success_msg = '';
$error_msg = '';
$prefill_service = $_GET['type'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $campus = trim($_POST['campus'] ?? '');
    $program = trim($_POST['program'] ?? '');
    $service_type = trim($_POST['service_type'] ?? '');
    $deadline = !empty($_POST['deadline']) ? $_POST['deadline'] : null;
    $message = trim($_POST['message'] ?? '');

    if (empty($name) || empty($email) || empty($phone) || empty($service_type) || empty($message)) {
        $error_msg = 'Please fill out all required fields to submit your service request.';
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO service_requests (name, email, phone, campus, program, service_type, deadline, message, status, created_at)
                VALUES (:name, :email, :phone, :campus, :program, :service_type, :deadline, :message, 'Pending', NOW())
            ");
            $stmt->execute([
                ':name' => $name,
                ':email' => $email,
                ':phone' => $phone,
                ':campus' => $campus,
                ':program' => $program,
                ':service_type' => $service_type,
                ':deadline' => $deadline,
                ':message' => $message
            ]);
            $success_msg = 'Your service request has been submitted successfully! Our academic mentor will contact you via WhatsApp / Phone within 24 hours.';
        } catch (Exception $e) {
            $error_msg = 'Submission failed: ' . $e->getMessage();
        }
    }
}

$page_title = "Research Support Services | AcademicHub Nepal";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="text-center mb-5">
        <span class="badge bg-info text-dark px-3 py-2 rounded-pill fw-bold mb-2">Mentorship &amp; Formatting</span>
        <h1 class="fw-bold text-primary">Academic &amp; Research Support Services</h1>
        <p class="text-muted mx-auto" style="max-width: 650px;">
            Work directly with experienced thesis supervisors, editors, and statisticians to polish your original student research.
        </p>
    </div>

    <?php if (!empty($success_msg)): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($success_msg) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($error_msg)): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error_msg) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Services Grid -->
    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="service-card">
                <div class="service-icon-box"><i class="bi bi-file-text-fill"></i></div>
                <h4 class="fw-bold mb-2">APA Formatting</h4>
                <p class="small text-muted mb-3">Converts your existing manuscript into strict APA 7th Edition format, ensuring correct margin ratios, reference citations, title pages, and running heads.</p>
                <span class="badge bg-light text-dark border">Estimated: 24–48 Hours</span>
            </div>
        </div>
        <div class="col-md-4">
            <div class="service-card">
                <div class="service-icon-box"><i class="bi bi-layout-text-window-reverse"></i></div>
                <h4 class="fw-bold mb-2">Report Formatting</h4>
                <p class="small text-muted mb-3">Campus guideline styling: Roman prelim page numbers, automated dynamic Table of Contents, List of Tables/Figures, and TU font requirements.</p>
                <span class="badge bg-light text-dark border">Estimated: 24 Hours</span>
            </div>
        </div>
        <div class="col-md-4">
            <div class="service-card">
                <div class="service-icon-box"><i class="bi bi-compass-fill"></i></div>
                <h4 class="fw-bold mb-2">Research Guidance</h4>
                <p class="small text-muted mb-3">Personal methodology consultation focusing on conceptual framework modeling, independent/dependent variable identification, and scope refinement.</p>
                <span class="badge bg-light text-dark border">Per Session / Milestone</span>
            </div>
        </div>
        <div class="col-md-4">
            <div class="service-card">
                <div class="service-icon-box"><i class="bi bi-check2-circle"></i></div>
                <h4 class="fw-bold mb-2">Proposal Review</h4>
                <p class="small text-muted mb-3">Thorough critical review of your Bachelor/Master proposal draft with constructive supervisor-ready notes on methodology and literature feasibility.</p>
                <span class="badge bg-light text-dark border">Estimated: 48 Hours</span>
            </div>
        </div>
        <div class="col-md-4">
            <div class="service-card">
                <div class="service-icon-box"><i class="bi bi-ui-checks"></i></div>
                <h4 class="fw-bold mb-2">Questionnaire Design</h4>
                <p class="small text-muted mb-3">Custom questionnaire instrument crafting tailored to your variables, standardized on 5-point Likert scales with internal consistency benchmarking.</p>
                <span class="badge bg-light text-dark border">Includes Pre-Test Review</span>
            </div>
        </div>
        <div class="col-md-4">
            <div class="service-card">
                <div class="service-icon-box"><i class="bi bi-pie-chart-fill"></i></div>
                <h4 class="fw-bold mb-2">Data Analysis Support</h4>
                <p class="small text-muted mb-3">Interpretation and writeup of SPSS outputs: mean, standard deviation, Pearson correlation coefficients, regression coefficients, and ANOVA tables.</p>
                <span class="badge bg-light text-dark border">Custom Quotation</span>
            </div>
        </div>
    </div>

    <!-- Request Intake Form -->
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="p-4 bg-primary text-white" style="background: linear-gradient(135deg, #0f2942 0%, #1e4976 100%) !important;">
                    <h3 class="fw-bold mb-1 text-white"><i class="bi bi-send-fill me-2"></i>Request a Service Quotation</h3>
                    <p class="small text-white-50 mb-0">Fill out your project requirements below to receive guidance and a custom quote.</p>
                </div>

                <div class="card-body p-4 p-md-5 bg-white">
                    <form action="" method="POST">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-muted">Full Name *</label>
                                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($_SESSION['user_name'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-muted">Email Address *</label>
                                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($_SESSION['user_email'] ?? '') ?>" required>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-muted">Mobile / WhatsApp Number *</label>
                                <input type="text" name="phone" class="form-control" placeholder="98XXXXXXXX" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-muted">Target Service *</label>
                                <select name="service_type" class="form-select" required>
                                    <option value="">-- Choose Service --</option>
                                    <option value="APA Formatting" <?= $prefill_service === 'APA Formatting' ? 'selected' : '' ?>>APA Formatting (7th Edition)</option>
                                    <option value="Report Formatting" <?= $prefill_service === 'Report Formatting' ? 'selected' : '' ?>>Campus Report Formatting</option>
                                    <option value="Research Guidance" <?= $prefill_service === 'Research Guidance' ? 'selected' : '' ?>>Research Guidance &amp; Methodology</option>
                                    <option value="Proposal Review" <?= $prefill_service === 'Proposal Review' ? 'selected' : '' ?>>Proposal Review &amp; Critique</option>
                                    <option value="Questionnaire Design" <?= $prefill_service === 'Questionnaire Design' ? 'selected' : '' ?>>Questionnaire &amp; Survey Instrument</option>
                                    <option value="Data Analysis Support" <?= $prefill_service === 'Data Analysis Support' ? 'selected' : '' ?>>SPSS / Statistical Analysis Interpretation</option>
                                </select>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-muted">Campus / College</label>
                                <input type="text" name="campus" class="form-control" placeholder="e.g. Gyan Jyoti Campus">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-muted">Academic Program</label>
                                <input type="text" name="program" class="form-control" placeholder="e.g. BBS 4th Year, MBA">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-muted">Desired Completion Deadline</label>
                            <input type="date" name="deadline" class="form-control">
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-semibold text-muted">Describe Your Requirements *</label>
                            <textarea name="message" class="form-control" rows="4" placeholder="Mention your topic, current draft status, page count, and specific supervisor remarks..." required></textarea>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-hub-primary py-2 fw-semibold">
                                <i class="bi bi-paperplane-fill me-2"></i> Submit Service Request
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
