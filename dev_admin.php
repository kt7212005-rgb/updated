<?php
/**
 * Development Admin Access File
 * SECURITY: This file provides development/admin utilities
 * IMPORTANT: This file should be protected or removed in production
 * 
 * Access Control: Add IP whitelist or password protection
 */

// SECURITY: Uncomment and configure one of these protection methods:

// Option 1: IP Whitelist (Recommended for development)
/*
$allowedIPs = ['127.0.0.1', '::1', 'YOUR_IP_ADDRESS'];
if (!in_array($_SERVER['REMOTE_ADDR'], $allowedIPs)) {
    http_response_code(403);
    die('Access Denied: This file is restricted to authorized IP addresses only.');
}
*/

// Option 2: Password Protection
/*
session_start();
$devPassword = 'your_dev_password_here'; // CHANGE THIS!
if (!isset($_SESSION['dev_authenticated'])) {
    if (isset($_POST['dev_password']) && $_POST['dev_password'] === $devPassword) {
        $_SESSION['dev_authenticated'] = true;
    } else {
        ?>
        <!DOCTYPE html>
        <html>
        <head><title>Dev Admin Access</title></head>
        <body style="font-family: Arial; padding: 50px; text-align: center;">
            <h2>Development Admin Access</h2>
            <form method="POST">
                <input type="password" name="dev_password" placeholder="Enter dev password" required>
                <button type="submit">Access</button>
            </form>
        </body>
        </html>
        <?php
        exit;
    }
}
*/

require_once __DIR__ . '/api/db.php';

$action = $_GET['action'] ?? 'dashboard';
$message = '';
$messageType = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? $action;
    
    if ($action === 'reset_admin') {
        $username = $_POST['username'] ?? 'admin';
        $newPassword = $_POST['new_password'] ?? 'admin';
        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("UPDATE admins SET password_hash = ? WHERE username = ?");
        $stmt->bind_param("ss", $passwordHash, $username);
        
        if ($stmt->execute()) {
            $message = "Password for '{$username}' has been reset successfully!";
            $messageType = 'success';
        } else {
            $message = "Error: " . $conn->error;
            $messageType = 'error';
        }
        $stmt->close();
    } elseif ($action === 'clear_cache') {
        // Clear any cached data (if you implement caching)
        $message = "Cache cleared successfully!";
        $messageType = 'success';
    } elseif ($action === 'test_db') {
        // Test database connection
        if ($conn->ping()) {
            $message = "Database connection is working!";
            $messageType = 'success';
        } else {
            $message = "Database connection failed!";
            $messageType = 'error';
        }
    }
}

// Get system info
$dbInfo = [];
$dbInfo['connected'] = $conn->ping();
$dbInfo['server_info'] = $conn->server_info;
$dbInfo['database'] = 'brgy_budget';

// Get table counts
$tables = ['budget_allocations', 'admins', 'posts', 'chat_messages', 'gallery_images', 'concerns'];
$tableCounts = [];
foreach ($tables as $table) {
    $result = $conn->query("SELECT COUNT(*) as cnt FROM $table");
    if ($result) {
        $tableCounts[$table] = $result->fetch_assoc()['cnt'];
    }
}

