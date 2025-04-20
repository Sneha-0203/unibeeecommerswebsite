-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 18, 2025 at 02:23 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `shoe_store`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `password`, `email`, `name`, `created_at`) VALUES
(1, 'admin', '$2y$10$i9IzmdttR.GZR4w9.TyRGuxVCFrdMUJ50vO9w3d4wNnnOzg3TPZIm', 'admin@shoestore.com', 'sneha', '2025-03-29 05:38:29');

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_size_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`id`, `user_id`, `product_id`, `product_size_id`, `quantity`, `created_at`) VALUES
(14, 3, 9, 16, 1, '2025-04-07 02:57:02');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`) VALUES
(1, 'Men', 'Men\'s footwear collection'),
(2, 'Women', 'Women\'s footwear collection'),
(3, 'Sports', 'Athletic and sports shoes'),
(4, 'Casual', 'Everyday casual footwear'),
(5, 'Formal', 'Formal and business shoes');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `subject` varchar(200) DEFAULT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_read` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `name`, `email`, `subject`, `message`, `created_at`, `is_read`) VALUES
(1, 'Sneha', 'snehas67248@gmail.com', 'ddddd', 'sdgsfgsf', '2025-03-30 10:19:31', 0);

-- --------------------------------------------------------

--
-- Table structure for table `newsletter_subscribers`
--

CREATE TABLE `newsletter_subscribers` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `subscribed_at` datetime NOT NULL,
  `status` enum('active','unsubscribed') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `newsletter_subscribers`
--

INSERT INTO `newsletter_subscribers` (`id`, `email`, `subscribed_at`, `status`) VALUES
(1, 'snehas67248@gmail.com', '2025-04-16 12:37:32', 'active'),
(2, 'emman301004@gmail.com', '2025-04-16 12:39:54', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','processing','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
  `shipping_address` text NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `total_amount`, `status`, `shipping_address`, `payment_method`, `created_at`) VALUES
