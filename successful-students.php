<?php
require_once __DIR__.'/config/db.php';

// Ensure successful_students table exists
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

    // Seed if empty
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
    // Table might already exist, continue
}

require_once __DIR__.'/includes/community-cards.php';
$page_title='Successful Students | AcademicHub Nepal';$page_description='Meet the students and original research behind our academic community.';
$rows=content_rows($pdo,'students');require __DIR__.'/includes/header.php'; ?>
<section class="community-hero"><div class="container"><span class="eyebrow">Student journeys</span><h1>Ambition. Research.<br>Achievement.</h1><p>Meet the students shaping their next chapter through thoughtful preparation and original academic work.</p><a href="#student-stories" class="btn btn-primary">Explore their stories <i class="bi bi-arrow-down"></i></a></div></section>
<section class="container py-5" id="student-stories"><div class="section-heading"><div><span class="eyebrow">Our community</span><h2>Successful Students</h2></div><p>Experiences shared with permission.<br>Progress worth celebrating.</p></div><?php render_community_cards($pdo,'students',$rows); ?></section>
<?php require __DIR__.'/includes/footer.php'; ?>
