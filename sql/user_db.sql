-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 04, 2026 at 07:38 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

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
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `actor_email` varchar(255) NOT NULL,
  `actor_role` varchar(50) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `entity_type` varchar(50) NOT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `label` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `actor_email`, `actor_role`, `ip_address`, `action`, `entity_type`, `entity_id`, `label`, `description`, `created_at`) VALUES
(1, '2@mail.com', 'researcher', '::1', 'CREATE', 'PROPOSAL', 1, 'Submit Proposal', 'Researcher submitted proposal: test1', '2026-02-04 02:12:01'),
(2, '2@mail.com', 'researcher', '::1', 'CREATE', 'PROPOSAL', 2, 'Submit Proposal', 'Researcher submitted proposal: reject', '2026-02-04 02:15:23'),
(3, '2@mail.com', 'researcher', '::1', 'CREATE', 'PROPOSAL', 3, 'Submit Proposal', 'Researcher submitted proposal: appeal', '2026-02-04 02:15:46'),
(4, '2@mail.com', 'researcher', '::1', 'CREATE', 'PROPOSAL', 4, 'Submit Proposal', 'Researcher submitted proposal: urgent', '2026-02-04 02:19:02'),
(5, '2@mail.com', 'researcher', '::1', 'CREATE', 'PROPOSAL', 5, 'Submit Proposal', 'Researcher submitted proposal: reject_appeal', '2026-02-04 02:20:00'),
(6, '2@mail.com', 'researcher', '::1', 'CREATE', 'PROPOSAL', 6, 'Submit Proposal', 'Researcher submitted proposal: amendment', '2026-02-04 02:21:07'),
(7, '2@mail.com', 'researcher', '::1', 'CREATE', 'PROPOSAL', 7, 'Submit Proposal', 'Researcher submitted proposal: misconduct', '2026-02-04 02:21:51'),
(8, '2@mail.com', 'researcher', '::1', 'CREATE', 'PROPOSAL', 8, 'Submit Proposal', 'Researcher submitted proposal: HOD_Reject', '2026-02-04 02:22:51'),
(9, '1@mail.com', 'reviewer', '::1', 'VIEW_MISCONDUCT_CASES', 'MISCONDUCT_REPORT', NULL, 'View Misconduct Cases', 'Reviewer viewed misconduct cases list', '2026-02-05 00:16:19'),
(10, '1@mail.com', 'reviewer', '::1', 'SUBMIT_MISCONDUCT_REPORT', 'MISCONDUCT_REPORT', 0, 'Submit Misconduct Report', 'Reviewer submitted misconduct report # for proposal #7 against 2@mail.com. Category=Plagiarism. Proposal set to UNDER_INVESTIGATION and review marked REPORTED/REJECTED.', '2026-02-05 01:01:48'),
(11, '1@mail.com', 'reviewer', '::1', 'VIEW_MISCONDUCT_CASES', 'MISCONDUCT_REPORT', NULL, 'View Misconduct Cases', 'Reviewer viewed misconduct cases list', '2026-02-05 01:02:26'),
(12, '2@mail.com', 'researcher', '::1', 'CREATE', 'APPEAL_REQUEST', NULL, 'Appeal Proposal', 'Researcher appealed proposal #3', '2026-02-05 01:24:56'),
(13, '3@mail.com', 'hod', '::1', 'GRANT_APPEAL', 'PROPOSAL', 3, 'Grant Appeal', 'HOD accepted appeal for proposal #3 (appeal) and set status=PENDING_REASSIGNMENT', '2026-02-05 01:29:16'),
(14, '4@mail.com', 'admin', '::1', 'ASSIGN_REVIEWER', 'APPEAL', 3, 'Assign Appeal Case', 'Assigned appeal case #3 () to reviewer_id=2', '2026-02-05 01:39:03'),
(15, '3@mail.com', 'hod', '::1', 'GRANT_APPEAL', 'PROPOSAL', 3, 'Grant Appeal', 'HOD accepted appeal for proposal #3 (appeal) and set status=PENDING_REASSIGNMENT', '2026-02-05 01:44:53'),
(16, '4@mail.com', 'admin', '::1', 'ASSIGN_REVIEWER', 'APPEAL', 3, 'Assign Appeal Case', 'Assigned appeal case #3 to reviewer_id=6', '2026-02-05 01:45:13'),
(17, '2@mail.com', 'researcher', '::1', 'CREATE', 'APPEAL_REQUEST', NULL, 'Appeal Proposal', 'Researcher appealed proposal #5', '2026-02-05 01:47:10'),
(18, '3@mail.com', 'hod', '::1', 'GRANT_APPEAL', 'PROPOSAL', 5, 'Grant Appeal', 'HOD accepted appeal for proposal #5 (reject_appeal) and set status=PENDING_REASSIGNMENT', '2026-02-05 01:47:56'),
(19, '4@mail.com', 'admin', '::1', 'ASSIGN_REVIEWER', 'APPEAL', 5, 'Assign Appeal Case', 'Assigned appeal case #5 to reviewer_id=6', '2026-02-05 01:48:09'),
(20, '2@mail.com', 'researcher', '::1', 'UPDATE', 'PROPOSAL', 6, 'Amend Proposal', 'Researcher resubmitted amendments (new version: v2.0)', '2026-02-05 01:52:50'),
(21, '2@mail.com', 'researcher', '::1', 'UPDATE', 'PROPOSAL', 6, 'Amend Proposal', 'Researcher resubmitted amendments (new version: v3.0)', '2026-02-05 01:54:48'),
(22, '2@mail.com', 'researcher', '::1', 'CREATE', 'PROPOSAL', 9, 'Submit Proposal', 'Researcher submitted proposal: notifications_off', '2026-02-05 02:30:01'),
(23, '4@mail.com', 'admin', '::1', 'ASSIGN_REVIEWER', 'PROPOSAL', 9, 'Assign Proposal', 'Assigned proposal #9 to reviewer_id=2', '2026-02-05 02:30:26'),
(24, '4@mail.com', 'admin', '::1', 'ASSIGN_REVIEWER', 'PROPOSAL', 9, 'Assign Proposal', 'Assigned proposal #9 to reviewer_id=2', '2026-02-05 02:37:28');

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

