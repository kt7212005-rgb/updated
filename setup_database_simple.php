<?php
/**
 * Simple Database Setup Script
 * Run this via browser: http://localhost/ta/setup_database_simple.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = '';

echo "<!DOCTYPE html><html><head><title>Database Setup</title>";
echo "<style>body{font-family:Arial,sans-serif;max-width:800px;margin:50px auto;padding:20px;}";
echo ".success{color:green;}.error{color:red;}.info{color:blue;}</style></head><body>";
echo "<h1>Database Setup Script</h1>";

// Connect without selecting a database first
$conn = new mysqli($dbHost, $dbUser, $dbPass);

if ($conn->connect_error) {
    die("<p class='error'>Connection failed: " . $conn->connect_error . "</p></body></html>");
}

echo "<p class='success'>✓ Connected to MySQL server.</p>";

// Create database
$sql = "CREATE DATABASE IF NOT EXISTS brgy_budget CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
if ($conn->query($sql) === TRUE) {
    echo "<p class='success'>✓ Database 'brgy_budget' created successfully.</p>";
} else {
    die("<p class='error'>Error creating database: " . $conn->error . "</p></body></html>");
}

// Select the database
$conn->select_db('brgy_budget');
echo "<p class='info'>→ Using database 'brgy_budget'</p>";

// Execute SQL statements one by one
$statements = [
    // Create budget_allocations table
    "CREATE TABLE IF NOT EXISTS budget_allocations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        category VARCHAR(255) NOT NULL,
        allocated DECIMAL(15,2) NOT NULL DEFAULT 0,
        spent DECIMAL(15,2) NOT NULL DEFAULT 0,
        status ENUM('Initial', 'Ongoing', 'Pending', 'Completed') NOT NULL DEFAULT 'Initial',
        project_progress TEXT DEFAULT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )",
    
    // Insert budget data
    "INSERT INTO budget_allocations (category, allocated, spent, status) VALUES
    ('Personnel Services (Salaries)', 3200000, 2800000, 'Ongoing'),
    ('Maintenance and Operating Expenses (MOOE)', 4500000, 2100000, 'Ongoing'),
    ('20% Development Fund (Infrastructure)', 2000000, 1500000, 'Completed'),
    ('Calamity Fund (5%)', 600000, 0, 'Initial'),
    ('SK Fund (Youth Programs)', 800000, 300000, 'Pending'),
    ('Gender and Development (GAD)', 900000, 450000, 'Ongoing')
    ON DUPLICATE KEY UPDATE category=category",
    
    // Create admins table
    "CREATE TABLE IF NOT EXISTS admins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(100) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    
    // Insert admin (password: admin)
    'INSERT INTO admins (username, password_hash) VALUES
    (\'admin\', \'$2y$10$CwTycUXWue0Thq9StjUM0uJ8uP/7d1DEuDfSU/E1GYm4VXOvNhWCa\')
    ON DUPLICATE KEY UPDATE username = username',
    
    // Create posts table
    "CREATE TABLE IF NOT EXISTS posts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        body TEXT NOT NULL,
        image_url VARCHAR(500) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    
    // Insert posts
    "INSERT INTO posts (title, body) VALUES
    ('Road Rehabilitation Update', 'Ongoing works along the main thoroughfare will continue nightly to minimize daytime disruptions. Expect completion by Q2.'),
    ('Health Center Expansion', 'The barangay health center is adding two additional consultation rooms and a dedicated vaccination area. Construction starts next week.')
    ON DUPLICATE KEY UPDATE title=title",
    
    // Create chat_messages table
    "CREATE TABLE IF NOT EXISTS chat_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        conversation_id VARCHAR(100) NOT NULL,
        sender_type ENUM('user', 'admin') NOT NULL,
        message TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_conversation (conversation_id),
        INDEX idx_created (created_at)
    )",
    
    // Create gallery_images table
    "CREATE TABLE IF NOT EXISTS gallery_images (
        id INT AUTO_INCREMENT PRIMARY KEY,
        image_url VARCHAR(500) NOT NULL,
        alt_text VARCHAR(255) DEFAULT 'Barangay project image',
        display_order INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_order (display_order)
    )",
    
    // Insert gallery images
    "INSERT INTO gallery_images (image_url, alt_text, display_order) VALUES
    ('image 1.jpg', 'Construction crew working on foundation', 1),
    ('image 2.jpeg', 'Road paving team smoothing concrete', 2),
    ('image 3.jpg', 'Construction crew working on foundation', 3),
    ('image 4.jpg', 'Road paving team smoothing concrete', 4),
    ('jayve.png', 'Barangay project image', 5)
    ON DUPLICATE KEY UPDATE image_url=image_url",
    
    // Create concerns table
    "CREATE TABLE IF NOT EXISTS concerns (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(255) DEFAULT NULL,
        concern_type ENUM('Infrastructure', 'Health Services', 'Security', 'Environment', 'Social Services', 'Others') NOT NULL,
        message TEXT NOT NULL,
        status ENUM('Pending', 'In Progress', 'Resolved') NOT NULL DEFAULT 'Pending',
        admin_response TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_status (status),
        INDEX idx_created (created_at)
    )"
];

$executed = 0;
$errors = 0;

foreach ($statements as $index => $sql) {
    if ($conn->query($sql) === FALSE) {
        $errorMsg = $conn->error;
        // Ignore duplicate key errors and table already exists errors
        if (strpos($errorMsg, 'Duplicate entry') === false && 
            strpos($errorMsg, 'already exists') === false &&
            strpos($errorMsg, 'Duplicate key') === false &&
            strpos($errorMsg, 'Duplicate column') === false) {
            echo "<p class='error'>Error on statement " . ($index + 1) . ": " . htmlspecialchars(substr($errorMsg, 0, 200)) . "</p>";
            $errors++;
        }
    } else {
        $executed++;
    }
}

echo "<p class='info'>→ Executed $executed SQL statements</p>";

// Verify tables were created
$tables = ['budget_allocations', 'admins', 'posts', 'chat_messages', 'gallery_images', 'concerns'];
echo "<h2>Verifying Tables:</h2><ul>";
foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result && $result->num_rows > 0) {
        $count = $conn->query("SELECT COUNT(*) as cnt FROM $table")->fetch_assoc()['cnt'];
        echo "<li class='success'>✓ Table '$table' exists ($count rows)</li>";
    } else {
        echo "<li class='error'>✗ Table '$table' missing</li>";
    }
}
echo "</ul>";

if ($errors == 0) {
    echo "<h2 class='success'>✓ Database setup completed successfully!</h2>";
    echo "<p>You can now <a href='index.php'>access the application</a>.</p>";
} else {
    echo "<h2 class='error'>⚠ Setup completed with $errors errors.</h2>";
    echo "<p>You can try <a href='index.php'>accessing the application</a> to see if it works.</p>";
}

$conn->close();
echo "</body></html>";
?>

