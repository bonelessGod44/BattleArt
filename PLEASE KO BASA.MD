DEPENDENCIES NEEDE:
XAMPP
PHP
COMPOSER
PHPMAILER

Tables
CREATE TABLE `challenges` (
 `challenge_id` int(11) NOT NULL AUTO_INCREMENT,
 `user_id` int(11) NOT NULL,
 `challenge_name` varchar(255) NOT NULL,
 `challenge_description` text NOT NULL,
 `category` varchar(100) NOT NULL,
 `original_art_filename` varchar(255) NOT NULL,
 `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
 PRIMARY KEY (`challenge_id`),
 KEY `user_id` (`user_id`),
 CONSTRAINT `challenges_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
comments	
CREATE TABLE `comments` (
 `comment_id` int(11) NOT NULL AUTO_INCREMENT,
 `user_id` int(11) NOT NULL,
 `challenge_id` int(11) NOT NULL,
 `comment_text` text NOT NULL,
 `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
 PRIMARY KEY (`comment_id`),
 KEY `user_id` (`user_id`),
 KEY `challenge_id` (`challenge_id`),
 CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
 CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`challenge_id`) REFERENCES `challenges` (`challenge_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
interpretations	
CREATE TABLE `interpretations` (
 `interpretation_id` int(11) NOT NULL AUTO_INCREMENT,
 `challenge_id` int(11) NOT NULL,
 `user_id` int(11) NOT NULL,
 `description` text DEFAULT NULL,
 `art_filename` varchar(255) NOT NULL,
 `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
 PRIMARY KEY (`interpretation_id`),
 KEY `challenge_id` (`challenge_id`),
 KEY `user_id` (`user_id`),
 CONSTRAINT `interpretations_ibfk_1` FOREIGN KEY (`challenge_id`) REFERENCES `challenges` (`challenge_id`) ON DELETE CASCADE,
 CONSTRAINT `interpretations_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
likes	
CREATE TABLE `likes` (
 `like_id` int(11) NOT NULL AUTO_INCREMENT,
 `user_id` int(11) NOT NULL,
 `challenge_id` int(11) NOT NULL,
 `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
 PRIMARY KEY (`like_id`),
 UNIQUE KEY `unique_like` (`user_id`,`challenge_id`),
 KEY `challenge_id` (`challenge_id`),
 CONSTRAINT `likes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
 CONSTRAINT `likes_ibfk_2` FOREIGN KEY (`challenge_id`) REFERENCES `challenges` (`challenge_id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
notifications	
CREATE TABLE `notifications` (
 `notification_id` int(11) NOT NULL AUTO_INCREMENT,
 `recipient_user_id` int(11) NOT NULL,
 `sender_user_id` int(11) NOT NULL,
 `type` enum('like','comment','interpretation') NOT NULL,
 `target_id` int(11) NOT NULL COMMENT 'ID of the challenge or interpretation',
 `is_read` tinyint(1) NOT NULL DEFAULT 0,
 `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
 PRIMARY KEY (`notification_id`),
 KEY `recipient_user_id` (`recipient_user_id`),
 KEY `sender_user_id` (`sender_user_id`),
 CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`recipient_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
 CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`sender_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
users	
CREATE TABLE `users` (
 `user_id` int(11) NOT NULL AUTO_INCREMENT,
 `user_firstName` varchar(255) NOT NULL,
 `user_middleName` varchar(255) NOT NULL,
 `user_lastName` varchar(255) NOT NULL,
 `user_email` varchar(255) NOT NULL,
 `user_password` varchar(255) NOT NULL,
 `user_type` varchar(255) NOT NULL,
 `user_userName` varchar(255) NOT NULL,
 `user_testing` int(11) DEFAULT NULL,
 `reset_token_hash` varchar(255) DEFAULT NULL,
 `reset_token_expires_at` datetime DEFAULT NULL,
 `user_profile_pic` varchar(255) DEFAULT NULL,
 `show_art` tinyint(1) NOT NULL DEFAULT 1,
 `show_history` tinyint(1) NOT NULL DEFAULT 1,
 `show_comments` tinyint(1) NOT NULL DEFAULT 1,
 `user_bio` text DEFAULT NULL,
 `user_banner_pic` varchar(255) DEFAULT NULL,
 `user_dob` date DEFAULT NULL,
 `allow_notifications` tinyint(1) NOT NULL DEFAULT 1,
 PRIMARY KEY (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
CREATE TABLE `ratings` (
  `rating_id` INT(11) NOT NULL AUTO_INCREMENT,
  `rater_user_id` INT(11) NOT NULL,
  `rated_user_id` INT(11) NOT NULL,
  `rating_value` DECIMAL(2,1) NOT NULL CHECK (`rating_value` BETWEEN 0 AND 5),
  `created_at` TIMESTAMP NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`rating_id`),
  KEY `rater_user_id` (`rater_user_id`),
  KEY `rated_user_id` (`rated_user_id`),
  CONSTRAINT `ratings_ibfk_1` FOREIGN KEY (`rater_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `ratings_ibfk_2` FOREIGN KEY (`rated_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
