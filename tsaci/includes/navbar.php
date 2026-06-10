<?php
if (!isset($current_page)) {
    $current_page = basename($_SERVER['PHP_SELF'], '.php');
}
$company_name = getSiteSetting('company_short_name', 'Tupi Supreme');
?>
<!-- Navigation -->
<nav class="bg-dark text-white fixed w-full top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <a href="index.php" class="flex items-center text-xl font-bold">
                <i class="fas fa-leaf mr-2"></i><?php echo htmlspecialchars_safe($company_name); ?>
            </a>
            <div class="hidden md:block">
                <div class="ml-10 flex items-baseline space-x-4">
                    <a href="index.php" class="<?php echo $current_page === 'index' ? 'text-white bg-primary' : 'text-gray-300 hover:text-white'; ?> px-3 py-2 rounded-md text-sm font-medium">Home</a>
                    <a href="about.php" class="<?php echo $current_page === 'about' ? 'text-white bg-primary' : 'text-gray-300 hover:text-white'; ?> px-3 py-2 rounded-md text-sm font-medium">About Us</a>
                    <a href="products.php" class="<?php echo $current_page === 'products' ? 'text-white bg-primary' : 'text-gray-300 hover:text-white'; ?> px-3 py-2 rounded-md text-sm font-medium">Products</a>
                    <a href="services.php" class="<?php echo $current_page === 'services' ? 'text-white bg-primary' : 'text-gray-300 hover:text-white'; ?> px-3 py-2 rounded-md text-sm font-medium">Services</a>
                    <a href="case-studies.php" class="<?php echo $current_page === 'case-studies' ? 'text-white bg-primary' : 'text-gray-300 hover:text-white'; ?> px-3 py-2 rounded-md text-sm font-medium">Case Studies</a>
                    <a href="gallery.php" class="<?php echo $current_page === 'gallery' ? 'text-white bg-primary' : 'text-gray-300 hover:text-white'; ?> px-3 py-2 rounded-md text-sm font-medium">Gallery</a>
                    <a href="resources.php" class="<?php echo $current_page === 'resources' ? 'text-white bg-primary' : 'text-gray-300 hover:text-white'; ?> px-3 py-2 rounded-md text-sm font-medium">Resources</a>
                    <a href="contact.php" class="<?php echo $current_page === 'contact' ? 'text-white bg-primary' : 'text-gray-300 hover:text-white'; ?> px-3 py-2 rounded-md text-sm font-medium">Contact</a>
                </div>
            </div>
            <div class="md:hidden">
                <button id="mobile-menu-button" class="text-white hover:text-gray-300 focus:outline-none focus:text-white" onclick="toggleMobileMenu()">
                    <i class="fas fa-bars text-2xl"></i>
                </button>
            </div>
        </div>
        <!-- Mobile Menu -->
        <div id="mobile-menu" class="hidden md:hidden bg-dark border-t border-gray-700">
            <div class="px-2 pt-2 pb-3 space-y-1">
                <a href="index.php" class="block px-3 py-2 rounded-md text-base font-medium <?php echo $current_page === 'index' ? 'text-white bg-primary' : 'text-gray-300 hover:text-white hover:bg-gray-700'; ?>">Home</a>
                <a href="about.php" class="block px-3 py-2 rounded-md text-base font-medium <?php echo $current_page === 'about' ? 'text-white bg-primary' : 'text-gray-300 hover:text-white hover:bg-gray-700'; ?>">About Us</a>
                <a href="products.php" class="block px-3 py-2 rounded-md text-base font-medium <?php echo $current_page === 'products' ? 'text-white bg-primary' : 'text-gray-300 hover:text-white hover:bg-gray-700'; ?>">Products</a>
                <a href="services.php" class="block px-3 py-2 rounded-md text-base font-medium <?php echo $current_page === 'services' ? 'text-white bg-primary' : 'text-gray-300 hover:text-white hover:bg-gray-700'; ?>">Services</a>
                <a href="case-studies.php" class="block px-3 py-2 rounded-md text-base font-medium <?php echo $current_page === 'case-studies' ? 'text-white bg-primary' : 'text-gray-300 hover:text-white hover:bg-gray-700'; ?>">Case Studies</a>
                <a href="gallery.php" class="block px-3 py-2 rounded-md text-base font-medium <?php echo $current_page === 'gallery' ? 'text-white bg-primary' : 'text-gray-300 hover:text-white hover:bg-gray-700'; ?>">Gallery</a>
                <a href="resources.php" class="block px-3 py-2 rounded-md text-base font-medium <?php echo $current_page === 'resources' ? 'text-white bg-primary' : 'text-gray-300 hover:text-white hover:bg-gray-700'; ?>">Resources</a>
                <a href="certifications.php" class="block px-3 py-2 rounded-md text-base font-medium <?php echo $current_page === 'certifications' ? 'text-white bg-primary' : 'text-gray-300 hover:text-white hover:bg-gray-700'; ?>">Certifications</a>
                <a href="contact.php" class="block px-3 py-2 rounded-md text-base font-medium <?php echo $current_page === 'contact' ? 'text-white bg-primary' : 'text-gray-300 hover:text-white hover:bg-gray-700'; ?>">Contact</a>
            </div>
        </div>
    </div>
</nav>

<script>
function toggleMobileMenu() {
    const mobileMenu = document.getElementById('mobile-menu');
    const menuButton = document.getElementById('mobile-menu-button');
    const icon = menuButton.querySelector('i');
    
    if (mobileMenu.classList.contains('hidden')) {
        mobileMenu.classList.remove('hidden');
        icon.classList.remove('fa-bars');
        icon.classList.add('fa-times');
    } else {
        mobileMenu.classList.add('hidden');
        icon.classList.remove('fa-times');
        icon.classList.add('fa-bars');
    }
}

document.addEventListener('click', function(event) {
    const mobileMenu = document.getElementById('mobile-menu');
    const menuButton = document.getElementById('mobile-menu-button');
    
    if (mobileMenu && menuButton && !mobileMenu.contains(event.target) && !menuButton.contains(event.target)) {
        if (!mobileMenu.classList.contains('hidden')) {
            mobileMenu.classList.add('hidden');
            const icon = menuButton.querySelector('i');
            if (icon) {
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            }
        }
    }
});

window.addEventListener('resize', function() {
    if (window.innerWidth >= 768) {
        const mobileMenu = document.getElementById('mobile-menu');
        const menuButton = document.getElementById('mobile-menu-button');
        if (mobileMenu && !mobileMenu.classList.contains('hidden')) {
            mobileMenu.classList.add('hidden');
            const icon = menuButton.querySelector('i');
            if (icon) {
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            }
        }
    }
});
</script>

