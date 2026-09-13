<?php
/**
 * AcademicHub Nepal - About, Policies & Academic Integrity
 * File: about.php
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/auth_check.php';

$page_title = "About Us & Academic Integrity Policies | AcademicHub Nepal";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <!-- Header -->
            <div class="text-center mb-5">
                <span class="badge bg-primary px-3 py-2 rounded-pill fw-bold mb-2">Our Mission &amp; Standards</span>
                <h1 class="fw-bold text-primary">About AcademicHub Nepal</h1>
                <p class="text-muted mx-auto" style="max-width: 700px;">
                    Dedicated to bridging the resource gap for college and university students across Nepal through ethical benchmark materials, verified formatting standards, and research mentorship.
                </p>
            </div>

            <!-- Core Values -->
            <div class="row g-4 mb-5">
                <div class="col-md-4">
                    <div class="p-4 bg-white rounded-4 border shadow-sm h-100 text-center">
                        <div class="hub-icon bg-primary text-white mx-auto mb-3" style="width:50px;height:50px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.5rem;">
                            <i class="bi bi-mortarboard-fill"></i>
                        </div>
                        <h5 class="fw-bold">Campus Relevance</h5>
                        <p class="small text-muted mb-0">Directly cataloged for Tribhuvan University colleges (Gyan Jyoti, Babai, Gorkha) so students reference exact syllabus formats.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-4 bg-white rounded-4 border shadow-sm h-100 text-center">
                        <div class="hub-icon bg-success text-white mx-auto mb-3" style="width:50px;height:50px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.5rem;">
                            <i class="bi bi-shield-check"></i>
                        </div>
                        <h5 class="fw-bold">Safe &amp; Instant eSewa</h5>
                        <p class="small text-muted mb-0">Integrated with official eSewa ePay V2 sandbox verification with automatic server-side unlock.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-4 bg-white rounded-4 border shadow-sm h-100 text-center">
                        <div class="hub-icon bg-info text-dark mx-auto mb-3" style="width:50px;height:50px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.5rem;">
                            <i class="bi bi-pencil-square"></i>
                        </div>
                        <h5 class="fw-bold">Originality Focused</h5>
                        <p class="small text-muted mb-0">Clear structural guides and sample datasets designed to assist students in composing their own thesis work.</p>
                    </div>
                </div>
            </div>

            <!-- Academic Integrity Commitment -->
            <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white mb-5">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <i class="bi bi-award-fill fs-2 text-primary"></i>
                    <h3 class="fw-bold text-primary mb-0">Academic Integrity Policy</h3>
                </div>
                <p class="text-secondary">
                    AcademicHub Nepal was founded on the fundamental principle of promoting genuine scholarly competence. We operate under strict academic integrity boundaries:
                </p>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded-3 border">
                            <h6 class="fw-bold text-success"><i class="bi bi-check-circle-fill me-1"></i> What AcademicHub Provides:</h6>
                            <ul class="small text-muted mb-0 ps-3">
                                <li>Structural project templates &amp; chapter organization samples.</li>
                                <li>Validated questionnaire instruments &amp; pilot test scales.</li>
                                <li>Statistical interpretation guides (SPSS, correlation, regression).</li>
                                <li>APA 7 reference formatting and citation guidelines.</li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded-3 border">
                            <h6 class="fw-bold text-danger"><i class="bi bi-x-circle-fill me-1"></i> What We Do NOT Provide:</h6>
                            <ul class="small text-muted mb-0 ps-3">
                                <li>Ready-made dissertations to submit without original student work.</li>
                                <li>Plagiarism evasion or illicit assignment proxy completion.</li>
                                <li>Guaranteed marks or official university approvals.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Policies: Terms, Privacy, Refund -->
            <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white mb-5" id="terms">
                <h4 class="fw-bold text-primary mb-3"><i class="bi bi-journal-check me-2"></i>Terms of Use</h4>
                <p class="small text-muted">
                    By accessing or purchasing reference materials on AcademicHub Nepal, you acknowledge that all documents are intended exclusively for individual academic reference and formatting benchmarking. Redistribution, commercial resale, or presenting purchased samples verbatim as your own submission is strictly prohibited.
                </p>

                <h4 class="fw-bold text-primary mt-4 mb-3" id="refund"><i class="bi bi-arrow-counterclockwise me-2"></i>Refund &amp; Return Policy</h4>
                <p class="small text-muted">
                    Due to the instantaneous digital nature of downloadable documents, standard refunds are not offered once a file has been downloaded. However, if you experience a verified technical error (corrupted file, duplicate charge, or system delivery failure), our support team will promptly review transaction logs and re-issue the file or issue a full refund within 3 business days.
                </p>

                <h4 class="fw-bold text-primary mt-4 mb-3" id="privacy"><i class="bi bi-shield-lock me-2"></i>Privacy &amp; Data Protection</h4>
                <p class="small text-muted mb-0">
                    We value student privacy. Personal information collected during registration (name, campus, email, and phone number) is used solely for order processing, account delivery, and customer support. We never sell or share student data with unauthorized third parties.
                </p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
