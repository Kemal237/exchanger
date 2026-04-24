-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Хост: localhost
-- Время создания: Апр 24 2026 г., 13:46
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
  `entity_id` int NOT NULL,
  `admin_name` varchar(100) NOT NULL DEFAULT 'Администратор',
  `note_text` text,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_entity` (`entity_type`,`entity_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `admin_notes`
--

INSERT INTO `admin_notes` (`id`, `entity_type`, `entity_id`, `admin_name`, `note_text`, `created_at`) VALUES
(1, 'user', 1, 'Kem4ik', '111', '2026-04-24 13:42:40');

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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
('ORD-1773584914-1292', 3, 'USDT_TRC20', 100.00000000, 'RUB', 8193.00000000, 0.00000000, 'new', NULL, '2026-03-15 17:28:34', '2026-03-15 17:28:34'),
('ORD-1773584918-2630', 3, 'USDT_TRC20', 100.00000000, 'RUB', 8193.00000000, 0.00000000, 'new', NULL, '2026-03-15 17:28:38', '2026-03-15 17:28:38'),
('ORD-1773584933-5419', 3, 'USDT_TRC20', 100.00000000, 'RUB', 8193.00000000, 0.00000000, 'new', NULL, '2026-03-15 17:28:53', '2026-03-15 17:28:53'),
('ORD-1773584938-7595', 3, 'USDT_TRC20', 100.00000000, 'RUB', 8193.00000000, 0.00000000, 'new', NULL, '2026-03-15 17:28:58', '2026-03-15 17:28:58'),
('ORD-1773584959-5246', 3, 'USDT_TRC20', 100.00000000, 'RUB', 8193.00000000, 0.00000000, 'canceled', '2026-03-18 18:18:25', '2026-03-15 17:29:19', '2026-03-18 18:18:25'),
('ORD-1773584968-2625', 3, 'USDT_TRC20', 100.00000000, 'RUB', 8193.00000000, 0.00000000, 'canceled', '2026-03-15 19:10:37', '2026-03-15 17:29:28', '2026-03-15 19:10:37'),
('ORD-1773585018-9818', 3, 'USDT_TRC20', 100.00000000, 'RUB', 8193.00000000, 0.00000000, 'canceled', '2026-03-15 19:10:26', '2026-03-15 17:30:18', '2026-03-15 19:10:26'),
('ORD-1773586891-8652', 3, 'USDT_TRC20', 100.00000000, 'RUB', 8193.00000000, 0.00000000, 'canceled', '2026-03-15 19:10:35', '2026-03-15 18:01:31', '2026-03-15 19:10:35'),
('ORD-1773587036-1497', 3, 'USDT_TRC20', 100.00000000, 'RUB', 8193.00000000, 0.00000000, 'canceled', '2026-03-15 18:54:58', '2026-03-15 18:03:56', '2026-03-15 18:54:58'),
('ORD-1773587739-4150', 3, 'USDT_TRC20', 100.00000000, 'RUB', 8193.00000000, 0.00000000, 'canceled', '2026-03-15 18:54:40', '2026-03-15 18:15:39', '2026-03-24 10:17:48'),
('ORD-1773854042-8830', 5, 'USDT_TRC20', 100.00000000, 'RUB', 8597.00000000, 0.00000000, 'success', NULL, '2026-03-18 20:14:02', '2026-03-30 11:34:48'),
('ORD-1774688244-5563', 3, 'USDT_TRC20', 100.00000000, 'RUB', 8340.00000000, 0.00000000, 'new', NULL, '2026-03-28 11:57:24', '2026-03-28 11:57:24'),
('ORD-1774689009-1305', 3, 'USDT_TRC20', 100.00000000, 'RUB', 8340.00000000, 0.00000000, 'in_process', NULL, '2026-03-28 12:10:09', '2026-03-31 10:06:48'),
('ORD-1775205774-1666', 7, 'USDT_TRC20', 156.84000000, 'RUB', 12884.41000000, 0.00000000, 'success', NULL, '2026-04-03 11:42:54', '2026-04-03 11:43:57'),
('ORD-1775302368-6340', 5, 'USDT_TRC20', 100.00000000, 'RUB', 8232.00000000, 0.00000000, 'new', NULL, '2026-04-04 14:32:48', '2026-04-04 14:32:48'),
('ORD-1775303167-5544', 7, 'USDT_TRC20', 100.00000000, 'RUB', 8232.00000000, 0.00000000, 'canceled', '2026-04-04 14:46:20', '2026-04-04 14:46:07', '2026-04-04 14:46:20'),
('ORD-1775303184-1618', 7, 'USDT_TRC20', 100.00000000, 'RUB', 8232.00000000, 0.00000000, 'new', NULL, '2026-04-04 14:46:24', '2026-04-04 14:46:24'),
('ORD-1775304475-8896', 7, 'USDT_TRC20', 100.00000000, 'RUB', 8232.00000000, 0.00000000, 'new', NULL, '2026-04-04 15:07:55', '2026-04-04 15:07:55'),
('ORD-1775304481-5006', 7, 'USDT_TRC20', 100.00000000, 'RUB', 8232.00000000, 0.00000000, 'new', NULL, '2026-04-04 15:08:01', '2026-04-04 15:08:01'),
('ORD-1775304486-4147', 7, 'USDT_TRC20', 100.00000000, 'RUB', 8232.00000000, 0.00000000, 'new', NULL, '2026-04-04 15:08:06', '2026-04-04 15:08:06'),
('ORD-1775305314-9040', 7, 'USDT_TRC20', 100.00000000, 'RUB', 8232.00000000, 0.00000000, 'new', NULL, '2026-04-04 15:21:54', '2026-04-04 15:21:54'),
('ORD-1775305322-7972', 7, 'USDT_TRC20', 100.00000000, 'RUB', 8232.00000000, 0.00000000, 'new', NULL, '2026-04-04 15:22:02', '2026-04-04 15:22:02'),
('ORD-1775305326-6118', 7, 'USDT_TRC20', 100.00000000, 'RUB', 8232.00000000, 0.00000000, 'new', NULL, '2026-04-04 15:22:06', '2026-04-04 15:22:06'),
('ORD-1775306047-8805', 7, 'USDT_TRC20', 105.13000000, 'RUB', 8654.30000000, 0.00000000, 'canceled', '2026-04-23 00:19:34', '2026-04-04 15:34:07', '2026-04-23 00:19:34'),
('ORD-1776875592-5405', 5, 'USDT_TRC20', 5000.00000000, 'RUB', 385000.00000000, 0.00000000, 'canceled', '2026-04-22 19:33:18', '2026-04-22 19:33:12', '2026-04-22 19:33:18'),
('ORD-1776875721-4510', 5, 'USDT_TRC20', 100.00000000, 'RUB', 7700.00000000, 0.00000000, 'canceled', '2026-04-24 01:17:58', '2026-04-22 19:35:21', '2026-04-24 01:17:58'),
('ORD-1776941376-5288', 3, 'USDT_TRC20', 100.00000000, 'RUB', 7294.00000000, 0.00000000, 'canceled', '2026-04-23 13:49:39', '2026-04-23 13:49:36', '2026-04-23 13:49:39'),
('ORD-1776963439-2554', 4, 'USDT_TRC20', 100.00000000, 'RUB', 7400.00000000, 0.00000000, 'new', NULL, '2026-04-23 19:57:19', '2026-04-23 19:57:19'),
('ORD-1776963641-5688', 5, 'USDT_TRC20', 100.00000000, 'RUB', 7400.00000000, 0.00000000, 'canceled', '2026-04-23 20:15:38', '2026-04-23 20:00:41', '2026-04-23 20:15:38'),
('ORD-1776963657-6116', 4, 'USDT_TRC20', 100.00000000, 'RUB', 7400.00000000, 0.00000000, 'new', NULL, '2026-04-23 20:00:57', '2026-04-23 20:00:57'),
('ORD-1776964529-8049', 5, 'USDT_TRC20', 100.00000000, 'RUB', 7400.00000000, 0.00000000, 'canceled', '2026-04-23 20:15:36', '2026-04-23 20:15:29', '2026-04-23 20:15:36'),
('ORD-1776978847-6635', 8, 'USDT_TRC20', 100.00000000, 'RUB', 7401.00000000, 0.00000000, 'new', NULL, '2026-04-24 00:14:07', '2026-04-24 00:14:07'),
('ORD-1776979947-3521', 5, 'USDT_TRC20', 100.00000000, 'RUB', 7401.00000000, 0.00000000, 'canceled', '2026-04-24 01:17:54', '2026-04-24 00:32:27', '2026-04-24 01:17:54'),
('ORD-1776979954-4348', 5, 'USDT_TRC20', 100.00000000, 'RUB', 7401.00000000, 0.00000000, 'new', NULL, '2026-04-24 00:32:34', '2026-04-24 00:32:34'),
('ORD-1776986593-8718', 5, 'USDT_TRC20', 100.00000000, 'RUB', 7399.00000000, 0.00000000, 'new', '2026-04-24 12:35:03', '2026-04-24 02:23:13', '2026-04-24 12:36:41'),
('ORD-1776986604-3686', 5, 'USDT_TRC20', 100.00000000, 'RUB', 7399.00000000, 0.00000000, 'new', '2026-04-24 12:35:01', '2026-04-24 02:23:24', '2026-04-24 12:36:45'),
('ORD-1777022975-3436', 5, 'USDT_TRC20', 100.00000000, 'RUB', 7357.00000000, 0.00000000, 'new', '2026-04-24 12:34:58', '2026-04-24 12:29:35', '2026-04-24 12:36:37'),
('ORD-1777022981-6824', 5, 'USDT_TRC20', 100.00000000, 'RUB', 7357.00000000, 0.00000000, 'new', '2026-04-24 12:34:55', '2026-04-24 12:29:41', '2026-04-24 12:36:36'),
('ORD-1777022987-5427', 5, 'USDT_TRC20', 100.00000000, 'RUB', 7357.00000000, 0.00000000, 'new', '2026-04-24 12:34:53', '2026-04-24 12:29:47', '2026-04-24 12:36:34');

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
('RUB', 45212591.70000000, '2026-04-24 12:29:47', 6000.000, 210000.000),
('USDT_TRC20', 1245858.45000000, '2026-04-04 14:35:15', 50.000, 50000.000);

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