(6, 2, 2714.00, 'pending', 'Sneha\nPsk Naidu Road\nFrazer Town, Karnataka 560005\nIndia', 'cod', '2025-04-01 07:15:11'),
(7, 2, 5428.00, 'pending', 'Sneha\n23 A 1 block khb colony psk Naidu Road Coxtown\nBangalore, Karnataka 560005\nIndia', 'cod', '2025-04-01 07:25:28'),
(8, 2, 2714.00, 'shipped', 'Sneha\nPsk Naidu Road\nFrazer Town, Karnataka 560005\nIndia', 'cod', '2025-04-05 08:14:53'),
(9, 3, 5428.00, 'pending', 'Emmanuel\nPsk Naidu Road\nFrazer Town, Karnataka 560005\nIndia', 'cod', '2025-04-06 01:50:27'),
(10, 3, 3186.00, 'processing', 'Emmanuel\nPsk Naidu Road\nFrazer Town, Karnataka 560005\nIndia', 'cod', '2025-04-06 15:59:08'),
(12, 2, 14962.40, 'pending', 'Sneha\n23 A 1 block khb colony psk Naidu Road Coxtown\nBangalore, Karnataka 560005\nIndia', 'cod', '2025-04-17 06:33:05');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_size_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_size_id`, `quantity`, `price`) VALUES
(6, 6, 1, 1, 1, 2300.00),
(7, 7, 1, 1, 2, 2300.00),
(8, 8, 1, 1, 1, 2300.00),
(9, 9, 1, 1, 2, 2300.00),
(10, 10, 6, 10, 1, 2700.00),
(12, 12, 7, 13, 1, 4050.00),
(13, 12, 9, 16, 1, 3580.00),
(14, 12, 10, 18, 1, 2700.00),
(15, 12, 8, 14, 1, 2350.00);

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(100) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `user_id`, `token`, `expires_at`, `created_at`) VALUES
(1, 1, '3c46bfa964e27d3a1409335bc5fc37a576d571e7d1794055e8cf0c4fefb79c9c', '2025-04-06 11:25:12', '2025-04-06 08:25:12');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `base_price` decimal(10,2) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `base_price`, `image`, `category_id`, `created_at`) VALUES
(1, 'puma', 'super ', 2000.00, '1743226803_girl-aesthetic-desktop-0zi7cn4q3y2wakt0.jpg', 4, '2025-03-29 05:40:03'),
(2, 'Nike Air Max', 'High-performance sports shoes', 3500.00, 'nike_air_max.jpg', 3, '2025-04-06 15:39:37'),
(3, 'Adidas Sneakers', 'Casual and trendy shoes', 2800.00, 'adidas_sneakers.jpg', 4, '2025-04-06 15:39:37'),
(4, 'Clarks Formal', 'Elegant formal shoes for men', 4200.00, 'clarks_formal.jpg', 5, '2025-04-06 15:39:37'),
(5, 'Reebok Runner', 'Men\'s running shoes', 3000.00, 'reebok_runner.jpg', 1, '2025-04-06 15:39:37'),
(6, 'Sketchers Slip-On', 'Comfortable women\'s casual shoes', 2600.00, 'sketchers_slip_on.jpg', 2, '2025-04-06 15:39:37'),
(7, 'Woodland Boots', 'Rugged boots for outdoor adventures', 3800.00, 'woodland_boots.jpg', 1, '2025-04-06 15:40:44'),
(8, 'Bata Comfort Loafers', 'Everyday comfort loafers for men', 2200.00, 'bata_loafers.jpg', 1, '2025-04-06 15:40:44'),
(9, 'Nike Air Zoom', 'Performance running shoes for women', 3400.00, 'nike_air_zoom.jpg', 2, '2025-04-06 15:40:44'),
(10, 'Metro Heels', 'Stylish heels for formal occasions', 2600.00, 'metro_heels.jpg', 2, '2025-04-06 15:40:44'),
(11, 'Asics Gel Kayano', 'Advanced stability running shoes', 4500.00, 'asics_kayano.jpg', 3, '2025-04-06 15:40:44'),
(12, 'Under Armour Charged', 'High cushioning sports sneakers', 3700.00, 'ua_charged.jpg', 3, '2025-04-06 15:40:44'),
(13, 'Vans Classic', 'Iconic casual skate shoes', 3000.00, 'vans_classic.jpg', 4, '2025-04-06 15:40:44'),
(14, 'Converse Chuck Taylor', 'All-time favorite casual shoes', 2800.00, 'converse_chuck.jpg', 4, '2025-04-06 15:40:44'),
(15, 'Hush Puppies Oxford', 'Premium leather formal shoes', 4100.00, 'hush_oxford.jpg', 5, '2025-04-06 15:40:44'),
(16, 'Red Tape Derby', 'Stylish lace-up business shoes', 3900.00, 'redtape_derby.jpg', 5, '2025-04-06 15:40:44');

-- --------------------------------------------------------

--
-- Table structure for table `product_sizes`
--

CREATE TABLE `product_sizes` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `size` varchar(10) NOT NULL,
  `price_adjustment` decimal(10,2) DEFAULT 0.00,
  `stock` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_sizes`
--

