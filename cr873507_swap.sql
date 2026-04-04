-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Хост: localhost
-- Время создания: Апр 04 2026 г., 14:47
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
('ORD-1775303184-1618', 7, 'USDT_TRC20', 100.00000000, 'RUB', 8232.00000000, 0.00000000, 'new', NULL, '2026-04-04 14:46:24', '2026-04-04 14:46:24');

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
('RUB', 45759304.00000000, '2026-04-04 14:46:24', 6000.000, 210000.000),
('USDT_TRC20', 1245858.45000000, '2026-04-04 14:35:15', 50.000, 50000.000);

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `telegram` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `role` enum('user','admin') NOT NULL DEFAULT 'user',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `telegram`, `password`, `created_at`, `role`) VALUES
(1, 'testuser', 'test@example.com', NULL, '$2y$10$5z5Qz5Qz5Qz5Qz5Qz5Qz5u5Qz5Qz5Qz5Qz5Qz5Qz5Qz5Qz5Qz5Qz5Qz', '2026-02-28 20:24:47', 'user'),
(2, 'admin', 'admin@cr873507.tw1.ru', NULL, '$2y$10$z5Qz5Qz5Qz5Qz5Qz5Qz5Qu5Qz5Qz5Qz5Qz5Qz5Qz5Qz5Qz5Qz5Qz5Qz', '2026-02-28 20:24:47', 'user'),
(3, 'kolbasa', 'shadowraze702@gmail.com', '@kolbasa', '$2y$10$NC786k7yVl/UvHkCIEkxNOB/efovBoT9J3vVXyJCfjaoCgUQqQyE6', '2026-02-28 21:11:40', 'user'),
(4, 'enyaa9', 'enyaa999@gmail.com', NULL, '$2y$10$/Wg401g/3t4yCSgzdaAekOq30xGPiP/BkdVIaZMTvTLcGvKYDVKna', '2026-02-28 21:18:24', 'admin'),
(5, 'Kem4ik', 'kemal.b31@mail.ru', '@kem4ik', '$2y$10$YoGJLJXb9COdx.KJGHI9IO5.JkCThw7l0avWxc7BHhm.iOGFANCsy', '2026-03-02 13:40:55', 'admin'),
(7, 'test2', 'ssss@mail.ru', '@test2', '$2y$10$eMGdbQioFzIH1IbSqaeotuTi4hE09GJw2RYFfmaVCfZto8ifYLbVG', '2026-03-02 15:05:30', 'user');

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
