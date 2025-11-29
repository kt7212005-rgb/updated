<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['conversation_id'], $input['message'], $input['sender_type'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
    exit;
}

$conversationId = trim($input['conversation_id']);
$message = trim($input['message']);
$senderType = $input['sender_type'];

if (empty($conversationId) || empty($message)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Conversation ID and message are required.']);
    exit;
}

if (!in_array($senderType, ['user', 'admin'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid sender type.']);
    exit;
}

// If admin, verify session
if ($senderType === 'admin') {
    if (!isset($_SESSION['admin_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized. Please log in.']);
        exit;
    }
}

$stmt = $conn->prepare('INSERT INTO chat_messages (conversation_id, sender_type, message) VALUES (?, ?, ?)');
$stmt->bind_param('sss', $conversationId, $senderType, $message);

if ($stmt->execute()) {
    $messageId = $conn->insert_id;
    $stmt->close();

    // Get the created message
    $getStmt = $conn->prepare('SELECT id, conversation_id, sender_type, message, created_at FROM chat_messages WHERE id = ?');
    $getStmt->bind_param('i', $messageId);
    $getStmt->execute();
    $result = $getStmt->get_result();
    $newMessage = $result->fetch_assoc();
    $getStmt->close();
    $conn->close();

    echo json_encode(['success' => true, 'message' => $newMessage]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to save message.']);
}

$conn->close();
?>
