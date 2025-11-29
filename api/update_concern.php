<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

// Verify admin session
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please log in.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['concern_id'], $input['status'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
    exit;
}

$concernId = (int)$input['concern_id'];
$status = $input['status'];
$adminResponse = isset($input['admin_response']) ? trim($input['admin_response']) : null;

$validStatuses = ['Pending', 'In Progress', 'Resolved'];
if (!in_array($status, $validStatuses)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid status.']);
    exit;
}

if ($concernId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid concern ID.']);
    exit;
}

$stmt = $conn->prepare('UPDATE concerns SET status = ?, admin_response = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
$stmt->bind_param('ssi', $status, $adminResponse, $concernId);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        $stmt->close();

        // Get the updated concern
        $getStmt = $conn->prepare('SELECT id, name, email, concern_type, message, status, admin_response, created_at, updated_at FROM concerns WHERE id = ?');
        $getStmt->bind_param('i', $concernId);
        $getStmt->execute();
        $result = $getStmt->get_result();
        $updatedConcern = $result->fetch_assoc();
        $getStmt->close();
        $conn->close();

        echo json_encode(['success' => true, 'concern' => $updatedConcern]);
    } else {
        $stmt->close();
        $conn->close();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Concern not found.']);
    }
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to update concern.']);
}

$conn->close();
?>
