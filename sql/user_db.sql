-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 23, 2026 at 10:53 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `user_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `appeal_requests`
--

CREATE TABLE `appeal_requests` (
  `id` int(11) NOT NULL,
  `proposal_id` int(11) NOT NULL,
  `researcher_email` varchar(255) NOT NULL,
  `justification` text NOT NULL,
  `status` enum('PENDING','APPROVED','REJECTED') DEFAULT 'PENDING',
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `budget_items`
--

CREATE TABLE `budget_items` (
  `id` int(11) NOT NULL,
  `proposal_id` int(11) NOT NULL,
  `category` enum('Equipment','Materials','Travel','Personnel','Other') NOT NULL,
  `description` text DEFAULT NULL,
  `allocated_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `spent_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `available_budget` decimal(12,2) DEFAULT 0.00,
  `total_budget` decimal(12,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `document_versions`
--

CREATE TABLE `document_versions` (
  `id` int(11) NOT NULL,
  `proposal_id` int(11) NOT NULL,
  `version_number` varchar(20) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `uploaded_by` varchar(255) NOT NULL,
  `upload_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `change_notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `expenditures`
--

CREATE TABLE `expenditures` (
  `id` int(11) NOT NULL,
  `budget_item_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `transaction_date` date NOT NULL,
  `description` text DEFAULT NULL,
  `receipt_path` varchar(255) DEFAULT NULL,
  `reimbursement_request_id` int(11) DEFAULT NULL,
  `status` enum('PENDING_REIMBURSEMENT','UNDER_REVIEW','APPROVED','REJECTED') DEFAULT 'PENDING_REIMBURSEMENT',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `extension_requests`
--

