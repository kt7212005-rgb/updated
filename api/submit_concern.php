<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['name'], $input['concern_type'], $input['message'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
    exit;
}

$name = trim($input['name']);
$email = isset($input['email']) ? trim($input['email']) : null;
$concernType = $input['concern_type'];
$message = trim($input['message']);

if (empty($name) || empty($concernType) || empty($message)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Name, concern type, and message are required.']);
    exit;
}

$validTypes = ['Infrastructure', 'Health Services', 'Security', 'Environment', 'Social Services', 'Others'];
if (!in_array($concernType, $validTypes)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid concern type.']);
    exit;
}

if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid email format.']);
    exit;
}

$stmt = $conn->prepare('INSERT INTO concerns (name, email, concern_type, message) VALUES (?, ?, ?, ?)');
$stmt->bind_param('ssss', $name, $email, $concernType, $message);

if ($stmt->execute()) {
    $concernId = $conn->insert_id;
    $stmt->close();

    // Get the created concern
    $getStmt = $conn->prepare('SELECT id, name, email, concern_type, message, status, created_at FROM concerns WHERE id = ?');
    $getStmt->bind_param('i', $concernId);
    $getStmt->execute();
    $result = $getStmt->get_result();
    $newConcern = $result->fetch_assoc();
    $getStmt->close();

    echo json_encode(['success' => true, 'concern' => $newConcern]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to submit concern.']);
}

$conn->close();
?>
