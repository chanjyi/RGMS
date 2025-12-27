-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 27, 2025 at 09:48 AM
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
-- Table structure for table `proposals`
--

CREATE TABLE `proposals` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `researcher_email` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `reviewer_email` varchar(255) DEFAULT NULL,
  `status` enum('SUBMITTED','ASSIGNED','RECOMMENDED','APPROVED','REJECTED','APPEALED','APPEAL_REJECTED') DEFAULT 'SUBMITTED',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `priority` enum('Normal','High') DEFAULT 'Normal'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `proposals`
--

INSERT INTO `proposals` (`id`, `title`, `researcher_email`, `file_path`, `reviewer_email`, `status`, `created_at`, `priority`) VALUES
(1, 'Hello World', '2@mail.com', 'uploads/prop_1766775766_2_mail_com.pdf', NULL, 'APPEAL_REJECTED', '2025-12-26 19:02:46', 'Normal'),
(2, 'Approval', '2@mail.com', 'uploads/prop_1766776144_2_mail_com.pdf', NULL, 'APPROVED', '2025-12-26 19:09:04', 'Normal'),
(3, 'Recommended', '2@mail.com', 'uploads/prop_1766776494_2_mail_com.pdf', NULL, 'APPROVED', '2025-12-26 19:14:54', 'Normal'),
(4, 'Appeal', '2@mail.com', 'uploads/prop_1766777479_2_mail_com.pdf', NULL, 'APPEALED', '2025-12-26 19:31:19', 'Normal'),
(5, 'Rejected HOD', '2@mail.com', 'uploads/prop_1766777657_2_mail_com.pdf', NULL, 'REJECTED', '2025-12-26 19:34:17', 'Normal'),
(6, 'Rejected HOD', '2@mail.com', 'uploads/prop_1766777728_2_mail_com.pdf', NULL, 'ASSIGNED', '2025-12-26 19:35:28', 'Normal');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `proposals`
--
ALTER TABLE `proposals`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `proposals`
--
ALTER TABLE `proposals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
