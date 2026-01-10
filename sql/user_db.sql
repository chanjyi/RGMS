-- phpMyAdmin SQL Dump
-- Combined Database Schema with ALL DATA from both databases
-- FIXED: Added proposal_id to misconduct_reports table

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
(1, 12, 'researcher@gmail.com', 'idk', 'PENDING', '2026-01-07 15:03:41'),
(2, 12, 'researcher@gmail.com', 'idk', 'PENDING', '2026-01-07 15:05:24');

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
-- Table structure for table `misconduct_reports`
-- FIXED: Added proposal_id column to link misconduct reports to proposals
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
-- Note: proposal_id is NULL for old records since we don't know which proposal they were for
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
-- Combined from both databases
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
(66, '4@mail.com', 'New Proposal Submitted: \'Test Proposal 4\' by researcher@gmail.com', 0, '2026-01-07 15:54:18', 'alert');

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
  `reviewer_feedback` text DEFAULT NULL,
  `amendment_notes` text DEFAULT NULL,
  `budget_requested` decimal(10,2) DEFAULT 0.00,
  `approved_budget` decimal(10,2) DEFAULT 0.00,
  `amount_spent` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `proposals`
-- Combined from both databases
--

INSERT INTO `proposals` (`id`, `title`, `description`, `researcher_email`, `file_path`, `reviewer_email`, `status`, `created_at`, `approved_at`, `resubmitted_at`, `priority`, `reviewer_feedback`, `amendment_notes`, `budget_requested`, `approved_budget`, `amount_spent`) VALUES
(1, 'Hello World', NULL, '2@mail.com', 'uploads/prop_1766775766_2_mail_com.pdf', NULL, 'APPEAL_REJECTED', '2025-12-26 19:02:46', NULL, NULL, 'Normal', NULL, NULL, 0.00, 0.00, 0.00),
(2, 'Approval', NULL, '2@mail.com', 'uploads/prop_1766776144_2_mail_com.pdf', NULL, 'APPROVED', '2025-12-26 19:09:04', NULL, NULL, 'Normal', NULL, NULL, 0.00, 0.00, 0.00),
(3, 'Recommended', NULL, '2@mail.com', 'uploads/prop_1766776494_2_mail_com.pdf', NULL, 'APPROVED', '2025-12-26 19:14:54', NULL, NULL, 'Normal', NULL, NULL, 0.00, 0.00, 0.00),
(5, 'Rejected HOD', NULL, '2@mail.com', 'uploads/prop_1766777657_2_mail_com.pdf', NULL, 'REJECTED', '2025-12-26 19:34:17', NULL, NULL, 'Normal', NULL, NULL, 0.00, 0.00, 0.00),
(6, 'Rejected HOD', NULL, '2@mail.com', 'uploads/prop_1766777728_2_mail_com.pdf', NULL, 'ASSIGNED', '2025-12-26 19:35:28', NULL, NULL, 'Normal', NULL, NULL, 0.00, 0.00, 0.00),
(7, 'new', NULL, '2@mail.com', 'uploads/prop_1767024370_2_mail_com.pdf', NULL, 'ASSIGNED', '2025-12-29 16:06:10', NULL, NULL, 'Normal', NULL, NULL, 0.00, 0.00, 0.00),
(8, 'Test Proposal 1', NULL, 'researcher@gmail.com', 'uploads/prop_1767793973_researcher_gmail_com.pdf', NULL, 'REQUIRES_AMENDMENT', '2026-01-07 13:52:53', NULL, NULL, 'Normal', NULL, NULL, 0.00, 0.00, 0.00),
(12, 'Test Proposal 2', NULL, 'researcher@gmail.com', 'uploads/prop_1767798101_researcher_gmail_com.pdf', NULL, 'APPEALED', '2026-01-07 15:01:41', NULL, NULL, 'Normal', NULL, NULL, 0.00, 0.00, 0.00),
(13, 'Test Proposal 3', NULL, 'researcher@gmail.com', 'uploads/prop_1767798356_researcher_gmail_com.pdf', NULL, 'APPROVED', '2026-01-07 15:05:56', NULL, NULL, 'Normal', NULL, NULL, 0.00, 3000.00, 0.00),
(15, 'Test Proposal 4', 'test', 'researcher@gmail.com', 'uploads/prop_1767801223_researcher_gmail_com.pdf', NULL, 'REJECTED', '2026-01-07 15:53:43', NULL, NULL, 'Normal', NULL, NULL, 3000.00, 0.00, 0.00);

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
-- Combined from both databases
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
(14, 1, NULL, 7, 'Pending', '2025-12-30', 'Proposal', NULL, NULL, NULL, NULL),
(15, 1, NULL, 5, 'Completed', '2026-01-07', 'Proposal', NULL, NULL, 'REJECT', NULL),
(16, 1, NULL, 11, 'Completed', '2026-01-07', 'Proposal', NULL, NULL, 'REJECT', NULL),
(17, 1, NULL, 12, 'Completed', '2026-01-07', 'Proposal', NULL, NULL, 'REJECT', NULL),
(18, 1, NULL, 15, 'Completed', '2026-01-07', 'Proposal', NULL, NULL, 'REJECT', NULL);

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
  `notify_hod_reject` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
