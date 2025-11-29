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
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON input.']);
        exit;
    }
    
    if (!isset($input['id']) || !is_numeric($input['id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Image ID is required.']);
        exit;
    }
    
    $imageId = intval($input['id']);
    
    // First, get the image path before deleting
    $getStmt = $conn->prepare('SELECT image_url FROM gallery_images WHERE id = ?');
    $getStmt->bind_param('i', $imageId);
    $getStmt->execute();
    $result = $getStmt->get_result();
    $image = $result->fetch_assoc();
    $getStmt->close();
    
    if (!$image) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Image not found.']);
        $conn->close();
        exit;
    }
    
    $stmt = $conn->prepare('DELETE FROM gallery_images WHERE id = ?');
    
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
        $conn->close();
        exit;
    }
    
    $stmt->bind_param('i', $imageId);
    
    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        
        // Delete the physical file
        $filePath = __DIR__ . '/../' . $image['image_url'];
        if (file_exists($filePath) && is_file($filePath)) {
            @unlink($filePath);
        }
        
        echo json_encode(['success' => true, 'message' => 'Image deleted successfully.']);
    } else {
        http_response_code(500);
        $errorMsg = $stmt->error ? $stmt->error : 'Failed to delete image.';
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

