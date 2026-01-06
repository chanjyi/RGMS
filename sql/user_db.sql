-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 06, 2026 at 09:48 AM
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
  `proposal_id` int(11) NOT NULL,
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
(5, 17, '1@mail.com', '2@mail.com', 'Data Fabrication', 'kick jiayi', 'PENDING', '2026-01-04 10:29:17'),
(6, 18, '1@mail.com', '2@mail.com', 'Plagiarism', 'kick chew', 'PENDING', '2026-01-04 10:35:28'),
(7, 19, '1@mail.com', '2@mail.com', 'Plagiarism', 'ew', 'PENDING', '2026-01-04 17:12:04');

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
(55, '4@mail.com', 'New Proposal Submitted: \'trying\' by 2@mail.com', 0, '2026-01-04 06:22:31', 'info'),
(57, '2@mail.com', 'Update on \'trying\': Your proposal status is now recommended.', 0, '2026-01-04 06:23:18', 'info'),
(58, '2@mail.com', 'Update on \'new\': Your proposal status is now recommended.', 0, '2026-01-04 06:23:23', 'info'),
(59, '4@mail.com', 'New Proposal Submitted: \'test\' by 2@mail.com', 0, '2026-01-04 06:32:00', 'info'),
(61, '2@mail.com', 'Update on \'test\': Your proposal status is now recommend.', 0, '2026-01-04 06:38:41', 'info'),
(62, '4@mail.com', 'New Proposal Submitted: \'urgent\' by 2@mail.com', 0, '2026-01-04 06:41:06', 'info'),
(64, '2@mail.com', 'Update on \'urgent\': Your proposal status is now recommend.', 0, '2026-01-04 06:42:57', 'info'),
(65, '4@mail.com', 'New Proposal Submitted: \'check feedback\' by 2@mail.com', 0, '2026-01-04 06:54:12', 'info'),
(67, '2@mail.com', 'Update on \'check feedback\': Your proposal status is now recommend.', 0, '2026-01-04 07:07:16', 'info'),
(68, '4@mail.com', 'New Proposal Submitted: \'request amendment\' by 2@mail.com', 0, '2026-01-04 07:16:21', 'info'),
(70, '2@mail.com', 'Action Required: The reviewer requested amendments on \'request amendment\'. Please check the dashboard.', 0, '2026-01-04 07:17:43', 'info'),
(71, '2@mail.com', 'Action Required: The reviewer requested amendments on \'request amendment\'. Please check the dashboard.', 0, '2026-01-04 07:24:41', 'info'),
(72, '4@mail.com', 'New Proposal Submitted: \'feedback sequence\' by 2@mail.com', 0, '2026-01-04 07:33:14', 'info'),
(74, '2@mail.com', 'Action Required: The reviewer requested amendments on \'feedback sequence\'. Please check the dashboard.', 0, '2026-01-04 09:19:32', 'info'),
(75, '4@mail.com', 'New Proposal Submitted: \'recommend\' by 2@mail.com', 0, '2026-01-04 09:19:55', 'info'),
(77, '2@mail.com', 'Update on \'recommend\': Your proposal status is now recommend.', 0, '2026-01-04 09:23:00', 'info'),
(78, '4@mail.com', 'New Proposal Submitted: \'reject\' by 2@mail.com', 0, '2026-01-04 09:23:32', 'info'),
(80, '2@mail.com', 'Update on \'reject\': Your proposal status is now rejected.', 0, '2026-01-04 09:23:52', 'info'),
(81, '4@mail.com', 'New Proposal Submitted: \'misconduct\' by 2@mail.com', 0, '2026-01-04 09:24:15', 'info'),
(83, '4@mail.com', 'ALERT: Misconduct reported by 1@mail.com against 2@mail.com. Category: Plagiarism', 0, '2026-01-04 09:31:43', 'alert'),
(84, '4@mail.com', 'New Proposal Submitted: \'misconduct\' by 2@mail.com', 0, '2026-01-04 10:20:28', 'info'),
(86, '4@mail.com', 'ALERT: Misconduct reported by 1@mail.com against 2@mail.com. Category: Data Fabrication', 0, '2026-01-04 10:29:17', 'alert'),
(87, '4@mail.com', 'New Proposal Submitted: \'misconduct\' by 2@mail.com', 0, '2026-01-04 10:35:06', 'info'),
(89, '4@mail.com', 'ALERT: Misconduct reported by 1@mail.com against 2@mail.com. Category: Plagiarism', 0, '2026-01-04 10:35:28', 'alert'),
(90, '4@mail.com', 'New Proposal Submitted: \'misconduct2\' by 2@mail.com', 0, '2026-01-04 17:01:45', 'info'),
(92, '2@mail.com', 'Final Decision: Your proposal \'recommend\' has been APPROVED by the Head of Department.', 0, '2026-01-04 17:03:05', 'info'),
(93, '4@mail.com', 'ALERT: Misconduct reported by 1@mail.com against 2@mail.com. Category: Plagiarism', 0, '2026-01-04 17:12:04', 'alert'),
(94, '4@mail.com', 'New Proposal Submitted: \'Annotate\' by 2@mail.com', 0, '2026-01-04 17:51:52', 'info'),
(96, '2@mail.com', 'Action Required: The reviewer requested amendments on \'Annotate\'. Please check the dashboard.', 0, '2026-01-04 17:53:07', 'info'),
(97, '4@mail.com', 'New Proposal Submitted: \'annotate\' by 2@mail.com', 0, '2026-01-04 17:56:41', 'info'),
(99, '2@mail.com', 'Action Required: The reviewer requested amendments on \'annotate\'. Please check the dashboard.', 0, '2026-01-04 17:58:12', 'info'),
(100, '4@mail.com', 'New Proposal Submitted: \'annotate_reject\' by 2@mail.com', 0, '2026-01-04 17:58:43', 'info'),
(102, '2@mail.com', 'Update on \'annotate_reject\': Your proposal status is now rejected.', 0, '2026-01-04 17:59:23', 'info'),
(103, '2@mail.com', 'Final Decision: Your proposal \'urgent\' has been APPROVED by the Head of Department.', 0, '2026-01-06 06:22:06', 'info'),
(104, '2@mail.com', 'Final Decision: Your proposal \'test\' has been REJECTED by the Head of Department.', 0, '2026-01-06 06:23:04', 'info'),
(105, '4@mail.com', 'New Proposal Submitted: \'reject by HOD\' by 2@mail.com', 0, '2026-01-06 06:35:32', 'info'),
(107, '2@mail.com', 'Update on \'reject by HOD\': Your proposal status is now recommend.', 0, '2026-01-06 06:36:40', 'info'),
(108, '2@mail.com', 'Final Decision: Your proposal \'reject by HOD\' has been REJECTED by the Head of Department.', 0, '2026-01-06 06:37:20', 'info'),
(109, '4@mail.com', 'New Proposal Submitted: \'hod_notifications\' by 2@mail.com', 0, '2026-01-06 06:46:08', 'info'),
(110, '1@mail.com', 'New Assignment: You have been assigned a proposal.', 0, '2026-01-06 06:46:16', 'info'),
(111, '2@mail.com', 'Update on \'hod_notifications\': Your proposal status is now recommend.', 0, '2026-01-06 06:46:38', 'info'),
(112, '2@mail.com', 'Final Decision: Your proposal \'hod_notifications\' has been APPROVED by the Head of Department.', 0, '2026-01-06 06:48:01', 'info'),
(113, '1@mail.com', 'Update: The proposal \'hod_notifications\' you reviewed has been FINAL APPROVED by the HOD.', 0, '2026-01-06 06:48:01', 'info'),
(114, '4@mail.com', 'New Proposal Submitted: \'hod_notifications_Reject\' by 2@mail.com', 0, '2026-01-06 06:48:46', 'info'),
(115, '1@mail.com', 'New Assignment: You have been assigned a proposal.', 0, '2026-01-06 06:48:53', 'info'),
(116, '2@mail.com', 'Update on \'hod_notifications_Reject\': Your proposal status is now recommend.', 0, '2026-01-06 06:49:09', 'info'),
(117, '2@mail.com', 'Final Decision: Your proposal \'hod_notifications_Reject\' has been REJECTED by the Head of Department.', 0, '2026-01-06 06:49:25', 'info'),
(118, '1@mail.com', 'Update: The proposal \'hod_notifications_Reject\' you reviewed was REJECTED by the HOD.', 0, '2026-01-06 06:49:25', 'info'),
(119, '4@mail.com', 'New Proposal Submitted: \'mute_HOD_notifications\' by 2@mail.com', 0, '2026-01-06 06:51:40', 'info'),
(120, '4@mail.com', 'New Proposal Submitted: \'mute_HOD_Reject_notifications\' by 2@mail.com', 0, '2026-01-06 06:51:55', 'info'),
(121, '4@mail.com', 'New Proposal Submitted: \'Appeal_notifications\' by 2@mail.com', 0, '2026-01-06 06:52:10', 'info'),
(122, '1@mail.com', 'New Assignment: You have been assigned a proposal.', 0, '2026-01-06 06:52:17', 'info'),
(123, '1@mail.com', 'New Assignment: You have been assigned a proposal.', 0, '2026-01-06 06:52:19', 'info'),
(124, '1@mail.com', 'New Assignment: You have been assigned a proposal.', 0, '2026-01-06 06:52:20', 'info'),
(126, '1@mail.com', 'New Assignment: You have been assigned a proposal.', 0, '2026-01-06 06:54:03', 'info'),
(127, '4@mail.com', 'New Proposal Submitted: \'Appeal\' by 2@mail.com', 0, '2026-01-06 07:37:41', 'info'),
(128, '4@mail.com', 'New Proposal Submitted: \'Appeal\' by 2@mail.com', 0, '2026-01-06 07:37:43', 'info'),
(129, '4@mail.com', 'New Proposal Submitted: \'Appeal\' by 2@mail.com', 0, '2026-01-06 07:37:47', 'info'),
(130, '1@mail.com', 'New Assignment: You have been assigned a proposal.', 0, '2026-01-06 07:38:21', 'info'),
(131, '2@mail.com', 'Update on \'Appeal\': Your proposal status is now rejected.', 0, '2026-01-06 07:39:15', 'info'),
(132, '4@mail.com', 'Appeal Request: Researcher (2@mail.com) has appealed the rejection of \'Appeal\'.', 0, '2026-01-06 07:39:29', 'info'),
(133, '2@mail.com', 'Update on \'mute_HOD_notifications\': Your proposal status is now recommend.', 0, '2026-01-06 07:44:42', 'info'),
(134, '2@mail.com', 'Update on \'mute_HOD_Reject_notifications\': Your proposal status is now recommend.', 0, '2026-01-06 07:44:51', 'info'),
(135, '2@mail.com', 'Appeal Update: The HOD has accepted your appeal. Your proposal will be reassigned to a new reviewer.', 0, '2026-01-06 07:47:14', 'info'),
(136, '4@mail.com', 'New Proposal Submitted: \'Appeal\' by 2@mail.com', 0, '2026-01-06 08:03:29', 'info'),
(137, '1@mail.com', 'New Assignment: You have been assigned a proposal.', 0, '2026-01-06 08:03:40', 'info'),
(138, '2@mail.com', 'Update on \'Appeal\': Your proposal status is now rejected.', 0, '2026-01-06 08:04:19', 'info'),
(139, '4@mail.com', 'Appeal Request: Researcher (2@mail.com) has appealed the rejection of \'Appeal\'.', 0, '2026-01-06 08:07:49', 'info'),
(140, '2@mail.com', 'Appeal Update: The HOD has accepted your appeal. Your proposal will be reassigned to a new reviewer.', 0, '2026-01-06 08:07:58', 'info'),
(141, '4@mail.com', 'New Proposal Submitted: \'Appeal\' by 2@mail.com', 0, '2026-01-06 08:14:08', 'info'),
(142, '1@mail.com', 'New Assignment: You have been assigned a proposal.', 0, '2026-01-06 08:14:27', 'info'),
(143, '2@mail.com', 'Update on \'Appeal\': Your proposal status is now rejected.', 0, '2026-01-06 08:14:38', 'info'),
(144, '4@mail.com', 'Appeal Request: Researcher (2@mail.com) has appealed the rejection of \'Appeal\'.', 0, '2026-01-06 08:14:59', 'info'),
(145, '2@mail.com', 'Appeal Update: The HOD has accepted your appeal. Your proposal will be reassigned to a new reviewer.', 0, '2026-01-06 08:15:27', 'info'),
(146, '4@mail.com', 'New Proposal Submitted: \'Appeal 2\' by 2@mail.com', 0, '2026-01-06 08:29:24', 'info'),
(147, '1@mail.com', 'New Assignment: You have been assigned a proposal.', 0, '2026-01-06 08:29:34', 'info'),
(148, '2@mail.com', 'Update on \'Appeal 2\': Your proposal status is now rejected.', 0, '2026-01-06 08:29:45', 'info'),
(149, '4@mail.com', 'Appeal Request: Researcher (2@mail.com) has appealed the rejection of \'Appeal 2\'.', 0, '2026-01-06 08:30:10', 'info'),
(150, '2@mail.com', 'Appeal Update: The HOD has accepted your appeal. Your proposal will be reassigned to a new reviewer.', 0, '2026-01-06 08:30:16', 'info'),
(151, '11@mail.com', 'Appeal Case: You have been assigned to review proposal #34.', 0, '2026-01-06 08:40:04', 'info'),
(152, '11@mail.com', 'Appeal Case: You have been assigned to review proposal #35.', 0, '2026-01-06 08:40:10', 'info');

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
  `status` enum('SUBMITTED','ASSIGNED','PENDING_REVIEW','REQUIRES_AMENDMENT','RESUBMITTED','RECOMMEND','REJECTED','APPROVED','APPEALED','APPEAL_REJECTED','UNDER_INVESTIGATION','PENDING_REASSIGNMENT') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `priority` enum('Normal','High') DEFAULT 'Normal',
  `reviewer_feedback` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `proposals`
