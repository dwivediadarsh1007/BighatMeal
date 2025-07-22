CREATE TABLE IF NOT EXISTS `custom_meal_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category` enum('fruits','vegetables') NOT NULL,
  `name` varchar(100) NOT NULL,
  `calories` decimal(10,2) NOT NULL,
  `protein` decimal(10,2) NOT NULL,
  `carbs` decimal(10,2) NOT NULL,
  `fat` decimal(10,2) NOT NULL,
  `fiber` decimal(10,2) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_category` (`name`,`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