--
-- Dumping data for table `appeal_requests`
--

INSERT INTO `appeal_requests` (`id`, `proposal_id`, `researcher_email`, `justification`, `status`, `submitted_at`) VALUES
(1, 3, '2@mail.com', 'should go straight back to reviewer not admin', 'PENDING', '2026-02-04 17:24:56'),
(2, 5, '2@mail.com', 'please reject this (by new reviewer)', 'PENDING', '2026-02-04 17:47:10');

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

--
-- Dumping data for table `budget_items`
--

INSERT INTO `budget_items` (`id`, `proposal_id`, `category`, `description`, `allocated_amount`, `spent_amount`, `created_at`) VALUES
(1, 1, 'Equipment', 'Equipment budget', 1.00, 0.00, '2026-02-03 18:12:01'),
(2, 2, 'Equipment', 'Equipment budget', 0.98, 0.00, '2026-02-03 18:15:23'),
(3, 3, 'Equipment', 'Equipment budget', 0.98, 0.00, '2026-02-03 18:15:46'),
(4, 4, 'Equipment', 'Equipment budget', 1.00, 0.00, '2026-02-03 18:19:02'),
(5, 5, 'Equipment', 'Equipment budget', 1.00, 0.00, '2026-02-03 18:20:00'),
(6, 6, 'Equipment', 'Equipment budget', 1.00, 0.00, '2026-02-03 18:21:07'),
(7, 7, 'Equipment', 'Equipment budget', 1.00, 0.00, '2026-02-03 18:21:51'),
(8, 8, 'Equipment', 'Equipment budget', 1.00, 0.00, '2026-02-03 18:22:51'),
(9, 9, 'Equipment', 'Equipment budget', 1.00, 0.00, '2026-02-04 18:30:01');

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

--
-- Dumping data for table `document_versions`
--

