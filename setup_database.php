<?php
/**
 * Database Setup Script
 * Run this file once to create the database and tables
 * Access via: http://localhost/ta/setup_database.php
 */

// Enable error reporting
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
    echo "<p class='success'>✓ Database 'brgy_budget' created successfully or already exists.</p>";
} else {
    die("<p class='error'>Error creating database: " . $conn->error . "</p></body></html>");
}

// Select the database
$conn->select_db('brgy_budget');
echo "<p class='info'>→ Using database 'brgy_budget'</p>";

// Read and execute database.sql
$sqlFile = __DIR__ . '/database.sql';
if (!file_exists($sqlFile)) {
    die("<p class='error'>Error: database.sql file not found at: " . $sqlFile . "</p></body></html>");
}

$sql = file_get_contents($sqlFile);

// Remove CREATE DATABASE and USE statements
$sql = preg_replace('/CREATE DATABASE.*?;/is', '', $sql);
$sql = preg_replace('/USE.*?;/is', '', $sql);

// Use multi_query to execute all statements at once
$executed = 0;
$errors = 0;

if ($conn->multi_query($sql)) {
    do {
        // Store first result set
        if ($result = $conn->store_result()) {
            $result->free();
        }
        $executed++;
        
        // Check for more results
        if ($conn->more_results()) {
            $conn->next_result();
        } else {
            break;
        }
    } while (true);
    
    // Clear any remaining results
    while ($conn->more_results() && $conn->next_result()) {
        if ($result = $conn->store_result()) {
            $result->free();
        }
    }
} else {
    $errorMsg = $conn->error;
    // Ignore duplicate key errors and table already exists errors
    if (strpos($errorMsg, 'Duplicate entry') === false && 
        strpos($errorMsg, 'already exists') === false &&
        strpos($errorMsg, 'Duplicate key') === false &&
        strpos($errorMsg, 'Duplicate column') === false) {
        echo "<p class='error'>Warning: " . htmlspecialchars(substr($errorMsg, 0, 200)) . "</p>";
        $errors++;
    }
}

echo "<p class='info'>→ Executed $executed SQL statements from database.sql</p>";

// Read and execute database_update.sql if it exists
$updateFile = __DIR__ . '/database_update.sql';
if (file_exists($updateFile)) {
    echo "<p class='info'>→ Applying database updates...</p>";
    $updateSql = file_get_contents($updateFile);
    $updateSql = preg_replace('/USE.*?;/is', '', $updateSql);
    
    if ($conn->multi_query($updateSql)) {
        do {
            if ($result = $conn->store_result()) {
                $result->free();
            }
            $executed++;
            if ($conn->more_results()) {
                $conn->next_result();
            } else {
                break;
            }
        } while (true);
        
        while ($conn->more_results() && $conn->next_result()) {
            if ($result = $conn->store_result()) {
                $result->free();
            }
        }
    } else {
        $errorMsg = $conn->error;
        if (strpos($errorMsg, 'Duplicate entry') === false && 
            strpos($errorMsg, 'already exists') === false &&
            strpos($errorMsg, 'Duplicate key') === false &&
            strpos($errorMsg, 'Duplicate column') === false) {
            echo "<p class='error'>Warning: " . htmlspecialchars(substr($errorMsg, 0, 200)) . "</p>";
            $errors++;
        }
    }
}

// Verify tables were created
$tables = ['budget_allocations', 'admins', 'posts', 'chat_messages', 'gallery_images', 'concerns'];
echo "<h2>Verifying Tables:</h2><ul>";
foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result && $result->num_rows > 0) {
        echo "<li class='success'>✓ Table '$table' exists</li>";
    } else {
        echo "<li class='error'>✗ Table '$table' missing</li>";
    }
}
echo "</ul>";

if ($errors == 0) {
    echo "<h2 class='success'>✓ Database setup completed successfully!</h2>";
    echo "<p>You can now <a href='index.php'>access the application</a>.</p>";
} else {
    echo "<h2 class='error'>⚠ Setup completed with $errors warnings.</h2>";
    echo "<p>You can try <a href='index.php'>accessing the application</a> to see if it works.</p>";
}

$conn->close();
echo "</body></html>";
?>

