-- =========================================================================
-- RESEARCH GRANT MANAGEMENT SYSTEM - DATABASE SCHEMA UPDATES
-- =========================================================================
-- This file contains all necessary schema updates to implement the 7 improvements
-- Run these updates on your existing user_db database
-- =========================================================================

-- -------------------------------------------------------------------------
-- 1. ADD REPORT_DEADLINE COLUMN TO MILESTONES TABLE (Requirement #4)
-- -------------------------------------------------------------------------
ALTER TABLE milestones 
ADD COLUMN IF NOT EXISTS report_deadline DATE NULL 
COMMENT 'Deadline for submitting progress report for this milestone'
AFTER target_date;

-- -------------------------------------------------------------------------
-- 2. ENSURE DESCRIPTION COLUMN EXISTS IN MILESTONES (Requirement #5)
-- -------------------------------------------------------------------------
ALTER TABLE milestones 
ADD COLUMN IF NOT EXISTS description TEXT NULL 
COMMENT 'Detailed description of the milestone'
AFTER title;

-- -------------------------------------------------------------------------
-- 3. ENSURE ANNOTATED_FILE COLUMN EXISTS IN REVIEWS TABLE (Requirement #1)
-- -------------------------------------------------------------------------
ALTER TABLE reviews 
ADD COLUMN IF NOT EXISTS annotated_file VARCHAR(500) NULL 
COMMENT 'Path to reviewer-annotated PDF file'
AFTER comments;

-- -------------------------------------------------------------------------
-- 4. ENSURE RECEIPT_PATH IS NOT NULL IN EXPENDITURES (Requirement #6)
-- -------------------------------------------------------------------------
-- First, update any existing NULL values with a placeholder
UPDATE expenditures 
SET receipt_path = 'uploads/receipts/missing_receipt.pdf' 
WHERE receipt_path IS NULL OR receipt_path = '';

-- Then alter the column to be NOT NULL
ALTER TABLE expenditures 
MODIFY COLUMN receipt_path VARCHAR(500) NOT NULL 
COMMENT 'Path to receipt file - MANDATORY for all expenditures';

-- -------------------------------------------------------------------------
-- 5. ADD INDEX FOR FASTER QUERIES
-- -------------------------------------------------------------------------
-- Index on proposals for researcher_email and status (used frequently)
CREATE INDEX IF NOT EXISTS idx_proposals_researcher_status 
ON proposals(researcher_email, status);

-- Index on milestones for grant_id (used in AJAX calls)
CREATE INDEX IF NOT EXISTS idx_milestones_grant 
ON milestones(grant_id);

-- Index on budget_items for grant_id (used in AJAX calls)
CREATE INDEX IF NOT EXISTS idx_budget_items_grant 
ON budget_items(grant_id);

-- Index on expenditures for grant_id and status
CREATE INDEX IF NOT EXISTS idx_expenditures_grant_status 
ON expenditures(grant_id, status);

-- Index on progress_reports for grant_id
CREATE INDEX IF NOT EXISTS idx_progress_reports_grant 
ON progress_reports(grant_id);

-- -------------------------------------------------------------------------
-- 6. VERIFY ALL REQUIRED TABLES EXIST
-- -------------------------------------------------------------------------
-- Check if activity_logs table exists, create if not
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    actor_email VARCHAR(100) NOT NULL,
    action VARCHAR(50) NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id INT NULL,
    label VARCHAR(255) NULL,
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_actor_email (actor_email),
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Check if appeal_requests table exists, create if not
CREATE TABLE IF NOT EXISTS appeal_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    proposal_id INT NOT NULL,
    researcher_email VARCHAR(100) NOT NULL,
    reason TEXT NOT NULL,
    status VARCHAR(50) DEFAULT 'PENDING',
    admin_remarks TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (proposal_id) REFERENCES proposals(id) ON DELETE CASCADE,
    INDEX idx_status (status),
    INDEX idx_researcher (researcher_email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Check if extension_requests table exists, create if not
CREATE TABLE IF NOT EXISTS extension_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_id INT NOT NULL,
    researcher_email VARCHAR(100) NOT NULL,
    current_deadline DATE NOT NULL,
    requested_deadline DATE NOT NULL,
    justification TEXT NOT NULL,
    status VARCHAR(50) DEFAULT 'PENDING',
    hod_remarks TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (report_id) REFERENCES progress_reports(id) ON DELETE CASCADE,
    INDEX idx_status (status),
    INDEX idx_researcher (researcher_email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Check if reimbursement_requests table exists, create if not
CREATE TABLE IF NOT EXISTS reimbursement_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    researcher_email VARCHAR(100) NOT NULL,
    grant_id INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    justification TEXT NULL,
    status VARCHAR(50) DEFAULT 'PENDING',
    hod_remarks TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (grant_id) REFERENCES proposals(id) ON DELETE CASCADE,
    INDEX idx_status (status),
    INDEX idx_researcher (researcher_email),
    INDEX idx_grant (grant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Check if document_versions table exists, create if not
CREATE TABLE IF NOT EXISTS document_versions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    proposal_id INT NOT NULL,
    version_number VARCHAR(10) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    uploaded_by VARCHAR(100) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (proposal_id) REFERENCES proposals(id) ON DELETE CASCADE,
    INDEX idx_proposal (proposal_id),
    INDEX idx_version (proposal_id, version_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------------------
-- 7. UPDATE PROPOSALS TABLE TO TRACK RESUBMISSION STATUS (Requirement #3)
-- -------------------------------------------------------------------------
-- Ensure the status column can handle all required statuses
ALTER TABLE proposals 
MODIFY COLUMN status VARCHAR(50) DEFAULT 'SUBMITTED';

-- Add a column to track amendment count (optional, for reporting)
ALTER TABLE proposals 
ADD COLUMN IF NOT EXISTS amendment_count INT DEFAULT 0 
COMMENT 'Number of times proposal has been amended';

-- -------------------------------------------------------------------------
-- 8. VERIFY DATA INTEGRITY
-- -------------------------------------------------------------------------
-- Check for any proposals with invalid status
SELECT id, title, status 
FROM proposals 
WHERE status NOT IN ('SUBMITTED', 'UNDER_REVIEW', 'APPROVED', 'REJECTED', 
                     'REQUIRES_AMENDMENT', 'RESUBMITTED', 'APPEALED', 
                     'PENDING_REASSIGNMENT', 'WITHDRAWN');

-- Check for expenditures without receipts (should be none after update)
SELECT id, grant_id, amount, receipt_path 
FROM expenditures 
WHERE receipt_path IS NULL OR receipt_path = '';

-- Check for milestones without target dates
SELECT id, grant_id, title, target_date 
FROM milestones 
WHERE target_date IS NULL;

-- -------------------------------------------------------------------------
-- 9. CREATE SAMPLE DATA FOR TESTING (OPTIONAL - COMMENT OUT IN PRODUCTION)
-- -------------------------------------------------------------------------
/*
-- Sample researcher user
INSERT IGNORE INTO users (name, email, password, role, department, phone, status) 
VALUES ('Test Researcher', 'researcher@test.com', '$2y$10$YourHashedPasswordHere', 'researcher', 'Computer Science', '0123456789', 'APPROVED');

-- Sample proposal
INSERT INTO proposals (title, description, researcher_email, department, budget_amount, status, file_path, version, created_at)
VALUES ('AI Research Project', 'Research on machine learning applications', 'researcher@test.com', 'Computer Science', 50000.00, 'APPROVED', 'uploads/prop_test.pdf', 'v1.0', NOW());

-- Get the last inserted proposal ID
SET @grant_id = LAST_INSERT_ID();

-- Sample budget items
INSERT INTO budget_items (grant_id, category_name, allocated_amount, spent_amount)
VALUES 
    (@grant_id, 'Equipment', 20000.00, 0.00),
    (@grant_id, 'Materials', 10000.00, 0.00),
    (@grant_id, 'Travel', 8000.00, 0.00),
    (@grant_id, 'Personnel', 10000.00, 0.00),
    (@grant_id, 'Other', 2000.00, 0.00);

-- Sample milestones with report deadlines
INSERT INTO milestones (grant_id, title, description, target_date, report_deadline, status)
VALUES 
    (@grant_id, 'Literature Review', 'Complete comprehensive literature review', DATE_ADD(NOW(), INTERVAL 3 MONTH), DATE_ADD(NOW(), INTERVAL 3 MONTH), 'PENDING'),
    (@grant_id, 'Data Collection', 'Collect and prepare dataset', DATE_ADD(NOW(), INTERVAL 6 MONTH), DATE_ADD(NOW(), INTERVAL 6 MONTH), 'PENDING'),
    (@grant_id, 'Model Development', 'Develop and train machine learning models', DATE_ADD(NOW(), INTERVAL 9 MONTH), DATE_ADD(NOW(), INTERVAL 9 MONTH), 'PENDING');
*/

-- -------------------------------------------------------------------------
-- 10. ADD WEIGHTAGE COLUMNS & FIX DECIMAL TYPE FOR PROPOSAL_RUBRIC (Rubrics Score Fix)
-- -------------------------------------------------------------------------
-- This ensures that percentage calculations remain consistent even if
-- weightages are changed after scoring, and total_score maintains decimal precision

-- Add weightage columns to store the weightages used during scoring
ALTER TABLE proposal_rubric
ADD COLUMN IF NOT EXISTS weight_outcome DECIMAL(3,1) DEFAULT 1.0 
COMMENT 'Outcome weightage used during scoring' AFTER is_evaluated,
ADD COLUMN IF NOT EXISTS weight_impact DECIMAL(3,1) DEFAULT 1.0 
COMMENT 'Impact weightage used during scoring' AFTER weight_outcome,
ADD COLUMN IF NOT EXISTS weight_alignment DECIMAL(3,1) DEFAULT 1.0 
COMMENT 'Alignment weightage used during scoring' AFTER weight_impact,
ADD COLUMN IF NOT EXISTS weight_funding DECIMAL(3,1) DEFAULT 1.0 
COMMENT 'Funding weightage used during scoring' AFTER weight_alignment;

-- Fix total_score column to use DECIMAL instead of INT for precision
ALTER TABLE proposal_rubric
MODIFY COLUMN total_score DECIMAL(6,1) DEFAULT 0 
COMMENT 'Weighted total score (can be decimal based on weightages)';

-- -------------------------------------------------------------------------
-- 11. BACKUP RECOMMENDATIONS
-- -------------------------------------------------------------------------
-- Before running these updates, create a backup:
-- mysqldump -u root -p user_db > backup_$(date +%Y%m%d_%H%M%S).sql
-- 
-- To restore if needed:
-- mysql -u root -p user_db < backup_YYYYMMDD_HHMMSS.sql
-- -------------------------------------------------------------------------

-- =========================================================================
-- END OF SCHEMA UPDATES
-- =========================================================================

-- Display completion message
SELECT 'Database schema updated successfully!' AS status,
       NOW() AS updated_at;