// Get admin list
$admins = [];
$result = $conn->query("SELECT id, username, created_at FROM admins ORDER BY created_at DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $admins[] = $row;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dev Admin - Barangay San Antonio 1</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .success { color: #10b981; }
        .error { color: #ef4444; }
        .warning { color: #f59e0b; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen py-8">
    <div class="max-w-6xl mx-auto px-4">
        <div class="bg-red-50 border-2 border-red-300 rounded-lg p-4 mb-6">
            <h2 class="text-red-800 font-bold text-lg mb-2">⚠️ DEVELOPMENT/ADMIN ACCESS ONLY</h2>
            <p class="text-red-700 text-sm">
                This file provides administrative and development utilities. 
                <strong>Remove or protect this file in production environments.</strong>
            </p>
        </div>
        
        <div class="bg-white rounded-xl shadow-lg p-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Development Admin Panel</h1>
            <p class="text-gray-600 mb-6">System utilities and maintenance tools</p>
            
            <?php if ($message): ?>
                <div class="mb-6 p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'; ?>">
                    <p class="<?php echo $messageType; ?> font-semibold"><?php echo htmlspecialchars($message); ?></p>
                </div>
            <?php endif; ?>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                <!-- System Info -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h3 class="font-bold text-blue-800 mb-2">System Information</h3>
                    <div class="text-sm space-y-1">
                        <p><strong>Database:</strong> <?php echo htmlspecialchars($dbInfo['database']); ?></p>
                        <p><strong>Status:</strong> 
                            <span class="<?php echo $dbInfo['connected'] ? 'text-green-600' : 'text-red-600'; ?>">
                                <?php echo $dbInfo['connected'] ? 'Connected' : 'Disconnected'; ?>
                            </span>
                        </p>
                        <p><strong>Server:</strong> <?php echo htmlspecialchars($dbInfo['server_info']); ?></p>
                    </div>
                </div>
                
                <!-- Table Counts -->
                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <h3 class="font-bold text-green-800 mb-2">Database Tables</h3>
                    <div class="text-sm space-y-1">
                        <?php foreach ($tableCounts as $table => $count): ?>
                            <p><strong><?php echo htmlspecialchars($table); ?>:</strong> <?php echo $count; ?> rows</p>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                    <h3 class="font-bold text-purple-800 mb-2">Quick Actions</h3>
                    <div class="space-y-2">
                        <form method="POST" class="inline">
                            <input type="hidden" name="action" value="test_db">
                            <button type="submit" class="text-sm bg-purple-600 text-white px-3 py-1 rounded hover:bg-purple-700">
                                Test Database
                            </button>
                        </form>
                        <form method="POST" class="inline">
                            <input type="hidden" name="action" value="clear_cache">
                            <button type="submit" class="text-sm bg-purple-600 text-white px-3 py-1 rounded hover:bg-purple-700">
                                Clear Cache
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Reset Admin Password -->
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 mb-6">
                <h2 class="text-xl font-bold text-yellow-800 mb-4">Reset Admin Password</h2>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="reset_admin">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                            <input type="text" name="username" value="admin" required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                            <input type="password" name="new_password" required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        </div>
                        <div class="flex items-end">
                            <button type="submit" 
                                    class="w-full bg-yellow-600 text-white py-2 px-4 rounded-lg font-semibold hover:bg-yellow-700">
                                Reset Password
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Admin Accounts -->
            <div class="mb-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">Admin Accounts</h2>
                <?php if (empty($admins)): ?>
                    <p class="text-gray-500">No admin accounts found.</p>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse border border-gray-300">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="border border-gray-300 px-4 py-2 text-left">ID</th>
                                    <th class="border border-gray-300 px-4 py-2 text-left">Username</th>
                                    <th class="border border-gray-300 px-4 py-2 text-left">Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($admins as $admin): ?>
                                    <tr>
                                        <td class="border border-gray-300 px-4 py-2"><?php echo $admin['id']; ?></td>
                                        <td class="border border-gray-300 px-4 py-2 font-semibold"><?php echo htmlspecialchars($admin['username']); ?></td>
                                        <td class="border border-gray-300 px-4 py-2"><?php echo date('Y-m-d H:i', strtotime($admin['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="pt-6 border-t border-gray-200">
                <a href="index.php" class="text-blue-600 hover:text-blue-700 font-semibold">← Back to Home</a>
                <span class="mx-2 text-gray-400">|</span>
                <a href="admin.php" class="text-blue-600 hover:text-blue-700 font-semibold">Admin Portal</a>
                <span class="mx-2 text-gray-400">|</span>
                <a href="create_admin.php" class="text-blue-600 hover:text-blue-700 font-semibold">Create Admin</a>
            </div>
        </div>
    </div>
</body>
</html>


