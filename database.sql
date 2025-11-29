CREATE DATABASE IF NOT EXISTS brgy_budget CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE brgy_budget;

CREATE TABLE IF NOT EXISTS budget_allocations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category VARCHAR(255) NOT NULL,
    allocated DECIMAL(15,2) NOT NULL DEFAULT 0,
    spent DECIMAL(15,2) NOT NULL DEFAULT 0,
    status ENUM('Initial', 'Ongoing', 'Pending', 'Completed') NOT NULL DEFAULT 'Initial',
    project_progress TEXT DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT INTO budget_allocations (category, allocated, spent, status) VALUES
('Personnel Services (Salaries)', 3200000, 2800000, 'Ongoing'),
('Maintenance and Operating Expenses (MOOE)', 4500000, 2100000, 'Ongoing'),
('20% Development Fund (Infrastructure)', 2000000, 1500000, 'Completed'),
('Calamity Fund (5%)', 600000, 0, 'Initial'),
('SK Fund (Youth Programs)', 800000, 300000, 'Pending'),
('Gender and Development (GAD)', 900000, 450000, 'Ongoing');

CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO admins (username, password_hash) VALUES
('admin', '$2y$10$CwTycUXWue0Thq9StjUM0uJ8uP/7d1DEuDfSU/E1GYm4VXOvNhWCa')
ON DUPLICATE KEY UPDATE username = username;

CREATE TABLE IF NOT EXISTS posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    image_url VARCHAR(500) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO posts (title, body) VALUES
('Road Rehabilitation Update', 'Ongoing works along the main thoroughfare will continue nightly to minimize daytime disruptions. Expect completion by Q2.'),
('Health Center Expansion', 'The barangay health center is adding two additional consultation rooms and a dedicated vaccination area. Construction starts next week.');

CREATE TABLE IF NOT EXISTS chat_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    conversation_id VARCHAR(100) NOT NULL,
    sender_type ENUM('user', 'admin') NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_conversation (conversation_id),
    INDEX idx_created (created_at)
);

CREATE TABLE IF NOT EXISTS gallery_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    image_url VARCHAR(500) NOT NULL,
    alt_text VARCHAR(255) DEFAULT 'Barangay project image',
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_order (display_order)
);

INSERT INTO gallery_images (image_url, alt_text, display_order) VALUES
('image 1.jpg', 'Construction crew working on foundation', 1),
('image 2.jpeg', 'Road paving team smoothing concrete', 2),
('image 3.jpg', 'Construction crew working on foundation', 3),
('image 4.jpg', 'Road paving team smoothing concrete', 4),
('jayve.png', 'Barangay project image', 5);

