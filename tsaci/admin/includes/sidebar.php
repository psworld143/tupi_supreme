<?php
if (!defined('ADMIN_ACCESS')) {
    die('Direct access not allowed');
}
$current_user = getCurrentUser();
$current_page = basename($_SERVER['PHP_SELF']);

// Get unread messages count
$db = getDB();
$result = $db->query("SELECT COUNT(*) as count FROM contact_messages WHERE is_read = 0 AND is_archived = 0");
$unread_messages = $result->fetch_assoc()['count'];
?>
<!-- Sidebar -->
<aside id="sidebar" class="fixed top-0 left-0 z-40 w-64 h-screen transition-all duration-300 -translate-x-full lg:translate-x-0 bg-dark text-white flex flex-col">
    <div class="sidebar-scroll flex-1 overflow-y-auto px-3 py-4">
        <!-- Logo/Brand -->
        <div class="sidebar-header flex items-center justify-between mb-8 px-2">
            <a href="index.php" class="sidebar-brand flex items-center text-xl font-bold">
                <i class="fas fa-shield-alt mr-2"></i>
                <span class="sidebar-label hidden lg:inline"><?php echo SITE_NAME; ?></span>
            </a>
            <div class="flex items-center">
                <!-- Desktop collapse toggle -->
                <button id="sidebar-collapse" type="button" title="Collapse sidebar" class="hidden lg:inline-block text-gray-400 hover:text-white hover:bg-gray-700 p-1 rounded transition-colors">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <!-- Mobile close button -->
                <button id="sidebar-close" class="lg:hidden text-white hover:text-gray-300">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
        </div>
        
        <!-- Navigation Menu -->
        <nav class="space-y-2">
            <a href="index.php" title="Dashboard" class="flex items-center px-4 py-3 rounded-lg hover:bg-gray-700 transition-colors <?php echo $current_page == 'index.php' ? 'bg-primary' : ''; ?>">
                <i class="fas fa-home w-5 text-center"></i>
                <span class="sidebar-label ml-3">Dashboard</span>
            </a>
            
            <a href="pages.php" title="Pages" class="flex items-center px-4 py-3 rounded-lg hover:bg-gray-700 transition-colors <?php echo $current_page == 'pages.php' ? 'bg-primary' : ''; ?>">
                <i class="fas fa-file-alt w-5 text-center"></i>
                <span class="sidebar-label ml-3">Pages</span>
            </a>
            
            <a href="about.php" title="About Page" class="flex items-center px-4 py-3 rounded-lg hover:bg-gray-700 transition-colors <?php echo $current_page == 'about.php' ? 'bg-primary' : ''; ?>">
                <i class="fas fa-info-circle w-5 text-center"></i>
                <span class="sidebar-label ml-3">About Page</span>
            </a>
            
            <a href="timeline.php" title="Our Journey" class="flex items-center px-4 py-3 rounded-lg hover:bg-gray-700 transition-colors <?php echo $current_page == 'timeline.php' ? 'bg-primary' : ''; ?>">
                <i class="fas fa-history w-5 text-center"></i>
                <span class="sidebar-label ml-3">Our Journey</span>
            </a>
            
            <a href="statistics.php" title="Statistics" class="flex items-center px-4 py-3 rounded-lg hover:bg-gray-700 transition-colors <?php echo $current_page == 'statistics.php' ? 'bg-primary' : ''; ?>">
                <i class="fas fa-chart-bar w-5 text-center"></i>
                <span class="sidebar-label ml-3">Statistics</span>
            </a>
            
            <a href="carousel.php" title="Carousel" class="flex items-center px-4 py-3 rounded-lg hover:bg-gray-700 transition-colors <?php echo $current_page == 'carousel.php' ? 'bg-primary' : ''; ?>">
                <i class="fas fa-images w-5 text-center"></i>
                <span class="sidebar-label ml-3">Carousel</span>
            </a>
            
            <a href="products.php" title="Products" class="flex items-center px-4 py-3 rounded-lg hover:bg-gray-700 transition-colors <?php echo $current_page == 'products.php' ? 'bg-primary' : ''; ?>">
                <i class="fas fa-cube w-5 text-center"></i>
                <span class="sidebar-label ml-3">Products</span>
            </a>
            
            <a href="services.php" title="Services" class="flex items-center px-4 py-3 rounded-lg hover:bg-gray-700 transition-colors <?php echo $current_page == 'services.php' ? 'bg-primary' : ''; ?>">
                <i class="fas fa-concierge-bell w-5 text-center"></i>
                <span class="sidebar-label ml-3">Services</span>
            </a>
            
            <a href="case-studies.php" title="Case Studies" class="flex items-center px-4 py-3 rounded-lg hover:bg-gray-700 transition-colors <?php echo $current_page == 'case-studies.php' ? 'bg-primary' : ''; ?>">
                <i class="fas fa-book w-5 text-center"></i>
                <span class="sidebar-label ml-3">Case Studies</span>
            </a>
            
            <a href="gallery.php" title="Gallery" class="flex items-center px-4 py-3 rounded-lg hover:bg-gray-700 transition-colors <?php echo $current_page == 'gallery.php' ? 'bg-primary' : ''; ?>">
                <i class="fas fa-images w-5 text-center"></i>
                <span class="sidebar-label ml-3">Gallery</span>
            </a>
            
            <a href="resources.php" title="Resources" class="flex items-center px-4 py-3 rounded-lg hover:bg-gray-700 transition-colors <?php echo $current_page == 'resources.php' ? 'bg-primary' : ''; ?>">
                <i class="fas fa-file-download w-5 text-center"></i>
                <span class="sidebar-label ml-3">Resources</span>
            </a>
            
            <a href="certifications.php" title="Certifications" class="flex items-center px-4 py-3 rounded-lg hover:bg-gray-700 transition-colors <?php echo $current_page == 'certifications.php' ? 'bg-primary' : ''; ?>">
                <i class="fas fa-certificate w-5 text-center"></i>
                <span class="sidebar-label ml-3">Certifications</span>
            </a>
            
            <a href="messages.php" title="Messages" class="flex items-center px-4 py-3 rounded-lg hover:bg-gray-700 transition-colors relative <?php echo $current_page == 'messages.php' ? 'bg-primary' : ''; ?>">
                <i class="fas fa-envelope w-5 text-center"></i>
                <span class="sidebar-label ml-3">Messages</span>
                <?php if ($unread_messages > 0): ?>
                    <span class="sidebar-badge ml-auto bg-red-500 text-white text-xs font-bold rounded-full px-2 py-1"><?php echo $unread_messages; ?></span>
                <?php endif; ?>
            </a>
        </nav>
    </div>
    
    <!-- User Section -->
    <div class="user-section p-4 border-t border-gray-700 bg-dark">
            <div class="flex items-center mb-3">
                <div class="flex-shrink-0">
                    <i class="fas fa-user-circle text-2xl text-gray-400"></i>
                </div>
                <div class="sidebar-label user-info ml-3 flex-1 min-w-0">
                    <p class="text-sm font-medium truncate"><?php echo htmlspecialchars($current_user['username']); ?></p>
                    <?php if (!empty($current_user['full_name'])): ?>
                        <p class="text-xs text-gray-400 truncate"><?php echo htmlspecialchars($current_user['full_name']); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="space-y-1">
                <a href="profile.php" title="Profile" class="user-link flex items-center px-2 py-1.5 text-sm rounded hover:bg-gray-700 transition-colors">
                    <i class="fas fa-user w-4 mr-2 text-center"></i>
                    <span class="sidebar-label">Profile</span>
                </a>
                <a href="settings.php" title="Settings" class="user-link flex items-center px-2 py-1.5 text-sm rounded hover:bg-gray-700 transition-colors">
                    <i class="fas fa-cog w-4 mr-2 text-center"></i>
                    <span class="sidebar-label">Settings</span>
                </a>
                <a href="logout.php" title="Logout" class="user-link flex items-center px-2 py-1.5 text-sm rounded hover:bg-gray-700 transition-colors text-red-400">
                    <i class="fas fa-sign-out-alt w-4 mr-2 text-center"></i>
                    <span class="sidebar-label">Logout</span>
                </a>
            </div>
    </div>
</aside>

<!-- Sidebar Overlay (Mobile) -->
<div id="sidebar-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-30 lg:hidden hidden"></div>

<!-- Mobile Menu Button -->
<button id="sidebar-toggle" class="fixed top-4 left-4 z-50 lg:hidden bg-dark text-white p-2 rounded-lg shadow-lg">
    <i class="fas fa-bars text-xl"></i>
</button>

<style>
    /* Sidebar scrollbar — matches the dark sidebar background */
    #sidebar .sidebar-scroll {
        scrollbar-width: thin;
        scrollbar-color: #4a5568 #1a1a1a;
    }
    #sidebar .sidebar-scroll::-webkit-scrollbar {
        width: 8px;
    }
    #sidebar .sidebar-scroll::-webkit-scrollbar-track {
        background: #1a1a1a;
    }
    #sidebar .sidebar-scroll::-webkit-scrollbar-thumb {
        background-color: #4a5568;
        border-radius: 4px;
        border: 2px solid #1a1a1a;
    }
    #sidebar .sidebar-scroll::-webkit-scrollbar-thumb:hover {
        background-color: #5a6776;
    }

    /* Collapsed (desktop-only) state: shrink sidebar to an icon rail */
    @media (min-width: 1024px) {
        body.sidebar-collapsed #sidebar {
            width: 4rem;
        }
        body.sidebar-collapsed #sidebar .sidebar-label {
            display: none;
        }
        body.sidebar-collapsed #sidebar .sidebar-badge {
            display: none;
        }
        body.sidebar-collapsed #sidebar nav a {
            justify-content: center;
            padding-left: 0.5rem;
            padding-right: 0.5rem;
        }
        body.sidebar-collapsed #sidebar .user-section .user-info {
            display: none;
        }
        body.sidebar-collapsed #sidebar .user-section .user-link {
            justify-content: center;
        }
        body.sidebar-collapsed #sidebar .sidebar-brand {
            display: none;
        }
        body.sidebar-collapsed #sidebar .sidebar-header {
            justify-content: center;
        }
        body.sidebar-collapsed .lg\:ml-64 {
            margin-left: 4rem !important;
        }
    }
