<?php
// Test concern submission
require_once 'api/db.php';

// Test data
$testData = [
    'name' => 'Test User',
    'email' => 'test@example.com',
    'concern_type' => 'Infrastructure',
    'message' => 'This is a test concern message.'
];

// Insert test concern
$stmt = $conn->prepare('INSERT INTO concerns (name, email, concern_type, message) VALUES (?, ?, ?, ?)');
$stmt->bind_param('ssss', $testData['name'], $testData['email'], $testData['concern_type'], $testData['message']);

if ($stmt->execute()) {
    echo "Test concern inserted successfully. ID: " . $conn->insert_id . "\n";
    
    // Retrieve and display the concern
    $result = $conn->query('SELECT * FROM concerns ORDER BY id DESC LIMIT 1');
    if ($row = $result->fetch_assoc()) {
        echo "Retrieved concern:\n";
        print_r($row);
    }
} else {
    echo "Error inserting test concern: " . $conn->error . "\n";
}

$stmt->close();
$conn->close();
?>
