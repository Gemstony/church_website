-- Create database
CREATE DATABASE IF NOT EXISTS `church_db`
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `church_db`;

-- 1. settings table (dynamic site configuration)
CREATE TABLE `settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `setting_key` VARCHAR(100) NOT NULL UNIQUE,
  `setting_value` TEXT,
  `setting_type` ENUM('text', 'color', 'image', 'textarea', 'url') DEFAULT 'text',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 2. users table (admin + members)
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(100) NOT NULL,
  `role` ENUM('admin', 'member') DEFAULT 'member',
  `profile_pic` VARCHAR(255) DEFAULT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `last_login` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_email` (`email`),
  INDEX `idx_role` (`role`)
);

-- 3. user_profiles (additional member info)
CREATE TABLE `user_profiles` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `bio` TEXT DEFAULT NULL,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_user_profile` (`user_id`)
);

-- 4. cvs (each member can have one CV)
CREATE TABLE `cvs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `file_name` VARCHAR(255) NOT NULL,
  `file_path` VARCHAR(255) NOT NULL,
  `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_user_cv` (`user_id`),
  INDEX `idx_user` (`user_id`)
);

-- 5. events table (supports start/end dates for calendar)
CREATE TABLE `events` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(200) NOT NULL,
  `description` TEXT,
  `event_date` DATETIME NOT NULL,          -- start date/time
  `event_end_date` DATETIME DEFAULT NULL,  -- optional end date/time
  `location` VARCHAR(255),
  `image` VARCHAR(255) DEFAULT NULL,
  `created_by` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_event_date` (`event_date`)
);

-- 6. event_registrations (track member attendance for reports)
CREATE TABLE `event_registrations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `event_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `registered_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`event_id`) REFERENCES `events`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_registration` (`event_id`, `user_id`),
  INDEX `idx_event` (`event_id`),
  INDEX `idx_user` (`user_id`)
);

-- 7. media_gallery (images/videos)
CREATE TABLE `media_gallery` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(200) NOT NULL,
  `description` TEXT,
  `file_path` VARCHAR(255) NOT NULL,
  `file_type` ENUM('image', 'video') DEFAULT 'image',
  `uploaded_by` INT NOT NULL,
  `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`uploaded_by`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_type` (`file_type`)
);

-- 8. contact_messages (from visitors)
CREATE TABLE `contact_messages` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `is_read` TINYINT(1) DEFAULT 0,
  `submitted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_read` (`is_read`)
);

-- 9. password_resets (for "forgot password" feature)
CREATE TABLE `password_resets` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(255) NOT NULL,
  `token` VARCHAR(255) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_token` (`token`),
  INDEX `idx_email` (`email`)
);

ALTER TABLE `event_registrations` 
ADD COLUMN `phone` VARCHAR(20) DEFAULT NULL AFTER `user_id`;

-- Default settings

INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_type`) VALUES
('site_name', 'Our Church', 'text'),
('logo_path', 'uploads/settings/logo-default.png', 'image'),
('primary_color', '#0d6efd', 'color'),
('secondary_color', '#6c757d', 'color'),
('about_content', '<h2>About Our Church</h2><p>We are a community of believers...</p>', 'textarea'),
('contact_email', 'info@ourchurch.org', 'text'),
('contact_phone', '+1 234 567 8900', 'text'),
('contact_address', '123 Faith Street, City, Country', 'text'),
('footer_text', '© 2025 Our Church. All rights reserved.', 'text'),
('facebook_url', 'https://facebook.com/ourchurch', 'url'),
('youtube_url', '', 'url'),
('google_maps_embed', '', 'textarea'),
('calendar_default_view', 'dayGridMonth', 'text');


-- default user
-- Password = "Admin123!" (hashed with PHP password_hash())
INSERT INTO `users` (`email`, `password_hash`, `full_name`, `role`) VALUES
('scar@gmail.com', '$2y$10$1vuhpzX2ln8gpiLvkZzwSeWiseE4Yxllx9/IoV9Aa9WStUKSJVWje', 'Super Admin', 'admin');