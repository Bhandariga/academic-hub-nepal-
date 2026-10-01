-- ==============================================================================
-- AcademicHub Nepal - CMS System Migration
-- Complete Content Management System for Admin Control
-- ==============================================================================

USE `academic_hub`;

-- -----------------------------------------------------------------------------
-- Update resources table with visibility and status fields
-- -----------------------------------------------------------------------------
ALTER TABLE `resources` 
ADD COLUMN `visibility` ENUM('public', 'private', 'purchase_required', 'approval_required') NOT NULL DEFAULT 'public' AFTER `featured`,
ADD COLUMN `status` ENUM('draft', 'published', 'hidden', 'archived') NOT NULL DEFAULT 'published' AFTER `visibility`,
ADD COLUMN `display_order` INT NOT NULL DEFAULT 0 AFTER `status`,
ADD INDEX `idx_visibility` (`visibility`),
ADD INDEX `idx_status` (`status`);

-- -----------------------------------------------------------------------------
-- Table: notifications
-- System-wide notification system for students and admins
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT DEFAULT NULL,
  `title` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `type` ENUM('info', 'success', 'warning', 'error', 'purchase', 'download', 'announcement') NOT NULL DEFAULT 'info',
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `link_url` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_user_notifications` (`user_id`),
  INDEX `idx_read_status` (`is_read`),
  INDEX `idx_type` (`type`),
  CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------------------------
-- Table: access_permissions
-- Granular access control for private resources
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `access_permissions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `resource_id` INT NOT NULL,
  `user_id` INT DEFAULT NULL,
  `campus` VARCHAR(100) DEFAULT NULL,
  `program` VARCHAR(50) DEFAULT NULL,
  `approved_by` INT DEFAULT NULL,
  `status` ENUM('pending', 'approved', 'rejected', 'revoked') NOT NULL DEFAULT 'pending',
  `access_type` ENUM('individual', 'campus', 'program') NOT NULL DEFAULT 'individual',
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_resource_access` (`resource_id`),
  INDEX `idx_user_access` (`user_id`),
  INDEX `idx_status` (`status`),
  CONSTRAINT `fk_access_resource` FOREIGN KEY (`resource_id`) REFERENCES `resources` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_access_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_access_approver` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------------------------
-- Table: content_sections
-- Dynamic content management for homepage sections
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `content_sections` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `section_key` VARCHAR(100) NOT NULL UNIQUE,
  `section_name` VARCHAR(100) NOT NULL,
  `content_type` ENUM('hero', 'statistics', 'campus', 'program', 'resources', 'testimonials', 'team', 'faq', 'notices', 'announcements', 'services', 'pricing', 'footer') NOT NULL,
  `title` VARCHAR(255) DEFAULT NULL,
  `subtitle` TEXT DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `image_url` VARCHAR(255) DEFAULT NULL,
  `button_text` VARCHAR(100) DEFAULT NULL,
  `button_link` VARCHAR(255) DEFAULT NULL,
  `display_order` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_section_key` (`section_key`),
  INDEX `idx_content_type` (`content_type`),
  INDEX `idx_display_order` (`display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------------------------
-- Table: campus_sections
-- Manage campus display on homepage
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `campus_sections` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `image_url` VARCHAR(255) DEFAULT NULL,
  `website_url` VARCHAR(255) DEFAULT NULL,
  `display_order` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_display_order` (`display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------------------------
-- Table: program_sections
-- Manage program display on homepage
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `program_sections` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `icon` VARCHAR(50) DEFAULT NULL,
  `display_order` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_display_order` (`display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------------------------
