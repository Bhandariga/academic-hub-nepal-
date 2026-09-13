<?php
/**
 * AcademicHub Nepal - Footer Include
 * Redesigned with High Contrast, Modern Glassmorphism, and Interactive Assistance CTA
 */
?>
</main>

<footer class="footer-hub mt-5">
    <!-- 1. Interactive Pre-Footer CTA Bar -->
    <div class="footer-cta-banner">
        <div class="container">
            <div class="row align-items-center g-3">
                <div class="col-lg-7 text-center text-lg-start">
                    <div class="d-flex align-items-center justify-content-center justify-content-lg-start gap-3 mb-1">
                        <span class="badge bg-info text-dark fw-bold px-2 py-1 rounded">Quick Assistance</span>
                        <h4 class="footer-cta-title mb-0">Need Help With Your Project Report or APA 7 Formatting?</h4>
                    </div>
                    <p class="footer-cta-text">
                        Connect with research mentors and technical formatting specialists for personalized guidance.
                    </p>
                </div>
                <div class="col-lg-5 text-center text-lg-end">
                    <div class="d-flex flex-wrap justify-content-center justify-content-lg-end gap-2">
                        <a href="https://wa.me/9779800000000" target="_blank" class="btn btn-success fw-semibold px-3 py-2 rounded-3 shadow-sm d-inline-flex align-items-center gap-2">
                            <i class="bi bi-whatsapp fs-5"></i> Chat on WhatsApp
                        </a>
                        <a href="<?= BASE_URL ?>/services.php" class="btn btn-hub-primary fw-semibold px-3 py-2 rounded-3 shadow-sm d-inline-flex align-items-center gap-2">
                            <i class="bi bi-send-fill"></i> Request Service
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Main Footer Navigation & Information Columns -->
    <div class="container">
        <div class="row g-4 mb-4">
            <!-- Brand Column -->
            <div class="col-lg-4 col-md-6">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <div class="hub-icon bg-info text-dark rounded-3 p-2 shadow-sm" style="width:40px;height:40px;display:flex;align-items:center;justify-content:center;">
                        <i class="bi bi-mortarboard-fill fs-5"></i>
                    </div>
                    <div>
                        <span class="fs-5 fw-bold text-white d-block lh-1">AcademicHub <span class="text-info">Nepal</span></span>
                        <span class="small text-white-50" style="font-size: 0.75rem;">Quality Resources for Higher Education</span>
                    </div>
                </div>
                
                <p class="footer-description mb-4">
                    Nepal's premier academic reference marketplace and research support platform. Empowering Bachelor and Master students with verified samples, structural templates, and research mentorship.
                </p>


                <div class="mt-2">
                    <span class="footer-trust-badge">
                        <i class="bi bi-shield-lock-fill"></i> 256-Bit SSL Encrypted Platform
                    </span>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="col-lg-2 col-md-6 col-6">
                <h5 class="footer-heading">Navigation</h5>
                <ul>
                    <li><a href="<?= BASE_URL ?>/index.php"><i class="bi bi-chevron-right link-bullet"></i> Home Page</a></li>
                    <li><a href="<?= BASE_URL ?>/resources.php"><i class="bi bi-chevron-right link-bullet"></i> Browse Catalog</a></li>
                    <li><a href="<?= BASE_URL ?>/index.php#campuses"><i class="bi bi-chevron-right link-bullet"></i> Campus Hubs</a></li>
                    <li><a href="<?= BASE_URL ?>/index.php#programs"><i class="bi bi-chevron-right link-bullet"></i> Degree Programs</a></li>
                    <li><a href="<?= BASE_URL ?>/services.php"><i class="bi bi-chevron-right link-bullet"></i> Research Services</a></li>
                    <li><a href="<?= BASE_URL ?>/about.php"><i class="bi bi-chevron-right link-bullet"></i> About &amp; Mission</a></li>
                </ul>
            </div>

            <!-- Campus Hubs -->
            <div class="col-lg-3 col-md-6 col-6">
                <h5 class="footer-heading">Campus Catalog</h5>
                <ul>
                    <li><a href="<?= BASE_URL ?>/resources.php?campus=Gyan+Jyoti+Campus"><i class="bi bi-chevron-right link-bullet"></i> Gyan Jyoti Campus (BBS)</a></li>
                    <li><a href="<?= BASE_URL ?>/resources.php?campus=Babai+Campus"><i class="bi bi-chevron-right link-bullet"></i> Babai Campus (BBS / BBA)</a></li>
                    <li><a href="<?= BASE_URL ?>/resources.php?campus=Gorkha+Campus"><i class="bi bi-chevron-right link-bullet"></i> Gorkha Campus (BBS / BCA)</a></li>
                    <li><a href="<?= BASE_URL ?>/resources.php?campus=Other+Campuses"><i class="bi bi-chevron-right link-bullet"></i> Tribhuvan University (All)</a></li>
                </ul>

                <div class="mt-4 pt-1">
                    <span class="badge bg-success bg-opacity-25 text-success border border-success py-2 px-3 fw-semibold">
                        <i class="bi bi-patch-check-fill me-1"></i> Verified eSewa ePay V2
                    </span>
                </div>
            </div>

            <!-- Academic Integrity & Contact Box -->
            <div class="col-lg-3 col-md-6">
                <h5 class="footer-heading">Trust &amp; Contact</h5>
                <div class="footer-glass-box">
                    <p class="small text-white-50 mb-3" style="line-height: 1.5;">
                        <strong class="text-white d-block mb-1"><i class="bi bi-award-fill text-warning me-1"></i> Academic Integrity:</strong>
                        All resources are strictly for reference, learning, and structural guidance. Students must prepare and submit original research.
                    </p>

                    <div class="footer-contact-item">
                        <div class="footer-contact-icon"><i class="bi bi-geo-alt-fill"></i></div>
                        <div>
                            <span class="text-white small fw-bold d-block">Location</span>
                            <span class="small text-white-50">Kathmandu / Dang / Pokhara</span>
                        </div>
                    </div>

                    <div class="footer-contact-item">
                        <div class="footer-contact-icon"><i class="bi bi-envelope-fill"></i></div>
                        <div>
                            <span class="text-white small fw-bold d-block">Support Desk</span>
                            <span class="small text-white-50">support@academichub.com.np</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Bottom Copyright & Compliance Bar -->
        <div class="footer-bottom-bar">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    &copy; <?= date('Y') ?> <strong class="text-white">AcademicHub Nepal</strong>. All rights reserved. Version 1.0 (Demo Prototype).
                </div>
                <div class="d-flex flex-wrap gap-3 gap-md-4 small">
                    <a href="<?= BASE_URL ?>/about.php#terms"><i class="bi bi-file-text me-1"></i> Terms of Use</a>
                    <a href="<?= BASE_URL ?>/about.php#privacy"><i class="bi bi-shield-check me-1"></i> Privacy Policy</a>
                    <a href="<?= BASE_URL ?>/about.php#refund"><i class="bi bi-arrow-counterclockwise me-1"></i> Refund Policy</a>
                    <a href="<?= BASE_URL ?>/admin/admin-login.php" class="text-info fw-semibold"><i class="bi bi-lock-fill me-1"></i> Admin Console</a>
                </div>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap 5 Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- Main Custom JS -->
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>
</html>
