<?php
/**
 * AcademicHub Nepal - Production Database Connection
 * Supports environment variables, connection pooling parameters, and error resilience
 */

require_once __DIR__ . '/config.php';

// Database credentials with environment variable override
$db_host = getenv('DB_HOST') ?: 'localhost';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';
$db_name = getenv('DB_NAME') ?: 'academic_hub';
$db_port = getenv('DB_PORT') ?: '3306';

try {
    $dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4";
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ]);

    // Transparent Auto-Migration Runner for RBAC & Super Admin
    static $migrated = false;
if (!$migrated && PHP_SAPI === 'cli' && getenv('ALLOW_DB_SETUP') === '1') {
        try {
            // Check users table columns
            $colsStmt = $pdo->query("SHOW COLUMNS FROM users");
            $existingCols = $colsStmt->fetchAll(PDO::FETCH_COLUMN, 0);

            // 1. Ensure role enum supports 'superadmin'
            $pdo->exec("ALTER TABLE users MODIFY COLUMN role ENUM('student', 'admin', 'superadmin') NOT NULL DEFAULT 'student'");

            // 2. Add admin_role column if missing
            if (!in_array('admin_role', $existingCols)) {
                $pdo->exec("ALTER TABLE users ADD COLUMN admin_role VARCHAR(60) DEFAULT NULL AFTER role");
            }

            // 3. Add permissions column if missing
            if (!in_array('permissions', $existingCols)) {
                $pdo->exec("ALTER TABLE users ADD COLUMN permissions TEXT DEFAULT NULL AFTER admin_role");
            }

            // 4. Add is_active column if missing
            if (!in_array('is_active', $existingCols)) {
                $pdo->exec("ALTER TABLE users ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER permissions");
            }

            // 5. Add last_login column if missing
            if (!in_array('last_login', $existingCols)) {
                $pdo->exec("ALTER TABLE users ADD COLUMN last_login DATETIME DEFAULT NULL AFTER is_active");
            }

            // 6. Add profile_image column if missing
            if (!in_array('profile_image', $existingCols)) {
                $pdo->exec("ALTER TABLE users ADD COLUMN profile_image VARCHAR(255) DEFAULT NULL AFTER campus");
            }

            // Ensure uploads/avatars directory exists
            $avatarsDir = dirname(__DIR__) . '/uploads/avatars';
            if (!file_exists($avatarsDir)) {
                @mkdir($avatarsDir, 0777, true);
            }

            // 7. Ensure admin_activity_logs table exists
            $pdo->exec("CREATE TABLE IF NOT EXISTS admin_activity_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                admin_id INT NOT NULL,
                action VARCHAR(100) NOT NULL,
                details TEXT DEFAULT NULL,
                target_type VARCHAR(50) DEFAULT NULL,
                target_id INT DEFAULT NULL,
                ip_address VARCHAR(45) DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_admin_act (admin_id),
                INDEX idx_action (action)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            // 8. Ensure reviews table exists
            $pdo->exec("CREATE TABLE IF NOT EXISTS reviews (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                rating TINYINT(1) NOT NULL DEFAULT 5,
                program VARCHAR(100) DEFAULT NULL,
                review_text TEXT NOT NULL,
                status ENUM('approved', 'pending', 'hidden') NOT NULL DEFAULT 'approved',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_review_user (user_id),
                INDEX idx_review_status (status),
                CONSTRAINT fk_reviews_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            // 9. Ensure demo students exist for seed reviews
            $checkStudent2 = $pdo->prepare("SELECT id FROM users WHERE email = 'pooja@demo.com'");
            $checkStudent2->execute();
            if (!$checkStudent2->fetch()) {
                $pwdHash = password_hash('password123', PASSWORD_BCRYPT);
                $pdo->prepare("INSERT INTO users (name, email, password, phone, campus, role, is_active, created_at) VALUES ('Pooja Rawat', 'pooja@demo.com', :pwd, '9841987654', 'Babai Campus', 'student', 1, NOW())")
                    ->execute([':pwd' => $pwdHash]);
            }

            $checkStudent3 = $pdo->prepare("SELECT id FROM users WHERE email = 'sagar@demo.com'");
            $checkStudent3->execute();
            if (!$checkStudent3->fetch()) {
                $pwdHash = password_hash('password123', PASSWORD_BCRYPT);
                $pdo->prepare("INSERT INTO users (name, email, password, phone, campus, role, is_active, created_at) VALUES ('Sagar Adhikari', 'sagar@demo.com', :pwd, '9841122334', 'Gorkha Campus', 'student', 1, NOW())")
                    ->execute([':pwd' => $pwdHash]);
            }

            // 10. Seed initial reviews if empty
            $revCountStmt = $pdo->query("SELECT COUNT(*) FROM reviews");
            if ((int)$revCountStmt->fetchColumn() === 0) {
                // Find student IDs
                $s1 = $pdo->query("SELECT id FROM users WHERE email = 'student@demo.com'")->fetchColumn();
                $s2 = $pdo->query("SELECT id FROM users WHERE email = 'pooja@demo.com'")->fetchColumn();
                $s3 = $pdo->query("SELECT id FROM users WHERE email = 'sagar@demo.com'")->fetchColumn();

                $insRev = $pdo->prepare("INSERT INTO reviews (user_id, rating, program, review_text, status, created_at) VALUES (:uid, :rating, :prog, :text, 'approved', :dt)");
                
                if ($s1) {
                    $insRev->execute([
                        ':uid' => $s1,
                        ':rating' => 5,
                        ':prog' => 'BBS 4th Year',
                        ':text' => 'The BBS 4th Year Finance project sample saved me weeks of confusion. The chapter organization, financial ratios, and statistical tables were exactly aligned with our college guidelines.',
                        ':dt' => date('Y-m-d H:i:s', strtotime('-5 days'))
                    ]);
                }
                if ($s2) {
                    $insRev->execute([
                        ':uid' => $s2,
                        ':rating' => 5,
                        ':prog' => 'BBA 8th Semester',
                        ':text' => 'The eSewa QR payment and instant unlock feature was completely hassle-free. The APA 7th Edition bibliography template helped me pass my internal defense without a single citation correction.',
                        ':dt' => date('Y-m-d H:i:s', strtotime('-3 days'))
                    ]);
                }
                if ($s3) {
                    $insRev->execute([
                        ':uid' => $s3,
                        ':rating' => 5,
                        ':prog' => 'Ma In Societies 4th Year',
                        ':text' => 'The system documentation and UML diagrams template gave me an absolute head start for my final software engineering viva. Excellent service and responsive support for TU students.',
                        ':dt' => date('Y-m-d H:i:s', strtotime('-1 days'))
                    ]);
                }
            }

            // 11. Ensure orders table supports QR payment and verification
            try {
                $orderColsStmt = $pdo->query("SHOW COLUMNS FROM orders");
                $existingOrderCols = $orderColsStmt->fetchAll(PDO::FETCH_COLUMN, 0);

                $pdo->exec("ALTER TABLE orders MODIFY COLUMN payment_status ENUM('PENDING', 'PENDING_VERIFICATION', 'PAID', 'FAILED', 'CANCELLED') NOT NULL DEFAULT 'PENDING'");

                if (!in_array('payment_method', $existingOrderCols)) {
                    $pdo->exec("ALTER TABLE orders ADD COLUMN payment_method VARCHAR(50) NOT NULL DEFAULT 'ESEWA_QR' AFTER amount");
                }
                if (!in_array('payment_proof', $existingOrderCols)) {
                    $pdo->exec("ALTER TABLE orders ADD COLUMN payment_proof VARCHAR(255) DEFAULT NULL AFTER transaction_code");
                }
                if (!in_array('verified_at', $existingOrderCols)) {
                    $pdo->exec("ALTER TABLE orders ADD COLUMN verified_at DATETIME DEFAULT NULL AFTER payment_proof");
                }
            } catch (Exception $eOrder) {}

            // 12. Seed or ensure Super Admin exists: superadmin@gmail.com / 123456
            $checkSa = $pdo->prepare("SELECT id, password, role FROM users WHERE email = 'superadmin@gmail.com' LIMIT 1");
            $checkSa->execute();
            $sa = $checkSa->fetch();

            $saHash = password_hash('123456', PASSWORD_BCRYPT);
            $saPermissions = json_encode(['all', 'manage_resources', 'manage_orders', 'manage_students', 'manage_admins', 'view_reports', 'system_settings']);

            if (!$sa) {
                $insSa = $pdo->prepare("INSERT INTO users 
                    (name, email, password, phone, campus, role, admin_role, permissions, is_active, created_at)
                    VALUES 
                    ('Chief Super Admin', 'superadmin@gmail.com', :pwd, '9800000000', 'Tribhuvan University HQ', 'superadmin', 'Super Administrator', :perm, 1, NOW())");
                $insSa->execute([':pwd' => $saHash, ':perm' => $saPermissions]);
            } else {
                if ($sa['role'] !== 'superadmin' || !password_verify('123456', $sa['password'])) {
                    $updSa = $pdo->prepare("UPDATE users SET 
                        role = 'superadmin', 
                        admin_role = 'Super Administrator', 
                        password = :pwd,
                        permissions = :perm,
                        is_active = 1
                        WHERE email = 'superadmin@gmail.com'");
                    $updSa->execute([':pwd' => $saHash, ':perm' => $saPermissions]);
                }
            }

            // 13. Ensure uploads/team directory exists
            $teamUploadDir = dirname(__DIR__) . '/uploads/team';
            if (!file_exists($teamUploadDir)) {
                @mkdir($teamUploadDir, 0777, true);
            }

            // 14. Ensure team_members table exists
            $pdo->exec("CREATE TABLE IF NOT EXISTS team_members (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                role_title VARCHAR(100) NOT NULL,
                department VARCHAR(100) DEFAULT NULL,
                bio TEXT DEFAULT NULL,
                photo VARCHAR(255) DEFAULT NULL,
                email VARCHAR(150) DEFAULT NULL,
                phone VARCHAR(30) DEFAULT NULL,
                linkedin_url VARCHAR(255) DEFAULT NULL,
                facebook_url VARCHAR(255) DEFAULT NULL,
                twitter_url VARCHAR(255) DEFAULT NULL,
                display_order INT NOT NULL DEFAULT 0,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_team_order (display_order),
                INDEX idx_team_active (is_active)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            // 15. Seed initial demo team members if empty
            $teamCount = (int)$pdo->query("SELECT COUNT(*) FROM team_members")->fetchColumn();
            if ($teamCount === 0) {
                $insTeam = $pdo->prepare("
                    INSERT INTO team_members 
                    (name, role_title, department, bio, photo, email, phone, linkedin_url, facebook_url, twitter_url, display_order, is_active, created_at)
                    VALUES 
                    (:name, :role, :dept, :bio, :photo, :email, :phone, :linkedin, :facebook, :twitter, :order, 1, NOW())
                ");

                $demoTeam = [
                    [
                        'name' => 'Prof. Dr. Ram Chandra Pokharel',
                        'role' => 'Chief Academic Advisor',
                        'dept' => 'Faculty of Management, Tribhuvan University',
                        'bio' => 'Over 22 years of academic leadership guiding BBS and MBA dissertations and project works across Tribhuvan University affiliated colleges.',
                        'photo' => null,
                        'email' => 'advisor@academichub.edu.np',
                        'phone' => '+977-9851023456',
                        'linkedin' => 'https://linkedin.com',
                        'facebook' => 'https://facebook.com',
                        'twitter' => 'https://twitter.com',
                        'order' => 1
                    ],
                    [
                        'name' => 'Er. Prakash Shrestha',
                        'role' => 'Founder & Lead Systems Architect',
                        'dept' => 'Technology & Academic Infrastructure',
                        'bio' => 'Senior software engineer and educational technology advocate committed to digitizing verified academic curricula, notes, and reference projects for Nepalese college students.',
                        'photo' => null,
                        'email' => 'prakash@academichub.com',
                        'phone' => '+977-9801234567',
                        'linkedin' => 'https://linkedin.com',
                        'facebook' => 'https://facebook.com',
                        'twitter' => null,
                        'order' => 2
                    ],
                    [
                        'name' => 'Sita Thapa, M.Phil',
                        'role' => 'Head of BBS & BBA Content Review',
                        'dept' => 'Mark International College / TU Affiliated',
                        'bio' => 'Senior lecturer in Financial Management with 14+ years of classroom research, curriculum mentoring, and project report methodology guidance.',
                        'photo' => null,
                        'email' => 'sita.thapa@academichub.com',
                        'phone' => '+977-9841889900',
                        'linkedin' => 'https://linkedin.com',
                        'facebook' => null,
                        'twitter' => null,
                        'order' => 3
                    ],
                    [
                        'name' => 'Bikram Basnet, MBA',
                        'role' => 'Research & Defense Coordinator',
                        'dept' => 'Babai Campus Faculty Liaison',
                        'bio' => 'Specialist in corporate finance and marketing field research, guiding over 1,500 undergraduate candidates through thesis defense and APA 7th edition formatting.',
                        'photo' => null,
                        'email' => 'bikram@academichub.com',
                        'phone' => '+977-9861234567',
                        'linkedin' => 'https://linkedin.com',
                        'facebook' => 'https://facebook.com',
                        'twitter' => null,
                        'order' => 4
                    ],
                    [
                        'name' => 'Anjali Sharma, MIT',
                        'role' => 'Digital Library & Student Experience Lead',
                        'dept' => 'Gorkha Campus IT Liaison',
                        'bio' => 'Oversees computer science documentation standards, UML architecture models, and ensures 24/7 digital resource accessibility for Ma In Societies and IT undergraduates.',
                        'photo' => null,
                        'email' => 'anjali.sharma@academichub.com',
                        'phone' => '+977-9811223344',
                        'linkedin' => 'https://linkedin.com',
                        'facebook' => null,
                        'twitter' => 'https://twitter.com',
                        'order' => 5
                    ]
                ];

                foreach ($demoTeam as $member) {
                    $insTeam->execute([
                        ':name' => $member['name'],
                        ':role' => $member['role'],
                        ':dept' => $member['dept'],
                        ':bio' => $member['bio'],
                        ':photo' => $member['photo'],
                        ':email' => $member['email'],
                        ':phone' => $member['phone'],
                        ':linkedin' => $member['linkedin'],
                        ':facebook' => $member['facebook'],
                        ':twitter' => $member['twitter'],
                        ':order' => $member['order']
                    ]);
                }
            }

            // 16. Ensure content management tables exist
            $pdo->exec("CREATE TABLE IF NOT EXISTS successful_students (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                photo VARCHAR(255) DEFAULT NULL,
                program VARCHAR(20) NOT NULL,
                campus VARCHAR(150) NOT NULL,
                achievement VARCHAR(200) NOT NULL,
                story TEXT NOT NULL,
                graduation_year SMALLINT NOT NULL,
                display_order INT NOT NULL DEFAULT 0,
                status TINYINT(1) NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_success_visible (status, display_order)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            $pdo->exec("CREATE TABLE IF NOT EXISTS colleges (
                id INT AUTO_INCREMENT PRIMARY KEY,
                college_name VARCHAR(150) NOT NULL,
                photo VARCHAR(255) DEFAULT NULL,
                location VARCHAR(150) NOT NULL,
                university VARCHAR(150) NOT NULL,
                programs VARCHAR(255) NOT NULL,
                website VARCHAR(255) DEFAULT NULL,
                description TEXT NOT NULL,
                status TINYINT(1) NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_college_visible(status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            $pdo->exec("CREATE TABLE IF NOT EXISTS society_gallery (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(150) NOT NULL,
                image VARCHAR(255) DEFAULT NULL,
                category VARCHAR(60) NOT NULL,
                description TEXT NOT NULL,
                status TINYINT(1) NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_gallery_visible(status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            $pdo->exec("CREATE TABLE IF NOT EXISTS media_assets (
                id INT AUTO_INCREMENT PRIMARY KEY,
                filename VARCHAR(255) NOT NULL UNIQUE,
                alt_text VARCHAR(200) NOT NULL,
                category VARCHAR(40) NOT NULL,
                width INT NOT NULL,
                height INT NOT NULL,
                file_size INT NOT NULL,
                uploaded_by INT DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            // Seed successful students if empty
            $studentCount = (int)$pdo->query("SELECT COUNT(*) FROM successful_students")->fetchColumn();
            if ($studentCount === 0) {
                $insStudent = $pdo->prepare("
                    INSERT INTO successful_students 
                    (name, photo, program, campus, achievement, story, graduation_year, display_order, status, created_at)
                    VALUES 
                    (:name, :photo, :prog, :campus, :achievement, :story, :year, :order, 1, NOW())
                ");

                $demoStudents = [
                    [
                        'name' => 'Aayush Sharma',
                        'photo' => 'student_aayush.jpg',
                        'prog' => 'BBS',
                        'campus' => 'Mark International College',
                        'achievement' => 'Dean\'s List - Academic Excellence',
                        'story' => 'Graduated with distinction in BBS 4th Year. Successfully completed finance project on digital banking impact analysis with highest honors. Now pursuing MBA at Tribhuvan University.',
                        'year' => 2024,
                        'order' => 1
                    ],
                    [
                        'name' => 'Pooja Rawat',
                        'photo' => 'student_pooja.jpg',
                        'prog' => 'BBA',
                        'campus' => 'Mark International College',
                        'achievement' => 'Best Research Paper Award',
                        'story' => 'Received Best Research Paper Award for BBA 8th Semester marketing thesis on consumer behavior in emerging markets. Currently working as marketing analyst at leading firm.',
                        'year' => 2024,
                        'order' => 2
                    ],
                    [
                        'name' => 'Sagar Adhikari',
                        'photo' => 'student_sagar.jpg',
                        'prog' => 'Ma In Societies',
                        'campus' => 'Mahendra Multiple Campus',
                        'achievement' => 'Innovation in Software Engineering',
                        'story' => 'Developed award-winning software project for final year viva. Specialized in system documentation and UML architecture. Now working as senior software developer.',
                        'year' => 2024,
                        'order' => 3
                    ],
                    [
                        'name' => 'Kritika Basnet',
                        'photo' => 'student_kritika.jpg',
                        'prog' => 'BBA',
                        'campus' => 'Deukhuri Bahumukhi Campus',
                        'achievement' => 'Outstanding Academic Performance',
                        'story' => 'Achieved highest GPA in BBA program. Excelled in research methodology and survey design. Currently pursuing MBS with focus on organizational behavior.',
                        'year' => 2025,
                        'order' => 4
                    ]
                ];

                foreach ($demoStudents as $student) {
                    $insStudent->execute([
                        ':name' => $student['name'],
                        ':photo' => $student['photo'],
                        ':prog' => $student['prog'],
                        ':campus' => $student['campus'],
                        ':achievement' => $student['achievement'],
                        ':story' => $student['story'],
                        ':year' => $student['year'],
                        ':order' => $student['order']
                    ]);
                }
            }

            // 17. Campus Realignment: Migrate records from Gyan Jyoti Campus to Mark International College / Mahendra Multiple / Deukhuri
            try {
                $pdo->exec("UPDATE resources SET campus = 'Mark International College' WHERE campus = 'Gyan Jyoti Campus' AND id = 1");
                $pdo->exec("UPDATE resources SET campus = 'Mahendra Multiple Campus' WHERE campus = 'Gyan Jyoti Campus' AND id = 6");
                $pdo->exec("UPDATE resources SET campus = 'Deukhuri Bahumukhi Campus' WHERE campus = 'Gyan Jyoti Campus' AND id = 7");
                $pdo->exec("UPDATE resources SET campus = 'Mark International College' WHERE campus = 'Gyan Jyoti Campus'");
                $pdo->exec("UPDATE users SET campus = 'Mark International College' WHERE campus = 'Gyan Jyoti Campus'");
                $pdo->exec("UPDATE team_members SET department = REPLACE(department, 'Gyan Jyoti Campus', 'Mark International College') WHERE department LIKE '%Gyan Jyoti Campus%'");
            } catch (Exception $eCamp) {}

            // 18. Ensure MBA resources exist in catalog
            try {
                $checkMba1 = (int)$pdo->query("SELECT COUNT(*) FROM resources WHERE program = 'MBA'")->fetchColumn();
                if ($checkMba1 === 0) {
                    $insMba = $pdo->prepare("INSERT INTO resources 
                        (title, description, campus, program, year, subject, document_type, pages, file_format, price, file_path, preview_image, featured, downloads_count, created_at)
                        VALUES 
                        (:title, :desc, :campus, 'MBA', :year, :subj, :type, :pages, :fmt, :price, :fpath, :pimg, 1, :dl, NOW())");
                    
                    $insMba->execute([
                        ':title' => 'Strategic Financial Performance & Risk Governance - MBA Master Dissertation',
                        ':desc' => 'Comprehensive executive MBA Master dissertation analyzing strategic capital budgeting, corporate risk governance, and digital transformation in Nepalese banking and corporate sectors. Adheres strictly to Tribhuvan University Faculty of Management MBA dissertation format with complete methodology, empirical data tables, regression analysis, and APA 7th edition references.',
                        ':campus' => 'Mark International College',
                        ':year' => 'Second Year',
                        ':subj' => 'Strategic Management & Finance',
                        ':type' => 'Project Report',
                        ':pages' => 112,
                        ':fmt' => 'PDF',
                        ':price' => 599.00,
                        ':fpath' => 'sample_bbs_digital_banking.pdf',
                        ':pimg' => 'preview_digital_banking.jpg',
                        ':dl' => 84
                    ]);

                    $insMba->execute([
                        ':title' => 'Brand Equity & Digital Consumer Analytics in Emerging Markets - MBA Research Project',
                        ':desc' => 'Standardized MBA graduate research report studying consumer loyalty, brand resonance, and omnichannel customer behavior across Kathmandu and Lumbini province enterprises. Features structural equation modeling (SEM), questionnaire validity metrics, and managerial implementation frameworks.',
                        ':campus' => 'Mahendra Multiple Campus',
                        ':year' => 'Fourth Semester',
                        ':subj' => 'Marketing & Analytics',
                        ':type' => 'Research Proposal',
                        ':pages' => 76,
                        ':fmt' => 'PDF',
                        ':price' => 499.00,
                        ':fpath' => 'sample_marketing_proposal.docx',
                        ':pimg' => 'preview_marketing_proposal.jpg',
                        ':dl' => 62
                    ]);

                    $insMba->execute([
                        ':title' => 'Corporate Governance & Microfinance Sustainability in Dang District - Empirical Study',
                        ':desc' => 'In-depth empirical research assessing credit disbursement, default mitigation, and rural financial inclusion metrics across community cooperatives. Follows complete Tribhuvan University MBA/MBS research standards.',
                        ':campus' => 'Deukhuri Bahumukhi Campus',
                        ':year' => 'Third Semester',
                        ':subj' => 'Finance & Cooperative Management',
                        ':type' => 'Project Report',
                        ':pages' => 84,
                        ':fmt' => 'PDF',
                        ':price' => 449.00,
                        ':fpath' => 'sample_credit_risk_bbs.pdf',
                        ':pimg' => 'preview_credit_risk.jpg',
                        ':dl' => 47
                    ]);
                }
            } catch (Exception $eMba) {}

            // 19. Add keywords column to resources table if missing
            try {
                $resColsStmt = $pdo->query("SHOW COLUMNS FROM resources");
                $existingResCols = $resColsStmt->fetchAll(PDO::FETCH_COLUMN, 0);

                if (!in_array('keywords', $existingResCols)) {
                    $pdo->exec("ALTER TABLE resources ADD COLUMN keywords VARCHAR(500) DEFAULT NULL AFTER description");
                }
            } catch (Exception $eRes) {}

            // 20. Update Student Profiles with Verified Photos & Seed Success Stories
            try {
                // Update Aayush Sharma
                $pdo->exec("UPDATE users SET profile_image = 'student_aayush.jpg', campus = 'Mark International College' WHERE email = 'student@demo.com'");
                
                // Update Pooja Rawat
                $pdo->exec("UPDATE users SET profile_image = 'student_pooja.jpg', campus = 'Mark International College' WHERE email = 'pooja@demo.com'");
                
                // Update Sagar Adhikari
                $pdo->exec("UPDATE users SET profile_image = 'student_sagar.jpg', campus = 'Mahendra Multiple Campus' WHERE email = 'sagar@demo.com'");

                // Add or update Kritika Basnet
                $checkKritika = $pdo->prepare("SELECT id FROM users WHERE email = 'kritika@demo.com'");
                $checkKritika->execute();
                $kId = $checkKritika->fetchColumn();
                if (!$kId) {
                    $pwdHash = password_hash('password123', PASSWORD_BCRYPT);
                    $pdo->prepare("INSERT INTO users (name, email, password, phone, campus, profile_image, role, is_active, created_at) VALUES ('Kritika Basnet', 'kritika@demo.com', :pwd, '9841556677', 'Deukhuri Bahumukhi Campus', 'student_kritika.jpg', 'student', 1, NOW())")
                        ->execute([':pwd' => $pwdHash]);
                    $kId = $pdo->lastInsertId();
                } else {
                    $pdo->exec("UPDATE users SET profile_image = 'student_kritika.jpg', campus = 'Deukhuri Bahumukhi Campus' WHERE email = 'kritika@demo.com'");
                }

                // Add review for Kritika if not exists
                if ($kId) {
                    $checkKRev = (int)$pdo->query("SELECT COUNT(*) FROM reviews WHERE user_id = {$kId}")->fetchColumn();
                    if ($checkKRev === 0) {
                        $pdo->prepare("INSERT INTO reviews (user_id, rating, program, review_text, status, created_at) VALUES (:uid, 5, 'BBA 8th Semester', 'The BBA research methodology and survey questionnaire template for Deukhuri Bahumukhi Campus was outstanding. My proposal was approved on the first submission with high distinction!', 'approved', NOW() - INTERVAL 2 DAY)")
                            ->execute([':uid' => $kId]);
                    }
                }
            } catch (Exception $eStudents) {}

            $migrated = true;
        } catch (Exception $migrateErr) {
            error_log("Database Auto-Migration notice: " . $migrateErr->getMessage());
        }
    }
} catch (PDOException $e) {
    error_log("Database Connection Error: " . $e->getMessage());

    // 1. Auto-Provision Database if it does not exist yet (Code 1049)
    if ((int)$e->getCode() === 1049 && (PHP_SAPI === 'cli' || isset($_GET['setup_db']))) {
        try {
            // Connect to MySQL server without selecting a database
            $pdoInit = new PDO("mysql:host={$db_host};port={$db_port};charset=utf8mb4", $db_user, $db_pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            $pdoInit->exec("CREATE DATABASE IF NOT EXISTS `{$db_name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
            $pdoInit->exec("USE `{$db_name}`;");

            // Execute database.sql if present
            $sqlFile = dirname(__DIR__) . '/database.sql';
            if (file_exists($sqlFile)) {
                $sqlContent = file_get_contents($sqlFile);
                $statements = array_filter(array_map('trim', explode(';', $sqlContent)));
                foreach ($statements as $stmt) {
                    if (!empty($stmt)) {
                        try {
                            $pdoInit->exec($stmt);
                        } catch (Exception $sqlErr) {}
                    }
                }
            }

            // Successfully initialized, establish standard connection
            $pdo = new PDO($dsn, $db_user, $db_pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ]);

            // Continue normally without error
            goto migration_check;
        } catch (Exception $autoCreateErr) {
            error_log("Database Auto-Creation failed: " . $autoCreateErr->getMessage());
        }
    }

    if (defined('APP_ENV') && APP_ENV === 'production') {
        http_response_code(503);
        die("<!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <title>Service Unavailable | AcademicHub Nepal</title>
            <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css' rel='stylesheet'>
        </head>
        <body class='bg-light d-flex align-items-center justify-content-center' style='min-height:100vh;'>
            <div class='card border-0 shadow-sm p-4 p-md-5 text-center' style='max-width:500px; border-radius:16px;'>
                <div class='mb-3 text-warning'><svg xmlns='http://www.w3.org/2000/svg' width='48' height='48' fill='currentColor' class='bi bi-database-exclamation' viewBox='0 0 16 16'><path d='M8 1c-1.573 0-3.022.289-4.096.777C2.823 2.268 2 2.949 2 4s.823 1.732 1.904 2.223C4.978 6.711 6.427 7 8 7s3.022-.289 4.096-.777C13.177 5.732 14 5.051 14 4s-.823-1.732-1.904-2.223C11.022 1.289 9.573 1 8 1'/><path d='M2 7v-.839c.457.432 1.004.751 1.49.972C4.646 7.64 6.277 8 8 8s3.354-.36 4.51-.867c.486-.22 1.033-.54 1.49-.972V7c0 .762-.439 1.424-1.189 1.87A9 9 0 0 1 8 9.5c-1.393 0-2.673-.186-3.811-.63C3.439 8.424 3 7.762 3 7'/><path d='M2 10.161V11c0 1.051.823 1.732 1.904 2.223C4.978 13.711 6.427 14 8 14s3.022-.289 4.096-.777C13.177 12.732 14 12.051 14 11v-.839c-.457.432-1.004.751-1.49.972-1.156.507-2.787.867-4.51.867s-3.354-.36-4.51-.867c-.486-.22-1.033-.54-1.49-.972'/><path d='M16 12.5a3.5 3.5 0 1 1-7 0 3.5 3.5 0 0 1 7 0m-3.5-2a.5.5 0 0 0-.5.5v1.5a.5.5 0 0 0 1 0V11a.5.5 0 0 0-.5-.5m0 4a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1'/></svg></div>
                <h4 class='fw-bold text-dark'>Service Temporarily Unavailable</h4>
                <p class='text-muted small'>Our database engine is undergoing brief maintenance or reconnecting. Please check back shortly.</p>
                <a href='javascript:location.reload()' class='btn btn-primary rounded-pill px-4'>Retry Connection</a>
            </div>
        </body>
        </html>");
    } else {
        die("<!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <title>Database Connection | AcademicHub Nepal</title>
            <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css' rel='stylesheet'>
            <link href='https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css' rel='stylesheet'>
        </head>
        <body class='bg-light py-5'>
            <div class='container' style='max-width: 640px;'>
                <div class='card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white'>
                    <div class='d-flex align-items-center gap-3 mb-3'>
                        <div class='bg-warning bg-opacity-10 text-warning rounded-circle d-flex align-items-center justify-content-center' style='width:52px; height:52px; font-size:1.6rem;'>
                            <i class='bi bi-hdd-network-fill'></i>
                        </div>
                        <div>
                            <h4 class='fw-bold text-dark mb-0'>Database Connection Notice</h4>
                            <span class='text-muted small'>AcademicHub Nepal Local Environment</span>
                        </div>
                    </div>

                    <p class='text-secondary small mb-3'>
                        AcademicHub Nepal was unable to connect to the MySQL server on <code>{$db_host}:{$db_port}</code>.
                    </p>

                    <div class='p-3 bg-light rounded-3 border mb-4'>
                        <h6 class='fw-bold text-dark small mb-2'><i class='bi bi-tools text-primary me-1'></i> How to resolve in XAMPP:</h6>
                        <ol class='small text-muted mb-0 ps-3' style='line-height:1.8;'>
                            <li>Open the <strong>XAMPP Control Panel</strong> on your PC.</li>
                            <li>Click the <strong>\"Start\"</strong> button next to <strong>MySQL</strong>.</li>
                            <li>Once the MySQL module turns green (Port 3306), refresh this page.</li>
                        </ol>
                    </div>

                    <div class='small text-muted font-monospace bg-light p-2 rounded border mb-4 text-truncate' title='" . htmlspecialchars($e->getMessage()) . "'>
                        " . htmlspecialchars($e->getMessage()) . "
                    </div>

                    <div class='d-flex gap-2'>
                        <a href='javascript:location.reload()' class='btn btn-primary rounded-pill px-4 fw-semibold'>
                            <i class='bi bi-arrow-clockwise me-1'></i> Refresh Page
                        </a>
                        <a href='" . BASE_URL . "/setup_db.php' class='btn btn-outline-secondary rounded-pill px-3'>
                            <i class='bi bi-database-gear me-1'></i> Run Setup Tool
                        </a>
                    </div>
                </div>
            </div>
        </body>
        </html>");
    }
}

migration_check:
