<?php
require_once 'config.php';
requireLogin();

$user = getCurrentUser();
$db = getDB();

// Get statistics
$stats = [];

// Total pages content
$result = $db->query("SELECT COUNT(*) as count FROM page_content WHERE is_active = 1");
$stats['pages'] = $result->fetch_assoc()['count'];

// Total products
$result = $db->query("SELECT COUNT(*) as count FROM products WHERE is_active = 1");
$stats['products'] = $result->fetch_assoc()['count'];

// Total services
$result = $db->query("SELECT COUNT(*) as count FROM services WHERE is_active = 1");
$stats['services'] = $result->fetch_assoc()['count'];

// Total case studies
$result = $db->query("SELECT COUNT(*) as count FROM case_studies WHERE is_active = 1");
$stats['case_studies'] = $result->fetch_assoc()['count'];

// Total gallery images
$result = $db->query("SELECT COUNT(*) as count FROM gallery_images WHERE is_active = 1");
$stats['gallery'] = $result->fetch_assoc()['count'];

// Total resources
$result = $db->query("SELECT COUNT(*) as count FROM resources WHERE is_active = 1");
$stats['resources'] = $result->fetch_assoc()['count'];

// Total certifications
$result = $db->query("SELECT COUNT(*) as count FROM certifications WHERE is_active = 1");
$stats['certifications'] = $result->fetch_assoc()['count'];

// Unread messages
$result = $db->query("SELECT COUNT(*) as count FROM contact_messages WHERE is_read = 0 AND is_archived = 0");
$stats['messages'] = $result->fetch_assoc()['count'];

