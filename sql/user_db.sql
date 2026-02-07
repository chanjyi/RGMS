-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 07, 2026 at 08:34 AM
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
(24, '4@mail.com', 'admin', '::1', 'ASSIGN_REVIEWER', 'PROPOSAL', 9, 'Assign Proposal', 'Assigned proposal #9 to reviewer_id=2', '2026-02-05 02:37:28'),
(25, '2@mail.com', 'researcher', '::1', 'CREATE', 'PROPOSAL', 10, 'Submit Proposal', 'Researcher submitted proposal: Active Research View', '2026-02-05 16:05:45'),
(26, '2@mail.com', 'researcher', '::1', 'CREATE', 'PROPOSAL', 11, 'Submit Proposal', 'Researcher submitted proposal: Active Research Flag follow-up', '2026-02-05 16:07:41'),
(27, '2@mail.com', 'researcher', '::1', 'CREATE', 'PROPOSAL', 12, 'Submit Proposal', 'Researcher submitted proposal: Active Research Upload Progress', '2026-02-05 16:08:57'),
(28, '4@mail.com', 'admin', '::1', 'ASSIGN_REVIEWER', 'PROPOSAL', 10, 'Assign Proposal', 'Assigned proposal #10 to reviewer_id=2', '2026-02-05 16:09:40'),
(29, '4@mail.com', 'admin', '::1', 'ASSIGN_REVIEWER', 'PROPOSAL', 12, 'Assign Proposal', 'Assigned proposal #12 to reviewer_id=2', '2026-02-05 16:09:44'),
(30, '4@mail.com', 'admin', '::1', 'ASSIGN_REVIEWER', 'PROPOSAL', 11, 'Assign Proposal', 'Assigned proposal #11 to reviewer_id=2', '2026-02-05 16:09:47'),
(31, '1@mail.com', 'reviewer', '::1', 'VIEW_MISCONDUCT_CASES', 'MISCONDUCT_REPORT', NULL, 'View Misconduct Cases', 'Reviewer viewed misconduct cases list', '2026-02-05 16:13:05'),
(32, '2@mail.com', 'researcher', '::1', 'CREATE', 'PROGRESS_REPORT', NULL, 'Submit Progress Report', 'Researcher submitted progress report for grant #12: progress 1', '2026-02-05 18:14:32'),
(33, '2@mail.com', 'researcher', '::1', 'CREATE', 'PROGRESS_REPORT', NULL, 'Submit Progress Report', 'Researcher submitted progress report for grant #12: progress 1', '2026-02-05 18:14:36'),
(34, '2@mail.com', 'researcher', '::1', 'CREATE', 'EXTENSION_REQUEST', NULL, 'Request Extension', 'Researcher requested extension for report #1 to 2026-02-13', '2026-02-05 22:51:32'),
(35, '2@mail.com', 'researcher', '::1', 'CREATE', 'EXTENSION_REQUEST', NULL, 'Request Extension', 'Researcher requested extension for report #6 to 2026-02-09', '2026-02-06 01:05:12'),
(36, '2@mail.com', 'researcher', '::1', 'CREATE', 'EXTENSION_REQUEST', NULL, 'Request Extension', 'Researcher requested extension for report #6 to 2026-02-09', '2026-02-06 01:30:14'),
(37, '2@mail.com', 'researcher', '::1', 'CREATE', 'EXPENDITURE', NULL, 'Log Expenditure', 'Researcher logged expenditure RM/$30 for budget_item_id=10', '2026-02-06 01:31:22'),
(38, '2@mail.com', 'researcher', '::1', 'CREATE', 'EXPENDITURE', NULL, 'Log Expenditure', 'Researcher logged expenditure RM/$30 for budget_item_id=10', '2026-02-06 01:31:30'),
(39, '2@mail.com', 'researcher', '::1', 'CREATE', 'REIMBURSEMENT_REQUEST', 1, 'Request Reimbursement', 'Researcher requested reimbursement for grant #10 (total: RM60.00)', '2026-02-06 02:02:26'),
(40, '2@mail.com', 'researcher', '::1', 'CREATE', 'EXPENDITURE', NULL, 'Log Expenditure', 'Researcher logged expenditure RM/$300 for budget_item_id=12', '2026-02-06 03:33:53'),
(41, '2@mail.com', 'researcher', '::1', 'CREATE', 'REIMBURSEMENT_REQUEST', 2, 'Request Reimbursement', 'Researcher requested reimbursement for grant #10 (total: RM300.00)', '2026-02-06 03:34:02'),
(42, '2@mail.com', 'researcher', '::1', 'CREATE', 'PROGRESS_REPORT', NULL, 'Submit Progress Report', 'Researcher submitted progress report for grant #12: progress 2', '2026-02-06 03:36:28'),
(43, '2@mail.com', 'researcher', '::1', 'CREATE', 'EXPENDITURE', NULL, 'Log Expenditure', 'Researcher logged expenditure RM/$50 for budget_item_id=11', '2026-02-06 04:30:43'),
(44, '2@mail.com', 'researcher', '::1', 'CREATE', 'EXPENDITURE', NULL, 'Log Expenditure', 'Researcher logged expenditure RM/$20 for budget_item_id=10', '2026-02-06 04:31:24'),
(45, '2@mail.com', 'researcher', '::1', 'CREATE', 'REIMBURSEMENT_REQUEST', 3, 'Request Reimbursement', 'Researcher requested reimbursement for grant #10 (total: RM70.00)', '2026-02-06 04:31:30'),
(46, 'researcher@gmail.com', 'researcher', '::1', 'CREATE', 'PROPOSAL', 26, 'Submit Proposal', 'Researcher submitted proposal: test', '2026-02-06 12:58:57'),
(47, 'researcher@gmail.com', 'researcher', '::1', 'DELETE', 'PROPOSAL', 26, 'Delete Proposal', 'Researcher deleted proposal #26', '2026-02-06 13:10:23'),
(48, 'researcher@gmail.com', 'researcher', '::1', 'CREATE', 'PROPOSAL', 27, 'Submit Proposal', 'Researcher submitted proposal: test', '2026-02-06 13:11:14'),
(49, 'researcher@gmail.com', 'researcher', '::1', 'CREATE', 'PROGRESS_REPORT', NULL, 'Submit Progress Report', 'Researcher submitted progress report for grant #27: progress', '2026-02-06 13:12:40'),
(50, 'researcher@gmail.com', 'researcher', '::1', 'CREATE', 'EXPENDITURE', NULL, 'Log Expenditure', 'Researcher logged expenditure RM100 for budget_item_id=25', '2026-02-06 13:13:11'),
(51, 'researcher@gmail.com', 'researcher', '::1', 'CREATE', 'REIMBURSEMENT_REQUEST', 18, 'Request Reimbursement', 'Researcher requested reimbursement for grant #27 (total: RM100.00)', '2026-02-06 13:13:19'),
(52, 'researcher@gmail.com', 'researcher', '::1', 'CREATE', 'EXTENSION_REQUEST', NULL, 'Request Extension', 'Researcher requested extension for report #1 to 2026-02-21', '2026-02-06 13:13:41'),
(53, 'researcher@gmail.com', 'researcher', '::1', 'CREATE', 'APPEAL_REQUEST', NULL, 'Appeal Proposal', 'Researcher appealed proposal #22', '2026-02-06 13:22:30'),
(54, 'researcher@gmail.com', 'researcher', '::1', 'CREATE', 'EXPENDITURE', NULL, 'Log Expenditure', 'Researcher logged expenditure RM100 for budget_item_id=26', '2026-02-06 13:41:43'),
(55, 'researcher@gmail.com', 'researcher', '::1', 'CREATE', 'REIMBURSEMENT_REQUEST', 19, 'Request Reimbursement', 'Researcher requested reimbursement for grant #27 (total: RM100.00)', '2026-02-06 13:50:52'),
(56, 'researcher@gmail.com', 'researcher', '::1', 'CREATE', 'PROPOSAL', 28, 'Submit Proposal', 'Researcher submitted proposal: test1', '2026-02-06 13:58:16'),
(57, 'researcher@gmail.com', 'researcher', '::1', 'DELETE', 'PROPOSAL', 28, 'Delete Proposal', 'Researcher deleted proposal #28', '2026-02-06 13:58:23'),
(58, 'researcher@gmail.com', 'researcher', '::1', 'CREATE', 'PROPOSAL', 29, 'Submit Proposal', 'Researcher submitted proposal: test', '2026-02-06 13:58:49'),
(59, '1@mail.com', 'reviewer', '::1', 'VIEW_MISCONDUCT_CASES', 'MISCONDUCT_REPORT', NULL, 'View Misconduct Cases', 'Reviewer viewed misconduct cases list', '2026-02-06 13:59:50'),
(60, '1@mail.com', 'reviewer', '::1', 'VIEW_MISCONDUCT_CASES', 'MISCONDUCT_REPORT', NULL, 'View Misconduct Cases', 'Reviewer viewed misconduct cases list', '2026-02-06 13:59:53'),
(61, '4@mail.com', 'admin', '::1', 'ASSIGN_REVIEWER', 'PROPOSAL', 29, 'Assign Proposal', 'Assigned proposal #29 to reviewer_id=1', '2026-02-06 14:00:18'),
(62, 'researcher@gmail.com', 'researcher', '::1', 'CREATE', 'PROGRESS_REPORT', NULL, 'Submit Progress Report', 'Researcher submitted progress report for grant #29: progress', '2026-02-06 14:04:54'),
(63, 'researcher@gmail.com', 'researcher', '::1', 'CREATE', 'PROPOSAL', 30, 'Submit Proposal', 'Researcher submitted proposal: test2', '2026-02-06 14:06:24'),
(64, '4@mail.com', 'admin', '::1', 'ASSIGN_REVIEWER', 'PROPOSAL', 30, 'Assign Proposal', 'Assigned proposal #30 to reviewer_id=1', '2026-02-06 14:06:50'),
(65, 'researcher@gmail.com', 'researcher', '::1', 'UPDATE', 'PROPOSAL', 30, 'Amend Proposal', 'Researcher resubmitted amendments (new version: v2.0)', '2026-02-06 14:07:38'),
(66, 'researcher@gmail.com', 'researcher', '::1', 'CREATE', 'PROPOSAL', 31, 'Submit Proposal', 'Researcher submitted proposal: testing', '2026-02-06 16:36:26'),
(67, 'researcher@gmail.com', 'researcher', '::1', 'CREATE', 'EXPENDITURE', NULL, 'Log Expenditure', 'Researcher logged expenditure RM100 for budget_item_id=45', '2026-02-06 16:37:50'),
(68, 'researcher@gmail.com', 'researcher', '::1', 'CREATE', 'REIMBURSEMENT_REQUEST', 20, 'Request Reimbursement', 'Researcher requested reimbursement for grant #31 (total: RM100.00)', '2026-02-06 16:37:57'),
(69, 'researcher@gmail.com', 'researcher', '::1', 'CREATE', 'EXTENSION_REQUEST', NULL, 'Request Extension', 'Researcher requested extension for milestone #13 to 2026-02-16', '2026-02-06 16:47:06'),
(70, 'researcher@gmail.com', 'researcher', '::1', 'CREATE', 'PROPOSAL', 32, 'Submit Proposal', 'Researcher submitted proposal: 1', '2026-02-06 16:47:52'),
(71, 'researcher@gmail.com', 'researcher', '::1', 'CREATE', 'EXTENSION_REQUEST', NULL, 'Request Extension', 'Researcher requested extension for milestone #14 to 2026-02-14', '2026-02-06 16:48:49'),
(72, 'researcher@gmail.com', 'researcher', '::1', 'CREATE', 'PROGRESS_REPORT', NULL, 'Submit Progress Report', 'Researcher submitted progress report for grant #32: 1', '2026-02-06 16:49:44'),
(73, 'researcher@gmail.com', 'researcher', '::1', 'CREATE', 'EXPENDITURE', NULL, 'Log Expenditure', 'Researcher logged expenditure RM1 for budget_item_id=51', '2026-02-06 16:50:02'),
(74, 'researcher@gmail.com', 'researcher', '::1', 'CREATE', 'REIMBURSEMENT_REQUEST', 21, 'Request Reimbursement', 'Researcher requested reimbursement for grant #32 (total: RM1.00)', '2026-02-06 16:50:09'),
(75, 'researcher@gmail.com', 'researcher', '::1', 'CREATE', 'PROPOSAL', 33, 'Submit Proposal', 'Researcher submitted proposal: 1', '2026-02-06 17:13:40'),
(76, 'researcher@gmail.com', 'researcher', '::1', 'DELETE', 'PROPOSAL', 33, 'Delete Proposal', 'Researcher deleted proposal #33', '2026-02-06 17:13:44'),
(77, 'researcher@gmail.com', 'researcher', '::1', 'CREATE', 'PROPOSAL', 34, 'Submit Proposal', 'Researcher submitted proposal: 2', '2026-02-06 17:14:06'),
(78, 'researcher@gmail.com', 'researcher', '::1', 'CREATE', 'EXTENSION_REQUEST', NULL, 'Request Extension', 'Researcher requested extension for milestone #16 to 2026-02-14', '2026-02-06 17:14:49'),
(79, 'researcher@gmail.com', 'researcher', '::1', 'CREATE', 'PROGRESS_REPORT', NULL, 'Submit Progress Report', 'Researcher submitted progress report for grant #34: 2', '2026-02-06 17:15:03'),
(80, 'researcher@gmail.com', 'researcher', '::1', 'CREATE', 'EXPENDITURE', NULL, 'Log Expenditure', 'Researcher logged expenditure RM12 for budget_item_id=60', '2026-02-06 17:15:18'),
(81, 'researcher@gmail.com', 'researcher', '::1', 'CREATE', 'REIMBURSEMENT_REQUEST', 22, 'Request Reimbursement', 'Researcher requested reimbursement for grant #34 (total: RM12.00)', '2026-02-06 17:15:24'),
(82, 'researcher@gmail.com', 'researcher', '::1', 'CREATE', 'APPEAL_REQUEST', NULL, 'Appeal Proposal', 'Researcher appealed proposal #17', '2026-02-06 17:15:37'),
(83, 'researcher@gmail.com', 'researcher', '::1', 'CREATE', 'PROPOSAL', 35, 'Submit Proposal', 'Researcher submitted proposal: 3', '2026-02-06 17:20:46'),
(84, '4@mail.com', 'admin', '::1', 'ASSIGN_REVIEWER', 'PROPOSAL', 35, 'Assign Proposal', 'Assigned proposal #35 to reviewer_id=1', '2026-02-06 17:21:06'),
(85, 'researcher@gmail.com', 'researcher', '::1', 'CREATE', 'PROPOSAL', 36, 'Submit Proposal', 'Researcher submitted proposal: 1', '2026-02-06 17:31:42'),
(86, 'researcher@gmail.com', 'researcher', '::1', 'DELETE', 'PROPOSAL', 36, 'Delete Proposal', 'Researcher deleted proposal #36', '2026-02-06 17:31:46'),
(87, 'researcher@gmail.com', 'researcher', '::1', 'UPDATE', 'PROPOSAL', 35, 'Amend Proposal', 'Researcher resubmitted amendments (new version: v2.0)', '2026-02-06 17:31:52'),
(88, 'researcher@gmail.com', 'researcher', '::1', 'CREATE', 'EXTENSION_REQUEST', NULL, 'Request Extension', 'Researcher requested extension for milestone #13 to 2026-02-13', '2026-02-06 17:32:32'),
(89, 'researcher@gmail.com', 'researcher', '::1', 'CREATE', 'EXPENDITURE', NULL, 'Log Expenditure', 'Researcher logged expenditure RM100 for budget_item_id=60', '2026-02-06 17:32:47'),
(90, 'researcher@gmail.com', 'researcher', '::1', 'CREATE', 'REIMBURSEMENT_REQUEST', 23, 'Request Reimbursement', 'Researcher requested reimbursement for grant #34 (total: RM100.00)', '2026-02-06 17:32:52'),
(91, 'researcher@gmail.com', 'researcher', '::1', 'CREATE', 'PROGRESS_REPORT', NULL, 'Submit Progress Report', 'Researcher submitted progress report for grant #34: 1', '2026-02-06 17:33:11'),
(92, '2@mail.com', 'researcher', '::1', 'CREATE', 'PROPOSAL', 37, 'Submit Proposal', 'Researcher submitted proposal: Deadline extend', '2026-02-07 02:06:14'),
(93, '4@mail.com', 'admin', '::1', 'ASSIGN_REVIEWER', 'PROPOSAL', 37, 'Assign Proposal', 'Assigned proposal #37 to reviewer_id=1', '2026-02-07 02:06:35'),
(94, '2@mail.com', 'researcher', '::1', 'CREATE', 'EXTENSION_REQUEST', NULL, 'Request Extension', 'Researcher requested extension for milestone #19 to 2026-02-11', '2026-02-07 02:47:28'),
(95, '2@mail.com', 'researcher', '::1', 'CREATE', 'EXTENSION_REQUEST', NULL, 'Request Extension', 'Researcher requested extension for milestone #19 to 2026-02-11', '2026-02-07 02:48:15'),
(96, '2@mail.com', 'researcher', '::1', 'CREATE', 'PROGRESS_REPORT', NULL, 'Submit Progress Report', 'Researcher submitted progress report for grant #37: progress 1', '2026-02-07 02:49:15'),
(97, '2@mail.com', 'researcher', '::1', 'CREATE', 'PROPOSAL', 38, 'Submit Proposal', 'Researcher submitted proposal: check annotate', '2026-02-07 03:30:41'),
(98, '4@mail.com', 'admin', '::1', 'ASSIGN_REVIEWER', 'PROPOSAL', 38, 'Assign Proposal', 'Assigned proposal #38 to reviewer_id=1', '2026-02-07 03:31:52'),
(99, '2@mail.com', 'researcher', '::1', 'CREATE', 'APPEAL_REQUEST', NULL, 'Appeal Proposal', 'Researcher appealed proposal #6', '2026-02-07 04:04:46'),
(100, '2@mail.com', 'researcher', '::1', 'CREATE', 'PROPOSAL', 39, 'Submit Proposal', 'Researcher submitted proposal: Test Rubric', '2026-02-07 14:08:18'),
(101, '4@mail.com', 'admin', '::1', 'ASSIGN_REVIEWER', 'PROPOSAL', 39, 'Assign Proposal', 'Assigned proposal #39 to reviewer_id=1', '2026-02-07 14:08:31');

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
(1, 3, '2@mail.com', 'should go straight back to reviewer not admin', 'APPROVED', '2026-02-04 17:24:56'),
(2, 5, '2@mail.com', 'please reject this (by new reviewer)', 'REJECTED', '2026-02-04 17:47:10'),
(5, 22, 'researcher@gmail.com', 'veli good', 'REJECTED', '2026-02-06 05:22:30'),
(6, 17, 'researcher@gmail.com', '2', 'APPROVED', '2026-02-06 09:15:37'),
(7, 6, '2@mail.com', 'test annotate', 'APPROVED', '2026-02-06 20:04:46');

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
(25, 27, 'Equipment', 'Equipment budget', 200.00, 0.00, '2026-02-06 05:11:14'),
(26, 27, 'Materials', 'Materials budget', 300.00, 100.00, '2026-02-06 05:11:14'),
(27, 27, 'Travel', 'Travel budget', 200.00, 0.00, '2026-02-06 05:11:14'),
(28, 27, 'Personnel', 'Personnel budget', 300.00, 0.00, '2026-02-06 05:11:14'),
(29, 27, 'Other', 'Other budget', 200.00, 0.00, '2026-02-06 05:11:14'),
(35, 29, 'Equipment', 'Equipment budget', 100.00, 0.00, '2026-02-06 05:58:49'),
(36, 29, 'Materials', 'Materials budget', 100.00, 0.00, '2026-02-06 05:58:49'),
(37, 29, 'Travel', 'Travel budget', 100.00, 0.00, '2026-02-06 05:58:49'),
(38, 29, 'Personnel', 'Personnel budget', 100.00, 0.00, '2026-02-06 05:58:49'),
(39, 29, 'Other', 'Other budget', 100.00, 0.00, '2026-02-06 05:58:49'),
(40, 30, 'Equipment', 'Equipment budget', 100.00, 0.00, '2026-02-06 06:06:24'),
(41, 30, 'Materials', 'Materials budget', 100.00, 0.00, '2026-02-06 06:06:24'),
(42, 30, 'Travel', 'Travel budget', 100.00, 0.00, '2026-02-06 06:06:24'),
(43, 30, 'Personnel', 'Personnel budget', 100.00, 0.00, '2026-02-06 06:06:24'),
(44, 30, 'Other', 'Other budget', 100.00, 0.00, '2026-02-06 06:06:24'),
(45, 31, 'Equipment', 'Equipment budget', 500.03, 0.00, '2026-02-06 08:36:26'),
(46, 31, 'Materials', 'Materials budget', 100.00, 0.00, '2026-02-06 08:36:26'),
(47, 31, 'Travel', 'Travel budget', 100.00, 0.00, '2026-02-06 08:36:26'),
(48, 31, 'Personnel', 'Personnel budget', 100.00, 0.00, '2026-02-06 08:36:26'),
(49, 31, 'Other', 'Other budget', 100.00, 0.00, '2026-02-06 08:36:27'),
(50, 32, 'Equipment', 'Equipment budget', 100.00, 0.00, '2026-02-06 08:47:52'),
(51, 32, 'Materials', 'Materials budget', 100.00, 0.00, '2026-02-06 08:47:52'),
(52, 32, 'Travel', 'Travel budget', 100.00, 0.00, '2026-02-06 08:47:52'),
(53, 32, 'Personnel', 'Personnel budget', 100.00, 0.00, '2026-02-06 08:47:52'),
(54, 32, 'Other', 'Other budget', 100.00, 0.00, '2026-02-06 08:47:52'),
(60, 34, 'Equipment', 'Equipment budget', 100.00, 0.00, '2026-02-06 09:14:06'),
(61, 34, 'Materials', 'Materials budget', 100.00, 0.00, '2026-02-06 09:14:06'),
(62, 34, 'Travel', 'Travel budget', 100.00, 0.00, '2026-02-06 09:14:06'),
(63, 34, 'Personnel', 'Personnel budget', 100.00, 0.00, '2026-02-06 09:14:06'),
(64, 34, 'Other', 'Other budget', 100.00, 0.00, '2026-02-06 09:14:06'),
(65, 35, 'Equipment', 'Equipment budget', 100.00, 0.00, '2026-02-06 09:20:46'),
(66, 35, 'Materials', 'Materials budget', 100.00, 0.00, '2026-02-06 09:20:46'),
(67, 35, 'Travel', 'Travel budget', 100.00, 0.00, '2026-02-06 09:20:46'),
(68, 35, 'Personnel', 'Personnel budget', 100.00, 0.00, '2026-02-06 09:20:46'),
(69, 35, 'Other', 'Other budget', 100.00, 0.00, '2026-02-06 09:20:46'),
(75, 37, 'Equipment', 'Equipment budget', 500.00, 0.00, '2026-02-06 18:06:14'),
(76, 37, 'Materials', 'Materials budget', 500.00, 0.00, '2026-02-06 18:06:14'),
(77, 37, 'Travel', 'Travel budget', 100.00, 0.00, '2026-02-06 18:06:14'),
(78, 37, 'Personnel', 'Personnel budget', 400.00, 0.00, '2026-02-06 18:06:14'),
(79, 38, 'Equipment', 'Equipment budget', 300.00, 0.00, '2026-02-06 19:30:41'),
(80, 38, 'Personnel', 'Personnel budget', 200.00, 0.00, '2026-02-06 19:30:41'),
(81, 39, 'Equipment', 'Equipment budget', 600.00, 0.00, '2026-02-07 06:08:18'),
(82, 39, 'Materials', 'Materials budget', 500.00, 0.00, '2026-02-07 06:08:18'),
(83, 39, 'Travel', 'Travel budget', 800.00, 0.00, '2026-02-07 06:08:18'),
(84, 39, 'Personnel', 'Personnel budget', 500.00, 0.00, '2026-02-07 06:08:18');

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
(1, 'Engineering', 'ENG', 'Faculty of Engineering', 406650.00, 1000000.00, '2026-01-10 08:20:38'),
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
(7, 27, 'v1.0', 'uploads/prop_1770354674_researcher_gmail_com.pdf', 'researcher@gmail.com', '2026-02-06 05:11:14', NULL),
(9, 29, 'v1.0', 'uploads/prop_1770357529_researcher_gmail_com.pdf', 'researcher@gmail.com', '2026-02-06 05:58:49', NULL),
(10, 30, 'v1.0', 'uploads/prop_1770357984_researcher_gmail_com.pdf', 'researcher@gmail.com', '2026-02-06 06:06:24', NULL),
(11, 30, 'v2.0', 'uploads/prop_1770358058_researcher_gmail_com_v2.pdf', 'researcher@gmail.com', '2026-02-06 06:07:38', 'yes'),
(12, 31, 'v1.0', 'uploads/prop_1770366986_researcher_gmail_com.pdf', 'researcher@gmail.com', '2026-02-06 08:36:27', NULL),
(13, 32, 'v1.0', 'uploads/prop_1770367672_researcher_gmail_com.pdf', 'researcher@gmail.com', '2026-02-06 08:47:52', NULL),
(15, 34, 'v1.0', 'uploads/prop_1770369246_researcher_gmail_com.pdf', 'researcher@gmail.com', '2026-02-06 09:14:06', NULL),
(16, 35, 'v1.0', 'uploads/prop_1770369646_researcher_gmail_com.pdf', 'researcher@gmail.com', '2026-02-06 09:20:46', NULL),
(18, 35, 'v2.0', 'uploads/prop_1770370312_researcher_gmail_com_v2.pdf', 'researcher@gmail.com', '2026-02-06 09:31:52', '21'),
(19, 37, 'v1.0', 'uploads/prop_1770401174_2_mail_com.pdf', '2@mail.com', '2026-02-06 18:06:14', NULL),
(20, 38, 'v1.0', 'uploads/prop_1770406241_2_mail_com.pdf', '2@mail.com', '2026-02-06 19:30:41', NULL),
(21, 39, 'v1.0', 'uploads/prop_1770444498_2_mail_com.pdf', '2@mail.com', '2026-02-07 06:08:18', NULL);

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
  `receipt_path` varchar(500) NOT NULL COMMENT 'Path to receipt file - MANDATORY for all expenditures',
  `reimbursement_request_id` int(11) DEFAULT NULL,
  `status` enum('PENDING_REIMBURSEMENT','UNDER_REVIEW','APPROVED','REJECTED') DEFAULT 'PENDING_REIMBURSEMENT',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `expenditures`