INSERT INTO `document_versions` (`id`, `proposal_id`, `version_number`, `file_path`, `uploaded_by`, `upload_date`, `change_notes`) VALUES
(1, 1, 'v1.0', 'uploads/prop_1770142321_2_mail_com.pdf', '2@mail.com', '2026-02-03 18:12:01', NULL),
(2, 2, 'v1.0', 'uploads/prop_1770142523_2_mail_com.pdf', '2@mail.com', '2026-02-03 18:15:23', NULL),
(3, 3, 'v1.0', 'uploads/prop_1770142546_2_mail_com.pdf', '2@mail.com', '2026-02-03 18:15:46', NULL),
(4, 4, 'v1.0', 'uploads/prop_1770142742_2_mail_com.pdf', '2@mail.com', '2026-02-03 18:19:02', NULL),
(5, 5, 'v1.0', 'uploads/prop_1770142800_2_mail_com.pdf', '2@mail.com', '2026-02-03 18:20:00', NULL),
(6, 6, 'v1.0', 'uploads/prop_1770142867_2_mail_com.pdf', '2@mail.com', '2026-02-03 18:21:07', NULL),
(7, 7, 'v1.0', 'uploads/prop_1770142911_2_mail_com.pdf', '2@mail.com', '2026-02-03 18:21:51', NULL),
(8, 8, 'v1.0', 'uploads/prop_1770142971_2_mail_com.pdf', '2@mail.com', '2026-02-03 18:22:51', NULL),
(9, 6, 'v2.0', 'uploads/prop_1770227570_2_mail_com_v2.pdf', '2@mail.com', '2026-02-04 17:52:50', 'change already'),
(10, 6, 'v3.0', 'uploads/prop_1770227688_2_mail_com_v2.pdf', '2@mail.com', '2026-02-04 17:54:48', 'final'),
(11, 9, 'v1.0', 'uploads/prop_1770229801_2_mail_com.pdf', '2@mail.com', '2026-02-04 18:30:01', NULL);

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

--
-- Dumping data for table `hod_tier_assignment`
--

INSERT INTO `hod_tier_assignment` (`assignment_id`, `proposal_id`, `hod_id`, `tier`, `approved_budget`, `is_approved`, `approval_date`, `created_at`, `updated_at`) VALUES
(1, 8, 4, 'bottom', NULL, 0, NULL, '2026-02-04 17:57:54', '2026-02-04 17:57:54');

-- --------------------------------------------------------

--
-- Table structure for table `issue_messages`
--

