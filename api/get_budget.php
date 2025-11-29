<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

// Check if project_progress column exists
$columnCheck = $conn->query("SHOW COLUMNS FROM budget_allocations LIKE 'project_progress'");
$hasProgressColumn = $columnCheck->num_rows > 0;

$sql = $hasProgressColumn 
    ? 'SELECT id, category, allocated, spent, status, project_progress FROM budget_allocations ORDER BY id ASC'
    : 'SELECT id, category, allocated, spent, status FROM budget_allocations ORDER BY id ASC';
$result = $conn->query($sql);

if (!$result) {
    http_response_code(500);
    echo json_encode(['error' => 'Query failed: ' . $conn->error]);
    $conn->close();
    exit;
}

$data = [];
while ($row = $result->fetch_assoc()) {
    $item = [
        'id' => (int) $row['id'],
        'category' => $row['category'],
        'allocated' => (float) $row['allocated'],
        'spent' => (float) $row['spent'],
        'status' => $row['status'],
    ];
    if ($hasProgressColumn && isset($row['project_progress'])) {
        $item['project_progress'] = $row['project_progress'];
    }
    $data[] = $item;
}

echo json_encode($data);
$conn->close();

