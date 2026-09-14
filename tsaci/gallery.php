<?php
require_once 'includes/config.php';
$current_page = 'gallery';

// Get dynamic content
$page_header_title = getPageContent('gallery', 'page_header_title', 'Photo Gallery');
$page_header_subtitle = getPageContent('gallery', 'page_header_subtitle', 'Explore our facilities, products, and operations through our photo collections');

// Get all gallery images
$all_gallery_images = getGalleryImages();
$gallery_categories = ['all', 'facilities', 'products', 'process', 'installations', 'team'];

// Function to generate unique placeholder image using SVG data URI (no external service needed)
function getPlaceholderImage($image, $index) {
    // Color schemes for different categories
    $colors = [
        'facilities' => ['#2c5530', '#4a7c59'],
        'products' => ['#4a7c59', '#8bc34a'],
        'process' => ['#8bc34a', '#2c5530'],
        'installations' => ['#2c5530', '#8bc34a'],
        'team' => ['#4a7c59', '#2c5530']
    ];
    
    $category = $image['category'] ?? 'facilities';
    $colorScheme = $colors[$category] ?? ['#2c5530', '#4a7c59'];
    
    // Create unique identifier using image ID or index
    $uniqueId = isset($image['id']) ? $image['id'] : ($index + 1);
    
    // Create unique text based on title
    $title = $image['title'] ?? 'Image';
    $displayText = htmlspecialchars(substr($title, 0, 20));
    
    // Use different background colors based on unique ID for variety
    $bgColorIndex = $uniqueId % count($colorScheme);
    $bgColor = $colorScheme[$bgColorIndex];
    $textColor = '#ffffff';
    
    // Create SVG placeholder as data URI (works offline, no external service needed)
    $width = 800;
    $height = 600;
    $fontSize = 24;
    $textX = $width / 2;
    $textY = $height / 2;
    
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $width . '" height="' . $height . '">';
    $svg .= '<defs><linearGradient id="grad' . $uniqueId . '" x1="0%" y1="0%" x2="100%" y2="100%">';
    $svg .= '<stop offset="0%" style="stop-color:' . $colorScheme[0] . ';stop-opacity:1" />';
    $svg .= '<stop offset="100%" style="stop-color:' . $colorScheme[1] . ';stop-opacity:1" />';
    $svg .= '</linearGradient></defs>';
    $svg .= '<rect width="' . $width . '" height="' . $height . '" fill="url(#grad' . $uniqueId . ')" />';
    $svg .= '<text x="' . $textX . '" y="' . ($textY - 10) . '" font-family="Arial, sans-serif" font-size="' . $fontSize . '" font-weight="bold" fill="' . $textColor . '" text-anchor="middle" dominant-baseline="middle">' . $displayText . '</text>';
    $svg .= '<text x="' . $textX . '" y="' . ($textY + 20) . '" font-family="Arial, sans-serif" font-size="16" fill="' . $textColor . '" text-anchor="middle" opacity="0.8">#' . $uniqueId . '</text>';
    $svg .= '</svg>';
    
    // Return as data URI
    return 'data:image/svg+xml;base64,' . base64_encode($svg);
}