--

INSERT INTO `expenditures` (`id`, `budget_item_id`, `amount`, `transaction_date`, `description`, `receipt_path`, `reimbursement_request_id`, `status`, `created_at`) VALUES
(8, 25, 100.00, '2026-02-07', NULL, 'uploads/receipts/receipt_1770354791_grant27.pdf', 18, 'UNDER_REVIEW', '2026-02-06 05:13:11'),
(9, 26, 100.00, '2026-02-06', NULL, 'uploads/receipts/receipt_1770356503_grant27.pdf', 19, 'APPROVED', '2026-02-06 05:41:43'),
(10, 45, 100.00, '2026-02-06', NULL, 'uploads/receipts/receipt_1770367070_grant31.pdf', 20, 'UNDER_REVIEW', '2026-02-06 08:37:50'),
(11, 51, 1.00, '2026-02-06', NULL, 'uploads/receipts/receipt_1770367802_grant32.pdf', 21, 'UNDER_REVIEW', '2026-02-06 08:50:02'),
(12, 60, 12.00, '2026-02-06', NULL, 'uploads/receipts/receipt_1770369318_grant34.pdf', 22, 'UNDER_REVIEW', '2026-02-06 09:15:18'),
(13, 60, 100.00, '2026-02-06', NULL, 'uploads/receipts/receipt_1770370367_grant34.pdf', 23, 'UNDER_REVIEW', '2026-02-06 09:32:47');

