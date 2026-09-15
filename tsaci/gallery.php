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
        'facilities' => ['#23332c', '#3d7a66'],
        'products' => ['#3d7a66', '#60796e'],
        'process' => ['#60796e', '#3d7a66'],
        'installations' => ['#23332c', '#60796e'],
        'team' => ['#3d7a66', '#23332c']
    ];
    
    $category = $image['category'] ?? 'facilities';
    $colorScheme = $colors[$category] ?? ['#23332c', '#3d7a66'];
    
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
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Poppins font — matches the admin console typeface -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Poppins', ui-sans-serif, system-ui, sans-serif;
            background-color: #f7faf8;
            color: #23332c;
        }

        .page-header-gradient {
            background: linear-gradient(135deg, #23332c, #3d7a66);
        }

        .page-header-pattern {
            background-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="50" cy="50" r="1" fill="rgba(255,255,255,0.1)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
        }

        /* Floating gradient orbs for depth — a modern hero accent */
        .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(60px);
            opacity: 0.35;
            pointer-events: none;
        }
        .orb-1 {
            width: 400px;
            height: 400px;
            background: #8bc34a;
            top: -100px;
            right: -80px;
            animation: float 8s ease-in-out infinite;
        }
        .orb-2 {
            width: 300px;
            height: 300px;
            background: #3d7a66;
            bottom: -80px;
            left: 10%;
            animation: float 10s ease-in-out infinite reverse;
        }
        @keyframes float {
            0%, 100% { transform: translate(0, 0); }
            50% { transform: translate(20px, -30px); }
        }

        /* Subtle dot grid for section backgrounds */
        .dot-grid {
            background-image: radial-gradient(circle, #c0ccc5 1px, transparent 1px);
            background-size: 24px 24px;
        }

        /* Eyebrow label — small uppercase tracked text above section titles */
        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.375rem 1rem;
            background-color: #eef3f0;
            color: #3d7a66;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            border-radius: 9999px;
        }

        /* Filter buttons */
        .filter-btn {
            transition: all 0.3s ease;
        }
        .filter-btn.active {
            background-color: #23332c;
            color: white;
            border-color: #23332c;
        }
        .filter-btn:hover:not(.active) {
            background-color: #eaf0ec;
            border-color: #3d7a66;
            color: #23332c;
        }

        /* Gallery items */
        .gallery-item {
            transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1), border-color 0.35s ease, box-shadow 0.35s ease;
            cursor: pointer;
            overflow: hidden;
        }
        .gallery-item:hover {
            transform: translateY(-6px);
            border-color: #3d7a66;
            box-shadow: 0 12px 32px -12px rgba(35, 51, 44, 0.15);
        }
        .gallery-item img {
            transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            background-color: #f5f7f5;
            min-height: 256px;
            opacity: 0;
        }
        .gallery-item:hover img {
            transform: scale(1.06);
        }
        .gallery-item img[src=""],
        .gallery-item img:not([src]) {
            display: none;
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
            background-color: rgba(15, 20, 18, 0.95);
            backdrop-filter: blur(8px);
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
            border-radius: 1rem;
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
            transition: color 0.3s ease;
        }
        .lightbox-close:hover {
            color: #8bc34a;
        }
        .lightbox-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            color: #fff;
            font-size: 24px;
            cursor: pointer;
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 9999px;
            border: 1px solid rgba(255, 255, 255, 0.15);
            transition: background-color 0.3s ease;
        }
        .lightbox-nav:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        .lightbox-prev {
            left: 20px;
        }
        .lightbox-next {
            right: 20px;
        }

        /* Scroll-triggered reveal */
        .reveal {
            opacity: 0;
            transform: translateY(24px);
            transition: opacity 0.6s cubic-bezier(0.4, 0, 0.2, 1), transform 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .reveal.is-visible {
            opacity: 1;
            transform: translateY(0);
        }
        .reveal-delay-1 { transition-delay: 0.08s; }
        .reveal-delay-2 { transition-delay: 0.16s; }
        .reveal-delay-3 { transition-delay: 0.24s; }
        .reveal-delay-4 { transition-delay: 0.32s; }

        /* Hero content uses the always-on fade-in (above the fold, no IO needed) */
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
        .fade-in-delay-1 { animation-delay: 0.05s; }
        .fade-in-delay-2 { animation-delay: 0.15s; }
        .fade-in-delay-3 { animation-delay: 0.25s; }

        /* Respect reduced-motion preference */
        @media (prefers-reduced-motion: reduce) {
            .fade-in,
            .reveal {
                opacity: 1;
                animation: none;
                transform: none;
                transition: none;
            }
            .orb {
                animation: none;
            }
            html {
                scroll-behavior: auto;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <!-- Page Header -->
    <section id="page-header" class="page-header-gradient text-white relative overflow-hidden pt-24 pb-20">
        <!-- Floating gradient orbs for depth -->
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
        <div class="page-header-pattern absolute inset-0 opacity-30"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="text-center">
                <span class="eyebrow bg-white/15 text-white/90 mb-5 fade-in fade-in-delay-1">
                    <i class="fas fa-images text-xs"></i> Visual Tour
                </span>
                <h1 class="text-4xl lg:text-6xl font-bold mb-5 leading-tight mt-4 fade-in fade-in-delay-2"><?php echo htmlspecialchars_safe($page_header_title); ?></h1>
                <p class="text-lg lg:text-xl text-white/85 max-w-2xl mx-auto fade-in fade-in-delay-3"><?php echo htmlspecialchars_safe($page_header_subtitle); ?></p>
            </div>
        </div>
    </section>

    <!-- Gallery Categories Filter -->
    <section class="py-10 bg-[#f5f7f5] border-b border-[#e6ece8]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-wrap justify-center gap-3 reveal">
                <button onclick="filterGallery('all', this)" class="filter-btn active border-2 border-[#d6ded9] text-[#23332c] px-6 py-2.5 rounded-full font-medium text-sm">All Photos</button>
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
                    <button onclick="filterGallery('<?php echo htmlspecialchars_safe($cat); ?>', this)" class="filter-btn border-2 border-[#d6ded9] text-[#23332c] px-6 py-2.5 rounded-full font-medium text-sm"><?php echo htmlspecialchars_safe($category_labels[$cat] ?? ucfirst($cat)); ?></button>
                <?php 
                    endif;
                endforeach; 
                ?>
            </div>
        </div>
    </section>

    <!-- Gallery Grid -->
    <section class="py-20 lg:py-24 relative overflow-hidden">
        <!-- Subtle dot grid backdrop -->
        <div class="absolute inset-0 dot-grid opacity-40"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
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
                        <div class="gallery-item gallery-<?php echo htmlspecialchars_safe($image['category']); ?> bg-white border border-[#e6ece8] rounded-2xl reveal <?php echo 'reveal-delay-' . ((($index % 4) + 1)); ?>" data-category="<?php echo htmlspecialchars_safe($image['category']); ?>" onclick="openLightbox(<?php echo $index; ?>)">
                            <div class="relative h-64 overflow-hidden bg-[#f5f7f5]">
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
                                <div class="absolute inset-0 bg-[#23332c] opacity-0 hover:opacity-30 transition-opacity duration-300 flex items-center justify-center cursor-pointer">
                                    <i class="fas fa-search-plus text-white text-3xl"></i>
                                </div>
                            </div>
                            <div class="p-5">
                                <h3 class="font-semibold text-[#23332c] mb-1 text-sm"><?php echo htmlspecialchars_safe($image['title']); ?></h3>
                                <?php if ($image['description']): ?>
                                    <p class="text-xs text-[#7d8b84] leading-relaxed"><?php echo htmlspecialchars_safe($image['description']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-20 reveal">
                    <div class="w-20 h-20 rounded-full bg-[#eef3f0] flex items-center justify-center mx-auto mb-5">
                        <i class="fas fa-images text-3xl text-[#60796e]"></i>
                    </div>
                    <p class="text-lg text-[#7d8b84]">No gallery images available at this time. Please check back later.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Lightbox Modal -->
    <div id="lightbox" class="lightbox" onclick="closeLightboxOnBackdrop(event)">
        <span class="lightbox-close" onclick="closeLightbox()">&times;</span>
        <span class="lightbox-nav lightbox-prev" onclick="changeImage(-1, event)">&#10094;</span>
        <span class="lightbox-nav lightbox-next" onclick="changeImage(1, event)">&#10095;</span>
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
        function filterGallery(category, btn) {
            const items = document.querySelectorAll('.gallery-item');
            const buttons = document.querySelectorAll('.filter-btn');
            
            // Update active button
            buttons.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            
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
        function changeImage(direction, event) {
            if (event) event.stopPropagation();
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

        // Scroll-triggered reveal animations
        (function() {
            const reveals = document.querySelectorAll('.reveal');
            if (!reveals.length) return;

            if (!('IntersectionObserver' in window)) {
                reveals.forEach(el => el.classList.add('is-visible'));
                return;
            }

            const observer = new IntersectionObserver(function(entries) {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        observer.unobserve(entry.target);
                    }
                });
            }, {
                threshold: 0.12,
                rootMargin: '0px 0px -60px 0px'
            });

            reveals.forEach(el => observer.observe(el));
        })();
    </script>
</body>
</html>
