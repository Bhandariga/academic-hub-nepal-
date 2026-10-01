<?php
/**
 * AcademicHub Nepal - Home Page
 * File: index.php
 */
require_once __DIR__ . '/config/db.php';

// SEO Configuration for Homepage
$page_title = "Academic Resources Nepal | TU, BBS & BCA Notes | AcademicHub Nepal";
$page_description = "Explore TU notes, BBS notes, BCA resources, project reports and research support for Nepalese university students at AcademicHub Nepal.";
$page_keywords = "AcademicHub Nepal, TU Notes, BBS 4th Year Project Report, BBA Internship Report Nepal, Ma In Societies Project Documentation TU, MBS Thesis Guidelines, Tribhuvan University notes, APA 7 thesis template Nepal, eSewa payment academic notes";
$canonical_url = BASE_URL . "/";
$og_image = BASE_URL . "/assets/images/og_banner.svg";

// Schema.org FAQPage Rich Snippet Data
$page_schema = [
    "@context" => "https://schema.org",
    "@type" => "FAQPage",
    "mainEntity" => [
        [
            "@type" => "Question",
            "name" => "How quickly can I download my resource after paying?",
            "acceptedAnswer" => [
                "@type" => "Answer",
                "text" => "Once your payment is completed via eSewa ePay Gateway or verified via eSewa QR code, your download link is unlocked immediately in your Student Dashboard under My Downloads. You can re-download your files whenever you need."
            ]
        ],
        [
            "@type" => "Question",
            "name" => "Are the reports editable in Microsoft Word?",
            "acceptedAnswer" => [
                "@type" => "Answer",
                "text" => "Yes! Most of our resources include editable DOCX / Word templates along with PDF reference versions. Each listing displays the exact format tag (PDF, DOCX) and total page count before purchase."
            ]
        ],
        [
            "@type" => "Question",
            "name" => "How does the eSewa QR payment verification work?",
            "acceptedAnswer" => [
                "@type" => "Answer",
                "text" => "Simply scan our verified eSewa QR code during checkout using your eSewa mobile app, complete the transfer, and submit your Transaction Reference Code or receipt screenshot. Our administrators verify the transaction code and immediately unlock your document."
            ]
        ],
        [
            "@type" => "Question",
            "name" => "Are these reports compliant with Tribhuvan University (TU) guidelines?",
            "acceptedAnswer" => [
                "@type" => "Answer",
                "text" => "Yes. Every template and sample report adheres to TU Faculty of Management and Institute of Science and Technology standards, including APA 7th edition referencing, standard preliminary page formats, and chapter structures."
            ]
        ],
        [
            "@type" => "Question",
            "name" => "Can I get customized thesis formatting or mentorship?",
            "acceptedAnswer" => [
                "@type" => "Answer",
                "text" => "Yes! Visit our Services page to submit a custom research support request. Our team of experienced academic supervisors provides APA 7 styling, statistical consultation, and proposal review."
            ]
        ]
    ]
];

require_once __DIR__ . '/includes/header.php';

$site_stats = ['students' => 0, 'resources' => 0, 'campuses' => 0, 'downloads' => 0];
try {
    $stats_statement = $pdo->prepare("SELECT (SELECT COUNT(*) FROM users WHERE role = 'student') AS students, (SELECT COUNT(*) FROM resources) AS resources, (SELECT COUNT(DISTINCT campus) FROM resources) AS campuses, (SELECT COUNT(*) FROM downloads) AS downloads");
    $stats_statement->execute(); $site_stats = $stats_statement->fetch();
} catch (PDOException $e) { error_log('Homepage statistics unavailable'); }
// Fetch Featured Resources from Database
try {
    $stmt = $pdo->prepare("SELECT * FROM resources WHERE featured = 1 ORDER BY id ASC LIMIT 6");
    $stmt->execute();
    $featured_resources = $stmt->fetchAll();
    
    // If no featured explicitly tagged, grab latest 6
    if (empty($featured_resources)) {
        $stmt = $pdo->query("SELECT * FROM resources ORDER BY id DESC LIMIT 6");
        $featured_resources = $stmt->fetchAll();
    }

    // Fetch Verified Student Reviews
    $revStmt = $pdo->prepare("
        SELECT r.*, u.name as student_name, u.campus as student_campus, u.profile_image
        FROM reviews r
        JOIN users u ON r.user_id = u.id
        WHERE r.status = 'approved'
        ORDER BY r.id DESC
        LIMIT 6
    ");
    $revStmt->execute();
    $homepage_reviews = $revStmt->fetchAll();
} catch (Exception $e) {
    $featured_resources = [];
    $homepage_reviews = [];
}

// Ensure content management tables exist
try {
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
} catch (Exception $e) {
    // Tables might already exist, continue
}
?>

<?php require __DIR__ . '/includes/home-premium.php'; require __DIR__ . '/includes/footer.php'; ?>
