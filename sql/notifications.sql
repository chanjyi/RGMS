-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 27, 2025 at 09:34 AM
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
(7, '1@mail.com', 'You have been assigned a new proposal to review.', 0, '2025-12-26 19:05:47', 'info'),
(8, '2@mail.com', 'Update on \'Hello World\': Your proposal has been rejected.', 0, '2025-12-26 19:08:30', 'info'),
(9, '1@mail.com', 'You have been assigned a new proposal to review.', 0, '2025-12-26 19:09:24', 'info'),
(10, '2@mail.com', 'Update on \'Approval\': Your proposal has been approved.', 0, '2025-12-26 19:09:52', 'info'),
(11, '1@mail.com', 'You have been assigned a new proposal to review.', 0, '2025-12-26 19:15:19', 'info'),
(12, '2@mail.com', 'Update on \'Recommended\': Your proposal has been recommended.', 0, '2025-12-26 19:15:33', 'info'),
(13, '2@mail.com', 'Final Decision: Your proposal \'Recommended\' has been APPROVED by the Head of Department.', 0, '2025-12-26 19:16:16', 'info'),
(14, 'admin@test.com', 'Appeal Request: Researcher (2@mail.com) has appealed the rejection of \'Hello World\'.', 0, '2025-12-26 19:27:52', 'info'),
(15, '1@mail.com', 'You have been assigned a new proposal to review.', 0, '2025-12-26 19:31:34', 'info'),
(16, '2@mail.com', 'Update on \'Appeal\': Your proposal has been rejected.', 0, '2025-12-26 19:32:31', 'info'),
(17, '4@mail.com', 'New Proposal Submitted: \'Rejected HOD\' by 2@mail.com', 0, '2025-12-26 19:34:17', 'info'),
(18, '1@mail.com', 'You have been assigned a new proposal to review.', 0, '2025-12-26 19:34:34', 'info'),
(19, '2@mail.com', 'Update on \'Rejected HOD\': Your proposal has been recommended.', 0, '2025-12-26 19:35:11', 'info'),
(20, '2@mail.com', 'Final Decision: Your proposal \'Rejected HOD\' has been REJECTED by the Head of Department.', 0, '2025-12-26 19:35:23', 'info'),
(21, '4@mail.com', 'New Proposal Submitted: \'Rejected HOD\' by 2@mail.com', 0, '2025-12-26 19:35:28', 'info'),
(22, '4@mail.com', 'Appeal Request: Researcher (2@mail.com) has appealed the rejection of \'Appeal\'.', 0, '2025-12-26 19:35:33', 'info'),
(23, '4@mail.com', 'Appeal Request: Researcher (2@mail.com) has appealed the rejection of \'Appeal\'.', 0, '2025-12-26 19:42:17', 'info'),
(24, '4@mail.com', 'Appeal Request: Researcher (2@mail.com) has appealed the rejection of \'Hello World\'.', 0, '2025-12-26 19:42:20', 'info'),
(25, '1@mail.com', 'New Assignment: You have been assigned a proposal.', 0, '2025-12-26 19:43:57', 'info'),
(26, '1@mail.com', 'Appeal Case: You have been assigned to review proposal #1.', 0, '2025-12-26 19:44:00', 'info'),
(27, '1@mail.com', 'Appeal Case: You have been assigned to review proposal #4.', 0, '2025-12-26 19:44:04', 'info'),
(28, '2@mail.com', 'Update on \'Hello World\': Your proposal has been rejected.', 0, '2025-12-26 19:44:27', 'info'),
(29, '4@mail.com', 'Appeal Request: Researcher (2@mail.com) has appealed the rejection of \'Hello World\'.', 0, '2025-12-26 19:44:35', 'info'),
(30, '4@mail.com', 'Appeal Request: Researcher (2@mail.com) has appealed the rejection of \'Hello World\'.', 0, '2025-12-26 19:44:41', 'info'),
(31, '4@mail.com', 'Appeal Request: Researcher (2@mail.com) has appealed the rejection of \'Hello World\'.', 0, '2025-12-26 19:47:47', 'info'),
(32, '2@mail.com', 'Update on \'Appeal\': Your proposal has been rejected.', 0, '2025-12-26 19:48:01', 'info'),
(33, '4@mail.com', 'Appeal Request: Researcher (2@mail.com) has appealed the rejection of \'Hello World\'.', 0, '2025-12-26 19:48:07', 'info'),
(34, '4@mail.com', 'Appeal Request: Researcher (2@mail.com) has appealed the rejection of \'Appeal\'.', 0, '2025-12-26 19:48:11', 'info'),
(35, '1@mail.com', 'Appeal Case: You have been assigned to review proposal #1.', 0, '2025-12-26 19:48:18', 'info'),
(36, '2@mail.com', 'Update on \'Hello World\': Your proposal has been recommended.', 0, '2025-12-26 19:48:24', 'info'),
(37, '4@mail.com', 'Appeal Request: Researcher (2@mail.com) has appealed the rejection of \'Appeal\'.', 0, '2025-12-26 19:48:35', 'info'),
(38, '1@mail.com', 'Appeal Case: You have been assigned to review proposal #4.', 0, '2025-12-26 19:48:44', 'info'),
(39, '2@mail.com', 'Update on \'Appeal\': Your proposal has been rejected.', 0, '2025-12-26 19:48:50', 'info'),
(40, '4@mail.com', 'Appeal Request: Researcher (2@mail.com) has appealed the rejection of \'Appeal\'.', 0, '2025-12-26 19:48:54', 'info'),
(41, '4@mail.com', 'Appeal Request: Researcher (2@mail.com) has appealed the rejection of \'Appeal\'.', 0, '2025-12-26 19:48:58', 'info'),
(42, '4@mail.com', 'Appeal Request: Researcher (2@mail.com) has appealed the rejection of \'Appeal\'.', 0, '2025-12-26 19:50:26', 'info'),
(43, '1@mail.com', 'Appeal Case: You have been assigned to review proposal #4.', 0, '2025-12-26 19:51:01', 'info'),
(44, '4@mail.com', 'Appeal Request: Researcher (2@mail.com) has appealed the rejection of \'Appeal\'.', 0, '2025-12-26 19:51:24', 'info'),
(45, '1@mail.com', 'Appeal Case: You have been assigned to review proposal #1.', 0, '2025-12-26 19:51:33', 'info'),
(46, '2@mail.com', 'Update on \'Appeal\': Your proposal status is now appeal_rejected.', 0, '2025-12-26 19:52:38', 'info'),
(47, '4@mail.com', 'Appeal Request: Researcher (2@mail.com) has appealed the rejection of \'Appeal\'.', 0, '2025-12-26 19:52:45', 'info'),
(48, '2@mail.com', 'Update on \'Hello World\': Your proposal status is now appeal_rejected.', 0, '2025-12-26 19:52:58', 'info'),
(49, '4@mail.com', 'Appeal Request: Researcher (2@mail.com) has appealed the rejection of \'Appeal\'.', 0, '2025-12-26 19:53:06', 'info');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
