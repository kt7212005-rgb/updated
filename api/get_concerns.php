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

$status = isset($_GET['status']) ? $_GET['status'] : null;

$query = 'SELECT id, name, email, concern_type, message, status, admin_response, created_at, updated_at FROM concerns';
$params = [];
$types = '';

if ($status) {
    $validStatuses = ['Pending', 'In Progress', 'Resolved'];
    if (!in_array($status, $validStatuses)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid status.']);
        exit;
    }
    $query .= ' WHERE status = ?';
    $params[] = $status;
    $types .= 's';
}

$query .= ' ORDER BY created_at DESC';

$stmt = $conn->prepare($query);
if ($params) {
    $stmt->bind_param($types, ...$params);
}

if ($stmt->execute()) {
    $result = $stmt->get_result();
    $concerns = [];
    while ($row = $result->fetch_assoc()) {
        $concerns[] = $row;
    }
    $stmt->close();
    $conn->close();

    echo json_encode(['success' => true, 'concerns' => $concerns]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to fetch concerns.']);
}

$conn->close();
?>
