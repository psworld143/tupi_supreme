<?php
require_once 'includes/config.php';

$current_page = 'index';
$company_name = getSiteSetting('company_short_name', 'Tupi Supreme');
$meta_description = getSiteSetting('meta_description', 'Premium activated carbon solutions for municipal water treatment facilities.');
$meta_keywords = getSiteSetting('meta_keywords', 'municipal water treatment activated carbon');

// Get dynamic content
$hero_title = getPageContent('index', 'hero_title', 'Premium Activated Carbon Solutions for Municipal Water Treatment');
$hero_description = getPageContent('index', 'hero_description', 'Leading provider of high-quality activated carbon products for municipal water treatment facilities.');
$hero_cta_primary_text = getPageContent('index', 'hero_cta_primary_text', 'Request Technical Consultation');
$hero_cta_primary_link = getPageContent('index', 'hero_cta_primary_link', 'contact.php');
$hero_cta_secondary_text = getPageContent('index', 'hero_cta_secondary_text', 'Download Product Specs');
$hero_cta_secondary_link = getPageContent('index', 'hero_cta_secondary_link', 'resources.php');
$features_title = getPageContent('index', 'features_title', 'Why Choose Tupi Supreme?');
$features_subtitle = getPageContent('index', 'features_subtitle', 'We deliver excellence in every product and service we provide');
$products_title = getPageContent('index', 'products_title', 'Our Premium Products');
$products_subtitle = getPageContent('index', 'products_subtitle', 'Discover our range of high-quality activated carbon solutions');
$cta_title = getPageContent('index', 'cta_title', 'Ready to Get Started?');
$cta_description = getPageContent('index', 'cta_description', 'Contact us today for a free technical consultation and quote on your activated carbon needs.');
$hero_icon = getPageContent('index', 'hero_icon', 'fas fa-water');
$cta_button_1_text = getPageContent('index', 'cta_button_1_text', 'Request Quote');
$cta_button_1_link = getPageContent('index', 'cta_button_1_link', 'contact.php');
$cta_button_2_text = getPageContent('index', 'cta_button_2_text', 'Download Technical Specs');
$cta_button_2_link = getPageContent('index', 'cta_button_2_link', 'resources.php');
$cta_button_3_text = getPageContent('index', 'cta_button_3_text', 'Contact Sales Team');
$cta_button_3_link = getPageContent('index', 'cta_button_3_link', 'contact.php');

