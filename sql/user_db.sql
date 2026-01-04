-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 04, 2026 at 09:53 AM
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
-- Table structure for table `misconduct_reports`
--

CREATE TABLE `misconduct_reports` (
  `id` int(11) NOT NULL,
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

INSERT INTO `misconduct_reports` (`id`, `reviewer_email`, `researcher_email`, `category`, `details`, `status`, `created_at`) VALUES
(1, '1@mail.com', '2@mail.com', 'Plagiarism', 'baddd', 'PENDING', '2025-12-27 16:54:55'),
(2, '1@mail.com', '2@mail.com', 'Plagiarism', '/', 'PENDING', '2025-12-27 18:03:10'),
(3, '1@mail.com', '2@mail.com', 'Data Fabrication', 'kick', 'PENDING', '2025-12-27 18:10:19');

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
(55, '4@mail.com', 'New Proposal Submitted: \'trying\' by 2@mail.com', 0, '2026-01-04 06:22:31', 'info'),
(56, '1@mail.com', 'New Assignment: You have been assigned a proposal.', 0, '2026-01-04 06:22:46', 'info'),
(57, '2@mail.com', 'Update on \'trying\': Your proposal status is now recommended.', 0, '2026-01-04 06:23:18', 'info'),
(58, '2@mail.com', 'Update on \'new\': Your proposal status is now recommended.', 0, '2026-01-04 06:23:23', 'info'),
(59, '4@mail.com', 'New Proposal Submitted: \'test\' by 2@mail.com', 0, '2026-01-04 06:32:00', 'info'),
(60, '1@mail.com', 'New Assignment: You have been assigned a proposal.', 0, '2026-01-04 06:32:09', 'info'),
(61, '2@mail.com', 'Update on \'test\': Your proposal status is now recommend.', 0, '2026-01-04 06:38:41', 'info'),
(62, '4@mail.com', 'New Proposal Submitted: \'urgent\' by 2@mail.com', 0, '2026-01-04 06:41:06', 'info'),
(63, '1@mail.com', 'New Assignment: You have been assigned a proposal.', 0, '2026-01-04 06:41:18', 'info'),
(64, '2@mail.com', 'Update on \'urgent\': Your proposal status is now recommend.', 0, '2026-01-04 06:42:57', 'info'),
(65, '4@mail.com', 'New Proposal Submitted: \'check feedback\' by 2@mail.com', 0, '2026-01-04 06:54:12', 'info'),
(66, '1@mail.com', 'New Assignment: You have been assigned a proposal.', 0, '2026-01-04 06:54:26', 'info'),
(67, '2@mail.com', 'Update on \'check feedback\': Your proposal status is now recommend.', 0, '2026-01-04 07:07:16', 'info'),
(68, '4@mail.com', 'New Proposal Submitted: \'request amendment\' by 2@mail.com', 0, '2026-01-04 07:16:21', 'info'),
(69, '1@mail.com', 'New Assignment: You have been assigned a proposal.', 0, '2026-01-04 07:16:32', 'info'),
(70, '2@mail.com', 'Action Required: The reviewer requested amendments on \'request amendment\'. Please check the dashboard.', 0, '2026-01-04 07:17:43', 'info'),
(71, '2@mail.com', 'Action Required: The reviewer requested amendments on \'request amendment\'. Please check the dashboard.', 0, '2026-01-04 07:24:41', 'info'),
(72, '4@mail.com', 'New Proposal Submitted: \'feedback sequence\' by 2@mail.com', 0, '2026-01-04 07:33:14', 'info'),
(73, '1@mail.com', 'New Assignment: You have been assigned a proposal.', 0, '2026-01-04 07:33:26', 'info');

-- --------------------------------------------------------

--
-- Table structure for table `proposals`
--

CREATE TABLE `proposals` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `researcher_email` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `reviewer_email` varchar(255) DEFAULT NULL,
  `status` enum('SUBMITTED','ASSIGNED','PENDING_REVIEW','REQUIRES_AMENDMENT','RESUBMITTED','RECOMMEND','REJECTED','APPROVED','APPEALED','APPEAL_REJECTED') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `priority` enum('Normal','High') DEFAULT 'Normal',
  `reviewer_feedback` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `proposals`
--

INSERT INTO `proposals` (`id`, `title`, `researcher_email`, `file_path`, `reviewer_email`, `status`, `created_at`, `priority`, `reviewer_feedback`) VALUES
(1, 'Hello World', '2@mail.com', 'uploads/prop_1766775766_2_mail_com.pdf', NULL, 'APPEAL_REJECTED', '2025-12-26 19:02:46', 'Normal', NULL),
(2, 'Approval', '2@mail.com', 'uploads/prop_1766776144_2_mail_com.pdf', NULL, 'APPROVED', '2025-12-26 19:09:04', 'Normal', NULL),
(3, 'Recommended', '2@mail.com', 'uploads/prop_1766776494_2_mail_com.pdf', NULL, 'APPROVED', '2025-12-26 19:14:54', 'Normal', NULL),
(5, 'Rejected HOD', '2@mail.com', 'uploads/prop_1766777657_2_mail_com.pdf', NULL, 'REJECTED', '2025-12-26 19:34:17', 'Normal', NULL),
(6, 'Rejected HOD', '2@mail.com', 'uploads/prop_1766777728_2_mail_com.pdf', NULL, 'ASSIGNED', '2025-12-26 19:35:28', 'Normal', NULL),
(7, 'new', '2@mail.com', 'uploads/prop_1767024370_2_mail_com.pdf', NULL, '', '2025-12-29 16:06:10', 'High', NULL),
(8, 'trying', '2@mail.com', 'uploads/prop_1767507751_2_mail_com.pdf', NULL, '', '2026-01-04 06:22:31', 'Normal', NULL),
(9, 'test', '2@mail.com', 'uploads/prop_1767508320_2_mail_com.pdf', NULL, 'RECOMMEND', '2026-01-04 06:32:00', 'Normal', NULL),
(10, 'urgent', '2@mail.com', 'uploads/prop_1767508866_2_mail_com.pdf', NULL, 'RECOMMEND', '2026-01-04 06:41:06', 'High', NULL),
(11, 'check feedback', '2@mail.com', 'uploads/prop_1767509652_2_mail_com.pdf', NULL, 'RECOMMEND', '2026-01-04 06:54:12', 'Normal', NULL),
(12, 'request amendment', '2@mail.com', 'uploads/prop_1767510981_2_mail_com.pdf', NULL, 'REQUIRES_AMENDMENT', '2026-01-04 07:16:21', 'Normal', 'not good enough'),
(13, 'feedback sequence', '2@mail.com', 'uploads/prop_1767511994_2_mail_com.pdf', NULL, 'ASSIGNED', '2026-01-04 07:33:14', 'Normal', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `reviewer_id` int(11) NOT NULL,
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

INSERT INTO `reviews` (`id`, `reviewer_id`, `proposal_id`, `status`, `assigned_date`, `type`, `feedback`, `annotated_file`, `decision`, `review_date`) VALUES
(2, 1, 1, 'Completed', '2025-12-27', '', 'so bad', 'uploads/reviews/rev_1766776110_prop_1766775766_2_mail_com.pdf', 'REJECT', '2025-12-27 03:08:30'),
(3, 1, 2, 'Completed', '2025-12-27', '', 'good', NULL, 'RECOMMEND', '2025-12-27 03:09:52'),
(4, 1, 3, 'Completed', '2025-12-27', '', 'great', NULL, 'RECOMMEND', '2025-12-27 03:15:33'),
(6, 1, 5, 'Completed', '2025-12-27', '', 'good', NULL, 'RECOMMEND', '2025-12-27 03:35:11'),
(7, 1, 6, '', '2025-12-27', 'Proposal', NULL, NULL, NULL, NULL),
(8, 1, 1, 'Completed', '2025-12-27', 'Appeal', 'baadddd', NULL, 'REJECT', '2025-12-27 03:44:27'),
(10, 1, 1, 'Completed', '2025-12-27', 'Appeal', 'bad', NULL, 'RECOMMEND', '2025-12-27 03:48:24'),
(13, 1, 1, 'Completed', '2025-12-27', 'Appeal', 'bad', NULL, 'REJECT', '2025-12-27 03:52:58'),
(14, 1, 7, 'Completed', '2025-12-30', 'Proposal', '', NULL, 'RECOMMEND', '2026-01-04 14:23:23'),
(15, 1, 8, 'Completed', '2026-01-04', 'Proposal', '', NULL, 'RECOMMEND', '2026-01-04 14:23:18'),
(16, 1, 9, 'Completed', '2026-01-04', 'Proposal', '', NULL, 'RECOMMEND', '2026-01-04 14:38:41'),
(17, 1, 10, 'Completed', '2026-01-04', 'Proposal', '', NULL, 'RECOMMEND', '2026-01-04 14:42:57'),
(18, 1, 11, 'Completed', '2026-01-04', 'Proposal', NULL, NULL, 'RECOMMEND', '2026-01-04 15:07:16'),
(19, 1, 12, 'Completed', '2026-01-04', 'Proposal', 'not good enough', NULL, 'AMENDMENT', '2026-01-04 15:24:41'),
(20, 1, 13, 'Pending', '2026-01-04', 'Proposal', NULL, NULL, NULL, NULL);

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
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `notify_email`, `notify_system`, `profile_pic`, `avatar`, `notify_new_assign`, `notify_appeals`, `notify_hod_approve`, `notify_hod_reject`) VALUES
(1, 'Ms.reviewer', '1@mail.com', '$2y$10$sxtA2hC.vebtunPSbUxtM.iFcJMFF0xmPS3zRzGhjslcyDNQx7p0m', 'reviewer', 0, 0, 'female.png', 'default', 1, 1, 1, 1),
(2, 'researcher', '2@mail.com', '$2y$10$5xuzeZ.7gbxXW/wBHAKo5.0MGavXIZBvzWDS3Dk1ulQMb38.1X5qy', 'researcher', 1, 1, 'default.png', 'default', 1, 1, 1, 1),
(3, 'HOD', '3@mail.com', '$2y$10$TB9alxPUk86xQDjNWsa24.ISJllSZGStmk70QCdDlWbhsv4wbGqXe', 'hod', 1, 1, 'default.png', 'default', 1, 1, 1, 1),
(4, 'mr admin', '4@mail.com', '$2y$10$0dlqy6UIW.iLj0M4.OVCHuNB3JXf9GxMlJScvf5W.Dw4Qw.aeihjC', 'admin', 1, 1, 'default.png', 'default', 1, 1, 1, 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `misconduct_reports`
--
ALTER TABLE `misconduct_reports`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `proposals`
--
ALTER TABLE `proposals`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email_unique` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `misconduct_reports`
--
ALTER TABLE `misconduct_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=74;

--
-- AUTO_INCREMENT for table `proposals`
--
ALTER TABLE `proposals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
