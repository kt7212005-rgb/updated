<?php
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');
require_once __DIR__ . '/db.php';

// Check if table exists first
$tableCheck = $conn->query("SHOW TABLES LIKE 'gallery_images'");
if ($tableCheck->num_rows == 0) {
    // Table doesn't exist, return empty array
    echo json_encode([]);
    $conn->close();
    exit;
}

$sql = 'SELECT id, image_url, alt_text, display_order FROM gallery_images ORDER BY display_order ASC, created_at ASC';
$result = $conn->query($sql);

if (!$result) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to load gallery: ' . $conn->error]);
    $conn->close();
    exit;
}

$images = [];
while ($row = $result->fetch_assoc()) {
    $images[] = $row;
}

// Always return an array, even if empty
echo json_encode($images);
$conn->close();