// Recent activity
$result = $db->query("SELECT al.*, au.username, au.full_name FROM activity_logs al LEFT JOIN admin_users au ON al.user_id = au.id ORDER BY al.created_at DESC LIMIT 10");
$recent_activity = [];
while ($row = $result->fetch_assoc()) {
    $recent_activity[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#2c5530',
                        secondary: '#4a7c59',
                        accent: '#8bc34a',
                        dark: '#1a1a1a',
                        light: '#f8f9fa'
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-100">
    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="lg:ml-64 p-4 lg:p-8">
        <!-- Welcome Section -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Welcome back, <?php echo htmlspecialchars($user['full_name'] ?: $user['username']); ?>!</h1>
            <p class="text-gray-600">Manage your website content from this admin console.</p>
        </div>
        
        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm">Page Content</p>
                        <p class="text-3xl font-bold text-primary"><?php echo $stats['pages']; ?></p>
                    </div>
                    <div class="bg-primary bg-opacity-10 p-3 rounded-full">
                        <i class="fas fa-file-alt text-primary text-2xl"></i>
                    </div>
                </div>
                <a href="pages.php" class="text-sm text-primary hover:underline mt-2 block">Manage Pages →</a>
            </div>
            
            <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm">Products</p>
                        <p class="text-3xl font-bold text-secondary"><?php echo $stats['products']; ?></p>
                    </div>
                    <div class="bg-secondary bg-opacity-10 p-3 rounded-full">
                        <i class="fas fa-cube text-secondary text-2xl"></i>
                    </div>
                </div>
                <a href="products.php" class="text-sm text-secondary hover:underline mt-2 block">Manage Products →</a>
            </div>
            
            <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm">Services</p>
                        <p class="text-3xl font-bold text-accent"><?php echo $stats['services']; ?></p>
                    </div>
                    <div class="bg-accent bg-opacity-10 p-3 rounded-full">
                        <i class="fas fa-concierge-bell text-accent text-2xl"></i>
                    </div>
                </div>
                <a href="services.php" class="text-sm text-accent hover:underline mt-2 block">Manage Services →</a>
            </div>
            
            <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm">Messages</p>
                        <p class="text-3xl font-bold <?php echo $stats['messages'] > 0 ? 'text-red-600' : 'text-gray-600'; ?>"><?php echo $stats['messages']; ?></p>
                    </div>
                    <div class="bg-red-100 p-3 rounded-full">
                        <i class="fas fa-envelope text-red-600 text-2xl"></i>
                    </div>
                </div>
                <a href="messages.php" class="text-sm text-primary hover:underline mt-2 block">View Messages →</a>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">Quick Actions</h2>
                <div class="grid grid-cols-2 gap-4">
                    <a href="pages.php?action=add" class="bg-primary text-white p-4 rounded-lg hover:bg-secondary transition-colors text-center">
                        <i class="fas fa-plus-circle text-2xl mb-2"></i>
                        <p class="font-semibold">Add Page Content</p>
                    </a>
                    <a href="products.php?action=add" class="bg-secondary text-white p-4 rounded-lg hover:bg-primary transition-colors text-center">
                        <i class="fas fa-cube text-2xl mb-2"></i>
                        <p class="font-semibold">Add Product</p>
                    </a>
                    <a href="case-studies.php?action=add" class="bg-accent text-white p-4 rounded-lg hover:bg-secondary transition-colors text-center">
                        <i class="fas fa-book text-2xl mb-2"></i>
                        <p class="font-semibold">Add Case Study</p>
                    </a>
                    <a href="gallery.php?action=add" class="bg-gray-700 text-white p-4 rounded-lg hover:bg-gray-800 transition-colors text-center">
                        <i class="fas fa-images text-2xl mb-2"></i>
                        <p class="font-semibold">Add Gallery Image</p>
                    </a>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">Recent Activity</h2>
                <div class="space-y-3 max-h-96 overflow-y-auto">
                    <?php if (empty($recent_activity)): ?>
                        <p class="text-gray-500 text-center py-4">No recent activity</p>
                    <?php else: ?>
                        <?php foreach ($recent_activity as $activity): ?>
                            <div class="border-l-4 border-primary pl-4 py-2">
                                <p class="text-sm text-gray-800">
                                    <span class="font-semibold"><?php echo htmlspecialchars($activity['username']); ?></span>
                                    <?php echo htmlspecialchars($activity['action']); ?>
                                    <?php if ($activity['table_name']): ?>
                                        <span class="text-gray-600">in <?php echo htmlspecialchars($activity['table_name']); ?></span>
                                    <?php endif; ?>
                                </p>
                                <p class="text-xs text-gray-500"><?php echo formatDate($activity['created_at'], 'M d, Y H:i'); ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Content Management Links -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Content Management</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <a href="pages.php" class="p-4 border-2 border-gray-200 rounded-lg hover:border-primary hover:bg-primary hover:bg-opacity-5 transition-all">
                    <i class="fas fa-file-alt text-primary text-2xl mb-2"></i>
                    <p class="font-semibold">Page Content</p>
                    <p class="text-sm text-gray-600">Manage page sections</p>
                </a>
                <a href="products.php" class="p-4 border-2 border-gray-200 rounded-lg hover:border-secondary hover:bg-secondary hover:bg-opacity-5 transition-all">
                    <i class="fas fa-cube text-secondary text-2xl mb-2"></i>
                    <p class="font-semibold">Products</p>
                    <p class="text-sm text-gray-600">Manage products</p>
                </a>
                <a href="services.php" class="p-4 border-2 border-gray-200 rounded-lg hover:border-accent hover:bg-accent hover:bg-opacity-5 transition-all">
                    <i class="fas fa-concierge-bell text-accent text-2xl mb-2"></i>
                    <p class="font-semibold">Services</p>
                    <p class="text-sm text-gray-600">Manage services</p>
                </a>
                <a href="case-studies.php" class="p-4 border-2 border-gray-200 rounded-lg hover:border-primary hover:bg-primary hover:bg-opacity-5 transition-all">
                    <i class="fas fa-book text-primary text-2xl mb-2"></i>
                    <p class="font-semibold">Case Studies</p>
                    <p class="text-sm text-gray-600">Manage case studies</p>
                </a>
                <a href="gallery.php" class="p-4 border-2 border-gray-200 rounded-lg hover:border-gray-700 hover:bg-gray-700 hover:bg-opacity-5 transition-all">
                    <i class="fas fa-images text-gray-700 text-2xl mb-2"></i>
                    <p class="font-semibold">Gallery</p>
                    <p class="text-sm text-gray-600">Manage images</p>
                </a>
                <a href="resources.php" class="p-4 border-2 border-gray-200 rounded-lg hover:border-secondary hover:bg-secondary hover:bg-opacity-5 transition-all">
                    <i class="fas fa-file-download text-secondary text-2xl mb-2"></i>
                    <p class="font-semibold">Resources</p>
                    <p class="text-sm text-gray-600">Manage documents</p>
                </a>
                <a href="certifications.php" class="p-4 border-2 border-gray-200 rounded-lg hover:border-accent hover:bg-accent hover:bg-opacity-5 transition-all">
                    <i class="fas fa-certificate text-accent text-2xl mb-2"></i>
                    <p class="font-semibold">Certifications</p>
                    <p class="text-sm text-gray-600">Manage certifications</p>
                </a>
                <a href="messages.php" class="p-4 border-2 border-gray-200 rounded-lg hover:border-red-500 hover:bg-red-500 hover:bg-opacity-5 transition-all">
                    <i class="fas fa-envelope text-red-500 text-2xl mb-2"></i>
                    <p class="font-semibold">Messages</p>
                    <p class="text-sm text-gray-600">View contact messages</p>
                </a>
            </div>
        </div>
    </div>
    
    <script>
        // Auto-refresh messages count every 30 seconds
        setInterval(function() {
            fetch('api/get_stats.php')
                .then(response => response.json())
                .then(data => {
                    if (data.messages !== undefined) {
                        const messagesEl = document.querySelector('[href="messages.php"]').previousElementSibling.querySelector('p.text-3xl');
                        if (messagesEl) {
                            messagesEl.textContent = data.messages;
                            messagesEl.className = data.messages > 0 ? 'text-3xl font-bold text-red-600' : 'text-3xl font-bold text-gray-600';
                        }
                    }
                })
                .catch(err => console.error('Error fetching stats:', err));
        }, 30000);
    </script>
</body>
</html>

