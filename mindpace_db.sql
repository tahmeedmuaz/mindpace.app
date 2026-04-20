-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 20, 2026 at 03:08 PM
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
-- Database: `mindpace_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `achievement`
--

CREATE TABLE `achievement` (
  `achv_id` int(11) NOT NULL,
  `badge_name` varchar(100) NOT NULL,
  `required_hours` decimal(5,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `log_date` date DEFAULT NULL,
  `log_type` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `burnout_alert`
--

CREATE TABLE `burnout_alert` (
  `alert_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `risk_level` int(11) DEFAULT NULL,
  `alert_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `department`
--

CREATE TABLE `department` (
  `dept_id` int(11) NOT NULL,
  `dept_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `department`
--

INSERT INTO `department` (`dept_id`, `dept_name`) VALUES
(1, 'Computer Science'),
(2, 'Psychology');

-- --------------------------------------------------------

--
-- Table structure for table `group_member`
--

CREATE TABLE `group_member` (
  `user_id` int(11) NOT NULL,
  `grp_id` int(11) NOT NULL,
  `joined_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `intervention`
--

CREATE TABLE `intervention` (
  `intervention_id` int(11) NOT NULL,
  `alert_id` int(11) DEFAULT NULL,
  `meeting_date` date DEFAULT NULL,
  `resolution_status` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `study_group`
--

CREATE TABLE `study_group` (
  `grp_id` int(11) NOT NULL,
  `group_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `study_session`
--

CREATE TABLE `study_session` (
  `log_id` int(11) NOT NULL,
  `subj_id` int(11) DEFAULT NULL,
  `start_time` datetime DEFAULT NULL,
  `end_time` datetime DEFAULT NULL,
  `focus_rating` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subject`
--

CREATE TABLE `subject` (
  `subj_id` int(11) NOT NULL,
  `subj_name` varchar(100) NOT NULL,
  `dept_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subject`
--

INSERT INTO `subject` (`subj_id`, `subj_name`, `dept_id`) VALUES
(1, 'Database Systems', 1),
(2, 'Web Development', 1);

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `user_type` varchar(20) NOT NULL,
  `counsellor_id` int(11) DEFAULT NULL,
  `mentor_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`user_id`, `username`, `email`, `user_type`, `counsellor_id`, `mentor_id`) VALUES
(1, 'admin_dave', 'dave@admin.edu', 'admin', NULL, NULL),
(2, 'counselor_alice', 'alice@staff.edu', 'counsellor', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_achievement`
--

CREATE TABLE `user_achievement` (
  `user_id` int(11) NOT NULL,
  `achv_id` int(11) NOT NULL,
  `date_earned` date DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wellness_log`
--

CREATE TABLE `wellness_log` (
  `log_id` int(11) NOT NULL,
  `sleep_hours` decimal(4,2) DEFAULT NULL,
  `stress_level` int(11) DEFAULT NULL,
  `mood_score` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `achievement`
--
ALTER TABLE `achievement`
  ADD PRIMARY KEY (`achv_id`);

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `burnout_alert`
--
ALTER TABLE `burnout_alert`
  ADD PRIMARY KEY (`alert_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `department`
--
ALTER TABLE `department`
  ADD PRIMARY KEY (`dept_id`);

--
-- Indexes for table `group_member`
--
ALTER TABLE `group_member`
  ADD PRIMARY KEY (`user_id`,`grp_id`),
  ADD KEY `grp_id` (`grp_id`);

--
-- Indexes for table `intervention`
--
ALTER TABLE `intervention`
  ADD PRIMARY KEY (`intervention_id`),
  ADD KEY `alert_id` (`alert_id`);

--
-- Indexes for table `study_group`
--
ALTER TABLE `study_group`
  ADD PRIMARY KEY (`grp_id`);

--
-- Indexes for table `study_session`
--
ALTER TABLE `study_session`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `subj_id` (`subj_id`);

--
-- Indexes for table `subject`
--
ALTER TABLE `subject`
  ADD PRIMARY KEY (`subj_id`),
  ADD KEY `dept_id` (`dept_id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `counsellor_id` (`counsellor_id`),
  ADD KEY `mentor_id` (`mentor_id`);

--
-- Indexes for table `user_achievement`
--
ALTER TABLE `user_achievement`
  ADD PRIMARY KEY (`user_id`,`achv_id`),
  ADD KEY `achv_id` (`achv_id`);

--
-- Indexes for table `wellness_log`
--
ALTER TABLE `wellness_log`
  ADD PRIMARY KEY (`log_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `achievement`
--
ALTER TABLE `achievement`
  MODIFY `achv_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `burnout_alert`
--
ALTER TABLE `burnout_alert`
  MODIFY `alert_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `department`
--
ALTER TABLE `department`
  MODIFY `dept_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `intervention`
--
ALTER TABLE `intervention`
  MODIFY `intervention_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `study_group`
--
ALTER TABLE `study_group`
  MODIFY `grp_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subject`
--
ALTER TABLE `subject`
  MODIFY `subj_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD CONSTRAINT `activity_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`);

--
-- Constraints for table `burnout_alert`
--
ALTER TABLE `burnout_alert`
  ADD CONSTRAINT `burnout_alert_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`);

--
-- Constraints for table `group_member`
--
ALTER TABLE `group_member`
  ADD CONSTRAINT `group_member_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`),
  ADD CONSTRAINT `group_member_ibfk_2` FOREIGN KEY (`grp_id`) REFERENCES `study_group` (`grp_id`);

--
-- Constraints for table `intervention`
--
ALTER TABLE `intervention`
  ADD CONSTRAINT `intervention_ibfk_1` FOREIGN KEY (`alert_id`) REFERENCES `burnout_alert` (`alert_id`);

--
-- Constraints for table `study_session`
--
ALTER TABLE `study_session`
  ADD CONSTRAINT `study_session_ibfk_1` FOREIGN KEY (`log_id`) REFERENCES `activity_log` (`log_id`),
  ADD CONSTRAINT `study_session_ibfk_2` FOREIGN KEY (`subj_id`) REFERENCES `subject` (`subj_id`);

--
-- Constraints for table `subject`
--
ALTER TABLE `subject`
  ADD CONSTRAINT `subject_ibfk_1` FOREIGN KEY (`dept_id`) REFERENCES `department` (`dept_id`);

--
-- Constraints for table `user`
--
ALTER TABLE `user`
  ADD CONSTRAINT `user_ibfk_1` FOREIGN KEY (`counsellor_id`) REFERENCES `user` (`user_id`),
  ADD CONSTRAINT `user_ibfk_2` FOREIGN KEY (`mentor_id`) REFERENCES `user` (`user_id`);

--
-- Constraints for table `user_achievement`
--
ALTER TABLE `user_achievement`
  ADD CONSTRAINT `user_achievement_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`),
  ADD CONSTRAINT `user_achievement_ibfk_2` FOREIGN KEY (`achv_id`) REFERENCES `achievement` (`achv_id`);

--
-- Constraints for table `wellness_log`
--
ALTER TABLE `wellness_log`
  ADD CONSTRAINT `wellness_log_ibfk_1` FOREIGN KEY (`log_id`) REFERENCES `activity_log` (`log_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
