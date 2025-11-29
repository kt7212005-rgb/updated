<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please log in.']);
    exit;
}

if (!isset($_POST['title'], $_POST['body'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Title and body are required.']);
    exit;
}

$title = trim($_POST['title']);
$body = trim($_POST['body']);

if ($title === '' || $body === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Title and body must not be empty.']);
    exit;
}

$imageUrl = null;

// Handle file upload if image is provided
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['image'];
    
    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, GIF, and WebP images are allowed.']);
        exit;
    }
    
    // Validate file size (max 5MB)
    $maxSize = 5 * 1024 * 1024; // 5MB
    if ($file['size'] > $maxSize) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'File is too large. Maximum size is 5MB.']);
        exit;
    }
    
    // Create uploads directory if it doesn't exist
    $uploadDir = __DIR__ . '/../uploads/posts/';
    if (!file_exists($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to create upload directory.']);
            exit;
        }
    }
    
    // Generate unique filename
    $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = 'post_' . time() . '_' . uniqid() . '.' . $fileExtension;
    $filePath = $uploadDir . $fileName;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to save uploaded file.']);
        exit;
    }
    
    // Relative path for database storage
    $imageUrl = 'uploads/posts/' . $fileName;
}

require_once __DIR__ . '/db.php';

$stmt = $conn->prepare('INSERT INTO posts (title, body, image_url) VALUES (?, ?, ?)');
$stmt->bind_param('sss', $title, $body, $imageUrl);

if (!$stmt->execute()) {
    // Delete uploaded file if database insert fails
    if ($imageUrl && file_exists(__DIR__ . '/../' . $imageUrl)) {
        @unlink(__DIR__ . '/../' . $imageUrl);
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to create post: ' . $stmt->error]);
    $stmt->close();
    $conn->close();
    exit;
}

$newId = $stmt->insert_id;
$stmt->close();

$selectStmt = $conn->prepare('SELECT * FROM posts WHERE id = ? LIMIT 1');
$selectStmt->bind_param('i', $newId);
$selectStmt->execute();
$result = $selectStmt->get_result();
$post = $result->fetch_assoc();

$selectStmt->close();
$conn->close();

echo json_encode(['success' => true, 'post' => $post]);