CREATE TABLE `issue_messages` (
  `id` int(11) NOT NULL,
  `report_id` int(11) NOT NULL,
  `sender_role` varchar(20) NOT NULL,
  `sender_email` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `attachment_path` varchar(255) DEFAULT NULL,
  `attachment_name` varchar(255) DEFAULT NULL,
  `attachment_mime` varchar(100) DEFAULT NULL
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

--
-- Dumping data for table `milestones`
--

INSERT INTO `milestones` (`id`, `grant_id`, `title`, `description`, `target_date`, `completion_date`, `status`, `created_at`) VALUES
(1, 1, 'My First Milestone', 'first', '2026-02-04', NULL, 'PENDING', '2026-02-03 18:12:01'),
(2, 2, '1', '1', '2026-02-04', NULL, 'PENDING', '2026-02-03 18:15:23'),
(3, 3, '1', '1', '2026-02-04', NULL, 'PENDING', '2026-02-03 18:15:46'),
(4, 4, '1', '1', '2026-02-04', NULL, 'PENDING', '2026-02-03 18:19:02'),
(5, 5, '1', '1', '2026-02-04', NULL, 'PENDING', '2026-02-03 18:20:00'),
(6, 6, '1', '1', '2026-02-04', NULL, 'PENDING', '2026-02-03 18:21:07'),
(7, 7, '1', '1', '2026-02-04', NULL, 'PENDING', '2026-02-03 18:21:51'),
(8, 8, '1', '1', '2026-02-04', NULL, 'PENDING', '2026-02-03 18:22:51'),
(9, 9, '1', '', '2026-02-04', NULL, 'PENDING', '2026-02-04 18:30:01');

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

--
-- Dumping data for table `misconduct_reports`
--

INSERT INTO `misconduct_reports` (`id`, `proposal_id`, `reviewer_email`, `researcher_email`, `category`, `details`, `status`, `created_at`) VALUES
(1, 7, '1@mail.com', '2@mail.com', 'Plagiarism', 'copycat', 'PENDING', '2026-02-04 17:01:48');

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

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_email`, `message`, `is_read`, `created_at`, `type`) VALUES
(39, '4@mail.com', 'New Proposal Submitted: \'notifications_off\' by 2@mail.com', 0, '2026-02-04 18:30:01', 'alert');

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

--
-- Dumping data for table `proposals`
--

INSERT INTO `proposals` (`id`, `title`, `description`, `researcher_email`, `file_path`, `reviewer_email`, `status`, `created_at`, `approved_at`, `resubmitted_at`, `priority`, `department_id`, `reviewer_feedback`, `amendment_notes`, `budget_requested`, `approved_budget`, `amount_spent`, `duration_months`) VALUES
(1, 'test1', 'test1', '2@mail.com', 'uploads/prop_1770142321_2_mail_com.pdf', NULL, '', '2026-02-03 18:12:01', NULL, NULL, 'Normal', NULL, NULL, NULL, 1.00, 0.00, 0.00, 12),
(2, 'reject', 'reject', '2@mail.com', 'uploads/prop_1770142523_2_mail_com.pdf', NULL, 'REJECTED', '2026-02-03 18:15:23', NULL, NULL, 'Normal', NULL, NULL, NULL, 0.98, 0.00, 0.00, 12),
(3, 'appeal', 'appeal', '2@mail.com', 'uploads/prop_1770142546_2_mail_com.pdf', NULL, 'ASSIGNED', '2026-02-03 18:15:46', NULL, NULL, 'Normal', NULL, NULL, NULL, 0.98, 0.00, 0.00, 11),
(4, 'urgent', 'urgent', '2@mail.com', 'uploads/prop_1770142742_2_mail_com.pdf', NULL, '', '2026-02-03 18:19:02', NULL, NULL, 'High', NULL, NULL, NULL, 1.00, 0.00, 0.00, 12),
(5, 'reject_appeal', 'reject_appeal', '2@mail.com', 'uploads/prop_1770142800_2_mail_com.pdf', NULL, 'APPEAL_REJECTED', '2026-02-03 18:20:00', NULL, NULL, 'Normal', NULL, NULL, NULL, 1.00, 0.00, 0.00, 12),
(6, 'amendment', 'amendment', '2@mail.com', 'uploads/prop_1770227688_2_mail_com_v2.pdf', NULL, 'RESUBMITTED', '2026-02-03 18:21:07', NULL, '2026-02-05 01:54:48', 'Normal', NULL, 'one more time', 'final', 1.00, 0.00, 0.00, 12),
(7, 'misconduct', 'misconduct', '2@mail.com', 'uploads/prop_1770142911_2_mail_com.pdf', NULL, '', '2026-02-03 18:21:51', NULL, NULL, 'Normal', NULL, NULL, NULL, 1.00, 0.00, 0.00, 12),
(8, 'HOD_Reject', 'HOD_Reject', '2@mail.com', 'uploads/prop_1770142971_2_mail_com.pdf', NULL, 'REJECTED', '2026-02-03 18:22:51', NULL, NULL, 'Normal', NULL, NULL, NULL, 1.00, 0.00, 0.00, 12),
(9, 'notifications_off', 'off', '2@mail.com', 'uploads/prop_1770229801_2_mail_com.pdf', NULL, 'ASSIGNED', '2026-02-04 18:30:01', NULL, NULL, 'Normal', NULL, NULL, NULL, 1.00, 0.00, 0.00, 12);

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

--
-- Dumping data for table `proposal_rubric`
--

INSERT INTO `proposal_rubric` (`rubric_id`, `proposal_id`, `hod_id`, `outcome_score`, `impact_score`, `alignment_score`, `funding_score`, `total_score`, `hod_notes`, `created_at`, `updated_at`, `is_evaluated`) VALUES
(1, 8, 4, 1, 1, 1, 1, 4, 'not good', '2026-02-04 17:56:30', '2026-02-04 17:56:40', 1);

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

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`id`, `reviewer_id`, `reviewer_email`, `proposal_id`, `status`, `assigned_date`, `type`, `feedback`, `annotated_file`, `decision`, `review_date`) VALUES
(1, 2, NULL, 1, 'Completed', '2026-02-04', 'Proposal', 'great', 'uploads/reviews/rev_1770144120_edited.pdf', 'RECOMMEND', '2026-02-04 02:42:00'),
(2, 2, NULL, 8, 'Completed', '2026-02-04', 'Proposal', '@hod, ok mou', 'uploads/reviews/rev_1770224539_edited.pdf', 'RECOMMEND', '2026-02-05 01:02:19'),
(3, 2, NULL, 7, '', '2026-02-04', 'Proposal', NULL, NULL, 'REJECT', '2026-02-05 01:01:48'),
(4, 2, NULL, 6, 'Completed', '2026-02-04', 'Proposal', 'make some changes here and there', 'uploads/reviews/rev_1770224488_edited.pdf', 'AMENDMENT', '2026-02-05 01:01:28'),
(5, 2, NULL, 5, 'Completed', '2026-02-04', 'Proposal', 'no one will accept even if u appeal', 'uploads/reviews/rev_1770224437_edited.pdf', 'REJECT', '2026-02-05 01:00:37'),
(6, 2, NULL, 4, 'Completed', '2026-02-04', 'Proposal', 'wow great, urgent', 'uploads/reviews/rev_1770224379_edited.pdf', 'RECOMMEND', '2026-02-05 00:59:39'),
(7, 2, NULL, 3, 'Completed', '2026-02-04', 'Proposal', 'not good', 'uploads/reviews/rev_1770224354_edited.pdf', 'REJECT', '2026-02-05 00:59:14'),
(8, 2, NULL, 2, 'Completed', '2026-02-04', 'Proposal', 'not good', 'uploads/reviews/rev_1770224330_edited.pdf', 'REJECT', '2026-02-05 00:58:50'),
(9, 2, NULL, 3, 'Pending', '2026-02-05', 'Appeal', NULL, NULL, NULL, NULL),
(10, 6, NULL, 3, 'Pending', '2026-02-05', 'Appeal', NULL, NULL, NULL, NULL),
(11, 6, NULL, 5, 'Completed', '2026-02-05', 'Appeal', 'not good', 'uploads/reviews/rev_1770227352_edited.pdf', 'REJECT', '2026-02-05 01:49:12'),
(12, 2, NULL, 6, 'Completed', '2026-02-05', 'Proposal', 'one more time', NULL, 'AMENDMENT', '2026-02-05 01:53:38'),
(13, 2, NULL, 6, 'Pending', '2026-02-05', 'Proposal', NULL, NULL, NULL, NULL),
(14, 2, NULL, 9, 'Pending', '2026-02-05', 'Proposal', NULL, NULL, NULL, NULL),
(15, 2, NULL, 9, 'Pending', '2026-02-05', 'Proposal', NULL, NULL, NULL, NULL);

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
  `notify_hod_reject` tinyint(1) DEFAULT 1,
  `status` enum('PENDING','APPROVED','REJECTED') DEFAULT 'PENDING'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `department_id`, `notify_email`, `notify_system`, `profile_pic`, `avatar`, `notify_new_assign`, `notify_appeals`, `notify_hod_approve`, `notify_hod_reject`, `status`) VALUES
(1, 'Admin', '4@mail.com', '$2y$10$lEEimobKJUEjv3TLs0zQje8slJb/ZEHMm4lA66mkwqXIHTR4TiRf6', 'admin', NULL, 1, 1, 'default.png', 'default', 1, 1, 1, 1, 'APPROVED'),
(2, 'Reviewer', '1@mail.com', '$2y$10$LbWin3gll7GIN4yfaxkIgeGq2HYQRzAfuWWYkvw3wlnkRLuLsXRM2', 'reviewer', NULL, 0, 0, 'default.png', 'default', 0, 0, 0, 0, 'APPROVED'),
(3, 'Researcher', '2@mail.com', '$2y$10$agLaaKAoN8vicr8UZbSIfuboAr7bchBkwatGtCK3CjvL9zEovN/t2', 'researcher', NULL, 1, 1, 'default.png', 'default', 1, 1, 1, 1, 'APPROVED'),
(4, 'HOD', '3@mail.com', '$2y$10$bK5ZMDG0WnzfRX8i.IJ0k.wA0ZzzUTOArNdUBo2fYkFBjdX/l8Mxm', 'hod', NULL, 1, 1, 'default.png', 'default', 1, 1, 1, 1, 'APPROVED'),
(6, 'new reviewer', '11@mail.com', '$2y$10$5g/euCr/ft6NUWs.qb2Mb.JgNiSRCO.LzN65nJ5qaCftsvdUj043C', 'reviewer', NULL, 1, 1, 'default.png', 'default', 1, 1, 1, 1, 'APPROVED');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`);

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
-- Indexes for table `issue_messages`
--
ALTER TABLE `issue_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_issue_report` (`report_id`);

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
  ADD KEY `role` (`role`),
  ADD KEY `department_id` (`department_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `appeal_requests`
--
ALTER TABLE `appeal_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `budget_items`
--
ALTER TABLE `budget_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `document_versions`
--
ALTER TABLE `document_versions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

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
  MODIFY `assignment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `issue_messages`
--
ALTER TABLE `issue_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `milestones`
--
ALTER TABLE `milestones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `misconduct_reports`
--
ALTER TABLE `misconduct_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `progress_reports`
--
ALTER TABLE `progress_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `proposals`
--
ALTER TABLE `proposals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `proposal_rubric`
--
ALTER TABLE `proposal_rubric`
  MODIFY `rubric_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

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
-- Constraints for table `issue_messages`
--
ALTER TABLE `issue_messages`
  ADD CONSTRAINT `fk_issue_report` FOREIGN KEY (`report_id`) REFERENCES `misconduct_reports` (`id`) ON DELETE CASCADE;

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
