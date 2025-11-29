<?php
/**
 * Admin Account Creation Tool
 * This file allows authorized personnel to create new admin accounts
 * SECURITY: This file should be protected or removed after initial setup
 */

require_once __DIR__ . '/api/db.php';

$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($username) || empty($password)) {
        $message = 'Username and password are required.';
        $messageType = 'error';
    } elseif (strlen($username) < 3) {
        $message = 'Username must be at least 3 characters long.';
        $messageType = 'error';
    } elseif (strlen($password) < 6) {
        $message = 'Password must be at least 6 characters long.';
        $messageType = 'error';
    } elseif ($password !== $confirmPassword) {
        $message = 'Passwords do not match.';
        $messageType = 'error';
    } else {
        // Check if username already exists
        $checkStmt = $conn->prepare("SELECT id FROM admins WHERE username = ?");
        $checkStmt->bind_param("s", $username);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        
        if ($result->num_rows > 0) {
            $message = 'Username already exists. Please choose a different username.';
            $messageType = 'error';
        } else {
            // Create admin account
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $insertStmt = $conn->prepare("INSERT INTO admins (username, password_hash) VALUES (?, ?)");
            $insertStmt->bind_param("ss", $username, $passwordHash);
            
            if ($insertStmt->execute()) {
                $message = "Admin account '{$username}' created successfully!";
                $messageType = 'success';
                // Clear form
                $username = '';
            } else {
                $message = 'Error creating admin account: ' . $conn->error;
                $messageType = 'error';
            }
            $insertStmt->close();
        }
        $checkStmt->close();
    }
}

// Get list of existing admins
$adminsList = [];
$result = $conn->query("SELECT id, username, created_at FROM admins ORDER BY created_at DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $adminsList[] = $row;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Admin Account - Barangay San Antonio 1</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .success { color: #10b981; }
        .error { color: #ef4444; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen py-8">
    <div class="max-w-4xl mx-auto px-4">
        <div class="bg-white rounded-xl shadow-lg p-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Create Admin Account</h1>
            <p class="text-gray-600 mb-6">Create a new administrator account for the Budget Transparency Portal</p>
            
            <?php if ($message): ?>
                <div class="mb-6 p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'; ?>">
                    <p class="<?php echo $messageType; ?> font-semibold"><?php echo htmlspecialchars($message); ?></p>
                </div>
            <?php endif; ?>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Create Admin Form -->
                <div>
                    <h2 class="text-xl font-bold text-gray-800 mb-4">New Admin Account</h2>
                    <form method="POST" class="space-y-4">
                        <div>
                            <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                            <input type="text" id="username" name="username" required 
                                   value="<?php echo htmlspecialchars($username ?? ''); ?>"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   minlength="3" autocomplete="username">
                        </div>
                        
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                            <input type="password" id="password" name="password" required 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   minlength="6" autocomplete="new-password">
                            <p class="text-xs text-gray-500 mt-1">Minimum 6 characters</p>
                        </div>
                        
                        <div>
                            <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" required 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   minlength="6" autocomplete="new-password">
                        </div>
                        
                        <button type="submit" 
                                class="w-full bg-blue-600 text-white py-2 px-4 rounded-lg font-semibold hover:bg-blue-700 transition duration-200">
                            Create Admin Account
                        </button>
                    </form>
                </div>
                
                <!-- Existing Admins List -->
                <div>
                    <h2 class="text-xl font-bold text-gray-800 mb-4">Existing Admins</h2>
                    <?php if (empty($adminsList)): ?>
                        <p class="text-gray-500">No admin accounts found.</p>
                    <?php else: ?>
                        <div class="space-y-2">
                            <?php foreach ($adminsList as $admin): ?>
                                <div class="p-3 bg-gray-50 rounded-lg border border-gray-200">
                                    <div class="font-semibold text-gray-800"><?php echo htmlspecialchars($admin['username']); ?></div>
                                    <div class="text-xs text-gray-500 mt-1">
                                        Created: <?php echo date('M d, Y', strtotime($admin['created_at'])); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="mt-8 pt-6 border-t border-gray-200">
                <a href="index.php" class="text-blue-600 hover:text-blue-700 font-semibold">← Back to Home</a>
                <span class="mx-2 text-gray-400">|</span>
                <a href="admin.php" class="text-blue-600 hover:text-blue-700 font-semibold">Go to Admin Portal</a>
            </div>
        </div>
        
        <div class="mt-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
            <p class="text-sm text-yellow-800">
                <strong>Security Notice:</strong> This file should be protected or removed after initial setup. 
                Consider restricting access to this file via .htaccess or moving it to a secure location.
            </p>
        </div>
    </div>
</body>
</html>


