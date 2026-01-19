-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 18, 2026 at 08:58 AM
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
(4, 19, 'researcher@gmail.com', 'idk', 'PENDING', '2026-01-08 06:23:07'),
(5, 15, 'researcher@gmail.com', 'my research confirm work der trust me', 'PENDING', '2026-01-18 07:49:04');

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
(1, 21, 'Equipment', 'Equipment budget', 120.00, 0.00, '2026-01-14 03:52:30'),
(2, 21, 'Materials', 'Materials budget', 300.00, 0.00, '2026-01-14 03:52:30'),
(3, 21, 'Travel', 'Travel budget', 400.00, 0.00, '2026-01-14 03:52:30'),
(4, 21, 'Personnel', 'Personnel budget', 500.00, 0.00, '2026-01-14 03:52:30'),
(5, 21, 'Other', 'Other budget', 100.00, 0.00, '2026-01-14 03:52:30'),
(6, 22, 'Equipment', 'Equipment budget', 1000.00, 0.00, '2026-01-14 03:54:45'),
(7, 22, 'Materials', 'Materials budget', 1000.00, 0.00, '2026-01-14 03:54:45'),
(8, 22, 'Travel', 'Travel budget', 1999.00, 0.00, '2026-01-14 03:54:45'),
(9, 22, 'Other', 'Other budget', 1000.00, 0.00, '2026-01-14 03:54:45');

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

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `name`, `code`, `description`, `available_budget`, `total_budget`, `created_at`) VALUES
(1, 'Engineering', 'ENG', 'Faculty of Engineering', 500000.00, 1000000.00, '2026-01-10 08:20:38'),
(2, 'Science', 'SCI', 'Faculty of Science', 400000.00, 800000.00, '2026-01-10 08:20:38'),
(3, 'Business', 'BUS', 'Faculty of Business', 350000.00, 700000.00, '2026-01-10 08:20:38');

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
(1, 21, 'v1.0', 'uploads/prop_1768391550_researcher_gmail_com.pdf', 'researcher@gmail.com', '2026-01-14 03:52:30', NULL),
(2, 22, 'v1.0', 'uploads/prop_1768391685_researcher_gmail_com.pdf', 'researcher@gmail.com', '2026-01-14 03:54:45', NULL),
(3, 23, 'v1.0', 'uploads/prop_1768391951_researcher_gmail_com.pdf', 'researcher@gmail.com', '2026-01-14 03:59:11', NULL);

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

--
-- Dumping data for table `extension_requests`
--

INSERT INTO `extension_requests` (`id`, `report_id`, `researcher_email`, `new_deadline`, `justification`, `status`, `requested_at`) VALUES
(1, 1, 'researcher@gmail.com', '2026-01-07', 'idk', 'PENDING', '2026-01-07 15:07:44'),
(2, 1, 'researcher@gmail.com', '2026-01-06', 'idk', 'PENDING', '2026-01-07 15:07:53');

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

--
-- Dumping data for table `fund`
--

INSERT INTO `fund` (`fund_id`, `proposal_id`, `grant_number`, `amount_allocated`, `amount_spent`, `amount_remaining`, `start_date`, `end_date`, `status`, `created_at`) VALUES
(1, 17, 'GRT-2026-00017', 0.00, 0.00, 0.00, NULL, NULL, 'Active', '2026-01-17 08:52:52');

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

--
-- Dumping data for table `grant_allocation`
--

INSERT INTO `grant_allocation` (`allocation_id`, `fund_id`, `requested_budget`, `approved_budget`, `allocation_notes`, `approved_by`, `approval_date`, `created_at`) VALUES
(1, 1, 0.00, 0.00, NULL, 3, '2026-01-17', '2026-01-17 08:52:52');

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
(1, 17, 3, 'top', 0.00, 1, NULL, '2026-01-17 08:52:52', '2026-01-17 08:52:52'),
(2, 6, 3, 'middle', NULL, 0, NULL, '2026-01-17 10:11:07', '2026-01-17 14:30:21'),
(10, 7, 3, '', NULL, 0, NULL, '2026-01-17 14:30:21', '2026-01-17 14:30:21');

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