--

INSERT INTO `proposals` (`id`, `title`, `researcher_email`, `file_path`, `reviewer_email`, `status`, `created_at`, `priority`, `reviewer_feedback`) VALUES
(2, 'Approval', '2@mail.com', 'uploads/prop_1766776144_2_mail_com.pdf', NULL, 'APPROVED', '2025-12-26 19:09:04', 'Normal', NULL),
(3, 'Recommended', '2@mail.com', 'uploads/prop_1766776494_2_mail_com.pdf', NULL, 'APPROVED', '2025-12-26 19:14:54', 'Normal', NULL),
(9, 'test', '2@mail.com', 'uploads/prop_1767508320_2_mail_com.pdf', NULL, 'REJECTED', '2026-01-04 06:32:00', 'Normal', NULL),
(10, 'urgent', '2@mail.com', 'uploads/prop_1767508866_2_mail_com.pdf', NULL, 'APPROVED', '2026-01-04 06:41:06', 'High', NULL),
(11, 'check feedback', '2@mail.com', 'uploads/prop_1767509652_2_mail_com.pdf', NULL, 'RECOMMEND', '2026-01-04 06:54:12', 'Normal', NULL),
(12, 'request amendment', '2@mail.com', 'uploads/prop_1767510981_2_mail_com.pdf', NULL, 'REQUIRES_AMENDMENT', '2026-01-04 07:16:21', 'Normal', 'not good enough'),
(13, 'feedback sequence', '2@mail.com', 'uploads/prop_1767511994_2_mail_com.pdf', NULL, 'REQUIRES_AMENDMENT', '2026-01-04 07:33:14', 'Normal', 'feedback'),
(14, 'recommend', '2@mail.com', 'uploads/prop_1767518395_2_mail_com.pdf', NULL, 'APPROVED', '2026-01-04 09:19:55', 'Normal', NULL),
(15, 'reject', '2@mail.com', 'uploads/prop_1767518612_2_mail_com.pdf', NULL, 'REJECTED', '2026-01-04 09:23:32', 'Normal', NULL),
(16, 'misconduct', '2@mail.com', 'uploads/prop_1767518655_2_mail_com.pdf', NULL, 'ASSIGNED', '2026-01-04 09:24:15', 'Normal', NULL),
(17, 'misconduct', '2@mail.com', 'uploads/prop_1767522028_2_mail_com.pdf', NULL, 'UNDER_INVESTIGATION', '2026-01-04 10:20:28', 'Normal', NULL),
(18, 'misconduct', '2@mail.com', 'uploads/prop_1767522906_2_mail_com.pdf', NULL, 'UNDER_INVESTIGATION', '2026-01-04 10:35:06', 'Normal', NULL),
(19, 'misconduct2', '2@mail.com', 'uploads/prop_1767546105_2_mail_com.pdf', NULL, 'UNDER_INVESTIGATION', '2026-01-04 17:01:45', 'Normal', NULL),
(21, 'annotate', '2@mail.com', 'uploads/prop_1767549401_2_mail_com.pdf', NULL, 'REQUIRES_AMENDMENT', '2026-01-04 17:56:41', 'Normal', 'bye'),
(22, 'annotate_reject', '2@mail.com', 'uploads/prop_1767549523_2_mail_com.pdf', NULL, 'REJECTED', '2026-01-04 17:58:43', 'Normal', NULL),
(23, 'reject by HOD', '2@mail.com', 'uploads/prop_1767681332_2_mail_com.pdf', NULL, 'REJECTED', '2026-01-06 06:35:32', 'Normal', NULL),
(24, 'hod_notifications', '2@mail.com', 'uploads/prop_1767681968_2_mail_com.pdf', NULL, 'APPROVED', '2026-01-06 06:46:08', 'Normal', NULL),
(25, 'hod_notifications_Reject', '2@mail.com', 'uploads/prop_1767682126_2_mail_com.pdf', NULL, 'REJECTED', '2026-01-06 06:48:46', 'Normal', NULL),
(26, 'mute_HOD_notifications', '2@mail.com', 'uploads/prop_1767682300_2_mail_com.pdf', NULL, 'APPROVED', '2026-01-06 06:51:40', 'Normal', NULL),
(27, 'mute_HOD_Reject_notifications', '2@mail.com', 'uploads/prop_1767682315_2_mail_com.pdf', NULL, 'REJECTED', '2026-01-06 06:51:55', 'Normal', NULL),
(34, 'Appeal', '2@mail.com', 'uploads/prop_1767687248_2_mail_com.pdf', NULL, 'ASSIGNED', '2026-01-06 08:14:08', 'High', NULL),
(35, 'Appeal 2', '2@mail.com', 'uploads/prop_1767688164_2_mail_com.pdf', NULL, 'ASSIGNED', '2026-01-06 08:29:24', 'High', NULL);

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
(20, 1, 13, 'Completed', '2026-01-04', 'Proposal', 'feedback', NULL, 'AMENDMENT', '2026-01-04 17:19:32'),
(21, 1, 14, 'Completed', '2026-01-04', 'Proposal', 'good', NULL, 'RECOMMEND', '2026-01-04 17:23:00'),
(22, 1, 15, 'Completed', '2026-01-04', 'Proposal', 'bad', NULL, 'REJECT', '2026-01-04 17:23:52'),
(23, 1, 16, '', '2026-01-04', 'Proposal', NULL, NULL, NULL, NULL),
(24, 1, 17, '', '2026-01-04', 'Proposal', NULL, NULL, 'REJECT', '2026-01-04 18:29:17'),
(25, 1, 18, '', '2026-01-04', 'Proposal', NULL, NULL, 'REJECT', '2026-01-04 18:35:28'),
(26, 1, 19, '', '2026-01-05', 'Proposal', NULL, NULL, 'REJECT', '2026-01-05 01:12:04'),
(27, 1, 20, 'Completed', '2026-01-05', 'Proposal', 'CHANGE IMMEDIATELY', NULL, 'AMENDMENT', '2026-01-05 01:53:07'),
(28, 1, 21, 'Completed', '2026-01-05', 'Proposal', 'bye', 'uploads/reviews/rev_1767549492_BAD.pdf', 'AMENDMENT', '2026-01-05 01:58:12'),
(29, 1, 22, 'Completed', '2026-01-05', 'Proposal', 'no', 'uploads/reviews/rev_1767549563_BAD.pdf', 'REJECT', '2026-01-05 01:59:23'),
(30, 1, 23, 'Completed', '2026-01-06', 'Proposal', 'great, reject by HOD', NULL, 'RECOMMEND', '2026-01-06 14:36:40'),
(31, 1, 24, 'Completed', '2026-01-06', 'Proposal', 'HOD APPROVE', NULL, 'RECOMMEND', '2026-01-06 14:46:38'),
(32, 1, 25, 'Completed', '2026-01-06', 'Proposal', 'HOD reject', NULL, 'RECOMMEND', '2026-01-06 14:49:09'),
(33, 1, 26, 'Completed', '2026-01-06', 'Proposal', 'muted approve', NULL, 'RECOMMEND', '2026-01-06 15:44:42'),
(34, 1, 27, 'Completed', '2026-01-06', 'Proposal', 'muted reject', NULL, 'RECOMMEND', '2026-01-06 15:44:51'),
(37, 1, 30, 'Completed', '2026-01-06', 'Proposal', 'appeal', NULL, 'REJECT', '2026-01-06 15:39:15'),
(38, 1, 33, 'Completed', '2026-01-06', 'Proposal', 'bad', NULL, 'REJECT', '2026-01-06 16:04:19'),
(39, 1, 34, 'Completed', '2026-01-06', 'Proposal', 'bad', NULL, 'REJECT', '2026-01-06 16:14:38'),
(40, 1, 35, 'Completed', '2026-01-06', 'Proposal', 'bad', NULL, 'REJECT', '2026-01-06 16:29:45'),
(41, 5, 34, 'Pending', '2026-01-06', 'Appeal', NULL, NULL, NULL, NULL),
(42, 5, 35, 'Pending', '2026-01-06', 'Appeal', NULL, NULL, NULL, NULL);

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
(1, 'Ms.reviewer', '1@mail.com', '$2y$10$sxtA2hC.vebtunPSbUxtM.iFcJMFF0xmPS3zRzGhjslcyDNQx7p0m', 'reviewer', 0, 0, 'female.png', 'default', 1, 1, 0, 0),
(2, 'researcher', '2@mail.com', '$2y$10$5xuzeZ.7gbxXW/wBHAKo5.0MGavXIZBvzWDS3Dk1ulQMb38.1X5qy', 'researcher', 1, 1, 'default.png', 'default', 1, 1, 1, 1),
(3, 'HOD', '3@mail.com', '$2y$10$TB9alxPUk86xQDjNWsa24.ISJllSZGStmk70QCdDlWbhsv4wbGqXe', 'hod', 1, 1, 'default.png', 'default', 1, 1, 1, 1),
(4, 'mr admin', '4@mail.com', '$2y$10$0dlqy6UIW.iLj0M4.OVCHuNB3JXf9GxMlJScvf5W.Dw4Qw.aeihjC', 'admin', 1, 1, 'default.png', 'default', 1, 1, 1, 1),
(5, 'Ms. New Reviewer', '11@mail.com', '$2y$10$yg6xn/T198WzGisvNYKR/.px5PfQ38dTPuTU8uBMeFxf8OswWQaYu', 'reviewer', 1, 1, 'default.png', 'default', 1, 1, 1, 1);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=153;

--
-- AUTO_INCREMENT for table `proposals`
--
ALTER TABLE `proposals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
