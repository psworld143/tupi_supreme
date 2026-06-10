<?php
if (!defined('ADMIN_ACCESS')) {
    die('Direct access not allowed');
}
$current_user = getCurrentUser();
$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="bg-dark text-white shadow-lg">
    <div class="container mx-auto px-4">
        <div class="flex justify-between items-center h-16">
            <div class="flex items-center">
                <a href="index.php" class="flex items-center text-xl font-bold">
                    <i class="fas fa-shield-alt mr-2"></i>
                    <?php echo SITE_NAME; ?>
                </a>
            </div>
            
            <div class="hidden md:flex items-center space-x-4">
                <a href="index.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-700 <?php echo $current_page == 'index.php' ? 'bg-primary' : ''; ?>">
                    <i class="fas fa-home mr-1"></i> Dashboard
                </a>
                <a href="pages.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-700 <?php echo $current_page == 'pages.php' ? 'bg-primary' : ''; ?>">
                    <i class="fas fa-file-alt mr-1"></i> Pages
                </a>
                <a href="carousel.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-700 <?php echo $current_page == 'carousel.php' ? 'bg-primary' : ''; ?>">
                    <i class="fas fa-images mr-1"></i> Carousel
                </a>
                <a href="products.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-700 <?php echo $current_page == 'products.php' ? 'bg-primary' : ''; ?>">
                    <i class="fas fa-cube mr-1"></i> Products
                </a>
                <a href="services.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-700 <?php echo $current_page == 'services.php' ? 'bg-primary' : ''; ?>">
                    <i class="fas fa-concierge-bell mr-1"></i> Services
                </a>
                <a href="case-studies.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-700 <?php echo $current_page == 'case-studies.php' ? 'bg-primary' : ''; ?>">
                    <i class="fas fa-book mr-1"></i> Case Studies
                </a>
                <a href="gallery.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-700 <?php echo $current_page == 'gallery.php' ? 'bg-primary' : ''; ?>">
                    <i class="fas fa-images mr-1"></i> Gallery
                </a>
                <a href="resources.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-700 <?php echo $current_page == 'resources.php' ? 'bg-primary' : ''; ?>">
                    <i class="fas fa-file-download mr-1"></i> Resources
                </a>
                <a href="certifications.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-700 <?php echo $current_page == 'certifications.php' ? 'bg-primary' : ''; ?>">
                    <i class="fas fa-certificate mr-1"></i> Certifications
                </a>
                <a href="messages.php" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-700 relative <?php echo $current_page == 'messages.php' ? 'bg-primary' : ''; ?>">
                    <i class="fas fa-envelope mr-1"></i> Messages
                    <?php
                    $db = getDB();
                    $result = $db->query("SELECT COUNT(*) as count FROM contact_messages WHERE is_read = 0 AND is_archived = 0");
                    $unread = $result->fetch_assoc()['count'];
                    if ($unread > 0):
                    ?>
                        <span class="absolute top-0 right-0 bg-red-500 text-white text-xs rounded-full px-2 py-1"><?php echo $unread; ?></span>
                    <?php endif; ?>
                </a>
                
                <div class="ml-4 pl-4 border-l border-gray-600">
                    <div class="relative group">
                        <button class="flex items-center space-x-2 px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-700">
                            <i class="fas fa-user-circle"></i>
                            <span><?php echo htmlspecialchars($current_user['username']); ?></span>
                            <i class="fas fa-chevron-down text-xs"></i>
                        </button>
                        <div class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 hidden group-hover:block z-50">
                            <a href="profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-user mr-2"></i> Profile
                            </a>
                            <a href="settings.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-cog mr-2"></i> Settings
                            </a>
                            <div class="border-t border-gray-200"></div>
                            <a href="logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-gray-100">
                                <i class="fas fa-sign-out-alt mr-2"></i> Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="md:hidden">
                <button id="mobile-menu-button" class="text-white hover:text-gray-300 focus:outline-none">
                    <i class="fas fa-bars text-2xl"></i>
                </button>
            </div>
        </div>
        
        <!-- Mobile Menu -->
        <div id="mobile-menu" class="hidden md:hidden border-t border-gray-700 pb-4">
            <div class="px-2 pt-2 space-y-1">
                <a href="index.php" class="block px-3 py-2 rounded-md text-base font-medium hover:bg-gray-700 <?php echo $current_page == 'index.php' ? 'bg-primary' : ''; ?>">Dashboard</a>
                <a href="pages.php" class="block px-3 py-2 rounded-md text-base font-medium hover:bg-gray-700 <?php echo $current_page == 'pages.php' ? 'bg-primary' : ''; ?>">Pages</a>
                <a href="carousel.php" class="block px-3 py-2 rounded-md text-base font-medium hover:bg-gray-700 <?php echo $current_page == 'carousel.php' ? 'bg-primary' : ''; ?>">Carousel</a>
                <a href="products.php" class="block px-3 py-2 rounded-md text-base font-medium hover:bg-gray-700 <?php echo $current_page == 'products.php' ? 'bg-primary' : ''; ?>">Products</a>
                <a href="services.php" class="block px-3 py-2 rounded-md text-base font-medium hover:bg-gray-700 <?php echo $current_page == 'services.php' ? 'bg-primary' : ''; ?>">Services</a>
                <a href="case-studies.php" class="block px-3 py-2 rounded-md text-base font-medium hover:bg-gray-700 <?php echo $current_page == 'case-studies.php' ? 'bg-primary' : ''; ?>">Case Studies</a>
                <a href="gallery.php" class="block px-3 py-2 rounded-md text-base font-medium hover:bg-gray-700 <?php echo $current_page == 'gallery.php' ? 'bg-primary' : ''; ?>">Gallery</a>
                <a href="resources.php" class="block px-3 py-2 rounded-md text-base font-medium hover:bg-gray-700 <?php echo $current_page == 'resources.php' ? 'bg-primary' : ''; ?>">Resources</a>
                <a href="certifications.php" class="block px-3 py-2 rounded-md text-base font-medium hover:bg-gray-700 <?php echo $current_page == 'certifications.php' ? 'bg-primary' : ''; ?>">Certifications</a>
                <a href="messages.php" class="block px-3 py-2 rounded-md text-base font-medium hover:bg-gray-700 <?php echo $current_page == 'messages.php' ? 'bg-primary' : ''; ?>">Messages</a>
                <div class="border-t border-gray-700 pt-2">
                    <a href="profile.php" class="block px-3 py-2 rounded-md text-base font-medium hover:bg-gray-700">Profile</a>
                    <a href="logout.php" class="block px-3 py-2 rounded-md text-base font-medium hover:bg-gray-700">Logout</a>
                </div>
            </div>
        </div>
    </div>
</nav>

<script>
    document.getElementById('mobile-menu-button')?.addEventListener('click', function() {
        const menu = document.getElementById('mobile-menu');
        menu.classList.toggle('hidden');
    });
</script>

