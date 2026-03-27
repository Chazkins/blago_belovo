-- Резервная копия базы данных: blagoustroistvo_belovo
-- Дата создания: 2026-03-16 10:55:28

SET FOREIGN_KEY_CHECKS=0;


-- Структура таблицы `audit_log`
CREATE TABLE `audit_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `table_name` varchar(100) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `old_data` text DEFAULT NULL,
  `new_data` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Данные таблицы `audit_log`
INSERT INTO `audit_log` (`id`, `user_id`, `action`, `table_name`, `record_id`, `old_data`, `new_data`, `ip_address`, `user_agent`, `created_at`) VALUES ('1', '1', 'CLEAR_LOGS', 'audit_log', NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 OPR/127.0.0.0 (Edition Yx GX)', '2026-03-13 16:06:50');
INSERT INTO `audit_log` (`id`, `user_id`, `action`, `table_name`, `record_id`, `old_data`, `new_data`, `ip_address`, `user_agent`, `created_at`) VALUES ('2', '1', 'LOGOUT', 'users', '1', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 OPR/127.0.0.0 (Edition Yx GX)', '2026-03-13 16:07:02');
INSERT INTO `audit_log` (`id`, `user_id`, `action`, `table_name`, `record_id`, `old_data`, `new_data`, `ip_address`, `user_agent`, `created_at`) VALUES ('3', '3', 'LOGIN', 'users', '3', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 OPR/127.0.0.0 (Edition Yx GX)', '2026-03-13 16:07:13');
INSERT INTO `audit_log` (`id`, `user_id`, `action`, `table_name`, `record_id`, `old_data`, `new_data`, `ip_address`, `user_agent`, `created_at`) VALUES ('4', '3', 'LOGOUT', 'users', '3', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 OPR/127.0.0.0 (Edition Yx GX)', '2026-03-13 16:17:42');
INSERT INTO `audit_log` (`id`, `user_id`, `action`, `table_name`, `record_id`, `old_data`, `new_data`, `ip_address`, `user_agent`, `created_at`) VALUES ('5', '3', 'LOGIN', 'users', '3', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 OPR/127.0.0.0 (Edition Yx GX)', '2026-03-13 16:17:51');
INSERT INTO `audit_log` (`id`, `user_id`, `action`, `table_name`, `record_id`, `old_data`, `new_data`, `ip_address`, `user_agent`, `created_at`) VALUES ('6', '3', 'LOGOUT', 'users', '3', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 OPR/127.0.0.0 (Edition Yx GX)', '2026-03-13 16:30:26');
INSERT INTO `audit_log` (`id`, `user_id`, `action`, `table_name`, `record_id`, `old_data`, `new_data`, `ip_address`, `user_agent`, `created_at`) VALUES ('7', '1', 'LOGIN', 'users', '1', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 OPR/127.0.0.0 (Edition Yx GX)', '2026-03-13 16:30:32');
INSERT INTO `audit_log` (`id`, `user_id`, `action`, `table_name`, `record_id`, `old_data`, `new_data`, `ip_address`, `user_agent`, `created_at`) VALUES ('8', '1', 'LOGOUT', 'users', '1', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 OPR/127.0.0.0 (Edition Yx GX)', '2026-03-13 16:34:20');
INSERT INTO `audit_log` (`id`, `user_id`, `action`, `table_name`, `record_id`, `old_data`, `new_data`, `ip_address`, `user_agent`, `created_at`) VALUES ('9', '3', 'LOGIN', 'users', '3', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 OPR/127.0.0.0 (Edition Yx GX)', '2026-03-13 16:34:26');
INSERT INTO `audit_log` (`id`, `user_id`, `action`, `table_name`, `record_id`, `old_data`, `new_data`, `ip_address`, `user_agent`, `created_at`) VALUES ('10', '3', 'UPDATE_REASON', 'work_plans', '10', NULL, '{\"overdue_reason\":\"Погодные условия\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 OPR/127.0.0.0 (Edition Yx GX)', '2026-03-13 16:40:56');
INSERT INTO `audit_log` (`id`, `user_id`, `action`, `table_name`, `record_id`, `old_data`, `new_data`, `ip_address`, `user_agent`, `created_at`) VALUES ('11', '3', 'LOGOUT', 'users', '3', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 OPR/127.0.0.0 (Edition Yx GX)', '2026-03-13 17:00:24');
INSERT INTO `audit_log` (`id`, `user_id`, `action`, `table_name`, `record_id`, `old_data`, `new_data`, `ip_address`, `user_agent`, `created_at`) VALUES ('12', '1', 'LOGIN', 'users', '1', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 OPR/127.0.0.0 (Edition Yx GX)', '2026-03-13 17:00:29');
INSERT INTO `audit_log` (`id`, `user_id`, `action`, `table_name`, `record_id`, `old_data`, `new_data`, `ip_address`, `user_agent`, `created_at`) VALUES ('13', '1', 'LOGIN', 'users', '1', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36', '2026-03-13 21:25:40');
INSERT INTO `audit_log` (`id`, `user_id`, `action`, `table_name`, `record_id`, `old_data`, `new_data`, `ip_address`, `user_agent`, `created_at`) VALUES ('14', '1', 'LOGOUT', 'users', '1', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 OPR/127.0.0.0 (Edition Yx GX)', '2026-03-15 16:35:19');
INSERT INTO `audit_log` (`id`, `user_id`, `action`, `table_name`, `record_id`, `old_data`, `new_data`, `ip_address`, `user_agent`, `created_at`) VALUES ('15', '1', 'LOGIN', 'users', '1', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 OPR/127.0.0.0 (Edition Yx GX)', '2026-03-15 16:35:39');
INSERT INTO `audit_log` (`id`, `user_id`, `action`, `table_name`, `record_id`, `old_data`, `new_data`, `ip_address`, `user_agent`, `created_at`) VALUES ('16', '1', 'LOGOUT', 'users', '1', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 OPR/127.0.0.0 (Edition Yx GX)', '2026-03-15 16:36:06');
INSERT INTO `audit_log` (`id`, `user_id`, `action`, `table_name`, `record_id`, `old_data`, `new_data`, `ip_address`, `user_agent`, `created_at`) VALUES ('17', '3', 'LOGIN', 'users', '3', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 OPR/127.0.0.0 (Edition Yx GX)', '2026-03-15 16:36:16');
INSERT INTO `audit_log` (`id`, `user_id`, `action`, `table_name`, `record_id`, `old_data`, `new_data`, `ip_address`, `user_agent`, `created_at`) VALUES ('18', '3', 'UPDATE_REASON', 'work_plans', '8', NULL, '{\"overdue_reason\":\"Ну как-то лень\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 OPR/127.0.0.0 (Edition Yx GX)', '2026-03-15 16:36:45');
INSERT INTO `audit_log` (`id`, `user_id`, `action`, `table_name`, `record_id`, `old_data`, `new_data`, `ip_address`, `user_agent`, `created_at`) VALUES ('19', '3', 'LOGOUT', 'users', '3', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 OPR/127.0.0.0 (Edition Yx GX)', '2026-03-15 16:37:13');
INSERT INTO `audit_log` (`id`, `user_id`, `action`, `table_name`, `record_id`, `old_data`, `new_data`, `ip_address`, `user_agent`, `created_at`) VALUES ('20', '1', 'LOGIN', 'users', '1', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 OPR/127.0.0.0 (Edition Yx GX)', '2026-03-16 16:28:03');
INSERT INTO `audit_log` (`id`, `user_id`, `action`, `table_name`, `record_id`, `old_data`, `new_data`, `ip_address`, `user_agent`, `created_at`) VALUES ('21', '1', 'CREATE', 'work_plans', '11', NULL, '{\"object_id\":\"4\",\"work_type\":\"покос травы\",\"planned_start\":\"2026-03-10\",\"planned_end\":\"2026-03-11\",\"responsible\":\"Бригада №1\",\"status\":\"запланировано\",\"description\":\"бла бла бла\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 OPR/127.0.0.0 (Edition Yx GX)', '2026-03-16 16:33:21');
INSERT INTO `audit_log` (`id`, `user_id`, `action`, `table_name`, `record_id`, `old_data`, `new_data`, `ip_address`, `user_agent`, `created_at`) VALUES ('22', '1', 'DELETE', 'work_plans', '11', '{\"id\":11,\"object_id\":4,\"work_type\":\"покос травы\",\"planned_start\":\"2026-03-10\",\"planned_end\":\"2026-03-11\",\"responsible\":\"Бригада №1\",\"status\":\"просрочено\",\"overdue_reason\":null,\"description\":\"бла бла бла\",\"file_name\":null,\"file_path\":null}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 OPR/127.0.0.0 (Edition Yx GX)', '2026-03-16 16:34:12');
INSERT INTO `audit_log` (`id`, `user_id`, `action`, `table_name`, `record_id`, `old_data`, `new_data`, `ip_address`, `user_agent`, `created_at`) VALUES ('23', '1', 'CREATE', 'work_plans', '12', NULL, '{\"object_id\":\"4\",\"work_type\":\"покос травы\",\"planned_start\":\"2026-03-19\",\"planned_end\":\"2026-03-19\",\"responsible\":\"Бригада №1\",\"status\":\"запланировано\",\"description\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 OPR/127.0.0.0 (Edition Yx GX)', '2026-03-16 16:49:35');
INSERT INTO `audit_log` (`id`, `user_id`, `action`, `table_name`, `record_id`, `old_data`, `new_data`, `ip_address`, `user_agent`, `created_at`) VALUES ('24', '1', 'LOGOUT', 'users', '1', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 OPR/127.0.0.0 (Edition Yx GX)', '2026-03-16 16:49:51');
INSERT INTO `audit_log` (`id`, `user_id`, `action`, `table_name`, `record_id`, `old_data`, `new_data`, `ip_address`, `user_agent`, `created_at`) VALUES ('25', '3', 'LOGIN', 'users', '3', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 OPR/127.0.0.0 (Edition Yx GX)', '2026-03-16 16:50:01');
INSERT INTO `audit_log` (`id`, `user_id`, `action`, `table_name`, `record_id`, `old_data`, `new_data`, `ip_address`, `user_agent`, `created_at`) VALUES ('26', '3', 'LOGOUT', 'users', '3', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 OPR/127.0.0.0 (Edition Yx GX)', '2026-03-16 16:50:36');
INSERT INTO `audit_log` (`id`, `user_id`, `action`, `table_name`, `record_id`, `old_data`, `new_data`, `ip_address`, `user_agent`, `created_at`) VALUES ('27', '1', 'LOGIN', 'users', '1', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 OPR/127.0.0.0 (Edition Yx GX)', '2026-03-16 16:55:20');


-- Структура таблицы `backup_settings`
CREATE TABLE `backup_settings` (
  `id` int(11) NOT NULL DEFAULT 1,
  `auto_backup_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `backup_time` time DEFAULT NULL,
  `backup_period` enum('daily','weekly','monthly') DEFAULT 'daily',
  `last_backup` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Данные таблицы `backup_settings`
