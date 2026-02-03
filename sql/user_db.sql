-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 24, 2026 at 05:15 AM
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

--
-- Dumping data for table `budget_items`
--

INSERT INTO `budget_items` (`id`, `proposal_id`, `category`, `description`, `allocated_amount`, `spent_amount`, `created_at`) VALUES
(1, 21, 'Equipment', 'Equipment budget', 120.00, 0.00, '2026-01-14 11:52:30'),
(2, 21, 'Materials', 'Materials budget', 300.00, 0.00, '2026-01-14 11:52:30'),
(3, 21, 'Travel', 'Travel budget', 400.00, 0.00, '2026-01-14 11:52:30'),
(4, 21, 'Personnel', 'Personnel budget', 500.00, 0.00, '2026-01-14 11:52:30'),
(5, 21, 'Other', 'Other budget', 100.00, 0.00, '2026-01-14 11:52:30'),
(6, 22, 'Equipment', 'Equipment budget', 1000.00, 0.00, '2026-01-14 11:54:45'),
(7, 22, 'Materials', 'Materials budget', 1000.00, 0.00, '2026-01-14 11:54:45'),
(8, 22, 'Travel', 'Travel budget', 1999.00, 0.00, '2026-01-14 11:54:45'),
(9, 22, 'Other', 'Other budget', 1000.00, 0.00, '2026-01-14 11:54:45'),
(10, 24, 'Equipment', 'Equipment budget', 300.00, 0.00, '2026-01-21 14:04:18'),
(11, 24, 'Materials', 'Materials budget', 200.00, 0.00, '2026-01-21 14:04:18'),
(12, 24, 'Travel', 'Travel budget', 100.00, 0.00, '2026-01-21 14:04:18'),
(13, 24, 'Personnel', 'Personnel budget', 1000.00, 0.00, '2026-01-21 14:04:18'),
(14, 24, 'Other', 'Other budget', 1000.00, 0.00, '2026-01-21 14:04:18'),
(15, 25, 'Equipment', 'Equipment budget', 100.00, 0.00, '2026-01-22 06:38:41'),
(16, 25, 'Materials', 'Materials budget', 200.00, 0.00, '2026-01-22 06:38:42'),
(17, 25, 'Travel', 'Travel budget', 200.00, 0.00, '2026-01-22 06:38:42'),
(18, 25, 'Personnel', 'Personnel budget', 100.00, 0.00, '2026-01-22 06:38:42'),
(19, 25, 'Other', 'Other budget', 200.00, 0.00, '2026-01-22 06:38:42'),
(20, 26, 'Equipment', 'Equipment budget', 11.00, 0.00, '2026-01-23 17:23:40'),
(21, 26, 'Materials', 'Materials budget', 11.00, 0.00, '2026-01-23 17:23:40'),
(22, 26, 'Travel', 'Travel budget', 11.00, 0.00, '2026-01-23 17:23:40'),
(23, 26, 'Personnel', 'Personnel budget', 11.00, 0.00, '2026-01-23 17:23:40'),
(24, 26, 'Other', 'Other budget', 11.00, 0.00, '2026-01-23 17:23:40');

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
(1, 21, 'v1.0', 'uploads/prop_1768391550_researcher_gmail_com.pdf', 'researcher@gmail.com', '2026-01-14 11:52:30', NULL),
(2, 22, 'v1.0', 'uploads/prop_1768391685_researcher_gmail_com.pdf', 'researcher@gmail.com', '2026-01-14 11:54:45', NULL),
(3, 23, 'v1.0', 'uploads/prop_1768391951_researcher_gmail_com.pdf', 'researcher@gmail.com', '2026-01-14 11:59:11', NULL),
(4, 24, 'v1.0', 'uploads/prop_1769004258_researcher_gmail_com.pdf', 'researcher@gmail.com', '2026-01-21 14:04:18', NULL),
(5, 25, 'v1.0', 'uploads/prop_1769063921_researcher_gmail_com.pdf', 'researcher@gmail.com', '2026-01-22 06:38:42', NULL),
(6, 26, 'v1.0', 'uploads/prop_1769189020_a_mail_com.pdf', 'a@mail.com', '2026-01-23 17:23:40', NULL);

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

