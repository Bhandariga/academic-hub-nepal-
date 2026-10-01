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
            $error_msg = 'Submission failed: ' . safe_error_message($e);
        }
    }
}

// Dynamic SEO Configuration for Services
$page_title = "Academic Research Support & APA 7 Formatting Services | AcademicHub Nepal";
$page_description = "Get expert thesis formatting, APA 7th edition referencing, questionnaire design, statistical analysis, and research defense mentorship for TU students.";
$page_keywords = "thesis formatting Nepal, APA 7 formatting service, BBS project report consulting, MBA dissertation guide TU, research methodology consulting Nepal, TU plagiarism check";
$canonical_url = BASE_URL . "/services.php";

$page_breadcrumbs = [
    ['name' => 'Home', 'url' => BASE_URL . '/index.php'],
    ['name' => 'Academic Support Services', 'url' => $canonical_url]
];

$page_schema = [
    "@context" => "https://schema.org",
    "@type" => "Service",
    "name" => "Academic Research Support & APA 7 Formatting",
    "serviceType" => "Academic Consultation, Thesis Formatting, Statistical Analysis",
    "provider" => [
        "@type" => "EducationalOrganization",
        "name" => "AcademicHub Nepal",
        "url" => BASE_URL
    ],
    "areaServed" => [
        "@type" => "Country",
        "name" => "Nepal"
    ],
    "description" => "Comprehensive academic support for Tribhuvan University students including APA 7 formatting, statistical matrix modeling, questionnaire design, and defense coaching.",
    "hasOfferCatalog" => [
        "@type" => "OfferCatalog",
        "name" => "Academic Mentorship Catalog",
        "itemListElement" => [
            ["@type" => "Offer", "itemOffered" => ["@type" => "Service", "name" => "APA 7th Edition Thesis Formatting"]],
            ["@type" => "Offer", "itemOffered" => ["@type" => "Service", "name" => "SPSS & Statistical Data Analysis"]],
            ["@type" => "Offer", "itemOffered" => ["@type" => "Service", "name" => "Survey Questionnaire Instrument Design"]],
            ["@type" => "Offer", "itemOffered" => ["@type" => "Service", "name" => "Research Proposal & Methodology Review"]]
        ]
    ]
];

require_once __DIR__ . '/includes/header.php';
?>

<!-- Modern Hero Header -->
<section class="py-5 text-white position-relative overflow-hidden" style="background: linear-gradient(135deg, #0b1e36 0%, #173d69 60%, #1e4976 100%);">
    <div class="position-absolute w-100 h-100 top-0 start-0 opacity-10" style="background-image: radial-gradient(#38bdf8 1px, transparent 1px); background-size: 24px 24px; pointer-events: none;"></div>
    <div class="container py-4 position-relative">
        <div class="row align-items-center justify-content-between">
            <div class="col-lg-8">
                <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold mb-3 shadow-sm" style="font-size:0.75rem; letter-spacing:0.06em;">
                    <i class="bi bi-patch-check-fill me-1"></i> FACULTY MENTORSHIP &amp; FORMATTING
                </span>
                <h1 class="display-5 fw-bold text-white mb-3" style="font-family: 'Outfit', sans-serif;">Academic &amp; Research Support Services</h1>
                <p class="lead text-light opacity-90 mb-4" style="max-width: 680px; font-size: 1.1rem; line-height: 1.6;">
                    Work directly with experienced thesis supervisors, editors, and research statisticians to elevate your original research, reports, and dissertation drafts.
                </p>
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <span class="text-white-50 small d-inline-flex align-items-center gap-1">
                        <i class="bi bi-clock-history text-warning"></i> 24–48 Hours Turnaround
                    </span>
                    <span class="text-white-50 small d-inline-flex align-items-center gap-1">
                        <i class="bi bi-mortarboard-fill text-info"></i> Strict TU &amp; APA 7 Guidelines
                    </span>
                    <span class="text-white-50 small d-inline-flex align-items-center gap-1">
                        <i class="bi bi-shield-check text-success"></i> 100% Confidential &amp; Original
                    </span>
                </div>
            </div>
            <div class="col-lg-4 text-lg-end mt-4 mt-lg-0">
                <a href="#intakeForm" class="btn btn-warning rounded-pill px-4 py-3 fw-bold text-dark shadow-sm">
                    <i class="bi bi-pencil-square me-2"></i> Request Service Quotation
                </a>
            </div>
        </div>
    </div>