// Function to check if image URL is valid
function isValidImageUrl($url) {
    if (empty($url) || $url === '#' || $url === null) {
        return false;
    }
    
    // Reject placeholder service URLs (we use SVG placeholders instead)
    if (strpos($url, 'via.placeholder.com') !== false || 
        strpos($url, 'placeholder.com') !== false) {
        return false;
    }
    
    // Check if it's a valid URL
    if (filter_var($url, FILTER_VALIDATE_URL)) {
        return true;
    }
    
    // Check if it's a local file path
    if (file_exists($url)) {
        return true;
    }
    
    return false;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="View our photo gallery showcasing facilities, products, manufacturing processes, and municipal water treatment installations at Tupi Supreme Activated Carbon, Inc.">
    <meta name="keywords" content="activated carbon gallery, water treatment photos, manufacturing facility, activated carbon products, TSACI gallery">
    <title>Gallery - Photo Albums | <?php echo htmlspecialchars_safe(getSiteSetting('company_name', 'Tupi Supreme Activated Carbon, Inc.')); ?></title>
    <!-- Tailwind CSS CDN - Note: For production, consider using PostCSS or Tailwind CLI -->
    <!-- See: https://tailwindcss.com/docs/installation -->
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
    <style>
        .page-header-gradient {
            background: linear-gradient(135deg, #2c5530, #4a7c59);
        }
        
        .page-header-pattern {
            background-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="50" cy="50" r="1" fill="rgba(255,255,255,0.1)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
        }
        
        .gallery-item {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            cursor: pointer;
            overflow: hidden;
        }
        
        .gallery-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
        
        .gallery-item img {
            transition: transform 0.3s ease;
            background-color: #f3f4f6;
            min-height: 256px;
            opacity: 0;
        }
        
        .gallery-item:hover img {
            transform: scale(1.05);
        }
        
        .gallery-item img[src=""],
        .gallery-item img:not([src]) {
            display: none;
        }
        
        /* Fallback for broken images */
        .gallery-item .image-fallback {
            background: linear-gradient(135deg, #2c5530, #4a7c59);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
            opacity: 0.75;
            width: 100%;
            height: 100%;
        }
        
        /* Lightbox Styles */
        .lightbox {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.9);
            overflow: auto;
        }
        
        .lightbox.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .lightbox-content {
            position: relative;
            max-width: 90%;
            max-height: 90%;
            margin: auto;
        }
        
        .lightbox-content img {
            width: 100%;
            height: auto;
            border-radius: 8px;
        }
        
        .lightbox-close {
            position: absolute;
            top: 20px;
            right: 35px;
            color: #fff;
            font-size: 40px;
            font-weight: bold;
            cursor: pointer;
            z-index: 10000;
        }
        
        .lightbox-close:hover {
            color: #8bc34a;
        }
        
        .lightbox-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            color: #fff;
            font-size: 30px;
            cursor: pointer;
            padding: 10px 15px;
            background: rgba(0, 0, 0, 0.5);
            border-radius: 5px;
        }
        
        .lightbox-nav:hover {
            background: rgba(0, 0, 0, 0.8);
        }
        
        .lightbox-prev {
            left: 20px;
        }
        
        .lightbox-next {
            right: 20px;
        }
    </style>
</head>
<body class="font-sans">
    <?php include 'includes/navbar.php'; ?>

    <!-- Page Header -->
    <section class="page-header-gradient text-white relative overflow-hidden pt-24 pb-20">
        <div class="page-header-pattern absolute inset-0 opacity-30"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="text-center">
                <h1 class="text-5xl lg:text-6xl font-bold mb-6"><?php echo htmlspecialchars_safe($page_header_title); ?></h1>
                <p class="text-xl"><?php echo htmlspecialchars_safe($page_header_subtitle); ?></p>
            </div>
        </div>
    </section>

    <!-- Gallery Categories Filter -->
    <section class="py-12 bg-light">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-wrap justify-center gap-4">
                <button onclick="filterGallery('all')" class="filter-btn active px-6 py-2 rounded-full bg-primary text-white font-semibold transition duration-300 hover:bg-secondary">All Photos</button>
                <?php 
                $category_labels = [
                    'facilities' => 'Facilities',
                    'products' => 'Products',
                    'process' => 'Manufacturing',
                    'installations' => 'Installations',
                    'team' => 'Team & Operations'
                ];
                foreach ($gallery_categories as $cat):
                    if ($cat === 'all') continue;
                    $has_images = false;
                    foreach ($all_gallery_images as $img) {
                        if ($img['category'] === $cat) {
                            $has_images = true;
                            break;
                        }
                    }
                    if ($has_images):
                ?>
                    <button onclick="filterGallery('<?php echo htmlspecialchars_safe($cat); ?>')" class="filter-btn px-6 py-2 rounded-full bg-gray-200 text-gray-700 font-semibold transition duration-300 hover:bg-gray-300"><?php echo htmlspecialchars_safe($category_labels[$cat] ?? ucfirst($cat)); ?></button>
                <?php 
                    endif;
                endforeach; 
                ?>
            </div>
        </div>
    </section>

    <!-- Gallery Grid -->
    <section class="py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <?php if (!empty($all_gallery_images)): ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6" id="gallery-grid">
                    <?php foreach ($all_gallery_images as $index => $image): 
                        // Always generate placeholder URL
                        $placeholderUrl = getPlaceholderImage($image, $index);
                        
                        // Check if we have a valid image URL (not empty, not '#', not placeholder service, and is a URL or file path)
                        $imageUrlFromDb = $image['image_url'] ?? '';
                        $isPlaceholderService = strpos($imageUrlFromDb, 'via.placeholder.com') !== false || 
                                               strpos($imageUrlFromDb, 'placeholder.com') !== false;
                        
                        // Resolve root-relative URLs (e.g. /tupi_supreme/tsaci/uploads/...) to a real filesystem path
                        $resolvedPath = $imageUrlFromDb;
                        if (strpos($imageUrlFromDb, '/') === 0) {
                            // Strip leading slash and try to map to the document root
                            $docRoot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/\\');
                            $resolvedPath = $docRoot . '/' . ltrim($imageUrlFromDb, '/');
                        }
                        
                        $hasValidImage = !empty($imageUrlFromDb) && 
                                        $imageUrlFromDb !== '#' && 
                                        $imageUrlFromDb !== null &&
                                        !$isPlaceholderService &&
                                        (filter_var($imageUrlFromDb, FILTER_VALIDATE_URL) || 
                                         strpos($imageUrlFromDb, '/') === 0 ||
                                         file_exists($resolvedPath));
                        
                        // Use placeholder if no valid image URL or if it's a placeholder service URL
                        $imageUrl = $hasValidImage ? $imageUrlFromDb : $placeholderUrl;
                    ?>
                        <div class="gallery-item gallery-<?php echo htmlspecialchars_safe($image['category']); ?> bg-white rounded-lg shadow-lg overflow-hidden" data-category="<?php echo htmlspecialchars_safe($image['category']); ?>" onclick="openLightbox(<?php echo $index; ?>)">
                            <div class="relative h-64 overflow-hidden bg-gray-100">
                                <img src="<?php echo htmlspecialchars_safe($imageUrl); ?>" 
                                     alt="<?php echo htmlspecialchars_safe($image['title']); ?>" 
                                     class="w-full h-full object-cover"
                                     loading="lazy"
                                     data-placeholder="<?php echo htmlspecialchars_safe($placeholderUrl); ?>"
                                     onerror="handleImageError(this);"
                                     onload="this.style.opacity='1';">
                                <noscript>
                                    <img src="<?php echo htmlspecialchars_safe($placeholderUrl); ?>" alt="<?php echo htmlspecialchars_safe($image['title']); ?>" class="w-full h-full object-cover">
                                </noscript>
                                <div class="absolute inset-0 bg-black opacity-0 hover:opacity-30 transition duration-300 flex items-center justify-center cursor-pointer">
                            <i class="fas fa-search-plus text-white text-3xl"></i>
                        </div>
                    </div>
                    <div class="p-4">
                                <h3 class="font-bold text-gray-900 mb-1"><?php echo htmlspecialchars_safe($image['title']); ?></h3>
                                <?php if ($image['description']): ?>
                                    <p class="text-sm text-gray-600"><?php echo htmlspecialchars_safe($image['description']); ?></p>
                                <?php endif; ?>
                </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-20">
                    <i class="fas fa-images text-6xl text-gray-300 mb-4"></i>
                    <p class="text-xl text-gray-600">No gallery images available at this time. Please check back later.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Lightbox Modal -->
    <div id="lightbox" class="lightbox" onclick="closeLightboxOnBackdrop(event)">
        <span class="lightbox-close" onclick="closeLightbox()">&times;</span>
        <span class="lightbox-nav lightbox-prev" onclick="changeImage(-1)">&#10094;</span>
        <span class="lightbox-nav lightbox-next" onclick="changeImage(1)">&#10095;</span>
        <div class="lightbox-content">
            <img id="lightbox-img" src="" alt="Gallery Image">
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <!-- JavaScript -->
    <script>
        // Handle broken images
        function handleImageError(img) {
            const placeholder = img.getAttribute('data-placeholder');
            if (placeholder) {
                img.onerror = null; // Prevent infinite loop
                img.src = placeholder;
                img.style.opacity = '1';
            } else {
                // Fallback to a generic SVG placeholder
                const fallbackSvg = 'data:image/svg+xml;base64,' + btoa('<svg xmlns="http://www.w3.org/2000/svg" width="800" height="600"><rect width="800" height="600" fill="#cccccc"/><text x="400" y="300" font-family="Arial" font-size="24" fill="#666666" text-anchor="middle" dominant-baseline="middle">Image Not Available</text></svg>');
                img.src = fallbackSvg;
                img.style.opacity = '1';
            }
        }
        
        // Gallery images data from database
        const galleryImages = <?php 
        $galleryData = [];
        foreach ($all_gallery_images as $index => $img) {
            $placeholderUrl = getPlaceholderImage($img, $index);
            $imageUrlFromDb = $img['image_url'] ?? '';
            $isPlaceholderService = strpos($imageUrlFromDb, 'via.placeholder.com') !== false || 
                                   strpos($imageUrlFromDb, 'placeholder.com') !== false;
            
            // Resolve root-relative URLs to a real filesystem path for the file_exists check
            $resolvedPath = $imageUrlFromDb;
            if (strpos($imageUrlFromDb, '/') === 0) {
                $docRoot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/\\');
                $resolvedPath = $docRoot . '/' . ltrim($imageUrlFromDb, '/');
            }
            
            $hasValidImage = !empty($imageUrlFromDb) && 
                            $imageUrlFromDb !== '#' && 
                            $imageUrlFromDb !== null &&
                            !$isPlaceholderService &&
                            (filter_var($imageUrlFromDb, FILTER_VALIDATE_URL) || 
                             strpos($imageUrlFromDb, '/') === 0 ||
                             file_exists($resolvedPath));
            $imageUrl = $hasValidImage ? $imageUrlFromDb : $placeholderUrl;
            
            $galleryData[] = [
                'src' => $imageUrl,
                'title' => $img['title'],
                'description' => $img['description'] ?? '',
                'category' => $img['category'],
                'placeholder' => $placeholderUrl
            ];
        }
        echo json_encode($galleryData); 
        ?>;

        let currentImageIndex = 0;

        // Filter Gallery
        function filterGallery(category) {
            const items = document.querySelectorAll('.gallery-item');
            const buttons = document.querySelectorAll('.filter-btn');
            
            // Update active button
            buttons.forEach(btn => {
                btn.classList.remove('active', 'bg-primary', 'text-white');
                btn.classList.add('bg-gray-200', 'text-gray-700');
            });
            event.target.classList.add('active', 'bg-primary', 'text-white');
            event.target.classList.remove('bg-gray-200', 'text-gray-700');
            
            // Filter items
            items.forEach(item => {
                if (category === 'all' || item.dataset.category === category) {
                    item.style.display = 'block';
                    setTimeout(() => {
                        item.style.opacity = '1';
                        item.style.transform = 'scale(1)';
                    }, 10);
                } else {
                    item.style.opacity = '0';
                    item.style.transform = 'scale(0.8)';
                    setTimeout(() => {
                        item.style.display = 'none';
                    }, 300);
                }
            });
        }

        // Open Lightbox
        function openLightbox(index) {
            if (index < 0 || index >= galleryImages.length) return;
            
            currentImageIndex = index;
            const lightbox = document.getElementById('lightbox');
            const img = document.getElementById('lightbox-img');
            
            // Use actual image URL or unique placeholder
            const imageData = galleryImages[index];
            img.src = imageData.src || imageData.placeholder;
            img.alt = imageData.title;
            
            // Ensure fallback if image fails to load
            img.onerror = function() {
                this.onerror = null; // Prevent infinite loop
                this.src = imageData.placeholder;
                this.style.display = 'block';
            };
            
            img.onload = function() {
                this.style.display = 'block';
            };
            
            lightbox.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        // Close Lightbox
        function closeLightbox() {
            const lightbox = document.getElementById('lightbox');
            lightbox.classList.remove('active');
            document.body.style.overflow = 'auto';
        }

        // Close lightbox on backdrop click
        function closeLightboxOnBackdrop(event) {
            if (event.target.id === 'lightbox') {
                closeLightbox();
            }
        }

        // Change Image in Lightbox
        function changeImage(direction) {
            event.stopPropagation();
            currentImageIndex += direction;
            
            if (currentImageIndex < 0) {
                currentImageIndex = galleryImages.length - 1;
            } else if (currentImageIndex >= galleryImages.length) {
                currentImageIndex = 0;
            }
            
            const img = document.getElementById('lightbox-img');
            const imageData = galleryImages[currentImageIndex];
            img.src = imageData.src || imageData.placeholder;
            img.alt = imageData.title;
            
            // Ensure fallback if image fails to load
            img.onerror = function() {
                this.onerror = null; // Prevent infinite loop
                this.src = imageData.placeholder;
                this.style.display = 'block';
            };
            
            img.onload = function() {
                this.style.display = 'block';
            };
        }

        // Keyboard navigation
        document.addEventListener('keydown', function(e) {
            const lightbox = document.getElementById('lightbox');
            if (lightbox.classList.contains('active')) {
                if (e.key === 'Escape') {
                    closeLightbox();
                } else if (e.key === 'ArrowLeft') {
                    changeImage(-1);
                } else if (e.key === 'ArrowRight') {
                    changeImage(1);
                }
            }
        });

        // Mobile Menu Toggle Function
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
</body>
</html>