INSERT INTO `backup_settings` (`id`, `auto_backup_enabled`, `backup_time`, `backup_period`, `last_backup`) VALUES ('1', '0', '03:00:00', 'daily', NULL);


-- Структура таблицы `messages`
CREATE TABLE `messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `deleted_by_sender` tinyint(1) NOT NULL DEFAULT 0,
  `deleted_by_receiver` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `sender_id` (`sender_id`),
  KEY `receiver_id` (`receiver_id`),
  CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Данные таблицы `messages`
INSERT INTO `messages` (`id`, `sender_id`, `receiver_id`, `message`, `file_name`, `file_path`, `is_read`, `deleted_by_sender`, `deleted_by_receiver`, `created_at`) VALUES ('3', '2', '3', 'На следующей неделе запланирована покраска качелей. Будьте готовы.', NULL, NULL, '1', '0', '0', '2026-02-27 07:00:00');
INSERT INTO `messages` (`id`, `sender_id`, `receiver_id`, `message`, `file_name`, `file_path`, `is_read`, `deleted_by_sender`, `deleted_by_receiver`, `created_at`) VALUES ('7', '3', '1', 'мяу', NULL, NULL, '1', '1', '0', '2026-03-13 16:48:53');


-- Структура таблицы `notifications`
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `message` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Данные таблицы `notifications`
INSERT INTO `notifications` (`id`, `user_id`, `type`, `message`, `link`, `is_read`, `created_at`) VALUES ('1', '2', 'new_plan', 'Новый план работ: покос травы', 'index.php?table=work_plans&edit_id=12', '0', '2026-03-16 16:49:35');
INSERT INTO `notifications` (`id`, `user_id`, `type`, `message`, `link`, `is_read`, `created_at`) VALUES ('2', '3', 'new_plan', 'Новый план работ: покос травы', 'index.php?table=work_plans&edit_id=12', '1', '2026-03-16 16:49:35');


-- Структура таблицы `objects`
CREATE TABLE `objects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `type` varchar(100) DEFAULT NULL,
  `address` varchar(200) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'активен',
  `responsible` varchar(100) DEFAULT NULL,
  `created_at` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Данные таблицы `objects`