--
-- Dumping data for table `misconduct_reports`
--

INSERT INTO `misconduct_reports` (`id`, `proposal_id`, `reviewer_email`, `researcher_email`, `category`, `details`, `status`, `created_at`) VALUES
(1, 1, '1@mail.com', '2@mail.com', 'Plagiarism', 'baddd', 'PENDING', '2025-12-27 16:54:55'),
(2, 1, '1@mail.com', '2@mail.com', 'Plagiarism', '/', 'PENDING', '2025-12-27 18:03:10'),
(3, 1, '1@mail.com', '2@mail.com', 'Data Fabrication', 'kick', 'PENDING', '2025-12-27 18:10:19');

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
(8, '2@mail.com', 'Update on \'Hello World\': Your proposal has been rejected.', 0, '2025-12-26 19:08:30', 'info'),
(10, '2@mail.com', 'Update on \'Approval\': Your proposal has been approved.', 0, '2025-12-26 19:09:52', 'info'),
(12, '2@mail.com', 'Update on \'Recommended\': Your proposal has been recommended.', 0, '2025-12-26 19:15:33', 'info'),
(13, '2@mail.com', 'Final Decision: Your proposal \'Recommended\' has been APPROVED by the Head of Department.', 0, '2025-12-26 19:16:16', 'info'),
(14, 'admin@test.com', 'Appeal Request: Researcher (2@mail.com) has appealed the rejection of \'Hello World\'.', 0, '2025-12-26 19:27:52', 'info'),
(16, '2@mail.com', 'Update on \'Appeal\': Your proposal has been rejected.', 0, '2025-12-26 19:32:31', 'info'),
(19, '2@mail.com', 'Update on \'Rejected HOD\': Your proposal has been recommended.', 0, '2025-12-26 19:35:11', 'info'),
(20, '2@mail.com', 'Final Decision: Your proposal \'Rejected HOD\' has been REJECTED by the Head of Department.', 0, '2025-12-26 19:35:23', 'info'),
(28, '2@mail.com', 'Update on \'Hello World\': Your proposal has been rejected.', 0, '2025-12-26 19:44:27', 'info'),
(32, '2@mail.com', 'Update on \'Appeal\': Your proposal has been rejected.', 0, '2025-12-26 19:48:01', 'info'),
(36, '2@mail.com', 'Update on \'Hello World\': Your proposal has been recommended.', 0, '2025-12-26 19:48:24', 'info'),
(39, '2@mail.com', 'Update on \'Appeal\': Your proposal has been rejected.', 0, '2025-12-26 19:48:50', 'info'),
(46, '2@mail.com', 'Update on \'Appeal\': Your proposal status is now appeal_rejected.', 0, '2025-12-26 19:52:38', 'info'),
(48, '2@mail.com', 'Update on \'Hello World\': Your proposal status is now appeal_rejected.', 0, '2025-12-26 19:52:58', 'info'),
(50, '4@mail.com', 'ALERT: Misconduct reported by 1@mail.com against 2@mail.com. Category: Plagiarism', 0, '2025-12-27 16:54:55', 'alert'),
(51, '4@mail.com', 'ALERT: Misconduct reported by 1@mail.com against 2@mail.com. Category: Plagiarism', 0, '2025-12-27 18:03:10', 'alert'),
(52, '4@mail.com', 'ALERT: Misconduct reported by 1@mail.com against 2@mail.com. Category: Data Fabrication', 0, '2025-12-27 18:10:19', 'alert'),
(53, '4@mail.com', 'New Proposal Submitted: \'new\' by 2@mail.com', 0, '2025-12-29 16:06:10', 'info'),
(54, '1@mail.com', 'New Assignment: You have been assigned a proposal.', 0, '2025-12-29 16:06:21', 'info'),
(55, '4@mail.com', 'New Proposal Submitted: \'Test Proposal 1\' by researcher@gmail.com', 0, '2026-01-07 13:52:53', 'info'),
(56, '4@mail.com', 'New Proposal Submitted: \'Test Proposal 1\' by researcher@gmail.com', 0, '2026-01-07 13:55:24', 'info'),
(57, '4@mail.com', 'New Proposal Submitted: \'Test Proposal 1\' by researcher@gmail.com', 0, '2026-01-07 13:55:36', 'info'),
(58, '4@mail.com', 'New Proposal Submitted: \'Test Proposal 1\' by researcher@gmail.com', 0, '2026-01-07 13:55:40', 'info'),
(59, '4@mail.com', 'Appeal Request: researcher@gmail.com appealed rejection of \'Test Proposal 1\'.', 0, '2026-01-07 14:08:22', 'info'),
(60, '4@mail.com', 'New Proposal Submitted: \'Test Proposal 2\' by researcher@gmail.com', 0, '2026-01-07 15:01:41', 'alert'),
(61, '3@mail.com', 'Appeal Request: researcher@gmail.com has contested rejection of Proposal #12.', 0, '2026-01-07 15:03:41', 'alert'),
(62, '3@mail.com', 'Appeal Request: researcher@gmail.com has contested rejection of Proposal #12.', 0, '2026-01-07 15:05:24', 'alert'),
(63, '4@mail.com', 'New Proposal Submitted: \'Test Proposal 3\' by researcher@gmail.com', 0, '2026-01-07 15:05:56', 'alert'),
(64, '4@mail.com', 'New Proposal Submitted: \'Test Proposal 3\' by researcher@gmail.com', 0, '2026-01-07 15:06:31', 'alert'),
(65, '4@mail.com', 'New Proposal Submitted: \'Test Proposal 4\' by researcher@gmail.com', 0, '2026-01-07 15:53:43', 'alert'),
(66, '4@mail.com', 'New Proposal Submitted: \'Test Proposal 4\' by researcher@gmail.com', 0, '2026-01-07 15:54:18', 'alert'),
(67, '2@mail.com', 'Update on \'new\': Your proposal status is now recommend.', 0, '2026-01-10 08:58:04', 'info'),
(68, '2@mail.com', 'Update on \'Rejected HOD\': Your proposal status is now recommend.', 0, '2026-01-10 08:58:25', 'info'),
(69, '4@mail.com', 'New Proposal Submitted: \'test1\' by 2@mail.com', 0, '2026-01-10 16:17:13', 'alert'),
(70, '1@mail.com', 'New Assignment: You have been assigned a proposal.', 0, '2026-01-11 02:57:30', 'info'),
(71, '2@mail.com', 'Update on \'test1\': Your proposal status is now recommend.', 0, '2026-01-11 02:58:38', 'info'),
(72, '4@mail.com', 'New Proposal Submitted: \'test5\' by 2@mail.com', 0, '2026-01-11 05:34:22', 'alert'),
(73, '1@mail.com', 'New Assignment: You have been assigned a proposal.', 0, '2026-01-11 05:34:37', 'info'),
(74, '2@mail.com', 'Final Decision: Your proposal \'test1\' has been APPROVED by the Head of Department.', 0, '2026-01-17 08:52:52', 'success'),
(75, '2@mail.com', 'Final Decision: Your proposal \'Rejected HOD\' has been REJECTED by the Head of Department.', 0, '2026-01-17 10:12:43', 'warning'),
(76, '3@mail.com', 'Appeal Request: researcher@gmail.com has contested rejection of Proposal #15. Please review and potentially reassign to a new reviewer.', 0, '2026-01-18 07:49:04', 'alert');

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