$carousel_slides = getCarouselSlides();
$features = getHomepageFeatures();
$statistics = getStatistics();
$featured_products = getProducts(3, true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo htmlspecialchars_safe($meta_description); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars_safe($meta_keywords); ?>">
    <title><?php echo htmlspecialchars_safe(getSiteSetting('company_name', 'Tupi Supreme Activated Carbon, Inc.')); ?> - Municipal Water Treatment Solutions</title>
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

        .hero-gradient {
            background: linear-gradient(135deg, #23332c, #3d7a66);
        }

        .hero-pattern {
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

        .feature-card {
            transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1), border-color 0.35s ease, box-shadow 0.35s ease;
        }

        .feature-card:hover {
            transform: translateY(-6px);
            border-color: #3d7a66;
            box-shadow: 0 12px 32px -12px rgba(35, 51, 44, 0.15);
        }

        .feature-card:hover .feature-icon {
            transform: scale(1.08) rotate(-3deg);
        }

        .feature-icon {
            background: linear-gradient(135deg, #3d7a66, #60796e);
            transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Product card hover */
        .product-card {
            transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1), border-color 0.35s ease, box-shadow 0.35s ease;
        }
        .product-card:hover {
            transform: translateY(-6px);
            border-color: #3d7a66;
            box-shadow: 0 12px 32px -12px rgba(35, 51, 44, 0.15);
        }
        .product-card:hover .product-image-wrap {
            background-color: #eaf0ec;
        }
        .product-image-wrap {
            transition: background-color 0.35s ease;
        }

        /* Stat card */
        .stat-card {
            transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .stat-card:hover {
            transform: translateY(-4px);
        }

        .carousel-container {
            position: relative;
            overflow: hidden;
        }

        .carousel-slide {
            display: none;
            animation: fadeIn 0.5s ease-in-out;
        }

        .carousel-slide.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .carousel-indicators {
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 10px;
            z-index: 10;
        }

        .carousel-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.5);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .carousel-indicator.active {
            background: white;
            width: 30px;
            border-radius: 6px;
        }

        .carousel-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: none;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 20px;
            z-index: 10;
            transition: all 0.3s ease;
            backdrop-filter: blur(4px);
        }

        .carousel-nav:hover {
            background: rgba(255, 255, 255, 0.35);
        }

        .carousel-nav.prev {
            left: 20px;
        }

        .carousel-nav.next {
            right: 20px;
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

        /* Scroll-triggered reveal — modern alternative to always-on fade-in.
           Elements start hidden and animate in when they enter the viewport. */
        .reveal, .reveal-left, .reveal-right, .reveal-scale {
            opacity: 0;
            transition: opacity 0.6s cubic-bezier(0.4, 0, 0.2, 1), transform 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .reveal { transform: translateY(24px); }
        .reveal-left { transform: translateX(-32px); }
        .reveal-right { transform: translateX(32px); }
        .reveal-scale { transform: scale(0.92); }
        .reveal.is-visible, .reveal-left.is-visible, .reveal-right.is-visible, .reveal-scale.is-visible {
            opacity: 1;
            transform: none;
        }

        /* Soft pulsing ring on the hero icon */
        @keyframes pulse-soft {
            0%, 100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(255, 255, 255, 0.25); }
            50% { transform: scale(1.05); box-shadow: 0 0 0 20px rgba(255, 255, 255, 0); }
        }
        .hero-icon-pulse {
            animation: pulse-soft 3.5s ease-in-out infinite;
        }

        /* Staggered delays for cascading reveal within a section */
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
            .reveal, .reveal-left, .reveal-right, .reveal-scale {
                opacity: 1;
                animation: none;
                transform: none;
                transition: none;
            }
            .orb, .hero-icon-pulse {
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

    <!-- Hero Carousel Section -->
    <?php if (!empty($carousel_slides)): ?>
    <section id="hero" class="carousel-container hero-gradient text-white relative overflow-hidden pt-24 pb-20" style="min-height: 600px;">
        <!-- Floating gradient orbs for depth -->
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
        <?php foreach ($carousel_slides as $index => $slide): ?>
            <div class="carousel-slide <?php echo $index === 0 ? 'active' : ''; ?>" style="background-image: linear-gradient(135deg, rgba(35, 51, 44, 0.92), rgba(61, 122, 102, 0.92)), url('<?php echo htmlspecialchars_safe($slide['image_url']); ?>'); background-size: cover; background-position: center; position: absolute; top: 0; left: 0; right: 0; bottom: 0;">
                <div class="hero-pattern absolute inset-0 opacity-30"></div>
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 h-full flex items-center">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center w-full">
                        <div class="fade-in fade-in-delay-1">
                            <span class="eyebrow bg-white/15 text-white/90 mb-5">
                                <i class="fas fa-water text-xs"></i> Municipal Water Treatment
                            </span>
                            <?php if ($slide['title']): ?>
                                <h1 class="text-4xl lg:text-6xl font-bold mb-6 leading-tight mt-5"><?php echo htmlspecialchars_safe($slide['title']); ?></h1>
                            <?php endif; ?>
                            <?php if ($slide['description']): ?>
                                <p class="text-lg lg:text-xl mb-8 text-white/85 max-w-xl"><?php echo htmlspecialchars_safe($slide['description']); ?></p>
                            <?php endif; ?>
                            <?php if ($slide['button_text'] && $slide['button_link']): ?>
                                <div class="flex flex-col sm:flex-row gap-4">
                                    <a href="<?php echo htmlspecialchars_safe($slide['button_link']); ?>" class="bg-white text-[#23332c] hover:bg-[#eff4f1] font-medium py-3 px-8 rounded-full transition-colors text-center inline-flex items-center justify-center gap-2">
                                        <?php echo htmlspecialchars_safe($slide['button_text']); ?>
                                        <i class="fas fa-arrow-right text-xs"></i>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="text-center fade-in fade-in-delay-2 hidden lg:block">
                            <div class="hero-icon-pulse inline-flex items-center justify-center w-40 h-40 rounded-full bg-white/10 backdrop-blur-sm border border-white/20">
                                <i class="<?php echo htmlspecialchars_safe($hero_icon); ?> text-7xl text-white opacity-90"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        
        <!-- Carousel Navigation -->
        <?php if (count($carousel_slides) > 1): ?>
            <button class="carousel-nav prev" onclick="changeSlide(-1)">‹</button>
            <button class="carousel-nav next" onclick="changeSlide(1)">›</button>
            
            <!-- Carousel Indicators -->
            <div class="carousel-indicators">
                <?php foreach ($carousel_slides as $index => $slide): ?>
                    <span class="carousel-indicator <?php echo $index === 0 ? 'active' : ''; ?>" onclick="goToSlide(<?php echo $index; ?>)"></span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
    <?php else: ?>
    <!-- Fallback Hero Section (if no carousel slides) -->
    <section id="hero" class="hero-gradient text-white relative overflow-hidden pt-24 pb-20">
        <!-- Floating gradient orbs for depth -->
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
        <div class="hero-pattern absolute inset-0 opacity-30"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <div class="fade-in fade-in-delay-1">
                    <span class="eyebrow bg-white/15 text-white/90 mb-5">
                        <i class="fas fa-water text-xs"></i> Municipal Water Treatment
                    </span>
                    <h1 class="text-4xl lg:text-6xl font-bold mb-6 leading-tight mt-5"><?php echo htmlspecialchars_safe($hero_title); ?></h1>
                    <p class="text-lg lg:text-xl mb-8 text-white/85 max-w-xl"><?php echo htmlspecialchars_safe($hero_description); ?></p>
                    <div class="flex flex-col sm:flex-row gap-4">
                        <a href="<?php echo htmlspecialchars_safe($hero_cta_primary_link); ?>" class="bg-white text-[#23332c] hover:bg-[#eff4f1] font-medium py-3 px-8 rounded-full transition-colors text-center inline-flex items-center justify-center gap-2">
                            <?php echo htmlspecialchars_safe($hero_cta_primary_text); ?>
                            <i class="fas fa-arrow-right text-xs"></i>
                        </a>
                        <a href="<?php echo htmlspecialchars_safe($hero_cta_secondary_link); ?>" class="border-2 border-white/80 text-white hover:bg-white hover:text-[#23332c] font-medium py-3 px-8 rounded-full transition-colors text-center inline-flex items-center justify-center gap-2">
                            <i class="fas fa-download text-xs"></i>
                            <?php echo htmlspecialchars_safe($hero_cta_secondary_text); ?>
                        </a>
                    </div>
                </div>
                <div class="text-center fade-in fade-in-delay-2 hidden lg:block">
                    <div class="hero-icon-pulse inline-flex items-center justify-center w-40 h-40 rounded-full bg-white/10 backdrop-blur-sm border border-white/20">
                        <i class="<?php echo htmlspecialchars_safe($hero_icon); ?> text-7xl text-white opacity-90"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Features Section -->
    <section id="features" class="py-20 lg:py-24 relative overflow-hidden">
        <!-- Subtle dot grid backdrop -->
        <div class="absolute inset-0 dot-grid opacity-40"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
            <div class="text-center mb-16 reveal">
                <span class="eyebrow mb-4">
                    <i class="fas fa-star text-xs"></i> Why Us
                </span>
                <h2 class="text-3xl lg:text-4xl font-bold text-[#23332c] mb-4 mt-4"><?php echo htmlspecialchars_safe($features_title); ?></h2>
                <p class="text-lg text-[#7d8b84] max-w-2xl mx-auto"><?php echo htmlspecialchars_safe($features_subtitle); ?></p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <?php foreach ($features as $i => $feature): ?>
                    <div class="feature-card bg-white border border-[#e6ece8] rounded-2xl p-8 h-full reveal <?php echo 'reveal-delay-' . ((($i % 4) + 1)); ?>">
                        <div class="text-center">
                            <?php if ($feature['icon']): ?>
                                <div class="feature-icon w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-6">
                                    <i class="<?php echo htmlspecialchars_safe($feature['icon']); ?> text-2xl text-white"></i>
                                </div>
                            <?php endif; ?>
                            <h3 class="text-xl font-semibold text-[#23332c] mb-3"><?php echo htmlspecialchars_safe($feature['title']); ?></h3>
                            <p class="text-[#7d8b84] leading-relaxed text-sm"><?php echo htmlspecialchars_safe($feature['description']); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section id="stats" class="bg-[#23332c] text-white py-20 lg:py-24 relative overflow-hidden">
        <!-- Decorative orbs -->
        <div class="orb orb-1" style="background: #3d7a66; opacity: 0.25;"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-6 lg:gap-8">
                <?php foreach ($statistics as $i => $stat): ?>
                <div class="stat-card text-center reveal-scale <?php echo 'reveal-delay-' . ((($i % 4) + 1)); ?>">
                        <div class="stat-value text-4xl lg:text-5xl font-bold mb-2"><?php echo htmlspecialchars_safe($stat['value']); ?></div>
                        <div class="text-sm font-medium uppercase tracking-wider text-[#8bc34a]"><?php echo htmlspecialchars_safe($stat['label']); ?></div>
                        <?php if ($stat['description']): ?>
                            <div class="text-sm text-white/60 mt-2"><?php echo htmlspecialchars_safe($stat['description']); ?></div>
                        <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Products Preview -->
    <section id="products" class="py-20 lg:py-24">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16 reveal">
                <span class="eyebrow mb-4">
                    <i class="fas fa-cube text-xs"></i> Products
                </span>
                <h2 class="text-3xl lg:text-4xl font-bold text-[#23332c] mb-4 mt-4"><?php echo htmlspecialchars_safe($products_title); ?></h2>
                <p class="text-lg text-[#7d8b84] max-w-2xl mx-auto"><?php echo htmlspecialchars_safe($products_subtitle); ?></p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <?php foreach ($featured_products as $i => $product): ?>
                    <div class="product-card bg-white border border-[#e6ece8] rounded-2xl h-full flex flex-col overflow-hidden <?php echo ['reveal-left', 'reveal', 'reveal-right'][$i % 3] . ' reveal-delay-' . (($i % 3) + 1); ?>">
                        <div class="product-image-wrap flex items-center justify-center pt-10 pb-6 px-8 bg-[#f7faf8]">
                            <?php if (!empty($product['image_url'])): ?>
                                <img src="<?php echo htmlspecialchars_safe($product['image_url']); ?>" alt="<?php echo htmlspecialchars_safe($product['name']); ?>" class="w-28 h-28 object-contain">
                            <?php else: ?>
                                <div class="w-20 h-20 rounded-2xl bg-white border border-[#e6ece8] flex items-center justify-center">
                                    <i class="fas fa-cube text-3xl text-[#60796e]"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="p-8 flex-1 flex flex-col">
                            <span class="eyebrow self-start mb-3">
                                <i class="fas fa-award text-xs"></i> Featured
                            </span>
                            <h3 class="text-xl font-semibold text-[#23332c] mb-3"><?php echo htmlspecialchars_safe($product['name']); ?></h3>
                            <p class="text-[#7d8b84] mb-6 leading-relaxed text-sm flex-1"><?php echo htmlspecialchars_safe($product['description']); ?></p>
                            <a href="products.php#<?php echo htmlspecialchars_safe($product['slug']); ?>" class="self-start inline-flex items-center gap-2 border border-[#d6ded9] text-[#23332c] hover:bg-[#23332c] hover:text-white hover:border-[#23332c] font-medium py-2.5 px-5 rounded-full transition-colors text-sm">
                                View Details
                                <i class="fas fa-arrow-right text-xs"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="text-center mt-12 reveal">
                <a href="products.php" class="inline-flex items-center gap-2 text-[#3d7a66] hover:text-[#23332c] font-medium transition-colors">
                    View All Products
                    <i class="fas fa-arrow-right text-xs"></i>
                </a>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section id="cta" class="relative overflow-hidden py-20 lg:py-24">
        <!-- Gradient background with orbs -->
        <div class="absolute inset-0 hero-gradient"></div>
        <div class="orb orb-1" style="background: #8bc34a; opacity: 0.25;"></div>
        <div class="orb orb-2" style="background: #3d7a66; opacity: 0.3;"></div>
        <div class="hero-pattern absolute inset-0 opacity-30"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative text-center text-white reveal-scale">
            <span class="eyebrow bg-white/15 text-white/90 mb-5">
                <i class="fas fa-comments text-xs"></i> Get In Touch
            </span>
            <h2 class="text-3xl lg:text-4xl font-bold mb-4 mt-4"><?php echo htmlspecialchars_safe($cta_title); ?></h2>
            <p class="text-lg mb-8 text-white/85 max-w-2xl mx-auto"><?php echo htmlspecialchars_safe($cta_description); ?></p>
            <div class="flex flex-col sm:flex-row justify-center gap-3">
                <?php if ($cta_button_1_text): ?>
                    <a href="<?php echo htmlspecialchars_safe($cta_button_1_link); ?>" class="bg-white text-[#23332c] hover:bg-[#eff4f1] font-medium py-3 px-8 rounded-full transition-colors inline-flex items-center justify-center gap-2">
                        <?php echo htmlspecialchars_safe($cta_button_1_text); ?>
                        <i class="fas fa-arrow-right text-xs"></i>
                    </a>
                <?php endif; ?>
                <?php if ($cta_button_2_text): ?>
                    <a href="<?php echo htmlspecialchars_safe($cta_button_2_link); ?>" class="border-2 border-white/80 text-white hover:bg-white hover:text-[#23332c] font-medium py-3 px-8 rounded-full transition-colors inline-flex items-center justify-center gap-2">
                        <i class="fas fa-download text-xs"></i>
                        <?php echo htmlspecialchars_safe($cta_button_2_text); ?>
                    </a>
                <?php endif; ?>
                <?php if ($cta_button_3_text): ?>
                    <a href="<?php echo htmlspecialchars_safe($cta_button_3_link); ?>" class="border-2 border-white/80 text-white hover:bg-white hover:text-[#23332c] font-medium py-3 px-8 rounded-full transition-colors inline-flex items-center justify-center gap-2">
                        <i class="fas fa-phone text-xs"></i>
                        <?php echo htmlspecialchars_safe($cta_button_3_text); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>
    
    <?php if (!empty($carousel_slides) && count($carousel_slides) > 1): ?>
    <script>
        let currentSlide = 0;
        const slides = document.querySelectorAll('.carousel-slide');
        const indicators = document.querySelectorAll('.carousel-indicator');
        const totalSlides = slides.length;
        
        function showSlide(index) {
            // Hide all slides
            slides.forEach(slide => slide.classList.remove('active'));
            indicators.forEach(indicator => indicator.classList.remove('active'));
            
            // Show current slide
            if (slides[index]) {
                slides[index].classList.add('active');
                indicators[index].classList.add('active');
            }
        }
        
        function changeSlide(direction) {
            currentSlide += direction;
            if (currentSlide >= totalSlides) {
                currentSlide = 0;
            } else if (currentSlide < 0) {
                currentSlide = totalSlides - 1;
            }
            showSlide(currentSlide);
        }
        
        function goToSlide(index) {
            currentSlide = index;
            showSlide(currentSlide);
        }
        
        // Auto-play carousel
        setInterval(() => {
            changeSlide(1);
        }, 5000); // Change slide every 5 seconds
    </script>
    <?php endif; ?>

    <!-- Scroll-triggered reveal animations -->
    <script>
        (function() {
            const reveals = document.querySelectorAll('.reveal, .reveal-left, .reveal-right, .reveal-scale');
            if (!reveals.length) return;

            // Fallback: if IntersectionObserver isn't supported, show everything
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

        // Count-up animation for stat values (e.g. "500+", "98%")
        (function() {
            const statValues = document.querySelectorAll('.stat-value');
            if (!statValues.length) return;

            const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            function animateValue(el) {
                const raw = el.dataset.rawValue || el.textContent.trim();
                const match = raw.match(/^([\d,.]+)(.*)$/);
                if (!match) return;
                const target = parseFloat(match[1].replace(/,/g, ''));
                const suffix = match[2] || '';
                const hasCommas = match[1].indexOf(',') !== -1;

                if (reducedMotion || isNaN(target)) {
                    el.textContent = raw;
                    return;
                }

                const duration = 1500;
                const start = performance.now();
                function tick(now) {
                    const progress = Math.min((now - start) / duration, 1);
                    const eased = 1 - Math.pow(1 - progress, 3);
                    const current = Math.round(target * eased);
                    el.textContent = (hasCommas ? current.toLocaleString() : String(current)) + suffix;
                    if (progress < 1) requestAnimationFrame(tick);
                }
                requestAnimationFrame(tick);
            }

            if (!('IntersectionObserver' in window)) return;

            const statObserver = new IntersectionObserver(function(entries) {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        animateValue(entry.target);
                        statObserver.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.5 });

            statValues.forEach(el => {
                el.dataset.rawValue = el.textContent.trim();
                statObserver.observe(el);
            });
        })();
    </script>
</body>
</html>
