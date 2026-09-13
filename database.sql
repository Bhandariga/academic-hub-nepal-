-- ==============================================================================
-- AcademicHub Nepal - Database Schema & Seed Data
-- Database Name: academic_hub
-- Fully compatible with MySQL 5.7+ / MariaDB 10.x / XAMPP
-- ==============================================================================

CREATE DATABASE IF NOT EXISTS `academic_hub` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `academic_hub`;

-- -----------------------------------------------------------------------------
-- Table: users
-- Roles: 'student', 'admin'
-- Passwords hashed with password_hash(..., PASSWORD_BCRYPT)
-- Default student: student@demo.com / password123
-- Default admin: admin@academichub.com / admin123
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(30) DEFAULT NULL,
  `campus` VARCHAR(100) DEFAULT NULL,
  `role` ENUM('student', 'admin') NOT NULL DEFAULT 'student',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
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
  `transaction_uuid` VARCHAR(100) NOT NULL UNIQUE,
  `payment_status` ENUM('PENDING', 'PAID', 'FAILED', 'CANCELLED') NOT NULL DEFAULT 'PENDING',
  `transaction_code` VARCHAR(100) DEFAULT NULL,
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

-- ==============================================================================
-- SEED DATA
-- Default Passwords:
-- 'student@demo.com' -> 'password123'
-- 'admin@academichub.com' -> 'admin123'
-- ==============================================================================

INSERT INTO `users` (`id`, `name`, `email`, `password`, `phone`, `campus`, `role`) VALUES
(1, 'Admin AcademicHub', 'admin@academichub.com', '$2y$10$tZ2E8hJ4eTfxM50hPZ7h4OzM9eS8cT.P2Y24/5gB56P5q8nLq321W', '9800000001', 'Tribhuvan University', 'admin'),
(2, 'Aayush Sharma', 'student@demo.com', '$2y$10$wBfF2mK6iUk3aVzFwI7E3.gD.w8zG9N0yK2J8V3/sXbQ5pLq9876O', '9841234567', 'Gyan Jyoti Campus', 'student')
ON DUPLICATE KEY UPDATE `id`=`id`;

-- Insert Initial Resources
INSERT INTO `resources` (`id`, `title`, `description`, `campus`, `program`, `year`, `subject`, `document_type`, `pages`, `file_format`, `price`, `file_path`, `preview_image`, `featured`, `downloads_count`) VALUES
(1, 'Impact of Digital Banking on Customer Satisfaction', 'A comprehensive empirical project report analyzing customer retention, mobile banking adoption, and service quality dimensions across commercial banks in Nepal. Follows complete TU BBS 4th year structure with structured questionnaires, SPSS correlation analysis, and APA 7 references.', 'Gyan Jyoti Campus', 'BBS', 'Fourth Year', 'Finance', 'Project Report', 64, 'PDF', 399.00, 'sample_bbs_digital_banking.pdf', 'preview_digital_banking.jpg', 1, 142),

(2, 'Marketing Research Proposal Template', 'Structured academic proposal template designed specifically for BBA and BBS students. Includes Problem Statement framework, Research Questions, Theoretical Concept Matrix, Methodology design, Sampling techniques, and standard Gantt chart schedule.', 'Babai Campus', 'BBA', 'Third Year', 'Marketing', 'Research Proposal', 24, 'DOCX', 299.00, 'sample_marketing_proposal.docx', 'preview_marketing_proposal.jpg', 1, 98),

(3, 'BCA Final Year Project Template & System Documentation', 'Complete Software Engineering Documentation template adhering strictly to Tribhuvan University BCA guidelines. Includes SRS format, UML Diagrams (Use Case, Sequence, ERD), Architectural blueprints, Test cases, and Implementation chapter templates.', 'Gorkha Campus', 'BCA', 'Fourth Year', 'Computer Science', 'Project Report', 82, 'PDF', 499.00, 'sample_bca_project_template.pdf', 'preview_bca_project.jpg', 1, 185),

(4, 'Credit Risk Management and Bank Performance Reference Project', 'Detailed financial reference project analyzing Non-Performing Loans (NPL), Capital Adequacy Ratio (CAR), and Return on Assets (ROA) of class-A commercial banks in Nepal. Includes multi-year financial tables, ratio analysis, and regression model interpretation.', 'Gorkha Campus', 'BBS', 'Fourth Year', 'Finance', 'Project Report', 72, 'PDF', 399.00, 'sample_credit_risk_bbs.pdf', 'preview_credit_risk.jpg', 0, 76),

(5, 'Commercial Bank Customer Survey Questionnaire Template', 'Standardized 5-point Likert scale questionnaire instrument measuring digital banking satisfaction, trust, usability, and responsiveness. Pre-tested reliability matrix (Cronbach Alpha > 0.82) ready for immediate field deployment.', 'Babai Campus', 'BBS', 'Fourth Year', 'Finance', 'Questionnaire', 6, 'DOCX', 199.00, 'sample_survey_questionnaire.docx', 'preview_questionnaire.jpg', 0, 115),

(6, 'APA 7 Research Report & Formatting Master Guide', 'Complete formatting master kit for Bachelor and Master theses. Pre-configured margins (1.5 inch left, 1 inch others), running heads, heading hierarchies (Level 1-5), in-text citation styles, table numbering, and bibliography examples.', 'Gyan Jyoti Campus', 'MBS', 'Second Year', 'Management', 'Template', 35, 'PDF', 249.00, 'sample_apa7_master_guide.pdf', 'preview_apa_guide.jpg', 1, 230),

(7, 'Employee Motivation and Job Satisfaction in Nepalese SMEs', 'Field research reference report focusing on Herzberg dual-factor theory applied to manufacturing SMEs in Lumbini Province. Features structured findings, employee survey breakdown, and actionable managerial recommendations.', 'Gyan Jyoti Campus', 'BBA', 'Fourth Year', 'Accounting', 'Project Report', 58, 'PDF', 349.00, 'sample_employee_motivation.pdf', 'preview_employee_motivation.jpg', 0, 64),

(8, 'E-Commerce Adoption Framework Among Retailers in Nepal', 'Master of Business Studies (MBS) research dissertation reference examining Technology Acceptance Model (TAM) variables across urban Kathmandu and Pokhara retail merchants.', 'Other Campuses', 'MBS', 'Second Year', 'Management', 'Project Report', 94, 'PDF', 599.00, 'sample_ecommerce_mbs.pdf', 'preview_ecommerce_mbs.jpg', 1, 89)
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
