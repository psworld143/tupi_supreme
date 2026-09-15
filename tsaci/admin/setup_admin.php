<?php
/**
 * Admin User Setup Script
 * Run this script to create or reset the admin user
 * Access: http://localhost/tupi_supreme/tsaci/admin/setup_admin.php
 */

require_once 'config.php';

// Security: Only allow this in development or with a secret key
$secret_key = $_GET['key'] ?? '';
$allowed_key = 'setup2024'; // Change this or remove after setup

if ($secret_key !== $allowed_key && !isset($_GET['force'])) {
    die('Access denied. Use: setup_admin.php?key=setup2024');
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username'] ?? 'admin');
    $email = sanitizeInput($_POST['email'] ?? 'admin@tsaci.com');
    $password = $_POST['password'] ?? 'admin123';
    $full_name = sanitizeInput($_POST['full_name'] ?? 'Administrator');
    
    if (empty($username) || empty($password)) {
        $error = 'Username and password are required.';
    } else {
        $db = getDB();
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        // Check if user exists
        $stmt = $db->prepare("SELECT id FROM admin_users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Update existing user
            $stmt = $db->prepare("UPDATE admin_users SET email = ?, password_hash = ?, full_name = ?, role = 'super_admin', is_active = 1 WHERE username = ?");
            $stmt->bind_param("ssss", $email, $password_hash, $full_name, $username);
            if ($stmt->execute()) {
                $message = "Admin user '{$username}' password has been reset successfully!";
            } else {
                $error = "Error updating user: " . $stmt->error;
            }
        } else {
            // Create new user
            $stmt = $db->prepare("INSERT INTO admin_users (username, email, password_hash, full_name, role, is_active) VALUES (?, ?, ?, ?, 'super_admin', 1)");
            $stmt->bind_param("ssss", $username, $email, $password_hash, $full_name);
            if ($stmt->execute()) {
                $message = "Admin user '{$username}' created successfully!";
            } else {
                $error = "Error creating user: " . $stmt->error;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin User Setup - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', ui-sans-serif, system-ui, sans-serif;
            background: #60796e;
        }
        .btn-primary {
            background-color: #3d7a66;
        }
        .btn-primary:hover {
            background-color: #2f6351;
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8 bg-white p-10 rounded-3xl shadow-2xl">
        <div>
            <div class="flex justify-center">
                <i class="fas fa-user-cog text-6xl" style="color: #3d7a66;"></i>
            </div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                Admin User Setup
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                Create or reset admin user account
            </p>
        </div>
        
        <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline"><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($message): ?>
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline"><?php echo htmlspecialchars($message); ?></span>
                <div class="mt-4">
                    <a href="login.php" class="text-green-800 underline font-semibold">Go to Login Page →</a>
                </div>
            </div>
        <?php endif; ?>
        
        <form class="mt-8 space-y-6" method="POST" action="">
            <div class="space-y-4">
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-2">Username</label>
                    <input id="username" name="username" type="text" required 
                           value="<?php echo htmlspecialchars($_POST['username'] ?? 'admin'); ?>"
                           class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm">
                </div>
                
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                    <input id="email" name="email" type="email" required 
                           value="<?php echo htmlspecialchars($_POST['email'] ?? 'admin@tsaci.com'); ?>"
                           class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm">
                </div>
                
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                    <input id="password" name="password" type="password" required 
                           value="<?php echo htmlspecialchars($_POST['password'] ?? 'admin123'); ?>"
                           class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm">
                </div>
                
                <div>
                    <label for="full_name" class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
                    <input id="full_name" name="full_name" type="text" 
                           value="<?php echo htmlspecialchars($_POST['full_name'] ?? 'Administrator'); ?>"
                           class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm">
                </div>
            </div>

            <div>
                <button type="submit" 
                        class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-md text-white btn-primary focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition duration-300 shadow-lg">
                    <i class="fas fa-save mr-2"></i>
                    Create/Reset Admin User
                </button>
            </div>
            
            <div class="text-center text-sm text-gray-600">
                <p class="text-xs text-red-600 mt-2">⚠️ Delete this file after setup for security!</p>
            </div>
        </form>
    </div>
</body>
</html>

