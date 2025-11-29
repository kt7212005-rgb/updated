<?php
// Suppress error display and ensure clean JSON output
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

session_start();
header('Content-Type: application/json');

try {
    require_once __DIR__ . '/db.php';
    
    if (!isset($_SESSION['admin_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized. Please log in.']);
        exit;
    }
    
    // Check if file was uploaded
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        $errorMsg = 'No file uploaded.';
        if (isset($_FILES['image']['error'])) {
            switch ($_FILES['image']['error']) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $errorMsg = 'File is too large.';
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $errorMsg = 'File upload was incomplete.';
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $errorMsg = 'No file was uploaded.';
                    break;
                default:
                    $errorMsg = 'File upload error occurred.';
            }
        }
        echo json_encode(['success' => false, 'message' => $errorMsg]);
        exit;
    }
    
    $file = $_FILES['image'];
    $altText = isset($_POST['alt_text']) ? trim($_POST['alt_text']) : 'Barangay project image';
    $displayOrder = isset($_POST['display_order']) ? intval($_POST['display_order']) : 0;
    
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
    $uploadDir = __DIR__ . '/../uploads/gallery/';
    if (!file_exists($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to create upload directory.']);
            exit;
        }
    }
    
    // Generate unique filename
    $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = 'gallery_' . time() . '_' . uniqid() . '.' . $fileExtension;
    $filePath = $uploadDir . $fileName;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to save uploaded file.']);
        exit;
    }
    
    // Relative path for database storage
    $relativePath = 'uploads/gallery/' . $fileName;
    
    // Check if table exists, if not create it
    $tableCheck = $conn->query("SHOW TABLES LIKE 'gallery_images'");
    if ($tableCheck->num_rows == 0) {
        $createTable = "CREATE TABLE IF NOT EXISTS gallery_images (
            id INT AUTO_INCREMENT PRIMARY KEY,
            image_url VARCHAR(500) NOT NULL,
            alt_text VARCHAR(255) DEFAULT 'Barangay project image',
            display_order INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_order (display_order)
        )";
        $conn->query($createTable);
    }
    
    $stmt = $conn->prepare('INSERT INTO gallery_images (image_url, alt_text, display_order) VALUES (?, ?, ?)');
    
    if (!$stmt) {
        // Delete uploaded file if database insert fails
        @unlink($filePath);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
        $conn->close();
        exit;
    }
    
    $stmt->bind_param('ssi', $relativePath, $altText, $displayOrder);
    
    if ($stmt->execute()) {
        $imageId = $conn->insert_id;
        $stmt->close();
        
        // Get the created image
        $getStmt = $conn->prepare('SELECT id, image_url, alt_text, display_order FROM gallery_images WHERE id = ?');
        $getStmt->bind_param('i', $imageId);
        $getStmt->execute();
        $result = $getStmt->get_result();
        $newImage = $result->fetch_assoc();
        $getStmt->close();
        $conn->close();
        
        echo json_encode(['success' => true, 'image' => $newImage]);
    } else {
        // Delete uploaded file if database insert fails
        @unlink($filePath);
        http_response_code(500);
        $errorMsg = $stmt->error ? $stmt->error : 'Failed to add image.';
        echo json_encode(['success' => false, 'message' => $errorMsg]);
        $stmt->close();
        $conn->close();
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
    if (isset($conn)) {
        $conn->close();
    }
}