--
-- Dumping data for table `progress_reports`
--

INSERT INTO `progress_reports` (`id`, `proposal_id`, `researcher_email`, `title`, `achievements`, `challenges`, `file_path`, `deadline`, `status`, `submission_date`, `submitted_at`) VALUES
(1, 13, 'researcher@gmail.com', 'Progress 1.0', NULL, NULL, 'uploads/reports/rep_1767798438_13.pdf', NULL, 'PENDING_REVIEW', '2026-01-07', '2026-01-07 15:51:02');

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
  `status` enum('DRAFT','SUBMITTED','ASSIGNED','PENDING_REVIEW','REQUIRES_AMENDMENT','RESUBMITTED','RECOMMENDED','REJECTED','APPROVED','APPEALED','APPEAL_REJECTED') DEFAULT 'SUBMITTED',
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
(1, 'Hello World', NULL, '2@mail.com', 'uploads/prop_1766775766_2_mail_com.pdf', NULL, 'APPEAL_REJECTED', '2025-12-26 19:02:46', NULL, NULL, 'Normal', NULL, NULL, NULL, 0.00, 20.00, 0.00, 12),
(2, 'Approval', NULL, '2@mail.com', 'uploads/prop_1766776144_2_mail_com.pdf', NULL, 'APPROVED', '2025-12-26 19:09:04', NULL, NULL, 'Normal', NULL, NULL, NULL, 0.00, 0.00, 0.00, 12),
(3, 'Recommended', NULL, '2@mail.com', 'uploads/prop_1766776494_2_mail_com.pdf', NULL, 'APPROVED', '2025-12-26 19:14:54', NULL, NULL, 'Normal', NULL, NULL, NULL, 0.00, 0.00, 0.00, 12),
(5, 'Rejected HOD', NULL, '2@mail.com', 'uploads/prop_1766777657_2_mail_com.pdf', NULL, 'REJECTED', '2025-12-26 19:34:17', NULL, NULL, 'Normal', NULL, NULL, NULL, 0.00, 0.00, 0.00, 12),
(6, 'Rejected HOD', NULL, '2@mail.com', 'uploads/prop_1766777728_2_mail_com.pdf', NULL, NULL, '2025-12-26 19:35:28', NULL, NULL, 'Normal', NULL, NULL, NULL, 0.00, 0.00, 0.00, 12),
(7, 'new', NULL, '2@mail.com', 'uploads/prop_1767024370_2_mail_com.pdf', NULL, '', '2025-12-29 16:06:10', NULL, NULL, 'Normal', NULL, NULL, NULL, 0.00, 0.00, 0.00, 12),
(8, 'Test Proposal 1', NULL, 'researcher@gmail.com', 'uploads/prop_1767793973_researcher_gmail_com.pdf', NULL, 'REQUIRES_AMENDMENT', '2026-01-07 13:52:53', NULL, NULL, 'Normal', NULL, NULL, NULL, 0.00, 0.00, 0.00, 12),
(12, 'Test Proposal 2', NULL, 'researcher@gmail.com', 'uploads/prop_1767798101_researcher_gmail_com.pdf', NULL, 'APPEALED', '2026-01-07 15:01:41', NULL, NULL, 'Normal', NULL, NULL, NULL, 0.00, 0.00, 0.00, 12),
(13, 'Test Proposal 3', NULL, 'researcher@gmail.com', 'uploads/prop_1767798356_researcher_gmail_com.pdf', NULL, 'APPROVED', '2026-01-07 15:05:56', NULL, NULL, 'Normal', NULL, NULL, NULL, 0.00, 3000.00, 0.00, 12),
(15, 'Test Proposal 4', 'test', 'researcher@gmail.com', 'uploads/prop_1767801223_researcher_gmail_com.pdf', NULL, 'APPEALED', '2026-01-07 15:53:43', NULL, NULL, 'Normal', NULL, NULL, NULL, 3000.00, 0.00, 0.00, 12),
(17, 'test1', 'test1_description', '2@mail.com', 'uploads/prop_1768061833_2_mail_com.pdf', NULL, NULL, '2026-01-10 16:17:13', NULL, NULL, 'High', NULL, NULL, NULL, 9.99, 9.00, 0.00, 12),
(18, 'test5', 'reviewer don\\\'t approve yet', '2@mail.com', 'uploads/prop_1768109662_2_mail_com.pdf', NULL, 'ASSIGNED', '2026-01-11 05:34:22', NULL, NULL, 'Normal', NULL, NULL, NULL, 10000.00, 0.00, 0.00, 12),
(19, 'testproposal3', 'idk', 'researcher@gmail.com', 'uploads/prop_1767881930_researcher_gmail_com.pdf', NULL, '', '2026-01-08 06:18:50', NULL, NULL, 'Normal', NULL, NULL, NULL, 5000.00, 0.00, 0.00, 12),
(20, 'testproposal4', 'idk', 'researcher@gmail.com', 'uploads/prop_1767881947_researcher_gmail_com.pdf', NULL, 'APPROVED', '2026-01-08 06:19:07', NULL, NULL, 'Normal', NULL, NULL, NULL, 6000.00, 0.00, 0.00, 12),
(21, 'test proposal', 'idk', 'researcher@gmail.com', 'uploads/prop_1768391550_researcher_gmail_com.pdf', NULL, 'SUBMITTED', '2026-01-14 03:52:30', NULL, NULL, 'Normal', NULL, NULL, NULL, 1420.00, 0.00, 0.00, 12),
(22, 'testproposal5', 'idk', 'researcher@gmail.com', 'uploads/prop_1768391685_researcher_gmail_com.pdf', NULL, 'REJECTED', '2026-01-14 03:54:45', NULL, NULL, 'Normal', NULL, NULL, NULL, 4999.00, 0.00, 0.00, 6),
(23, 'test ', 'idk', 'researcher@gmail.com', 'uploads/prop_1768391951_researcher_gmail_com.pdf', NULL, 'APPROVED', '2026-01-14 03:59:11', NULL, NULL, 'Normal', NULL, NULL, NULL, 0.00, 0.00, 0.00, 14);

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
(3, 1, 3, 2, 3, 4, 5, 14, 'Good', '2026-01-11 18:21:38', '2026-01-11 18:21:38', 0),
(5, 6, 3, 1, 4, 3, 5, 13, 'excellent', '2026-01-11 18:22:18', '2026-01-17 07:21:50', 1),
(11, 7, 3, 2, 3, 4, 5, 14, 'null', '2026-01-12 11:07:06', '2026-01-17 10:14:18', 1),
(17, 17, 3, 3, 4, 5, 4, 16, 'asdf', '2026-01-12 14:39:06', '2026-01-17 07:51:04', 1);

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
  `decision` enum('RECOMMEND','REJECT') DEFAULT NULL,
  `review_date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`id`, `reviewer_id`, `reviewer_email`, `proposal_id`, `status`, `assigned_date`, `type`, `feedback`, `annotated_file`, `decision`, `review_date`) VALUES
(2, 1, NULL, 1, 'Completed', '2025-12-27', 'Proposal', 'so bad', 'uploads/reviews/rev_1766776110_prop_1766775766_2_mail_com.pdf', 'REJECT', '2025-12-27 03:08:30'),
(3, 1, NULL, 2, 'Completed', '2025-12-27', 'Proposal', 'good', NULL, 'RECOMMEND', '2025-12-27 03:09:52'),
(4, 1, NULL, 3, 'Completed', '2025-12-27', 'Proposal', 'great', NULL, 'RECOMMEND', '2025-12-27 03:15:33'),
(6, 1, NULL, 5, 'Completed', '2025-12-27', 'Proposal', 'good', NULL, 'RECOMMEND', '2025-12-27 03:35:11'),
(7, 1, NULL, 6, 'Completed', '2025-12-27', 'Proposal', 'ok la', NULL, 'RECOMMEND', '2026-01-10 16:58:25'),
(8, 1, NULL, 1, 'Completed', '2025-12-27', 'Appeal', 'baadddd', NULL, 'REJECT', '2025-12-27 03:44:27'),
(10, 1, NULL, 1, 'Completed', '2025-12-27', 'Appeal', 'bad', NULL, 'RECOMMEND', '2025-12-27 03:48:24'),
(13, 1, NULL, 1, 'Completed', '2025-12-27', 'Appeal', 'bad', NULL, 'REJECT', '2025-12-27 03:52:58'),
(14, 1, NULL, 7, 'Completed', '2025-12-30', 'Proposal', 'Top tier', NULL, 'RECOMMEND', '2026-01-10 16:58:04'),
(15, 1, NULL, 5, 'Completed', '2026-01-07', 'Proposal', NULL, NULL, 'REJECT', NULL),
(16, 1, NULL, 11, 'Completed', '2026-01-07', 'Proposal', NULL, NULL, 'REJECT', NULL),
(17, 1, NULL, 12, 'Completed', '2026-01-07', 'Proposal', NULL, NULL, 'REJECT', NULL),
(18, 1, NULL, 15, 'Completed', '2026-01-07', 'Proposal', NULL, NULL, 'REJECT', NULL),
(19, 1, NULL, 17, 'Completed', '2026-01-11', 'Proposal', 'veli good cepat approve this', NULL, 'RECOMMEND', '2026-01-11 10:58:38'),
(20, 1, NULL, 18, 'Pending', '2026-01-11', 'Proposal', NULL, NULL, NULL, NULL);

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
(1, 'Ms.reviewer', '1@mail.com', '$2y$10$sxtA2hC.vebtunPSbUxtM.iFcJMFF0xmPS3zRzGhjslcyDNQx7p0m', 'reviewer', NULL, 0, 0, 'female.png', 'default', 1, 1, 1, 1),
(2, 'researcher', '2@mail.com', '$2y$10$5xuzeZ.7gbxXW/wBHAKo5.0MGavXIZBvzWDS3Dk1ulQMb38.1X5qy', 'researcher', NULL, 1, 1, 'default.png', 'default', 1, 1, 1, 1),
(3, 'HOD', '3@mail.com', '$2y$10$TB9alxPUk86xQDjNWsa24.ISJllSZGStmk70QCdDlWbhsv4wbGqXe', 'hod', 1, 1, 1, 'default.png', 'default', 1, 1, 1, 1),
(4, 'mr admin', '4@mail.com', '$2y$10$0dlqy6UIW.iLj0M4.OVCHuNB3JXf9GxMlJScvf5W.Dw4Qw.aeihjC', 'admin', NULL, 1, 1, 'default.png', 'default', 1, 1, 1, 1),
(5, 'a', 'a@mail.com', '$2y$10$F3Ht1Hd2i2gW8oZGsGaJQONlqc6JQ7qwgsr3u0KyCyNUx1PhKmZKu', 'researcher', NULL, 1, 1, 'default.png', 'default', 1, 1, 1, 1),
(6, 'researcher', 'researcher@gmail.com', '$2y$10$najEye47FlSQ/CPwoDrRduvG/Aj2FGufluVvyhnbTVzgqHlkN3It6', 'researcher', NULL, 1, 1, 'default.png', 'default', 1, 1, 1, 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `appeal_requests`
--
ALTER TABLE `appeal_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `proposal_id` (`proposal_id`),
  ADD KEY `researcher_email` (`researcher_email`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `budget_items`
--
ALTER TABLE `budget_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `proposal_id` (`proposal_id`),
  ADD KEY `category` (`category`);

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
  ADD KEY `proposal_id` (`proposal_id`),
  ADD KEY `version_number` (`version_number`);

--
-- Indexes for table `expenditures`
--
ALTER TABLE `expenditures`
  ADD PRIMARY KEY (`id`),
  ADD KEY `budget_item_id` (`budget_item_id`),
  ADD KEY `transaction_date` (`transaction_date`);

--
-- Indexes for table `extension_requests`
--
ALTER TABLE `extension_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `report_id` (`report_id`),
  ADD KEY `researcher_email` (`researcher_email`),
  ADD KEY `status` (`status`);

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
  ADD KEY `approved_by` (`approved_by`);

--
-- Indexes for table `grant_document`
--
ALTER TABLE `grant_document`
  ADD PRIMARY KEY (`document_id`),
  ADD KEY `fund_id` (`fund_id`),
  ADD KEY `uploaded_by` (`uploaded_by`);

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
  ADD KEY `grant_id` (`grant_id`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `misconduct_reports`
--
ALTER TABLE `misconduct_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `proposal_id` (`proposal_id`),
  ADD KEY `reviewer_email` (`reviewer_email`),
  ADD KEY `researcher_email` (`researcher_email`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_email` (`user_email`),
  ADD KEY `is_read` (`is_read`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `progress_reports`
--
ALTER TABLE `progress_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `proposal_id` (`proposal_id`),
  ADD KEY `researcher_email` (`researcher_email`),
  ADD KEY `status` (`status`),
  ADD KEY `deadline` (`deadline`);

--
-- Indexes for table `proposals`
--
ALTER TABLE `proposals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `researcher_email` (`researcher_email`),
  ADD KEY `reviewer_email` (`reviewer_email`),
  ADD KEY `status` (`status`),
  ADD KEY `created_at` (`created_at`),
  ADD KEY `priority` (`priority`);

--
-- Indexes for table `proposal_rubric`
--
ALTER TABLE `proposal_rubric`
  ADD PRIMARY KEY (`rubric_id`),
  ADD UNIQUE KEY `unique_proposal_hod` (`proposal_id`,`hod_id`),
  ADD KEY `hod_id` (`hod_id`);

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
  ADD KEY `reviewer_email` (`reviewer_email`),
  ADD KEY `proposal_id` (`proposal_id`),
  ADD KEY `status` (`status`),
  ADD KEY `type` (`type`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `budget_items`
--
ALTER TABLE `budget_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `document_versions`
--
ALTER TABLE `document_versions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `expenditures`
--
ALTER TABLE `expenditures`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `extension_requests`
--
ALTER TABLE `extension_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `fund`
--
ALTER TABLE `fund`
  MODIFY `fund_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `grant_allocation`
--
ALTER TABLE `grant_allocation`
  MODIFY `allocation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `grant_document`
--
ALTER TABLE `grant_document`
  MODIFY `document_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `hod_tier_assignment`
--
ALTER TABLE `hod_tier_assignment`
  MODIFY `assignment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `milestones`
--
ALTER TABLE `milestones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `misconduct_reports`
--
ALTER TABLE `misconduct_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=77;

--
-- AUTO_INCREMENT for table `progress_reports`
--
ALTER TABLE `progress_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `proposals`
--
ALTER TABLE `proposals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `proposal_rubric`
--
ALTER TABLE `proposal_rubric`
  MODIFY `rubric_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `research_progress`
--
ALTER TABLE `research_progress`
  MODIFY `progress_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

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
  ADD CONSTRAINT `expenditures_ibfk_1` FOREIGN KEY (`budget_item_id`) REFERENCES `budget_items` (`id`) ON DELETE CASCADE;

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
-- Constraints for table `research_progress`
--
ALTER TABLE `research_progress`
  ADD CONSTRAINT `research_progress_ibfk_1` FOREIGN KEY (`fund_id`) REFERENCES `fund` (`fund_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
