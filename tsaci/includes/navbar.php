<?php
if (!isset($current_page)) {
    $current_page = basename($_SERVER['PHP_SELF'], '.php');
}
$company_name = getSiteSetting('company_short_name', 'Tupi Supreme');
?>
<!-- Poppins font — matches the admin console typeface -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<!-- Offset anchored sections so they don't hide under the fixed navbar -->
<style>
    section[id]{scroll-margin-top:5rem}
    /* Hide empty paragraph tags for a cleaner look on landing pages */
    p:empty{display:none !important}
</style>

<!-- Navigation -->
<nav class="bg-white/95 backdrop-blur-sm text-[#23332c] fixed w-full top-0 z-50 border-b border-[#e6ece8]">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <a href="index.php" class="flex items-center text-xl font-semibold text-[#23332c]">
                <img src="uploads/images/tupi_supreme_logo.png" alt="<?php echo htmlspecialchars_safe($company_name); ?> logo" class="h-9 w-auto mr-2 object-contain"><?php echo htmlspecialchars_safe($company_name); ?>
            </a>
            <div class="hidden md:block">
                <div class="ml-10 flex items-center gap-1">
                    <a href="index.php" class="<?php echo $current_page === 'index' ? 'bg-[#e2eae4] text-[#23332c] font-medium' : 'text-[#66746c] hover:bg-[#eaf0ec] hover:text-[#23332c]'; ?> px-3.5 py-2 rounded-full text-sm transition-colors">Home</a>
                    <a href="about.php" class="<?php echo $current_page === 'about' ? 'bg-[#e2eae4] text-[#23332c] font-medium' : 'text-[#66746c] hover:bg-[#eaf0ec] hover:text-[#23332c]'; ?> px-3.5 py-2 rounded-full text-sm transition-colors">About Us</a>
                    <a href="products.php" class="<?php echo $current_page === 'products' ? 'bg-[#e2eae4] text-[#23332c] font-medium' : 'text-[#66746c] hover:bg-[#eaf0ec] hover:text-[#23332c]'; ?> px-3.5 py-2 rounded-full text-sm transition-colors">Products</a>
                    <a href="services.php" class="<?php echo $current_page === 'services' ? 'bg-[#e2eae4] text-[#23332c] font-medium' : 'text-[#66746c] hover:bg-[#eaf0ec] hover:text-[#23332c]'; ?> px-3.5 py-2 rounded-full text-sm transition-colors">Services</a>
                    <a href="case-studies.php" class="<?php echo $current_page === 'case-studies' ? 'bg-[#e2eae4] text-[#23332c] font-medium' : 'text-[#66746c] hover:bg-[#eaf0ec] hover:text-[#23332c]'; ?> px-3.5 py-2 rounded-full text-sm transition-colors">Case Studies</a>
                    <a href="gallery.php" class="<?php echo $current_page === 'gallery' ? 'bg-[#e2eae4] text-[#23332c] font-medium' : 'text-[#66746c] hover:bg-[#eaf0ec] hover:text-[#23332c]'; ?> px-3.5 py-2 rounded-full text-sm transition-colors">Gallery</a>
                    <a href="resources.php" class="<?php echo $current_page === 'resources' ? 'bg-[#e2eae4] text-[#23332c] font-medium' : 'text-[#66746c] hover:bg-[#eaf0ec] hover:text-[#23332c]'; ?> px-3.5 py-2 rounded-full text-sm transition-colors">Resources</a>
                    <a href="certifications.php" class="<?php echo $current_page === 'certifications' ? 'bg-[#e2eae4] text-[#23332c] font-medium' : 'text-[#66746c] hover:bg-[#eaf0ec] hover:text-[#23332c]'; ?> px-3.5 py-2 rounded-full text-sm transition-colors">Certifications</a>
                    <a href="contact.php" class="ml-2 bg-[#23332c] text-white hover:bg-[#3a4a41] px-4 py-2 rounded-full text-sm font-medium transition-colors">Contact</a>
                </div>
            </div>
            <div class="md:hidden">
                <button id="mobile-menu-button" class="text-[#23332c] hover:text-[#3d7a66] focus:outline-none w-10 h-10 flex items-center justify-center rounded-full hover:bg-[#eaf0ec] transition-colors" onclick="toggleMobileMenu()">
                    <i class="fas fa-bars text-xl"></i>
                </button>
            </div>
        </div>
        <!-- Mobile Menu -->
        <div id="mobile-menu" class="hidden md:hidden bg-white border-t border-[#e6ece8]">
            <div class="px-3 pt-3 pb-4 space-y-1">
                <a href="index.php" class="block px-3.5 py-2.5 rounded-xl text-base font-medium <?php echo $current_page === 'index' ? 'bg-[#e2eae4] text-[#23332c]' : 'text-[#66746c] hover:bg-[#eaf0ec] hover:text-[#23332c]'; ?> transition-colors">Home</a>
                <a href="about.php" class="block px-3.5 py-2.5 rounded-xl text-base font-medium <?php echo $current_page === 'about' ? 'bg-[#e2eae4] text-[#23332c]' : 'text-[#66746c] hover:bg-[#eaf0ec] hover:text-[#23332c]'; ?> transition-colors">About Us</a>
                <a href="products.php" class="block px-3.5 py-2.5 rounded-xl text-base font-medium <?php echo $current_page === 'products' ? 'bg-[#e2eae4] text-[#23332c]' : 'text-[#66746c] hover:bg-[#eaf0ec] hover:text-[#23332c]'; ?> transition-colors">Products</a>
                <a href="services.php" class="block px-3.5 py-2.5 rounded-xl text-base font-medium <?php echo $current_page === 'services' ? 'bg-[#e2eae4] text-[#23332c]' : 'text-[#66746c] hover:bg-[#eaf0ec] hover:text-[#23332c]'; ?> transition-colors">Services</a>
                <a href="case-studies.php" class="block px-3.5 py-2.5 rounded-xl text-base font-medium <?php echo $current_page === 'case-studies' ? 'bg-[#e2eae4] text-[#23332c]' : 'text-[#66746c] hover:bg-[#eaf0ec] hover:text-[#23332c]'; ?> transition-colors">Case Studies</a>
                <a href="gallery.php" class="block px-3.5 py-2.5 rounded-xl text-base font-medium <?php echo $current_page === 'gallery' ? 'bg-[#e2eae4] text-[#23332c]' : 'text-[#66746c] hover:bg-[#eaf0ec] hover:text-[#23332c]'; ?> transition-colors">Gallery</a>
                <a href="resources.php" class="block px-3.5 py-2.5 rounded-xl text-base font-medium <?php echo $current_page === 'resources' ? 'bg-[#e2eae4] text-[#23332c]' : 'text-[#66746c] hover:bg-[#eaf0ec] hover:text-[#23332c]'; ?> transition-colors">Resources</a>
                <a href="certifications.php" class="block px-3.5 py-2.5 rounded-xl text-base font-medium <?php echo $current_page === 'certifications' ? 'bg-[#e2eae4] text-[#23332c]' : 'text-[#66746c] hover:bg-[#eaf0ec] hover:text-[#23332c]'; ?> transition-colors">Certifications</a>
                <a href="contact.php" class="block px-3.5 py-2.5 rounded-xl text-base font-medium bg-[#23332c] text-white hover:bg-[#3a4a41] transition-colors">Contact</a>
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
