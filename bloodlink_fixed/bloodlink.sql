-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 13, 2026 at 06:23 AM
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
-- Database: `bloodlink`
--

-- --------------------------------------------------------

--
-- Table structure for table `bl_attendance_log`
--

CREATE TABLE `bl_attendance_log` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `officer_id` int(11) NOT NULL,
  `action` enum('approved','rejected') NOT NULL,
  `reject_reason` enum('noshow','health') DEFAULT NULL,
  `action_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bl_attendance_log`
--

INSERT INTO `bl_attendance_log` (`id`, `booking_id`, `officer_id`, `action`, `reject_reason`, `action_at`) VALUES
(1, 1, 1, 'approved', NULL, '2026-04-11 03:59:13'),
(2, 3, 1, 'approved', NULL, '2026-04-11 04:03:48'),
(3, 1, 1, 'approved', NULL, '2026-04-11 09:02:37');

-- --------------------------------------------------------

--
-- Table structure for table `bl_bookings`
--

CREATE TABLE `bl_bookings` (
  `id` int(11) NOT NULL,
  `slot_id` int(11) NOT NULL,
  `donor_id` int(11) NOT NULL,
  `donor_name` varchar(255) NOT NULL,
  `ic_number` varchar(20) NOT NULL,
  `blood_type` varchar(5) NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `reject_reason` enum('noshow','health') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bl_bookings`
--

INSERT INTO `bl_bookings` (`id`, `slot_id`, `donor_id`, `donor_name`, `ic_number`, `blood_type`, `status`, `reject_reason`, `created_at`) VALUES
(1, 3, 2, 'Ahmad Donor', '990101-01-1234', 'A+', 'pending', NULL, '2026-04-11 03:58:43'),
(3, 5, 2, 'Ahmad Donor', '990101-01-1234', 'A+', 'approved', NULL, '2026-04-11 04:03:33');

-- --------------------------------------------------------

--
-- Table structure for table `bl_donation_records`
--

CREATE TABLE `bl_donation_records` (
  `id` int(11) NOT NULL,
  `record_id` varchar(20) NOT NULL,
  `donor_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `blood_type` enum('A+','A-','B+','B-','O+','O-','AB+','AB-') NOT NULL,
  `volume_ml` int(11) NOT NULL DEFAULT 450,
  `donation_date` date NOT NULL,
  `blood_pressure` varchar(30) DEFAULT NULL,
  `haemoglobin` decimal(4,1) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `status` enum('Pending','Verified','Rejected') DEFAULT 'Pending',
  `recorded_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bl_donation_records`
--

INSERT INTO `bl_donation_records` (`id`, `record_id`, `donor_id`, `event_id`, `blood_type`, `volume_ml`, `donation_date`, `blood_pressure`, `haemoglobin`, `remarks`, `status`, `recorded_by`, `created_at`, `updated_at`) VALUES
(1, 'DR-0041', 1, 1, 'A+', 450, '2026-03-22', '118/76 mmHg', 14.2, NULL, 'Verified', 'Dr. Siti Aminah', '2026-04-13 03:59:43', '2026-04-13 03:59:43'),
(2, 'DR-0040', 2, 1, 'O+', 450, '2026-03-22', '120/80 mmHg', 13.8, NULL, 'Verified', 'Dr. Siti Aminah', '2026-04-13 03:59:43', '2026-04-13 03:59:43'),
(3, 'DR-0039', 3, 2, 'B+', 350, '2026-04-05', '115/75 mmHg', 13.1, NULL, 'Pending', 'Dr. Siti Aminah', '2026-04-13 03:59:43', '2026-04-13 03:59:43'),
(4, 'DR-0038', 4, 3, 'AB+', 450, '2026-02-28', '122/82 mmHg', 14.5, NULL, 'Verified', 'Dr. Siti Aminah', '2026-04-13 03:59:43', '2026-04-13 03:59:43'),
(5, 'DR-0037', 5, 3, 'O-', 450, '2026-02-28', '119/78 mmHg', 13.9, NULL, 'Verified', 'Dr. Siti Aminah', '2026-04-13 03:59:43', '2026-04-13 03:59:43');

-- --------------------------------------------------------

--
-- Table structure for table `bl_donors`
--

CREATE TABLE `bl_donors` (
  `id` int(11) NOT NULL,
  `donor_id` varchar(20) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `ic_number` varchar(20) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `blood_type` enum('A+','A-','B+','B-','O+','O-','AB+','AB-') NOT NULL,
  `health_status` enum('Healthy','Under Medication','Not Eligible') DEFAULT 'Healthy',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bl_donors`
--

INSERT INTO `bl_donors` (`id`, `donor_id`, `first_name`, `last_name`, `ic_number`, `phone`, `blood_type`, `health_status`, `created_at`) VALUES
(1, 'D-0021', 'Ahmad Haziq', 'bin Roslan', '980214-03-5671', '019-3821044', 'A+', 'Healthy', '2026-04-13 03:59:07'),
(2, 'D-0019', 'Nurul Fatihah', 'binti Hassan', '001203-03-4521', '011-2345678', 'O+', 'Healthy', '2026-04-13 03:59:07'),
(3, 'D-0015', 'Muhammad Razif', 'bin Zain', '990506-08-1234', '017-8765432', 'B+', 'Healthy', '2026-04-13 03:59:07'),
(4, 'D-0012', 'Siti Khadijah', 'binti Ali', '970312-05-9876', '013-5556789', 'AB+', 'Healthy', '2026-04-13 03:59:07'),
(5, 'D-0009', 'Farid Khairul', 'bin Ismail', '950820-07-1122', '016-9876543', 'O-', 'Healthy', '2026-04-13 03:59:07'),
(6, 'D-0022', 'Ahmad', 'Donor', '990101-01-1234', '012-3456789', 'A+', 'Healthy', '2026-04-14 00:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `bl_events`
--

CREATE TABLE `bl_events` (
  `id` int(11) NOT NULL,
  `event_id` varchar(20) NOT NULL,
  `event_name` varchar(200) NOT NULL,
  `event_date` date NOT NULL,
  `location` varchar(200) DEFAULT NULL,
  `partner` varchar(200) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('Upcoming','Completed','Cancelled') DEFAULT 'Upcoming',
  `created_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bl_events`
--

INSERT INTO `bl_events` (`id`, `event_id`, `event_name`, `event_date`, `location`, `partner`, `description`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'EV-0001', 'Hospital Putra Drive', '2026-03-22', 'Hospital Putra, Kota Bharu', 'Hospital Putra Kota Bharu', 'Annual blood donation drive hosted at Hospital Putra. Open to all eligible donors.', 'Upcoming', 'Dr. Siti Aminah', '2026-04-11 08:32:00', '2026-04-11 08:32:00'),
(2, 'EV-0002', 'Community Blood Day', '2026-04-05', 'Dewan Orang Ramai, Pasir Mas', 'MPPM Pasir Mas', 'Community outreach blood donation event partnered with the local municipal council.', 'Upcoming', 'Dr. Siti Aminah', '2026-04-11 08:32:00', '2026-04-11 08:32:00'),
(3, 'EV-0003', 'Kelantan State Drive', '2026-02-28', 'Pusat Komuniti Machang', 'JKR Machang', 'State-level drive covering the Machang district.', 'Completed', 'Dr. Siti Aminah', '2026-04-11 08:32:00', '2026-04-11 08:32:00'),
(4, 'EV-0004', 'UTM Campus Donation Campaign', '2026-01-15', 'UTM Skudai, Johor', 'UTM Health Centre', 'Student and staff blood donation campaign at UTM campus.', 'Completed', 'Dr. Siti Aminah', '2026-04-11 08:32:00', '2026-04-11 08:32:00'),
(5, 'EV-0005', 'Raya Blood Drive', '2026-06-10', 'Kompleks Membeli-belah Kubang Kerian', 'Mydin Kubang Kerian', 'Special Hari Raya blood donation drive at a popular shopping complex.', 'Upcoming', 'Dr. Siti Aminah', '2026-04-11 08:32:00', '2026-04-11 08:32:00'),
(6, 'EV-0006', 'Tumpat District Outreach', '2026-05-20', 'Dewan Serbaguna Tumpat', 'Majlis Daerah Tumpat', 'Outreach program targeting underserved areas in the Tumpat district.', 'Cancelled', 'Dr. Siti Aminah', '2026-04-11 08:32:00', '2026-04-11 08:32:00');

-- --------------------------------------------------------

--
-- Table structure for table `bl_record_edit_history`
--

CREATE TABLE `bl_record_edit_history` (
  `id` int(11) NOT NULL,
  `record_id` int(11) NOT NULL,
  `edited_by` varchar(100) DEFAULT NULL,
  `edit_category` varchar(100) DEFAULT NULL,
  `edit_note` text DEFAULT NULL,
  `edited_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bl_slots`
--

CREATE TABLE `bl_slots` (
  `id` int(11) NOT NULL,
  `event_id` int(11) DEFAULT NULL,
  `event_name` varchar(255) NOT NULL,
  `event_date` date NOT NULL,
  `event_location` varchar(255) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `capacity` int(11) NOT NULL DEFAULT 5,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bl_slots`
--

INSERT INTO `bl_slots` (`id`, `event_id`, `event_name`, `event_date`, `event_location`, `start_time`, `end_time`, `capacity`, `created_at`) VALUES
(6, 1, 'Hospital Putra Drive', '2026-03-22', 'Hospital Putra, Kota Bharu', '09:00:00', '12:00:00', 10, '2026-04-11 09:10:11'),
(7, 1, 'Hospital Putra Drive', '2026-03-22', 'Hospital Putra, Kota Bharu', '14:00:00', '17:00:00', 10, '2026-04-11 09:10:31'),
(8, 2, 'Community Blood Day', '2026-04-05', 'Dewan Orang Ramai, Pasir Mas', '09:00:00', '12:00:00', 15, '2026-04-11 09:10:32'),
(9, 2, 'Community Blood Day', '2026-04-05', 'Dewan Orang Ramai, Pasir Mas', '13:00:00', '16:00:00', 15, '2026-04-11 09:10:33'),
(10, 5, 'Raya Blood Drive', '2026-06-10', 'Kompleks Membeli-belah Kubang Kerian', '10:00:00', '13:00:00', 20, '2026-04-11 09:10:34'),
(11, 5, 'Raya Blood Drive', '2026-06-10', 'Kompleks Membeli-belah Kubang Kerian', '14:00:00', '17:00:00', 20, '2026-04-11 09:10:35');

-- --------------------------------------------------------

--
-- Table structure for table `bl_users`
--

CREATE TABLE `bl_users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `role` enum('medical_officer','donor') NOT NULL,
  `blood_type` varchar(5) DEFAULT NULL,
  `ic_number` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bl_users`
--

INSERT INTO `bl_users` (`id`, `username`, `password`, `full_name`, `role`, `blood_type`, `ic_number`, `created_at`) VALUES
(1, 'drfauziah', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dr. Fauziah', 'medical_officer', NULL, NULL, '2026-04-11 03:42:44'),
(2, 'donor1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Ahmad Donor', 'donor', 'A+', '990101-01-1234', '2026-04-11 03:42:44');

-- --------------------------------------------------------

--
-- Table structure for table `bl_volunteers`
--

CREATE TABLE `bl_volunteers` (
  `id` int(11) NOT NULL,
  `volunteer_id` varchar(20) NOT NULL,
  `full_name` varchar(200) NOT NULL,
  `ic_number` varchar(20) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bl_volunteers`
--

INSERT INTO `bl_volunteers` (`id`, `volunteer_id`, `full_name`, `ic_number`, `phone`, `email`, `created_at`) VALUES
(1, 'VL-0001', 'Amirul Hakim bin Zainudin', '990312-03-1234', '019-2341234', 'amirul@email.com', '2026-04-11 08:32:00'),
(2, 'VL-0002', 'Siti Norfazilah binti Roslan', '010501-04-5678', '011-5671234', 'siti.nf@email.com', '2026-04-11 08:32:00'),
(3, 'VL-0003', 'Hazwan bin Abdul Karim', '980720-07-9012', '017-9012345', 'hazwan@email.com', '2026-04-11 08:32:00'),
(4, 'VL-0004', 'Nurul Ain binti Jamaludin', '011203-03-3456', '013-3456789', 'nurulaim@email.com', '2026-04-11 08:32:00'),
(5, 'VL-0005', 'Faizul Aizat bin Ismail', '950606-08-7890', '016-7891234', 'faizul@email.com', '2026-04-11 08:32:00');

-- --------------------------------------------------------

--
-- Table structure for table `event_volunteers`
--

CREATE TABLE `event_volunteers` (
  `id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `volunteer_id` int(11) NOT NULL,
  `role` enum('Registration Desk','Medical Assistant','Blood Collection','Refreshment','Logistics','Coordinator') NOT NULL,
  `assigned_by` varchar(100) DEFAULT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `event_volunteers`
--

INSERT INTO `event_volunteers` (`id`, `event_id`, `volunteer_id`, `role`, `assigned_by`, `assigned_at`) VALUES
(1, 1, 1, 'Registration Desk', 'Dr. Siti Aminah', '2026-04-11 08:32:00'),
(2, 1, 2, 'Medical Assistant', 'Dr. Siti Aminah', '2026-04-11 08:32:00'),
(3, 1, 3, 'Blood Collection', 'Dr. Siti Aminah', '2026-04-11 08:32:00'),
(4, 2, 1, 'Coordinator', 'Dr. Siti Aminah', '2026-04-11 08:32:00'),
(5, 2, 4, 'Refreshment', 'Dr. Siti Aminah', '2026-04-11 08:32:00'),
(6, 3, 5, 'Logistics', 'Dr. Siti Aminah', '2026-04-11 08:32:00'),
(7, 3, 2, 'Registration Desk', 'Dr. Siti Aminah', '2026-04-11 08:32:00');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bl_attendance_log`
--
ALTER TABLE `bl_attendance_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `booking_id` (`booking_id`);

--
-- Indexes for table `bl_bookings`
--
ALTER TABLE `bl_bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `slot_id` (`slot_id`);

--
-- Indexes for table `bl_donation_records`
--
ALTER TABLE `bl_donation_records`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `record_id` (`record_id`),
  ADD KEY `donor_id` (`donor_id`),
  ADD KEY `event_id` (`event_id`);

--
-- Indexes for table `bl_donors`
--
ALTER TABLE `bl_donors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `donor_id` (`donor_id`);

--
-- Indexes for table `bl_events`
--
ALTER TABLE `bl_events`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `event_id` (`event_id`);

--
-- Indexes for table `bl_record_edit_history`
--
ALTER TABLE `bl_record_edit_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `record_id` (`record_id`);

--
-- Indexes for table `bl_slots`
--
ALTER TABLE `bl_slots`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_slot_event` (`event_id`);

--
-- Indexes for table `bl_users`
--
ALTER TABLE `bl_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `bl_volunteers`
--
ALTER TABLE `bl_volunteers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `volunteer_id` (`volunteer_id`);

--
-- Indexes for table `event_volunteers`
--
ALTER TABLE `event_volunteers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_assignment` (`event_id`,`volunteer_id`),
  ADD KEY `volunteer_id` (`volunteer_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bl_attendance_log`
--
ALTER TABLE `bl_attendance_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `bl_bookings`
--
ALTER TABLE `bl_bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `bl_donation_records`
--
ALTER TABLE `bl_donation_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `bl_donors`
--
ALTER TABLE `bl_donors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `bl_events`
--
ALTER TABLE `bl_events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `bl_record_edit_history`
--
ALTER TABLE `bl_record_edit_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bl_slots`
--
ALTER TABLE `bl_slots`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `bl_users`
--
ALTER TABLE `bl_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `bl_volunteers`
--
ALTER TABLE `bl_volunteers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `event_volunteers`
--
ALTER TABLE `event_volunteers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bl_attendance_log`
--
ALTER TABLE `bl_attendance_log`
  ADD CONSTRAINT `bl_attendance_log_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bl_bookings` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `bl_bookings`
--
ALTER TABLE `bl_bookings`
  ADD CONSTRAINT `bl_bookings_ibfk_1` FOREIGN KEY (`slot_id`) REFERENCES `bl_slots` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `bl_donation_records`
--
ALTER TABLE `bl_donation_records`
  ADD CONSTRAINT `bl_donation_records_ibfk_1` FOREIGN KEY (`donor_id`) REFERENCES `bl_donors` (`id`),
  ADD CONSTRAINT `bl_donation_records_ibfk_2` FOREIGN KEY (`event_id`) REFERENCES `bl_events` (`id`);

--
-- Constraints for table `bl_record_edit_history`
--
ALTER TABLE `bl_record_edit_history`
  ADD CONSTRAINT `bl_record_edit_history_ibfk_1` FOREIGN KEY (`record_id`) REFERENCES `bl_donation_records` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `bl_slots`
--
ALTER TABLE `bl_slots`
  ADD CONSTRAINT `fk_slot_event` FOREIGN KEY (`event_id`) REFERENCES `bl_events` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `event_volunteers`
--
ALTER TABLE `event_volunteers`
  ADD CONSTRAINT `event_volunteers_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `bl_events` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `event_volunteers_ibfk_2` FOREIGN KEY (`volunteer_id`) REFERENCES `bl_volunteers` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