</section>

<div class="container py-5">

    <?php if (!empty($success_msg)): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm rounded-4 p-4 mb-4 border-0" role="alert" style="background-color: #ECFDF5; border-left: 5px solid #059669 !important;">
            <div class="d-flex align-items-center">
                <i class="bi bi-check-circle-fill text-success fs-3 me-3"></i>
                <div>
                    <h6 class="fw-bold text-success mb-1">Request Received Successfully!</h6>
                    <p class="mb-0 text-dark small"><?= htmlspecialchars($success_msg) ?></p>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($error_msg)): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm rounded-4 p-4 mb-4 border-0" role="alert" style="background-color: #FEF2F2; border-left: 5px solid #DC2626 !important;">
            <div class="d-flex align-items-center">
                <i class="bi bi-exclamation-triangle-fill text-danger fs-3 me-3"></i>
                <div>
                    <h6 class="fw-bold text-danger mb-1">Submission Incomplete</h6>
                    <p class="mb-0 text-dark small"><?= htmlspecialchars($error_msg) ?></p>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Section Heading -->
    <div class="text-center mb-5">
        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-3 py-1 rounded-pill fw-semibold mb-2">Expert Academic Solutions</span>
        <h2 class="fw-bold text-dark" style="font-family: 'Outfit', sans-serif;">Specialized Research &amp; Editorial Assistance</h2>
        <p class="text-muted mx-auto" style="max-width: 600px; font-size: 0.95rem;">
            Choose from our specialized student packages designed to meet the strict defense standards of Tribhuvan University and affiliated colleges.
        </p>
    </div>

    <!-- Services Grid -->
    <div class="row g-4 mb-5">
        <!-- Service 1: APA Formatting -->
        <div class="col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white p-4 d-flex flex-column justify-content-between transition-lift" style="border-top: 4px solid #2563eb !important;">
                <div>
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="hub-icon bg-primary bg-opacity-10 text-primary p-3 rounded-3" style="width: 50px; height: 50px; display:flex; align-items:center; justify-content:center; font-size: 1.4rem;">
                            <i class="bi bi-file-text-fill"></i>
                        </div>
                        <span class="badge bg-light text-primary border rounded-pill px-3 py-1 fw-semibold small">24–48 Hours</span>
                    </div>
                    <h4 class="fw-bold text-dark mb-2" style="font-family: 'Outfit', sans-serif;">APA 7th Formatting</h4>
                    <p class="small text-muted mb-3" style="line-height: 1.6;">
                        Converts your manuscript into strict APA 7th Edition format: correct 1-inch margin ratios, in-text citations, DOI references, title page, and running head rules.
                    </p>
                    <ul class="list-unstyled small text-muted mb-4 ps-0">
                        <li class="mb-1"><i class="bi bi-check2 text-success me-2"></i>Title Page &amp; Abstract Setup</li>
                        <li class="mb-1"><i class="bi bi-check2 text-success me-2"></i>In-text &amp; Reference List Audit</li>
                        <li class="mb-1"><i class="bi bi-check2 text-success me-2"></i>Heading Level Hierarchy (1–5)</li>
                    </ul>
                </div>
                <div class="pt-3 border-top">
                    <button type="button" class="btn btn-outline-primary btn-sm rounded-pill w-100 fw-semibold" onclick="selectService('APA Formatting')">
                        <i class="bi bi-arrow-down-circle me-1"></i> Request APA Formatting
                    </button>
                </div>
            </div>
        </div>

        <!-- Service 2: Report Formatting -->
        <div class="col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white p-4 d-flex flex-column justify-content-between transition-lift" style="border-top: 4px solid #059669 !important;">
                <div>
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="hub-icon bg-success bg-opacity-10 text-success p-3 rounded-3" style="width: 50px; height: 50px; display:flex; align-items:center; justify-content:center; font-size: 1.4rem;">
                            <i class="bi bi-layout-text-window-reverse"></i>
                        </div>
                        <span class="badge bg-light text-success border rounded-pill px-3 py-1 fw-semibold small">24 Hours Fast</span>
                    </div>
                    <h4 class="fw-bold text-dark mb-2" style="font-family: 'Outfit', sans-serif;">Proofreading &amp; Report Formatting</h4>
                    <p class="small text-muted mb-3" style="line-height: 1.6;">
                        Comprehensive styling following campus guidelines: Roman preliminary page numbers, automated dynamic Table of Contents, List of Tables/Figures, and font standards.
                    </p>
                    <ul class="list-unstyled small text-muted mb-4 ps-0">
                        <li class="mb-1"><i class="bi bi-check2 text-success me-2"></i>Automated Dynamic TOC &amp; Lists</li>
                        <li class="mb-1"><i class="bi bi-check2 text-success me-2"></i>Roman &amp; Arabic Pagination</li>
                        <li class="mb-1"><i class="bi bi-check2 text-success me-2"></i>Supervisor &amp; Viva Signature Pages</li>
                    </ul>
                </div>
                <div class="pt-3 border-top">
                    <button type="button" class="btn btn-outline-success btn-sm rounded-pill w-100 fw-semibold" onclick="selectService('Report Formatting')">
                        <i class="bi bi-arrow-down-circle me-1"></i> Request Report Formatting
                    </button>
                </div>
            </div>
        </div>

        <!-- Service 3: Research Guidance -->
        <div class="col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white p-4 d-flex flex-column justify-content-between transition-lift" style="border-top: 4px solid #f59e0b !important;">
                <div>
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="hub-icon bg-warning bg-opacity-10 text-warning p-3 rounded-3" style="width: 50px; height: 50px; display:flex; align-items:center; justify-content:center; font-size: 1.4rem;">
                            <i class="bi bi-compass-fill"></i>
                        </div>
                        <span class="badge bg-light text-dark border rounded-pill px-3 py-1 fw-semibold small">1-on-1 Mentorship</span>
                    </div>
                    <h4 class="fw-bold text-dark mb-2" style="font-family: 'Outfit', sans-serif;">Research Methodology</h4>
                    <p class="small text-muted mb-3" style="line-height: 1.6;">
                        Personalized methodology consultation focusing on conceptual framework modeling, independent/dependent variable identification, and scope refinement.
                    </p>
                    <ul class="list-unstyled small text-muted mb-4 ps-0">
                        <li class="mb-1"><i class="bi bi-check2 text-success me-2"></i>Theoretical Framework Modeling</li>
                        <li class="mb-1"><i class="bi bi-check2 text-success me-2"></i>Hypothesis Formulation Support</li>
                        <li class="mb-1"><i class="bi bi-check2 text-success me-2"></i>Pre-Defense Q&amp;A Preparation</li>
                    </ul>
                </div>
                <div class="pt-3 border-top">
                    <button type="button" class="btn btn-outline-warning btn-sm rounded-pill w-100 fw-bold text-dark" onclick="selectService('Research Guidance')">
                        <i class="bi bi-arrow-down-circle me-1"></i> Request Guidance
                    </button>
                </div>
            </div>
        </div>

        <!-- Service 4: Proposal Review -->
        <div class="col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white p-4 d-flex flex-column justify-content-between transition-lift" style="border-top: 4px solid #6366f1 !important;">
                <div>
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="hub-icon bg-primary bg-opacity-10 text-primary p-3 rounded-3" style="width: 50px; height: 50px; display:flex; align-items:center; justify-content:center; font-size: 1.4rem;">
                            <i class="bi bi-check2-circle"></i>
                        </div>
                        <span class="badge bg-light text-primary border rounded-pill px-3 py-1 fw-semibold small">48 Hours Review</span>
                    </div>
                    <h4 class="fw-bold text-dark mb-2" style="font-family: 'Outfit', sans-serif;">Research Proposal Review</h4>
                    <p class="small text-muted mb-3" style="line-height: 1.6;">
                        Thorough critical review of your Bachelor/Master proposal draft with constructive supervisor-ready notes on methodology and literature feasibility.
                    </p>
                    <ul class="list-unstyled small text-muted mb-4 ps-0">
                        <li class="mb-1"><i class="bi bi-check2 text-success me-2"></i>Statement of Problem Analysis</li>
                        <li class="mb-1"><i class="bi bi-check2 text-success me-2"></i>Literature Gap Evaluation</li>
                        <li class="mb-1"><i class="bi bi-check2 text-success me-2"></i>Line-by-line Editorial Comments</li>
                    </ul>
                </div>
                <div class="pt-3 border-top">
                    <button type="button" class="btn btn-outline-primary btn-sm rounded-pill w-100 fw-semibold" onclick="selectService('Proposal Review')">
                        <i class="bi bi-arrow-down-circle me-1"></i> Request Proposal Review
                    </button>
                </div>
            </div>
        </div>

        <!-- Service 5: Questionnaire Design -->
        <div class="col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white p-4 d-flex flex-column justify-content-between transition-lift" style="border-top: 4px solid #0284c7 !important;">
                <div>
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="hub-icon bg-info bg-opacity-10 text-info p-3 rounded-3" style="width: 50px; height: 50px; display:flex; align-items:center; justify-content:center; font-size: 1.4rem;">
                            <i class="bi bi-ui-checks"></i>
                        </div>
                        <span class="badge bg-light text-info border rounded-pill px-3 py-1 fw-semibold small">Includes Pre-Test</span>
                    </div>
                    <h4 class="fw-bold text-dark mb-2" style="font-family: 'Outfit', sans-serif;">Questionnaire Design</h4>
                    <p class="small text-muted mb-3" style="line-height: 1.6;">
                        Custom questionnaire instrument crafting tailored to your variables, standardized on 5-point Likert scales with internal consistency benchmarking.
                    </p>
                    <ul class="list-unstyled small text-muted mb-4 ps-0">
                        <li class="mb-1"><i class="bi bi-check2 text-success me-2"></i>Demographic &amp; Variable Mapping</li>
                        <li class="mb-1"><i class="bi bi-check2 text-success me-2"></i>5-Point Likert Scale Metrics</li>
                        <li class="mb-1"><i class="bi bi-check2 text-success me-2"></i>Google Forms Setup Assistance</li>
                    </ul>
                </div>
                <div class="pt-3 border-top">
                    <button type="button" class="btn btn-outline-info btn-sm rounded-pill w-100 fw-semibold text-dark" onclick="selectService('Questionnaire Design')">
                        <i class="bi bi-arrow-down-circle me-1"></i> Request Questionnaire
                    </button>
                </div>
            </div>
        </div>

        <!-- Service 6: Data Analysis Support -->
        <div class="col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white p-4 d-flex flex-column justify-content-between transition-lift" style="border-top: 4px solid #8b5cf6 !important;">
                <div>
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="hub-icon bg-primary bg-opacity-10 text-primary p-3 rounded-3" style="width: 50px; height: 50px; display:flex; align-items:center; justify-content:center; font-size: 1.4rem;">
                            <i class="bi bi-pie-chart-fill"></i>
                        </div>
                        <span class="badge bg-light text-dark border rounded-pill px-3 py-1 fw-semibold small">SPSS / Excel Matrix</span>
                    </div>
                    <h4 class="fw-bold text-dark mb-2" style="font-family: 'Outfit', sans-serif;">SPSS / Data Analysis</h4>
                    <p class="small text-muted mb-3" style="line-height: 1.6;">
                        Statistical interpretation and writeup of SPSS outputs: mean, standard deviation, Pearson correlation coefficients, regression models, and ANOVA tables.
                    </p>
                    <ul class="list-unstyled small text-muted mb-4 ps-0">
                        <li class="mb-1"><i class="bi bi-check2 text-success me-2"></i>Descriptive &amp; Correlation Matrix</li>
                        <li class="mb-1"><i class="bi bi-check2 text-success me-2"></i>Multiple Linear Regression</li>
                        <li class="mb-1"><i class="bi bi-check2 text-success me-2"></i>Supervisor-Ready Table Formatting</li>
                    </ul>
                </div>
                <div class="pt-3 border-top">
                    <button type="button" class="btn btn-outline-primary btn-sm rounded-pill w-100 fw-semibold" onclick="selectService('Data Analysis Support')">
                        <i class="bi bi-arrow-down-circle me-1"></i> Request Analysis
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Request Intake Form -->
    <div class="row justify-content-center" id="intakeForm">
        <div class="col-lg-9">
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden bg-white">
                <div class="p-4 p-md-5 text-white position-relative" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #2563eb 100%) !important;">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-white bg-opacity-10 p-3 fs-3 d-flex align-items-center justify-content-center" style="width: 56px; height: 56px;">
                            <i class="bi bi-send-fill text-warning"></i>
                        </div>
                        <div>
                            <h3 class="fw-bold mb-1 text-white" style="font-family: 'Outfit', sans-serif;">Request a Free Service Quotation</h3>
                            <p class="small text-white-50 mb-0">Fill out your project requirements below to receive personalized guidance and a transparent price quote within 24 hours.</p>
                        </div>
                    </div>
                </div>

                <div class="card-body p-4 p-md-5">
                    <form action="" method="POST">
                        <?= csrf_field() ?>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-dark">Full Name <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border"><i class="bi bi-person"></i></span>
                                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($_SESSION['user_name'] ?? '') ?>" placeholder="e.g. Binod KC" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-dark">Email Address <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border"><i class="bi bi-envelope"></i></span>
                                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($_SESSION['user_email'] ?? '') ?>" placeholder="student@example.com" required>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-dark">Mobile / WhatsApp Number <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border"><i class="bi bi-whatsapp text-success"></i></span>
                                    <input type="text" name="phone" class="form-control" placeholder="98XXXXXXXX" required>
                                </div>
                                <span class="form-text text-muted" style="font-size:0.75rem;">Our mentor will contact you directly on WhatsApp.</span>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-dark">Target Service <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border"><i class="bi bi-award"></i></span>
                                    <select name="service_type" id="serviceSelect" class="form-select" required>
                                        <option value="">-- Choose Academic Service --</option>
                                        <option value="APA Formatting" <?= $prefill_service === 'APA Formatting' ? 'selected' : '' ?>>APA Formatting (7th Edition)</option>
                                        <option value="Report Formatting" <?= $prefill_service === 'Report Formatting' ? 'selected' : '' ?>>Proofreading &amp; Report Formatting</option>
                                        <option value="Research Guidance" <?= $prefill_service === 'Research Guidance' ? 'selected' : '' ?>>Research Guidance &amp; Methodology</option>
                                        <option value="Proposal Review" <?= $prefill_service === 'Proposal Review' ? 'selected' : '' ?>>Research Proposal Review</option>
                                        <option value="Questionnaire Design" <?= $prefill_service === 'Questionnaire Design' ? 'selected' : '' ?>>Questionnaire &amp; Survey Instrument</option>
                                        <option value="Data Analysis Support" <?= $prefill_service === 'Data Analysis Support' ? 'selected' : '' ?>>SPSS / Statistical Analysis Interpretation</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-dark">Campus / College</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border"><i class="bi bi-building"></i></span>
                                    <input type="text" name="campus" class="form-control" placeholder="e.g. Gyan Jyoti / Babai / Gorkha / MMC">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-dark">Academic Program &amp; Semester</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border"><i class="bi bi-book"></i></span>
                                    <input type="text" name="program" class="form-control" placeholder="e.g. BBS 4th Year, BBA 8th Sem, MBA">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-dark">Desired Completion Deadline</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border"><i class="bi bi-calendar3"></i></span>
                                <input type="date" name="deadline" class="form-control">
                            </div>
                            <span class="form-text text-muted" style="font-size:0.75rem;">When is your college submission or defense date?</span>
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-semibold text-dark">Describe Your Requirements <span class="text-danger">*</span></label>
                            <textarea name="message" class="form-control" rows="4" placeholder="Mention your topic title, current draft status, page count, and specific supervisor remarks or revisions needed..." required></textarea>
                        </div>

                        <div class="p-3 bg-light rounded-3 border mb-4 d-flex align-items-center gap-3">
                            <i class="bi bi-shield-lock-fill text-success fs-4"></i>
                            <div class="small text-muted" style="font-size:0.8rem;">
                                <strong>Academic Privacy Guarantee:</strong> Your draft documents, data files, and contact details remain strictly confidential and will never be shared or redistributed.
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-hub-primary py-3 fw-bold shadow-sm" style="font-size: 1rem;">
                                <i class="bi bi-send-check-fill me-2"></i> Submit Service Request
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function selectService(serviceName) {
    const sel = document.getElementById('serviceSelect');
    if (sel) {
        sel.value = serviceName;
    }
    const formEl = document.getElementById('intakeForm');
    if (formEl) {
        formEl.scrollIntoView({ behavior: 'smooth' });
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
