<?php
// Test API endpoints
header('Content-Type: application/json');

// Test submit_concern.php
$ch = curl_init();
$data = [
    'name' => 'API Test User',
    'email' => 'api@test.com',
    'concern_type' => 'Health Services',
    'message' => 'Test concern via API'
];

curl_setopt($ch, CURLOPT_URL, 'http://localhost/ta/api/submit_concern.php');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Submit Concern API Test:\n";
echo "HTTP Status: $httpCode\n";
echo "Response: $response\n\n";

// Test get_concerns.php (this will fail without admin session, but let's see the error)
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/ta/api/get_concerns.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Get Concerns API Test (without session):\n";
echo "HTTP Status: $httpCode\n";
echo "Response: $response\n";
?>
