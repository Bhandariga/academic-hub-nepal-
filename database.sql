-- ==============================================================================
-- AcademicHub Nepal - Database Schema & Seed Data
-- Database Name: academic_hub
-- Fully compatible with MySQL 5.7+ / MariaDB 10.x / XAMPP
-- ==============================================================================

CREATE DATABASE IF NOT EXISTS `academic_hub` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `academic_hub`;

-- -----------------------------------------------------------------------------
-- Table: users
-- Roles: 'student', 'admin', 'superadmin'
-- Passwords hashed with password_hash(..., PASSWORD_BCRYPT)
-- Default Super Admin: superadmin@gmail.com / 123456
-- Default admin: admin@academichub.com / admin123
-- Default student: student@demo.com / password123
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(30) DEFAULT NULL,
  `campus` VARCHAR(100) DEFAULT NULL,
  `profile_image` VARCHAR(255) DEFAULT NULL,
  `role` ENUM('student', 'admin', 'superadmin') NOT NULL DEFAULT 'student',
  `admin_role` VARCHAR(60) DEFAULT NULL,
  `permissions` TEXT DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `last_login` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_role` (`role`),
  INDEX `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------------------------
-- Table: admin_activity_logs
-- Audit log of administrative operations, role changes, and catalog updates
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `admin_activity_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `admin_id` INT NOT NULL,
  `action` VARCHAR(100) NOT NULL,
  `details` TEXT DEFAULT NULL,
  `target_type` VARCHAR(50) DEFAULT NULL,
  `target_id` INT DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_admin_id` (`admin_id`),
  INDEX `idx_action` (`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- -----------------------------------------------------------------------------
-- Table: resources
-- Core academic materials classified by Campus, Program, Year, Subject, Document Type
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `resources` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT NOT NULL,
  `campus` VARCHAR(100) NOT NULL,
  `program` VARCHAR(50) NOT NULL,
  `year` VARCHAR(50) NOT NULL,
  `subject` VARCHAR(100) NOT NULL,
  `document_type` VARCHAR(50) NOT NULL,
  `pages` INT NOT NULL DEFAULT 1,
  `file_format` VARCHAR(20) NOT NULL DEFAULT 'PDF',
  `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `file_path` VARCHAR(255) NOT NULL,
  `preview_image` VARCHAR(255) NOT NULL,
  `featured` TINYINT(1) NOT NULL DEFAULT 0,
  `downloads_count` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------------------------
-- Table: orders
-- Tracks checkout, generated transaction_uuid, eSewa status and code
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `orders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `resource_id` INT NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `payment_method` VARCHAR(50) NOT NULL DEFAULT 'ESEWA_QR',
  `transaction_uuid` VARCHAR(100) NOT NULL UNIQUE,
  `payment_status` ENUM('PENDING', 'PENDING_VERIFICATION', 'PAID', 'FAILED', 'CANCELLED') NOT NULL DEFAULT 'PENDING',
  `transaction_code` VARCHAR(100) DEFAULT NULL,
  `payment_proof` VARCHAR(255) DEFAULT NULL,
  `verified_at` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_user_order` (`user_id`),
  INDEX `idx_resource_order` (`resource_id`),
  CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_orders_resource` FOREIGN KEY (`resource_id`) REFERENCES `resources` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------------------------
-- Table: downloads
-- Secure log of every downloaded file
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `downloads` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `resource_id` INT NOT NULL,
  `order_id` INT DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `download_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_user_download` (`user_id`),
  CONSTRAINT `fk_downloads_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_downloads_resource` FOREIGN KEY (`resource_id`) REFERENCES `resources` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------------------------
-- Table: service_requests
-- For custom formatting, APA 7, proofreading, methodology consulting
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `service_requests` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `phone` VARCHAR(30) NOT NULL,
  `campus` VARCHAR(100) DEFAULT NULL,
  `program` VARCHAR(50) DEFAULT NULL,
  `service_type` VARCHAR(100) NOT NULL,
  `deadline` DATE DEFAULT NULL,
  `message` TEXT NOT NULL,
  `status` ENUM('Pending', 'In Progress', 'Completed', 'Cancelled') DEFAULT 'Pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------------------------
-- Table: reviews
-- Verified student ratings and testimonials displayed dynamically on website
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `reviews` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `rating` TINYINT(1) NOT NULL DEFAULT 5,
  `program` VARCHAR(100) DEFAULT NULL,
  `review_text` TEXT NOT NULL,
  `status` ENUM('approved', 'pending', 'hidden') NOT NULL DEFAULT 'approved',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_review_user` (`user_id`),
  INDEX `idx_review_status` (`status`),
  CONSTRAINT `fk_reviews_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==============================================================================
-- SEED DATA
-- Default Passwords:
-- 'superadmin@gmail.com' -> '123456'
-- 'admin@academichub.com' -> 'admin123'
-- 'student@demo.com' -> 'password123'
-- ==============================================================================

INSERT INTO `users` (`id`, `name`, `email`, `password`, `phone`, `campus`, `role`, `admin_role`, `permissions`, `is_active`) VALUES
(1, 'Chief Super Administrator', 'superadmin@gmail.com', '$2y$10$f66sYq6Qo12WlJpMfl844eO5vVq718Y7lYk5bSj/XkL7768bF2tCe', '9800000000', 'Tribhuvan University HQ', 'superadmin', 'Super Administrator', '["all","manage_resources","manage_orders","manage_students","manage_admins","view_reports","system_settings"]', 1),
(2, 'Admin AcademicHub', 'admin@academichub.com', '$2y$10$tZ2E8hJ4eTfxM50hPZ7h4OzM9eS8cT.P2Y24/5gB56P5q8nLq321W', '9800000001', 'Tribhuvan University', 'admin', 'General Administrator', '["manage_resources","manage_orders","manage_students","view_reports"]', 1),
(3, 'Aayush Sharma', 'student@demo.com', '$2y$10$wBfF2mK6iUk3aVzFwI7E3.gD.w8zG9N0yK2J8V3/sXbQ5pLq9876O', '9841234567', 'Mark International College', 'student', NULL, NULL, 1),
(4, 'Pooja Rawat', 'pooja@demo.com', '$2y$10$wBfF2mK6iUk3aVzFwI7E3.gD.w8zG9N0yK2J8V3/sXbQ5pLq9876O', '9841987654', 'Babai Campus', 'student', NULL, NULL, 1),
(5, 'Sagar Adhikari', 'sagar@demo.com', '$2y$10$wBfF2mK6iUk3aVzFwI7E3.gD.w8zG9N0yK2J8V3/sXbQ5pLq9876O', '9841122334', 'Mahendra Multiple Campus', 'student', NULL, NULL, 1)
ON DUPLICATE KEY UPDATE `id`=`id`;


-- Insert Initial Resources
INSERT INTO `resources` (`id`, `title`, `description`, `campus`, `program`, `year`, `subject`, `document_type`, `pages`, `file_format`, `price`, `file_path`, `preview_image`, `featured`, `downloads_count`) VALUES
(1, 'Impact of Digital Banking on Customer Satisfaction', 'A comprehensive empirical project report analyzing customer retention, mobile banking adoption, and service quality dimensions across commercial banks in Nepal. Follows complete TU BBS 4th year structure with structured questionnaires, SPSS correlation analysis, and APA 7 references.', 'Mark International College', 'BBS', 'Fourth Year', 'Finance', 'Project Report', 64, 'PDF', 399.00, 'sample_bbs_digital_banking.pdf', 'preview_digital_banking.jpg', 1, 142),

(2, 'Marketing Research Proposal Template', 'Structured academic proposal template designed specifically for BBA and BBS students. Includes Problem Statement framework, Research Questions, Theoretical Concept Matrix, Methodology design, Sampling techniques, and standard Gantt chart schedule.', 'Babai Campus', 'BBA', 'Third Year', 'Marketing', 'Research Proposal', 24, 'DOCX', 299.00, 'sample_marketing_proposal.docx', 'preview_marketing_proposal.jpg', 1, 98),

(3, 'Ma In Societies Final Year Project Template & System Documentation', 'Complete Software Engineering Documentation template adhering strictly to Tribhuvan University Ma In Societies guidelines. Includes SRS format, UML Diagrams (Use Case, Sequence, ERD), Architectural blueprints, Test cases, and Implementation chapter templates.', 'Gorkha Campus', 'Ma In Societies', 'Fourth Year', 'Computer Science', 'Project Report', 82, 'PDF', 499.00, 'sample_bca_project_template.pdf', 'preview_bca_project.jpg', 1, 185),

(4, 'Credit Risk Management and Bank Performance Reference Project', 'Detailed financial reference project analyzing Non-Performing Loans (NPL), Capital Adequacy Ratio (CAR), and Return on Assets (ROA) of class-A commercial banks in Nepal. Includes multi-year financial tables, ratio analysis, and regression model interpretation.', 'Gorkha Campus', 'BBS', 'Fourth Year', 'Finance', 'Project Report', 72, 'PDF', 399.00, 'sample_credit_risk_bbs.pdf', 'preview_credit_risk.jpg', 0, 76),

(5, 'Commercial Bank Customer Survey Questionnaire Template', 'Standardized 5-point Likert scale questionnaire instrument measuring digital banking satisfaction, trust, usability, and responsiveness. Pre-tested reliability matrix (Cronbach Alpha > 0.82) ready for immediate field deployment.', 'Babai Campus', 'BBS', 'Fourth Year', 'Finance', 'Questionnaire', 6, 'DOCX', 199.00, 'sample_survey_questionnaire.docx', 'preview_questionnaire.jpg', 0, 115),

(6, 'APA 7 Research Report & Formatting Master Guide', 'Complete formatting master kit for Bachelor and Master theses. Pre-configured margins (1.5 inch left, 1 inch others), running heads, heading hierarchies (Level 1-5), in-text citation styles, table numbering, and bibliography examples.', 'Mahendra Multiple Campus', 'MBS', 'Second Year', 'Management', 'Template', 35, 'PDF', 249.00, 'sample_apa7_master_guide.pdf', 'preview_apa_guide.jpg', 1, 230),

(7, 'Employee Motivation and Job Satisfaction in Nepalese SMEs', 'Field research reference report focusing on Herzberg dual-factor theory applied to manufacturing SMEs in Lumbini Province. Features structured findings, employee survey breakdown, and actionable managerial recommendations.', 'Deukhuri Bahumukhi Campus', 'BBA', 'Fourth Year', 'Accounting', 'Project Report', 58, 'PDF', 349.00, 'sample_employee_motivation.pdf', 'preview_employee_motivation.jpg', 0, 64),

(8, 'E-Commerce Adoption Framework Among Retailers in Nepal', 'Master of Business Studies (MBS) research dissertation reference examining Technology Acceptance Model (TAM) variables across urban Kathmandu and Pokhara retail merchants.', 'Other Campuses', 'MBS', 'Second Year', 'Management', 'Project Report', 94, 'PDF', 599.00, 'sample_ecommerce_mbs.pdf', 'preview_ecommerce_mbs.jpg', 1, 89),

(9, 'Strategic Financial Performance & Risk Governance - MBA Master Dissertation', 'Comprehensive executive MBA Master dissertation analyzing strategic capital budgeting, corporate risk governance, and digital transformation in Nepalese commercial banking sector. Follows TU Faculty of Management MBA guidelines with complete regression models and APA 7 citations.', 'Mark International College', 'MBA', 'Second Year', 'Finance', 'Project Report', 112, 'PDF', 599.00, 'sample_bbs_digital_banking.pdf', 'preview_digital_banking.jpg', 1, 84)
ON DUPLICATE KEY UPDATE `id`=`id`;

-- Insert Initial Demo Orders
INSERT INTO `orders` (`id`, `user_id`, `resource_id`, `amount`, `transaction_uuid`, `payment_status`, `transaction_code`, `created_at`) VALUES
(1, 2, 1, 399.00, 'ORD-2026-DEMO-001', 'PAID', 'ESEWA-TXN-8841290', NOW() - INTERVAL 2 DAY),
(2, 2, 3, 499.00, 'ORD-2026-DEMO-002', 'PAID', 'ESEWA-TXN-9123847', NOW() - INTERVAL 1 DAY)
ON DUPLICATE KEY UPDATE `id`=`id`;

-- Insert Demo Downloads
INSERT INTO `downloads` (`id`, `user_id`, `resource_id`, `order_id`, `ip_address`, `download_date`) VALUES
(1, 2, 1, 1, '127.0.0.1', NOW() - INTERVAL 2 DAY),
(2, 2, 3, 2, '127.0.0.1', NOW() - INTERVAL 12 HOUR)
ON DUPLICATE KEY UPDATE `id`=`id`;

-- Insert Verified Reviews
INSERT INTO `reviews` (`id`, `user_id`, `rating`, `program`, `review_text`, `status`, `created_at`) VALUES
(1, 3, 5, 'BBS 4th Year', 'The BBS 4th Year Finance project sample saved me weeks of confusion. The chapter organization, financial ratios, and statistical tables were exactly aligned with our college guidelines.', 'approved', NOW() - INTERVAL 5 DAY),
(2, 4, 5, 'BBA 8th Semester', 'The eSewa QR payment and instant unlock feature was completely hassle-free. The APA 7th Edition bibliography template helped me pass my internal defense without a single citation correction.', 'approved', NOW() - INTERVAL 3 DAY),
(3, 5, 5, 'Ma In Societies 4th Year', 'The system documentation and UML diagrams template gave me an absolute head start for my final software engineering viva. Excellent service and responsive support for TU students.', 'approved', NOW() - INTERVAL 1 DAY)
ON DUPLICATE KEY UPDATE `id`=`id`;

-- -----------------------------------------------------------------------------
-- Table: team_members
-- Leadership, Academic Advisors, Subject Specialists, and Technical Team
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `team_members` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `role_title` VARCHAR(100) NOT NULL,
  `department` VARCHAR(100) DEFAULT NULL,
  `bio` TEXT DEFAULT NULL,
  `photo` VARCHAR(255) DEFAULT NULL,
  `email` VARCHAR(150) DEFAULT NULL,
  `phone` VARCHAR(30) DEFAULT NULL,
  `linkedin_url` VARCHAR(255) DEFAULT NULL,
  `facebook_url` VARCHAR(255) DEFAULT NULL,
  `twitter_url` VARCHAR(255) DEFAULT NULL,
  `display_order` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_team_order` (`display_order`),
  INDEX `idx_team_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert Seed Demo Team Members
INSERT INTO `team_members` (`id`, `name`, `role_title`, `department`, `bio`, `photo`, `email`, `phone`, `linkedin_url`, `facebook_url`, `twitter_url`, `display_order`, `is_active`, `created_at`) VALUES
(1, 'Prof. Dr. Ram Chandra Pokharel', 'Chief Academic Advisor', 'Faculty of Management, Tribhuvan University', 'Over 22 years of academic leadership guiding BBS and MBA dissertations and project works across Tribhuvan University affiliated colleges.', NULL, 'advisor@academichub.edu.np', '+977-9851023456', 'https://linkedin.com', 'https://facebook.com', 'https://twitter.com', 1, 1, NOW()),
(2, 'Er. Prakash Shrestha', 'Founder & Lead Systems Architect', 'Technology & Academic Infrastructure', 'Senior software engineer and educational technology advocate committed to digitizing verified academic curricula, notes, and reference projects for Nepalese college students.', NULL, 'prakash@academichub.com', '+977-9801234567', 'https://linkedin.com', 'https://facebook.com', NULL, 2, 1, NOW()),
(3, 'Sita Thapa, M.Phil', 'Head of BBS & BBA Content Review', 'Mark International College / TU Affiliated', 'Senior lecturer in Financial Management with 14+ years of classroom research, curriculum mentoring, and project report methodology guidance.', NULL, 'sita.thapa@academichub.com', '+977-9841889900', 'https://linkedin.com', NULL, NULL, 3, 1, NOW()),
(4, 'Bikram Basnet, MBA', 'Research & Defense Coordinator', 'Babai Campus Faculty Liaison', 'Specialist in corporate finance and marketing field research, guiding over 1,500 undergraduate candidates through thesis defense and APA 7th edition formatting.', NULL, 'bikram@academichub.com', '+977-9861234567', 'https://linkedin.com', 'https://facebook.com', NULL, 4, 1, NOW()),
(5, 'Anjali Sharma, MIT', 'Digital Library & Student Experience Lead', 'Gorkha Campus IT Liaison', 'Oversees computer science documentation standards, UML architecture models, and ensures 24/7 digital resource accessibility for Ma In Societies and IT undergraduates.', NULL, 'anjali.sharma@academichub.com', '+977-9811223344', 'https://linkedin.com', NULL, 'https://twitter.com', 5, 1, NOW())
ON DUPLICATE KEY UPDATE `id`=`id`;
