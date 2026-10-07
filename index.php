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
    <link rel="icon" type="image/png" href="uploads/images/favicon.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo htmlspecialchars_safe($meta_description); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars_safe($meta_keywords); ?>">
    <title><?php echo htmlspecialchars_safe(getSiteSetting('company_name', 'Tupi Supreme Activated Carbon, Inc.')); ?> - Municipal Water Treatment Solutions</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Inter — modern geometric sans, matches the admin console -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
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
            font-family: 'Inter', ui-sans-serif, system-ui, sans-serif;
            background-color: #f8faf9;
            color: #23332c;
            -webkit-font-smoothing: antialiased;
            letter-spacing: -0.01em;
        }

        .hero-gradient {
            background: #23332c;
        }

        .hero-pattern {
            background-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="50" cy="50" r="1" fill="rgba(255,255,255,0.1)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
        }

        /* Modern button sheen */
        .btn-glow {
            box-shadow: 0 1px 2px rgba(21, 35, 27, 0.35), 0 8px 24px -8px rgba(21, 35, 27, 0.5);
        }
        .btn-glow:hover {
            box-shadow: 0 2px 4px rgba(21, 35, 27, 0.35), 0 14px 32px -10px rgba(21, 35, 27, 0.55);
            transform: translateY(-1px);
        }
        .btn-glow, .btn-ghost-light {
            transition: transform 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease, color 0.2s ease;
        }
        .btn-ghost-light:hover {
            transform: translateY(-1px);
        }

        /* Section heading tightening */
        h1, h2, h3 { letter-spacing: -0.025em; }



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
            border-color: rgba(61, 122, 102, 0.4);
            box-shadow: 0 20px 40px -16px rgba(35, 51, 44, 0.18);
        }

        .feature-card:hover .feature-icon {
            transform: scale(1.08) rotate(-3deg);
            box-shadow: 0 12px 24px -8px rgba(44, 85, 48, 0.45);
        }

        /* Flat icon chip with a soft brand shadow */
        .feature-icon {
            background: #2c5530;
            box-shadow: 0 8px 18px -8px rgba(44, 85, 48, 0.45);
            transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.35s ease;
        }

        /* Product card hover */
        .product-card {
            transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1), border-color 0.35s ease, box-shadow 0.35s ease;
        }
        .product-card:hover {
            transform: translateY(-6px);
            border-color: rgba(61, 122, 102, 0.4);
            box-shadow: 0 20px 40px -16px rgba(35, 51, 44, 0.18);
        }
        .product-card:hover .product-image-wrap {
            background-color: #e9f1ea;
        }
        .product-card:hover .product-image-wrap img {
            transform: scale(1.06);
        }
        .product-image-wrap {
            background-color: #f7faf8;
            transition: background-color 0.35s ease;
        }
        .product-image-wrap img {
            transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Stat card */
        .stat-card {
            position: relative;
            transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1), background-color 0.3s ease;
            border-radius: 1rem;
            padding: 1.5rem 0.75rem;
        }
        .stat-card:hover {
            transform: translateY(-4px);
            background-color: rgba(255, 255, 255, 0.05);
        }
        .stat-card::after {
            content: '';
            display: block;
            width: 2.25rem;
            height: 3px;
            margin: 0.875rem auto 0;
            border-radius: 9999px;
            background: #8bc34a;
            opacity: 0.85;
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
            width: 22px;
            height: 5px;
            border-radius: 9999px;
            background: rgba(255, 255, 255, 0.35);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .carousel-indicator:hover { background: rgba(255, 255, 255, 0.6); }

        .carousel-indicator.active {
            background: #8bc34a;
            width: 38px;
        }

        .carousel-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.22);
            width: 46px;
            height: 46px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 18px;
            z-index: 10;
            transition: all 0.3s ease;
            backdrop-filter: blur(8px);
        }

        .carousel-nav:hover {
            background: rgba(255, 255, 255, 0.25);
            border-color: rgba(255, 255, 255, 0.4);
        }

        .carousel-nav.prev {
            left: 20px;
        }

        .carousel-nav.next {
            right: 20px;
        }

        /* Eyebrow label — small uppercase tracked pill above section titles */
        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.4rem 1.05rem;
            background-color: #e9f1ea;
            border: 1px solid rgba(44, 85, 48, 0.12);
            color: #2c5530;
            font-size: 0.6875rem;
            font-weight: 700;
            letter-spacing: 0.1em;
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
            .hero-icon-pulse {
                animation: none;
            }
            html {
                scroll-behavior: auto;
            }
        }
        .home-hero { min-height: 620px; padding-top: 7.5rem; padding-bottom: 6.5rem; }
        .home-hero.carousel-container { padding: 0; }
        .home-hero .carousel-slide { position: relative !important; padding: 7.5rem 0 6.5rem; }
        .home-hero .carousel-slide::before { content: ''; position: absolute; inset: 0; background-color: var(--slide-overlay, rgba(35, 51, 44, .88)); }
        .home-hero .hero-inner { min-height: 460px; }
        .home-hero h1 { max-width: 780px; font-size: clamp(2.6rem, 5vw, 4.75rem); line-height: 1.08; font-weight: 700; }
        .home-hero .eyebrow, .home-cta .eyebrow { background: rgba(255,255,255,.1); border-color: rgba(255,255,255,.2); color: #e9f1ea; }

        .home-hero .carousel-indicators { bottom: 2.5rem; }
        .home-hero .carousel-nav { width: 42px; height: 42px; }
        .home-section-head { max-width: 680px; }
        .home-section-head h2 { line-height: 1.16; font-size: clamp(2rem, 3.4vw, 3.25rem); }
        .home-section-head p { line-height: 1.7; }
        .home-features .feature-card { position: relative; text-align: left; border-radius: 1rem; padding: 2rem; box-shadow: 0 3px 15px rgba(35,51,44,.035); }
        .home-features .feature-card:hover { transform: translateY(-3px); box-shadow: 0 12px 28px rgba(35,51,44,.08); }
        .home-features .feature-icon { width: 3rem; height: 3rem; margin: 0 0 2rem; border-radius: .8rem; box-shadow: none; }
        .home-features .feature-card:hover .feature-icon { transform: none; box-shadow: none; }
        .home-features .feature-card .feature-index { font-size: .75rem; font-weight: 600; letter-spacing: .1em; color: #4a7c59; }
        .home-stats { background: #23332c; }
        .home-stats .stat-card { border-radius: 0; padding: 1rem 1.5rem; text-align: left; }
        .home-stats .stat-card:not(:nth-child(4n+1)) { border-left: 1px solid rgba(255,255,255,.2); }
        .home-stats .stat-card::after { margin: 1.5rem 0 0; }
        .home-stats .stat-value { letter-spacing: -.045em; line-height: 1.05; }
        .home-products { background: #f7f9f7; }
        .home-products .product-card { border-radius: 1rem; box-shadow: 0 3px 15px rgba(35,51,44,.035); }
        .home-products .product-card:hover { transform: translateY(-3px); box-shadow: 0 12px 28px rgba(35,51,44,.08); }
        .home-products .product-image-wrap { min-height: 220px; background: #eef3ef; }
        .home-products .product-image-wrap img { width: 9rem; height: 9rem; }
        .home-products .product-card .eyebrow { padding: 0; border: 0; background: transparent; border-radius: 0; }
        .home-products .product-link { border-top: 1px solid #e6ece8; margin-top: auto; padding-top: 1.25rem; color: #2c5530; font-weight: 600; }
        .home-products .product-link:hover { color: #23332c; }
        .home-cta { background: #e9f1ea; }
        .home-cta .eyebrow { background: #fff; border-color: #d6e4d8; color: #2c5530; }
        .home-cta .cta-panel { border-radius: 1.5rem; background: #23332c; padding: clamp(2rem, 6vw, 4.5rem); }
        .home-cta .cta-actions { max-width: 440px; }
        .home-cta .cta-actions a { width: 100%; }

        /* Full-viewport sections — content centers vertically under the fixed nav */
        .home-hero,
        .home-features,
        .home-stats,
        .home-products,
        .home-cta {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .home-hero .carousel-slide { min-height: 100vh; }
        .home-hero .carousel-slide.active {
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        a:focus-visible, button:focus-visible, .carousel-indicator:focus-visible { outline: 3px solid #8bc34a; outline-offset: 3px; }
        @media (max-width: 1023px) {
            .home-stats .stat-card:nth-child(odd) { border-left: 0; }
            .home-stats .stat-card:nth-child(even) { border-left: 1px solid rgba(255,255,255,.2); }
            .home-stats .stat-card:nth-child(n+3) { border-top: 1px solid rgba(255,255,255,.2); padding-top: 2rem; }
        }
        @media (max-width: 639px) {
            .home-hero { min-height: 0; padding-top: 7.5rem; padding-bottom: 6rem; }
            .home-hero .carousel-slide { min-height: 0; padding: 7.5rem 0 6rem; }
            .home-hero .carousel-slide.active { display: block; }
            .home-features, .home-stats, .home-products, .home-cta { min-height: 0; }
            .home-hero .hero-inner { min-height: 450px; }
            .home-hero .carousel-nav { display: none; }
            .home-hero .carousel-indicators { bottom: 1.5rem; }
            .home-stats .stat-card { padding: 1rem .75rem; }
            .home-stats .stat-card .stat-value { font-size: 2rem; }
        }
        @media (prefers-reduced-motion: reduce) {
            .feature-card, .product-card, .btn-glow, .btn-ghost-light { transition: none; }
            .feature-card:hover, .product-card:hover, .btn-glow:hover, .btn-ghost-light:hover { transform: none; }
        }

        /* ── Anti-slop pass ──────────────────────────────────────────────
           Flat surfaces, one radius, calm motion — mirrors the admin
           anti-slop rules. Override-only; markup + PHP untouched. */

        /* CTA buttons: no sheen, no hover lift */
        .btn-glow { box-shadow: none; }
        .btn-glow:hover,
        .btn-ghost-light:hover {
            transform: none !important;
            box-shadow: none !important;
        }
        a[class*="backdrop-blur"],
        button[class*="backdrop-blur"] {
            backdrop-filter: none !important;
            -webkit-backdrop-filter: none !important;
        }

        /* Cards: flat hairline, no lift/glow on hover */
        .home-features .feature-card,
        .home-products .product-card { box-shadow: none; }
        .feature-card:hover,
        .product-card:hover,
        .home-features .feature-card:hover,
        .home-products .product-card:hover,
        .stat-card:hover {
            transform: none !important;
            box-shadow: none !important;
        }
        .stat-card:hover { background-color: transparent; }
        .feature-icon,
        .feature-card:hover .feature-icon,
        .home-features .feature-card:hover .feature-icon {
            box-shadow: none;
            transform: none !important;
        }
        .product-card:hover .product-image-wrap img { transform: none !important; }

        /* Decorative grain/dot textures + infinite pulse → off */
        .hero-pattern, .dot-grid { background-image: none; }
        .hero-icon-pulse { animation: none; }
        .carousel-nav { backdrop-filter: none; -webkit-backdrop-filter: none; }

        /* Frosted navbar → flat white */
        nav[class*="backdrop-blur"] {
            backdrop-filter: none !important;
            -webkit-backdrop-filter: none !important;
            background-color: #fff !important;
        }

        /* One radius — pill CTAs/eyebrows + over-rounded panels → 0.5rem */
        .eyebrow { border-radius: 0.5rem; }
        a[class*="rounded-full"],
        button[class*="rounded-full"] { border-radius: 0.5rem !important; }
        [class*="rounded-2xl"],
        [class*="rounded-3xl"] { border-radius: 0.5rem !important; }
        .home-features .feature-card,
        .home-products .product-card,
        .home-cta .cta-panel { border-radius: 0.5rem; }

        /* Scroll-reveal sparkle → content renders immediately */
        .reveal, .reveal-left, .reveal-right, .reveal-scale {
            opacity: 1 !important;
            transform: none !important;
            transition: none !important;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <!-- Hero Carousel Section -->
    <?php if (isModuleEnabled('sec_hero')): ?>
    <?php if (!empty($carousel_slides)): ?>
    <section id="hero" class="home-hero carousel-container hero-gradient text-white relative overflow-hidden">
        <?php foreach ($carousel_slides as $index => $slide):
            // Per-slide overlay opacity (0-100 stored; 92 = original fixed value).
            $overlay_alpha = number_format((isset($slide['overlay_opacity']) ? (int)$slide['overlay_opacity'] : 92) / 100, 2);
        ?>
            <div class="carousel-slide <?php echo $index === 0 ? 'active' : ''; ?>" style="--slide-overlay: rgba(35, 51, 44, <?php echo $overlay_alpha; ?>); background-image: url('<?php echo htmlspecialchars_safe($slide['image_url']); ?>'); background-size: cover; background-position: center;">
                <div class="hero-pattern absolute inset-0 opacity-30"></div>
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 h-full flex items-center">
                    <div class="hero-inner grid grid-cols-1 w-full">
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
                            <?php if ($slide['button_text'] && $slide['button_link'] && isPageLinkEnabled($slide['button_link'])): ?>
                                <div class="flex flex-col sm:flex-row gap-4">
                                    <a href="<?php echo htmlspecialchars_safe($slide['button_link']); ?>" class="btn-glow bg-white text-[#23332c] hover:bg-[#eff4f1] font-semibold py-3.5 px-8 rounded-full text-center inline-flex items-center justify-center gap-2">
                                        <?php echo htmlspecialchars_safe($slide['button_text']); ?>
                                        <i class="fas fa-arrow-right text-xs"></i>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        
        <!-- Carousel Navigation -->
        <?php if (count($carousel_slides) > 1): ?>
            <button type="button" class="carousel-nav prev" onclick="changeSlide(-1)" aria-label="Previous slide">‹</button>
            <button type="button" class="carousel-nav next" onclick="changeSlide(1)" aria-label="Next slide">›</button>
            
            <!-- Carousel Indicators -->
            <div class="carousel-indicators">
                <?php foreach ($carousel_slides as $index => $slide): ?>
                    <button type="button" class="carousel-indicator <?php echo $index === 0 ? 'active' : ''; ?>" onclick="goToSlide(<?php echo $index; ?>)" aria-label="Go to slide <?php echo $index + 1; ?>" aria-current="<?php echo $index === 0 ? 'true' : 'false'; ?>"></button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
    <?php else: ?>
    <!-- Fallback Hero Section (if no carousel slides) -->
    <section id="hero" class="home-hero hero-gradient text-white relative overflow-hidden">
        <div class="hero-pattern absolute inset-0 opacity-30"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="hero-inner grid grid-cols-1">
                <div class="fade-in fade-in-delay-1">
                    <span class="eyebrow bg-white/15 text-white/90 mb-5">
                        <i class="fas fa-water text-xs"></i> Municipal Water Treatment
                    </span>
                    <h1 class="text-4xl lg:text-6xl font-bold mb-6 leading-tight mt-5"><?php echo htmlspecialchars_safe($hero_title); ?></h1>
                    <p class="text-lg lg:text-xl mb-8 text-white/85 max-w-xl"><?php echo htmlspecialchars_safe($hero_description); ?></p>
                    <div class="flex flex-col sm:flex-row gap-4">
                        <?php if (isPageLinkEnabled($hero_cta_primary_link)): ?>
                            <a href="<?php echo htmlspecialchars_safe($hero_cta_primary_link); ?>" class="btn-glow bg-white text-[#23332c] hover:bg-[#eff4f1] font-semibold py-3.5 px-8 rounded-full text-center inline-flex items-center justify-center gap-2">
                                <?php echo htmlspecialchars_safe($hero_cta_primary_text); ?>
                                <i class="fas fa-arrow-right text-xs"></i>
                            </a>
                        <?php endif; ?>
                        <?php if (isPageLinkEnabled($hero_cta_secondary_link)): ?>
                            <a href="<?php echo htmlspecialchars_safe($hero_cta_secondary_link); ?>" class="btn-ghost-light border border-white/40 bg-white/5 backdrop-blur-sm text-white hover:bg-white hover:text-[#23332c] font-semibold py-3.5 px-8 rounded-full text-center inline-flex items-center justify-center gap-2">
                                <i class="fas fa-download text-xs"></i>
                                <?php echo htmlspecialchars_safe($hero_cta_secondary_text); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>
    <?php endif; ?>

    <!-- Features Section -->
    <?php if (isModuleEnabled('sec_features')): ?>
    <section id="features" class="home-features py-20 lg:py-28 relative overflow-hidden bg-white">
        <!-- Subtle dot grid backdrop -->
        <div class="absolute inset-0 dot-grid opacity-40"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
            <div class="home-section-head mb-12 lg:mb-14 reveal">
                <span class="eyebrow mb-4"><i class="fas fa-star text-xs"></i> Why Us</span>
                <h2 class="font-bold text-[#23332c] mb-4 mt-3"><?php echo htmlspecialchars_safe($features_title); ?></h2>
                <p class="text-base lg:text-lg text-[#66746c]"><?php echo htmlspecialchars_safe($features_subtitle); ?></p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-5">
                <?php foreach ($features as $i => $feature): ?>
                    <div class="feature-card bg-white border border-[#e6ece8] h-full reveal <?php echo 'reveal-delay-' . ((($i % 4) + 1)); ?>">
                        <div class="flex items-start justify-between gap-4">
                            <?php if ($feature['icon']): ?>
                                <div class="feature-icon flex items-center justify-center text-white"><i class="<?php echo htmlspecialchars_safe($feature['icon']); ?> text-lg" aria-hidden="true"></i></div>
                            <?php endif; ?>
                            <span class="feature-index"><?php echo str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT); ?></span>
                        </div>
                        <h3 class="text-lg font-semibold text-[#23332c] mb-3"><?php echo htmlspecialchars_safe($feature['title']); ?></h3>
                        <p class="text-[#66746c] leading-relaxed text-sm"><?php echo htmlspecialchars_safe($feature['description']); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Stats Section -->
    <?php if (isModuleEnabled('sec_stats')): ?>
    <section id="stats" class="home-stats text-white py-16 lg:py-20 relative overflow-hidden">
        <div class="hero-pattern absolute inset-0 opacity-30"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-y-8">
                <?php foreach ($statistics as $i => $stat): ?>
                    <div class="stat-card reveal-scale <?php echo 'reveal-delay-' . ((($i % 4) + 1)); ?>">
                        <div class="stat-value text-4xl lg:text-5xl font-bold mb-3"><?php echo htmlspecialchars_safe($stat['value']); ?></div>
                        <div class="text-sm font-semibold text-[#b6db9a]"><?php echo htmlspecialchars_safe($stat['label']); ?></div>
                        <?php if ($stat['description']): ?>
                            <div class="text-sm text-white/70 mt-2 leading-relaxed"><?php echo htmlspecialchars_safe($stat['description']); ?></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Products Preview -->
    <?php if (isModuleEnabled('sec_products')): ?>
    <section id="products" class="home-products py-20 lg:py-28 relative overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="home-section-head mb-12 lg:mb-14 reveal">
                <span class="eyebrow mb-4"><i class="fas fa-cube text-xs"></i> Products</span>
                <h2 class="font-bold text-[#23332c] mb-4 mt-3"><?php echo htmlspecialchars_safe($products_title); ?></h2>
                <p class="text-base lg:text-lg text-[#66746c]"><?php echo htmlspecialchars_safe($products_subtitle); ?></p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <?php foreach ($featured_products as $i => $product): ?>
                    <div class="product-card bg-white border border-[#e6ece8] rounded-2xl h-full flex flex-col overflow-hidden <?php echo ['reveal-left', 'reveal', 'reveal-right'][$i % 3] . ' reveal-delay-' . (($i % 3) + 1); ?>">
                        <div class="product-image-wrap flex items-center justify-center p-8">
                            <?php if (!empty($product['image_url'])): ?>
                                <img src="<?php echo htmlspecialchars_safe($product['image_url']); ?>" alt="<?php echo htmlspecialchars_safe($product['name']); ?>" class="w-28 h-28 object-contain">
                            <?php else: ?>
                                <div class="w-20 h-20 rounded-2xl bg-white border border-[#e6ece8] flex items-center justify-center">
                                    <i class="fas fa-cube text-3xl text-[#60796e]"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="p-6 lg:p-8 flex-1 flex flex-col">
                            <span class="eyebrow self-start mb-4"><i class="fas fa-award text-xs"></i> Featured</span>
                            <h3 class="text-xl font-semibold text-[#23332c] mb-3"><?php echo htmlspecialchars_safe($product['name']); ?></h3>
                            <p class="text-[#66746c] mb-7 leading-relaxed text-sm flex-1"><?php echo htmlspecialchars_safe($product['description']); ?></p>
                            <?php if (isModuleEnabled('page_products')): ?>
                                <a href="products.php#<?php echo htmlspecialchars_safe($product['slug']); ?>" class="product-link inline-flex items-center justify-between gap-2 text-sm">
                                    View Details
                                    <i class="fas fa-arrow-right text-xs" aria-hidden="true"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php if (isModuleEnabled('page_products')): ?>
                <div class="text-center mt-12 reveal">
                    <a href="products.php" class="inline-flex items-center gap-2 text-[#3d7a66] hover:text-[#23332c] font-medium transition-colors">
                        View All Products
                        <i class="fas fa-arrow-right text-xs"></i>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- CTA Section -->
    <?php if (isModuleEnabled('sec_cta')): ?>
    <section id="cta" class="home-cta py-16 lg:py-24">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 reveal">
            <div class="cta-panel grid grid-cols-1 lg:grid-cols-[minmax(0,1.3fr)_minmax(280px,0.7fr)] gap-10 lg:gap-16 items-center text-white">
                <div>
                    <span class="eyebrow mb-5"><i class="fas fa-comments text-xs"></i> Get In Touch</span>
                    <h2 class="text-3xl lg:text-5xl font-bold mb-5 mt-3 leading-tight"><?php echo htmlspecialchars_safe($cta_title); ?></h2>
                    <p class="text-base lg:text-lg text-white/80 max-w-xl leading-relaxed"><?php echo htmlspecialchars_safe($cta_description); ?></p>
                </div>
                <div class="cta-actions flex flex-col gap-3">
                <?php if ($cta_button_1_text && isPageLinkEnabled($cta_button_1_link)): ?>
                    <a href="<?php echo htmlspecialchars_safe($cta_button_1_link); ?>" class="btn-glow bg-white text-[#23332c] hover:bg-[#eff4f1] font-semibold py-3.5 px-8 rounded-full inline-flex items-center justify-center gap-2">
                        <?php echo htmlspecialchars_safe($cta_button_1_text); ?>
                        <i class="fas fa-arrow-right text-xs"></i>
                    </a>
                <?php endif; ?>
                <?php if ($cta_button_2_text && isPageLinkEnabled($cta_button_2_link)): ?>
                    <a href="<?php echo htmlspecialchars_safe($cta_button_2_link); ?>" class="btn-ghost-light border border-white/40 bg-white/5 backdrop-blur-sm text-white hover:bg-white hover:text-[#23332c] font-semibold py-3.5 px-8 rounded-full inline-flex items-center justify-center gap-2">
                        <i class="fas fa-download text-xs"></i>
                        <?php echo htmlspecialchars_safe($cta_button_2_text); ?>
                    </a>
                <?php endif; ?>
                <?php if ($cta_button_3_text && isPageLinkEnabled($cta_button_3_link)): ?>
                    <a href="<?php echo htmlspecialchars_safe($cta_button_3_link); ?>" class="btn-ghost-light border border-white/40 bg-white/5 backdrop-blur-sm text-white hover:bg-white hover:text-[#23332c] font-semibold py-3.5 px-8 rounded-full inline-flex items-center justify-center gap-2">
                        <i class="fas fa-phone text-xs"></i>
                        <?php echo htmlspecialchars_safe($cta_button_3_text); ?>
                    </a>
                <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <?php include 'includes/footer.php'; ?>

    <?php if (isModuleEnabled('sec_hero') && !empty($carousel_slides) && count($carousel_slides) > 1): ?>
    <script>
        let currentSlide = 0;
        const slides = document.querySelectorAll('.carousel-slide');
        const indicators = document.querySelectorAll('.carousel-indicator');
        const totalSlides = slides.length;
        
        function showSlide(index) {
            // Hide all slides
            slides.forEach(slide => slide.classList.remove('active'));
            indicators.forEach(indicator => {
                indicator.classList.remove('active');
                indicator.setAttribute('aria-current', 'false');
            });
            
            // Show current slide
            if (slides[index]) {
                slides[index].classList.add('active');
                indicators[index].classList.add('active');
                indicators[index].setAttribute('aria-current', 'true');
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
        if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            setInterval(() => {
                changeSlide(1);
            }, 5000); // Change slide every 5 seconds
        }
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
