-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 17, 2026 at 01:23 PM
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
-- Database: `blagoustroistvo_belovo`
--

-- --------------------------------------------------------

--
-- Table structure for table `objects`
--

CREATE TABLE `objects` (
  `id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `type` varchar(100) DEFAULT NULL,
  `address` varchar(200) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'активен',
  `responsible` varchar(100) DEFAULT NULL,
  `created_at` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `objects`
--

INSERT INTO `objects` (`id`, `name`, `type`, `address`, `status`, `responsible`, `created_at`) VALUES
(1, 'Парк Горького', 'парк', 'ул. Ленина, 1', 'активен', 'Иванов И.И.', '2023-01-15'),
(2, 'Сквер Школьный', 'сквер', 'ул. Школьная, 10', 'активен', 'Петров П.П.', '2023-02-20'),
(3, 'Набережная', 'набережная', 'ул. Речная', 'активен', 'Сидоров С.С.', '2023-03-10'),
(4, 'Детская площадка', 'площадка', 'ул. Мира, 5', 'на реконструкции', 'Козлова А.С.', '2023-04-05');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `login` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(150) DEFAULT NULL,
  `role` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `login`, `password`, `full_name`, `role`) VALUES
(1, 'admin', 'admin123', 'Администратор', 'администратор'),
(2, 'planner', 'plan123', 'Планировщик', 'планировщик'),
(3, 'worker', 'work123', 'Рабочий', 'исполнитель');

-- --------------------------------------------------------

--
-- Table structure for table `work_executions`
--

CREATE TABLE `work_executions` (
  `id` int(11) NOT NULL,
  `plan_id` int(11) DEFAULT NULL,
  `object_id` int(11) NOT NULL,
  `work_type` varchar(100) NOT NULL,
  `date_performed` date NOT NULL,
  `result` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `responsible` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `work_executions`
--

INSERT INTO `work_executions` (`id`, `plan_id`, `object_id`, `work_type`, `date_performed`, `result`, `description`, `responsible`) VALUES
(1, 3, 2, 'уборка мусора', '2025-05-21', 'выполнено', 'Убрано 5 мешков мусора', 'Бригада №3'),
(2, NULL, 1, 'покраска скамеек', '2025-04-15', 'выполнено', 'Покрашены 3 скамейки', 'Бригада №2');

-- --------------------------------------------------------

--
-- Table structure for table `work_plans`
--

CREATE TABLE `work_plans` (
  `id` int(11) NOT NULL,
  `object_id` int(11) NOT NULL,
  `work_type` varchar(100) NOT NULL,
  `planned_start` date NOT NULL,
  `planned_end` date NOT NULL,
  `responsible` varchar(100) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'запланировано',
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `work_plans`
--

INSERT INTO `work_plans` (`id`, `object_id`, `work_type`, `planned_start`, `planned_end`, `responsible`, `status`, `description`) VALUES
(1, 1, 'покос травы', '2025-06-01', '2025-06-15', 'Бригада №1', 'запланировано', 'Покос травы в парке'),
(2, 1, 'ремонт лавочек', '2025-07-01', '2025-07-10', 'Бригада №2', 'запланировано', 'Замена досок на лавочках'),
(3, 2, 'уборка мусора', '2025-05-20', '2025-05-21', 'Бригада №3', 'выполнено', 'Ежедневная уборка'),
(4, 3, 'обрезка деревьев', '2025-04-10', '2025-04-15', 'Бригада №1', 'в работе', 'Санитарная обрезка'),
(5, 4, 'ремонт качелей', '2025-05-01', '2025-05-05', 'Бригада №2', 'просрочено', 'Замена подвесов');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `objects`
--
ALTER TABLE `objects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_name` (`name`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `login` (`login`);

--
-- Indexes for table `work_executions`
--
ALTER TABLE `work_executions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `object_id` (`object_id`),
  ADD KEY `plan_id` (`plan_id`);

--
-- Indexes for table `work_plans`
--
ALTER TABLE `work_plans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `object_id` (`object_id`),
  ADD KEY `idx_planned_start` (`planned_start`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `objects`
--
ALTER TABLE `objects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `work_executions`
--
ALTER TABLE `work_executions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `work_plans`
--
ALTER TABLE `work_plans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `work_executions`
--
ALTER TABLE `work_executions`
  ADD CONSTRAINT `work_executions_ibfk_1` FOREIGN KEY (`object_id`) REFERENCES `objects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `work_executions_ibfk_2` FOREIGN KEY (`plan_id`) REFERENCES `work_plans` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `work_plans`
--
ALTER TABLE `work_plans`
  ADD CONSTRAINT `work_plans_ibfk_1` FOREIGN KEY (`object_id`) REFERENCES `objects` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
