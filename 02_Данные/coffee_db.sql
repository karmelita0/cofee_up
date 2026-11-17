-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Хост: 127.0.0.1:3307
-- Время создания: Ноя 17 2026 г., 19:36
-- Версия сервера: 8.0.30
-- Версия PHP: 8.1.9

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `coffee_db`
--

-- --------------------------------------------------------

--
-- Структура таблицы `categories`
--

CREATE TABLE `categories` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `sort_order` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `categories`
--

INSERT INTO `categories` (`id`, `name`, `sort_order`) VALUES
(1, 'Кофе', 0),
(2, 'Напитки', 0),
(3, 'Выпечка', 0);

-- --------------------------------------------------------

--
-- Структура таблицы `menu_items`
--

CREATE TABLE `menu_items` (
  `id` int NOT NULL,
  `category_id` int NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` text,
  `price` decimal(10,2) NOT NULL,
  `image` varchar(255) DEFAULT 'default.jpg',
  `is_available` tinyint(1) DEFAULT '1'
) ;

--
-- Дамп данных таблицы `menu_items`
--

INSERT INTO `menu_items` (`id`, `category_id`, `title`, `description`, `price`, `image`, `is_available`) VALUES
(1, 1, 'Капучино', 'Классический итальянский кофе с плотной молочной пенкой.', '250.00', 'cappuccino.jpg', 1),
(2, 1, 'Латте', 'Нежный кофейный напиток с большим количеством молока.', '280.00', 'latte.jpg', 1),
(3, 2, 'Матча Латте', 'Японский зеленый чай с молоком.', '320.00', 'matcha.jpg', 1),
(4, 3, 'Круассан', 'Свежая французская выпечка с хрустящей корочкой.', '150.00', 'croissant.jpg', 1),
(5, 1, 'Американо 300 мл', 'Классический чёрный кофе на основе двойного эспрессо.', '180.00', 'default.jpg', 1),
(6, 1, 'Раф Ванильный 300 мл', 'Сливочный кофейный напиток с натуральным ванильным сахаром.', '290.00', 'latte.jpg', 1),
(7, 1, 'Флэт Уайт 250 мл', 'Насыщенный кофейный вкус двойного ристретто с тонким слоем молочной пены.', '260.00', 'cappuccino.jpg', 1),
(8, 1, 'Эспрессо двойной', 'Плотный и бодрящий шот свежеобжаренной арабики.', '150.00', 'default.jpg', 1),
(9, 2, 'Какао на молоке 300 мл', 'Тёплый шоколадный напиток с лёгкой воздушной пенкой.', '220.00', 'default.jpg', 1),
(10, 2, 'Чай облепиховый 400 мл', 'Согревающий ягодный напиток с мёдом и апельсином.', '240.00', 'matcha.jpg', 1),
(11, 2, 'Айс Латте Карамель', 'Охлаждённый кофейный напиток со льдом и карамельным сиропом.', '270.00', 'latte.jpg', 1),
(12, 2, 'Лимонад Маракуйя 400 мл', 'Освежающий газированный напиток с пюре маракуйи и мятой.', '230.00', 'matcha.jpg', 1),
(13, 3, 'Сырники со сметаной', 'Домашние творожные сырники из фермерского творога (2 шт.).', '240.00', 'croissant.jpg', 1),
(14, 3, 'Сэндвич с курицей и сыром', 'Хрустящий тост с запечённым куриным филе, сыром и соусом.', '260.00', 'croissant.jpg', 1),
(15, 3, 'Булочка с корицей', 'Сдобная выпечка с ароматной корицей и сливочной глазурью.', '170.00', 'croissant.jpg', 1),
(16, 3, 'Чизкейк Нью-Йорк', 'Классический сливочный десерт на песочной основе.', '280.00', 'croissant.jpg', 1);

-- --------------------------------------------------------

--
-- Структура таблицы `orders`
--

CREATE TABLE `orders` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `status` enum('new','cooking','ready','completed','cancelled') NOT NULL DEFAULT 'new',
  `total_price` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `order_items`
--

CREATE TABLE `order_items` (
  `id` int NOT NULL,
  `order_id` int NOT NULL,
  `menu_item_id` int NOT NULL,
  `quantity` int NOT NULL,
  `price_per_item` decimal(10,2) NOT NULL
) ;

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `login` varchar(50) NOT NULL,
  `first_name` varchar(60) DEFAULT NULL,
  `last_name` varchar(60) DEFAULT NULL,
  `phone` varchar(25) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('client','manager') DEFAULT 'client',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `login`, `first_name`, `last_name`, `phone`, `password_hash`, `role`, `created_at`) VALUES
(2, 'student', NULL, NULL, NULL, '$2y$10$e.wXqW9/5Mv9Zq1r3B6r1uVw2wB3B5d7B9h1j3k5m7n9p1q3s5t7u', 'client', '2026-11-14 19:04:49'),
(3, 'admin', 'админ', 'Админка', '+7 (898) 123-12-12', '$2y$10$8hubhVPqWkbPeSiKu7TPcOH/yf7TwTWM8o2W0kV0RRJlRJDeZA/ZC', 'manager', '2026-11-17 16:06:06'),
(4, 'anna_smirnova', 'анна', 'смирнова', '+7 (898) 123-90-98', '$2y$10$FY4fnIphyM9RNX3iATr2DuC6.dDBFy/yPIXypWIgY1acuJZJ.eiWy', 'client', '2026-11-17 16:20:57');

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `menu_items`
--
ALTER TABLE `menu_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- Индексы таблицы `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Индексы таблицы `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `menu_item_id` (`menu_item_id`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `login` (`login`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT для таблицы `menu_items`
--
ALTER TABLE `menu_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `menu_items`
--
ALTER TABLE `menu_items`
  ADD CONSTRAINT `menu_items_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);

--
-- Ограничения внешнего ключа таблицы `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Ограничения внешнего ключа таблицы `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
