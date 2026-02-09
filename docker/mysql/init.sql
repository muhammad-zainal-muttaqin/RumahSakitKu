-- Create database if not exists
CREATE DATABASE IF NOT EXISTS rumahsakitu_simrs CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create user with privileges
CREATE USER IF NOT EXISTS 'simrs'@'%' IDENTIFIED BY 'secret';
GRANT ALL PRIVILEGES ON rumahsakitu_simrs.* TO 'simrs'@'%';
FLUSH PRIVILEGES;