CREATE TABLE `extension_requests` (
  `id` int(11) NOT NULL,
  `report_id` int(11) NOT NULL,
  `researcher_email` varchar(255) NOT NULL,
  `new_deadline` date NOT NULL,
  `justification` text NOT NULL,
  `status` enum('PENDING','APPROVED','REJECTED') DEFAULT 'PENDING',
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fund`
--

CREATE TABLE `fund` (
  `fund_id` int(11) NOT NULL,
  `proposal_id` int(11) NOT NULL,
  `grant_number` varchar(50) NOT NULL,
  `amount_allocated` decimal(12,2) NOT NULL,
  `amount_spent` decimal(12,2) DEFAULT 0.00,
  `amount_remaining` decimal(12,2) DEFAULT 0.00,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('Active','Completed','Terminated') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `grant_allocation`
--

CREATE TABLE `grant_allocation` (
  `allocation_id` int(11) NOT NULL,
  `fund_id` int(11) NOT NULL,
  `requested_budget` decimal(12,2) NOT NULL,
  `approved_budget` decimal(12,2) NOT NULL,
  `allocation_notes` text DEFAULT NULL,
  `approved_by` int(10) UNSIGNED NOT NULL,
  `approval_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `grant_document`
--

CREATE TABLE `grant_document` (
  `document_id` int(11) NOT NULL,
  `fund_id` int(11) NOT NULL,
  `document_type` enum('Receipt','Legal','Report','Other') DEFAULT 'Other',
  `filename` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `uploaded_by` int(10) UNSIGNED NOT NULL,
  `current_version` int(11) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hod_tier_assignment`
--

CREATE TABLE `hod_tier_assignment` (
  `assignment_id` int(11) NOT NULL,
  `proposal_id` int(11) NOT NULL,
  `hod_id` int(10) UNSIGNED NOT NULL,
  `tier` enum('top','middle','bottom') DEFAULT 'bottom',
  `approved_budget` decimal(12,2) DEFAULT NULL,
  `is_approved` tinyint(1) DEFAULT 0,
  `approval_date` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `milestones`
--

CREATE TABLE `milestones` (
  `id` int(11) NOT NULL,
  `grant_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `target_date` date NOT NULL,
  `completion_date` date DEFAULT NULL,
  `status` enum('PENDING','IN_PROGRESS','COMPLETED','DELAYED') DEFAULT 'PENDING',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `misconduct_reports`
--

CREATE TABLE `misconduct_reports` (
  `id` int(11) NOT NULL,
  `proposal_id` int(11) DEFAULT NULL,
  `reviewer_email` varchar(255) NOT NULL,
  `researcher_email` varchar(255) NOT NULL,
  `category` enum('Plagiarism','Data Fabrication','Falsification','Unethical Conduct','Other') NOT NULL,
  `details` text NOT NULL,
  `status` enum('PENDING','INVESTIGATING','RESOLVED') DEFAULT 'PENDING',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_email` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `type` enum('info','alert','success','warning') DEFAULT 'info'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `progress_reports`
--

CREATE TABLE `progress_reports` (
  `id` int(11) NOT NULL,
  `proposal_id` int(11) NOT NULL,
  `researcher_email` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `achievements` text DEFAULT NULL,
  `challenges` text DEFAULT NULL,
  `file_path` varchar(255) NOT NULL,
  `deadline` date DEFAULT NULL,
  `status` enum('PENDING_REVIEW','APPROVED','REJECTED') DEFAULT 'PENDING_REVIEW',
  `submission_date` date DEFAULT curdate(),
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `proposals`
--

CREATE TABLE `proposals` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `researcher_email` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `reviewer_email` varchar(255) DEFAULT NULL,
  `status` enum('DRAFT','SUBMITTED','ASSIGNED','PENDING_REVIEW','REQUIRES_AMENDMENT','RESUBMITTED','RECOMMENDED','REJECTED','APPROVED','APPEALED','APPEAL_REJECTED','PENDING_REASSIGNMENT') DEFAULT 'SUBMITTED',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `approved_at` datetime DEFAULT NULL,
  `resubmitted_at` datetime DEFAULT NULL,
  `priority` enum('Normal','High') DEFAULT 'Normal',
  `department_id` int(11) DEFAULT NULL,
  `reviewer_feedback` text DEFAULT NULL,
  `amendment_notes` text DEFAULT NULL,
  `budget_requested` decimal(10,2) DEFAULT 0.00,
  `approved_budget` decimal(10,2) DEFAULT 0.00,
  `amount_spent` decimal(10,2) DEFAULT 0.00,
  `duration_months` int(11) DEFAULT 12
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `proposal_rubric`
--

CREATE TABLE `proposal_rubric` (
  `rubric_id` int(11) NOT NULL,
  `proposal_id` int(11) NOT NULL,
  `hod_id` int(10) UNSIGNED NOT NULL,
  `outcome_score` int(11) DEFAULT 0,
  `impact_score` int(11) DEFAULT 0,
  `alignment_score` int(11) DEFAULT 0,
  `funding_score` int(11) DEFAULT 0,
  `total_score` int(11) DEFAULT 0,
  `hod_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_evaluated` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reimbursement_requests`
--

CREATE TABLE `reimbursement_requests` (
  `id` int(11) NOT NULL,
  `grant_id` int(11) NOT NULL,
  `researcher_email` varchar(255) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `justification` text NOT NULL,
  `status` enum('PENDING','APPROVED','REJECTED') DEFAULT 'PENDING',
  `hod_remarks` text DEFAULT NULL,
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reviewed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `research_progress`
--

CREATE TABLE `research_progress` (
  `progress_id` int(11) NOT NULL,
  `fund_id` int(11) NOT NULL,
  `milestone_name` varchar(255) NOT NULL,
  `milestone_description` text DEFAULT NULL,
  `target_date` date DEFAULT NULL,
  `completion_date` date DEFAULT NULL,
  `status` enum('Pending','In Progress','Completed') DEFAULT 'Pending',
  `completion_percentage` int(11) DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `reviewer_id` int(11) NOT NULL,
  `reviewer_email` varchar(255) DEFAULT NULL,
  `proposal_id` int(11) NOT NULL,
  `status` enum('Pending','Completed') DEFAULT 'Pending',
  `assigned_date` date DEFAULT curdate(),
  `type` enum('Proposal','Appeal') DEFAULT 'Proposal',
  `feedback` text DEFAULT NULL,
  `annotated_file` varchar(255) DEFAULT NULL,
  `decision` enum('RECOMMEND','REJECT','AMENDMENT') DEFAULT NULL,
  `review_date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('researcher','reviewer','hod','admin') NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `notify_email` tinyint(1) DEFAULT 1,
  `notify_system` tinyint(1) DEFAULT 1,
  `profile_pic` varchar(255) DEFAULT 'default.png',
  `avatar` enum('default','male','female') DEFAULT 'default',
  `notify_new_assign` tinyint(1) DEFAULT 1,
  `notify_appeals` tinyint(1) DEFAULT 1,
  `notify_hod_approve` tinyint(1) DEFAULT 1,
  `notify_hod_reject` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `department_id`, `notify_email`, `notify_system`, `profile_pic`, `avatar`, `notify_new_assign`, `notify_appeals`, `notify_hod_approve`, `notify_hod_reject`) VALUES
(1, 'researcher', '2@mail.com', '$2y$10$kKtEYohxkpCbADaKI95MAOnN6CWMq1RtW8igLYOUK1CHMDLpzrFUq', 'researcher', NULL, 1, 1, 'default.png', 'default', 1, 1, 1, 1),
(2, 'Ms.reviewer', '1@mail.com', '$2y$10$D2u4temDCoERfvFzog/BGOz6WO0xCBXwxC4iZId2AREznDEoxhZCa', 'reviewer', NULL, 1, 1, 'default.png', 'default', 1, 1, 1, 1),
(3, 'HOD', '3@mail.com', '$2y$10$58K3HThWYQ/qR/zs6wttM.lolaF4v6pilokHpZA0UbRBuPvmNrIYC', 'hod', NULL, 1, 1, 'default.png', 'default', 1, 1, 1, 1),
(4, 'Admin', '4@mail.com', '$2y$10$CZEhPtLLD1PIqxqkFL2uN.xl6qub7LTERxWS6mgDgZKEDlbFmYUlm', 'admin', NULL, 1, 1, 'default.png', 'default', 1, 1, 1, 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `appeal_requests`
--
ALTER TABLE `appeal_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `proposal_id` (`proposal_id`);

--
-- Indexes for table `budget_items`
--
ALTER TABLE `budget_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `proposal_id` (`proposal_id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `dept_code` (`code`);

--
-- Indexes for table `document_versions`
--
ALTER TABLE `document_versions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `proposal_id` (`proposal_id`);

--
-- Indexes for table `expenditures`
--
ALTER TABLE `expenditures`
  ADD PRIMARY KEY (`id`),
  ADD KEY `budget_item_id` (`budget_item_id`),
  ADD KEY `reimbursement_request_id` (`reimbursement_request_id`);

--
-- Indexes for table `extension_requests`
--
ALTER TABLE `extension_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `report_id` (`report_id`);

--
-- Indexes for table `fund`
--
ALTER TABLE `fund`
  ADD PRIMARY KEY (`fund_id`),
  ADD UNIQUE KEY `grant_number` (`grant_number`),
  ADD KEY `proposal_id` (`proposal_id`);

--
-- Indexes for table `grant_allocation`
--
ALTER TABLE `grant_allocation`
  ADD PRIMARY KEY (`allocation_id`),
  ADD KEY `fund_id` (`fund_id`),
  ADD KEY `grant_allocation_ibfk_2` (`approved_by`);

--
-- Indexes for table `grant_document`
--
ALTER TABLE `grant_document`
  ADD PRIMARY KEY (`document_id`),
  ADD KEY `fund_id` (`fund_id`),
  ADD KEY `grant_document_ibfk_2` (`uploaded_by`);

--
-- Indexes for table `hod_tier_assignment`
--
ALTER TABLE `hod_tier_assignment`
  ADD PRIMARY KEY (`assignment_id`),
  ADD UNIQUE KEY `unique_proposal_hod` (`proposal_id`,`hod_id`),
  ADD KEY `hod_id` (`hod_id`);

--
-- Indexes for table `milestones`
--
ALTER TABLE `milestones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `grant_id` (`grant_id`);

--
-- Indexes for table `misconduct_reports`
--
ALTER TABLE `misconduct_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `proposal_id` (`proposal_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_email` (`user_email`);

--
-- Indexes for table `progress_reports`
--
ALTER TABLE `progress_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `proposal_id` (`proposal_id`);

--
-- Indexes for table `proposals`
--
ALTER TABLE `proposals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `researcher_email` (`researcher_email`),
  ADD KEY `reviewer_email` (`reviewer_email`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `proposal_rubric`
--
ALTER TABLE `proposal_rubric`
  ADD PRIMARY KEY (`rubric_id`),
  ADD UNIQUE KEY `unique_proposal_hod` (`proposal_id`,`hod_id`),
  ADD KEY `hod_id` (`hod_id`);

--
-- Indexes for table `reimbursement_requests`
--
ALTER TABLE `reimbursement_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `grant_id` (`grant_id`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `research_progress`
--
ALTER TABLE `research_progress`
  ADD PRIMARY KEY (`progress_id`),
  ADD KEY `fund_id` (`fund_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reviewer_id` (`reviewer_id`),
  ADD KEY `proposal_id` (`proposal_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email_unique` (`email`),
  ADD KEY `role` (`role`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `appeal_requests`
--
ALTER TABLE `appeal_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `budget_items`
--
ALTER TABLE `budget_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `document_versions`
--
ALTER TABLE `document_versions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `expenditures`
--
ALTER TABLE `expenditures`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `extension_requests`
--
ALTER TABLE `extension_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fund`
--
ALTER TABLE `fund`
  MODIFY `fund_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `grant_allocation`
--
ALTER TABLE `grant_allocation`
  MODIFY `allocation_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `grant_document`
--
ALTER TABLE `grant_document`
  MODIFY `document_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `hod_tier_assignment`
--
ALTER TABLE `hod_tier_assignment`
  MODIFY `assignment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `milestones`
--
ALTER TABLE `milestones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `misconduct_reports`
--
ALTER TABLE `misconduct_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `progress_reports`
--
ALTER TABLE `progress_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `proposals`
--
ALTER TABLE `proposals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `proposal_rubric`
--
ALTER TABLE `proposal_rubric`
  MODIFY `rubric_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reimbursement_requests`
--
ALTER TABLE `reimbursement_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `research_progress`
--
ALTER TABLE `research_progress`
  MODIFY `progress_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appeal_requests`
--
ALTER TABLE `appeal_requests`
  ADD CONSTRAINT `appeal_requests_ibfk_1` FOREIGN KEY (`proposal_id`) REFERENCES `proposals` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `budget_items`
--
ALTER TABLE `budget_items`
  ADD CONSTRAINT `budget_items_ibfk_1` FOREIGN KEY (`proposal_id`) REFERENCES `proposals` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `document_versions`
--
ALTER TABLE `document_versions`
  ADD CONSTRAINT `document_versions_ibfk_1` FOREIGN KEY (`proposal_id`) REFERENCES `proposals` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `expenditures`
--
ALTER TABLE `expenditures`
  ADD CONSTRAINT `expenditures_ibfk_1` FOREIGN KEY (`budget_item_id`) REFERENCES `budget_items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `expenditures_ibfk_2` FOREIGN KEY (`reimbursement_request_id`) REFERENCES `reimbursement_requests` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `extension_requests`
--
ALTER TABLE `extension_requests`
  ADD CONSTRAINT `extension_requests_ibfk_1` FOREIGN KEY (`report_id`) REFERENCES `progress_reports` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `fund`
--
ALTER TABLE `fund`
  ADD CONSTRAINT `fund_ibfk_1` FOREIGN KEY (`proposal_id`) REFERENCES `proposals` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `grant_allocation`
--
ALTER TABLE `grant_allocation`
  ADD CONSTRAINT `grant_allocation_ibfk_1` FOREIGN KEY (`fund_id`) REFERENCES `fund` (`fund_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `grant_allocation_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `grant_document`
--
ALTER TABLE `grant_document`
  ADD CONSTRAINT `grant_document_ibfk_1` FOREIGN KEY (`fund_id`) REFERENCES `fund` (`fund_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `grant_document_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `hod_tier_assignment`
--
ALTER TABLE `hod_tier_assignment`
  ADD CONSTRAINT `hod_tier_assignment_ibfk_1` FOREIGN KEY (`proposal_id`) REFERENCES `proposals` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `hod_tier_assignment_ibfk_2` FOREIGN KEY (`hod_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `milestones`
--
ALTER TABLE `milestones`
  ADD CONSTRAINT `milestones_ibfk_1` FOREIGN KEY (`grant_id`) REFERENCES `proposals` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `misconduct_reports`
--
ALTER TABLE `misconduct_reports`
  ADD CONSTRAINT `misconduct_reports_ibfk_1` FOREIGN KEY (`proposal_id`) REFERENCES `proposals` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `progress_reports`
--
ALTER TABLE `progress_reports`
  ADD CONSTRAINT `progress_reports_ibfk_1` FOREIGN KEY (`proposal_id`) REFERENCES `proposals` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `proposal_rubric`
--
ALTER TABLE `proposal_rubric`
  ADD CONSTRAINT `proposal_rubric_ibfk_1` FOREIGN KEY (`proposal_id`) REFERENCES `proposals` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `proposal_rubric_ibfk_2` FOREIGN KEY (`hod_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reimbursement_requests`
--
ALTER TABLE `reimbursement_requests`
  ADD CONSTRAINT `reimbursement_requests_ibfk_1` FOREIGN KEY (`grant_id`) REFERENCES `proposals` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `research_progress`
--
ALTER TABLE `research_progress`
  ADD CONSTRAINT `research_progress_ibfk_1` FOREIGN KEY (`fund_id`) REFERENCES `fund` (`fund_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
