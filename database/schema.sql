-- ============================================================
-- Website Monitoring System with Telegram Alerts
-- Database Schema
-- ============================================================

CREATE DATABASE IF NOT EXISTS website_monitor CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE website_monitor;

-- ------------------------------------------------------------
-- Admins table (single admin role, session-based login)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Default admin account
-- Username: admin
-- Password: admin123   <-- CHANGE THIS IMMEDIATELY AFTER FIRST LOGIN
INSERT INTO admins (username, password) VALUES
('admin', '$2b$10$phNRNtqV8U0f4bALrHmEhuOgyErITKLvpI.8CTlcuzpsRH/pNLU9K');

-- ------------------------------------------------------------
-- Websites table
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS websites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    url VARCHAR(500) NOT NULL,
    interval_minutes INT NOT NULL DEFAULT 5,
    status ENUM('UP', 'DOWN', 'PENDING') NOT NULL DEFAULT 'PENDING',
    response_time INT DEFAULT NULL,          -- milliseconds
    last_checked DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Logs table (full monitoring history)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    website_id INT NOT NULL,
    status ENUM('UP', 'DOWN') NOT NULL,
    response_time INT DEFAULT NULL,          -- milliseconds
    checked_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (website_id) REFERENCES websites(id) ON DELETE CASCADE,
    INDEX idx_website_checked (website_id, checked_at)
) ENGINE=InnoDB;