</style>

<script>
    // Sidebar toggle functionality
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const sidebarClose = document.getElementById('sidebar-close');
    const sidebarOverlay = document.getElementById('sidebar-overlay');
    const sidebarCollapse = document.getElementById('sidebar-collapse');

    const COLLAPSE_KEY = 'tsaci_sidebar_collapsed';

    function applyDesktopState() {
        const collapsed = localStorage.getItem(COLLAPSE_KEY) === '1';
        document.body.classList.toggle('sidebar-collapsed', collapsed);
        if (sidebarCollapse) {
            sidebarCollapse.title = collapsed ? 'Expand sidebar' : 'Collapse sidebar';
        }
    }

    function openSidebar() {
        sidebar.classList.remove('-translate-x-full');
        sidebarOverlay.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeSidebar() {
        sidebar.classList.add('-translate-x-full');
        sidebarOverlay.classList.add('hidden');
        document.body.style.overflow = '';
    }

    sidebarToggle?.addEventListener('click', openSidebar);
    sidebarClose?.addEventListener('click', closeSidebar);
    sidebarOverlay?.addEventListener('click', closeSidebar);

    // Desktop collapse/expand toggle
    sidebarCollapse?.addEventListener('click', function() {
        const isCollapsed = document.body.classList.contains('sidebar-collapsed');
        localStorage.setItem(COLLAPSE_KEY, isCollapsed ? '0' : '1');
        applyDesktopState();
    });

    // Apply persisted state on load (desktop only)
    if (window.innerWidth >= 1024) {
        applyDesktopState();
    }

    // Close sidebar on window resize if switching to desktop
    window.addEventListener('resize', function() {
        if (window.innerWidth >= 1024) {
            closeSidebar();
        }
    });
</script>