INSERT INTO `objects` (`id`, `name`, `type`, `address`, `status`, `responsible`, `created_at`) VALUES ('1', 'Парк Горького', 'парк', 'ул. Ленина, 1', 'активен', 'Иванов И.И.', '2023-01-15');
INSERT INTO `objects` (`id`, `name`, `type`, `address`, `status`, `responsible`, `created_at`) VALUES ('2', 'Сквер Школьный', 'сквер', 'ул. Школьная, 10', 'активен', 'Петров П.П.', '2023-02-20');
INSERT INTO `objects` (`id`, `name`, `type`, `address`, `status`, `responsible`, `created_at`) VALUES ('3', 'Набережная', 'набережная', 'ул. Речная', 'активен', 'Сидоров С.С.', '2023-03-10');
INSERT INTO `objects` (`id`, `name`, `type`, `address`, `status`, `responsible`, `created_at`) VALUES ('4', 'Детская площадка', 'площадка', 'ул. Мира, 5', 'на реконструкции', 'Козлова А.С.', '2023-04-05');
INSERT INTO `objects` (`id`, `name`, `type`, `address`, `status`, `responsible`, `created_at`) VALUES ('5', 'Парк Победы', 'парк', 'ул. Победы, 2', 'активен', 'Иванов И.И.', '2023-05-01');
INSERT INTO `objects` (`id`, `name`, `type`, `address`, `status`, `responsible`, `created_at`) VALUES ('6', 'Сквер Молодежный', 'сквер', 'ул. Комсомольская, 15', 'активен', 'Петров П.П.', '2023-06-10');
INSERT INTO `objects` (`id`, `name`, `type`, `address`, `status`, `responsible`, `created_at`) VALUES ('7', 'Фонтан на площади', 'фонтан', 'пл. Центральная, 1', 'на реконструкции', 'Сидоров С.С.', '2023-07-20');
INSERT INTO `objects` (`id`, `name`, `type`, `address`, `status`, `responsible`, `created_at`) VALUES ('8', 'Детский городок \"Сказка\"', 'площадка', 'ул. Детская, 8', 'активен', 'Козлова А.С.', '2023-08-15');
INSERT INTO `objects` (`id`, `name`, `type`, `address`, `status`, `responsible`, `created_at`) VALUES ('9', 'Спортивная площадка', 'спортплощадка', 'ул. Спортивная, 12', 'закрыт', 'Иванов И.И.', '2023-09-01');
INSERT INTO `objects` (`id`, `name`, `type`, `address`, `status`, `responsible`, `created_at`) VALUES ('10', 'Памятник воинам', 'памятник', 'ул. Ленина, 5', 'активен', 'Петров П.П.', '2024-01-10');