-- Table: testimonials
-- Manage testimonials displayed on website
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `testimonials` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_name` VARCHAR(100) NOT NULL,
  `student_program` VARCHAR(100) DEFAULT NULL,
  `student_campus` VARCHAR(100) DEFAULT NULL,
  `testimonial_text` TEXT NOT NULL,
  `rating` TINYINT(1) NOT NULL DEFAULT 5,
  `profile_image` VARCHAR(255) DEFAULT NULL,
  `display_order` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_display_order` (`display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------------------------
-- Table: faq_items
-- Manage FAQ items
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `faq_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `question` VARCHAR(255) NOT NULL,
  `answer` TEXT NOT NULL,
  `category` VARCHAR(100) DEFAULT NULL,
  `display_order` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_category` (`category`),
  INDEX `idx_display_order` (`display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------------------------
-- Table: notices
-- Manage notices and announcements
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `notices` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `content` TEXT NOT NULL,
  `type` ENUM('notice', 'announcement', 'alert') NOT NULL DEFAULT 'notice',
  `priority` ENUM('low', 'medium', 'high', 'urgent') NOT NULL DEFAULT 'medium',
  `start_date` DATE DEFAULT NULL,
  `end_date` DATE DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_type` (`type`),
  INDEX `idx_priority` (`priority`),
  INDEX `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------------------------
-- Table: services
-- Manage services displayed on website
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `services` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT NOT NULL,
  `icon` VARCHAR(50) DEFAULT NULL,
  `price` DECIMAL(10,2) DEFAULT NULL,
  `display_order` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_display_order` (`display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------------------------
-- Table: pricing_plans
-- Manage pricing plans
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pricing_plans` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `plan_name` VARCHAR(100) NOT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `duration` VARCHAR(50) DEFAULT NULL,
  `features` TEXT NOT NULL,
  `is_popular` TINYINT(1) NOT NULL DEFAULT 0,
  `display_order` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_display_order` (`display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------------------------
-- Table: user_activity_logs
-- Track user activity for admin monitoring
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_activity_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `action` VARCHAR(100) NOT NULL,
  `details` TEXT DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_user_activity` (`user_id`),
  INDEX `idx_action` (`action`),
  CONSTRAINT `fk_activity_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------------------------
-- Seed initial content sections
-- -----------------------------------------------------------------------------
INSERT INTO `content_sections` (`section_key`, `section_name`, `content_type`, `title`, `subtitle`, `description`, `button_text`, `button_link`, `display_order`, `is_active`) VALUES
('hero_main', 'Main Hero Section', 'hero', 'Academic Excellence Made Simple', 'Premium Project Reports, Research Templates & Academic Resources for TU Students', 'Access verified BBS, BBA, MBA, MBS, and Ma In Societies project reports, research proposals, and formatting guides aligned with Tribhuvan University curriculum.', 'Browse Resources', '/resources.php', 1, 1),
('stats_main', 'Statistics Section', 'statistics', 'Trusted by Students Across Nepal', NULL, NULL, NULL, NULL, 2, 1)
ON DUPLICATE KEY UPDATE `section_key`=`section_key`;

-- -----------------------------------------------------------------------------
-- Seed initial campus sections
-- -----------------------------------------------------------------------------
INSERT INTO `campus_sections` (`name`, `description`, `display_order`, `is_active`) VALUES
('Mark International College', 'Leading TU-affiliated college in Butwal with excellence in management studies.', 1, 1),
('Mahendra Multiple Campus', 'Premier educational institution in Butwal offering diverse academic programs.', 2, 1),
('Deukhuri Bahumukhi Campus', 'Quality education hub in Dang district serving rural students.', 3, 1),
('Babai Campus', 'Reputed college in Nepalgunj known for academic excellence.', 4, 1),
('Gorkha Campus', 'Historic institution providing quality education in Gorkha district.', 5, 1)
ON DUPLICATE KEY UPDATE `name`=`name`;

-- -----------------------------------------------------------------------------
-- Seed initial program sections
-- -----------------------------------------------------------------------------
INSERT INTO `program_sections` (`name`, `description`, `icon`, `display_order`, `is_active`) VALUES
('BBS', 'Bachelor of Business Studies - Comprehensive business management curriculum', 'bi-mortarboard', 1, 1),
('BBA', 'Bachelor of Business Administration - Modern business education', 'bi-briefcase', 2, 1),
('MBA', 'Master of Business Administration - Advanced management studies', 'bi-building', 3, 1),
('MBS', 'Master of Business Studies - Specialized business masters', 'bi-graph-up', 4, 1),
('Ma In Societies', 'Bachelor of Computer Application - IT and software development', 'bi-pc-display', 5, 1)
ON DUPLICATE KEY UPDATE `name`=`name`;

-- -----------------------------------------------------------------------------
-- Seed initial testimonials
-- -----------------------------------------------------------------------------
INSERT INTO `testimonials` (`student_name`, `student_program`, `student_campus`, `testimonial_text`, `rating`, `display_order`, `is_active`) VALUES
('Aayush Sharma', 'BBS 4th Year', 'Mark International College', 'The BBS 4th Year Finance project sample saved me weeks of confusion. The chapter organization, financial ratios, and statistical tables were exactly aligned with our college guidelines.', 5, 1, 1),
('Pooja Rawat', 'BBA 8th Semester', 'Babai Campus', 'The eSewa QR payment and instant unlock feature was completely hassle-free. The APA 7th Edition bibliography template helped me pass my internal defense without a single citation correction.', 5, 2, 1),
('Sagar Adhikari', 'Ma In Societies 4th Year', 'Mahendra Multiple Campus', 'The system documentation and UML diagrams template gave me an absolute head start for my final software engineering viva. Excellent service and responsive support for TU students.', 5, 3, 1)
ON DUPLICATE KEY UPDATE `student_name`=`student_name`;

-- -----------------------------------------------------------------------------
-- Seed initial FAQ items
-- -----------------------------------------------------------------------------
INSERT INTO `faq_items` (`question`, `answer`, `category`, `display_order`, `is_active`) VALUES
('How do I download purchased resources?', 'After successful payment via eSewa, your download will be automatically unlocked. You can access your purchased resources from your dashboard or directly from the resource page.', 'General', 1, 1),
('What payment methods do you accept?', 'We accept eSewa QR payment, the most popular digital payment method in Nepal. Simply scan the QR code and complete the payment to unlock your download.', 'Payment', 2, 1),
('Are the project reports plagiarism-free?', 'All our resources are original reference materials created by academic experts. They serve as templates and guides for your own work. We recommend customizing them to meet your specific requirements.', 'Resources', 3, 1),
('Can I get a refund if not satisfied?', 'Due to the digital nature of our products, we do not offer refunds. However, if you encounter any issues with your download, our support team will assist you promptly.', 'Policy', 4, 1),
('How do I contact support?', 'You can reach our support team through the contact form on our website or email us at support@academichub.com. We typically respond within 24 hours.', 'Support', 5, 1)
ON DUPLICATE KEY UPDATE `question`=`question`;

-- -----------------------------------------------------------------------------
-- Seed initial services
-- -----------------------------------------------------------------------------
INSERT INTO `services` (`title`, `description`, `icon`, `price`, `display_order`, `is_active`) VALUES
('Custom Project Report Writing', 'Get a personalized project report written according to your specific requirements and college guidelines.', 'bi-file-earmark-text', 5000.00, 1, 1),
('APA 7 Formatting Service', 'Professional formatting of your thesis or report according to APA 7th edition standards.', 'bi-text-indent-left', 1500.00, 2, 1),
('Research Consultation', 'One-on-one consultation with academic experts for research methodology and data analysis guidance.', 'bi-chat-dots', 2000.00, 3, 1),
('Proofreading & Editing', 'Comprehensive proofreading and editing of your academic documents for grammar, clarity, and flow.', 'bi-check2-circle', 1000.00, 4, 1)
ON DUPLICATE KEY UPDATE `title`=`title`;

-- -----------------------------------------------------------------------------
-- Seed initial pricing plans
-- -----------------------------------------------------------------------------
INSERT INTO `pricing_plans` (`plan_name`, `price`, `duration`, `features`, `is_popular`, `display_order`, `is_active`) VALUES
('Single Resource', NULL, NULL, 'Access to one purchased resource|Lifetime download access|Email support', 0, 1, 1),
('Student Bundle', 2999.00, '6 months', 'Access to 10 resources|Priority support|Exclusive templates|Campus-specific content', 1, 2, 1),
('Institutional License', 9999.00, '1 year', 'Unlimited access for campus|Bulk download|Admin dashboard|Custom requests support', 0, 3, 1)
ON DUPLICATE KEY UPDATE `plan_name`=`plan_name`;

-- -----------------------------------------------------------------------------
-- Seed initial notices
-- -----------------------------------------------------------------------------
INSERT INTO `notices` (`title`, `content`, `type`, `priority`, `is_active`) VALUES
('New MBA Resources Available', 'We have added new MBA dissertation samples and research templates. Check the resources section for the latest additions.', 'announcement', 'medium', 1),
('Payment System Maintenance', 'eSewa payment system will undergo maintenance on Sunday from 2 AM to 4 AM. Downloads will not be affected.', 'notice', 'low', 1)
ON DUPLICATE KEY UPDATE `title`=`title`;