--
-- Dumping data for table `expenditures`
--

INSERT INTO `expenditures` (`id`, `budget_item_id`, `amount`, `transaction_date`, `description`, `receipt_path`, `reimbursement_request_id`, `status`, `created_at`) VALUES
(1, 10, 100.00, '2026-01-21', 'idk', 'uploads/receipts/receipt_1769006189_grant24.pdf', NULL, 'PENDING_REIMBURSEMENT', '2026-01-21 14:36:29'),
(2, 10, 100.00, '2026-01-21', 'idk', 'uploads/receipts/receipt_1769006221_grant24.pdf', 16, 'UNDER_REVIEW', '2026-01-21 14:37:01'),
(3, 10, 100.00, '2026-01-21', 'idk', 'uploads/receipts/receipt_1769006289_grant24.pdf', 15, 'UNDER_REVIEW', '2026-01-21 14:38:09'),
(4, 15, 100.00, '2026-01-22', 'id', 'uploads/receipts/receipt_1769064296_grant25.pdf', NULL, 'PENDING_REIMBURSEMENT', '2026-01-22 06:44:56'),
(5, 15, 50.00, '2026-01-22', 'idk', 'uploads/receipts/receipt_1769064703_grant25.pdf', 17, 'UNDER_REVIEW', '2026-01-22 06:51:43'),
(6, 15, 50.00, '2026-01-22', 'idk', 'uploads/receipts/receipt_1769064737_grant25.pdf', NULL, 'PENDING_REIMBURSEMENT', '2026-01-22 06:52:17'),
(7, 18, 100.00, '2026-01-22', 'yes', 'uploads/receipts/receipt_1769070529_grant25.pdf', NULL, 'PENDING_REIMBURSEMENT', '2026-01-22 08:28:49');

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
(4, 2, 'researcher@gmail.com', '2026-01-10', 'idk', 'PENDING', '2026-01-08 14:32:10'),
(5, 4, 'researcher@gmail.com', '2026-01-30', 'isk', 'PENDING', '2026-01-21 14:05:35'),
(6, 4, 'researcher@gmail.com', '2026-01-24', 'why', 'PENDING', '2026-01-21 14:38:28'),
(7, 4, 'researcher@gmail.com', '2026-01-24', 'why', 'PENDING', '2026-01-21 14:39:14'),
(8, 4, 'researcher@gmail.com', '2026-01-24', 'why', 'PENDING', '2026-01-21 14:45:01');

-- --------------------------------------------------------

--
-- Table structure for table `issue_attachments`
--

