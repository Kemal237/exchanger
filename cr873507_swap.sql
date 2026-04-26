-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Хост: localhost
-- Время создания: Апр 26 2026 г., 19:10
-- Версия сервера: 8.0.44-35
-- Версия PHP: 7.4.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `cr873507_swap`
--

-- --------------------------------------------------------

--
-- Структура таблицы `admin_notes`
--

CREATE TABLE IF NOT EXISTS `admin_notes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `entity_type` varchar(10) NOT NULL,
  `entity_id` varchar(100) NOT NULL,
  `admin_name` varchar(100) NOT NULL DEFAULT 'Администратор',
  `note_text` text,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_entity` (`entity_type`,`entity_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `admin_notes`
--

INSERT INTO `admin_notes` (`id`, `entity_type`, `entity_id`, `admin_name`, `note_text`, `created_at`) VALUES
(1, 'user', '1', 'Kem4ik', '111', '2026-04-24 13:42:40'),
(3, 'user', '4', 'Kem4ik', 'куууу', '2026-04-24 13:48:22'),
(4, 'user', '4', 'Kem4ik', '', '2026-04-24 13:48:52'),
(8, 'order', 'SW-8P0QE', 'Kem4ik', '1', '2026-04-24 14:32:43'),
(9, 'order', 'SW-ZJ0K4', 'Kem4ik', '1', '2026-04-25 02:15:33'),
(10, 'order', 'SW-ZJ0K4', 'Kem4ik', '', '2026-04-25 02:15:40');

-- --------------------------------------------------------

--
-- Структура таблицы `admin_note_files`
--

CREATE TABLE IF NOT EXISTS `admin_note_files` (
  `id` int NOT NULL AUTO_INCREMENT,
  `note_id` int NOT NULL,
  `filename` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `note_id` (`note_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `admin_note_files`
--

INSERT INTO `admin_note_files` (`id`, `note_id`, `filename`, `original_name`, `created_at`) VALUES
(2, 4, '6382b104af0952240986a429a42c2013.jpg', 'Mikky Mouse Аватарка.jpg', '2026-04-24 13:48:52'),
(3, 10, 'fe191e4a6728f74f054672386094455c.jpg', 'YYXixFvaZ0c.jpg', '2026-04-25 02:15:40');

-- --------------------------------------------------------

--
-- Структура таблицы `orders`
--

CREATE TABLE IF NOT EXISTS `orders` (
  `id` varchar(50) NOT NULL,
  `user_id` int DEFAULT NULL,
  `give_currency` varchar(50) NOT NULL,
  `amount_give` decimal(18,8) NOT NULL,
  `get_currency` varchar(50) NOT NULL,
  `amount_get` decimal(18,8) NOT NULL,
  `rate` decimal(18,8) NOT NULL,
  `status` enum('new','in_process','success','canceled') NOT NULL DEFAULT 'new',
  `canceled_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `give_currency`, `amount_give`, `get_currency`, `amount_get`, `rate`, `status`, `canceled_at`, `created_at`, `updated_at`) VALUES
('SW-2JBU6', 7, 'USDT_TRC20', 156.84000000, 'RUB', 12884.41000000, 0.00000000, 'success', NULL, '2026-04-03 11:42:54', '2026-04-24 14:05:22'),
('SW-2KUMB', 5, 'USDT_TRC20', 100.00000000, 'RUB', 7399.00000000, 0.00000000, 'canceled', '2026-04-25 02:14:41', '2026-04-24 02:23:24', '2026-04-25 02:14:41'),
('SW-2QYSH', 3, 'USDT_TRC20', 100.00000000, 'RUB', 8193.00000000, 0.00000000, 'new', NULL, '2026-03-15 17:28:34', '2026-04-24 14:05:22'),
('SW-34JEO', 5, 'USDT_TRC20', 100.00000000, 'RUB', 7700.00000000, 0.00000000, 'canceled', '2026-04-24 01:17:58', '2026-04-22 19:35:21', '2026-04-24 14:05:22'),
('SW-62CWF', 5, 'USDT_TRC20', 100.00000000, 'RUB', 7357.00000000, 0.00000000, 'new', '2026-04-24 12:34:55', '2026-04-24 12:29:41', '2026-04-24 14:05:22'),
('SW-7GITC', 7, 'USDT_TRC20', 105.13000000, 'RUB', 8654.30000000, 0.00000000, 'canceled', '2026-04-23 00:19:34', '2026-04-04 15:34:07', '2026-04-24 14:05:22'),
('SW-80ILR', 3, 'USDT_TRC20', 100.00000000, 'RUB', 8193.00000000, 0.00000000, 'canceled', '2026-03-15 19:10:35', '2026-03-15 18:01:31', '2026-04-24 14:05:22'),
('SW-8P0QE', 4, 'USDT_TRC20', 100.00000000, 'RUB', 7400.00000000, 0.00000000, 'new', NULL, '2026-04-23 20:00:57', '2026-04-24 14:05:22'),
('SW-8UO0C', 5, 'USDT_TRC20', 5000.00000000, 'RUB', 385000.00000000, 0.00000000, 'canceled', '2026-04-22 19:33:18', '2026-04-22 19:33:12', '2026-04-24 14:05:22'),
('SW-A3QAI', 7, 'USDT_TRC20', 100.00000000, 'RUB', 8232.00000000, 0.00000000, 'canceled', '2026-04-04 14:46:20', '2026-04-04 14:46:07', '2026-04-24 14:05:22'),
('SW-A8NOM', 5, 'USDT_TRC20', 100.00000000, 'RUB', 8232.00000000, 0.00000000, 'canceled', '2026-04-25 02:14:36', '2026-04-04 14:32:48', '2026-04-25 02:14:36'),
('SW-AVBHR', 5, 'USDT_TRC20', 100.00000000, 'RUB', 7357.00000000, 0.00000000, 'new', '2026-04-24 12:34:58', '2026-04-24 12:29:35', '2026-04-24 14:05:22'),
('SW-B4AKB', 7, 'USDT_TRC20', 100.00000000, 'RUB', 8232.00000000, 0.00000000, 'new', NULL, '2026-04-04 15:08:01', '2026-04-24 14:05:22'),
('SW-B67DD', 3, 'USDT_TRC20', 100.00000000, 'RUB', 8193.00000000, 0.00000000, 'new', NULL, '2026-03-15 17:28:38', '2026-04-24 14:05:22'),
('SW-EPIR5', 7, 'USDT_TRC20', 100.00000000, 'RUB', 8232.00000000, 0.00000000, 'new', NULL, '2026-04-04 15:22:02', '2026-04-24 14:05:22'),
('SW-F51C8', 7, 'USDT_TRC20', 100.00000000, 'RUB', 8232.00000000, 0.00000000, 'new', NULL, '2026-04-04 14:46:24', '2026-04-24 14:05:22'),
('SW-FD9JH', 3, 'USDT_TRC20', 100.00000000, 'RUB', 8340.00000000, 0.00000000, 'new', NULL, '2026-03-28 11:57:24', '2026-04-24 14:05:22'),
('SW-IB6SF', 5, 'USDT_TRC20', 100.00000000, 'RUB', 7400.00000000, 0.00000000, 'canceled', '2026-04-23 20:15:36', '2026-04-23 20:15:29', '2026-04-24 14:05:22'),
('SW-K1ITY', 3, 'USDT_TRC20', 100.00000000, 'RUB', 8193.00000000, 0.00000000, 'new', NULL, '2026-03-15 17:28:53', '2026-04-24 14:05:22'),
('SW-M91M2', 5, 'USDT_TRC20', 100.00000000, 'RUB', 8597.00000000, 0.00000000, 'success', NULL, '2026-03-18 20:14:02', '2026-04-24 14:05:22'),
('SW-N12OD', 7, 'USDT_TRC20', 100.00000000, 'RUB', 8232.00000000, 0.00000000, 'new', NULL, '2026-04-04 15:08:06', '2026-04-24 14:05:22'),
('SW-N164B', 5, 'USDT_TRC20', 100.00000000, 'RUB', 7399.00000000, 0.00000000, 'canceled', '2026-04-25 02:14:39', '2026-04-24 02:23:13', '2026-04-25 02:14:39'),
('SW-N7149', 3, 'USDT_TRC20', 100.00000000, 'RUB', 8193.00000000, 0.00000000, 'canceled', '2026-03-15 18:54:58', '2026-03-15 18:03:56', '2026-04-24 14:05:22'),
('SW-O3CS6', 3, 'USDT_TRC20', 100.00000000, 'RUB', 8193.00000000, 0.00000000, 'new', NULL, '2026-03-15 17:28:58', '2026-04-24 14:05:22'),
('SW-PIRRL', 3, 'USDT_TRC20', 100.00000000, 'RUB', 8193.00000000, 0.00000000, 'canceled', '2026-03-15 19:10:26', '2026-03-15 17:30:18', '2026-04-24 14:05:22'),
('SW-Q9749', 7, 'USDT_TRC20', 100.00000000, 'RUB', 8232.00000000, 0.00000000, 'new', NULL, '2026-04-04 15:07:55', '2026-04-24 14:05:22'),
('SW-QKRI8', 3, 'USDT_TRC20', 100.00000000, 'RUB', 8193.00000000, 0.00000000, 'canceled', '2026-03-15 18:54:40', '2026-03-15 18:15:39', '2026-04-24 14:05:22'),
('SW-RFM3I', 8, 'USDT_TRC20', 100.00000000, 'RUB', 7401.00000000, 0.00000000, 'new', NULL, '2026-04-24 00:14:07', '2026-04-24 14:05:22'),
('SW-RORE7', 5, 'USDT_TRC20', 100.00000000, 'RUB', 7400.00000000, 0.00000000, 'canceled', '2026-04-23 20:15:38', '2026-04-23 20:00:41', '2026-04-24 14:05:22'),
('SW-SDRM8', 3, 'USDT_TRC20', 100.00000000, 'RUB', 8340.00000000, 0.00000000, 'in_process', NULL, '2026-03-28 12:10:09', '2026-04-24 14:05:22'),
('SW-TGSK1', 3, 'USDT_TRC20', 100.00000000, 'RUB', 7294.00000000, 0.00000000, 'canceled', '2026-04-23 13:49:39', '2026-04-23 13:49:36', '2026-04-24 14:05:22'),
('SW-UC4GQ', 5, 'USDT_TRC20', 100.00000000, 'RUB', 7401.00000000, 0.00000000, 'canceled', '2026-04-24 01:17:54', '2026-04-24 00:32:27', '2026-04-24 14:05:22'),
('SW-UJRXW', 3, 'USDT_TRC20', 100.00000000, 'RUB', 8193.00000000, 0.00000000, 'canceled', '2026-03-15 19:10:37', '2026-03-15 17:29:28', '2026-04-24 14:05:22'),
('SW-VVBAL', 5, 'USDT_ERC20', 100.00000000, 'RUB_SBP', 7340.00000000, 73.40000000, 'new', NULL, '2026-04-26 14:30:11', '2026-04-26 14:30:11'),
('SW-WMJ3L', 7, 'USDT_TRC20', 100.00000000, 'RUB', 8232.00000000, 0.00000000, 'new', NULL, '2026-04-04 15:21:54', '2026-04-24 14:05:22'),
('SW-XI2U5', 7, 'USDT_TRC20', 100.00000000, 'RUB', 8232.00000000, 0.00000000, 'new', NULL, '2026-04-04 15:22:06', '2026-04-24 14:05:22'),
('SW-YV6WW', 3, 'USDT_TRC20', 100.00000000, 'RUB', 8193.00000000, 0.00000000, 'canceled', '2026-03-18 18:18:25', '2026-03-15 17:29:19', '2026-04-24 14:05:22'),
('SW-ZFH8L', 4, 'USDT_TRC20', 100.00000000, 'RUB', 7400.00000000, 0.00000000, 'new', NULL, '2026-04-23 19:57:19', '2026-04-24 14:05:22'),
('SW-ZHD1E', 5, 'USDT_TRC20', 100.00000000, 'RUB', 7401.00000000, 0.00000000, 'new', NULL, '2026-04-24 00:32:34', '2026-04-24 14:05:22'),
('SW-ZJ0K4', 5, 'USDT_TRC20', 100.00000000, 'RUB', 7357.00000000, 0.00000000, 'canceled', '2026-04-25 02:14:55', '2026-04-24 12:29:47', '2026-04-25 02:14:55');

-- --------------------------------------------------------

--
-- Структура таблицы `reserves`
--

CREATE TABLE IF NOT EXISTS `reserves` (
  `currency` varchar(50) NOT NULL,
  `amount` decimal(18,8) NOT NULL DEFAULT '0.00000000',
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `min` decimal(20,3) NOT NULL DEFAULT '0.000',
  `max` decimal(20,3) NOT NULL DEFAULT '0.000',
  PRIMARY KEY (`currency`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `reserves`
--

INSERT INTO `reserves` (`currency`, `amount`, `updated_at`, `min`, `max`) VALUES
('BTC', 12.78451637, '2026-03-29 17:26:13', 0.001, 10.000),
('ETH', 80000.00000000, '2026-04-25 15:42:44', 0.010, 100.000),
('RUB', 45205251.70000000, '2026-04-26 14:30:11', 6000.000, 210000.000),
('SOL', 15000.00000000, '2026-04-25 15:42:44', 0.500, 10000.000),
('USD', 37688.00000000, '2026-04-25 15:46:49', 50.000, 50000.000),
('USDC', 16152.00000000, '2026-04-25 15:46:11', 50.000, 55000.000),
('USDT_TRC20', 1245858.45000000, '2026-04-04 14:35:15', 50.000, 50000.000);

-- --------------------------------------------------------

--
-- Структура таблицы `support_messages`
--

CREATE TABLE IF NOT EXISTS `support_messages` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `ticket_id` int UNSIGNED NOT NULL,
  `sender` enum('user','admin') COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ticket_id` (`ticket_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `support_tickets`
--

CREATE TABLE IF NOT EXISTS `support_tickets` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('open','answered','closed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'open',
  `tg_message_id` bigint DEFAULT NULL COMMENT 'message_id в Telegram для привязки ответов',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_status` (`status`),
  KEY `idx_tg_message_id` (`tg_message_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `email_verified` tinyint(1) NOT NULL DEFAULT '0',
  `email_verification_token` varchar(64) DEFAULT NULL,
  `email_verification_sent_at` datetime DEFAULT NULL,
  `telegram` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `role` enum('user','admin') NOT NULL DEFAULT 'user',
  `password_reset_token` varchar(64) DEFAULT NULL,
  `password_reset_sent_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `email_verified`, `email_verification_token`, `email_verification_sent_at`, `telegram`, `password`, `created_at`, `role`, `password_reset_token`, `password_reset_sent_at`) VALUES
(1, 'testuser', 'test@example.com', 0, NULL, NULL, NULL, '$2y$10$5z5Qz5Qz5Qz5Qz5Qz5Qz5u5Qz5Qz5Qz5Qz5Qz5Qz5Qz5Qz5Qz5Qz5Qz', '2026-02-28 20:24:47', 'user', NULL, NULL),
(2, 'admin', 'admin@cr873507.tw1.ru', 0, NULL, NULL, NULL, '$2y$10$z5Qz5Qz5Qz5Qz5Qz5Qz5Qu5Qz5Qz5Qz5Qz5Qz5Qz5Qz5Qz5Qz5Qz5Qz', '2026-02-28 20:24:47', 'user', NULL, NULL),
(3, 'kolbasa', 'shadowraze702@gmail.com', 0, NULL, NULL, '', '$2y$10$NC786k7yVl/UvHkCIEkxNOB/efovBoT9J3vVXyJCfjaoCgUQqQyE6', '2026-02-28 21:11:40', 'user', NULL, NULL),
(4, 'enyaa9', 'enyaa999@gmail.com', 1, NULL, '2026-04-23 20:03:15', '@prosto_obmen', '$2y$10$LL8gteLNektDD.tcKc9kGeOBcYVpactjKrImmxFLZQx7i9rY1pn8O', '2026-02-28 21:18:24', 'admin', NULL, NULL),
(5, 'Kem4ik', 'kemal.b31@mail.ru', 1, NULL, '2026-04-23 19:30:19', '@kem4ik', '$2y$10$U7CpNaTCIB6AVHz53FzJeeMaTFO6P.Rulifs8nurvBjNL/tEl3YZq', '2026-03-02 13:40:55', 'admin', '2e3bd13079c87d5936d96c55957af197419f80adcf93f610f65cdb285c009119', '2026-04-23 20:23:23'),
(7, 'test2', 'ssss@mail.ru', 0, NULL, NULL, '@test2', '$2y$10$eMGdbQioFzIH1IbSqaeotuTi4hE09GJw2RYFfmaVCfZto8ifYLbVG', '2026-03-02 15:05:30', 'user', NULL, NULL),
(8, 'test3', 'test3@mail.ru', 0, '9674dc0f5c3c891ee43caf5f745463444f6c3d1b7c762b2a4af644f537711aa5', '2026-04-24 00:13:49', '@test3', '$2y$10$pE2SefcU4kIRWjfUXHf/VuZKWBQPPP.fDTD8QwIotil4eHmu1xcUq', '2026-04-24 00:13:49', 'user', NULL, NULL);

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `admin_note_files`
--
ALTER TABLE `admin_note_files`
  ADD CONSTRAINT `admin_note_files_ibfk_1` FOREIGN KEY (`note_id`) REFERENCES `admin_notes` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
