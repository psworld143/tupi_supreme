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

    <!-- Page-scoped alignment: keep the main content card the exact same
         height as the fixed sidebar so both panels align top/bottom. -->
    <style>
        @media (min-width: 1024px) {
            /* Thin, themed scrollbar for the in-card scroll area */
            .lg\:ml-64 {
                scrollbar-width: thin;
                scrollbar-color: #d2dcd5 transparent;
            }
            .lg\:ml-64::-webkit-scrollbar {
                width: 8px;
            }
            .lg\:ml-64::-webkit-scrollbar-track {
                background: transparent;
            }
            .lg\:ml-64::-webkit-scrollbar-thumb {
                background-color: #d2dcd5;
                border-radius: 4px;
                border: 2px solid transparent;
                background-clip: padding-box;
            }
            .lg\:ml-64::-webkit-scrollbar-thumb:hover {
                background-color: #c0ccc5;
            }
        }

        /* Fade-in animation for dashboard content */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(12px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in {
            opacity: 0;
            animation: fadeInUp 0.5s ease-out forwards;
        }

        /* Staggered delays for a cascading reveal */
        .fade-in-delay-1 { animation-delay: 0.05s; }
        .fade-in-delay-2 { animation-delay: 0.15s; }
        .fade-in-delay-3 { animation-delay: 0.25s; }
        .fade-in-delay-4 { animation-delay: 0.35s; }
        .fade-in-delay-5 { animation-delay: 0.45s; }

        /* Respect reduced-motion preference */
        @media (prefers-reduced-motion: reduce) {
            .fade-in {
                opacity: 1;
                animation: none;
            }
        }
    </style>

    <!-- Main Content -->
    <div class="lg:ml-64 p-6 lg:p-12">
        <!-- Hero / Welcome -->
        <div class="mb-12 fade-in fade-in-delay-1">
            <h1 class="text-3xl lg:text-4xl font-bold text-[#23332c]">Hello! I'm <?php echo htmlspecialchars($user['full_name'] ?: $user['username']); ?></h1>
            <div class="flex flex-wrap items-center gap-x-5 gap-y-2 mt-2">
                <span class="text-[#3d7a66] font-medium text-lg"><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $user['role'] ?? 'Administrator'))); ?></span>
                <span class="inline-flex items-center gap-1.5 text-sm text-[#66746c]">
                    <i class="fas fa-globe"></i> <?php echo SITE_NAME; ?>
                </span>
            </div>
            <p class="text-[#7d8b84] mt-5 max-w-xl text-sm leading-relaxed">
                Manage your website content, products, services and messages from this admin console.
            </p>
            <div class="flex flex-wrap gap-3 mt-6">
                <a href="../index.php" target="_blank" class="px-5 py-2.5 rounded-full bg-[#3d7a66] text-white text-sm font-medium hover:bg-[#2f6351] transition-colors">View Website</a>
                <a href="messages.php" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full border border-[#d6ded9] text-sm font-medium text-[#23332c] hover:bg-[#eff4f1] transition-colors">
                    <i class="far fa-envelope"></i> Messages
                </a>
            </div>
        </div>

        <!-- Overview -->
        <div class="flex items-center justify-between mb-5 fade-in fade-in-delay-2">
            <h2 class="text-xl font-bold text-[#23332c]">Overview</h2>
            <a href="statistics.php" class="text-sm text-[#7d8b84] hover:text-[#23332c] transition-colors">View All <i class="fas fa-arrow-right text-xs"></i></a>
        </div>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-12 fade-in fade-in-delay-2">
            <a href="pages.php" class="border border-[#e6ece8] rounded-2xl p-5 hover:bg-[#f7faf8] transition-colors">
                <div class="w-10 h-10 rounded-full bg-[#eef3f0] flex items-center justify-center mb-4">
                    <i class="fas fa-file-alt text-[#60796e]"></i>
                </div>
                <p class="text-3xl font-semibold text-[#23332c]"><?php echo $stats['pages']; ?></p>
                <p class="text-xs text-[#8a978f] mt-1">Page Content</p>
            </a>

            <a href="products.php" class="border border-[#e6ece8] rounded-2xl p-5 hover:bg-[#f7faf8] transition-colors">
                <div class="w-10 h-10 rounded-full bg-[#eef3f0] flex items-center justify-center mb-4">
                    <i class="fas fa-cube text-[#60796e]"></i>
                </div>
                <p class="text-3xl font-semibold text-[#23332c]"><?php echo $stats['products']; ?></p>
                <p class="text-xs text-[#8a978f] mt-1">Products</p>
            </a>

            <a href="services.php" class="border border-[#e6ece8] rounded-2xl p-5 hover:bg-[#f7faf8] transition-colors">
                <div class="w-10 h-10 rounded-full bg-[#eef3f0] flex items-center justify-center mb-4">
                    <i class="fas fa-concierge-bell text-[#60796e]"></i>
                </div>
                <p class="text-3xl font-semibold text-[#23332c]"><?php echo $stats['services']; ?></p>
                <p class="text-xs text-[#8a978f] mt-1">Services</p>
            </a>

            <a href="messages.php" class="border border-[#e6ece8] rounded-2xl p-5 hover:bg-[#f7faf8] transition-colors">
                <div class="w-10 h-10 rounded-full bg-[#eef3f0] flex items-center justify-center mb-4">
                    <i class="fas fa-envelope text-[#60796e]"></i>
                </div>
                <p id="stat-messages-count" class="text-3xl font-semibold <?php echo $stats['messages'] > 0 ? 'text-red-500' : 'text-[#23332c]'; ?>"><?php echo $stats['messages']; ?></p>
                <p class="text-xs text-[#8a978f] mt-1">Unread Messages</p>
            </a>
        </div>

        <!-- Quick Actions -->
        <h2 class="text-xl font-bold text-[#23332c] mb-5 fade-in fade-in-delay-3">Quick Actions</h2>
        <div class="flex flex-wrap gap-3 mb-12 fade-in fade-in-delay-3">
            <a href="pages.php?action=add" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full bg-[#23332c] text-white text-sm font-medium hover:bg-[#3a4a41] transition-colors">
                <i class="fas fa-plus text-xs"></i> Page Content
            </a>
            <a href="products.php?action=add" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full border border-[#d6ded9] text-sm font-medium text-[#23332c] hover:bg-[#eff4f1] transition-colors">
                <i class="fas fa-plus text-xs"></i> Product
            </a>
            <a href="case-studies.php?action=add" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full border border-[#d6ded9] text-sm font-medium text-[#23332c] hover:bg-[#eff4f1] transition-colors">
                <i class="fas fa-plus text-xs"></i> Case Study
            </a>
            <a href="gallery.php?action=add" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full border border-[#d6ded9] text-sm font-medium text-[#23332c] hover:bg-[#eff4f1] transition-colors">
                <i class="fas fa-plus text-xs"></i> Gallery Image
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 fade-in fade-in-delay-4">
            <!-- Content Management -->
            <div>
                <h2 class="text-xl font-bold text-[#23332c] mb-5">Content Management</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <?php
                    $content_links = [
                        ['pages.php',          'fa-file-alt',      'Page Content',   'Manage page sections'],
                        ['products.php',       'fa-cube',          'Products',       'Manage products'],
                        ['services.php',       'fa-concierge-bell','Services',       'Manage services'],
                        ['case-studies.php',   'fa-book',          'Case Studies',   'Manage case studies'],
                        ['gallery.php',        'fa-photo-video',   'Gallery',        'Manage images'],
                        ['resources.php',      'fa-file-download', 'Resources',      'Manage documents'],
                        ['certifications.php', 'fa-certificate',   'Certifications', 'Manage certifications'],
                        ['messages.php',       'fa-envelope',      'Messages',       'View contact messages'],
                    ];
                    foreach ($content_links as $link):
                    ?>
                        <a href="<?php echo $link[0]; ?>" class="flex items-center gap-3 p-4 border border-[#e6ece8] rounded-2xl hover:bg-[#f7faf8] transition-colors">
                            <div class="w-9 h-9 rounded-full bg-[#eef3f0] flex items-center justify-center flex-shrink-0">
                                <i class="fas <?php echo $link[1]; ?> text-[#60796e] text-sm"></i>
                            </div>
                            <div class="min-w-0">
                                <p class="font-medium text-sm text-[#23332c]"><?php echo $link[2]; ?></p>
                                <p class="text-xs text-[#8a978f] truncate"><?php echo $link[3]; ?></p>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Recent Activity -->
            <div>
                <h2 class="text-xl font-bold text-[#23332c] mb-5">Recent Activity</h2>
                <div class="space-y-3 max-h-96 overflow-y-auto pr-2">
                    <?php if (empty($recent_activity)): ?>
                        <p class="text-[#8a978f] text-sm py-4">No recent activity</p>
                    <?php else: ?>
                        <?php foreach ($recent_activity as $activity): ?>
                            <div class="border-l-2 border-[#3d7a66] pl-4 py-1.5">
                                <p class="text-sm text-[#23332c]">
                                    <span class="font-semibold"><?php echo htmlspecialchars($activity['username']); ?></span>
                                    <?php echo htmlspecialchars($activity['action']); ?>
                                    <?php if ($activity['table_name']): ?>
                                        <span class="text-[#8a978f]">in <?php echo htmlspecialchars($activity['table_name']); ?></span>
                                    <?php endif; ?>
                                </p>
                                <p class="text-xs text-[#8a978f]"><?php echo formatDate($activity['created_at'], 'M d, Y H:i'); ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
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
                        const messagesEl = document.getElementById('stat-messages-count');
                        if (messagesEl) {
                            messagesEl.textContent = data.messages;
                            messagesEl.className = data.messages > 0 ? 'text-3xl font-semibold text-red-500' : 'text-3xl font-semibold text-[#23332c]';
                        }
                    }
                })
                .catch(err => console.error('Error fetching stats:', err));
        }, 30000);
    </script>
</body>
</html>