CREATE TABLE `issue_attachments` (
  `id` int(11) NOT NULL,
  `message_id` int(11) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

--
-- Dumping data for table `issue_messages`
--

INSERT INTO `issue_messages` (`id`, `report_id`, `sender_role`, `sender_email`, `message`, `created_at`, `attachment_path`, `attachment_name`, `attachment_mime`) VALUES
(1, 1, 'admin', 'admin@gmail.com', 'hihi', '2026-01-23 17:17:58', NULL, NULL, NULL);

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
(1, 20, 'Literature Review Complete', 'Complete comprehensive review of existing research', '2026-02-15', '2026-01-21', 'COMPLETED', '2026-01-20 05:39:46'),
(2, 20, 'Data Collection Phase 1', 'Collect initial dataset from primary sources', '2026-04-30', '2026-01-21', 'COMPLETED', '2026-01-20 05:39:46'),
(3, 20, 'Preliminary Analysis', 'Conduct initial statistical analysis', '2026-06-15', '2026-01-21', 'COMPLETED', '2026-01-20 05:39:46'),
(4, 20, 'Final Report Draft', 'Submit draft findings to supervisor', '2026-08-30', '2026-01-21', 'COMPLETED', '2026-01-20 05:39:46'),
(5, 24, 'idk', 'idk', '2026-01-22', NULL, 'PENDING', '2026-01-21 14:04:18'),
(6, 24, 'idk', 'idk', '2026-01-24', NULL, 'PENDING', '2026-01-21 14:04:18'),
(7, 25, 'yes', 'yes', '2026-01-23', '2026-01-22', 'COMPLETED', '2026-01-22 06:38:42');

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
(3, 1, '1@mail.com', '2@mail.com', 'Data Fabrication', 'kick', '', '2025-12-27 18:10:19');

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
(67, '3@mail.com', 'Appeal Request: researcher@gmail.com has contested rejection of Proposal #15. Please review and potentially reassign to a new reviewer.', 0, '2026-01-08 06:24:20', 'alert'),
(68, '3@mail.com', 'Deadline Extension Request: researcher@gmail.com requests extension for Report #1 to 2026-01-23. Reason: idk', 0, '2026-01-08 06:24:41', 'alert'),
(69, '4@mail.com', 'New Proposal Submitted: \'testproposal1\' by researcher@gmail.com', 0, '2026-01-08 14:17:56', 'alert'),
(70, '4@mail.com', 'New Proposal Submitted: \'testproposal2\' by researcher@gmail.com', 0, '2026-01-08 14:18:18', 'alert'),
(71, '4@mail.com', 'New Proposal Submitted: \'testproposal3\' by researcher@gmail.com', 0, '2026-01-08 14:18:50', 'alert'),
(72, '4@mail.com', 'New Proposal Submitted: \'testproposal4\' by researcher@gmail.com', 0, '2026-01-08 14:19:07', 'alert'),
(73, '1@mail.com', 'Proposal #18 has been amended and resubmitted by researcher@gmail.com. Please verify corrections.', 0, '2026-01-08 14:21:29', 'alert'),
(74, '3@mail.com', 'Appeal Request: researcher@gmail.com has contested rejection of Proposal #19. Please review and potentially reassign to a new reviewer.', 0, '2026-01-08 14:23:07', 'alert'),
(75, 'researcher@gmail.com', 'Appeal Update: The HOD has accepted your appeal. Your proposal will be reassigned to a new reviewer.', 1, '2026-01-08 14:29:48', 'info'),
(76, '1@mail.com', 'New Assignment: You have been assigned a proposal.', 0, '2026-01-08 14:30:15', 'info'),
(77, '3@mail.com', 'New Progress Report submitted by researcher@gmail.com for Grant #20: \'progress1\'', 0, '2026-01-08 14:31:59', 'alert'),
(78, '3@mail.com', 'Deadline Extension Request: researcher@gmail.com requests extension for Report #2 to 2026-01-10. Reason: idk', 0, '2026-01-08 14:32:10', 'alert'),
(79, '4@mail.com', 'New Proposal Submitted: \'test proposal\' by researcher@gmail.com', 0, '2026-01-14 11:52:30', 'alert'),
(80, '3@mail.com', 'New Progress Report submitted by researcher@gmail.com for Grant #20: \'progress\'', 0, '2026-01-14 11:53:15', 'alert'),
(81, '4@mail.com', 'New Proposal Submitted: \'testproposal5\' by researcher@gmail.com', 0, '2026-01-14 11:54:45', 'alert'),
(82, '4@mail.com', 'New Proposal Submitted: \'test \' by researcher@gmail.com', 0, '2026-01-14 11:59:11', 'alert'),
(83, 'researcher@gmail.com', 'Update on \'testproposal1\': Your proposal status is now rejected.', 1, '2026-01-19 12:53:55', 'info'),
(84, '4@mail.com', 'New Proposal Submitted: \'test123\' by researcher@gmail.com', 0, '2026-01-21 14:04:18', 'alert'),
(85, '3@mail.com', 'New Progress Report submitted by researcher@gmail.com for Grant #20: \'idk\'', 0, '2026-01-21 14:04:55', 'alert'),
(86, '3@mail.com', 'Deadline Extension Request: researcher@gmail.com requests extension for Report #4 to 2026-01-30. Reason: isk', 0, '2026-01-21 14:05:35', 'alert'),
(87, '3@mail.com', 'Deadline Extension Request: researcher@gmail.com requests extension for Report #4 to 2026-01-24. Reason: why', 0, '2026-01-21 14:38:28', 'alert'),
(88, '3@mail.com', 'Deadline Extension Request: researcher@gmail.com requests extension for Report #4 to 2026-01-24. Reason: why', 0, '2026-01-21 14:39:14', 'alert'),
(89, '3@mail.com', 'Deadline Extension Request: researcher@gmail.com requests extension for Report #4 to 2026-01-24. Reason: why', 0, '2026-01-21 14:45:01', 'alert'),
(90, '4@mail.com', 'New Proposal Submitted: \'test1234\' by researcher@gmail.com', 0, '2026-01-22 06:38:42', 'alert'),
(91, '3@mail.com', 'New Progress Report submitted by researcher@gmail.com for Grant #25: \'yes\'', 0, '2026-01-22 06:40:51', 'alert'),
(92, '3@mail.com', 'New Progress Report submitted by researcher@gmail.com for Grant #25: \'yes\'', 0, '2026-01-22 06:41:48', 'alert'),
(93, '3@mail.com', 'Reimbursement Request: researcher@gmail.com requests $100.00 for Grant #24', 0, '2026-01-22 08:27:03', 'alert'),
(94, '3@mail.com', 'Reimbursement Request: researcher@gmail.com requests RM100.00 for Grant #24', 0, '2026-01-22 12:15:42', 'alert'),
(95, '3@mail.com', 'Reimbursement Request: researcher@gmail.com requests RM50.00 for Grant #25', 0, '2026-01-22 12:53:34', 'alert'),
(96, '2@mail.com', 'Action Required: The reviewer requested amendments on \'new\'. Please check the dashboard.', 0, '2026-01-22 13:14:24', 'info'),
(97, '1@mail.com', 'New Assignment: You have been assigned a proposal.', 0, '2026-01-23 09:20:30', 'info'),
(98, '2@mail.com', 'Admin Warning: Please review your recent activity. Report ID: #3', 0, '2026-01-23 17:17:27', 'info'),
(99, '4@mail.com', 'New Proposal Submitted: \'a\' by a@mail.com', 0, '2026-01-23 17:23:40', 'alert'),
(100, 'admin@gmail.com', 'New Proposal Submitted: \'a\' by a@mail.com', 0, '2026-01-23 17:23:40', 'alert');

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
(2, 20, 'researcher@gmail.com', 'progress1', 'idk', 'idk', 'uploads/reports/rep_1767882719_20.pdf', '2026-01-08', 'PENDING_REVIEW', '2026-01-08', '2026-01-08 14:31:59'),
(3, 20, 'researcher@gmail.com', 'progress', 'idk', 'idk', 'uploads/reports/rep_1768391595_20.pdf', '2026-01-15', 'PENDING_REVIEW', '2026-01-14', '2026-01-14 11:53:15'),
(4, 20, 'researcher@gmail.com', 'idk', 'idk', 'idk', 'uploads/reports/rep_1769004295_20.pdf', '2026-01-22', 'PENDING_REVIEW', '2026-01-21', '2026-01-21 14:04:55'),
(5, 25, 'researcher@gmail.com', 'yes', 'idk', 'no', 'uploads/reports/rep_1769064051_25.pdf', '2026-01-23', 'PENDING_REVIEW', '2026-01-22', '2026-01-22 06:40:51'),
(6, 25, 'researcher@gmail.com', 'yes', 'idk', 'no', 'uploads/reports/rep_1769064108_25.pdf', '2026-01-23', 'PENDING_REVIEW', '2026-01-22', '2026-01-22 06:41:48');

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
  `reviewer_feedback` text DEFAULT NULL,
  `amendment_notes` text DEFAULT NULL,
  `budget_requested` decimal(10,2) DEFAULT 0.00,
  `duration_months` int(11) DEFAULT 12,
  `approved_budget` decimal(10,2) DEFAULT 0.00,
  `amount_spent` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `proposals`
--

INSERT INTO `proposals` (`id`, `title`, `description`, `researcher_email`, `file_path`, `reviewer_email`, `status`, `created_at`, `approved_at`, `resubmitted_at`, `priority`, `reviewer_feedback`, `amendment_notes`, `budget_requested`, `duration_months`, `approved_budget`, `amount_spent`) VALUES
(1, 'Hello World', NULL, '2@mail.com', 'uploads/prop_1766775766_2_mail_com.pdf', NULL, 'APPEAL_REJECTED', '2025-12-26 19:02:46', NULL, NULL, 'Normal', NULL, NULL, 0.00, 12, 0.00, 0.00),
(2, 'Approval', NULL, '2@mail.com', 'uploads/prop_1766776144_2_mail_com.pdf', NULL, 'APPROVED', '2025-12-26 19:09:04', NULL, NULL, 'Normal', NULL, NULL, 0.00, 12, 0.00, 0.00),
(3, 'Recommended', NULL, '2@mail.com', 'uploads/prop_1766776494_2_mail_com.pdf', NULL, 'APPROVED', '2025-12-26 19:14:54', NULL, NULL, 'Normal', NULL, NULL, 0.00, 12, 0.00, 0.00),
(5, 'Rejected HOD', NULL, '2@mail.com', 'uploads/prop_1766777657_2_mail_com.pdf', NULL, 'REJECTED', '2025-12-26 19:34:17', NULL, NULL, 'Normal', NULL, NULL, 0.00, 12, 0.00, 0.00),
(6, 'Rejected HOD', NULL, '2@mail.com', 'uploads/prop_1766777728_2_mail_com.pdf', NULL, 'ASSIGNED', '2025-12-26 19:35:28', NULL, NULL, 'Normal', NULL, NULL, 0.00, 12, 0.00, 0.00),
(7, 'new', NULL, '2@mail.com', 'uploads/prop_1767024370_2_mail_com.pdf', NULL, 'REQUIRES_AMENDMENT', '2025-12-29 16:06:10', NULL, NULL, 'Normal', 'no', NULL, 0.00, 12, 0.00, 0.00),
(17, 'testproposal1', 'idk', 'researcher@gmail.com', 'uploads/prop_1767881876_researcher_gmail_com.pdf', NULL, 'REJECTED', '2026-01-08 14:17:56', NULL, NULL, 'Normal', NULL, NULL, 2999.99, 12, 0.00, 0.00),
(18, 'testproposal2', 'idk', 'researcher@gmail.com', 'uploads/amend_1767882089_18.pdf', NULL, 'RESUBMITTED', '2026-01-08 14:18:18', NULL, '2026-01-08 22:21:29', 'Normal', NULL, 'amend', 1500.00, 12, 0.00, 0.00),
(20, 'testproposal4', 'idk', 'researcher@gmail.com', 'uploads/prop_1767881947_researcher_gmail_com.pdf', NULL, 'APPROVED', '2026-01-08 14:19:07', NULL, NULL, 'Normal', NULL, NULL, 6000.00, 12, 0.00, 0.00),
(21, 'test proposal', 'idk', 'researcher@gmail.com', 'uploads/prop_1768391550_researcher_gmail_com.pdf', NULL, 'ASSIGNED', '2026-01-14 11:52:30', NULL, NULL, 'Normal', NULL, NULL, 1420.00, 12, 0.00, 0.00),
(22, 'testproposal5', 'idk', 'researcher@gmail.com', 'uploads/prop_1768391685_researcher_gmail_com.pdf', NULL, 'REJECTED', '2026-01-14 11:54:45', NULL, NULL, 'Normal', NULL, NULL, 4999.00, 6, 0.00, 0.00),
(23, 'test ', 'idk', 'researcher@gmail.com', 'uploads/prop_1768391951_researcher_gmail_com.pdf', NULL, 'APPROVED', '2026-01-14 11:59:11', NULL, NULL, 'Normal', NULL, NULL, 0.00, 14, 0.00, 0.00),
(24, 'test123', 'idk', 'researcher@gmail.com', 'uploads/prop_1769004258_researcher_gmail_com.pdf', NULL, 'APPROVED', '2026-01-21 14:04:18', NULL, NULL, 'Normal', NULL, NULL, 2600.00, 12, 0.00, 0.00),
(25, 'test1234', 'idk', 'researcher@gmail.com', 'uploads/prop_1769063921_researcher_gmail_com.pdf', NULL, 'APPROVED', '2026-01-22 06:38:41', NULL, NULL, 'Normal', NULL, NULL, 800.00, 16, 0.00, 0.00),
(26, 'a', 'a', 'a@mail.com', 'uploads/prop_1769189020_a_mail_com.pdf', NULL, 'SUBMITTED', '2026-01-23 17:23:40', NULL, NULL, 'Normal', NULL, NULL, 55.00, 3, 0.00, 0.00);

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

--
-- Dumping data for table `reimbursement_requests`
--

INSERT INTO `reimbursement_requests` (`id`, `grant_id`, `researcher_email`, `total_amount`, `justification`, `status`, `hod_remarks`, `requested_at`, `reviewed_at`) VALUES
(15, 24, 'researcher@gmail.com', 100.00, 'yes', 'PENDING', NULL, '2026-01-22 08:27:03', NULL),
(16, 24, 'researcher@gmail.com', 100.00, 'yes', 'PENDING', NULL, '2026-01-22 12:15:42', NULL),
(17, 25, 'researcher@gmail.com', 50.00, 'yay', 'PENDING', NULL, '2026-01-22 12:53:34', NULL);

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
(7, 1, NULL, 6, 'Pending', '2025-12-27', 'Proposal', NULL, NULL, NULL, NULL),
(8, 1, NULL, 1, 'Completed', '2025-12-27', 'Appeal', 'baadddd', NULL, 'REJECT', '2025-12-27 03:44:27'),
(10, 1, NULL, 1, 'Completed', '2025-12-27', 'Appeal', 'bad', NULL, 'RECOMMEND', '2025-12-27 03:48:24'),
(13, 1, NULL, 1, 'Completed', '2025-12-27', 'Appeal', 'bad', NULL, 'REJECT', '2025-12-27 03:52:58'),
(14, 1, NULL, 7, 'Completed', '2025-12-30', 'Proposal', 'no', 'uploads/reviews/rev_1769087664_Test proposal 1.pdf', '', '2026-01-22 21:14:24'),
(15, 1, NULL, 5, 'Completed', '2026-01-07', 'Proposal', NULL, NULL, 'REJECT', NULL),
(16, 1, NULL, 11, 'Completed', '2026-01-07', 'Proposal', NULL, NULL, 'REJECT', NULL),
(17, 1, NULL, 12, 'Completed', '2026-01-07', 'Proposal', NULL, NULL, 'REJECT', NULL),
(18, 1, NULL, 15, 'Completed', '2026-01-07', 'Proposal', NULL, NULL, 'REJECT', NULL),
(19, 1, NULL, 19, 'Completed', '2026-01-08', 'Proposal', NULL, NULL, 'REJECT', NULL),
(20, 1, NULL, 17, 'Completed', '2026-01-08', 'Proposal', 'no good', NULL, 'REJECT', '2026-01-19 20:53:55'),
(21, 1, NULL, 22, 'Completed', '2026-01-14', 'Proposal', NULL, NULL, 'REJECT', NULL),
(22, 1, NULL, 21, 'Pending', '2026-01-23', 'Proposal', NULL, NULL, NULL, NULL);

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

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `notify_email`, `notify_system`, `profile_pic`, `avatar`, `notify_new_assign`, `notify_appeals`, `notify_hod_approve`, `notify_hod_reject`, `status`) VALUES
(1, 'Ms.reviewer', '1@mail.com', '$2y$10$sxtA2hC.vebtunPSbUxtM.iFcJMFF0xmPS3zRzGhjslcyDNQx7p0m', 'reviewer', 0, 0, 'female.png', 'default', 1, 1, 1, 1, 'APPROVED'),
(2, 'agnes', '2@mail.com', '$2y$10$5xuzeZ.7gbxXW/wBHAKo5.0MGavXIZBvzWDS3Dk1ulQMb38.1X5qy', 'researcher', 1, 1, 'default.png', 'default', 1, 1, 1, 1, 'APPROVED'),
(3, 'HOD', '3@mail.com', '$2y$10$TB9alxPUk86xQDjNWsa24.ISJllSZGStmk70QCdDlWbhsv4wbGqXe', 'hod', 1, 1, 'default.png', 'default', 1, 1, 1, 1, 'APPROVED'),
(4, 'mr admin', '4@mail.com', '$2y$10$0dlqy6UIW.iLj0M4.OVCHuNB3JXf9GxMlJScvf5W.Dw4Qw.aeihjC', 'admin', 1, 1, 'default.png', 'default', 1, 1, 1, 1, 'APPROVED'),
(5, 'a', 'a@mail.com', '$2y$10$F3Ht1Hd2i2gW8oZGsGaJQONlqc6JQ7qwgsr3u0KyCyNUx1PhKmZKu', 'researcher', 1, 1, 'default.png', 'default', 1, 1, 1, 1, 'APPROVED'),
(6, 'agnes', 'researcher@gmail.com', '$2y$10$najEye47FlSQ/CPwoDrRduvG/Aj2FGufluVvyhnbTVzgqHlkN3It6', 'researcher', 1, 1, 'default.png', 'default', 1, 1, 1, 1, 'PENDING'),
(7, 'admin', 'admin@gmail.com', '$2y$10$tor3Z2rANNrh/Fb.5vhqS.gIhfCtz0Er2n3/JtoP5UrbJWkeCOffS', 'admin', 1, 1, 'default.png', 'default', 1, 1, 1, 1, 'PENDING');

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
  ADD KEY `transaction_date` (`transaction_date`),
  ADD KEY `reimbursement_request_id` (`reimbursement_request_id`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `extension_requests`
--
ALTER TABLE `extension_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `report_id` (`report_id`),
  ADD KEY `researcher_email` (`researcher_email`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `issue_attachments`
--
ALTER TABLE `issue_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `message_id` (`message_id`);

--
-- Indexes for table `issue_messages`
--
ALTER TABLE `issue_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `report_id` (`report_id`);

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
-- Indexes for table `reimbursement_requests`
--
ALTER TABLE `reimbursement_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `grant_id` (`grant_id`),
  ADD KEY `researcher_email` (`researcher_email`),
  ADD KEY `status` (`status`);

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
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `appeal_requests`
--
ALTER TABLE `appeal_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `budget_items`
--
ALTER TABLE `budget_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `document_versions`
--
ALTER TABLE `document_versions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `expenditures`
--
ALTER TABLE `expenditures`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `extension_requests`
--
ALTER TABLE `extension_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `issue_attachments`
--
ALTER TABLE `issue_attachments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `issue_messages`
--
ALTER TABLE `issue_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `milestones`
--
ALTER TABLE `milestones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `misconduct_reports`
--
ALTER TABLE `misconduct_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=101;

--
-- AUTO_INCREMENT for table `progress_reports`
--
ALTER TABLE `progress_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `proposals`
--
ALTER TABLE `proposals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `reimbursement_requests`
--
ALTER TABLE `reimbursement_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

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
-- Constraints for table `issue_attachments`
--
ALTER TABLE `issue_attachments`
  ADD CONSTRAINT `issue_attachments_ibfk_1` FOREIGN KEY (`message_id`) REFERENCES `issue_messages` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `issue_messages`
--
ALTER TABLE `issue_messages`
  ADD CONSTRAINT `issue_messages_ibfk_1` FOREIGN KEY (`report_id`) REFERENCES `misconduct_reports` (`id`) ON DELETE CASCADE;

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
-- Constraints for table `reimbursement_requests`
--
ALTER TABLE `reimbursement_requests`
  ADD CONSTRAINT `reimbursement_requests_ibfk_1` FOREIGN KEY (`grant_id`) REFERENCES `proposals` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
