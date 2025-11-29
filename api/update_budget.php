<?php
session_start();
header('Content-Type: application/json');

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please log in.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['id'], $input['allocated'], $input['spent'], $input['status'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
    exit;
}

$id = (int) $input['id'];
$allocated = (float) $input['allocated'];
$spent = (float) $input['spent'];
$status = trim($input['status']);
$projectProgress = isset($input['project_progress']) ? trim($input['project_progress']) : null;

if ($allocated < $spent) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Allocated must be greater than spent.']);
    exit;
}

require_once __DIR__ . '/db.php';

// Check if project_progress column exists, if not add it
$columnCheck = $conn->query("SHOW COLUMNS FROM budget_allocations LIKE 'project_progress'");
if ($columnCheck->num_rows == 0) {
    $conn->query("ALTER TABLE budget_allocations ADD COLUMN project_progress TEXT DEFAULT NULL AFTER status");
}

$stmt = $conn->prepare('UPDATE budget_allocations SET allocated = ?, spent = ?, status = ?, project_progress = ? WHERE id = ?');
$stmt->bind_param('ddssi', $allocated, $spent, $status, $projectProgress, $id);

if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Update failed: ' . $stmt->error]);
    $stmt->close();
    $conn->close();
    exit;
}

$stmt->close();

$selectStmt = $conn->prepare('SELECT id, category, allocated, spent, status, project_progress FROM budget_allocations WHERE id = ? LIMIT 1');
$selectStmt->bind_param('i', $id);
$selectStmt->execute();
$result = $selectStmt->get_result();
$updatedItem = $result->fetch_assoc();
$selectStmt->close();
$conn->close();

echo json_encode([
    'success' => true,
    'updatedItem' => $updatedItem,
]);

