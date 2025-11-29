<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

$conversationId = isset($_GET['conversation_id']) ? trim($_GET['conversation_id']) : null;
$senderType = isset($_GET['sender_type']) ? $_GET['sender_type'] : null;

// If admin, verify session
if ($senderType === 'admin' || isset($_GET['admin'])) {
    if (!isset($_SESSION['admin_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized. Please log in.']);
        exit;
    }
}

if ($conversationId) {
    // Get messages for a specific conversation
    $stmt = $conn->prepare('SELECT id, conversation_id, sender_type, message, created_at FROM chat_messages WHERE conversation_id = ? ORDER BY created_at ASC');
    $stmt->bind_param('s', $conversationId);
    $stmt->execute();
    $result = $stmt->get_result();
    $messages = [];
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }
    $stmt->close();
    $conn->close();

    echo json_encode(['success' => true, 'messages' => $messages]);
} else {
    // Get all unique conversations (for admin)
    if (!isset($_SESSION['admin_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
        exit;
    }

    $stmt = $conn->prepare('SELECT conversation_id, MAX(created_at) as last_message, COUNT(*) as message_count FROM chat_messages GROUP BY conversation_id ORDER BY last_message DESC');
    $stmt->execute();
    $result = $stmt->get_result();
    $conversations = [];
    while ($row = $result->fetch_assoc()) {
        $conversations[] = $row;
    }
    $stmt->close();
    $conn->close();

    echo json_encode(['success' => true, 'conversations' => $conversations]);
}
?>
