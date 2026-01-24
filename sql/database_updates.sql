-- =====================================================
-- UPDATED DATABASE SCHEMA FOR HOD PROPOSAL MANAGEMENT
-- Added: Departments, Grant, GrantAllocation tables
-- =====================================================

-- --------------------------------------------------------
-- Add department_id to users table
-- --------------------------------------------------------
ALTER TABLE `users` ADD COLUMN `department_id` INT(11) DEFAULT NULL AFTER `role`;

-- --------------------------------------------------------
-- Add missing fields to proposals table
-- --------------------------------------------------------
ALTER TABLE `proposals` ADD COLUMN `department_id` INT(11) DEFAULT NULL AFTER `priority`;

-- --------------------------------------------------------
-- Create departments table
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `departments` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `code` VARCHAR(50) DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `available_budget` DECIMAL(12, 2) DEFAULT 0.00,
  `total_budget` DECIMAL(12, 2) DEFAULT 0.00,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `dept_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert sample departments
INSERT INTO `departments` (`name`, `code`, `description`, `available_budget`, `total_budget`) VALUES
('Engineering', 'ENG', 'Faculty of Engineering', 500000.00, 1000000.00),
('Science', 'SCI', 'Faculty of Science', 400000.00, 800000.00),
('Business', 'BUS', 'Faculty of Business', 350000.00, 700000.00);

-- --------------------------------------------------------
-- Create fund table (as per data dictionary)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `fund` (
  `fund_id` INT(11) NOT NULL AUTO_INCREMENT,
  `proposal_id` INT(11) NOT NULL,
  `grant_number` VARCHAR(50) NOT NULL,
  `amount_allocated` DECIMAL(12, 2) NOT NULL,
  `amount_spent` DECIMAL(12, 2) DEFAULT 0.00,
  `amount_remaining` DECIMAL(12, 2) DEFAULT 0.00,
  `start_date` DATE DEFAULT NULL,
  `end_date` DATE DEFAULT NULL,
  `status` ENUM('Active', 'Completed', 'Terminated') DEFAULT 'Active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`fund_id`),
  UNIQUE KEY `grant_number` (`grant_number`),
  FOREIGN KEY (`proposal_id`) REFERENCES `proposals` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Create grant_allocation table (as per data dictionary)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `grant_allocation` (
  `allocation_id` INT(11) NOT NULL AUTO_INCREMENT,
  `fund_id` INT(11) NOT NULL,
  `requested_budget` DECIMAL(12, 2) NOT NULL,
  `approved_budget` DECIMAL(12, 2) NOT NULL,
  `allocation_notes` TEXT DEFAULT NULL,
  `approved_by`  INT(10) UNSIGNED NOT NULL,
  `approval_date` DATE NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`allocation_id`),
  FOREIGN KEY (`fund_id`) REFERENCES `fund` (`fund_id`) ON DELETE CASCADE,
  FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Create budget_item table (as per data dictionary)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `budget_item` (
  `budget_item_id` INT(11) NOT NULL AUTO_INCREMENT,
  `fund_id` INT(11) NOT NULL,
  `category` ENUM('Travel', 'Equipment', 'Salary', 'Other') DEFAULT 'Other',
  `item_name` VARCHAR(255) NOT NULL,
  `allocated_amount` DECIMAL(12, 2) NOT NULL,
  `spent_amount` DECIMAL(12, 2) DEFAULT 0.00,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`budget_item_id`),
  FOREIGN KEY (`fund_id`) REFERENCES `fund` (`fund_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Create expenditure table (as per data dictionary)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `expenditure` (
  `expenditure_id` INT(11) NOT NULL AUTO_INCREMENT,
  `fund_id` INT(11) NOT NULL,
  `budget_item_id` INT(11) DEFAULT NULL,
  `amount` DECIMAL(12, 2) NOT NULL,
  `transaction_date` DATE NOT NULL,
  `description` TEXT NOT NULL,
  `receipt_file_path` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('Pending', 'Approved') DEFAULT 'Pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`expenditure_id`),
  FOREIGN KEY (`fund_id`) REFERENCES `fund` (`fund_id`) ON DELETE CASCADE,
  FOREIGN KEY (`budget_item_id`) REFERENCES `budget_item` (`budget_item_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Create grant_document table (as per data dictionary)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `grant_document` (
  `document_id` INT(11) NOT NULL AUTO_INCREMENT,
  `fund_id` INT(11) NOT NULL,
  `document_type` ENUM('Receipt', 'Legal', 'Report', 'Other') DEFAULT 'Other',
  `filename` VARCHAR(255) NOT NULL,
  `file_path` VARCHAR(255) NOT NULL,
  `uploaded_by`  INT(10) UNSIGNED NOT NULL,
  `current_version` INT(11) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`document_id`),
  FOREIGN KEY (`fund_id`) REFERENCES `fund` (`fund_id`) ON DELETE CASCADE,
  FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Create proposal_rubric table (for HOD evaluation)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `proposal_rubric` (
  `rubric_id` INT(11) NOT NULL AUTO_INCREMENT,
  `proposal_id` int(11) NOT NULL,
  `hod_id`  INT(10) UNSIGNED NOT NULL,
  `outcome_score` INT(11) DEFAULT 0,
  `impact_score` INT(11) DEFAULT 0,
  `alignment_score` INT(11) DEFAULT 0,
  `funding_score` INT(11) DEFAULT 0,
  `total_score` INT(11) DEFAULT 0,
  `hod_notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`rubric_id`),
  FOREIGN KEY (`proposal_id`) REFERENCES `proposals` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`hod_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Create hod_tier_assignment table (for tier list management)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `hod_tier_assignment` (
  `assignment_id` INT(11) NOT NULL AUTO_INCREMENT,
  `proposal_id` int(11) NOT NULL,
  `hod_id` INT(10) UNSIGNED NOT NULL,
  `tier` ENUM('top', 'middle', 'bottom') DEFAULT 'bottom',
  `approved_budget` DECIMAL(12, 2) DEFAULT NULL,
  `is_approved` TINYINT(1) DEFAULT 0,
  `approval_date` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`assignment_id`),
  UNIQUE KEY `unique_proposal_hod` (`proposal_id`, `hod_id`),
  FOREIGN KEY (`proposal_id`) REFERENCES `proposals` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`hod_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Create research_progress table (for Research Progress Tracking)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `research_progress` (
  `progress_id` INT(11) NOT NULL AUTO_INCREMENT,
  `fund_id` INT(11) NOT NULL,
  `milestone_name` VARCHAR(255) NOT NULL,
  `milestone_description` TEXT DEFAULT NULL,
  `target_date` DATE DEFAULT NULL,
  `completion_date` DATE DEFAULT NULL,
  `status` ENUM('Pending', 'In Progress', 'Completed') DEFAULT 'Pending',
  `completion_percentage` INT(11) DEFAULT 0,
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`progress_id`),
  FOREIGN KEY (`fund_id`) REFERENCES `fund` (`fund_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Update users table to add HOD department assignment
-- --------------------------------------------------------
-- Note: Make sure to update existing HOD users with their department_id
UPDATE `users` SET `department_id` = 1 WHERE `email` = '3@mail.com' AND `role` = 'hod';