-- Combined from both databases
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `notify_email`, `notify_system`, `profile_pic`, `avatar`, `notify_new_assign`, `notify_appeals`, `notify_hod_approve`, `notify_hod_reject`) VALUES
(1, 'Ms.reviewer', '1@mail.com', '$2y$10$sxtA2hC.vebtunPSbUxtM.iFcJMFF0xmPS3zRzGhjslcyDNQx7p0m', 'reviewer', 0, 0, 'female.png', 'default', 1, 1, 1, 1),
(2, 'researcher', '2@mail.com', '$2y$10$5xuzeZ.7gbxXW/wBHAKo5.0MGavXIZBvzWDS3Dk1ulQMb38.1X5qy', 'researcher', 1, 1, 'default.png', 'default', 1, 1, 1, 1),
(3, 'HOD', '3@mail.com', '$2y$10$TB9alxPUk86xQDjNWsa24.ISJllSZGStmk70QCdDlWbhsv4wbGqXe', 'hod', 1, 1, 'default.png', 'default', 1, 1, 1, 1),
(4, 'mr admin', '4@mail.com', '$2y$10$0dlqy6UIW.iLj0M4.OVCHuNB3JXf9GxMlJScvf5W.Dw4Qw.aeihjC', 'admin', 1, 1, 'default.png', 'default', 1, 1, 1, 1),
(5, 'a', 'a@mail.com', '$2y$10$F3Ht1Hd2i2gW8oZGsGaJQONlqc6JQ7qwgsr3u0KyCyNUx1PhKmZKu', 'researcher', 1, 1, 'default.png', 'default', 1, 1, 1, 1),
(6, 'researcher', 'researcher@gmail.com', '$2y$10$najEye47FlSQ/CPwoDrRduvG/Aj2FGufluVvyhnbTVzgqHlkN3It6', 'researcher', 1, 1, 'default.png', 'default', 1, 1, 1, 1);

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
-- Indexes for table `extension_requests`
--
ALTER TABLE `extension_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `report_id` (`report_id`),
  ADD KEY `researcher_email` (`researcher_email`),
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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `extension_requests`
--
ALTER TABLE `extension_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `misconduct_reports`
--
ALTER TABLE `misconduct_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- AUTO_INCREMENT for table `progress_reports`
--
ALTER TABLE `progress_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `proposals`
--
ALTER TABLE `proposals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

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
-- Constraints for table `extension_requests`
--
ALTER TABLE `extension_requests`
  ADD CONSTRAINT `extension_requests_ibfk_1` FOREIGN KEY (`report_id`) REFERENCES `progress_reports` (`id`) ON DELETE CASCADE;

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

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;