-- --------------------------------------------------------

--
-- Table structure for table `extension_requests`
--

CREATE TABLE `extension_requests` (
  `id` int(11) NOT NULL,
  `milestone_id` int(11) NOT NULL,
  `researcher_email` varchar(255) NOT NULL,
  `new_deadline` date NOT NULL,
  `justification` text NOT NULL,
  `status` enum('PENDING','APPROVED','REJECTED') DEFAULT 'PENDING',
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `extension_requests`
--

INSERT INTO `extension_requests` (`id`, `milestone_id`, `researcher_email`, `new_deadline`, `justification`, `status`, `requested_at`) VALUES
(12, 13, 'researcher@gmail.com', '2026-02-16', 'no', 'PENDING', '2026-02-06 08:47:06'),
(13, 14, 'researcher@gmail.com', '2026-02-14', '1', 'APPROVED', '2026-02-06 08:48:49'),
(14, 16, 'researcher@gmail.com', '2026-02-14', '2', 'REJECTED', '2026-02-06 09:14:49'),
(15, 13, 'researcher@gmail.com', '2026-02-13', '1', 'APPROVED', '2026-02-06 09:32:32'),
(16, 19, '2@mail.com', '2026-02-11', 'approve', 'APPROVED', '2026-02-06 18:47:28'),
(17, 19, '2@mail.com', '2026-02-11', 'approve', 'PENDING', '2026-02-06 18:48:15');

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
(2, 37, 'GRT-2026-00037', 1000.00, 0.00, 1000.00, NULL, NULL, 'Active', '2026-02-06 18:10:56');

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
(2, 2, 1000.00, 1000.00, NULL, 3, '2026-02-06', '2026-02-06 18:10:56');

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
(2, 37, 3, 'top', 1000.00, 1, NULL, '2026-02-06 18:10:56', '2026-02-06 18:10:56'),
(3, 38, 3, 'bottom', NULL, 0, NULL, '2026-02-06 20:02:26', '2026-02-06 20:02:26'),
(4, 39, 3, 'top', NULL, 0, NULL, '2026-02-07 06:46:52', '2026-02-07 06:55:12');

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
  `report_deadline` date DEFAULT NULL COMMENT 'Deadline for submitting progress report for this milestone',
  `completion_date` date DEFAULT NULL,
  `status` enum('PENDING','IN_PROGRESS','COMPLETED','DELAYED') DEFAULT 'PENDING',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `milestones`
--

INSERT INTO `milestones` (`id`, `grant_id`, `title`, `description`, `report_deadline`, `completion_date`, `status`, `created_at`) VALUES
(9, 27, 'testt', 'test', '2026-02-07', '2026-02-06', 'COMPLETED', '2026-02-06 05:11:14'),
(11, 29, 'test', 'test', '2026-02-14', '2026-02-06', 'COMPLETED', '2026-02-06 05:58:49'),
(12, 30, 'test', 'test', '2026-02-07', NULL, 'PENDING', '2026-02-06 06:06:24'),
(13, 31, 'test', 'test', '2026-02-13', NULL, 'PENDING', '2026-02-06 08:36:27'),
(14, 32, '1', '1', '2026-02-14', '2026-02-06', 'COMPLETED', '2026-02-06 08:47:52'),
(16, 34, '2', '2', '2026-02-08', '2026-02-06', 'COMPLETED', '2026-02-06 09:14:06'),
(17, 35, '3', '3', '2026-02-01', NULL, 'PENDING', '2026-02-06 09:20:46'),
(19, 37, 'Step1', 'step1 des', '2026-02-11', NULL, 'PENDING', '2026-02-06 18:06:14'),
(20, 37, 'Step2', 'step2 des', '2026-02-19', NULL, 'PENDING', '2026-02-06 18:06:14'),
(21, 37, 'Final Step', 'final step des', '2026-02-28', NULL, 'PENDING', '2026-02-06 18:06:14'),
(22, 38, 'step1', 'step1', '2026-02-26', NULL, 'PENDING', '2026-02-06 19:30:41'),
(23, 39, 'step1', 'step 1 description', '2026-02-12', NULL, 'PENDING', '2026-02-07 06:08:18');

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
(97, '4@mail.com', 'New Proposal Submitted: \'test\' by researcher@gmail.com', 0, '2026-02-06 04:58:57', 'alert'),
(98, '4@mail.com', 'New Proposal Submitted: \'test\' by researcher@gmail.com', 0, '2026-02-06 05:11:14', 'alert'),
(99, '3@mail.com', 'New Progress Report submitted by researcher@gmail.com for Grant #27: \'progress\'', 0, '2026-02-06 05:12:40', 'alert'),
(100, '3@mail.com', 'Reimbursement Request: researcher@gmail.com requests RM100.00 for Grant #27', 0, '2026-02-06 05:13:19', 'alert'),
(101, '3@mail.com', 'Deadline Extension Request: researcher@gmail.com requests extension for Report #1 to 2026-02-21. Reason: dunno', 0, '2026-02-06 05:13:41', 'alert'),
(102, '3@mail.com', 'Appeal Request: researcher@gmail.com has contested rejection of Proposal #22. Please review and potentially reassign to a new reviewer.', 0, '2026-02-06 05:22:30', 'alert'),
(103, '3@mail.com', 'Reimbursement Request: researcher@gmail.com requests RM100.00 for Grant #27', 0, '2026-02-06 05:50:52', 'alert'),
(104, 'researcher@gmail.com', 'Reimbursement APPROVED: RM100.00 released.', 0, '2026-02-06 05:55:25', 'success'),
(105, '4@mail.com', 'New Proposal Submitted: \'test1\' by researcher@gmail.com', 0, '2026-02-06 05:58:16', 'alert'),
(106, '4@mail.com', 'New Proposal Submitted: \'test\' by researcher@gmail.com', 0, '2026-02-06 05:58:49', 'alert'),
(107, 'researcher@gmail.com', 'Update on \'test\': Your proposal status is now recommend.', 0, '2026-02-06 06:00:52', 'info'),
(108, 'researcher@gmail.com', 'Appeal Update: The HOD accepted your appeal for \'testproposal5\'. The proposal will be reassigned to a new reviewer.', 0, '2026-02-06 06:02:29', 'info'),
(109, 'researcher@gmail.com', 'Appeal Update: The HOD accepted your appeal for \'testproposal5\'. The proposal will be reassigned to a new reviewer.', 0, '2026-02-06 06:02:32', 'info'),
(110, '3@mail.com', 'New Progress Report submitted by researcher@gmail.com for Grant #29: \'progress\'', 0, '2026-02-06 06:04:54', 'alert'),
(111, '4@mail.com', 'New Proposal Submitted: \'test2\' by researcher@gmail.com', 0, '2026-02-06 06:06:24', 'alert'),
(112, 'researcher@gmail.com', 'Action Required: The reviewer requested amendments on \'test2\'. Please check the dashboard.', 0, '2026-02-06 06:07:10', 'info'),
(113, '1@mail.com', 'Action Required: Researcher has submitted amendments for Proposal #30. It is back in your pending list.', 0, '2026-02-06 06:07:38', 'info'),
(114, 'researcher@gmail.com', 'Update on \'test2\': Your proposal status is now rejected.', 0, '2026-02-06 06:08:04', 'info'),
(115, '4@mail.com', 'New Proposal Submitted: \'testing\' by researcher@gmail.com', 0, '2026-02-06 08:36:27', 'alert'),
(116, '3@mail.com', 'Reimbursement Request: researcher@gmail.com requests RM100.00 for Grant #31', 0, '2026-02-06 08:37:57', 'alert'),
(117, '3@mail.com', 'Deadline Extension Request: researcher@gmail.com requests extension for Milestone #13 to 2026-02-16. Reason: no', 0, '2026-02-06 08:47:06', 'alert'),
(118, '4@mail.com', 'New Proposal Submitted: \'1\' by researcher@gmail.com', 0, '2026-02-06 08:47:52', 'alert'),
(119, '3@mail.com', 'Deadline Extension Request: researcher@gmail.com requests extension for Milestone #14 to 2026-02-14. Reason: 1', 0, '2026-02-06 08:48:49', 'alert'),
(120, '3@mail.com', 'New Progress Report submitted by researcher@gmail.com for Grant #32: \'1\'', 0, '2026-02-06 08:49:44', 'alert'),
(121, '3@mail.com', 'Reimbursement Request: researcher@gmail.com requests RM1.00 for Grant #32', 0, '2026-02-06 08:50:09', 'alert'),
(122, '4@mail.com', 'New Proposal Submitted: \'1\' by researcher@gmail.com', 0, '2026-02-06 09:13:40', 'alert'),
(123, '4@mail.com', 'New Proposal Submitted: \'2\' by researcher@gmail.com', 0, '2026-02-06 09:14:06', 'alert'),
(124, '3@mail.com', 'Deadline Extension Request: researcher@gmail.com requests extension for Milestone #16 to 2026-02-14. Reason: 2', 0, '2026-02-06 09:14:49', 'alert'),
(125, '3@mail.com', 'New Progress Report submitted by researcher@gmail.com for Grant #34: \'2\'', 0, '2026-02-06 09:15:03', 'alert'),
(126, '3@mail.com', 'Reimbursement Request: researcher@gmail.com requests RM12.00 for Grant #34', 0, '2026-02-06 09:15:24', 'alert'),
(127, '3@mail.com', 'Appeal Request: researcher@gmail.com has contested rejection of Proposal #17. Please review and potentially reassign to a new reviewer.', 0, '2026-02-06 09:15:37', 'alert'),
(128, '4@mail.com', 'New Proposal Submitted: \'3\' by researcher@gmail.com', 0, '2026-02-06 09:20:46', 'alert'),
(129, 'researcher@gmail.com', 'Action Required: The reviewer requested amendments on \'3\'. Please check the dashboard.', 0, '2026-02-06 09:21:25', 'info'),
(130, '4@mail.com', 'New Proposal Submitted: \'1\' by researcher@gmail.com', 0, '2026-02-06 09:31:42', 'alert'),
(131, '1@mail.com', 'Action Required: Researcher has submitted amendments for Proposal #35. It is back in your pending list.', 0, '2026-02-06 09:31:52', 'info'),
(132, '3@mail.com', 'Deadline Extension Request: researcher@gmail.com requests extension for Milestone #13 to 2026-02-13. Reason: 1', 0, '2026-02-06 09:32:32', 'alert'),
(133, '3@mail.com', 'Reimbursement Request: researcher@gmail.com requests RM100.00 for Grant #34', 0, '2026-02-06 09:32:52', 'alert'),
(134, '3@mail.com', 'New Progress Report submitted by researcher@gmail.com for Grant #34: \'1\'', 0, '2026-02-06 09:33:11', 'alert'),
(135, '4@mail.com', 'New Proposal Submitted: \'Deadline extend\' by 2@mail.com', 0, '2026-02-06 18:06:14', 'alert'),
(136, '2@mail.com', 'Update on \'Deadline extend\': Your proposal status is now recommend.', 0, '2026-02-06 18:07:03', 'info'),
(137, '2@mail.com', 'Final Decision: Your proposal \'Deadline extend\' has been APPROVED by the Head of Department.', 0, '2026-02-06 18:10:56', 'success'),
(138, 'researcher@gmail.com', 'Extension request APPROVED for milestone \'test\'. New deadline: 2026-02-13', 0, '2026-02-06 18:27:22', 'success'),
(139, 'researcher@gmail.com', 'Extension request APPROVED for milestone \'test\'. New deadline: 2026-02-13', 0, '2026-02-06 18:28:10', 'success'),
(140, 'researcher@gmail.com', 'Extension request APPROVED for milestone \'1\'. New deadline: 2026-02-14', 0, '2026-02-06 18:38:39', 'success'),
(141, 'researcher@gmail.com', 'Extension request REJECTED for milestone \'2\'. Remarks: no extension', 0, '2026-02-06 18:39:16', 'warning'),
(142, 'researcher@gmail.com', 'Your progress report \'1\' for \'2\' has been approved by HOD.', 0, '2026-02-06 18:40:23', 'success'),
(143, 'researcher@gmail.com', 'Your progress report \'2\' for \'2\' has been approved by HOD.', 0, '2026-02-06 18:40:34', 'success'),
(144, 'researcher@gmail.com', 'Your progress report \'progress\' for \'test\' has been approved by HOD.', 0, '2026-02-06 18:41:02', 'success'),
(145, '3@mail.com', 'Deadline Extension Request: 2@mail.com requests extension for Milestone #19 to 2026-02-11. Reason: approve', 0, '2026-02-06 18:47:28', 'alert'),
(146, '2@mail.com', 'Extension request APPROVED for milestone \'Step1\'. New deadline: 2026-02-11', 0, '2026-02-06 18:48:03', 'success'),
(147, '3@mail.com', 'Deadline Extension Request: 2@mail.com requests extension for Milestone #19 to 2026-02-11. Reason: approve', 0, '2026-02-06 18:48:15', 'alert'),
(148, '3@mail.com', 'New Progress Report submitted by 2@mail.com for Grant #37: \'progress 1\'', 0, '2026-02-06 18:49:15', 'alert'),
(149, '2@mail.com', 'Extension request APPROVED for milestone \'Step1\'. New deadline: 2026-02-11', 0, '2026-02-06 18:50:50', 'success'),
(150, '2@mail.com', 'Extension request APPROVED for milestone \'Step1\'. New deadline: 2026-02-11', 0, '2026-02-06 18:51:29', 'success'),
(151, '2@mail.com', 'Extension request APPROVED for milestone \'Step1\'. New deadline: 2026-02-11', 0, '2026-02-06 18:52:06', 'success'),
(152, 'researcher@gmail.com', 'Your progress report \'1\' for \'2\' has been approved by HOD.', 0, '2026-02-06 18:52:38', 'success'),
(153, 'researcher@gmail.com', 'Congratulations! Your research project \'2\' has been marked as COMPLETED by the HOD.', 0, '2026-02-06 18:52:39', 'success'),
(154, '2@mail.com', 'Extension request APPROVED for milestone \'Step1\'. New deadline: 2026-02-11', 0, '2026-02-06 18:55:15', 'success'),
(155, '2@mail.com', 'Extension request APPROVED for milestone \'Step1\'. New deadline: 2026-02-11', 0, '2026-02-06 19:15:01', 'success'),
(156, '2@mail.com', 'Extension request APPROVED for milestone \'Step1\'. New deadline: 2026-02-11', 0, '2026-02-06 19:15:32', 'success'),
(157, '4@mail.com', 'New Proposal Submitted: \'check annotate\' by 2@mail.com', 0, '2026-02-06 19:30:41', 'alert'),
(158, '2@mail.com', 'Update on \'check annotate\': Your proposal status is now recommend.', 0, '2026-02-06 19:32:37', 'info'),
(159, '2@mail.com', 'Final Decision: Your proposal \'check annotate\' has been REJECTED by the Head of Department.', 0, '2026-02-06 20:02:26', 'warning'),
(160, '2@mail.com', 'Update on \'Rejected HOD\': Your proposal status is now rejected.', 0, '2026-02-06 20:04:08', 'info'),
(161, '3@mail.com', 'Appeal Request: 2@mail.com has contested rejection of Proposal #6. Please review and potentially reassign to a new reviewer.', 0, '2026-02-06 20:04:46', 'alert'),
(162, '4@mail.com', 'New Proposal Submitted: \'Test Rubric\' by 2@mail.com', 0, '2026-02-07 06:08:18', 'alert'),
(163, '2@mail.com', 'Update on \'Test Rubric\': Your proposal status is now recommend.', 0, '2026-02-07 06:08:57', 'info'),
(164, 'researcher@gmail.com', 'Appeal Update: The HOD accepted your appeal for \'testproposal1\'. The proposal will be reassigned to a new reviewer.', 0, '2026-02-07 07:17:23', 'info'),
(165, '2@mail.com', 'Appeal Update: The HOD accepted your appeal for \'Rejected HOD\'. The proposal will be reassigned to a new reviewer.', 0, '2026-02-07 07:17:51', 'info'),
(166, '2@mail.com', 'Appeal Update: The HOD upheld the original decision for \'Rejected HOD\'.', 0, '2026-02-07 07:22:51', 'warning'),
(167, '2@mail.com', 'Appeal Update: The HOD accepted your appeal for \'Recommended\'. The proposal will be reassigned to a new reviewer.', 0, '2026-02-07 07:25:58', 'info'),
(168, '2@mail.com', 'Appeal Update: The HOD accepted your appeal for \'Recommended\'. The proposal will be reassigned to a new reviewer.', 0, '2026-02-07 07:29:30', 'info'),
(169, 'researcher@gmail.com', 'Appeal Update: The HOD upheld the original decision for \'testproposal5\'.', 0, '2026-02-07 07:30:16', 'warning');

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
  `status` enum('PENDING_REVIEW','APPROVED','FOLLOW_UP_REQUIRED') DEFAULT 'PENDING_REVIEW',
  `submission_date` date DEFAULT curdate(),
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `hod_remarks` text DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `reviewed_by` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `progress_reports`
--

INSERT INTO `progress_reports` (`id`, `proposal_id`, `researcher_email`, `title`, `achievements`, `challenges`, `file_path`, `deadline`, `status`, `submission_date`, `submitted_at`, `hod_remarks`, `reviewed_at`, `reviewed_by`) VALUES
(1, 27, 'researcher@gmail.com', 'progress', 'progress', 'no', 'uploads/reports/rep_1770354760_27.pdf', '2026-02-07', 'PENDING_REVIEW', '2026-02-06', '2026-02-06 05:12:40', NULL, NULL, NULL),
(2, 29, 'researcher@gmail.com', 'progress', 'idk', 'idk', 'uploads/reports/rep_1770357894_29.pdf', '2026-02-07', 'PENDING_REVIEW', '2026-02-06', '2026-02-06 06:04:54', '', '2026-02-06 18:41:02', '3@mail.com'),
(3, 32, 'researcher@gmail.com', '1', '1', '1', 'uploads/reports/rep_1770367784_32.pdf', NULL, 'PENDING_REVIEW', '2026-02-06', '2026-02-06 08:49:44', NULL, NULL, NULL),
(4, 34, 'researcher@gmail.com', '2', '2', '2', 'uploads/reports/rep_1770369303_34.pdf', NULL, 'APPROVED', '2026-02-06', '2026-02-06 09:15:03', '', '2026-02-06 18:40:34', '3@mail.com'),
(5, 34, 'researcher@gmail.com', '1', '1', '1', 'uploads/reports/rep_1770370391_34.pdf', NULL, 'APPROVED', '2026-02-06', '2026-02-06 09:33:11', '', '2026-02-06 18:52:38', '3@mail.com'),
(6, 37, '2@mail.com', 'progress 1', 'step1', 'hard', 'uploads/reports/rep_1770403755_37.pdf', NULL, 'PENDING_REVIEW', '2026-02-07', '2026-02-06 18:49:15', NULL, NULL, NULL);

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
  `duration_months` int(11) DEFAULT 12,
  `health_status` enum('ON_TRACK','AT_RISK','DELAYED','COMPLETED','ARCHIVED') DEFAULT 'ON_TRACK',
  `health_notes` longtext DEFAULT NULL,
  `health_updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `proposals`
--

INSERT INTO `proposals` (`id`, `title`, `description`, `researcher_email`, `file_path`, `reviewer_email`, `status`, `created_at`, `approved_at`, `resubmitted_at`, `priority`, `department_id`, `reviewer_feedback`, `amendment_notes`, `budget_requested`, `approved_budget`, `amount_spent`, `duration_months`, `health_status`, `health_notes`, `health_updated_at`) VALUES
(1, 'Hello World', NULL, '2@mail.com', 'uploads/prop_1766775766_2_mail_com.pdf', NULL, 'APPEAL_REJECTED', '2025-12-26 19:02:46', NULL, NULL, 'Normal', NULL, NULL, NULL, 0.00, 0.00, 0.00, 12, 'ON_TRACK', NULL, '2026-02-06 04:12:45'),
(2, 'Approval', NULL, '2@mail.com', 'uploads/prop_1766776144_2_mail_com.pdf', NULL, 'APPROVED', '2025-12-26 19:09:04', NULL, NULL, 'Normal', NULL, NULL, NULL, 0.00, 0.00, 0.00, 12, 'ON_TRACK', NULL, '2026-02-06 04:12:45'),
(3, 'Recommended', NULL, '2@mail.com', 'uploads/prop_1766776494_2_mail_com.pdf', NULL, 'PENDING_REASSIGNMENT', '2025-12-26 19:14:54', NULL, NULL, 'High', NULL, NULL, NULL, 0.00, 0.00, 0.00, 12, 'ON_TRACK', NULL, '2026-02-07 07:25:58'),
(5, 'Rejected HOD', NULL, '2@mail.com', 'uploads/prop_1766777657_2_mail_com.pdf', NULL, 'APPEAL_REJECTED', '2025-12-26 19:34:17', NULL, NULL, 'Normal', NULL, NULL, NULL, 0.00, 0.00, 0.00, 12, 'ON_TRACK', NULL, '2026-02-07 07:22:51'),
(6, 'Rejected HOD', NULL, '2@mail.com', 'uploads/prop_1766777728_2_mail_com.pdf', NULL, 'PENDING_REASSIGNMENT', '2025-12-26 19:35:28', NULL, NULL, 'High', NULL, NULL, NULL, 0.00, 0.00, 0.00, 12, 'ON_TRACK', NULL, '2026-02-07 07:17:51'),
(7, 'new', NULL, '2@mail.com', 'uploads/prop_1767024370_2_mail_com.pdf', NULL, 'REQUIRES_AMENDMENT', '2025-12-29 16:06:10', NULL, NULL, 'Normal', NULL, 'no', NULL, 0.00, 0.00, 0.00, 12, 'ON_TRACK', NULL, '2026-02-06 04:12:45'),
(17, 'testproposal1', 'idk', 'researcher@gmail.com', 'uploads/prop_1767881876_researcher_gmail_com.pdf', NULL, 'PENDING_REASSIGNMENT', '2026-01-08 14:17:56', NULL, NULL, 'High', NULL, NULL, NULL, 2999.99, 0.00, 0.00, 12, 'ON_TRACK', NULL, '2026-02-07 07:17:23'),
(18, 'testproposal2', 'idk', 'researcher@gmail.com', 'uploads/amend_1767882089_18.pdf', NULL, 'RESUBMITTED', '2026-01-08 14:18:18', NULL, '2026-01-08 22:21:29', 'Normal', NULL, NULL, 'amend', 1500.00, 0.00, 0.00, 12, 'ON_TRACK', NULL, '2026-02-06 04:12:45'),
(20, 'testproposal4', 'idk', 'researcher@gmail.com', 'uploads/prop_1767881947_researcher_gmail_com.pdf', NULL, 'APPROVED', '2026-01-08 14:19:07', NULL, NULL, 'Normal', NULL, NULL, NULL, 6000.00, 0.00, 0.00, 12, 'ON_TRACK', NULL, '2026-02-06 04:12:45'),
(21, 'test proposal', 'idk', 'researcher@gmail.com', 'uploads/prop_1768391550_researcher_gmail_com.pdf', NULL, 'SUBMITTED', '2026-01-14 11:52:30', NULL, NULL, 'Normal', NULL, NULL, NULL, 1420.00, 0.00, 0.00, 12, 'ON_TRACK', NULL, '2026-02-06 04:12:45'),
(22, 'testproposal5', 'idk', 'researcher@gmail.com', 'uploads/prop_1768391685_researcher_gmail_com.pdf', NULL, 'APPEAL_REJECTED', '2026-01-14 11:54:45', NULL, NULL, 'High', NULL, NULL, NULL, 4999.00, 0.00, 0.00, 6, 'ON_TRACK', NULL, '2026-02-07 07:30:16'),
(23, 'test ', 'idk', 'researcher@gmail.com', 'uploads/prop_1768391951_researcher_gmail_com.pdf', NULL, 'APPROVED', '2026-01-14 11:59:11', NULL, NULL, 'Normal', NULL, NULL, NULL, 0.00, 0.00, 0.00, 14, 'ON_TRACK', NULL, '2026-02-06 04:12:45'),
(24, 'test123', 'idk', 'researcher@gmail.com', 'uploads/prop_1769004258_researcher_gmail_com.pdf', NULL, 'APPROVED', '2026-01-21 14:04:18', NULL, NULL, 'Normal', NULL, NULL, NULL, 2600.00, 0.00, 0.00, 12, 'ON_TRACK', NULL, '2026-02-06 04:12:45'),
(25, 'test1234', 'idk', 'researcher@gmail.com', 'uploads/prop_1769063921_researcher_gmail_com.pdf', NULL, 'APPROVED', '2026-01-22 06:38:41', NULL, NULL, 'Normal', NULL, NULL, NULL, 800.00, 0.00, 0.00, 16, 'ON_TRACK', NULL, '2026-02-06 04:12:45'),
(27, 'test', 'test', 'researcher@gmail.com', 'uploads/prop_1770354674_researcher_gmail_com.pdf', NULL, 'APPROVED', '2026-02-06 05:11:14', NULL, NULL, 'Normal', NULL, NULL, NULL, 1200.00, 0.00, 100.00, 16, 'ON_TRACK', NULL, '2026-02-06 05:55:25'),
(29, 'test', 'test', 'researcher@gmail.com', 'uploads/prop_1770357529_researcher_gmail_com.pdf', NULL, 'APPROVED', '2026-02-06 05:58:49', NULL, NULL, 'High', NULL, NULL, NULL, 500.00, 0.00, 0.00, 14, 'ON_TRACK', NULL, '2026-02-06 06:04:05'),
(30, 'test2', 'test', 'researcher@gmail.com', 'uploads/prop_1770358058_researcher_gmail_com_v2.pdf', NULL, 'REJECTED', '2026-02-06 06:06:24', NULL, '2026-02-06 14:07:38', 'Normal', NULL, 'amend', 'yes', 500.00, 0.00, 0.00, 14, 'ON_TRACK', NULL, '2026-02-06 06:08:04'),
(31, 'testing', 'test', 'researcher@gmail.com', 'uploads/prop_1770366986_researcher_gmail_com.pdf', NULL, 'APPROVED', '2026-02-06 08:36:26', NULL, NULL, 'Normal', NULL, NULL, NULL, 900.03, 0.00, 0.00, 12, 'ON_TRACK', NULL, '2026-02-06 08:37:19'),
(32, '1', '1', 'researcher@gmail.com', 'uploads/prop_1770367672_researcher_gmail_com.pdf', NULL, 'APPROVED', '2026-02-06 08:47:52', NULL, NULL, 'Normal', NULL, NULL, NULL, 500.00, 0.00, 0.00, 12, 'ON_TRACK', NULL, '2026-02-06 08:48:17'),
(34, '2', '2', 'researcher@gmail.com', 'uploads/prop_1770369246_researcher_gmail_com.pdf', NULL, 'APPROVED', '2026-02-06 09:14:06', NULL, NULL, 'Normal', NULL, NULL, NULL, 500.00, 0.00, 0.00, 12, 'ARCHIVED', NULL, '2026-02-06 19:15:32'),
(35, '3', '3', 'researcher@gmail.com', 'uploads/prop_1770370312_researcher_gmail_com_v2.pdf', NULL, 'RESUBMITTED', '2026-02-06 09:20:46', NULL, '2026-02-06 17:31:52', 'Normal', NULL, '1', '21', 500.00, 0.00, 0.00, 33, 'ON_TRACK', NULL, '2026-02-06 09:31:52'),
(37, 'Deadline extend', 'test deadline extend', '2@mail.com', 'uploads/prop_1770401174_2_mail_com.pdf', NULL, 'APPROVED', '2026-02-06 18:06:14', NULL, NULL, 'Normal', NULL, NULL, NULL, 1500.00, 1000.00, 0.00, 9, 'ON_TRACK', NULL, '2026-02-06 18:10:56'),
(38, 'check annotate', 'check annotate', '2@mail.com', 'uploads/prop_1770406241_2_mail_com.pdf', NULL, 'REJECTED', '2026-02-06 19:30:41', NULL, NULL, 'Normal', NULL, NULL, NULL, 500.00, 0.00, 0.00, 13, 'ON_TRACK', NULL, '2026-02-06 20:02:26'),
(39, 'Test Rubric', 'test rubric description', '2@mail.com', 'uploads/prop_1770444498_2_mail_com.pdf', NULL, '', '2026-02-07 06:08:18', NULL, NULL, 'Normal', NULL, NULL, NULL, 2400.00, 240.00, 0.00, 8, 'ON_TRACK', NULL, '2026-02-07 06:53:52');

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
  `total_score` decimal(6,1) DEFAULT 0.0,
  `hod_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_evaluated` tinyint(1) DEFAULT 0,
  `weight_outcome` decimal(3,1) DEFAULT 1.0 COMMENT 'Outcome weightage used during scoring',
  `weight_impact` decimal(3,1) DEFAULT 1.0 COMMENT 'Impact weightage used during scoring',
  `weight_alignment` decimal(3,1) DEFAULT 1.0 COMMENT 'Alignment weightage used during scoring',
  `weight_funding` decimal(3,1) DEFAULT 1.0 COMMENT 'Funding weightage used during scoring'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `proposal_rubric`
--

INSERT INTO `proposal_rubric` (`rubric_id`, `proposal_id`, `hod_id`, `outcome_score`, `impact_score`, `alignment_score`, `funding_score`, `total_score`, `hod_notes`, `created_at`, `updated_at`, `is_evaluated`, `weight_outcome`, `weight_impact`, `weight_alignment`, `weight_funding`) VALUES
(1, 29, 3, 5, 5, 5, 5, 20.0, '', '2026-02-06 06:01:15', '2026-02-06 06:01:15', 1, 1.0, 1.0, 1.0, 1.0),
(2, 37, 3, 5, 5, 5, 4, 19.0, '', '2026-02-06 18:07:51', '2026-02-06 18:07:51', 1, 1.0, 1.0, 1.0, 1.0),
(3, 38, 3, 3, 3, 3, 3, 12.0, '', '2026-02-06 19:32:53', '2026-02-06 19:32:53', 1, 1.0, 1.0, 1.0, 1.0),
(4, 39, 3, 5, 1, 1, 3, 17.0, 'rejected', '2026-02-07 06:09:47', '2026-02-07 06:57:51', 1, 2.0, 2.5, 1.5, 1.0);

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
(17, 25, 'researcher@gmail.com', 50.00, 'yay', 'PENDING', NULL, '2026-01-22 12:53:34', NULL),
(18, 27, 'researcher@gmail.com', 100.00, 'claim', 'PENDING', NULL, '2026-02-06 05:13:19', NULL),
(19, 27, 'researcher@gmail.com', 100.00, 'yes', 'APPROVED', '', '2026-02-06 05:50:52', '2026-02-06 13:55:25'),
(20, 31, 'researcher@gmail.com', 100.00, 'ok', 'PENDING', NULL, '2026-02-06 08:37:57', NULL),
(21, 32, 'researcher@gmail.com', 1.00, '1', 'PENDING', NULL, '2026-02-06 08:50:09', NULL),
(22, 34, 'researcher@gmail.com', 12.00, '2', 'PENDING', NULL, '2026-02-06 09:15:24', NULL),
(23, 34, 'researcher@gmail.com', 100.00, '1', 'PENDING', NULL, '2026-02-06 09:32:52', NULL);

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
(2, 1, NULL, 1, 'Completed', '2025-12-27', 'Proposal', 'so bad', 'uploads/reviews/rev_1766776110_prop_1766775766_2_mail_com.pdf', 'REJECT', '2025-12-27 03:08:30'),
(3, 1, NULL, 2, 'Completed', '2025-12-27', 'Proposal', 'good', NULL, 'RECOMMEND', '2025-12-27 03:09:52'),
(4, 1, NULL, 3, 'Completed', '2025-12-27', 'Proposal', 'great', NULL, 'RECOMMEND', '2025-12-27 03:15:33'),
(6, 1, NULL, 5, 'Completed', '2025-12-27', 'Proposal', 'good', NULL, 'RECOMMEND', '2025-12-27 03:35:11'),
(7, 1, NULL, 6, 'Completed', '2025-12-27', 'Proposal', 'test annotate', 'uploads/reviews/rev_1770408248_TEST_Annotate.pdf', 'REJECT', '2026-02-07 04:04:08'),
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
(22, 1, NULL, 29, 'Completed', '2026-02-06', 'Proposal', 'good', NULL, 'RECOMMEND', '2026-02-06 14:00:52'),
(23, 1, NULL, 30, 'Completed', '2026-02-06', 'Proposal', 'amend', 'uploads/reviews/rev_1770358030_Test proposal 1.pdf', 'AMENDMENT', '2026-02-06 14:07:10'),
(24, 1, NULL, 30, 'Completed', '2026-02-06', 'Proposal', 'no', NULL, 'REJECT', '2026-02-06 14:08:04'),
(25, 1, NULL, 35, 'Completed', '2026-02-06', 'Proposal', '1', 'uploads/reviews/rev_1770369685_Test proposal 1.pdf', 'AMENDMENT', '2026-02-06 17:21:25'),
(26, 1, NULL, 35, 'Pending', '2026-02-06', 'Proposal', NULL, NULL, NULL, NULL),
(27, 1, NULL, 37, 'Completed', '2026-02-07', 'Proposal', 'good', 'uploads/reviews/rev_1770401223_TEST_Annotate.pdf', 'RECOMMEND', '2026-02-07 02:07:03'),
(28, 1, NULL, 38, 'Completed', '2026-02-07', 'Proposal', 'good', 'uploads/reviews/rev_1770406357_TEST_Annotate.pdf', 'RECOMMEND', '2026-02-07 03:32:37'),
(29, 1, NULL, 39, 'Completed', '2026-02-07', 'Proposal', 'good', 'uploads/reviews/rev_1770444537_TEST_Annotate.pdf', 'RECOMMEND', '2026-02-07 14:08:57');

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
  `status` enum('PENDING','APPROVED','REJECTED') DEFAULT 'PENDING',
  `joined_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `department_id`, `notify_email`, `notify_system`, `profile_pic`, `avatar`, `notify_new_assign`, `notify_appeals`, `notify_hod_approve`, `notify_hod_reject`, `status`, `joined_at`) VALUES
