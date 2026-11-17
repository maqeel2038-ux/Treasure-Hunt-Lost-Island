-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 10, 2026 at 06:13 AM
-- Server version: 10.11.16-MariaDB
-- PHP Version: 8.3.36

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `treasure_hunt`
--

-- --------------------------------------------------------

--
-- Table structure for table `players`
--

CREATE TABLE `players` (
  `player_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `coins` int(11) DEFAULT 0,
  `score` int(11) DEFAULT 0,
  `energy` int(11) DEFAULT 100,
  `current_location` varchar(50) DEFAULT 'Beach',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `players`
--

INSERT INTO `players` (`player_id`, `username`, `email`, `password`, `coins`, `score`, `energy`, `current_location`, `created_at`) VALUES
(1, 'AqeelPlayer', 'aqeeltest@gmail.com', '$2y$10$63c40enuyJYpvukDtHWbpu42dbD2Ohu3os53fvOg8d5fDrUIDmPPO', 120, 1200, 80, 'Beach', '2026-11-10 03:51:59'),
(2, 'aqeelo', 'asfffff@gmail.com', '$2y$10$GY3sEOZ7rirv.Lh6H/Rdn.JS1tbAXMk1R76Lf33yYUpLVySNOLlfS', 0, 0, 100, 'Beach', '2026-11-10 04:25:53');

-- --------------------------------------------------------

--
-- Table structure for table `player_challenges`
--

CREATE TABLE `player_challenges` (
  `id` int(11) NOT NULL,
  `player_id` int(11) NOT NULL,
  `location` varchar(50) NOT NULL,
  `completed_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `player_challenges`
--

INSERT INTO `player_challenges` (`id`, `player_id`, `location`, `completed_at`) VALUES
(1, 1, 'Beach', '2026-11-10 04:14:01');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `players`
--
ALTER TABLE `players`
  ADD PRIMARY KEY (`player_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `player_challenges`
--
ALTER TABLE `player_challenges`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_player_challenge` (`player_id`,`location`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `players`
--
ALTER TABLE `players`
  MODIFY `player_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `player_challenges`
--
ALTER TABLE `player_challenges`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `player_challenges`
--
ALTER TABLE `player_challenges`
  ADD CONSTRAINT `player_challenges_ibfk_1` FOREIGN KEY (`player_id`) REFERENCES `players` (`player_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
