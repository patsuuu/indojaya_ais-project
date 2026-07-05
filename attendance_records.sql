-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 29, 2026 at 02:50 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `attendance_records`
--

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` int(11) NOT NULL,
  `bio_id` varchar(50) NOT NULL,
  `gmail` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `department` varchar(100) NOT NULL,
  `account_stage` varchar(100) NOT NULL,
  `account` varchar(100) NOT NULL,
  `team_leader` varchar(100) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `payment_balance` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `bio_id`, `gmail`, `last_name`, `first_name`, `department`, `account_stage`, `account`, `team_leader`, `is_active`, `payment_balance`, `created_at`, `updated_at`) VALUES
(2, '111', 'spatag14@gmail.com', 'PATAG', 'SIMON', 'Collection', 'Trainee', 'N/A', 'N/A', 1, 1833.00, '2026-06-26 00:36:53', '2026-06-28 02:59:47'),
(3, '222', 'simonapayorpatag12@gmail.com', 'PATAG', 'SIMON', 'Collection', 'S2', 'S2-CTKFL-25', 'ARLAN', 1, 1222.00, '2026-06-27 03:41:35', '2026-06-28 03:03:48'),
(4, '333', 'simonapayorpatag12@gmail.com', 'PATAG', 'SIMON', 'Collection', 'S3', 'S3-CTKFL-33', 'FATIMA', 1, 0.00, '2026-06-28 00:18:29', '2026-06-28 00:18:29');

-- --------------------------------------------------------

--
-- Table structure for table `hr_users`
--

CREATE TABLE `hr_users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `role` varchar(20) NOT NULL DEFAULT 'HR',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hr_users`
--

INSERT INTO `hr_users` (`id`, `username`, `password`, `email`, `role`, `created_at`) VALUES
(1, 'hr', '$2y$10$lzNLrMkTUaEOptFc0KGzhemQIoE8gp4hihnDJvJJeM3JeDiakgTVK', 'hr@example.com', 'HR', '2026-06-27 11:51:31');

-- --------------------------------------------------------

--
-- Table structure for table `records`
--

CREATE TABLE `records` (
  `id` int(11) NOT NULL,
  `bio_id` varchar(50) NOT NULL,
  `gmail` varchar(100) NOT NULL,
  `date` date NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `time_in` datetime DEFAULT NULL,
  `time_out` datetime DEFAULT NULL,
  `department` varchar(100) NOT NULL,
  `account_stage` varchar(100) NOT NULL,
  `account` varchar(100) NOT NULL,
  `team_leader` varchar(100) NOT NULL,
  `direction` varchar(10) NOT NULL,
  `photo` longtext DEFAULT NULL,
  `late_in_minutes` int(11) DEFAULT 0,
  `late_out_minutes` int(11) DEFAULT 0,
  `status` varchar(50) DEFAULT 'incomplete_no_timeout',
  `payslip_file` varchar(255) DEFAULT NULL,
  `payslip_data` text DEFAULT NULL,
  `ot_hours` decimal(5,2) NOT NULL DEFAULT 0.00,
  `ot_rate` decimal(7,2) NOT NULL DEFAULT 0.00,
  `ot_pay` decimal(10,2) NOT NULL DEFAULT 0.00,
  `ot_requested` tinyint(1) NOT NULL DEFAULT 0,
  `ot_reason` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `records`
--

INSERT INTO `records` (`id`, `bio_id`, `gmail`, `date`, `last_name`, `first_name`, `time_in`, `time_out`, `department`, `account_stage`, `account`, `team_leader`, `direction`, `photo`, `late_in_minutes`, `late_out_minutes`, `status`, `payslip_file`, `payslip_data`, `ot_hours`, `ot_rate`, `ot_pay`, `ot_requested`, `ot_reason`) VALUES
(84, '222', 'simonapayorpatag12@gmail.com', '2026-06-12', 'PATAG', 'SIMON', '2026-06-12 08:23:24', '2026-06-12 18:23:43', 'Collection', 'S2', 'S2-CTKFL-25', 'ARLAN', 'OUT', 'photo_20260612122342_6a2bde2e4c4ba.png', 0, 23, 'incomplete_no_timeout', NULL, NULL, 0.00, 0.00, 0.00, 0, NULL),
(86, '222', 'simonapayorpatag12@gmail.com', '2026-06-13', 'PATAG', 'SIMON', '2026-06-13 08:25:11', '2026-06-13 18:25:58', 'Collection', 'S2', 'S2-CTKFL-25', 'ARLAN', 'OUT', 'photo_20260613122557_6a2d30351343a.png', 0, 25, 'incomplete_no_timeout', 'payslip_222_20260627151652_6a3fcd449a224.jpg', NULL, 0.00, 0.00, 0.00, 0, NULL),
(87, '222', 'simonapayorpatag12@gmail.com', '2026-06-11', 'PATAG', 'SIMON', '2026-06-11 08:23:09', '2026-06-11 18:19:44', 'Collection', 'S2', 'S2-CTKFL-25', 'ARLAN', 'OUT', 'photo_20260611121943_6a2a8bbf99226.png', 0, 19, 'incomplete_no_timeout', NULL, NULL, 0.00, 0.00, 0.00, 0, NULL),
(88, '111', 'spatag14@gmail.com', '2026-06-27', 'PATAG', 'SIMON', '2026-06-27 21:12:50', '2026-06-27 21:13:05', 'Collection', 'Trainee', 'N/A', 'N/A', 'OUT', 'photo_20260627151304_6a3fcc60ac5b6.png', 762, 193, 'incomplete_no_timeout', 'payslip_111_20260627151439_6a3fccbf5d963.png', NULL, 0.00, 0.00, 0.00, 0, NULL),
(91, '111', 'spatag14@gmail.com', '2026-05-27', 'PATAG', 'SIMON', NULL, NULL, 'Collection', 'Trainee', 'N/A', 'N/A', 'HOLIDAY_OF', 'photo_20260628045946_6a408e223d8e9.png', 0, 0, 'holiday_off', NULL, NULL, 0.00, 0.00, 0.00, 0, NULL),
(92, '222', 'simonapayorpatag12@gmail.com', '2026-05-27', 'PATAG', 'SIMON', NULL, NULL, 'Collection', 'S2', 'S2-CTKFL-25', 'ARLAN', 'HOLIDAY_OF', 'photo_20260628050118_6a408e7ecd237.png', 0, 0, 'holiday_off', NULL, NULL, 0.00, 0.00, 0.00, 0, NULL),
(93, '222', 'simonapayorpatag12@gmail.com', '2026-08-31', 'PATAG', 'SIMON', NULL, NULL, 'Collection', 'S2', 'S2-CTKFL-25', 'ARLAN', 'HOLIDAY_OF', 'photo_20260628050347_6a408f13767ad.png', 0, 0, 'holiday_off', NULL, '{\"basic_salary\":\"1241\",\"basic_salary_days\":\"141\",\"overtime\":\"1241\",\"overtime_days\":\"12421\",\"legal_holiday\":\"121\",\"legal_holiday_days\":\"124124141\",\"legal_holiday_ot\":\"124\",\"legal_holiday_ot_days\":\"12412\",\"special_holiday_30\":\"0\",\"special_holiday_30_days\":\"0\",\"special_holiday_ot\":\"0\",\"special_holiday_ot_days\":\"0\",\"weekend_ot\":\"0\",\"weekend_ot_days\":\"0\",\"performance_bonus\":\"14000\",\"performance_bonus_days\":\"1\",\"adjustments\":\"241\",\"adjustments_days\":\"412\",\"allowance\":\"4141\",\"allowance_days\":\"1241\",\"internet_loan_allowance\":\"4124\",\"internet_loan_allowance_days\":\"4124\",\"total_earnings\":\"100000000000000\",\"sss\":\"141\",\"phic\":\"424\",\"hdmf\":\"2\",\"tax\":\"0\",\"sss_loan\":\"41\",\"pagibig_loan\":\"0\",\"late_ut\":\"0\",\"net_pay\":\"40000001241\",\"account_number\":\"1214151\",\"bank_name\":\"gasgagag\"}', 0.00, 0.00, 0.00, 0, NULL),
(96, '333', 'simonapayorpatag12@gmail.com', '2026-06-28', 'PATAG', 'SIMON', '2026-06-28 08:16:41', '2026-06-28 19:03:20', 'Collection', 'S3', 'S3-CTKFL-33', 'FATIMA', 'OUT', 'photo_20260628130319_6a40ff77349a2.png', 0, 63, 'incomplete_no_timeout', NULL, NULL, 0.50, 25.00, 12.50, 1, 'sfsa'),
(97, '333', 'simonapayorpatag12@gmail.com', '2026-06-29', 'PATAG', 'SIMON', '2026-06-29 07:25:38', '2026-06-29 07:31:40', 'Collection', 'S3', 'S3-CTKFL-33', 'FATIMA', 'OUT', 'photo_20260629013139_6a41aedb69024.png', 0, -628, 'incomplete_no_timeout', NULL, NULL, 2.00, 95.00, 190.00, 1, 'fsafa');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `role` varchar(20) DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `role`, `created_at`, `updated_at`, `last_login`, `is_active`) VALUES
(3, 'admin', '$2y$10$cmArR66UNiLU83BmSgMyTOiGTPTZ/Ugsl.k.e5Dw8eXKLxXOb2gpi', 'simon@gmail.com', 'ADMIN', '2026-02-22 07:25:14', '2026-06-27 12:54:39', NULL, 1),
(4, 'admin1', '$2y$10$pKgSZUu1T5H0dF9xHtEpU.jY9o7yq9EUQF9UXa9JCBtHimMX/R892', 'fsa@gmail.com', 'ADMIN', '2026-02-22 07:48:22', '2026-06-27 12:55:34', NULL, 1),
(5, 'admin2', '$2y$10$PBcMHZzn1YqUhVIc6lj1ruiiucPBwBUbQ.wz8IxfUI8HoAZJ2aYvi', 'fsas@gmail.com', 'ADMIN', '2026-02-22 07:49:45', '2026-06-27 12:55:39', NULL, 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `bio_id` (`bio_id`);

--
-- Indexes for table `hr_users`
--
ALTER TABLE `hr_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `records`
--
ALTER TABLE `records`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `hr_users`
--
ALTER TABLE `hr_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `records`
--
ALTER TABLE `records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=98;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