-- Структура таблицы `users`
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `login` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(150) DEFAULT NULL,
  `role` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `login` (`login`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Данные таблицы `users`
INSERT INTO `users` (`id`, `login`, `password`, `full_name`, `role`) VALUES ('1', 'admin', 'admin123', 'Администратор', 'администратор');
INSERT INTO `users` (`id`, `login`, `password`, `full_name`, `role`) VALUES ('2', 'planner', 'plan123', 'Планировщик', 'планировщик');
INSERT INTO `users` (`id`, `login`, `password`, `full_name`, `role`) VALUES ('3', 'worker', 'work123', 'Рабочий', 'исполнитель');


-- Структура таблицы `work_executions`
CREATE TABLE `work_executions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `plan_id` int(11) DEFAULT NULL,
  `object_id` int(11) NOT NULL,
  `work_type` varchar(100) NOT NULL,
  `date_performed` date NOT NULL,
  `result` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `responsible` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `object_id` (`object_id`),
  KEY `plan_id` (`plan_id`),
  CONSTRAINT `work_executions_ibfk_1` FOREIGN KEY (`object_id`) REFERENCES `objects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `work_executions_ibfk_2` FOREIGN KEY (`plan_id`) REFERENCES `work_plans` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Данные таблицы `work_executions`
INSERT INTO `work_executions` (`id`, `plan_id`, `object_id`, `work_type`, `date_performed`, `result`, `description`, `file_name`, `file_path`, `responsible`) VALUES ('1', '2', '2', 'ремонт лавочек', '2025-05-05', 'выполнено', 'Заменены доски, покрашено', NULL, NULL, 'Бригада №2');
INSERT INTO `work_executions` (`id`, `plan_id`, `object_id`, `work_type`, `date_performed`, `result`, `description`, `file_name`, `file_path`, `responsible`) VALUES ('2', '3', '3', 'обрезка деревьев', '2025-04-18', 'частично', 'Обрезано только 5 деревьев из 10', NULL, NULL, 'Бригада №3');
INSERT INTO `work_executions` (`id`, `plan_id`, `object_id`, `work_type`, `date_performed`, `result`, `description`, `file_name`, `file_path`, `responsible`) VALUES ('3', '5', '5', 'уборка мусора', '2025-06-01', 'выполнено', 'Собрано 8 мешков мусора', NULL, NULL, 'Бригада №2');
INSERT INTO `work_executions` (`id`, `plan_id`, `object_id`, `work_type`, `date_performed`, `result`, `description`, `file_name`, `file_path`, `responsible`) VALUES ('4', '6', '6', 'посадка цветов', '2025-04-20', 'выполнено', 'Высажены петунии и бархатцы', NULL, NULL, 'Бригада №3');
INSERT INTO `work_executions` (`id`, `plan_id`, `object_id`, `work_type`, `date_performed`, `result`, `description`, `file_name`, `file_path`, `responsible`) VALUES ('5', NULL, '1', 'полив газонов', '2025-07-15', 'выполнено', 'Полив в засушливый период', NULL, NULL, 'Бригада №1');
INSERT INTO `work_executions` (`id`, `plan_id`, `object_id`, `work_type`, `date_performed`, `result`, `description`, `file_name`, `file_path`, `responsible`) VALUES ('6', NULL, '4', 'уборка мусора', '2025-07-20', 'выполнено', 'Убрана территория вокруг площадки', NULL, NULL, 'Бригада №2');
INSERT INTO `work_executions` (`id`, `plan_id`, `object_id`, `work_type`, `date_performed`, `result`, `description`, `file_name`, `file_path`, `responsible`) VALUES ('7', '1', '1', 'покос травы', '2025-06-10', 'выполнено', 'Скошено 2 га', NULL, NULL, 'Бригада №1');
INSERT INTO `work_executions` (`id`, `plan_id`, `object_id`, `work_type`, `date_performed`, `result`, `description`, `file_name`, `file_path`, `responsible`) VALUES ('8', '7', '7', 'чистка фонтана', '2025-05-12', 'выполнено', 'Фонтан очищен, вода заменена', NULL, NULL, 'Бригада №1');
INSERT INTO `work_executions` (`id`, `plan_id`, `object_id`, `work_type`, `date_performed`, `result`, `description`, `file_name`, `file_path`, `responsible`) VALUES ('9', '9', '9', 'замена сеток', '2025-06-03', 'выполнено', 'Сетки заменены на новые', NULL, NULL, 'Бригада №3');
INSERT INTO `work_executions` (`id`, `plan_id`, `object_id`, `work_type`, `date_performed`, `result`, `description`, `file_name`, `file_path`, `responsible`) VALUES ('10', NULL, '8', 'покраска', '2025-08-05', 'выполнено', 'Покрашены малые формы', NULL, NULL, 'Бригада №2');


-- Структура таблицы `work_plans`
CREATE TABLE `work_plans` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `object_id` int(11) NOT NULL,
  `work_type` varchar(100) NOT NULL,
  `planned_start` date NOT NULL,
  `planned_end` date NOT NULL,
  `responsible` varchar(100) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'запланировано',
  `overdue_reason` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `object_id` (`object_id`),
  KEY `idx_planned_start` (`planned_start`),
  CONSTRAINT `work_plans_ibfk_1` FOREIGN KEY (`object_id`) REFERENCES `objects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Данные таблицы `work_plans`
INSERT INTO `work_plans` (`id`, `object_id`, `work_type`, `planned_start`, `planned_end`, `responsible`, `status`, `overdue_reason`, `description`, `file_name`, `file_path`) VALUES ('1', '1', 'покос травы', '2025-06-01', '2025-06-15', 'Бригада №1', 'просрочено', NULL, 'Покос травы в центральной части парка', NULL, NULL);
INSERT INTO `work_plans` (`id`, `object_id`, `work_type`, `planned_start`, `planned_end`, `responsible`, `status`, `overdue_reason`, `description`, `file_name`, `file_path`) VALUES ('2', '2', 'ремонт лавочек', '2025-05-01', '2025-05-05', 'Бригада №2', 'выполнено', NULL, 'Замена досок на 3 лавочках', NULL, NULL);
INSERT INTO `work_plans` (`id`, `object_id`, `work_type`, `planned_start`, `planned_end`, `responsible`, `status`, `overdue_reason`, `description`, `file_name`, `file_path`) VALUES ('3', '3', 'обрезка деревьев', '2025-04-10', '2025-04-20', 'Бригада №3', 'просрочено', NULL, 'Санитарная обрезка тополей', NULL, NULL);
INSERT INTO `work_plans` (`id`, `object_id`, `work_type`, `planned_start`, `planned_end`, `responsible`, `status`, `overdue_reason`, `description`, `file_name`, `file_path`) VALUES ('4', '4', 'покраска качелей', '2025-07-01', '2025-07-10', 'Бригада №1', 'просрочено', NULL, 'Обновление покрытия на детской площадке', NULL, NULL);
INSERT INTO `work_plans` (`id`, `object_id`, `work_type`, `planned_start`, `planned_end`, `responsible`, `status`, `overdue_reason`, `description`, `file_name`, `file_path`) VALUES ('5', '5', 'уборка мусора', '2025-06-01', '2025-06-01', 'Бригада №2', 'выполнено', NULL, 'Ежедневная уборка парка Победы', NULL, NULL);
INSERT INTO `work_plans` (`id`, `object_id`, `work_type`, `planned_start`, `planned_end`, `responsible`, `status`, `overdue_reason`, `description`, `file_name`, `file_path`) VALUES ('6', '6', 'посадка цветов', '2025-04-15', '2025-04-25', 'Бригада №3', 'выполнено', NULL, 'Высадка однолетников в сквере', 'схема_клумб.pdf', 'uploads/schema_flowers.pdf');
INSERT INTO `work_plans` (`id`, `object_id`, `work_type`, `planned_start`, `planned_end`, `responsible`, `status`, `overdue_reason`, `description`, `file_name`, `file_path`) VALUES ('7', '7', 'чистка фонтана', '2025-05-10', '2025-05-15', 'Бригада №1', 'просрочено', NULL, 'Удаление водорослей и мусора', NULL, NULL);
INSERT INTO `work_plans` (`id`, `object_id`, `work_type`, `planned_start`, `planned_end`, `responsible`, `status`, `overdue_reason`, `description`, `file_name`, `file_path`) VALUES ('8', '8', 'ремонт горок', '2025-08-01', '2025-08-10', 'Бригада №2', 'просрочено', 'Ну как-то лень', 'Замена пластиковых элементов', NULL, NULL);
INSERT INTO `work_plans` (`id`, `object_id`, `work_type`, `planned_start`, `planned_end`, `responsible`, `status`, `overdue_reason`, `description`, `file_name`, `file_path`) VALUES ('9', '9', 'замена сеток', '2025-06-01', '2025-06-05', 'Бригада №3', 'просрочено', NULL, 'Замена баскетбольных сеток', NULL, NULL);
INSERT INTO `work_plans` (`id`, `object_id`, `work_type`, `planned_start`, `planned_end`, `responsible`, `status`, `overdue_reason`, `description`, `file_name`, `file_path`) VALUES ('10', '10', 'покраска постамента', '2025-09-01', '2025-09-10', 'Бригада №1', 'просрочено', 'Погодные условия', 'Обновление памятника', 'ЛР7 (1).pdf', 'uploads/69a153c12a569_1772180417.pdf');
INSERT INTO `work_plans` (`id`, `object_id`, `work_type`, `planned_start`, `planned_end`, `responsible`, `status`, `overdue_reason`, `description`, `file_name`, `file_path`) VALUES ('12', '4', 'покос травы', '2026-03-19', '2026-03-19', 'Бригада №1', 'запланировано', NULL, '', NULL, NULL);


SET FOREIGN_KEY_CHECKS=1;