INSERT INTO `product_sizes` (`id`, `product_id`, `size`, `price_adjustment`, `stock`) VALUES
(1, 1, 'US 10', 300.00, 200),
(2, 2, 'US 9', 200.00, 100),
(3, 2, 'US 10', 300.00, 80),
(4, 3, 'US 8', 150.00, 50),
(5, 3, 'US 9', 200.00, 60),
(6, 4, 'US 10', 250.00, 70),
(7, 4, 'US 11', 300.00, 40),
(8, 5, 'US 8', 180.00, 90),
(9, 5, 'US 9', 200.00, 85),
(10, 6, 'US 7', 100.00, 120),
(11, 6, 'US 8', 150.00, 100),
(12, 7, 'US 9', 200.00, 50),
(13, 7, 'US 10', 250.00, 40),
(14, 8, 'US 8', 150.00, 60),
(15, 8, 'US 9', 200.00, 45),
(16, 9, 'US 7', 180.00, 70),
(17, 9, 'US 8', 220.00, 65),
(18, 10, 'US 6', 100.00, 30),
(19, 10, 'US 7', 120.00, 25),
(20, 11, 'US 9', 300.00, 55),
(21, 11, 'US 10', 350.00, 50),
(22, 12, 'US 10', 250.00, 40),
(23, 12, 'US 11', 280.00, 35),
(24, 13, 'US 8', 150.00, 80),
(25, 13, 'US 9', 180.00, 75),
(26, 14, 'US 8', 120.00, 70),
(27, 14, 'US 9', 140.00, 65),
(28, 15, 'US 9', 200.00, 40),
(29, 15, 'US 10', 250.00, 35);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `created_at`) VALUES
(2, 'Sneha', 'snehas6724@gmail.com', '$2y$10$AXmRcdpDQBVLRanDlUEYmenhuoL11IZBNL16FWLUGd/q/omCIDNqS', '2025-03-30 08:35:40'),
(3, 'Emmanuel', 'emman301004@gmail.com', '$2y$10$W5tNkmX3/ySO0XKzbJ87oOVz2teLj3NrXwFkGiE9FeCgMSAUSZz8G', '2025-04-06 01:35:17');

-- --------------------------------------------------------

--
-- Table structure for table `wishlists`
--

CREATE TABLE `wishlists` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT 'My Wishlist',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wishlists`
--

INSERT INTO `wishlists` (`id`, `user_id`, `name`, `created_at`) VALUES
(2, 2, 'My Wishlist', '2025-04-01 05:42:37'),
(3, 3, 'My Wishlist', '2025-04-06 08:05:49');

-- --------------------------------------------------------

--
-- Table structure for table `wishlist_items`
--

CREATE TABLE `wishlist_items` (
  `id` int(11) NOT NULL,
  `wishlist_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wishlist_items`
--

INSERT INTO `wishlist_items` (`id`, `wishlist_id`, `product_id`, `added_at`) VALUES
(20, 3, 1, '2025-04-06 08:05:49'),
(21, 3, 8, '2025-04-06 16:07:22'),
(22, 2, 8, '2025-04-11 06:02:33');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `product_size_id` (`product_size_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `newsletter_subscribers`
--
ALTER TABLE `newsletter_subscribers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `product_size_id` (`product_size_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `password_resets_token_index` (`token`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `product_sizes`
--
ALTER TABLE `product_sizes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `product_size` (`product_id`,`size`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `wishlists`
--
ALTER TABLE `wishlists`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `wishlist_items`
--
ALTER TABLE `wishlist_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `wishlist_product` (`wishlist_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `newsletter_subscribers`
--
ALTER TABLE `newsletter_subscribers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `product_sizes`
--
ALTER TABLE `product_sizes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `wishlists`
--
ALTER TABLE `wishlists`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `wishlist_items`
--
ALTER TABLE `wishlist_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `cart_ibfk_3` FOREIGN KEY (`product_size_id`) REFERENCES `product_sizes` (`id`);

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `order_items_ibfk_3` FOREIGN KEY (`product_size_id`) REFERENCES `product_sizes` (`id`);

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);

--
-- Constraints for table `product_sizes`
--
ALTER TABLE `product_sizes`
  ADD CONSTRAINT `product_sizes_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wishlists`
--
ALTER TABLE `wishlists`
  ADD CONSTRAINT `wishlists_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wishlist_items`
--
ALTER TABLE `wishlist_items`
  ADD CONSTRAINT `wishlist_items_ibfk_1` FOREIGN KEY (`wishlist_id`) REFERENCES `wishlists` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wishlist_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