(1, 'Ms.reviewer', '1@mail.com', '$2y$10$sxtA2hC.vebtunPSbUxtM.iFcJMFF0xmPS3zRzGhjslcyDNQx7p0m', 'reviewer', NULL, 0, 0, 'female.png', 'default', 1, 1, 1, 1, 'APPROVED', '2026-02-06 15:06:03'),
(2, 'agnes', '2@mail.com', '$2y$10$5xuzeZ.7gbxXW/wBHAKo5.0MGavXIZBvzWDS3Dk1ulQMb38.1X5qy', 'researcher', NULL, 1, 1, 'default.png', 'default', 1, 1, 1, 1, 'APPROVED', '2026-02-06 15:06:03'),
(3, 'HOD', '3@mail.com', '$2y$10$TB9alxPUk86xQDjNWsa24.ISJllSZGStmk70QCdDlWbhsv4wbGqXe', 'hod', 1, 1, 1, 'default.png', 'default', 1, 1, 1, 1, 'APPROVED', '2026-02-06 15:06:03'),
(4, 'mr admin', '4@mail.com', '$2y$10$0dlqy6UIW.iLj0M4.OVCHuNB3JXf9GxMlJScvf5W.Dw4Qw.aeihjC', 'admin', NULL, 1, 1, 'default.png', 'default', 1, 1, 1, 1, 'APPROVED', '2026-02-06 15:06:03'),
(5, 'a', 'a@mail.com', '$2y$10$F3Ht1Hd2i2gW8oZGsGaJQONlqc6JQ7qwgsr3u0KyCyNUx1PhKmZKu', 'researcher', NULL, 1, 1, 'default.png', 'default', 1, 1, 1, 1, 'APPROVED', '2026-02-06 15:06:03'),
(6, 'agnes', 'researcher@gmail.com', '$2y$10$najEye47FlSQ/CPwoDrRduvG/Aj2FGufluVvyhnbTVzgqHlkN3It6', 'researcher', 1, 1, 1, 'female.png', 'default', 1, 1, 1, 1, 'APPROVED', '2026-02-06 15:06:03');

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
  ADD KEY `reimbursement_request_id` (`reimbursement_request_id`),
  ADD KEY `transaction_date` (`transaction_date`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `extension_requests`
--
ALTER TABLE `extension_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `researcher_email` (`researcher_email`),
  ADD KEY `status` (`status`),
  ADD KEY `milestone_id` (`milestone_id`);

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
  ADD KEY `grant_id` (`grant_id`),
  ADD KEY `status` (`status`),
  ADD KEY `idx_milestones_grant` (`grant_id`);

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
  ADD KEY `priority` (`priority`),
  ADD KEY `idx_proposals_researcher_status` (`researcher_email`,`status`);

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
  ADD KEY `researcher_email` (`researcher_email`),
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
  ADD KEY `role` (`role`),
  ADD KEY `department_id` (`department_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=102;

--
-- AUTO_INCREMENT for table `appeal_requests`
--
ALTER TABLE `appeal_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `budget_items`
--
ALTER TABLE `budget_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=85;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `document_versions`
--
ALTER TABLE `document_versions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `expenditures`
--
ALTER TABLE `expenditures`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `extension_requests`
--
ALTER TABLE `extension_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `fund`
--
ALTER TABLE `fund`
  MODIFY `fund_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `grant_allocation`
--
ALTER TABLE `grant_allocation`
  MODIFY `allocation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `grant_document`
--
ALTER TABLE `grant_document`
  MODIFY `document_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `hod_tier_assignment`
--
ALTER TABLE `hod_tier_assignment`
  MODIFY `assignment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `issue_messages`
--
ALTER TABLE `issue_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `milestones`
--
ALTER TABLE `milestones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `misconduct_reports`
--
ALTER TABLE `misconduct_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=170;

--
-- AUTO_INCREMENT for table `progress_reports`
--
ALTER TABLE `progress_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `proposals`
--
ALTER TABLE `proposals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `proposal_rubric`
--
ALTER TABLE `proposal_rubric`
  MODIFY `rubric_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `reimbursement_requests`
--
ALTER TABLE `reimbursement_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `research_progress`
--
ALTER TABLE `research_progress`
  MODIFY `progress_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

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
  ADD CONSTRAINT `extension_requests_ibfk_2` FOREIGN KEY (`milestone_id`) REFERENCES `milestones` (`id`);

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
